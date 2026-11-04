<?php
/**
 * Homepage template for the Sojib68 theme.
 */

get_header();

$services     = sojib68_get_services();
$stats        = function_exists( 'sojib68_get_stats' ) ? sojib68_get_stats() : array();
$case_stories = function_exists( 'sojib68_get_case_stories' ) ? sojib68_get_case_stories() : array();
$testimonials = function_exists( 'sojib68_get_testimonials' ) ? sojib68_get_testimonials() : array();

$how_we_work = array(
	array(
		'icon'  => 'discuss',
		'title' => 'প্রয়োজন বুঝে আলোচনা',
	),
	array(
		'icon'  => 'folder',
		'title' => 'কাগজপত্র গোছানো',
	),
	array(
		'icon'  => 'clarity',
		'title' => 'করণীয় ও খরচ সম্পর্কে পরিষ্কার ধারণা',
	),
	array(
		'icon'  => 'update',
		'title' => 'কাজের অগ্রগতি নিয়ে যোগাযোগ',
	),
);
?>

<div id="top"></div>

<!-- Hero -->
<section class="hero">
	<div class="site-container hero__inner">
		<div class="hero__content">
			<p class="hero__greeting"><?php esc_html_e( 'Hi there,', 'sojib68' ); ?></p>
			<h1 class="hero__name"><?php esc_html_e( 'I’m Ismail Hossain Sojib', 'sojib68' ); ?></h1>
			<p class="hero__designation"><?php esc_html_e( 'Land & IT Consultant', 'sojib68' ); ?></p>
			<p class="hero__tagline"><?php esc_html_e( 'জমির তথ্য বুঝে, সঠিক পদক্ষেপের পথে।', 'sojib68' ); ?></p>
			<p class="hero__desc">
				<?php esc_html_e( 'দলিল ও খতিয়ানের তথ্য পর্যালোচনা, প্রয়োজনীয় নথি অনুসন্ধান এবং কাগজপত্রের অসামঞ্জস্য চিহ্নিত করে করণীয় বুঝতে সহায়তা করি। জমি কেনাবেচা, রেজিস্ট্রেশন, নামজারি ও ভূমিসংক্রান্ত কাজে বাস্তব অভিজ্ঞতার সঙ্গে যুক্ত করি তথ্যপ্রযুক্তির দক্ষতা।', 'sojib68' ); ?>
			</p>

			<dl class="hero__facts">
				<div>
					<dt><?php esc_html_e( 'অভিজ্ঞতা ও পেশাগত পরিচয়', 'sojib68' ); ?></dt>
					<dd><?php esc_html_e( 'তিন প্রজন্মের দলিল লেখার পারিবারিক ধারাবাহিকতায়, ২০২০ সাল থেকে বাবা মোঃ নুরুল ইসলামের সহকারী হিসেবে কাজ করছি। নথি গোছানো, তথ্য অনুসন্ধান ও কাজের অগ্রগতি অনুসরণে প্রযুক্তি ব্যবহার আমার কাজের গুরুত্বপূর্ণ অংশ।', 'sojib68' ); ?></dd>
				</div>
				<div>
					<dt><?php esc_html_e( 'শিক্ষাগত যোগ্যতা', 'sojib68' ); ?></dt>
					<dd class="hero__edu">
						<span><?php esc_html_e( 'Diploma in Engineering · Dhaka Polytechnic Institute', 'sojib68' ); ?></span>
						<span><?php esc_html_e( 'BSc in Computer Science and Engineering · Canadian University of Bangladesh', 'sojib68' ); ?></span>
					</dd>
				</div>
			</dl>

			<?php
			// Set SOJIB68_BOOKING_URL (functions.php) to a real booking link — WhatsApp, a calendar, a payment page.
			// Until then the button opens the contact form instead of a dead link.
			$sojib68_booking_url = ( defined( 'SOJIB68_BOOKING_URL' ) && SOJIB68_BOOKING_URL ) ? SOJIB68_BOOKING_URL : '';
			?>
			<div class="hero__actions">
				<a href="#contact" class="btn btn-primary"><?php esc_html_e( 'যোগাযোগ করুন', 'sojib68' ); ?></a>
				<?php if ( $sojib68_booking_url ) : ?>
					<a href="<?php echo esc_url( $sojib68_booking_url ); ?>" class="btn btn-outline" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'বিস্তারিত পরামর্শ বুক করুন', 'sojib68' ); ?> <span aria-hidden="true">↗</span></a>
				<?php else : ?>
					<a href="#contact" class="btn btn-outline"><?php esc_html_e( 'বিস্তারিত পরামর্শ বুক করুন', 'sojib68' ); ?></a>
				<?php endif; ?>
			</div>
			<p class="hero__fee-note"><?php esc_html_e( 'নির্ধারিত ফি প্রযোজ্য। বুকিংয়ের আগে ফি ও আলোচনার সময় জানুন।', 'sojib68' ); ?></p>
			<a href="#services" class="hero__textlink"><?php esc_html_e( 'আমার সেবাসমূহ', 'sojib68' ); ?> <span aria-hidden="true">→</span></a>
		</div>
		<div class="hero__media">
			<img
				src="<?php echo esc_url( get_theme_file_uri( 'assets/images/sojib-hero.webp' ) ); ?>"
				alt="<?php esc_attr_e( 'Ismail Hossain Sojib', 'sojib68' ); ?>"
				class="hero__portrait"
				width="840"
				height="960"
				fetchpriority="high"
			>
		</div>
	</div>
