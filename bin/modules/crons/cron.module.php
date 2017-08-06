<?php
	defined( 'ABSPATH' ) || die( 'Sorry, but you cannot access this page directly.' );

	function handle_cron_request( $path = '/', $query = array(), $method = 'GET', $headers = array() ) {
		if ( is_cli() && beginning_matches( '/cron/', $path ) ) {
			$action = filter_cron_action( $path );
			switch ( $action ) {
				case 'test':
					cli_success( null, 'Cron Test Completed Successfully.' );
					break;

				case 'cleanup-phones':
					//tc_cron_cleanup_phones();
					cli_failure( null, 'Nothing Happened' );
					break;

				case 'cleanup-emails':
					//tc_cron_cleanup_emails();
					cli_failure( null, 'Nothing Happened' );
					break;

				case 'cleanup-duplicates':
					tc_cron_cleanup_duplicates();
					cli_failure( null, 'Nothing Happened' );
					break;

				case 'cleanup-finished-import-jobs':
					tc_cron_cleanup_finished_import_jobs();
					cli_failure( null, 'Nothing Happened' );
					break;

				case 'cache-objects':
					tc_cron_cache_objects();
					cli_failure( null, 'Nothing Happened' );
					break;

				case 'update-dashboard-stats':
					//cli_echo( dash_save_list_leads_by_country() );
					//cli_echo( dash_save_list_leads_by_language() );
					//cli_echo( dash_save_list_leads_by_source() );
					//cli_echo( dash_save_list_leads_by_meta() );
					//cli_echo( dash_save_list_leads_by_tag() );
					cli_success( null, 'Completed' );
					break;

				case 'check-database':
					tc_check_database();
					cli_success( null, 'Completed' );
					break;

                case 'check-profile-image':
                    tc_check_profile_image();
                    cli_failure( null, 'Nothing Happened' );
                    break;

				default:
					cli_failure( null, sprintf( 'No such cron action "%s"', $action ) );
					break;
			}
		}
	}

	function filter_cron_action( $input ) {
		$out = $input;
		$out = str_replace( '/cron/', '', $out );
		$out = str_replace( '/', '', $out );
		return $out;
	}

	add_action( 'route', 'handle_cron_request' );