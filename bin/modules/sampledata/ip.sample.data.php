<?php
	defined( 'ABSPATH' ) || die( 'Sorry, but you cannot access this page directly.' );

	if ( ! isset( $_tc_model_sample_data ) ) {
		$_tc_model_sample_data = array();
	}

	$_tc_model_sample_data['ip'] = array(
		'ip' => str_repeat( 'l', 254 ),
		'continent' => str_repeat( 'l', 254 ),
		'country' => str_repeat( 'l', 254 ),
		'region' => str_repeat( 'l', 254 ),
		'city' => str_repeat( 'l', 254 ),
		'postal' => str_repeat( 'l', 254 ),
		'latitude' => str_repeat( 1, 100 ),
		'longitude' => str_repeat( 1, 100 ),
	);