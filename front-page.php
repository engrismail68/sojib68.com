<?php
/**
 * Homepage template for the Sojib68 theme.
 */

get_header();

$services    = sojib68_get_services();
$case_stories = function_exists( 'sojib68_get_case_stories' ) ? sojib68_get_case_stories() : array();
$testimonials = function_exists( 'sojib68_get_testimonials' ) ? sojib68_get_testimonials() : array();
?>

<div id="top"></div>

<!-- Hero -->
<section class="hero">
	<div class="site-container hero__inner">
		<div class="hero__content">
			<p class="hero__greeting"><?php esc_html_e( 'আসসালামু আলাইকুম, আমি', 'sojib68' ); ?></p>
			<h1 class="hero__name">Ismail Hossain Sojib</h1>
			<p class="hero__designation">Land &amp; IT Consultant</p>
			<p class="hero__statement">
				<?php esc_html_e( 'জমি ও দলিলের জটিলতায়,', 'sojib68' ); ?><br>
				<?php esc_html_e( 'সঠিক পদক্ষেপ নিতে পাশে আছি।', 'sojib68' ); ?>
			</p>
			<p class="hero__desc">
				<?php esc_html_e( 'জমি রেজিস্ট্রেশন, কেনাবেচা, নামজারি ও দলিলসংক্রান্ত বিষয়ে প্রয়োজনীয় পরামর্শ ও সহায়তা প্রদান করি।', 'sojib68' ); ?>
			</p>
			<div class="hero__actions">
				<a href="#contact" class="btn btn-primary"><?php esc_html_e( 'আপনার বিষয়টি জানান', 'sojib68' ); ?></a>
				<a href="#work" class="btn btn-outline"><?php esc_html_e( 'আমার কাজ দেখুন', 'sojib68' ); ?></a>
			</div>
		</div>
		<div class="hero__media">
			<div class="hero__media-shape" aria-hidden="true"></div>
			<div class="hero__media-lines" aria-hidden="true">
				<svg viewBox="0 0 400 400" fill="none" xmlns="http://www.w3.org/2000/svg" width="100%" height="100%" preserveAspectRatio="xMidYMid slice">
					<path d="M0 80 H400 M0 160 H400 M0 240 H400 M0 320 H400" stroke="currentColor" stroke-width="1"/>
					<path d="M80 0 V400 M160 0 V400 M240 0 V400 M320 0 V400" stroke="currentColor" stroke-width="1"/>
				</svg>
			</div>
			<img
				src="<?php echo esc_url( get_theme_file_uri( 'assets/images/sojib-portrait.jpg' ) ); ?>"
				alt="<?php esc_attr_e( 'Ismail Hossain Sojib', 'sojib68' ); ?>"
				class="hero__portrait"
				width="704"
				height="709"
			>
		</div>
	</div>
</section>

<!-- About -->
<section class="about" id="about">
	<div class="site-container about__inner">
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
			<p class="eyebrow"><?php esc_html_e( 'আমার সম্পর্কে', 'sojib68' ); ?></p>
			<h2>
				<?php esc_html_e( 'ভূমির কাজে অভিজ্ঞতা,', 'sojib68' ); ?><br>
				<?php esc_html_e( 'সঙ্গে প্রযুক্তির দক্ষতা।', 'sojib68' ); ?>
			</h2>
			<p>
				<?php esc_html_e( 'আমি Ismail Hossain Sojib। দলিল, জমি রেজিস্ট্রেশন এবং ভূমিসংক্রান্ত বিভিন্ন বিষয়ে পরামর্শ ও সহায়তা নিয়ে কাজ করি।', 'sojib68' ); ?>
			</p>
			<p>
				<?php esc_html_e( 'আমার পড়াশোনা তথ্যপ্রযুক্তিতে। সেই জ্ঞান কাজে লাগিয়ে প্রয়োজনীয় তথ্য ও কাগজপত্র গোছানো, অনলাইন সেবার কাজ এবং গ্রাহকের সঙ্গে যোগাযোগ সহজ রাখার চেষ্টা করি।', 'sojib68' ); ?>
			</p>
			<ul class="about__points">
				<li><?php esc_html_e( 'বিষয়টি বুঝে করণীয় জানানো', 'sojib68' ); ?></li>
				<li><?php esc_html_e( 'প্রয়োজনীয় কাগজপত্র সম্পর্কে পরিষ্কার ধারণা দেওয়া', 'sojib68' ); ?></li>
				<li><?php esc_html_e( 'কাজের পরিধি ও খরচ আগে আলোচনা করা', 'sojib68' ); ?></li>
			</ul>
		</div>
	</div>
