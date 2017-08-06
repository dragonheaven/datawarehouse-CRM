<?php
	defined( 'ABSPATH' ) || die( 'Sorry, but you cannot access this page directly.' );

	function ajax_upload_files( $data ) {
		$return = array();
		$files = tc_get_uploaded_files();
		if ( can_loop( $files ) ) {
			if ( ! file_exists( get_current_user_file_upload_path() ) ) {
				mkdir( get_current_user_file_upload_path(), 0777, true );
			}
			foreach ( $files as $name => $tmp ) {
				if ( '.csv' == substr( $name, -4 ) || '.txt' == substr( $name, -4 ) ) {
					$res = move_uploaded_file( $tmp, sprintf( '%s%s', get_current_user_file_upload_path(), $name ) );
				}
			}
		}
		if ( true == $res ) {
			ajax_success( strip_tags( $name ) );
		}
		else {
			ajax_failure( 'Could not upload file' );
		}
	}

	function tc_get_files() {
		$isSysAdmin = get_current_user_info( 'isSysAdmin' );
		
	}

	function tc_get_unmapped_files() {
		$return = array();
		if ( ! file_exists( get_current_user_file_upload_path() ) ) {
			return array();
		}
		$files = scandir( get_current_user_file_upload_path() );
		if ( can_loop( $files ) ) {
			foreach ( $files as $file ) {
				if ( '.csv' == substr( $file, -4 ) || '.txt' == substr( $file, -4 ) ) {
					if ( ! lead_file_mapped( $file ) ) {
						array_push( $return, $file );
					}
				}
			}
		}
		return $return;
	}

	function tc_get_mapped_files() {
		$return = array();
		if ( ! file_exists( get_current_user_file_upload_path() ) ) {
			return array();
		}
		$files = scandir( get_current_user_file_upload_path() );
		if ( can_loop( $files ) ) {
			foreach ( $files as $file ) {
				if ( '.csv' == substr( $file, -4 ) || '.txt' == substr( $file, -4 ) ) {
					if ( lead_file_mapped( $file ) ) {
						array_push( $return, $file );
					}
				}
			}
		}
		return $return;
	}

	function tc_get_pending_files() {
		global $_tc_mapped_files;
		if ( ! can_loop( $_tc_mapped_files ) ) {
			$_tc_mapped_files = tc_get_mapped_files();
		}
		$return = array();
		if ( can_loop( $_tc_mapped_files ) ) {
			foreach ( $_tc_mapped_files as $file ) {
				$fm = tc_get_filemap_for_file( $file, true );
				if ( ! can_loop( $fm->fileimportjobs ) ) {
					$return[ $file ] = $fm;
				}
			}
		}
		return $return;
	}

	function tc_get_in_progress_files() {
		global $_tc_mapped_files;
		if ( ! can_loop( $_tc_mapped_files ) ) {
			$_tc_mapped_files = tc_get_mapped_files();
		}
		$return = array();
		if ( can_loop( $_tc_mapped_files ) ) {
			foreach ( $_tc_mapped_files as $file ) {
				$fm = tc_get_filemap_for_file( $file, true );
				if ( can_loop( $fm->fileimportjobs ) ) {
					$return[ $file ] = $fm;
				}
			}
		}
		return $return;
	}

	function lead_file_mapped( $file ) {
		$file = sprintf( '%s%s', get_current_user_file_upload_path(), $file );
		$fm = tc_get_filemap_for_file( $file, true );
		if ( is_a( $fm, 'RedBeanPHP\OODBBean' ) ) {
			if ( true == $fm->approved ) {
				return true;
			}
			if ( count( tc_get_fileimportjobs_for_file( $file, 'preview', 'NOT LIKE' ) ) > 0 ) {
				return true;
			}
		}
		return false;
	}

	function ajax_get_file_status_info( $data ) {
		$file = get_array_key( 'file', $data );
		if ( is_empty( $file ) || ! lead_file_mapped( $file ) ) {
			ajax_failure( 'No such file or file not mapped' );
		}
		$fm = tc_get_filemap_for_file( $file, true );
		if ( ! is_a( $fm, 'RedBeanPHP\OODBBean' ) ) {
			ajax_failure( 'No such file or file not mapped' );
		}
		$fm->totalRows = 0;
		$fm->validRows = 0;
		$fm->incompleteRows = 0;
		$fm->duplicateRows = 0;
		$fm->invalidRows = 0;
		$fm->progress = 0;
		$jobs = $fm->fileimportjobs();
		if ( can_loop( $jobs ) ) {
			foreach ( $jobs as $job ) {
				$fm->totalRows = $fm->totalRows + $job->get_total_row_count();
				$fm->validRows = $fm->validRows + $job->get_processed_valid_row_count();
				$fm->incompleteRows = $fm->incompleteRows + $job->get_unprocessed_count();
				$fm->duplicateRows = $fm->duplicateRows + $job->get_processed_duplicate_row_count();
				$fm->invalidRows = $fm->invalidRows + $job->get_processed_invalid_row_count();
				$fm->progress = $fm->progress + $job->get_processed_row_count();
			}
		}
		$return = array(
			'total' => absint( $fm->totalRows ),
			'valid' => absint( $fm->validRows ),
			'incomplete' => absint( $fm->incompleteRows ),
			'duplicate' => absint( $fm->duplicateRows ),
			'invalid' => absint( $fm->invalidRows ),
			'progress' => make_percent( $fm->progress, $fm->totalRows ),
		);
		ajax_success( $return );
	}

	function ajax_get_import_job_stats( $data ) {
		$return = array();
		$files = get_array_key( 'file', $data, array() );
		$jids = array();
		if ( can_loop( $files ) ) {
			$dr = array();
			foreach ( $files as $file ) {
				if ( ! array_key_exists( $file, $dr ) ) {
					$dr[ $file ] = array();
				}
				$fm = tc_get_filemap_for_file( $file, true );
				if ( is_a( $fm, 'RedBeanPHP\OODBBean' ) ) {
					$fm->totalRows = 0;
					$fm->validRows = 0;
					$fm->incompleteRows = 0;
					$fm->duplicateRows = 0;
					$fm->invalidRows = 0;
					$fm->progress = 0;
					$jobs = $fm->fileimportjobs();
					if ( can_loop( $jobs ) ) {
						if ( can_loop( $jobs ) ) {
							foreach ( $jobs as $job ) {
								$fm->totalRows = $fm->totalRows + $job->get_total_row_count();
								$fm->validRows = $fm->validRows + $job->get_processed_valid_row_count();
								$fm->incompleteRows = $fm->incompleteRows + $job->get_unprocessed_count();
								$fm->duplicateRows = $fm->duplicateRows + $job->get_processed_duplicate_row_count();
								$fm->invalidRows = $fm->invalidRows + $job->get_processed_invalid_row_count();
								$fm->progress = $fm->progress + $job->get_processed_row_count();
							}
						}
					}
				}
				$dr[ $file ]['total'] = $fm->totalRows;
				$dr[ $file ]['valid'] = $fm->validRows;
				$dr[ $file ]['incomplete'] = $fm->incompleteRows;
				$dr[ $file ]['duplicate'] = $fm->duplicateRows;
				$dr[ $file ]['invalid'] = $fm->invalidRows;
				$dr[ $file ]['progress'] = $fm->progress;
			}
		}
		if ( isset( $jids ) && can_loop( $jids ) ) {
			$query = sprintf( 'SELECT COUNT( fileimportrows.id ) as count, fileimportrows.status, fileimportmap.file FROM fileimportrows LEFT JOIN fileimportjobs ON fileimportrows.fileimportjob_id = fileimportjobs.id LEFT JOIN fileimportmap ON fileimportjobs.fileimportmap_id = fileimportmap.id WHERE fileimportrows.fileimportjob_id IN ( %s ) GROUP BY fileimportrows.status, fileimportmap.file ORDER BY file ASC, status DESC;', implode( ',', $jids ) );
			try {
				$statrows = R::getAll( $query );
				if ( can_loop( $statrows ) ) {
					foreach ( $statrows as $r ) {
						$drf = str_replace( get_current_user_file_upload_path(), '', get_array_key( 'file', $r ) );
						if ( ! array_key_exists( $drf, $dr ) ) {
							$dr[ $drf ] = array();
						}
						$dr[ $drf ][ get_array_key( 'status', $r ) ] = get_array_key( 'count', $r );
						$dr[ $drf ]['progress'] = absint( $dr[ $drf ]['progress'] ) + absint( get_array_key( 'count', $r, 0 ) );
					}
					$dr['progress'] = make_percent( $dr[ $drf ]['progress'], $dr[ $drf ]['total'] );
				}
			}
			catch( Exception $e ) {}
		}
		if ( isset( $dr ) ) {
			ajax_success( $dr );
		}
		ajax_failure( 'Failed to Retrieve Results' );
	}

	function ajax_file_row_action( $data ) {
		$file = get_array_key( 'file', $data );
		$action = get_array_key( 'action', $data );
		switch ( $action ) {
			case 'preview':
				$res = tc_start_import_job_for_file( $file );
				if ( function_exists( 'tc_run_websocket_cli_function' ) ) {
					$cli = tc_run_websocket_cli_function( 'push-import-stats' );
				}
				if ( false == $res ) {
					ajax_failure( 'Job Already in Progress' );
				}
				else {
					ajax_success( 'Enqueued Preview Job' );
				}
				break;

			case 'import':
				$res = tc_start_import_job_for_file( $file, false );
				if ( function_exists( 'tc_run_websocket_cli_function' ) ) {
					$cli = tc_run_websocket_cli_function( 'push-import-stats' );
				}
				if ( false == $res ) {
					ajax_failure( 'Job Already in Progress' );
				}
				else {
					ajax_success( 'Enqueued Import Job' );
				}
				break;

			case 'cancel':
				$res = tc_cancel_import_job_for_file( $file, false );
				if ( function_exists( 'tc_run_websocket_cli_function' ) ) {
					$cli = tc_run_websocket_cli_function( 'push-import-stats' );
				}
				if ( false == $res ) {
					ajax_failure( 'Could not cancel job' );
				}
				else {
					ajax_success( 'Cancelled Import Job' );
				}
				break;

			case 'reset':
				$res = tc_reset_import_job_for_file( $file, false );
				if ( function_exists( 'tc_run_websocket_cli_function' ) ) {
					$cli = tc_run_websocket_cli_function( 'push-import-stats' );
				}
				if ( false == $res ) {
					ajax_failure( 'Could not reset job' );
				}
				else {
					ajax_success( 'Reset Import Job', '/leads/import/' );
				}
				break;

			case 'stop':
				$res = tc_stop_import_job_for_file( $file, false );
				if ( function_exists( 'tc_run_websocket_cli_function' ) ) {
					$cli = tc_run_websocket_cli_function( 'push-import-stats' );
				}
				if ( false == $res ) {
					ajax_failure( 'Could not reset job' );
				}
				else {
					ajax_success( 'Stopped', '/leads/import/' );
				}
				break;

			default:
				if ( function_exists( 'tc_run_websocket_cli_function' ) ) {
					$cli = tc_run_websocket_cli_function( 'push-import-stats' );
				}
				ajax_failure( sprintf( 'No Such Action "%s"', htmlentities( $action ) ) );
				break;
		}
	}