<?php
	defined( 'ABSPATH' ) || die( 'Sorry, but you cannot access this page directly.' );

	function tc_cron_cleanup_finished_import_jobs() {
		cli_echo( 'Looking for finished jobs' );
		try {
			$jobs = R::find( 'fileimportmap', "total_rows IS NOT NULL AND progress IS NOT NULL AND status <> 'done'" );
		}
		catch ( Exception $e ) {
			cli_echo( sprintf( 'Could not find finished jobs due to database error: %s', $e->getmessage() ) );
			$jobs = array();
		}
		if ( can_loop( $jobs ) ) {
			cli_echo( sprintf( 'Found %d potentially finished jobs', count( $jobs ) ) );
			foreach ( $jobs as $job ) {
				//cli_echo( $job );
				$file = str_replace( get_current_user_file_upload_path(), '', $job->file );
				$lcp = absint( $job->lastCheckProgress );
				$lcpc = absint( $job->lastCheckProgressCount );
				cli_echo( sprintf( 'Checking File %s ( job %d )', $file, $job->id ) );
				if ( $lcp === absint( $job->progress ) ) {
					if ( $job->lastCheckProgressCount >= 2 ) {
						cli_echo( sprintf( 'File %s ( job %d ) is done', $file, $job->id ) );
						// close job process
						// step 1: copy the file somewhere else
						if ( ! file_exists( FILE_STORAGE_PATH ) ) {
							mkdir( FILE_STORAGE_PATH );
							chmod( FILE_STORAGE_PATH, 0775 );
						}
						$nf = sprintf( '%s%s', FILE_STORAGE_PATH, $file );
						if ( ! file_exists( $nf ) && file_exists( $job->file ) ) {
							$mv = rename( $job->file, $nf );
							if ( true == $mv ) {
								// step 2: mark job as done
								$job->status = 'done';
								try {
									R::store( $job );
									$save = true;
								}
								catch ( Exception $e ) {
									$save = false;
								}
								if ( true == $save ) {
									// step 3: alert to streamer
									$alert = array(
										'msg' => sprintf( 'File %s [%d] has finished importing', $file, $job->id ),
										'file' => $file,
										'map' => $job->id,
									);
									if ( function_exists( 'streamer_emit' ) ) {
										streamer_emit( 'import-job-finished', $alert );
									}
								}
							}
							else {
								cli_echo( sprintf( 'Could not move file %s ( job %d )', $file, $job->id ) );
							}
						}
					}
					else {
						$job->lastCheckProgressCount ++;
						try {
							R::store( $job );
							cli_echo( sprintf( 'File %s ( job %d ) still in progress for another check [c:%d]', $file, $job->id, $job->lastCheckProgressCount ) );
						}
						catch ( Exception $e ) {
							cli_echo( sprintf( 'Could not update job %d because of database error: %s', $job->id, $e->getmessage() ) );
						}
					}
				}
				else {
					$job->lastCheckProgress = $job->progress;
					$job->lastCheckProgressCount = 0;
					cli_echo( sprintf( 'File %s ( job %d ) has never been scanned', $file, $job->id ) );
					try {
						R::store( $job );
						cli_echo( sprintf( 'File %s ( job %d ) in progress for another check', $file, $job->id ) );
					}
					catch ( Exception $e ) {
						cli_echo( sprintf( 'Could not update job %d because of database error: %s', $job->id, $e->getmessage() ) );
					}
				}
			}
			cli_success( null, 'Closed or Progressed all jobs' );
		}
		else {
			cli_success( null, 'Could not find any active jobs' );
		}
	}