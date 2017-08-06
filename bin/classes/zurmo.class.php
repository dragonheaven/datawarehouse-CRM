<?php
	defined( 'ABSPATH' ) || die( 'Sorry, but you cannot access this page directly.' );

	class Zurmo {
		private $base;
		private $user;
		private $pass;
		private $session_id;
		private $token;
		private $authenticated = false;

		function __construct( $base = null, $user = null, $pass = null ) {
			$this->base = $base;
			$this->user = $user;
			$this->pass = $pass;
		}

		private function authenticate() {
			$ar = $this->api_query( 'authenticate' );
			if (
				is_object( $ar->data )
				&& property_exists( $ar->data, 'status' )
				&& 'SUCCESS' == $ar->data->status
			) {
				$this->session_id = $ar->data->data->sessionId;
				$this->token = $ar->data->data->token;
				$this->authenticated = true;
			}
			else {
				throw new Exception( 'Invalid CRM Credentials' );
			}
		}

		function api_query( $endpoint = 'authenticate', $body = null, $method = 'GET' ) {
			$method = strtoupper( $method );
			switch ( $endpoint ) {
				case 'authenticate':
					$url = sprintf( '%s/zurmo/api/login', $this->base );
					$headers = array(
						'Accept: application/json',
						'Zurmo-Api-Request-Type: REST',
						sprintf( 'Zurmo-Auth-Username: %s', $this->user ),
						sprintf( 'Zurmo-Auth-Password: %s', $this->pass ),
					);
					$method = 'POST';
					break;

				default:
					if ( is_empty( $this->session_id ) || is_empty( $this->token ) ) {
						$this->authenticate();
					}
					if ( '/' == substr( $endpoint, 0, 1 ) ) {
						$endpoint = substr( $endpoint, 1 );
					}
					$url = sprintf( '%s/%s', $this->base, $endpoint );
					$headers = array(
						'Accept: application/json',
						'Zurmo-Api-Request-Type: REST',
						sprintf( 'Zurmo-Session-Id: %s', $this->session_id ),
						sprintf( 'Zurmo-Token: %s', $this->token ),
					);
					break;
			}
			switch ( $method ) {
				case 'DELETE':
					$return = HTTP_REQUEST::DELETE( $url, $body, $headers );
					break;

				case 'POST':
					$return = HTTP_REQUEST::POST( $url, $body, $headers );
					break;

				case 'PUT':
					$return = HTTP_REQUEST::PUT( $url, $body, $headers );
					break;

				case 'GET':
					$return = HTTP_REQUEST::GET( $url, $body, $headers );
					break;

				default:
					$return = HTTP_REQUEST::REQUEST( $url, $method, $body, $headers );
					break;
			}
			if ( is_object( $return->data ) && property_exists( $return->data, 'contents' ) ) {
				$return->data = json_decode( $return->data->contents );
			}
			return $return;
		}

		function is_authenticated() {
			return ( true == $this->authenticated );
		}

		function get_auth_headers() {
			return array(
				'Accept: application/json',
				'Zurmo-Api-Request-Type: REST',
				sprintf( 'Zurmo-Session-Id: %s', $this->session_id ),
				sprintf( 'Zurmo-Token: %s', $this->token ),
			);
		}
	}