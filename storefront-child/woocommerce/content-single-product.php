<?php
/**
 * The template for displaying product content in the single-product.php template
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/content-single-product.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 3.6.0
 */

defined( 'ABSPATH' ) || exit;

global $product;
if ( ! $product instanceof WC_Product ) {
  $product = wc_get_product( get_the_ID() );
}
if ( ! $product ) return;

/**
 * Images: 1) featured (exterior), then gallery.
 * We'll show: big main image + small thumbnails below.
 */
$gallery_ids = $product->get_gallery_image_ids() ?: [];

// Fallback: if no gallery exists, use featured so page isn't empty
if ( empty($gallery_ids) && $product->get_image_id() ) {
  $gallery_ids = [ (int) $product->get_image_id() ];
}

// Story images come from gallery positions 0-3
$story = [
  [ 'label' => 'Exterior',       'key' => '_larissa_story_exterior',   'img' => $gallery_ids[0] ?? 0 ],
  [ 'label' => 'Back seats',     'key' => '_larissa_story_rear',       'img' => $gallery_ids[1] ?? 0 ],
  [ 'label' => 'Passenger seat', 'key' => '_larissa_story_passenger',  'img' => $gallery_ids[2] ?? 0 ],
  [ 'label' => 'Driver seat',    'key' => '_larissa_story_driver',     'img' => $gallery_ids[3] ?? 0 ],
];

$fuel = trim( (string) $product->get_attribute('pa_fuel-type') );
$trans = trim( (string) $product->get_attribute('pa_transmission') );
$seats = trim( (string) $product->get_attribute('pa_seating-capacity') );
?>

<section class="lc-product">

  <!-- HERO -->
  <div class="lc-hero">

    <!-- LEFT: Gallery (no carousel, clean) -->
    <?php
// Use ONLY gallery images (featured is a transparent “outside page” asset)
// $gallery_ids = $product->get_gallery_image_ids() ?: [];

// Fallback: if no gallery exists, use featured so page isn't empty
if ( empty( $gallery_ids ) && $product->get_image_id() ) {
  $gallery_ids = [ (int) $product->get_image_id() ];
}

$main_id   = $gallery_ids[0] ?? 0;
$thumb_ids = array_slice( $gallery_ids, 0, 20 ); // limit thumbs
?>

<div class="lc-gallery" aria-label="Car photos">
  <figure class="lc-gallery__main">
    <?php if ( $main_id ) :
      $main_large = wp_get_attachment_image_url( $main_id, 'large' );
      $main_alt   = get_post_meta( $main_id, '_wp_attachment_image_alt', true );
    ?>
      <img
        class="lc-gallery__mainImg"
        data-lc-main
        src="<?php echo esc_url( $main_large ); ?>"
        alt="<?php echo esc_attr( $main_alt ?: $product->get_name() ); ?>"
        loading="eager"
      />
    <?php endif; ?>
  </figure>

  <?php if ( count( $thumb_ids ) > 1 ) : ?>
    <div class="lc-thumbs" aria-label="Photo thumbnails">
      <button type="button" class="lc-thumbs__nav is-prev" data-lc-thumbs-prev aria-label="Previous thumbnails">‹</button>

      <div class="lc-thumbs__viewport" data-lc-thumbs-viewport>
        <div class="lc-thumbs__track">
          <?php foreach ( $thumb_ids as $i => $att_id ) :
            $large = wp_get_attachment_image_url( $att_id, 'large' );
            $alt   = get_post_meta( $att_id, '_wp_attachment_image_alt', true );
          ?>
            <button
              type="button"
              class="lc-thumb <?php echo $i === 0 ? 'is-active' : ''; ?>"
              data-lc-thumb
              data-lc-large="<?php echo esc_url( $large ); ?>"
              aria-label="<?php echo esc_attr( 'Show photo ' . ( $i + 1 ) ); ?>"
            >
              <?php
                echo wp_get_attachment_image(
                  $att_id,
                  'thumbnail',
                  false,
                  [
                    'class'   => 'lc-thumb__img',
                    'loading' => 'lazy',
                    'alt'     => esc_attr( $alt ?: '' ),
                  ]
                );
              ?>
            </button>
          <?php endforeach; ?>
        </div>
      </div>

      <button type="button" class="lc-thumbs__nav is-next" data-lc-thumbs-next aria-label="Next thumbnails">›</button>
    </div>
  <?php endif; ?>
