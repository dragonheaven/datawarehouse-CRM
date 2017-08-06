<?php
	defined( 'ABSPATH' ) || die( 'Sorry, but you cannot access this page directly.' );

	function tc_websocket_push_export_stats() {
		$send = array();
		try {
			$rows = R::find( 'exportjobs', 'WHERE printed_rows <> total_rows OR request_time >= :sevendaysago OR request_time IS NULL ORDER BY id DESC', array( ':sevendaysago' => date( 'Y-m-d H:i:s', strtotime( '1 week ago' ) ) ) );
		}
		catch ( Exception $e ) {
			$rows = array();
		}
		if ( can_loop( $rows ) ) {
			foreach ( $rows as $j ) {
				unset( $j->fetchQuery );
				unset( $j->fetchVars );
				unset( $j->leadIds );
				unset( $j->fields );
				unset( $j->conditions );
				unset( $j->filtergroups );
				$j->progress = make_percent( $j->printedRows, $j->totalRows, 2 );
				//$send[ $j->id ] = $j->export();
				array_push( $send, $j->export() );
			}
		}
		streamer_emit( 'export-stats', $send );
		cli_echo( $send );
	}