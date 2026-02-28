<?php
/**
 * The template for displaying the footer.
 *
 * Contains the closing of the #content div and all content after
 *
 * @package storefront
 */

?>

		</div><!-- .col-full -->
	</div><!-- #content -->

	<?php do_action( 'storefront_before_footer' ); ?>
	<footer id="colophon" class="site-footer">
    <div class="footer-widgets">
        <section id="where-to-find-us" class="where-to-find-us">
            <div class="container">
                <h2>Where to Find Us</h2>
                <p class="subtitle">
                    Car Rentals in Larissa – Car Rental Larissa 24 – Car Sales – VIP Cars – Wedding Car Rental – Event Car Rental – VIP Car Services
                </p>

                <div class="contact-info">
                    <div class="info-block">
                        <h3>📍 Address</h3>
                        <p>26 Oresti Kanelli Street, Larissa, Greece</p>
                    </div>

                    <div class="info-block">
                        <h3>📞 Phone</h3>
                        <p><a href="tel:+306941603635">+30 694 160 3635</a></p>
                        <p><a href="tel:+306948519080">+30 694 851 9080</a></p>
                    </div>

                    <div class="info-block">
                        <h3>🕒 Working Hours</h3>
                        <p>Monday – Sunday: 09:00 – 21:00</p>
                    </div>
                </div>

                <div class="map">
                    <iframe 
                        src="https://www.google.com/maps?q=Oresti+Kanelli+26,+Larissa,+Greece&output=embed" 
                        width="100%" height="300" style="border:0;" allowfullscreen="" loading="lazy">
                    </iframe>
                    
                </div>
            </div>
        </section>
    </div>

    <div class="site-info">
        <p>&copy; <?php echo date("Y"); ?> Car Rental Larissa 24. All rights reserved.</p>
    </div>
</footer>

<?php wp_footer(); ?>

</body>
</html>
