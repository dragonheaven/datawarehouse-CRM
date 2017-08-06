<?php
	defined( 'ABSPATH' ) || die( 'Sorry, but you cannot access this page directly.' );

	function tc_save_import_row( $row ) {
		if ( is_a( $row, 'RedBeanPHP\OODBBean' ) ) {
			try {
				$type = $row->getMeta( 'type' );
			}
			catch ( Exception $e ) {
				cli_echo( sprintf( 'Could not get type: %s', $e->getMessage() ) );
				exit();
			}
			try {
				R::store( $row );
			}
			catch( Exception $e ) {
				if ( ! isset( $type ) ) {
					cli_echo( sprintf( 'Exception: %s' . "\r\n" . '%s', $e->getMessage(), print_r( $e, true ) ) );
				}
				if ( isset( $type ) && 'lead' == $type ) {
					cli_echo( sprintf( 'Exception: %s' . "\r\n" . '%s', $e->getMessage(), print_r( $e, true ) ) );
					exit();
				}
			}
		}
	}

	function tc_cancel_import_job_for_file( $file ) {
		$file = trim( $file );
		if ( false === strpos( $file, get_current_user_file_upload_path() ) ) {
			$file = sprintf( '%s%s', get_current_user_file_upload_path(), $file );
		}
		$return = false;
		$fm = tc_get_filemap_for_file( $file );
		if ( is_a( $fm, 'RedBeanPHP\OODBBean' ) ) {
			if ( can_loop( $fm->fileimportjobs() ) ) {
				foreach ( $fm->fileimportjobs() as $fmj ) {
					if ( is_a( $fmj, 'RedBeanPHP\OODBBean' ) ) {
						if ( 'new' == $fmj->status ) {
							try {
								R::trash( $fmj );
							}
							catch ( Exception $e ) {

							}
						}
					}
				}
			}
		}
		try {
			R::trash( $fm );
			return true;
		}
		catch( Exception $e ) {

		}
		return false;
	}

	function tc_reset_import_job_for_file( $file ) {
		$return = false;
		$file = trim( $file );
		if ( false === strpos( $file, get_current_user_file_upload_path() ) ) {
			$file = sprintf( '%s%s', get_current_user_file_upload_path(), $file );
		}
		$return = false;
		$fm = tc_get_filemap_for_file( $file, true );
		if ( is_a( $fm, 'RedBeanPHP\OODBBean' ) ) {
			if ( can_loop( $fm->fileimportjobs() ) ) {
				foreach ( $fm->fileimportjobs() as $fmj ) {
					if ( is_a( $fmj, 'RedBeanPHP\OODBBean' ) ) {
						$fmj->status = 'new';
						$fmj->validRows = 0;
						$fmj->incompleteRows = 0;
						$fmj->duplicateRows = 0;
						$fmj->invalidRows = 0;
						$fmj->jobProgress = 0;
						try {
							$id = R::store( $fmj );
							$return = true;
						}
						catch( Exception $e ) {
							if ( true == DEBUG ) {
								ajax_failure( $e->getMessage() );
							}
						}
					}
				}
			}
		}
		if ( true == $return ) {
			try {
				$fm->validRows = 0;
				$fm->incompleteRows = 0;
				$fm->duplicateRows = 0;
				$fm->invalidRows = 0;
				$fm->jobProgress = 0;
				R::store( $fm );
			}
			catch ( Exception $e ) {
				if ( true == DEBUG ) {
					ajax_debug( $e->getMessage() );
				}
			}
		}
		if ( function_exists( 'tc_run_websocket_cli_function' ) ) {
			$cli = tc_run_websocket_cli_function( 'push-import-stats' );
		}
		return $return;
	}

	function tc_stop_import_job_for_file( $file, $preview = false ) {
		$return = false;
		$file = trim( $file );
		if ( false === strpos( $file, get_current_user_file_upload_path() ) ) {
			$file = sprintf( '%s%s', get_current_user_file_upload_path(), $file );
		}
		$return = false;
		$fm = tc_get_filemap_for_file( $file, true );
		if ( is_a( $fm, 'RedBeanPHP\OODBBean' ) ) {
			if ( can_loop( $fm->fileimportjobs() ) ) {
				foreach ( $fm->fileimportjobs() as $fmj ) {
					if ( is_a( $fmj, 'RedBeanPHP\OODBBean' ) ) {
						$return = tc_stop_import_job_by_id( $fmj->id );
						cache_set( sprintf( 'kill_%d', $fmj->id ), 'kill' );
						try {
							R::trash( $fmj );
						}
						catch( Exception $e ) {
							trigger_error( $e->getmessage() );
						}
					}
				}
			}
		}
		try {
			R::trash( $fm );
		}
		catch ( Exception $e ) {
			ajax_failure( $e->getMessage() );
		}
		$return = unlink( $file );
		return $return;
	}

	function tc_stop_import_job_by_id( $id = 0 ) {
		$cmd1 = sprintf( "kill $( ps aux | grep '[p]hp' | grep 'fmj_id=%d' | awk '{print $2}' )", $id );
		$cmd2 = sprintf( "kill $( ps aux | grep '[p]hp' | grep 'create-import-rows-from-job' | grep %d | awk '{print $2}' )", $id );
		$res = shell_exec( $cmd1 );
		$res2 = shell_exec( $cmd2 );
		return array( $cmd1, $cmd2 );
	}

	function tc_start_import_job_for_file( $file, $preview = true ) {
		$file = trim( $file );
		if ( false === strpos( $file, get_current_user_file_upload_path() ) ) {
			$file = sprintf( '%s%s', get_current_user_file_upload_path(), $file );
		}
		$return = false;
		$fm = tc_get_filemap_for_file( $file );
		if ( is_a( $fm, 'RedBeanPHP\OODBBean' ) ) {
			if ( ! can_loop( $fm->fileimportjobs() ) ) {
				try {
					$fmj = R::dispense( 'fileimportjobs' );
					$fmj->status = 'new';
					$fmj->importAfterPreview = ( false == $preview );
					$fmj->fileimportmap = $fm;
					$fmj->totalRows = 0;
					$fmj->validRows = 0;
					$fmj->incompleteRows = 0;
					$fmj->duplicateRows = 0;
					$fmj->invalidRows = 0;
					$fmj->jobProgress = 0;
					$fmj->user = get_current_user_info( 'user' );
					$id = R::store( $fmj );
					$return = ( intval( $id ) > 0 );
				}
				catch ( Exception $e ) {
					if( true == DEBUG ) {
						ajax_failure( $e->getMessage() );
					}
				}
			}
		}
		return $return;
	}

	function tc_create_import_rows_for_all_files() {
		global $_tc_mapped_files;
		if ( ! can_loop( $_tc_mapped_files ) ) {
			$_tc_mapped_files = tc_get_mapped_files();
		}
		$return = array();
		if ( can_loop( $_tc_mapped_files ) ) {
			foreach ( $_tc_mapped_files as $file ) {
				$file = trim( $file );
				if ( false === strpos( $file, get_current_user_file_upload_path() ) ) {
					$file = sprintf( '%s%s', get_current_user_file_upload_path(), $file );
				}
				$fm = tc_get_filemap_for_file( $file, true );
				if ( is_a( $fm, 'RedBeanPHP\OODBBean' ) ) {
					$fmjs = $fm->fileimportjobs();
					if ( can_loop( $fmjs ) ) {
						foreach ( $fmjs as $fmj ) {
							if ( 'new' == $fmj->status ) {
								$memusage = floatval( get_server_memory_usage() );
								while( $memusage >= get_option( 'MAX_MEM_USAGE_PERCENT', MAX_MEM_USAGE_PERCENT, true ) ) {
									cli_echo( sprintf( 'Waiting %s second(s) for memory to drop below %d%s. Currently at %s%s', get_option( 'MAX_MEMORY_SLEEP_WAIT_TIME', MAX_MEMORY_SLEEP_WAIT_TIME, true ), get_option( 'MAX_MEM_USAGE_PERCENT', MAX_MEM_USAGE_PERCENT, true ), '%', $memusage, '%' ) );
									tc_sleep( get_option( 'MAX_MEMORY_SLEEP_WAIT_TIME', MAX_MEMORY_SLEEP_WAIT_TIME, true ) );
									$memusage = floatval( get_server_memory_usage() );
								}
								$simuimportjobcount = get_raw_simultanous_import_instance_count();
								if ( $simuimportjobcount >= get_option( 'MAX_SIMULTANOUS_IMPORT_JOBS', MAX_SIMULTANOUS_IMPORT_JOBS, true ) ) {
									cli_failure( null, sprintf( 'Cannot run more than %d import jobs concurrently. Currently at %s', get_option( 'MAX_MEMORY_SLEEP_WAIT_TIME', MAX_MEMORY_SLEEP_WAIT_TIME, true ), get_option( 'MAX_SIMULTANOUS_IMPORT_JOBS', MAX_SIMULTANOUS_IMPORT_JOBS, true ), $simuimportjobcount ) );
								}
								$res = tc_run_lead_cli_function( 'create-import-rows-from-job', $fmj->id );
								$return[ $fmj->id ] = $res;
							}
						}
					}
				}
			}
		}
		return $return;
	}

	function tc_create_import_rows_for_job( $jobId = 0 ) {
		$jobId = absint( $jobId );
		$rows = array();
		if ( 0 == $jobId ) {
			cli_failure( null, 'Invalid Job ID', array( sprintf( 'No job with ID %d Exists', $jobId ) ) );
		}
		try {
			$fmj = R::cachedLoad( 'fileimportjobs', $jobId );
		}
		catch ( Exception $e ) {
			if ( true == DEBUG ) {
				cli_failure( $e->getMessage() );
			}
		}
		if ( is_a( $fmj, 'RedBeanPHP\OODBBean' ) ) {
			$return = array();
			$fmj->totalRows = 0;
			$fmj->validRows = 0;
			$fmj->incompleteRows = 0;
			$fmj->duplicateRows = 0;
			$fmj->invalidRows = 0;
			$fmj->status = 'filtering';
			try {
				R::store( $fmj );
			}
			catch ( Exception $e ) {
				cli_echo( sprintf( 'Exception: %s', $e->getMessage() ) );
			}
			$fm = $fmj->getFileImportMap();
			if ( is_a( $fm, 'RedBeanPHP\OODBBean' ) ) {
				$map = $fm->getFileMap();
				$rows = $fm->getFileRows();
				$keys = array();
				if ( can_loop( get_array_key( 'cm', $map ) ) ) {
					foreach ( $map['cm'] as $col ) {
						array_push( $keys, ( ! is_empty( get_array_key( 'newkey', $col ) ) ? get_array_key( 'newkey', $col ) : get_array_key( 'fieldmap', $col ) ) );
					}
				}
				$ia = array();
				if ( can_loop( $rows ) ) {
					foreach ( $rows as $rowindex => $row ) {
						if ( 'kill' == cache_get( sprintf( 'kill_%d', $fmj->id ), 'nokill' ) ) {
							cli_success( 'Caught Kill Signal' );
						}
						$memusage = floatval( get_server_memory_usage() );
						while( $memusage >= get_option( 'MAX_MEM_USAGE_PERCENT', MAX_MEM_USAGE_PERCENT, true ) ) {
							cli_echo( sprintf( 'Waiting %s second(s) for memory to drop below %d%s. Currently at %s%s', get_option( 'MAX_MEMORY_SLEEP_WAIT_TIME', MAX_MEMORY_SLEEP_WAIT_TIME, true ), get_option( 'MAX_MEM_USAGE_PERCENT', MAX_MEM_USAGE_PERCENT, true ), '%', $memusage, '%' ) );
							tc_sleep( get_option( 'MAX_MEMORY_SLEEP_WAIT_TIME', MAX_MEMORY_SLEEP_WAIT_TIME, true ) );
							$memusage = floatval( get_server_memory_usage() );
						}
						$simucount = get_simultanous_instance_count();
						while( $simucount >= get_option( 'MAX_SIMULTANOUS_THREADS', MAX_SIMULTANOUS_THREADS, true ) ) {
							cli_echo( sprintf( 'Waiting %s second(s) simultanous thread count to drop under %d. Currently at %d', get_option( 'MAX_THREAD_SLEEP_WAIT_TIME', MAX_THREAD_SLEEP_WAIT_TIME, true ), get_option( 'MAX_SIMULTANOUS_THREADS', MAX_SIMULTANOUS_THREADS, true ), $simucount ) );
							tc_sleep( get_option( 'MAX_THREAD_SLEEP_WAIT_TIME', MAX_THREAD_SLEEP_WAIT_TIME, true ) );
							$simucount = get_simultanous_instance_count();
						}
						$passthrough = array(
							'keys' => $keys,
							'map' => $map,
							'row' => $row,
							'rowindex' => $rowindex,
							'fmj_id' => $fmj->id,
						);
						if ( true == MULTITHREAD_IMPORTING ) {
							$cachekey = md5( sprintf( '%d-%s', $rowindex, print_r( $passthrough, true ) ) );
							cache_update( $cachekey, @serialize( $passthrough ) );
							$fb = tc_run_lead_cli_function( 'import-row', $rowindex, array(
								'cachekey' => $cachekey,
							) );
							cli_echo( $fb );
						}
						else {
							cli_echo( sprintf( 'Starting import for row %d', $rowindex ) );
						tc_import_from_row( $passthrough );
						}
					}
				}
			}
		}
		$fmj->status = 'pending';
		try {
			unset( $fmj->fileimportrows );
			R::store( $fmj );
		}
		catch ( Exception $e ) {
			cli_echo( sprintf( 'Exception: %s', $e->getMessage() ) );
		}
		if ( true == $fmj->import_after_preview ) {
			$res = tc_run_lead_cli_function( 'import-rows-as-leads', $fmj->id );
		}
		cli_success( null, 'Opened Row Query' );
	}

	function tc_make_lead_from_row( $row, $save = true, $debug = false ) {
		$return = false;
		try {
			$return = LeadEngine::process( $row, $save, $debug );
		}
		catch ( Exception $e ) {
			if ( true == $debug ) {
				ajax_failure( sprintf( 'Row with data "%s" failed with message: %s', http_build_query( $row ), $e->getMessage() ) );
			}
			trigger_error( sprintf( 'Row with data "%s" failed with message: %s', http_build_query( $row ), $e->getMessage() ) );
		}
		if ( true == $debug ) {
			ajax_success( print_r( array(
				'msg' => 'Got to importing.functions:333',
				'row' => $row,
				'ret' => $return,
				'rere' => $return->sharedIpList,
			), true )  );
		}
		return $return;
	}

	function tc_fix_fieldname( $name ) {
		return preg_replace( "/\_\d*/", '', $name );
	}

	function tc_import_from_row( $data ) {
		$st = time() + microtime();
		if ( true == MULTITHREAD_IMPORTING ) {
			$cachekey = get_array_key( 'cachekey', $data );
			$res = @unserialize( cache_get( $cachekey ) );
			if ( can_loop( $res ) ) {
				$data = $res;
				cache_delete( $cachekey );
			}
		}
		if ( function_exists( 'tc_start_tr_transaction' ) ) {
			tc_start_tr_transaction( 'row-import' );
		}
		$status = 'invalid';
		$map = get_array_key( 'map', $data, array() );
		$keys = get_array_key( 'keys', $data, array() );
		@array_walk( $keys, 'tc_fix_fieldname' );
		$row = get_array_key( 'row', $data, array() );
		$rowindex = absint( get_array_key( 'rowindex', $data, 0 ) );
		if ( absint( $rowindex ) > 100 ) {
			if ( true == FREEZE_DB ) {
				R::freeze( TRUE );
			}
		}
		$fmj = R::cachedLoad( 'fileimportjobs', absint( get_array_key( 'fmj_id', $data ) ) );
		if ( ! can_loop( $row ) ) {
			$row = $fmj->getRowFromFile( $rowindex );
		}
		if ( is_a( $fmj, 'RedBeanPHP\OODBBean' ) ) {
			try {
				$fir = R::findOne( 'fileimportrows', 'row_id = :i AND fileimportjob_id = :f', array( ':i' => $rowindex, ':f' => $fmj->id ) );
			}
			catch ( Exception $e ) {
				if ( true == DEBUG ) {
					cli_echo( sprintf( 'Could not find file row: %s', $e->getMessage() ) );
				}
				$fir = null;
			}
			if ( ! is_a( $fir, 'RedBeanPHP\OODBBean' ) ) {
				try {
					$fir = R::dispense( 'fileimportrows' );
					$fir->fileimportjobId = $fmj->id;
					$fir->rowId = $rowindex;
					$fir->status = 'invalid';
				}
				catch ( Exception $e ) {
					if ( true == DEBUG ) {
						cli_echo( sprintf( 'Could not dispense file row: %s', $e->getMessage() ) );
					}
					$fir = null;
				}
			}
			$fir->importStartMicroTime = time() + microtime();
			if ( count( $row ) == count( $keys ) ) {
				$ia = deep_array_combine( $keys, $row );
				$cia = deep_array_merge( $ia, get_array_key( 'ac', $map, array() ) );
				$fia = tc_filter_fields_deep( $cia );
				$fir->data = serialize( $fia );
				$fir->imported = false;
				$fir->importdatetime = date( 'Y-m-d H:i:s' );
				cli_echo( sprintf( 'Added File Import Row for Row Index "%d" from Job ID "%d"', $rowindex, $fmj->id ) );
				$lead = tc_make_lead_from_row( $fia, false );
				if ( is_a( $lead, 'RedBeanPHP\OODBBean' ) ) {
					if (
						( absint( $rowindex ) > 100 && ! table_exists( 'email' ) && ! table_exists( 'phone' ) )
					) {
						$fir->status = 'invalid';
						$status = 'invalid';
						try {
							R::trash( $lead );
						}
						catch ( Exception $e ) {
							cli_echo( sprintf( 'Could not trash lead with ID %d', $lead->id ) );
						}
					}
					else {
						if ( 0 == $lead->id ) {
							$fir->status = 'valid';
							$status = 'valid';
						}
						else {
							$fir->status = 'duplicate';
							$status = 'duplicate';
						}
						if ( true == $fmj->import_after_preview ) {
							try {
								$lid = R::store( $lead );
								cli_echo( sprintf( 'Saved lead with ID "%d" for Row Index "%d" from Job ID "%d"', $lid, $rowindex, $fmj->id ) );
							}
							catch( Exception $e ) {
								// this means we need to rebound!
								cli_echo( sprintf( 'Failed to lead for Row Index "%d" from Job ID "%d" due to duplicate', $rowindex, $fmj->id ) );
								$rebound = true;
							}
							if ( isset( $rebound ) && true == $rebound ) {
								try {
									cli_echo( sprintf( 'Trying to Rebound Lead for Row Index "%d" from Job ID "%d"', $rowindex, $fmj->id ) );
									$lead = tc_make_lead_from_row( $fia );
									cli_echo( sprintf( 'Saved Rebounded lead with ID "%d" for Row Index "%d" from Job ID "%d"', $lead->id, $rowindex, $fmj->id ) );
								}
								catch ( Exception $e ) {
									cli_echo( sprintf( 'Failed rebound lead for Row Index "%d" from Job ID "%d": %s', $rowindex, $fmj->id, $e->getMessage() ) );
								}
							}
							$fir->sharedLeadList[ $lead->id ] = $lead;
							$fir->imported = true;
						}
					}
				}
			}
			if ( is_a( $fir, 'RedBeanPHP\OODBBean' ) ) {
				cli_echo( 'Saving Changes to Import Row Record' );
				$fir->status = $status;
				tc_save_import_row( $fir );
			}
		}
		if ( isset( $fir ) && is_a( $fir, 'RedBeanPHP\OODBBean' ) ) {
			$fir->status = $status;
			$fir->importEndMicroTime = time() + microtime();
			tc_save_import_row( $fir );
		}
		if ( function_exists( 'tr_end_tr_transaction' ) ) {
			tr_end_tr_transaction();
		}
		if ( 'invalid' == $fir->status ) {
			if ( isset( $lead ) ) {
				cli_echo( $lead );
			}
			else {
				cli_echo( 'No Lead!' );
				cli_echo( count( $row ) == count( $keys ) );
			}
		}
		$et = time() + microtime();
		$seconds = $et - $st;
		cli_echo( sprintf( 'Took %s Seconds to process', $seconds ) );
		if ( nr_enabled() ) {
			newrelic_record_custom_event( 'row-import', array(
				'time' => $seconds,
				'status' => $fir->status,
				'job' => $fmj->id,
				'row' => $fir->id,
			) );
		}
		if ( true == MULTITHREAD_IMPORTING ) {
			cli_success( null, sprintf( 'Job %d Row %d Parsed with Status: "%s"', $fmj->id, $fir->id, $fir->status ) );
		}
		else {
			cli_echo( sprintf( 'Job %d Row %d Parsed with Status: "%s"', $fmj->id, $fir->id, $fir->status ) );
		}
	}

	function tc_get_import_map_stats() {
		$umf = tc_get_pending_files();
		$rmf = tc_get_in_progress_files();
		if ( can_loop( $umf ) ) {
			foreach ( $umf as $file => $map ) {
				cli_echo( sprintf( 'Starting to count for map %d', $map->id ) );
				$map->totalRows = 0;
				$map->validRows = 0;
				$map->incompleteRows = 0;
				$map->duplicateRows = 0;
				$map->invalidRows = 0;
				$map->progress = 0;
				$jobs = $map->fileimportjobs();
				if ( can_loop( $jobs ) ) {
					foreach ( $jobs as $job ) {
						$map->totalRows = $map->totalRows + $job->get_total_row_count();
						$map->validRows = $map->validRows + $job->get_processed_valid_row_count();
						$map->incompleteRows = $map->incompleteRows + $job->get_unprocessed_count();
						$map->duplicateRows = $map->duplicateRows + $job->get_processed_duplicate_row_count();
						$map->invalidRows = $map->invalidRows + $job->get_processed_invalid_row_count();
						$map->progress = $map->progress + $job->get_processed_row_count();
					}
				}
				try {
					R::store( $map );
					cli_echo( sprintf(
						'Map %d Stats:		Total Rows: %d 		Parsed Rows: %d		Valid Rows: %d 		Incomplete Rows: %d 	Duplicate Rows: %d 		Invalid Rows: %d',
						$map->id,
						$map->totalRows,
						$map->progress,
						$map->validRows,
						$map->incompleteRows,
						$map->duplicateRows,
						$map->invalidRows
					) );
				}
				catch( Exception $e ) {
					cli_echo( $e->getMessage() );
				}
			}
		}
		if ( can_loop( $rmf ) ) {
			foreach ( $umf as $file => $map ) {
				cli_echo( sprintf( 'Starting to count for map %d', $map->id ) );
				$map->totalRows = 0;
				$map->validRows = 0;
				$map->incompleteRows = 0;
				$map->duplicateRows = 0;
				$map->invalidRows = 0;
				$map->progress = 0;
				$jobs = $map->fileimportjobs();
				if ( can_loop( $jobs ) ) {
					foreach ( $jobs as $job ) {
						$map->totalRows = $map->totalRows + $job->get_total_row_count();
						$map->validRows = $map->validRows + $job->get_processed_valid_row_count();
						$map->incompleteRows = $map->incompleteRows + $job->get_unprocessed_count();
						$map->duplicateRows = $map->duplicateRows + $job->get_processed_duplicate_row_count();
						$map->invalidRows = $map->invalidRows + $job->get_processed_invalid_row_count();
						$map->progress = $map->progress + $job->get_processed_row_count();
					}
				}
				try {
					R::store( $map );
					cli_echo( sprintf(
						'Map %d Stats:		Total Rows: %d 		Parsed Rows: %d		Valid Rows: %d 		Incomplete Rows: %d 	Duplicate Rows: %d 		Invalid Rows: %d',
						$map->id,
						$map->totalRows,
						$map->progress,
						$map->validRows,
						$map->incompleteRows,
						$map->duplicateRows,
						$map->invalidRows
					) );
				}
				catch( Exception $e ) {
					cli_echo( $e->getMessage() );
				}
			}
		}
		cli_success( null, 'Finished Job' );
	}