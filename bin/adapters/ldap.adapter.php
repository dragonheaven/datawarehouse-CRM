<?php
	defined( 'ABSPATH' ) || die( 'Sorry, but you cannot access this page directly.' );
	// Adapted from instructions found at url:
	// https://www.exchangecore.com/blog/how-use-ldap-active-directory-authentication-php/

	class tcldap {
		const no_connection = 0;
		const valid_user = 1;
		const bad_creds = 2;
		const no_such_user = 3;
		const not_allowed_from_ip = 4;
		const not_in_group = false;
		const in_group = true;

		private $server = 'ldap://ad.tcldb.cloud';
		private $ldap;
		private $user;
		private $pass;
		private $rpn;
		private $bind;

		function __construct( $user = null, $pass = null ) {
			$this->user = $user;
			$this->pass = $pass;
			$this->rpn = sprintf( 'tcldb\\%s', $this->user );
			try {
				$this->ldap = ldap_connect( $this->server );
			}
			catch ( Exception $e ) {
				trigger_error( sprintf( 'Could not connected to LDAP server: %s', $e->getMessage() ) );
			}
			if ( ! is_null( $this->ldap ) ) {
				ldap_set_option( $this->ldap, LDAP_OPT_PROTOCOL_VERSION, 3 );
    			ldap_set_option( $this->ldap, LDAP_OPT_REFERRALS, 0 );
    			$this->bind = @ldap_bind( $this->ldap, $this->rpn, $this->pass );
			}
		}

		private function rebind() {
			if ( ! is_null( $this->ldap ) ) {
				ldap_set_option( $this->ldap, LDAP_OPT_PROTOCOL_VERSION, 3 );
    			ldap_set_option( $this->ldap, LDAP_OPT_REFERRALS, 0 );
    			$this->bind = @ldap_bind( $this->ldap, $this->rpn, $this->pass );
			}
		}

		public function is_active() {
			return ( ! is_null( $this->bind ) && false !== $this->bind );
		}

		public function get_person_info( $user, $return = 'person' ) {
			$this->rebind();
			if ( is_empty( $user ) ) {
				return self::bad_creds;
			}
			if ( ! $this->is_active() ) {
				return self::no_connection;
			}
			$filter = sprintf( '(sAMAccountName=%s)', $user );
			try {
				$res = ldap_search( $this->ldap, 'OU=Accounts,DC=tcldb,DC=cloud', $filter );
			}
			catch ( Exception $e ) {}
			if ( isset( $res ) ) {
				ldap_sort( $this->ldap, $res, 'sn' );
			}
			try {
				$info = ldap_get_entries( $this->ldap, $res );
			}
			catch ( Exception $e ) {}
			if ( isset( $info ) ) {
				for ( $i=0; $i < $info['count']; $i++ ) {
					$u = $info[ $i ];
					unset( $u['objectclass']['count'] );
					unset( $u['memberof']['count'] );
					$person = new tcuser( $this->ldap, array(
						'objectclass' => implode( ',', $u['objectclass'] ),
						'cn' => ( array_key_exists( 'cn', $u ) ) ? $u['cn'][0] : null,
						'sn' => ( array_key_exists( 'sn', $u ) ) ? $u['sn'][0] : null,
						'title' => ( array_key_exists( 'title', $u ) ) ? $u['title'][0] : null,
						'description' => ( array_key_exists( 'description', $u ) ) ? $u['description'][0] : null,
						'physicaldeliveryofficename' => ( array_key_exists( 'physicaldeliveryofficename', $u ) ) ? $u['nullphysicaldeliveryofficename'][0] : null,
						'distinguishedname' => ( array_key_exists( 'distinguishedname', $u ) ) ? $u['distinguishedname'][0] : null,
						'displayname' => ( array_key_exists( 'displayname', $u ) ) ? $u['displayname'][0] : null,
						'memberof' => ( array_key_exists( 'memberof', $u ) ) ? $u['memberof'] : null,
						'department' => ( array_key_exists( 'department', $u ) ) ? $u['department'][0] : null,
						'company' => ( array_key_exists( 'company', $u ) ) ? $u['company'][0] : null,
						'badpasswordtime' => ( array_key_exists( 'badpasswordtime', $u ) ) ? $u['badpasswordtime'][0] : null,
						'lastlogoff' => ( array_key_exists( 'lastlogoff', $u ) ) ? $u['lastlogoff'][0] : null,
						'pwdlastset' => ( array_key_exists( 'pwdlastset', $u ) ) ? $u['pwdlastset'][0] : null,
						'primarygroupid' => ( array_key_exists( 'primarygroupid', $u ) ) ? $u['primarygroupid'][0] : null,
						'samaccountname' => ( array_key_exists( 'samaccountname', $u ) ) ? $u['samaccountname'][0] : null,
						'userprincipalname' => ( array_key_exists( 'userprincipalname', $u ) ) ? $u['userprincipalname'][0] : null,
						'objectcategory' => ( array_key_exists( 'objectcategory', $u ) ) ? $u['objectcategory'][0] : null,
						'mail' => ( array_key_exists( 'mail', $u ) ) ? $u['mail'][0] : null,
						'lastlogontimestamp' => ( array_key_exists( 'lastlogontimestamp', $u ) ) ? $u['lastlogontimestamp'][0] : null,
						'dn' => ( array_key_exists( 'dn', $u ) ) ? $u['dn'] : null,
					) );
				}
			}
			if ( isset( $person ) && is_a( $person, 'tcuser' ) && count( $person ) > 0 ) {
				switch ( $return ) {
					case 'person':
						return $person;
						break;

					default:
						return self::valid_user;
						break;
				}
			}
			return self::no_such_user;
		}

		/**
		 * Validate a username & password combination to allow access to the systsem
		 * @param  string $user     The requested username
		 * @param  string $password The requested password
		 * @param  string $return   The type of output desired. Options are: bool, person. Default: bool
		 * @return ?           If the user is valid, the requested feedback type. Otherwise, an interger specifying the type of error
		 */
		public function validate_user( $user = null, $password = null, $return = 'bool', $ip = null ) {
			if ( is_empty( $ip ) ) {
				$ip = get_request_ip();
			}
			if ( is_empty( $user ) || is_empty( $password ) ) {
				return self::bad_creds;
			}
			$rpn = sprintf( 'tcldb\\%s', $user );
			try {
				$bind = @ldap_bind( $this->ldap, $rpn, $password );
			}
			catch ( Exception $e ) {
				return self::no_such_user;
			}
			if ( true !== $bind ) {
				return self::no_such_user;
			}
			$person = $this->get_person_info( $user, $return );
			if ( ! is_a( $person, 'tcuser' ) ) {
				return $person;
			}
			return ( $person->can_login_from_current_ip( $ip ) ) ? $person : self::not_allowed_from_ip;
		}

		/**
		 * Check if user is in active directory group
		 * @param  tcuser   $person Person array returned from tcldap::validate_credentials
		 * @param  string  $group  The DN of the group
		 * @return boolean         If the user is in the group or not. Also returns false if the group doesn't exist.
		 */
		public function is_user_in_group( tcuser $person, $group = null ) {
			if ( is_empty( $group ) ) {
				return self::not_in_group;
			}
			$gs = sprintf( 'CN=%s,OU=User Roles,DC=tcldb,DC=cloud', $group );
			$ret = self::not_in_group;
			if ( can_loop( get_object_property( 'memberof', $person ) ) ) {
				foreach ( get_object_property( 'memberof', $person ) as $gpn ) {
					if ( self::not_in_group == $ret ) {
						$ret = ( $gpn == $gs ) ? self::in_group : self::not_in_group;
					}
				}
			}
		}
	}

	class tcuser extends stdClass {
		const sysadmin = 'SysAdmin';
		const call_center = 'Call Center Users';
		const marketer = 'Marketer Users';
		const dashboard = 'Dashboard Users';
		const warehouse = 'Warehouse Users';

		public $objectclass;
		public $cn;
		public $sn;
		public $title;
		public $description;
		public $physicaldeliveryofficename;
		public $distinguishedname;
		public $displayname;
		public $memberof;
		public $department;
		public $company;
		public $badpasswordtime;
		public $lastlogoff;
		public $pwdlastset;
		public $primarygroupid;
		public $samaccountname;
		public $userprincipalname;
		public $objectcategory;
		public $mail;
		public $lastlogontimestamp;
		public $dn;

		private $ldap;

		function __construct( $ldap, $params = array() ) {
			if ( can_loop( $params ) ) {
				foreach ( $params as $key => $value ) {
					$this->{ $key } = $value;
				}
			}
			$this->ldap = $ldap;
		}

		public function can_login_from_current_ip( $ip = null ) {
			if ( is_empty( $ip ) ) {
				$ip = get_request_ip();
			}
			if ( $this->in_group( self::sysadmin ) ) {
				return true;
			}
			if ( 'all' == $this->description ) {
				return true;
			}
			else {
				$ips = explode( ',', $this->description );
				if ( can_loop( $ips ) ) {
					foreach ( $ips as $i ) {
						if ( false !== strpos( $i, '/' ) ) {
							if ( in_cidr( $ip, $i ) ) {
								return true;
							}
						}
						else {
							if ( $i == $ip ) {
								return true;
							}
						}
					}
				}
			}
			return false;
		}

		public function can_login_to_site() {
			$group = sprintf( 'CN=%s_permitted,OU=Websites,DC=tcldb,DC=cloud', LICENSE_SITE );
			if ( true == $this->in_group( self::sysadmin ) ) {
				return true;
			}
			return ( can_loop( $this->memberof ) && in_array( $group, $this->memberof ) );
		}

		public function in_group( $group ) {
			return ( can_loop( $this->memberof ) && in_array( sprintf( 'CN=%s,OU=User Roles,DC=tcldb,DC=cloud', $group ), $this->memberof ) );
		}
	}