</section>

<!-- Services: numbered interactive rows -->
<section class="services" id="services">
	<div class="site-container">
		<div class="section-heading">
			<p class="eyebrow"><?php esc_html_e( 'যেসব কাজে পাশে আছি', 'sojib68' ); ?></p>
			<h2><?php esc_html_e( 'আপনার প্রয়োজন অনুযায়ী ভূমিসেবা।', 'sojib68' ); ?></h2>
		</div>

		<div class="services-list">
			<?php foreach ( $services as $key => $service ) : ?>
				<?php $panel_id = 'service-panel-' . esc_attr( $key ); ?>
				<div class="service-row" data-service-row>
					<button
						type="button"
						class="service-row__header"
						aria-expanded="false"
						aria-controls="<?php echo esc_attr( $panel_id ); ?>"
					>
						<span class="service-row__number"><?php echo esc_html( $service['number'] ); ?></span>
						<span class="service-row__title"><?php echo esc_html( $service['title'] ); ?></span>
						<svg class="service-row__chevron" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 6l6 6-6 6"/></svg>
					</button>
					<div class="service-row__panel-outer">
						<div class="service-row__panel" id="<?php echo esc_attr( $panel_id ); ?>">
							<div class="service-row__panel-inner">
								<p><?php echo esc_html( $service['text'] ); ?></p>
								<a href="#contact" class="service-row__link" data-service="<?php echo esc_attr( $key ); ?>">
									<?php esc_html_e( 'এই বিষয়ে কথা বলুন ↗', 'sojib68' ); ?>
								</a>
							</div>
						</div>
					</div>
				</div>
			<?php endforeach; ?>
		</div>
	</div>
</section>

<!-- Work / Portfolio -->
<section class="work" id="work">
	<div class="site-container">
		<div class="section-heading">
			<p class="eyebrow"><?php esc_html_e( 'কাজের অভিজ্ঞতা', 'sojib68' ); ?></p>
			<h2>
				<?php esc_html_e( 'প্রতিটি কাজের পেছনে,', 'sojib68' ); ?><br>
				<?php esc_html_e( 'একটি বাস্তব প্রয়োজন।', 'sojib68' ); ?>
			</h2>
		</div>

		<?php if ( empty( $case_stories ) ) : ?>
			<div class="work__empty">
				<p><?php esc_html_e( 'এই অংশে শীঘ্রই বাস্তব কাজের অভিজ্ঞতা (case story) যুক্ত করা হবে।', 'sojib68' ); ?></p>
			</div>
		<?php else : ?>
			<?php $featured = array_shift( $case_stories ); ?>
			<div class="work__grid">
				<article class="work__featured">
					<p class="work__type"><?php echo esc_html( $featured['type'] ); ?></p>
					<h3><?php echo esc_html( $featured['title'] ); ?></h3>
					<p><?php echo esc_html( $featured['problem'] ); ?></p>
					<p><?php echo esc_html( $featured['help'] ); ?></p>
					<p class="work__label"><?php echo esc_html( $featured['outcome'] ); ?></p>
				</article>
				<div class="work__entries">
					<?php foreach ( $case_stories as $story ) : ?>
						<article class="work__entry">
							<p class="work__type"><?php echo esc_html( $story['type'] ); ?></p>
							<h3><?php echo esc_html( $story['title'] ); ?></h3>
							<p class="work__label"><?php echo esc_html( $story['outcome'] ); ?></p>
						</article>
					<?php endforeach; ?>
				</div>
			</div>
		<?php endif; ?>
	</div>
</section>