</div>


  </div>

  <!-- CONTENT -->
  <div class="lc-body">

    <!-- Quick highlights (you can later fill from attributes) -->
    <div class="lc-highlights">
      <div class="lc-highlights__item">
        <div class="lc-highlights__k">Pickup</div>
        <div class="lc-highlights__v">Larissa</div>
      </div>
      <div class="lc-highlights__item">
        <div class="lc-highlights__k">Fuel</div>
        <div class="lc-highlights__v"><?php echo esc_html( $fuel ); ?></div>
      </div>
      <div class="lc-highlights__item">
        <div class="lc-highlights__k">Transmission</div>
        <div class="lc-highlights__v"><?php echo esc_html( $trans ); ?></div>
      </div>
      <div class="lc-highlights__item">
        <div class="lc-highlights__k">Seats</div>
        <div class="lc-highlights__v"><?php echo esc_html( $seats ); ?></div>
      </div>
    </div>

    <!-- Description -->
    <article class="lc-desc">
      <h2 class="lc-h2">Overview</h2>
      <div class="lc-desc__text">
        <?php the_content(); ?>
      </div>
    </article>

   <!-- Photo Story (compact, not giant paragraphs) -->
<section class="lc-story" aria-label="Photo story">
  <h2 class="lc-h2">Photo story</h2>

  <div class="lc-story__grid">
    <?php foreach ( $story as $idx => $b ) : ?>
      <?php
        $text = $product->get_meta( $b['key'] );
        if ( ! trim( wp_strip_all_tags( (string) $text ) ) ) {
          continue;
        }

        $bg = '';
        if ( ! empty( $b['img'] ) ) {
          $bg = wp_get_attachment_image_url( (int) $b['img'], 'large' );
        }
      ?>

      <article class="lc-storyCard" <?php echo $bg ? 'style="--lc-bg:url(' . esc_url( $bg ) . ')"' : ''; ?>>
        <div class="lc-storyCard__top">
          <div class="lc-storyCard__label"><?php echo esc_html( $b['label'] ); ?></div>

          <?php if ( ! empty( $b['img'] ) ) : ?>
            <div class="lc-storyCard__thumb">
              <?php
                echo wp_get_attachment_image(
                  (int) $b['img'],
                  'thumbnail',
                  false,
                  [
                    'class'   => 'lc-storyCard__img',
                    'loading' => 'lazy',
                    'alt'     => esc_attr( $product->get_name() . ' — ' . $b['label'] ),
                  ]
                );
              ?>
            </div>
          <?php endif; ?>
        </div>

        <div class="lc-storyCard__text">
          <?php echo wpautop( wp_kses_post( $text ) ); ?>
        </div>
      </article>

    <?php endforeach; ?>
  </div>
</section>



  </div>

  <!-- Sticky price bar -->
<div class="lc-stickyBar" data-lc-sticky>
  <div class="lc-stickyBar__inner">
    <div class="lc-stickyBar__left">
      <div class="lc-stickyBar__title"><?php echo esc_html( $product->get_name() ); ?></div>
      <div class="lc-stickyBar__price"><?php echo $product->get_price_html(); ?></div>
    </div>

    <button type="button" class="lc-stickyBar__cta" data-lc-open>
      Rent now
    </button>
  </div>
</div>

<!-- Booking drawer (hidden by default) -->
<div class="lc-drawer" data-lc-drawer aria-hidden="true">
  <div class="lc-drawer__backdrop" data-lc-close></div>

  <div class="lc-drawer__panel" role="dialog" aria-modal="true" aria-label="Booking form">
    <div class="lc-drawer__head">
      <div class="lc-drawer__headLeft">
        <div class="lc-drawer__name"><?php echo esc_html( $product->get_name() ); ?></div>
        <div class="lc-drawer__price"><?php echo $product->get_price_html(); ?></div>
      </div>

      <button type="button" class="lc-drawer__close" data-lc-close aria-label="Close">✕</button>
    </div>

    <div class="lc-drawer__body">
      <?php do_action( 'woocommerce_before_add_to_cart_form' ); ?>
      <?php woocommerce_template_single_add_to_cart(); ?>
      <?php do_action( 'woocommerce_after_add_to_cart_form' ); ?>
    </div>

    <div class="lc-drawer__fine">
      By booking, you accept the rental terms & cancellation policy.
    </div>
  </div>
</div>


</section>
