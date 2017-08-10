<?php
	defined( 'ABSPATH' ) || die( 'Sorry, but you cannot access this page directly.' );

	class Model_fileimportmap extends RedBean_SimpleModel {
		public function open() {
			
		}

		public function getFileMap() {
			$return = array();
			$return['cm'] = unserialize( $this->bean->column_map );
			$return['ac'] = unserialize( $this->bean->additional );
			return $return;
		}

		

		public function fileimportjobs() {
			try {
				return ( 0 == $this->id ) ? array() : R::find( 'fileimportjobs', 'fileimportmap_id = :id', array( ':id' => $this->id ) );
			}
			catch ( Exception $e ) {
				return array();
			}
		}

		public function getStatus() {
			$status = 'unknown';
			$fmjs = $this->fileimportjobs();
			if ( can_loop( $fmjs ) ) {
				foreach ( $fmjs as $fmj ) {
					$status = $fmj->status;
				}
			}
			return $status;
		}
	}
