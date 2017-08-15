<?php
    defined( 'ABSPATH' ) || die( 'Sorry, but you cannot access this page directly.' );

    function route_facebook_modules( $path = '/', $query = array(), $method = 'GET', $headers = array() ) {
        if ( is_cli() && beginning_matches( '/facebook/', $path ) ) {
            $action = filter_facebook_action( $path );
            switch ( $action ) {
                case 'search-facebook-profile':
                    tc_search_facebook_profile();
                    cli_success( null, 'Websocket Action Completed Successfully.' );
                    break;

                case 'get-facebook-id':
                    get_facebook_id();
                    cli_success( null, 'Websocket Action Completed Successfully.' );
                    break;

                default:
                    cli_failure( null, sprintf( 'No such websocket action "%s"', $action ) );
                    break;
            }
        }

    }

    function tc_search_facebook_profile() {
        $ids = array();
        //pull all ids from lead table
        try {
            $ids = R::getCol( 'select id from lead' );
        }
        catch ( Exception $e ) {
            cli_echo( sprintf( 'There is no data in table \'%s\'', 'lead' ) );
        }
        if ( can_loop( $ids ) ) {
            foreach ( $ids as $id ) {
                $lead = R::load( 'lead', $id );
                $fname = $lead->fname;
                $lname = $lead->lname;
                $country_code = $lead->country;
                $f_id = get_facebook_id();

            }
        }
    }

    //collect facebook id from user name and country
    function get_facebook_id( $fname = null, $lname = null, $city = null, $country_code = null ) {
        global $_tc_countries;

        $country_name = get_array_key( 'name', get_array_key( $country_code, $_tc_countries ) );
        $country_name = preg_replace( '/\s+/', '+', $country_name );
        $url = sprintf( 'https://www.facebook.com/public/?query=%s+%s+%s+%s', $fname, $lname, $city, $country_name );
        $url = preg_replace( '/\++/', '+', $url );

        $finder = null;
        $content = null;
        $headers[] = "User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_12_4) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/58.0.3029.81 Safari/537.36";

        $response = HTTP_REQUEST::GET( $url, null, $headers );
        if ( 200 != $response->code ) {
            cli_echo( sprintf( 'Wrong response from Facebook' ) );
            return null;
        }

        $dom = new DOMDocument();
        $content = preg_replace( '/<!-- | -->/', '', $response->data->contents );
        $dom->loadHTML( $content );
        $finder = new DOMXPath( $dom );

        $atag_list = $finder->query( "//a[@class='_2ial _8o _8s lfloat _ohe']" );
        if ( $atag_list->length == 0 ) {
            cli_echo( sprintf( 'No result from facebook for %s %s, %s', $fname, $lname, $country_name ) );
            return null;
        }
        elseif ( $atag_list->length > 1 ) {
            cli_echo( sprintf( 'Got Multiple results for %s %s, %s', $fname, $lname, $country_name ) );
            return null;
        }
        $href = $atag_list->item(0)->getAttribute( "href" );
        return $href;
    }

    function get_profile_info( $profile_url ) {
        $profile_image = null;
        $profile_favorites = array();
        $profile_posts = array();

        $html = file_get_contents( $profile_url );
        $headers[] = "User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_12_4) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/58.0.3029.81 Safari/537.36";
        $response = HTTP_REQUEST::GET( $profile_url, null, $headers );
        if ( 200 != $response->code ) {
            cli_echo( sprintf( 'Failed to get profile page from Facebook' ) );
            return null;
        }

        $dom = new DOMDocument();
        $content = preg_replace( '/<!-- | -->/', '', $response->data->contents );
        $dom->loadHTML( $content );
        $finder = new DOMXPath( $dom );

        $profile_image = $finder->query( '//img[@class=\'profilePic img\']' )->item(0)->getAttribute('src');

        //Favorites section
        $pis_node = $finder->query( '//div[@id=\'favorites\']' )->item(0);
        if ( is_object( $pis_node ) ) {
            $pis_html = $pis_node->ownerDocument->saveHTML( $pis_node );
            $pis_dom = new DOMDocument();
            $pis_dom->loadHTML( $pis_html );
            $pis_query = new DOMXPath( $pis_dom );
            $fav_list = $pis_query->query( '//tbody' );
            if ( is_object( $fav_list ) && $fav_list->length ) {
                for ( $i = 0; $i < $fav_list->length; $i++ ) {
                    $row = $fav_list->item( $i );
                    $row_html = $row->ownerDocument->saveHTML( $row );
                    $row_dom = new DOMDocument();
                    $row_dom->loadHTML( $row_html );
                    $row_query = new DOMXPath( $row_dom );

                    $favor_label = $row_query->query( '//div[@class=\'labelContainer\']' )->item(0)->textContent;
                    //$favor_img_url = $row_query->query( '//img[@class=\'photo img\']' )->item(0)->getAttribute( 'src' );
                    $favor_title = $row_query->query( '//div[@class=\'mediaPageName\']' )->item(0)->textContent;
                    $favor = array(
                        'favor_label' => $favor_label,
                        //'favor_img_url' => $favor_img_url,
                        'favor_title' => $favor_title
                    );
                    array_push( $profile_favorites, $favor );
                }
            }
        }

        return array(
            'profile_image' => $profile_image,
            'profile_favorites' => $profile_favorites
        );
    }

    function test() {
//        $href = get_facebook_id( 'Michelle', 'Sinclair', 'London', 'GB' );
//        if ( null == $href ) {
//            echo sprintf('Can\'t identify %s %s, %s', 'Michelle', 'Sinclair', 'GB');
//        }
//        else {
//            echo $href;
//        }

        $url = "https://www.facebook.com/melissa.sterling.167";
        $res = get_profile_info($url);
        echo get_array_key( 'favor_title', get_array_key( 'profile_favorites', $res ) );
    }

    function filter_facebook_action( $input ) {
        $out = $input;
        $out = str_replace( '/facebook/', '', $out );
        $out = str_replace( '/', '', $out );
        return $out;
    }

    add_action( 'route', 'handle_facebook_request' );
