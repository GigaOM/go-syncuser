<?php
/**
 * core go-syncuser class
 */
class GO_Sync_User
{
	public $slug = 'go-syncuser';
	public $action_hooks = array();     // action hook names and params
	public $did_action_hooks = array(); // track which actions we have called

	/**
	 * constructor
	 */
	public function __construct()
	{
		add_action( 'init', array( $this, 'init' ) );
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
	}//END init

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

		do_action( 'go_syncuser_user', $user->ID, $options, $args );

		// in case this was called on a filter, return the first argument
		return $args[0];
	}//END action_trigger_handler
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