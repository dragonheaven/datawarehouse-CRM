<?php
	defined( 'ABSPATH' ) || die( 'Sorry, but you cannot access this page directly.' );

	function nr_enabled() {
		return ( true == extension_loaded( 'newrelic' ) );
	}

	function tc_init_newrelic() {
		if ( ! nr_enabled() ) {
			return;
		}
		newrelic_set_appname( NEW_RELIC_APP );
		newrelic_background_job( is_cli() );
		set_exception_handler( 'newrelic_notice_error' );
		set_error_handler( 'newrelic_notice_error' );
		if ( is_cli() ) {
			newrelic_ignore_apdex();
		}
	}

	function tc_start_tr_transaction( $name = null ) {
		if ( ! nr_enabled() ) {
			return;
		}
		newrelic_end_transaction( false );
		newrelic_start_transaction( NEW_RELIC_APP );
		if ( ! is_empty( $name ) ) {
			newrelic_name_transaction( $name );
		}
	}

	function tr_end_tr_transaction() {
		if ( ! nr_enabled() ) {
			return;
		}
		newrelic_end_transaction( false );
	}