<?php
/**
 * Template for /about — "আমার পরিচয় ও পেশাগত উত্তরাধিকার".
 *
 * All text and years come from sojib68_about_content() in
 * inc/about-page.php; styling is in assets/css/about.css.
 */

get_header();

$c         = sojib68_about_content();
$hero      = $c['hero'];
$history   = $c['history'];
$approach  = $c['approach'];
$education = $c['education'];
$institute = $c['institute'];
$services  = $c['services'];
$closing   = $c['closing'];
$range     = sojib68_about_range( $history['people'] );

/** Heading with each line on its own row. */
$about_heading = function ( array $lines, $accent_last = true ) {
	$out = '';
	foreach ( $lines as $i => $line ) {
		$is_last = $i === count( $lines ) - 1;
		$out    .= '<span class="ab-line' . ( $accent_last && $is_last ? ' ab-line--accent' : '' ) . '">' . esc_html( $line ) . '</span>';
	}
	return $out;
};

/** Photo frame: the real photo when the file exists, otherwise a labelled placeholder. */
$about_photo = function ( $slug, $name ) {
	$url = sojib68_about_image( $slug );
	if ( $url ) {
		return '<div class="ab-photo"><img src="' . esc_url( $url ) . '" alt="' . esc_attr( $name ) . '" width="480" height="600" loading="lazy"></div>';
	}
	return '<div class="ab-photo ab-photo--empty" role="img" aria-label="' . esc_attr( $name . ' — ছবি যুক্ত করা হবে' ) . '">'
		. '<svg viewBox="0 0 48 48" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="6" y="9" width="36" height="30" rx="3"/><circle cx="17" cy="20" r="3"/><path d="M6 33l10-9 8 7 6-5 12 10"/></svg>'
		. '<span>ছবি যুক্ত করা হবে</span></div>';
};
?>

