<?php
	defined( 'ABSPATH' ) || die( 'Sorry, but you cannot access this page directly.' );

	function tc_requires_db_update() {
		return true;
		return ( floatval( DB_VERSION ) > floatval( get_option( 'tc_database_version', 0 ) ) );
	}

	function update_database_indexes() {
		cli_echo( 'Updating Indexes' );
		global $tc_fields, $_tc_model_sample_data;
		switch ( DBTYPE ) {
			case 'pgsql':
				$template = "ALTER TABLE %s ADD CONSTRAINT %s UNIQUE ( %s )";
				$order = array(
					'table',
					'iname',
					'column',
				);
				break;

			case 'mysql':
				$template = "ALTER TABLE `%s` ADD UNIQUE INDEX `%s` ( %s )";
				$order = array(
					'table',
					'iname',
					'column',
				);
				break;
		}
		if ( can_loop( $_tc_model_sample_data ) ) {
			$models = array();
			$ld = get_array_key( 'lead', $_tc_model_sample_data, array() );
			unset( $_tc_model_sample_data['lead'] );
			foreach ( $_tc_model_sample_data as $model => $sample ) {
				cli_echo( sprintf( 'Making sample data for model %s', $model ) );
				if ( $model !== 'lead' ) {
					try {
						$models[ $model ] = R::dispense( $model );
						$models[ $model ]->import( $sample );
						$id = R::store( $models[ $model ] );
					}
					catch ( Exception $e ) {
						cli_echo( sprintf( 'Error Generating Sample Data for "%s": %s', $model, $e->getMessage() ) );
					}
				}
			}
			if ( can_loop( $ld ) ) {
				cli_echo( 'Making Sample Data for lead' );
				try {
					$lead = R::dispense( 'lead' );
					$lead->import( $ld );
					foreach ( $models as $model => $bean ) {
						if ( 'language' !== $model ) {
							$lead->{$model} = $bean;
						}
						$sharedList = sprintf( 'shared%sList', ucfirst( $model ) );
						$lead->{$sharedList}[] = $bean;
					}
					$nid = R::store( $lead );
					cli_echo( sprintf( 'Created new lead with ID %d', $nid ) );
					cli_echo( 'Cleaning Up' );
					R::trash( $lead );
					foreach ( $models as $model => $bean ) {
						R::trash( $bean );
					}
					cli_echo( 'All Cleaned Up' );
				}
				catch ( Exception $e ) {
					cli_echo( sprintf( 'Error Generating Sample Data for "lead": %s', $e->getMessage() ) );
				}
			}
		}
		$updates = array();
		if ( can_loop( $tc_fields ) ) {
			foreach ( $tc_fields as $key => $data ) {
				$mc = sprintf( 'Model_%s', $key );
				if ( class_exists( $mc ) && is_subclass_of( $mc, 'RedBean_SimpleModel' ) ) {
					try {
						$obj = R::dispense( $key );
					}
					catch ( Exception $e ) {
						cli_failure( null, 'Database Error', array(
							$e->getMessage(),
						) );
					}
					$column = $obj->getTableIndex();
					if ( ! is_empty( $column ) ) {
						$cols = array( $column, $key );
						sort( $cols );
						$iname = ( 'pgsql' == DBTYPE ) ? sprintf( '%s_unique', implode( '_', $cols ) ) : md5( sprintf( '%s - %s', $key, $column ) );
						$table = $key;
						$query = sprintf( $template, ${$order[0]}, ${$order[1]}, ${$order[2]});
						$updates[ $key ] = $query;
						cli_echo( sprintf( 'Attempting to set index for table "%s"', $key ) );
						while ( false == tc_set_index( $table, $query, $iname, $column ) );
						cli_echo( sprintf( 'Index set for table "%s"', $key ) );
					}
				}
			}
		}
		//set_option( 'tc_database_version', DB_VERSION );
	}

	function tc_check_database() {
		if ( ! tc_requires_db_update() ) {
			cli_success( null, 'Database does not require update' );
		}
		else {
			cli_echo( 'Starting Database Upgrade Procedure' );
			update_database_indexes();
		}
	}

	function tc_set_index( $key, $query, $iname, $column ) {
		$return = false;
		try {
			$res = R::getAll( $query );
			cli_echo( $res );
			$return = true;
		}
		catch ( Exception $e ) {
			$msg = $e->getMessage();
			if ( tc_index_cannot_be_set( $msg ) ) {
				cli_echo( 'Index Already Set' );
				return true;
			}
			else {
				$dup = ( 'pgsql' == DBTYPE ) ? tc_get_duplicate_pgsql_value_from_msg( $msg )  : tc_get_duplicate_mysql_value_from_msg( $msg );
				if ( ! is_empty( $dup ) ) {
					cli_echo( sprintf( 'Found Duplicate Value: "%s"', $dup ) );
					cli_echo( sprintf( 'Need to resoluve duplicate for "%s" value "%s"', $key, $dup ) );
					// get duplicate list
					$dupes = tc_get_duplicate_values( $dup, $key, $column );
					if ( can_loop( $dupes ) ) {
						$c = 0;
						foreach ( $dupes as $index => $bean ) {
							if ( $c > 0 ) {
								// delete
								cli_echo( sprintf( 'Removing %s with ID %d as duplicate', $key, $bean->id ) );
								R::trash( $bean );
							}
							$c ++;
						}
					}
				}
				else {
					cli_echo( 'Index Already Set' );
					return true;
				}
			}
		}
		return $return;
	}

	function tc_get_duplicate_mysql_value_from_msg( $msg ) {
		if ( false === strpos( $msg, 'SQLSTATE[23000]' ) ) {
			return null;
		}
		$msg = str_replace( "SQLSTATE[23000]: Integrity constraint violation: 1062 Duplicate entry '", '', $msg );
		$pos = strpos( $msg, "' for key '" );
		$rep = substr( $msg, $pos );
		$msg = str_replace( $rep, '', $msg );
		return $msg;
	}

	function tc_get_duplicate_pgsql_value_from_msg( $msg ) {
		if ( false === strpos( $msg, 'SQLSTATE[23505]' ) ) {
			return null;
		}
		$fm = $msg;
		$matchcount = preg_match_all( '/\(([^)]*)\)/', $fm, $matches );
		array_shift( $matches );
		if ( can_loop( $matches ) && can_loop( $matches[0] ) ) {
			return $matches[0][1];
		}
		return null;
	}

	function tc_index_cannot_be_set( $msg ) {
		if ( 'mysql' == DBTYPE ) {
			if ( false === strpos( $msg, ' SQLSTATE[42000]' ) ) {
				$fm = $msg;
				cli_echo( $fm );
			}
			return ( false !== strpos( $msg, 'SQLSTATE[42000]' ) );
		}
		if ( 'pgsql' == DBTYPE ) {
			if ( false === strpos( $msg, 'ERROR: ') ) {
				$fm = $msg;
				cli_echo( $fm );
			}
			return ( false !== strpos( $msg, ' SQLSTATE[23505]' ) );
		}
	}

	function tc_get_duplicate_values( $val, $key, $column ) {
		$query = sprintf( 'WHERE %s = :v ORDER BY id ASC', $column );
		$vars = array(
			':v' => $val,
		);
		$return = array();
		try {
			$return = R::find( $key, $query, $vars );
		}
		catch ( Exception $e ) {
			cli_echo( sprintf( 'Could not find duplicates for %s "%s": %s', $key, $val, $e->getMessage() ) );
		}
		return $return;
	}