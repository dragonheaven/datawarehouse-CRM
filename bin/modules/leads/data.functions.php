<?php
	defined( 'ABSPATH' ) || die( 'Sorry, but you cannot access this page directly.' );

	function ajax_get_list_leads_by_country( $data ) {
		ajax_success( get_option( 'leads_by_country', array() ) );
	}

	function ajax_get_list_leads_by_language( $data ) {
		ajax_success( get_option( 'leads_by_language', array() ) );
	}

	function ajax_get_list_leads_by_source( $data ) {
		ajax_success( get_option( 'leads_by_source', array() ) );
	}

	function ajax_get_list_leads_by_meta( $data ) {
		ajax_success( get_option( 'leads_by_meta', array() ) );
	}

	function ajax_get_list_leads_by_tag( $data ) {
		ajax_success( get_option( 'leads_by_tag', array() ) );
	}

	function tc_get_sample_data_for_fieldmap( $key ) {
		switch ( $key ) {
			case 'phone':
				$return = str_repeat( '1', 30 );
				break;

			case 'email':
				$return = str_repeat( 's', 64 );
				break;

			case 'fname':
				$return = str_repeat( 's', 128 );
				break;

			case 'mname':
				$return = str_repeat( 's', 256 );
				break;

			case 'lname':
				$return = str_repeat( 's', 512 );
				break;

			case 'source':
				$return = str_repeat( 's', 1024 );
				break;

			default:
				$return = str_repeat( 's', 2048 );
				trigger_error( sprintf( 'No Sample Data for Key "%s"', $key ) );
				break;
		}
		return $return;
	}
