<?php
	defined( 'ABSPATH' ) || die( 'Sorry, but you cannot access this page directly.' );

	if ( ! isset( $_tc_model_sample_data ) ) {
		$_tc_model_sample_data = array();
	}

	$_tc_model_sample_data['lead'] = array(
		'gender' => str_repeat( 'l', 254 ),
		'salutation' => str_repeat( 'l', 254 ),
		'name' => str_repeat( 'l', 254 ),
		'fname' => str_repeat( 'l', 254 ),
		'latinfname' => str_repeat( 'l', 254 ),
		'mname' => str_repeat( 'l', 254 ),
		'latinmname' => str_repeat( 'l', 254 ),
		'lname' => str_repeat( 'l', 254 ),
		'latinlname' => str_repeat( 'l', 254 ),
		'street1' => str_repeat( 'l', 254 ),
		'street2' => str_repeat( 'l', 254 ),
		'city' => str_repeat( 'l', 254 ),
		'region' => str_repeat( 'l', 254 ),
		'postalcode' => str_repeat( 'l', 254 ),
		'country' => str_repeat( 'l', 254 ),
		'timezone' => str_repeat( 'l', 254 ),
		'originalsource' => str_repeat( 'l', 254 ),
		'regtimestamp' => '1970-01-01 00:00:00',
		'createtimestamp' => '1970-01-01 00:00:00',
		'updatetimestamp' => '1970-01-01 00:00:00',
	);