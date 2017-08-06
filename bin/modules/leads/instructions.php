<?php
	defined( 'ABSPATH' ) || die( 'Sorry, but you cannot access this page directly.' );

	add_action( 'route', 'route_lead_pages' );
	add_action( 'import_item-template-pre-header', 'tc_validate_import_item' );