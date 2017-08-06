<?php
	defined( 'ABSPATH' ) || die( 'Sorry, but you cannot access this page directly.' );

	function ajax_filter_lead_list( $data ) {
		global $_tc_countries;
		$page = absint( get_array_key( 'page', $data, 0 ) );
		$perpage = absint( get_array_key( 'perpage', $data, 10 ) );
		$conditions = get_array_key( 'conditions', $data, array() );
		$filtergrouping = get_array_key( 'filtergrouping', $data, '' );
		$countquery = tc_generate_export_query( true, $conditions, $filtergrouping );
		$leadquery = tc_generate_export_query( false, $conditions, $filtergrouping );
		$leadquery['query'] = str_replace( 'ORDER BY exportcount ASC', 'ORDER BY id DESC', $leadquery['query'] );
		$leadquery['query'] = str_replace( ', ( SELECT COUNT(id) FROM exportjobs_lead WHERE exportjobs_lead.lead_id = lead.id ) as exportcount', '', $leadquery['query'] );
		$leadquery['query'] = str_replace( ', 0 as exportcount', '', $leadquery['query'] );
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
					$push = array(
						'id' => $l->id,
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

	function make_lead_full_name( RedBeanPHP\OODBBean $lead ) {
		$return = '';
		if ( ! is_empty( get_bean_property( 'salutation', $lead ) ) ) {
			$return .= sprintf( '%s. ', get_bean_property( 'salutation', $lead ) );
		}
		if ( ! is_empty( get_bean_property( 'fname', $lead ) ) ) {
			$return .= sprintf( '%s ', get_bean_property( 'fname', $lead ) );
		}
		if ( ! is_empty( get_bean_property( 'mname', $lead ) ) ) {
			$return .= sprintf( '%s ', get_bean_property( 'mname', $lead ) );
		}
		if ( ! is_empty( get_bean_property( 'lname', $lead ) ) ) {
			$return .= sprintf( '%s', get_bean_property( 'lname', $lead ) );
		}
		return $return;
	}

	function make_lead_address( RedBeanPHP\OODBBean $lead ) {
		global $_tc_countries;
		$return = '';
		if ( ! is_empty( get_bean_property( 'street1', $lead ) ) ) {
			$return .= sprintf( '%s' . "\r\n", get_bean_property( 'street1', $lead ) );
		}
		if ( ! is_empty( get_bean_property( 'street2', $lead ) ) ) {
			$return .= sprintf( '%s' . "\r\n", get_bean_property( 'street2', $lead ) );
		}
		if ( ! is_empty( get_bean_property( 'city', $lead ) ) ) {
			$return .= sprintf( '%s', get_bean_property( 'city', $lead ) );
			$hasCity = true;
		}
		if ( ! is_empty( get_bean_property( 'region', $lead ) ) ) {
			$return .= sprintf( '%s%s', ( isset( $hasCity ) && true == $hasCity ? ', ' : '' ), get_bean_property( 'region', $lead ) );
			$hasState = true;
		}
		if ( ! is_empty( get_bean_property( 'postalcode', $lead ) ) ) {
			$return .= sprintf( '%s%s', ( ( isset( $hasCity ) && true == $hasCity ) || ( isset( $hasState ) && true == $hasState ) ? ', ' : '' ), get_bean_property( 'postalcode', $lead ) );
			$hasCountry = true;
		}
		if ( ! is_empty( get_bean_property( 'country', $lead ) ) ) {
			$return .= sprintf( '%s%s', ( ( isset( $hasCity ) || isset( $hasState ) || isset( $hasCountry ) ) ? "\r\n" : '' ), get_array_key( 'name', get_array_key( get_bean_property( 'country', $lead ), $_tc_countries ) ) );
		}
		return $return;
	}