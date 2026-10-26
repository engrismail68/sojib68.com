<?php
/**
 * Template for /about — "আমার সম্পর্কে" (the full story; the homepage has the short version).
 *
 * All text comes from sojib68_about_content() in inc/about-page.php;
 * styling is in assets/css/about.css (the site's own green + cream).
 */

get_header();

$c         = sojib68_about_content();
$roots     = $c['roots'];
$about     = $c['about'];
$education = $c['education'];
$institute = $c['institute'];
$cta       = $c['cta'];

// Same rule as the homepage hero: a real booking link opens in a new tab; until one is set the button opens the contact form.
$booking_url = ( defined( 'SOJIB68_BOOKING_URL' ) && SOJIB68_BOOKING_URL ) ? SOJIB68_BOOKING_URL : '';

?>

<div class="about-page">

	<!-- 1. আমার পেশাগত শিকড় -->
	<section class="ab-roots" aria-labelledby="ab-roots-title">
		<div class="site-container">
			<header class="ab-head">
				<p class="eyebrow"><?php echo esc_html( $roots['eyebrow'] ); ?></p>
				<h1 id="ab-roots-title"><?php echo esc_html( $roots['heading'] ); ?></h1>
				<p class="ab-intro"><?php echo esc_html( $roots['intro'] ); ?></p>
			</header>

			<div class="ab-people">
				<?php foreach ( $roots['people'] as $person ) : ?>
					<article class="ab-person">
						<?php echo sojib68_about_photo_html( $person['slug'], $person['name'] ); // phpcs:ignore WordPress.Security.EscapeOutput -- escaped in the helper. ?>
						<div class="ab-person__body">
							<h2 class="ab-person__name"><?php echo esc_html( $person['name'] ); ?></h2>
							<p class="ab-person__role"><?php echo esc_html( $person['role'] ); ?></p>
							<p class="ab-person__period"><?php echo esc_html( $person['period'] ); ?></p>
							<p><?php echo esc_html( $person['text'] ); ?></p>
						</div>
					</article>
				<?php endforeach; ?>
			</div>
		</div>
	</section>

	<!-- 2. আমার সম্পর্কে -->
	<section class="ab-me" aria-labelledby="ab-me-title">
		<div class="site-container ab-me__inner">
			<div class="ab-me__media">
				<?php echo sojib68_about_photo_html( $about['photo_slug'], $about['photo_name'], get_theme_file_uri( 'assets/images/sojib-hero.webp' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
			</div>
			<div class="ab-me__text">
				<h2 id="ab-me-title"><?php echo esc_html( $about['heading'] ); ?></h2>
				<p class="ab-lead"><?php echo esc_html( $about['lead'] ); ?></p>
				<?php foreach ( $about['paragraphs'] as $paragraph ) : ?>
					<p><?php echo sojib68_about_rich( $paragraph ); // phpcs:ignore WordPress.Security.EscapeOutput -- escaped, then **x** → <strong>. ?></p>
				<?php endforeach; ?>
			</div>
		</div>
	</section>

	<!-- 3. শিক্ষাগত পটভূমি -->
	<section class="ab-edu" aria-labelledby="ab-edu-title">
		<div class="site-container">
			<h2 id="ab-edu-title" class="ab-edu__title"><?php echo esc_html( $education['heading'] ); ?></h2>
			<ul class="ab-creds">
				<?php foreach ( $education['credentials'] as $cred ) : ?>
					<li class="ab-cred">
						<h3 class="ab-cred__degree"><?php echo esc_html( $cred['degree'] ); ?></h3>
						<?php if ( ! empty( $cred['institution'] ) ) : ?>
							<p class="ab-cred__inst"><?php echo esc_html( $cred['institution'] ); ?></p>
						<?php endif; ?>
						<?php $meta = array_filter( (array) ( $cred['meta'] ?? array() ), 'strlen' ); ?>
						<?php if ( $meta ) : ?>
							<dl class="ab-cred__meta">
								<?php foreach ( $meta as $label => $value ) : ?>
									<div><dt><?php echo esc_html( $label ); ?></dt><dd><?php echo esc_html( $value ); ?></dd></div>
								<?php endforeach; ?>
							</dl>
						<?php endif; ?>
					</li>
				<?php endforeach; ?>
			</ul>
		</div>
	</section>

	<!-- 4. প্রযুক্তি প্রশিক্ষণে সম্পৃক্ততা -->
	<section class="ab-inst" aria-labelledby="ab-inst-title">
		<div class="site-container ab-inst__inner">
			<div class="ab-inst__text">
				<h2 id="ab-inst-title"><?php echo esc_html( $institute['heading'] ); ?></h2>
				<p><?php echo sojib68_about_rich( $institute['body'] ); // phpcs:ignore WordPress.Security.EscapeOutput ?></p>
			</div>
			<?php $institute_logo = sojib68_about_image( 'institute-logo', array( 'png', 'webp', 'svg', 'jpg', 'jpeg' ) ); ?>
			<div class="ab-plate">
				<?php if ( $institute_logo ) : ?>
					<img class="ab-plate__logo" src="<?php echo esc_url( $institute_logo ); ?>" alt="<?php echo esc_attr( $institute['name_en'] ); ?>" loading="lazy">
				<?php endif; ?>
				<p class="ab-plate__bn"><?php echo esc_html( $institute['name_bn'] ); ?></p>
				<p class="ab-plate__en"><?php echo esc_html( $institute['name_en'] ); ?></p>
			</div>
		</div>
	</section>

	<!-- 5. পরের ধাপ -->
	<section class="ab-cta" aria-label="<?php esc_attr_e( 'পরের ধাপ', 'sojib68' ); ?>">
		<div class="site-container ab-cta__inner">
			<div class="ab-cta__actions">
				<a href="<?php echo esc_url( home_url( '/#services' ) ); ?>" class="btn btn-outline"><?php echo esc_html( $cta['services'] ); ?></a>
				<?php if ( $booking_url ) : ?>
					<a href="<?php echo esc_url( $booking_url ); ?>" class="btn btn-primary" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $cta['booking'] ); ?> <span aria-hidden="true">↗</span></a>
				<?php else : ?>
					<a href="<?php echo esc_url( home_url( '/#contact' ) ); ?>" class="btn btn-primary"><?php echo esc_html( $cta['booking'] ); ?></a>
				<?php endif; ?>
			</div>
			<p class="ab-cta__note"><?php echo esc_html( $cta['note'] ); ?></p>
		</div>
	</section>

</div>

<?php
get_footer();
