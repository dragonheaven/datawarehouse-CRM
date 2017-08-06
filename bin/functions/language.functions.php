<?php
	defined( 'ABSPATH' ) || die( 'Sorry, but you cannot access this page directly.' );

	function __( $term ) {
		$terms = get_translation_terms();
		if ( can_loop( $terms ) && array_key_exists( $term, $terms ) ) {
			do_action( 'report_untranslated_term', array(
				'term' => $term,
				'lang' => LANG,
			) );
		}
		return get_array_key( $term, $terms, $term );
	}

	function get_translation_terms() {
		$f = sprintf( '%s/langs/%s.json', ABSPATH, LANG );
		if ( file_exists( $f ) ) {
			$c = file_get_contents( $f );
			$json = json_decode( $c );
			$ret = json_decode( json_encode( $json->terms ), true );
		}
		else {
			$ret = array();
		}
		return $ret;
	}