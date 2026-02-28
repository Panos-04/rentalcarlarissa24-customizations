<?php
/**
 * Storefront Child Theme functions
 */

/* ------------------------------
 * Theme styles / assets
 * ------------------------------ */
function storefront_child_enqueue_styles() {

    // Don't load the parent style.css on Checkout (includes /checkout/ and its endpoints)
    if ( function_exists('is_checkout') && is_checkout() ) {
        return;
    }

    // Only load on the homepage/front page
    if (  is_front_page() ) {
        return;
    }

    wp_enqueue_style(
        'storefront-parent-style',
        get_template_directory_uri() . '/style.css'
    );
}
add_action('wp_enqueue_scripts', 'storefront_child_enqueue_styles', 5);
add_action('wp_enqueue_scripts', 'storefront_child_enqueue_styles');

function childtheme_enqueue_assets() {
    wp_enqueue_style(
        'font-awesome',
        'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css',
        [],
        '6.5.1'
    );
}
add_action('wp_enqueue_scripts', 'childtheme_enqueue_assets');

/* ------------------------------
 * Storefront header/footer tweaks
 * ------------------------------ */
function custom_delete_storefront_search() {
    remove_action('storefront_header', 'storefront_product_search', 40);
}
add_action('init', 'custom_delete_storefront_search');

function disable_storefront_sticky_add_to_cart() {
    remove_action('storefront_after_footer', 'storefront_sticky_single_add_to_cart', 999);
}
add_action('init', 'disable_storefront_sticky_add_to_cart');

// (You had this override; leaving it as-is to avoid accidental changes.)
if ( ! function_exists('storefront_primary_navigation') ) {
    function storefront_primary_navigation() { ?>
        <?php
    }
}

// Move cart into the header top area (where search was)
function custom_relocate_storefront_cart() {
    remove_action('storefront_header', 'storefront_header_cart', 60);
    add_action('storefront_header', 'storefront_header_cart', 40);
}
add_action('init', 'custom_relocate_storefront_cart');

remove_action('woocommerce_before_shop_loop', 'woocommerce_catalog_ordering', 30);

// Remove Storefront's handheld footer bar (footer)
function custom_remove_handheld_footer_bar() {
    remove_action('storefront_footer', 'storefront_handheld_footer_bar', 999);
}
add_action('init', 'custom_remove_handheld_footer_bar');

// Add handheld bar to header instead

function childtheme_remove_storefront_footer() {
    remove_action('storefront_footer', 'storefront_footer_widgets', 10);
    remove_action('storefront_footer', 'storefront_credit', 20);
}
add_action('init', 'childtheme_remove_storefront_footer');


/* ------------------------------
 * Waiver plans + fees
 * ------------------------------ */
function larissa_get_waiver_plans() {
    return [
        'basic' => [
            'label'        => esc_html__('Basic (included)', 'larissa24'),
            'desc'         => esc_html__('Third-party liability only. Renter is responsible for damage to the rental vehicle.', 'larissa24'),
            'excess_label' => esc_html__('Full vehicle value', 'larissa24'),
            'rate'         => 0.00,
            'cap_days'     => 1000,
        ],
        'cdw' => [
            'label'         => esc_html__('Smart Protection', 'larissa24'),
            'desc'          => esc_html__('Collision Damage Waiver. Limits renter liability. + Damage in the cars interior ', 'larissa24'),
            'excess_amount' => 1200,
            'rate'          => 18.55,
            'cap_days'      => 1000,
        ],
        'scdw' => [
            'label'         => esc_html__('Absolute Protection – Plus', 'larissa24'),
            'desc'          => esc_html__('Further reduces renter liability.  + Damage in the cars interior   + Damage cover in the exterior parts ', 'larissa24'),
            'excess_amount' => 175,
            'rate'          => 41.25,
            'cap_days'      => 1000,
        ],
    ];
}

add_action('woocommerce_before_add_to_cart_button', function () {
    global $product;
    if (!$product || $product->get_type() !== 'redq_rental') return;

    $plans  = larissa_get_waiver_plans();
    $chosen = isset($_POST['larissa_coverage_plan']) ? sanitize_text_field($_POST['larissa_coverage_plan']) : '';
    ?>
    <div class="larissa-coverage-wrap">
        <div class="lc-header">
            <h4 class="lc-title"><?php echo esc_html__('Coverage / Liability Waiver', 'larissa24'); ?></h4>
            <span class="lc-required">*</span>
        </div>

        <div class="larissa-coverage-options">
            <?php foreach ($plans as $key => $p) :
                $pricing = ($p['rate'] > 0)
                    ? sprintf(esc_html__('%1$s / day', 'larissa24'), wp_kses_post(wc_price($p['rate'])))
                    : esc_html__('Included', 'larissa24');

                $excess_value = isset($p['excess_amount'])
                    ? wp_kses_post(wc_price($p['excess_amount']))
                    : esc_html($p['excess_label']);

                $excess_text = sprintf(esc_html__('Excess: %s', 'larissa24'), $excess_value);
                ?>
                <label class="larissa-coverage-option">
                    <input class="lc-radio" type="radio"
                           name="larissa_coverage_plan"
                           value="<?php echo esc_attr($key); ?>"
                           <?php checked($chosen, $key); ?>
                           required>
                    <div class="lc-body">
                        <div class="lc-row">
                            <span class="lc-head"><?php echo esc_html($p['label']); ?></span>
                            <span class="lc-excess"><?php echo $excess_text; ?></span>
                        </div>
                        <p class="lc-desc"><?php echo esc_html($p['desc']); ?></p>
                        <div class="lc-row">
                            <span class="lc-price"><?php echo $pricing; ?></span>
                        </div>
                    </div>
                </label>
            <?php endforeach; ?>
        </div>

        <p class="lc-note">
            <?php echo esc_html__('This is a contractual liability waiver, not an insurance policy. Coverage applies only if rental terms & traffic laws are followed.', 'larissa24'); ?>
        </p>
    </div>
    <?php
});