</section>

<?php $sojib68_roots = sojib68_about_content()['roots']; // same text as /about, so the two never drift apart ?>
<!-- Family roots (compact; the full story is on /about). id="about" stays: the hero button and the menu link here. -->
<section class="about about--short" id="about">
	<div class="site-container">
		<div class="about__roots-block">
			<p class="eyebrow"><?php echo esc_html( $sojib68_roots['eyebrow'] ); ?></p>
			<h2 class="about__roots-title"><?php echo esc_html( $sojib68_roots['heading'] ); ?></h2>
			<p class="about__roots-intro"><?php echo esc_html( $sojib68_roots['intro'] ); ?></p>

			<div class="about__people">
				<?php foreach ( $sojib68_roots['people'] as $sojib68_person ) : ?>
					<div class="about__person">
						<?php echo sojib68_about_photo_html( $sojib68_person['slug'], $sojib68_person['name'], '', 'ab-photo--sm' ); // phpcs:ignore WordPress.Security.EscapeOutput -- escaped in the helper. ?>
						<div class="about__person-body">
							<h4 class="about__person-name"><?php echo esc_html( $sojib68_person['name'] ); ?></h4>
							<p class="about__person-role"><?php echo esc_html( $sojib68_person['role'] ); ?></p>
							<p class="about__person-period"><?php echo esc_html( $sojib68_person['period'] ); ?></p>
						</div>
					</div>
				<?php endforeach; ?>
			</div>

			<a href="<?php echo esc_url( home_url( '/about/' ) ); ?>" class="btn btn-outline"><?php esc_html_e( 'পুরো পরিচয় দেখুন', 'sojib68' ); ?></a>
		</div>
	</div>
</section>

