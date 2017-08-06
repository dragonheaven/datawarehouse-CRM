<?php
	defined( 'ABSPATH' ) || die( 'Sorry, but you cannot access this page directly.' );

	if ( extension_loaded( 'newrelic' ) ) {
		add_action( 'init', 'tc_init_newrelic' );
	}