/* --- Robust day detection for RnB (handles many keys & date formats) --- */
function larissa_parse_dt($date = null, $time = null) {
    if (!$date) return null;
    $s = trim($date . ' ' . ($time ?? ''));
    $s = preg_replace('/\s+/', ' ', $s);

    $fmts = [
        'Y-m-d H:i','Y-m-d',
        'd/m/Y H:i','d/m/Y',
        'm/d/Y H:i','m/d/Y',
        'd-m-Y H:i','d-m-Y',
        'm-d-Y H:i','m-d-Y',
        'Y/m/d H:i','Y/m/d',
    ];
    foreach ($fmts as $f) {
        $dt = DateTime::createFromFormat($f, $s);
        if ($dt instanceof DateTime) return $dt;
    }
    $ts = strtotime($s);
    if ($ts) { $dt = new DateTime(); $dt->setTimestamp($ts); return $dt; }
    return null;
}

function larissa_rnb_days($ci) {
    $rd = $ci['redq_rental_data'] ?? $ci['rnb_data'] ?? $ci['rental_data'] ?? [];

    $startDT = $rd['pickup_datetime']  ?? $rd['start_datetime']  ?? $rd['from_datetime'] ?? null;
    $endDT   = $rd['dropoff_datetime'] ?? $rd['end_datetime']    ?? $rd['to_datetime']   ?? null;

    $startDate = $rd['pickup_date']  ?? $rd['rnb_start_date'] ?? $rd['start_date'] ?? $rd['pickup'] ?? $rd['start'] ?? $rd['from'] ?? null;
    $startTime = $rd['pickup_time']  ?? $rd['rnb_start_time'] ?? $rd['start_time'] ?? null;
    $endDate   = $rd['dropoff_date'] ?? $rd['rnb_end_date']   ?? $rd['end_date']   ?? $rd['dropoff'] ?? $rd['end'] ?? $rd['to'] ?? null;
    $endTime   = $rd['dropoff_time'] ?? $rd['rnb_end_time']   ?? $rd['end_time']   ?? null;

    $d1 = $startDT ? larissa_parse_dt($startDT, null) : larissa_parse_dt($startDate, $startTime);
    $d2 = $endDT   ? larissa_parse_dt($endDT,   null) : larissa_parse_dt($endDate,   $endTime);

    if ($d1 && $d2) {
        $secs = max(0, $d2->getTimestamp() - $d1->getTimestamp());
        $days = (int) ceil($secs / 86400);
        return max(1, $days);
    }

    foreach (['total_days','rnb_duration_days','rnb_days','booking_days','reservation_days','days'] as $k) {
        if (!empty($rd[$k]) && is_numeric($rd[$k])) return max(1, (int)$rd[$k]);
    }

    return 1;
}

add_action('woocommerce_cart_calculate_fees', function (WC_Cart $cart) {
    if (is_admin() && !defined('DOING_AJAX')) return;

    $plans = larissa_get_waiver_plans();

    foreach ($cart->get_cart() as $ci) {
        if (empty($ci['larissa_coverage']['key'])) continue;
        $key = $ci['larissa_coverage']['key'];
        if (!isset($plans[$key])) continue;

        $plan = $plans[$key];
        $rate = (float) $plan['rate'];
        if ($rate <= 0) continue;

        $days   = larissa_rnb_days($ci);
        $bill_d = min($days, (int)$plan['cap_days']);
        $fee    = $rate * $bill_d * max(1, (int)$ci['quantity']);

        $product = $ci['data'] instanceof WC_Product ? $ci['data'] : null;
        $car     = $product ? $product->get_name() : __('Vehicle');
        $label   = sprintf('Coverage — %s · %dd%s (%s)',
            $plan['label'],
            $days,
            $days > $bill_d ? ' (capped)' : '',
            $car
        );

        $cart->add_fee($label, $fee, true, $product ? $product->get_tax_class() : '');
    }
}, 20);

// Require waiver choice
add_filter('woocommerce_add_to_cart_validation', function ($passed, $product_id) {
    $product = wc_get_product($product_id);
    if ($product && $product->get_type() === 'redq_rental' && empty($_POST['larissa_coverage_plan'])) {
        wc_add_notice(__('Please select a Coverage / Liability Waiver option.'), 'error');
        return false;
    }
    return $passed;
}, 10, 2);

