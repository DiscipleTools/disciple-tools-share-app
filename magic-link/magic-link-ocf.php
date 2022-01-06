<?php
if ( !defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly.

if ( strpos( dt_get_url_path(), 'share_app' ) !== false
    || strpos( dt_get_url_path(), 'settings' ) !== false
    || strpos( dt_get_url_path(), 'contacts' ) !== false
){
    DT_Share_Magic_Link::instance();
}

class DT_Share_Magic_Link extends DT_Magic_Url_Base
{

    public $page_title = 'Share App';
    public $page_description = 'A micro user app that tracks shares and followup.';
    public $root = "share_app";
    public $type = 'ocf';
    public $type_name = 'Share App';
    public $post_type = 'contacts';
    public $type_actions = [
        '' => "Share",
        'map' => "Map View",
    ];
    public $show_bulk_send = true;
    public $show_app_tile = true;
    public $js_file_name = 'share-app-ocf.js';
    private $meta_key = '';

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

    public function dt_settings_apps_list( $apps_list ) {
        $apps_list[ $this->meta_key ] = [
            'key'              => $this->meta_key,
            'url_base'         => $this->root . '/' . $this->type,
            'label'            => $this->page_title,
            'description'      => $this->page_description,
            'settings_display' => true,
        ];

        return $apps_list;
    }

    public function dt_magic_url_base_allowed_js( $allowed_js ) {
        $allowed_js[] = 'share-app-'.$this->type;
        $allowed_js[] = 'jquery-touch-punch';
        $allowed_js[] = 'mapbox-gl';
        $allowed_js[] = 'mapbox-cookie';
        $allowed_js[] = 'jquery-cookie';

        return $allowed_js;
    }

    public function dt_magic_url_base_allowed_css( $allowed_css ) {
        $allowed_css[] = 'share-app-css';
        $allowed_css[] = 'mapbox-gl-css';
        return $allowed_css;
    }

    public function scripts() {
        wp_enqueue_script( 'jquery-cookie', 'https://cdn.jsdelivr.net/npm/js-cookie@rc/dist/js.cookie.min.js', [ 'jquery' ], '3.0.0' );
        wp_enqueue_script( 'mapbox-cookie', trailingslashit( get_stylesheet_directory_uri() ) . 'dt-mapping/geocode-api/mapbox-cookie.js', [ 'jquery', 'jquery-cookie' ], '3.0.0' );
        wp_register_script( 'jquery-touch-punch', '/wp-includes/js/jquery/jquery.ui.touch-punch.js' ); // @phpcs:ignore
        wp_enqueue_script( 'share-app-'.$this->type, trailingslashit( plugin_dir_url( __FILE__ ) ) . $this->js_file_name, [
            'jquery',
        ], filemtime( trailingslashit( plugin_dir_path( __FILE__ ) ) .$this->js_file_name ), true );
        wp_enqueue_style( 'share-app-css', trailingslashit( plugin_dir_url( __FILE__ ) ) . 'share-app.css', [], filemtime( trailingslashit( plugin_dir_path( __FILE__ ) ) .'share-app.css' ) );
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

        $link = site_url() . '/' . $this->parts['root'] . '/' . $this->parts['type'] . '/' . $this->parts['public_key'] . '/';
        ?>
        <div id="custom-style"></div>

        <!-- title -->
        <div class="grid-x">
            <div class="cell padding-1">
                <button type="button" style="margin:1em;" data-open="offCanvasLeft"><i class="fi-list" style="font-size:2em;"></i></button>
                <span class="loading-spinner" style="float:right;margin:10px;"></span><!-- javascript container -->
            </div>
        </div>

        <!-- off canvas menus -->
        <div class="off-canvas-wrapper">
            <!-- Left Canvas -->
            <div class="off-canvas position-left" id="offCanvasLeft" data-off-canvas data-transition="push">
                <button class="close-button" aria-label="Close alert" type="button" data-close>
                    <span aria-hidden="true">&times;</span>
                </button>
                <div class="grid-x grid-padding-x">
                    <div class="cell center" style="padding-top: 1em;"><h2>Share App</h2></div>
                    <div class="cell"><hr></div>
                    <ul>
                        <li><h2><a href="<?php echo esc_url( $link ) ?>">Share Home</a></h2></li>
                        <li><h2><a href="<?php echo esc_url( $link . 'map' ) ?>">My Map</a></h2></li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- body-->
        <div id="wrapper">
            <div id="content"></div>
        </div>
        <div id="footer-bar">
            <div id="location-status"></div>
        </div>
        <?php
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
            case 'geojson':
                return $this->endpoint_geojson( $params['parts'] );
            default:
                return new WP_Error( __METHOD__, "Missing valid action", [ 'status' => 400 ] );
        }
    }

    public function endpoint_log( $parts, $data ) {

        if ( ! isset( $data['state'], $data['location'] ) ) {
            return new WP_Error( __METHOD__, "Missing required parameters", [ 'status' => 400, 'data' => $data ] );
        }

        // get user contact record id
        $longitude = sanitize_text_field( wp_unslash( $data['location']['longitude'] ) );
        $latitude = sanitize_text_field( wp_unslash( $data['location']['latitude'] ) );

        $geocoder = new Location_Grid_Geocoder();
        $grid = $geocoder->get_grid_id_by_lnglat( $longitude, $latitude );
        if ( ! empty( $grid ) ) {
            $full_name = Disciple_Tools_Mapping_Queries::get_full_name_by_grid_id( $grid['grid_id'] );
        } else {
            $full_name = '';
        }

        $user_id = get_post_meta( $parts['post_id'], 'corresponds_to_user', true );
        if ( ! $user_id ) {
            $user_id = 0;
        }

        $state = 0;
        if ( 'open' === $data['state'] ) {
            $state = 1;
        }
        else if ( 'followup' === $data['state'] ){
            $state = 2;
        }

        $args = [
            'user_id' => $user_id,
            'post_id' => $parts['post_id'],
            'post_type' => 'contacts',
            'type' => $parts['root'],
            'subtype' => $parts['type'],
            'lng' => $longitude,
            'lat' => $latitude,
            'level' => '',
            'label' => $full_name,
            'grid_id' => $grid['grid_id'] ?? '',
            'value' => $state,
            'time_end' => time(),
        ];

        return Disciple_Tools_Reports::insert( $args );
    }

    public function endpoint_followup( $parts, $data ) {

        if ( ! isset( $data['email'], $data['phone'], $data['name'], $data['location'] ) ) {
            return new WP_Error( __METHOD__, "Missing required parameters", [ 'status' => 400, 'data' => $data ] );
        }

        $user_id = get_post_meta( $parts['post_id'], 'corresponds_to_user', true );
        if ( ! $user_id ) {
            $user_id = 0;
        }

        $notes['note'] = $data['notes'];

        $fields = [
            'title' => sanitize_text_field( wp_unslash( $data['name'] ) ),
            "assigned_to" => sanitize_text_field( wp_unslash( $user_id ) ),
            "subassigned" => [
                "values" => [
                    [ "value" => sanitize_text_field( wp_unslash( $parts['post_id'] ) ) ],
                ],
            ],
            "type" => 'access',
            "contact_phone" => [
                [ "value" => sanitize_text_field( wp_unslash( $data['phone'] ) ) ]
            ],
            "contact_email" => [
                [ "value" => sanitize_text_field( wp_unslash( $data['email'] ) ) ]
            ],
            "notes" => $notes
        ];

        $post_id = DT_Posts::create_post( 'contacts', $fields, false, false );

        $report_id = $this->endpoint_log( $parts, [
            'location' => $data['location'],
            'state' => 'followup'
        ] );

        return [
          'post_id' => $post_id,
          'report' => $report_id
        ];
    }

    public function endpoint_geojson( $parts ) {
        global $wpdb;
        $contact_id = $parts['post_id'];
        $results = $wpdb->get_results( $wpdb->prepare(
            "SELECT lng, lat, value as type, label
                    FROM $wpdb->dt_reports
                    WHERE post_id = %d
                    AND type = 'share_app'
                    AND subtype = 'ocf'
                    ORDER BY time_end DESC
        ", $contact_id ), ARRAY_A );

        if ( empty( $results ) ) {
            return $this->_empty_geojson();
        }

        $features = [];
        foreach ($results as $result) {
            $features[] = array(
                'type' => 'Feature',
                'properties' => array(
                    'label' => $result['label'],
                    'type' => $result['type']
                ),
                'geometry' => array(
                    'type' => 'Point',
                    'coordinates' => array(
                        (float) $result['lng'],
                        (float) $result['lat'],
                        1
                    ),
                ),
            );
        }

        $geojson = array(
            'type' => 'FeatureCollection',
            'features' => $features,
        );

        return $geojson;
    }

    private function _empty_geojson() {
        return array(
            'type' => 'FeatureCollection',
            'features' => array()
        );
    }
}
