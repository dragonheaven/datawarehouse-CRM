<?php
	defined( 'ABSPATH' ) || die( 'Sorry, but you cannot access this page directly.' );

	class Model_source extends RedBean_SimpleModel {
		public static function getFilterableAttributes() {
			$return = array(
				'source' => array(
					'description' => 'Source',
					'filtertype' => 'text',
					'filteroptions' => array(),
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