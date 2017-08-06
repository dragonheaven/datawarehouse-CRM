<?php
	defined( 'ABSPATH' ) || die( 'Sorry, but you cannot access this page directly.' );

	function dash_save_list_leads_by_country( $data = array() ) {
		if ( true == FREEZE_DB ) {
			R::freeze( TRUE );
		}
		global $_tc_countries;
		$query = "SELECT COUNT(lead.id) as value, lead.country as code FROM lead WHERE lead.country IS NOT NULL AND lead.country NOT LIKE 'XX' AND lead.country NOT LIKE ''  GROUP BY lead.country ORDER BY COUNT(lead.id) DESC;";
		$recs = array();
		try {
			$cs = R::getAll( $query );
			if ( can_loop( $cs ) ) {
				foreach ( $cs as $c ) {
					$c['name'] = get_array_key( 'name', get_array_key( get_array_key( 'code', $c ), $_tc_countries ) );
					$c['smalliso'] = strtolower( get_array_key( 'code', $c ) );
					array_push( $recs, $c );
				}
			}
		}
		catch( Exception $e ) {
		}
		update_option( 'leads_by_country', $recs );
		return $recs;
	}

	function dash_save_list_leads_by_language( $data = array() ) {
		if ( true == FREEZE_DB ) {
			R::freeze( TRUE );
		}
		$query = "SELECT count(language_lead.id) as value, language.lang as name FROM language_lead LEFT JOIN language on language_lead.language_id = language.id GROUP BY language.lang ORDER BY count(language_lead.id) DESC;";
		$recs = array();
		try {
			$recs = R::getAll( $query );
		}
		catch( Exception $e ) {
		}
		update_option( 'leads_by_language', $recs );
		return $recs;
	}

	function dash_save_list_leads_by_source( $data = array() ) {
		if ( true == FREEZE_DB ) {
			R::freeze( TRUE );
		}
		$query = "SELECT count(lead_source.id) as value, source.source as name FROM lead_source LEFT JOIN source ON lead_source.source_id = source.id GROUP BY source.source ORDER BY count(lead_source.id) DESC;";
		$recs = array();
		try {
			$recs = R::getAll( $query );
		}
		catch( Exception $e ) {
		}
		update_option( 'leads_by_source', $recs );
		return $recs;
	}

	function dash_save_list_leads_by_meta( $data = array() ) {
		if ( true == FREEZE_DB ) {
			R::freeze( TRUE );
		}
		$query = "SELECT count(lead_leadmeta.id) as value, leadmeta.key as name FROM lead_leadmeta LEFT JOIN leadmeta ON lead_leadmeta.leadmeta_id = leadmeta.id GROUP BY leadmeta.key ORDER BY count(lead_leadmeta.id) DESC;";
		$recs = array();
		try {
			$recs = R::getAll( $query );
		}
		catch( Exception $e ) {
		}
		update_option( 'leads_by_meta', $recs );
		return $recs;
	}

	function dash_save_list_leads_by_tag( $data = array() ) {
		if ( true == FREEZE_DB ) {
			R::freeze( TRUE );
		}
		$query = "SELECT count(lead_tag.id) as value, tag.tag as name FROM lead_tag LEFT JOIN tag ON lead_tag.tag_id = tag.id GROUP BY tag.tag ORDER BY count(lead_tag.id) DESC;";
		$recs = array();
		try {
			$recs = R::getAll( $query );
		}
		catch( Exception $e ) {
		}
		update_option( 'leads_by_tag', $recs );
		return $recs;
	}