<?php
	defined( 'ABSPATH' ) || die( 'Sorry, but you cannot access this page directly.' );

	function tc_get_list_of_additional_lead_graphs() {
		$queries = tc_get_user_graph_queries();
		$index = 1;
		$return = array();
		if ( can_loop( $queries ) ) {
			foreach ( $queries as $oid => $bean ) {
				$return[ $index ] = $bean;
				$index ++;
			}
		}
		return $return;
	}

	function tc_get_additional_lead_graph_series_info() {
		$return = array();
		$graphs = tc_get_list_of_additional_lead_graphs();
		if ( can_loop( $graphs ) ) {
			foreach ( $graphs as $index => $bean ) {
				$p = array(
					'type' => 'areaspline',
					'name' => strip_tags( $bean->name ),
					'data' => array(),
					'beanid' => absint( $bean->id ),
				);
				array_push( $return, $p );
			}
		}
		return $return;
	}

	function tc_websocket_push_export_queries() {
		$start = time();
		$send = array();
		$queries = array();
		try {
			$queries = R::findCollection( 'savedfilterqueries', 'show_graph = 1' );
		}
		catch ( Exception $e ) {}
		if ( is_a( $queries, 'RedBeanPHP\BeanCollection' ) ) {
			while( $q = $queries->next() ) {
				$conditions = @unserialize( $q->conditions );
				$grouping = $q->grouping;
				$query = tc_generate_export_query( true, $conditions, $grouping );
				try {
					$res = R::getCell( get_array_key( 'query', $query ), get_array_key( 'vars', $query, array() ) );
				}
				catch( Exception $e ) {
					cli_echo( $e->getMessage() );
					$res = 0;
				}
				$point = array(
					( time() * 1000 ),
					absint( $res ),
				);
				$send[ sprintf( 'bean_%d', $q->id ) ] = $point;
				cli_echo( $res );
			}
		}
		$end = time();
		$time = $end - $start;
		cli_echo( sprintf( 'Queries took %s seconds', $time ) );
		streamer_emit( 'export-queries', $send );
		cli_echo( $send );
	}

    function tc_get_results_from_saved_queries() {
    	cli_echo( 'Fetching Saved Queries' );
        $return = array();
        $queries = array();
        try {
            $queries = R::findCollection( 'savedfilterqueries', 'show_graph = 1' );
        }
        catch ( Exception $e ) {
        }
        if ( is_a( $queries, 'RedBeanPHP\BeanCollection' ) ) {
            while ( $q = $queries->next() ) {
            	cli_echo( sprintf( 'Working on Query #%d', $q->id ) );
                $conditions = @unserialize( $q->conditions );
                $grouping = $q->grouping;
                $query = tc_generate_export_query_with_group_by( true, $conditions, $grouping );

                $recs = array();

                try {
                    $recs = R::getAll( get_array_key( 'query', $query ) , get_array_key( 'vars', $query, array() ) );
                }
                catch ( Exception $e ){
                    cli_echo( $e->getMessage() );
                }

                $item = array();

                if ( can_loop( $recs ) ) {
                    foreach ( $recs as $r ) {
                        $rec = array(
                            'code' => strtoupper( get_array_key( 'code', $r, 'XX' ) ),
                            'value' => absint( get_array_key( 'value', $r, 0 ) ),
                        );
                        array_push( $item, $rec );
                    }
                }

                $sub_return = array(
                    'query_id' => $q->id,
                    'query_name' => $q->name,
                    'query_series' => $item,
                );

                array_push( $return, $sub_return );
            }
        }
        return $return;
    }