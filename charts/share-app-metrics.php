<?php
if ( !defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly.

class DT_Share_Chart_Template extends DT_Metrics_Chart_Base
{
    public $base_slug = 'share-app-metrics'; // lowercase
    public $base_title = 'Share App';

    public $title = 'Map';
    public $slug = 'map'; // lowercase
    public $js_object_name = 'wp_js_object'; // This object will be loaded into the metrics.js file by the wp_localize_script.
    public $js_file_name = 'share-app-metrics.js'; // should be full file name plus extension
    public $permissions = [ 'dt_all_access_contacts', 'view_project_metrics' ];

    public function __construct() {
        parent::__construct();

        add_action( 'rest_api_init', [ $this, 'add_api_routes' ] );

        if ( !$this->has_permission() ){
            return;
        }
        $url_path = dt_get_url_path();

        // only load scripts if exact url
        if ( "metrics/$this->base_slug/$this->slug" === $url_path ) {
            add_action( 'wp_enqueue_scripts', [ $this, 'scripts' ], 99 );
        }
    }

    /**
     * Load scripts for the plugin
     */
    public function scripts() {

        DT_Mapbox_API::load_mapbox_header_scripts();

        wp_enqueue_script( 'jquery-cookie', 'https://cdn.jsdelivr.net/npm/js-cookie@rc/dist/js.cookie.min.js', [ 'jquery' ], '3.0.0' );
        wp_enqueue_script( 'mapbox-cookie', trailingslashit( get_stylesheet_directory_uri() ) . 'dt-mapping/geocode-api/mapbox-cookie.js', [ 'jquery', 'jquery-cookie' ], '3.0.0' );

        wp_enqueue_script( 'dt_'.$this->slug.'_script', trailingslashit( plugin_dir_url( __FILE__ ) ) . $this->js_file_name, [
            'jquery',
        ], filemtime( plugin_dir_path( __FILE__ ) .$this->js_file_name ), true );

        // Localize script with array data
        wp_localize_script(
            'dt_'.$this->slug.'_script', $this->js_object_name, [
                'map_key' => DT_Mapbox_API::get_key(),
                'rest_endpoints_base' => esc_url_raw( rest_url() ) . "$this->base_slug/$this->slug",
                'base_slug' => $this->base_slug,
                'slug' => $this->slug,
                'root' => esc_url_raw( rest_url() ),
                'plugin_uri' => plugin_dir_url( __DIR__ ),
                'nonce' => wp_create_nonce( 'wp_rest' ),
                'current_user_login' => wp_get_current_user()->user_login,
                'current_user_id' => get_current_user_id(),
                'stats' => [
                    // add preload stats data into arrays here
                ],
                'translations' => [
                    'title' => $this->title,
                    'Sample API Call' => __( 'Sample API Call' )
                ]
            ]
        );
    }

    public function add_api_routes() {
        $namespace = "$this->base_slug/$this->slug";
        register_rest_route(
            $namespace, '/geojson', [
                'methods'  => 'POST',
                'callback' => [ $this, 'endpoint_geojson' ],
                'permission_callback' => function( WP_REST_Request $request ) {
                    return $this->has_permission();
                },
            ]
        );
    }

    public function endpoint_geojson( WP_REST_Request $request ) {
        global $wpdb;
        $results = $wpdb->get_results(
            "SELECT *
                    FROM $wpdb->dt_reports
                    WHERE type = 'share_app'
                    AND subtype = 'ocf'
                    ORDER BY time_end DESC
        ", ARRAY_A );

        if ( empty( $results ) ) {
            return [
                'closed' => [
                    'type' => 'FeatureCollection',
                    'features' => [],
                ],
                'open' => [
                    'type' => 'FeatureCollection',
                    'features' => [],
                ],
                'followup' => [
                    'type' => 'FeatureCollection',
                    'features' => [],
                ]
            ];
        }

        $data = [
            'closed' => [],
            'open' => [],
            'followup' => []
        ];
        foreach ( $results as $result ) {
            $feature = array(
                'type' => 'Feature',
                'properties' => array(
                    'type' => $result['value']
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
            if ( '0' === $result['value'] ) {
                $data['closed'][] = $feature;
            } else if ( '1' === $result['value'] ) {
                $data['open'][] = $feature;
            } else if ( '2' === $result['value'] ) {
                $data['followup'][] = $feature;
            }
        }

        return [
            'closed' => [
                'type' => 'FeatureCollection',
                'features' => $data['closed'],
            ],
            'open' => [
                'type' => 'FeatureCollection',
                'features' => $data['open'],
            ],
            'followup' => [
                'type' => 'FeatureCollection',
                'features' => $data['followup'],
            ]
        ];
    }

}
