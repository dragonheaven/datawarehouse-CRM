<?php
	defined( 'ABSPATH' ) || die( 'Sorry, but you cannot access this page directly.' );

	function route_home_page( $path = '/', $query = array(), $method = 'GET', $headers = array() ) {
		if ( ( is_empty( $path ) || '/' == $path ) && 'GET' == $method ) {
			html_success( 'home_page', 'Date Warehouse Home' );
		}
	}

	function ajax_update_system_params( $data ) {
		if ( can_loop( $data ) ) {
			foreach ( $data as $key => $value ) {
				if ( beginning_matches( 'MAX_', $key ) ) {
					update_option( $key, floatval( $value ) );
				}
			}
		}
		ajax_success( 'Updated' );
	}

	function ajax_get_sysinfo( $data ) {
		try {
			$count = R::getCell( 'SELECT COUNT(id) FROM fileimportrows WHERE importdatetime BETWEEN :d1 AND :d2', array(
				':d1' => date( 'Y-m-d H:i:00', time() - 60 ),
				':d2' => date( 'Y-m-d H:i:59', time() - 60 ),
			) );
			$average = R::getCell( 'SELECT ( AVG( import_end_micro_time) - AVG( import_start_micro_time ) ) as avgtime FROM fileimportrows' );
		}
		catch ( Exception $e ) {
			trigger_error( $e->getMessage() );
			$count = 0;
			$average = 86400;
		}
		ajax_success( array(
			'rlm' => absint( $count ),
			'alt' => round( floatval( $average ), 2 ),
		) );
	}

	function route_system_stats_request( $path = '/', $query = array(), $method = 'GET', $headers = array() ) {
		if ( is_cli() && '/update-sys-stats/' === $path ) {
			cli_echo( 'Getting System Stats' );
			$count = 0;
			while( $count < 60 ) {
				cli_echo( sprintf( 'Getting stats for second %d', ( $count + 1 ) ) );
				$get_raw_server_memory_usage = get_raw_server_memory_usage();
				$get_raw_server_cpu_usage = get_raw_server_cpu_usage();
				$get_raw_simultanous_instance_count = get_raw_simultanous_instance_count();
				cache_set( 'server_memory_usage', $get_raw_server_memory_usage );
				cache_set( 'server_cpu_usage', $get_raw_server_cpu_usage );
				cache_set( 'server_instance_count', $get_raw_simultanous_instance_count );
				sleep( 1 );
				$count ++;
			}
			cli_success( null, 'Got System Stats' );
		}
	}