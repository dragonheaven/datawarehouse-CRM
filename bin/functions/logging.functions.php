<?php
	defined( 'ABSPATH' ) || die( 'Sorry, but you cannot access this page directly.' );

	function add_log_entry( $logType, array $data ) {
		return true;
	}

	function add_route_log_entry( $path = '/', $query = array(), $method = 'GET', $headers = array() ) {
		return add_log_entry( 'access', array(
			'path' => $path,
			'query' => $query,
			'method' => $method,
			'headers' => $headers,
		) );
	}

	function get_add_log_id() {
		return tc_get_cookie( '_add_log_id' );
	}