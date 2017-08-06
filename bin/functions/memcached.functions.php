<?php
	defined( 'ABSPATH' ) || die( 'Sorry, but you cannot access this page directly.' );

	function init_memcache() {
		global $_MEMCACHE;
		$servers = @unserialize( MEMCACHED_SERVERS );
		if ( can_loop( $servers ) ) {
			if ( ! is_a( $_MEMCACHE, 'Memcached' ) ) {
				$_MEMCACHE = new Memcached();
			}
			foreach ( $servers as $server ) {
				try {
					$_MEMCACHE->addServer(
						get_array_key( 'host', $server ),
						get_array_key( 'port', $server ),
						get_array_key( 'weight', $server )
					);
				}
				catch ( Exception $e ) {

				}
			}
		}
	}

	function cache_set( $key, $value = null, $exp = 0 ) {
		global $_MEMCACHE, $_tc_current_thread_cache;
		if ( is_null( $exp ) || ! is_numeric( $exp ) ) {
			$exp = time() + ( 86400 * 30 );
		}
		if ( is_a( $_MEMCACHE, 'Memcached' ) ) {
			if ( ! is_array( $_tc_current_thread_cache ) ) {
				$_tc_current_thread_cache = array();
			}
			$_tc_current_thread_cache[ $key ] = $value;
			return $_MEMCACHE->set( $key, $value, $exp );
		}
		return false;
	}

	function cache_update( $key, $value = null, $exp = 0 ) {
		return cache_set( $key, $value, $exp );
	}

	function cache_get( $key, $default = null ) {
		global $_MEMCACHE, $_tc_current_thread_cache;
		$return = $default;
		if ( is_a( $_MEMCACHE, 'Memcached' ) ) {
			$res = $_MEMCACHE->get( $key );
			$return = get_array_key( $key, $_tc_current_thread_cache, ( ! is_empty( $res ) ) ? $res : $default );
			if ( ! is_empty( $res ) ) {
				$return = $res;
			}
		}
		return $return;
	}

	function cache_delete( $key ) {
		global $_MEMCACHE, $_tc_current_thread_cache;
		$return = false;
		if ( is_a( $_MEMCACHE, 'Memcached' ) ) {
			$return = $_MEMCACHE->delete( $key );
		}
		unset( $_tc_current_thread_cache[ $key ] );
		return $return;
	}

	function cache_flush() {
		global $_MEMCACHE, $_tc_current_thread_cache;
		$return = false;
		if ( is_a( $_MEMCACHE, 'Memcached' ) ) {
			$return = $_MEMCACHE->flush();
		}
		unset( $_tc_current_thread_cache );
		$_tc_current_thread_cache = array();
		return $return;
	}

	function cache_get_status() {
		global $_MEMCACHE;
		$return = false;
		if ( is_a( $_MEMCACHE, 'Memcached' ) ) {
			$return = $_MEMCACHE->getStats();
		}
		return $return;
	}

	function ajax_get_memcached_status() {
		$status = cache_get_status();
		if ( can_loop( $status ) ) {
			foreach ( $status as $server => $info ) {
				if ( intval( get_array_key( 'pid', $info, 0 ) ) <= 0 ) {
					ajax_failure( sprintf( 'Server %s is not connected', $server ) );
				}
			}
			ajax_success( 'Memcached is operating normally' );
		}
		ajax_failure( 'Memcached is not running' );
	}