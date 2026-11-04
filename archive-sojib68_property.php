<?php
/**
 * জমি ও প্রপার্টি — the listing page (/land-property/). Advertisements come from
 * wp-admin → জমি ও প্রপার্টি (inc/properties.php); styling in assets/css/property.css.
 */

get_header();

$f      = sojib68_property_filters();
$types  = sojib68_property_types();
$cats   = sojib68_property_categories();
$sts    = sojib68_property_statuses();
$action = get_post_type_archive_link( SOJIB68_PROPERTY_CPT );
$wa     = ( defined( 'SOJIB68_WHATSAPP' ) && SOJIB68_WHATSAPP ) ? SOJIB68_WHATSAPP : '';
?>

<div class="prop-page">

	<section class="prop-hero">
		<div class="site-container">
			<p class="eyebrow"><?php esc_html_e( 'জমি ও প্রপার্টি', 'sojib68' ); ?></p>
			<h1><?php esc_html_e( 'বিক্রি ও ভাড়ার জমি-প্রপার্টি', 'sojib68' ); ?></h1>
			<p class="prop-hero__lead"><?php esc_html_e( 'পছন্দের বিজ্ঞাপনটির বিস্তারিত দেখুন। আরও জানতে বা বিক্রেতার সঙ্গে কথা বলতে আমার মাধ্যমে যুক্ত হোন।', 'sojib68' ); ?></p>
		</div>
	</section>

	<section class="prop-list">
		<div class="site-container">

			<form method="get" action="<?php echo esc_url( $action ); ?>" class="prop-filter">
				<select name="ptype" aria-label="<?php esc_attr_e( 'বিক্রয় বা ভাড়া', 'sojib68' ); ?>">
					<option value=""><?php esc_html_e( 'বিক্রয় ও ভাড়া', 'sojib68' ); ?></option>
					<?php foreach ( $types as $k => $label ) : ?>
						<option value="<?php echo esc_attr( $k ); ?>" <?php selected( $f['type'], $k ); ?>><?php echo esc_html( $label ); ?></option>
					<?php endforeach; ?>
				</select>
				<select name="pcat" aria-label="<?php esc_attr_e( 'ধরন', 'sojib68' ); ?>">
					<option value=""><?php esc_html_e( 'সব ধরন', 'sojib68' ); ?></option>
					<?php foreach ( $cats as $k => $label ) : ?>
						<option value="<?php echo esc_attr( $k ); ?>" <?php selected( $f['cat'], $k ); ?>><?php echo esc_html( $label ); ?></option>
					<?php endforeach; ?>
				</select>
				<select name="pstatus" aria-label="<?php esc_attr_e( 'অবস্থা', 'sojib68' ); ?>">
					<option value=""><?php esc_html_e( 'পাওয়া যাচ্ছে ও আলোচনায়', 'sojib68' ); ?></option>
					<?php foreach ( $sts as $k => $label ) : ?>
						<option value="<?php echo esc_attr( $k ); ?>" <?php selected( $f['status'], $k ); ?>><?php echo esc_html( $label ); ?></option>
					<?php endforeach; ?>
				</select>
				<input type="search" name="pq" value="<?php echo esc_attr( $f['q'] ); ?>" placeholder="<?php esc_attr_e( 'এলাকা বা কথা দিয়ে খুঁজুন', 'sojib68' ); ?>">
				<button type="submit" class="btn btn-primary btn-sm"><?php esc_html_e( 'খুঁজুন', 'sojib68' ); ?></button>
				<?php if ( $f['type'] || $f['cat'] || $f['status'] || $f['q'] ) : ?>
					<a class="prop-filter__clear" href="<?php echo esc_url( $action ); ?>"><?php esc_html_e( 'ফিল্টার মুছুন', 'sojib68' ); ?></a>
				<?php endif; ?>
			</form>

			<?php if ( have_posts() ) : ?>
				<div class="prop-grid">
					<?php
					while ( have_posts() ) {
						the_post();
						sojib68_property_card( get_the_ID() );
					}
					?>
				</div>

				<?php
				$links = paginate_links( array(
					'total'     => (int) $GLOBALS['wp_query']->max_num_pages,
					'current'   => max( 1, (int) get_query_var( 'paged' ) ),
					'type'      => 'list',
					'prev_text' => '‹',
					'next_text' => '›',
				) );
				if ( $links ) {
					echo '<nav class="prop-pager" aria-label="' . esc_attr__( 'পৃষ্ঠা', 'sojib68' ) . '">' . $links . '</nav>'; // phpcs:ignore WordPress.Security.EscapeOutput
				}
				?>
			<?php else : ?>
				<div class="prop-empty">
					<?php if ( $f['type'] || $f['cat'] || $f['status'] || $f['q'] ) : ?>
						<p><?php esc_html_e( 'এই শর্তে কোনো বিজ্ঞাপন পাওয়া যায়নি।', 'sojib68' ); ?></p>
						<a class="btn btn-outline btn-sm" href="<?php echo esc_url( $action ); ?>"><?php esc_html_e( 'সব বিজ্ঞাপন দেখুন', 'sojib68' ); ?></a>
					<?php else : ?>
						<p><?php esc_html_e( 'এই মুহূর্তে কোনো বিজ্ঞাপন নেই। শীঘ্রই যুক্ত করা হবে।', 'sojib68' ); ?></p>
						<?php if ( $wa ) : ?>
							<a class="btn btn-primary btn-sm" href="https://wa.me/<?php echo esc_attr( $wa ); ?>?text=<?php echo esc_attr( rawurlencode( __( 'আসসালামু আলাইকুম, জমি ও প্রপার্টি সম্পর্কে জানতে চাই।', 'sojib68' ) ) ); ?>" target="_blank" rel="noopener" data-lead="property_whatsapp"><?php esc_html_e( 'আপনার চাহিদা WhatsApp-এ জানান', 'sojib68' ); ?></a>
						<?php endif; ?>
					<?php endif; ?>
				</div>
			<?php endif; ?>

			<?php if ( $wa ) : ?>
				<aside class="prop-sellcta">
					<div>
						<h2><?php esc_html_e( 'জমি বা প্রপার্টি বিক্রি বা ভাড়া দিতে চান?', 'sojib68' ); ?></h2>
						<p><?php esc_html_e( 'আপনার জমি-প্রপার্টির তথ্য জানান। যাচাই করে এখানে বিজ্ঞাপন দেওয়া হবে, ক্রেতা আমার মাধ্যমে আপনার সঙ্গে যুক্ত হবেন।', 'sojib68' ); ?></p>
					</div>
					<a class="btn btn-primary" href="https://wa.me/<?php echo esc_attr( $wa ); ?>?text=<?php echo esc_attr( rawurlencode( __( 'আসসালামু আলাইকুম, আমি আমার জমি/প্রপার্টি বিক্রি বা ভাড়া দিতে চাই।', 'sojib68' ) ) ); ?>" target="_blank" rel="noopener" data-lead="property_sell_whatsapp"><?php esc_html_e( 'WhatsApp-এ জানান', 'sojib68' ); ?></a>
				</aside>
			<?php endif; ?>

		</div>
	</section>
</div>

<?php
get_footer();
