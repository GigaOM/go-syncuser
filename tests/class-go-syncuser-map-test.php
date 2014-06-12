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
}// END class