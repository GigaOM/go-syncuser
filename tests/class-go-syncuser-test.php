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
}// END class