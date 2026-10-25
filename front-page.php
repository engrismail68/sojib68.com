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

<!-- About -->
<section class="about" id="about">
	<div class="site-container">
		<h2 class="section-title-center"><?php esc_html_e( 'আমার সম্পর্কে', 'sojib68' ); ?></h2>

		<div class="about__inner">
			<div class="about__media">
				<img
					src="<?php echo esc_url( get_theme_file_uri( 'assets/images/sojib-portrait.jpg' ) ); ?>"
					alt="<?php esc_attr_e( 'Ismail Hossain Sojib', 'sojib68' ); ?>"
					width="520"
					height="524"
					loading="lazy"
				>
			</div>
			<div class="about__content">
				<p>
					<?php esc_html_e( 'আমি Ismail Hossain Sojib। পেশাগতভাবে দলিল, জমি রেজিস্ট্রেশন ও ভূমিসংক্রান্ত বিভিন্ন জটিলতা নিয়ে কাজ করি। প্রয়োজন অনুযায়ী কাগজপত্র, সংশ্লিষ্ট প্রক্রিয়া ও পরবর্তী করণীয় সম্পর্কে পরামর্শ ও সহায়তা প্রদান করি।', 'sojib68' ); ?>
				</p>
				<p>
					<?php esc_html_e( 'আমার পড়াশোনা তথ্যপ্রযুক্তিতে। এই দক্ষতা কাজে লাগিয়ে তথ্য ও কাগজপত্র গোছানো, অনলাইন ভূমিসেবার কাজ এবং গ্রাহকের সঙ্গে যোগাযোগ সহজ রাখার চেষ্টা করি।', 'sojib68' ); ?>
				</p>
				<a href="#contact" class="btn btn-outline"><?php esc_html_e( 'আরও জানুন', 'sojib68' ); ?></a>

				<?php if ( ! empty( $stats ) ) : ?>
					<div class="about__stats">
						<?php foreach ( $stats as $stat ) : ?>
							<div class="stat-box">
								<span class="stat-box__number"><?php echo esc_html( $stat['number'] ); ?></span>
								<span class="stat-box__label"><?php echo esc_html( $stat['label'] ); ?></span>
							</div>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>
			</div>
		</div>
	</div>
</section>

<!-- তিন প্রজন্মের পথচলা (teaser → /about) -->
<section class="gen-teaser" aria-labelledby="gen-teaser-title">
	<div class="site-container gen-teaser__inner">
		<div class="gen-teaser__text">
			<p class="eyebrow"><?php esc_html_e( 'পারিবারিক পেশা', 'sojib68' ); ?></p>
			<h2 id="gen-teaser-title"><?php esc_html_e( 'তিন প্রজন্মের পথচলা', 'sojib68' ); ?></h2>
			<p><?php esc_html_e( '১৯৫০ সালে দাদা আলী আহাম্মদের হাত ধরে দলিল লেখার পেশায় আমাদের পরিবারের যাত্রা শুরু। বাবা কাজ করছেন ১৯৮১ সাল থেকে, আর আমি ২০২০ সাল থেকে তাঁর সহকারী হিসেবে যুক্ত।', 'sojib68' ); ?></p>
			<a href="<?php echo esc_url( home_url( '/about/' ) ); ?>" class="btn btn-outline"><?php esc_html_e( 'আমার পরিচয় ও পেশাগত উত্তরাধিকার', 'sojib68' ); ?></a>
		</div>
		<div class="gen-teaser__years">
			<p class="gen-teaser__line">
				<span>১৯৫০</span><i aria-hidden="true">→</i><span>১৯৮১</span><i aria-hidden="true">→</i><span>২০২০</span>
			</p>
			<p class="gen-teaser__caption"><?php esc_html_e( 'তিন প্রজন্মের কাজ শুরুর বছর', 'sojib68' ); ?></p>
		</div>
	</div>
</section>

<!-- Services -->
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
						<a href="#contact" class="btn btn-outline btn-sm service-card__cta" data-service="<?php echo esc_attr( $key ); ?>">
							<?php esc_html_e( 'বিস্তারিত জানুন', 'sojib68' ); ?>
						</a>
					</div>
				</div>
			<?php endforeach; ?>
		</div>
	</div>
</section>

<!-- Portfolio -->
<section class="portfolio" id="work">
	<div class="site-container">
		<div class="section-heading-center">
			<p class="eyebrow"><?php esc_html_e( 'আমার কাজ', 'sojib68' ); ?></p>
			<h2><?php esc_html_e( 'আমার কাজের অভিজ্ঞতা', 'sojib68' ); ?></h2>
		</div>

		<?php if ( empty( $case_stories ) ) : ?>
			<div class="work__empty">
				<p><?php esc_html_e( 'এই অংশে শীঘ্রই বাস্তব কাজের অভিজ্ঞতা (অনুমতিপ্রাপ্ত ছবি ও বিবরণসহ) যুক্ত করা হবে।', 'sojib68' ); ?></p>
			</div>
		<?php else : ?>
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
		<?php endif; ?>
	</div>
