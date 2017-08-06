<?php
	defined( 'ABSPATH' ) || die( 'Sorry, but you cannot access this page directly.' );

	function is_user_login( $headers = null ) {
		global $_tc_session_status;
		if ( true == is_cli() ) {
			return true;
		}
		if ( true == $_tc_session_status ) {
			return $_tc_session_status;
		}
		$person = get_current_person( $headers );
		$_tc_session_status = ( is_a( $person, 'tcuser' ) && $person->in_group( tcuser::warehouse ) && $person->can_login_to_site() );
		return $_tc_session_status;
	}

	function route_logged_out_to_login( $path = '/', $query = array(), $method = 'GET', $headers = null ) {
		$logged = is_user_login( $headers );
		if ( false == $logged && '/login/' !== $path && ! beginning_matches( '/api/', $path ) && ! beginning_matches( '/ajax/', $path ) ) {
			$current = get_array_key( 'REQUEST_URI', $_SERVER, '/' );
			$rurl = sprintf( '/login/?%s', http_build_query( array(
				'return' => $current,
			) ) );
			header( sprintf( 'Location: %s', $rurl ) );
			exit();
		}
		if ( false == $logged && beginning_matches( '/api/', $path ) && ! beginning_matches( '/api/authorize/', $path ) ) {
			$errors = array();
			array_push( $errors, 'Invalid Session' );
			if ( ! is_zurmo_headers( $headers ) ) {
				array_push( $errors, 'Incomplete Authentication Information Received' );
			}
			else {
				array_push( $errors, 'Invalid or Expired Session' );
			}
			api_failure( null, 'Unauthorized', $errors, 401 );
		}
		if ( true == $logged && '/login/' == $path ) {
			header( 'Location: /' );
			exit();
		}
	}

	function route_login_page( $path = '/', $query = array(), $method = 'GET', $headers = null ) {
		if ( '/login/' == $path ) {
			html_success( 'login', 'Log In' );
		}
	}

	function ajax_authenticate( $data ) {
		$ldap = get_ldap_object();
		$valid = $ldap->validate_user( get_array_key( 'user', $data ), get_array_key( 'pass', $data ), 'person' );
		if ( ! is_a( $valid, 'tcuser' ) ) {
			switch ( $valid ) {
				case tcldap::no_connection:
					ajax_failure( 'Could not access authentication service' );
					break;
				case tcldap::bad_creds:
					ajax_failure( 'You are missing information' );
					break;
				case tcldap::no_such_user:
					ajax_failure( 'Invalid Credentials' );
					break;
				case tcldap::not_allowed_from_ip:
					ajax_failure( 'Access Denied' );
					break;
				default:
					ajax_failure( 'Invalid Credentials' );
					break;
			}
		}
		if ( ! $valid->can_login_to_site() ) {
			ajax_failure( 'Incorrect Credentials' );
		}
		if ( ! $valid->in_group( tcuser::warehouse ) ) {
			ajax_failure( 'No Access' );
		}
		tc_set_session( 'user', serialize( $valid ) );
		ajax_success( 'Please wait', get_array_key( 'redirect', $data, '/' ) );
	}

	function ajax_logout() {
		tc_unset_session( 'user' );
		ajax_success( 'Wait', '/login/' );
	}

	function get_current_person( $headers = null ) {
		if (
			! is_empty( get_array_key( 'x-auth-user', $headers ) )
			&& ! is_empty( get_array_key( 'x-auth-password', $headers ) )
		) {
			$ldap = get_ldap_object();
			$person = $ldap->validate_user( get_array_key( 'x-auth-user', $headers ), get_array_key( 'x-auth-ip', $headers, null ), 'person' );
		}
		else {
			$person = @unserialize( tc_get_session( 'user' ) );
		}
		return $person;
	}

	function get_current_user_info( $key = null ) {
		$person = get_current_person();
		$data = array(
			'fname' => get_object_property( 'cn', $person ),
			'lname' => get_object_property( 'sn', $person ),
			'title' => get_object_property( 'title', $person ),
			'name' => get_object_property( 'displayname', $person ),
			'department' => get_object_property( 'department', $person ),
			'company' => get_object_property( 'company', $person ),
			'user' => get_object_property( 'samaccountname', $person ),
			'userprinciple' => get_object_property( 'userprincipalname', $person ),
			'email' => get_object_property( 'mail', $person ),
			'lastlogontimestamp' => get_object_property( 'lastlogontimestamp', $person ),
			'isSysAdmin' => ( is_cli() || ( is_a( $person, 'tcuser' ) && true == $person->in_group( tcuser::sysadmin ) ) ),
		);
		if ( is_empty( $key ) ) {
			return $data;
		}
		return get_array_key( $key, $data );
	}

	function get_current_user_file_upload_path() {
		return FILE_UPLOAD_PATH;
	}