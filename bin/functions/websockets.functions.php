<?php
	use ElephantIO\Client,
		ElephantIO\Engine\SocketIO\Version1X;
	defined( 'ABSPATH' ) || die( 'Sorry, but you cannot access this page directly.' );

	function get_streamer() {
		try {
			$host = sprintf( 'http://%s/socket.io/', WEBSOCKETSERVER );
			$streamer = new Client( new Version1X( $host ) );
			$streamer->initialize();
		}
		catch( Exception $e ) {
			trigger_error( $e->getMessage() );
			$streamer = false;
		}
		return $streamer;
	}

	function streamer_emit( $eventName, $data = array() ) {
		$streamer = get_streamer();
		if ( is_object( $streamer ) ) {
			try {
				return $streamer->emit( $eventName, $data );
			}
			catch( Exception $e ) {
				trigger_error( $e->getMessage() );
				return false;
			}
		}
		return false;
	}