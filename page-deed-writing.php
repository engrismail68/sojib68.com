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

$login_url = add_query_arg( 'redirect_to', rawurlencode( home_url( '/deed-writing/' ) ), SOJIB68_OFFICE_LOGIN_URL );
?>
<style>
	/* sign-in notice (inline so a cached stylesheet can never leave it unstyled) */
	.dwg { position: relative; display: grid; place-items: center; min-height: 66vh; padding: 64px 18px; background:
		radial-gradient(60% 55% at 12% 8%, rgba(25,128,48,.13), transparent 70%),
		radial-gradient(55% 50% at 92% 92%, rgba(243,233,181,.55), transparent 70%), #faf8f1; }
	.dwg-card { position: relative; width: min(560px, 100%); text-align: center; background: #fff; border: 1px solid #e4e2d8; border-radius: 26px; padding: 54px 40px 44px; box-shadow: 0 2px 4px rgba(3,39,55,.04), 0 24px 60px rgba(20,83,45,.14); overflow: hidden; }
	.dwg-card::before { content: ""; position: absolute; inset: 0 0 auto 0; height: 7px; background: linear-gradient(90deg, #14532d, #198030 55%, #7fc08c); }
	.dwg-ico { width: 92px; height: 92px; margin: 0 auto 26px; display: grid; place-items: center; border-radius: 50%; color: #fff; background: linear-gradient(145deg, #198030, #0f6023); box-shadow: 0 0 0 10px rgba(25,128,48,.10), 0 0 0 22px rgba(25,128,48,.05), 0 14px 28px rgba(15,96,35,.35); }
	.dwg-card h1 { text-wrap: balance; margin: 0 0 12px; color: #032737; font-size: clamp(1.55rem, 4.2vw, 2.15rem); line-height: 1.35; }
	.dwg-lead { margin: 0 0 8px; color: #14532d; font-size: clamp(1.15rem, 3vw, 1.45rem); font-weight: 700; }
	.dwg-sub { text-wrap: balance; margin: 0 auto 32px; max-width: 400px; color: #566760; font-size: 1rem; line-height: 1.7; }
	.dwg-btn { display: inline-flex; align-items: center; justify-content: center; gap: 10px; min-width: 230px; padding: 16px 38px; border-radius: 14px; background: linear-gradient(135deg, #198030, #0f6023); color: #fff !important; font-size: 1.15rem; font-weight: 700; text-decoration: none; box-shadow: 0 10px 24px rgba(15,96,35,.32); transition: transform .15s ease, box-shadow .15s ease; }
	.dwg-btn:hover, .dwg-btn:focus-visible { transform: translateY(-2px); box-shadow: 0 14px 30px rgba(15,96,35,.4); }
	@media (max-width: 520px) { .dwg-card { padding: 44px 22px 34px; border-radius: 22px; } .dwg-btn { width: 100%; min-width: 0; } }
</style>

<main class="dwg">
	<section class="dwg-card" aria-labelledby="dwg-title">
		<div class="dwg-ico" aria-hidden="true">
			<svg viewBox="0 0 24 24" width="44" height="44" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="10.5" width="16" height="10" rx="2.2"/><path d="M8 10.5V7.8a4 4 0 0 1 8 0v2.7"/><circle cx="12" cy="15.5" r="1.3" fill="currentColor"/></svg>
		</div>
		<h1 id="dwg-title"><?php esc_html_e( 'এটি শুধুমাত্র দলিল লেখার জন্য ব্যবহৃত পেজ', 'sojib68' ); ?></h1>
		<p class="dwg-lead"><?php esc_html_e( 'দলিল লেখার জন্য লগইন করুন।', 'sojib68' ); ?></p>
		<p class="dwg-sub"><?php esc_html_e( 'শুধু দলিল লেখক ও অফিসের অনুমোদিত কর্মীরা এই সিস্টেম ব্যবহার করতে পারেন।', 'sojib68' ); ?></p>
		<a class="dwg-btn" href="<?php echo esc_url( $login_url ); ?>"><?php esc_html_e( 'লগইন করুন', 'sojib68' ); ?> <span aria-hidden="true">→</span></a>
	</section>
</main>

<?php
get_footer();
