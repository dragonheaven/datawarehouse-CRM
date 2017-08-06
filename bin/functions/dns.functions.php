<?php
	defined( 'ABSPATH' ) || die( 'Sorry, but you cannot access this page directly.' );

	function get_dns_resolver() {
		if ( class_exists( 'Net_DNS2' ) ) {
			try {
				$obj = new Net_DNS2_Resolver( array(
					'nameservers' => array(
						'8.8.8.8',
						'8.8.4.4',
					),
					'cache_type' => 'shared',
        			'cache_file' => '/tmp/net_dns2.cache',
					'cache_size' => 100000,
        			'cache_serializer' => 'json',
				) );
			}
			catch( Exception $e ) {
				if ( true == DEBUG ) {
					if ( is_cli() ) {
						cli_echo( sprintf( 'Error getting DNS Resolver: %s', $e->getMessage() ) );
					}
					else {
						ajax_failure( sprintf( 'Error getting DNS Resolver: %s', $e->getMessage() ) );
					}
				}
				$obj = null;
			}
		}
		return ( isset( $obj ) ) ? $obj : null;
	}

	function dns_resolve_mx_records( $domain = null ) {
		$return = array();
		if ( ! is_empty( $domain ) ) {
			$r = get_dns_resolver();
			if ( is_a( $r, 'Net_DNS2_Resolver' ) ) {
				try {
					$ans = $r->query( $domain, 'MX' );
					if (
						is_a( $ans, 'Net_DNS2_Packet_Response' )
						&& property_exists( $ans, 'answer' )
						&& can_loop( $ans->answer )
					) {
						foreach ( $ans->answer as $a ) {
							array_push( $return, $a );
						}
					}
				}
				catch ( Exception $e ) {
					if ( true == DEBUG ) {
						if ( is_cli() ) {
							//cli_echo( sprintf( 'Error getting DNS Records for "%s": %s', $domain, $e->getMessage() ) );
						}
					}
				}
			}
		}
		return $return;
	}

	function dns_supports_email( $domain = null ) {
		if ( false !== strpos( $domain, '@' ) ) {
			list( $box, $domain ) = explode( '@', $domain );
		}
		$ck = md5( sprintf( 'domain_valid_email_%s', $domain ) );
		$cached = cache_get( $ck, '!NOCACHE!' );
		if ( '!NOCACHE!' == $cached ) {
			$cached = dns_resolve_mx_records( $domain );
			cache_set( $ck, $cached );
		}
		return ( can_loop( $cached ) );
	}

	function init_dns_resolver() {
		global $_dnsr;
		if ( ! @ini_get( 'mbstring.func_overload' ) ) {
			@ini_set( 'mbstring.func_overload', 0 );
		}
		if ( ! is_a( $_dnsr, 'Net_DNS2_Resolver' ) ) {
			$_dnsr = get_dns_resolver();
		}
	}