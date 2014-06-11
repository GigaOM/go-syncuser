<?php
/**
 * core go-syncuser class
 */
class GO_Sync_User
{
	public $slug = 'go-syncuser';

	// user meta keys
	public $umkey_cronned = 'go_syncuser_cronned';

	private $action_hooks = array();     // action hook names and params
	private $did_action_hooks = array(); // track which actions we have called
	private $suspend_triggers = FALSE;

	/**
	 * constructor
	 */
	public function __construct()
	{
		add_action( 'init', array( $this, 'init' ) );

		// cron callbacks
		add_filter( 'cron_schedules', array( $this, 'cron_schedules' ) );
		add_action( 'go_syncuser_cron', array( $this, 'sync_users_cron' ) );

		// install when the plugin is activated
		register_activation_hook( dirname( __DIR__ ) . '/go-syncuser.php', array( $this, 'cron_register' ) );

		// and uninstall when the plugin is deactivated
		register_deactivation_hook( dirname( __DIR__ ) . '/go-syncuser.php', array( $this, 'cron_deregister' ) );

	}//END __construct

	/**
	 * called when WP is ready to initialize this plugin
	 */
	public function init()
	{
		// register all the action triggers defined in our config file
		if ( ( $triggers = $this->config( 'triggers' ) ) && is_array( $triggers ) )
		{
			foreach ( $this->config( 'triggers' ) as $hook => $parameters )
			{
				array_unshift( $parameters, $hook );
				call_user_func_array( array( $this, 'add_action_trigger_handler' ), $parameters );
			}//END foreach
		}//END if

		if ( is_admin() )
		{
			add_action( 'admin_init', array( $this, 'admin_init' ) );
		}
	}//END init

	/**
	 * init hooks in admin mode
	 */
	public function admin_init()
	{
		// Ajax handlers
		add_action( 'wp_ajax_go_syncuser_user_update_sync', array( $this, 'user_update_sync_ajax' ) );
	}//END admin_init

	/**
	 * Set up a custom cron interval based on the 'cron_interval_in_secs'
	 * config file setting.
	 *
	 * @param array $cron_times current cron time intervals
	 * @return array the input with the new cron time interval added
	 */
	public function cron_schedules( $cron_times )
	{
		if ( ! ( $period = $this->config( 'cron_interval_in_secs' ) ) )
		{
			return $cron_times;
		}

		$cron_times[ 'go_syncuser_interval' ] = array(
			'interval' => intval( $period ),
			'display' => 'Plugin Configured: Every ' . $period . ' seconds.',
		);

		return $cron_times;
	}//END cron_schedules

	/**
	 * we get a list of users that need to be sync'ed and call our
	 * action on each user, with the corresponding 'add', 'update', or
	 * 'delete' action.
	 */
	public function sync_users_cron()
	{
		$user_ids = $this->get_cronned_users();

		if ( ! is_array( $user_ids ) )
		{
			return;
		}

		// debugging info when run/tested on the command line
		if ( 'cli' == php_sapi_name() )
		{
			echo 'Running actions on ' . count( $user_ids ) ." users\n";
		}

		// turn off triggers while running
		$this->suspend_triggers = TRUE;

		foreach ( $user_ids as $user_id )
		{
			// we only cron user updates, not deletes
			do_action( 'go_syncuser_user', $user_id, 'update' );

			// delete the user meta we use to identify users to sync
			delete_user_meta( $user_id, $this->umkey_cronned );

			if ( 'cli' == php_sapi_name() )
			{
				echo "Update user $user_id\n";
			}
		}//END foreach

		// turn triggers back on
		go_mcsync()->suspend_triggers = FALSE;
	}//END sync_users_cron

	/**
	 * activate our cron hook when the plugin is activated
	 */
	public function cron_register()
	{
		if ( ! wp_next_scheduled( 'go_syncuser_cron' ) )
		{
			wp_schedule_event( time(), 'go_syncuser_interval', 'go_syncuser_cron' );
		}
	}//END cron_register

	/**
	 * clear out our cron hook when the plugin is deactivated
	 */
	public function cron_deregister()
	{
		wp_clear_scheduled_hook( 'go_syncuser_cron' );
	}//END cron_deregister

	/**
	 * Ajax callback to sync a user immediately. The user id is passed in
	 * via a query var 'go_syncuser_user_id'. We always invoke the 
	 * user sync action with the action type 'update' when sync'ed by
	 * this ajax function.
	 */
	public function user_update_sync_ajax()
	{
		// only allowed for people who can edit users
		if ( ! current_user_can( 'edit_users' ) )
		{
			die;
		}

		if ( ! ( $user = $this->sanitize_user( wp_filter_nohtml_kses( $_REQUEST[ 'go_syncuser_user_id' ] ) ) ) )
		{
			echo '<p class="error">Missing user id</p>';
			die;
		}

		do_action( 'go_syncuser_user', $user->ID, 'update' );

		die;
	}//END user_update_sync_ajax

	/**
	 * retrieve the cronned users from the database
	 *
	 * @return array An array of users to run do our go_syncuser_user action on
	 */
	public function get_cronned_users()
	{
		return get_users( array(
			'meta_key' => $this->umkey_cronned,
			'meta_value' => '1',
			'orderby' => 'ID',
			'order' => 'DESC',
			'number' => '23',
			'fields' => 'ID',
		) );
	}//END get_cronned_users

