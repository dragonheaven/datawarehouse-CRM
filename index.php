<?php
	define( 'ABSPATH', sprintf( '%s', dirname( __FILE__ ) ) );

	/**
	 * Define System Verson Number
	 */
	define( 'VERSION', '1.1.6' );
	define( 'DB_VERSION', '1.06' );

	/**
	 * Set the Default Time Zone
	 */
	date_default_timezone_set( 'UTC' );

	/**
	 * Initialize Session $_SESSION
	 */
	session_start();

	/**
	 * Add Pre-Loaded Functions
	 */
	$loaded_files = array();
	$pld = sprintf( '%s/preload/', ABSPATH );
	if ( file_exists( $pld ) && is_dir( $pld ) ) {
		$plf = scandir( $pld );
		if ( is_array( $plf ) && count( $plf ) > 0 ) {
			foreach ( $plf as $file ) {
				if (
					'..' !== $file
					&& '.' !== $file
					&& 'index.php' !== $file
					&& '.php' == substr( $file, -4 )
				) {
					$f = sprintf( '%s/preload/%s', ABSPATH, $file );
					require_once $f;
					array_push( $loaded_files, $f );
				}
			}
		}
	}
	else {
		exit( 'Missing Pre-Load Functions' );
	}

	/**
	 * Set Global Variables
	 * @var [type]
	 */
	$_server = $_SERVER;
	$_post = parse_http_method_data( 'POST' );
	$_get = parse_http_method_data( 'GET' );
	$_put = parse_http_method_data( 'PUT' );
	$_delete = parse_http_method_data( 'DELETE' );
	$_headers = parse_http_headers();
	$_files = $_FILES;

	/**
	 * Load config file
	 */
	$cf = sprintf( '%s/config.php', ABSPATH );
	if ( file_exists( $cf ) ) {
		require_once $cf;
		array_push( $loaded_files, $cf );
	}
	else {
		exit( 'Missing configuration file' );
	}


	/**
	 * Handle Config
	 */
	if ( can_loop( $_sc ) ) {
		foreach ( $_sc as $key => $value ) {
			if ( ! defined( $key ) ) {
				define( $key, $value );
			}
		}
	}

	$req_functions = array(
		'password_hash',
		'password_get_info',
		'password_needs_rehash',
		'password_verify',
	);
	if ( can_loop( $req_functions ) ) {
		foreach ( $req_functions as $funct ) {
			if ( ! function_exists( $funct ) ) {
				exit( 'Your version of PHP is too low to use this application' );
			}
		}
	}

	if ( ! function_exists( 'pg_connect' ) ) {
		exit( 'The PostgreSQL Driver for PHP could not be found.' );
	}

	/**
	 * Load Composer Libraries
	 */
	$clf = sprintf( '%s/composer/vendor/autoload.php', ABSPATH );
	if ( file_exists( $clf ) ) {
		require_once $clf;
		array_push( $loaded_files, $clf );
	}

	/**
	 * Load Bin
	 */
	$bin = sprintf( '%s/bin/', ABSPATH );
	try {
		$binfiles = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator( $bin ),
			RecursiveIteratorIterator::SELF_FIRST
		);
		foreach ( $binfiles as $name => $obj ) {
			if ( substr( $name, -4 ) == '.php' && strpos( $name, 'index.php' ) === false ) {
				require_once $name;
				array_push( $loaded_files, $name );
			}
		}
	}
	catch ( UnexpectedValueException $e ) {
		echo '<pre>';
		print_r( $e->getMessage() );
		echo '</pre>';
		define( 'ERROREXIT', true );
	}

	/**
	 * Load Plugins
	 */
	$pd = sprintf( '%s/plugins/', ABSPATH );
	if ( file_exists( $pd ) && is_dir( $pd ) ) {
		$pdc = scandir( $pd );
		if ( can_loop( $pdc ) ) {
			foreach ( $pdc as $pdcn ) {
				$plugdir = sprintf( '%s/plugins/%s', ABSPATH, $pdcn );
				$pluginst = sprintf( '%s/instructions.php', $plugdir );
				if ( file_exists( $plugdir ) && is_dir( $plugdir ) && file_exists( $pluginst ) && ! is_dir( $pluginst ) ) {
					require_once $pluginst;
					array_push( $loaded_files, $pluginst );
				}
			}
		}
	}

	if ( defined( 'ERROREXIT' ) && true == ERROREXIT ) {
		exit();
	}

	do_action( 'preinit' );
	/**
	 * Check that required information is loaded
	 */
	$lr = array(
		'Net_DNS2' => 'class',
		'get_dns_resolver' => 'function',
		'loaded_files' => 'variable',
	);
	if ( can_loop( $lr ) ) {
		foreach ( $lr as $req => $reqtype ) {
			switch ( $reqtype ) {
				case 'class':
					$check = ( class_exists( $req ) );
					break;

				case 'function':
					$check = ( function_exists( $req ) );
					break;

				case 'variable':
					$check = ( isset( ${ $req } ) );
					break;

				default:
					$check = ( is_a( ${ $req }, $reqtype ) );
					break;
			}
			if ( false === $check ) {
				if ( is_cli() ) {
					cli_failure( null, 'Missing Framework Requirement', array(
						sprintf( '"%s" is not a "%s"', $req, $reqtype ),
					) );
				}
				else {
					html_failure( 'error', 'Missing Framework Requirement', array(
						sprintf( '"%s" is not a "%s"', $req, $reqtype ),
					), 500 );
				}
			}
		}
	}
	do_action( 'init' );
	do_action( 'shutdown' );
	exit();