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
        $allowed_js[] = 'jquery-touch-punch';
        $allowed_js[] = 'mapbox-gl';
        return $allowed_js;
    }

    public function dt_magic_url_base_allowed_css( $allowed_css ) {
        $allowed_css[] = 'share-app-css';
        $allowed_css[] = 'mapbox-gl-css';
        return $allowed_css;
    }

    public function scripts() {
        wp_register_script( 'jquery-touch-punch', '/wp-includes/js/jquery/jquery.ui.touch-punch.js' ); // @phpcs:ignore
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
        DT_Mapbox_API::geocoder_scripts();
        include( 'share-app.html' );
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
                return $this->endpoint_followup( $params['parts'], $params['data'] );
            default:
                return new WP_Error( __METHOD__, "Missing valid action", [ 'status' => 400 ] );
        }
    }

    public function endpoint_log( $parts, $data ) {
        // get user contact record id
        $contact_id = get_user_option( "corresponds_to_contact", $parts['post_id'] );
        if ( empty( $contact_id ) ) {
            return new WP_Error( __METHOD__, 'No contact id found for user' );
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
            'payload' => [],
            'value' => 1,
            'time_end' => time(),
        ];

        return Disciple_Tools_Reports::insert( $args );
    }

    public function endpoint_followup( $parts, $data ) {

        $notes['note'] = $data['notes'];

        $fields = [
            'title' => sanitize_text_field( wp_unslash( $data['name'] ) ),
            "assigned_to" => sanitize_text_field( wp_unslash( $parts['post_id'] ) ),
            "contact_phone" => [
                [ "value" => sanitize_text_field( wp_unslash( $data['phone'] ) ) ]
            ],
            "contact_email" => [
                [ "value" => sanitize_text_field( wp_unslash( $data['email'] ) ) ]
            ],
            "type" => 'access',
            "notes" => $notes
        ];

        return DT_Posts::create_post( 'contacts', $fields, false, false );
    }
}
