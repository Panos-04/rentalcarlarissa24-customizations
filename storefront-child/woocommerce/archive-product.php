<?php
/**
 * Custom Shop / Cars archive template (Storefront child)
 * Path: wp-content/themes/storefront-child/woocommerce/archive-product.php
 */
defined( 'ABSPATH' ) || exit;


get_header( 'shop' );

/**
 * Keep Woo wrappers working (Storefront relies on these).
 * If you dislike breadcrumbs, remove that hook in functions.php (shown below).
 */
do_action( 'woocommerce_before_main_content' );

// Page title (shop or taxonomy)
$page_title = woocommerce_page_title( false );

// Helper: get a nice attribute value (supports "pa_fuel" style too)
$lc_get_attr = function( WC_Product $p, array $keys, $fallback = '' ) {
	foreach ( $keys as $k ) {
		$val = trim( (string) $p->get_attribute( $k ) );
		if ( $val !== '' ) return $val;
	}
	return $fallback;
};

// Helper: first product category label (e.g. Hatchback)
$lc_primary_cat = function( $product_id ) {
	$terms = get_the_terms( $product_id, 'product_cat' );
	if ( is_wp_error( $terms ) || empty( $terms ) ) return '';
	// pick first (or customize ordering later)
	return $terms[0]->name ?? '';
};
/**
 * HERO sub text should come from the actual PAGE:
 * - If we're on your "category front" pages, detect page-id-XXX from body class
 * - Else (real Shop), use Shop page
 */

 $rc24_page_id = 0;

 // 1) If you already set this global anywhere, prefer it
 if ( ! empty( $GLOBALS['rc24_category_front_page_id'] ) ) {
	 $rc24_page_id = (int) $GLOBALS['rc24_category_front_page_id'];
 }
 
 // 2) If query is spoofed into a taxonomy archive, is_page() won't work.
 //    But your body still contains "page-id-273" etc. Use that.
 if ( ! $rc24_page_id && function_exists( 'get_body_class' ) ) {
	 $classes = (array) get_body_class();
	 foreach ( $classes as $c ) {
		 if ( preg_match( '/^page-id-(\d+)$/', $c, $m ) ) {
			 $rc24_page_id = (int) $m[1];
			 break;
		 }
	 }
 }
 
 // 3) Real Woo shop archive fallback
 if ( ! $rc24_page_id && function_exists( 'wc_get_page_id' ) ) {
	 $shop_id = (int) wc_get_page_id( 'shop' );
	 if ( $shop_id > 0 ) {
		 $rc24_page_id = $shop_id;
	 }
 }
 
 // 4) Final fallback
 if ( ! $rc24_page_id ) {
	 $rc24_page_id = (int) get_queried_object_id();
 }
 
 // Prefer Excerpt
 $rc24_hero_sub = trim( (string) get_post_field( 'post_excerpt', $rc24_page_id ) );
 
 // Fallback: first paragraph of content
 if ( $rc24_hero_sub === '' ) {
	 $content = (string) get_post_field( 'post_content', $rc24_page_id );
	 $content = apply_filters( 'the_content', $content );
 
	 if ( preg_match( '/<p\b[^>]*>(.*?)<\/p>/is', $content, $m ) ) {
		 $rc24_hero_sub = wp_strip_all_tags( $m[1] );
	 } else {
		 $rc24_hero_sub = wp_strip_all_tags( $content );
	 }
 }
 
 $rc24_hero_sub = trim( wp_strip_all_tags( $rc24_hero_sub ) );

 // === RC24 segmented page bar (Cars / Family / Economy) ===
// Set these to your real URLs (keep them language-aware if needed)
$cars_url   = site_url('/en_us/cars/');
$family_url = site_url('/en_us/family/');
$econ_url   = site_url('/en_us/economy/');

// Detect current page (works with page-id too)
$is_family = is_page(270) || str_contains(trailingslashit($_SERVER['REQUEST_URI'] ?? ''), '/family/');
$is_econ   = is_page(273) || str_contains(trailingslashit($_SERVER['REQUEST_URI'] ?? ''), '/economy/');
$is_cars   = ! $is_family && ! $is_econ; // default
?>

