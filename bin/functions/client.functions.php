<?php
	defined( 'ABSPATH' ) || die( 'Sorry, but you cannot access this page directly.' );

	function make_side_nav_link( $title, $path = '/', $query = array(), $pattern = '/', $print = true, $hoverover = null ) {
		$current_path = get_request_path();
		$active = ( $current_path == $path || preg_match( fix_pattern_for_regex( $pattern ), $current_path ) !== 0 ) ? 'active' : '';
		$url = make_absolute_url( $path, $query );
		$html = sprintf( '<li class="%s" title="%s" data-placement="right"><a href="%s">%s</a>', $active, $hoverover, $url, $title );
		if ( true == $print ) {
			echo $html;
		}
		return $html;
	}

	function fix_pattern_for_regex( $input ) {
		$input = str_replace( '/', '\/', $input );
		$input = sprintf( '/%s/', $input );
		return $input;
	}

	function get_request_method() {
		global $_server;
		return ( 'cli' === php_sapi_name() || ! array_key_exists( 'REQUEST_METHOD', $_server ) ) ? 'CLI' : strtoupper( $_server['REQUEST_METHOD'] );
	}

	function get_request_path() {
		global $_server;
		$path = '/';
		if ( 'CLI' === get_request_method() ) {
			$vars = getopt( '', array( 'path:' ) );
			if ( array_key_exists( 'path', $vars ) && ! is_empty( $vars['path'] ) ) {
				$path = $vars['path'];
			}
		}
		else {
			$raw_uri = ( array_key_exists( 'REQUEST_URI', $_server ) ) ? $_server['REQUEST_URI'] : '/';
			if ( false !== strpos( $raw_uri , '?' ) ) {
				list( $path, $query ) = explode( '?', $raw_uri );
			}
			else {
				$path = $raw_uri;
			}
		}
		return $path;
	}

	function make_absolute_url( $path = '/', $query = array() ) {
		return sprintf(
			'%s%s%s',
			get_base_url(),
			$path,
			( can_loop( $query ) ) ? sprintf( '?%s', http_build_query( $query ) ) : ''
		);
	}

	function get_base_url( $add_slash = false ) {
		global $_server;
		$servername = get_array_key( 'SERVER_NAME', $_server, 'localhost' );
		if (
			( ! is_ssl() && 80 !== tc_get_request_port() )
			|| ( is_ssl() && 443 !== tc_get_request_port() )
		) {
			$servername .= sprintf( ':%d', tc_get_request_port() );
		}
		return sprintf(
			'%s://%s%s',
			( true == is_ssl() ) ? 'https' : 'http',
			$servername,
			( true === $add_slash ) ? '/' : ''
		);
	}

	function path_matches_pattern( $path = '/', $pattern = '([^/]*)' ) {
		return ( preg_match( fix_pattern_for_regex( $pattern ), $path ) !== 0 );
	}

	function return_path_vars( $path = '/', $pattern = '([^/]*)', $keys = array() ) {
		$return = array();
		$count = preg_match( fix_pattern_for_regex( $pattern ), $path, $matches );
		if ( intval( $count ) > 0 ) {
			array_shift( $matches );
			if ( count( $keys ) == count( $matches ) ) {
				$return = array_combine( $keys, $matches );
			}
			else {
				$return = $matches;
			}
		}
		return $return;
	}

	function tc_get_client_js_data( $template ) {
		global $_tc_countries;
		$return = array(
			'debug' => ( true == DEBUG ),
			'countries' => $_tc_countries,
			'webapp' => is_webapp(),
		);
		if ( true == DEBUG ) {
			$return['template'] = $template;
		}
		if ( function_exists( 'tc_get_client_websocket_host' ) ) {
			$return['websockethost'] = tc_get_client_websocket_host();
		}
		if ( function_exists( 'tc_get_additional_lead_graph_series_info' ) ) {
			$return['leadgraphs'] = tc_get_additional_lead_graph_series_info();
		}
        if ( function_exists( 'tc_get_results_from_saved_queries' ) ) {
            $return['savedqueryresults'] = array();
        }
         if ( function_exists( 'tc_get_sortable_columns' ) ) {
            $return['sortablecolumns'] = tc_get_sortable_columns();
        }
		return $return;
	}