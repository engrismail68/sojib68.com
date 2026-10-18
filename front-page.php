<?php
/**
 * Homepage template for the Sojib68 theme.
 */

get_header();

$services = sojib68_get_services();
?>

<div id="top"></div>

<!-- Hero -->
<section class="hero">
	<div class="site-container hero__inner">
		<div class="hero__content">
			<p class="eyebrow"><?php esc_html_e( 'Ismail Hossain Sojib · Land & IT Consultant', 'sojib68' ); ?></p>
			<h1 class="hero__heading">
				<?php esc_html_e( 'জমির কাগজ নিয়ে জটিলতা?', 'sojib68' ); ?><br>
				<?php esc_html_e( 'শুরু করুন সঠিক পরামর্শ দিয়ে।', 'sojib68' ); ?>
			</h1>
			<p class="hero__desc">
				<?php esc_html_e( 'জমি রেজিস্ট্রেশন, কেনাবেচা, নামজারি ও দলিলসংক্রান্ত বিষয়ে প্রয়োজনীয় পরামর্শ ও সহায়তা নিন।', 'sojib68' ); ?>
			</p>
			<div class="hero__actions">
				<a href="#contact" class="btn btn-primary"><?php esc_html_e( 'আপনার সমস্যাটি জানান', 'sojib68' ); ?></a>
				<a href="#services" class="btn btn-outline"><?php esc_html_e( 'সেবাগুলো দেখুন', 'sojib68' ); ?></a>
			</div>
			<p class="hero__line">
				<?php esc_html_e( 'রেজিস্ট্রেশন • নামজারি • দলিল অনুসন্ধান • জমি পরিমাপ', 'sojib68' ); ?>
			</p>
		</div>
		<div class="hero__media">
			<div class="hero__media-lines" aria-hidden="true">
				<svg viewBox="0 0 400 400" fill="none" xmlns="http://www.w3.org/2000/svg">
					<path d="M20 60 L180 20 L380 90 L340 220 L200 380 L40 300 Z" stroke="currentColor" stroke-width="1"/>
					<path d="M180 20 L200 380" stroke="currentColor" stroke-width="1"/>
					<path d="M20 60 L340 220" stroke="currentColor" stroke-width="1"/>
					<path d="M380 90 L40 300" stroke="currentColor" stroke-width="1"/>
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

<!-- Problem-based services -->
<section class="services" id="services">
	<div class="site-container">
		<div class="section-heading">
			<h2><?php esc_html_e( 'কোন কাজে সহায়তা দরকার?', 'sojib68' ); ?></h2>
			<p><?php esc_html_e( 'আপনার প্রয়োজনের সঙ্গে মিলিয়ে সেবাটি বেছে নিন।', 'sojib68' ); ?></p>
		</div>

		<div class="services__grid">
			<?php foreach ( $services as $key => $service ) : ?>
				<a href="#contact" class="service-card" data-service="<?php echo esc_attr( $key ); ?>">
					<?php echo sojib68_icon( $service['icon'] ); ?>
					<h3><?php echo esc_html( $service['title'] ); ?></h3>
					<p><?php echo esc_html( $service['text'] ); ?></p>
					<span class="service-card__link"><?php esc_html_e( 'বিস্তারিত জানান →', 'sojib68' ); ?></span>
				</a>
			<?php endforeach; ?>
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
			<h2><?php esc_html_e( 'দলিল ও ভূমির কাজে, প্রযুক্তির দক্ষতা সঙ্গে নিয়ে।', 'sojib68' ); ?></h2>
			<p>
				<?php esc_html_e( 'আমি Ismail Hossain Sojib। পেশাগতভাবে দলিল, জমি রেজিস্ট্রেশন এবং ভূমিসংক্রান্ত বিভিন্ন বিষয়ে পরামর্শ ও সহায়তা প্রদান করি।', 'sojib68' ); ?>
			</p>
			<p>
				<?php esc_html_e( 'আমার পড়াশোনা তথ্যপ্রযুক্তিতে। সেই জ্ঞান কাজে লাগিয়ে প্রয়োজনীয় তথ্য ও কাগজপত্র গোছানো, অনলাইন ভূমিসেবার কাজ এবং গ্রাহকের সঙ্গে যোগাযোগ সহজ রাখার চেষ্টা করি।', 'sojib68' ); ?>
			</p>
			<ul class="about__points">
				<li><?php esc_html_e( 'আপনার বিষয়টি বুঝে করণীয় জানানো', 'sojib68' ); ?></li>
				<li><?php esc_html_e( 'প্রয়োজনীয় কাগজপত্রের তালিকা বুঝিয়ে দেওয়া', 'sojib68' ); ?></li>
				<li><?php esc_html_e( 'কাজের পরিধি ও খরচ আগে আলোচনা করা', 'sojib68' ); ?></li>
			</ul>
		</div>
	</div>
</section>

