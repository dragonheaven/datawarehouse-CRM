<?php
	defined( 'ABSPATH' ) || die( 'Sorry, but you cannot access this page directly.' );

	class Model_lead extends RedBean_SimpleModel {
		public function dispense() {
			global $tc_fields;
			if ( can_loop( $tc_fields ) ) {
				foreach ( $tc_fields as $key => $data ) {
					if (
						'id' !== $key
						&& 'meta' !== $key
						&& 's' !== substr( $key, -1 )
					) {
						$this->setPropertyValue( $key, get_array_key( 'default', $data ), true );
					}
				}
			}
			$this->setPropertyValue( 'createtimestamp', date( 'Y-m-d H:i:s' ), true );
		}

		public function importFromRow( $row ) {
			/** This is the likliest spot for the merge bug since we're over-writing information */
			global $tc_fields;
			$meta = array();
			$phones = array();
			$emails = array();
			$addresses = array();
			$sources = array();
			$languages = array();
			$tags = array();
			$ips = array();
			$browsers = array();
			$clicks = array();
			$signups = array();
			$conversions = array();
			$deposits = array();
			$notes = array();
			$messages = array();
			$exports = array();
			$skypes = array();
			if ( can_loop( $row ) ) {
				foreach ( $row as $key => $value ) {
					$mulipleKey = sprintf( '%ss', $key );
					$modelKey = sprintf( 'Model_%s', $key );
					if ( array_key_exists( $key, $tc_fields ) && true == get_array_key( 'canset', $tc_fields[ $key ], false ) ) {
						if ( ! is_array( $value ) ) {
							$this->setPropertyValue( $key, $value );
						}
						else if ( can_loop( $value ) && ! is_associative_array( $value ) ) {
							$nv = array();
							if ( class_exists( $modelKey, false ) ) {
								foreach ( $value as $array ) {
									if ( can_loop( $array ) ) {
										$bean = R::dispense( $key );
										$bean->import( $array );
										array_push( $nv, $bean );
									}
								}
								$value = $nv;
							}
							$value = $value[0];
						}
						else if ( can_loop( $value ) && is_associative_array( $value ) ) {
							if ( class_exists( $modelKey, false ) ) {
								$bean = R::dispense( $key );
								$bean->import( $value );
								$value = $bean;
							}
							$this->setPropertyValue( $key, $value );
						}
						if ( is_empty( $this->bean->{ $key } ) || is_empty( $this->{ $key } ) ) {
							if ( isset( ${ $mulipleKey } ) && can_loop( ${ $mulipleKey } ) ) {
								$this->setPropertyValue( $key, ${ $mulipleKey }[0] );
							}
						}
					}
					else if ( 'new' !== $key ) {
						if ( can_loop( $value ) ) {
							foreach ( $value as $val ) {
								array_push( $meta, array(
									'key' => $key,
									'value' => $val,
								) );
							}
						}
						else {
							array_push( $meta, array(
								'key' => $key,
								'value' => $value,
							) );
						}
					}
				}
			}
			if ( can_loop( $meta ) ) {
				foreach ( $meta as $m ) {
					$this->addMetaValue( get_array_key( 'key', $m ), get_array_key( 'value', $m ) );
				}
			}
			if ( can_loop( $phones ) ) {
				foreach ( $phones as $obj ) {
					$this->addToPropertySharedList( 'phone', $obj );
				}
			}
			if ( can_loop( $emails ) ) {
				foreach ( $emails as $obj ) {
					$this->addToPropertySharedList( 'email', $obj );
				}
			}
			if ( can_loop( $skypes ) ) {
				foreach ( $skypes as $obj ) {
					$this->addToPropertySharedList( 'skype', $obj );
				}
			}
			if ( can_loop( $addresses ) ) {
				foreach ( $addresses as $obj ) {
					$this->addToPropertySharedList( 'addresse', $obj );
				}
			}
			if ( can_loop( $sources ) ) {
				foreach ( $sources as $obj ) {
					$this->addToPropertySharedList( 'source', $obj );
				}
			}
			if ( can_loop( $tags ) ) {
				foreach ( $tags as $obj ) {
					$this->addToPropertySharedList( 'tag', $obj );
				}
			}
			if ( can_loop( $languages ) ) {
				foreach ( $languages as $obj ) {
					$this->addToPropertySharedList( 'language', $obj );
				}
			}
			if ( can_loop( $ips ) ) {
				foreach ( $ips as $obj ) {
					$this->addToPropertySharedList( 'ip', $obj );
				}
			}
			if ( can_loop( $browsers ) ) {
				foreach ( $browsers as $obj ) {
					$this->addToPropertySharedList( 'browser', $obj );
				}
			}
			if ( can_loop( $clicks ) ) {
				foreach ( $clicks as $obj ) {
					$this->addToPropertySharedList( 'click', $obj );
				}
			}
			if ( can_loop( $signups ) ) {
				foreach ( $signups as $obj ) {
					$this->addToPropertySharedList( 'signup', $obj );
				}
			}
			if ( can_loop( $conversions ) ) {
				foreach ( $conversions as $obj ) {
					$this->addToPropertySharedList( 'conversion', $obj );
				}
			}
			if ( can_loop( $deposits ) ) {
				foreach ( $deposits as $obj ) {
					$this->addToPropertySharedList( 'deposit', $obj );
				}
			}
			if ( can_loop( $notes ) ) {
				foreach ( $notes as $obj ) {
					$this->addToPropertySharedList( 'note', $obj );
				}
			}
			if ( can_loop( $messages ) ) {
				foreach ( $messages as $obj ) {
					$this->addToPropertySharedList( 'message', $obj );
				}
			}
			if ( can_loop( $exports ) ) {
				foreach ( $exports as $obj ) {
					$this->addToPropertySharedList( 'export', $obj );
				}
			}
			//ajax_success( print_r( $this->bean, true ) );
			//ajax_debug( $this->bean );
		}

		private function setPropertyValue( $prop, $value = null, $force = false ) {
			if (
				(
					! in_array( $prop, array( 'tag', 'meta', 'leadmeta', 'langauge' ) )
					&& ! is_a( $this->{ $prop }, 'RedBeanPHP\OODBBean' )
					&& ! is_a( $this->bean->{ $prop }, 'RedBeanPHP\OODBBean' )
					&& ! is_array( $this->{ $prop } )
					&& ! is_array( $this->bean->{ $prop } )
					&& is_empty( $this->{ $prop } )
					&& is_empty( $this->bean->{ $prop } )
				)
				|| (
					true == $force
				)
			) {
				try {
					$this->{ $prop } = ( is_a( $value, 'RedBeanPHP\OODBBean' ) ) ? $value : utf8_encode( $value );
					$this->bean->{ $prop } = ( is_a( $value, 'RedBeanPHP\OODBBean' ) ) ? $value : utf8_encode( $value );
				}
				catch ( Exception $e ) {
					if ( true == DEBUG ) {
						cli_echo( sprintf( 'Cannot Set Property "%s" to "%s"', $prop, serialize( $value ) ) );
						cli_echo( $e->getMessage() );
						if ( ! is_cli() ) {
							trigger_error( sprintf( 'Cannot Set Property "%s" to "%s"', $prop, serialize( $value ) ) );
							trigger_error( $e->getMessage() );
						}
					}
				}
			}
		}

		private function addToPropertySharedList( $prop, $value = null ) {
			$key = sprintf( 'shared%sList', ucfirst( $prop ) );
			if ( is_a( $value, 'RedBeanPHP\OODBBean' ) ) {
				// we should get to see that there are no other records with the same "primary key" first
				$pi = $value->getTableIndex();
				if ( ! is_empty( $pi ) ) {
					$val = $value->{$pi};
					if ( ! can_loop( $this->bean->{$key} ) ) {
						$this->bean->{ $key }[] = $value;
					}
					else {
						$has = false;
						foreach ( $this->bean->{$key} as $cbid => $cb ) {
							if ( $cb->{$pi} == $val ) {
								$has = true;
								break;
							}
						}
						if ( false == $has ) {
							$this->bean->{ $key }[] = $value;
						}
					}
				}
				else {
					// If there is no primary key, just make sure the item isn't already in the list
					$id = $value->id;
					if ( ! array_key_exists( $id, $this->bean->{ $key } ) ) {
						$this->bean->{ $key }[] = $value;
					}
				}
			}
			else {
				$this->bean->{ $key }[] = $value;
			}
		}

		private function addMetaValue( $key, $value ) {
			if ( is_empty( $key ) ) {
				return;
			}
			try {
				$m = R::dispense( 'leadmeta' );
				$m->key = $key;
				$m->value = $value;
				R::store( $m );
				$this->bean->sharedLeadmetaList[] = $m;
			}
			catch ( Exception $e ) {

			}
		}

		public function meta() {
			return $this->bean->xownMetaList;
		}

		public function phones() {
			return $this->bean->sharedPhoneList;
		}

		public function emails() {
			return $this->bean->sharedEmailList;
		}

		public function addresses() {
			return $this->bean->sharedAddressList;
		}

		public function sources() {
			return $this->bean->sharedSourceList;
		}

		public function ips() {
			return $this->bean->sharedIpList;
		}

		public function browsers() {
			return $this->bean->sharedBrowserList;
		}

		public function clicks() {
			return $this->bean->sharedClickList;
		}

		public function signups() {
			return $this->bean->sharedSignupList;
		}

		public function conversions() {
			return $this->bean->sharedConversionList;
		}

		public function deposits() {
			return $this->bean->sharedDepositList;
		}

		public function notes() {
			return $this->bean->sharedNoteList;
		}

		public function messages() {
			return $this->bean->sharedMessageList;
		}

		public function exports() {
			return $this->bean->sharedExportList;
		}
	}
