<?php
	defined( 'ABSPATH' ) || die( 'Sorry, but you cannot access this page directly.' );

	function tc_get_exportable_lead_columns() {
		global $tc_fields;
		$return = array();
		if ( can_loop( $tc_fields ) ) {
			foreach ( $tc_fields as $field => $data ) {
				if (
					'meta' !== $field
					&& 'name' !== $field
					&& 's' !== substr( $field, -1 )
					&& false === strpos( $field, 'latin' )
				) {
					$fieldModel = sprintf( 'Model_%s', $field );
					if ( class_exists( $fieldModel, false ) ) {
						$atts = $fieldModel::getFilterableAttributes();
					}
					else {
						$atts = array(
							'value' => array(
								'description' => 'Value',
								'filtertype' => get_array_key( 'filtertype', get_array_key( $field, $tc_fields, array() ), array( '=' => 'Is' ) ),
								'filteroptions' => get_array_key( 'filteroptions', get_array_key( $field, $tc_fields, array() ), array( '=' => 'Is' ) ),
								'filterconditions' => get_array_key( 'filterconditions', get_array_key( $field, $tc_fields, array() ), array( '=' => 'Is' ) ),
							),
						);
					}
					if ( can_loop( $atts ) ) {
						foreach ( $atts as $attr => $info ) {
							$colkey = sprintf( '%s.%s', $field, $attr );
							$colname = sprintf(
								'%s - %s',
								get_array_key( 'description', get_array_key( $field, $tc_fields, array() ), 'Unkown' ),
								get_array_key( 'description', $info, 'Unknown' )
							);
							$return[ $colkey ] = $colname;
						}
					}
				}
			}
		}
		return $return;
	}

	function ajax_preview_export_job( $data ) {
		if ( is_empty( get_array_key( 'filtergrouping', $data ) ) ) {
			ajax_failure( 'You must set Condition Grouping' );
		}
		if ( ! can_loop( get_array_key( 'exportfields', $data ) ) ) {
			ajax_failure( 'You must choose at least 1 field to export.' );
		}
		$query = tc_generate_export_query( true, get_array_key( 'conditions', $data ), get_array_key( 'filtergrouping', $data), get_array_key( 'exportfields', $data ), get_array_key( 'maxleads', $data ) );
		//ajax_debug( $query );
		try {
			$res = R::getCell( get_array_key( 'query', $query, 'SELECT 0' ), get_array_key( 'vars', $query, array() ) );
		}
		catch ( Exception $e ) {
			ajax_failure( $e->getMessage() );
		}
		ajax_success( absint( $res ) );
	}

	function ajax_create_export_job( $data ) {
		if ( is_empty( get_array_key( 'filtergrouping', $data ) ) ) {
			ajax_failure( 'You must set Condition Grouping' );
		}
		if ( ! can_loop( get_array_key( 'exportfields', $data ) ) ) {
			ajax_failure( 'You must choose at least 1 field to export.' );
		}
		if ( 'all' == get_array_key( 'maxleads', $data, 'all' ) ) {
			$leadsToExport = null;
		}
		else {
			$leadsToExport = absint( get_array_key( 'maxleads', $data, 10 ) );
		}
		$query = tc_generate_export_query( false, get_array_key( 'conditions', $data ), get_array_key( 'filtergrouping', $data), get_array_key( 'exportfields', $data ), $leadsToExport );
		try {
			$res = R::getCol( get_array_key( 'query', $query, 'SELECT 0' ), get_array_key( 'vars', $query, array() ) );
		}
		catch ( Exception $e ) {
			if ( true == DEBUG ) {
				ajax_failure( $e->getMessage() );
			}
			ajax_failure( 'Invalid Filter or Condition Grouping' );
		}
		try {
			$ejob = R::dispense( 'exportjobs' );
			$ejob->status = 'new';
			$ejob->leadIds = serialize( $res );
			$ejob->fetchQuery = get_array_key( 'query', $query );
			$ejob->fetchVars = serialize( get_array_key( 'vars', $query, array() ) );
			$ejob->fields = serialize( get_array_key( 'exportfields', $data ) );
			$ejob->conditions = serialize( get_array_key( 'conditions', $data ) );
			$ejob->filtergroups = get_array_key( 'filtergrouping', $data );
			$ejob->printedRows = 0;
			$ejob->totalRows = count( $res );
			$ejob->requestTime = date( 'Y-m-d H:i:s' );
			$ejob->description = tc_generate_english_query( get_array_key( 'conditions', $data ), get_array_key( 'filtergrouping', $data ), get_array_key( 'exportfields', $data ), $leadsToExport );
			R::store( $ejob );
			ajax_success( $ejob->export() );
		}
		catch( Exception $e ) {
			if ( true == DEBUG ) {
				ajax_failure( $e->getMessage() );
			}
			ajax_failure( 'Invalid Filter or Condition Grouping' );
		}
	}

	function tc_generate_export_query( $count = true, $conditions = array(), $grouping = '', $fields = array(), $max = null ) {
		if ( table_exists( 'exportjobs_lead' ) ) {
			$idQuery = ( true == $count ) ? 'SELECT COUNT( DISTINCT( lead.id ) ) FROM lead LEFT JOIN ( SELECT COUNT( exportjobs_lead.exportjobs_id ) as exportcount, exportjobs_lead.lead_id FROM exportjobs_lead GROUP BY exportjobs_lead.lead_id ) c ON lead.id = c.lead_id WHERE lead.id IS NOT NULL' : 'SELECT DISTINCT( lead.id ), ( SELECT COUNT(id) FROM exportjobs_lead WHERE exportjobs_lead.lead_id = lead.id ) as exportcount FROM lead LEFT JOIN ( SELECT COUNT( exportjobs_lead.exportjobs_id ) as exportcount, exportjobs_lead.lead_id FROM exportjobs_lead GROUP BY exportjobs_lead.lead_id ) c ON lead.id = c.lead_id WHERE lead.id IS NOT NULL';
		}
		else {
			$idQuery = ( true == $count ) ? 'SELECT COUNT( DISTINCT( lead.id ) ) FROM lead WHERE lead.id IS NOT NULL' : 'SELECT DISTINCT( lead.id ), 0 as exportcount FROM lead  WHERE lead.id IS NOT NULL';
		}
		$whereConditions = array();
		$whereConditionVars = array();
		if ( can_loop( $conditions ) ) {
			foreach ( $conditions as $index => $c ) {
				if ( ! table_exists( get_array_key( 'field', $c ) ) ) {
					$sqt = '%s';
					$table = 'lead';
					$field = sprintf( '%s.%s', $table, get_array_key( 'field', $c ) );
				}
				else {
					switch ( get_array_key( 'field', $c ) ) {
						case 'phone':
							$sqt = 'lead.id IN ( SELECT lead_phone.lead_id FROM lead_phone LEFT JOIN phone ON lead_phone.phone_id = phone.id WHERE %s )';
							$table = 'phone';
							$field = sprintf( '%s.%s', $table, get_array_key( 'attribute', $c ) );
							break;

						case 'email':
							$sqt = 'lead.id IN ( SELECT email_lead.lead_id FROM email_lead LEFT JOIN email ON email_lead.email_id = email.id WHERE %s )';
							$table = 'email';
							$field = sprintf( '%s.%s', $table, get_array_key( 'attribute', $c ) );
							break;

						case 'skype':
							$sqt = 'lead.id IN ( SELECT skype_lead.lead_id FROM skype_lead LEFT JOIN skype ON skype_lead.skype_id = skype.id WHERE %s )';
							$table = 'skype';
							$field = sprintf( '%s.%s', $table, get_array_key( 'attribute', $c ) );
							break;

						case 'ip':
							$sqt = 'lead.id IN ( SELECT ip_lead.lead_id FROM ip_lead LEFT JOIN ip ON ip_lead.ip_id = ip.id WHERE %s )';
							$table = 'ip';
							$field = sprintf( '%s.%s', $table, get_array_key( 'attribute', $c ) );
							break;

						case 'source':
							$sqt = 'lead.id IN ( SELECT lead_source.lead_id FROM lead_source LEFT JOIN source ON lead_source.source_id = source.id WHERE %s )';
							$table = 'souce';
							$field = sprintf( '%s.%s', $table, get_array_key( 'attribute', $c ) );
							break;

						case 'language':
							$sqt = 'lead.id IN ( SELECT language_lead.lead_id FROM language_lead LEFT JOIN language ON language_lead.language_id = language.id WHERE %s )';
							$table = 'language';
							$field = sprintf( '%s.%s', $table, get_array_key( 'attribute', $c ) );
							break;

						case 'tag':
							$sqt = 'lead.id IN ( SELECT lead_tag.lead_id FROM lead_tag LEFT JOIN tag ON lead_tag.tag_id = tag.id WHERE %s )';
							$table = 'tag';
							$field = sprintf( '%s.%s', $table, get_array_key( 'attribute', $c ) );
							break;

						case 'meta':
							$sqt = 'lead.id IN ( SELECT lead_leadmeta.lead_id FROM lead_leadmeta LEFT JOIN leadmeta ON lead_leadmeta.leadmeta_id = leadmeta.id WHERE %s )';
							$table = 'leadmeta';
							$field = sprintf( '%s.%s', $table, get_array_key( 'attribute', $c ) );
							break;

						case 'leadmeta':
							$sqt = 'lead.id IN ( SELECT lead_leadmeta.lead_id FROM lead_leadmeta LEFT JOIN leadmeta ON lead_leadmeta.leadmeta_id = leadmeta.id WHERE %s )';
							$table = 'leadmeta';
							$field = sprintf( '%s.%s', $table, get_array_key( 'attribute', $c ) );
							break;

						case 'exportcount':
							$sqt = '%s';
							$table = 'c';
							$field = 'exportcount';
							break;

						default:
							$sqt = '%s';
							$table = 'lead';
							$field = sprintf( '%s.%s', $table, get_array_key( 'field', $c ) );
							break;
					}
				}
				$wkey = strtolower( str_replace( '.', '_', $field ) );
				switch ( get_array_key( 'condition', $c ) ) {
					case '!INTNULL!':
						$condition = sprintf( "( %s IS NULL or %s = 0 )", $field, $field );
						$whereConditions[ $index ] = sprintf( $sqt, $condition );
						break;

					case '!INTNOTNULL!':
						$condition = sprintf( "( %s IS NOT NULL AND %s <> 0 )", $field, $field );
						$whereConditions[ $index ] = sprintf( $sqt, $condition );
						break;

					case '!NULL!':
						$condition = sprintf( "( %s IS NULL or %s LIKE '' )", $field, $field );
						$whereConditions[ $index ] = sprintf( $sqt, $condition );
						break;

					case '!NOTNULL!':
						$condition = sprintf( "( %s IS NOT NULL AND %s <> '' )", $field, $field );
						$whereConditions[ $index ] = sprintf( $sqt, $condition );
						break;

					case '<>':
						$condition = sprintf( '%s <> :%s', $field, $wkey );
						$whereConditions[ $index ] = sprintf( $sqt, $condition );
						$whereConditionVars[ $index ][ $wkey ] = get_array_key( 'filter', $c );
						break;

					case '>':
						$condition = sprintf( '%s > :%s', $field, $wkey );
						$whereConditions[ $index ] = sprintf( $sqt, $condition );
						$whereConditionVars[ $index ][ $wkey ] = get_array_key( 'filter', $c );
						break;

					case '|->':
						$condition = sprintf( '%s > :%s', $field, $wkey );
						$whereConditions[ $index ] = sprintf( $sqt, $condition );
						$whereConditionVars[ $index ][ $wkey ] = get_array_key( 'filter', $c );
						break;

					case '>=':
						$condition = sprintf( '%s >= :%s', $field, $wkey );
						$whereConditions[ $index ] = sprintf( $sqt, $condition );
						$whereConditionVars[ $index ][ $wkey ] = get_array_key( 'filter', $c );
						break;

					case '<':
						$condition = sprintf( '%s < :%s', $field, $wkey );
						$whereConditions[ $index ] = sprintf( $sqt, $condition );
						$whereConditionVars[ $index ][ $wkey ] = get_array_key( 'filter', $c );
						break;

					case '<-|':
						$condition = sprintf( '%s < :%s', $field, $wkey );
						$whereConditions[ $index ] = sprintf( $sqt, $condition );
						$whereConditionVars[ $index ][ $wkey ] = get_array_key( 'filter', $c );
						break;

					case '<=':
						$condition = sprintf( '%s <= :%s', $field, $wkey );
						$whereConditions[ $index ] = sprintf( $sqt, $condition );
						$whereConditionVars[ $index ][ $wkey ] = get_array_key( 'filter', $c );
						break;

					case '%_%':
						$condition = sprintf( '%s LIKE :%s', $field, $wkey );
						$whereConditions[ $index ] = sprintf( $sqt, $condition );
						$whereConditionVars[ $index ][ $wkey ] = sprintf( '%s%s%s', '%', get_array_key( 'filter', $c ), '%' );
						break;

					case '!%_%':
						$condition = sprintf( '%s NOT LIKE :%s', $field, $wkey );
						$whereConditions[ $index ] = sprintf( $sqt, $condition );
						$whereConditionVars[ $index ][ $wkey ] = sprintf( '%s%s%s', '%', get_array_key( 'filter', $c ), '%' );
						break;

					case '%_':
						$condition = sprintf( '%s LIKE :%s', $field, $wkey );
						$whereConditions[ $index ] = sprintf( $sqt, $condition );
						$whereConditionVars[ $index ][ $wkey ] = sprintf( '%s%s%s', '', get_array_key( 'filter', $c ), '%' );
						break;

					case '!%_':
						$condition = sprintf( '%s NOT LIKE :%s', $field, $wkey );
						$whereConditions[ $index ] = sprintf( $sqt, $condition );
						$whereConditionVars[ $index ][ $wkey ] = sprintf( '%s%s%s', '', get_array_key( 'filter', $c ), '%' );
						break;

					case '_%':
						$condition = sprintf( '%s LIKE :%s', $field, $wkey );
						$whereConditions[ $index ] = sprintf( $sqt, $condition );
						$whereConditionVars[ $index ][ $wkey ] = sprintf( '%s%s%s', '%', get_array_key( 'filter', $c ), '' );
						break;

					case '!_%':
						$condition = sprintf( '%s NOT LIKE :%s', $field, $wkey );
						$whereConditions[ $index ] = sprintf( $sqt, $condition );
						$whereConditionVars[ $index ][ $wkey ] = sprintf( '%s%s%s', '%', get_array_key( 'filter', $c ), '' );
						break;

					case '()':
						$list = array();
						if ( can_loop( get_array_key( 'filter', $c ) ) ) {
							foreach ( get_array_key( 'filter', $c ) as $value ) {
								array_push( $list, escape_sql_input( $value ) );
							}
						}
						$inList = implode( ',', $list );
						$condition = sprintf( '%s IN ( %s )', $field, $inList );
						$whereConditions[ $index ] = sprintf( $sqt, $condition );
						break;

					case '!()':
						$list = array();
						if ( can_loop( get_array_key( 'filter', $c ) ) ) {
							foreach ( get_array_key( 'filter', $c ) as $value ) {
								array_push( $list, escape_sql_input( $value ) );
							}
						}
						$inList = implode( ',', $list );
						$condition = sprintf( '%s NOT IN ( %s )', $field, $inList );
						$whereConditions[ $index ] = sprintf( $sqt, $condition );
						break;

					case '|-|':
						$firstKey = sprintf( 'first%s', $wkey );
						$lastKey = sprintf( 'last%s', $wkey );
						$condition = sprintf( '%s BETWEEN :%s AND :%s', $field, $firstKey, $lastKey );
						$whereConditions[ $index ] = sprintf( $sqt, $condition );
						$whereConditionVars[ $index ][ $firstKey ] = get_array_key( 'start', get_array_key( 'filter', $c, array() ) );
						$whereConditionVars[ $index ][ $lastKey ] = get_array_key( 'end', get_array_key( 'filter', $c, array() ) );
						break;

					case '!|-|':
						$firstKey = sprintf( 'first%s', $wkey );
						$lastKey = sprintf( 'last%s', $wkey );
						$condition = sprintf( '%s NOT BETWEEN :%s AND :%s', $field, $firstKey, $lastKey );
						$whereConditions[ $index ] = sprintf( $sqt, $condition );
						$whereConditionVars[ $index ][ $firstKey ] = get_array_key( 'start', get_array_key( 'filter', $c, array() ) );
						$whereConditionVars[ $index ][ $lastKey ] = get_array_key( 'end', get_array_key( 'filter', $c, array() ) );
						break;


					default:
						$condition = sprintf( '%s = :%s', $field, $wkey );
						$whereConditions[ $index ] = sprintf( $sqt, $condition );
						$whereConditionVars[ $index ][ $wkey ] = get_array_key( 'filter', $c );
						break;
				}
			}
		}
		$whereString = $grouping;
		$whereVars = array();
		if ( can_loop( $whereConditions ) ) {
			foreach ( $whereConditions as $index => $condition ) {
				$whereString = str_replace( $index, sprintf( ' %s ', $condition ), $whereString );
				if ( can_loop( get_array_key( $index, $whereConditionVars ) ) ) {
					foreach ( get_array_key( $index, $whereConditionVars ) as $wk => $wv ) {
						$whereVars[ $wk ] = $wv;
					}
				}
			}
		}
		$idQuery .= ( can_loop( $whereConditions ) ? ' AND ' : ' ' ) . $whereString;
		if ( true !== $count && absint( $max ) > 0 ) {
			$idQuery .= sprintf( ' GROUP BY lead.id ORDER BY exportcount ASC LIMIT %d', $max );
		}
		else if ( true !== $count && 0 == absint( $max ) ) {
			$idQuery .= sprintf( ' GROUP BY lead.id ORDER BY exportcount ASC', $max );
		}
		$return = array(
			'query' => $idQuery,
			'vars' => $whereVars,
		);
		return $return;
	}

    function tc_generate_export_query_with_group_by( $count = true, $conditions = array(), $grouping = '', $fields = array(), $max = null ) {
        if ( table_exists( 'exportjobs_lead' ) ) {
            $idQuery = ( true == $count ) ? 'SELECT COUNT( DISTINCT( lead.id ) ) as value, lead.country as code FROM lead LEFT JOIN ( SELECT COUNT( exportjobs_lead.exportjobs_id ) as exportcount, exportjobs_lead.lead_id FROM exportjobs_lead GROUP BY exportjobs_lead.lead_id ) c ON lead.id = c.lead_id WHERE lead.id IS NOT NULL' : 'SELECT DISTINCT( lead.id ), ( SELECT COUNT(id) FROM exportjobs_lead WHERE exportjobs_lead.lead_id = lead.id ) as exportcount FROM lead LEFT JOIN ( SELECT COUNT( exportjobs_lead.exportjobs_id ) as exportcount, exportjobs_lead.lead_id FROM exportjobs_lead GROUP BY exportjobs_lead.lead_id ) c ON lead.id = c.lead_id WHERE lead.id IS NOT NULL';
        }
        else {
            $idQuery = ( true == $count ) ? 'SELECT COUNT( DISTINCT( lead.id ) ) as value, lead.country as code FROM lead WHERE lead.id IS NOT NULL' : 'SELECT DISTINCT( lead.id ), 0 as exportcount FROM lead  WHERE lead.id IS NOT NULL';
        }
        $whereConditions = array();
        $whereConditionVars = array();
        if ( can_loop( $conditions ) ) {
            foreach ( $conditions as $index => $c ) {
                switch ( get_array_key( 'field', $c ) ) {
                    case 'phone':
                        $sqt = 'lead.id IN ( SELECT lead_phone.lead_id FROM lead_phone LEFT JOIN phone ON lead_phone.phone_id = phone.id WHERE %s )';
                        $table = 'phone';
                        $field = sprintf( '%s.%s', $table, get_array_key( 'attribute', $c ) );
                        break;

                    case 'email':
                        $sqt = 'lead.id IN ( SELECT email_lead.lead_id FROM email_lead LEFT JOIN email ON email_lead.email_id = email.id WHERE %s )';
                        $table = 'email';
                        $field = sprintf( '%s.%s', $table, get_array_key( 'attribute', $c ) );
                        break;

                    case 'skype':
                        $sqt = 'lead.id IN ( SELECT skype_lead.lead_id FROM skype_lead LEFT JOIN skype ON skype_lead.skype_id = skype.id WHERE %s )';
                        $table = 'skype';
                        $field = sprintf( '%s.%s', $table, get_array_key( 'attribute', $c ) );
                        break;

                    case 'ip':
                        $sqt = 'lead.id IN ( SELECT ip_lead.lead_id FROM ip_lead LEFT JOIN ip ON ip_lead.ip_id = ip.id WHERE %s )';
                        $table = 'ip';
                        $field = sprintf( '%s.%s', $table, get_array_key( 'attribute', $c ) );
                        break;

                    case 'source':
                        $sqt = 'lead.id IN ( SELECT lead_source.lead_id FROM lead_source LEFT JOIN source ON lead_source.source_id = source.id WHERE %s )';
                        $table = 'souce';
                        $field = sprintf( '%s.%s', $table, get_array_key( 'attribute', $c ) );
                        break;

                    case 'language':
                        $sqt = 'lead.id IN ( SELECT language_lead.lead_id FROM language_lead LEFT JOIN language ON language_lead.language_id = language.id WHERE %s )';
                        $table = 'language';
                        $field = sprintf( '%s.%s', $table, get_array_key( 'attribute', $c ) );
                        break;

                    case 'tag':
                        $sqt = 'lead.id IN ( SELECT lead_tag.lead_id FROM lead_tag LEFT JOIN tag ON lead_tag.tag_id = tag.id WHERE %s )';
                        $table = 'tag';
                        $field = sprintf( '%s.%s', $table, get_array_key( 'attribute', $c ) );
                        break;

                    case 'meta':
                        $sqt = 'lead.id IN ( SELECT lead_leadmeta.lead_id FROM lead_leadmeta LEFT JOIN leadmeta ON lead_leadmeta.leadmeta_id = leadmeta.id WHERE %s )';
                        $table = 'leadmeta';
                        $field = sprintf( '%s.%s', $table, get_array_key( 'attribute', $c ) );
                        break;

                    case 'leadmeta':
                        $sqt = 'lead.id IN ( SELECT lead_leadmeta.lead_id FROM lead_leadmeta LEFT JOIN leadmeta ON lead_leadmeta.leadmeta_id = leadmeta.id WHERE %s )';
                        $table = 'leadmeta';
                        $field = sprintf( '%s.%s', $table, get_array_key( 'attribute', $c ) );
                        break;

                    case 'exportcount':
                        $sqt = '%s';
                        $table = 'c';
                        $field = 'exportcount';
                        break;

                    default:
                        $sqt = '%s';
                        $table = 'lead';
                        $field = sprintf( '%s.%s', $table, get_array_key( 'field', $c ) );
                        break;
                }
                $wkey = strtolower( str_replace( '.', '_', $field ) );
                switch ( get_array_key( 'condition', $c ) ) {
                    case '!INTNULL!':
                        $condition = sprintf( "( %s IS NULL or %s = 0 )", $field, $field );
                        $whereConditions[ $index ] = sprintf( $sqt, $condition );
                        break;

                    case '!INTNOTNULL!':
                        $condition = sprintf( "( %s IS NOT NULL AND %s <> 0 )", $field, $field );
                        $whereConditions[ $index ] = sprintf( $sqt, $condition );
                        break;

                    case '!NULL!':
                        $condition = sprintf( "( %s IS NULL or %s LIKE '' )", $field, $field );
                        $whereConditions[ $index ] = sprintf( $sqt, $condition );
                        break;

                    case '!NOTNULL!':
                        $condition = sprintf( "( %s IS NOT NULL AND %s <> '' )", $field, $field );
                        $whereConditions[ $index ] = sprintf( $sqt, $condition );
                        break;

                    case '<>':
                        $condition = sprintf( '%s <> :%s', $field, $wkey );
                        $whereConditions[ $index ] = sprintf( $sqt, $condition );
                        $whereConditionVars[ $index ][ $wkey ] = get_array_key( 'filter', $c );
                        break;

                    case '>':
                        $condition = sprintf( '%s > :%s', $field, $wkey );
                        $whereConditions[ $index ] = sprintf( $sqt, $condition );
                        $whereConditionVars[ $index ][ $wkey ] = get_array_key( 'filter', $c );
                        break;

                    case '|->':
                        $condition = sprintf( '%s > :%s', $field, $wkey );
                        $whereConditions[ $index ] = sprintf( $sqt, $condition );
                        $whereConditionVars[ $index ][ $wkey ] = get_array_key( 'filter', $c );
                        break;

                    case '>=':
                        $condition = sprintf( '%s >= :%s', $field, $wkey );
                        $whereConditions[ $index ] = sprintf( $sqt, $condition );
                        $whereConditionVars[ $index ][ $wkey ] = get_array_key( 'filter', $c );
                        break;

                    case '<':
                        $condition = sprintf( '%s < :%s', $field, $wkey );
                        $whereConditions[ $index ] = sprintf( $sqt, $condition );
                        $whereConditionVars[ $index ][ $wkey ] = get_array_key( 'filter', $c );
                        break;

                    case '<-|':
                        $condition = sprintf( '%s < :%s', $field, $wkey );
                        $whereConditions[ $index ] = sprintf( $sqt, $condition );
                        $whereConditionVars[ $index ][ $wkey ] = get_array_key( 'filter', $c );
                        break;

                    case '<=':
                        $condition = sprintf( '%s <= :%s', $field, $wkey );
                        $whereConditions[ $index ] = sprintf( $sqt, $condition );
                        $whereConditionVars[ $index ][ $wkey ] = get_array_key( 'filter', $c );
                        break;

                    case '%_%':
                        $condition = sprintf( '%s LIKE :%s', $field, $wkey );
                        $whereConditions[ $index ] = sprintf( $sqt, $condition );
                        $whereConditionVars[ $index ][ $wkey ] = sprintf( '%s%s%s', '%', get_array_key( 'filter', $c ), '%' );
                        break;

                    case '!%_%':
                        $condition = sprintf( '%s NOT LIKE LIKE :%s', $field, $wkey );
                        $whereConditions[ $index ] = sprintf( $sqt, $condition );
                        $whereConditionVars[ $index ][ $wkey ] = sprintf( '%s%s%s', '%', get_array_key( 'filter', $c ), '%' );
                        break;

                    case '%_':
                        $condition = sprintf( '%s LIKE :%s', $field, $wkey );
                        $whereConditions[ $index ] = sprintf( $sqt, $condition );
                        $whereConditionVars[ $index ][ $wkey ] = sprintf( '%s%s%s', '', get_array_key( 'filter', $c ), '%' );
                        break;

                    case '!%_':
                        $condition = sprintf( '%s NOT LIKE LIKE :%s', $field, $wkey );
                        $whereConditions[ $index ] = sprintf( $sqt, $condition );
                        $whereConditionVars[ $index ][ $wkey ] = sprintf( '%s%s%s', '', get_array_key( 'filter', $c ), '%' );
                        break;

                    case '_%':
                        $condition = sprintf( '%s LIKE :%s', $field, $wkey );
                        $whereConditions[ $index ] = sprintf( $sqt, $condition );
                        $whereConditionVars[ $index ][ $wkey ] = sprintf( '%s%s%s', '%', get_array_key( 'filter', $c ), '' );
                        break;

                    case '!_%':
                        $condition = sprintf( '%s NOT LIKE LIKE :%s', $field, $wkey );
                        $whereConditions[ $index ] = sprintf( $sqt, $condition );
                        $whereConditionVars[ $index ][ $wkey ] = sprintf( '%s%s%s', '%', get_array_key( 'filter', $c ), '' );
                        break;

                    case '()':
                        $list = array();
                        if ( can_loop( get_array_key( 'filter', $c ) ) ) {
                            foreach ( get_array_key( 'filter', $c ) as $value ) {
                                array_push( $list, escape_sql_input( $value ) );
                            }
                        }
                        $inList = implode( ',', $list );
                        $condition = sprintf( '%s IN ( %s )', $field, $inList );
                        $whereConditions[ $index ] = sprintf( $sqt, $condition );
                        break;

                    case '!()':
                        $list = array();
                        if ( can_loop( get_array_key( 'filter', $c ) ) ) {
                            foreach ( get_array_key( 'filter', $c ) as $value ) {
                                array_push( $list, escape_sql_input( $value ) );
                            }
                        }
                        $inList = implode( ',', $list );
                        $condition = sprintf( '%s NOT IN ( %s )', $field, $inList );
                        $whereConditions[ $index ] = sprintf( $sqt, $condition );
                        break;

                    case '|-|':
                        $firstKey = sprintf( 'first%s', $wkey );
                        $lastKey = sprintf( 'last%s', $wkey );
                        $condition = sprintf( '%s BETWEEN :%s AND :%s', $field, $firstKey, $lastKey );
                        $whereConditions[ $index ] = sprintf( $sqt, $condition );
                        $whereConditionVars[ $index ][ $firstKey ] = get_array_key( 'start', get_array_key( 'filter', $c, array() ) );
                        $whereConditionVars[ $index ][ $lastKey ] = get_array_key( 'end', get_array_key( 'filter', $c, array() ) );
                        break;

                    case '!|-|':
                        $firstKey = sprintf( 'first%s', $wkey );
                        $lastKey = sprintf( 'last%s', $wkey );
                        $condition = sprintf( '%s NOT BETWEEN :%s AND :%s', $field, $firstKey, $lastKey );
                        $whereConditions[ $index ] = sprintf( $sqt, $condition );
                        $whereConditionVars[ $index ][ $firstKey ] = get_array_key( 'start', get_array_key( 'filter', $c, array() ) );
                        $whereConditionVars[ $index ][ $lastKey ] = get_array_key( 'end', get_array_key( 'filter', $c, array() ) );
                        break;


                    default:
                        $condition = sprintf( '%s = :%s', $field, $wkey );
                        $whereConditions[ $index ] = sprintf( $sqt, $condition );
                        $whereConditionVars[ $index ][ $wkey ] = get_array_key( 'filter', $c );
                        break;
                }
            }
        }
        $whereString = $grouping;
        $whereVars = array();
        if ( can_loop( $whereConditions ) ) {
            foreach ( $whereConditions as $index => $condition ) {
                $whereString = str_replace( $index, sprintf( ' %s ', $condition ), $whereString );
                if ( can_loop( get_array_key( $index, $whereConditionVars ) ) ) {
                    foreach ( get_array_key( $index, $whereConditionVars ) as $wk => $wv ) {
                        $whereVars[ $wk ] = $wv;
                    }
                }
            }
        }
        $idQuery .= ( can_loop( $whereConditions ) ? ' AND ' : ' ' ) . $whereString;
        if ( true !== $count && absint( $max ) > 0 ) {
            $idQuery .= sprintf( ' GROUP BY lead.id ORDER BY exportcount ASC LIMIT %d', $max );
        }
        else if ( true !== $count && 0 == absint( $max ) ) {
            $idQuery .= sprintf( ' GROUP BY lead.id ORDER BY exportcount ASC', $max );
        }
        $idQuery.= "GROUP BY lead.country ORDER BY COUNT( DISTINCT( lead.id ) ) DESC;";

        $return = array(
            'query' => $idQuery,
            'vars' => $whereVars,
        );
        return $return;
    }

	function tc_generate_english_query( $conditions = array(), $grouping = '', $fields = array(), $max = null ) {
		$idQuery = sprintf( '%s unique leads where ', $max );
		$whereConditions = array();
		$whereConditionVars = array();
		if ( can_loop( $conditions ) ) {
			foreach ( $conditions as $index => $c ) {
				switch ( get_array_key( 'field', $c ) ) {
					case 'phone':
						$sqt = '%s';
						$table = 'phone';
						$field = sprintf( '%s.%s', $table, get_array_key( 'attribute', $c ) );
						break;

					case 'email':
						$sqt = '%s';
						$table = 'email';
						$field = sprintf( '%s.%s', $table, get_array_key( 'attribute', $c ) );
						break;

					case 'skype':
						$sqt = '%s';
						$table = 'skype';
						$field = sprintf( '%s.%s', $table, get_array_key( 'attribute', $c ) );
						break;

					case 'ip':
						$sqt = '%s';
						$table = 'ip';
						$field = sprintf( '%s.%s', $table, get_array_key( 'attribute', $c ) );
						break;

					case 'source':
						$sqt = '%s';
						$table = 'source';
						$field = sprintf( '%s.%s', $table, get_array_key( 'attribute', $c ) );
						break;

					case 'language':
						$sqt = '%s';
						$table = 'language';
						$field = sprintf( '%s.%s', $table, get_array_key( 'attribute', $c ) );
						break;

					case 'tag':
						$sqt = '%s';
						$table = 'tag';
						$field = sprintf( '%s.%s', $table, get_array_key( 'attribute', $c ) );
						break;

					case 'meta':
						$sqt = '%s';
						$table = 'leadmeta';
						$field = sprintf( '%s.%s', $table, get_array_key( 'attribute', $c ) );
						break;

					case 'leadmeta':
						$sqt = '%s';
						$table = 'leadmeta';
						$field = sprintf( '%s.%s', $table, get_array_key( 'attribute', $c ) );
						break;

					default:
						$sqt = '%s';
						$table = 'lead';
						$field = sprintf( '%s.%s', $table, get_array_key( 'field', $c ) );
						break;
				}
				$wkey = strtolower( str_replace( '.', '_', $field ) );
				switch ( get_array_key( 'condition', $c ) ) {
					case '!NULL!':
						$condition = sprintf( '%s is blank', $field );
						$whereConditions[ $index ] = sprintf( $sqt, $condition );
						break;

					case '!NOTNULL!':
						$condition = sprintf( "%s is not blank", $field, $field );
						$whereConditions[ $index ] = sprintf( $sqt, $condition );
						break;

					case '!INTNULL!':
						$condition = sprintf( '%s is blank', $field );
						$whereConditions[ $index ] = sprintf( $sqt, $condition );
						break;

					case '!INTNOTNULL!':
						$condition = sprintf( "%s is not blank", $field, $field );
						$whereConditions[ $index ] = sprintf( $sqt, $condition );
						break;

					case '<>':
						$condition = sprintf( '%s different than :%s', $field, $wkey );
						$whereConditions[ $index ] = sprintf( $sqt, $condition );
						$whereConditionVars[ $index ][ $wkey ] = get_array_key( 'filter', $c );
						break;

					case '>':
						$condition = sprintf( '%s is greater than :%s', $field, $wkey );
						$whereConditions[ $index ] = sprintf( $sqt, $condition );
						$whereConditionVars[ $index ][ $wkey ] = get_array_key( 'filter', $c );
						break;

					case '|->':
						$condition = sprintf( '%s is after :%s', $field, $wkey );
						$whereConditions[ $index ] = sprintf( $sqt, $condition );
						$whereConditionVars[ $index ][ $wkey ] = get_array_key( 'filter', $c );
						break;

					case '>=':
						$condition = sprintf( '%s is greater than or equal to :%s', $field, $wkey );
						$whereConditions[ $index ] = sprintf( $sqt, $condition );
						$whereConditionVars[ $index ][ $wkey ] = get_array_key( 'filter', $c );
						break;

					case '<':
						$condition = sprintf( '%s is less than :%s', $field, $wkey );
						$whereConditions[ $index ] = sprintf( $sqt, $condition );
						$whereConditionVars[ $index ][ $wkey ] = get_array_key( 'filter', $c );
						break;

					case '<-|':
						$condition = sprintf( '%s is before :%s', $field, $wkey );
						$whereConditions[ $index ] = sprintf( $sqt, $condition );
						$whereConditionVars[ $index ][ $wkey ] = get_array_key( 'filter', $c );
						break;

					case '<=':
						$condition = sprintf( '%s is less than or equal to :%s', $field, $wkey );
						$whereConditions[ $index ] = sprintf( $sqt, $condition );
						$whereConditionVars[ $index ][ $wkey ] = get_array_key( 'filter', $c );
						break;

					case '%_%':
						$condition = sprintf( '%s contains :%s', $field, $wkey );
						$whereConditions[ $index ] = sprintf( $sqt, $condition );
						$whereConditionVars[ $index ][ $wkey ] = sprintf( '%s%s%s', '%', get_array_key( 'filter', $c ), '%' );
						break;

					case '!%_%':
						$condition = sprintf( '%s does not contain :%s', $field, $wkey );
						$whereConditions[ $index ] = sprintf( $sqt, $condition );
						$whereConditionVars[ $index ][ $wkey ] = sprintf( '%s%s%s', '%', get_array_key( 'filter', $c ), '%' );
						break;

					case '%_':
						$condition = sprintf( '%s begins with :%s', $field, $wkey );
						$whereConditions[ $index ] = sprintf( $sqt, $condition );
						$whereConditionVars[ $index ][ $wkey ] = sprintf( '%s%s%s', '', get_array_key( 'filter', $c ), '%' );
						break;

					case '!%_':
						$condition = sprintf( '%s does not begin with :%s', $field, $wkey );
						$whereConditions[ $index ] = sprintf( $sqt, $condition );
						$whereConditionVars[ $index ][ $wkey ] = sprintf( '%s%s%s', '', get_array_key( 'filter', $c ), '%' );
						break;

					case '_%':
						$condition = sprintf( '%s ends with :%s', $field, $wkey );
						$whereConditions[ $index ] = sprintf( $sqt, $condition );
						$whereConditionVars[ $index ][ $wkey ] = sprintf( '%s%s%s', '%', get_array_key( 'filter', $c ), '' );
						break;

					case '!_%':
						$condition = sprintf( '%s does not end with :%s', $field, $wkey );
						$whereConditions[ $index ] = sprintf( $sqt, $condition );
						$whereConditionVars[ $index ][ $wkey ] = sprintf( '%s%s%s', '%', get_array_key( 'filter', $c ), '' );
						break;

					case '()':
						$list = array();
						if ( can_loop( get_array_key( 'filter', $c ) ) ) {
							foreach ( get_array_key( 'filter', $c ) as $value ) {
								array_push( $list, ( $value ) );
							}
						}
						$inList = implode( ',', $list );
						$condition = sprintf( '%s in list ( %s )', $field, $inList );
						$whereConditions[ $index ] = sprintf( $sqt, $condition );
						break;

					case '!()':
						$list = array();
						if ( can_loop( get_array_key( 'filter', $c ) ) ) {
							foreach ( get_array_key( 'filter', $c ) as $value ) {
								array_push( $list, ( $value ) );
							}
						}
						$inList = implode( ',', $list );
						$condition = sprintf( '%s is not in list ( %s )', $field, $inList );
						$whereConditions[ $index ] = sprintf( $sqt, $condition );
						break;

					case '|-|':
						$firstKey = sprintf( 'first%s', $wkey );
						$lastKey = sprintf( 'last%s', $wkey );
						$condition = sprintf( '%s is between :%s and :%s', $field, $firstKey, $lastKey );
						$whereConditions[ $index ] = sprintf( $sqt, $condition );
						$whereConditionVars[ $index ][ $firstKey ] = get_array_key( 'start', get_array_key( 'filter', $c, array() ) );
						$whereConditionVars[ $index ][ $lastKey ] = get_array_key( 'end', get_array_key( 'filter', $c, array() ) );
						break;

					case '!|-|':
						$firstKey = sprintf( 'first%s', $wkey );
						$lastKey = sprintf( 'last%s', $wkey );
						$condition = sprintf( '%s is not between :%s AND :%s', $field, $firstKey, $lastKey );
						$whereConditions[ $index ] = sprintf( $sqt, $condition );
						$whereConditionVars[ $index ][ $firstKey ] = get_array_key( 'start', get_array_key( 'filter', $c, array() ) );
						$whereConditionVars[ $index ][ $lastKey ] = get_array_key( 'end', get_array_key( 'filter', $c, array() ) );
						break;


					default:
						$condition = sprintf( '%s is the same as :%s', $field, $wkey );
						$whereConditions[ $index ] = sprintf( $sqt, $condition );
						$whereConditionVars[ $index ][ $wkey ] = get_array_key( 'filter', $c );
						break;
				}
			}
		}
		$whereString = $grouping;
		$whereVars = array();
		if ( can_loop( $whereConditions ) ) {
			foreach ( $whereConditions as $index => $condition ) {
				$whereString = str_replace( $index, sprintf( ' %s ', $condition ), $whereString );
				if ( can_loop( get_array_key( $index, $whereConditionVars ) ) ) {
					foreach ( get_array_key( $index, $whereConditionVars ) as $wk => $wv ) {
						$whereVars[ $wk ] = $wv;
					}
				}
			}
		}
		$idQuery .= $whereString;
		$return = $idQuery;
		if ( can_loop( $whereVars ) ) {
			foreach ( $whereVars as $find => $replace ) {
				$return = str_replace( sprintf( ':%s', $find ), $replace, $return );
			}
		}
		return $return;
	}

	function ajax_get_export_job_status( $data ) {
		$return = array();
		if ( can_loop( get_array_key( 'jobs', $data, array() ) ) ) {
			foreach ( get_array_key( 'jobs', $data, array() ) as $job ) {
				if ( 0 !== absint( $job ) ) {
					try {
						$j = R::cachedLoad( 'exportjobs', absint( $job ) );
						unset( $j->fetchQuery );
						unset( $j->fetchVars );
						unset( $j->leadIds );
						unset( $j->fields );
						unset( $j->conditions );
						unset( $j->filtergroups );
						$j->progress = make_percent( $j->printedRows, $j->totalRows, 2 );
						array_push( $return, $j->export() );
					}
					catch ( Exception $e ) {}
				}
			}
		}
		ajax_success( $return );
	}

	function ajax_reset_export_job( $data ) {
		$job = absint( get_array_key( 'job', $data, 0 ) );
		if ( $job <= 0 ) {
			ajax_failure( 'Invalid Export Job' );
		}
		try {
			$j = R::cachedLoad( 'exportjobs', $job );
			if ( 'in progress' !== $j->status ) {
				ajax_failure( sprintf( 'Job is %s', strtoupper( $j->status ) ) );
			}
			$j->status = 'pending reset';
			R::store( $j );
			ajax_success( 'Job Reset' );
		}
		catch( Exception $e ) {
			if ( true == DEBUG ) {
				ajax_failure( $e->getMessage() );
			}
			ajax_failure( 'Invalid Export Job' );
		}
	}

	function tc_run_export_jobs() {
		try {
			$rows = R::find( 'exportjobs', 'WHERE status = :s1 OR status = :s2 ORDER BY id ASC', array(
				's1' => 'new',
				's2' => 'pending reset',
			) );
		}
		catch ( Exception $e ) {
			if ( true == DEBUG ) {
				cli_failure( $e->getMessage() );
			}
			$rows = array();
		}
		cli_echo( sprintf( 'Found %d Jobs which need to be started', count( $rows ) ) );
		if ( can_loop( $rows ) ) {
			foreach ( $rows as $row ) {
				$fb = tc_run_lead_cli_function( 'run-export-job-by-id', $row->id );
				cli_echo( $fb );
			}
		}
		cli_success( null, 'Opened threads to start jobs' );
	}

	function tc_run_export_job( $jobId ) {
		if ( function_exists( 'tc_start_tr_transaction' ) ) {
			tc_start_tr_transaction( 'row-export' );
		}
		cli_echo( 'Fetching Export Job Information' );
		try {
			$j = R::cachedLoad( 'exportjobs', absint( $jobId ) );
			$leads = @unserialize( $j->leadIds );
			$fields = @unserialize( $j->fields );
			$conditions = @unserialize( $j->conditions );
			$grouping = $j->filtergroups;
		}
		catch( Exception $e ) {
			if ( true == DEBUG ) {
				cli_failure( null, $e->getMessage );
			}
			cli_failure( null, 'Failed to Start Job' );
		}
		cli_echo( 'Getting information for each lead' );
		if ( can_loop( $leads ) ) {
			// step 0: set the printedRows = 0 && status = in progress
			cli_echo( 'Updating Job Status' );
			$file = sprintf( '%s%s.csv', FILE_EXPORT_PATH, md5( $j->requestTime . $j->description ) );
			$j->printedRows = 0;
			$j->status = 'in progress';
			$j->file = $file;
			tc_quick_save_bean( $j );
			// step 1: make the file to output;
			if ( ! file_exists( FILE_EXPORT_PATH ) ) {
				mkdir( FILE_EXPORT_PATH, 0777, true );
			}
			// reset file to empty
			$csv = fopen( $file, 'w' );
			// step 2: make header row;
			fputcsv( $csv, $fields );
			foreach ( $leads as $leadId ) {
				// step 3: for each lead, add a row
				try {
					$lead = R::load( 'lead', absint( $leadId ) );
				}
				catch ( Exception $e ) {
					if ( is_cli() && true == DEBUG ) {
						cli_echo( sprintf( 'Error Loading Lead: %s', $e->getMessage() ) );
					}
				}
				// step 3.1 - update the counter on the job by 1
				$vals = array();
				if ( is_redbean( $lead ) ) {
					if ( can_loop( $fields ) ) {
						foreach ( $fields as $field ) {
							list( $table, $prop ) = explode( '.', $field );
							switch ( $table ) {
								case 'phone':
									$obj = tc_get_shared_list_object_matching_conditions( 'phone', $lead, $conditions, $grouping );
									$vals[ $field ] = ( is_redbean( $obj ) ) ? $obj->$prop : null;
									break;

								case 'email':
									$obj = tc_get_shared_list_object_matching_conditions( 'email', $lead, $conditions, $grouping );
									$vals[ $field ] = ( is_redbean( $obj ) ) ? $obj->$prop : null;
									break;

								case 'skype':
									$obj = tc_get_shared_list_object_matching_conditions( 'skype', $lead, $conditions, $grouping );
									$vals[ $field ] = ( is_redbean( $obj ) ) ? $obj->$prop : null;
									break;

								case 'source':
									$obj = tc_get_shared_list_object_matching_conditions( 'source', $lead, $conditions, $grouping );
									$vals[ $field ] = ( is_redbean( $obj ) ) ? $obj->$prop : null;
									break;

								case 'ip':
									$obj = tc_get_shared_list_object_matching_conditions( 'ip', $lead, $conditions, $grouping );
									$vals[ $field ] = ( is_redbean( $obj ) ) ? $obj->$prop : null;
									break;

								case 'language':
									$obj = tc_get_shared_list_object_matching_conditions( 'language', $lead, $conditions, $grouping );
									$vals[ $field ] = ( is_redbean( $obj ) ) ? $obj->$prop : null;
									break;

								case 'tag':
									$obj = tc_get_shared_list_object_matching_conditions( 'tag', $lead, $conditions, $grouping );
									$vals[ $field ] = ( is_redbean( $obj ) ) ? $obj->$prop : null;
									break;

								case 'meta':
									$obj = tc_get_shared_list_object_matching_conditions( 'leadmeta', $lead, $conditions, $grouping );
									$vals[ $field ] = ( is_redbean( $obj ) ) ? $obj->$prop : null;
									break;

								case 'leadmeta':
									$obj = tc_get_shared_list_object_matching_conditions( 'leadmeta', $lead, $conditions, $grouping );
									$vals[ $field ] = ( is_redbean( $obj ) ) ? $obj->$prop : null;
									break;

								default:
									global $tc_fields;
									$filter = get_array_key( 'filterFunction', get_array_key( $table, $tc_fields, array() ), 'tc_filter_text_field' );
									if ( function_exists( $filter ) ) {
										$filter = 'tc_filter_text_field';
									}
									$res = call_user_func( $filter, strip_tags( $lead->$table ), true );
									$vals[ $field ] = ( is_array( $res ) ? implode( ' ', $res ) : $res );
									break;
							}
						}
					}
				}
				else {
					// insert blank record
					if ( can_loop( $fields ) ) {
						foreach ( $fields as $field ) {
							$vals[ $field ] = null;
						}
					}
				}
				cli_echo( $vals );
				$len = fputcsv( $csv, array_values( $vals ) );
				cli_echo( ( false === $len ) ? 'Failed to write line' : sprintf( 'Wrote %d characters', $len ) );
				$j->printedRows ++;
				// step 3.2 - add the lead to the "export job's lead list"
				if ( is_redbean( $lead ) ) {
					$j->noLoad()->sharedLeadList[] = $lead;
				}
				tc_quick_save_bean( $j );
				cli_echo( sprintf( 'Adding Row for Lead ID %d', $leadId ) );
			}
			fclose( $csv );
			cli_echo( sprintf( 'Saved File %s Successfully', $file ) );
		}
		if ( function_exists( 'tr_end_tr_transaction' ) ) {
			tr_end_tr_transaction();
		}
		cli_success( null, 'Operation Completed' );
	}

	function tc_quick_save_bean( $bean ) {
		try {
			return ( R::store( $bean ) > 0 );
		}
		catch( Exception $e ) {
			if ( is_cli() && true == DEBUG ) {
				cli_echo( sprintf( 'Error Quick Saving: %s', $e->getMessage() ) );
			}
			return false;
		}
		cli_echo( 'Bean Quick Saved' );
	}

	function tc_get_object_that_matches_conditions( $objtype, $conditions, $list, $grouping ) {
		$keys = array_keys( $list );
		if ( count( $list ) == 1 ) {
			return $list[ $keys[0] ];
		}
		$eval = $grouping;
		$eval = strtoupper( $eval );
		$eval = str_replace( 'AND', '&&', $eval );
		$eval = str_replace( 'OR', '||', $eval );
		$matches = array();
		$matchingFilters = array();
		if ( can_loop( $conditions ) ) {
			foreach ( $conditions as $index => $data ) {
				if ( get_array_key( 'field', $data, 'value' ) == $objtype ) {
					$matchingFilters[ $index ] = $data;
				}
				else {
					$eval = str_replace( $index, 'is_empty( null )', $eval );
				}
			}
		}
		if ( can_loop( $list ) ) {
			foreach ( $list as $obj ) {
				if ( can_loop( $matchingFilters ) ) {
					foreach ( $matchingFilters as $index => $c ) {
						$field = get_array_key( 'attribute', $c, 'value' );
						$con = get_array_key( 'condition', $c, '=' );
						switch ( $con ) {
							case '!NULL!':
								$condition = sprintf( "is_empty( $obj->%s )", $field );
								break;

							case '!NOTNULL!':
								$condition = sprintf( "! is_empty( $obj->%s )", $field );
								break;

							case '!INTNULL!':
								$condition = sprintf( "is_empty( $obj->%s )", $field );
								break;

							case '!INTNOTNULL!':
								$condition = sprintf( "! is_empty( $obj->%s )", $field );
								break;

							case '<>':
								$condition = sprintf( '$obj->%s !== %s', $field, escape_sql_input( get_array_key( 'filter', $c, '' ) ) );
								break;

							case '>':
								$condition = sprintf( '$obj->%s > %s', $field, escape_sql_input( get_array_key( 'filter', $c, '' ) ) );
								break;

							case '|->':
								$condition = sprintf( '$obj->%s > %s', $field, escape_sql_input( get_array_key( 'filter', $c, '' ) ) );
								break;

							case '>=':
								$condition = sprintf( '$obj->%s >= %s', $field, escape_sql_input( get_array_key( 'filter', $c, '' ) ) );
								break;

							case '<':
								$condition = sprintf( '$obj->%s < %s', $field, escape_sql_input( get_array_key( 'filter', $c, '' ) ) );
								break;

							case '<-|':
								$condition = sprintf( '$obj->%s < %s', $field, escape_sql_input( get_array_key( 'filter', $c, '' ) ) );
								break;

							case '<=':
								$condition = sprintf( '$obj->%s <= %s', $field, escape_sql_input( get_array_key( 'filter', $c, '' ) ) );
								break;

							case '%_%':
								$condition = sprintf( 'false !== strpos( $obj->%s, %s )', $field, escape_sql_input( get_array_key( 'filter', $c, '' ) ) );
								break;

							case '!%_%':
								$condition = sprintf( 'false === strpos( $obj->%s, %s )', $field, escape_sql_input( get_array_key( 'filter', $c, '' ) ) );
								break;

							case '%_':
								$condition = sprintf( 'beginning_matches( %s, $obj->%s )', $field, escape_sql_input( get_array_key( 'filter', $c, '' ) ) );
								break;

							case '!%_':
								$condition = sprintf( '!beginning_matches( %s, $obj->%s )', $field, escape_sql_input( get_array_key( 'filter', $c, '' ) ) );
								break;

							case '_%':
								$condition = sprintf( 'ending_matches( $s, $obj->%s )', $field, escape_sql_input( get_array_key( 'filter', $c, '' ) ) );
								break;

							case '!_%':
								$condition = sprintf( '! ending_matches( $s, $obj->%s )', $field, escape_sql_input( get_array_key( 'filter', $c, '' ) ) );
								break;

							case '()':
								$list = array();
								if ( can_loop( get_array_key( 'filter', $c ) ) ) {
									foreach ( get_array_key( 'filter', $c ) as $value ) {
										array_push( $list, escape_sql_input( $value ) );
									}
								}
								$inList = implode( ',', $list );
								$condition = sprintf( 'in_array( $obj->%s, array( %s ) )', $field, $inList );
								break;

							case '!()':
								$list = array();
								if ( can_loop( get_array_key( 'filter', $c ) ) ) {
									foreach ( get_array_key( 'filter', $c ) as $value ) {
										array_push( $list, escape_sql_input( $value ) );
									}
								}
								$inList = implode( ',', $list );
								$condition = sprintf( '! in_array( $obj->%s, array( %s ) )', $field, $inList );
								break;

							case '|-|':
								$condition = sprintf( 'is_between( $obj->%s, %s, %s )', $field, escape_sql_input( get_array_key( 'start', get_array_key( 'filter', $c, array() ) ) ), escape_sql_input( get_array_key( 'end', get_array_key( 'filter', $c, array() ) ) ) );
								break;

							case '!|-|':
								$condition = sprintf( '!is_between( $obj->%s, %s, %s )', $field, escape_sql_input( get_array_key( 'start', get_array_key( 'filter', $c, array() ) ) ), escape_sql_input( get_array_key( 'end', get_array_key( 'filter', $c, array() ) ) ) );
								break;


							default:
								$condition = sprintf( '$obj->%s == %s', $field, escape_sql_input( get_array_key( 'filter', $c, '' ) ) );
								break;
						}
						$eval = str_replace( $index, $condition, $eval );
					}
				}
				if ( ! beginning_matches( 'return', $eval ) ) {
					$eval = sprintf( 'return ( %s );', $eval );
				}
				if ( eval( $eval ) ) {
					array_push( $matches, $obj );
				}
			}
		}
		if ( count( $matches ) >= 1 ) {
			return $matches[0];
		}
		return $list[ $keys[0] ];
	}

	function tc_get_shared_list_object_matching_conditions( $objtype, $parent, $conditions, $grouping ) {
		$listName = sprintf( 'shared%sList', ucfirst( $objtype ) );
		$idQuery = '';
		$whereConditions = array();
		$whereConditionVars = array();
		if ( can_loop( $conditions ) ) {
			foreach ( $conditions as $index => $c ) {
				if ( $objtype !== get_array_key( 'field', $c, 'value' ) ) {
					unset( $conditions[ $index ] );
				}
			}
		}
		if ( can_loop( $conditions ) ) {
			foreach ( $conditions as $index => $c ) {
				switch ( get_array_key( 'field', $c ) ) {
					case 'phone':
						$sqt = '%s';
						$table = 'phone';
						$field = sprintf( '%s.%s', $table, get_array_key( 'attribute', $c ) );
						break;

					case 'email':
						$sqt = '%s';
						$table = 'email';
						$field = sprintf( '%s.%s', $table, get_array_key( 'attribute', $c ) );
						break;

					case 'skype':
						$sqt = '%s';
						$table = 'skype';
						$field = sprintf( '%s.%s', $table, get_array_key( 'attribute', $c ) );
						break;

					case 'ip':
						$sqt = '%s';
						$table = 'ip';
						$field = sprintf( '%s.%s', $table, get_array_key( 'attribute', $c ) );
						break;

					case 'source':
						$sqt = '%s';
						$table = 'souce';
						$field = sprintf( '%s.%s', $table, get_array_key( 'attribute', $c ) );
						break;

					case 'language':
						$sqt = '%s';
						$table = 'language';
						$field = sprintf( '%s.%s', $table, get_array_key( 'attribute', $c ) );
						break;

					case 'tag':
						$sqt = '%s';
						$table = 'tag';
						$field = sprintf( '%s.%s', $table, get_array_key( 'attribute', $c ) );
						break;

					case 'meta':
						$sqt = '%s';
						$table = 'leadmeta';
						$field = sprintf( '%s.%s', $table, get_array_key( 'attribute', $c ) );
						break;

					case 'leadmeta':
						$sqt = '%s';
						$table = 'leadmeta';
						$field = sprintf( '%s.%s', $table, get_array_key( 'attribute', $c ) );
						break;

					default:
						$sqt = '%s';
						$table = 'lead';
						$field = sprintf( '%s.%s', $table, get_array_key( 'field', $c ) );
						break;
				}
				$wkey = strtolower( str_replace( '.', '_', $field ) );
				switch ( get_array_key( 'condition', $c ) ) {
					case '!NULL!':
						$condition = sprintf( "( %s IS NULL or %s LIKE '' )", $field, $field );
						$whereConditions[ $index ] = sprintf( $sqt, $condition );
						break;

					case '!NOTNULL!':
						$condition = sprintf( "( %s IS NOT NULL AND %s <> '' )", $field, $field );
						$whereConditions[ $index ] = sprintf( $sqt, $condition );
						break;

					case '!INTNULL!':
						$condition = sprintf( "( %s IS NULL or %s = 0' )", $field, $field );
						$whereConditions[ $index ] = sprintf( $sqt, $condition );
						break;

					case '!INTNOTNULL!':
						$condition = sprintf( "( %s IS NOT NULL AND %s <> 0 )", $field, $field );
						$whereConditions[ $index ] = sprintf( $sqt, $condition );
						break;

					case '<>':
						$condition = sprintf( '%s <> :%s', $field, $wkey );
						$whereConditions[ $index ] = sprintf( $sqt, $condition );
						$whereConditionVars[ $index ][ $wkey ] = get_array_key( 'filter', $c );
						break;

					case '>':
						$condition = sprintf( '%s > :%s', $field, $wkey );
						$whereConditions[ $index ] = sprintf( $sqt, $condition );
						$whereConditionVars[ $index ][ $wkey ] = get_array_key( 'filter', $c );
						break;

					case '|->':
						$condition = sprintf( '%s > :%s', $field, $wkey );
						$whereConditions[ $index ] = sprintf( $sqt, $condition );
						$whereConditionVars[ $index ][ $wkey ] = get_array_key( 'filter', $c );
						break;

					case '>=':
						$condition = sprintf( '%s >= :%s', $field, $wkey );
						$whereConditions[ $index ] = sprintf( $sqt, $condition );
						$whereConditionVars[ $index ][ $wkey ] = get_array_key( 'filter', $c );
						break;

					case '<':
						$condition = sprintf( '%s < :%s', $field, $wkey );
						$whereConditions[ $index ] = sprintf( $sqt, $condition );
						$whereConditionVars[ $index ][ $wkey ] = get_array_key( 'filter', $c );
						break;

					case '<-|':
						$condition = sprintf( '%s < :%s', $field, $wkey );
						$whereConditions[ $index ] = sprintf( $sqt, $condition );
						$whereConditionVars[ $index ][ $wkey ] = get_array_key( 'filter', $c );
						break;

					case '<=':
						$condition = sprintf( '%s <= :%s', $field, $wkey );
						$whereConditions[ $index ] = sprintf( $sqt, $condition );
						$whereConditionVars[ $index ][ $wkey ] = get_array_key( 'filter', $c );
						break;

					case '%_%':
						$condition = sprintf( '%s LIKE :%s', $field, $wkey );
						$whereConditions[ $index ] = sprintf( $sqt, $condition );
						$whereConditionVars[ $index ][ $wkey ] = sprintf( '%s%s%s', '%', get_array_key( 'filter', $c ), '%' );
						break;

					case '!%_%':
						$condition = sprintf( '%s NOT LIKE :%s', $field, $wkey );
						$whereConditions[ $index ] = sprintf( $sqt, $condition );
						$whereConditionVars[ $index ][ $wkey ] = sprintf( '%s%s%s', '%', get_array_key( 'filter', $c ), '%' );
						break;

					case '%_':
						$condition = sprintf( '%s LIKE :%s', $field, $wkey );
						$whereConditions[ $index ] = sprintf( $sqt, $condition );
						$whereConditionVars[ $index ][ $wkey ] = sprintf( '%s%s%s', '', get_array_key( 'filter', $c ), '%' );
						break;

					case '!%_':
						$condition = sprintf( '%s NOT LIKE :%s', $field, $wkey );
						$whereConditions[ $index ] = sprintf( $sqt, $condition );
						$whereConditionVars[ $index ][ $wkey ] = sprintf( '%s%s%s', '', get_array_key( 'filter', $c ), '%' );
						break;

					case '_%':
						$condition = sprintf( '%s LIKE :%s', $field, $wkey );
						$whereConditions[ $index ] = sprintf( $sqt, $condition );
						$whereConditionVars[ $index ][ $wkey ] = sprintf( '%s%s%s', '%', get_array_key( 'filter', $c ), '' );
						break;

					case '!_%':
						$condition = sprintf( '%s NOT LIKE :%s', $field, $wkey );
						$whereConditions[ $index ] = sprintf( $sqt, $condition );
						$whereConditionVars[ $index ][ $wkey ] = sprintf( '%s%s%s', '%', get_array_key( 'filter', $c ), '' );
						break;

					case '()':
						$list = array();
						if ( can_loop( get_array_key( 'filter', $c ) ) ) {
							foreach ( get_array_key( 'filter', $c ) as $value ) {
								array_push( $list, escape_sql_input( $value ) );
							}
						}
						$inList = implode( ',', $list );
						$condition = sprintf( '%s IN ( %s )', $field, $inList );
						$whereConditions[ $index ] = sprintf( $sqt, $condition );
						break;

					case '!()':
						$list = array();
						if ( can_loop( get_array_key( 'filter', $c ) ) ) {
							foreach ( get_array_key( 'filter', $c ) as $value ) {
								array_push( $list, escape_sql_input( $value ) );
							}
						}
						$inList = implode( ',', $list );
						$condition = sprintf( '%s NOT IN ( %s )', $field, $inList );
						$whereConditions[ $index ] = sprintf( $sqt, $condition );
						break;

					case '|-|':
						$firstKey = sprintf( 'first%s', $wkey );
						$lastKey = sprintf( 'last%s', $wkey );
						$condition = sprintf( '%s BETWEEN :%s AND :%s', $field, $firstKey, $lastKey );
						$whereConditions[ $index ] = sprintf( $sqt, $condition );
						$whereConditionVars[ $index ][ $firstKey ] = get_array_key( 'start', get_array_key( 'filter', $c, array() ) );
						$whereConditionVars[ $index ][ $lastKey ] = get_array_key( 'end', get_array_key( 'filter', $c, array() ) );
						break;

					case '!|-|':
						$firstKey = sprintf( 'first%s', $wkey );
						$lastKey = sprintf( 'last%s', $wkey );
						$condition = sprintf( '%s NOT BETWEEN :%s AND :%s', $field, $firstKey, $lastKey );
						$whereConditions[ $index ] = sprintf( $sqt, $condition );
						$whereConditionVars[ $index ][ $firstKey ] = get_array_key( 'start', get_array_key( 'filter', $c, array() ) );
						$whereConditionVars[ $index ][ $lastKey ] = get_array_key( 'end', get_array_key( 'filter', $c, array() ) );
						break;


					default:
						$condition = sprintf( '%s = :%s', $field, $wkey );
						$whereConditions[ $index ] = sprintf( $sqt, $condition );
						$whereConditionVars[ $index ][ $wkey ] = get_array_key( 'filter', $c );
						break;
				}
			}
		}
		$whereString = $grouping;
		$whereVars = array();
		if ( can_loop( $whereConditions ) ) {
			foreach ( $whereConditions as $index => $condition ) {
				$whereString = str_replace( $index, sprintf( ' %s ', $condition ), $whereString );
				if ( can_loop( get_array_key( $index, $whereConditionVars ) ) ) {
					foreach ( get_array_key( $index, $whereConditionVars ) as $wk => $wv ) {
						$whereVars[ $wk ] = $wv;
					}
				}
			}
		}
		$idQuery = $whereString;
		$idQuery = preg_replace( '/(AND|OR)\s*[0-9]+/', '', $idQuery );
		$idQuery = sprintf( '( %s )', $idQuery );
		$idQuery = str_replace( '1', 'TRUE', $idQuery );
		$return = array(
			'query' => $idQuery,
			'vars' => $whereVars,
		);
		if ( false !== strpos( $idQuery, 'TRUE' ) ) {
			try {
				$list = $parent->{ $listName };
			}
			catch( Exception $e ) {
				if ( true == DEBUG ) {
					cli_echo( $e->getMessage() );
					cli_echo( 'Function tc_get_shared_list_object_matching_conditions' );
				}
			}
		}
		else {
			try {
				$list = $parent->withCondition( $idQuery, $whereVars )->{ $listName };
			}
			catch ( Exception $e ) {
				if ( true == DEBUG ) {
					cli_echo( $e->getMessage() );
					cli_echo( 'Function tc_get_shared_list_object_matching_conditions' );
				}
			}
		}
		if ( isset( $list ) && can_loop( $list ) ) {
			$ret = array_shift( $list );
			return $ret;
		}
		return false;
	}

	function tc_print_export_item_contents( $exjobid ) {
		try {
			$ej = R::load( 'exportjobs', absint( $exjobid ) );
		}
		catch( Exception $e ) {
			html_failure( 'error', 'Invalid Export Job', array(
				( true == DEBUG ) ? $e->getMessage() : 'Invalid Export Job ID',
			) );
		}
		$fn = str_replace( FILE_EXPORT_PATH, '', $ej->file );
		$contents = file_get_contents( $ej->file );
		header( 'Content-Type: text/csv;' );
		header( sprintf( 'Content-Disposition: attachment; filename="%s"', sprintf( '%s - Export ID %d - %s.csv', $ej->request_time, $ej->id, md5( trim( $ej->description ) ) ) ) );
		$lines = @explode( "\n", $contents );
		if ( can_loop( $lines ) ) {
			$fp = fopen( 'php://output', 'w' );
			foreach ( $lines as $line ) {
				fwrite( $fp, $line . "\n" );
			}
			fclose( $fp );
		}
	}

	function tc_get_meta_keys() {
		$mks = array();
		try {
			$mksr = R::getCol( 'SELECT DISTINCT( leadmeta.key ) FROM leadmeta ORDER BY leadmeta.key ASC' );
			if ( can_loop( $mksr ) ) {
				foreach ( $mksr as $mk ) {
					$mks[ $mk ] = $mk;
				}
			}
		}
		catch ( Exception $e ) {
			if ( true == DEBUG ) {
				ajax_failure( $e->getMessage() );
			}
		}
		return $mks;
	}