</section>

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

<!-- Contact -->
<section class="contact" id="contact">
	<div class="site-container contact__inner">
		<div class="contact__intro">
			<h2><?php esc_html_e( 'আপনার জমির বিষয়টি নিয়ে কথা বলুন।', 'sojib68' ); ?></h2>
			<p><?php esc_html_e( 'কী সহায়তা প্রয়োজন, সংক্ষেপে জানান। বিস্তারিত আলোচনার জন্য যোগাযোগ করা হবে।', 'sojib68' ); ?></p>

			<ul class="contact__details">
				<?php if ( defined( 'SOJIB68_PHONE' ) && SOJIB68_PHONE ) : ?>
					<li><a href="tel:<?php echo esc_attr( SOJIB68_PHONE ); ?>"><?php esc_html_e( 'কল করুন', 'sojib68' ); ?>: <?php echo esc_html( SOJIB68_PHONE ); ?></a></li>
				<?php endif; ?>
				<?php if ( defined( 'SOJIB68_WHATSAPP' ) && SOJIB68_WHATSAPP ) : ?>
					<li><a href="https://wa.me/<?php echo esc_attr( SOJIB68_WHATSAPP ); ?>" target="_blank" rel="noopener">WhatsApp</a></li>
				<?php endif; ?>
				<?php if ( defined( 'SOJIB68_EMAIL' ) && SOJIB68_EMAIL ) : ?>
					<li><a href="mailto:<?php echo esc_attr( SOJIB68_EMAIL ); ?>"><?php echo esc_html( SOJIB68_EMAIL ); ?></a></li>
				<?php endif; ?>
				<?php if ( defined( 'SOJIB68_ADDRESS' ) && SOJIB68_ADDRESS ) : ?>
					<li><?php echo esc_html( SOJIB68_ADDRESS ); ?></li>
				<?php endif; ?>
			</ul>
		</div>

		<form class="contact__form" id="contact-form" novalidate>
			<div class="form-row">
				<label for="field-name"><?php esc_html_e( 'নাম', 'sojib68' ); ?> <span class="req">*</span></label>
				<input type="text" id="field-name" name="name" required autocomplete="name">
				<span class="field-error" data-error-for="name"></span>
			</div>

			<div class="form-row">
				<label for="field-phone"><?php esc_html_e( 'মোবাইল নম্বর', 'sojib68' ); ?> <span class="req">*</span></label>
				<input type="tel" id="field-phone" name="phone" required autocomplete="tel">
				<span class="field-error" data-error-for="phone"></span>
			</div>

			<div class="form-row">
				<label for="field-service"><?php esc_html_e( 'সেবার ধরন', 'sojib68' ); ?> <span class="req">*</span></label>
				<select id="field-service" name="service" required>
					<option value=""><?php esc_html_e( '— নির্বাচন করুন —', 'sojib68' ); ?></option>
					<?php foreach ( $services as $key => $service ) : ?>
						<option value="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $service['title'] ); ?></option>
					<?php endforeach; ?>
					<option value="other"><?php esc_html_e( 'অন্যান্য', 'sojib68' ); ?></option>
				</select>
				<span class="field-error" data-error-for="service"></span>
			</div>

			<div class="form-row">
				<label for="field-message"><?php esc_html_e( 'সংক্ষিপ্ত বিবরণ', 'sojib68' ); ?> <span class="req">*</span></label>
				<textarea id="field-message" name="message" rows="4" required></textarea>
				<span class="field-error" data-error-for="message"></span>
			</div>

			<div class="form-row form-row--honeypot" aria-hidden="true">
				<label for="field-website"><?php esc_html_e( 'Website', 'sojib68' ); ?></label>
				<input type="text" id="field-website" name="sojib68_website" tabindex="-1" autocomplete="off">
			</div>

			<button type="submit" class="btn btn-primary contact__submit">
				<span class="btn-label"><?php esc_html_e( 'পরামর্শের অনুরোধ পাঠান', 'sojib68' ); ?></span>
			</button>

			<p class="contact__status" role="status" aria-live="polite"></p>

			<p class="contact__disclaimer">
				<?php esc_html_e( 'এই ফর্মে দলিলের ছবি, জাতীয় পরিচয়পত্র বা সংবেদনশীল ব্যক্তিগত তথ্য পাঠাবেন না।', 'sojib68' ); ?>
			</p>
		</form>
	</div>
</section>

<?php
get_footer();
