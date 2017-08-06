<?php
	defined( 'ABSPATH' ) || die( 'Sorry, but you cannot access this page directly.' );

	add_action( 'pre-route', 'route_logged_out_to_login' );
	add_action( 'route', 'route_login_page' );