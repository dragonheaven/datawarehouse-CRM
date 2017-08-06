<?php
	/**
	 * TCAPI Class
	 * Class used for interacting with Tacticlicks Whitelabel API
	 * @Author: Jak Giveon <jak@tacticlicks.com>
	 * Requires: HTTP_REQUEST by Jak Giveon <jak@tacticlicks.com> https://gist.github.com/bdbjack/87ef84f53cf5f5f9e6f448fffb0072ac
	 *
	 * Usage example:
	 * $wl = new TCAPI( 'domain.com', 'XXXXXXXXXXXXXX', 'contact@domain.com', 'somesecuredpassword' );
	 * $qr = $wl->query( 'someCommand', array( 'var1' => 1, 'var2' => 2 ) );
	 */

	class TCAPI {
		private $domain;
		private $token;
		private $authtoken;
		private $serverDomain;

		function __construct( $domain, $token, $username, $password, $server_domain = 'server.tacticlicks.com' ) {
			$this->domain = $domain;
			$this->token = $token;
			$this->serverDomain = $server_domain;
			$authquery = HTTP_REQUEST::POST( sprintf( 'http://%s/ws/whitelabel.php', $this->domain ), array(
				'email' => $username,
				'password' => $password,
				'command' => 'login',
				'authToken' => $this->token,
			) );
			if ( 200 == $authquery->code ) {
				$this->authtoken = $authquery->data->data->token;
			}
		}

		function query( $command = '', $query = array() ) {
			if ( ! can_loop( $query ) ) {
				$query = array();
			}
			$query['command'] = $command;
			$query['token'] = $this->authtoken;
			$res = HTTP_REQUEST::POST( sprintf( 'http://%s/ws/whitelabel.php', $this->domain ), $query, array(
				'Content-Type: application/x-www-form-urlencoded',
			), 10 );
			add_log_entry( 'tcquery', array(
				'query' => serialize( $query ),
				'code' => $res->code,
				'data' => serialize( $res->data ),
			) );
			if ( 200 !== $res->code ) {
				return false;
			}
			return $res->data;
		}

		function project_query( $command = '', $query = array(), $authkey = '' ) {
			if ( ! can_loop( $query ) ) {
				$query = array();
			}
			$query['command'] = $command;
			$query['token'] = $this->authtoken;
			$query['auth_key'] = $authkey;
			$res = HTTP_REQUEST::POST( sprintf( 'http://%s/api/project.php', $this->serverDomain ), $query, array(
				'Content-Type: application/x-www-form-urlencoded',
			), 10 );
			add_log_entry( 'tcpquery', array(
				'query' => serialize( $query ),
				'code' => $res->code,
				'data' => serialize( $res->data ),
			) );
			//if ( 200 !== $res->code ) {
			//	return false;
			//}
			return $res->data;
		}
	}