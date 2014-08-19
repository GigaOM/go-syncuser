<?php
/**
 * Admin-related functionality
 */
class GO_Sync_User_Admin
{
	private $core = NULL;

	/**
	 * constructor
	 *
	 * @param GO_Sync_User $core the GO_Sync_User object that contains
	 *  this instance
	 */
	public function __construct( $core )
	{
		$this->core = $core;

		add_action( 'admin_init', array( $this, 'admin_init' ) );
		add_action( 'admin_menu', array( $this, 'admin_menu' ) );

		// admin ajax to set the debug option
		add_action( 'wp_ajax_go_syncuser_set_debug', array( $this, 'set_debug_ajax' ) );
	}//END __construct

	/**
	 * register our admin dash hooks
	 */
	public function admin_init()
	{
		add_action( 'wp_ajax_go_syncuser_user_update_sync', array( $this, 'user_update_sync_ajax' ) );

		wp_register_script( 'go-usersync-admin-settings', plugins_url( '', __FILE__ ) . '/js/go-usersync-admin-settings.js', array( 'jquery' ), $this->core->version, TRUE );
		wp_localize_script( 'go-usersync-admin-settings', 'go_usersync_ajax', array( 'admin_ajax_url' => admin_url( '/admin-ajax.php' ) ) );

		wp_enqueue_script( 'go-usersync-admin-settings' );
	}//END admin_init

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

		if ( ! ( $user = $this->core->sanitize_user( wp_filter_nohtml_kses( $_REQUEST[ 'go_syncuser_user_id' ] ) ) ) )
		{
			echo '<p class="error">Missing user id</p>';
			die;
		}

		do_action( 'go_syncuser_user', $user->ID, 'update' );

		die;
	}//END user_update_sync_ajax

	/**
	 * Add a menu item for the settings page
	 */
	public function admin_menu()
	{
		add_submenu_page( 'plugins.php', 'GO Sync User', 'GO Sync User', 'manage_options', 'go-syncuser-admin-menu', array( $this, 'admin_settings_page' )  );
	} // END admin_menu

	/**
	 * Output the admin settings page
	 */
	public function admin_settings_page()
	{
		require __DIR__ . '/templates/admin-settings.php';
	}//END admin_settings_page

	public function set_debug_ajax()
	{
		if ( ! current_user_can( 'manage_options' ) )
		{
			die;
		}

		if ( ! isset( $_GET['debug'] ) )
		{
			die( 'missing query var' );
		}

		$this->core->debug( 'true' == $_GET['debug'] );

		die( 'ok' );
	}//END set_debug_ajax
}//END class