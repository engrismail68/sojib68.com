<?php
/**
 * One জমি ও প্রপার্টি advertisement: full details and the way to connect through the owner.
 * The seller's own contact details are never printed here (they live in wp-admin only).
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

						<div class="prop-box prop-contact">
							<h2><?php esc_html_e( 'বিক্রেতার সঙ্গে কথা বলতে চান?', 'sojib68' ); ?></h2>
							<p><?php esc_html_e( 'আমাকে জানান। আমি আপনাকে বিক্রেতার সঙ্গে যুক্ত করে দেব ও প্রয়োজনীয় কাগজপত্র দেখতে সাহায্য করব।', 'sojib68' ); ?></p>
							<?php if ( 'sold' === $st ) : ?>
								<p class="prop-contact__sold"><?php esc_html_e( 'এই প্রপার্টিটি বিক্রি হয়ে গেছে। অনুরূপ কিছু খুঁজতে আমাকে জানাতে পারেন।', 'sojib68' ); ?></p>
							<?php endif; ?>
							<?php if ( $wa_url ) : ?>
								<a class="btn btn-primary" href="<?php echo esc_url( $wa_url ); ?>" target="_blank" rel="noopener" data-lead="property_whatsapp"><?php esc_html_e( 'WhatsApp-এ জানতে চাই', 'sojib68' ); ?></a>
							<?php endif; ?>
							<?php if ( $phone ) : ?>
								<a class="btn btn-outline" href="tel:<?php echo esc_attr( $phone ); ?>" data-lead="property_call"><?php esc_html_e( 'কল করুন', 'sojib68' ); ?> <span class="prop-contact__num"><?php echo esc_html( preg_replace( '/^\+?880/', '0', $phone ) ); ?></span></a>
							<?php endif; ?>
							<p class="prop-contact__hint"><?php echo esc_html( sprintf( /* translators: %s: reference */ __( 'বার্তায় কোড %s উল্লেখ থাকবে।', 'sojib68' ), sojib68_property_ref( $id ) ) ); ?></p>
						</div>
					</aside>
				</div>
			</div>
		</section>
	</div>

<?php endwhile; ?>

<?php
get_footer();
