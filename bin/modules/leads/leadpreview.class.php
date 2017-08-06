<?php
	defined( 'ABSPATH' ) || die( 'Sorry, but you cannot access this page directly.' );

	class map_preview {
		public $existingColumns = array();
		public $columns = array();
		private $delimiter = ',';
		private $encapsulation = "'";
		private $headerRow = 0;

		function __construct( $delimiter = ',', $encapsulation = "'", $headerRow = 0, $file = null ) {
			$this->delimiter = $this->filter_csv_data( $delimiter );
			$this->encapsulation = $this->filter_csv_data( $encapsulation );
			$this->headerRow = $this->filter_absint( $headerRow );
			$this->existingColumns = tc_get_existing_columns();
			if ( ! is_empty( $file ) ) {
				$this->load_file( $file );
			}
		}

		function load_file( $file ) {
			$rawRows = $this->load_csv_file( $file, $this->delimiter, $this->encapsulation, 10 );
			$rawRows = $this->filter_raw_rows( $rawRows );
			$columns = array();
			if ( can_loop( $rawRows ) ) {
				$keys = get_array_key( $this->headerRow, $rawRows, array() );
				$indexToKey = ( can_loop( $keys ) ) ? array_flip( $keys ) : array();
				foreach ( $rawRows as $row ) {
					if ( can_loop( $row ) ) {
						foreach ( $row as $index => $value ) {
							if ( ! can_loop( $columns ) ) {
								$columns = array();
							}
							if ( ! isset( $columns[ $index] ) || ! is_array( $columns[ $index ] ) ) {
								$columns[ $index ] = array();
							}
							$columns[ $index ]['index'] = $index;
							$columns[ $index ]['key'] = ( can_loop( $keys ) && count( $keys ) > $index ) ? $keys[ $index ] : null;
							$columns[ $index ]['fieldmap'] = $this->get_default_fieldmap_from_fieldname( $columns[ $index ]['key'] );
							if ( ! can_loop( $columns[ $index ] ) ) {
								$columns[ $index ] = array();
							}
							if ( ! array_key_exists( 'preview', $columns[ $index ] ) || ! is_array( $columns[ $index ]['preview'] ) ) {
								$columns[ $index ]['preview'] = array();
							}
							array_push( $columns[ $index ]['preview'], htmlentities( $value ) );
						}
					}
				}
			}
			if ( can_loop( $columns ) ) {
				$this->columns = $columns;
			}
			// let's see if there's a map which already exists!
			$emap = tc_get_filemap_for_file( $file, true );
			if ( is_a( $emap, 'RedBeanPHP\OODBBean' ) ) {
				$cm = @unserialize( $emap->columnMap );
				$af = @unserialize( $emap->additional );
				if ( can_loop( $this->columns ) ) {
					foreach ( $this->columns as $index => $column ) {
						if ( can_loop( $cm ) && ! array_key_exists( $index, $cm ) ) {
							unset( $this->columns[ $index ] );
						}
						else {
							$fieldmap = ( 'new' == get_array_key( 'fieldmap', $cm[ $index ] ) ) ? get_array_key( 'newkey', $cm[ $index ] ) : get_array_key( 'fieldmap', $cm[ $index ] );
							$this->columns[ $index ]['fieldmap'] = $this->fix_fieldname( $fieldmap );
							$this->columns[ $index ]['default'] = get_array_key( 'default', $cm[ $index ] );
						}
					}
				}
				if ( can_loop( $af ) ) {
					foreach ( $af as $fieldname => $default ) {
						$pa = array();
						$pc = 0;
						while ( $pc < 10 ) {
							array_push( $pa, $default );
							$pc ++;
						}
						$push = array(
							'index' => null,
							'key' => 'new',
							'fieldmap' => $this->fix_fieldname( $fieldname ),
							'preview' => $pa,
							'default' => $default,
						);
						array_push( $this->columns, $push );
					}
				}
			}
			return true;
		}

		private function filter_raw_rows( $rows ) {
			$return = array();
			if ( can_loop( $rows ) ) {
				foreach ( $rows as $index => $row ) {
					$rr = array();
					if ( can_loop( $row ) ) {
						foreach ( $row as $column ) {
							array_push( $rr, fix_data( $column ) );
						}
					}
					array_push( $return, $rr );
				}
			}
			return $return;
		}

		private function fix_fieldname( $name ) {
			return tc_fix_fieldname( $name );
		}

		private function get_default_fieldmap_from_fieldname( $name ) {
			global $tc_fields;
			$name = $this->fix_fieldname( $name );
			$filtered = strtolower( $name );
			$return = null;
			switch ( true ) {
				case (
						( false !== strpos( $name, 'country' ) )
						|| ( false !== strpos( $name, 'iso' ) )
					):
					$return = 'country';
					break;

				case (
					( 'fname' == $name )
					|| ( 'firstname' == $name )
					|| ( 'first name' == $name )
					):
					$return = 'fname';
					break;

				case (
					( 'lname' == $name )
					|| ( 'lastname' == $name )
					|| ( 'last name' == $name )
					):
					$return = 'lname';
					break;

				case (
					( 'mname' == $name )
					|| ( 'middlename' == $name )
					|| ( 'middle name' == $name )
					):
					$return = 'mname';
					break;

				case (
					( false !== strpos( $name, 'province' ) )
					|| ( false !== strpos( $name, 'state' ) )
					|| ( false !== strpos( $name, 'region' ) )
					):
					$return = 'region';
					break;

				case (
						( false !== strpos( $name, 'city' ) )
						|| ( false !== strpos( $name, 'town' ) )
					):
					$return = 'city';
					break;

				case (
						( false !== strpos( $name, 'address' ) )
						|| ( false !== strpos( $name, 'street' ) )
					):
					$return = 'street1';
					break;

				case ( false !== strpos( $name, 'name' ) ):
					$return = 'name';
					break;

				default:
					$return = ( array_key_exists( $filtered, $tc_fields ) && true == get_array_key( 'canset', $tc_fields[ $filtered ], false ) ) ? $filtered : null;
					break;
			}
			return $return;
		}

		private function filter_csv_data( $input, $default = null ) {
			$return = $default;
			if ( is_string( $input ) && ! is_empty( $input ) ) {
				$return = $input;
			}
			return $return;
		}

		private function filter_absint( $input, $default = 0 ) {
			$return = $default;
			if ( is_numeric( $input ) ) {
				$interger = intval( $input );
				if ( $interger < 0 ) {
					$interger = $interger * -1;
				}
				$return = $interger;
			}
			return $return;
		}

		public static function get( $delimiter = ',', $encapsulation = "'", $headerRow = 0, $file ) {
			$c = get_called_class();
			$o = new $c( $delimiter, $encapsulation, $headerRow );
			if ( false === strpos( $file, get_current_user_file_upload_path() ) ) {
				$file = sprintf( '%s%s', get_current_user_file_upload_path(), $file );
			}
			$o->load_file( $file );
			return $o;
		}

		private function load_csv_file( $file, $delimiter = ',', $enclosure = '"', $rowsToLoad = 'all', $rowOffset = 0 ) {
			//return $this->load_csv_file_line_by_line( $file, $delimiter, $enclosure, $rowsToLoad, $rowOffset );
			$return = array();
			if ( file_exists( $file ) ) {
				$contents = file_get_contents( $file );
				$rows = explode( "\n", $contents );
				if ( can_loop( $rows ) && 1 == count( $rows ) ) {
					$rows = explode( "\r", $contents );
				}
				if ( can_loop( $rows ) ) {
					$total = count( $rows );
					$count = 0;
					$target = ( 'all' == strtolower( $rowsToLoad ) ) ? $total : absint( $rowsToLoad );
					if ( absint( $rowOffset ) > 0 ) {
						$oc = 0;
						while ( $oc < $rowOffset ) {
							array_shift( $rows );
							$total = count( $rows );
							$oc ++;
						}
					}
					if ( can_loop( $rows ) && count( $rows ) > 1 ) {
						while ( $count < $target && $count < $total ) {
							if ( $count <= ( count( $rows ) - 1 ) ) {
								array_push( $return, str_getcsv( $rows[ $count ], $delimiter, $enclosure ) );
							}
							$count ++;
						}
					}
				}
			}
			return $return;
		}

		private function load_csv_file_line_by_line( $file, $delimiter = ',', $enclosure = '"', $rowsToLoad = 'all', $rowOffset = 0 ) {
			$return = array();
			$count = 0;
			$target = ( 'all' == strtolower( $rowsToLoad ) ) ? 'all' : absint( $rowsToLoad );
			if ( file_exists( $file ) ) {
				$fh = @fopen( $file );
				if ( $fh ) {
					while( ( $row = @fgets( $fh ) ) !== false ) {
						if ( $count >= $rowOffset ) {
							if ( 'all' == $target || count( $return ) < absint( $target ) ) {
								array_push( $return, str_getcsv( $row, $delimiter, $enclosure ) );
							}
						}
						$count ++;
					}
					@fclose( $fh );
				}
			}
			return $return;
		}
	}