<!-- Process -->
<section class="process">
	<div class="site-container">
		<div class="section-heading">
			<h2><?php esc_html_e( 'কীভাবে শুরু করবেন?', 'sojib68' ); ?></h2>
		</div>

		<ol class="process__steps">
			<li>
				<span class="process__num">০১</span>
				<h3><?php esc_html_e( 'আপনার বিষয়টি জানান', 'sojib68' ); ?></h3>
				<p><?php esc_html_e( 'ফোন, WhatsApp অথবা ফর্মে আপনার প্রয়োজন সংক্ষেপে জানান।', 'sojib68' ); ?></p>
			</li>
			<li>
				<span class="process__num">০২</span>
				<h3><?php esc_html_e( 'প্রয়োজনীয় কাগজপত্র দেখান', 'sojib68' ); ?></h3>
				<p><?php esc_html_e( 'বিষয় অনুযায়ী কোন কাগজগুলো প্রয়োজন, তা আলোচনা করে নিন।', 'sojib68' ); ?></p>
			</li>
			<li>
				<span class="process__num">০৩</span>
				<h3><?php esc_html_e( 'করণীয় ও খরচ বুঝে সিদ্ধান্ত নিন', 'sojib68' ); ?></h3>
				<p><?php esc_html_e( 'সেবার পরিধি, সম্ভাব্য সময় ও খরচ আলোচনা করে কাজ শুরু করুন।', 'sojib68' ); ?></p>
			</li>
		</ol>
		<p class="process__note"><?php esc_html_e( 'সময় ও খরচ কাজের ধরন এবং সংশ্লিষ্ট দপ্তরের প্রক্রিয়ার ওপর নির্ভর করবে।', 'sojib68' ); ?></p>
	</div>
</section>

<!-- Resources (structure ready; links intentionally withheld until real content exists) -->
<section class="resources" id="resources">
	<div class="site-container">
		<div class="section-heading">
			<h2><?php esc_html_e( 'জমির কাজে প্রয়োজনীয় তথ্য', 'sojib68' ); ?></h2>
		</div>

		<ul class="resources__list">
			<li>
				<span class="resources__title"><?php esc_html_e( 'জমি কেনার আগে কী কী যাচাই করবেন?', 'sojib68' ); ?></span>
				<span class="resources__status"><?php esc_html_e( 'শীঘ্রই প্রকাশিত হবে', 'sojib68' ); ?></span>
			</li>
			<li>
				<span class="resources__title"><?php esc_html_e( 'রেজিস্ট্রেশনের জন্য প্রয়োজনীয় কাগজপত্র', 'sojib68' ); ?></span>
				<span class="resources__status"><?php esc_html_e( 'শীঘ্রই প্রকাশিত হবে', 'sojib68' ); ?></span>
			</li>
			<li>
				<span class="resources__title"><?php esc_html_e( 'জমির পরিমাপ ক্যালকুলেটর', 'sojib68' ); ?></span>
				<span class="resources__status"><?php esc_html_e( 'শীঘ্রই প্রকাশিত হবে', 'sojib68' ); ?></span>
			</li>
		</ul>
	</div>
</section>

<!-- FAQ -->
<section class="faq">
	<div class="site-container">
		<div class="section-heading">
			<h2><?php esc_html_e( 'সাধারণ প্রশ্ন', 'sojib68' ); ?></h2>
		</div>

		<div class="faq__list">
			<details class="faq__item">
				<summary><?php esc_html_e( 'প্রথমবার যোগাযোগের সময় কী জানাব?', 'sojib68' ); ?></summary>
				<p><?php esc_html_e( 'জমিটি কোন এলাকায়, কী ধরনের সহায়তা প্রয়োজন এবং আপনার কাছে কী কাগজপত্র আছে—সংক্ষেপে জানাতে পারেন।', 'sojib68' ); ?></p>
			</details>
			<details class="faq__item">
				<summary><?php esc_html_e( 'সেবার খরচ কীভাবে জানব?', 'sojib68' ); ?></summary>
				<p><?php esc_html_e( 'আপনার বিষয় ও প্রয়োজনীয় কাজের পরিধি বোঝার পর খরচ আলোচনা করা হবে।', 'sojib68' ); ?></p>
			</details>
			<details class="faq__item">
				<summary><?php esc_html_e( 'হারানো দলিল খুঁজতে কী তথ্য প্রয়োজন?', 'sojib68' ); ?></summary>
				<p><?php esc_html_e( 'দলিল নম্বর, সাল, সংশ্লিষ্ট সাব-রেজিস্ট্রি অফিস বা পক্ষদের নাম জানা থাকলে অনুসন্ধানে সুবিধা হয়। আপনার কাছে যে তথ্য আছে, সেটি নিয়ে যোগাযোগ করুন।', 'sojib68' ); ?></p>
			</details>
			<details class="faq__item">
				<summary><?php esc_html_e( 'অনলাইনে প্রাথমিকভাবে কথা বলা যাবে?', 'sojib68' ); ?></summary>
				<p><?php esc_html_e( 'ফোন বা WhatsApp-এ বিষয়টি জানিয়ে প্রাথমিকভাবে আলোচনা করতে পারেন। প্রয়োজন হলে কাগজপত্রসহ সরাসরি সাক্ষাতের ব্যবস্থা করা যাবে।', 'sojib68' ); ?></p>
			</details>
		</div>
	</div>
</section>

<!-- Contact -->
<section class="contact" id="contact">
	<div class="site-container contact__inner">
		<div class="contact__intro">
			<h2><?php esc_html_e( 'আপনার জমিসংক্রান্ত বিষয়টি জানান।', 'sojib68' ); ?></h2>
			<p><?php esc_html_e( 'কী সহায়তা প্রয়োজন, সংক্ষেপে লিখুন। বিস্তারিত আলোচনার জন্য যোগাযোগ করা হবে।', 'sojib68' ); ?></p>

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
				<label for="field-message"><?php esc_html_e( 'সমস্যার সংক্ষিপ্ত বিবরণ', 'sojib68' ); ?> <span class="req">*</span></label>
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
				<?php esc_html_e( 'এই ফর্মে জাতীয় পরিচয়পত্র, দলিলের ছবি বা সংবেদনশীল ব্যক্তিগত তথ্য পাঠাবেন না।', 'sojib68' ); ?>
			</p>
		</form>
	</div>
</section>

<?php
get_footer();
