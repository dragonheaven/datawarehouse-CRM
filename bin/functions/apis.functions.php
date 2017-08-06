<?php
	defined( 'ABSPATH' ) || die( 'Sorry, but you cannot access this page directly.' );

	function get_zurmo() {
		global $_zapi;
		if ( ! is_a( $_zapi, 'Zurmo' ) ) {
			try {
				$_zapi = new Zurmo( ZURMO_BASE, ZURMO_API_USER, ZURMO_API_PASS );
			}
			catch ( Exception $e ) {
				$msg = $e->getMessage();
				trigger_error( $msg );
				return false;
			}
		}
		return $_zapi;
	}

	function check_zurmo_login( $username = null, $password = null ) {
		try {
			$zapi = new Zurmo( ZURMO_BASE, $username, $password );
			$res = $zapi->api_query( '/zurmo/currency/api/list/', null, 'GET' );
			if ( property_exists_deep( $res, 'data->status' ) && 'SUCCESS' == $res->data->status ) {
				return $zapi->get_auth_headers();
			}
		}
		catch( Exception $e ) {
			$msg = $e->getMessage();
			trigger_error( $msg );
		}
		return false;
	}

	function check_zurmo_headers( $headers = array() ) {
		$url = sprintf( '%s/zurmo/currency/api/list/', ZURMO_BASE );
		$res = HTTP_REQUEST::GET( $url, null, $headers );
		return ( property_exists_deep( $res, 'data->status' ) && 'SUCCESS' == $res->data->status );
	}

	function is_zurmo_headers( $headers ) {
		return (
			is_array( $headers )
			&& array_key_exists( 'Accept', $headers )
			&& array_key_exists( 'Zurmo-Api-Request-Type', $headers )
			&& array_key_exists( 'Zurmo-Session-Id', $headers )
			&& array_key_exists( 'Zurmo-Token', $headers )
		);
	}

	function get_twilio_class() {
		if ( class_exists( 'Twilio' ) ) {
			return new Twilio( TWILIO_SID, TWILIO_AUTH );
		}
		return false;
	}

	function get_mailboxlayer() {
		if ( class_exists( 'MailBoxLayer' ) ) {
			return new MailBoxLayer( MAILBOXLAYERACCESS );
		}
	}