// Attach waiver selection to cart item
add_filter('woocommerce_add_cart_item_data', function ($cart_item_data, $product_id) {
    $product = wc_get_product($product_id);
    if ($product && $product->get_type() === 'redq_rental' && isset($_POST['larissa_coverage_plan'])) {
        $key   = sanitize_text_field($_POST['larissa_coverage_plan']);
        $plans = larissa_get_waiver_plans();
        if (isset($plans[$key])) {
            $cart_item_data['larissa_coverage'] = ['key' => $key];
        }
    }
    return $cart_item_data;
}, 10, 2);

// Save waiver on order item (admin + emails)
add_action('woocommerce_checkout_create_order_line_item', function ($item, $cart_item_key, $values, $order) {
    if (empty($values['larissa_coverage']['key'])) return;

    $key   = $values['larissa_coverage']['key'];
    $plans = larissa_get_waiver_plans();
    if (!isset($plans[$key])) return;

    $p = $plans[$key];

    $excess = isset($p['excess_amount']) ? wc_price($p['excess_amount']) : $p['excess_label'];
    $pricing = ($p['rate'] > 0)
        ? sprintf('%s/day', wc_price($p['rate']))
        : 'Included';

    $item->add_meta_data(
        'Coverage',
        sprintf('%s | Excess %s | %s', $p['label'], wp_strip_all_tags($excess), $pricing),
        true
    );
}, 10, 4);


/* ------------------------------
 * Cart behaviors
 * ------------------------------ */
add_action('init', function () {
    remove_action('woocommerce_cart_collaterals', 'woocommerce_cross_sell_display');
    add_filter('woocommerce_cross_sells_total', '__return_zero');
});

// Redirect empty cart to /cars/
add_action('template_redirect', function () {
    if (function_exists('is_cart') && is_cart() && WC()->cart && WC()->cart->is_empty()) {
        wp_safe_redirect(site_url('/cars/'));
        exit;
    }
});


/* ------------------------------
 * JS + misc
 * ------------------------------ */
add_action('wp_enqueue_scripts', function () {
    wp_enqueue_script(
        'larissa-child-js',
        get_stylesheet_directory_uri() . '/my-script.js',
        ['wp-data'],
        file_exists(get_stylesheet_directory() . '/my-script.js')
            ? filemtime(get_stylesheet_directory() . '/my-script.js')
            : wp_get_theme()->get('Version'),
        true
    );
    wp_localize_script('larissa-child-js', 'larissaSite', [
        'carsUrl' => site_url('/cars/'),
    ]);
}, 30);

// Force classic checkout rendering if a Checkout Block is present.
add_filter('the_content', function ($content) {
    if (function_exists('is_checkout') && is_checkout()) {
        if (function_exists('has_block') && has_block('woocommerce/checkout', $content)) {
            return do_shortcode('[woocommerce_checkout]');
        }
    }
    return $content;
}, 999);

add_action('after_setup_theme', function () {
    load_child_theme_textdomain('larissa24', get_stylesheet_directory() . '/languages');
});

add_filter('default_checkout_billing_country', fn() => 'GR');


/* ------------------------------
 * REST restriction (as you had it)
 * ------------------------------ */
remove_action('wp_head', 'wp_generator');

add_filter('rest_authentication_errors', function ($result) {
    if (!empty($result)) return $result;
    if (is_user_logged_in()) return $result;

    $uri = $_SERVER['REQUEST_URI'] ?? '';
    if (
        strpos($uri, '/wp-json/wc/store') !== false ||
        strpos($uri, '/wp-json/wc/') !== false ||
        strpos($uri, '/wp-json/woocommerce/') !== false
    ) {
        return $result;
    }

    return new WP_Error('rest_cannot_access', 'Nope', ['status' => 401]);
}, 10);


/* ------------------------------
 * Filters shortcodes (Inspect + category switch)
 * ------------------------------ */

// [rc24_cat_switch all="/en/cars/" family="/en/family-cars/" economy="/en/economical-cars/"]
add_shortcode('rc24_cat_switch', function ($atts = []) {
    $atts = shortcode_atts([
        'all'     => '',
        'family'  => '',
        'economy' => '',
    ], $atts, 'rc24_cat_switch');

    $map = [
        'all'     => ['label' => __('All Cars','rc24'), 'url' => $atts['all']],
        'family'  => ['label' => __('Family','rc24'),   'url' => $atts['family']],
        'economy' => ['label' => __('Economy','rc24'),  'url' => $atts['economy']],
    ];

    if (empty($map['all']['url'])) $map['all']['url'] = wc_get_page_permalink('shop');

    if (empty($map['family']['url'])) {
        $t = get_term_by('slug', 'family-cars', 'product_cat');
        if ($t && !is_wp_error($t)) $map['family']['url'] = get_term_link($t);
    }

    if (empty($map['economy']['url'])) {
        $t = get_term_by('slug', 'economy-cars', 'product_cat');
        if ($t && !is_wp_error($t)) $map['economy']['url'] = get_term_link($t);
    }

    ob_start(); ?>
    <div class="rc24-cat-switch">
        <label for="rc24-cat-switch-select" class="screen-reader-text"><?php esc_html_e('Browse cars','rc24'); ?></label>
        <select id="rc24-cat-switch-select">
            <?php foreach ($map as $item):
                if (empty($item['url'])) continue; ?>
                <option value="<?php echo esc_url($item['url']); ?>"><?php echo esc_html($item['label']); ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <script>
    (function(){
      var s = document.getElementById('rc24-cat-switch-select');
      if(!s) return;
      s.addEventListener('change', function(){ if(this.value){ location.href = this.value; } });
    })();
    </script>
    <?php
    return ob_get_clean();
});

