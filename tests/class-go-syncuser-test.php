<?php

/**
 * GO_Sync_User unit tests
 */

require_once dirname( __DIR__ ) . '/go-syncuser.php';

class GO_Sync_User_Test extends WP_UnitTestCase
{
	/**
	 * this is run before each test* function in this class, to set
	 * up the environment each test runs in.
	 */
	public function setUp()
	{
		parent::setUp();
		$this->clear_caches();
	}//END setUp

	/**
	 * clean up stuff
	 */
	public function tearDown()
	{
		parent::tearDown();
		$this->clear_caches();
	}//END tearDown

	public function clear_caches()
	{
		$this->flush_cache();

		// clear cache if enabled
		$save_handler = ini_get( 'session.save_handler' );
		$save_path = ini_get( 'session.save_path' );

		try
		{
			if ( ! $save_path )
			{
				$save_path = 'tcp://127.0.0.1:11211';
			}

			$memcache = new Memcache;

			$save_path = str_replace( 'tcp://', '', $save_path );
			$save_path = explode( ':', $save_path );

			$memcache->connect( $save_path[0], $save_path[1] );
			$memcache->flush();
		}
		catch( Exception $e )
		{
			var_dump( $e );
		}//END catch
	}//END clear_caches

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
		add_filter( 'go_config', function( $config, $which )
					{
						if ( 'go-syncuser' == $which )
						{
							$config = array(
								'triggers' => array(
									'wp_login' => array(
										'user_var'    => 1,
										'user_token'  => 'object',
										'now'         => FALSE,
										'subscribe'   => TRUE,
									),
								),
							);
						}//END if
						return $config;
					}, 'go-syncuser', 10, 2 );

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
}// END class