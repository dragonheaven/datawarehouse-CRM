<?php
	defined( 'ABSPATH' ) || die( 'Sorry, but you cannot access this page directly.' );

	class Model_fileimportjobs extends RedBean_SimpleModel {
		public function open() {
			
		}

		public function asArray() {
			$return = array();
			foreach ( $this as $key => $value ) {
				$return[ $key ] = $value;
			}
			return $return;
		}

		public function getFileImportMap() {
			$return = false;
			if ( 0 !== absint( $this->id ) ) {
				try {
					$return = R::load( 'fileimportmap', $this->bean->fileimportmap_id );
				}
				catch ( Exception $e ) {
					
				}
			}
			return $return;
		}

		public function fileimportrows() {
			try {
				return ( 0 == $this->id ) ? array() : R::find( 'fileimportrows', 'fileimportjob_id = :id', array( ':id' => $this->id ) );
			}
			catch ( Exception $e ) {
				return array();
			}
		}

		public function getRowFromFile( $row = 0 ) {
			$return = array();
			$map = $this->getFileImportMap();
			if ( is_a( $map, 'RedBeanPHP\OODBBean' ) ) {
				$rd = $map->getFileRows( 1, absint( $row ) );
				$return = array_shift( $rd );
				cli_echo( $rd );
			}
			return $return;
		}

		public function getimportstats() {
			$totalRows = 0;
			$validRows = 0;
			$incompleteRows = 0;
			$duplicateRows = 0;
			$invalidRows = 0;
			$progress = 0;
			try {
				$totalRows = $this->get_file_row_count( R::getCell( 'SELECT file FROM fileimportmap WHERE id = :id', array( ':id' => $this->bean->fileimportmap_id ) ) );
				$jobInfo = R::getRow( "SELECT ( SELECT COUNT(id) FROM fileimportrows WHERE fileimportjob_id = :jid ) as progress, ( SELECT COUNT(id) FROM fileimportrows WHERE fileimportjob_id = :jid AND status = 'valid' ) as valid, ( SELECT COUNT(id) FROM fileimportrows WHERE fileimportjob_id = :jid AND status = 'incomplete' ) as incompletes, ( SELECT COUNT(id) FROM fileimportrows WHERE fileimportjob_id = :jid AND status = 'duplicate' ) as duplicates, ( SELECT COUNT(id) FROM fileimportrows WHERE fileimportjob_id = :jid AND status = 'invalid' ) as invalid", array( ':jid' => $this->bean->id ) );
				$validRows = get_array_key( 'valid', $jobInfo, 0 );
				$duplicateRows = get_array_key( 'duplicate', $jobInfo, 0 );
				$invalidRows = get_array_key( 'invalid', $jobInfo, 0 );
				$progress = get_array_key( 'progress', $jobInfo, 0 );
				$incompleteRows = ( absint( $totalRows ) ) - ( absint( $validRows ) + absint( $duplicateRows ) + absint( $invalidRows ) );

			}
			catch ( Exception $e ) {}
			return array(
				'totalRows' => $totalRows,
				'validRows' => $validRows,
				'incompleteRows' => $incompleteRows,
				'duplicateRows' => $duplicateRows,
				'invalidRows' => $invalidRows,
				'progress' => $progress,
			);
		}

		public function get_file_row_count( $file = null ) {
			$return = 0;
			if ( file_exists( $file ) ) {
				$contents = file_get_contents( $file );
				$rows = explode( "\n", $contents );
				if ( can_loop( $rows ) && 1 == count( $rows ) ) {
					$rows = explode( "\r", $contents );
				}
				$return = count( $rows );
			}
			return absint( $return );
		}

		public function get_total_row_count() {
			$ck = sprintf( '$_tc_import_map_%d_total_row_count', $this->bean->fileimportmap_id );
			global ${ $ck };
			if ( ! is_empty( ${$ck} ) ) {
				return ${ $ck };
			}
			try {
				$return = absint( $this->get_file_row_count( R::getCell( 'SELECT file FROM fileimportmap WHERE id = :id', array( ':id' => $this->bean->fileimportmap_id ) ) ) );
			}
			catch( Exception $e ) {
				$return = 0;
			}
			${ $ck } = $return;
			return $return;
		}

		public function get_processed_row_count() {
			$ck = sprintf( '$_tc_import_map_%d_processed_row_count', $this->bean->fileimportmap_id );
			global ${ $ck };
			if ( ! is_empty( ${$ck} ) ) {
				return ${ $ck };
			}
			try {
				$return = absint( R::getCell( 'SELECT COUNT(id) FROM fileimportrows WHERE fileimportjob_id = :jid', array( ':jid' => $this->bean->id ) ) );
			}
			catch( Exception $e ) {
				$return = 0;
			}
			${ $ck } = $return;
			return $return;
		}

		public function get_processed_valid_row_count() {
			$ck = sprintf( '$_tc_import_map_%d_valid_row_count', $this->bean->fileimportmap_id );
			global ${ $ck };
			if ( ! is_empty( ${$ck} ) ) {
				return ${ $ck };
			}
			try {
				$return = absint( R::getCell( 'SELECT COUNT(id) FROM fileimportrows WHERE fileimportjob_id = :jid AND status = :status', array( ':jid' => $this->bean->id, ':status' => 'valid' ) ) );
			}
			catch( Exception $e ) {
				$return = 0;
			}
			${ $ck } = $return;
			return $return;
		}

		public function get_processed_invalid_row_count() {
			$ck = sprintf( '$_tc_import_map_%d_invalid_row_count', $this->bean->fileimportmap_id );
			global ${ $ck };
			if ( ! is_empty( ${$ck} ) ) {
				return ${ $ck };
			}
			try {
				$return = absint( R::getCell( 'SELECT COUNT(id) FROM fileimportrows WHERE fileimportjob_id = :jid AND status = :status', array( ':jid' => $this->bean->id, ':status' => 'invalid' ) ) );
			}
			catch( Exception $e ) {
				$return = 0;
			}
			${ $ck } = $return;
			return $return;
		}

		public function get_processed_duplicate_row_count() {
			$ck = sprintf( '$_tc_import_map_%d_duplicate_row_count', $this->bean->fileimportmap_id );
			global ${ $ck };
			if ( ! is_empty( ${$ck} ) ) {
				return ${ $ck };
			}
			try {
				$return = absint( R::getCell( 'SELECT COUNT(id) FROM fileimportrows WHERE fileimportjob_id = :jid AND status = :status', array( ':jid' => $this->bean->id, ':status' => 'duplicate' ) ) );
			}
			catch( Exception $e ) {
				$return = 0;
			}
			${ $ck } = $return;
			return $return;
		}

		public function get_unprocessed_count() {
			$return = absint( $this->get_total_row_count() - $this->get_processed_row_count() );
			return $return;
		}
	}