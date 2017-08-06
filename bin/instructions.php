<?php
	defined( 'ABSPATH' ) || die( 'Sorry, but you cannot access this page directly.' );

	add_action( 'preinit', 'init_database' );
	add_action( 'preinit', 'init_memcache' );
	add_action( 'preinit', 'init_dns_resolver' );
	add_action( 'init', 'initialize_routing' );
	add_action( 'api-shutdown', 'shutdown_database' );
	add_action( 'shutdown', 'shutdown_database' );
	add_action( 'shutdown', 'api_nothing_happened' );
	add_action( 'shutdown', 'close_cli_php_process' );
	add_action( 'api-shutdown', 'close_cli_php_process' );
	add_action( 'route', 'handle_ajax_request' );