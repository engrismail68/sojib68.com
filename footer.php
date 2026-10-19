<?php
/**
 * The footer for the Sojib68 theme.
 */
$sojib68_footer_services = function_exists( 'sojib68_get_services' ) ? array_slice( sojib68_get_services(), 0, 4, true ) : array();
$sojib68_socials         = function_exists( 'sojib68_get_social_links' ) ? sojib68_get_social_links() : array();
?>
</main>

<footer class="site-footer" id="site-footer">
	<div class="site-container footer-grid">
		<div class="footer-brand">
			<?php if ( has_custom_logo() ) : ?>
				<?php the_custom_logo(); ?>
			<?php else : ?>
				<img
					src="<?php echo esc_url( get_theme_file_uri( 'assets/images/logo.webp' ) ); ?>"
					alt="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>"
					class="footer-logo-img logo-on-dark"
					width="180"
					height="60"
					loading="lazy"
				>
			<?php endif; ?>
			<p class="footer-name"><?php esc_html_e( 'Ismail Hossain Sojib', 'sojib68' ); ?></p>
			<p class="footer-role"><?php esc_html_e( 'Land & IT Consultant', 'sojib68' ); ?></p>
			<p class="footer-desc"><?php esc_html_e( 'দলিল, জমি রেজিস্ট্রেশন ও ভূমিসংক্রান্ত বিষয়ে প্রয়োজনীয় পরামর্শ ও সহায়তা।', 'sojib68' ); ?></p>

			<?php if ( ! empty( $sojib68_socials ) ) : ?>
				<ul class="footer-socials">
					<?php foreach ( $sojib68_socials as $network => $url ) : ?>
						<li><a href="<?php echo esc_url( $url ); ?>" target="_blank" rel="noopener"><?php echo esc_html( ucfirst( $network ) ); ?></a></li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</div>

		<nav class="footer-links" aria-label="<?php esc_attr_e( 'ফুটার মেনু', 'sojib68' ); ?>">
			<h3 class="footer-heading"><?php esc_html_e( 'প্রয়োজনীয় মেনু', 'sojib68' ); ?></h3>
			<ul>
				<li><a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'হোম', 'sojib68' ); ?></a></li>
				<li><a href="<?php echo esc_url( home_url( '/#about' ) ); ?>"><?php esc_html_e( 'আমার সম্পর্কে', 'sojib68' ); ?></a></li>
				<li><a href="<?php echo esc_url( home_url( '/#services' ) ); ?>"><?php esc_html_e( 'সেবাসমূহ', 'sojib68' ); ?></a></li>
				<li><a href="<?php echo esc_url( home_url( '/#contact' ) ); ?>"><?php esc_html_e( 'যোগাযোগ', 'sojib68' ); ?></a></li>
			</ul>
		</nav>

		<nav class="footer-links" aria-label="<?php esc_attr_e( 'সেবা মেনু', 'sojib68' ); ?>">
			<h3 class="footer-heading"><?php esc_html_e( 'সেবা', 'sojib68' ); ?></h3>
			<ul>
				<?php foreach ( $sojib68_footer_services as $service ) : ?>
					<li><a href="<?php echo esc_url( home_url( '/#services' ) ); ?>"><?php echo esc_html( $service['title'] ); ?></a></li>
				<?php endforeach; ?>
			</ul>
		</nav>

		<div class="footer-contact">
			<h3 class="footer-heading"><?php esc_html_e( 'তথ্য', 'sojib68' ); ?></h3>
			<ul>
				<?php if ( defined( 'SOJIB68_PHONE' ) && SOJIB68_PHONE ) : ?>
					<li><a href="tel:<?php echo esc_attr( SOJIB68_PHONE ); ?>"><?php echo esc_html( SOJIB68_PHONE ); ?></a></li>
				<?php endif; ?>
				<?php if ( defined( 'SOJIB68_EMAIL' ) && SOJIB68_EMAIL ) : ?>
					<li><a href="mailto:<?php echo esc_attr( SOJIB68_EMAIL ); ?>"><?php echo esc_html( SOJIB68_EMAIL ); ?></a></li>
				<?php endif; ?>
				<?php if ( defined( 'SOJIB68_ADDRESS' ) && SOJIB68_ADDRESS ) : ?>
					<li><?php echo esc_html( SOJIB68_ADDRESS ); ?></li>
				<?php endif; ?>
				<?php
				$sojib68_privacy_page = get_page_by_path( 'privacy-policy' );
				$sojib68_terms_page   = get_page_by_path( 'terms-and-conditions' );
				?>
				<?php if ( $sojib68_privacy_page ) : ?>
					<li><a href="<?php echo esc_url( get_permalink( $sojib68_privacy_page ) ); ?>"><?php esc_html_e( 'Privacy Policy', 'sojib68' ); ?></a></li>
				<?php endif; ?>
				<?php if ( $sojib68_terms_page ) : ?>
					<li><a href="<?php echo esc_url( get_permalink( $sojib68_terms_page ) ); ?>"><?php esc_html_e( 'Terms & Conditions', 'sojib68' ); ?></a></li>
				<?php endif; ?>
			</ul>
		</div>
	</div>

	<div class="site-container footer-bottom">
		<p>&copy; <?php echo esc_html( gmdate( 'Y' ) ); ?> <?php bloginfo( 'name' ); ?></p>
		<p><?php esc_html_e( 'বেসরকারি পরামর্শ ও সহায়তা সেবা।', 'sojib68' ); ?></p>
	</div>
</footer>

<?php wp_footer(); ?>
</body>
</html>
