<?php
if ( !defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly.

if ( strpos( dt_get_url_path(), 'share_app' ) !== false || strpos( dt_get_url_path(), 'settings' ) !== false ){
    DT_Share_Magic_Link::instance();
}

class DT_Share_Magic_Link extends DT_Magic_Url_Base
{

    public $page_title = 'Share App (OCF)';
    public $page_description = 'A micro user app that tracks shares and followup.';
    public $root = "share_app";
    public $type = 'ocf';
    public $post_type = 'user';
    private $meta_key = '';
    public $js_file_name = 'share-app-ocf.js';

    private static $_instance = null;
    public static function instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    } // End instance()

    public function __construct() {
        $this->meta_key = $this->root . '_' . $this->type . '_magic_key';
        parent::__construct();

        /**
         * user_app and module section
         */
        add_filter( 'dt_settings_apps_list', [ $this, 'dt_settings_apps_list' ], 10, 1 );
        add_action( 'rest_api_init', [ $this, 'add_endpoints' ] );

        /**
         * tests if other URL
         */
        $url = dt_get_url_path();
        if ( strpos( $url, $this->root . '/' . $this->type ) === false ) {
            return;
        }
        /**
         * tests magic link parts are registered and have valid elements
         */
        if ( !$this->check_parts_match() ){
            return;
        }

        // load if valid url
        add_action( 'wp_enqueue_scripts', [ $this, 'scripts' ], 99 );
        add_action( 'dt_blank_body', [ $this, 'body' ] );
        add_filter( 'dt_magic_url_base_allowed_css', [ $this, 'dt_magic_url_base_allowed_css' ], 10, 1 );
        add_filter( 'dt_magic_url_base_allowed_js', [ $this, 'dt_magic_url_base_allowed_js' ], 10, 1 );
    }

    public function dt_magic_url_base_allowed_js( $allowed_js ) {
        $allowed_js[] = 'share-app-'.$this->type;
        return $allowed_js;
    }

    public function dt_magic_url_base_allowed_css( $allowed_css ) {
        $allowed_css[] = 'share-app-css';
        return $allowed_css;
    }

    public function scripts() {
        wp_enqueue_script( 'share-app-'.$this->type, trailingslashit( plugin_dir_url( __FILE__ ) ) . $this->js_file_name, [
            'jquery',
        ], filemtime( trailingslashit( plugin_dir_path( __FILE__ ) ) .$this->js_file_name ), true );
        wp_enqueue_style( 'share-app-css', trailingslashit( plugin_dir_url( __FILE__ ) ) . 'share-app.css', [], filemtime( trailingslashit( plugin_dir_path( __FILE__ ) ) .'share-app.css' ) );
    }

    public function dt_settings_apps_list( $apps_list ) {
        $apps_list[$this->meta_key] = [
            'key' => $this->meta_key,
            'url_base' => $this->root. '/'. $this->type,
            'label' => $this->page_title,
            'description' => $this->page_description,
        ];
        return $apps_list;
    }

    public function footer_javascript(){
        ?>
        <script>
            let jsObject = [<?php echo json_encode([
                'map_key' => DT_Mapbox_API::get_key(),
                'ipstack' => DT_Ipstack_API::get_key(),
                'root' => esc_url_raw( rest_url() ),
                'nonce' => wp_create_nonce( 'wp_rest' ),
                'parts' => $this->parts,
                'translations' => [
                    'add' => __( 'Add Magic', 'disciple-tools-plugin-starter-template' ),
                ],
            ]) ?>][0]
        </script>
        <?php
        return;
    }

    public function body(){
        include('share-app.html');
    }

    public function add_endpoints() {
        $namespace = $this->root . '/v1';
        register_rest_route(
            $namespace, '/'.$this->type, [
                [
                    'methods'  => "POST",
                    'callback' => [ $this, 'endpoint' ],
                ],
            ]
        );
    }
    public function endpoint( WP_REST_Request $request ) {
        $params = $request->get_params();

        if ( ! isset( $params['parts'], $params['action'] ) ) {
            return new WP_Error( __METHOD__, "Missing parameters", [ 'status' => 400 ] );
        }

        $params = dt_recursive_sanitize_array( $params );
        $action = sanitize_text_field( wp_unslash( $params['action'] ) );

        switch ( $action ) {
            case 'log':
                return $this->endpoint_log( $params['parts'], $params['data'] );
            case 'followup':
                return $this->endpoint_followup( $params['parts'], $params['post_id'] );
            default:
                return new WP_Error( __METHOD__, "Missing valid action", [ 'status' => 400 ] );
        }
    }

    public function endpoint_log( $parts, $data ) {
        // get user contact record id
        $contact_id = get_user_option( "corresponds_to_contact", $parts['post_id'] );
        if ( empty( $contact_id ) ) {
            return new WP_Error(__METHOD__, 'No contact id found for user' );
        }

        $longitude = sanitize_text_field( wp_unslash( $data['longitude'] ) );
        $latitude = sanitize_text_field( wp_unslash( $data['latitude'] ) );


        $geocoder = new Location_Grid_Geocoder();
        $grid = $geocoder->get_grid_id_by_lnglat( $longitude, $latitude );
        if ( ! empty( $grid ) ) {
            $full_name = Disciple_Tools_Mapping_Queries::get_full_name_by_grid_id( $grid['grid_id'] );
        } else {
            $full_name = '';
        }

        $args = [
            'post_id' => $contact_id,
            'post_type' => 'contact',
            'type' => $parts['root'],
            'subtype' => $parts['type'],
            'lng' => $longitude,
            'lat' => $latitude,
            'level' => '',
            'label' => $full_name,
            'grid_id' => $grid['grid_id'] ?? '',
            'payload' => [

            ],
            'value' => 1,
            'time_end' => time(),
        ];

        return Disciple_Tools_Reports::insert( $args );
    }

    public function endpoint_followup( $parts, $post_id ) {
//        $post_type = get_post_type( $post_id );
//
//        $args = [
//            'parent_id' => $parts['post_id'], // using parent_id to record the user_id. i.e. parent of the record is the user.
//            'post_id' => $post_id,
//            'post_type' => $post_type,
//            'type' => $parts['root'],
//            'subtype' => $parts['type'],
//            'payload' => null,
//            'value' => 1,
//            'time_end' => time(),
//        ];
//
//        // get geolocation of the contact, not the user
//        $post_object = DT_Posts::get_post( $post_type, $post_id, false, false, true );
//        if ( isset( $post_object['location_grid_meta'] ) ) {
//            $location = $post_object['location_grid_meta'][0];
//            if ( isset( $location['lng'] ) ) {
//                $args['lng'] = $location['lng'];
//                $args['lat'] = $location['lat'];
//                $args['level'] = $location['level'];
//                $args['label'] = $location['label'];
//                $args['grid_id'] = $location['grid_id'];
//            }
//        } else if ( isset( $post_object['location_grid'][0] ) ) {
//            $location = $post_object['location_grid'][0];
//            $grid_record = Disciple_Tools_Mapping_Queries::get_by_grid_id( $location['id'] );
//            if ( isset( $grid_record['lng'] ) ) {
//                $args['lng'] = $grid_record['lng'];
//                $args['lat'] = $grid_record['lat'];
//                $args['level'] = $grid_record['level'];
//                $args['label'] = $location['label'];
//                $args['grid_id'] = $location['grid_id'];
//            }
//        }

//        return Disciple_Tools_Reports::insert( $args );
        return true;
    }
}
//DT_Share_Magic_Link::instance();
