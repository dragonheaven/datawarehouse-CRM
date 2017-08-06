<?php
	defined( 'ABSPATH' ) || die( 'Sorry, but you cannot access this page directly.' );

	class MaxMind {
		private $user;
		private $license;

		function __construct( $user, $license ) {
			$this->user = $user;
			$this->license = $license;
		}

		function ip_query( $ip = null, $level = 'Country' ) {
			if ( is_empty( $ip ) ) {
				$ip = get_request_ip();
			}
			switch ( strtolower( $level ) ) {
				case 'city':
					$url = 'https://geoip.maxmind.com/geoip/v2.1/city/';
					break;

				case 'insights':
					$url = 'https://geoip.maxmind.com/geoip/v2.1/insights/';
					break;

				default:
					$url = 'https://geoip.maxmind.com/geoip/v2.1/country/';
					break;
			}
			$url .= filter_var( $ip, FILTER_VALIDATE_IP );
			$res = new stdClass();
			$res->data = $this->get_response( $url, 'GET', null, array(
				'Accept' => 'application/json',
				'Authorization' => 'Basic ' . base64_encode( $this->user . ':' . $this->license ),
			), 0 );
			$res->operation_status = true;
			return $res->data;
		}

		function bin_query( $bin = null ) {
			$bin = sanitize_cc_bin( $bin );
			$return = new stdClass();
			if ( '000000' !== $bin && false !== $bin && '111122' !== $bin ) {
				$return = $this->get_response( 'https://minfraud.maxmind.com/app/bin_http?' . http_build_query( array(
					'l' => $this->license,
					'bin' => $bin,
				) ), 'GET', null, array() );
			}
			return $return;
		}

		function fraud_query( $bin, $email = null, $phone = null, $city = null, $postal = null, $country = null, $ip = null ) {
			$url = 'https://minfraud.maxmind.com/app/ccv2r';
			if ( is_empty( $ip ) ) {
				$ip = get_request_ip();
			}
			$bin = sanitize_cc_bin( $bin );
			$email = $this->sanitize_email( $email );
			if ( ! is_empty( $email ) ) {
				list( $box, $domain ) = explode( '@', $email );
			}
			else {
				$domain = null;
			}
			$query = array(
				'i' => $ip,
				'license_key' => $this->license,
				'city' => $city,
				'postal' => $postal,
				'country' => $country,
				'domain' => $domain,
				'custPhone' => $phone,
				'emailMD5' => md5( strtolower( $email ) ),
				'bin' => $bin,
			);
			$res = $this->get_response( $url, 'POST', $query, array(), 0 );
			if ( ( '111122' == $bin || '000000' == $bin ) && is_object( $res ) && property_exists( $res, 'riskScore' ) ) {
				$res->riskScore = 100;
			}
			return $res;
		}

		public static function get( $ip = null, $level = 'Country' ) {
			$obj = new self( MAXMIND_USER, MAXMIND_LICENSE );
			return $obj->ip_query( $ip, $level );
		}

		public static function bin( $bin = null ) {
			$obj = new self( MAXMIND_USER, MAXMIND_LICENSE );
			return $obj->bin_query( $bin );
		}

		public static function fraud( $bin, $email = null, $phone = null, $city = null, $postal = null, $country = null, $ip = null ) {
			$obj = new self( MAXMIND_USER, MAXMIND_LICENSE );
			return $obj->fraud_query( $bin, $email, $phone, $city, $postal, $country, $ip );
		}

		private function sanitize_email( $input = null ) {
			$input = trim( $input );
			if ( ! is_string( $input ) ) {
				return null;
			}
			$input = strtolower( $input );
			$input = filter_var( $input, FILTER_SANITIZE_EMAIL );
			if ( 1 !== substr_count( $input, '@' ) || 0 === substr_count( $input, '.' ) ) {
				$input = null;
			}
			return $input;
		}

		protected function get_response( $url, $method = 'GET', $body = null, $headers = array(), $cachetime = 0, $ua = null, $timeout = 60 ) {
			if ( false === strpos( $url, '?' ) ) {
				$fq = sprintf( '%s?%s', $url, can_loop( $body ) ? http_build_query( $body ) : $body );
			}
			else {
				$fq = sprintf( '%s&%s', $url, can_loop( $body ) ? http_build_query( $body ) : $body );
			}
			$fqk = md5( $fq );
			if ( 0 !== absint( $cachetime ) ) {
				$cached = cache_get( $fqk );
			}
			if ( ! isset( $cached ) || is_empty( $cached ) ) {
				$res = HTTP_REQUEST::REQUEST( $url, $method, $body, $headers, $timeout );
				$data = $res->data;
				if ( 0 !== $cachetime ) {
					cache_set( $fqk, $data );
				}
			}
			return ( isset( $cached ) && ! is_empty( $cached ) ) ? $cached : $data;
		}
	}