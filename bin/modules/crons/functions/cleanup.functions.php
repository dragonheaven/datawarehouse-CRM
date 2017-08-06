<?php
	defined( 'ABSPATH' ) || die( 'Sorry, but you cannot access this page directly.' );

	function tc_cron_cleanup_phones() {
		cli_echo( 'Cleaning up invalid phone numbers' );
		$successes = 0;
		try {
			$badPhones = R::getCol( "SELECT id FROM phone WHERE country_code = 'XX' OR number LIKE '' OR valid = 0 OR country_name = 'Unknown'" );
			$count = count( $badPhones );
		}
		catch ( Exception $e ) {
			cli_echo( sprintf( 'Exception: %s', $e->getMessage() ) );
			$count = 0;
		}
		if ( $count > 0 ) {
			cli_echo( sprintf( 'Found %d invalid phone numbers', $count ) );
			$rc = 0;
			foreach ( $badPhones as $pid ) {
				$rc ++;
				cli_echo( sprintf( 'Removing #%d invalid phone with ID %d', $rc, $pid ) );
				try {
					$p = R::cachedLoad( 'phone', $pid );
					R::trash( $p );
					$successes ++;
					cli_echo( sprintf( 'Deleted #%d invalid phone ID %d', $rc, $pid ) );
				}
				catch( Exception $e ) {
					cli_echo( sprintf( 'Exception: %s', $e->getMessage() ) );
				}
			}
		}
		else {
			cli_echo( 'No invalid phone numbers found' );
		}
		cli_success( null, sprintf( 'Cleaned up %d invalid phone numbers', $successes ) );
	}

	function tc_cron_cleanup_emails() {
		cli_echo( 'Cleaning up invalid email addresses' );
		$successes = 0;
		try {
			$badEmails = R::getCol( "SELECT id FROM email WHERE email_raw = '' OR email_raw IS NULL OR valid_format = 0" );
			$count = count( $badEmails );
		}
		catch ( Exception $e ) {
			cli_echo( sprintf( 'Exception: %s', $e->getMessage() ) );
			$count = 0;
		}
		if ( $count > 0 ) {
			cli_echo( sprintf( 'Found %d invalid email addresses', $count ) );
			$rc = 0;
			foreach ( $badEmails as $eid ) {
				$rc ++;
				cli_echo( sprintf( 'Removing #%d email address with ID %d', $rc, $eid ) );
				try {
					$p = R::cachedLoad( 'email', $eid );
					R::trash( $p );
					$successes ++;
					cli_echo( sprintf( 'Deleted #%d email address ID %d', $rc, $eid ) );
				}
				catch( Exception $e ) {
					cli_echo( sprintf( 'Exception: %s', $e->getMessage() ) );
				}
			}
		}
		else {
			cli_echo( 'No invalid email addresses found' );
		}
		cli_success( null, sprintf( 'Cleaned up %d invalid email addresses', $successes ) );
	}

	function tc_cron_cleanup_duplicates() {
		cli_echo( 'Cleaning up duplicated leads' );
		if ( table_exists( 'phone' ) ) {
			$phonedupequery = 'SELECT COUNT(lead_phone.id) AS count, lead_phone.phone_id FROM lead_phone GROUP BY lead_phone.phone_id HAVING COUNT(lead_phone.id) > 1 ORDER BY COUNT(lead_phone.id) DESC';
			cli_echo( 'Looking for duplicates by phone number' );
			try {
				$dupes = R::getAll( $phonedupequery );
				if ( can_loop( $dupes ) ) {
					cli_echo( sprintf( 'Found %d Phone Numbers with Duplicates', count( $dupes ) ) );
					foreach ( $dupes as $d ) {
						$phone = get_array_key( 'phone_id', $d, 0 );
						if ( absint( $phone ) > 0 ) {
							$p = R::load( 'phone', absint( $phone ) );
							$leads = $p->sharedLeadList;
							$count = 0;
							foreach ( $leads as $lead ) {
								if ( $count >= 1 ) {
									cli_echo( sprintf( 'Breaking Association between phone %d and lead %d', $phone, $lead->id ) );
									unset( $lead->sharedPhoneList[ absint( $phone ) ] );
									R::store( $lead );
									cli_echo( sprintf( 'Association between phone %d and lead %d broken', $phone, $lead->id ) );
								}
								else {
									cli_echo( sprintf( 'Association betwwen phone %d and lead %d kept', $phone, $lead->id ) );
								}
								$count ++;
							}
						}
					}
					cli_echo( 'No duplicate phone numbers found' );
				}
			}
			catch( Exception $e ) {
				cli_echo( 'Failed to find any duplicates by phone number:' );
				cli_echo( $e->getMessage() );
			}
			cli_echo( 'No more duplicates by phone number' );
		}
		if ( table_exists( 'email' ) ) {
			$emaildupequery = 'SELECT COUNT(email_lead.id) AS count, email_lead.email_id FROM email_lead GROUP BY email_lead.email_id HAVING count > 1 ORDER BY COUNT(email_lead.id) DESC;';
			cli_echo( 'Looking for duplicates by email' );
			try {
				$dupes = R::getAll( $emaildupequery );
				if ( can_loop( $dupes ) ) {
					cli_echo( sprintf( 'Found %d emails with Duplicates', count( $dupes ) ) );
					foreach ( $dupes as $d ) {
						$email = get_array_key( 'email_id', $d, 0 );
						if ( absint( $email ) > 0 ) {
							$p = R::load( 'email', absint( $email ) );
							$leads = $p->sharedLeadList;
							$count = 0;
							foreach ( $leads as $lead ) {
								if ( $count >= 1 ) {
									cli_echo( sprintf( 'Breaking Association between email %d and lead %d', $email, $lead->id ) );
									unset( $lead->sharedEmailList[ absint( $email ) ] );
									R::store( $lead );
									cli_echo( sprintf( 'Association between email %d and lead %d broken', $email, $lead->id ) );
								}
								else {
									cli_echo( sprintf( 'Association betwwen email %d and lead %d kept', $email, $lead->id ) );
								}
								$count ++;
							}
						}
					}
					cli_echo( 'No duplicate emails found' );
				}
			}
			catch( Exception $e ) {
				cli_echo( 'Failed to find any duplicates by email:' );
				cli_echo( $e->getMessage() );
			}
			cli_echo( 'No more duplicates by email' );
		}
		// now we need to find all leads which have neither a phone number nor an email address
		$uselessquery = "SELECT lead.id, email_lead.email_id, lead_phone.phone_id FROM lead LEFT JOIN email_lead ON email_lead.lead_id = lead.id LEFT JOIN lead_phone ON lead_phone.lead_id = lead.id WHERE ( email_lead.email_id = 0 OR email_lead.email_id IS NULL ) AND ( lead_phone.phone_id = 0 OR lead_phone.phone_id IS NULL );";
		cli_echo( 'Looking for useless leads' );
		try {
			$useless = R::getAll( $uselessquery );
			if ( can_loop( $useless ) ) {
				foreach ( $useless as $info ) {
					$lid = get_array_key( 'id', $info, 0 );
					if ( absint( $lid ) > 0 ) {
						$lead = R::load( 'lead', absint( $lid ) );
						cli_echo( sprintf( 'Deleting Useless Lead with ID %d', $lead->id ) );
						R::trash( $lead );
						cli_echo( sprintf( 'Deleted Useless Lead with ID %d', $lead->id ) );
					}
				}
				cli_echo( sprintf( 'Found %d useless leads', count( $useless ) ) );
			}
			else {
				cli_echo( 'No useless leads found' );
			}
		}
		catch ( Exception $e ) {
			cli_echo( 'Failed to find any useless leads:' );
				cli_echo( $e->getMessage() );
		}
		cli_success( null, 'Duplicates Cleaned' );
	}