// [rc24_filters] -> modal containing category switch + Inspect form
add_shortcode('rc24_filters', function ($atts = []) {
    ob_start(); ?>
    <div class="custom-filters" id="custom-filters">
        <div class="filters-title">
            <div class="text"><?php esc_html_e('Filters','rc24'); ?></div>
            <p><a class="close-icon" id="close-icon"><i class="fa fa-times" aria-hidden="true"></i></a></p>
        </div>
        <div class="custom-search">
            <?php echo do_shortcode('[rc24_cat_switch all="/en/cars/" family="/en/family-cars/" economy="/en/economical-cars/"]'); ?>
            <?php echo do_shortcode('[inspect_search_form post_id="154"]'); ?>
        </div>
    </div>
    <?php
    return ob_get_clean();
});

// Optional: allow ?rc24_scope=family-cars to filter the main shop query
add_action('pre_get_posts', function ($q) {
    if (is_admin() || !$q->is_main_query()) return;

    if ((is_shop() || is_post_type_archive('product')) && !empty($_GET['rc24_scope'])) {
        $term = sanitize_text_field(wp_unslash($_GET['rc24_scope']));
        $taxq = (array) $q->get('tax_query');
        $taxq[] = [
            'taxonomy' => 'product_cat',
            'field'    => 'slug',
            'terms'    => [$term],
        ];
        $q->set('tax_query', $taxq);
    }
});


/* ------------------------------
 * Sorting row wrapper
 * ------------------------------ */
add_action('after_setup_theme', function () {
    remove_action('woocommerce_before_shop_loop', 'woocommerce_result_count', 20);
    remove_action('woocommerce_before_shop_loop', 'woocommerce_catalog_ordering', 30);
});

add_action('woocommerce_before_shop_loop', function () {
    echo '<div class="rc24-sorting">';
        echo '<div class="rc24-sorting__order">';
            woocommerce_catalog_ordering();
        echo '</div>';
        echo '<div class="rc24-sorting__count">';
            woocommerce_result_count();
        echo '</div>';
    echo '</div>';
}, 25);

add_filter('woocommerce_catalog_orderby', function ($sortby) {
    $map = [
        'menu_order' => 'Recommended',
        'popularity' => 'Popular',
        'rating'     => 'Top rated',
        'date'       => 'Newest',
        'price'      => 'Price: low → high',
        'price-desc' => 'Price: high → low',
    ];
    foreach ($map as $k => $v) {
        if (isset($sortby[$k])) $sortby[$k] = $v;
    }
    return $sortby;
});


/* ------------------------------
 * Single product custom assets
 * ------------------------------ */
add_action('wp_enqueue_scripts', function () {
    if (function_exists('is_product') && is_product()) {
        $file = get_stylesheet_directory() . '/assets/css/custom/single-product.css';
        $ver  = file_exists($file) ? filemtime($file) : wp_get_theme()->get('Version');

        wp_enqueue_style(
            'larissa-single-product',
            get_stylesheet_directory_uri() . '/assets/css/custom/single-product.css',
            [],
            $ver
        );
    }
}, 999);

add_action('wp_enqueue_scripts', function () {
    if (function_exists('is_product') && is_product()) {
        $rel  = '/assets/js/custom/single-product.js';
        $file = get_stylesheet_directory() . $rel;
        $ver  = file_exists($file) ? filemtime($file) : wp_get_theme()->get('Version');

        wp_enqueue_script(
            'larissa-single-product',
            get_stylesheet_directory_uri() . $rel,
            [],
            $ver,
            true
        );
    }
}, 999);



/**
 * Helper: map verification status to a class suffix.
 */
function larissa_get_verification_status_class( $user_id ) {
	$status  = get_user_meta( $user_id, 'larissa_verification_status', true );
	$allowed = array( 'verified', 'pending', 'rejected' );
	if ( ! in_array( $status, $allowed, true ) ) {
		$status = 'pending';
	}
	return ' status--' . $status;
}

/**
 * Build the account pill markup.
 */
function larissa_get_account_pill_html() {
	$href = wc_get_page_permalink( 'myaccount' );

	if ( is_user_logged_in() ) {
		$label        = __( 'Connected', 'larissa24' );
		$status_class = larissa_get_verification_status_class( get_current_user_id() );
	} else {
		$label        = __( 'Register', 'larissa24' );
		$status_class = '';
	}

	ob_start(); ?>
	<a class="header-pill header-pill--account<?php echo esc_attr( $status_class ); ?>"
	   href="<?php echo esc_url( $href ); ?>">
		<span class="pill-icon" aria-hidden="true">
			<svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor" role="img">
				<path d="M12 12a5 5 0 1 0-5-5 5 5 0 0 0 5 5zm0 2c-4.33 0-8 2.24-8 5v1h16v-1c0-2.76-3.67-5-8-5z"/>
			</svg>
		</span>
		<span class="pill-text"><?php echo esc_html( $label ); ?></span>
	</a>
	<?php
	return ob_get_clean();
}



