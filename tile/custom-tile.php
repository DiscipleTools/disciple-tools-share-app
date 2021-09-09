<?php
if ( !defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly.

class DT_Share_Tile
{
    private static $_instance = null;
    public static function instance(){
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    public $token = 'share_app_user';

    public function __construct(){
        add_filter( "dt_custom_fields_settings", [ $this, "dt_custom_fields" ], 100, 2 );
        add_filter( "dt_user_list_filters", [ $this, "dt_user_list_filters" ], 10, 2 );
    }

    /**
     * @param array $fields
     * @param string $post_type
     * @return array
     */
    public function dt_custom_fields( array $fields, string $post_type = "" ) {

        if ( in_array( $post_type, [ 'contacts','groups','trainings' ] ) ){
            $fields[$this->token] = [
                'name' => __( 'Share App', 'disciple_tools' ),
                'type' => 'key_select',
                'private' => true,
                "tile" => "status",
                'default' => [
                    'none'   => [
                        "label" => _x( 'Not on Calendar', 'Share App label', 'disciple_tools' ),
                        "description" => _x( "Not on prayer calendar", "Share App field description", 'disciple_tools' ),
                        'color' => "#ff9800"
                    ],
                    'every_month' => [
                        "label" => _x( 'Every Month', 'Share App label', 'disciple_tools' ),
                        "description" => _x( "Pray for this contact every month. Automatically, ordered.", "Share App field description", 'disciple_tools' ),
                        'color' => "#4CAF50"
                    ],
                    'every_week' => [
                        "label" => _x( 'Every Week', 'Share App label', 'disciple_tools' ),
                        "description" => _x( "Pray for this contact every month. Automatically, ordered.", "Share App field description", 'disciple_tools' ),
                        'color' => "#4CAF50"
                    ],
                    'every_day' => [
                        "label" => _x( 'Every Day', 'Share App label', 'disciple_tools' ),
                        "description" => _x( "Pray for this contact every month. Automatically, ordered.", "Share App field description", 'disciple_tools' ),
                        'color' => "#4CAF50"
                    ],
                    'every_1' => [
                        "label" => _x( 'Every Monday', 'Share App label', 'disciple_tools' ),
                        "description" => _x( "Pray every Monday.", "Share App field description", 'disciple_tools' ),
                        'color' => "#4CAF50"
                    ],
                    'every_2' => [
                        "label" => _x( 'Every Tuesday', 'Share App label', 'disciple_tools' ),
                        "description" => _x( "Pray every Tuesday.", "Share App field description", 'disciple_tools' ),
                        'color' => "#4CAF50"
                    ],
                    'every_3' => [
                        "label" => _x( 'Every Wednesday', 'Share App label', 'disciple_tools' ),
                        "description" => _x( "Pray every Wednesday.", "Share App field description", 'disciple_tools' ),
                        'color' => "#4CAF50"
                    ],
                    'every_4' => [
                        "label" => _x( 'Every Thursday', 'Share App label', 'disciple_tools' ),
                        "description" => _x( "Pray every Thursday.", "Share App field description", 'disciple_tools' ),
                        'color' => "#4CAF50"
                    ],
                    'every_5' => [
                        "label" => _x( 'Every Friday', 'Share App label', 'disciple_tools' ),
                        "description" => _x( "Pray every Friday.", "Share App field description", 'disciple_tools' ),
                        'color' => "#4CAF50"
                    ],
                    'every_6' => [
                        "label" => _x( 'Every Saturday', 'Share App label', 'disciple_tools' ),
                        "description" => _x( "Pray every Saturday.", "Share App field description", 'disciple_tools' ),
                        'color' => "#4CAF50"
                    ],
                    'every_7' => [
                        "label" => _x( 'Every Sunday', 'Share App label', 'disciple_tools' ),
                        "description" => _x( "Pray every Sunday.", "Share App field description", 'disciple_tools' ),
                        'color' => "#4CAF50"
                    ],
                ],
            ];
        }

        return $fields;
    }


    public function dt_user_list_filters( $filters, $post_type ){

        if ( in_array( $post_type, [ 'contacts','groups','trainings' ] ) ) {
            $key = get_user_option( $this->token );
            if ( ! empty( $key ) ) {
                $counts = $this->get_my_prayer_counts( $post_type );
                $meta_value_counts = [];
                $total_prayer_items = 0;
                foreach ($counts as $count) {
                    $total_prayer_items += $count["count"];
                    dt_increment( $meta_value_counts[$count["meta_value"]], $count["count"] );
                }

                $filters["tabs"][] = [
                    "key" => $this->token,
                    "label" => _x( "Share App", 'List Filters', 'disciple_tools' ),
                    "count" => $total_prayer_items,
                    "order" => 20
                ];

                $post_type_fields = DT_Posts::get_post_field_settings( $post_type );
                foreach ( $post_type_fields[$this->token]['default'] as $key => $item ){
                    if ( 'none' === $key ){
                        continue;
                    }

                    $filters["filters"][] = [
                        'ID' => $key,
                        'tab' => $this->token,
                        'name' => $item['label'],
                        'query' => [
                            $this->token => [ $key ],
                            'sort' => 'name'
                        ],
                        "count" => $meta_value_counts[$key] ?? 0,
                    ];
                }
            }
        }
        return $filters;
    }

    public function get_my_prayer_counts( $post_type ){

        global $wpdb;
        $current_user = get_current_user_id();

        $results = $wpdb->get_results( $wpdb->prepare( "
            SELECT pum.meta_value, count(pum.id) as count
                FROM $wpdb->dt_post_user_meta pum
                JOIN $wpdb->posts p ON p.ID=pum.post_id
                WHERE
                pum.user_id = %d
                AND pum.meta_key = %s
                AND p.post_type = %s
                GROUP BY pum.meta_value
        ", $current_user, $this->token, $post_type ), ARRAY_A);

        return $results;
    }

}
DT_Share_Tile::instance();
