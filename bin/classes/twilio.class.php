<?php
	defined( 'ABSPATH' ) || die( 'Sorry, but you cannot access this page directly.' );

	class Twilio {
		private $sid = null;
		private $auth = null;
		private $base_url = '';

		function __construct( $sid, $auth, $base = 'api.twilio.com' ) {
			$this->sid = $sid;
			$this->auth = $auth;
			$this->base_url = $base;
		}

		function send_sms( $recipient, $message = 'Test Message' ) {
			$url = sprintf( 'https://%s/2010-04-01/Accounts/%s/Messages.json', $this->base_url, $this->sid );
			$body = array(
				'To' => $recipient,
				'From' => $this->number,
				'Body' => $message,
			);
			$return = HTTP_REQUEST::POST( $url, $body, array(), 59, $this->sid, $this->auth );
			return $return->data;
		}

		function lookup_phone( $phone ) {
			$url = sprintf( 'https://lookups.twilio.com/v1/PhoneNumbers/%s', $phone );
			$ckey = md5( $url );
			$cres = cache_get( $ckey );
			if ( ! is_object( @unserialize( $cres ) ) ) {
				$body = array(
					'Type' => 'carrier',
				);
				$return = HTTP_REQUEST::GET( $url, $body, array(), 59, $this->sid, $this->auth );
				cache_set( $ckey, serialize( $return ) );
				$cres = $return;
			}
			return get_object_property( 'data', $cres->data );
		}
	}