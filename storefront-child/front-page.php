<?php
/**
 * Redesigned Homepage Template (Storefront child theme)
 * File: storefront-child/front-page.php
 */

if ( ! defined( 'ABSPATH' ) ) exit;

get_header();

/**
 * RnB Search (keeps your shortcode)
 */
function rcl24_rnb_search() {
    if ( shortcode_exists( 'inspect_search_form' ) ) {
        echo do_shortcode('[inspect_search_form post_id="154"]');
    } else {
        echo '<p style="color:#fff;opacity:.8;">Search form is not available.</p>';
    }
}

// $hero_img = get_stylesheet_directory_uri() . '/assets/hero.png'; // <-- put your image here
$is_logged_in = is_user_logged_in();
$register_url = wc_get_page_permalink('myaccount'); // Woo My Account handles register
$sales_url    = home_url('/my-account/sales/');
?>

<div id="rcl24-home" class="rcl24-home">



  <!-- HERO -->
  <section class="rcl24-hero" style="--hero:url('<?php echo esc_url($hero_img); ?>');">
    <div class="rcl24-hero-overlay"></div>

    <div class="rcl24-container rcl24-hero-grid">
      <div class="rcl24-hero-copy">
        



      <!-- <div class="rcl24-hero-car">
        <img src="/wp-content/uploads/2025/09/audi_rs6_bg.png" alt="Audi RS6">
        <span class="rcl24-hero-car-badge">Premium • Most Valuable</span>
      </div> -->
        <h1>Car Rental in Larissa — Simple, Clean, Fair</h1>
        <p>
          We’re a small local rental business. Our goal is loyalty:
          give you a service you’ll want again and again — with competitive prices and real support.
        </p>

        <div class="rcl24-hero-cta">
          <button class="rcl24-btn rcl24-btn-primary" id="rcl24-open-search">
            Find a Car
          </button>
          <a class="rcl24-btn rcl24-btn-ghost" href="<?php echo esc_url( home_url('/all-cars/') ); ?>">
            View All Cars
          </a>
        </div>
        <div class="rs6-viewer-pro rs6-wide">
      <iframe
  class="rs6-sketchfab"
  title="Porsche Panamera Turbo S 2022"
  src="https://sketchfab.com/models/2c7269c26a6447d3874c73701532ef64/embed?autostart=1&autospin=0.25&preload=1&transparent=1&ui_infos=0&ui_controls=0&ui_watermark=0&ui_help=0&ui_settings=0&ui_inspector=0&ui_stop=0"
  frameborder="0"
  allow="autoplay; fullscreen; xr-spatial-tracking"
  allowfullscreen
  loading="eager"
  referrerpolicy="strict-origin-when-cross-origin">
