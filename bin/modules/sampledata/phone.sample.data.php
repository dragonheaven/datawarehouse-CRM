<?php
	defined( 'ABSPATH' ) || die( 'Sorry, but you cannot access this page directly.' );

	if ( ! isset( $_tc_model_sample_data ) ) {
		$_tc_model_sample_data = array();
	}

	$_tc_model_sample_data['phone'] = array(
		'valid' => true,
		'number' => str_repeat( 'l', 254 ),
		'number_numbers_only' => str_repeat( 'l', 254 ),
		'local_format' => str_repeat( 'l', 254 ),
		'international_format' => str_repeat( 'l', 254 ),
		'E164' => str_repeat( 'l', 254 ),
		'country_prefix' => str_repeat( 'l', 254 ),
		'country_code' => str_repeat( 'l', 254 ),
		'country_name' => str_repeat( 'l', 254 ),
		'location' => str_repeat( 'l', 254 ),
		'carrier' => str_repeat( 'l', 254 ),
		'line_type' => str_repeat( 'l', 254 ),
	);