<?php
	defined( 'ABSPATH' ) || die( 'Sorry, but you cannot access this page directly.' );

	function handle_websocket_request( $path = '/', $query = array(), $method = 'GET', $headers = array() ) {
		if ( is_cli() && beginning_matches( '/websocket/', $path ) ) {
			$action = filter_websocket_action( $path );
			switch ( $action ) {
				case 'test':
					$res = streamer_emit( 'test', $query );
					cli_success( null, 'Websocket Test Completed Successfully.' );
					break;
				case 'push-system-stats':
					tc_websocket_push_system_stats();
					cli_success( null, 'Websocket Action Completed Successfully.' );
					break;

				case 'push-import-stats':
					tc_websocket_push_import_stats();
					cli_success( null, 'Websocket Action Completed Successfully.' );
					break;

				case 'push-export-stats':
					tc_websocket_push_export_stats();
					cli_success( null, 'Websocket Action Completed Successfully.' );
					break;

				case 'push-lead-stats':
					tc_websocket_push_lead_stats();
					cli_success( null, 'Websocket Action Completed Successfully.' );
					break;

				case 'push-export-queries':
					tc_websocket_push_export_queries();
					cli_success( null, 'Websocket Action Completed Successfully.' );
					break;

				case 'push-leads-per-country':
					tc_websocket_push_leads_per_country();
					cli_success( null, 'Websocket Action Completed Successfully.' );
					break;

                case 'push-get-saved-query-results':
                	cli_echo( 'Starting' );
                	$start = time();
                    //$send = array_merge( array( array(
                    //	'query_id' => '0',
                    //	'query_name' => 'Unfiltered',
                    //	'query_series' => array(),
                    //) ), tc_get_results_from_saved_queries() );
                    $send = tc_get_results_from_saved_queries();
                    $end = time();
					$time = $end - $start;
                    cli_echo( sprintf( 'Queries took %s seconds', $time ) );
					streamer_emit( 'saved-query-results', $send );
					cli_echo( $send );
                    cli_success( null, 'Websocket Action Completed Successfully.' );
                    break;

				default:
					cli_failure( null, sprintf( 'No such websocket action "%s"', $action ) );
					break;
			}
		}
	}

	function filter_websocket_action( $input ) {
		$out = $input;
		$out = str_replace( '/websocket/', '', $out );
		$out = str_replace( '/', '', $out );
		return $out;
	}

	function tc_run_websocket_cli_function( $command, $query = array() ) {
		if ( ! file_exists( FILE_LOG_PATH ) ) {
			mkdir( FILE_LOG_PATH, 0777, true );
		}
		$cmd = sprintf( 'php %s/index.php --path="/websocket/%s/" %s >> %s%s 2>&1 &', ABSPATH, $command, ( can_loop( $query ) ? sprintf( '--query="%s"', http_build_query( $query ) ) : '' ) , FILE_LOG_PATH, sprintf( 'websocket_%s_command.log', str_replace( '-', '_', $command ) ) );
		$res = shell_exec( $cmd );
		return $cmd;
	}

	function tc_get_client_websocket_host() {
		$domain = ( ! is_empty( get_array_key( 'HTTP_HOST', $_SERVER ) ) ) ? get_array_key( 'HTTP_HOST', $_SERVER ) : get_array_key( 'SERVER_NAME', $_SERVER );
		if ( ! in_array( tc_get_request_port(), array( 80, 443 ) ) ) {
			$host = sprintf(
				'//%s%s',
				$domain,
				sprintf( ':%s', tc_get_request_port() )
			);
		}
		else {
			$host = sprintf(
				'//%s',
				$domain
			);
		}
		return $host;
	}