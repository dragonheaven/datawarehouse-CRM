<?php
	defined( 'ABSPATH' ) || die( 'Sorry, but you cannot access this page directly.' );

	interface fieldModel {
		public function __construct( $fieldData, Array $rowData );
		public function convert();
	}

	abstract class fieldModelAbstract implements fieldModel {
		protected $raw;
		protected $row;

		function __construct( $fieldData, Array $rowData ) {
			$this->raw = $fieldData;
			$this->row = $rowData;
			$this->convert();
		}

		protected function get_bean_from_cache( $raw = null ) {
			if ( is_empty( $raw ) ) {
				return false;
			}
			$class = get_called_class();
			$beanType = str_replace( 'FieldModel', '', $class );
			$preCacheKey = sprintf( '%s_valcache_%s', $class, $raw );
			$cacheKey = md5( $preCacheKey );
			$cached = cache_get( $cacheKey, '!NOCACHE!' );
			if ( '!NOCACHE!' == $cached || 0 == absint( $cached ) ) {
				return false;
			}
			try {
				$bean = R::load( $beanType, absint( $cached ) );
			}
			catch ( Exception $e ) {
				$bean = false;
			}
			return $bean;
		}

		protected function set_bean_from_cache( $raw = null, RedBeanPHP\OODBBean $bean ) {
			$class = get_called_class();
			$preCacheKey = sprintf( '%s_valcache_%s', $class, print_r( $raw, true ) );
			$cacheKey = md5( $preCacheKey );
			cache_set( $cacheKey, $bean->id );
		}
	}