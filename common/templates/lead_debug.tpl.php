<?php
	defined( 'ABSPATH' ) || die( 'Sorry, but you cannot access this page directly.' );
	echo '<pre>';
	$res = test();
//	if ( is_array($res) ) {
//
//	    for ( $i=0; $i<$res->length; $i++) {
//	        echo $res->item($i)->textContent;
//	        echo "<br>";
//        }
//    }
    echo $res;
	echo '</pre>';