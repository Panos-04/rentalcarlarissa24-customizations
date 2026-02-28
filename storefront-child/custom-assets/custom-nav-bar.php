<?php if ( ! defined('ABSPATH') ) exit; ?>

<div id="rcl24-scroll-sentinel" style="position:absolute;top:0;left:0;width:1px;height:1px;"></div>
<div class="rcl24-nav rcl24-nav--desktop is-sticky">

  <!-- Left brand (optional). You can remove this block if you want pure links only. -->
  <a class="rcl24-brand" href="<?php echo esc_url(home_url('/')); ?>">
    <span class="rcl24-brand-top">RENTAL CAR</span>
    <span class="rcl24-brand-bottom">LARISSA 24</span>
  </a>

  <!-- Center links -->
  <nav class="rcl24-links" aria-label="Primary navigation">
    <a href="<?php echo esc_url(home_url('/cars/')); ?>">All Cars</a>

    <?php
      // Cart link (only if WooCommerce active)
      if ( function_exists('WC') ) :
        $count = WC()->cart ? WC()->cart->get_cart_contents_count() : 0;
        $cart_url = wc_get_cart_url();
        if ( $count > 0 ) :
    ?>
      <a class="rcl24-cart-link" href="<?php echo esc_url($cart_url); ?>">
        Cart <span class="rcl24-badge"><?php echo (int) $count; ?></span>
      </a>
    <?php
        else :
    ?>
      <a class="rcl24-cart-link rcl24-cart-empty" href="<?php echo esc_url($cart_url); ?>">
        Cart
      </a>
    <?php
        endif;
      endif;
    ?>

    <a href="<?php echo esc_url(home_url('/about/')); ?>">About</a>
    <a href="<?php echo esc_url(home_url('/contact/')); ?>">Contact</a>
  </nav>

  <!-- Right utilities -->
  <div class="rcl24-utils">

        <!-- TranslatePress switcher -->

        <div class="rcl24-lang">
            <?php
                // Safely render TranslatePress switcher without breaking the page.
                // (Some TP versions throw PHP warnings/notices in the switcher.)
                $old = error_reporting();
                error_reporting($old & ~E_WARNING & ~E_NOTICE & ~E_DEPRECATED);

                if ( shortcode_exists('language-switcher') ) {
                    echo do_shortcode('[language-switcher]');
                } else {
                    // Fallback: try TP's other common shortcodes
                    echo do_shortcode('[language_switcher]');
                }

                error_reporting($old);
            ?>

        </div>

        <!-- Account / Connected (changes depending on login) -->
        <div class="rcl24-account">
        <?php if ( is_user_logged_in() ): ?>
            <a class="rcl24-btn rcl24-btn--ghost" href="<?php echo esc_url(wc_get_page_permalink('myaccount')); ?>">
            <i class="fas fa-user"></i> Connected
            </a>
        <?php else: ?>
            <a class="rcl24-btn rcl24-btn--ghost" href="<?php echo esc_url(wc_get_page_permalink('myaccount')); ?>">
            <i class="fas fa-user"></i> Account
            </a>
        <?php endif; ?>
        </div>

  </div>
</div>
<div class="rcl24-ribbon">
    <div class="rcl24-ribbon-inner">
        <span class="rcl24-ribbon-title">Launch Deals:</span>
        <span>25% off (1 time) on 3+ day rentals</span>
        <span class="rcl24-dot">•</span>
        <span>15% off (up to 3 times) for members</span>

        <a class="rcl24-ribbon-cta" href="<?php echo esc_url(wc_get_account_endpoint_url('sales')); ?>">
            My Account → Sales
        </a>
    </div>
</div>

<script>

</script>