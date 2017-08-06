<?php
	defined( 'ABSPATH' ) || die( 'Sorry, but you cannot access this page directly.' );

	class InterspireApi {
		private $url;
		private $user;
		private $key;

		function __construct( $base, $user, $key ) {
			$this->url = $base;
			$this->user = $user;
			$this->key = $key;
		}

		public function query( $command, $type, $query = array(), $originIp = '127.0.0.1' ) {
			$query['username'] = $this->user;
			$query['usertoken'] = $this->key;
			$query['requesttype'] = $type;
			$query['requestmethod'] = $command;
			$xml = $this->array_2_xml( $query )->asXML();
			if ( '127.0.0.1' !== $originIp && ! is_empty( $originIp ) ) {
				$url = $this->url . sprintf( '?HTTP_X_REAL_IP=%s', $originIp );
			}
			else {
				$url = $this->url;
			}
			$res = HTTP_REQUEST::POST( $url, $xml, array(
				'Content-Type' => 'text/xml',
			), 59, null, null, true );
			$ret = new stdClass();
			foreach ( $res as $key => $value ) {
				$ret->{$key} = $res->{$key};
			}
			return $ret;
		}

		private function array_2_xml( array $array, $node = null, $ndk = 'data_' ) {
			if ( ! is_a( $node, 'SimpleXMLElement' ) ) {
				$node = simplexml_load_string( '<?xml version="1.0" encoding="UTF-8" ?><xmlrequest></xmlrequest>' );
			}
			if ( $this->can_loop( $array ) ) {
				foreach ( $array as $key => $value ) {
					if ( is_numeric( $key ) ) {
						$key = $ndk;
					}
					if ( ! $this->can_loop( $value ) ) {
						$node->addChild( $key, $value );
					}
					else if ( $this->is_numeric_array( $value ) ) {
						foreach ( $value as $i ) {
							$cn = $node->addChild( $key );
							$this->array_2_xml( $i, $cn, $key );
						}
					}
					else {
						$cn = $node->addChild( $key );
						$this->array_2_xml( $value, $cn, $key );
					}
				}
			}
			return $node;
		}

		private function is_numeric_array( array $array ) {
			$keys = array_keys( $array );
			if ( ! $this->can_loop( $keys ) ) {
				return false;
			}
			foreach ( $keys as $key ) {
				if ( ! is_numeric( $key ) ) {
					return false;
				}
			}
			return true;
		}

		private function can_loop( $var ) {
			return ( ( is_array( $var ) && count( $var ) > 0 ) || is_object( $var ) );
		}
	}