<?php
	defined( 'ABSPATH' ) || die( 'Sorry, but you cannot access this page directly.' );

	class Model_language extends RedBean_SimpleModel {
		public static function get_unique_index_key() {
			return 'lang';
		}

		public static function getFilterableAttributes() {
			$fo = array();
			try {
				$for = R::getCol( 'SELECT DISTINCT( lang ) FROM language ORDER BY lang ASC' );
				if ( can_loop( $for ) ) {
					foreach ( $for as $f ) {
						$fo[ $f ] = $f;
					}
				}
			}
			catch( Exception $e ) {
				if ( true == DEBUG ) {
					ajax_failure( $e->getMessage() );
				}
			}
			$return = array(
				'lang' => array(
					'description' => 'Value',
					'filtertype' => 'text',
					'filteroptions' => $fo,
					'filterconditions' => array(
						'=' => 'Equals',
						'<>' => 'Different Than',
						'%_%' => 'Contains Text',
						'%_' => 'Begins With',
						'_%' => 'Ends With',
						'!%_%' => 'Does not Contain',
						'!%_' => 'Does not Begin With',
						'!_%' => 'Does not End With',
						'()' => 'In List',
						'!()' => 'Not In List',
						'!NULL!' => 'Is Blank',
						'!NOTNULL!' => 'Is Not Blank',
					),
				),
			);
			return $return;
		}

		public function getTableIndex() {
			return '';
		}
	}