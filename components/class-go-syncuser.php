<?php
/**
 * core go-syncuser class
 */
class GO_Sync_User
{

}//END class


function go_syncuser()
{
	global $go_syncuser;
	if ( ! $go_syncuser )
	{
		$go_syncuser = new GO_Sync_User;
	}

	return $go_syncuser;
}//END go_syncuser