<?php
	defined( 'ABSPATH' ) || die( 'Sorry, but you cannot access this page directly.' );

	add_action( 'route', 'route_home_page' );
	add_action( 'route', 'route_system_stats_request' );