<div class="about-page">

	<!-- 1. Hero -->
	<section class="ab-section ab-dark ab-hero" aria-labelledby="ab-hero-title">
		<div class="site-container ab-hero__inner">
			<div class="ab-hero__content">
				<p class="ab-eyebrow"><?php echo esc_html( $hero['eyebrow'] ); ?></p>
				<h1 id="ab-hero-title" class="ab-h1"><?php echo $about_heading( $hero['heading'] ); // phpcs:ignore WordPress.Security.EscapeOutput -- built from esc_html() above. ?></h1>
				<p class="ab-lead"><?php echo esc_html( $hero['description'] ); ?></p>

				<p class="ab-id">
					<span class="ab-id__name"><?php echo esc_html( $hero['name'] ); ?></span>
					<span class="ab-id__role"><?php echo esc_html( $hero['role'] ); ?></span>
				</p>

				<div class="ab-actions">
					<a href="#timeline" class="ab-btn ab-btn--primary"><?php echo esc_html( $hero['cta_history'] ); ?></a>
					<a href="#education" class="ab-btn ab-btn--ghost"><?php echo esc_html( $hero['cta_edu'] ); ?></a>
				</div>
			</div>

			<div class="ab-years">
				<p class="ab-years__line">
					<?php foreach ( $hero['years'] as $i => $year ) : ?>
						<?php if ( $i > 0 ) : ?><span class="ab-years__arrow" aria-hidden="true">→</span><?php endif; ?>
						<span class="ab-years__year"><?php echo esc_html( sojib68_about_bn( $year ) ); ?></span>
					<?php endforeach; ?>
				</p>
				<p class="ab-years__caption"><?php echo esc_html( $hero['years_label'] ); ?></p>
			</div>
		</div>
	</section>

	<!-- 2. তিন প্রজন্ম -->
	<section class="ab-section ab-light ab-history" id="timeline" aria-labelledby="ab-history-title">
		<div class="site-container">
			<div class="ab-head ab-head--center">
				<h2 id="ab-history-title" class="ab-h2"><?php echo $about_heading( $history['heading'] ); // phpcs:ignore WordPress.Security.EscapeOutput ?></h2>
				<p class="ab-intro"><?php echo esc_html( $history['intro'] ); ?></p>
			</div>

			<ol class="ab-gens">
				<?php foreach ( $history['people'] as $person ) : ?>
					<li class="ab-gen">
						<div class="ab-gen__node">
							<span class="ab-gen__period"><?php echo esc_html( sojib68_about_period( $person['from'], $person['to'] ) ); ?></span>
						</div>
						<article class="ab-gen__card">
							<?php echo $about_photo( $person['slug'], $person['name'] ); // phpcs:ignore WordPress.Security.EscapeOutput -- escaped in the closure. ?>
							<h3 class="ab-gen__name"><?php echo esc_html( $person['name'] ); ?></h3>
							<p class="ab-gen__role"><?php echo esc_html( $person['role'] ); ?></p>
							<p class="ab-gen__text"><?php echo esc_html( $person['text'] ); ?></p>
						</article>
					</li>
				<?php endforeach; ?>
			</ol>

			<figure class="ab-range">
				<figcaption class="ab-range__title"><?php echo esc_html( $history['range_title'] ); ?></figcaption>
				<ul class="ab-range__rows">
					<?php foreach ( $range['bars'] as $i => $bar ) : ?>
						<li class="ab-range__row">
							<p class="ab-range__label"><strong><?php echo esc_html( $bar['name'] ); ?></strong> <span><?php echo esc_html( $bar['period'] ); ?></span></p>
							<div class="ab-range__track">
								<span class="ab-range__bar ab-range__bar--<?php echo (int) $i; ?>" style="left: <?php echo esc_attr( $bar['left'] ); ?>%; width: <?php echo esc_attr( $bar['width'] ); ?>%;"></span>
							</div>
						</li>
					<?php endforeach; ?>
				</ul>
				<div class="ab-range__axis" aria-hidden="true">
					<?php foreach ( $range['ticks'] as $tick ) : ?>
						<span style="left: <?php echo esc_attr( $tick['left'] ); ?>%;"><?php echo esc_html( $tick['label'] ); ?></span>
					<?php endforeach; ?>
				</div>
				<p class="ab-range__note"><?php echo esc_html( $history['range_note'] ); ?></p>
			</figure>

			<blockquote class="ab-statement">
				<?php foreach ( $history['statement'] as $line ) : ?>
					<p><?php echo esc_html( $line ); ?></p>
				<?php endforeach; ?>
			</blockquote>
		</div>
	</section>

	<!-- 3. আমার কাজের ধরন -->
	<section class="ab-section ab-white ab-approach" aria-labelledby="ab-approach-title">
		<div class="site-container ab-approach__inner">
			<div class="ab-approach__intro">
				<h2 id="ab-approach-title" class="ab-h2"><?php echo $about_heading( $approach['heading'] ); // phpcs:ignore WordPress.Security.EscapeOutput ?></h2>
				<p class="ab-body"><?php echo esc_html( $approach['body'] ); ?></p>
			</div>
			<ol class="ab-steps">
				<?php foreach ( $approach['items'] as $i => $item ) : ?>
					<li class="ab-step">
						<span class="ab-step__no" aria-hidden="true"><?php echo esc_html( sojib68_about_bn( $i + 1 ) ); ?></span>
						<div>
							<h3 class="ab-step__title"><?php echo esc_html( $item['title'] ); ?></h3>
							<p><?php echo esc_html( $item['text'] ); ?></p>
						</div>
					</li>
				<?php endforeach; ?>
			</ol>
		</div>
	</section>

	<!-- 4. শিক্ষা -->
	<section class="ab-section ab-light ab-education" id="education" aria-labelledby="ab-edu-title">
		<div class="site-container">
			<div class="ab-head">
				<p class="ab-eyebrow ab-eyebrow--ink"><?php echo esc_html( $education['eyebrow'] ); ?></p>
				<h2 id="ab-edu-title" class="ab-h2"><?php echo $about_heading( $education['heading'] ); // phpcs:ignore WordPress.Security.EscapeOutput ?></h2>
				<p class="ab-intro"><?php echo esc_html( $education['intro'] ); ?></p>
			</div>

			<ol class="ab-creds">
				<?php foreach ( $education['credentials'] as $i => $cred ) : ?>
					<li class="ab-cred">
						<span class="ab-cred__no" aria-hidden="true"><?php echo esc_html( sojib68_about_bn( str_pad( (string) ( $i + 1 ), 2, '0', STR_PAD_LEFT ) ) ); ?></span>
						<div class="ab-cred__body">
							<h3 class="ab-cred__degree"><?php echo esc_html( $cred['degree'] ); ?></h3>
							<?php if ( ! empty( $cred['field'] ) ) : ?>
								<p class="ab-cred__field"><?php echo esc_html( $cred['field'] ); ?></p>
							<?php endif; ?>
							<?php if ( ! empty( $cred['institution'] ) ) : ?>
								<p class="ab-cred__inst"><span>প্রতিষ্ঠান</span><?php echo esc_html( $cred['institution'] ); ?></p>
							<?php endif; ?>
							<?php $meta = array_filter( (array) ( $cred['meta'] ?? array() ), 'strlen' ); ?>
							<?php if ( $meta ) : ?>
								<dl class="ab-cred__meta">
									<?php foreach ( $meta as $label => $value ) : ?>
										<div><dt><?php echo esc_html( $label ); ?></dt><dd><?php echo esc_html( $value ); ?></dd></div>
									<?php endforeach; ?>
								</dl>
							<?php endif; ?>
						</div>
					</li>
				<?php endforeach; ?>
			</ol>
		</div>
	</section>

	<!-- 5. Life Support IT Institute -->
	<section class="ab-section ab-dark ab-institute" aria-labelledby="ab-inst-title">
		<div class="site-container ab-institute__inner">
			<div class="ab-institute__content">
				<p class="ab-eyebrow"><?php echo esc_html( $institute['eyebrow'] ); ?></p>
				<h2 id="ab-inst-title" class="ab-h2"><?php echo $about_heading( $institute['heading'] ); // phpcs:ignore WordPress.Security.EscapeOutput ?></h2>
				<p class="ab-lead"><?php echo esc_html( $institute['body'] ); ?></p>
			</div>

			<?php $institute_logo = sojib68_about_image( 'institute-logo', array( 'png', 'webp', 'svg', 'jpg', 'jpeg' ) ); ?>
			<div class="ab-plate">
				<?php if ( $institute_logo ) : ?>
					<img class="ab-plate__logo" src="<?php echo esc_url( $institute_logo ); ?>" alt="<?php echo esc_attr( $institute['name_en'] ); ?>" loading="lazy">
				<?php endif; ?>
				<p class="ab-plate__label">তথ্যপ্রযুক্তি প্রশিক্ষণকেন্দ্র</p>
				<h3 class="ab-plate__bn"><?php echo esc_html( $institute['name_bn'] ); ?></h3>
				<p class="ab-plate__en"><?php echo esc_html( $institute['name_en'] ); ?></p>
			</div>
		</div>
	</section>

	<!-- 6. সেবা -->
	<section class="ab-section ab-white ab-services" aria-labelledby="ab-svc-title">
		<div class="site-container">
			<div class="ab-head ab-head--center">
				<h2 id="ab-svc-title" class="ab-h2"><?php echo $about_heading( $services['heading'] ); // phpcs:ignore WordPress.Security.EscapeOutput ?></h2>
			</div>
			<ul class="ab-svc-list">
				<?php foreach ( $services['items'] as $service ) : ?>
					<li>
						<a class="ab-svc" href="<?php echo esc_url( home_url( '/#services' ) ); ?>">
							<span class="ab-svc__icon"><?php echo sojib68_service_icon( $service['icon'] ); // phpcs:ignore WordPress.Security.EscapeOutput -- static inline SVG from the theme. ?></span>
							<span class="ab-svc__label"><?php echo esc_html( $service['label'] ); ?></span>
							<span class="ab-svc__go" aria-hidden="true">→</span>
						</a>
					</li>
				<?php endforeach; ?>
			</ul>
		</div>
	</section>

	<!-- 7. Closing -->
	<section class="ab-section ab-dark ab-closing" aria-labelledby="ab-close-title">
		<div class="site-container ab-closing__inner">
			<h2 id="ab-close-title" class="ab-h2 ab-h2--xl"><?php echo $about_heading( $closing['heading'] ); // phpcs:ignore WordPress.Security.EscapeOutput ?></h2>
			<p class="ab-lead"><?php echo esc_html( $closing['description'] ); ?></p>
			<a href="<?php echo esc_url( home_url( '/#contact' ) ); ?>" class="ab-btn ab-btn--primary ab-btn--lg"><?php echo esc_html( $closing['button'] ); ?></a>
		</div>
	</section>

</div>

<?php
get_footer();
