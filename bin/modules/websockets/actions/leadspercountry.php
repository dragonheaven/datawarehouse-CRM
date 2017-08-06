<?php
	defined( 'ABSPATH' ) || die( 'Sorry, but you cannot access this page directly.' );

	function tc_websocket_push_leads_per_country() {
		$return = array();
		$query = "SELECT COUNT(lead.id) as value, lead.country as code FROM lead WHERE lead.country IS NOT NULL AND lead.country NOT LIKE 'XX' AND lead.country NOT LIKE ''  GROUP BY lead.country ORDER BY COUNT(lead.id) DESC;";
		$recs = array();
		try {
			$recs = R::getAll( $query );
		}
		catch( Exception $e ) {
			cli_echo( sprintf( 'Error Getting Date from Database: %s', $e->getMessage() ) );
		}
		if ( can_loop( $recs ) ) {
			foreach ( $recs as $r ) {
				$rec = array(
					'code' => strtoupper( get_array_key( 'code', $r, 'XX' ) ),
					'value' => absint( get_array_key( 'value', $r, 0 ) ),
				);
				array_push( $return, $rec );
			}
		}
		streamer_emit( 'leads-per-country', $return );
		cli_success( $return, 'Sent Data' );
	}