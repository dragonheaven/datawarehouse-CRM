<?php
	defined( 'ABSPATH' ) || die( 'Sorry, but you cannot access this page directly.' );

	function tc_websocket_push_lead_stats() {
		global $_tc_countries;
		$send = array();
		try {
			$send['allLeadCount'] = R::count( 'lead' );
			$lbc = R::getAll( 'SELECT COUNT(id) as count, country FROM lead WHERE country IS NOT NULL GROUP BY country ORDER BY count DESC;' );
			$send['leadsByCountry'] = array();
			if ( can_loop( $lbc ) ) {
				foreach ( $lbc as $row ) {
					$cname = get_array_key( 'name', get_array_key( get_array_key( 'country', $row, 'XX' ), $_tc_countries, array( 'name' => 'Unknown' ) ), 'Unknown' );
					array_push( $send['leadsByCountry'], array(
						'y' => absint( get_array_key( 'count', $row, 0 ) ),
						'name' => $cname,
					) );
				}
			}
			$tts = R::getAll( 'SELECT COUNT( lead_source.id ) as count, source.source FROM lead_source LEFT JOIN source ON lead_source.source_id = source.id GROUP BY source.source ORDER BY count DESC LIMIT 20;' );
			$send['leadsBySources'] = array();
			if ( can_loop( $tts ) ) {
				foreach ( $tts as $row ) {
					array_push( $send['leadsBySources'], array(
						'y' => absint( get_array_key( 'count', $row, 0 ) ),
						'name' => get_array_key( 'source', $row, 'Unknown' ),
					) );
				}
			}
			$pls = R::getAll( 'SELECT COUNT( id ) as count, phone.line_type FROM phone WHERE phone.valid = 1 AND phone.line_type IS NOT NULL GROUP BY phone.line_type ORDER BY count DESC;' );
			$send['phonesByType'] = array();
			if ( can_loop( $pls ) ) {
				foreach ( $pls as $row ) {
					$lt = get_array_key( 'line_type', $row, 'unknown' );
					$lt = str_replace( '_', ' ', $lt );
					$lt = ucwords( $lt );
					$lt = str_replace( 'Or', 'or', $lt );
					$lt = str_replace( 'And', 'and', $lt );
					array_push( $send['phonesByType'], array(
						'y' => absint( get_array_key( 'count', $row, 0 ) ),
						'name' => $lt,
					) );
				}
			}
			$els = R::getAll( 'SELECT COUNT( id ) as count, email.valid_domain FROM email WHERE valid_domain IS NOT NULL GROUP BY email.valid_domain ORDER BY count DESC;' );
			$send['emailByValidity'] = array();
			if ( can_loop( $els ) ) {
				foreach ( $els as $row ) {
					array_push( $send['emailByValidity'], array(
						'y' => absint( get_array_key( 'count', $row, 0 ) ),
						'name' => ( true == get_array_key( 'valid_domain', $row, false ) ) ? 'Valid' : 'Invalid',
					) );
				}
			}
		}
		catch( Exception $e ) {
			if ( true == DEBUG ) {
				cli_echo( $e->getMessage() );
			}
		}
		streamer_emit( 'lead-stats', $send );
		cli_echo( $send );
	}