add_action( 'wp_enqueue_scripts', function() {

    // Only load on WooCommerce single product pages
    if ( function_exists('is_product') && is_product() ) {

        $file = get_stylesheet_directory() . '/assets/css/custom/single-product.css';
        $ver  = file_exists($file) ? filemtime($file) : wp_get_theme()->get('Version');

        // Enqueue AFTER theme styles by using a late priority (999)
        wp_enqueue_style(
            'larissa-single-product',
            get_stylesheet_directory_uri() . '/assets/css/custom/single-product.css',
            [],     // no dependencies needed
            $ver
        );
    }

}, 999 );
add_action( 'wp_enqueue_scripts', function () {

    if ( function_exists('is_product') && is_product() ) {

        $rel  = '/assets/js/custom/single-product.js';
        $file = get_stylesheet_directory() . $rel;
        $ver  = file_exists( $file ) ? filemtime( $file ) : wp_get_theme()->get('Version');

        wp_enqueue_script(
            'larissa-single-product',
            get_stylesheet_directory_uri() . $rel,
            [],      // dependencies (add 'jquery' if you use it)
            $ver,
            true     // load in footer
        );
    }

}, 999 );

    // Add the tabs above the two forms
    add_action('woocommerce_before_customer_login_form', function () {
        if ( ! function_exists('is_account_page') || ! is_account_page() ) return; ?>
        <nav class="acc-switch" role="tablist" aria-label="<?php esc_attr_e('Account', 'larissa24'); ?>">
            <button class="acc-tab is-active" data-target="login" role="tab" aria-selected="true" aria-controls="acc-panel-login" id="acc-tab-login">
                <?php esc_html_e('Login', 'larissa24'); ?>
            </button>
            <button class="acc-tab" data-target="register" role="tab" aria-selected="false" aria-controls="acc-panel-register" id="acc-tab-register">
                <?php esc_html_e('Register', 'larissa24'); ?>
            </button>
        </nav>
    <?php });
    
    
    


// ---------------------------------------------


add_action( 'wp_enqueue_scripts', function () {

    $is_wc_archive = ( function_exists('is_shop') && is_shop() )
        || ( function_exists('is_product_taxonomy') && is_product_taxonomy() );

    // Load also on your custom "category front" pages (template OR page IDs)
    $is_rc24_front = is_page_template('page-rc24-shop-front.php') || is_page([270, 273]);

    if ( $is_wc_archive || $is_rc24_front ) {

        $rel  = '/assets/css/custom/cars.css';
        $file = get_stylesheet_directory() . $rel;
        $ver  = file_exists($file) ? filemtime($file) : wp_get_theme()->get('Version');

        wp_enqueue_style(
            'larissa-cars-archive',
            get_stylesheet_directory_uri() . $rel,
            [],
            $ver
        );
    }

}, 999 );

add_action('woocommerce_before_shop_loop', 'rc24_output_inspect_form_contextual', 15);

function rc24_output_inspect_form_contextual() {
    // Only on product archives (shop + categories + tags)
    if ( ! ( is_shop() || is_product_taxonomy() ) ) return;

    // Map slugs -> Inspect Builder post_id
    // Change these IDs to your real Inspect Builder IDs
    $map = [
        'economy' => 201,  // economy builder post_id
        'family'  => 202,  // family builder post_id
    ];

    // Default builder for main shop (All Cars)
    $default_builder_id = 154;

    $builder_id = $default_builder_id;

    // If this is a product category page, choose based on category slug
    if ( is_product_category() ) {
        $term = get_queried_object(); // WP_Term
        if ( $term && ! empty($term->slug) && isset($map[$term->slug]) ) {
            $builder_id = (int) $map[$term->slug];
        }
    }

    echo '<div class="rc24-inspect-wrap" data-inspect-builder="' . esc_attr($builder_id) . '">';
    echo do_shortcode('[inspect_search_form post_id="' . (int) $builder_id . '"]');
    echo '</div>';
}




