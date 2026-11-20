<?php
/**
 * Template for /deed-writing — "দলিল লিখুন".
 * Only facts already on the site are stated here (family profession dates, role);
 * no fees, legal requirements or document lists are invented.
 */

get_header();

$wa_base = 'https://wa.me/' . ( defined( 'SOJIB68_WHATSAPP' ) ? SOJIB68_WHATSAPP : '' );
$wa_text = 'আসসালামু আলাইকুম, আমি দলিল লেখাতে চাই। কোন ধরনের দলিল এবং কী কী তথ্য লাগবে জানতে চাই।';
$inq_text = "আমি দলিল লেখাতে চাই।\nদলিলের ধরন:\nজমির এলাকা:";
?>

<main class="dw-page">
	<div class="site-container">
		<header class="dw-head">
			<p class="eyebrow"><?php esc_html_e( 'দলিল লিখুন', 'sojib68' ); ?></p>
			<h1><?php esc_html_e( 'দলিল লেখার পেশায় তিন প্রজন্মের অভিজ্ঞতা', 'sojib68' ); ?></h1>
			<p class="dw-lead"><?php esc_html_e( '১৯৫০ সাল থেকে আমাদের পরিবার দলিল লেখার পেশায় আছে। আমার বাবা মোঃ নুরুল ইসলাম ১৯৮১ সাল থেকে দলিল লেখক হিসেবে কাজ করছেন, আর ২০২০ সাল থেকে আমি তাঁর সহকারী হিসেবে যুক্ত আছি।', 'sojib68' ); ?></p>
		</header>

		<p class="dw-staff"><?php esc_html_e( 'দলিল লেখক/অফিসের কর্মী?', 'sojib68' ); ?> <a href="<?php echo esc_url( add_query_arg( 'redirect_to', rawurlencode( home_url( '/deed-writing/' ) ), SOJIB68_OFFICE_LOGIN_URL ) ); ?>"><?php esc_html_e( 'লগইন করে দলিল লেখার সিস্টেম খুলুন', 'sojib68' ); ?></a></p>

		<div class="dw-grid">
			<section class="dw-card">
				<h2><?php esc_html_e( 'কীভাবে শুরু করবেন', 'sojib68' ); ?></h2>
				<ol class="dw-steps">
					<li><?php esc_html_e( 'নিচের বোতাম থেকে WhatsApp-এ বা ফর্মে জানান, আপনি কোন ধরনের দলিল লেখাতে চান।', 'sojib68' ); ?></li>
					<li><?php esc_html_e( 'আমরা আপনার বিষয়টি শুনে বলব কী কী তথ্য ও কাগজপত্র দরকার হবে।', 'sojib68' ); ?></li>
					<li><?php esc_html_e( 'কাগজপত্র দেখে দলিল প্রস্তুতের কাজ শুরু হবে।', 'sojib68' ); ?></li>
				</ol>
				<p class="dw-note"><?php esc_html_e( 'প্রতিটি ক্ষেত্রে ভিন্ন কাগজ লাগতে পারে, তাই আগে আপনার বিষয়টি শুনে নেওয়া হয়।', 'sojib68' ); ?></p>
			</section>

			<section class="dw-card dw-card--cta">
				<h2><?php esc_html_e( 'দলিল লেখাতে যোগাযোগ করুন', 'sojib68' ); ?></h2>
				<div class="dw-actions">
					<a class="btn btn-primary" href="<?php echo esc_url( $wa_base . '?text=' . rawurlencode( $wa_text ) ); ?>" target="_blank" rel="noopener noreferrer" data-lead="whatsapp"><?php esc_html_e( 'WhatsApp-এ জানান', 'sojib68' ); ?></a>
					<a class="btn btn-outline" href="<?php echo esc_url( home_url( '/#contact' ) ); ?>" data-inq-open data-inq-text="<?php echo esc_attr( $inq_text ); ?>" data-lead="inquiry_form"><?php esc_html_e( 'ফর্মে তথ্য দিন', 'sojib68' ); ?></a>
					<a class="btn btn-outline" href="<?php echo esc_url( 'tel:' . ( defined( 'SOJIB68_PHONE' ) ? SOJIB68_PHONE : '' ) ); ?>" data-lead="call"><?php esc_html_e( 'ফোন করুন', 'sojib68' ); ?></a>
				</div>
				<p class="dw-note"><?php esc_html_e( 'বিস্তারিত পরামর্শের জন্য নির্ধারিত ফি প্রযোজ্য হতে পারে।', 'sojib68' ); ?></p>
			</section>
		</div>
	</div>
</main>

<?php
get_footer();
