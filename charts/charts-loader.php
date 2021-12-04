<?php
if ( !defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly.

class DT_Share_Charts
{
    private static $_instance = null;
    public static function instance(){
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    } // End instance()

    public function __construct(){

        require_once( 'share-app-metrics.php' );
        new DT_Share_Chart_Template();

        /**
         * @todo add other charts like the pattern above here
         */

    } // End __construct
}
DT_Share_Charts::instance();
