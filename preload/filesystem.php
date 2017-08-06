<?php
	defined( 'ABSPATH' ) || die( 'Sorry, but you cannot access this page directly.' );

	function tc_save_file_contents( $absfile, $content = null, $overwrite = true ) {
		if ( ! is_writable( $absfile ) ) {
			return false;
		}
		$mode = ( true == $overwrite ) ? 'w' : 'a';
		$obj = fopen( $absfile, $mode );
		if ( false !== $obj ) {
			fwrite( $obj, $content );
			fclose( $obj );
			return true;
		}
		return false;
	}

	function tc_get_uploaded_files() {
		global $_files;
		$_files = $_FILES;
		$return = array();
		if ( array_key_exists( 'files', $_files ) ) {
			$names = $_files['files']['name'];
			$tmps = $_files['files']['tmp_name'];
			$info = array_combine( $names, $tmps );
			if ( can_loop( $info ) ) {
				foreach ( $info as $fn => $fl ) {
					if ( ! is_empty( $fl ) ) {
						$return[ $fn ] = $fl;
					}
				}
			}
		}
		return $return;
	}