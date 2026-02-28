<?php
/**
 * Template Name: RCL24 - About
 */
if ( ! defined('ABSPATH') ) exit;

get_header(); ?>

<main id="primary" class="rcl24-page rcl24-about">
  <section class="rcl24-hero">
    <div class="rcl24-container">
      <h1>About Rental Car Larissa 24</h1>
      <p class="rcl24-sub">
        We’re a small local rental business in Larissa focused on one thing: a clean, fair, repeat-worthy experience.
        Simple pricing, real support, and cars that are ready when you are.
      </p>

      <div class="rcl24-hero-actions">
        <a class="rcl24-btn rcl24-btn-primary" href="<?php echo esc_url( home_url('/cars/') ); ?>">View All Cars</a>
        <a class="rcl24-btn rcl24-btn-ghost" href="<?php echo esc_url( home_url('/contact/') ); ?>">Contact</a>
      </div>
    </div>
  </section>

  <section class="rcl24-section">
    <div class="rcl24-container rcl24-grid-3">
      <article class="rcl24-card">
        <h3>Fair, Simple Rentals</h3>
        <p>
          Clear terms, fast booking, and support when you need it. We want you to come back — that’s our business model.
        </p>
      </article>

      <article class="rcl24-card">
        <h3>Car Sales (Coming Soon)</h3>
        <p>
          We will also list selected vehicles for sale on our website soon. If you’re looking for a reliable car,
          our sales section will be available later.
        </p>
        <span class="rcl24-badge">Coming soon</span>
      </article>

      <article class="rcl24-card">
        <h3>Event &amp; Special Rentals (Coming Soon)</h3>
        <p>
          Need a car for a wedding, photoshoot, or a special event? We’re building dedicated event options on the site.
        </p>
        <span class="rcl24-badge">Coming soon</span>
      </article>
    </div>
  </section>

  <section class="rcl24-section">
    <div class="rcl24-container rcl24-split">
      <div class="rcl24-panel">
        <h2>What we focus on</h2>
        <ul class="rcl24-list">
          <li><strong>Clean &amp; ready</strong> – cars prepared properly, every time.</li>
          <li><strong>Local convenience</strong> – pick-up in Larissa, flexible options on request.</li>
          <li><strong>Loyalty-first</strong> – member deals live inside your account (Sales tab).</li>
        </ul>
      </div>

      <div class="rcl24-panel rcl24-panel-accent">
        <h2>Our goal</h2>
        <p>
          We don’t try to look “big”. We try to be <strong>consistent</strong>.
          If we do the basics extremely well, you’ll trust us again and again.
        </p>
        <div class="rcl24-mini">
          <span>✔ Transparent pricing</span>
          <span>✔ Real support</span>
          <span>✔ Competitive local rates</span>
        </div>
      </div>
    </div>
  </section>

  <section class="rcl24-cta">
    <div class="rcl24-container">
      <h2>Ready to book?</h2>
      <p>Browse cars, choose dates, and confirm online in under a minute.</p>
      <a class="rcl24-btn rcl24-btn-primary" href="<?php echo esc_url( home_url('/cars/') ); ?>">Find a Car</a>
    </div>
  </section>
</main>

<?php get_footer(); ?>
