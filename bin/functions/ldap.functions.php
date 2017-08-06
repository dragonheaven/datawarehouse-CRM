<?php
	defined( 'ABSPATH' ) || die( 'Sorry, but you cannot access this page directly.' );

	function get_ldap_object() {
		$obj = new tcldap( LICENSE_SITE, LICENSE_KEY );
		return $obj;
	}