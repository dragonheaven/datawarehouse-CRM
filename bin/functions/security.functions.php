<?php
	defined( 'ABSPATH' ) || die( 'Sorry, but you cannot access this page directly.' );

	function hashed_to( $val ) {
		global $config;
		return base64_encode( mcrypt_encrypt( MCRYPT_RIJNDAEL_256, substr( md5( APP ), 0, 16 ), $val, MCRYPT_MODE_ECB ) );
	}

	function hashed_from( $val ) {
		global $config;
		return trim( mcrypt_decrypt( MCRYPT_RIJNDAEL_256, substr( md5( APP ), 0, 16 ), base64_decode( $val ), MCRYPT_MODE_ECB ) );
	}