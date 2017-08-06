<?php
	defined( 'ABSPATH' ) || die( 'Sorry, but you cannot access this page directly.' );

	function init_database() {
		$conn_string = sprintf( '%s:host=%s;port=%d;dbname=%s', DBTYPE, DBHOST, intval( DBPORT ), DBNAME );
		try {
			R::setup( $conn_string, DBUSER, DBPASS );
			$rbh = new SWMFBeanHelper;
			$redbean = R::getRedBean();
			if ( is_object( $redbean ) ) {
				$redbean->setBeanHelper( $rbh );
			}
			else {
				api_failure( null, 'Error Connecting to Database: Missing Redbean', array( 'Missing Redbean' ) );
			}
			R::ext( 'cachedLoad', 'cached_load' );
			R::ext( 'cachedFindOne', 'cached_findone' );
			R::ext( 'sysDispense', 'db_dispense' );
			R::ext( 'sysDispenseAll', 'db_dispense_all' );
			R::ext( 'sysLoad', 'db_load' );
			R::ext( 'sysLoadAll', 'db_load_all' );
			R::ext( 'sysFind', 'db_find' );
			R::ext( 'sysFindOne', 'db_find_one' );
			R::ext( 'sysFindAll', 'db_find_all' );
			R::ext( 'sysCount', 'db_count' );
			R::ext( 'sysWipe', 'db_wipe' );
			R::ext( 'sysInspect', 'db_inspect' );
			R::ext( 'sysBeanExists', 'db_table_exists' );
			set_db_pref( DBPREF );
		}
		catch ( Exception $e ) {
			error_log( $e->getMessage() );
			$errors = array(
				'Caught Exception',
			);
			if ( true == DEBUG ) {
				array_push( $errors, $e->getMessage() );
			}
			api_failure( null, 'Error Connecting to Database: Caught Exception', $errors );
		}
		if ( false === R::testConnection() ) {
			$feedback = ( true == DEBUG ) ? array(
				'connstring' => $conn_string,
				'dbuser' => DBUSER,
			) : null;
			api_failure( $feedback, 'Error Connecting to Database: Connection Failed', array( 'Connection Failed' ) );
		}
	}

	function shutdown_database() {
		R::close();
	}

	function cached_load( $type, $id = 0 ) {
		$ck = md5( sprintf( 'Redbean_%s_%d_cached', $type, $id ) );
		$cached = cache_get( $ck, '!NOCACHE!' );
		if ( ! can_loop( $cached ) && 0 !== absint( $id ) ) {
			$bean = R::load( $type, $id );
			if ( is_a( $bean, 'RedBeanPHP\OODBBean' ) ) {
				$cached = $bean->export();
			}
			else {
				$cached = array();
			}
			cache_set( $ck, $cached );
		}
		try {
			$ret = R::dispense( $type );
			if ( can_loop( $cached ) ) {
				$ret->import( $cached );
			}
		}
		catch ( Exception $e ) {
			$ret = null;
		}
		return $ret;
	}

	function cached_findone( $type, $query = null, $vars = array() ) {
		$ck = md5( sprintf( 'Redbean_findone_%s_matching_%s_with_%s', $type, $query, serialize( $vars ) ) );
		$cached = cache_get( $ck, '!NOCACHE!' );
		if ( ! can_loop( $cached ) ) {
			$bean = R::findOne( $type, $query, $vars );
			if ( is_a( $bean, 'RedBeanPHP\OODBBean' ) ) {
				$cached = $bean->export();
			}
			else {
				$cached = array();
			}
			cache_set( $ck, $cached );
		}
		try {
			$ret = R::dispense( $type );
			if ( can_loop( $cached ) ) {
				$ret->import( $cached );
			}
		}
		catch ( Exception $e ) {
			$ret = null;
		}
		return $ret;
	}

	function db_dispense( $type, $param = null ) {
		$ot = sprintf( '%s%s', get_db_pref(), $type );
		if ( ! is_empty( $param ) ) {
			return R::getRedBean()->dispense( $ot, $param );
		}
		return R::getRedBean()->dispense( $ot );
	}

	function db_dispense_all( $type, $param = null ) {
		$ot = sprintf( '%s%s', get_db_pref(), $type );
		if ( ! is_empty( $param ) ) {
			return R::getRedBean()->dispenseAll( $ot, $param );
		}
		return R::getRedBean()->dispenseAll( $ot );
	}

	function db_load( $type, $id = 0 ) {
		$ot = sprintf( '%s%s', get_db_pref(), $type );
		return R::getRedBean()->load( $ot, $id );
	}

	function db_load_all( $type, $ids = array() ) {
		$ot = sprintf( '%s%s', get_db_pref(), $type );
		return R::getRedBean()->loadAll( $ot, $ids );
	}

	function db_find( $type, $query = null, $vars = array() ) {
		$ot = sprintf( '%s%s', get_db_pref(), $type );
		return R::getRedBean()->find( $ot, array(), $query, $vars );
	}

	function db_find_one( $type, $query = null, $vars = array() ) {
		$ot = sprintf( '%s%s', get_db_pref(), $type );
		$res = R::sysFind( $type, $query, $vars );
		if ( ! can_loop( $res ) ) {
			return null;
		}
		$reskeys = array_keys( $res );
		return $res[ $reskeys[0] ];
	}

	function db_find_all( $type, $query = null, $vars = array() ) {
		$ot = sprintf( '%s%s', get_db_pref(), $type );
		return R::getRedBean()->find( $ot, array(), $query, $vars );
	}

	function db_count( $type, $query = null, $vars = array() ) {
		$ot = sprintf( '%s%s', get_db_pref(), $type );
		if ( ! can_loop( $vars ) ) {
			return R::getRedBean()->count( $ot, $query );
		}
		return R::getRedBean()->count( $ot, $query, $vars );
	}

	function db_wipe( $type ) {
		$ot = sprintf( '%s%s', get_db_pref(), $type );
		return R::getRedBean()->wipe( $ot );
	}

	function db_inspect( $type ) {
		$return = array();
		try {
			$data = R::getAll( sprintf( "select * from information_schema.columns where table_name = '%s%s'", get_db_pref(), $type ) );
			if ( can_loop( $data ) ) {
				foreach ( $data as $col ) {
					array_push( $return, get_array_key( 'COLUMN_NAME', $col, null ) );
				}
			}
		}
		catch ( Exception $e ) {
			error_log( $e->getMessage() );
		}
		return $return;
	}

	function db_table_exists( $type ) {
		$return = false;
		try {
			$count = R::getCell( sprintf( "select count( table_name ) FROM information_schema.columns WHERE table_name = '%s%s'", get_db_pref(), $type ) );
			$return = intval( $count ) > 0;
		}
		catch ( Exception $e ) {
			error_log( $e->getMessage() );
		}
		return $return;
	}

	function set_db_pref( $prefix ) {
		global $sys_default_db_prefix;
		$sys_default_db_prefix = $prefix;
	}

	function get_db_pref() {
		global $sys_default_db_prefix;
		return $sys_default_db_prefix;
	}

	function swap_db( Array $params ) {
		global $sys_defined_db_keys;
		if ( ! is_array( $sys_defined_db_keys ) ) {
			$sys_defined_db_keys = array();
		}
		$dparams = array(
			'type' => 'mysql',
			'host' => 'localhost',
			'port' => 3306,
			'name' => '',
			'user' => '',
			'pass' => '',
			'prefix' => '',
		);
		$p = array();
		foreach ( $dparams as $key => $default ) {
			$p[ $key ] = get_array_key( $key, $params, $default );
		}
		$key = md5( http_build_query( $p ) );
		if ( ! in_array( $key, $sys_defined_db_keys ) ) {
			$conn_string = sprintf( '%s:host=%s;port=%d;%s=%s', $p['type'], $p['host'], $p['port'], 'dbname', $p['name'] );
			try {
				R::addDatabase( $key, $conn_string, $p['user'], $p['pass'] );
				array_push( $sys_defined_db_keys, $key );
			}
			catch ( Exception $e ) {
				error_log( $e->getMessage() );
				return false;
			}
		}
		set_db_pref( $p['prefix'] );
		R::selectDatabase( $key );
		return true;
	}

	function reset_db() {
		R::selectDatabase( 'default' );
		set_db_pref( get_configuration_data( 'dbprefix' ) );
	}

	if ( ! function_exists( 'is_redbean' ) ) {
		function is_redbean( $input ) {
			return ( is_a( $input, 'RedBeanPHP\OODBBean' ) );
		}
	}

	if ( ! function_exists( 'table_exists' ) ) {
		function table_exists( $table ) {
			if ( ! is_string( $table ) || is_empty( $table ) ) {
				return false;
			}
			try {
				$query = sprintf( 'SELECT 1 FROM %s LIMIT 1', $table );
				$cell = R::getCell( $query );
				return ( ! is_empty( $cell ) );
			}
			catch ( Exception $e ) {
				return false;
			}
			return false;
		}
	}