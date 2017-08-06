<?php
	defined( 'ABSPATH' ) || die( 'Sorry, but you cannot access this page directly.' );

	if ( ! isset( $_tc_model_sample_data ) ) {
		$_tc_model_sample_data = array();
	}

	$_tc_model_sample_data['email'] = array(
		'email_raw' => str_repeat( 'l', 254 ),
		'email' => str_repeat( 'l', 254 ),
		'valid_format' => true,
		'valid_domain' => true,
		'valid_inbox' => true,
		'suppressed' => true,
	);