</iframe>

          <div class="rs6-viewer-overlay"></div>
          <div class="rs6-viewer-label">Audi RS6 • Premium Option</div>
          
      </div>
        <div class="rcl24-trust-row">
          <div class="rcl24-pill"><i class="fas fa-road"></i> 300 km/day</div>
          <div class="rcl24-pill"><i class="fas fa-broom"></i> Clean & ready</div>
          <div class="rcl24-pill"><i class="fas fa-headset"></i> Real support</div>
        </div>


      </div>

      <div class="rcl24-hero-card">
        <div class="rcl24-hero-card-top "id="hero-card-top">
          <h3>Quick Search</h3>
          <p>Choose dates & car. Instant confirmation.</p>
        </div>
        <div class="rcl24-hero-card-body">
          <?php rcl24_rnb_search(); ?>
        </div>
        <div class="rcl24-hero-card-bottom">
          <small>
            Member deals are saved in <strong>My Account → Sales</strong>.
          </small>
        </div>
      </div>
    </div>
  </section>

  <!-- VALUE / LOYALTY -->
  <section class="rcl24-section">
    <div class="rcl24-container">

      <div class="rcl24-section-head">
        <h2>Why We’re Different</h2>
        <p>We’re new — so we focus on standards, not stories. You’ll feel the difference from the first rental.</p>
      </div>

      <div class="rcl24-cards-3">
        <div class="rcl24-card">
          <div class="rcl24-card-icon"><i class="fas fa-shield-alt"></i></div>
          <h3>Transparent Pricing</h3>
          <p>No surprises. Clear terms. Straightforward booking.</p>
        </div>
        <div class="rcl24-card">
          <div class="rcl24-card-icon"><i class="fas fa-heart"></i></div>
          <h3>Loyalty First</h3>
          <p>We aim to keep you as a customer — with member-only deals and easy repeat bookings.</p>
        </div>
        <div class="rcl24-card">
          <div class="rcl24-card-icon"><i class="fas fa-map-marker-alt"></i></div>
          <h3>Local Convenience</h3>
          <p>Pick up in Larissa. Flexible options on request.</p>
        </div>
      </div>

      <div class="rcl24-split">
        <div class="rcl24-loyalty-left">
          <h3>How Loyalty Works</h3>

          <div class="rcl24-steps">
            <div class="rcl24-step">
              <div class="rcl24-step-badge">1</div>
              <div class="rcl24-step-text">Create an account (1 minute)</div>
            </div>
            <div class="rcl24-step">
              <div class="rcl24-step-badge">2</div>
              <div class="rcl24-step-text">Get your launch coupons inside <strong>Sales</strong></div>
            </div>
            <div class="rcl24-step">
              <div class="rcl24-step-badge">3</div>
              <div class="rcl24-step-text">Return anytime — repeat customers get the best value</div>
            </div>
          </div>

          <div class="rcl24-perk-row">
            <span class="rcl24-perk">Saved in account</span>
            <span class="rcl24-perk">No spam</span>
            <span class="rcl24-perk">Auto at checkout</span>
          </div>
        </div>

        <div class="rcl24-panel- rcl24-accent">
              <div class="rcl24-loyalty-card">

      <?php if ( ! $is_logged_in ) : ?>

        <div class="rcl24-loyalty-head">
          <h3>Become a Member</h3>
          <p>Create an account in 1 minute and unlock <strong>2 free coupons</strong> for your first bookings.</p>
        </div>

        <ul class="rcl24-loyalty-list">
          <li>Coupons appear in <strong>My Account → Sales</strong> (no email spam).</li>
          <li>Member discounts work automatically at checkout.</li>
          <li>Repeat customers get the best value.</li>
        </ul>

        <div class="rcl24-loyalty-actions">
          <a class="rcl24-btn-loyalty rcl24-btn--primary" href="<?php echo esc_url($register_url); ?>">
            Register & Get Coupons
          </a>
          <a class="rcl24-btn-loyalty rcl24-btn--ghost" href="<?php echo esc_url( home_url('/all-cars/') ); ?>">
            Browse Cars
          </a>
        </div>

        <div class="rcl24-loyalty-foot">
          <span class="rcl24-lock">🔒</span>
          <span>No spam • Your coupons stay in your account</span>
        </div>

      <?php else : ?>

        <div class="rcl24-loyalty-head">
          <h3>Thanks for signing up, <?php echo esc_html( wp_get_current_user()->display_name ); ?>!</h3>
          <p>Here’s a small reward for your loyalty. Your member coupons are ready:</p>
        </div>

        <div class="rcl24-loyalty-coupons">
          <div class="rcl24-loyalty-couponbox">
            <div class="rcl24-loyalty-couponbox-title">Your Coupons</div>
              <?php
              if ( is_user_logged_in() ) {
                $user_id = get_current_user_id();

                echo '<h4>Your available coupons</h4>';
                rcl24_render_campaign_coupons( $user_id, 'available', false ); // ✅ only available, no tabs, no usage stats
              }
              ?>
          </div>
  </div>

  <div class="rcl24-loyalty-foot">
    <span class="rcl24-lock">✅</span>
    <span>Member deals are saved to your account</span>
  </div>

<?php endif; ?>

</div>
        </div>
      </div>

    </div>
  </section>

  <!-- HOW IT WORKS -->
  <section class="rcl24-section rcl24-muted-bg">
    <div class="rcl24-container">
      <div class="rcl24-section-head">
        <h2>How It Works</h2>
        <p>Fast booking. No confusion. Just drive.</p>
      </div>

      <div class="rcl24-steps">
        <div class="rcl24-step">
          <div class="rcl24-step-num">1</div>
          <h3>Pick dates</h3>
          <p>Select pickup & return. Choose your car.</p>
        </div>
        <div class="rcl24-step">
          <div class="rcl24-step-num">2</div>
          <h3>Confirm</h3>
          <p>Instant confirmation. Secure checkout.</p>
        </div>
        <div class="rcl24-step">
          <div class="rcl24-step-num">3</div>
          <h3>Pick up & go</h3>
          <p>Clean car, ready on time.</p>
        </div>
      </div>
    </div>
  </section>

  <!-- FAQ -->
  <section class="rcl24-section">
    <div class="rcl24-container">
      <div class="rcl24-section-head">
        <h2>FAQ</h2>
        <p>Quick answers to common questions.</p>
      </div>

      <div class="rcl24-faq">
        <details class="rcl24-faq-item">
          <summary>How do I use my discounts?</summary>
          <div class="rcl24-faq-body">
            Create an account, then go to <strong>My Account → Sales</strong>. Copy your coupon and apply it at checkout.
          </div>
        </details>

        <details class="rcl24-faq-item">
          <summary>Is the 25% discount available for any rental?</summary>
          <div class="rcl24-faq-body">
            It applies to rentals of <strong>3+ days</strong> and can be used <strong>one time</strong>.
          </div>
        </details>

        <details class="rcl24-faq-item">
          <summary>How many kilometers are included?</summary>
          <div class="rcl24-faq-body">
            Many rentals include up to <strong>300 km/day</strong>. Final terms show during booking.
          </div>
        </details>
      </div>
    </div>
  </section>


</div><!-- /.rcl24-home -->

<?php get_footer(); ?>
