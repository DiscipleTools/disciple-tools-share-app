<?php
if ( !defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly.

class DT_Share_Tile {

    public $root = "share_app";
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
        add_action( 'dt_details_additional_section', [ $this, 'dt_details_additional_section' ], 30, 2 );
        add_filter( 'dt_details_additional_tiles', [ $this, 'dt_details_additional_tiles' ], 10, 2 );
    }

    public function dt_details_additional_tiles( $tiles, $post_type = "" ) {
        if ( $post_type === 'contacts' ){
            $tiles["apps"] = [
                "label" => __( "Apps", 'disciple-tools-share-app' ),
                "description" => "This app has a share app"
            ];
        }
        return $tiles;
    }

    public function dt_details_additional_section( $section, $post_type ) {
        // test if campaigns post type and campaigns_app_module enabled
        if ( $post_type === $this->post_type ) {
            if ( 'apps' === $section ) {
                $record = DT_Posts::get_post( $post_type, get_the_ID() );
                if ( isset( $record[$this->meta_key] )) {
                    $key = $record[$this->meta_key];
                } else {
                    $key = dt_create_unique_key();
                    update_post_meta( get_the_ID(), $this->meta_key, $key );
                }
                $link = DT_Magic_URL::get_link_url( $this->root, $this->type, $key )
                ?>
                <div class="section-subheader">Share App</div>
                <div id="practitioner_portal">
                    <a class="button small hollow" href="<?php echo esc_html( $link ); ?>" target="_blank">Open Share App</a>
                    <a class="button small hollow copy_to_clipboard" data-value="<?php echo esc_html( $link ); ?>" target="_blank">Copy Link</a>
                </div>

                <script>
                    jQuery(document).ready(function(){
                        jQuery('#open-portal-activity').on('click', function(e){
                            jQuery('#modal-full-title').empty().html(`Portal Activity`)
                            jQuery('#modal-full-content').empty().html(`content`) // @todo add content logic
                            jQuery('#modal-full').foundation('open')
                        })
                    })
                </script>
                <?php
            }
        }
    }
}
DT_Share_Tile::instance();