add_action('add_meta_boxes', function () {
    add_meta_box(
      'rc24_shop_term',
      'RC24 Shop Category',
      function ($post) {
        $val = get_post_meta($post->ID, '_rc24_product_cat', true);
        wp_nonce_field('rc24_shop_term_save', 'rc24_shop_term_nonce');
  
        echo '<p>Select product category slug to render on this page.</p>';
        echo '<input style="width:100%" type="text" name="rc24_product_cat" value="' . esc_attr($val) . '" placeholder="e.g. family-cars">';
        echo '<p style="opacity:.7">Example slugs: <code>family-cars</code>, <code>economy-cars</code></p>';
      },
      'page',
      'side'
    );
  });
  
  add_action('save_post_page', function ($post_id) {
    if (!isset($_POST['rc24_shop_term_nonce']) || !wp_verify_nonce($_POST['rc24_shop_term_nonce'], 'rc24_shop_term_save')) return;
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_page', $post_id)) return;
  
    $slug = isset($_POST['rc24_product_cat']) ? sanitize_text_field(wp_unslash($_POST['rc24_product_cat'])) : '';
    update_post_meta($post_id, '_rc24_product_cat', $slug);
  });
  

  

  if ( ! class_exists('RC24_Inspect_Form_Renderer') ) {
  
    class RC24_Inspect_Form_Renderer {
  
      /**
       * Map: page_id => inspect_form_post_id
       * Change these IDs to your real Inspect form post IDs.
       */
      private static function map(): array {
        return [
          270 => 276, // FAMILY page (page-id-270) -> Inspect form post_id (CHANGE THIS)
          273 => 279, // ECONOMY page (page-id-273) -> Inspect form post_id (CHANGE THIS)
          'default' => 154, // default shop/cars page Inspect form
        ];
      }
  
      /**
       * Determine current "front page" id (works on your spoofed category pages too).
       */
      public static function current_front_page_id(): int {
        if ( ! empty($GLOBALS['rc24_category_front_page_id']) ) {
          return (int) $GLOBALS['rc24_category_front_page_id'];
        }
  
        // Normal shop/category pages fallback
        $qo = get_queried_object();
        if ( $qo && isset($qo->ID) ) {
          return (int) $qo->ID;
        }
  
        return 0;
      }
  
      /**
       * Resolve inspect form post_id.
       */
      public static function resolve_form_id(): int {
        $map     = self::map();
        $page_id = self::current_front_page_id();
  
        // If page is one of our category front pages, return mapped form id
        if ( $page_id && isset($map[$page_id]) && (int)$map[$page_id] > 0 ) {
          return (int) $map[$page_id];
        }
  
        return (int) ($map['default'] ?? 154);
      }
  
      /**
       * Render the inspect shortcode wrapper.
       */
      public static function render(): void {
        if ( ! function_exists('do_shortcode') ) return;
  
        $form_id = self::resolve_form_id();
        if ( $form_id <= 0 ) return;
  
        echo '<div class="rc24-inspect-wrap">';
        echo do_shortcode('[inspect_search_form post_id="' . (int)$form_id . '"]');
        echo '</div>';
      }
    }
  }
  

  add_action( 'wp_enqueue_scripts', function () {

    $is_cars_context =
        ( function_exists('is_shop') && is_shop() ) ||
        ( function_exists('is_product_taxonomy') && is_product_taxonomy() ) ||
        ( function_exists('is_page_template') && is_page_template('page-cars-archive.php') ) ||
        is_page( [270, 273] ); // family/economy front pages

    if ( ! $is_cars_context ) return;

    // CSS
    $css_rel  = '/assets/css/custom/cars.css';
    $css_file = get_stylesheet_directory() . $css_rel;
    $css_ver  = file_exists($css_file) ? filemtime($css_file) : wp_get_theme()->get('Version');

    wp_enqueue_style(
        'larissa-cars-archive',
        get_stylesheet_directory_uri() . $css_rel,
        [],
        $css_ver
    );

    // JS (collapse)
    $js_rel  = '/assets/js/custom/inspect-collapse.js';
    $js_file = get_stylesheet_directory() . $js_rel;
    $js_ver  = file_exists($js_file) ? filemtime($js_file) : wp_get_theme()->get('Version');

    wp_enqueue_script(
        'rc24-inspect-collapse',
        get_stylesheet_directory_uri() . $js_rel,
        [],
        $js_ver,
        true
    );

}, 999 );
// ------------------




function rcl24_render_campaign_coupons( $user_id, $view = 'available', $show_tabs = true ) {

    if ( ! $user_id ) {
        echo '<p>Please log in to view your coupons.</p>';
        return;
    }

    // Campaign coupons
    $campaign = [
        [
            'code'  => 'RCL25',
            'title' => '25% off (one-time)',
            'rules' => 'Valid on rentals of 3+ days',
        ],
        [
            'code'  => 'RCL15',
            'title' => '15% off (up to 3 uses)',
            'rules' => 'Valid for registered users',
        ],
    ];

    $view = ($view === 'used') ? 'used' : 'available';

    // Optional tabs (only for Sales page)
    if ( $show_tabs ) {
        $base_url      = wc_get_account_endpoint_url('sales');
        $available_url = esc_url($base_url);
        $used_url      = esc_url(add_query_arg('view', 'used', $base_url));

        echo '<div class="my-sales-tabs" style="margin-bottom:16px;">';
        echo '<a class="button" href="'.$available_url.'" style="margin-right:8px;">Available</a>';
        echo '<a class="button" href="'.$used_url.'">Used</a>';
        echo '</div>';

        echo '<h3>'.($view === 'used' ? 'Used coupons' : 'Available coupons').'</h3>';
    }

    $any = false;

    foreach ($campaign as $c) {
        $code = $c['code'];

        $coupon_id = wc_get_coupon_id_by_code($code);
        if (!$coupon_id) continue;

        $coupon = new WC_Coupon($code);

        $limit_per_user = (int) $coupon->get_usage_limit_per_user(); // 0 = unlimited
        $used_count     = my_count_coupon_uses_for_user($user_id, $code);

        $remaining = $limit_per_user > 0 ? max(0, $limit_per_user - $used_count) : null;
        $is_used_up = ($limit_per_user > 0 && $remaining === 0);

        // FILTERING
        if ($view === 'available' && $is_used_up) continue;
        if ($view === 'used' && !$is_used_up && $used_count === 0) continue;

        $any = true;

        echo '<div class="rcl24-coupon-card" style="border:1px solid #444;padding:14px;border-radius:10px;margin:12px 0;">';
        echo '<strong style="font-size:16px;color:#fff;">'.$c['title'].'</strong><br>';
        echo '<code style="font-size:15px;color:#fff;background:#e80721;font-weight:800;padding:4px 8px;border-radius:8px;display:inline-block;margin-top:6px;">'.esc_html($code).'</code><br>';
        echo '<small style="color:rgba(255,255,255,.85);display:block;margin-top:6px;">'.esc_html($c['rules']).'</small>';

        // Optional stats (you can hide these outside sales page)
        if ( $show_tabs ) {
            if ($limit_per_user > 0) {
                echo '<p style="margin:10px 0 0;color:#fff;">Used: <strong>'.(int)$used_count.'</strong> / '.(int)$limit_per_user.' — Remaining: <strong>'.(int)$remaining.'</strong></p>';
            } else {
                echo '<p style="margin:10px 0 0;color:#fff;">Used: <strong>'.(int)$used_count.'</strong></p>';
            }
        }

        echo '</div>';
    }

    if (!$any) {
        echo '<p>No coupons to show here right now.</p>';
    }
}



