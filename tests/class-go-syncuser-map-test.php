<?php

/**
 * GO_Sync_User_Map unit tests
 */

require_once dirname( __DIR__ ) . '/go-syncuser.php';

class GO_Sync_User_Map_Test extends GO_Sync_User_Test_Abstract
{
	/**
	 * make sure we can get an instance of our plugin
	 */
	public function test_singleton()
	{
		$this->assertTrue( function_exists( 'go_syncuser_map' ) );
		$this->assertTrue( is_object( go_syncuser_map() ) );
	}//END test_singleton

	/**
	 * test the config file mapping functions
	 */
	public function test_map()
	{
		// set up our own config data so we know what to expect
		remove_filter( 'go_config', array( go_config(), 'go_config_filter' ), 10, 2 );
		add_filter( 'go_config', array( $this, 'go_config_filter' ), 'go-syncuser', 10, 2 );

		$users = $this->create_users();

		// test all the private mapping functions
		$this->assertEquals( 'contributor', go_syncuser_map()->map( $users[0], 'role' ) );
		$this->assertEquals( $users[1], go_syncuser_map()->map( $users[1], 'user_id' ) );
		$this->assertEquals( 'user_0@test.com', go_syncuser_map()->map( $users[0], 'email' ) );
		$this->assertEquals( 'baconissoyummy!', go_syncuser_map()->map( $users[0], 'meta0' ) );
		$this->assertEquals( 'Pork Belly Rulz!', go_syncuser_map()->map( $users[1], 'meta1' ) );
	}//END test_map

	/**
	 * create a couple of test users
	 *
	 * @return array list of test user ids
	 */
	private function create_users()
	{
		$users = array();

		$users[] = wp_insert_user( array( 'user_login' => 'user_0', 'user_email' => 'user_0@test.com' ) );
		$users[] = wp_insert_user( array( 'user_login' => 'user_1', 'user_email' => 'user_1@test.com' ) );

		// set some user meta, submeta and role
		update_user_meta( $users[0], 'meta0', 'baconissoyummy!' );
		update_user_meta( $users[1], 'meta1', array( 'name' => 'Pork Belly Rulz!' ) );

		$user = get_user_by( 'id', $users[0] );
		$user->set_role( 'contributor' );

		return $users;
	}//END create_users

	/**
	 * return custom config data for our tests
	 */
	public function go_config_filter( $config, $which )
	{
		if ( 'go-syncuser' == $which )
		{
			$config = array(
				'field_map' => array(
					'email' => array(
						'function' => array( go_syncuser_map(), 'user_meta' ),
						'args' => array(
							'user_email',
						),
					),
					'meta0' => array(
						'function' => array( go_syncuser_map(), 'user_meta' ),
						'args' => array(
							'meta0',
						),
					),
					'meta1' => array(
						'function' => array( go_syncuser_map(), 'user_meta_subkey' ),
						'args' => array(
							'meta1',
							'name',
						),
					),
					'role' => array(
						'function' => array( go_syncuser_map(), 'get_role' ),
					),
					'user_id' => array(
						'function' => array( go_syncuser_map(), 'get_user_id' ),
					),
				),
			);
		}//END if

		return $config;
	}//END go_config_filter
}// END class