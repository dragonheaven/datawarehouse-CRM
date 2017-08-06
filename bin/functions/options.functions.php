<?php
	defined( 'ABSPATH' ) || die( 'Sorry, but you cannot access this page directly.' );

	function get_option( $key, $default = null, $fromCache = false ) {
		global $sys_global_options;
		if ( ! is_array( $sys_global_options ) ) {
			$sys_global_options = array();
		}
		if ( true == $fromCache ) {
			$memcached = cache_get( $key, '!NOCACHE!' );
			if ( '!NOCACHE!' !== $memcached ) {
				return $memcached;
			}
		}
		$cached = get_array_key( $key, $sys_global_options, '!NOCACHE!' );
		if ( '!NOCACHE!' !== $cached ) {
			return $cached;
		}
		try {
			$res = R::sysFindOne( 'siteoptions', 'metakey LIKE :k', array( ':k' => $key ) );
			if ( ! is_object( $res ) ) {
				return $default;
			}
			$return = ( true == $res->serialized ) ? unserialize( $res->value ) : $res->value;
			$sys_global_options[ $key ] = $return;
			return $return;
		}
		catch( Exception $e ) {
			trigger_error( ( $e->getMessage() ) );
			return $default;
		}
	}

	function update_option( $key, $value ) {
		global $sys_global_options;
		if ( ! is_array( $sys_global_options ) ) {
			$sys_global_options = array();
		}
		$serialized = ( is_array( $value ) || is_object( $value ) );
		$type = gettype( $value );
		$sys_global_options[ $key ] = $value;
		cache_set( $key, $value );
		try {
			$bean = R::sysFindOne( 'siteoptions', 'metakey LIKE :k', array( ':k' => $key ) );
		}
		catch ( Exception $e ) {}
		try {
			if ( ! isset( $bean ) || ! is_object( $bean ) ) {
				$bean = R::sysDispense( 'siteoptions' );
				$bean->metakey = $key;
			}
			$bean->serialized = $serialized;
			$bean->itemtype = $type;
			$bean->value = ( true == $serialized ) ? serialize( $value ) : $value;
			$id = R::store( $bean );
			return ( $id > 0 );
		}
		catch ( Exception $e ) {
			trigger_error( ( $e->getMessage() ) );
			return false;
		}
		return false;
	}

	function set_option( $key, $value ) {
		return update_option( $key, $value );
	}