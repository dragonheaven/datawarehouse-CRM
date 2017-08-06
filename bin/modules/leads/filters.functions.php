<?php
	defined( 'ABSPATH' ) || die( 'Sorry, but you cannot access this page directly.' );

	function tc_get_filter_function_for_field( $field, $default = 'tc_filter_text_field' ) {
		global $tc_fields;
		$field = get_array_key( $field, $tc_fields, array() );
		return get_array_key( 'filterFunction', $field, $default );
	}

	function tc_filter_fields_deep( $data, $lead = null ) {
		global $tc_fields;
		unset( $data['None'] );
		if ( can_loop( $data ) ) {
			foreach ( $data as $key => $d ) {
				$fixed = tc_fix_fieldname( $key );
				if ( $fixed !== $key ) {
					unset( $data[ $key ] );
					$key = $fixed;
				}
				$merge = array();
				$filter = tc_get_filter_function_for_field( $key );
				if ( is_array( $d ) ) {
					$nd = array();
					foreach ( $d as $od ) {
						array_push( $nd, call_user_func( $filter, $od, true ) );
					}
				}
				else {
					$nd = call_user_func( $filter, $d, true );
				}
				if ( is_array( $nd ) ) {
					foreach ( $nd as $nk => $nv ) {
						if ( can_loop( $nv ) ) {
							foreach ( $nv as $nvk => $nvv ) {
								$f = tc_get_filter_function_for_field( $nvk, $filter );
								$nd[ $nvk ] = call_user_func( $f, $nvv );
								if ( array_key_exists( $nvk, $tc_fields ) ) {
									$merge[ $nvk ] = $nd[ $nvk ];
								}
							}
							unset( $nd[ $nk ] );
						}
						else {
							$f = tc_get_filter_function_for_field( $nk, $filter );
							$nd[ $nk ] = call_user_func( $f, $nv );
							if ( array_key_exists( $nk, $tc_fields ) ) {
								$merge[ $nk ] = $nd[ $nk ];
							}
						}
					}
				}
				if ( can_loop( $merge ) ) {
					unset( $data[ $key ] );
					$rnd = deep_array_merge( $nd, $merge );
					$data = deep_array_merge( $data, $rnd );
				}
				else {
					$data[ $key ] = $nd;
				}
			}
		}
		if ( can_loop( $data ) ) {
			if ( class_exists( 'fieldModelAbstract', false ) ) {
				foreach ( $data as $key => $value ) {
					$cc = sprintf( '%sFieldModel', $key );
					if ( class_exists( $cc, false ) ) {
						$cl = new $cc( $value, $data );
						$data[ $key ] = $cl->convert( $lead );
					}
					if ( is_array( $data[ $key ] ) ) {
						$nd = array();
						foreach ( $data[ $key ] as $item ) {
							if ( is_a( $item, 'RedBeanPHP\OODBBean' ) ) {
								$item = $item->export();
							}
							array_push( $nd, $item );
						}
						$data[ $key ] = $nd;
					}
					else {
						if ( is_a( $data[ $key ], 'RedBeanPHP\OODBBean' ) ) {
							$data[ $key ] = $data[ $key ]->export();
						}
					}
				}
			}
		}
		if ( can_loop( $data ) ) {
			foreach ( $data as $key => $value ) {
				$ok = $key;
				$key = tc_fix_fieldname( $key );
				if ( $ok !== $key ) {
					unset( $data[ $ok ] );
				}
				$data[ $key ] = $value;
			}
		}
		$canarray = array();
		if ( can_loop( $data ) ) {
			foreach ( $data as $key => $value ) {
				$canarray[ $key ] = tc_key_can_array( $key );
				if ( is_array( $value ) && can_loop( $value ) ) {
					$value = @array_unique( $value, SORT_REGULAR );
				}
				if ( ! tc_key_can_array( $key ) && is_array( $value ) && count( $value ) == 1 ) {
					$data[ $key ] = $value[0];
				}
				else {
					$data[ $key ] = $value;
				}
			}
		}
		return $data;
	}

	function tc_filter_absint( $input, $deep = false ) {
		return absint( $input );
	}

	function tc_filter_text_field( $input, $deep = false ) {
		$input = trim( $input );
		$filtered = do_action( 'filter_text_field', $input );
		if ( ! is_empty( $filtered ) ) {
			$input = $filtered;
		}
		$return = htmlentities( strip_tags( $input ) );
		return utf8_encode( $return );
	}

	function tc_filter_language_field( $input, $deep = false ) {
		$input = trim( $input );
		$filtered = do_action( 'tc_filter_language_field', $input );
		if ( ! is_empty( $filtered ) ) {
			$input = $filtered;
		}
		$input = strtoupper( $input );
		if ( false === strpos( $input, '-' ) ) {
			$input = substr( $input, 0, 2 );
		}
		$return = htmlentities( strip_tags( $input ) );
		return utf8_encode( $return );
	}

	function tc_filter_ip_address( $input, $deep = false ) {
		$input = trim( $input );
		$output = $input;
		if ( 3 == substr_count( $output, '.' ) ) {
			$output = preg_replace( '/[^0-9\.]/', '', $output );
		}
		else {
			$output = preg_replace( '/[^0-9a-fA-F:]/', '', $output );
		}
		$filtered = do_action( 'filter_ip_field', $output );
		if ( ! is_empty( $filtered ) ) {
			$output = $filtered;
		}
		return filter_var( $output, FILTER_VALIDATE_IP, FILTER_NULL_ON_FAILURE );
	}

	function tc_filter_salutation( $input, $deep = false ) {
		$input = trim( $input );
		$output = $input;
		$output = utf8_decode( $output );
		$output = trim( $output );
		$output = preg_replace( '/[^a-zA-Z\_]/', '', $output );
		$output = str_replace( "\0", "", $output );
		$output = ucwords( strtolower( $output ) );
		$filtered = do_action( 'filter_salutation_field', $output );
		if ( ! is_empty( $filtered ) ) {
			$output = $filtered;
		}
		$return = ( is_empty( $output ) || 1 == strlen( $output ) ) ? null : (string) $output;
		return utf8_encode( $return );
	}

	function tc_filter_gender( $input, $deep = false ) {
		$input = trim( $input );
		$output = $input;
		$output = utf8_decode( $output );
		$output = trim( $output );
		$output = preg_replace( '/[^a-zA-Z\_]/', '', $output );
		$output = str_replace( "\0", "", $output );
		$output = strtolower( $output );
		$man = array(
			'm',
		);
		$woman = array(
			'f',
			'l',
			'w',
			's',
		);
		$gender = null;
		foreach ( $man as $letter ) {
			if ( is_empty( $gender ) && false !== strpos( $output, $letter ) ) {
				$gender = 'Male';
			}
		}
		foreach ( $woman as $letter ) {
			if ( is_empty( $gender ) && false !== strpos( $output, $letter ) ) {
				$gender = 'Female';
			}
		}
		if ( is_empty( $gender ) && ! is_empty( $output ) ) {
			$gender = 'Other';
		}
		$filtered = do_action( 'filter_salutation_field', $gender );
		if ( ! is_empty( $filtered ) ) {
			$gender = $filtered;
		}
		return utf8_encode( $gender );
	}

	function tc_filter_fullname( $input, $deep = false ) {
		if ( ! is_string( $input ) ) {
			$input = null;
		}
		$input = trim( $input );
		$first = null;
		$middle = null;
		$last = null;
		$parts = explode( ' ', $input );
		$return = '';
		if ( can_loop( $parts ) ) {
			if ( 1 == count( $parts ) ) {
				$first = $parts[0];
			}
			elseif ( 2 == count( $parts ) ) {
				list( $first, $last ) = $parts;
			}
			elseif ( 3 == count( $parts ) ) {
				list( $first, $middle, $last ) = $parts;
			}
			else {
				$first = array_shift( $parts );
				$last = array_pop( $parts );
				$middle = implode( ' ', $parts );
			}
		}
		if ( true == $deep ) {
			$return = array();
			if ( isset( $first ) && ! is_empty( $first ) ) {
				$return['fname'] = tc_filter_name_field( $first );
			}
			if ( isset( $middle ) && ! is_empty( $middle ) ) {
				$return['mname'] = tc_filter_name_field( $middle );
			}
			if ( isset( $last ) && ! is_empty( $last ) ) {
				$return['lname'] = tc_filter_name_field( $last );
			}
			return $return;
		}
		return sprintf( '<abbr class="label label-default" title="First Name">%s</abbr> <abbr class="label label-default" title="Middle Name">%s</abbr> <abbr class="label label-default" title="Last Name">%s</abbr>', tc_filter_name_field( $first ), tc_filter_name_field( $middle ), tc_filter_name_field( $last ) );
	}

	function tc_filter_fname_field( $input, $deep = false ) {
		$input = trim( $input );
		if ( 0 == substr_count( $input, ' ' ) ) {
			return tc_filter_name_field( $input, $deep );
		}
		$parts = explode( ' ', $input );
		$first = null;
		$middle = null;
		if ( can_loop( $parts ) ) {
			if ( 1 == count( $parts ) ) {
				$first = $part[0];
			}
			else {
				$first = array_shift( $parts );
				$middle = implode( ' ', $parts );
			}
		}
		if ( true == $deep ) {
			$return = array();
			if ( isset( $first ) && ! is_empty( $first ) ) {
				$return['fname'] = tc_filter_name_field( $first );
			}
			if ( isset( $middle ) && ! is_empty( $middle ) ) {
				$return['mname'] = tc_filter_name_field( $middle );
			}
			return $return;
		}
		return sprintf( '<abbr class="label label-default" title="First Name">%s</abbr> <abbr class="label label-default" title="Middle Name">%s</abbr>', tc_filter_name_field( $first ), tc_filter_name_field( $middle ) );
	}

	function tc_filter_name_field( $input, $deep = false ) {
		$input = trim( $input );
		$output = $input;
		$output = utf8_decode( $output );
		$output = str_replace( '?', '', $output );
		$output = trim( $output );
		$output = preg_replace( '/[^a-zA-Z\_\']/', '', $output );
		$output = str_replace( "\0", "", $output );
		$output = ucwords( strtolower( $output ) );
		$filtered = do_action( 'filter_name_field', $output );
		if ( ! is_empty( $filtered ) ) {
			$output = $filtered;
		}
		$return = (string) $output;
		return utf8_encode( $return );
	}

	function tc_filter_phone( $input, $deep = false ) {
		$input = trim( $input );
		$output = sanitize_phone( $input );
		$filtered = do_action( 'filter_phone_field', $output );
		if ( ! is_empty( $filtered ) ) {
			$output = $filtered;
		}
		return (string) $output;
	}

	function tc_filter_email( $input, $deep = false ) {
		$input = trim( $input );
		if ( ! is_string( $input ) ) {
			return null;
		}
		$input = strtolower( $input );
		$input = filter_var( $input, FILTER_SANITIZE_EMAIL );
		$filtered = do_action( 'filter_email_field', $input );
		if ( ! is_empty( $filtered ) ) {
			$input = $filtered;
		}
		if ( 1 !== substr_count( $input, '@' ) || 0 === substr_count( $input, '.' ) ) {
			$input = null;
		}
		return $input;
	}

	function tc_filter_country_field( $input, $deep = false ) {
		$input = trim( $input );
		$output = $input;
		$output = utf8_decode( $output );
		$output = str_replace( '?', '', $output );
		$output = trim( $output );
		$filtered = do_action( 'filter_country_field', $output );
		if ( ! is_empty( $filtered ) ) {
			$output = $filtered;
		}
		$res = COUNTRY_PARSER::GET_COUNTRY( $output );
		if ( 'XX' == $res ) {
			$res = null;
		}
		return $res;
	}

	function tc_filter_timezone_field( $input, $deep = false ) {
		$input = trim( $input );
		$output = $input;
		$filtered = do_action( 'filter_timezone_field', $output );
		if ( ! is_empty( $filtered ) ) {
			$output = $filtered;
		}
	}

	function tc_filter_datetime( $input, $deep = false ) {
		$input = trim( $input );
		$output = $input;
		$filtered = do_action( 'filter_datetime_field', $output );
		if ( ! is_empty( $filtered ) ) {
			$output = $filtered;
		}
		$time = strtotime( $output );
		if ( $time <= 0 ) {
			return null;
		}
		if ( $time !== 0 ) {
			return date( 'Y-m-d H:i:s', $time );
		}
		return null;
	}

	function tc_filter_address_field( $input, $deep = false ) {
		$input = trim( $input );
		$output = $input;
		$output = utf8_decode( $output );
		$output = str_replace( '?', '', $output );
		$output = str_replace( "\0", "", $output );
		$output = str_replace( "\\", "", $output );
		if ( strlen( $output ) < 2 ) {
			$output = null;
		}
		$output = ucwords( strtolower( $output ) );
		$filtered = do_action( 'filter_address_field', $output );
		if ( ! is_empty( $filtered ) ) {
			$output = $filtered;
		}
		return utf8_encode( $output );
	}

	function tc_filter_postal_field( $input, $deep = false ) {
		$input = trim( $input );
		$output = $input;
		$output = utf8_decode( $output );
		$output = str_replace( '?', '', $output );
		$output = str_replace( "\0", "", $output );
		$output = ucwords( strtolower( $output ) );
		$filtered = do_action( 'filter_postal_field', $output );
		if ( ! is_empty( $filtered ) ) {
			$output = $filtered;
		}
		$return = strtoupper( $output );
		return utf8_encode( $return );
	}

	function ajax_get_filter_options_for_field( $data ) {
		$field = get_array_key( 'field', $data, null );
		if ( 'meta' == $field ) {
			$field = 'leadmeta';
		}
		$attribute = get_array_key( 'attr', $data, null );
		$condition = get_array_key( 'condition', $data, '=' );
		if ( is_empty( $field ) ) {
			ajax_failure( 'No Field Chosen' );
		}
		global $tc_fields;
		$filterType = get_array_key( 'filtertype', get_array_key( $field, $tc_fields, array() ), 'none' );
		if ( is_empty( $filterType ) || 'none' == $filterType ) {
			ajax_failure( 'Field cannot be used as a filter' );
		}
		$return = array(
			'attributes' => array(),
			'conditions' => array(),
			'fieldhtml' => '',
		);
		$modelKey = sprintf( 'Model_%s', $field );
		if ( class_exists( $modelKey, false ) ) {
			$atts = $modelKey::getFilterableAttributes();
			if ( can_loop( $atts ) ) {
				foreach ( $atts as $key => $d ) {
					$return['attributes'][ $key ] = get_array_key( 'description', $d, 'Unknown' );
				}
			}
		}
		else {
			$atts = array(
				'value' => array(
					'description' => 'Value',
					'filtertype' => get_array_key( 'filtertype', get_array_key( $field, $tc_fields, array() ), array( '=' => 'Is' ) ),
					'filteroptions' => get_array_key( 'filteroptions', get_array_key( $field, $tc_fields, array() ), array( '=' => 'Is' ) ),
					'filterconditions' => get_array_key( 'filterconditions', get_array_key( $field, $tc_fields, array() ), array( '=' => 'Is' ) ),
				),
			);
			$return['attributes']['value'] = 'Value';
		}
		if ( array_key_exists( $attribute, $atts ) ) {
			$return['conditions'] = get_array_key( 'filterconditions', $atts[ $attribute ], array() );
			$options = get_array_key( 'filteroptions', $atts[ $attribute ], array() );
			$filterType = get_array_key( 'filtertype', $atts[ $attribute ], array() );
		}
		else {
			$return['conditions'] = get_array_key( 'filterconditions', get_array_key( $field, $tc_fields, array() ), array( '=' => 'Is' ) );
			$options = get_array_key( 'filteroptions', get_array_key( $field, $tc_fields, array() ), array() );
		}
		if ( ! isset( $options ) ) {
			$options = array();
		}
		$ahtml = '';
		$ahtml .= sprintf( '<select name="conditions[%d][attribute]" class="form-control" required>' . "\r\n", get_array_key( 'cid', $data, 0 ) );
		if ( can_loop( $return['attributes'] ) ) {
			foreach ( $return['attributes'] as $att => $des ) {
				$ahtml .= sprintf( '<option value="%s" %s>%s</option>' . "\r\n", $att, ( $att == $attribute ) ? 'selected' : '', $des );
			}
		}
		$ahtml .= '</select>';
		$return['ahtml'] = $ahtml;
		$chtml = '';
		$chtml .= sprintf( '<select name="conditions[%d][condition]" class="form-control" required>' . "\r\n", get_array_key( 'cid', $data, 0 ) );
		if ( can_loop( $return['conditions'] ) ) {
			foreach ( $return['conditions'] as $con => $des ) {
				$chtml .= sprintf( '<option value="%s" %s>%s</option>' . "\r\n", $con, ( $con == $condition ) ? 'selected' : '', $des );
			}
		}
		$chtml .= '</select>';
		$return['chtml'] = $chtml;
		$html = '';
		if ( ! is_array( $options ) && function_exists( $options ) ) {
			$options = call_user_func( $options );
		}
		switch ( $condition ) {
			case '()':
				if ( can_loop( $options ) ) {
					$html .= sprintf( '<select name="conditions[%d][filter][]" class="form-control multi-chosen" required multiple data-can-add="">' . "\r\n", get_array_key( 'cid', $data, 0 ) );
					foreach ( $options as $index => $val ) {
						if ( true === $val ) {
							$html .= sprintf( '	<option value="1" %s>True</option>' . "\r\n", ( 1 == get_array_key( 'filter', $data ) ) ? 'selected' : '' );
						}
						else if ( false === $val ) {
							$html .= sprintf( '	<option value="0" %s>False</option>' . "\r\n", ( 0 == get_array_key( 'filter', $data ) ) ? 'selected' : '' );
						}
						else {
							$html .= sprintf( '<option value="%s" %s>%s</option>', $index, ( $val == get_array_key( 'filter', $data ) ) ? 'selected' : '', $val );
						}
					}
					$html .= '</select>' . "\r\n";
				}
				else {
					$html .= sprintf( '<select name="conditions[%d][filter][]" class="form-control multi-chosen" required multiple data-can-add="1">' . "\r\n", get_array_key( 'cid', $data, 0 ) );
					$html .= '</select>' . "\r\n";
				}
				break;

			case '!()':
				if ( can_loop( $options ) ) {
					$html .= sprintf( '<select name="conditions[%d][filter][]" class="form-control multi-chosen" required multiple data-can-add="">' . "\r\n", get_array_key( 'cid', $data, 0 ) );
					foreach ( $options as $index => $val ) {
						if ( true === $val ) {
							$html .= sprintf( '	<option value="1" %s>True</option>' . "\r\n", ( 1 == get_array_key( 'filter', $data ) ) ? 'selected' : '' );
						}
						else if ( false === $val ) {
							$html .= sprintf( '	<option value="0" %s>False</option>' . "\r\n", ( 0 == get_array_key( 'filter', $data ) ) ? 'selected' : '' );
						}
						else {
							$html .= sprintf( '<option value="%s" %s>%s</option>', $index, ( $val == get_array_key( 'filter', $data ) ) ? 'selected' : '', $val );
						}
					}
					$html .= '</select>' . "\r\n";
				}
				else {
					$html .= sprintf( '<select name="conditions[%d][filter][]" class="form-control multi-chosen" required multiple data-can-add="1">' . "\r\n", get_array_key( 'cid', $data, 0 ) );
					$html .= '</select>' . "\r\n";
				}
				break;

			case '|-|':
				switch ( $filterType ) {
					case 'datetime':
						$html .= sprintf( '<div class="input-group">' . "\r\n" );
						$html .= sprintf( '	<input type="datetime-local" class="form-control" name="conditions[%d][filter][start]" required />' . "\r\n", get_array_key( 'cid', $data, 0 ) );
						$html .= sprintf( '	<span class="input-group-addon"> and </span>' . "\r\n" );
						$html .= sprintf( '	<input type="datetime-local" class="form-control" name="conditions[%d][filter][end]" required />' . "\r\n", get_array_key( 'cid', $data, 0 ) );
						$html .= sprintf( '</div>' . "\r\n" );
						break;

					default:
						$html .= sprintf( '<div class="input-group">' . "\r\n" );
						$html .= sprintf( '	<input type="number" class="form-control" name="conditions[%d][filter][start]" required />' . "\r\n", get_array_key( 'cid', $data, 0 ) );
						$html .= sprintf( '	<span class="input-group-addon"> and </span>' . "\r\n" );
						$html .= sprintf( '	<input type="number" class="form-control" name="conditions[%d][filter][end]" required />' . "\r\n", get_array_key( 'cid', $data, 0 ) );
						$html .= sprintf( '</div>' . "\r\n" );
						break;
				}
				break;

			case '!|-|':
				switch ( $filterType ) {
					case 'datetime':
						$html .= sprintf( '<div class="input-group">' . "\r\n" );
						$html .= sprintf( '	<input type="datetime-local" class="form-control" name="conditions[%d][filter][start]" required />' . "\r\n", get_array_key( 'cid', $data, 0 ) );
						$html .= sprintf( '	<span class="input-group-addon"> and </span>' . "\r\n" );
						$html .= sprintf( '	<input type="datetime-local" class="form-control" name="conditions[%d][filter][end]" required />' . "\r\n", get_array_key( 'cid', $data, 0 ) );
						$html .= sprintf( '</div>' . "\r\n" );
						break;

					default:
						$html .= sprintf( '<div class="input-group">' . "\r\n" );
						$html .= sprintf( '	<input type="number" class="form-control" name="conditions[%d][filter][start]" required />' . "\r\n", get_array_key( 'cid', $data, 0 ) );
						$html .= sprintf( '	<span class="input-group-addon"> and </span>' . "\r\n" );
						$html .= sprintf( '	<input type="number" class="form-control" name="conditions[%d][filter][end]" required />' . "\r\n", get_array_key( 'cid', $data, 0 ) );
						$html .= sprintf( '</div>' . "\r\n" );
						break;
				}
				break;

			case '!NULL!':
				break;

			case '!NOTNULL!':
				break;

			case '!INTNULL!':
				break;

			case '!INTNOTNULL!':
				break;

			default:
				if ( can_loop( $options ) ) {
					$html .= sprintf( '<select name="conditions[%d][filter]" class="form-control" required>' . "\r\n", get_array_key( 'cid', $data, 0 ) );
					foreach ( $options as $index => $val ) {
						if ( true === $val ) {
							$html .= sprintf( '	<option value="1">True</option>' . "\r\n", ( 1 == get_array_key( 'filter', $data ) ) ? 'selected' : '' );
						}
						else if ( false === $val ) {
							$html .= sprintf( '	<option value="0">False</option>' . "\r\n", ( 0 == get_array_key( 'filter', $data ) ) ? 'selected' : '' );
						}
						else {
							$html .= sprintf( '<option value="%s" %s>%s</option>', $index, ( 0 == get_array_key( 'filter', $data ) ) ? 'selected' : '', $val );
						}
					}
					$html .= '</select>' . "\r\n";
				}
				else {
					switch ( $filterType ) {
						case 'datetime':
							$html .= sprintf( '<input type="datetime-local" name="conditions[%d][filter]" class="form-control" value="%s" required />' . "\r\n", get_array_key( 'cid', $data, 0 ), get_array_key( 'filter', $data ) );
							break;

						case 'integer':
							$html .= sprintf( '<input type="number" name="conditions[%d][filter]" class="form-control" value="%s" required />' . "\r\n", get_array_key( 'cid', $data, 0 ), get_array_key( 'filter', $data ) );
							break;

						default:
							$html .= sprintf( '<input type="text" name="conditions[%d][filter]" class="form-control" value="%s" required />' . "\r\n", get_array_key( 'cid', $data, 0 ), get_array_key( 'filter', $data ) );
							break;
					}
				}
				break;
		}
		$return['fieldhtml'] = $html;
		ajax_success( $return );
	}

	function tc_get_filter_country_field() {
		global $_tc_countries;
		$return = array();
		if ( can_loop( $_tc_countries ) ) {
			foreach ( $_tc_countries as $iso => $data ) {
				$return[ $iso ] = get_array_key( 'name', $data, 'Unknown' );
			}
		}
		return $return;
	}

	function ajax_create_saved_query( $data ) {
		if ( true == get_array_key( 'debug', $data ) ) {
			global $_tc_countries;
			$page = absint( get_array_key( 'page', $data, 0 ) );
			$perpage = absint( get_array_key( 'perpage', $data, 10 ) );
			$conditions = get_array_key( 'conditions', $data, array() );
			$filtergrouping = get_array_key( 'filtergrouping', $data, '' );
			$countquery = tc_generate_export_query( true, $conditions, $filtergrouping );
			$leadquery = tc_generate_export_query( false, $conditions, $filtergrouping );
			$leadquery['query'] = str_replace( 'ORDER BY exportcount ASC', 'ORDER BY id ASC', $leadquery['query'] );
			$leadquery['query'] = str_replace( ', ( SELECT COUNT(id) FROM exportjobs_lead WHERE exportjobs_lead.lead_id = lead.id ) as exportcount', '', $leadquery['query'] );
			$leadquery['query'] = str_replace( ', 0 as exportcount', '', $leadquery['query'] );
			ajax_success( print_r( $leadquery, true ) );
		}
		else {
			$id = get_array_key( 'queryId', $data, 0 );
			if ( absint( $id ) > 0 ) {
				try {
					$q = R::load( 'savedfilterqueries', absint( $id ) );
				}
				catch ( Exception $e ) {
					if ( true == DEBUG ) {
						ajax_failure( sprintf( 'Exception: %s', $e->getMessage() ) );
					}
				}
			}
			if ( ! isset( $q ) || ! is_a( $q, 'RedBeanPHP\OODBBean' ) ) {
				try {
					$q = R::dispense( 'savedfilterqueries' );
					$q->owner = tc_get_session( 'user' );
				}
				catch ( Exception $e ) {
					if ( true == DEBUG ) {
						ajax_failure( sprintf( 'Exception: %s', $e->getMessage() ) );
					}
				}
			}
			if ( ! isset( $q ) || ! is_a( $q, 'RedBeanPHP\OODBBean' ) ) {
				ajax_failure( 'Could not create saved query' );
			}
			$q->name = get_array_key( 'filtername', $data, 'New Saved Query' );
			$q->description = get_array_key( 'description', $data, 'New Saved Query' );
			$q->public = ( true == get_array_key( 'public', $data, false ) || is_empty( $q->owner ) );
			$q->showGraph = ( true == get_array_key( 'showGraph', $data, false ) );
			$q->conditions = @serialize( get_array_key( 'conditions', $data, array() ) );
			$q->grouping = get_array_key( 'filtergrouping', $data, '' );
			try {
				R::store( $q );
			}
			catch( Exception $e ) {
				if ( true == DEBUG ) {
					ajax_failure( sprintf( 'Exception: %s', $e->getMessage() ) );
				}
				ajax_failure( 'Could not save query' );
			}
			ajax_success( 'Saved Query Successfully', sprintf( '/leads/saved-queries/%d', $q->id ) );
		}
	}

	function ajax_get_saved_query_filters( $data ) {
		$id = absint( get_array_key( 'quid', $data, 0 ) );
		if ( 0 == $id ) {
			ajax_success( array() );
		}
		try {
			$q = R::load( 'savedfilterqueries', $id );
		}
		catch ( Exception $e ) {
			if ( true == DEBUG ) {
				ajax_failure( sprintf( 'Exception: %s', $e->getMessage() ) );
			}
			ajax_failure( 'Could not load query' );
		}
		if ( ! isset( $q ) || ! is_a( $q, 'RedBeanPHP\OODBBean' ) ) {
			ajax_failure( 'Could not load query' );
		}
		if ( false == $q->public && ! is_empty( tc_get_session( 'user' ) ) && tc_get_session( 'user' ) !== $q->owner ) {
			ajax_failure( 'You cannot access this query' );
		}
		ajax_success( @unserialize( $q->conditions ) );
	}

	function ajax_get_saved_query_grouping( $data ) {
		$id = absint( get_array_key( 'quid', $data, 0 ) );
		if ( 0 == $id ) {
			ajax_success( array() );
		}
		try {
			$q = R::load( 'savedfilterqueries', $id );
		}
		catch ( Exception $e ) {
			if ( true == DEBUG ) {
				ajax_failure( sprintf( 'Exception: %s', $e->getMessage() ) );
			}
			ajax_failure( 'Could not load query' );
		}
		if ( ! isset( $q ) || ! is_a( $q, 'RedBeanPHP\OODBBean' ) ) {
			ajax_failure( 'Could not load query' );
		}
		if ( false == $q->public && ! is_empty( tc_get_session( 'user' ) ) && tc_get_session( 'user' ) !== $q->owner ) {
			ajax_failure( 'You cannot access this query' );
		}
		ajax_success( strip_tags( $q->grouping ) );
	}

	function ajax_delete_saved_query( $data ) {
		$id = absint( get_array_key( 'quid', $data, 0 ) );
		if ( 0 == $id ) {
			ajax_failure( 'Invalid Saved Query' );
		}
		try {
			$q = R::load( 'savedfilterqueries', $id );
		}
		catch ( Exception $e ) {
			if ( true == DEBUG ) {
				ajax_failure( sprintf( 'Exception: %s', $e->getMessage() ) );
			}
			ajax_failure( 'Could not load query' );
		}
		if ( ! isset( $q ) || ! is_a( $q, 'RedBeanPHP\OODBBean' ) ) {
			ajax_failure( 'Could not load query' );
		}
		if ( is_empty( tc_get_session( 'user' ) ) || tc_get_session( 'user' ) !== $q->owner ) {
			ajax_failure( 'You cannot access this query' );
		}
		try {
			R::trash( $q );
		}
		catch( Exception $e ) {
			if ( true == DEBUG ) {
				ajax_failure( sprintf( 'Exception: %s', $e->getMessage() ) );
			}
			ajax_failure( 'Could not delete query' );
		}
		ajax_success( 'Deleted Query Successfully', '/leads/saved-queries/' );
	}

	function tc_get_user_saved_queries() {
		$queries = array();
		if ( is_user_login() ) {
			try {
				$queries = R::find( 'savedfilterqueries', 'public = 1 OR owner LIKE :o', array( ':o' => tc_get_session( 'user' ) ) );
			}
			catch ( Exception $e ) {}
		}
		return $queries;
	}

	function tc_get_user_graph_queries() {
		$return = array();
		$queries = tc_get_user_saved_queries();
		if ( can_loop( $queries ) ) {
			foreach ( $queries as $i => $q ) {
				if ( true == $q->showGraph ) {
					$return[ $i ] = $q;
				}
			}
		}
		return $return;
	}

	function tc_get_graph_queries() {
        $return = array();
        $queries = array();
        try {
            $queries = R::findCollection( 'savedfilterqueries', 'show_graph = 1' );
        }
        catch ( Exception $e ) {
        }
        if ( is_a( $queries, 'RedBeanPHP\BeanCollection' ) ) {
            while ( $q = $queries->next() ) {
               $return[ $q->id ] = $q;
            }
        }
        return $return;
    }

	function get_decription_for_saved_query( $id, $default = null ) {
		try {
			$q = R::load( 'savedfilterqueries', $id );
		}
		catch ( Exception $e ) {
			if ( true == DEBUG ) {
				ajax_failure( sprintf( 'Exception: %s', $e->getMessage() ) );
			}
			return $default;
		}
		return get_bean_property( 'description', $q, $default );
	}

	function tc_get_sortable_columns() {
		global $tc_fields;
		$return = array();
		if ( can_loop( $tc_fields ) ) {
			foreach ( $tc_fields as $key => $data ) {
				if ( true == get_array_key( 'orderable', $data, false ) ) {
					$return[ $key ] = get_array_key( 'description', $data, 'Unknown' );
				}
			}
		}
		$return['exportcount'] = 'Times Exported';
		return $return;
	}