<?php
	defined( 'ABSPATH' ) || die( 'Sorry, but you cannot access this page directly.' );

	function ajax_response( $input, $success = false, $redirect = false ) {
		exit( json_encode( array(
			'status' => $success,
			'data' => $input,
			'redirect' => $redirect,
		) ) );
	}

	function ajax_success( $input, $redirect = false ) {
		ajax_response( $input, true, $redirect );
	}

	function ajax_failure( $input, $redirect = false ) {
		ajax_response( $input, false, $redirect );
	}

	function ajax_debug( $input ) {
		$c = htmlentities( print_r( $input, true ) );
		$trace = debug_backtrace();
		$lines = array();
		if ( can_loop( $trace ) ) {
			foreach ( $trace as $t ) {
				array_push( $lines, sprintf( '%s LINE %d', get_array_key( 'file', $t ), get_array_key( 'line', $t ) ) );
			}
		}
		$c .= "\r\n" . print_r( $lines, true );
		ajax_failure( sprintf( '<pre>%s</pre>', $c ) );
	}

	function api_response( $data = null, $success = false, $message = null, $errors = null, $status = 200, $more = null ) {
		do_action( 'api-shutdown' );
		@header( 'Content-Type: application/json' );
		$status = intval( $status );
		if ( $status < 200 ) {
			$status = 200;
		}
		http_response_code( $status );
		$feedback = array(
			'status' => ( true == $success ) ? 'SUCCESS' : 'FAILURE',
			'data' => $data,
			'message' => $message,
			'errors' => ( can_loop( $errors ) ) ? $errors : null,
			'code' => $status,
			'more' => ( is_string( $more ) ) ? $more : serialize( $more ),
		);
		if ( true == DEBUG ) {
			$trace = debug_backtrace();
			$lines = array();
			if ( can_loop( $trace ) ) {
				foreach ( $trace as $t ) {
					if ( false === strpos( get_array_key( 'file', $t ), 'feedback.php' ) ) {
						array_push( $lines, sprintf( '%s LINE %d', get_array_key( 'file', $t ), get_array_key( 'line', $t ) ) );
					}
				}
			}
			if ( can_loop( $lines ) ) {
				$feedback['line'] = $lines[0];
			}
		}
		echo json_encode( $feedback );
		if ( is_cli() ) {
			echo "\r\n";
		}
		if ( function_exists( 'add_log_entry' ) ) {
			add_log_entry( 'response', array(
				'status' => ( true == $success ) ? 'SUCCESS' : 'FAILURE',
				'data' => is_string( $data ) ? $data : serialize( $data ),
				'message' => $message,
				'errors' => serialize( $errors ),
				'code' => $status,
				'more' => ( is_string( $more ) ) ? $more : serialize( $more ),
				'addLogId' => get_add_log_id(),
			) );
		}
		exit();
	}

	function api_success( $data, $message = null, $errors = null, $status = 200, $more = null ) {
		api_response( $data, true, $message, $errors, $status, $more );
	}

	function api_failure( $data = null, $message = null, $errors = null, $status = 400, $more = null ) {
		api_response( $data, false, $message, $errors, $status, $more );
	}

	function api_redirect( $url ) {
		do_action( 'api-shutdown' );
		echo json_encode( array(
			'status' => 'REDIRECT',
			'data' => $url,
			'message' => 'Redirect Required',
			'errors' => null,
		) );
		if ( is_cli() ) {
			echo "\r\n";
		}
		exit();
	}

	function api_debug( $content ) {
		api_failure( null, print_r( $content, true ), array(
			'API Debug',
		) );
	}

	function api_debug_obj( $content ) {
		api_failure( null, $content, array( 'API Debug' ) );
	}

	function api_nothing_happened() {
		api_failure( null, 'Nothing Happened', array( 'Nothing Happened' ) );
	}

	function html_response( $template = '404', $success = false, $title = null, $errors = null, $status = 200, $more = null ) {
		if ( is_empty( $template ) ) {
			$template = 'error';
		}
		do_action( 'html-shutdown' );
		header( 'Content-Type: text/html' );
		http_response_code( $status );
		$header = sprintf( '%s/common/header.php', ABSPATH );
		$footer = sprintf( '%s/common/footer.php', ABSPATH );
		$tpl = sprintf( '%s/common/templates/%s.tpl.php', ABSPATH, $template );
		$_dws_title_tag = ( is_empty( $title ) ) ? APP : sprintf( '%s - %s', $title, APP );
		do_action( 'template-pre-header', array( $template, $success, $title, $errors, $status, $more ), 6 );
		do_action( sprintf( '%s-template-pre-header', $template ), array( $template, $success, $title, $errors, $status, $more ), 6 );
		require_once $header;
		do_action( 'template-post-header', array( $template, $success, $title, $errors, $status, $more ), 6 );
		do_action( sprintf( '%s-template-post-header', $template ), array( $template, $success, $title, $errors, $status, $more ), 6 );
		if ( file_exists( $tpl ) ) {
			do_action( 'template-pre-body', array( $template, $success, $title, $errors, $status, $more ), 6 );
			do_action( sprintf( '%s-template-pre-body', $template ), array( $template, $success, $title, $errors, $status, $more ), 6 );
			require_once $tpl;
			do_action( 'template-post-body', array( $template, $success, $title, $errors, $status, $more ), 6 );
			do_action( sprintf( '%s-template-post-body', $template ), array( $template, $success, $title, $errors, $status, $more ), 6 );
		}
		else {
			echo sprintf( 'Cannot find template file <code>%s</code>', $template );
		}
		do_action( 'template-pre-footer', array( $template, $success, $title, $errors, $status, $more ), 6 );
		do_action( sprintf( '%s-template-pre-footer', $template ), array( $template, $success, $title, $errors, $status, $more ), 6 );
		require_once $footer;
		do_action( 'template-post-footer', array( $template, $success, $title, $errors, $status, $more ), 6 );
		do_action( sprintf( '%s-template-post-footer', $template ), array( $template, $success, $title, $errors, $status, $more ), 6 );
		do_action( 'api-shutdown' );
		exit();
	}

	function html_success( $template = '404', $title = null, $errors = null, $status = 200, $more = null ) {
		html_response( $template, true, $title, $errors, $status, $more );
	}

	function html_failure( $template = 'error', $title = null, $errors = null, $status = 400, $more = null ) {
		html_response( $template, false, $title, $errors, $status, $more );
	}

	function get_resource_version() {
		return ( true == DEBUG ) ? md5( time() ) : VERSION;
	}

	function cli_echo( $input ) {
		if ( is_cli() ) {
			print_r( $input );
			echo "\r\n";
		}
	}

	function cli_response( $data = null, $success = false, $message = null, $errors = null, $status = 200, $more = null ) {
		$title = ( true == $success ) ? 'Operation Successful' : 'Operation Failed';
		$output = "\r\n" . '=================================' . "\r\n" . $title . "\r\n" . '---------------------------------';
		cli_echo( $output );
		$out = '';
		if ( ! is_empty( $message ) ) {
			$out .= sprintf( 'Message: "%s"' . "\r\n", $message );
		}
		if ( can_loop( $data ) ) {
			$out .= print_r( $data, true ) . "\r\n";
		}
		if ( can_loop( $errors ) ) {
			$out .= 'Errors:' . "\r\n";
			foreach ( $errors as $index => $error ) {
				$out .= sprintf( 'Error %d: %s' . "\r\n", $index, $error );
			}
		}
		if ( true == DEBUG ) {
			$trace = debug_backtrace();
			$lines = array();
			if ( can_loop( $trace ) ) {
				foreach ( $trace as $t ) {
					if ( false === strpos( get_array_key( 'file', $t ), 'feedback.php' ) ) {
						array_push( $lines, sprintf( '%s LINE %d', get_array_key( 'file', $t ), get_array_key( 'line', $t ) ) );
					}
				}
			}
			if ( can_loop( $lines ) ) {
				$out .= "\r\n\r\n";
				$out .= $lines[0];
			}
		}
		$out .= "\r\n" . '=================================';
		cli_echo( $out );
		do_action( 'api-shutdown' );
		exit();
	}

	function cli_success( $data, $message = null, $errors = null, $status = 200, $more = null ) {
		cli_response( $data, true, $message, $errors, $status, $more );
	}

	function cli_failure( $data = null, $message = null, $errors = null, $status = 400, $more = null ) {
		cli_response( $data, false, $message, $errors, $status, $more );
	}

	function cli_debug( $content ) {
		cli_failure( null, print_r( $content, true ), array(
			'Debug',
		) );
	}

	function tc_get_page_specific_scripts( $template = null ) {
		$f = sprintf( '%s/resources/page-specific/%s/js.js', ABSPATH, $template );
		if ( file_exists( $f ) ) {
			$url = sprintf( '/resources/page-specific/%s/js.js?v=%s', $template, get_resource_version() );
		}
		if ( isset( $url ) ) {
			echo sprintf( '<script type="text/javascript" src="%s"></script>', $url );
		}
	}

	function tc_get_page_specific_styles( $template = null ) {
		$f = sprintf( '%s/resources/page-specific/%s/style.css', ABSPATH, $template );
		if ( file_exists( $f ) ) {
			$url = sprintf( '/resources/page-specific/%s/style.css?v=%s', $template, get_resource_version() );
		}
		if ( isset( $url ) ) {
			echo sprintf( '<link rel="stylesheet" href="%s">', $url );
		}
	}
