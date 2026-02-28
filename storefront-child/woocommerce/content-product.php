<?php
/**
 * The template for displaying product content within loops
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/content-product.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 9.4.0
 */

defined( 'ABSPATH' ) || exit;

global $product;

// Check if the product is a valid WooCommerce product and ensure its visibility before proceeding.
if ( ! is_a( $product, WC_Product::class ) || ! $product->is_visible() ) {
	return;
}
// Get product data
$product_name = $product->get_name();
$product_price = $product->get_price_html();
$product_image = wp_get_attachment_image_src( $product->get_image_id(), 'medium' );

// WooCommerce attributes (adjust these to match your attribute slugs)
$car_type       = $product->get_attribute('pa_car-type');
$seating        = $product->get_attribute('pa_seating-capacity');
$fuel_type      = $product->get_attribute('pa_fuel-type');
$transmission   = $product->get_attribute('pa_transmission');
?>

<li <?php wc_product_class( 'custom-product-card', $product ); ?>>
    <a href="<?php the_permalink(); ?>">
        <div class="product-container">

            <div class="car-info">
                <div class="product-name"><?php echo esc_html( $product_name ); ?></div>
                <div class="car-type"><?php echo esc_html( $car_type ); ?></div>

                <div class="car-details">
                    <?php if ( $seating ) : ?>
                        <div class="info-details car-space">
                            <i class="fa fa-user"></i>
                            <?php echo esc_html( $seating ); ?> 
                        </div>
                    <?php endif; ?>

                    <?php if ( $fuel_type ) : ?>
                        <div class="info-details car-fuel">
                        <i class="fa-solid fa-gas-pump"></i>
                            <?php echo esc_html( $fuel_type ); ?>
                        </div>
                    <?php endif; ?>

                    <?php if ( $transmission ) : ?>
                        <div class="info-details car-transmission">
                            <i class="fa fa-gears"></i>
                            <?php echo esc_html( $transmission ); ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="img-container">
                <?php if ( $product_image ) : ?>
                    <img src="<?php echo esc_url( $product_image[0] ); ?>" alt="<?php echo esc_attr( $product_name ); ?>">
                <?php endif; ?>
            </div>

            <div class="PurchaseDetails">
                <div class="givenKM">&#x2705; 300km / per day</div>
                <div class="Price"><?php echo $product_price; ?></div>
            </div>

        </div>
    </a>
</li>