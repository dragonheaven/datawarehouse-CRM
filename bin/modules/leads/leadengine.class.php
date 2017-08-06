<?php
	defined( 'ABSPATH' ) || die( 'Sorry, but you cannot access this page directly.' );

	/**
	 * Class LeadEngine is the class which merges and "heals" data
	 */

	class LeadEngine {
		private $bean;
		private $enrichedRow = array();

		function __construct( $enrichedRow = array() ) {
			if ( ! can_loop( $enrichedRow ) ) {
				throw new Exception( 'Enriched Row must be an array', true );
			}
			$this->enrichedRow = $enrichedRow;
		}

		private function load_bean( $id = 0, $debug = false ) {
			if ( 0 == absint( $id ) ) {
				try {
					$return = R::dispense( 'lead' );
					$return->importFromRow( $this->enrichedRow );
				}
				catch ( Exception $e ) {
					throw new Exception( sprintf( 'RedBean Exception: %s', $e->getMessage() ), true );
				}
			}
			else {
				try {
					$return = R::load( 'lead', $id );
					$return->importFromRow( $this->enrichedRow );
				}
				catch ( Exception $e ) {
					throw new Exception( sprintf( 'RedBean Exception: %s', $e->getMessage() ), true );
				}
			}
			return $return;
		}

		function get_lead_entity( $save = false, $debug = false ) {
			try {
				$matches = $this->get_possible_matches();
				if ( ! can_loop( $matches ) ) {
					$this->bean = $this->load_bean( 0, $debug );
				}
				if ( 1 == count( $matches ) ) {
					$this->bean = $this->load_bean( $matches[0], $debug );
					if ( true == $debug ) {
						ajax_debug( 'Got Here!' );
					}
				}
				if ( count( $matches ) > 1 ) {
					$ms = array();
					foreach ( $matches as $match ) {
						$c = $this->load_bean( $match,$debug );
						$ms[ $match ] = $c;
					}
					krsort( $ms );
					$msk = array_keys( $ms );
					$matchid = $msk[0];
					$matrix = $this->create_similarity_matrix( $ms );
					$matchid = $this->get_likliest_match( $ms );
					$this->bean = $this->load_bean( $matchid, $debug );
					foreach ( $matrix as $mid => $sims ) {
						if ( $mid !== $matchid ) {
							$s = absint( get_array_key( $mid, get_array_key( $matchid, $matrix, array() ) ), 0 );
							//$s = $matrix[ $matchid ][ $mid ];
							if ( $s > 25 ) {
								$t = $this->load_bean( $s, $debug );
								try {
									$this->bean = $this->update_missing_parameters( $this->bean, $t );
								}
								catch ( Exception $e ) {}
								try {
									R::trash( $t );
								}
								catch ( Exception $e ) {}
							}
						}
					}
				}
				if ( true == $debug ) {
					ajax_debug( 'Got Here!' );
					ajax_success( print_r( $this->bean, true ) );
				}
				$this->bean = $this->merge_enriched_data( $this->bean->id );
				if ( true == $debug ) {
					ajax_success( print_r( $this->bean, true ) );
				}
				/** check associated beans that they are beans */
				$beanTypes = array(
					'phone',
					'email',
					'skype',
					'ip',
					'source',
					'tag',
					'leadmeta',
					'language',
				);
				foreach ( $beanTypes as $type ) {
					try {
						if ( ! is_a( $this->bean->{$type}, 'RedBeanPHP\OODBBean' ) ) {
							$this->bean->{$type} = null;
						}
					}
					catch ( Exception $e ) {
						cli_echo( sprintf( 'Could not fix single value: %s', $e->getMessage() ) );
					}
					try {
						$sharedList = sprintf( 'shared%sList', ucwords( $type ) );
						$table_names = array( 'lead', $type );
						sort( $table_names );
						$table_name = implode( '_', $table_names );
						if ( table_exists( $table_name ) ) {
							if ( can_loop( $this->bean->{$sharedList} ) ) {
								foreach ( $this->bean->{$sharedList} as $bean_id => $bean ) {
									if ( ! is_a( $bean, 'RedBeanPHP\OODBBean' ) ) {
										unset( $this->bean->{$sharedList}[ $bean_id ] );
									}
								}
							}
							else {
								unset( $this->bean->{$sharedList} );
							}
						}
					}
					catch ( Exception $e ) {
						cli_echo( sprintf( 'Could not fix list: %s', $e->getMessage() ) );
					}
				}
				if ( ! can_loop( $this->bean->sharedPhoneList ) && ! can_loop( $this->bean->sharedEmailList ) ) {
					$this->bean = null;
				}
			}
			catch ( Exception $e ) {
				if ( true == $debug ) {
					ajax_failure( sprintf( 'Could not generate lead: %s', $e->getMessage() ) );
				}
				cli_echo( sprintf( 'Error is Here: %s', $e->getMessage() ) );
			}
			if ( is_a( $this->bean, 'RedBeanPHP\OODBBean' ) ) {
				if ( can_loop( $this->bean->sharedIpList ) ) {
					$beanIds = array_keys( $this->bean->sharedIpList );
					$beanId = array_shift( $beanIds );
					$ip = $this->bean->sharedIpList[ $beanId ];
					if ( is_empty( $this->bean->country ) ) {
						$this->bean->country = strtoupper( $ip->country );
					}
					if ( $this->bean->country == strtoupper( $ip->country ) ) {
						if ( is_empty( $this->bean->region ) ) {
							$this->bean->region = $ip->region;
						}
						if ( is_empty( $this->bean->city ) ) {
							$this->bean->city = $ip->city;
						}
						if ( is_empty( $this->bean->postalcode ) ) {
							$this->bean->postalcode = strtoupper( $ip->postal );
						}
					}
				}
				if ( is_empty( $this->bean->regtimestamp ) ) {
					$this->bean->regtimestamp = '1970-01-01 00:00:00';
				}
				if ( is_empty( $this->bean->createtimestamp ) ) {
					$this->bean->createtimestamp = '1970-01-01 00:00:00';
				}
				if ( is_empty( $this->bean->updatetimestamp ) ) {
					$this->bean->updatetimestamp = '1970-01-01 00:00:00';
				}
			}
			if ( true == $save ) {
				try {
					R::store( $this->bean );
				}
				catch ( Exception $e ) {}
			}
			return $this->bean;
		}

		/**
		 * The goal of this function is to update / merge information for a lead
		 * @param  RedBeanPHP\OODBBean $final Lead Bean origin
		 * @param  RedBeanPHP\OODBBean $merge Lead Bean to merge
		 * @return RedBeanPHP\OODBBean		  Lead Bean final
		 */
		private function update_missing_parameters( RedBeanPHP\OODBBean $final, RedBeanPHP\OODBBean $merge ) {
			global $tc_fields;
			if ( can_loop( $tc_fields ) ) {
				foreach ( $tc_fields as $f => $i ) {
					if ( true == get_array_key( 'canset', $i, false ) ) {
						if ( is_empty( $final->{ $f } ) ) {
							$final->{ $f } = $merge->{ $f };
						}
					}
				}
			}

			return $final;
		}

		function get_possible_matches() {
			$return = array();
			$emailMatches = $this->get_possible_matches_by_email();
			if ( can_loop( $emailMatches ) ) {
				foreach ( $emailMatches as $m ) {
					$return = static::push_to_array( $return, absint( $m ) );
				}
			}
			$phoneMatches = $this->get_possible_matches_by_phone();
			if ( can_loop( $phoneMatches ) ) {
				foreach ( $phoneMatches as $m ) {
					$return = static::push_to_array( $return, absint( $m ) );
				}
			}
			return $return;
		}

		private function count_times_value_appears_for_key( $beanList, $key = null, $value = null ) {
			$count = 0;
			if ( can_loop( $beanList ) ) {
				foreach ( $beanList as $id => $bean ) {
					$val = get_array_key( $key, $bean );
					if ( $val == $value ) {
						$count ++;
					}
				}
			}
			return $count;
		}

		private function create_similarity_matrix( $beans ) {
			$matrix = array();
			$count = 0;
			krsort( $beans );
			if ( can_loop( $beans ) ) {
				if ( $count <= 5 ) {
					foreach ( $beans as $id => $b ) {
						if ( ! array_key_exists( $id, $matrix ) ) {
							$matrix[ $id ][ $id ] = 0;
						}
						foreach ( $matrix as $lid => $chuff ) {
							$sim = rand( 1, 100 );
							$matrix[ $id ][ $lid ] = $sim;
						}
					}
				}
				$count ++;
			}
			if ( can_loop( $matrix ) ) {
				foreach ( $matrix as $i => $d ) {
					arsort( $d, SORT_NUMERIC );
					$matrix[ $i ] = $d;
				}
			}
			return $matrix;
		}

		private function get_likliest_match( $beans ) {
			$matrix = $this->create_similarity_matrix( $beans );
			$averages = array();
			foreach ( $matrix as $id => $sims ) {
				$averages[ $id ] = array_average( $sims );
			}
			arsort( $averages );
			$matches = array_keys( $averages );
			if ( can_loop( $matches ) ) {
				return $matches[0];
			}
			if ( can_loop( $beans ) ) {
				$keys = array_keys( $beans );
				return $keys[0];
			}
			return 0;
		}

		private function merge_enriched_data( $bean = 0 ) {
			global $_tc_countries;
			$bean = $this->load_bean( absint( $bean ) );
			$ips = $this->get_all_lead_ips( $bean );
			$phones = $this->get_all_lead_phones( $bean );
			$phoneCountries = $this->get_phone_country_list( $bean, true );
			$ipCountries = $this->get_ip_country_list( $bean );
			$ipCities = $this->get_ip_city_list( $bean );
			$ipRegions = $this->get_ip_region_list( $bean );
			$ipPostals = $this->get_ip_postal_list( $bean );
			$countryPhoneCountryMatch = $this->lead_country_matches_phone_country( $bean );
			$countryIpCountryMatch = $this->lead_country_matches_ip_country( $bean );
			$countryPhoneIpMatch = $this->lead_ip_country_matches_phone_country( $bean );
			$rowCountries = ( is_array( get_array_key( 'country', $this->enrichedRow, array() ) ) ? get_array_key( 'country', $this->enrichedRow, array() ) : array( get_array_key( 'country', $this->enrichedRow, 'XX' ) ) );
			$bco = $bean->country;
			/**
			 * Merging & Updating Conditions for Countries
			 */
			$bestMatchCountries = $this->get_countries_by_total_matches( array(), $rowCountries, $phoneCountries, $ipCountries );
			$bmc = array_shift( $bestMatchCountries );
			if ( $bmc !== $bco ) {
				$bean->country = $bmc;
				$bean->city = null;
				$bean->postalcode = null;
				$bean->region = null;
				if ( can_loop( $phones ) ) {
					foreach ( $phones as $phone ) {
						$valid = get_array_key( 'valid', $phone );
						$prefix = get_array_key( 'country_prefix', $phone );
						$bmcPrefix = sprintf( '+%d', get_array_key( 'prefix', get_array_key( $bmc, $_tc_countries, array() ) ) );
						if (
							get_array_key( 'country_code', $phone ) !== $bmc
							&& ( false == $valid || $bmcPrefix == $prefix )
						) {
							$this->update_phone_with_country( $phone, $bmc );
						}
					}
				}
			}
			if ( can_loop( $ips ) ) {
				foreach ( $ips as $ip ) {
					if ( $bean->country == get_array_key( 'country', $ip ) ) {
						if ( is_empty( $bean->city ) && ! is_empty( get_array_key( 'city', $ip ) ) ) {
							$bean->city = get_array_key( 'city', $ip );
						}
						if ( is_empty( $bean->postalcode ) && ! is_empty( get_array_key( 'postal', $ip ) ) ) {
							$bean->postalcode = get_array_key( 'postal', $ip );
						}
						if ( is_empty( $bean->region ) && ! is_empty( get_array_key( 'region', $ip ) ) ) {
							$bean->region = get_array_key( 'region', $ip );
						}
					}
				}
			}
			/**
			 * End Merging & Updating Conditions for Countries
			 */
			return $bean;
		}

		private function update_phone_with_country( array $phone, $country = 'XX' ) {
			try {
				$p = R::load( 'phone', get_array_key( 'id', $phone, 0 ) );
				$p->country_code = $country;
				R::store( $p );
				return true;
			}
			catch ( Exception $e ) {}
			return false;
		}

		/**
		 * Sort through all of the matched countries and figure out which one has the most matches
		 * @param  array $lc Lead Countries
		 * @param  array $rc Row Countries
		 * @param  array $pc Phone Countries
		 * @param  array $ic IP Countries
		 * @return array     Return Countries returned in descending order by the amount of matches
		 */
		private function get_countries_by_total_matches( array $lc, array $rc, array $pc, array $ic ) {
			$super = array();
			if ( can_loop( $lc ) ) {
				foreach ( $lc as $c ) {
					$c = strtoupper( $c );
					if ( ! is_empty( $c ) ) {
						if ( ! array_key_exists( $c, $super ) ) {
							$super[ $c ] = 0;
						}
						$super[ $c ] ++;
					}

				}
			}
			if ( can_loop( $rc ) ) {
				foreach ( $rc as $c ) {
					$c = strtoupper( $c );
					if ( ! is_empty( $c ) ) {
						if ( ! array_key_exists( $c, $super ) ) {
							$super[ $c ] = 0;
						}
						$super[ $c ] ++;
					}

				}
			}
			if ( can_loop( $pc ) ) {
				foreach ( $pc as $c ) {
					$c = strtoupper( $c );
					if ( ! is_empty( $c ) ) {
						if ( ! array_key_exists( $c, $super ) ) {
							$super[ $c ] = 0;
						}
						$super[ $c ] = $super[ $c ] + 1.02;
					}

				}
			}
			if ( can_loop( $ic ) ) {
				foreach ( $ic as $c ) {
					$c = strtoupper( $c );
					if ( ! is_empty( $c ) ) {
						if ( ! array_key_exists( $c, $super ) ) {
							$super[ $c ] = 0;
						}
						$super[ $c ] = $super[ $c ] + 1.01;
					}

				}
			}
			arsort( $super, SORT_NUMERIC );
			return array_keys( $super );
		}

		private function lead_country_matches_phone_country( RedBeanPHP\OODBBean $lead ) {
			$phones = $this->get_all_lead_phones( $lead );
			if ( can_loop( $phones ) ) {
				foreach ( $phones as $phone ) {
					if ( $lead->country == get_array_key( 'country_code', $phone ) ) {
						return true;
					}
				}
			}
			return false;
		}

		private function lead_country_matches_ip_country( RedBeanPHP\OODBBean $lead ) {
			$ips = $this->get_all_lead_ips( $lead );
			if ( can_loop( $ips ) ) {
				foreach ( $ips as $ip ) {
					if ( $lead->country == get_array_key( 'country', $ip ) ) {
						return true;
					}
				}
			}
			return false;
		}

		private function lead_ip_country_matches_phone_country( RedBeanPHP\OODBBean $lead ) {
			$phoneCountries = $this->get_phone_country_list( $lead );
			$ipCountries = $this->get_ip_country_list( $lead );
			foreach ( $ipCountries as $iso ) {
				if ( in_array( $iso, $phoneCountries ) ) {
					return true;
				}
			}
			return false;
		}

		private function get_all_lead_phones( RedBeanPHP\OODBBean $lead, $validOnly = false, $countries = false ) {
			if ( ! table_exists( 'phone' ) ) {
				return array();
			}
			global $_tc_cached_lead_lists;
			if ( ! is_array( $_tc_cached_lead_lists ) ) {
				$_tc_cached_lead_lists = array();
			}
			$cachekey = sprintf( 'lead_%d_phones', $lead->id );
			if ( array_key_exists( $cachekey, $_tc_cached_lead_lists ) ) {
				return get_array_key( $cachekey, $_tc_cached_lead_lists, array() );
			}
			$phones = get_array_key( 'phone', $this->enrichedRow, array() );
			// replace next line with more efficient code
			$phonesFromDB = $this->get_unique_key_values_from_table( 'phone', 'number_numbers_only', $lead->id );
			// end replacement
			$phones = array_merge( $phones, $phonesFromDB );
			if ( can_loop( $phones ) ) {
				foreach ( $phones as $index => $data ) {
					if ( ! is_array( $data ) ) {
						// we need to fix this!
						if ( is_a( $data, 'RedBeanPHP\OODBBean' ) ) {
							$phones[ $index ] = $data->export();
						}
						else {
							$b = R::findOne( 'phone', 'number_numbers_only = :val', array( ':val' => $data ) );
							if ( is_a( $b, 'RedBeanPHP\OODBBean' ) ) {
								$phones[ $index ] = $b->export();
							}
						}
					}
				}
			}
			$uniqePhones = array();
			foreach ( $phones as $phone ) {
				if ( array_key_exists( get_array_key( 'number_numbers_only', $phone ), $uniqePhones ) && ( ( true == $validOnly &&  true == get_array_key( 'valid', $phone, false ) ) || false == $validOnly ) ) {
					if ( get_array_key( 'id', $uniqePhones[ get_array_key( 'number_numbers_only', $phone ) ] ) > get_array_key( 'id', $phone ) ) {
						$uniqePhones[ get_array_key( 'number_numbers_only', $phone ) ] = $phone;
					}
				}
				else {
					$uniqePhones[ get_array_key( 'number_numbers_only', $phone ) ] = $phone;
				}
			}
			$phones = array_values( $uniqePhones );
			$_tc_cached_lead_lists[ $cachekey ] = $phones;
			return $phones;
		}

		private function get_all_lead_skypes( RedBeanPHP\OODBBean $lead ) {
			if ( ! table_exists( 'skype' ) ) {
				return array();
			}
			global $_tc_cached_lead_lists;
			if ( ! is_array( $_tc_cached_lead_lists ) ) {
				$_tc_cached_lead_lists = array();
			}
			$cachekey = sprintf( 'lead_%d_skypes', $lead->id );
			if ( array_key_exists( $cachekey, $_tc_cached_lead_lists ) ) {
				return get_array_key( $cachekey, $_tc_cached_lead_lists, array() );
			}
			$skypes = get_array_key( 'skype', $this->enrichedRow, array() );
			// replace next line with more efficient code
			$skypesFromDB = $this->get_unique_key_values_from_table( 'skype', 'skype', $lead->id );
			// end replacement
			$skypes = array_merge( $skypes, $skypesFromDB );
			if ( can_loop( $skypes ) ) {
				foreach ( $skypes as $index => $data ) {
					if ( ! is_array( $data ) ) {
						// we need to fix this!
						if ( is_a( $data, 'RedBeanPHP\OODBBean' ) ) {
							$skypes[ $index ] = $data->export();
						}
						else {
							$b = R::findOne( 'skype', 'skype = :val', array( ':val' => $data ) );
							if ( is_a( $b, 'RedBeanPHP\OODBBean' ) ) {
								$skypes[ $index ] = $b->export();
							}
						}
					}
				}
			}
			$uniqueSkypes = array();
			foreach ( $skypes as $skype ) {
				if ( array_key_exists( get_array_key( 'skype', $skype ), $uniqueSkypes ) ) {
					if ( get_array_key( 'id', $uniqueSkypes[ get_array_key( 'skype', $skype ) ] ) > get_array_key( 'id', $skype ) ) {
						$uniqueSkypes[ get_array_key( 'skype', $skype ) ] = $skype;
					}
				}
				else {
					$uniqueSkypes[ get_array_key( 'skype', $skype ) ] = $skype;
				}
			}
			$skypes = array_values( $uniqueSkypes );
			$_tc_cached_lead_lists[ $cachekey ] = $skypes;
			return $skypes;
		}

		private function get_all_lead_ips( RedBeanPHP\OODBBean $lead ) {
			if ( ! table_exists( 'ip' ) ) {
				return array();
			}
			global $_tc_cached_lead_lists;
			if ( ! is_array( $_tc_cached_lead_lists ) ) {
				$_tc_cached_lead_lists = array();
			}
			$cachekey = sprintf( 'lead_%d_ips', $lead->id );
			if ( array_key_exists( $cachekey, $_tc_cached_lead_lists ) ) {
				return get_array_key( $cachekey, $_tc_cached_lead_lists, array() );
			}
			$ips = get_array_key( 'ip', $this->enrichedRow, array() );
			// replace next line with more efficient code
			$ipsFromDB = $this->get_unique_key_values_from_table( 'ip', 'ip', $lead->id );
			// end replacement
			$ips = array_merge( $ips, $ipsFromDB );
			if ( can_loop( $ips ) ) {
				foreach ( $ips as $index => $data ) {
					if ( ! is_array( $data ) ) {
						// we need to fix this!
						if ( is_a( $data, 'RedBeanPHP\OODBBean' ) ) {
							$ips[ $index ] = $data->export();
						}
						else {
							$b = R::findOne( 'ip', 'ip = :val', array( ':val' => $data ) );
							if ( is_a( $b, 'RedBeanPHP\OODBBean' ) ) {
								$ips[ $index ] = $b->export();
							}
						}
					}
				}
			}
			$uniqeIPs = array();
			foreach ( $ips as $ip ) {
				if ( array_key_exists( get_array_key( 'ip', $ip ), $uniqeIPs ) ) {
					if ( get_array_key( 'id', $uniqeIPs[ get_array_key( 'ip', $ip ) ] ) > get_array_key( 'id', $ip ) ) {
						$uniqeIPs[ get_array_key( 'ip', $ip ) ] = $ip;
					}
				}
				else {
					$uniqeIPs[ get_array_key( 'ip', $ip ) ] = $ip;
				}
			}
			$ips = array_values( $uniqeIPs );
			$_tc_cached_lead_lists[ $cachekey ] = $ips;
			return $ips;
		}

		private function get_phone_country_list( RedBeanPHP\OODBBean $lead, $validOnly = false ) {
			$phones = $this->get_all_lead_phones( $lead, $validOnly, true );
			$phoneCountries = array();
			foreach ( $phones as $phone ) {
				$country = get_array_key( 'country_code', $phone );
				if ( ! is_empty( $country ) && ! array_key_exists( $country, $phoneCountries ) ) {
					$phoneCountries[ $country ] = 0;
				}
				if ( ! is_empty( $country ) ) {
					$phoneCountries[ $country ] ++;
				}
			}
			arsort( $phoneCountries, SORT_NUMERIC );
			$phoneCountries = array_keys( $phoneCountries );
			return $phoneCountries;
		}

		private function get_ip_country_list( RedBeanPHP\OODBBean $lead ) {
			$ips = $this->get_all_lead_ips( $lead );
			$ipCountries = array();
			foreach ( $ips as $ip ) {
				if ( ! is_empty( get_array_key( 'country', $ip ) ) && ! array_key_exists( get_array_key( 'country', $ip ), $ipCountries ) ) {
					$ipCountries[ get_array_key( 'country', $ip ) ] = 0;
				}
				if ( ! is_empty( get_array_key( 'country', $ip ) ) ) {
					$ipCountries[ get_array_key( 'country', $ip ) ] ++;
				}
			}
			arsort( $ipCountries, SORT_NUMERIC );
			$ipCountries = array_keys( $ipCountries );
			return $ipCountries;
		}

		private function get_ip_city_list( RedBeanPHP\OODBBean $lead ) {
			$ips = $this->get_all_lead_ips( $lead );
			$ipCountries = array();
			foreach ( $ips as $ip ) {
				if ( ! is_empty( get_array_key( 'city', $ip ) ) && ! array_key_exists( get_array_key( 'city', $ip ), $ipCountries ) ) {
					$ipCountries[ get_array_key( 'city', $ip ) ] = 0;
				}
				if ( ! is_empty( get_array_key( 'city', $ip ) ) ) {
					$ipCountries[ get_array_key( 'city', $ip ) ] ++;
				}
			}
			arsort( $ipCountries, SORT_NUMERIC );
			$ipCountries = array_keys( $ipCountries );
			return $ipCountries;
		}

		private function get_ip_region_list( RedBeanPHP\OODBBean $lead ) {
			$ips = $this->get_all_lead_ips( $lead );
			$ipCountries = array();
			foreach ( $ips as $ip ) {
				if ( ! is_empty( get_array_key( 'region', $ip ) ) && ! array_key_exists( get_array_key( 'region', $ip ), $ipCountries ) ) {
					$ipCountries[ get_array_key( 'region', $ip ) ] = 0;
				}
				if ( ! is_empty( get_array_key( 'region', $ip ) ) ) {
					$ipCountries[ get_array_key( 'region', $ip ) ] ++;
				}
			}
			arsort( $ipCountries, SORT_NUMERIC );
			$ipCountries = array_keys( $ipCountries );
			return $ipCountries;
		}

		private function get_ip_postal_list( RedBeanPHP\OODBBean $lead ) {
			$ips = $this->get_all_lead_ips( $lead );
			$ipCountries = array();
			foreach ( $ips as $ip ) {
				if ( ! is_empty( get_array_key( 'postal', $ip ) ) && ! array_key_exists( get_array_key( 'postal', $ip ), $ipCountries ) ) {
					$ipCountries[ get_array_key( 'postal', $ip ) ] = 0;
				}
				if ( ! is_empty( get_array_key( 'postal', $ip ) ) ) {
					$ipCountries[ get_array_key( 'postal', $ip ) ] ++;
				}
			}
			arsort( $ipCountries, SORT_NUMERIC );
			$ipCountries = array_keys( $ipCountries );
			return $ipCountries;
		}

		private function get_possible_matches_by_email() {
			if ( ! table_exists( 'email' ) ) {
				return array();
			}
			$toCheck = array();
			$objs = get_array_key( 'email', $this->enrichedRow, array() );
			if ( ! static::is_deep_value( $objs ) ) {
				if (
					true == get_array_key( 'valid_format', get_array_key( 'email', $this->enrichedRow, array() ) )
				) {
					$val = get_array_key( 'email', get_array_key( 'email', $this->enrichedRow, array() ) );
					$toCheck = static::push_to_array( $toCheck, $val );
				}
			}
			else {
				if ( can_loop( $objs ) ) {
					foreach ( $objs as $index => $obj ) {
						if (
							true == get_array_key( 'valid_format', $obj )
						) {
							$toCheck = static::push_to_array( $toCheck, get_array_key( 'email', $obj, '' ) );
						}
					}
				}
			}
			$query = sprintf( 'SELECT email_lead.lead_id FROM email_lead LEFT JOIN email ON email_lead.email_id = email.id WHERE email IN %s GROUP BY email_lead.lead_id', static::make_in_list( $toCheck ) );
			if ( ! can_loop( $toCheck ) ) {
				return array( 0 );
			}
			try {
				$ids = R::getCol( $query );
			}
			catch ( Exception $e ) {
				trigger_error( sprintf( 'Database Error: %s', $e->getMessage() ) );
				$ids = array();
			}
			return $ids;
		}

		private function get_possible_matches_by_phone() {
			if ( ! table_exists( 'phone' ) ) {
				return array();
			}
			$toCheck = array();
			$objs = get_array_key( 'phone', $this->enrichedRow, array() );
			if ( ! static::is_deep_value( $objs ) ) {
				$val = get_array_key( 'number_numbers_only', get_array_key( 'phone', $this->enrichedRow, array() ) );
					$toCheck = static::push_to_array( $toCheck, $val );
			}
			else {
				if ( can_loop( $objs ) ) {
					foreach ( $objs as $index => $obj ) {
						$toCheck = static::push_to_array( $toCheck, get_array_key( 'number_numbers_only', $obj, '' ) );
					}
				}
			}
			if ( ! can_loop( $toCheck ) ) {
				return array( 0 );
			}
			$query = sprintf( 'SELECT lead_phone.lead_id FROM lead_phone LEFT JOIN phone ON lead_phone.phone_id = phone.id WHERE phone.number_numbers_only IN %s GROUP BY lead_phone.lead_id', static::make_in_list( $toCheck ) );
			try {
				$ids = R::getCol( $query );
			}
			catch ( Exception $e ) {
				trigger_error( sprintf( 'Database Error: %s', $e->getMessage() ) );
				$ids = array();
			}
			return $ids;
		}

		private static function push_to_array( array $array, $value = null ) {
			if ( ! is_empty( $value ) && ! in_array( $value, $array ) ) {
				array_push( $array, $value );
			}
			return $array;
		}

		private static function is_deep_value( $value ) {
			$return = false;
			if ( can_loop( $value ) ) {
				foreach ( $value as $index => $info ) {
					if ( can_loop( $info ) ) {
						$return = true;
					}
				}
			}
			return $return;
		}

		private static function make_in_list( $vals ) {
			$in = array();
			if ( can_loop( $vals ) ) {
				foreach ( $vals as $val ) {
					array_push( $in, sprintf( "'%s'", $val ) );
				}
			}
			return sprintf( '( %s )', implode( ',', $in ) );
		}

		private function get_unique_key_values_from_table( $table, $key, $leadId = 0 ) {
			$return = array();
			if ( ! is_empty( $table ) && ! is_empty( $key ) && table_exists( $table ) ) {
				$tkv = array( $table, 'lead' );
				sort( $tkv );
				$tk = implode( '_', $tkv );
				$idcol = sprintf( '%s_id', $table );
				$query = sprintf(
					'SELECT %s.%s FROM %s LEFT JOIN %s ON %s.id = %s.%s WHERE %s.lead_id = %d',
					$table,
					$key,
					$table,
					$tk,
					$table,
					$tk,
					$idcol,
					$tk,
					$leadId
				);
				try {
					$res = R::getCol( $query );
				}
				catch ( Exception $e ) {
					cli_echo( sprintf( 'Could not get unique values due to dabase error: %s', $e->getMessage() ) );
					$res = array();
				}
				if ( can_loop( $res ) ) {
					foreach ( $res as $item ) {
						if ( ! in_array( $item, $return ) ) {
							array_push( $return, $item );
						}
					}
				}
			}
			return $return;
		}

		static function process( $enrichedRow, $save = false, $debug = false ) {
			$c = get_called_class();
			$obj = new $c( $enrichedRow );
			return $obj->get_lead_entity( $save, $debug );
		}
	}