<?php
if ( !defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly.

class DT_Share_Tile {

    public $page_title = 'Share App';
    public $page_description = 'A micro user app that tracks shares and followup.';
    public $root = 'share_app';
    public $type = 'ocf';
    public $post_type = 'contacts';
    private $meta_key = 'share_app_ocf_magic_key';

    private static $_instance = null;
    public static function instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    } // End instance()

    public function __construct() {
//        add_filter( 'dt_settings_apps_list', [ $this, 'dt_settings_apps_list' ], 10, 1 );
        add_filter( 'dt_custom_fields_settings', [ $this, 'dt_custom_fields_settings' ], 50, 2 );
    }

//    public function dt_settings_apps_list( $apps_list ) {
//        $apps_list[$this->meta_key] = [
//            'key' => $this->meta_key,
//            'url_base' => $this->root. '/'. $this->type,
//            'label' => $this->page_title,
//            'description' => $this->page_description,
//        ];
//        return $apps_list;
//    }

    public function dt_custom_fields_settings( array $fields, string $post_type = '' ) {
        if ( $post_type === 'contacts' ) {
            $fields[$this->meta_key] = [
                'name' => $this->page_title,
                'type' => 'hash',
                'default' => dt_create_unique_key(),
                'hidden' => true,
            ];
        }
        return $fields;
    }
}
DT_Share_Tile::instance();
