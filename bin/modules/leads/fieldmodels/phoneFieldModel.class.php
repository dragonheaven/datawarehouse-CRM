<?php
	defined( 'ABSPATH' ) || die( 'Sorry, but you cannot access this page directly.' );

	if ( ! class_exists( 'fieldModelAbstract', false ) ) {
		require_once sprintf( '%s/bin/modules/leads/fieldmodels/abstract.interface.php', ABSPATH );
	}
	class phoneFieldModel extends fieldModelAbstract {
		private $type = 'phone';
		private $attr = 'number_numbers_only';
		private $filter = 'tc_filter_phone';
		function convert( $lead = null ) {
			$raw = $this->raw;
			$return = array();
			if ( can_loop( $raw ) ) {
				foreach ( $raw as $rr ) {
					array_push( $return, $this->convert_single( $rr, $this->row ) );
				}
			}
			else {
				array_push( $return, $this->convert_single( $raw, $this->row ) );
			}
			if ( can_loop( $return ) ) {
				$ret = $return;
				$return = array();
				foreach ( $ret as $index => $value ) {
					if ( ! is_empty( $value ) ) {
						array_push( $return, $value );
					}
				}
			}
			return $return;
		}

		function convert_single( $raw, $row, $lead = null ) {
			$raw = call_user_func( $this->filter, $raw );
			if ( is_empty( $raw ) ) {
				return null;
			}
			$combos = array();
			if ( can_loop( get_array_key( 'country', $row, array() ) ) ) {
				foreach ( get_array_key( 'country', $row ) as $country ) {
					$country = tc_filter_country_field( $country );
					$combos[ $country ] = $raw;
				}
			}
			else {
				$combos[ tc_filter_country_field( get_array_key( 'country', $row, 'XX' ) ) ] = $raw;
			}
			if ( ! can_loop( $combos ) ) {
				$combos[''] = $raw;
			}
			$reses = array();
			foreach ( $combos as $iso => $number ) {
				$r = new PhoneObj( $number, $iso );
				if ( true == $r->valid ) {
					$reses[ $iso ] = $r->number_numbers_only;
				}
			}
			$aks = array_keys( $reses );
			$iso = array_shift( $aks );
			/**
			 * New Code as of 1.1.3
			 * Because of Indexes, let's try to add first, then look after
			 */
			try {
				$obj = R::load( $this->type, absint( 0 ) );
				$obj->{$this->attr} = $raw;
				$obj->country_code = $iso;
				$cid = R::store( $obj );
				return $obj;
			}
			catch ( Exception $e ) {
				if ( is_empty( $iso ) ) {
					try {
						$cid = absint( R::getCell( sprintf( 'SELECT %s.id FROM %s WHERE %s = :val', $this->type, $this->type, $this->attr ), array( ':val' => $raw ) ) );
					}
					catch( Exception $e ) {
						$cid = 0;
					}
				}
				else {
					$res = $reses[ $iso ];
					try {
						$cid = absint( R::getCell( sprintf( 'SELECT %s.id FROM %s WHERE %s = :val AND country_code = :iso', $this->type, $this->type, $this->attr ), array( ':val' => $res, ':iso' => $iso ) ) );
					}
					catch( Exception $e ) {
						$cid = 0;
					}
				}
			}
			/**
			 * End of New Code
			 */
			$cachekey = md5( sprintf( '%s_bean_for_%s', $this->type, ( is_empty( $iso ) ? $raw : $reses[ $iso ] ) ) );
			$cid = cache_get( $cachekey, $cid );
			if ( false === USE_CACHE_BYPASS ) {
				if ( 0 == absint( $cid ) ) {
					if ( is_empty( $iso ) ) {
						try {
							$cid = absint( R::getCell( sprintf( 'SELECT %s.id FROM %s WHERE %s = :val', $this->type, $this->type, $this->attr ), array( ':val' => $raw ) ) );
						}
						catch( Exception $e ) {
							$cid = 0;
						}
					}
					else {
						$res = $reses[ $iso ];
						try {
							$cid = absint( R::getCell( sprintf( 'SELECT %s.id FROM %s WHERE %s = :val AND country_code = :iso', $this->type, $this->type, $this->attr ), array( ':val' => $res, ':iso' => $iso ) ) );
						}
						catch( Exception $e ) {
							$cid = 0;
						}
					}
				}
			}
			try {
				$obj = R::load( $this->type, absint( $cid ) );
			}
			catch ( Exception $e ) {
				$obj = null;
			}
			if ( 0 == absint( $obj->id ) ) {
				$obj->number = $raw;
				$obj->country_code = $iso;
				$cid = R::store( $obj );
				cache_set( $cachekey, $cid );
			}
			else {
				if ( is_empty( $obj->number_numbers_only ) ) {
					$pn = new PhoneObj( $raw );
					if ( true == $pn->valid ) {
						$obj->number = $raw;
						$obj->country_code = $pn->country_code;
						$obj->number_numbers_only = $pn->number_numbers_only;
						try {
							R::store( $obj );
						}
						catch ( Exception $e ){
							$rebound = true;
							trigger_error( sprintf( 'Could not update phone due to DB error: %s', $e->getMessage() ) );
							cli_echo( 'Attempting Rebound' );
						}
					}
					if ( isset( $rebound ) && true == $rebound ) {
						try {
							$obj = R::findOne( 'phone', 'number_numbers_only = :v', array( 'v' => $pn->number_numbers_only ) );
							cli_echo( 'Phone Rebound Successful' );
						}
						catch ( Exception $e ) {
							trigger_error( sprintf( 'Rebound due to DB error: %s', $e->getMessage() ) );
						}
					}
				}
			}
			if ( is_empty( $obj->number_numbers_only ) ) {
				$obj = null;
			}
			return $obj;
		}
	}