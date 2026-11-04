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
			<h1><?php esc_html_e( 'দালালমুক্ত — নিজের জমি নিজে বিক্রি করুন', 'sojib68' ); ?></h1>
			<p class="prop-hero__lead"><?php esc_html_e( 'জমি ও প্রপার্টির ক্রয়, বিক্রয় ও ভাড়ার বিজ্ঞাপন এক জায়গায়। পছন্দের বিজ্ঞাপনটি সম্পর্কে মেসেজ দিন, নিরাপদে বিক্রেতার সঙ্গে যুক্ত করে দেওয়া হবে। কাগজপত্র যাচাইয়েও সাহায্য পাবেন।', 'sojib68' ); ?></p>

			<div class="prop-paths">
				<a class="prop-path" href="<?php echo esc_url( add_query_arg( 'ptype', 'sale', $action ) ); ?>#prop-results">
					<strong><?php esc_html_e( 'কিনতে চাই', 'sojib68' ); ?></strong>
					<span><?php esc_html_e( 'বিক্রির জমি-প্রপার্টি দেখুন', 'sojib68' ); ?></span>
				</a>
				<a class="prop-path" href="<?php echo esc_url( add_query_arg( 'ptype', 'rent', $action ) ); ?>#prop-results">
					<strong><?php esc_html_e( 'ভাড়া নিতে চাই', 'sojib68' ); ?></strong>
					<span><?php esc_html_e( 'ভাড়ার প্রপার্টি দেখুন', 'sojib68' ); ?></span>
				</a>
				<a class="prop-path prop-path--sell" href="<?php echo esc_url( home_url( '/#contact' ) ); ?>" data-inq-open data-inq-text="<?php echo esc_attr( __( 'আমি আমার জমি/প্রপার্টি বিক্রি বা ভাড়া দিতে চাই।\nএলাকা:\nপরিমাণ:\nদাম (আনুমানিক):', 'sojib68' ) ); ?>" data-lead="property_sell_form">
					<strong><?php esc_html_e( 'আমার জমি বিক্রি করতে চাই', 'sojib68' ); ?></strong>
					<span><?php esc_html_e( 'বিজ্ঞাপনের তথ্য দিন', 'sojib68' ); ?></span>
				</a>
			</div>

			<ul class="prop-points">
				<li><?php esc_html_e( 'নিজের জমির বিজ্ঞাপন নিজের শর্তে', 'sojib68' ); ?></li>
				<li><?php esc_html_e( 'দলিল-খতিয়ান বোঝা মানুষ পাশে', 'sojib68' ); ?></li>
				<li><?php esc_html_e( 'বিস্তারিত তথ্য ও ছবিসহ বিজ্ঞাপন', 'sojib68' ); ?></li>
			</ul>
		</div>
	</section>

	<section class="prop-list">
		<div class="site-container">

			<h2 class="prop-list__title" id="prop-results"><?php esc_html_e( 'সব বিজ্ঞাপন', 'sojib68' ); ?></h2>

			<form method="get" action="<?php echo esc_url( $action ); ?>#prop-results" class="prop-filter">
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

			<aside class="prop-how">
				<h2><?php esc_html_e( 'বিক্রি করতে চান? তিন ধাপে', 'sojib68' ); ?></h2>
				<ol>
					<li><strong><?php esc_html_e( 'তথ্য ও ছবি দিন', 'sojib68' ); ?></strong><span><?php esc_html_e( 'জমি বা প্রপার্টির এলাকা, পরিমাণ, কাগজপত্রের অবস্থা ও ছবি WhatsApp-এ পাঠান।', 'sojib68' ); ?></span></li>
					<li><strong><?php esc_html_e( 'বিজ্ঞাপন প্রকাশ', 'sojib68' ); ?></strong><span><?php esc_html_e( 'আপনার অনুমতি নিয়ে বিস্তারিত বিবরণ ও ছবিসহ বিজ্ঞাপন এই পেজে দেওয়া হয়।', 'sojib68' ); ?></span></li>
					<li><strong><?php esc_html_e( 'ক্রেতা যুক্ত হন', 'sojib68' ); ?></strong><span><?php esc_html_e( 'আগ্রহী ক্রেতা আমাকে মেসেজ দেন। আপনার সম্মতি নিয়ে আমি তাঁকে আপনার সঙ্গে যুক্ত করে দিই। আপনার নম্বর সাইটে দেওয়া হয় না।', 'sojib68' ); ?></span></li>
				</ol>
				<a class="btn btn-primary" href="<?php echo esc_url( home_url( '/#contact' ) ); ?>" data-inq-open data-inq-text="<?php echo esc_attr( __( 'আমি আমার জমি/প্রপার্টি বিক্রি বা ভাড়া দিতে চাই।\nএলাকা:\nপরিমাণ:\nদাম (আনুমানিক):', 'sojib68' ) ); ?>" data-lead="property_sell_form"><?php esc_html_e( 'বিজ্ঞাপনের তথ্য দিন', 'sojib68' ); ?></a>
				<?php if ( $wa ) : ?>
					<a class="btn btn-outline-light" href="https://wa.me/<?php echo esc_attr( $wa ); ?>?text=<?php echo esc_attr( rawurlencode( __( 'আসসালামু আলাইকুম, আমি আমার জমি/প্রপার্টি বিক্রি বা ভাড়া দিতে চাই।', 'sojib68' ) ) ); ?>" target="_blank" rel="noopener" data-lead="property_sell_whatsapp"><?php esc_html_e( 'অথবা WhatsApp-এ পাঠান', 'sojib68' ); ?></a>
				<?php endif; ?>
			</aside>

		</div>
	</section>
</div>

<?php
get_footer();
