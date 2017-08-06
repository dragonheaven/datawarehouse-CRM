<?php
	defined( 'ABSPATH' ) || die( 'Sorry, but you cannot access this page directly.' );

	class Model_ip extends RedBean_SimpleModel {
		public $ip = null;

		public static function get_unique_index_key() {
			return 'ip';
		}

		public function dispense() {
			$this->bean->ip = null;
		}

		public function open() {
			if ( ! is_empty( $this->bean->ip ) ) {
				$this->getMetaData();
			}
		}

		public function getMetaData() {
			if ( ! is_empty( $this->bean->ip ) && is_empty( $this->continent ) ) {
				$local_info = get_geoip_info( $this->bean->ip );
				if ( can_loop( $local_info ) ) {
					$this->setPropertyValue( 'continent', get_array_key( 'continent', $local_info ) );
					$iso = COUNTRY_PARSER::GET_COUNTRY( get_array_key( 'country', $local_info ) );
					$this->setPropertyValue( 'country', $iso );
					$this->setPropertyValue( 'region', get_array_key( 'region', $local_info ) );
					$this->setPropertyValue( 'city', get_array_key( 'city', $local_info ) );
					$this->setPropertyValue( 'postal', get_array_key( 'postal', $local_info ) );
					$this->setPropertyValue( 'latitude', get_array_key( 'latitude', $local_info ) );
					$this->setPropertyValue( 'longitude', get_array_key( 'longitude', $local_info ) );
					$this->setPropertyValue( 'longitude', get_array_key( 'longitude', $local_info ) );
				}
				else {
					$info = MaxMind::get( $this->bean->ip, 'insights' );
					$this->setPropertyValue( 'continent', get_object_property( 'code', get_object_property( 'continent', $info ) ) );
					$iso = get_object_property( 'iso_code', get_object_property( 'country', $info ) );
					$iso = COUNTRY_PARSER::GET_COUNTRY( $iso );
					$this->setPropertyValue( 'country', ( 'XX' == $iso ) ? null : $iso );
					$this->setPropertyValue( 'city', get_object_property( 'en', get_object_property( 'names', get_object_property( 'city', $info ) ) ) );
					$this->setPropertyValue( 'timeZone', get_object_property( 'time_zone', get_object_property( 'location', $info ) ) );
					$this->setPropertyValue( 'isp', get_object_property( 'isp', get_object_property( 'traits', $info ) ) );
					$this->setPropertyValue( 'organization', get_object_property( 'organization', get_object_property( 'traits', $info ) ) );
					$this->setPropertyValue( 'domain', get_object_property( 'domain', get_object_property( 'traits', $info ) ) );
				}
			}
		}

		private function setPropertyValue( $prop, $value = null ) {
			$this->{ $prop } = ( is_a( $value, 'RedBeanPHP\OODBBean' ) ) ? $value : utf8_encode( $value );
			$this->bean->{ $prop } = ( is_a( $value, 'RedBeanPHP\OODBBean' ) ) ? $value : utf8_encode( $value );
		}

		public function getAssociatedLeads() {
			$return = array();
			return $return;
		}

		public static function getFilterableAttributes() {
			$return = array(
				'ip' => array(
					'description' => 'IP Address',
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
				'continent' => array(
					'description' => 'IP Continent',
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
				'country' => array(
					'description' => 'IP Country',
					'filtertype' => 'text',
					'filteroptions' => 'tc_get_filter_country_field',
					'filterconditions' => array(
						'=' => 'Is',
						'()' => 'In List',
						'!()' => 'Not In List',
					),
				),
				'city' => array(
					'description' => 'IP City',
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
				'timeZone' => array(
					'description' => 'IP TimeZone',
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
				'isp' => array(
					'description' => 'IP ISP',
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
				'organization' => array(
					'description' => 'IP Organization',
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
				'domain' => array(
					'description' => 'IP Domain',
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
			);
			return $return;
		}

		public function getTableIndex() {
			return 'ip';
		}
	}