<?php
	defined( 'ABSPATH' ) || die( 'Sorry, but you cannot access this page directly.' );

	class MailBoxLayer {
		private $key;

		function __construct( $key = null ) {
			$this->key = $key;
		}

		function validate( $email = null ) {
			$url = sprintf( 'https://apilayer.net/api/check?%s', http_build_query( array(
				'access_key' => $this->key,
				'email' => $email,
				'smtp' => 1,
				'format' => 1,
			) ) );
			$ret = HTTP_REQUEST::GET( $url );
			return $ret->data;
		}
	}