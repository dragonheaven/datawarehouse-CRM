<?php
    defined( 'ABSPATH' ) || die( 'Sorry, but you cannot access this page directly.' );

    function tc_check_profile_image() {

        //pull all ids from 'lead' table
        try {
            $ids = R::getCol( 'select id from lead' );
        }
        catch ( Exception $e ) {
            cli_echo( sprintf( 'There is no data in table \'%s\'', 'lead' ) );
        }
        if ( can_loop( $ids ) ) {
            foreach ( $ids as $id ) {
                $lead = R::load( 'lead', $id );
                if ( is_empty( $lead->profile_image ) ) {
                    $email_id = $lead->email_id;
                    $email_row = R::load('email', $email_id);
                    $email = $email_row->email;
                    if ( ! is_empty( $email ) ) {
                        $image = tc_get_gravatar_by_email( $email );
                        if ( ! is_empty( $image ) ) {
                            $lead->profile_image = base64_encode( $image );
                        }
                        else {
                            $lead->profile_image = null;
                        }
                    }
                    R::store( $lead );
                }
                else {
                    $image = $lead->profile_image;
                    $image = base64_decode( $image );

                    if ( true == is_gd_installed() ) {
                        if ( ! is_default_image( $image ) ) {
                            //reduce size to default size 100X100
                            $image = do_default_image( $image );
                        }
                    }
                    
                    R::store( $lead );
                }
            }
        }
    }

    // Get gravatar image by email. $return or null
    function tc_get_gravatar_by_email( $email ) {
        cli_echo( sprintf( 'Getting gravatar for "%s"', $email ) );
        $return = null;
        // Craft a potential url and test its headers
        $hash = md5(strtolower(trim($email)));
        //$url = 'http://www.gravatar.com/avatar/' . $hash . '?d=404'.'&s=100';
        $url = sprintf( 'http://www.gravatar.com/avatar/%s?d=%d&s=%d', $hash, 404, 100 );

        $response = HTTP_REQUEST::GET( $url );
        if ( 200 == $response->code ) {
            cli_echo( sprintf( 'Found Image for "%s"', $email ) );
        }
        return ( 200 == $response->code ) ? $response->data->contents : null;
    }

    // check if the image is 100 X 100 or not
    // $image is a string standing for an image
    function is_default_image( $image ) {
        if ( ! is_empty( $image ) && is_image( $image ) ) {
            list( $width, $height ) = getimagesizefromstring( $image );
            if ( 100 == $width && 100 == $height ) {
                return true;
            }
        }
        return false;
    }

    // reduce image size to default size 100 x 100
    // $image is a string standing for an image
    function do_default_image( $ori_image ) {
        if ( ! is_image( $ori_image ) ) {
            return null;
        }

        $default_width = 100;
        $default_height = 100;
        list( $ori_width, $ori_height ) = getimagesizefromstring( $ori_image );
        $image_p = imagecreatetruecolor( $default_width, $default_height );
        $new_image = imagecreatefromstring( $ori_image );
        imagecopyresampled( $image_p, $new_image, 0, 0, 0, 0, $default_width, $default_height, $ori_width, $ori_height );

        return $image_p;
    }

    // check GD library is installed or not
    function is_gd_installed() {
        if ( extension_loaded( 'gd' ) && function_exists( 'gd_info' ) ) {
            return true;
        }
        return false;
    }
