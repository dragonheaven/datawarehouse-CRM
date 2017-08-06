<?php
	defined( 'ABSPATH' ) || die( 'Sorry, but you cannot access this page directly.' );

	function handle_system_request( $path = '/', $query = array(), $method = 'GET', $headers = array() ) {
		if ( ! is_cli() && beginning_matches( '/system/', $path ) ) {
			$action = filter_system_action( $path );
			switch ( $action ) {
				case 'settings':
					html_success( 'system_settings', 'System Settings' );
					break;

				default:
					html_failure( null, sprintf( 'No such system panel "%s"', $action ) );
					break;
			}
		}
	}

	function filter_system_action( $input ) {
		$out = $input;
		$out = str_replace( '/system/', '', $out );
		$out = str_replace( '/', '', $out );
		return $out;
	}

	function ajax_update_default_queries( $data ) {
		if ( can_loop( $data ) ) {
			update_option( 'callCenterReady', get_array_key( 'callCenterReady', $data ) );
			update_option( 'emailMarketingReady', get_array_key( 'emailMarketingReady', $data ) );
			update_option( 'smsMarketingReady', get_array_key( 'smsMarketingReady', $data ) );
		}
		ajax_success( 'Updated Default Queries' );
	}

	function ajax_heartbeat( $data ) {
		ajax_success( 'Heart is Beating' );
	}

	function ajax_update_predefined_meta_tags( $data ) {
		$sd = get_array_key( 'predefinedMetaTags', $data, array() );
		update_option( 'predefinedMetaTags', serialize( $sd ) );
		ajax_success( 'Updated Predefined Meta Tags Successfully' );
	}

	function ajax_update_predefined_lead_list_tags( $data ) {
		$sd = get_array_key( 'predefinedLeadListTags', $data, array() );
		update_option( 'predefinedLeadListTags', serialize( $sd ) );
		ajax_success( 'Updated Predefined Lead List Tags Successfully' );
	}

	function get_predefined_meta_tags() {
		$return = array();
		$o = get_option( 'predefinedMetaTags' );
		if ( ! is_empty( $o ) ) {
			$d = @unserialize( $o );
			if ( can_loop( $d ) ) {
				$return = $d;
			}
		}
		return $return;
	}

	function get_predefined_lead_list_tags() {
		$return = array();
		$o = get_option( 'predefinedLeadListTags' );
		if ( ! is_empty( $o ) ) {
			$d = @unserialize( $o );
			if ( can_loop( $d ) ) {
				$return = $d;
			}
		}
		return $return;
	}

	add_action( 'route', 'handle_system_request' );