<?php
	defined( 'ABSPATH' ) || die( 'Sorry, but you cannot access this page directly.' );

	class Model_email extends RedBean_SimpleModel {
		public $email_raw = null;
		public $email = null;
		public $valid_format = null;
		public $valid_domain = null;
		public $valid_inbox = null;
		public $suppressed = false;

		public static function get_unique_index_key() {
			return 'email	';
		}

		public function dispense() {
			$this->bean->email_raw = null;
			$this->bean->email = null;
			$this->bean->valid_format = false;
			$this->bean->valid_domain = false;
			$this->bean->valid_inbox = false;
			$this->bean->suppressed = false;
		}

		public function open() {
			if ( is_null( $this->valid_inbox ) || is_null( $this->bean->valid_inbox ) ) {
				$this->getMetaData();
			}
		}

		public function getMetaData() {
			$email = $this->sanitize_email( $this->bean->email_raw );
			$this->setPropertyValue( 'email', $email );
			if ( ! is_empty( $email ) ) {
				$this->setPropertyValue( 'valid_format', true );
				$this->setPropertyValue( 'valid_domain', $this->checkEmailDomain( $email ) );
				//if ( true == $this->valid_domain ) {
				//	$mbl = get_mailboxlayer();
				//	$external = $mbl->validate( $this->bean->email );
				//	if ( property_exists( $external, 'smtp_check' ) ) {
				//		$this->setPropertyValue( 'valid_inbox', ( true == get_object_property( 'smtp_check', $external, false ) ) );
				//		if ( true == get_object_property( 'free', $external, false ) ) {
				//			$this->setPropertyValue( 'valid_format', true );
				//			$this->setPropertyValue( 'valid_domain', false );
				//			$this->setPropertyValue( 'valid_inbox', false );
				//			$this->setPropertyValue( 'suppressed', true );
				//		}
				//		$this->setPropertyValue( 'trust_score', floatval( get_object_property( 'score', $external, 1 ) ) );
				//		if ( floatval( get_object_property( 'score', $external ) ) < floatval( 1 / 3 ) ) {
				//			$this->setPropertyValue( 'suppressed', true );
				//		}
				//	}
				//}
			}
			else {
				$this->setPropertyValue( 'suppressed', true );
			}
		}

		private function sanitize_email( $input = null ) {
			$input = trim( $input );
			if ( ! is_string( $input ) ) {
				return null;
			}
			$input = strtolower( $input );
			$input = filter_var( $input, FILTER_SANITIZE_EMAIL );
			if ( 1 !== substr_count( $input, '@' ) || 0 === substr_count( $input, '.' ) ) {
				$input = null;
			}
			return $input;
		}

		private function setPropertyValue( $prop, $value = null ) {
			$this->{ $prop } = ( is_a( $value, 'RedBeanPHP\OODBBean' ) ) ? $value : utf8_encode( $value );
			$this->bean->{ $prop } = ( is_a( $value, 'RedBeanPHP\OODBBean' ) ) ? $value : utf8_encode( $value );
		}

		private function checkEmailDomain( $email ) {
			$email = $this->sanitize_email( $email );
			if ( ! is_empty( $email ) ) {
				if ( function_exists( 'dns_supports_email' ) ) {
					return dns_supports_email( $email );
				}
				list( $box, $domain ) = explode( '@', $email );
				$mx_records = $this->getCachedMXRecordsForDomain( $domain );
				return ( can_loop( $mx_records ) );
			}
			return false;
		}

		private function getCachedMXRecordsForDomain( $domain ) {
			$return = null;
			$cachekey = md5( sprintf( 'dns_mx_for_%s', $domain ) );
			$cached = cache_get( $cachekey, null );
			if ( is_empty( $cached ) ) {
				$res = @dns_get_record( $domain, DNS_MX );
				if ( can_loop( $res ) ) {
					cache_set( $cachekey, $cached );
					$return = $res;
				}
			}
			else {
				$return = $cached;
			}
			return $return;
		}

		public function getAssociatedLeads() {
			$return = array();
			try {
				$return = $this->bean->sharedLeadList;
			}
			catch( Exception $e ) {}
			return $return;
		}

		public function getTableIndex() {
			return 'email';
		}

		public static function getFilterableAttributes() {
			$return = array(
				'email' => array(
					'description' => 'Email Address',
					'filtertype' => 'text',
					'filteroptions' => array(),
					'filterconditions' => array(
						'=' => 'Equals',
						'<>' => 'Different Than',
						'%_%' => 'Contains Text',
						'%_' => 'Begins With',
						'_%' => 'Ends With',
						'!%_%' => 'Does not Contain',
						'!%_' => 'Does not Begin With',
						'!_%' => 'Does not End With',
						'()' => 'In List',
						'!()' => 'Not In List',
						'!NULL!' => 'Is Blank',
						'!NOTNULL!' => 'Is Not Blank',
					),
				),
				'valid_format' => array(
					'description' => 'Email is Valid Format',
					'filtertype' => 'bool',
					'filteroptions' => array( true, false ),
					'filterconditions' => array(
						'=' => 'Is',
					),
				),
				'valid_domain' => array(
					'description' => 'Email is Valid Domain',
					'filtertype' => 'bool',
					'filteroptions' => array( true, false ),
					'filterconditions' => array(
						'=' => 'Is',
					),
				),
				'valid_inbox' => array(
					'description' => 'Email is Valid Inbox',
					'filtertype' => 'bool',
					'filteroptions' => array( true, false ),
					'filterconditions' => array(
						'=' => 'Is',
					),
				),
				'suppressed' => array(
					'description' => 'Email is Suppressed',
					'filtertype' => 'bool',
					'filteroptions' => array( true, false ),
					'filterconditions' => array(
						'=' => 'Is',
					),
				),
				'score' => array(
					'description' => 'Email Quality',
					'filtertype' => 'interger',
					'filteroptions' => array(),
					'filterconditions' => array(
						'=' => 'Equals',
						'<>' => 'Different Than',
						'>' => 'Greater Than',
						'>=' => 'Greater Than or Equal To',
						'<' => 'Less Than',
						'<=' => 'Less Than or Equal To',
						'%_%' => 'Contains Text',
						'%_' => 'Begins With',
						'_%' => 'Ends With',
						'!%_%' => 'Does not Contain',
						'!%_' => 'Does not Begin With',
						'!_%' => 'Does not End With',
						'()' => 'In List',
						'!()' => 'Not In List',
						'|-|' => 'Between',
						'!|-|' => 'Not Between',
						'!NULL!' => 'Is Blank',
						'!NOTNULL!' => 'Is Not Blank',
					),
				),
			);
			return $return;
		}
	}