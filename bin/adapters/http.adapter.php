<?php
	defined( 'ABSPATH' ) || die( 'Sorry, but you cannot access this page directly.' );

	class HTTP_REQUEST {
		public $connection_status = false;
		public $mime = null;
		public $total_time = 0;
		public $redirects = 0;
		public $headers = array();
		public $cookies = array();
		public $data = null;
		public $code = 0;
		public $msg = null;

		private $url = null;
		private $method = 'GET';
		private $send_body = null;
		private $send_headers = array();
		private $timeout = 0;
		private $raw_return = null;
		private $ch = null;
		private $follow = false;

		private $auth_user = null;
		private $auth_password = null;

		function __construct( $url, $method = 'GET', $body = null, $headers = array(), $timeout = 59, $raw_data = false, $follow = true ) {
			if ( ! function_exists( 'curl_version' ) ) {
				$this->msg = 'Missing cURL Library';
				$this->data = new Exception( 'Missing cURL Library', true );
				return;
			}
			$this->follow = ( true == $follow );
			$method = strtoupper( $method );
			$url = $this->sanitize_url( $url );
			if ( false !== strpos( $url, '?' ) && ( 'GET' == $method || 'DELETE' == $method ) ) {
				list( $url, $query ) = explode( '?', $url );
				parse_str( $query, $ubody );
				if ( self::can_loop( $ubody ) ) {
					foreach ( $ubody as $key => $value ) {
						if ( ! is_array( $body ) && ! is_object( $body ) ) {
							$body = array();
						}
						if ( is_object( $body ) ) {
							$body->$key = $value;
						}
						if ( is_array( $body ) ) {
							$body[ $key ] = $value;
						}
					}
				}
			}
			if ( self::is_empty( $url ) ) {
				$this->msg = 'Invalid URL: ' . strtolower( $url );
				$this->data = new Exception( 'Invalid URL: ' . strtolower( $url ), true );
				return;
			}
			switch ( strtoupper( $method ) ) {
				case 'GET':
					$this->method = strtoupper( $method );
					$this->url = $url;
					if (
						( is_array( $body ) && can_loop( $body ) )
						|| ! is_array( $body ) && ! is_empty( $body )
					) {
						$this->url .= $this->make_body_query( $body, true );
						$this->send_body = null;
					}
					break;

				case 'DELETE':
					$this->method = strtoupper( $method );
					$this->url = $url;
					if (
						( is_array( $body ) && can_loop( $body ) )
						|| ! is_array( $body ) && ! is_empty( $body )
					) {
						$this->url .= $this->make_body_query( $body, true );
						$this->send_body = null;
					}
					break;

				case 'POST':
					$this->method = strtoupper( $method );
					$this->url = $url;
					$this->send_body = $this->make_body_query( $body, false, $raw_data );
					break;

				case 'PUT':
					$this->method = strtoupper( $method );
					$this->url = $url;
					$this->send_body = $this->make_body_query( $body, false, $raw_data );
					break;

				default:
					$this->msg = 'Unknown/Unsupported HTTP Method: ' . strtoupper( $method );
					$this->data = new Exception( 'Unknown/Unsupported HTTP Method: ' . strtoupper( $method ), true );
					return;
					break;
			}
			if ( self::can_loop( $headers ) ) {
				$this->send_headers = array();
				foreach ( $headers as $key => $value ) {
					if ( is_string( $value ) ) {
						if ( ! is_numeric( $key ) ) {
							array_push( $this->send_headers, $key . ': ' . $value );
						}
						if ( false !== strpos( $value, ': ' ) ) {
							array_push( $this->send_headers, $value );
						}
					}
				}
			}
			else {
				$this->send_headers = null;
			}
			if ( intval( $timeout ) > 0 ) {
				$this->timeout = intval( $timeout );
			}
			else {
				$this->timeout = 59;
			}
		}

		private function make_body_query( $body, $has_q = false, $raw_data = false ) {
			$return = '';
			if ( is_string( $body ) && false == $raw_data ) {
				parse_str( $body, $d );
				if ( is_array( $d ) ) {
					$body = $d;
				}
			}
			else if ( is_string( $body ) && true == $raw_data ) {
				$return = $body;
			}
			if ( is_array( $body ) || is_object( $body ) ) {
				if ( true == $has_q ) {
					$return .= '?';
				}
				$return .= http_build_query( $body );
			}
			return ( strlen( $return ) > 0 ) ? $return : null;
		}

		private function get_headers( $curl_return, $header_size ) {
			$curl_return = substr( $curl_return, 0, $header_size );
			$rows = explode( "\r\n", $curl_return );
			$headers = array();
			if ( self::can_loop( $rows ) ) {
				foreach ( $rows as $row ) {
					$fc = strpos( $row, ':' );
					if ( ! empty( $row ) && false !== $fc ) {
						$key = substr( $row, 0, $fc );
						$value = substr( $row, $fc + 1 );
						$headers[ strtolower( $key ) ] = $value;
					}
				}
			}
			return $headers;
		}

		private function get_cookies( $curl_return ) {
			$return = array();
			$rows = explode( "\r\n", $curl_return );
			if ( can_loop( $rows ) ) {
				foreach ( $rows as $row ) {
					if ( false !== strpos( strtolower( $row ), 'set-cookie:' ) ) {
						$cookie = substr( $row, 12 );
						trim( $row );
						$first_seperator = strpos( $cookie, '; ' );
						$cookie_main = substr( $cookie, 0, $first_seperator );
						$cookie_more = substr( $cookie, $first_seperator + 2 );
						$more_urlized = str_replace( '; ', '&', $cookie_more );
						list( $cookie_name, $cookie_value ) = explode( '=', $cookie_main );
						parse_str( $more_urlized, $more_parts );
						$c = new stdClass();
						$c->name = $cookie_name;
						$c->value = $cookie_value;
						$c->expires = $this->get_array_key( 'expires', $more_parts, date( 'D, d-M-Y H:i:s \G\M\T', time() + 82400 ) );
						$c->path = $this->get_array_key( 'path', $more_parts, '/' );
						if ( $this->can_loop( $more_parts ) ) {
							foreach ( $more_parts as $key => $value ) {
								$c->$key = $value;
							}
						}
						array_push( $return, $c );
					}
				}
			}
			return $return;
		}

		private function get_body( $curl_return, $mime, $header_size ) {
			$curl_return = substr( $curl_return, $header_size );
			$curl_return = trim( $curl_return );
			switch ( true ) {
				case ( false !== strpos( $mime, '/json' ) ):
					$return = json_decode( $curl_return );
					break;

				case ( false !== strpos( $mime, '+json' ) ):
					$return = json_decode( $curl_return );
					break;

				case ( false !== strpos( $mime, '/xml' ) ):
					try {
						$return = new SimpleXMLElement( $curl_return );
					}
					catch ( Exception $e ) {
						$return = new stdClass();
						$return = $e;
					}
					break;

				default:
					$return = new stdClass();
					$return->contents = $curl_return;
					break;
			}
			return $return;
		}

		private function sanitize_url( $url ) {
			$url = filter_var( $url, FILTER_SANITIZE_URL );
			if ( false === strpos( $url, 'http://' ) && false === strpos( $url, 'https://' ) ) {
				return null;
			}
			return $url;
		}

		private static function is_empty( $val ) {
			return ( empty( $val ) || is_null( $val ) );
		}

		private static function can_loop( $data ) {
			return ( is_array( $data ) && count( $data ) > 0 );
		}

		private static function get_array_key( $key, $array = array(), $default = null ) {
			return ( is_array( $array ) && array_key_exists( $key, $array ) ) ? $array[ $key ] : $default;
		}

		function make_request() {
			global $http_request_set_proxy_settings;
			if ( is_a( $this->data, 'Exception' ) ) {
				return false;
			}
			try {
				$this->ch = curl_init();
				$ch_options = array(
					CURLOPT_URL => $this->url,
					CURLOPT_CUSTOMREQUEST => $this->method,
					CURLOPT_POSTFIELDS => $this->send_body,
					CURLOPT_FOLLOWLOCATION => $this->follow,
					CURLOPT_SSL_VERIFYHOST => false,
					CURLOPT_SSL_VERIFYPEER => false,
					CURLOPT_RETURNTRANSFER => true,
					CURLOPT_FAILONERROR => false,
					CURLOPT_HEADER => true,
					CURLOPT_TIMEOUT => $this->timeout,
					CURLOPT_AUTOREFERER => true,
					CURLOPT_FRESH_CONNECT => true,
					CURLOPT_MAXCONNECTS => 100,
					CURLOPT_CONNECTTIMEOUT_MS => 4999,
				);
				$phost = get_array_key( 'host', $http_request_set_proxy_settings, null );
				$pport = get_array_key( 'port', $http_request_set_proxy_settings, null );
				if ( ! is_empty( $phost ) && ! is_empty( $pport ) && 0 !== $pport ) {
					$ch_options[ CURLOPT_PROXY ] = sprintf( '%s:%d', $phost, $pport );
				}
				if ( is_object( $this->send_headers ) || is_array( $this->send_headers ) ) {
					$ch_options[ CURLOPT_HTTPHEADER ] = $this->send_headers;
				}
				if ( ! self::is_empty( $this->auth_user ) || ! self::is_empty( $this->auth_password ) ) {
					$ch_options[ CURLOPT_USERPWD ] = $this->auth_user . ':' . $this->auth_password;
				}
				curl_setopt_array( $this->ch, $ch_options );
				$this->raw_return = curl_exec( $this->ch );
				$this->msg = curl_error( $this->ch );
				$this->code = intval( curl_getinfo( $this->ch, CURLINFO_HTTP_CODE ) );
				$this->connection_status = $this->code > 0;
				$this->mime = curl_getinfo( $this->ch, CURLINFO_CONTENT_TYPE );
				$this->total_time = floatval( curl_getinfo( $this->ch, CURLINFO_TOTAL_TIME ) );
				$this->redirects = intval( curl_getinfo( $this->ch, CURLINFO_REDIRECT_COUNT ) );
				$header_size = curl_getinfo( $this->ch, CURLINFO_HEADER_SIZE );
				$this->headers = $this->get_headers( $this->raw_return, $header_size );
				$this->cookies = $this->get_cookies( $this->raw_return );
				$parsed = $this->get_body( $this->raw_return, $this->mime, $header_size );
				if ( is_a( $parsed, 'Exception' ) ) {
					$this->msg = $parsed->getMessage();
				}
				$this->data = $parsed;
				curl_close( $this->ch );
			}
			catch ( Exception $e ) {
				$this->data = $e;
				$this->msg = $e->getMessage();
			}
		}

		function set_auth( $user, $password = null ) {
			$this->auth_user = $user;
			$this->auth_password = $password;
		}

		public static function REQUEST( $url, $method = 'GET', $body = null, $headers = array(), $timeout = 59, $user = null, $password = null, $follow = true ) {
			$c = get_called_class();
			$obj = new $c( $url, $method, $body, $headers, $timeout, false, $follow );
			if ( ! self::is_empty( $user ) ) {
				$obj->set_auth( $user, $password );
			}
			$obj->make_request();
			$obj::CLEAR_PROXY();
			return $obj;
		}

		public static function GET( $url, $body = null, $headers = array(), $timeout = 59, $user = null, $password = null, $follow = true ) {
			$c = get_called_class();
			$obj = new $c( $url, 'GET', $body, $headers, $timeout, false, $follow );
			if ( ! self::is_empty( $user ) ) {
				$obj->set_auth( $user, $password );
			}
			$obj->make_request();
			$obj::CLEAR_PROXY();
			return $obj;
		}

		public static function POST( $url, $body = null, $headers = array(), $timeout = 59, $user = null, $password = null, $raw_data = false, $follow = true ) {
			$c = get_called_class();
			$obj = new $c( $url, 'POST', $body, $headers, $timeout, $raw_data, $follow );
			if ( ! self::is_empty( $user ) ) {
				$obj->set_auth( $user, $password );
			}
			$obj->make_request();
			$obj::CLEAR_PROXY();
			return $obj;
		}

		public static function DELETE( $url, $body = null, $headers = array(), $timeout = 59, $user = null, $password = null, $follow = true ) {
			$c = get_called_class();
			$obj = new $c( $url, 'DELETE', $body, $headers, $timeout, false, $follow );
			if ( ! self::is_empty( $user ) ) {
				$obj->set_auth( $user, $password );
			}
			$obj->make_request();
			$obj::CLEAR_PROXY();
			return $obj;
		}

		public static function PUT( $url, $body = null, $headers = array(), $timeout = 59, $user = null, $password = null, $raw_data = false, $follow = true ) {
			$c = get_called_class();
			$obj = new $c( $url, 'PUT', $body, $headers, $timeout, $raw_data, $follow );
			if ( ! self::is_empty( $user ) ) {
				$obj->set_auth( $user, $password );
			}
			$obj->make_request();
			$obj::CLEAR_PROXY();
			return $obj;
		}

		public static function SET_PROXY( $host, $port ) {
			global $http_request_set_proxy_settings;
			if ( is_empty( $host ) || is_empty( $port ) || 0 == intval( $port ) ) {
				return false;
			}
			if ( ! is_array( $http_request_set_proxy_settings ) ) {
				$http_request_set_proxy_settings = array();
			}
			$http_request_set_proxy_settings['host'] = $host;
			$http_request_set_proxy_settings['port'] = intval( $port );
		}

		public static function CLEAR_PROXY() {
			global $http_request_set_proxy_settings;
			$http_request_set_proxy_settings = array();
		}
	}