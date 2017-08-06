<?php
	defined( 'ABSPATH' ) || die( 'Sorry, but you cannot access this page directly.' );

	function initialize_routing() {
		global $_headers;
		if ( is_cli() ) {
			$vars = getopt( '', array( 'path:', 'query:' ) );
			$current_path = get_array_key( 'path', $vars, '/' );
			$current_query = get_array_key( 'query', $vars, '' );
			if ( is_empty( get_array_key( 'path', $vars ) ) ) {
				api_failure( null, 'You are missing the "path" parameter' );
			}
		}
		else {
			$current_path = ( ! is_empty( get_array_key( 'SCRIPT_URL', $_SERVER ) ) ) ? get_array_key( 'SCRIPT_URL', $_SERVER ) : get_array_key( 'REDIRECT_URL', $_SERVER );
			$current_query = get_array_key( 'QUERY_STRING', $_SERVER, http_build_query( $_GET ) );
		}
		$cd = array();
		if ( ! is_empty( $current_query ) ) {
			parse_str( $current_query, $cd );
		}
		if ( true == MAINTENANCE && ! is_cli() ) {
			if ( beginning_matches( '/api/', $current_path ) || beginning_matches( '/ajax/', $current_path ) || is_cli() ) {
				api_failure( null, 'Maintenance Mode', array( 'Site in Maintenance Mode' ) );
			}
			else {
				html_failure( 'maintenance', 'Maintenance Mode', array( 'This application is currently undergoing maintenance. Please try again later.' ), 404 );
			}
		}
		do_action( 'pre-route', array(
			'path' => $current_path,
			'query' => parse_http_method_data( ( is_cli() ) ? 'CLI' : strtoupper( get_array_key( 'REQUEST_METHOD', $_SERVER, 'GET' ) ) ),
			'method' => ( is_cli() ) ? 'CLI' : strtoupper( get_array_key( 'REQUEST_METHOD', $_SERVER, 'GET' ) ),
			'headers' => $_headers,
		) );
		switch ( strtoupper( get_array_key( 'REQUEST_METHOD', $_SERVER, 'GET' ) ) ) {
			case 'POST':
				do_action( 'route', array(
					'path' => $current_path,
					'query' => parse_http_method_data( 'POST' ),
					'method' => 'POST',
					'headers' => $_headers,
				) );
				break;

			case 'PUT':
				do_action( 'route', array(
					'path' => $current_path,
					'query' => parse_http_method_data( 'PUT' ),
					'method' => 'PUT',
					'headers' => $_headers,
				) );
				break;

			case 'DELETE':
				do_action( 'route', array(
					'path' => $current_path,
					'query' => parse_http_method_data( 'DELETE' ),
					'method' => 'DELETE',
					'headers' => $_headers,
				) );
				break;

			case is_cli():
				do_action( 'route', array(
					'path' => $current_path,
					'query' => $cd,
					'method' => 'CLI',
					'headers' => $_headers,
				) );
				break;

			default:
				do_action( 'route', array(
					'path' => $current_path,
					'query' => parse_http_method_data( 'GET' ),
					'method' => 'GET',
					'headers' => $_headers,
				) );
				break;
		}
		if ( beginning_matches( '/api/', $current_path ) || beginning_matches( '/ajax/', $current_path ) || is_cli() ) {
			api_failure( null, 'Invalid Request', array( 'You have requested an invalid endpoint' ) );
		}
		else {
			html_failure( 'error', 'Page Not Found', array( 'The page you requested could not be found' ), 404 );
		}
		api_failure( null, 'Invalid Request', array( 'You have requested an invalid endpoint' ) );
	}

	function tc_get_path() {
		if ( is_cli() ) {
			$vars = getopt( '', array( 'path:', 'query:' ) );
			$path = get_array_key( 'path', $vars, '/' );
		}
		else {
			$path = ( ! is_empty( get_array_key( 'SCRIPT_URL', $_SERVER ) ) ) ? get_array_key( 'SCRIPT_URL', $_SERVER ) : get_array_key( 'REDIRECT_URL', $_SERVER );
		}
		return $path;
	}