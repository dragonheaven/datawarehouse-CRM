<?php
	defined( 'ABSPATH' ) || die( 'Sorry, but you cannot access this page directly.' );

	function get_interspire_object() {
		$obj = new InterspireApi(
			INTERSPIRE_API_URL,
			INTERSPIRE_API_USER,
			INTERSPIRE_API_KEY
		);
		return $obj;
	}

	function interspire_query( $command, $type, $query = array(), $originIp = '127.0.0.1' ) {
		$i = get_interspire_object();
		return $i->query( $command, $type, $query, $originIp );
	}