<!-- Services -->
<?php $sojib68_checklists = function_exists( 'sojib68_get_checklists' ) ? sojib68_get_checklists() : array(); // services with a "কী কী কাগজ লাগে" list (wp-admin → কাগজপত্র তালিকা) ?>
<section class="services" id="services">
	<div class="site-container">
		<div class="section-heading-center">
			<p class="eyebrow"><?php esc_html_e( 'আমার সেবাসমূহ', 'sojib68' ); ?></p>
			<h2><?php esc_html_e( 'জমি ও দলিলের প্রয়োজনীয় সেবা এক জায়গায়।', 'sojib68' ); ?></h2>
		</div>

		<div class="services__grid">
			<?php foreach ( $services as $key => $service ) : ?>
				<div class="service-card">
					<div class="service-card__media" aria-hidden="true">
						<?php echo sojib68_service_icon( $service['icon'] ?? 'document' ); ?>
					</div>
					<div class="service-card__body">
						<h3><?php echo esc_html( $service['title'] ); ?></h3>
						<p><?php echo esc_html( $service['text'] ); ?></p>
						<div class="service-card__actions">
							<?php if ( defined( 'SOJIB68_WHATSAPP' ) && SOJIB68_WHATSAPP && defined( 'SOJIB68_CONSULT_SERVICE' ) && SOJIB68_CONSULT_SERVICE === $key ) : ?>
								<button type="button" class="btn btn-outline btn-sm service-card__cta" data-cs-open>
									<?php esc_html_e( 'WhatsApp-এ জানান', 'sojib68' ); ?>
								</button>
							<?php elseif ( defined( 'SOJIB68_WHATSAPP' ) && SOJIB68_WHATSAPP ) : ?>
								<?php
								/* translators: %s: service title */
								$sojib68_wa_text = sprintf( __( 'আসসালামু আলাইকুম, আমি "%s" সম্পর্কে জানতে চাই।', 'sojib68' ), $service['title'] );
								?>
								<a href="https://wa.me/<?php echo esc_attr( SOJIB68_WHATSAPP ); ?>?text=<?php echo esc_attr( rawurlencode( $sojib68_wa_text ) ); ?>" class="btn btn-outline btn-sm service-card__cta" target="_blank" rel="noopener" data-lead="whatsapp_card">
									<?php esc_html_e( 'WhatsApp-এ জানুন', 'sojib68' ); ?>
								</a>
							<?php else : ?>
								<a href="#contact" class="btn btn-outline btn-sm service-card__cta" data-service="<?php echo esc_attr( $key ); ?>">
									<?php esc_html_e( 'বিস্তারিত জানুন', 'sojib68' ); ?>
								</a>
							<?php endif; ?>
							<?php if ( isset( $sojib68_checklists[ $key ] ) ) : ?>
								<button type="button" class="btn btn-primary btn-sm service-card__docs" data-ck-open="<?php echo esc_attr( $key ); ?>">
									<?php echo defined( 'SOJIB68_CONSULT_SERVICE' ) && SOJIB68_CONSULT_SERVICE === $key ? esc_html__( 'কী কী আনবেন', 'sojib68' ) : esc_html__( 'কী কী লাগে', 'sojib68' ); ?>
								</button>
							<?php endif; ?>
							<?php if ( defined( 'SOJIB68_LAND_CALC_SERVICE' ) && SOJIB68_LAND_CALC_SERVICE === $key ) : ?>
								<button type="button" class="btn btn-primary btn-sm service-card__docs" data-lc-open>
									<?php esc_html_e( 'জমির হিসাব', 'sojib68' ); ?>
								</button>
							<?php endif; ?>
						</div>
					</div>
				</div>
			<?php endforeach; ?>
		</div>
	</div>
</section>

<!-- Portfolio: shown only once there are real case stories (sojib68_get_case_stories()); until then the whole section stays out -->
<?php if ( ! empty( $case_stories ) ) : ?>
<section class="portfolio" id="work">
	<div class="site-container">
		<div class="section-heading-center">
			<p class="eyebrow"><?php esc_html_e( 'আমার কাজ', 'sojib68' ); ?></p>
			<h2><?php esc_html_e( 'আমার কাজের অভিজ্ঞতা', 'sojib68' ); ?></h2>
		</div>

		<div class="portfolio__grid">
				<?php foreach ( $case_stories as $story ) : ?>
					<article class="portfolio-card">
						<?php if ( ! empty( $story['image'] ) ) : ?>
							<img src="<?php echo esc_url( $story['image'] ); ?>" alt="" loading="lazy">
						<?php endif; ?>
						<div class="portfolio-card__body">
							<p class="portfolio-card__type"><?php echo esc_html( $story['type'] ); ?></p>
							<h3><?php echo esc_html( $story['title'] ); ?></h3>
						</div>
					</article>
				<?php endforeach; ?>
		</div>
	</div>
</section>
<?php endif; ?>

<!-- How we work -->
<section class="how-we-work">
	<div class="site-container">
		<h2 class="section-title-center"><?php esc_html_e( 'যেভাবে কাজ করি', 'sojib68' ); ?></h2>

		<div class="how-we-work__grid">
			<?php foreach ( $how_we_work as $item ) : ?>
				<div class="how-we-work__card">
					<span class="how-we-work__icon" aria-hidden="true"><?php echo sojib68_work_icon( $item['icon'] ); ?></span>
					<p><?php echo esc_html( $item['title'] ); ?></p>
				</div>
			<?php endforeach; ?>
		</div>
	</div>
</section>

