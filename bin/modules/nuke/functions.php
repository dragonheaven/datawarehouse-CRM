<?php
	defined( 'ABSPATH' ) || die( 'Sorry, but you cannot access this page directly.' );

	function handle_nuke_request( $path = '/', $query = array(), $method = 'GET', $headers = array() ) {
		if ( is_cli() && beginning_matches( '/nuke/', $path ) ) {
			cli_echo( 'Clearing all User Sessions' );
			$res = ini_set( 'session.gc_max_lifetime', 0 );
			if ( false === $res ) {
				cli_echo( sprintf( 'Could not set php setting "%s" to "%s"', 'session.gc_max_lifetime', 0 ) );
				$csf = true;
			}
			$res = ini_set( 'session.gc_probability', 1 );
			if ( false === $res ) {
				cli_echo( sprintf( 'Could not set php setting "%s" to "%s"', 'session.gc_probability', 1 ) );
				$csf = true;
			}
			$res = ini_set( 'session.gc_divisor', 1 );
			if ( false === $res ) {
				cli_echo( sprintf( 'Could not set php setting "%s" to "%s"', 'session.gc_divisor', 1 ) );
				$csf = true;
			}
			if ( isset( $csf ) && true == $csf ) {
				cli_echo( 'Failed to clear all user sessions' );
			}
			cli_echo( 'Clearing Database' );
			R::nuke();
			if ( class_exists( 'Memcached' ) ) {
				cli_echo( 'Clearing Memcached' );
				$r = cache_flush();
				cli_echo( ( true == $r ) ? 'Successfully Flushed Memcached' : 'Failed to flush Memcached' );
			}
			//if ( file_exists( get_current_user_file_upload_path() ) ) {
			//	$f = scandir( get_current_user_file_upload_path() );
			//	if ( can_loop( $f ) ) {
			//		cli_echo( 'Clearing Files' );
			//		foreach ( $f as $file ) {
			//			if ( ! in_array( $file, array( '.', '..', 'index.php' ) ) ) {
			//				$del = unlink( sprintf( '%s%s', get_current_user_file_upload_path(), $file ) );
			//				cli_echo( ( true == $del ) ? sprintf( 'Successfully Deleted File "%s"', $file ) : sprintf( 'Failed to Delete "%s"', $file ) );
			//			}
			//		}
			//	}
			//}
			if ( function_exists( 'opcache_reset' ) ) {
				cli_echo( 'Resetting Opcache' );
				$r = opcache_reset();
				cli_echo( ( true == $r ) ? 'Successfully Reset Opcache' : 'Opcache not enabled' );
			}
			cli_success( null, 'Successfully Nuked Warehouse' );
		}
		if ( is_cli() && beginning_matches( '/reset-objects/', $path ) ) {
			$destroy = array(
				'fileimportmap',
				'fileimportjobs',
			);
			foreach ( $destroy as $d ) {
				$dls = R::findAll( $d );
				foreach ( $dls as $dl ) {
					R::trash( $dl );
				}
			}
			cli_success( null, 'Successfully Reset Objects' );
		}
		if ( is_cli() && beginning_matches( '/reset-all-imports/', $path ) ) {
			// step 1: get all insert jobs!
			try {
				cli_echo( 'Fetching All Import Jobs' );
				$jobs = R::find( 'fileimportjobs' );
				if ( ! can_loop( $jobs ) ) {
					cli_echo( 'No jobs to delete' );
				}
				else {
					cli_echo( sprintf( 'Removing %d jobs', count( $jobs ) ) );
					R::trashAll( $jobs );
					cli_echo( sprintf( 'Removed %d jobs', count( $jobs ) ) );
				}
			}
			catch ( Exception $e ) {
				cli_echo( sprintf( 'Exception: %s', $e->getMessage() ) );
			}
			// delete all insert-rows and insert jobs so that all files require re-mapping
			tc_run_websocket_cli_function( 'push-import-stats' );
			cli_success( null, 'Successfully Reset all Import Jobs' );
		}
		if ( is_cli() && beginning_matches( '/nuke-soft/', $path ) ) {
			cli_echo( 'Soft-Nuking Database ( Remove all leads & attributes )' );
			cli_echo( 'This may take some time depending on the size of your database' );
			try {
				cli_echo( 'Removing all beans from type "lead"' );
				$leads = R::findCollection( 'lead' );
				while( $lead = $leads->next() ) {
					cli_echo( sprintf( 'Deleting Lead %s', $lead->id ) );
					R::trash( $lead );
				}
			}
			catch ( Exception $e ) {
				cli_failure( null, 'lead Database Error', array(
					$e->getMessage(),
				) );
			}
			try {
				cli_echo( 'Removing all beans from type "phone"' );
				$phones = R::findCollection( 'phone' );
				while( $phone = $phones->next() ) {
					cli_echo( sprintf( 'Deleting phone %s', $phone->id ) );
					R::trash( $phone );
				}
			}
			catch ( Exception $e ) {
				cli_failure( null, 'phone Database Error', array(
					$e->getMessage(),
				) );
			}
			try {
				cli_echo( 'Removing all beans from type "email"' );
				$emails = R::findCollection( 'email' );
				while( $email = $emails->next() ) {
					cli_echo( sprintf( 'Deleting email %s', $email->id ) );
					R::trash( $email );
				}
			}
			catch ( Exception $e ) {
				cli_failure( null, 'email Database Error', array(
					$e->getMessage(),
				) );
			}
			try {
				cli_echo( 'Removing all beans from type "skype"' );
				$skypes = R::findCollection( 'skype' );
				while( $skype = $skypes->next() ) {
					cli_echo( sprintf( 'Deleting skype %s', $skype->id ) );
					R::trash( $skype );
				}
			}
			catch ( Exception $e ) {
				cli_failure( null, 'skype Database Error', array(
					$e->getMessage(),
				) );
			}
			try {
				cli_echo( 'Removing all beans from type "ip"' );
				$ips = R::findCollection( 'ip' );
				while( $ip = $ips->next() ) {
					cli_echo( sprintf( 'Deleting ip %s', $ip->id ) );
					R::trash( $ip );
				}
			}
			catch ( Exception $e ) {
				cli_failure( null, 'ip Database Error', array(
					$e->getMessage(),
				) );
			}
			try {
				cli_echo( 'Removing all beans from type "source"' );
				$sources = R::findCollection( 'source' );
				while( $source = $sources->next() ) {
					cli_echo( sprintf( 'Deleting skype %s', $source->id ) );
					R::trash( $source );
				}
			}
			catch ( Exception $e ) {
				cli_failure( null, 'source Database Error', array(
					$e->getMessage(),
				) );
			}
			try {
				cli_echo( 'Removing all beans from type "tag"' );
				$tags = R::findCollection( 'tag' );
				while( $tag = $tags->next() ) {
					cli_echo( sprintf( 'Deleting tag %s', $tag->id ) );
					R::trash( $tag );
				}
			}
			catch ( Exception $e ) {
				cli_failure( null, 'tag Database Error', array(
					$e->getMessage(),
				) );
			}
			try {
				cli_echo( 'Removing all beans from type "leadmeta"' );
				$leadmetas = R::findCollection( 'leadmeta' );
				while( $leadmeta = $leadmetas->next() ) {
					cli_echo( sprintf( 'Deleting leadmeta %s', $leadmeta->id ) );
					R::trash( $leadmeta );
				}
			}
			catch ( Exception $e ) {
				cli_failure( null, 'leadmeta Database Error', array(
					$e->getMessage(),
				) );
			}
			try {
				cli_echo( 'Removing all beans from type "language"' );
				$languages = R::findCollection( 'language' );
				while( $language = $languages->next() ) {
					cli_echo( sprintf( 'Deleting language %s', $language->id ) );
					R::trash( $language );
				}
			}
			catch ( Exception $e ) {
				cli_failure( null, 'leadmeta Database Error', array(
					$e->getMessage(),
				) );
			}
			try {
				cli_echo( 'Clearing all File Import Jobs' );
				$fileimportjobss = R::findCollection( 'fileimportjobs' );
				while( $fileimportjobs = $fileimportjobss->next() ) {
					cli_echo( sprintf( 'Deleting File Import Jobs %s', $fileimportjobs->id ) );
					R::trash( $fileimportjobs );
				}
			}
			catch ( Exception $e ) {
				cli_failure( null, 'File Import Job Database Error', array(
					$e->getMessage(),
				) );
			}
			if ( class_exists( 'Memcached' ) ) {
				cli_echo( 'Clearing Memcached' );
				$r = cache_flush();
				cli_echo( ( true == $r ) ? 'Successfully Flushed Memcached' : 'Failed to flush Memcached' );
			}
			cli_success( null, 'Soft-Nuked Database Successfully' );
		}
	}

	function ajax_reset_progress( $data ) {
		if ( function_exists( 'opcache_reset' ) ) {
			$r = opcache_reset();
		}
		if ( isset( $r ) && true == $r ) {
			ajax_success( 'Reset Opcache' );
		}
		else {
			ajax_failure( 'Opcache is not Working' );
		}
	}