<!-- Process -->
<section class="process">
	<div class="site-container">
		<div class="section-heading">
			<h2>
				<?php esc_html_e( 'আপনার বিষয়টি থেকে,', 'sojib68' ); ?><br>
				<?php esc_html_e( 'পরবর্তী পদক্ষেপ পর্যন্ত।', 'sojib68' ); ?>
			</h2>
		</div>

		<ol class="process__steps">
			<li>
				<span class="process__num">০১</span>
				<h3><?php esc_html_e( 'বিষয়টি জানান', 'sojib68' ); ?></h3>
				<p><?php esc_html_e( 'আপনার প্রয়োজন ও জমির অবস্থান সম্পর্কে সংক্ষেপে জানান।', 'sojib68' ); ?></p>
			</li>
			<li>
				<span class="process__num">০২</span>
				<h3><?php esc_html_e( 'কাগজপত্র নিয়ে আলোচনা', 'sojib68' ); ?></h3>
				<p><?php esc_html_e( 'বিষয় অনুযায়ী প্রয়োজনীয় তথ্য ও কাগজপত্র নিয়ে আলোচনা করুন।', 'sojib68' ); ?></p>
			</li>
			<li>
				<span class="process__num">০৩</span>
				<h3><?php esc_html_e( 'করণীয় বুঝে কাজ শুরু', 'sojib68' ); ?></h3>
				<p><?php esc_html_e( 'সেবার পরিধি, সম্ভাব্য সময় ও খরচ বুঝে সিদ্ধান্ত নিন।', 'sojib68' ); ?></p>
			</li>
		</ol>
		<p class="process__note"><?php esc_html_e( 'সময় ও খরচ কাজের ধরন এবং সংশ্লিষ্ট দপ্তরের প্রক্রিয়ার ওপর নির্ভর করবে।', 'sojib68' ); ?></p>
	</div>
</section>

<!-- Guides & Tools -->
<section class="guides" id="resources">
	<div class="site-container">
		<div class="section-heading">
			<h2>
				<?php esc_html_e( 'সঠিক সিদ্ধান্তের আগে,', 'sojib68' ); ?><br>
				<?php esc_html_e( 'প্রয়োজনীয় তথ্য জানুন।', 'sojib68' ); ?>
			</h2>
		</div>

		<ul class="guides__list">
			<li>
				<span class="guides__title"><?php esc_html_e( 'জমি কেনার আগে যাচাইয়ের বিষয়গুলো', 'sojib68' ); ?></span>
				<span class="guides__status"><?php esc_html_e( 'শীঘ্রই প্রকাশিত হবে', 'sojib68' ); ?></span>
			</li>
			<li>
				<span class="guides__title"><?php esc_html_e( 'রেজিস্ট্রেশনের প্রয়োজনীয় কাগজপত্র', 'sojib68' ); ?></span>
				<span class="guides__status"><?php esc_html_e( 'শীঘ্রই প্রকাশিত হবে', 'sojib68' ); ?></span>
			</li>
			<li>
				<span class="guides__title"><?php esc_html_e( 'জমির পরিমাপ ক্যালকুলেটর', 'sojib68' ); ?></span>
				<span class="guides__status"><?php esc_html_e( 'শীঘ্রই প্রকাশিত হবে', 'sojib68' ); ?></span>
			</li>
		</ul>
	</div>
</section>

<?php if ( ! empty( $testimonials ) ) : ?>
<!-- Testimonials (only rendered once verified reviews exist) -->
<section class="testimonials">
	<div class="site-container">
		<div class="section-heading">
			<h2>
				<?php esc_html_e( 'যাঁদের সঙ্গে কাজ করেছি,', 'sojib68' ); ?><br>
				<?php esc_html_e( 'তাঁদের অভিজ্ঞতা।', 'sojib68' ); ?>
			</h2>
		</div>
		<div class="testimonials__grid">
			<?php foreach ( array_slice( $testimonials, 0, 3 ) as $t ) : ?>
				<div class="testimonial-card">
					<p class="testimonial-card__quote">“<?php echo esc_html( $t['quote'] ); ?>”</p>
					<p class="testimonial-card__name"><?php echo esc_html( $t['name'] ); ?></p>
					<p class="testimonial-card__meta"><?php echo esc_html( $t['meta'] ); ?></p>
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
					<li>
						<a href="tel:<?php echo esc_attr( SOJIB68_PHONE ); ?>"><?php esc_html_e( 'কল করুন', 'sojib68' ); ?>: <?php echo esc_html( SOJIB68_PHONE ); ?></a>
					</li>
				<?php endif; ?>
				<?php if ( defined( 'SOJIB68_WHATSAPP' ) && SOJIB68_WHATSAPP ) : ?>
					<li>
						<a href="https://wa.me/<?php echo esc_attr( SOJIB68_WHATSAPP ); ?>" target="_blank" rel="noopener">WhatsApp</a>
					</li>
				<?php endif; ?>
				<?php if ( defined( 'SOJIB68_EMAIL' ) && SOJIB68_EMAIL ) : ?>
					<li>
						<a href="mailto:<?php echo esc_attr( SOJIB68_EMAIL ); ?>"><?php echo esc_html( SOJIB68_EMAIL ); ?></a>
					</li>
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
				<label for="field-area"><?php esc_html_e( 'জমির এলাকা', 'sojib68' ); ?></label>
				<input type="text" id="field-area" name="area" autocomplete="address-level2">
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