<?php if ( ! empty( $testimonials ) ) : ?>
<!-- Reviews (only rendered once verified reviews exist) -->
<section class="reviews">
	<div class="site-container">
		<div class="section-heading-center">
			<h2><?php esc_html_e( 'যাঁরা পাশে ছিলেন', 'sojib68' ); ?></h2>
		</div>
		<div class="reviews__grid">
			<?php foreach ( array_slice( $testimonials, 0, 3 ) as $t ) : ?>
				<div class="review-card">
					<p class="review-card__quote">&ldquo;<?php echo esc_html( $t['quote'] ); ?>&rdquo;</p>
					<div class="review-card__author">
						<span class="review-card__name"><?php echo esc_html( $t['name'] ); ?></span>
					</div>
				</div>
			<?php endforeach; ?>
		</div>
	</div>
</section>
<?php endif; ?>

<!-- Contact: one-tap actions (no form). Each button carries data-lead so ad platforms / analytics can count it as a lead (assets/js/track.js). -->
<?php
$sojib68_wa      = defined( 'SOJIB68_WHATSAPP' ) ? SOJIB68_WHATSAPP : '';
$sojib68_phone   = defined( 'SOJIB68_PHONE' ) ? SOJIB68_PHONE : '';
$sojib68_email   = defined( 'SOJIB68_EMAIL' ) ? SOJIB68_EMAIL : '';
$sojib68_socials = function_exists( 'sojib68_get_social_links' ) ? sojib68_get_social_links() : array();
?>
<section class="contact contact--actions" id="contact">
	<div class="site-container">
		<div class="contact__head">
			<h2><?php esc_html_e( 'আপনার জমির বিষয়টি নিয়ে কথা বলুন।', 'sojib68' ); ?></h2>
			<p><?php esc_html_e( 'নিচের যেকোনো একটি মাধ্যমে সরাসরি যোগাযোগ করুন।', 'sojib68' ); ?></p>
		</div>

		<div class="contact__actions">
			<?php if ( $sojib68_wa ) : ?>
				<a class="contact__action contact__action--primary" href="https://wa.me/<?php echo esc_attr( $sojib68_wa ); ?>?text=<?php echo esc_attr( rawurlencode( __( 'আসসালামু আলাইকুম, আমি জমি সংক্রান্ত বিষয়ে জানতে চাই।', 'sojib68' ) ) ); ?>" target="_blank" rel="noopener" data-lead="whatsapp">
					<span class="contact__action-label"><?php esc_html_e( 'WhatsApp-এ মেসেজ করুন', 'sojib68' ); ?></span>
				</a>
			<?php endif; ?>
			<?php if ( $sojib68_phone ) : ?>
				<a class="contact__action" href="tel:<?php echo esc_attr( $sojib68_phone ); ?>" data-lead="call">
					<span class="contact__action-label"><?php esc_html_e( 'কল করুন', 'sojib68' ); ?></span>
					<span class="contact__action-sub"><?php echo esc_html( preg_replace( '/^\+?880/', '0', $sojib68_phone ) ); ?></span>
				</a>
			<?php endif; ?>
			<?php foreach ( $sojib68_socials as $network => $url ) : ?>
				<a class="contact__action" href="<?php echo esc_url( $url ); ?>" target="_blank" rel="noopener" data-lead="<?php echo esc_attr( $network ); ?>">
					<span class="contact__action-label"><?php echo esc_html( ucfirst( $network ) ); ?></span>
				</a>
			<?php endforeach; ?>
			<?php if ( $sojib68_email ) : ?>
				<a class="contact__action" href="mailto:<?php echo esc_attr( $sojib68_email ); ?>" data-lead="email">
					<span class="contact__action-label"><?php esc_html_e( 'ইমেইল করুন', 'sojib68' ); ?></span>
					<span class="contact__action-sub"><?php echo esc_html( $sojib68_email ); ?></span>
				</a>
			<?php endif; ?>
		</div>

		<?php if ( defined( 'SOJIB68_ADDRESS' ) && SOJIB68_ADDRESS ) : ?>
			<p class="contact__address"><?php echo esc_html( SOJIB68_ADDRESS ); ?></p>
		<?php endif; ?>
		<p class="contact__disclaimer-light"><?php esc_html_e( 'দলিলের ছবি, জাতীয় পরিচয়পত্র বা সংবেদনশীল ব্যক্তিগত তথ্য প্রথম বার্তায় পাঠাবেন না।', 'sojib68' ); ?></p>
	</div>
</section>

<?php
get_footer();
