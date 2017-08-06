<?php
	/**
	 * PhoneObj Class
	 * @Author: Jak Giveon ( jak@tacticlicks.com )
	 * @Required: giggsey/libphonenumber-for-php (https://github.com/giggsey/libphonenumber-for-php)
	 * Replaces all functionality of https://numverify.com/ API ( Except location property )
	 * Note: because this is a free solution, the information is only as accurate as the version of libphonenumber-for-php that you have installed.
	 *
	 * Public Properties:
	 * - valid						// Whether the phone number is valid for the country
	 * - number						// The raw input
	 * - number_numbers_only		// The phone number formatted as E164 but without the +
	 * - local_format				// The phone number formatted for a local user
	 * - international_format		// The phone number formatted for international users
	 * - E164						// The phone number formatted in E164
	 * - country_prefix				// The dialing prefix of the country
	 * - country_code				// The ISO2 identifier of the phone number country
	 * - country_name				// The name of the country
	 * - location					// Deprecated. Filler for property from https://numverify.com/ API.
	 * - carrier					// Name of the phone carrier ( if available )
	 * - line_type					// Type of phone line ( if available )
	  								// Possible values: landline, mobile, landline_or_mobile, toll_free, premium_rate, shared_cost, voip, personal_number, pager, uan, unknown, emergency, voicemail, short_code, standard_rate

	 * Public Methods:
	 * __construct					// Class Constructor
	 * asJSON						// Returns a JSON formatted string the same as the return from https://numverify.com/ API
	 * asObject						// Returns a stdClass object with only the public properties
	 * asArray						// Returns an array with only the public properties as keys
	 * formatted					// Returns the phone number formatted as one of the following: default, original, local, international, e164

	 * Public Static Methods:
	 * GetPhoneObj					// Get the PhoneObj object statically
	 * GetObj 						// Get a stdClass object with only the public properties statically
	 * GetArray 					// Get an array with only public properties as keys
	 * GetJSON 						// Get a JSON formatted string the same as the return from https://numverify.com/ API statically
	 * PhoneIsValid 				// A boolean indicating if the phone number is valid
	 * GetPhoneFormatted 			// Returns the phone number formatted as one of the following: default, original, local, international, e164 statically
	 * GetCountryList 				// Returns a stdClass object with the same formatting as https://numverify.com/ API's country endpoint
	 */
	class PhoneObj {
		private $phoneutil;
		private $phoneproto;
		private $phonecarriermapper;
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

		/**
		 * Class Constructor
		 * @param string $number       The input phone number
		 * @param string $country_code The ISO2 string identifying the country
		 */
		function __construct( $number, $country_code = null ) {
			$this->number = self::sanitize_phone( $number );
			if (
				self::is_empty( $country_code )
				|| strlen( trim( $country_code ) ) !== 2
			) {
				$country_code = self::guess_country_code_by_phone( $this->number );
			}
			$this->country_code = strtoupper( trim( $country_code ) );
			$c = self::get_array_key( $this->country_code, self::get_countries(), array(
				'iso' => 'XX',
				'name' => 'Unknown',
				'iso3' => 'XXX',
				'prefix' => '',
				'defaultLang' => 'en',
				'nationality' => 'Unknown',
			) );
			$this->country_prefix = sprintf( '+%s', self::get_array_key( 'prefix', $c ) );
			$this->country_name = self::get_array_key( 'name', $c );
			$this->_make_number_proto();
			if (
				is_a( $this->phoneutil, 'libphonenumber\PhoneNumberUtil' )
				&& is_a( $this->phoneproto, 'libphonenumber\PhoneNumber' )
			) {
				$this->valid = $this->phoneutil->isValidNumber( $this->phoneproto );
				$this->line_type = strtolower( self::get_phone_number_type_name( intval( $this->phoneutil->getNumberType( $this->phoneproto ) ) ) );
				if ( class_exists( '\libphonenumber\PhoneNumberFormat' ) ) {
						$this->local_format = $this->phoneutil->format( $this->phoneproto, \libphonenumber\PhoneNumberFormat::NATIONAL );
						$this->international_format = $this->phoneutil->format( $this->phoneproto, \libphonenumber\PhoneNumberFormat::INTERNATIONAL );
						$this->E164 = $this->phoneutil->format( $this->phoneproto, \libphonenumber\PhoneNumberFormat::E164 );
						$this->number_numbers_only = substr( $this->E164, 1 );
					}
				}
				if ( is_a( $this->phonecarriermapper, 'libphonenumber\PhoneNumberToCarrierMapper' ) ) {
					$this->carrier = $this->phonecarriermapper->getNameForNumber( $this->phoneproto, 'en' );
				}
		}

		public function asJSON() {
			if ( function_exists( 'json_encode' ) ) {
				return json_encode( $this );
			}
			return false;
		}

		public function asObject() {
			$ret = new stdClass();
			$properties = $this->_get_public_properties();
			if ( can_loop( $properties ) ) {
				foreach ( $properties as $prop ) {
					$p = $prop->getName();
					$ret->{ $p } = $this->{ $p };
				}
			}
			return $ret;
		}

		public function asArray() {
			$ret = array();
			$properties = $this->_get_public_properties();
			if ( function_exists( 'json_encode' ) ) {
				return json_decode( json_encode( $this ), true );
			}
			if ( can_loop( $properties ) ) {
				foreach ( $properties as $prop ) {
					$p = $prop->getName();
					$ret[ $p ] = $this->{ $p };
				}
			}
			return $ret;
		}

		/**
		 * formatted
		 * @param  string $format ENUM( default, original, local, international, e164 )
		 * @return string         Phone Number in requested format
		 */
		public function formatted( $format = null ) {
			switch ( strtolower( $format ) ) {
				case 'original':
					return self::get_obj_property( 'number', $this, $this->number );
					break;

				case 'local':
					return self::get_obj_property( 'local_format', $this, self::get_obj_property( 'number', $this, $this->number ) );
					break;

				case 'international':
					return self::get_obj_property( 'international_format', $this, self::get_obj_property( 'number', $this, $this->number ) );
					break;

				case 'e164':
					return self::get_obj_property( 'E164', $this, self::get_obj_property( 'number', $this, $this->number ) );
					break;

				default:
					return self::get_obj_property( 'number_numbers_only', $this, self::get_obj_property( 'number', $this, $this->number ) );
					break;
			}
		}

		public static function GetPhoneObj( $number, $country_code = null ) {
			$c = get_called_class();
			$obj = new $c( $number, $country_code );
			return $obj;
		}

		public static function GetObj( $number, $country_code = null ) {
			$c = get_called_class();
			$obj = $c::GetPhoneObj( $number, $country_code );
			return $obj->asObject();
		}

		public static function GetArray( $number, $country_code = null ) {
			$c = get_called_class();
			$obj = $c::GetPhoneObj( $number, $country_code );
			return $obj->asArray();
		}

		public static function GetJSON( $number, $country_code = null ) {
			$c = get_called_class();
			$obj = $c::GetPhoneObj( $number, $country_code );
			return $obj->asJSON();
		}

		public static function PhoneIsValid( $number, $country_code = null ) {
			$c = get_called_class();
			$obj = $c::GetPhoneObj( $number, $country_code );
			return $obj->valid;
		}

		public static function GetPhoneFormatted( $number, $country_code = null, $format = null ) {
			$c = get_called_class();
			$obj = $c::GetPhoneObj( $number, $country_code );
			return $obj->formatted( $format );
		}

		public static function GetCountryList( $array = false ) {
			$c = get_called_class();
			$countries = $c::get_countries();
			if ( true == $array ) {
				$return = array();
			}
			else {
				$return = new stdClass();
			}
			if ( can_loop( $countries ) ) {
				foreach ( $countries as $iso => $info ) {
					if ( ! is_array( $return ) ) {
						$i = new stdClass();
						$i->country_name = $c::get_array_key( 'name', $info, 'Unknown' );
						$i->dialling_code = sprintf( '+%s', $c::get_array_key( 'prefix', $info, '' ) );
						$return->{ strtoupper( $iso ) } = $i;
					}
					else {
						$i = array(
							'country_name' => $c::get_array_key( 'name', $info, 'Unknown' ),
							'dialling_code' => sprintf( '+%s', $c::get_array_key( 'prefix', $info, '' ) ),
						);
						$return[ strtoupper( $iso ) ] = $i;
					}
				}
			}
			return $return;
		}

		public static function GetCountryListJSON() {
			$c = get_called_class();
			if ( function_exists( 'json_encode' ) ) {
				return json_encode( $c::GetCountryList() );
			}
		}

		private function _get_public_properties() {
			$ref = new ReflectionObject( $this );
			return $ref->getProperties( ReflectionProperty::IS_PUBLIC );
		}

		private function _make_number_proto() {
			if ( class_exists( 'libphonenumber\PhoneNumberUtil' ) ) {
				$this->phoneutil = \libphonenumber\PhoneNumberUtil::getInstance();
			}
			if ( is_a( $this->phoneutil, 'libphonenumber\PhoneNumberUtil' ) ) {
				try {
					$this->phoneproto = $this->phoneutil->parse( $this->number, $this->country_code );
					if ( class_exists( 'libphonenumber\PhoneNumberToCarrierMapper' ) ) {
						$this->phonecarriermapper = \libphonenumber\PhoneNumberToCarrierMapper::getInstance();
					}
				}
				catch ( Exception $e ) {}
			}
		}

		private static function get_phone_number_type_name( $val ) {
			$val = intval( $val );
			$types = array(
				0 => 'LANDLINE',
				1 => 'MOBILE',
				2 => 'LANDLINE_OR_MOBILE',
				3 => 'TOLL_FREE',
				4 => 'PREMIUM_RATE',
				5 => 'SHARED_COST',
				6 => 'VOIP',
				7 => 'PERSONAL_NUMBER',
				8 => 'PAGER',
				9 => 'UAN',
				10 => 'UNKNOWN',
				27 => 'EMERGENCY',
				28 => 'VOICEMAIL',
				29 => 'SHORT_CODE',
				30 => 'STANDARD_RATE',
			);
			return self::get_array_key( $val, $types, 'UNKNOWN' );
		}

		public static function guess_country_code_by_phone( $phone ) {
			$c = get_called_class();
			$phone = self::sanitize_phone( $phone );
			$countries = self::get_countries();
			$possible = array();
			if ( self::can_loop( $countries ) ) {
				foreach ( $countries as $iso2 => $info ) {
					$cp = self::get_array_key( 'prefix', $info );
					if ( ! self::is_empty( $cp ) ) {
						if ( $cp == substr( $phone, 0, strlen( $cp ) ) ) {
							array_push( $possible, $info );
						}
					}
				}
			}
			if ( self::can_loop( $possible ) ) {
				usort( $possible, array( $c, '_sort_by_prefix_length' ) );
				if ( 1 == count( $possible ) ) {
					return self::get_array_key( 'iso', $possible[0] );
				}
				else {
					foreach ( $possible as $i ) {
						$test = new $c( $phone, self::get_array_key( 'iso', $i ) );
						if ( true == $test->valid ) {
							return strtoupper( self::get_array_key( 'iso', $i ) );
						}
					}
					return strtoupper( self::get_array_key( 'iso', $possible[0] ) );
				}
			}
			return 'XX';
		}

		private static function _sort_by_prefix_length( $a, $b ) {
			$ap = self::get_array_key( 'prefix', $a );
			$bp = self::get_array_key( 'prefix', $b );
			if ( strlen( $ap ) == strlen( $bp ) ) {
				return 0;
			}
			return ( strlen( $ap ) > strlen( $bp ) ) ? -1 : 1;
		}

		private static function sanitize_phone( $phone ) {
			$phone = trim( $phone );
			$phone = preg_replace( '/[^0-9]/', '', $phone );
			return $phone;
		}

		private static function is_empty( $var ) {
			return ( is_null( $var ) || empty( $var ) || 0 == strlen( $var ) );
		}

		private static function can_loop( $data ) {
			return ( is_array( $data ) && count( $data ) > 0 );
		}

		private static function get_array_key( $key, $array = array(), $default = null ) {
			return ( is_array( $array ) && array_key_exists( $key, $array ) && ! empty( $array[ $key ] ) ) ? $array[ $key ] : $default;
		}

		private static function get_obj_property( $key, $obj, $default = null ) {
			return ( is_object( $obj ) && property_exists( $obj, $key ) && ! empty( $obj->{$key} ) ) ? $obj->{$key} : $default;
		}

		private static function get_countries() {
			return array(
				'AF' => array(
					'iso' => 'AF',
					'name' => 'Afghanistan',
					'iso3' => 'AFG',
					'prefix' => '93',
					'defaultLang' => 'ar',
					'nationality' => 'Afghan',
				),
				'AX' => array(
					'iso' => 'AX',
					'name' => 'Aland Islands',
					'iso3' => 'ALA',
					'prefix' => '358',
					'defaultLang' => 'en',
					'nationality' => 'Finnish',
				),
				'AL' => array(
					'iso' => 'AL',
					'name' => 'Albania',
					'iso3' => 'ALB',
					'prefix' => '355',
					'defaultLang' => 'en',
					'nationality' => 'Albanian',
				),
				'DZ' => array(
					'iso' => 'DZ',
					'name' => 'Algeria',
					'iso3' => 'DZA',
					'prefix' => '213',
					'defaultLang' => 'ar',
					'nationality' => 'Algerian',
				),
				'AS' => array(
					'iso' => 'AS',
					'name' => 'American Samoa',
					'iso3' => 'ASM',
					'prefix' => '1684',
					'defaultLang' => 'en',
					'nationality' => 'American Samoan',
				),
				'AD' => array(
					'iso' => 'AD',
					'name' => 'Andorra',
					'iso3' => 'AND',
					'prefix' => '376',
					'defaultLang' => 'en',
					'nationality' => 'Andorran',
				),
				'AO' => array(
					'iso' => 'AO',
					'name' => 'Angola',
					'iso3' => 'AGO',
					'prefix' => '244',
					'defaultLang' => 'pt-br',
					'nationality' => 'Angolan',
				),
				'AI' => array(
					'iso' => 'AI',
					'name' => 'Anguilla',
					'iso3' => 'AIA',
					'prefix' => '1264',
					'defaultLang' => 'en',
					'nationality' => 'Anguilla',
				),
				'AQ' => array(
					'iso' => 'AQ',
					'name' => 'Antarctica',
					'iso3' => '',
					'prefix' => '672',
					'defaultLang' => 'en',
					'nationality' => 'Antarctican',
				),
				'AG' => array(
					'iso' => 'AG',
					'name' => 'Antigua and Barbuda',
					'iso3' => 'ATG',
					'prefix' => '1268',
					'defaultLang' => 'en',
					'nationality' => 'Antiguan/Barbudan',
				),
				'AR' => array(
					'iso' => 'AR',
					'name' => 'Argentina',
					'iso3' => 'ARG',
					'prefix' => '54',
					'defaultLang' => 'es',
					'nationality' => 'Argentine',
				),
				'AM' => array(
					'iso' => 'AM',
					'name' => 'Armenia',
					'iso3' => 'ARM',
					'prefix' => '374',
					'defaultLang' => 'en',
					'nationality' => 'Armenian',
				),
				'AW' => array(
					'iso' => 'AW',
					'name' => 'Aruba',
					'iso3' => 'ABW',
					'prefix' => '297',
					'defaultLang' => 'en',
					'nationality' => 'Aruban',
				),
				'AU' => array(
					'iso' => 'AU',
					'name' => 'Australia',
					'iso3' => 'AUS',
					'prefix' => '61',
					'defaultLang' => 'en',
					'nationality' => 'Australian',
				),
				'AT' => array(
					'iso' => 'AT',
					'name' => 'Austria',
					'iso3' => 'AUT',
					'prefix' => '43',
					'defaultLang' => 'de',
					'nationality' => 'Austrian',
				),
				'AZ' => array(
					'iso' => 'AZ',
					'name' => 'Azerbaijan',
					'iso3' => 'AZE',
					'prefix' => '994',
					'defaultLang' => 'en',
					'nationality' => 'Azerbaijani',
				),
				'BS' => array(
					'iso' => 'BS',
					'name' => 'Bahamas',
					'iso3' => 'BHS',
					'prefix' => '1242',
					'defaultLang' => 'en',
					'nationality' => 'Bahamian',
				),
				'BH' => array(
					'iso' => 'BH',
					'name' => 'Bahrain',
					'iso3' => 'BHR',
					'prefix' => '973',
					'defaultLang' => 'ar',
					'nationality' => 'Bahraini',
				),
				'BD' => array(
					'iso' => 'BD',
					'name' => 'Bangladesh',
					'iso3' => 'BGD',
					'prefix' => '880',
					'defaultLang' => 'en',
					'nationality' => 'Bangladeshi',
				),
				'BB' => array(
					'iso' => 'BB',
					'name' => 'Barbados',
					'iso3' => 'BRB',
					'prefix' => '1246',
					'defaultLang' => 'en',
					'nationality' => 'Barbadian',
				),
				'BY' => array(
					'iso' => 'BY',
					'name' => 'Belarus',
					'iso3' => 'BLR',
					'prefix' => '375',
					'defaultLang' => 'ru',
					'nationality' => 'Belarusian',
				),
				'BE' => array(
					'iso' => 'BE',
					'name' => 'Belgium',
					'iso3' => 'BEL',
					'prefix' => '32',
					'defaultLang' => 'fr',
					'nationality' => 'Belgian',
				),
				'BZ' => array(
					'iso' => 'BZ',
					'name' => 'Belize',
					'iso3' => 'BLZ',
					'prefix' => '501',
					'defaultLang' => 'en',
					'nationality' => 'Belizean',
				),
				'BJ' => array(
					'iso' => 'BJ',
					'name' => 'Benin',
					'iso3' => 'BEN',
					'prefix' => '229',
					'defaultLang' => 'fr',
					'nationality' => 'Beninese',
				),
				'BM' => array(
					'iso' => 'BM',
					'name' => 'Bermuda',
					'iso3' => 'BMU',
					'prefix' => '1441',
					'defaultLang' => 'en',
					'nationality' => 'Bermudian',
				),
				'BT' => array(
					'iso' => 'BT',
					'name' => 'Bhutan',
					'iso3' => 'BTN',
					'prefix' => '975',
					'defaultLang' => 'en',
					'nationality' => 'Bhutanese',
				),
				'BO' => array(
					'iso' => 'BO',
					'name' => 'Bolivia',
					'iso3' => 'BOL',
					'prefix' => '591',
					'defaultLang' => 'es',
					'nationality' => 'Bolivian',
				),
				'BA' => array(
					'iso' => 'BA',
					'name' => 'Bosnia and Herzegovina',
					'iso3' => 'BIH',
					'prefix' => '387',
					'defaultLang' => 'ru',
					'nationality' => 'Bosnian/Herzegovinian',
				),
				'BW' => array(
					'iso' => 'BW',
					'name' => 'Botswana',
					'iso3' => 'BWA',
					'prefix' => '267',
					'defaultLang' => 'en',
					'nationality' => 'Botswana/Motswana',
				),
				'BV' => array(
					'iso' => 'BV',
					'name' => 'Bouvet Island',
					'iso3' => null,
					'prefix' => null,
					'defaultLang' => 'en',
					'nationality' => 'Bouvet Island',
				),
				'BR' => array(
					'iso' => 'BR',
					'name' => 'Brazil',
					'iso3' => 'BRA',
					'prefix' => '55',
					'defaultLang' => 'pt-br',
					'nationality' => 'Brazilian',
				),
				'IO' => array(
					'iso' => 'IO',
					'name' => 'British Indian Ocean Territory',
					'iso3' => null,
					'prefix' => '246',
					'defaultLang' => 'en',
					'nationality' => 'British Indian Ocean Territory',
				),
				'BN' => array(
					'iso' => 'BN',
					'name' => 'Brunei Darussalam',
					'iso3' => 'BRN',
					'prefix' => '673',
					'defaultLang' => 'ar',
					'nationality' => 'Bruneian',
				),
				'BG' => array(
					'iso' => 'BG',
					'name' => 'Bulgaria',
					'iso3' => 'BGR',
					'prefix' => '359',
					'defaultLang' => 'en',
					'nationality' => 'Bulgarian',
				),
				'BF' => array(
					'iso' => 'BF',
					'name' => 'Burkina Faso',
					'iso3' => 'BFA',
					'prefix' => '226',
					'defaultLang' => 'es',
					'nationality' => 'Burkinabe',
				),
				'BI' => array(
					'iso' => 'BI',
					'name' => 'Burundi',
					'iso3' => 'BDI',
					'prefix' => '257',
					'defaultLang' => 'en',
					'nationality' => 'Burundian',
				),
				'KH' => array(
					'iso' => 'KH',
					'name' => 'Cambodia',
					'iso3' => 'KHM',
					'prefix' => '855',
					'defaultLang' => 'en',
					'nationality' => 'Cambodian',
				),
				'CM' => array(
					'iso' => 'CM',
					'name' => 'Cameroon',
					'iso3' => 'CMR',
					'prefix' => '237',
					'defaultLang' => 'en',
					'nationality' => 'Cameroonian',
				),
				'CA' => array(
					'iso' => 'CA',
					'name' => 'Canada',
					'iso3' => 'CAN',
					'prefix' => '1',
					'defaultLang' => 'en',
					'nationality' => 'Canadian',
				),
				'CV' => array(
					'iso' => 'CV',
					'name' => 'Cape Verde',
					'iso3' => 'CPV',
					'prefix' => '238',
					'defaultLang' => 'pt-br',
					'nationality' => 'Cape Verdian',
				),
				'KY' => array(
					'iso' => 'KY',
					'name' => 'Cayman Islands',
					'iso3' => 'CYM',
					'prefix' => '1345',
					'defaultLang' => 'en',
					'nationality' => 'Cayman Islander',
				),
				'CF' => array(
					'iso' => 'CF',
					'name' => 'Central African Republic',
					'iso3' => 'CAF',
					'prefix' => '236',
					'defaultLang' => 'en',
					'nationality' => 'Central African',
				),
				'TD' => array(
					'iso' => 'TD',
					'name' => 'Chad',
					'iso3' => 'TCD',
					'prefix' => '235',
					'defaultLang' => 'en',
					'nationality' => 'Chadian',
				),
				'CL' => array(
					'iso' => 'CL',
					'name' => 'Chile',
					'iso3' => 'CHL',
					'prefix' => '56',
					'defaultLang' => 'es',
					'nationality' => 'Chilean',
				),
				'CN' => array(
					'iso' => 'CN',
					'name' => 'China',
					'iso3' => 'CHN',
					'prefix' => '86',
					'defaultLang' => 'en',
					'nationality' => 'Chinese',
				),
				'CX' => array(
					'iso' => 'CX',
					'name' => 'Christmas Island',
					'iso3' => null,
					'prefix' => '61',
					'defaultLang' => 'en',
					'nationality' => 'Christmas Islander',
				),
				'CC' => array(
					'iso' => 'CC',
					'name' => 'Cocos (Keeling) Islands',
					'iso3' => '',
					'prefix' => '61',
					'defaultLang' => 'en',
					'nationality' => 'Cocos (Keeling) Islander',
				),
				'CO' => array(
					'iso' => 'CO',
					'name' => 'Colombia',
					'iso3' => 'COL',
					'prefix' => '57',
					'defaultLang' => 'es',
					'nationality' => 'Colombian',
				),
				'KM' => array(
					'iso' => 'KM',
					'name' => 'Comoros',
					'iso3' => 'COM',
					'prefix' => '269',
					'defaultLang' => 'en',
					'nationality' => 'Comoran',
				),
				'CG' => array(
					'iso' => 'CG',
					'name' => 'Congo',
					'iso3' => 'COG',
					'prefix' => '242',
					'defaultLang' => 'en',
					'nationality' => 'Congolese',
				),
				'CD' => array(
					'iso' => 'CD',
					'name' => 'Congo, the Democratic Republic of the',
					'iso3' => 'COD',
					'prefix' => '243',
					'defaultLang' => 'en',
					'nationality' => 'Congolese',
				),
				'CK' => array(
					'iso' => 'CK',
					'name' => 'Cook Islands',
					'iso3' => 'COK',
					'prefix' => '682',
					'defaultLang' => 'en',
					'nationality' => 'Cook Islander',
				),
				'CR' => array(
					'iso' => 'CR',
					'name' => 'Costa Rica',
					'iso3' => 'CRI',
					'prefix' => '506',
					'defaultLang' => 'es',
					'nationality' => 'Costa Rican',
				),
				'CI' => array(
					'iso' => 'CI',
					'name' => 'Cote D\'Ivoire',
					'iso3' => 'CIV',
					'prefix' => '225',
					'defaultLang' => 'fr',
					'nationality' => 'Ivorian',
				),
				'HR' => array(
					'iso' => 'HR',
					'name' => 'Croatia',
					'iso3' => 'HRV',
					'prefix' => '385',
					'defaultLang' => 'en',
					'nationality' => 'Croatian',
				),
				'CU' => array(
					'iso' => 'CU',
					'name' => 'Cuba',
					'iso3' => 'CUB',
					'prefix' => '53',
					'defaultLang' => 'es',
					'nationality' => 'Cuban',
				),
				'CY' => array(
					'iso' => 'CY',
					'name' => 'Cyprus',
					'iso3' => 'CYP',
					'prefix' => '357',
					'defaultLang' => 'en',
					'nationality' => 'Cypriot',
				),
				'CZ' => array(
					'iso' => 'CZ',
					'name' => 'Czech Republic',
					'iso3' => 'CZE',
					'prefix' => '420',
					'defaultLang' => 'en',
					'nationality' => 'Czech',
				),
				'DK' => array(
					'iso' => 'DK',
					'name' => 'Denmark',
					'iso3' => 'DNK',
					'prefix' => '45',
					'defaultLang' => 'en',
					'nationality' => 'Danish',
				),
				'DJ' => array(
					'iso' => 'DJ',
					'name' => 'Djibouti',
					'iso3' => 'DJI',
					'prefix' => '253',
					'defaultLang' => 'en',
					'nationality' => 'Djiboutian',
				),
				'DM' => array(
					'iso' => 'DM',
					'name' => 'Dominica',
					'iso3' => 'DMA',
					'prefix' => '1767',
					'defaultLang' => 'es',
					'nationality' => 'Dominican',
				),
				'DO' => array(
					'iso' => 'DO',
					'name' => 'Dominican Republic',
					'iso3' => 'DOM',
					'prefix' => '1809',
					'defaultLang' => 'es',
					'nationality' => 'Dominican',
				),
				'EC' => array(
					'iso' => 'EC',
					'name' => 'Ecuador',
					'iso3' => 'ECU',
					'prefix' => '593',
					'defaultLang' => 'es',
					'nationality' => 'Ecuadorian',
				),
				'EG' => array(
					'iso' => 'EG',
					'name' => 'Egypt',
					'iso3' => 'EGY',
					'prefix' => '20',
					'defaultLang' => 'ar',
					'nationality' => 'Egyptian',
				),
				'SV' => array(
					'iso' => 'SV',
					'name' => 'El Salvador',
					'iso3' => 'SLV',
					'prefix' => '503',
					'defaultLang' => 'es',
					'nationality' => 'Salvadorian',
				),
				'GQ' => array(
					'iso' => 'GQ',
					'name' => 'Equatorial Guinea',
					'iso3' => 'GNQ',
					'prefix' => '240',
					'defaultLang' => 'en',
					'nationality' => 'Equatoguinean',
				),
				'ER' => array(
					'iso' => 'ER',
					'name' => 'Eritrea',
					'iso3' => 'ERI',
					'prefix' => '291',
					'defaultLang' => 'en',
					'nationality' => 'Eritrean',
				),
				'EE' => array(
					'iso' => 'EE',
					'name' => 'Estonia',
					'iso3' => 'EST',
					'prefix' => '372',
					'defaultLang' => 'en',
					'nationality' => 'Estonian',
				),
				'ET' => array(
					'iso' => 'ET',
					'name' => 'Ethiopia',
					'iso3' => 'ETH',
					'prefix' => '251',
					'defaultLang' => 'en',
					'nationality' => 'Ethiopian',
				),
				'FK' => array(
					'iso' => 'FK',
					'name' => 'Falkland Islands (Malvinas)',
					'iso3' => 'FLK',
					'prefix' => '500',
					'defaultLang' => 'en',
					'nationality' => 'Falkland Islander (Malvinas)',
				),
				'FO' => array(
					'iso' => 'FO',
					'name' => 'Faroe Islands',
					'iso3' => 'FRO',
					'prefix' => '298',
					'defaultLang' => 'en',
					'nationality' => 'Faroe Islander',
				),
				'FJ' => array(
					'iso' => 'FJ',
					'name' => 'Fiji',
					'iso3' => 'FJI',
					'prefix' => '679',
					'defaultLang' => 'en',
					'nationality' => 'Fijian',
				),
				'FI' => array(
					'iso' => 'FI',
					'name' => 'Finland',
					'iso3' => 'FIN',
					'prefix' => '358',
					'defaultLang' => 'en',
					'nationality' => 'Finnish',
				),
				'FR' => array(
					'iso' => 'FR',
					'name' => 'France',
					'iso3' => 'FRA',
					'prefix' => '33',
					'defaultLang' => 'fr',
					'nationality' => 'French',
				),
				'GF' => array(
					'iso' => 'GF',
					'name' => 'French Guiana',
					'iso3' => 'GUF',
					'prefix' => '',
					'defaultLang' => 'fr',
					'nationality' => 'French Guianese',
				),
				'PF' => array(
					'iso' => 'PF',
					'name' => 'French Polynesia',
					'iso3' => 'PYF',
					'prefix' => '689',
					'defaultLang' => 'fr',
					'nationality' => 'French Polynesian',
				),
				'TF' => array(
					'iso' => 'TF',
					'name' => 'French Southern Territories',
					'iso3' => null,
					'prefix' => null,
					'defaultLang' => 'fr',
					'nationality' => 'French Southern Territories',
				),
				'GA' => array(
					'iso' => 'GA',
					'name' => 'Gabon',
					'iso3' => 'GAB',
					'prefix' => '241',
					'defaultLang' => 'en',
					'nationality' => 'Gabonese',
				),
				'GM' => array(
					'iso' => 'GM',
					'name' => 'Gambia',
					'iso3' => 'GMB',
					'prefix' => '220',
					'defaultLang' => 'en',
					'nationality' => 'Gambian',
				),
				'GE' => array(
					'iso' => 'GE',
					'name' => 'Georgia',
					'iso3' => 'GEO',
					'prefix' => '995',
					'defaultLang' => 'ru',
					'nationality' => 'Georgian',
				),
				'DE' => array(
					'iso' => 'DE',
					'name' => 'Germany',
					'iso3' => 'DEU',
					'prefix' => '49',
					'defaultLang' => 'de',
					'nationality' => 'German',
				),
				'GH' => array(
					'iso' => 'GH',
					'name' => 'Ghana',
					'iso3' => 'GHA',
					'prefix' => '233',
					'defaultLang' => 'en',
					'nationality' => 'Ghanaian',
				),
				'GI' => array(
					'iso' => 'GI',
					'name' => 'Gibraltar',
					'iso3' => 'GIB',
					'prefix' => '350',
					'defaultLang' => 'en',
					'nationality' => 'Gibraltarian',
				),
				'GR' => array(
					'iso' => 'GR',
					'name' => 'Greece',
					'iso3' => 'GRC',
					'prefix' => '30',
					'defaultLang' => 'en',
					'nationality' => 'Greek',
				),
				'GL' => array(
					'iso' => 'GL',
					'name' => 'Greenland',
					'iso3' => 'GRL',
					'prefix' => '2991',
					'defaultLang' => 'en',
					'nationality' => 'Greenlander',
				),
				'GD' => array(
					'iso' => 'GD',
					'name' => 'Grenada',
					'iso3' => 'GRD',
					'prefix' => '1473',
					'defaultLang' => 'en',
					'nationality' => 'Grenadian',
				),
				'GP' => array(
					'iso' => 'GP',
					'name' => 'Guadeloupe',
					'iso3' => 'GLP',
					'prefix' => '590',
					'defaultLang' => 'es',
					'nationality' => 'Guatemalan',
				),
				'GU' => array(
					'iso' => 'GU',
					'name' => 'Guam',
					'iso3' => 'GUM',
					'prefix' => '1671',
					'defaultLang' => 'en',
					'nationality' => 'Guamanian',
				),
				'GT' => array(
					'iso' => 'GT',
					'name' => 'Guatemala',
					'iso3' => 'GTM',
					'prefix' => '502',
					'defaultLang' => 'es',
					'nationality' => 'Guatemalan',
				),
				'GN' => array(
					'iso' => 'GN',
					'name' => 'Guinea',
					'iso3' => 'GIN',
					'prefix' => '224',
					'defaultLang' => 'en',
					'nationality' => 'Guinean',
				),
				'GW' => array(
					'iso' => 'GW',
					'name' => 'Guinea-Bissau',
					'iso3' => 'GNB',
					'prefix' => '245',
					'defaultLang' => 'en',
					'nationality' => 'Guinea-Bissauan',
				),
				'GY' => array(
					'iso' => 'GY',
					'name' => 'Guyana',
					'iso3' => 'GUY',
					'prefix' => '592',
					'defaultLang' => 'en',
					'nationality' => 'Guyanese',
				),
				'HT' => array(
					'iso' => 'HT',
					'name' => 'Haiti',
					'iso3' => 'HTI',
					'prefix' => '509',
					'defaultLang' => 'fr',
					'nationality' => 'Haitian',
				),
				'HM' => array(
					'iso' => 'HM',
					'name' => 'Heard Island and Mcdonald Islands',
					'iso3' => null,
					'prefix' => null,
					'defaultLang' => 'en',
					'nationality' => 'Heard Island and Mcdonald Islands',
				),
				'VA' => array(
					'iso' => 'VA',
					'name' => 'Holy See (Vatican City State)',
					'iso3' => 'VAT',
					'prefix' => '379',
					'defaultLang' => 'en',
					'nationality' => 'Holy See (Vatican City State)',
				),
				'HN' => array(
					'iso' => 'HN',
					'name' => 'Honduras',
					'iso3' => 'HND',
					'prefix' => '504',
					'defaultLang' => 'es',
					'nationality' => 'Honduran',
				),
				'HK' => array(
					'iso' => 'HK',
					'name' => 'Hong Kong',
					'iso3' => 'HKG',
					'prefix' => '852',
					'defaultLang' => 'en',
					'nationality' => 'Chinese',
				),
				'HU' => array(
					'iso' => 'HU',
					'name' => 'Hungary',
					'iso3' => 'HUN',
					'prefix' => '36',
					'defaultLang' => 'en',
					'nationality' => 'Hungarian/Magyar',
				),
				'IS' => array(
					'iso' => 'IS',
					'name' => 'Iceland',
					'iso3' => 'ISL',
					'prefix' => '354',
					'defaultLang' => 'en',
					'nationality' => 'Icelander',
				),
				'IN' => array(
					'iso' => 'IN',
					'name' => 'India',
					'iso3' => 'IND',
					'prefix' => '91',
					'defaultLang' => 'en',
					'nationality' => 'Indian',
				),
				'ID' => array(
					'iso' => 'ID',
					'name' => 'Indonesia',
					'iso3' => 'IDN',
					'prefix' => '62',
					'defaultLang' => 'en',
					'nationality' => 'Indonesian',
				),
				'IR' => array(
					'iso' => 'IR',
					'name' => 'Iran, Islamic Republic of',
					'iso3' => 'IRN',
					'prefix' => '98',
					'defaultLang' => 'ar',
					'nationality' => 'Iranian',
				),
				'IQ' => array(
					'iso' => 'IQ',
					'name' => 'Iraq',
					'iso3' => 'IRQ',
					'prefix' => '964',
					'defaultLang' => 'ar',
					'nationality' => 'Iraqi',
				),
				'IE' => array(
					'iso' => 'IE',
					'name' => 'Ireland',
					'iso3' => 'IRL',
					'prefix' => '353',
					'defaultLang' => 'en',
					'nationality' => 'Irish',
				),
				'IL' => array(
					'iso' => 'IL',
					'name' => 'Israel',
					'iso3' => 'ISR',
					'prefix' => '972',
					'defaultLang' => 'en',
					'nationality' => 'Israeli',
				),
				'IT' => array(
					'iso' => 'IT',
					'name' => 'Italy',
					'iso3' => 'ITA',
					'prefix' => '39',
					'defaultLang' => 'it',
					'nationality' => 'Italian',
				),
				'JM' => array(
					'iso' => 'JM',
					'name' => 'Jamaica',
					'iso3' => 'JAM',
					'prefix' => '1876',
					'defaultLang' => 'en',
					'nationality' => 'Jamaican',
				),
				'JP' => array(
					'iso' => 'JP',
					'name' => 'Japan',
					'iso3' => 'JPN',
					'prefix' => '81',
					'defaultLang' => 'en',
					'nationality' => 'Japanese',
				),
				'JO' => array(
					'iso' => 'JO',
					'name' => 'Jordan',
					'iso3' => 'JOR',
					'prefix' => '962',
					'defaultLang' => 'ar',
					'nationality' => 'Jordanian',
				),
				'KZ' => array(
					'iso' => 'KZ',
					'name' => 'Kazakhstan',
					'iso3' => 'KAZ',
					'prefix' => '7',
					'defaultLang' => 'ru',
					'nationality' => 'Kazakhstani',
				),
				'KE' => array(
					'iso' => 'KE',
					'name' => 'Kenya',
					'iso3' => 'KEN',
					'prefix' => '254',
					'defaultLang' => 'en',
					'nationality' => 'Kenyan',
				),
				'KI' => array(
					'iso' => 'KI',
					'name' => 'Kiribati',
					'iso3' => 'KIR',
					'prefix' => '686',
					'defaultLang' => 'en',
					'nationality' => 'Kiribati',
				),
				'KP' => array(
					'iso' => 'KP',
					'name' => 'Korea, Democratic People\'s Republic of',
					'iso3' => 'PRK',
					'prefix' => '850',
					'defaultLang' => 'en',
					'nationality' => 'North Korean',
				),
				'KR' => array(
					'iso' => 'KR',
					'name' => 'Korea, Republic of',
					'iso3' => 'KOR',
					'prefix' => '82',
					'defaultLang' => 'en',
					'nationality' => 'South Korean',
				),
				'KV' => array(
					'iso' => 'KV',
					'name' => 'Kosovo',
					'iso3' => 'UNK',
					'prefix' => '381',
					'defaultLang' => 'ru',
					'nationality' => null,
				),
				'KW' => array(
					'iso' => 'KW',
					'name' => 'Kuwait',
					'iso3' => 'KWT',
					'prefix' => '965',
					'defaultLang' => 'ar',
					'nationality' => 'Kuwaiti',
				),
				'KG' => array(
					'iso' => 'KG',
					'name' => 'Kyrgyzstan',
					'iso3' => 'KGZ',
					'prefix' => '996',
					'defaultLang' => 'ru',
					'nationality' => 'Kyrgyz',
				),
				'LA' => array(
					'iso' => 'LA',
					'name' => 'Lao People\'s Democratic Republic',
					'iso3' => 'LAO',
					'prefix' => '856',
					'defaultLang' => 'en',
					'nationality' => 'Laotian',
				),
				'LV' => array(
					'iso' => 'LV',
					'name' => 'Latvia',
					'iso3' => 'LVA',
					'prefix' => '371',
					'defaultLang' => 'en',
					'nationality' => 'Latvian',
				),
				'LB' => array(
					'iso' => 'LB',
					'name' => 'Lebanon',
					'iso3' => 'LBN',
					'prefix' => '961',
					'defaultLang' => 'en',
					'nationality' => 'Lebanese',
				),
				'LS' => array(
					'iso' => 'LS',
					'name' => 'Lesotho',
					'iso3' => 'LSO',
					'prefix' => '266',
					'defaultLang' => 'en',
					'nationality' => 'Mosotho',
				),
				'LR' => array(
					'iso' => 'LR',
					'name' => 'Liberia',
					'iso3' => 'LBR',
					'prefix' => '231',
					'defaultLang' => 'en',
					'nationality' => 'Liberian',
				),
				'LY' => array(
					'iso' => 'LY',
					'name' => 'Libyan Arab Jamahiriya',
					'iso3' => 'LBY',
					'prefix' => '218',
					'defaultLang' => 'ar',
					'nationality' => 'Libyan',
				),
				'LI' => array(
					'iso' => 'LI',
					'name' => 'Liechtenstein',
					'iso3' => 'LIE',
					'prefix' => '423',
					'defaultLang' => 'en',
					'nationality' => 'Liechtensteiner',
				),
				'LT' => array(
					'iso' => 'LT',
					'name' => 'Lithuania',
					'iso3' => 'LTU',
					'prefix' => '370',
					'defaultLang' => 'en',
					'nationality' => 'Lithuanian',
				),
				'LU' => array(
					'iso' => 'LU',
					'name' => 'Luxembourg',
					'iso3' => 'LUX',
					'prefix' => '352',
					'defaultLang' => 'en',
					'nationality' => 'Luxembourger',
				),
				'MO' => array(
					'iso' => 'MO',
					'name' => 'Macao',
					'iso3' => 'MAC',
					'prefix' => '853',
					'defaultLang' => 'pt-br',
					'nationality' => 'Macanese',
				),
				'MK' => array(
					'iso' => 'MK',
					'name' => 'Macedonia, the Former Yugoslav Republic of',
					'iso3' => 'MKD',
					'prefix' => '389',
					'defaultLang' => 'en',
					'nationality' => 'Macedonian',
				),
				'MG' => array(
					'iso' => 'MG',
					'name' => 'Madagascar',
					'iso3' => 'MDG',
					'prefix' => '261',
					'defaultLang' => 'fr',
					'nationality' => 'Malagasy',
				),
				'MW' => array(
					'iso' => 'MW',
					'name' => 'Malawi',
					'iso3' => 'MWI',
					'prefix' => '265',
					'defaultLang' => 'en',
					'nationality' => 'Malawian',
				),
				'MY' => array(
					'iso' => 'MY',
					'name' => 'Malaysia',
					'iso3' => 'MYS',
					'prefix' => '60',
					'defaultLang' => 'en',
					'nationality' => 'Malaysian',
				),
				'MV' => array(
					'iso' => 'MV',
					'name' => 'Maldives',
					'iso3' => 'MDV',
					'prefix' => '960',
					'defaultLang' => 'en',
					'nationality' => 'Maldivan',
				),
				'ML' => array(
					'iso' => 'ML',
					'name' => 'Mali',
					'iso3' => 'MLI',
					'prefix' => '223',
					'defaultLang' => 'en',
					'nationality' => 'Malian',
				),
				'MT' => array(
					'iso' => 'MT',
					'name' => 'Malta',
					'iso3' => 'MLT',
					'prefix' => '356',
					'defaultLang' => 'en',
					'nationality' => 'Maltese',
				),
				'MH' => array(
					'iso' => 'MH',
					'name' => 'Marshall Islands',
					'iso3' => 'MHL',
					'prefix' => '692',
					'defaultLang' => 'en',
					'nationality' => 'Marshallese',
				),
				'MQ' => array(
					'iso' => 'MQ',
					'name' => 'Martinique',
					'iso3' => 'MTQ',
					'prefix' => '596',
					'defaultLang' => 'fr',
					'nationality' => 'Martinique',
				),
				'MR' => array(
					'iso' => 'MR',
					'name' => 'Mauritania',
					'iso3' => 'MRT',
					'prefix' => '222',
					'defaultLang' => 'en',
					'nationality' => 'Mauritanian',
				),
				'MU' => array(
					'iso' => 'MU',
					'name' => 'Mauritius',
					'iso3' => 'MUS',
					'prefix' => '2302',
					'defaultLang' => 'en',
					'nationality' => 'Mauritian',
				),
				'YT' => array(
					'iso' => 'YT',
					'name' => 'Mayotte',
					'iso3' => null,
					'prefix' => '262',
					'defaultLang' => 'en',
					'nationality' => 'Mahoran',
				),
				'MX' => array(
					'iso' => 'MX',
					'name' => 'Mexico',
					'iso3' => 'MEX',
					'prefix' => '52',
					'defaultLang' => 'es',
					'nationality' => 'Mexican',
				),
				'FM' => array(
					'iso' => 'FM',
					'name' => 'Micronesia, Federated States of',
					'iso3' => 'FSM',
					'prefix' => '691',
					'defaultLang' => 'en',
					'nationality' => 'Micronesian',
				),
				'MD' => array(
					'iso' => 'MD',
					'name' => 'Moldova, Republic of',
					'iso3' => 'MDA',
					'prefix' => '373',
					'defaultLang' => 'en',
					'nationality' => 'Moldovan',
				),
				'MC' => array(
					'iso' => 'MC',
					'name' => 'Monaco',
					'iso3' => 'MCO',
					'prefix' => '37797',
					'defaultLang' => 'fr',
					'nationality' => 'Monegasque/Monacan',
				),
				'MN' => array(
					'iso' => 'MN',
					'name' => 'Mongolia',
					'iso3' => 'MNG',
					'prefix' => '976',
					'defaultLang' => 'en',
					'nationality' => 'Mongolian',
				),
				'ME' => array(
					'iso' => 'ME',
					'name' => 'Montenegro',
					'iso3' => 'MNE',
					'prefix' => '382',
					'defaultLang' => 'en',
					'nationality' => 'Montenegrin',
				),
				'MS' => array(
					'iso' => 'MS',
					'name' => 'Montserrat',
					'iso3' => 'MSR',
					'prefix' => '1664',
					'defaultLang' => 'en',
					'nationality' => 'Montserrat',
				),
				'MA' => array(
					'iso' => 'MA',
					'name' => 'Morocco',
					'iso3' => 'MAR',
					'prefix' => '212',
					'defaultLang' => 'ar',
					'nationality' => 'Moroccan',
				),
				'MZ' => array(
					'iso' => 'MZ',
					'name' => 'Mozambique',
					'iso3' => 'MOZ',
					'prefix' => '258',
					'defaultLang' => 'pt-br',
					'nationality' => 'Mozambican',
				),
				'MM' => array(
					'iso' => 'MM',
					'name' => 'Myanmar',
					'iso3' => 'MMR',
					'prefix' => '95',
					'defaultLang' => 'en',
					'nationality' => 'Myanmarese/Burmese',
				),
				'NA' => array(
					'iso' => 'NA',
					'name' => 'Namibia',
					'iso3' => 'NAM',
					'prefix' => '264',
					'defaultLang' => 'en',
					'nationality' => 'Namibian',
				),
				'NR' => array(
					'iso' => 'NR',
					'name' => 'Nauru',
					'iso3' => 'NRU',
					'prefix' => '674',
					'defaultLang' => 'en',
					'nationality' => 'Nauruan',
				),
				'NP' => array(
					'iso' => 'NP',
					'name' => 'Nepal',
					'iso3' => 'NPL',
					'prefix' => '977',
					'defaultLang' => 'en',
					'nationality' => 'Nepalese',
				),
				'NL' => array(
					'iso' => 'NL',
					'name' => 'Netherlands',
					'iso3' => 'NLD',
					'prefix' => '31',
					'defaultLang' => 'en',
					'nationality' => 'Dutch',
				),
				'AN' => array(
					'iso' => 'AN',
					'name' => 'Netherlands Antilles',
					'iso3' => 'ANT',
					'prefix' => '599',
					'defaultLang' => 'fr',
					'nationality' => 'Netherlands Antilles',
				),
				'NC' => array(
					'iso' => 'NC',
					'name' => 'New Caledonia',
					'iso3' => 'NCL',
					'prefix' => '687',
					'defaultLang' => 'en',
					'nationality' => 'New Caledonian',
				),
				'NZ' => array(
					'iso' => 'NZ',
					'name' => 'New Zealand',
					'iso3' => 'NZL',
					'prefix' => '64',
					'defaultLang' => 'en',
					'nationality' => 'New Zealander',
				),
				'NI' => array(
					'iso' => 'NI',
					'name' => 'Nicaragua',
					'iso3' => 'NIC',
					'prefix' => '505',
					'defaultLang' => 'es',
					'nationality' => 'Nicaraguan',
				),
				'NE' => array(
					'iso' => 'NE',
					'name' => 'Niger',
					'iso3' => 'NER',
					'prefix' => '227',
					'defaultLang' => 'en',
					'nationality' => 'Nigerien',
				),
				'NG' => array(
					'iso' => 'NG',
					'name' => 'Nigeria',
					'iso3' => 'NGA',
					'prefix' => '234',
					'defaultLang' => 'en',
					'nationality' => 'Nigerian',
				),
				'NU' => array(
					'iso' => 'NU',
					'name' => 'Niue',
					'iso3' => 'NIU',
					'prefix' => '683',
					'defaultLang' => 'en',
					'nationality' => 'Niuean',
				),
				'NF' => array(
					'iso' => 'NF',
					'name' => 'Norfolk Island',
					'iso3' => 'NFK',
					'prefix' => '672',
					'defaultLang' => 'en',
					'nationality' => 'Norfolk Islander',
				),
				'MP' => array(
					'iso' => 'MP',
					'name' => 'Northern Mariana Islands',
					'iso3' => 'MNP',
					'prefix' => '1670',
					'defaultLang' => 'en',
					'nationality' => 'Northern Mariana Islander',
				),
				'NO' => array(
					'iso' => 'NO',
					'name' => 'Norway',
					'iso3' => 'NOR',
					'prefix' => '47',
					'defaultLang' => 'en',
					'nationality' => 'Norwegian',
				),
				'OM' => array(
					'iso' => 'OM',
					'name' => 'Oman',
					'iso3' => 'OMN',
					'prefix' => '968',
					'defaultLang' => 'ar',
					'nationality' => 'Omani',
				),
				'PK' => array(
					'iso' => 'PK',
					'name' => 'Pakistan',
					'iso3' => 'PAK',
					'prefix' => '92',
					'defaultLang' => 'en',
					'nationality' => 'Pakistani',
				),
				'PW' => array(
					'iso' => 'PW',
					'name' => 'Palau',
					'iso3' => 'PLW',
					'prefix' => '680',
					'defaultLang' => 'en',
					'nationality' => 'Palauan',
				),
				'PS' => array(
					'iso' => 'PS',
					'name' => 'Palestinian Territory',
					'iso3' => '',
					'prefix' => '970',
					'defaultLang' => 'ar',
					'nationality' => 'Palestinian Territory',
				),
				'PA' => array(
					'iso' => 'PA',
					'name' => 'Panama',
					'iso3' => 'PAN',
					'prefix' => '507',
					'defaultLang' => 'es',
					'nationality' => 'Panamanian',
				),
				'PG' => array(
					'iso' => 'PG',
					'name' => 'Papua New Guinea',
					'iso3' => 'PNG',
					'prefix' => '675',
					'defaultLang' => 'en',
					'nationality' => 'Papua New Guinean',
				),
				'PY' => array(
					'iso' => 'PY',
					'name' => 'Paraguay',
					'iso3' => 'PRY',
					'prefix' => '595',
					'defaultLang' => 'es',
					'nationality' => 'Paraguayan',
				),
				'PE' => array(
					'iso' => 'PE',
					'name' => 'Peru',
					'iso3' => 'PER',
					'prefix' => '51',
					'defaultLang' => 'es',
					'nationality' => 'Peruvian',
				),
				'PH' => array(
					'iso' => 'PH',
					'name' => 'Philippines',
					'iso3' => 'PHL',
					'prefix' => '63',
					'defaultLang' => 'en',
					'nationality' => 'Filipino',
				),
				'PN' => array(
					'iso' => 'PN',
					'name' => 'Pitcairn',
					'iso3' => 'PCN',
					'prefix' => '870',
					'defaultLang' => 'en',
					'nationality' => 'Pitcairn Islander',
				),
				'PL' => array(
					'iso' => 'PL',
					'name' => 'Poland',
					'iso3' => 'POL',
					'prefix' => '48',
					'defaultLang' => 'en',
					'nationality' => 'Polish',
				),
				'PT' => array(
					'iso' => 'PT',
					'name' => 'Portugal',
					'iso3' => 'PRT',
					'prefix' => '351',
					'defaultLang' => 'pt-br',
					'nationality' => 'Portuguese',
				),
				'PR' => array(
					'iso' => 'PR',
					'name' => 'Puerto Rico',
					'iso3' => 'PRI',
					'prefix' => '1',
					'defaultLang' => 'es',
					'nationality' => 'Puerto Rican',
				),
				'QA' => array(
					'iso' => 'QA',
					'name' => 'Qatar',
					'iso3' => 'QAT',
					'prefix' => '974',
					'defaultLang' => 'ar',
					'nationality' => 'Qatari',
				),
				'RE' => array(
					'iso' => 'RE',
					'name' => 'Reunion',
					'iso3' => 'REU',
					'prefix' => '',
					'defaultLang' => 'fr',
					'nationality' => 'Reunion',
				),
				'RO' => array(
					'iso' => 'RO',
					'name' => 'Romania',
					'iso3' => 'ROU',
					'prefix' => '40',
					'defaultLang' => 'en',
					'nationality' => 'Romanian',
				),
				'RU' => array(
					'iso' => 'RU',
					'name' => 'Russia',
					'iso3' => 'RUS',
					'prefix' => '7',
					'defaultLang' => 'ru',
					'nationality' => 'Russian',
				),
				'RW' => array(
					'iso' => 'RW',
					'name' => 'Rwanda',
					'iso3' => 'RWA',
					'prefix' => '250',
					'defaultLang' => 'en',
					'nationality' => 'Rwandan',
				),
				'SH' => array(
					'iso' => 'SH',
					'name' => 'Saint Helena',
					'iso3' => 'SHN',
					'prefix' => '290',
					'defaultLang' => 'en',
					'nationality' => 'Saint Helenian',
				),
				'KN' => array(
					'iso' => 'KN',
					'name' => 'Saint Kitts and Nevis',
					'iso3' => 'KNA',
					'prefix' => '1869',
					'defaultLang' => 'en',
					'nationality' => 'Kittian/Nevisian',
				),
				'LC' => array(
					'iso' => 'LC',
					'name' => 'Saint Lucia',
					'iso3' => 'LCA',
					'prefix' => '1758',
					'defaultLang' => 'en',
					'nationality' => 'Saint Lucian',
				),
				'PM' => array(
					'iso' => 'PM',
					'name' => 'Saint Pierre and Miquelon',
					'iso3' => 'SPM',
					'prefix' => '508',
					'defaultLang' => 'en',
					'nationality' => 'Saint-Pierrais/Miquelonnais',
				),
				'VC' => array(
					'iso' => 'VC',
					'name' => 'Saint Vincent and the Grenadines',
					'iso3' => 'VCT',
					'prefix' => '1784',
					'defaultLang' => 'en',
					'nationality' => 'Saint Vincent and the Grenadines',
				),
				'WS' => array(
					'iso' => 'WS',
					'name' => 'Samoa',
					'iso3' => 'WSM',
					'prefix' => '685',
					'defaultLang' => 'en',
					'nationality' => 'Samoan',
				),
				'SM' => array(
					'iso' => 'SM',
					'name' => 'San Marino',
					'iso3' => 'SMR',
					'prefix' => '378',
					'defaultLang' => 'en',
					'nationality' => 'San Marinese',
				),
				'ST' => array(
					'iso' => 'ST',
					'name' => 'Sao Tome and Principe',
					'iso3' => 'STP',
					'prefix' => '239',
					'defaultLang' => 'en',
					'nationality' => 'Sao Tomean',
				),
				'SA' => array(
					'iso' => 'SA',
					'name' => 'Saudi Arabia',
					'iso3' => 'SAU',
					'prefix' => '966',
					'defaultLang' => 'ar',
					'nationality' => 'Saudi',
				),
				'SN' => array(
					'iso' => 'SN',
					'name' => 'Senegal',
					'iso3' => 'SEN',
					'prefix' => '221',
					'defaultLang' => 'en',
					'nationality' => 'Senegalese',
				),
				'CS' => array(
					'iso' => 'CS',
					'name' => 'Serbia',
					'iso3' => '',
					'prefix' => '381',
					'defaultLang' => 'ru',
					'nationality' => 'Serbian',
				),
				'SC' => array(
					'iso' => 'SC',
					'name' => 'Seychelles',
					'iso3' => 'SYC',
					'prefix' => '248',
					'defaultLang' => 'fr',
					'nationality' => 'Seychellois',
				),
				'SL' => array(
					'iso' => 'SL',
					'name' => 'Sierra Leone',
					'iso3' => 'SLE',
					'prefix' => '232',
					'defaultLang' => 'en',
					'nationality' => 'Sierra Leonean',
				),
				'SG' => array(
					'iso' => 'SG',
					'name' => 'Singapore',
					'iso3' => 'SGP',
					'prefix' => '65',
					'defaultLang' => 'en',
					'nationality' => 'Singaporean',
				),
				'SK' => array(
					'iso' => 'SK',
					'name' => 'Slovakia',
					'iso3' => 'SVK',
					'prefix' => '421',
					'defaultLang' => 'en',
					'nationality' => 'Slovakian',
				),
				'SI' => array(
					'iso' => 'SI',
					'name' => 'Slovenia',
					'iso3' => 'SVN',
					'prefix' => '386',
					'defaultLang' => 'ru',
					'nationality' => 'Slovenian',
				),
				'SB' => array(
					'iso' => 'SB',
					'name' => 'Solomon Islands',
					'iso3' => 'SLB',
					'prefix' => '677',
					'defaultLang' => 'en',
					'nationality' => 'Solomon Islander',
				),
				'SO' => array(
					'iso' => 'SO',
					'name' => 'Somalia',
					'iso3' => 'SOM',
					'prefix' => '252',
					'defaultLang' => 'en',
					'nationality' => 'Somali',
				),
				'ZA' => array(
					'iso' => 'ZA',
					'name' => 'South Africa',
					'iso3' => 'ZAF',
					'prefix' => '27',
					'defaultLang' => 'en',
					'nationality' => 'South African',
				),
				'GS' => array(
					'iso' => 'GS',
					'name' => 'South Georgia and the South Sandwich Islands',
					'iso3' => null,
					'prefix' => '500',
					'defaultLang' => 'en',
					'nationality' => 'South Georgia and the South Sandwich Islander',
				),
				'ES' => array(
					'iso' => 'ES',
					'name' => 'Spain',
					'iso3' => 'ESP',
					'prefix' => '34',
					'defaultLang' => 'es',
					'nationality' => 'Spanish',
				),
				'LK' => array(
					'iso' => 'LK',
					'name' => 'Sri Lanka',
					'iso3' => 'LKA',
					'prefix' => '94',
					'defaultLang' => 'en',
					'nationality' => 'Sri Lankan',
				),
				'SD' => array(
					'iso' => 'SD',
					'name' => 'Sudan',
					'iso3' => 'SDN',
					'prefix' => '249',
					'defaultLang' => 'ar',
					'nationality' => 'Sudanese',
				),
				'SR' => array(
					'iso' => 'SR',
					'name' => 'Suriname',
					'iso3' => 'SUR',
					'prefix' => '597',
					'defaultLang' => 'en',
					'nationality' => 'Surinamer',
				),
				'SJ' => array(
					'iso' => 'SJ',
					'name' => 'Svalbard and Jan Mayen',
					'iso3' => 'SJM',
					'prefix' => '47',
					'defaultLang' => 'en',
					'nationality' => 'Svalbard and Jan Mayen',
				),
				'SZ' => array(
					'iso' => 'SZ',
					'name' => 'Swaziland',
					'iso3' => 'SWZ',
					'prefix' => '268',
					'defaultLang' => 'en',
					'nationality' => 'Swazi',
				),
				'SE' => array(
					'iso' => 'SE',
					'name' => 'Sweden',
					'iso3' => 'SWE',
					'prefix' => '46',
					'defaultLang' => 'en',
					'nationality' => 'Swedish',
				),
				'CH' => array(
					'iso' => 'CH',
					'name' => 'Switzerland',
					'iso3' => 'CHE',
					'prefix' => '41',
					'defaultLang' => 'en',
					'nationality' => 'Swiss',
				),
				'SY' => array(
					'iso' => 'SY',
					'name' => 'Syrian Arab Republic',
					'iso3' => 'SYR',
					'prefix' => '963',
					'defaultLang' => 'ar',
					'nationality' => 'Syrian',
				),
				'TW' => array(
					'iso' => 'TW',
					'name' => 'Taiwan, Province of China',
					'iso3' => 'TWN',
					'prefix' => '886',
					'defaultLang' => 'en',
					'nationality' => 'Taiwanese',
				),
				'TJ' => array(
					'iso' => 'TJ',
					'name' => 'Tajikistan',
					'iso3' => 'TJK',
					'prefix' => '992',
					'defaultLang' => 'en',
					'nationality' => 'Tajik',
				),
				'TZ' => array(
					'iso' => 'TZ',
					'name' => 'Tanzania, United Republic of',
					'iso3' => 'TZA',
					'prefix' => '255',
					'defaultLang' => 'en',
					'nationality' => 'Tanzanian',
				),
				'TH' => array(
					'iso' => 'TH',
					'name' => 'Thailand',
					'iso3' => 'THA',
					'prefix' => '66',
					'defaultLang' => 'en',
					'nationality' => 'Thai',
				),
				'TL' => array(
					'iso' => 'TL',
					'name' => 'Timor-Leste',
					'iso3' => null,
					'prefix' => '670',
					'defaultLang' => 'pt-br',
					'nationality' => 'Timorese',
				),
				'TG' => array(
					'iso' => 'TG',
					'name' => 'Togo',
					'iso3' => 'TGO',
					'prefix' => '228',
					'defaultLang' => 'en',
					'nationality' => 'Togolese',
				),
				'TK' => array(
					'iso' => 'TK',
					'name' => 'Tokelau',
					'iso3' => 'TKL',
					'prefix' => '690',
					'defaultLang' => 'en',
					'nationality' => 'Tokelauan',
				),
				'TO' => array(
					'iso' => 'TO',
					'name' => 'Tonga',
					'iso3' => 'TON',
					'prefix' => '676',
					'defaultLang' => 'en',
					'nationality' => 'Tongan',
				),
				'TT' => array(
					'iso' => 'TT',
					'name' => 'Trinidad and Tobago',
					'iso3' => 'TTO',
					'prefix' => '1868',
					'defaultLang' => 'en',
					'nationality' => 'Trinidadian/Tobagonian',
				),
				'TN' => array(
					'iso' => 'TN',
					'name' => 'Tunisia',
					'iso3' => 'TUN',
					'prefix' => '216',
					'defaultLang' => 'ar',
					'nationality' => 'Tunisian',
				),
				'TR' => array(
					'iso' => 'TR',
					'name' => 'Turkey',
					'iso3' => 'TUR',
					'prefix' => '90',
					'defaultLang' => 'en',
					'nationality' => 'Turkish',
				),
				'TM' => array(
					'iso' => 'TM',
					'name' => 'Turkmenistan',
					'iso3' => 'TKM',
					'prefix' => '993',
					'defaultLang' => 'ar',
					'nationality' => 'Turkmen',
				),
				'TC' => array(
					'iso' => 'TC',
					'name' => 'Turks and Caicos Islands',
					'iso3' => 'TCA',
					'prefix' => '1649',
					'defaultLang' => 'en',
					'nationality' => 'Turks and Caicos Islander',
				),
				'TV' => array(
					'iso' => 'TV',
					'name' => 'Tuvalu',
					'iso3' => 'TUV',
					'prefix' => '688',
					'defaultLang' => 'en',
					'nationality' => 'Tuvaluan',
				),
				'UG' => array(
					'iso' => 'UG',
					'name' => 'Uganda',
					'iso3' => 'UGA',
					'prefix' => '256',
					'defaultLang' => 'en',
					'nationality' => 'Ugandan',
				),
				'UA' => array(
					'iso' => 'UA',
					'name' => 'Ukraine',
					'iso3' => 'UKR',
					'prefix' => '380',
					'defaultLang' => 'ru',
					'nationality' => 'Ukrainian',
				),
				'AE' => array(
					'iso' => 'AE',
					'name' => 'United Arab Emirates',
					'iso3' => 'ARE',
					'prefix' => '971',
					'defaultLang' => 'en',
					'nationality' => 'Emirian',
				),
				'GB' => array(
					'iso' => 'GB',
					'name' => 'United Kingdom',
					'iso3' => 'GBR',
					'prefix' => '44',
					'defaultLang' => 'en',
					'nationality' => 'British',
				),
				'US' => array(
					'iso' => 'US',
					'name' => 'United States',
					'iso3' => 'USA',
					'prefix' => '1',
					'defaultLang' => 'en',
					'nationality' => 'American',
				),
				'UM' => array(
					'iso' => 'UM',
					'name' => 'United States Minor Outlying Islands',
					'iso3' => '',
					'prefix' => '',
					'defaultLang' => 'en',
					'nationality' => 'American Islander',
				),
				'UY' => array(
					'iso' => 'UY',
					'name' => 'Uruguay',
					'iso3' => 'URY',
					'prefix' => '598',
					'defaultLang' => 'es',
					'nationality' => 'Uruguayan',
				),
				'UZ' => array(
					'iso' => 'UZ',
					'name' => 'Uzbekistan',
					'iso3' => 'UZB',
					'prefix' => '998',
					'defaultLang' => 'ru',
					'nationality' => 'Uzbekistani',
				),
				'VU' => array(
					'iso' => 'VU',
					'name' => 'Vanuatu',
					'iso3' => 'VUT',
					'prefix' => '678',
					'defaultLang' => 'en',
					'nationality' => 'Vanuatuan',
				),
				'VE' => array(
					'iso' => 'VE',
					'name' => 'Venezuela',
					'iso3' => 'VEN',
					'prefix' => '58',
					'defaultLang' => 'es',
					'nationality' => 'Venezuelan',
				),
				'VN' => array(
					'iso' => 'VN',
					'name' => 'Viet Nam',
					'iso3' => 'VNM',
					'prefix' => '84',
					'defaultLang' => 'en',
					'nationality' => 'Vietnamese',
				),
				'VG' => array(
					'iso' => 'VG',
					'name' => 'Virgin Islands, British',
					'iso3' => 'VGB',
					'prefix' => '1284',
					'defaultLang' => 'en',
					'nationality' => 'Virgin Islander ',
				),
				'VI' => array(
					'iso' => 'VI',
					'name' => 'Virgin Islands, U.s.',
					'iso3' => 'VIR',
					'prefix' => '1340',
					'defaultLang' => 'en',
					'nationality' => ' U.S. Virgin Islander',
				),
				'WF' => array(
					'iso' => 'WF',
					'name' => 'Wallis and Futuna',
					'iso3' => 'WLF',
					'prefix' => '681',
					'defaultLang' => 'en',
					'nationality' => 'Wallisian/Futunan',
				),
				'EH' => array(
					'iso' => 'EH',
					'name' => 'Western Sahara',
					'iso3' => 'ESH',
					'prefix' => '212',
					'defaultLang' => 'en',
					'nationality' => 'Western Sahara/Sahrawi',
				),
				'YE' => array(
					'iso' => 'YE',
					'name' => 'Yemen',
					'iso3' => 'YEM',
					'prefix' => '967',
					'defaultLang' => 'en',
					'nationality' => 'Yemeni',
				),
				'ZM' => array(
					'iso' => 'ZM',
					'name' => 'Zambia',
					'iso3' => 'ZMB',
					'prefix' => '260',
					'defaultLang' => 'en',
					'nationality' => 'Zambian',
				),
				'ZW' => array(
					'iso' => 'ZW',
					'name' => 'Zimbabwe',
					'iso3' => 'ZWE',
					'prefix' => '263',
					'defaultLang' => 'en',
					'nationality' => 'Zimbabwean',
				),
			);
		}
	}