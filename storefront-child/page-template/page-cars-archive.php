<?php
/**
 * Template Name: RC24 Shop Page (Category Front)
 */

defined('ABSPATH') || exit;

get_header();

/**
 * 1) Decide which product category this page should show.
 * Brute-force mapping by PAGE ID (as you requested).
 *
 * page-id-273 => economy page
 * page-id-270 => family page
 *
 * IMPORTANT: the values must be PRODUCT CATEGORY SLUGS (product_cat)
 */
$page_id = get_queried_object_id();

$GLOBALS['rc24_category_front_page_id'] = get_the_ID();


$forced_map = [
    273 => 'economy', // <-- CHANGE to your REAL product_cat slug
    270 => 'family',  // <-- CHANGE to your REAL product_cat slug
];

// First priority: brute force by ID
$term_slug = $forced_map[$page_id] ?? '';

// Second priority: your existing meta fallback (optional)
if (!$term_slug) {
    $term_slug = (string) get_post_meta($page_id, '_rc24_product_cat', true);
}

// Third priority: URL fallback for testing (?cat=family-cars)
if (!$term_slug && isset($_GET['cat'])) {
    $term_slug = sanitize_text_field(wp_unslash($_GET['cat']));
}

// If nothing found -> show normal page content
if (!$term_slug) {
    while (have_posts()) {
        the_post();
        the_content();
    }
    get_footer();
    exit;
}

// Resolve the term
$term = get_term_by('slug', $term_slug, 'product_cat');
if (!$term || is_wp_error($term)) {
    echo '<div class="woocommerce"><p>Category not found: ' . esc_html($term_slug) . '</p></div>';
    get_footer();
    exit;
}
/**
 * 2) Build a REAL WooCommerce archive query for that category.
 */
$paged = max(1, (int) get_query_var('paged'), (int) get_query_var('page'));

// Per page (Woo filter)
$per_page = (int) apply_filters('loop_shop_per_page', (int) get_option('posts_per_page'));

// Preserve original query so we can restore it after rendering
global $wp_query, $post;
$original_wp_query = $wp_query;
$original_post     = $post;

// Let Woo handle ordering
$ordering_args = [];
if (function_exists('WC') && WC()->query) {
    $ordering_args = WC()->query->get_catalog_ordering_args();
}

// Let Woo inject its visibility/meta filters
$meta_query = (function_exists('WC') && WC()->query) ? WC()->query->get_meta_query() : [];
$tax_query  = (function_exists('WC') && WC()->query) ? WC()->query->get_tax_query()  : [];

$tax_query[] = [
    'taxonomy' => 'product_cat',
    'field'    => 'term_id',
    'terms'    => [$term->term_id],
];

// Build the product query
$args = [
    'post_type'           => 'product',
    'post_status'         => 'publish',
    'ignore_sticky_posts' => true,
    'paged'               => $paged,
    'posts_per_page'      => $per_page,

    // THIS tells Woo it's a real product loop
    'wc_query'            => 'product_query',

    // helps conditionals/templates
    'taxonomy'            => 'product_cat',
    'term'                => $term->slug,
    'product_cat'         => $term->slug,

    'orderby'             => $ordering_args['orderby'] ?? 'menu_order title',
    'order'               => $ordering_args['order'] ?? 'ASC',
    'meta_query'          => $meta_query,
    'tax_query'           => $tax_query,
];

// Only set meta_key if Woo actually needs it
if (!empty($ordering_args['meta_key'])) {
    $args['meta_key'] = $ordering_args['meta_key'];
}

// Replace globals temporarily
$wp_query = new WP_Query($args);

// Make WP/Woo believe this is a product category archive
$wp_query->is_page              = false;
$wp_query->is_singular          = false;
$wp_query->is_archive           = true;
$wp_query->is_tax               = true;
$wp_query->is_post_type_archive = true;
$wp_query->queried_object       = $term;
$wp_query->queried_object_id    = $term->term_id;

// Also mirror to $GLOBALS for anything reading it directly
$GLOBALS['wp_query'] = $wp_query;

/**
 * 3) Render using Woo’s archive template so your modified archive-product.php is used.
 * This will output the SAME structure as the main shop page.
 */
echo '<div class="woocommerce">';

// This loads Woo templates (archive-product.php etc)
if (function_exists('wc_get_template')) {
    wc_get_template('archive-product.php');
} else {
    // fallback
    woocommerce_content();
}

echo '</div>';

/**
 * 4) Restore original query (very important to avoid breaking footer/widgets/other loops)
 */
$wp_query = $original_wp_query;
$GLOBALS['wp_query'] = $original_wp_query;

$post = $original_post;
if ($post) {
    setup_postdata($post);
}
wp_reset_postdata();

get_footer();