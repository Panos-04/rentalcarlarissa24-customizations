<?php
/**
 * Template Name: RCL24 - Contact
 */
if ( ! defined('ABSPATH') ) exit;

get_header(); ?>

<main id="primary" class="rcl24-page rcl24-contact">
  <section class="rcl24-hero">
    <div class="rcl24-container">
      <h1>Contact</h1>
      <p class="rcl24-sub">
        Questions before booking? We reply fast. For the best deals, check your account’s <strong>Sales</strong> tab.
      </p>
    </div>
  </section>

  <section class="rcl24-section">
    <div class="rcl24-container rcl24-grid-2">
      <div class="rcl24-card">
        <h2>Reach us</h2>

        <div class="rcl24-contact-row">
          <div class="rcl24-contact-label">Email</div>
          <a class="rcl24-contact-value" href="mailto:rentalcarlarisa@gmail.com">rentalcarlarisa@gmail.com</a>
        </div>

        <div class="rcl24-contact-row">
          <div class="rcl24-contact-label">Phone</div>
          <a class="rcl24-contact-value" href="tel:+306941603635">+30 694 160 3635</a>
        </div>

        <div class="rcl24-contact-row">
          <div class="rcl24-contact-label">Hours</div>
          <div class="rcl24-contact-value">Mon – Sun: 09:00 – 21:00</div>
        </div>

        <div class="rcl24-note">
          Tip: For faster help, include your dates, car name, and pickup/return location in your message.
        </div>

        <div class="rcl24-hero-actions">
          <a class="rcl24-btn rcl24-btn-primary" href="<?php echo esc_url( home_url('/cars/') ); ?>">View Cars</a>
          <a class="rcl24-btn rcl24-btn-ghost" href="<?php echo esc_url( wc_get_account_endpoint_url('sales') ); ?>">My Deals</a>
        </div>
      </div>

      <div class="rcl24-card rcl24-card-accent">
        <h2>FAQ</h2>
        <p class="rcl24-sub-mini">Tap a question to expand.</p>

        <div class="rcl24-faq">
          <details>
            <summary>How do I book a car?</summary>
            <p>Go to “All Cars”, select your dates, choose pickup/return, and complete checkout.</p>
          </details>

          <details>
            <summary>Do you have member discounts?</summary>
            <p>Yes. Registered users can access deals in <strong>My Account → Sales</strong>.</p>
          </details>

          <details>
            <summary>What are the launch deals?</summary>
            <p>Launch deals are limited-time offers available in your account. Check the Sales tab for current coupons.</p>
          </details>

          <details>
            <summary>Can I rent for 1 day?</summary>
            <p>Yes, depending on availability. Some promotions may require a minimum number of days.</p>
          </details>

          <details>
            <summary>What documents do I need?</summary>
            <p>Typically: ID/passport and a valid driving license. If anything extra is needed, we’ll tell you before confirmation.</p>
          </details>

          <details>
            <summary>Is there a mileage limit?</summary>
            <p>Each car listing shows the included daily kilometers. If you need more, contact us.</p>
          </details>

          <details>
            <summary>Can you deliver the car to my location?</summary>
            <p>We can arrange flexible pickup/drop-off options on request (Larissa and nearby areas).</p>
          </details>

          <details>
            <summary>Can I cancel or change dates?</summary>
            <p>Policies depend on the booking terms. Contact us with your order number and we’ll help.</p>
          </details>

          <details>
            <summary>Do you support late pickup/return?</summary>
            <p>Sometimes yes. Let us know your time so we can confirm availability.</p>
          </details>

          <details>
            <summary>How do payments work?</summary>
            <p>Payments are handled through checkout. If you have a special case, contact us before booking.</p>
          </details>

          <details>
            <summary>Can I book without creating an account?</summary>
            <p>Yes, but an account helps you keep track of orders and access member deals.</p>
          </details>

          <details>
            <summary>What if I have an issue during my rental?</summary>
            <p>Contact us immediately — we respond fast and will guide you through the next steps.</p>
          </details>

          <details>
            <summary>Do you offer cars for weddings/events?</summary>
            <p>Yes — we’re preparing event-focused services on the website soon.</p>
          </details>

          <details>
            <summary>Do you sell cars?</summary>
            <p>We’ll list selected vehicles for sale on our website later.</p>
          </details>

          <details>
            <summary>What areas do you serve?</summary>
            <p>Primarily Larissa. Nearby options can be arranged on request.</p>
          </details>
        </div>
      </div>
    </div>
  </section>
</main>

<?php get_footer(); ?>
