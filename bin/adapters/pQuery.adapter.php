<?php
    defined( 'ABSPATH' ) || die( 'Sorry, but you cannot access this page directly.' );

    // To traverse HTML Markup
    class pQuery {
        private $doc_url = '';
        private $dom = NULL;
        private $finder = NULL;

        function __construct( $url = NULL ) {
            if ( null != $url ) {
                $this->doc_url = $url;
                $header = array (
                    'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
                    'User-agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_12_4) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/58.0.3029.81 Safari/537.36'
                );
                $response = HTTP_REQUEST::GET( $url, null, $header );
                if ( 200 == $response->code ) {
                    $this->dom = new DOMDocument();
                    $this->dom->loadHTML( $response->data->contents );
                    $this->dom->preserveWhiteSpace = false;
                }
            }
        }

        public function find( $query ) {
            if ( preg_match( '^\#', $query ) ) {
                return;
            }

            $container_list = $this->finder->query( '//a' );
            if ( is_array( $container_list ) ) {
                for ( $i = 0; $i < $container_list->length; $i ++ ) {
                    $list = $container_list->item( $i );
                }
            }
        }
    }
