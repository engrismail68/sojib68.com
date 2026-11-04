<?php
/**
 * One জমি ও প্রপার্টি advertisement: full details and the way to connect through the owner.
 * The seller's number is printed here only for an "open" listing; otherwise it is handed out by
 * ajax after the buyer leaves a name and mobile (inc/properties.php, assets/js/property.js).
 */

get_header();

while ( have_posts() ) :
	the_post();
	$id       = get_the_ID();
	$types    = sojib68_property_types();
	$cats     = sojib68_property_categories();
	$sts      = sojib68_property_statuses();
	$units    = sojib68_property_units();
	$type     = sojib68_property_meta( $id, 'type', 'sale' );
	$cat      = sojib68_property_meta( $id, 'category', 'land' );
	$st       = sojib68_property_meta( $id, 'status', 'available' );
	$loc      = sojib68_property_meta( $id, 'location' );
	$size     = sojib68_property_size_text( $id );
	$images   = sojib68_property_image_ids( $id );
	$features = array_filter( array_map( 'trim', preg_split( '/\r\n|\r|\n/', (string) sojib68_property_meta( $id, 'features' ) ) ) );
	$wa_url   = sojib68_property_whatsapp_url( $id );
	$mode     = sojib68_property_meta( $id, 'contact_mode', 'gate' );
	$seller   = 'open' === $mode ? sojib68_property_seller_contact( $id ) : ( sojib68_property_seller_contact( $id ) ? true : null ); // the number itself is only printed in "open" mode
	$phone    = ( defined( 'SOJIB68_PHONE' ) && SOJIB68_PHONE ) ? SOJIB68_PHONE : '';
	$back     = get_post_type_archive_link( SOJIB68_PROPERTY_CPT );
	?>

	<div class="prop-page prop-single">
		<section class="prop-single__top">
			<div class="site-container">
				<nav class="prop-crumbs" aria-label="<?php esc_attr_e( 'পথ', 'sojib68' ); ?>">
					<a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'হোম', 'sojib68' ); ?></a> <span aria-hidden="true">›</span>
					<a href="<?php echo esc_url( $back ); ?>"><?php esc_html_e( 'জমি ও প্রপার্টি', 'sojib68' ); ?></a> <span aria-hidden="true">›</span>
					<span aria-current="page"><?php echo esc_html( sojib68_property_ref( $id ) ); ?></span>
				</nav>

				<div class="prop-single__grid">
					<div class="prop-single__main">
						<div class="prop-gallery" data-prop-gallery>
							<?php if ( $images ) : ?>
								<div class="prop-gallery__stage">
									<?php echo wp_get_attachment_image( $images[0], 'large', false, array( 'data-prop-stage' => '', 'alt' => get_the_title() ) ); // phpcs:ignore ?>
									<span class="prop-badges">
										<span class="prop-badge prop-badge--type"><?php echo esc_html( $types[ $type ] ?? '' ); ?></span>
										<?php if ( 'available' !== $st ) : ?><span class="prop-badge prop-badge--status"><?php echo esc_html( $sts[ $st ] ?? '' ); ?></span><?php endif; ?>
									</span>
								</div>
								<?php if ( count( $images ) > 1 ) : ?>
									<ul class="prop-gallery__thumbs">
										<?php foreach ( $images as $i => $img_id ) : ?>
											<li><button type="button" class="<?php echo 0 === $i ? 'is-on' : ''; ?>" data-prop-thumb="<?php echo esc_url( wp_get_attachment_image_url( $img_id, 'large' ) ); ?>" aria-label="<?php echo esc_attr( sprintf( /* translators: %d: photo number */ __( 'ছবি %d', 'sojib68' ), $i + 1 ) ); ?>"><?php echo wp_get_attachment_image( $img_id, 'thumbnail', false, array( 'alt' => '' ) ); // phpcs:ignore ?></button></li>
										<?php endforeach; ?>
									</ul>
								<?php endif; ?>
							<?php else : ?>
								<div class="prop-gallery__stage prop-gallery__stage--empty"><span><?php esc_html_e( 'ছবি যোগ করা হয়নি', 'sojib68' ); ?></span></div>
							<?php endif; ?>
						</div>

						<h2 class="prop-h"><?php esc_html_e( 'বিস্তারিত', 'sojib68' ); ?></h2>
						<div class="prop-content"><?php the_content(); ?></div>

						<?php if ( $features ) : ?>
							<h2 class="prop-h"><?php esc_html_e( 'বৈশিষ্ট্য', 'sojib68' ); ?></h2>
							<ul class="prop-features">
								<?php foreach ( $features as $feature ) : ?>
									<li><?php echo esc_html( $feature ); ?></li>
								<?php endforeach; ?>
							</ul>
						<?php endif; ?>

						<p class="prop-disclaimer"><?php esc_html_e( 'এই বিজ্ঞাপনের তথ্য বিক্রেতার দেওয়া। কেনা বা ভাড়া নেওয়ার আগে জমির কাগজপত্র ও বাস্তব অবস্থা নিজে যাচাই করে নিন।', 'sojib68' ); ?></p>
					</div>

					<aside class="prop-single__side">
						<div class="prop-box">
							<p class="prop-box__cat"><?php echo esc_html( ( $cats[ $cat ] ?? '' ) . ' · ' . ( $types[ $type ] ?? '' ) ); ?></p>
							<h1 class="prop-box__title"><?php the_title(); ?></h1>
							<p class="prop-box__price"><?php echo esc_html( sojib68_property_price_text( $id ) ); ?></p>
							<dl class="prop-facts">
								<div><dt><?php esc_html_e( 'কোড', 'sojib68' ); ?></dt><dd><?php echo esc_html( sojib68_property_ref( $id ) ); ?></dd></div>
								<?php if ( $loc ) : ?><div><dt><?php esc_html_e( 'এলাকা', 'sojib68' ); ?></dt><dd><?php echo esc_html( $loc ); ?></dd></div><?php endif; ?>
								<?php if ( $size ) : ?><div><dt><?php esc_html_e( 'পরিমাণ', 'sojib68' ); ?></dt><dd><?php echo esc_html( $size ); ?></dd></div><?php endif; ?>
								<div><dt><?php esc_html_e( 'অবস্থা', 'sojib68' ); ?></dt><dd><?php echo esc_html( $sts[ $st ] ?? '' ); ?></dd></div>
								<div><dt><?php esc_html_e( 'প্রকাশ', 'sojib68' ); ?></dt><dd><?php echo esc_html( get_the_date( 'j F, Y' ) ); ?></dd></div>
							</dl>
						</div>

						<div class="prop-box prop-contact" data-prop-contact data-prop-id="<?php echo (int) $id; ?>">
							<h2><?php esc_html_e( 'বিক্রেতার সঙ্গে সরাসরি কথা বলুন', 'sojib68' ); ?></h2>
							<?php if ( 'sold' === $st ) : ?>
								<p class="prop-contact__sold"><?php esc_html_e( 'এই প্রপার্টিটি বিক্রি হয়ে গেছে।', 'sojib68' ); ?></p>
							<?php elseif ( ! $seller ) : ?>
								<p><?php esc_html_e( 'এই বিজ্ঞাপনের বিক্রেতার যোগাযোগ শীঘ্রই যুক্ত হবে। তথ্য জানতে নিচের নম্বরে যোগাযোগ করুন।', 'sojib68' ); ?></p>
							<?php elseif ( 'open' === $mode ) : ?>
								<?php if ( $seller['name'] ) : ?><p class="prop-seller__name"><?php echo esc_html( $seller['name'] ); ?></p><?php endif; ?>
								<?php if ( $seller['wa'] ) : ?><a class="btn btn-primary" href="<?php echo esc_url( $seller['wa'] ); ?>" target="_blank" rel="noopener" data-lead="property_seller_whatsapp"><?php esc_html_e( 'বিক্রেতাকে WhatsApp করুন', 'sojib68' ); ?></a><?php endif; ?>
								<a class="btn btn-outline" href="<?php echo esc_url( $seller['tel'] ); ?>" data-lead="property_seller_call"><?php esc_html_e( 'বিক্রেতাকে কল করুন', 'sojib68' ); ?> <span class="prop-contact__num"><?php echo esc_html( $seller['phone'] ); ?></span></a>
							<?php else : ?>
								<p><?php esc_html_e( 'নিজের নাম ও মোবাইল দিন, সঙ্গে সঙ্গে বিক্রেতার নম্বর দেখতে পাবেন।', 'sojib68' ); ?></p>
								<form class="prop-gate" data-prop-gate novalidate>
									<div class="prop-gate__field">
										<label for="pg-name"><?php esc_html_e( 'আপনার নাম', 'sojib68' ); ?></label>
										<input type="text" id="pg-name" name="name" autocomplete="name" required>
										<span class="prop-gate__error" data-error-for="name"></span>
									</div>
									<div class="prop-gate__field">
										<label for="pg-phone"><?php esc_html_e( 'আপনার মোবাইল নম্বর', 'sojib68' ); ?></label>
										<input type="tel" id="pg-phone" name="phone" inputmode="tel" autocomplete="tel" required>
										<span class="prop-gate__error" data-error-for="phone"></span>
									</div>
									<div class="prop-gate__hp" aria-hidden="true"><input type="text" name="website" tabindex="-1" autocomplete="off"></div>
									<button type="submit" class="btn btn-primary" data-prop-gate-submit><?php esc_html_e( 'বিক্রেতার নম্বর দেখুন', 'sojib68' ); ?></button>
									<p class="prop-gate__status" data-prop-gate-status role="status" aria-live="polite"></p>
								</form>
								<div class="prop-seller" data-prop-seller hidden></div>
							<?php endif; ?>
						</div>

						<div class="prop-box prop-verify">
							<h2><?php esc_html_e( 'কাগজপত্র যাচাই করাতে চান?', 'sojib68' ); ?></h2>
							<p><?php esc_html_e( 'কেনা বা বিক্রির আগে দলিল, খতিয়ান ও খাজনার কাগজ দেখে বুঝতে চাইলে আমার সঙ্গে যোগাযোগ করুন। আপনি অন্য কাউকে দিয়ে যাচাই করালেও কোনো সমস্যা নেই।', 'sojib68' ); ?></p>
							<?php if ( $wa_url ) : ?>
								<a class="btn btn-outline" href="<?php echo esc_url( $wa_url ); ?>" target="_blank" rel="noopener" data-lead="property_verify_whatsapp"><?php esc_html_e( 'WhatsApp-এ জানান', 'sojib68' ); ?></a>
							<?php endif; ?>
							<?php if ( $phone ) : ?>
								<a class="btn btn-outline" href="tel:<?php echo esc_attr( $phone ); ?>" data-lead="property_verify_call"><?php esc_html_e( 'কল করুন', 'sojib68' ); ?> <span class="prop-contact__num"><?php echo esc_html( preg_replace( '/^\+?880/', '0', $phone ) ); ?></span></a>
							<?php endif; ?>
						</div>
					</aside>
				</div>
			</div>
		</section>
	</div>

<?php endwhile; ?>

<?php
get_footer();
