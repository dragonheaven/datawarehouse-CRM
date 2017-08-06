<?php
	defined( 'ABSPATH' ) || die( 'Sorry, but you cannot access this page directly.' );

	function get_server_memory_usage(){
		$cached = cache_get( 'server_memory_usage', '!NOCACHE!' );
		if ( '!NOCACHE!' !== $cached ) {
			return $cached;
		}
		$res = get_raw_server_memory_usage();
		cache_set( 'server_memory_usage', $res );
		return $res;
	}

	function get_server_cpu_usage(){
		$cached = cache_get( 'server_cpu_usage', '!NOCACHE!' );
		if ( '!NOCACHE!' !== $cached ) {
			return $cached;
		}
		$res = get_raw_server_cpu_usage();
		cache_set( 'server_cpu_usage', $res );
		return $res;
	}

	function get_simultanous_instance_count() {
		return get_raw_simultanous_instance_count();
	}

	function get_raw_server_memory_usage() {
		$memory_usage = 100;
		$free = @shell_exec( 'free' );
		$free = (string)trim( $free );
		$free_arr = explode( "\n", $free );
		if ( can_loop( $free_arr ) && count( $free_arr ) >= 2 ) {
			$mem = explode( " ", $free_arr[1] );
			$mem = array_filter( $mem );
			$mem = array_merge( $mem );
			if ( can_loop( $mem ) ) {
				$memory_usage = $mem[2] / $mem[1] * 100;
			}
		}
		return $memory_usage;
	}

	function get_raw_server_cpu_usage() {
		$load = sys_getloadavg();
		return $load[0] * 100;
	}

	function get_raw_simultanous_instance_count() {
		return intval( exec( 'ps aux|grep "[i]ndex.php"|wc -l ' ) );
	}

	function get_raw_simultanous_import_instance_count() {
		return intval( exec( "ps aux|grep '[c]reate-import-rows-from-job'|wc -l" ) );
	}

	function close_cli_php_process() {
		global $_tc_php_process;
		if ( is_resource( $_tc_php_process ) ) {
			return proc_close( $_tc_php_process );
		}
	}