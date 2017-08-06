<?php
	defined( 'ABSPATH' ) || die( 'Sorry, but you cannot access this page directly.' );

	/**
	 * Formats data which can then be parsed similar to ajax_filter_lead_list
	 * @param  [type] $data [description]
	 * @return [type]       [description]
	 */
	function ajax_get_lead_search_results( $data ) {
		global $_tc_countries;
		$default = array(
			'orderby' => 'id',
			'order' => 'desc',
			'searchterm' => '',
			'basicsearchobjects' => array(
				'name',
				'email',
				'phone',
				'address',
				'source',
				'ip',
				'tag',
				'metavalue',
			),
			'savedqueryid' => 0,
			'filtergrouping' => '',
		);
		if ( ! can_loop( $data ) ) {
			$data = $default;
		}
		$perpage = 15;
		$page = absint( get_array_key( 'page', $data, 0 ) );
		// Step 1 - get the query info
		if ( 0 !== absint( get_array_key( 'savedqueryid', $data ) ) ) {
			try {
				$q = R::load( 'savedfilterqueries', absint( get_array_key( 'savedqueryid', $data ) ) );
			}
			catch ( Exception $e ) {
				ajax_failure( sprintf( 'Could not load saved query: %s', $e->getMessage() ) );
			}
		}
		else {
			try {
				$q = R::dispense( 'savedfilterqueries' );
				$q->conditions = '';
				$q->grouping = '';
			}
			catch ( Exception $e ) {
				ajax_failure( sprintf( 'Could not load fresh query: %s', $e->getMessage() ) );
			}
			// this is where we have to figure out if we're a basic query or an advanced query
			if ( ! is_empty( get_array_key( 'searchterm', $data, '' ) ) ) {
				if ( ! can_loop( get_array_key( 'basicsearchobjects', $data, array() ) ) ) {
					ajax_failure( 'You must choose at least one module to search by.' );
				}
				$conditions = array();
				$filtergrouping = array();
				$searchable = get_array_key( 'basicsearchobjects', $data, array() );
				if ( in_array( 'name', $searchable ) ) {
					$conditions['1'] = array(
						'field' => 'fname',
						'attribute' => 'value',
						'condition' => '%_%',
						'filter' => get_array_key( 'searchterm', $data, '' ),
					);
					$conditions['2'] = array(
						'field' => 'mname',
						'attribute' => 'value',
						'condition' => '%_%',
						'filter' => get_array_key( 'searchterm', $data, '' ),
					);
					$conditions['3'] = array(
						'field' => 'lname',
						'attribute' => 'value',
						'condition' => '%_%',
						'filter' => get_array_key( 'searchterm', $data, '' ),
					);
					array_push( $filtergrouping, '1' );
					array_push( $filtergrouping, '2' );
					array_push( $filtergrouping, '3' );
				}
				if ( in_array( 'email', $searchable ) ) {
					$conditions['4'] = array(
						'field' => 'email',
						'attribute' => 'email',
						'condition' => '%_%',
						'filter' => get_array_key( 'searchterm', $data, '' ),
					);
					array_push( $filtergrouping, '4' );
				}
				if ( in_array( 'phone', $searchable ) ) {
					$conditions['5'] = array(
						'field' => 'phone',
						'attribute' => 'number_numbers_only',
						'condition' => '%_%',
						'filter' => get_array_key( 'searchterm', $data, '' ),
					);
					array_push( $filtergrouping, '5' );
				}
				if ( in_array( 'source', $searchable ) ) {
					$conditions['6'] = array(
						'field' => 'source',
						'attribute' => 'source',
						'condition' => '%_%',
						'filter' => get_array_key( 'searchterm', $data, '' ),
					);
					array_push( $filtergrouping, '6' );
				}
				if ( in_array( 'ip', $searchable ) ) {
					$conditions['7'] = array(
						'field' => 'ip',
						'attribute' => 'ip',
						'condition' => '%_%',
						'filter' => get_array_key( 'searchterm', $data, '' ),
					);
					array_push( $filtergrouping, '7' );
				}
				if ( in_array( 'tag', $searchable ) ) {
					$conditions['8'] = array(
						'field' => 'tag',
						'attribute' => 'tag',
						'condition' => '%_%',
						'filter' => get_array_key( 'searchterm', $data, '' ),
					);
					array_push( $filtergrouping, '8' );
				}
				if ( in_array( 'metavalue', $searchable ) ) {
					$conditions['9'] = array(
						'field' => 'meta',
						'attribute' => 'key',
						'condition' => '%_%',
						'filter' => get_array_key( 'searchterm', $data, '' ),
					);
					$conditions['10'] = array(
						'field' => 'meta',
						'attribute' => 'value',
						'condition' => '%_%',
						'filter' => get_array_key( 'searchterm', $data, '' ),
					);
					array_push( $filtergrouping, '9' );
					array_push( $filtergrouping, '10' );
				}
				if ( in_array( 'address', $searchable ) ) {
					$conditions['11'] = array(
						'field' => 'street1',
						'attribute' => 'value',
						'condition' => '%_%',
						'filter' => get_array_key( 'searchterm', $data, '' ),
					);
					$conditions['12'] = array(
						'field' => 'street2',
						'attribute' => 'value',
						'condition' => '%_%',
						'filter' => get_array_key( 'searchterm', $data, '' ),
					);
					$conditions['13'] = array(
						'field' => 'city',
						'attribute' => 'value',
						'condition' => '%_%',
						'filter' => get_array_key( 'searchterm', $data, '' ),
					);
					$conditions['14'] = array(
						'field' => 'region',
						'attribute' => 'value',
						'condition' => '%_%',
						'filter' => get_array_key( 'searchterm', $data, '' ),
					);
					$conditions['15'] = array(
						'field' => 'postalcode',
						'attribute' => 'value',
						'condition' => '%_%',
						'filter' => get_array_key( 'searchterm', $data, '' ),
					);
					$conditions['16'] = array(
						'field' => 'country',
						'attribute' => 'value',
						'condition' => '%_%',
						'filter' => get_array_key( 'searchterm', $data, '' ),
					);
					array_push( $filtergrouping, '11' );
					array_push( $filtergrouping, '12' );
					array_push( $filtergrouping, '13' );
					array_push( $filtergrouping, '14' );
					array_push( $filtergrouping, '15' );
					array_push( $filtergrouping, '16' );
				}
				$filtergrouping = implode( ' OR ', $filtergrouping );
				$q->conditions = serialize( $conditions );
				$q->grouping = $filtergrouping;
			}
			else {
				$q->conditions = serialize( get_array_key( 'conditions', $data, array(
					'1' => array(
						'field' => 'id',
						'attribute' => 'value',
						'condition' => '>=',
						'filter' => '0',
					),
				) ) );
				$q->grouping = get_array_key( 'filtergrouping', $data, '1' );
				if ( is_empty( $q->grouping ) ) {
					$q->grouping = '1';
				}
			}
		}
		// Step 2 - parse query info
		$conditions = @unserialize( $q->conditions );
		$filtergrouping = $q->grouping;
		$countquery = tc_generate_export_query( true, $conditions, $filtergrouping );
		$leadquery = tc_generate_export_query( false, $conditions, $filtergrouping );
		$orderByQuery = sprintf(
			'ORDER BY %s %s',
			get_array_key( 'orderby', $data, 'id' ),
			strtoupper( get_array_key( 'order', $data, 'desc' ) )
		);
		$leadquery['query'] = str_replace( 'ORDER BY exportcount ASC', $orderByQuery, $leadquery['query'] );
		try {
			$count = absint( R::getCell( get_array_key( 'query', $countquery ), get_array_key( 'vars', $countquery, array() ) ) );;
		}
		catch ( Exception $e ) {
			ajax_failure( sprintf( 'Error getting count: %s', $e->getMessage() ) );
			$count = 0;
		}
		if ( $page >= ceil( $count / $perpage ) ) {
			$page = ceil( $count / $perpage );
		}
		if ( $page <= 0 ) {
			$page = 0;
		}
		$limitString = sprintf( ' LIMIT %d OFFSET %d', $perpage, ( $page * $perpage ) );
		$leadquery['query'] .= $limitString;
		try {
			$leadIds = R::getCol( get_array_key( 'query', $leadquery ), get_array_key( 'vars', $leadquery, array() ) );
		}
		catch( Exception $e ) {
			ajax_failure( $e->getMessage() );
			$leadIds = array();
		}
		$leads = array();
		if ( can_loop( $leadIds ) ) {
			foreach ( $leadIds as $beanId ) {
				try {
					$l = R::load( 'lead', absint( $beanId ) );
					$hash = ( is_object( $lead->email ) ) ? md5( $lead->email->email ) : md5( 'none' );
					$push = array(
						'id' => $l->id,
						'profile' => ( ! is_empty( $l->profile_picture ) ) ? $l->profile_picture : sprintf( '//s.gravatar.com/avatar/%s?s=30&d=mm&r=x', $hash ),
						'name' => implode( ' ', array( $l->fname, $l->mname, $l->lname ) ),
						'email' => null,
						'phone' => null,
						'country' => get_array_key( 'name', get_array_key( $l->country, $_tc_countries ) ),
						'source' => null,
						'importtime' => $l->createtimestamp,
					);
					$emails = $l->sharedEmailList;
					if ( can_loop( $emails ) ) {
						$keys = array_keys( $emails );
						$key = array_shift( $keys );
						$push['email'] = $emails[ $key ]->email;
					}
					$phones = $l->sharedPhoneList;
					if ( can_loop( $phones ) ) {
						$keys = array_keys( $phones );
						$key = array_shift( $keys );
						$push['phone'] = $phones[ $key ]->international_format;
					}
					$sources = $l->sharedSourceList;
					if ( can_loop( $sources ) ) {
						$keys = array_keys( $sources );
						$key = array_shift( $keys );
						$push['source'] = $sources[ $key ]->source;
					}
					array_push( $leads, $push );
				}
				catch ( Exception $e ) {}
			}
		}
		$return = array(
			'page' => $page,
			'totalpages' => ceil( $count / $perpage ),
			'total' => $count,
			'leads' => $leads,
		);
		ajax_success( $return );
	}

	function tc_get_phone_line_type_description( $type ) {
		$type = str_replace( '_', ' ', $type );
		$type = ucwords( strtolower( $type ) );
		$type = str_replace( 'Or', 'or', $type );
		$type = str_replace( 'And', 'and', $type );
		return $type;
	}

	function tc_make_google_url_from_ip( RedBeanPHP\OODBBean $ip, $size = 37 ) {
		$size = absint( $size );
		if ( $size < 37 ) {
			$size = 37;
		}
		$base = '//maps.googleapis.com/maps/api/staticmap';
		switch ( true ) {
			case ( ! is_empty( get_bean_property( 'latitude', $ip ) ) && ! is_empty( get_bean_property( 'longitude', $ip ) ) ):
				$center = implode( ',', array(
					get_bean_property( 'latitude', $ip ),
					get_bean_property( 'longitude', $ip ),
				) );
				break;

			case ( ! is_empty( get_bean_property( 'city', $ip ) ) ):
				$center = get_bean_property( 'city', $ip );
				break;

			case ( ! is_empty( get_bean_property( 'region', $ip ) ) ):
				$center = get_bean_property( 'region', $ip );
				break;

			case ( ! is_empty( get_bean_property( 'postal', $ip ) ) && ! is_empty( get_bean_property( 'country', $ip ) ) ):
				$center = implode( ' ', array(
					get_bean_property( 'postal', $ip ),
					get_bean_property( 'country', $ip ),
				) );
				break;

			case ( ! is_empty( get_bean_property( 'country', $ip ) ) ):
				$center = get_bean_property( 'country', $ip );
				break;

			default:
				$center = get_bean_property( 'continent', $ip );
				break;
		}
		$url = sprintf( '%s?%s',$base, implode( '&', array(
			sprintf( 'center=%s', urlencode( $center ) ),
			sprintf( 'size=%dx%d', $size, $size + 20 ),
			'style=element:labels|visibility:off',
			sprintf( 'key=%s', GOOGLE_MAPS_API_KEY ),
			'zoom=0',
			'format=png32',
			'type=hybrid',
			sprintf( 'markers=size:tiny|color:black|%s', $center )
		) ) );
		return $url;
	}

	function ajax_get_all_files_aync( $data ) {
		$mf = tc_get_unmapped_files();
		$umf = tc_get_pending_files();
		$rmf = tc_get_in_progress_files();
		ajax_success( array(
			'mf' => $mf,
			'umf' => $umf,
			'rmf' => $rmf,
		) );
	}

	function ajax_tc_multiple_file_delete_from_import_lobby( $data ) {
		$return = array();
		if ( can_loop( get_array_key( 'files', $data ) ) ) {
			foreach ( get_array_key( 'files', $data, array() ) as $file ) {
				$f = $file;
				if ( false == strpos( $file, get_current_user_file_upload_path() ) ) {
					$file = sprintf( '%s%s', get_current_user_file_upload_path(), $file );
				}
				$map = tc_get_filemap_for_file( $file );
				if ( is_a( $map, 'RedBeanPHP\OODBBean' ) ) {
					$return[ $f ] = R::trash( $map );
				}
				$try = unlink( $file );
				if ( false === $try ) {
					sprintf( sprintf( 'Could not delete file "%s" from disk. Please check permissions.', $f ) );
				}
			}
		}
		ajax_success( $return );
	}

	function ajax_tc_copy_file_mapping( $data ) {
		$newfile = sprintf( '%s%s', get_current_user_file_upload_path(), get_array_key( 'new', $data ) );
		$nfm = tc_get_filemap_for_file( $newfile, true );
		$oldfile = sprintf( '%s%s', get_current_user_file_upload_path(), get_array_key( 'old', $data ) );
		$ofm = tc_get_filemap_for_file( $oldfile, true );
		if ( ! is_a( $nfm, 'RedBeanPHP\OODBBean' ) ) {
			ajax_failure( sprintf( 'Could not find file map for file %s', get_array_key( 'new', $data ) ) );
		}
		if ( ! is_a( $ofm, 'RedBeanPHP\OODBBean' ) ) {
			ajax_failure( sprintf( 'Could not find file map for file %s', get_array_key( 'old', $data ) ) );
		}
		if ( ! is_empty( $ofm->column_map ) ) {
			ajax_failure( 'Cannot over-write an existing map.<br />Please reset file map to continue' );
		}
		$ofm->column_map = $nfm->column_map;
		$ofm->additional = $nfm->additional;
		try {
			R::store( $ofm );
		}
		catch ( Exception $e ) {
			ajax_failure( sprintf( 'Failed to copy changes due to database error: %s', $e->getMessage() ) );
		}
		ajax_success( sprintf( 'File Mapping was successfully copied from %s to %s', get_array_key( 'new', $data ), get_array_key( 'old', $data ) ), sprintf( '/leads/import/%s', get_array_key( 'old', $data ) ) );
	}