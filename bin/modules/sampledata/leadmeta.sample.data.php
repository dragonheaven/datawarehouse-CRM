<?php
	defined( 'ABSPATH' ) || die( 'Sorry, but you cannot access this page directly.' );

	if ( ! isset( $_tc_model_sample_data ) ) {
		$_tc_model_sample_data = array();
	}

	$_tc_model_sample_data['leadmeta'] = array(
		'key' => str_repeat( 'l', 254 ),
		'value' => str_repeat( 'l', 2048 ),
	);