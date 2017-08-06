<?php
	defined( 'ABSPATH' ) || die( 'Sorry, but you cannot access this page directly.' );

	function tc_get_filemap_for_file( $file, $override = false ) {
		$file = trim( $file );
		if ( false === strpos( $file, get_current_user_file_upload_path() ) ) {
			$file = sprintf( '%s%s', get_current_user_file_upload_path(), $file );
		}
		if ( is_empty( $file ) || ! file_exists( $file ) ) {
			return false;
		}
		try {
			$a = R::findAll( 'fileimportmap', 'file = :file ORDER BY id ASC', array( ':file' => $file ) );
			$e = array_shift( $a );
			if ( can_loop( $a ) ) {
				R::trashAll( $a );
			}
		}
		catch ( Exception $err ) {
			if ( true == DEBUG ) {
				ajax_debug( $err->getMessage() );
			}
			$e = false;
		}
		if ( is_a( $e, 'RedBeanPHP\OODBBean' ) && false === $override ) {
			$count = count( tc_get_fileimportjobs_for_file( $file, 'preview', 'NOT LIKE' ) );
			if ( $count > 0 ) {
				return false;
			}
		}
		else if ( is_a( $e, 'RedBeanPHP\OODBBean' ) ) {
			return $e;
		}
		else {
			try {
				$e = R::dispense( 'fileimportmap' );
				$e->approved = false;
				$e->file = $file;
				$e->delimiter = null;
				$e->encapsulation = null;
				$e->headerRow = 0;
				$e->columnMap = null;
				$e->additional = null;
				$e->status = 'pending';
				$e->user = get_current_user_info( 'user' );
				R::store( $e );
			}
			catch ( Exception $err ) {
				if ( true == DEBUG ) {
					ajax_debug( $err->getMessage() );
				}
				$e = false;
			}
		}
		return $e;
	}

	function tc_get_fileimportjobs_for_file( $file, $status = 'all', $like = 'LIKE' ) {
		$filemap = tc_get_filemap_for_file( $file, true );
		$like = strtoupper( $like );
		$like = ( 'LIKE' == $like ) ? 'LIKE' : 'NOT LIKE';
		$status = strtolower( $status );
		if ( ! is_a( $filemap, 'RedBeanPHP\OODBBean' ) ) {
			return array();
		}
		try {
			if ( 'all' == strtolower( $status ) ) {
				$jobs = R::find( 'fileimportjobs', 'fileimportmap_id = :id', array( ':id' => $filemap->id ) );
			}
			else {
				$jobs = R::find( 'fileimportjobs', sprintf( 'fileimportmap_id = :id AND status %s :status', $like ), array( ':id' => $filemap->id, ':status' => $status ) );
			}
		}
		catch ( Exception $e ) {
			if ( true == DEBUG ) {
				ajax_debug( $e->getMessage() );
			}
			$jobs = array();
		}
		return $jobs;
	}