	/**
	 * Singleton for our configuration
	 *
	 * @param string $key if not NULL we'll return the configuration for this
	 *  key
	 * @return if $key is not NULL, we'll return its configuration value if
	 *  is it set, or NULL if not. If $key is NULL then we'll return all the
	 *  configuration array.
	 */
	public function config( $key = NULL )
	{
		if ( ! isset( $this->config ) || ! $this->config )
		{
			$this->config = apply_filters(
				'go_config',
				NULL,
				$this->slug
			);
		}//END if

		if ( $key )
		{
			return isset( $this->config[ $key ] ) ? $this->config[ $key ] : NULL;
		}

		return $this->config;
	}//END config

	/**
	 * Register named action/filter hooks to trigger synchronization of a user
	 * Triggers are defined in the config file. See usage example in $this->default_triggers
	 *
	 * @param string $hook_name name of the hook to trigger off of
	 * @param int $user_var index to the user parameter in the hook's callback arguments list
	 * @param string $user_token how is the user tokenized? 'id', 'object', 'email', 'username', etc.
	 * @param boolean $now call the action hook now or queue it up for cron
	 * @param $action string what type of action trigger this sync? one of
	 *  'add', 'update' or 'delete'
	 */
	public function add_action_trigger_handler( $hook_name, $user_var, $user_token = 'id', $now = FALSE, $action = 'update' )
	{
		// register the hook internally
		$this->action_hooks[ $hook_name ] = (object) array(
			'user_var'   => $user_var,
			'user_token' => $user_token,
			'now'        => $now,
			'action'     => $action,
		);

		// register the hook with WordPress. we set the number of args to 5
		// in case the user configures an action that takes that many args.
		// but so far the most args needed are 4.
		add_action( $hook_name, array( $this, 'action_trigger_handler' ), 10, 5 );
	}//END add_action_trigger_handler

	/**
	 * Generic hook handler is called for defined action/filter hooks to
	 * extract the user info and call the sync user action
	 */
	public function action_trigger_handler()
	{
		// get all the args provided from the hook call
		$args = func_get_args();

		// what hook are we working on?
		$hook_name = current_filter();

		// allow us to suspend triggers during cron and internal updates
		if ( $this->suspend_triggers )
		{
			// in case this was called on a filter, return the first argument
			return $args[0];
		}//END if

		if ( ! isset( $this->action_hooks[ $hook_name ] ) )
		{
			// nothing to do here, but...
			// return the first argument anyway, in case this was called on a filter
			return $args[0];
		}//END if

		// if we've already executed this hook during this page load, we can bail
		if ( isset( $this->did_action_hook[ $hook_name ] ) )
		{
			return $args[0];
		}//END if

		// only allow running this hook once per page load. marking it as done
		$this->did_action_hook[ $hook_name ] = TRUE;

		// suspend triggers so we don't re-hook while executing
		$this->suspend_triggers = TRUE;

		// get an easy handle on the vars for this hook
		$options = $this->action_hooks[ $hook_name ];

		// get the variable that represents the user
		$user = $args[ $options->user_var ];

		// check if that var is a user object or if we need to load one
		if ( ! $user instanceof WP_User )
		{
			// Support objects that contain user_id
			if ( is_object( $user ) && isset ( $user->user_id ) )
			{
				$user = get_user_by( 'id', $user->user_id );
			}
			else
			{
				$user = get_user_by( $options->user_token, $user );
			}
		}// END if

		// call the action now or batch it up for later? we cannot sync
		// a deleted user asynchronously later, so we must sync those users
		// now regardless of the value of $options->now
		if ( $options->now || 'delete' == $options->action )
		{
			do_action( 'go_syncuser_user', $user->ID, $options->action );
		}
		else
		{
			$this->queue_user_update( $user );
		}

		// in case this was called on a filter, return the first argument
		return $args[0];
	}//END action_trigger_handler

	/**
	 * save a user to be sync'ed by wp cron later
	 *
	 * @param object $user The user to be saved
	 */
	public function queue_user_update( $user )
	{
		$user = $this->sanitize_user( $user );

		// make sure we found a user
		if ( empty( $user ) || is_wp_error( $user ) )
		{
			apply_filters( 'go_slog', 'go-syncuser', __FUNCTION__ . ': No user found for input value, got ' . var_export( $user, TRUE ), '' );
			return FALSE;
		}//END if

		// turn off triggers while running
		$this->suspend_triggers = TRUE;

		update_user_meta( $user->ID, $this->umkey_cronned, 1 );

		// turn triggers back on
		$this->suspend_triggers = FALSE;
	}//END queue_user_update

	/**
	 * Turn a user ID, email address, or object into a proper user object
	 */
	public function sanitize_user( $user_input )
	{
		if ( is_object( $user_input ) )
		{
			if ( isset( $user_input->ID ) )
			{
				return get_userdata( (int) $user_input->ID );
			}

			return FALSE;
		}//END if
		elseif ( is_numeric( $user_input ) )
		{
			return get_userdata( (int) $user_input );
		}
		elseif ( is_string( $user_input ) )
		{
			return get_user_by( 'email', $user_input );
		}

		return FALSE;
	}//END sanitize_user
}//END class

/**
 * @return the singleton instance of GO_Sync_User
 */
function go_syncuser()
{
	global $go_syncuser;
	if ( ! $go_syncuser )
	{
		$go_syncuser = new GO_Sync_User;
	}

	return $go_syncuser;
}//END go_syncuser