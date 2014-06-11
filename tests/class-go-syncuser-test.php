<?php

/**
 * GO_Sync_User unit tests
 */

require_once dirname( __DIR__ ) . '/go-syncuser.php';

class GO_Sync_User_Test extends GO_Sync_User_Test_Abstract
{
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
		// set up our own config data so we know what to expect
		remove_filter( 'go_config', array( go_config(), 'go_config_filter' ), 10, 2 );
		add_filter( 'go_config', array( $this, 'go_config_filter' ), 10, 2 );

		$this->assertFalse( has_action( 'wp_login' ) );

		// we need to manually call this from our phpunit environment until
		// i figure out how to get it called automatically by the test WP
		go_syncuser()->init();
		$config = go_syncuser()->config();

		$this->assertFalse( empty( $config ) );
		$this->assertTrue( has_action( 'wp_login' ) );

		$triggers = go_syncuser()->config( 'triggers' );
		$this->assertFalse( empty( $triggers ) );
		$this->assertTrue( isset( $triggers['wp_login'] ) );

		$this->assertEquals( NULL, go_syncuser()->config( 'nada' ) );

		global $wp_filter;
		$this->assertTrue( isset( $wp_filter['wp_login'] ) );
		$this->assertGreaterThan( 0, count( $wp_filter['wp_login'] ) );
	}//END test_config

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
						'user_var'    => 1,
						'user_token'  => 'object',
						'now'         => FALSE,
					),
				),
			);
		}//END if

		return $config;
	}//END go_config_filter
}// END class