/**
 * 1) Register /my-account/sales/ endpoint
 */
add_action('init', function () {
    add_rewrite_endpoint('sales', EP_ROOT | EP_PAGES);
});

add_filter('woocommerce_account_menu_items', function ($items) {
    // Insert Sales after Dashboard (adjust as you like)
    $new = [];
    foreach ($items as $key => $label) {
        $new[$key] = $label;
        if ($key === 'dashboard') {
            $new['sales'] = 'Sales';
        }
    }
    return $new;
}, 20);



/**
 * 2) Helper: count how many times a user used a coupon in paid-ish orders
 */
function my_count_coupon_uses_for_user($user_id, $coupon_code) {
    $orders = wc_get_orders([
        'customer_id' => $user_id,
        'status'      => ['wc-processing', 'wc-completed', 'wc-on-hold'],
        'limit'       => 200, // keep it safe; increase if needed
        'orderby'     => 'date',
        'order'       => 'DESC',
        'return'      => 'objects',
    ]);

    $count = 0;
    $needle = strtolower($coupon_code);

    foreach ($orders as $order) {
        // get_coupon_codes() is the modern method
        $codes = array_map('strtolower', $order->get_coupon_codes());
        if (in_array($needle, $codes, true)) {
            $count++;
        }
    }
    return $count;
}
add_action('woocommerce_account_sales_endpoint', function () {

    if (!is_user_logged_in()) {
        echo '<p>Please log in to view your sales.</p>';
        return;
    }

    $user_id = get_current_user_id();
    $view    = (isset($_GET['view']) && $_GET['view'] === 'used') ? 'used' : 'available';

    rcl24_render_campaign_coupons( $user_id, $view, true );
});

add_action('wp_enqueue_scripts', function () {
    if ( function_exists('is_checkout') && is_checkout() ) {

        $rel  = '/assets/css/custom/checkout.css';
        $file = get_stylesheet_directory() . $rel;
        $ver  = file_exists($file) ? filemtime($file) : wp_get_theme()->get('Version');

        wp_enqueue_style(
            'larissa-checkout',
            get_stylesheet_directory_uri() . $rel,
            [],
            $ver
        );
    }
}, 999);

add_action('wp', function () {

    // Only on checkout page
    if ( ! function_exists('is_checkout') || ! is_checkout() ) {
        return;
    }

    // 1) Remove the default coupon notice/form at the top
    remove_action('woocommerce_before_checkout_form', 'woocommerce_checkout_coupon_form', 10);

});

add_action('wp_enqueue_scripts', function () {

    // Only load on the homepage/front page
    if ( ! is_front_page() ) {
        return;
    }

    $rel_path = '/assets/css/custom/homepage.css';
    $file     = get_stylesheet_directory() . $rel_path;
    $url      = get_stylesheet_directory_uri() . $rel_path;

    // Use file modified time as version (cache-busting)
    $ver = file_exists($file) ? filemtime($file) : null;

    wp_enqueue_style(
        'rcl24-homepage',
        $url,
        [],
        $ver
    );

}, 20);




add_action('wp', function () {

    // Remove default Storefront header parts
    remove_action('storefront_header', 'storefront_header_container', 0);
    remove_action('storefront_header', 'storefront_skip_links', 5);
    remove_action('storefront_header', 'storefront_site_branding', 20);
    remove_action('storefront_header', 'storefront_secondary_navigation', 30);
    remove_action('storefront_header', 'storefront_primary_navigation', 50);
    remove_action('storefront_header', 'storefront_header_container_close', 41);

    // Add our custom header at the same hook
    add_action('storefront_header', 'rcl24_render_custom_header', 5);
});

function rcl24_render_custom_header() {

    $base = trailingslashit(get_stylesheet_directory()) . 'custom-assets/';

    $desktop = $base . 'custom-nav-bar.php';
    $mobile  = $base . 'handheld-nav-bar.php';

    echo '<header id="rcl24-header" class="rcl24-header" role="banner">';

    // Desktop
    if ( file_exists($desktop) ) {
        echo '<div class="rcl24-header-desktop">';
        include $desktop;
        echo '</div>';
    }

    // Handheld
    if ( file_exists($mobile) ) {
        echo '<div class="rcl24-header-handheld">';
        include $mobile;
        echo '</div>';
    }

    echo '</header>';
}


