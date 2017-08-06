<?php
	defined( 'ABSPATH' ) || die( 'Sorry, but you cannot access this page directly.' );

	function generate_tutorial_tag( $tag = null, $text = null ) {
		$html = '';
		if ( ! is_empty( $tag ) ) {
			$html = sprintf( '<a href="javascript:void(0);" class="tutorial-link with-tool-tip" title="Click to Open Tutorial" data-placement="bottom" data-tutorial="%s"><span class="fa fa-info-circle"></span>%s</a>', $tag, $text );
		}
		return $html;
	}

	function ajax_get_tutorial_data( $data ) {
		$tutorial = get_array_key( 'tutorial', $data, null );
		ajax_failure( sprintf( 'Sorry, but we couldn\'t find the tutorial for "%s". Please try again later.', $tutorial ) );
	}