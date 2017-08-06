<?php
	defined( 'ABSPATH' ) || die( 'Sorry, but you cannot access this page directly.' );

	function tc_cron_cache_objects() {
		$cacheable = array(
			'phone',
			'email',
			'ip',
			'source',
			'language',
			'skype',
		);
		cli_echo( 'Starting to Cache Objects' );
		foreach ( $cacheable as $beanType ) {
			cli_echo( sprintf( 'Starting to work on "%s" Bean Type', $beanType ) );
			$fieldModelClass = sprintf( '%sFieldModel', $beanType );
			$cacheKeyTemplate = sprintf( '%s_valcache_%s', $beanType, '%s' );
			if ( class_exists( $fieldModelClass, false ) ) {
				try {
					$collection = R::findCollection( $beanType );
				}
				catch ( Exception $e ) {
					cli_echo( sprintf( 'Exception: %s', $e->getMessage() ) );
				}
			}
			else {
				cli_echo( sprintf( 'Cannot find class "%s"', $fieldModelClass ) );
			}
			if ( isset( $collection ) && is_a( $collection, 'RedBeanPHP\BeanCollection' ) ) {
				cli_echo( sprintf( 'Starting to loop through "%s" records', $beanType ) );
				while( $bean = $collection->next() ) {
					$cacheKey = sprintf( $cacheKeyTemplate, $bean->id );
					if ( '!NOCACHE!' == cache_get( $cacheKey, '!NOCACHE!' ) ) {
						switch ( $beanType ) {
							case 'phone':
								$val = $bean->number;
								break;

							case 'email':
								$val = $bean->email_raw;
								break;

							case 'ip':
								$val = $bean->ip;
								break;

							case 'source':
								$val = $bean->source;
								break;

							default:
								$val = $bean->id;
								break;
						}
						cache_set( $cacheKey, $val );
						cli_echo( sprintf( 'Set caching for bean %d of type "%s"', $bean->id, $beanType ) );
					}
					else {
						cli_echo( sprintf( 'Bean %d of type "%s" is already cached', $bean->id, $beanType ) );
					}
				}
			}
		}
	}