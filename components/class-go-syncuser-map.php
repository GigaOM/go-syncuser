<?php
/**
 * The GO_Sync_User_Map class defines some utility functions the can be
 * called when sync'ing users.
 *
 * The one constraint here is that the first parameter of these functions
 * must be a WP_User object.
 */
class GO_Sync_User_Map
{
    /**
     * Call a function mapped to a field specified in $config and return
     * the result of the function call. The configuration is an array
     * containing 'function', 'args', and optionally 'type' (of the returned
     * value). e.g.:
     *
     * array(
     *   'function' => array( go_syncuser_map(), 'user_meta_subkey' ),
     *   'args' => array(
     *       'go_user_recurly_subscription',
     *       'sub_current_period_ends_at',
     *   ),
     *   'type' => 'date',
     * )
     *
     * @param $user WP_User User being synchronized
     * @param $config array Configuration for the map field
	 */
	public function map_field( $user, $config )
	{
		// build the arguments array
		$args = array();
		if ( isset( $config['args'] ) )
		{
			$args = is_array( $config['args'] ) ? $config['args'] : array( $config['args'] );
		}

		array_unshift( $args, $user ); // add $user as the first arg

		$field_value = call_user_func_array( $config['function'], $args );

		// check if we need to convert the return value to a different type
		if ( isset( $config['type'] ) )
		{
			switch ( $config['type'] )
			{
				case 'date':
					// check to see if the field is a unix timestamp
					// methodology from: http://stackoverflow.com/questions/3377537/checking-if-a-string-contains-an-integer/3377560#3377560
					if ( (string) (int) $field_value == $field_value )
					{
						$utime = $field_value;
					}
					else
					{
						$utime = strtotime( $field_value );
					}

					$field_value = ( $utime ) ? date( 'Y-m-d H:i:s', $utime ) : '';
					break;

				case 'int':
					$field_value = intval( $field_value );
					break;
			}//END switch
 		}//END if

		return $field_value;
	}//END map_field

	/**
	 * get a WP_User member var or a user meta. if $field is a member var
	 * of WP_User, then return its value, else treat it as a user meta
	 * key and return the user meta value.
	 *
	 * @param WP_User $user the user whose meta value we're getting
	 * @param string $field the user meta field to get
	 * @return the user or user meta value specified by $field, or an empty
	 *  string if not found.
	 */
	public function user_meta( $user, $field )
	{
		// is the field in the $user object?
		if ( isset( $user->$field ) )
		{
			return $user->$field;
		}

		// try to get the value from user_meta
		if ( $general_meta = get_user_meta( $user->ID, $field, TRUE ) )
		{
			return $general_meta;
		}

		// better to map the value to be blank than potentially insert a
		// literal FALSE
		return '';
	}//END user_meta

	/**
	 * get a "sub meta" value, where $key indexes a value in a user meta
	 * that's an associative array.
	 *
	 * @param WP_User $user the user whose meta value we're getting
	 * @param string $field the user meta field to get
	 * @param string $subkey key of the associative array of $field
	 * @return the user meta value specified by $field, or an empty
	 *  string if not found.
	 */
	public function user_meta_subkey( $user, $field, $subkey )
	{
		$meta = $this->user_meta( $user, $field );
		if ( $meta && isset( $meta[ $subkey ] ) )
		{
			return $meta[ $subkey ];
		}

		return '';
	}//END user_meta_subkey

	/**
	 * Get the first role slug name for a user
	 * logic borrowed from wp-admin/user-edit.php
	 * @param string $subkey key of the associative array of $field
	 * @return the first user role or an empty string if the user has no role
	 */
	public function get_role( $user )
	{
		global $wp_roles;

		$user_roles = array_intersect( array_values( $user->roles ), array_keys( $wp_roles->roles ) );

		if ( ! empty( $user_roles ) )
		{
			$user_role  = array_shift( $user_roles );
		}
		else
		{
			$user_role = '';
		}
		return $user_role;
	}//END get_role

	/**
	 * this seemingly brain-dead function is necessary to massage the
	 * first parameter passed in by map_field() (WP_User) into the
	 * user id.
	 *
	 * @param WP_User $user a WP_User object
	 * @return int the wp user id
	 */
	public function get_user_id( $user )
	{
		return is_object( $user ) ? $user->ID : $user;
	}//END get_user_id
}//END class

/**
 * GO_Sync_User_Map class gets a singleton function
 */
function go_syncuser_map()
{
	global $go_syncuser_map;

	if ( ! isset( $go_syncuser_map ) )
	{
		$go_syncuser_map = new GO_Sync_User_Map();
	}

	return $go_syncuser_map;
}//END go_syncuser_map
