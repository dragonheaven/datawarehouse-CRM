<?php
	if ( class_exists( '\RedBeanPHP\BeanHelper\SimpleFacadeBeanHelper' ) ) {
		class SWMFBeanHelper extends \RedBeanPHP\BeanHelper\SimpleFacadeBeanHelper {
			public function getModelForBean( \RedBeanPHP\OODBBean $bean ) {
				$type = $bean->getMeta( 'type' );
				$original_bean_name = str_replace( DBPREF, '', $type );
				$model_name = sprintf( '%s%s', ucfirst( 'model_' ), ucfirst( strtolower( $original_bean_name ) ) );
				if ( defined( 'REDBEAN_MODEL_PREFIX' ) ) {
					$full_class_name = sprintf( '%s%s', REDBEAN_MODEL_PREFIX, $model_name );
				}
				else {
					$full_class_name = $model_name;
				}
				if ( class_exists( $full_class_name ) ) {
					$return = new $full_class_name;
				}
				else {
					return null;
				}
				$return->loadBean( $bean );
				return $return;
			}
		}
	}