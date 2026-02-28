<?php
/**
 * Plugin Name: Larissa Car Photo Story
 * Description: Adds a 4-photo story section to WooCommerce car products (Exterior / Back seats / Passenger / Driver) with per-product text fields.
 * Version: 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class Larissa_Car_Photo_Story {

    const META_EXT  = '_larissa_story_exterior';
    const META_REAR = '_larissa_story_rear';
    const META_PASS  = '_larissa_story_passenger';
    const META_DRV  = '_larissa_story_driver';

    public function __construct() {
        // Admin: fields in product editor
        add_action( 'woocommerce_product_options_general_product_data', [ $this, 'add_fields' ] );
        add_action( 'woocommerce_admin_process_product_object', [ $this, 'save_fields' ] );

        // Frontend: render on single product
        add_action( 'woocommerce_after_single_product_summary', [ $this, 'render_story_section' ], 8 );

        // Optional: small default styling
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_css' ] );
    }

    public function add_fields() {
        echo '<div class="options_group">';

        woocommerce_wp_textarea_input([
            'id'          => self::META_EXT,
            'label'       => __( 'Photo Story — Exterior (Photo 1)', 'larissa24' ),
            'description' => __( 'Short, vibrant text that matches the first (featured) exterior photo.', 'larissa24' ),
            'desc_tip'    => true,
            'rows'        => 3,
        ]);

        woocommerce_wp_textarea_input([
            'id'          => self::META_REAR,
            'label'       => __( 'Photo Story — Back Seats (Photo 2)', 'larissa24' ),
            'description' => __( 'Text for the back-seat photo (gallery #1).', 'larissa24' ),
            'desc_tip'    => true,
            'rows'        => 3,
        ]);

        woocommerce_wp_textarea_input([
            'id'          => self::META_PASS,
            'label'       => __( 'Photo Story — Passenger Seat (Photo 3)', 'larissa24' ),
            'description' => __( 'Text for the passenger-seat photo (gallery #2).', 'larissa24' ),
            'desc_tip'    => true,
            'rows'        => 3,
        ]);

        woocommerce_wp_textarea_input([
            'id'          => self::META_DRV,
            'label'       => __( 'Photo Story — Driver Seat (Photo 4)', 'larissa24' ),
            'description' => __( 'Text for the driver-seat photo (gallery #3).', 'larissa24' ),
            'desc_tip'    => true,
            'rows'        => 3,
        ]);

        echo '</div>';
    }

    public function save_fields( $product ) {
        $fields = [
            self::META_EXT,
            self::META_REAR,
            self::META_PASS,
            self::META_DRV,
        ];

        foreach ( $fields as $key ) {
            if ( isset( $_POST[ $key ] ) ) {
                $product->update_meta_data( $key, wp_kses_post( wp_unslash( $_POST[ $key ] ) ) );
            }
        }
    }

    /**
     * Collect the 4 images in your required order:
     * 1) Featured image (exterior)
     * 2) Gallery image #1 (back seats)
     * 3) Gallery image #2 (passenger seat)
     * 4) Gallery image #3 (driver seat)
     */
    private function get_story_image_ids( WC_Product $product ) : array {
        $ids = [];

        $featured_id = $product->get_image_id();
        if ( $featured_id ) {
            $ids[] = (int) $featured_id;
        }

        $gallery_ids = $product->get_gallery_image_ids();
        foreach ( $gallery_ids as $gid ) {
            $ids[] = (int) $gid;
            if ( count( $ids ) >= 4 ) break;
        }

        // Ensure array length is max 4
        return array_slice( array_values( array_unique( $ids ) ), 0, 4 );
    }

    public function render_story_section() {
        if ( ! is_product() ) return;

        global $product;
        if ( ! $product instanceof WC_Product ) return;

        $image_ids = $this->get_story_image_ids( $product );

        // If you don't have at least 1 image + 1 story text, don't show.
        $texts = [
            [
                'title' => __( 'Exterior', 'larissa24' ),
                'text'  => $product->get_meta( self::META_EXT ),
            ],
            [
                'title' => __( 'Back Seats', 'larissa24' ),
                'text'  => $product->get_meta( self::META_REAR ),
            ],
            [
                'title' => __( 'Passenger Seat', 'larissa24' ),
                'text'  => $product->get_meta( self::META_PASS ),
            ],
            [
                'title' => __( 'Driver Seat', 'larissa24' ),
                'text'  => $product->get_meta( self::META_DRV ),
            ],
        ];

        $has_any_text = false;
        foreach ( $texts as $t ) {
            if ( trim( wp_strip_all_tags( (string) $t['text'] ) ) !== '' ) {
                $has_any_text = true;
                break;
            }
        }
        if ( ! $has_any_text ) return;

        echo '<section class="larissa-photo-story">';
        echo '<h2 class="larissa-photo-story__title">' . esc_html__( 'Car Photo Story', 'larissa24' ) . '</h2>';

        echo '<div class="larissa-photo-story__grid">';

        for ( $i = 0; $i < 4; $i++ ) {
            $title = $texts[$i]['title'];
            $text  = $texts[$i]['text'];

            $img_html = '';
            if ( isset( $image_ids[$i] ) && $image_ids[$i] ) {
                $img_html = wp_get_attachment_image(
                    $image_ids[$i],
                    'large',
                    false,
                    [
                        'class' => 'larissa-photo-story__img',
                        'loading' => 'lazy',
                        'alt' => esc_attr( $product->get_name() . ' — ' . $title ),
                    ]
                );
            }

            echo '<article class="larissa-photo-story__card">';
            echo '<div class="larissa-photo-story__media">' . ( $img_html ?: '' ) . '</div>';
            echo '<div class="larissa-photo-story__body">';
            echo '<h3 class="larissa-photo-story__cardtitle">' . esc_html( $title ) . '</h3>';
            if ( $text ) {
                echo '<div class="larissa-photo-story__text">' . wpautop( wp_kses_post( $text ) ) . '</div>';
            } else {
                // Optional: show nothing if empty
                echo '<div class="larissa-photo-story__text larissa-photo-story__text--empty"></div>';
            }
            echo '</div>';
            echo '</article>';
        }

        echo '</div>';
        echo '</section>';
    }

    public function enqueue_css() {
        if ( ! is_product() ) return;

        $css = "
        .larissa-photo-story{margin:40px 0;padding:0}
        .larissa-photo-story__title{margin:0 0 16px;font-size:26px;font-weight:700}
        .larissa-photo-story__grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:18px}
        .larissa-photo-story__card{background:#fff;border:1px solid rgba(0,0,0,.08);border-radius:14px;overflow:hidden}
        .larissa-photo-story__media{background:rgba(0,0,0,.03)}
        .larissa-photo-story__img{display:block;width:100%;height:auto}
        .larissa-photo-story__body{padding:14px 16px}
        .larissa-photo-story__cardtitle{margin:0 0 8px;font-size:18px;font-weight:700}
        .larissa-photo-story__text{font-size:14px;line-height:1.6}
        @media (max-width: 900px){
          .larissa-photo-story__grid{grid-template-columns:1fr}
        }";
        wp_register_style( 'larissa-photo-story-inline', false );
        wp_enqueue_style( 'larissa-photo-story-inline' );
        wp_add_inline_style( 'larissa-photo-story-inline', $css );
    }
}

new Larissa_Car_Photo_Story();