// Remove Woo/Storefront header cart
remove_action('storefront_header', 'storefront_header_cart', 60);

// Remove any remaining navigation wrappers (some versions/themes add these)
remove_action('storefront_header', 'storefront_primary_navigation_wrapper', 42);
remove_action('storefront_header', 'storefront_primary_navigation_wrapper_close', 68);

remove_action('storefront_before_header', 'storefront_handheld_navigation', 999);
remove_action('storefront_before_header', 'storefront_handheld_footer_bar', 999);
remove_action('storefront_header', 'storefront_handheld_navigation', 999);
remove_action('storefront_header', 'storefront_handheld_footer_bar', 999);

add_action('wp', function () {

    // Try common priorities just in case
    remove_action('storefront_header', 'storefront_header_cart', 60);
    remove_action('storefront_header', 'storefront_header_cart', 40);
    remove_action('storefront_header', 'storefront_header_cart', 50);

}, 30);


function rcl24_trp_languages() {
    // Returns array like: [ 'en_US' => ['url'=>..., 'name'=>..., 'slug'=>...], ... ]
    if ( function_exists('trp_custom_language_switcher') ) {
        // TranslatePress helper that returns languages with urls
        return trp_custom_language_switcher();
    }

    // Fallback: nothing
    return [];
}

function rcl24_lang_url( $target_slug ) {
    // Current request path (no domain)
    $uri = $_SERVER['REQUEST_URI'] ?? '/';
    $uri = strtok($uri, '?'); // remove query

    // Normalize
    $uri = '/' . ltrim($uri, '/');

    // Your language slugs (adjust if needed)
    $slugs = ['en_us', 'el'];

    // Remove any existing language prefix
    foreach ($slugs as $s) {
        $prefix = '/' . $s . '/';
        if (strpos($uri, $prefix) === 0) {
            $uri = substr($uri, strlen('/'.$s));
            if ($uri === '') $uri = '/';
            break;
        }
        if ($uri === '/'.$s) { // just "/en_us"
            $uri = '/';
            break;
        }
    }

    // Build new URL
    $target_slug = trim($target_slug, '/');
    $path = '/' . $target_slug . $uri;

    // Clean double slashes
    $path = preg_replace('#/+#', '/', $path);

    return home_url($path);
}


add_action( 'wp_enqueue_scripts', function () {
    if (function_exists("is_front_page")){
        $js_rel ='/assets/js/custom/custom-homepage.js';
        $js_file = get_stylesheet_directory() . $js_rel ;
        $js_ver  = file_exists($js_file) ? filemtime($js_file) : wp_get_theme()->get('Version');
    }

    wp_enqueue_script(
        'rc24-inspect-collapse',
        get_stylesheet_directory_uri() . $js_rel,
        [],
        $js_ver,
        true
    );
},999);


/**
 * Reliable TranslatePress URL builder (with fallback to slug rewrite).
 * Works even if trp_translate_url() returns unchanged.
 */
function rcl24_trp_lang_urls_safe( array $langs = [] ) : array {
    $settings = get_option('trp_settings', []);
    $tp_langs = $settings['translation-languages'] ?? [];

    if (empty($langs)) {
        $langs = !empty($tp_langs) ? $tp_langs : ['en_US','el'];
    }

    $slugs = $settings['url-slugs'] ?? []; // e.g. ['en_US'=>'en_us','el'=>'el'] or ['el_GR'=>'el']

    // Get current full URL (keep query string)
    if (function_exists('trp_get_current_url')) {
        $current_url = trp_get_current_url();
    } else {
        $scheme = is_ssl() ? 'https://' : 'http://';
        $current_url = $scheme . ($_SERVER['HTTP_HOST'] ?? '') . ($_SERVER['REQUEST_URI'] ?? '/');
    }

    // Parse URL for manual rebuild
    $p = wp_parse_url($current_url);
    $root = ($p['scheme'] ?? 'http') . '://' . ($p['host'] ?? '');
    if (!empty($p['port'])) $root .= ':' . $p['port'];

    $path     = $p['path'] ?? '/';
    $query    = isset($p['query']) ? ('?' . $p['query']) : '';
    $fragment = isset($p['fragment']) ? ('#' . $p['fragment']) : '';

    // Strip existing language slug from path if present
    $all_slugs = array_values($slugs);
    $segments  = explode('/', trim($path, '/'));
    if (!empty($segments[0]) && in_array($segments[0], $all_slugs, true)) {
        array_shift($segments);
    }
    $base_path = '/' . implode('/', $segments);
    if ($base_path === '/') { /* ok */ }

    $out = [];

    foreach ($langs as $lang_code) {
        $tp_url = $current_url;

        // 1) Try TranslatePress first
        if (function_exists('trp_translate_url')) {
            $try = trp_translate_url($current_url, $lang_code);
            if (!empty($try)) $tp_url = $try;
        }

        // 2) If TP returns unchanged, do a slug rewrite fallback
        if ($tp_url === $current_url) {
            $slug = $slugs[$lang_code] ?? '';
            if ($slug) {
                $tp_url = $root . '/' . trim($slug, '/') . $base_path . $query . $fragment;
            } else {
                // last resort: keep current
                $tp_url = $current_url;
            }
        }

        $out[$lang_code] = $tp_url;
    }

    return $out;
}