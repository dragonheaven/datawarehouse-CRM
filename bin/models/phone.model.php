<?php
	defined( 'ABSPATH' ) || die( 'Sorry, but you cannot access this page directly.' );

	class Model_phone extends RedBean_SimpleModel {
		public static function get_unique_index_key() {
			return 'number_numbers_only';
		}

		public $valid = false;
		public $number = '';
		public $number_numbers_only = '';
		public $local_format = '';
		public $international_format = '';
		public $E164 = '';
		public $country_prefix = '';
		public $country_code = '';
		public $country_name = '';
		public $location = '';
		public $carrier = '';
		public $line_type = '';

		public function open() {
			if ( $this->number !== str_repeat( 'l', 254 ) ) {
				$pobj = new PhoneObj( $this->bean->number, $this->bean->country_code );
				foreach ( $this as $property => $chuff ) {
					if ( ! in_array( $property, array( 'bean' ) ) ) {
						$this->{ $property } = $pobj->{ $property };
						$this->bean->{ $property } = $pobj->{ $property };
					}
				}
				//$t = get_twilio_class();
				//if ( is_a( $t, 'Twilio' ) ) {
				//	$l = $t->lookup_phone( $this->number_numbers_only );
				//	if ( property_exists_deep( $l, 'carrier->name' ) ) {
				//		$this->setPropertyValue( 'carrier', $l->carrier->name );
				//	}
				//	if ( property_exists_deep( $l, 'carrier->type' ) ) {
				//		$this->setPropertyValue( 'line_type', $l->carrier->type );
				//	}
				//}
			}
		}

		public function after_update() {
			if ( $this->number !== str_repeat( 'l', 254 ) ) {
				$pobj = new PhoneObj( $this->bean->number, $this->bean->country_code );
				foreach ( $this as $property => $chuff ) {
					if ( ! in_array( $property, array( 'bean' ) ) ) {
						$this->{ $property } = $pobj->{ $property };
						$this->bean->{ $property } = $pobj->{ $property };
					}
				}
				//$t = get_twilio_class();
				//if ( is_a( $t, 'Twilio' ) ) {
				//	$l = $t->lookup_phone( $this->number_numbers_only );
				//	if ( property_exists_deep( $l, 'carrier->name' ) ) {
				//		$this->setPropertyValue( 'carrier', $l->carrier->name );
				//	}
				//	if ( property_exists_deep( $l, 'carrier->type' ) ) {
				//		$this->setPropertyValue( 'line_type', $l->carrier->type );
				//	}
				//}
			}
		}

		public function get_phone_obj() {
			return new PhoneObj( $this->bean->number, $this->bean->country_code );
		}

		private function setPropertyValue( $prop, $value = null ) {
			$this->{ $prop } = ( is_a( $value, 'RedBeanPHP\OODBBean' ) ) ? $value : utf8_encode( $value );
			$this->bean->{ $prop } = ( is_a( $value, 'RedBeanPHP\OODBBean' ) ) ? $value : utf8_encode( $value );
		}

		public function getAssociatedLeads() {
			$return = array();
			try {
				$return = $this->bean->sharedLeadList;
			}
			catch( Exception $e ) {}
			return $return;
		}

		public static function getFilterableAttributes() {
			$return = array(
				'number_numbers_only' => array(
					'description' => 'Phone Number',
					'filtertype' => 'phone',
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
				'valid' => array(
					'description' => 'Phone Number is Valid',
					'filtertype' => 'bool',
					'filteroptions' => array( true, false ),
					'filterconditions' => array(
						'=' => 'Is',
					),
				),
				'country_code' => array(
					'description' => 'Phone Number Country',
					'filtertype' => 'text',
					'filteroptions' => 'tc_get_filter_country_field',
					'filterconditions' => array(
						'=' => 'Is',
						'()' => 'In List',
						'!()' => 'Not In List',
						'!NULL!' => 'Is Blank',
						'!NOTNULL!' => 'Is Not Blank',
					),
				),
				'carrier' => array(
					'description' => 'Phone Number Carrier',
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
				'line_type' => array(
					'description' => 'Phone Line Type',
					'filtertype' => 'text',
					'filteroptions' => array(
						'landline' => 'Landline',
						'mobile' => 'Mobile',
						'landline_or_mobile' => 'Undetermined Landline or Mobile',
						'toll_free' => 'Toll Free',
						'premium_rate' => 'Premium Rate',
						'shared_cost' => 'Shared Cost',
						'voip' => 'VoIP',
						'personal_number' => 'Personal',
						'pager' => 'Page',
						'uan' => 'UAN',
						'unknown' => 'Unknown',
						'emergency' => 'Emergecy',
						'voicemail' => 'Voicemail',
						'short_code' => 'Short Code',
						'standard_rate' => 'Standard Rate',
					),
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
			);
			return $return;
		}

		public function getTableIndex() {
			return 'number_numbers_only';
		}
	}