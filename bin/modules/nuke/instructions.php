<?php
	defined( 'ABSPATH' ) || die( 'Sorry, but you cannot access this page directly.' );

	add_action( 'route', 'handle_nuke_request' );
