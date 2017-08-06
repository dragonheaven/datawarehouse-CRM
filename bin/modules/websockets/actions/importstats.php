<?php
	defined( 'ABSPATH' ) || die( 'Sorry, but you cannot access this page directly.' );

	function tc_websocket_push_import_stats() {
		$send = array();
		$umf = tc_get_pending_files();
		$rmf = tc_get_in_progress_files();
		if ( can_loop( $umf ) ) {
			foreach ( $umf as $file => $bean ) {
				$send[ $file ] = array(
					'status' => $bean->getStatus(),
					'total' => absint( $bean->totalRows ),
					'valid' => absint( $bean->validRows ),
					'invalid' => absint( $bean->invalidRows ),
					'duplicate' => absint( $bean->duplicateRows ),
					'attempted' => absint( $bean->progress ),
					'incomplete' => absint( $bean->totalRows - $bean->progress ),
					'progress' => make_percent( $bean->progress, $bean->totalRows ),
				);
			}
		}
		if ( can_loop( $rmf ) ) {
			foreach ( $rmf as $file => $bean ) {
				$send[ $file ] = array(
					'status' => $bean->getStatus(),
					'total' => absint( $bean->totalRows ),
					'valid' => absint( $bean->validRows ),
					'invalid' => absint( $bean->invalidRows ),
					'duplicate' => absint( $bean->duplicateRows ),
					'attempted' => absint( $bean->progress ),
					'incomplete' => absint( $bean->totalRows - $bean->progress ),
					'progress' => make_percent( $bean->progress, $bean->totalRows ),
				);
			}
		}
		streamer_emit( 'import-stats', $send );
	}