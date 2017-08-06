<?php
	defined( 'ABSPATH' ) || die( 'Sorry, but you cannot access this page directly.' );

	function get_geoip_info( $ip = 0 ) {
		$ck = md5( sprintf( 'ip_info_for_%s', $ip ) );
		$cached = cache_get( $ck, '!NOCACHE!' );
		if ( '!NOCACHE!' == $cached ) {
			$name = @gethostbyaddr( $ip );
			$return = array();
			if ( ! is_empty( $name ) ) {
				$return['hostname'] = $name;
				$return['continent'] = geoip_continent_code_by_name( $ip );
				$return['country'] = geoip_country_code_by_name( $ip );
				$ci = geoip_record_by_name( $ip );
				if ( can_loop( $ci ) ) {
					foreach ( $ci as $k => $v ) {
						$k = str_replace( '_code', '', $k );
						$return[ $k ] = $v;
					}
				}
			}
			cache_set( $ck, $return );
			$cached = $return;
		}
		return $cached;
	}