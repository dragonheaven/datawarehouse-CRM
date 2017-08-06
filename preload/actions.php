<?php
	defined( 'ABSPATH' ) || die( 'Sorry, but you cannot access this page directly.' );

	function add_action( $key, $function, $priority = null ) {
		global $sys_actions;
		if ( ! is_array( $sys_actions ) ) {
			$sys_actions = array();
		}
		if ( ! array_key_exists( $key, $sys_actions ) ) {
			$sys_actions[ $key ] = array();
		}
		if ( is_null( $priority ) ) {
			array_push( $sys_actions[ $key ], $function );
		}
		else {
			$keyid = $priority;
			while ( array_key_exists( $keyid, $sys_actions[ $key ] ) ) {
				$keyid ++;
			}
			$sys_actions[ $key ][ $keyid ] = $function;
		}
	}

	function do_action( $action, $data = null, $params = 1 ) {
		global $sys_actions;
		if ( ! is_array( $sys_actions ) ) {
			$sys_actions = array();
		}
		if ( array_key_exists( $action, $sys_actions ) && count( $sys_actions[ $action ] ) > 0 ) {
			ksort( $sys_actions[ $action ], SORT_NUMERIC );
			foreach ( $sys_actions[ $action ] as $index => $function ) {
				if (
					( is_array( $function ) &&
					class_exists( $function[0] ) &&
					method_exists( $function[0], $function[1] )
					) ||
					function_exists( $function )
				) {
					if ( is_null( $data ) ) {
						call_user_func( $function );
					}
					else if ( ! is_array( $data ) || $params > 1 ) {
						call_user_func( $function, $data );
					}
					else {
						call_user_func_array( $function, $data );
					}
				}
			}
		}
	}

	function add_filter( $key, $function, $priority = null ) {
		global $sys_filters;
		if ( ! is_array( $sys_filters ) ) {
			$sys_filters = array();
		}
		if ( ! array_key_exists( $key, $sys_filters ) ) {
			$sys_filters[ $key ] = array();
		}
		if ( is_null( $priority ) ) {
			array_push( $sys_filters[ $key ], $function );
		}
		else {
			$keyid = $priority;
			while ( array_key_exists( $keyid, $sys_filters[ $key ] ) ) {
				$keyid ++;
			}
			$sys_filters[ $key ][ $keyid ] = $function;
		}
	}

	function do_filter( $action, $data = null, $params = 1 ) {
		global $sys_filters;
		if ( ! is_array( $sys_filters ) ) {
			$sys_filters = array();
		}
		if ( array_key_exists( $action, $sys_filters ) && count( $sys_filters[ $action ] ) > 0 ) {
			ksort( $sys_filters[ $action ], SORT_NUMERIC );
			foreach ( $sys_filters[ $action ] as $index => $function ) {
				if (
					( is_array( $function ) &&
					class_exists( $function[0] ) &&
					method_exists( $function[0], $function[1] )
					) ||
					function_exists( $function )
				) {
					if ( is_null( $data ) ) {
						$data = call_user_func( $function );
					}
					else if ( ! is_array( $data ) || $params == 1 ) {
						$data = call_user_func( $function, $data );
					}
					else {
						$data = call_user_func_array( $function, $data );
					}
				}
			}
		}
		return $data;
	}