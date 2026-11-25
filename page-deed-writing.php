<?php
/**
 * Template for /deed-writing — "দলিল লিখুন".
 * Only facts already on the site are stated here (family profession dates, role);
 * no fees, legal requirements or document lists are invented.
 */

// Signed-in office users get the deed-writing tool on this page; everyone else sees the public page below.
$sojib68_dw_staff = function_exists( 'sojib68_office_is_authorized' ) && sojib68_office_is_authorized();
if ( $sojib68_dw_staff ) {
	if ( ! defined( 'DONOTCACHEPAGE' ) ) {
		define( 'DONOTCACHEPAGE', true );
	}
	nocache_headers();
	do_action( 'litespeed_control_set_nocache', 'deed writer (signed-in)' );
}

get_header();

if ( $sojib68_dw_staff ) :
	?>
	<main class="dw-page dw-page--tool">
		<div class="site-container">
			<h1><?php esc_html_e( 'দলিল লিখুন', 'sojib68' ); ?></h1>
			<p class="dw-lead"><?php esc_html_e( 'তথ্য ভরুন, নিজে নিজে সেভ হবে। শেষে বিজয় ফন্টে Word ডাউনলোড করুন।', 'sojib68' ); ?></p>
			<?php sojib68_dw_render( home_url( '/deed-writing/' ) ); ?>
		</div>
	</main>
	<?php
	get_footer();
	return;
endif;

$wa_base = 'https://wa.me/' . ( defined( 'SOJIB68_WHATSAPP' ) ? SOJIB68_WHATSAPP : '' );
$wa_text = 'আসসালামু আলাইকুম, আমি দলিল লেখাতে চাই। কোন ধরনের দলিল এবং কী কী তথ্য লাগবে জানতে চাই।';
$inq_text = "আমি দলিল লেখাতে চাই।\nদলিলের ধরন:\nজমির এলাকা:";
?>

<main class="dw-page">
	<div class="site-container">
		<section class="dw-gate" aria-labelledby="dw-gate-title">
			<span class="dw-gate__icon" aria-hidden="true">
				<svg viewBox="0 0 24 24" width="44" height="44" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="10.5" width="16" height="10" rx="2.2"/><path d="M8 10.5V7.8a4 4 0 0 1 8 0v2.7"/><circle cx="12" cy="15.5" r="1.3" fill="currentColor"/></svg>
			</span>
			<div class="dw-gate__body">
				<h1 id="dw-gate-title"><?php esc_html_e( 'এটি শুধুমাত্র দলিল লেখার জন্য ব্যবহৃত পেজ', 'sojib68' ); ?></h1>
				<p class="dw-gate__lead"><?php esc_html_e( 'দলিল লেখার জন্য লগইন করুন।', 'sojib68' ); ?></p>
				<p class="dw-gate__sub"><?php esc_html_e( 'শুধু দলিল লেখক ও অফিসের অনুমোদিত কর্মীরা এই সিস্টেম ব্যবহার করতে পারেন।', 'sojib68' ); ?></p>
				<p class="dw-gate__act">
					<a class="btn btn-primary dw-gate__btn" href="<?php echo esc_url( add_query_arg( 'redirect_to', rawurlencode( home_url( '/deed-writing/' ) ), SOJIB68_OFFICE_LOGIN_URL ) ); ?>"><?php esc_html_e( 'লগইন করুন', 'sojib68' ); ?></a>
				</p>
			</div>
		</section>

		<header class="dw-head">
			<p class="eyebrow"><?php esc_html_e( 'দলিল লিখুন', 'sojib68' ); ?></p>
			<h2 class="dw-head__title"><?php esc_html_e( 'দলিল লেখার পেশায় তিন প্রজন্মের অভিজ্ঞতা', 'sojib68' ); ?></h2>
			<p class="dw-lead"><?php esc_html_e( '১৯৫০ সাল থেকে আমাদের পরিবার দলিল লেখার পেশায় আছে। আমার বাবা মোঃ নুরুল ইসলাম ১৯৮১ সাল থেকে দলিল লেখক হিসেবে কাজ করছেন, আর ২০২০ সাল থেকে আমি তাঁর সহকারী হিসেবে যুক্ত আছি।', 'sojib68' ); ?></p>
			<p class="dw-lead dw-lead--small"><?php esc_html_e( 'আপনি যদি দলিল লেখাতে চান, নিচে যোগাযোগের বিকল্পগুলো দেখুন।', 'sojib68' ); ?></p>
		</header>

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
