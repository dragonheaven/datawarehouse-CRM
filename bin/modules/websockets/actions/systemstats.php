<?php
	defined( 'ABSPATH' ) || die( 'Sorry, but you cannot access this page directly.' );

	function tc_websocket_push_system_stats() {
		try {
			$count = R::getCell( 'SELECT COUNT(id) FROM fileimportrows WHERE importdatetime BETWEEN :d1 AND :d2', array(
				':d1' => date( 'Y-m-d H:i:00', time() - 60 ),
				':d2' => date( 'Y-m-d H:i:59', time() - 60 ),
			) );
			$average = R::getCell( 'SELECT ( AVG( import_end_micro_time - import_start_micro_time ) ) as avgtime FROM fileimportrows' );
		}
		catch ( Exception $e ) {
			trigger_error( $e->getMessage() );
			$count = 0;
			$average = 86400;
		}
		$data = array(
			'cpu' => round( get_server_cpu_usage(), 2 ),
			'memory' => round( get_server_memory_usage(), 2 ),
			'threads' => get_simultanous_instance_count(),
			'imports' => get_raw_simultanous_import_instance_count(),
			'rlm' => absint( $count ),
			'alt' => round( floatval( $average ), 2 ),
		);
		streamer_emit( 'system-stats', $data );
	}