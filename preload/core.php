<?php
	defined( 'ABSPATH' ) || die( 'Sorry, but you cannot access this page directly.' );

	if ( ! function_exists( 'can_loop' ) ) {
		function can_loop( $data ) {
			return ( is_array( $data ) && count( $data ) > 0 );
		}
	}

	if ( ! function_exists( 'is_associative_array' ) ) {
		function is_associative_array( $array ) {
			if ( ! is_array( $array ) ) {
				return false;
			}
			return ( array_keys( $array ) !== range( 0, count( $array ) - 1 ));
		}
	}

	if ( ! function_exists( 'is_empty' ) ) {
		function is_empty( $var ) {
			if ( is_object( $var ) ) {
				return false;
			}
			if ( is_array( $var ) && can_loop( $var ) ) {
				return false;
			}
			return ( empty( $var ) || is_null( $var ) || ( ! is_array( $var ) && ! is_object( $var ) && 0 == strlen( $var ) ) );
		}
	}

	if ( ! function_exists( 'is_bool_val' ) ) {
		function is_bool_val( $var ) {
			return ( 0 === $var || 1 === $var || '0' === $var || '1' === $var || true === $var || false === $var );
		}
	}

	if ( ! function_exists( 'is_cli' ) ) {
		function is_cli() {
			return ( 'cli' == php_sapi_name() );
		}
	}

	if ( ! function_exists( 'get_array_key' ) ) {
		function get_array_key( $key, $array = array(), $default = null ) {
			return ( is_array( $array ) && array_key_exists( $key, $array ) ) ? $array[ $key ] : $default;
		}
	}

	if ( ! function_exists( 'get_defined_value' ) ) {
		function get_defined_value( $key, $default = null ) {
			return ( defined( $key ) && ! is_empty( constant( $key ) ) ) ? constant( $key ) : $default;
		}
	}

	if ( ! function_exists( 'get_object_property' ) ) {
		function get_object_property( $key, $obj, $default = null ) {
			return ( is_object( $obj ) && property_exists( $obj, $key ) ) ? $obj->{$key} : $default;
		}
	}

	if ( ! function_exists( 'get_bean_property' ) ) {
		function get_bean_property( $key, $obj, $default = null ) {
			if ( is_a( $obj, 'RedBeanPHP\OODBBean' ) ) {
				$ret = $obj->{ $key };
				return ( is_empty( $ret ) ) ? $default : $ret;
			}
			if ( is_object( $obj ) && property_exists( $obj, $key ) ) {
				return $obj->{$key};
			}
			return ( ! is_string( $default ) ) ? null : $default;
		}
	}

	if ( ! function_exists( 'beginning_matches' ) ) {
		function beginning_matches( $beginning, $match ) {
			return ( $beginning == substr( $match, 0, strlen( $beginning ) ) );
		}
	}

	if ( ! function_exists( 'ending_matches' ) ) {
		function ending_matches( $end, $match ) {
			return ( $end == substr( $match, ( -1 * strlen( $end ) ) ) );
		}
	}

	if ( ! function_exists( 'is_between' ) ) {
		function is_between( $val, $start, $end ) {
			return ( $val >= $start && $val <= $end );
		}
	}

	if ( ! function_exists( 'property_exists_deep' ) ) {
		function property_exists_deep( $obj, $deeppath = '' ) {
			if ( is_empty( $deeppath ) ) {
				return false;
			}
			if ( false == strpos( $deeppath, '->' ) ) {
				return property_exists( $obj, $deeppath );
			}
			$deep = explode( '->', $deeppath );
			$curobj = $obj;
			foreach ( $deep as $property ) {
				if ( is_object( $curobj ) ) {
					if ( property_exists( $curobj, $property ) ) {
						$curobj = $curobj->{ $property };
					}
					else {
						return false;
					}
				}
			}
			return true;
		}
	}

	if ( ! function_exists( 'days_ago' ) ) {
		function days_ago( $days = 0 ) {
			$curr = strtotime( date( 'Y-m-d 00:00:00', time() ) );
			return $curr - ( 86400 * intval( $days ) );
		}
	}

	if ( ! function_exists( 'absint' ) ) {
		function absint( $input ) {
			if ( ! is_numeric( $input ) || is_empty( $input ) ) {
				return 0;
			}
			$int = intval( $input );
			if ( $int < 0 ) {
				$int = $int * -1;
			}
			return $int;
		}
	}

	if ( ! function_exists( 'sanitize_url' ) ) {
		function sanitize_url( $url ) {
			$url = filter_var( $url, FILTER_SANITIZE_URL );
			if ( false === strpos( $url, 'http://' ) && false === strpos( $url, 'https://' ) ) {
				return null;
			}
			return $url;
		}
	}

	if ( ! function_exists( 'sanitize_phone' ) ) {
		function sanitize_phone( $phone ) {
			$phone = trim( $phone );
			$phone = preg_replace( '/[^0-9]/', '', $phone );
			return $phone;
		}
	}

	if ( ! function_exists( 'sanitize_cc_bin' ) ) {
		function sanitize_cc_bin( $bin ) {
			$bin = trim( $bin );
			$bin = preg_replace( '/[^0-9]/', '', $bin );
			$bin = substr( $bin, 0, 6 );
			return $bin;
		}
	}

	if ( ! function_exists( 'strip_get_query' ) ) {
		function strip_get_query( $uri ) {
			$query_seperator = strpos( $uri, '?' );
			if ( false !== $query_seperator ) {
				$uri = substr( $uri, 0, $query_seperator + 1 );
			}
			$uri = str_replace( '?', '', $uri );
			return $uri;
		}
	}

	if ( ! function_exists( 'fix_key' ) ) {
		function fix_key( $input ) {
			$output = $input;
			$output = utf8_decode( $output );
			$output = str_replace( '?', '', $output );
			$output = trim( $output );
			$output = preg_replace( '/[^a-zA-Z\_]/', '', $output );
			$output = str_replace( "\0", "", $output );
			return (string) $output;
		}
	}

	if ( ! function_exists( 'fix_data' ) ) {
		function fix_data( $input ) {
			$output = $input;
			//$output = str_replace( '?', '', $output );
			//$output = trim( $output );
			//$colpos = strpos( $output, ':' );
			//if ( false !== $colpos ) {
			//	$output = substr( $output, $colpos + 1 );
			//}
			$output = mb_convert_encoding( $output, 'UTF-8' );
			$output = strval( $output );
			$output = fix_unicode( $output );
			return (string) $output;
		}
	}

	if ( ! function_exists( 'fix_unicode' ) ) {
		function fix_unicode( $input ) {
			$output = $input;
			$output = str_replace( "\0", "", $output );
			return $output;
		}
	}

	if ( ! function_exists( 'array_average' ) ) {
		function array_average( $array ) {
			$count = 0;
			$sum = 0;
			if ( can_loop( $array ) ) {
				foreach ( $array as $index => $value ) {
					$sum = $sum + floatval( $value );
					$count ++;
				}
			}
			return ( $sum / $count );
		}
	}

	if ( ! function_exists( 'str_replace_first' ) ) {
		function str_replace_first($from, $to, $subject) {
			$from = '/' . preg_quote( $from, '/' ) . '/';
			return preg_replace( $from, $to, $subject, 1 );
		}
	}

	if( ! function_exists( 'deep_array_combine' ) ) {
		function deep_array_combine( Array $keys, Array $values ) {
			$return = array();
			$kc = array();
			if ( can_loop( $keys ) ) {
				foreach ( $keys as $k ) {
					if ( ! array_key_exists( $k, $kc ) ) {
						$kc[ $k ] = 0;
					}
					$kc[ $k ] ++;
				}
			}
			if ( can_loop( $kc ) ) {
				$nodupes = true;
				foreach ( $kc as $key => $count ) {
					if ( $count !== 1 ) {
						$nodupes = false;
					}
				}
				if ( true == $nodupes && count( $keys ) == count( $values ) ) {
					$return = array_combine( $keys, $values );
				}
				else if ( count( $keys ) == count( $values ) ) {
					$rc = array();
					if ( can_loop( $keys ) ) {
						foreach ( $keys as $index => $key ) {
							if ( array_key_exists( $key, $rc ) ) {
								if ( ! is_array( $rc[ $key ] ) ) {
									$val = $rc[ $key ];
									$rc[ $key ] = array( $val );
								}
								array_push( $rc[ $key ], $values[ $index ] );
							}
							else {
								$rc[ $key ] = $values[ $index ];
							}
						}
					}
					$return = $rc;
				}
			}
			return $return;
		}
	}

	if( ! function_exists( 'deep_array_merge' ) ) {
		function deep_array_merge( Array $a1, Array $a2 ) {
			$return = array();
			$akc = array();
			if ( can_loop( $a1 ) ) {
				foreach ( $a1 as $k => $v ) {
					if ( ! array_key_exists( $k, $akc ) ) {
						$akc[ $k ] = 0;
					}
					$akc[ $k ] ++;
				}
			}
			if ( can_loop( $a2 ) ) {
				foreach ( $a2 as $k => $v ) {
					if ( ! array_key_exists( $k, $akc ) ) {
						$akc[ $k ] = 0;
					}
					$akc[ $k ] ++;
				}
			}
			$nodupes = true;
			if ( can_loop( $akc ) ) {
				foreach ( $akc as $key => $count ) {
					if ( $count !== 1 ) {
						$nodupes = false;
					}
				}
			}
			if ( true == $nodupes ) {
				$return = array_merge_recursive( $a1, $a2 );
			}
			else {
				$return = $a1;
				if ( can_loop( $a2 ) ) {
					foreach ( $a2 as $key => $value ) {
						if ( array_key_exists( $key, $return ) ) {
							$val = $return[ $key ];
							$return[ $key ] = array();
							array_push( $return[ $key ], $val );
							array_push( $return[ $key ], $value );
						}
					}
				}
			}
			return $return;
		}
	}

	if ( ! function_exists( 'get_class_public_properties' ) ) {
		function get_class_public_properties( $object ) {
			if ( is_object( $object ) ) {
				$class = get_class( $object );
			}
			else {
				$class = $object;
			}
			$reflection = new ReflectionClass( $class );
			$properties = $reflection->getProperties( ReflectionMethod::IS_PUBLIC );
			$return = array();
			if ( can_loop( $properties ) ) {
				foreach ( $properties as $prop ) {
					$return[ $prop->name ] = get_object_property( $prop->name, $object, null );
				}
			}
			return $return;
		}
	}

	if ( ! function_exists( 'escape_sql_input' ) ) {
		function escape_sql_input( $input ) {
			try {
				$pdo = new PDO( 'sqlite:fakepdo.db' );
			}
			catch( Exception $e ) {}
			if ( isset( $pdo ) && is_a( $pdo, 'PDO' ) ) {
				$input = $pdo->quote( $input );
			}
			if ( 2 !== substr_count( $input, "'" ) ) {
				$input = sprintf( "'%s'", $input );
			}
			return $input;
		}
	}

	if ( ! function_exists( 'make_percent_as_interger' ) ) {
		function make_percent_as_interger( $float, $depth = 0 ) {
			$newfloat = $float * 100;
			return round( $newfloat, absint( $depth ) );
		}
	}

	if ( ! function_exists( 'make_percent' ) ) {
		function make_percent( $current = 0, $total = 0, $depth = 0 ) {
			if ( 0 == absint( $total ) || 0 == absint( $current ) ) {
				return 0;
			}
			$percent = $current / $total;
			return make_percent_as_interger( $percent, $depth );
		}
	}

	if ( ! function_exists( 'is_decimal' ) ) {
		function is_decimal( $val ) {
			return ( is_numeric( $val ) && floor( $val ) != $val );
		}
	}

	if ( ! function_exists( 'tc_sleep' ) ) {
		function tc_sleep( $waitTimeSeconds = 0 ) {
			if ( is_decimal( $waitTimeSeconds ) ) {
				list( $seconds, $decimal ) = explode( '.', $waitTimeSeconds );
				$nanoseconds = ( intval( $decimal ) * 10000000 );
				time_nanosleep( $seconds, $nanoseconds );
			}
			else {
				time_nanosleep( absint( $waitTimeSeconds ), 0 );
			}
		}
	}

	if ( ! function_exists( 'is_webapp' ) ) {
		function is_webapp() {
			$ua = get_array_key( 'HTTP_USER_AGENT', $_SERVER, '' );
			return ( false !== strpos( $ua, 'iPhone; CPU'));
		}
	}

	if ( ! function_exists( 'in_cidr' ) ) {
		function in_cidr( $ip = null, $range = null ) {
			list ( $subnet, $bits ) = explode( '/', $range );
			$ip = ip2long( $ip );
			$subnet = ip2long( $subnet );
			$mask = -1 << ( 32 - $bits );
			$subnet &= $mask;
			return ( $ip & $mask ) == $subnet;
		}
	}