<section class="lc-carsPage">

	<!-- HERO -->
	<header class="lc-carsHero">
		<div class="lc-carsHero__inner">
			<!-- <h1 class="lc-carsHero__title"><?php echo esc_html( $page_title ); ?></h1> -->


			<?php if ( ! empty( $rc24_hero_sub ) ) : ?>
				<p class="lc-carsHero__sub"><?php echo esc_html( $rc24_hero_sub ); ?></p>
			<?php endif; ?>

			<div class="lc-carsHero__chips" aria-label="Highlights">
				<span class="lc-chip">✅ 300 km/day</span>
				<span class="lc-chip">✅ 5 seats</span>
				<span class="lc-chip">✅ ISOFIX on request</span>
				<span class="lc-chip">✅ Transparent pricing</span>
			</div>

			<div class="lc-carsHero__cta">
				<a class="lc-btn lc-btn--primary" href="#lc-cars-grid">Browse cars</a>
				<a class="lc-btn lc-btn--ghost" href="#lc-cars-how">How it works</a>
			</div>

		</div>
	</header>


	<nav class="rc24-pagebar" aria-label="Car categories">
	<a class="rc24-pagebar__item <?php echo $is_cars ? 'is-active' : ''; ?>" href="<?php echo esc_url($cars_url); ?>">
		<?php echo esc_html__('All Cars', 'larissa24'); ?>
	</a>
	<a class="rc24-pagebar__item <?php echo $is_family ? 'is-active' : ''; ?>" href="<?php echo esc_url($family_url); ?>">
		<?php echo esc_html__('Family', 'larissa24'); ?>
	</a>
	<a class="rc24-pagebar__item <?php echo $is_econ ? 'is-active' : ''; ?>" href="<?php echo esc_url($econ_url); ?>">
		<?php echo esc_html__('Economy', 'larissa24'); ?>
	</a>
	</nav>

	<?php if ( class_exists('RC24_Inspect_Form_Renderer') ) { RC24_Inspect_Form_Renderer::render(); } ?>


	<!-- TOOLBAR (result count + ordering) -->
	<div class="lc-carsToolbar" id="lc-cars-toolbar">
		<div class="lc-carsToolbar__inner">
			<div class="lc-carsToolbar__left">
				<?php woocommerce_result_count(); ?>
			</div>
			<div class="lc-carsToolbar__right">
				<?php woocommerce_catalog_ordering(); ?>
			</div>
		</div>
	</div>

	<!-- GRID -->
	<main class="lc-carsMain" id="lc-cars-grid">
		<?php if ( woocommerce_product_loop() ) : ?>

			<div class="lc-carsGrid">
				<?php while ( have_posts() ) : the_post(); ?>
					<?php
					$product = wc_get_product( get_the_ID() );
					if ( ! $product ) continue;

					$pid   = $product->get_id();
					$link  = get_permalink( $pid );
					$title = $product->get_name();

					$thumb = $product->get_image( 'medium_large', [ 'class' => 'lc-carCard__img', 'loading' => 'lazy' ] );

					$cat_label = $lc_primary_cat( $pid );

					// Try common attribute slugs (edit these if your slugs differ)
					$seats = $lc_get_attr( $product, [ 'seats', 'pa_seats' ], '' );
					$fuel  = $lc_get_attr( $product, [ 'fuel', 'pa_fuel' ], '' );
					$gear  = $lc_get_attr( $product, [ 'transmission', 'pa_transmission', 'gearbox', 'pa_gearbox' ], '' );

					// Friendly fallbacks (no more "—")
					if ( $seats === '' ) $seats = '5';
					if ( $fuel  === '' ) $fuel  = 'Petrol/Diesel';
					if ( $gear  === '' ) $gear  = 'Manual/Auto';
					?>

					<article class="lc-carCard">
						<a class="lc-carCard__link" href="<?php echo esc_url( $link ); ?>">

							<div class="lc-carCard__top">
								<div class="lc-carCard__titleWrap">
									<h2 class="lc-carCard__title"><?php echo esc_html( $title ); ?></h2>
									<?php if ( $cat_label ) : ?>
										<div class="lc-carCard__sub"><?php echo esc_html( $cat_label ); ?></div>
									<?php endif; ?>
								</div>

								<div class="lc-carCard__badges" aria-label="Specs">
									<span class="lc-badge">👤 <?php echo esc_html( $seats ); ?></span>
									<span class="lc-badge">⛽ <?php echo esc_html( $fuel ); ?></span>
									<span class="lc-badge">⚙️ <?php echo esc_html( $gear ); ?></span>
								</div>
							</div>

							<div class="lc-carCard__media">
								<?php echo $thumb; ?>
							</div>

							<div class="lc-carCard__bottom">
								<div class="lc-carCard__perk">✅ 300 km / day included</div>
								<div class="lc-carCard__price"><?php echo $product->get_price_html(); ?></div>
								<div class="lc-carCard__cta">View & Rent →</div>
							</div>

						</a>
					</article>

				<?php endwhile; ?>
			</div>

			<div class="lc-carsPagination">
				<?php woocommerce_pagination(); ?>
			</div>

		<?php else : ?>
			<div class="lc-carsEmpty">
				<h2>No cars found</h2>
				<p>Try changing your filters or dates.</p>
			</div>
		<?php endif; ?>
	</main>

	<!-- WHY US -->
	<section class="lc-carsWhy" aria-label="Why rent with us">
		<div class="lc-carsSection__inner">
			<h2 class="lc-sectionTitle">Why rent with RC Larissa 24</h2>

			<div class="lc-whyGrid">
				<div class="lc-whyItem">✅ Clear pricing — no hidden fees</div>
				<div class="lc-whyItem">✅ 300 km/day included</div>
				<div class="lc-whyItem">✅ Easy pickup & return in Larissa</div>
				<div class="lc-whyItem">✅ ISOFIX available on request</div>
				<div class="lc-whyItem">✅ Local support if you need help</div>
				<div class="lc-whyItem">✅ Fast confirmation</div>
			</div>
		</div>
	</section>

	<!-- HOW IT WORKS -->
	<section class="lc-carsHow" id="lc-cars-how" aria-label="How it works">
		<div class="lc-carsSection__inner">
			<h2 class="lc-sectionTitle">How it works</h2>

			<div class="lc-steps">
				<div class="lc-step">
					<div class="lc-step__num">1</div>
					<div class="lc-step__title">Choose dates</div>
					<div class="lc-step__text">Select pickup & return times that fit your trip.</div>
				</div>
				<div class="lc-step">
					<div class="lc-step__num">2</div>
					<div class="lc-step__title">Pick a car</div>
					<div class="lc-step__text">Compare cars quickly and open the one you like.</div>
				</div>
				<div class="lc-step">
					<div class="lc-step__num">3</div>
					<div class="lc-step__title">Confirm booking</div>
					<div class="lc-step__text">Complete your reservation and get confirmation instantly.</div>
				</div>
			</div>
		</div>
	</section>

	<!-- FAQ -->
	<section class="lc-carsFaq" aria-label="FAQ">
		<div class="lc-carsSection__inner">
			<h2 class="lc-sectionTitle">FAQ</h2>

			<div class="lc-faq">
				<details class="lc-faqItem">
					<summary>What documents do I need?</summary>
					<div class="lc-faqBody">Driver’s license + ID/passport. Some bookings may require verification.</div>
				</details>

				<details class="lc-faqItem">
					<summary>Is there a deposit?</summary>
					<div class="lc-faqBody">For cash on pick up. You are required to pay half the total on pick-up day. The rest can be paid on the return day</div>
				</details>

				<details class="lc-faqItem">
					<summary>What is included in the price?</summary>
					<div class="lc-faqBody">Clear pricing with your selected options and coverage. 300 km/day included.</div>
				</details>

				<details class="lc-faqItem">
					<summary>Can I cancel?</summary>
					<div class="lc-faqBody">Cancellation is available for 8 hours after placing your order or 14 days before the starter renting day. If you need help, contact us.</div>
				</details>
			</div>
		</div>
	</section>

</section>

<?php
do_action( 'woocommerce_after_main_content' );

// Remove sidebar (shop feels cleaner)
 // do_action( 'woocommerce_sidebar' );

get_footer( 'shop' );