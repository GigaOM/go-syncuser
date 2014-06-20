<?php

/**
 * GO_Sync_User unit tests
 */

require_once dirname( __DIR__ ) . '/go-syncuser.php';

class GO_Sync_User_Test extends GO_Sync_User_Test_Abstract
{
	// set up our own config data so we know what to expect
	public function setUp()
	{
		parent::setUp();
		remove_filter( 'go_config', array( go_config(), 'go_config_filter' ), 10, 2 );
		add_filter( 'go_config', array( $this, 'go_config_filter' ), 10, 2 );

		// add the hook to check if we get the expected callback
		add_action( 'go_syncuser_user', array( $this, 'go_syncuser_user' ), 10, 2 );

		// we need to manually call this from our phpunit environment until
		// i figure out how to get it called automatically by the test WP
		go_syncuser()->init();
		go_syncuser()->config();
	}//END setUp

	/**
	 * make sure we can get an instance of our plugin
	 */
	public function test_singleton()
	{
		$this->assertTrue( function_exists( 'go_syncuser' ) );
		$this->assertTrue( is_object( go_syncuser() ) );
	}//END test_singleton

	/**
	 * test the various return types of go_syncuser()->config()
	 */
	public function test_config()
	{
		$triggers = go_syncuser()->config( 'triggers' );
		$this->assertFalse( empty( $triggers ) );
		$this->assertTrue( isset( $triggers['wp_login'] ) );

		$this->assertEquals( NULL, go_syncuser()->config( 'nada' ) );

		global $wp_filter;
		$this->assertTrue( isset( $wp_filter['wp_login'] ) );
		$this->assertGreaterThan( 0, count( $wp_filter['wp_login'] ) );
	}//END test_config

	/**
	 * test one of our triggers. we can only test one trigger at a time
	 * because the go-syncuser action trigger can only be fired at most
	 * once per page load.
	 */
	public function test_wp_login_trigger()
	{
		$user_id = wp_insert_user( array( 'user_login' => 'user1' ) );

		$this->assertFalse( is_wp_error( $user_id ) );

		do_action( 'wp_login', 'user1', get_user_by( 'id', $user_id ) );

		$this->assertEquals( $user_id, $this->user_id );
		$this->assertEquals( 'update', $this->action );
	}//END test_wp_login_tigger

	/**
	 * test another one of our triggers
	 */
	public function test_delete_user_trigger()
	{
		// test the delete_user trigger
		$user_id = wp_insert_user( array( 'user_login' => 'user2' ) );
		$this->assertFalse( is_wp_error( $user_id ) );

		do_action( 'delete_user', $user_id, NULL );

		$this->assertEquals( $user_id, $this->user_id );
		$this->assertEquals( 'delete', $this->action );
	}//END test_delete_user_trigger

	/**
	 * callback when one of our configured triggers is fired
	 */
	public function go_syncuser_user( $user_id, $action )
	{
		// just save the results so the test function can check
		$this->user_id = $user_id;
		$this->action = $action;
	}//END go_syncuser_user

	/**
	 * return custom config data for our tests
	 */
	public function go_config_filter( $config, $which )
	{
		if ( 'go-syncuser' == $which )
		{
			$config = array(
				'triggers' => array(
					'wp_login' => array(
						'user_var' => 1,
						'now'      => TRUE,
						'action'   => 'update',
					),
					'delete_user' => array(
						'user_var' => 0,
						'now'      => TRUE,
						'action'   => 'delete',
					),
				),
			);
		}//END if

		return $config;
	}//END go_config_filter
}// END class