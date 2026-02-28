<?php if ( ! defined('ABSPATH') ) exit; ?>


<?php
$settings  = get_option('trp_settings', []);
$langs     = $settings['translation-languages'] ?? ['en_us','el']; // THIS is the truth
$slugs     = $settings['url-slugs'] ?? [];

$urls      = rcl24_trp_lang_urls_safe($langs);

// figure out current language by matching the current URL's first path segment to TP slugs
$current_url = function_exists('trp_get_current_url') ? trp_get_current_url() : home_url(add_query_arg([],''));
$path = wp_parse_url($current_url, PHP_URL_PATH) ?: '/';
$seg0 = explode('/', trim($path,'/'))[0] ?? '';

$current_lang = $settings['default-language'] ?? ($langs[0] ?? 'en_US');
foreach ($slugs as $code => $slug) {
  if ($seg0 === $slug) { $current_lang = $code; break; }
}

// If only 2 languages, pick the other
$other_lang = null;
foreach ($langs as $lc) {
  if ($lc !== $current_lang) { $other_lang = $lc; break; }
}

// Labels (you can extend later)
$labels = [
  'en'    => 'English',
  'en_US' => 'English',
  'en_us' => 'English',
  'el'    => 'Ελληνικά',
  'el_GR' => 'Ελληνικά',
];
?>


<div class="rcl24-brand-container" id="rcl24-brand-container">

  <a class="rcl24-brand rcl24-brand-mobile rcl24-mobile-logo" id="rcl24-mobile-logo" href="http://rental-cars-larissa-24.local/en_us/?v=124c54355f39">
    <span class="rcl24-brand-top">RENTAL CAR</span>
    <span class="rcl24-brand-bottom">LARISSA 24</span>
  </a>
  
</div>

<div class="rcl24-handheld rcl24-handheld--blackglass">

  <!-- Account -->
  <a class="rcl24-hbtn" href="<?php echo esc_url( wc_get_page_permalink('myaccount') ); ?>" aria-label="Account">
    <i class="fas fa-user"></i>
  </a>

  <!-- Car -> Cart (switches if items in cart) -->
  <?php
    $cart_count = 0;
    if ( function_exists('WC') && WC()->cart ) {
      $cart_count = (int) WC()->cart->get_cart_contents_count();
    }

    $cars_url = home_url('/cars/');
    $cart_url = function_exists('wc_get_cart_url') ? wc_get_cart_url() : home_url('/cart/');
  ?>

  <?php if ( $cart_count > 0 ): ?>
    <a class="rcl24-hbtn rcl24-hbtn-cart" href="<?php echo esc_url($cart_url); ?>" aria-label="Cart">
      <i class="fas fa-shopping-cart"></i>
      <span class="rcl24-hbadge"><?php echo $cart_count; ?></span>
    </a>
  <?php else: ?>
    <a class="rcl24-hbtn" href="<?php echo esc_url($cars_url); ?>" aria-label="All Cars">
      <i class="fas fa-car"></i>
    </a>
  <?php endif; ?>

  <!-- Language (globe) -->
  <button class="rcl24-hbtn" type="button" id="rcl24-lang-toggle" aria-label="Language" aria-expanded="false">
    <i class="fas fa-globe"></i>

    <div class="rcl24-lang-dd" id="rcl24-lang-dd" aria-hidden="true">
    <div class="rcl24-lang-dd-inner">
  <div class="rcl24-lang-item is-active" aria-current="true">
    <span><?php echo esc_html($labels[$current_lang] ?? $current_lang); ?></span>
  </div>

  <?php if ($other_lang) : ?>
    <a class="rcl24-lang-item" href="<?php echo esc_url($urls[$other_lang] ?? '#'); ?>">
      <span><?php echo esc_html($labels[$other_lang] ?? $other_lang); ?></span>
    </a>
  <?php endif; ?>
</div>
</div>
  </button>

  <!-- Menu (hamburger) -->
  <button class="rcl24-hbtn" type="button" id="rcl24-menu-toggle" aria-label="Menu" aria-expanded="false">
    <i class="fas fa-bars"></i>
  </button>

</div>

<!-- Language panel (hidden) -->
<div class="rcl24-panel rcl24-panel-lang" id="rcl24-lang-panel" >
  <div class="rcl24-panel-inner">
    <div class="rcl24-panel-head">
      <span>Language</span>
      <button type="button" class="rcl24-panel-close" data-close="lang" aria-label="Close">
        <i class="fas fa-times"></i>
      </button>
    </div>

    <div class="rcl24-panel-body rcl24-lang">
      <?php
        // Keep your safe shortcode render (no warnings)
        $old = error_reporting();
        error_reporting($old & ~E_WARNING & ~E_NOTICE & ~E_DEPRECATED);
        echo do_shortcode('[language-switcher]');
        error_reporting($old);
      ?>
    </div>
  </div>
</div>

<!-- Menu panel (hidden) -->
<div class="rcl24-panel rcl24-panel-menu" id="rcl24-menu-panel">
  <div class="rcl24-panel-inner">
    <div class="rcl24-panel-head">
      <span>Menu</span>
      <button type="button" class="rcl24-panel-close" data-close="menu" aria-label="Close">
        <i class="fas fa-times"></i>
      </button>
    </div>

    <div class="rcl24-panel-body">
      <a class="rcl24-panel-link" href="<?php echo esc_url(home_url('/cars/')); ?>">All Cars</a>
      <a class="rcl24-panel-link" href="<?php echo esc_url( function_exists('wc_get_cart_url') ? wc_get_cart_url() : home_url('/cart/') ); ?>">Cart</a>
      <a class="rcl24-panel-link" href="<?php echo esc_url(home_url('/about/')); ?>">About</a>
      <a class="rcl24-panel-link" href="<?php echo esc_url(home_url('/contact/')); ?>">Contact</a>
      <a class="rcl24-panel-link" href="<?php echo esc_url( wc_get_page_permalink('myaccount') ); ?>">
        <?php echo is_user_logged_in() ? 'My Account' : 'Login / Register'; ?>
      </a>
    </div>
  </div>
</div>


<div class="rcl24-ribbon " id="rcl24-ribbon">
    <div class="rcl24-ribbon-inner">
        <span class="rcl24-ribbon-title">Launch Deals:</span>
        <span>25% off (1 time) on 3+ day rentals</span>
        <span class="rcl24-dot">•</span>
        <span>15% off (up to 3 times) for members</span>

    </div>
</div>
<!-- Backdrop -->
<div class="rcl24-backdrop" id="rcl24-backdrop" hidden></div>