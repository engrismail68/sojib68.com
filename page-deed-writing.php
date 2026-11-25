<?php
/**
 * Template for /deed-writing — "দলিল লিখুন".
 * Only facts already on the site are stated here (family profession dates, role);
 * no fees, legal requirements or document lists are invented.
 */

// Signed-in office users get the deed-writing tool on this page; everyone else sees the public page below.
$sojib68_dw_staff = function_exists( 'sojib68_dw_can_use' ) && sojib68_dw_can_use();
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
			<p class="dw-userbar">
				<?php echo esc_html( wp_get_current_user()->display_name ); ?>
				<?php if ( defined( 'SOJIB68_2FA_PAGE' ) ) : ?>
					· <a href="<?php echo esc_url( admin_url( 'users.php?page=' . SOJIB68_2FA_PAGE ) ); ?>"><?php esc_html_e( 'টু-স্টেপ ভেরিফিকেশন', 'sojib68' ); ?></a>
				<?php endif; ?>
				· <a href="<?php echo esc_url( wp_logout_url( home_url( '/deed-writing/' ) ) ); ?>"><?php esc_html_e( 'লগআউট', 'sojib68' ); ?></a>
			</p>
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
	.dwg-or { display: flex; align-items: center; gap: 14px; margin: 26px 0 18px; color: #8a948f; font-size: .95rem; }
	.dwg-or::before, .dwg-or::after { content: ""; flex: 1; height: 1px; background: #e4e2d8; }
	.dwg-req-btn { display: inline-flex; align-items: center; justify-content: center; min-width: 230px; padding: 13px 30px; border-radius: 14px; border: 2px solid #198030; background: #fff; color: #14532d; font: inherit; font-size: 1.05rem; font-weight: 700; cursor: pointer; transition: background .15s ease; }
	.dwg-req-btn:hover { background: #eaf4ec; }
	.dwg-fee { margin: 12px auto 0; max-width: 420px; color: #566760; font-size: .95rem; }
	.dwg-form { margin: 22px 0 0; padding: 22px 20px; text-align: left; background: #f6faf6; border: 1px solid #cfe5d3; border-radius: 18px; }
	.dwg-form[hidden] { display: none; }
	.dwg-form h2 { margin: 0 0 4px; font-size: 1.25rem; color: #032737; }
	.dwg-form .dwg-form__note { margin: 0 0 14px; color: #566760; font-size: .93rem; }
	.dwg-field { display: grid; gap: 5px; margin: 0 0 14px; font-weight: 600; font-size: .98rem; color: #1f3a2c; }
	.dwg-field input, .dwg-field textarea { width: 100%; box-sizing: border-box; padding: 12px 14px; border: 1px solid #b9cdbd; border-radius: 10px; background: #fff; font: inherit; font-weight: 400; font-size: 1rem; }
	.dwg-field input:focus, .dwg-field textarea:focus { outline: 3px solid rgba(25,128,48,.25); border-color: #198030; }
	.dwg-card [hidden] { display: none !important; }
	.dwg-hp { position: absolute; left: -9999px; width: 1px; height: 1px; overflow: hidden; }
	.dwg-send { width: 100%; padding: 15px; border: 0; border-radius: 12px; background: linear-gradient(135deg, #198030, #0f6023); color: #fff; font: inherit; font-size: 1.1rem; font-weight: 700; cursor: pointer; }
	.dwg-send[disabled] { opacity: .6; cursor: wait; }
	.dwg-msg { margin: 12px 0 0; padding: 11px 14px; border-radius: 10px; font-size: .97rem; }
	.dwg-msg.is-ok { background: #e6f5e9; color: #14532d; border: 1px solid #a6d4af; }
	.dwg-msg.is-bad { background: #fdecec; color: #8a1c14; border: 1px solid #f0b4ae; }
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

		<div class="dwg-or"><?php esc_html_e( 'অথবা', 'sojib68' ); ?></div>
		<button type="button" class="dwg-req-btn" id="dwg-open"><?php esc_html_e( 'এই সেবা ব্যবহার করতে অনুরোধ পাঠান', 'sojib68' ); ?></button>
		<p class="dwg-fee"><?php esc_html_e( 'সেবা ব্যবহারে সার্ভিস চার্জ প্রযোজ্য।', 'sojib68' ); ?></p>

		<form class="dwg-form" id="dwg-form" hidden novalidate>
			<h2><?php esc_html_e( 'অ্যাক্সেসের অনুরোধ', 'sojib68' ); ?></h2>
			<p class="dwg-form__note"><?php esc_html_e( 'নিচের তথ্য দিন। আমরা দেখে অনুমোদন দিলে আপনার ইমেইলে পাসওয়ার্ড সেট করার লিঙ্ক যাবে। সেবা ব্যবহারে সার্ভিস চার্জ প্রযোজ্য।', 'sojib68' ); ?></p>
			<label class="dwg-field"><?php esc_html_e( 'নাম', 'sojib68' ); ?><input type="text" name="name" autocomplete="name" required></label>
			<label class="dwg-field"><?php esc_html_e( 'পূর্ণ ঠিকানা', 'sojib68' ); ?><textarea name="address" rows="3" autocomplete="street-address" required></textarea></label>
			<label class="dwg-field"><?php esc_html_e( 'মোবাইল নম্বর', 'sojib68' ); ?><input type="tel" name="mobile" inputmode="tel" autocomplete="tel" placeholder="01XXXXXXXXX" required></label>
			<label class="dwg-field"><?php esc_html_e( 'ইমেইল', 'sojib68' ); ?><input type="email" name="email" autocomplete="email" required></label>
			<div class="dwg-hp" aria-hidden="true"><label>Website <input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>
			<button type="submit" class="dwg-send" id="dwg-send"><?php esc_html_e( 'অনুরোধ পাঠান', 'sojib68' ); ?></button>
			<p class="dwg-msg" id="dwg-msg" hidden></p>
		</form>
		<script>
		( function () {
			var open = document.getElementById( 'dwg-open' ), form = document.getElementById( 'dwg-form' ), msg = document.getElementById( 'dwg-msg' ), send = document.getElementById( 'dwg-send' );
			if ( ! open || ! form ) { return; }
			open.addEventListener( 'click', function () { form.hidden = ! form.hidden; if ( ! form.hidden ) { form.querySelector( 'input' ).focus(); form.scrollIntoView( { behavior: 'smooth', block: 'center' } ); } } );
			function say( t, ok ) { msg.textContent = t; msg.hidden = ! t; msg.className = 'dwg-msg ' + ( ok ? 'is-ok' : 'is-bad' ); }
			form.addEventListener( 'submit', function ( e ) {
				e.preventDefault();
				var fd = new FormData( form ); fd.append( 'action', 'sojib68_dw_request' );
				send.disabled = true; say( 'পাঠানো হচ্ছে…', true );
				fetch( <?php echo wp_json_encode( admin_url( 'admin-ajax.php' ) ); ?>, { method: 'POST', body: fd, credentials: 'same-origin' } ).then( function ( r ) { return r.json(); } ).then( function ( res ) {
					if ( res && res.success ) { say( res.data.message, true ); form.reset(); open.hidden = true; [].forEach.call( form.querySelectorAll( '.dwg-field, .dwg-send, .dwg-form__note, h2' ), function ( el ) { el.hidden = true; } ); }
					else { say( ( res && res.data && res.data.message ) || 'পাঠানো যায়নি। আবার চেষ্টা করুন।', false ); send.disabled = false; }
				}, function () { say( 'সংযোগ সমস্যা। আবার চেষ্টা করুন।', false ); send.disabled = false; } );
			} );
		}() );
		</script>
	</section>
</main>

<?php
get_footer();
