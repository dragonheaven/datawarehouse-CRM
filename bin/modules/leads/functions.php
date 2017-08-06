<?php
	defined( 'ABSPATH' ) || die( 'Sorry, but you cannot access this page directly.' );

	function route_lead_pages( $path = '/', $query = array(), $method = 'GET', $headers = array() ) {
		if ( beginning_matches( '/leads/', $path ) ) {
			$vars = return_path_vars( $path, '^/leads/([^/]*)/([^/]*)$', array( 'controller', 'item' ) );
			if ( is_cli() ) {
				$p = str_replace( '/leads/', '', $path );
				$parts = explode( '/', $p );
				$np = array();
				foreach ( $parts as $pa ) {
					array_push( $np, str_replace( '/', '', $pa ) );
				}
				list( $vars['controller'], $vars['item'] ) = $np;
			}
			switch ( get_array_key( 'controller', $vars, 'none' ) ) {
				case 'import':
					if ( ! is_empty( get_array_key( 'item', $vars ) ) ) {
						html_success( 'import_item', sprintf( 'Import File %s', get_array_key( 'item', $vars ) ) );
					}
					else {
						html_success( 'import_lobby', 'Import Files to the System' );
					}
					break;

				case 'export':
					if ( ! is_empty( get_array_key( 'item', $vars ) ) ) {
						tc_print_export_item_contents( get_array_key( 'item', $vars ) );
						exit();
					}
					else {
						html_success( 'export_lobby', 'Export Leads from the System' );
					}
					break;

				case 'import-single':
					html_success( 'import_single', 'Import Single Leads' );
					break;

				case 'debug':
					html_success( 'lead_debug', 'Debug' );
					break;

				case 'import-row':
					if ( ! is_cli() ) {
						html_failure( null, 'Forbidden: CLI Access Only', array(
							sprintf( 'The requested page <code>%s</code> can only be accessed from the Command Line Interface', $path ),
						), 401 );
					}
					tc_import_from_row( $query );
					break;

				case 'create-import-rows':
					if ( ! is_cli() ) {
						html_failure( null, 'Forbidden: CLI Access Only', array(
							sprintf( 'The requested page <code>%s</code> can only be accessed from the Command Line Interface', $path ),
						), 401 );
					}
					$jobs = tc_create_import_rows_for_all_files();
					api_success( $jobs, 'Started to create Import Rows for all Files' );
					break;

				case 'create-import-rows-from-job':
					if ( ! is_cli() ) {
						html_failure( null, 'Forbidden: CLI Access Only', array(
							sprintf( 'The requested page <code>%s</code> can only be accessed from the Command Line Interface', $path ),
						), 401 );
					}
					tc_create_import_rows_for_job( get_array_key( 'item', $vars, 0 ) );
					break;

				case 'run-export-jobs':
					if ( ! is_cli() ) {
						html_failure( null, 'Forbidden: CLI Access Only', array(
							sprintf( 'The requested page <code>%s</code> can only be accessed from the Command Line Interface', $path ),
						), 401 );
					}
					tc_run_export_jobs();
					break;

				case 'run-export-job-by-id':
					if ( ! is_cli() ) {
						html_failure( null, 'Forbidden: CLI Access Only', array(
							sprintf( 'The requested page <code>%s</code> can only be accessed from the Command Line Interface', $path ),
						), 401 );
					}
					if ( is_empty( get_array_key( 'item', $vars ) ) ) {
						cli_failure( null, 'Missing Item' );
					}
					tc_run_export_job( get_array_key( 'item', $vars ) );
					break;

				case 'get-import-map-stats':
					if ( ! is_cli() ) {
						html_failure( null, 'Forbidden: CLI Access Only', array(
							sprintf( 'The requested page <code>%s</code> can only be accessed from the Command Line Interface', $path ),
						), 401 );
					}
					tc_get_import_map_stats();
					break;

				case 'saved-queries':
					if ( ! is_empty( get_array_key( 'item', $vars ) ) ) {
						html_success( 'saved_queries', 'Saved Query', null, 200, get_array_key( 'item', $vars ) );
					}
					else {
						html_success( 'saved_querielobby', 'Saved Queries' );
					}
					break;

				case 'view':
					if ( ! is_empty( get_array_key( 'item', $vars ) ) ) {
						html_success( 'lead_item', 'View Lead', null, 200, get_array_key( 'item', $vars ) );
					}
					else {
						html_success( 'lead_lobby', 'View Leads' );
					}
					break;

				default:
					if ( is_cli() ) {
						api_failure( null, 'Invalid Endpoint', array(
							sprintf( 'The requested path "%s" could not be found', $path ),
						) );
					}
					html_failure( null, 'Page not Found', array(
						sprintf( 'The requested page <code>%s</code> could not be found', $path ),
					), 404 );
					break;
			}
		}
	}

	function tc_validate_import_item( $template = '404', $success = false, $title = null, $errors = null, $status = 200, $more = null ) {
		$path = tc_get_path();
		$vars = return_path_vars( $path, '^/leads/([^/]*)/([^/]*)$', array( 'controller', 'item' ) );
		$item = get_array_key( 'item', $vars );
		if ( is_empty( $item ) ) {
			html_failure( null, 'Invalid Import File', array( sprintf( 'The name of the file to import is empty' ) ), null, 401 );
		}
		$file = sprintf( '%s%s', get_current_user_file_upload_path(), $item );
		if ( ! file_exists( $file ) ) {
			html_failure( null, 'Invalid Import File', array( sprintf( 'No file named <code>%s</code> exists.', $file ) ), null, 401 );
		}
		if ( lead_file_mapped( $item ) ) {
			html_failure( null, 'Invalid Import File', array( sprintf( 'The file <code>%s</code> is already mapped.', $item ) ), null, 401 );
		}
	}

	function tc_get_existing_columns( $forFilter = false ) {
		global $tc_fields;
		$return = array();
		if ( true == $forFilter ) {
			unset( $return['name'] );
			$return['id'] = 'Lead ID';
		}
		if ( can_loop( $tc_fields ) ) {
			foreach ( $tc_fields as $key => $data ) {
				if ( true == get_array_key( 'canset', $data, false ) ) {
					$return[ $key ] = get_array_key( 'description', $data );
				}
				if ( can_loop( get_array_key( 'splitsto', $data, array() ) ) ) {
					$return[ $key ] = get_array_key( 'description', $data );
				}
			}
		}
		if ( true == $forFilter ) {
			$return['meta'] = 'Lead Meta Data';
			$return['exportcount'] = 'Export Count';
		}
		else {
			unset( $return['tag'] );
			$pdmt = get_predefined_meta_tags();
			if ( can_loop( $pdmt ) ) {
				foreach ( $pdmt as $t ) {
					$return[ $t ] = $t;
				}
			}
		}
		ksort( $return );
		return $return;
	}

	function tc_load_csv_file( $file, $delimiter = ',', $enclosure = '"', $rowsToLoad = 'all', $rowOffset = 0 ) {
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

	function ajax_generate_file_preview( $data ) {
		$delimiter = get_array_key( 'delimiter', $data, ',' );
		if ( 'TAB' == strtoupper(( $delimiter ) ) ) {
			$delimiter = "\t";
		}
		if ( 'SPACE' == strtoupper(( $delimiter ) ) ) {
			$delimiter = " ";
		}
		$res = map_preview::get(
			$delimiter,
			get_array_key( 'encapsulation', $data, '"' ),
			get_array_key( 'headerrow', $data, 0 ),
			get_array_key( 'file', $data, null )
		);
		if ( ! is_object( $res ) ) {
			ajax_failure( 'Error' );
		}
		ajax_success( $res );
	}

	function ajax_get_preview_for_column( $data ) {
		$return = array();
		$fieldmap = ( 'new' == get_array_key( 'fieldmap', $data ) && ! is_empty( get_array_key( 'newkey', $data ) ) ) ? get_array_key( 'newkey', $data ) : get_array_key( 'fieldmap', $data );
		$preview = map_preview::get(
			get_array_key( 'delimiter', $data, null ),
			get_array_key( 'encapsulation', $data, null ),
			get_array_key( 'headerrow', $data, null ),
			get_array_key( 'file', $data, null )
		);
		if (
			! beginning_matches( 'tmp_', get_array_key( 'index', $data ) )
			&& can_loop( $preview->columns )
			&& count( $preview->columns ) >= absint( get_array_key( 'index', $data ) )
		) {
			$demo = get_array_key( 'preview', $preview->columns[ absint( get_array_key( 'index', $data ) ) ] );
			if ( can_loop( $demo ) ) {
				foreach ( $demo as $val ) {
					if ( is_empty( $val ) ) {
						$val = get_array_key( 'default', $data );
					}
					array_push( $return, tc_filter_field_value( $val, $fieldmap, get_array_key( 'default', $data, null ) ) );
				}
			}
		}
		if ( beginning_matches( 'tmp_', get_array_key( 'index', $data ) ) ) {
			$c = 0;
			while( $c < 10 ) {
				array_push( $return, tc_filter_field_value( get_array_key( 'default', $data ), $fieldmap ) );
				$c ++;
			}
		}
		ajax_success( $return );
	}

	function tc_filter_field_value( $value, $field, $default = null ) {
		global $tc_fields;
		$function = ( array_key_exists( $field, $tc_fields ) ) ? get_array_key( 'filterFunction', $tc_fields[ $field ] ) : 'tc_filter_text_field';
		if ( ! function_exists( $function ) ) {
			$function = 'tc_filter_text_field';
		}
		$ret = call_user_func( $function, $value );
		$def = call_user_func( $function, $default );
		return ( is_empty( $ret ) ) ? $def : $ret;
	}

	function ajax_save_file_map( $data ) {
		$listTags = get_array_key( 'listtags', $data, array() );
		unset( $data['listtags'] );
		if ( can_loop( $listTags ) ) {
			foreach ( $listTags as $tag ) {
				if ( ! is_array( get_array_key( 'column', $data ) ) ) {
					$data['column'] = array();
				}
				$tagHash = md5( microtime() * mt_rand() );
				$tagKey = sprintf( 'tag_%s', $tagHash );
				$data['column'][ $tagKey ] = array(
					'fieldmap' => 'tag',
					'default' => $tag,
				);
			}
		}
		else {
			ajax_failure( 'You must choose at least one tag for the list' );
		}
		$map = tc_get_filemap_for_file( get_array_key( 'file', $data ) );
		$map->approved = false;
		$map->delimiter = get_array_key( 'map_delimiter', $data );
		$map->encapsulation = get_array_key( 'map_encapsulation', $data );
		$map->headerRow = get_array_key( 'map_headerrow', $data );
		$cm = array();
		$of = array();
		if ( can_loop( get_array_key( 'column', $data ) ) ) {
			$fmc = 0;
			foreach ( $data['column'] as $index => $column ) {
				$numericIndex = preg_replace( '/[^0-9]/', '', $index );
				$fm = ( 'new' !== get_array_key( 'fieldmap', $column ) ) ? get_array_key( 'fieldmap', $column ) : get_array_key( 'newkey', $column );
				if ( $index == $numericIndex ) {
					$cm[ $index ] = $column;
				}
				else {
					$k = sprintf( '%s_%d', $fm, $fmc );
					$of[ $k ] = tc_filter_text_field( get_array_key( 'default', $column ) );
					$fmc ++;
				}
			}
		}
		$map->columnMap = serialize( $cm );
		$map->additional = serialize( $of );
		try {
			unset( $map->fileimportjobs );
			R::store( $map );
		}
		catch ( Exception $e ) {
			ajax_failure( $e->getMessage() );
		}
		ajax_success( 'Saved Field Map Successfully', sprintf( '/leads/import/%s', get_array_key( 'file', $data ) ) );
	}

	function ajax_other_file_actions( $data ) {
		$file = get_array_key( 'file', $data );
		switch ( get_array_key( 'action', $data ) ) {
			case 'nothing':
				ajax_failure( 'Nothing Happened' );
				break;

			case 'approve':
				$map = tc_get_filemap_for_file( $file );
				if ( is_a( $map, 'RedBeanPHP\OODBBean' ) ) {
					$map->approved = true;
					try {
						unset( $map->fileimportjobs );
						R::store( $map );
						//$sample = tc_make_sample_lead( $map, array_key_exists( 'debug', $data ) );
						//if ( array_key_exists( 'debug', $data ) ) {
						//	ajax_debug( array(
						//		'leadMeta' => $sample->sharedLeadmetaList,
						//		'phones' => $sample->sharedPhoneList,
						//		'emails' => $sample->sharedEmailList,
						//		'languages' => $sample->sharedSourceList,
						//		'ips' => $sample->sharedIpList,
						//		'tags' => $sample->sharedTagList,
						//		'lead' => $sample,
						//	) );
						//}
						ajax_success( 'Approved', '/leads/import/' );
					}
					catch ( Exception $e ) {
						if ( true == DEBUG ) {
							ajax_failure( $e->get_message() );
						}
						ajax_failure( 'Could not approve File Mapping');
					}
				}
				ajax_failure( 'Error: No such File Map' );
				break;

			case 'delete':
				if ( false == strpos( $file, get_current_user_file_upload_path() ) ) {
					$file = sprintf( '%s%s', get_current_user_file_upload_path(), $file );
				}
				$map = tc_get_filemap_for_file( $file );
				if ( is_a( $map, 'RedBeanPHP\OODBBean' ) ) {
					R::trash( $map );
				}
				$try = unlink( $file );
				if ( false == $try ) {
					ajax_failure( 'Failed to delete file. Please check file permissions.' );
				}
				ajax_success( 'Deleted', '/leads/import/' );
				break;

			case 'reset':
				$f = $file;
				if ( false == strpos( $file, get_current_user_file_upload_path() ) ) {
					$file = sprintf( '%s%s', get_current_user_file_upload_path(), $file );
				}
				$map = tc_get_filemap_for_file( $file );
				if ( is_a( $map, 'RedBeanPHP\OODBBean' ) ) {
					R::trash( $map );
				}
				ajax_success( 'Reset', sprintf( '/leads/import/%s', $f ) );
				break;

			default:
				ajax_failure( 'No such action' );
				break;
		}
		api_debug( $data );
	}

	function tc_run_lead_cli_function( $command, $item, $query = array() ) {
		if ( ! file_exists( FILE_LOG_PATH ) ) {
			mkdir( FILE_LOG_PATH, 0777, true );
		}
		$cmd = sprintf( 'php %s/index.php --path="/leads/%s/%s/" %s >> %s%s 2>&1 &', ABSPATH, $command, $item, ( can_loop( $query ) ? sprintf( '--query="%s"', http_build_query( $query ) ) : '' ) , FILE_LOG_PATH, sprintf( 'lead_%s_command.log', $command ) );
		$res = shell_exec( $cmd );
		return $cmd;
	}

	function ajax_get_existing_columns( $data ) {
		ajax_success( tc_get_existing_columns( true == get_array_key( 'forfilter', $data, false ) ) );
	}

	function ajax_import_single_lead( $data ) {
		$return = array();
		if ( can_loop( get_array_key( 'column', $data ) ) ) {
			foreach ( get_array_key( 'column', $data ) as $col ) {
				$key = ( 'new' == get_array_key( 'fieldmap', $col ) ) ? get_array_key( 'newname', $col ) : get_array_key( 'fieldmap', $col );
				if ( ! array_key_exists( $key, $return ) ) {
					$return[ $key ] = get_array_key( 'value', $col );
				}
				if ( ! is_array( $return[ $key ] ) && ! is_empty( $return[ $key ] ) ) {
					$ov = $return[ $key ];
					$return[ $key ] = array( $ov );
				}
				if ( is_array( $return[ $key ] ) ) {
					if ( ! in_array( get_array_key( 'value', $col ), $return[ $key ] ) ) {
						array_push( $return[ $key ], get_array_key( 'value', $col ) );
					}
				}
			}
		}
		$return = tc_filter_fields_deep( $return );
		$fia = $return;
		$return = tc_make_lead_from_row( $return, false );
		$rebound = false;
		try {
			R::store( $return );
		}
		catch ( Exception $e ) {
			$rebound = true;
		}
		if ( isset( $rebound ) && true == $rebound ) {
			try {
				$lead = tc_make_lead_from_row( $fia, true, true );
			}
			catch ( Exception $e ) {
				ajax_failure( sprintf( 'Failed to Rebound Lead: %s', $e->getMessage() ) );
			}
		}
		else {
			$lead = $return;
		}
		if ( ! is_a( $lead, 'RedBeanPHP\OODBBean' ) ) {
			ajax_failure( 'Failed to create merged lead from rebound' );
		}
		else if ( true == get_array_key( 'debug', $data, false ) ) {
			if ( isset( $rebound ) && true == $rebound ) {
				$lead->rebounded = true;
			}
			else {
				$lead->rebounded = false;
			}
			ajax_success( print_r( $lead, true )  );
		}
		ajax_success( 'Please wait while you are redirected.', sprintf( '/leads/view/%d', $lead->id ) );
	}

	function tc_make_sample_lead( RedBeanPHP\OODBBean $map, $debug = false ) {
		global $tc_fields;
		$columns = array();
		$cm = @unserialize( $map->column_map );
		$am = @unserialize( $map->additional );
		if ( can_loop( $cm ) ) {
			foreach ( $cm as $col ) {
				$push = array(
					'fieldmap' => get_array_key( 'fieldmap', $col ),
					'newname' => get_array_key( 'newkey', $col, '' ),
					'value' => tc_get_sample_field_value( get_array_key( 'fieldmap', $col ) ),
				);
				array_push( $columns, $push );
			}
		}
		if ( can_loop( $am ) ) {
			foreach ( $am as $fieldmap => $default ) {
				$fm = tc_fix_fieldname( $fieldmap );
				$push = array(
					'fieldmap' => ( array_key_exists( $fm, $tc_fields ) ) ? $fm : 'new',
					'newname' => ( array_key_exists( $fm, $tc_fields ) ) ? '': $fm,
					'value' => $default,
				);
				array_push( $columns, $push );
			}
		}
		$return = array();
		if ( can_loop( $columns ) ) {
			foreach ( $columns as $col ) {
				$key = ( 'new' == get_array_key( 'fieldmap', $col ) ) ? get_array_key( 'newname', $col ) : get_array_key( 'fieldmap', $col );
				if ( ! array_key_exists( $key, $return ) ) {
					$return[ $key ] = get_array_key( 'value', $col );
				}
				if ( ! is_array( $return[ $key ] ) && ! is_empty( $return[ $key ] ) ) {
					$ov = $return[ $key ];
					$return[ $key ] = array( $ov );
				}
				if ( is_array( $return[ $key ] ) ) {
					if ( ! in_array( get_array_key( 'value', $col ), $return[ $key ] ) ) {
						array_push( $return[ $key ], get_array_key( 'value', $col ) );
					}
				}
			}
		}
		$return = tc_filter_fields_deep( $return );
		$return = tc_make_lead_from_row( $return, false );
		//if ( ! is_cli() ) {
		//	trigger_error( str_replace( "\n", "\r\n", print_r( $return, true ) ) );
		//}
		//ajax_debug( $return );
		try {
			$cid = R::store( $return );
		}
		catch ( Exception $e ) {
			ajax_failure( sprintf( 'Database Error: %s', $e->getMessage() ) );
		}
		if ( false == $debug ) {
			try {
				R::trash( $return );
			}
			catch ( Exception $e ) {
				ajax_failure( sprintf( 'Database Error: %s', $e->getMessage() ) );
			}
		}
		return $return;
	}

	function tc_get_sample_field_value( $key ) {
		global $tc_sample_lead_row, $_tc_sample_field_count;
		if ( ! can_loop( $_tc_sample_field_count ) ) {
			$_tc_sample_field_count = array();
		}
		if ( ! array_key_exists( $key, $_tc_sample_field_count ) ) {
			$_tc_sample_field_count = 0;
		}
		$return = 'Sample';
		if ( can_loop( $tc_sample_lead_row ) ) {
			$rc = array();
			foreach ( $tc_sample_lead_row as $row ) {
				$k = get_array_key( 'fieldmap', $row );
				if ( $k == $key ) {
					$return = tc_get_sample_data_for_fieldmap( $k );
					break;
				}
			}
		}
		$_tc_sample_field_count ++;
		return $return;
	}

	function tc_key_can_array( $key = null ) {
		global $tc_fields;
		$f = get_array_key( $key, $tc_fields, array() );
		$canset = get_array_key( 'canset', $f, false );
		$model = sprintf( 'Model_%s', $key );
		return ( false === $canset || true === class_exists( $model ) );
	}

	function tc_verify_whatsapp_number( $username = "" ) {
	    $username = "447740258369";
	    $r = new Registration( $username, true );
	    try {
	        $r->checkCredentials();
        }
        catch ( Exception $e )
        {
            return $e->getMessage();
        }
        return "registered";
    }

	function tc_verify_numbers() {
	    $return = array();
	    $leads = array();
	    $leads = R::getAll( "select lead.id, lead.fname, lead.lname, phone.number from lead left join phone on lead.phone_id = phone.id;" );
	    foreach ( $leads as $lead ) {
	        $user_name = $lead->number;
	        $debug = true;
	        $item = array(
	            'lead_id' => $lead->id,
                'lead_name' => $lead->name,
                'lead_is_whatsapp' => 1
            );
            $r = new Registration( $user_name, $debug );
            try {
                $r->checkCredentials();
            }
            catch ( Exception $e ) {
                $item = array(
                    'lead_id' => $lead->id,
                    'lead_name' => $lead->name,
                    'lead_is_whatsapp' => 0
                );
            }


            array_push( $return, $item );
        }
        return $return;
    }
