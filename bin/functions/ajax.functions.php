<?php
	defined( 'ABSPATH' ) || die( 'Sorry, but you cannot access this page directly.' );

	function handle_ajax_request( $path = '/', $query = array(), $method = 'GET', $headers = array() ) {
		if ( '/ajax/' == $path && 'POST' == $method ) {
			header( 'Content-Type: application/json' );
			global $_post;
			$action = get_array_key( 'ajax-action', $_post );
			$data = get_array_key( 'data', $_post, array() );
			if ( ! is_array( $data ) ) {
				parse_str( $data, $data );
			}
			$called_ajax_function = sprintf( 'ajax_%s', str_replace( '-', '_', $action ) );
			$called_api_function = sprintf( 'ajax_%s', str_replace( '-', '_', $action ) );
			if ( 'authenticate' !== $action && function_exists( 'is_user_login' ) ) {
				if ( ! is_user_login() ) {
					ajax_failure( 'Unauthorized' );
				}
			}
			if ( is_empty( $action ) ) {
				ajax_failure( 'No Action Requested' );
			}
			else if ( function_exists( $called_ajax_function ) ) {
				call_user_func( $called_ajax_function, $data );
				ajax_failure( sprintf( 'Nothing Happened' ) );
			}
			else if ( function_exists( $called_api_function ) ) {
				call_user_func( $called_api_function, $data );
				ajax_failure( sprintf( 'Nothing Happened' ) );
			}
			else {
				ajax_failure( sprintf( 'No such function "%s"', $called_api_function ) );
			}
			exit();
		}
	}