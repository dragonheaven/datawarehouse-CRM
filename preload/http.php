<?php
	defined( 'ABSPATH' ) || die( 'Sorry, but you cannot access this page directly.' );

	if ( ! function_exists( 'parse_http_method_data' ) ) {
		function parse_http_method_data( $method ) {
			global $phpi;
			$method = strtoupper( $method );
			if ( ( 'PUT' == $method || 'POST' == $method ) && can_loop( $_POST ) ) {
				return $_POST;
			}
			if ( ( 'DELETE' == $method || 'GET' == $method ) && can_loop( $_GET ) ) {
				return $_GET;
			}
			$return = array();
			if ( is_empty( $phpi ) ) {
				$input = file_get_contents( 'php://input' );
				$phpi = $input;
			}
			else {
				$input = $phpi;
			}
			$rows = explode( "\r\n-", $input );
			$querystring = '';
			if ( ! array_key_exists( 'CONTENT_TYPE', $_SERVER ) ) {
				$_SERVER['CONTENT_TYPE'] = null;
			}
			if ( false !== strpos( $_SERVER['CONTENT_TYPE'], 'form-urlencoded' ) ) {
				parse_str( $input, $return );
			}
			else if ( false !== strpos( $_SERVER['CONTENT_TYPE'], 'text/plain' ) ) {
				parse_str( $input, $return );
			}
			else if ( false !== strpos( $_SERVER['CONTENT_TYPE'], 'application/json' ) ) {
				$return = json_decode( $input, true );
			}
			else if ( false !== strpos( $_SERVER['CONTENT_TYPE'], '/xml' ) ) {
				try {
					$e = simplexml_load_string( $input );
					$return = json_decode( json_encode( $e ), true );
				}
				catch ( Exception $e ) {

				}
			}
			else if ( can_loop( $rows ) ) {
				foreach ( $rows as $row ) {
					if ( ! is_empty( $row ) ) {
						if ( false !== strpos( $row, "\r\n\r\n" ) ) {
							list( $uglyname, $value ) = explode( "\r\n\r\n", $row );
							list( $boundary, $info ) = explode( "\r\n", $uglyname );
							if ( ! is_empty( $info ) && ! is_null( $value ) ) {
								list( $chuff, $rawname ) = explode( 'name=', $info );
								$name = str_replace( '"', '', $rawname );
								$name = str_replace( "'", '', $name );
								$querystring .= '&' . $name . '=' . $value;
								$return[ $name ] = $value;
							}
						}
					}
				}
				parse_str( $querystring, $return );
			}
			return $return;
		}
	}

	if ( ! function_exists( 'parse_http_headers' ) ) {
		function parse_http_headers() {
			if ( function_exists( 'getallheaders' ) ) {
				return getallheaders();
			}
			$return = array();
			foreach ( $_SERVER as $key => $value ) {
				if ( substr( strtoupper( $key ), 0, 5 ) == 'HTTP_' ) {
					$key = substr( $key, 0, 5 );
					$key = str_replace( '_', ' ', $key );
					$key = ucwords( strtolower( $key ) );
					$key = str_replace( ' ', '-', $key );
					$return[ $key ] = $value;
				}
			}
			return $return;
		}
	}

	function get_request_ip() {
		switch ( true ) {
			case ( array_key_exists( 'testingIPaddress', $_GET ) && strlen( filter_var( $_GET['testingIPaddress'], FILTER_VALIDATE_IP ) ) > 0 ):
				$ip = filter_var( $_GET['testingIPaddress'], FILTER_VALIDATE_IP );
				break;

			case ( isset( $_SERVER['HTTP_CF_CONNECTING_IP'] ) ):
				$ip = $_SERVER['HTTP_CF_CONNECTING_IP'];
				break;

			case ( isset( $_SERVER['HTTP_INCAP_CLIENT_IP'] ) ):
				$ip = $_SERVER['HTTP_INCAP_CLIENT_IP'];
				break;

			case ( isset( $_SERVER['True-Client-IP'] ) ):
				$ip = $_SERVER['True-Client-IP'];
				break;

			case ( isset( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ):
				$ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
				break;

			case ( isset( $_SERVER['HTTP_X_REAL_IP'] ) ):
				$ip = $_SERVER['HTTP_X_REAL_IP'];
				break;

			case ( isset( $_SERVER['X-Forwarded-For'] ) ):
				$ip = $_SERVER['X-Forwarded-For'];
				break;

			case ( isset( $_SERVER['X-Forwarded-For'] ) ):
				$ip = $_SERVER['X-Forwarded-For'];
				break;

			default:
				$cur = get_array_key( 'REMOTE_ADDR', $_SERVER, '127.0.0.1' );
				$list = explode( ',', $cur );
				$real = filter_var( $list[0], FILTER_VALIDATE_IP );
				$parts = explode( '.', $real );
				if ( '10' == $parts[0] || '192' == $parts[0] || '127' == $parts[0] ) {
					$ip = '81.157.49.99';
				} else {
					$ip = $real;
				}
				break;
		}
		return $ip;
	}

	if ( ! function_exists( 'get_cookie_domain' ) ) {
		function get_cookie_domain() {
			$host = get_array_key( 'HTTP_HOST', $_SERVER );
			$server = get_array_key( 'SERVER_NAME', $_SERVER );
			$domain = ( is_empty( $host ) ) ? $server : $host;
			if ( '.' !== substr( $domain, 0, 1 ) && ! beginning_matches( '127.0.0.1', $domain ) ) {
				$domain = sprintf( '.%s', $domain );
			}
			$portstop = strpos( $domain, ':' );
			if ( false !== $portstop ) {
				$domain = substr( $domain, 0, $portstop );
			}
			//if ( '.localhost' == $domain ) {
			//	$domain = false;
			//}
			return $domain;
		}
	}

	function is_ssl() {
		global $_server;
		return ( 'on' == get_array_key( 'HTTPS', $_server, 'off' ) || 'https' == get_array_key( 'HTTP_X_FORWARDED_PROTO', $_server, 'http' ) );
	}

	function tc_set_cookie( $key, $value, $exp = null ) {
		if ( is_null( $exp ) || ! is_numeric( $exp ) ) {
			$exp = time() + ( 86400 * 30 );
		}
		$_COOKIE[ $key ] = $value;
		return setcookie( $key, $value, $exp, '/', get_cookie_domain(), false, false );
	}

	function tc_get_cookie( $key, $default = null ) {
		return ( is_array( $_COOKIE ) && array_key_exists( $key, $_COOKIE ) ) ? $_COOKIE[ $key ] : $default;
	}

	function tc_unset_cookie( $key ) {
		$exp = time() - 3600;
		unset( $_COOKIE[ $key ] );
		return setcookie( $key, '', $exp, '/', get_cookie_domain(), false, false );
	}

	function tc_make_absolute_url( $path = '/' ) {
		$https = ( ! is_empty( get_array_key( 'HTTPS', $_SERVER ) ) || 'https' == get_array_key( 'HTTP_X_FORWARDED_PROTO', $_SERVER, 'http' ) );
		$domain = ( ! is_empty( get_array_key( 'HTTP_HOST', $_SERVER ) ) ) ? get_array_key( 'HTTP_HOST', $_SERVER ) : get_array_key( 'SERVER_NAME', $_SERVER );
		return sprintf(
			'%s://%s%s',
			( true == $https ) ? 'https' : 'http',
			$domain,
			$path
		);
	}

	function tc_set_session( $key, $value ) {
		if ( ! is_string( $key ) ) {
			return false;
		}
		$_SESSION[ $key ] = $value;
		return true;
	}

	function tc_get_session( $key ) {
		return get_array_key( $key, $_SESSION, null );
	}

	function tc_unset_session( $key ) {
		if ( ! is_string( $key ) ) {
			return false;
		}
		$_SESSION[ $key ] = null;
		unset( $_SESSION[ $key ] );
		return true;
	}

	function tc_get_request_port() {
		$pos = strpos( get_array_key( 'HTTP_HOST', $_SERVER, 'localhost' ), ':' );
		if ( false === $pos ) {
			return ( is_ssl() ) ? 443 : 80;
		}
		return intval( substr( get_array_key( 'HTTP_HOST', $_SERVER, 'localhost' ), $pos + 1 ) );
	}