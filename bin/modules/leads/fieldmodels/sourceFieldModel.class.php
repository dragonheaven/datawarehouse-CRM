<?php
	defined( 'ABSPATH' ) || die( 'Sorry, but you cannot access this page directly.' );

	if ( ! class_exists( 'fieldModelAbstract', false ) ) {
		require_once sprintf( '%s/bin/modules/leads/fieldmodels/abstract.interface.php', ABSPATH );
	}
	class sourceFieldModel extends fieldModelAbstract {
		private $type = 'source';
		private $attr = 'source';
		private $filter = 'tc_filter_text_field';
		function convert() {
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

		function convert_single( $raw, $row ) {
			$raw = call_user_func( $this->filter, $raw );
			if ( is_empty( $raw ) ) {
				return null;
			}
			$raw = call_user_func( $this->filter, $raw );
			if ( is_empty( $raw ) ) {
				return null;
			}
			/**
			 * New Code as of 1.1.3
			 * Because of Indexes, let's try to add first, then look after
			 */
			try {
				$obj = R::load( $this->type, absint( 0 ) );
				$obj->{$this->attr} = $raw;
			}
			catch ( Exception $e ) {
				ajax_success( print_r( $e, true ) );
			}
			try {
				$cid = R::store( $obj );
			}
			catch ( Exception $e ) {
				try {
					$cid = absint( R::getCell( sprintf( 'SELECT %s.id FROM %s WHERE %s = :val', $this->type, $this->type, $this->attr ), array( ':val' => $raw ) ) );
				}
				catch( Exception $e ) {
					$cid = 0;
				}
			}
			/**
			 * End of New Code
			 */
			$cachekey = md5( sprintf( '%s_bean_for_%s', $this->type, $raw ) );
			$cid = cache_get( $cachekey, $cid );
			if ( false === USE_CACHE_BYPASS ) {
				if ( 0 == absint( $cid ) ) {
					try {
						$cid = absint( R::getCell( sprintf( 'SELECT %s.id FROM %s WHERE %s = :val', $this->type, $this->type, $this->attr ), array( ':val' => $raw ) ) );
					}
					catch( Exception $e ) {
						$cid = 0;
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
				$obj->{$this->attr} = $raw;
				$cid = R::store( $obj );
				cache_set( $cachekey, $cid );
			}
			return $obj;
		}
	}