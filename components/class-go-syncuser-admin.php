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
		wp_register_script( 'go-usersync-admin-settings', plugins_url( '/js/go-usersync-admin-settings.js', __FILE__ ), array( 'jquery' ), $this->core->version, TRUE );

		$what_to_localize = array(
			'admin_ajax_url' => admin_url( '/admin-ajax.php' ),
			'go_syncuser_nonce' => wp_create_nonce( $this->core->slug ),
			'slug' => $this->core->slug,
		);

		wp_localize_script( 'go-usersync-admin-settings', 'go_usersync_ajax', $what_to_localize );

		wp_enqueue_script( 'go-usersync-admin-settings' );
	}//END admin_init

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

	/**
	 * admin ajax callback to save the debug option
	 */
	public function set_debug_ajax()
	{
		if ( ! current_user_can( 'manage_options' ) )
		{
			wp_send_json_error();
		}

		if ( ! $this->verify_nonce() )
		{
			wp_send_json_error( 'bad nonce!' );
		}

		if ( ! isset( $_GET['debug'] ) )
		{
			wp_send_json_error( 'missing query var' );
		}

		$this->core->set_debug( 'true' == $_GET['debug'], TRUE );

		wp_send_json_success();
	}//END set_debug_ajax

	/**
	 * verify the nonce
	 * @return bool TRUE if nonce if verified, FALSE if not
	 */
	public function verify_nonce()
	{
		if ( ! isset( $_GET[ $this->core->slug . '-nonce' ] ) )
		{
			return FALSE;
		}

		return wp_verify_nonce( $_GET[ $this->core->slug .'-nonce' ], $this->core->slug );
	}//end verify_nonce
}//END class