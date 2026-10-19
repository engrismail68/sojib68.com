<?php
/**
 * Private "দলিল রেকর্ড ও হিসাব" (office records) system.
 *
 * This is a separate, access-controlled area from the public site and
 * from any future CRM. It reuses WordPress's own authentication
 * (wp_signon, auth cookies, core password hashing, core lost-password
 * flow) rather than inventing a custom login mechanism — only the
 * presentation (branded login page + dashboard shell) is custom.
 *
 * Current scope: working authentication + a navigable dashboard shell
 * with placeholder sections. No record entry/search/report logic yet
 * (and no fake data is ever shown in those placeholders).
 *
 * Routing: intercepted on `init` by raw request path, so it works
 * regardless of permalink structure/rewrite rules. See
 * sojib68_office_router().
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Where the public "অফিস লগইন" footer link points. Change this constant
 * to an external URL (e.g. a future https://records.sojib68.com/) once
 * this system is moved to its own subdomain/app/database — nothing
 * else needs to change.
 */
if ( ! defined( 'SOJIB68_OFFICE_LOGIN_URL' ) ) {
	define( 'SOJIB68_OFFICE_LOGIN_URL', home_url( '/office-login/' ) );
}

define( 'SOJIB68_OFFICE_CAP', 'access_sojib_records' );
define( 'SOJIB68_OFFICE_BASE', 'office' );        // /office/...
define( 'SOJIB68_OFFICE_LOGIN_SLUG', 'office-login' ); // /office-login/

/**
 * Grants the office-records capability to Administrator and a
 * dedicated "Office Staff" role, so the existing WP administrator
 * account (created during the original WordPress install) already has
 * access, and additional staff accounts can be created by the admin
 * via Users > Add New without exposing public signup.
 */
function sojib68_office_ensure_role() {
	$admin = get_role( 'administrator' );
	if ( $admin && ! $admin->has_cap( SOJIB68_OFFICE_CAP ) ) {
		$admin->add_cap( SOJIB68_OFFICE_CAP );
	}

	if ( ! get_role( 'sojib_office_staff' ) ) {
		add_role(
			'sojib_office_staff',
			__( 'Office Staff', 'sojib68' ),
			array(
				'read'               => true,
				SOJIB68_OFFICE_CAP   => true,
			)
		);
	}
}
add_action( 'after_switch_theme', 'sojib68_office_ensure_role' );
add_action( 'init', 'sojib68_office_ensure_role' );

/**
 * Sends the standard "do not cache, do not index" headers used by
 * every page in this system (login and every dashboard screen).
 */
function sojib68_office_no_cache_no_index() {
	if ( ! defined( 'DONOTCACHEPAGE' ) ) {
		define( 'DONOTCACHEPAGE', true ); // Respected by most caching plugins (LiteSpeed Cache included).
	}
	if ( function_exists( 'nocache_headers' ) ) {
		nocache_headers();
	}
	header( 'X-Robots-Tag: noindex, nofollow, noarchive', true );
}

function sojib68_office_is_authorized() {
	return is_user_logged_in() && current_user_can( SOJIB68_OFFICE_CAP );
}

/**
 * Simple IP-based login throttling: 5 failed attempts locks that IP
 * out of further attempts for 15 minutes. WordPress core has no
 * built-in login rate limiting, so this closes that gap without
 * depending on a plugin.
 */
function sojib68_office_rate_limit_key() {
	$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? $_SERVER['REMOTE_ADDR'] : 'unknown';
	return 'sojib68_office_rl_' . md5( $ip );
}

function sojib68_office_is_rate_limited() {
	$data = get_transient( sojib68_office_rate_limit_key() );
	return is_array( $data ) && isset( $data['locked_until'] ) && $data['locked_until'] > time();
}

function sojib68_office_register_failed_attempt() {
	$key  = sojib68_office_rate_limit_key();
	$data = get_transient( $key );
	$data = is_array( $data ) ? $data : array( 'count' => 0, 'locked_until' => 0 );

	$data['count']++;

	if ( $data['count'] >= 5 ) {
		$data['locked_until'] = time() + ( 15 * MINUTE_IN_SECONDS );
		$data['count']        = 0;
	}

	set_transient( $key, $data, 30 * MINUTE_IN_SECONDS );
}

function sojib68_office_clear_rate_limit() {
	delete_transient( sojib68_office_rate_limit_key() );
}

/**
 * Pseudo-routing for /office-login/ and /office/... . Hooked on `init`
 * (which runs before WP resolves the main query), so it works the same
 * whether permalinks are pretty or plain, and doesn't need a rewrite
 * flush. Every matched route exits — nothing falls through to the
 * normal theme templates.
 */
function sojib68_office_router() {
	$request_path = (string) wp_parse_url( add_query_arg( array() ), PHP_URL_PATH );
	$home_path    = rtrim( (string) wp_parse_url( home_url(), PHP_URL_PATH ), '/' );

	if ( $home_path && 0 === strpos( $request_path, $home_path ) ) {
		$request_path = substr( $request_path, strlen( $home_path ) );
	}

	$request_path = trim( $request_path, '/' );

	if ( '' === $request_path ) {
		return;
	}

	$segments = explode( '/', $request_path );

	if ( SOJIB68_OFFICE_LOGIN_SLUG === $segments[0] ) {
		sojib68_office_no_cache_no_index();
		sojib68_office_handle_login_request();
		exit;
	}

	if ( SOJIB68_OFFICE_BASE === $segments[0] ) {
		sojib68_office_no_cache_no_index();

		if ( ! sojib68_office_is_authorized() ) {
			wp_safe_redirect( add_query_arg( 'redirect_to', urlencode( home_url( $_SERVER['REQUEST_URI'] ) ), SOJIB68_OFFICE_LOGIN_URL ) );
			exit;
		}

		if ( isset( $segments[1] ) && 'logout' === $segments[1] ) {
			sojib68_office_handle_logout();
			exit;
		}

		$page_key = isset( $segments[1] ) && '' !== $segments[1] ? $segments[1] : 'dashboard';
		sojib68_office_render_dashboard( $page_key );
		exit;
	}
}
add_action( 'init', 'sojib68_office_router' );

/* ---------------------------------------------------------------------
 * Login
 * ------------------------------------------------------------------ */

function sojib68_office_handle_login_request() {
	// Already signed in with access — skip straight to the dashboard.
	if ( sojib68_office_is_authorized() ) {
		$redirect = isset( $_GET['redirect_to'] ) ? esc_url_raw( urldecode( wp_unslash( $_GET['redirect_to'] ) ) ) : home_url( '/' . SOJIB68_OFFICE_BASE . '/' );
		wp_safe_redirect( $redirect );
		return;
	}

	$error = '';

	if ( 'POST' === $_SERVER['REQUEST_METHOD'] ) {
		if ( sojib68_office_is_rate_limited() ) {
			$error = __( 'অনেকবার ভুল চেষ্টা হয়েছে। কিছুক্ষণ পর আবার চেষ্টা করুন।', 'sojib68' );
		} elseif ( ! isset( $_POST['sojib68_office_nonce'] ) || ! wp_verify_nonce( wp_unslash( $_POST['sojib68_office_nonce'] ), 'sojib68_office_login' ) ) {
			$error = __( 'সেশনের মেয়াদ শেষ হয়ে গেছে। পাতাটি আবার লোড করে চেষ্টা করুন।', 'sojib68' );
		} else {
			$username = isset( $_POST['username'] ) ? sanitize_user( wp_unslash( $_POST['username'] ) ) : '';
			$password = isset( $_POST['password'] ) ? (string) wp_unslash( $_POST['password'] ) : '';

			$user = wp_signon( array(
				'user_login'    => $username,
				'user_password' => $password,
				'remember'      => true,
			), is_ssl() );

			if ( is_wp_error( $user ) ) {
				sojib68_office_register_failed_attempt();
				$error = __( 'ইউজারনেম বা পাসওয়ার্ড সঠিক নয়।', 'sojib68' );
			} elseif ( ! user_can( $user, SOJIB68_OFFICE_CAP ) ) {
				wp_logout();
				sojib68_office_register_failed_attempt();
				$error = __( 'এই অ্যাকাউন্টের এই সিস্টেমে প্রবেশের অনুমতি নেই।', 'sojib68' );
			} else {
				sojib68_office_clear_rate_limit();
				$redirect = isset( $_POST['redirect_to'] ) ? esc_url_raw( wp_unslash( $_POST['redirect_to'] ) ) : home_url( '/' . SOJIB68_OFFICE_BASE . '/' );
				wp_safe_redirect( $redirect );
				return;
			}
		}
	}

	sojib68_office_render_login( $error );
}

function sojib68_office_render_login( $error = '' ) {
	$redirect_to = isset( $_REQUEST['redirect_to'] ) ? esc_url_raw( wp_unslash( $_REQUEST['redirect_to'] ) ) : home_url( '/' . SOJIB68_OFFICE_BASE . '/' );
	$locked      = sojib68_office_is_rate_limited();
	?>
	<!DOCTYPE html>
	<html <?php language_attributes(); ?>>
	<head>
		<meta charset="<?php bloginfo( 'charset' ); ?>">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<meta name="robots" content="noindex, nofollow">
		<title><?php esc_html_e( 'অফিস লগইন', 'sojib68' ); ?> — <?php bloginfo( 'name' ); ?></title>
		<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Figtree:wght@400;500;600;700&family=Hind+Siliguri:wght@400;500;600;700&display=swap">
		<link rel="stylesheet" href="<?php echo esc_url( get_stylesheet_uri() ); ?>">
		<style><?php echo sojib68_office_inline_css(); // phpcs:ignore ?></style>
	</head>
	<body class="office-body">
		<div class="office-login-wrap">
			<div class="office-login-card">
				<img
					src="<?php echo esc_url( get_theme_file_uri( 'assets/images/logo.webp' ) ); ?>"
					alt="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>"
					class="office-login-logo"
					width="170"
					height="47"
				>
				<h1><?php esc_html_e( 'অফিস লগইন', 'sojib68' ); ?></h1>
				<p class="office-login-sub"><?php esc_html_e( 'দলিল রেকর্ড ও হিসাব সিস্টেম — শুধুমাত্র অনুমোদিত ব্যবহারকারীদের জন্য।', 'sojib68' ); ?></p>

				<?php if ( $error ) : ?>
					<div class="office-notice office-notice--error"><?php echo esc_html( $error ); ?></div>
				<?php endif; ?>

				<form method="post" action="<?php echo esc_url( SOJIB68_OFFICE_LOGIN_URL ); ?>" autocomplete="off">
					<?php wp_nonce_field( 'sojib68_office_login', 'sojib68_office_nonce' ); ?>
					<input type="hidden" name="redirect_to" value="<?php echo esc_attr( $redirect_to ); ?>">

					<div class="office-field">
						<label for="office-username"><?php esc_html_e( 'ইউজারনেম', 'sojib68' ); ?></label>
						<input type="text" id="office-username" name="username" required autocomplete="username" <?php echo $locked ? 'disabled' : ''; ?>>
					</div>

					<div class="office-field">
						<label for="office-password"><?php esc_html_e( 'পাসওয়ার্ড', 'sojib68' ); ?></label>
						<div class="office-password-row">
							<input type="password" id="office-password" name="password" required autocomplete="current-password" <?php echo $locked ? 'disabled' : ''; ?>>
							<button type="button" class="office-toggle-password" data-target="office-password" <?php echo $locked ? 'disabled' : ''; ?>>
								<?php esc_html_e( 'দেখুন', 'sojib68' ); ?>
							</button>
						</div>
					</div>

					<button type="submit" class="btn btn-primary office-login-submit" <?php echo $locked ? 'disabled' : ''; ?>>
						<?php esc_html_e( 'লগইন করুন', 'sojib68' ); ?>
					</button>
				</form>

				<p class="office-login-forgot">
					<a href="<?php echo esc_url( wp_lostpassword_url( SOJIB68_OFFICE_LOGIN_URL ) ); ?>">
						<?php esc_html_e( 'পাসওয়ার্ড ভুলে গেছেন?', 'sojib68' ); ?>
					</a>
				</p>

				<p class="office-login-back">
					<a href="<?php echo esc_url( home_url( '/' ) ); ?>">&larr; <?php esc_html_e( 'মূল ওয়েবসাইটে ফিরুন', 'sojib68' ); ?></a>
				</p>
			</div>
		</div>

		<script>
		(function () {
			'use strict';
			document.querySelectorAll('.office-toggle-password').forEach(function (btn) {
				btn.addEventListener('click', function () {
					var input = document.getElementById(btn.getAttribute('data-target'));
					if (!input) { return; }
					var showing = input.type === 'text';
					input.type = showing ? 'password' : 'text';
					btn.textContent = showing ? '<?php echo esc_js( __( 'দেখুন', 'sojib68' ) ); ?>' : '<?php echo esc_js( __( 'লুকান', 'sojib68' ) ); ?>';
				});
			});
		})();
		</script>
	</body>
	</html>
	<?php
}

function sojib68_office_handle_logout() {
	wp_safe_redirect( wp_logout_url( SOJIB68_OFFICE_LOGIN_URL ) );
}

/* ---------------------------------------------------------------------
 * Dashboard shell
 * ------------------------------------------------------------------ */

function sojib68_office_menu_items() {
	return array(
		'dashboard'  => __( 'ড্যাশবোর্ড', 'sojib68' ),
		'entry-new'  => __( 'নতুন দলিল এন্ট্রি', 'sojib68' ),
		'entry-old'  => __( 'পুরোনো খাতা এন্ট্রি', 'sojib68' ),
		'search'     => __( 'দলিল অনুসন্ধান', 'sojib68' ),
		'dues'       => __( 'জমা–বকেয়া', 'sojib68' ),
		'reports'    => __( 'রিপোর্ট ও ব্যাকআপ', 'sojib68' ),
	);
}

function sojib68_office_render_dashboard( $page_key ) {
	$menu = sojib68_office_menu_items();
	if ( ! isset( $menu[ $page_key ] ) ) {
		$page_key = 'dashboard';
	}

	$user = wp_get_current_user();
	?>
	<!DOCTYPE html>
	<html <?php language_attributes(); ?>>
	<head>
		<meta charset="<?php bloginfo( 'charset' ); ?>">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<meta name="robots" content="noindex, nofollow">
		<title><?php echo esc_html( $menu[ $page_key ] ); ?> — <?php esc_html_e( 'অফিস', 'sojib68' ); ?></title>
		<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Figtree:wght@400;500;600;700&family=Hind+Siliguri:wght@400;500;600;700&display=swap">
		<link rel="stylesheet" href="<?php echo esc_url( get_stylesheet_uri() ); ?>">
		<style><?php echo sojib68_office_inline_css(); // phpcs:ignore ?></style>
	</head>
	<body class="office-body office-dashboard-body">
		<div class="office-shell">
			<button type="button" class="office-menu-toggle" id="office-menu-toggle" aria-expanded="false" aria-controls="office-sidebar">
				<span></span><span></span><span></span>
			</button>

			<aside class="office-sidebar" id="office-sidebar">
				<img
					src="<?php echo esc_url( get_theme_file_uri( 'assets/images/logo.webp' ) ); ?>"
					alt="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>"
					class="office-sidebar-logo"
					width="150"
					height="41"
				>
				<nav aria-label="<?php esc_attr_e( 'অফিস মেনু', 'sojib68' ); ?>">
					<ul>
						<?php foreach ( $menu as $key => $label ) : ?>
							<li>
								<a
									href="<?php echo esc_url( home_url( '/' . SOJIB68_OFFICE_BASE . '/' . ( 'dashboard' === $key ? '' : $key ) ) ); ?>"
									class="<?php echo $page_key === $key ? 'is-active' : ''; ?>"
								>
									<?php echo esc_html( $label ); ?>
								</a>
							</li>
						<?php endforeach; ?>
					</ul>
				</nav>
			</aside>

			<div class="office-main">
				<header class="office-topbar">
					<span class="office-topbar-user">
						<?php echo esc_html( sprintf( /* translators: %s: display name */ __( '%s হিসেবে লগইন করা আছেন', 'sojib68' ), $user->display_name ) ); ?>
					</span>
					<a href="<?php echo esc_url( home_url( '/' . SOJIB68_OFFICE_BASE . '/logout' ) ); ?>" class="btn btn-outline btn-sm">
						<?php esc_html_e( 'লগআউট', 'sojib68' ); ?>
					</a>
				</header>

				<main class="office-content">
					<h1><?php echo esc_html( $menu[ $page_key ] ); ?></h1>
					<?php echo sojib68_office_render_section( $page_key ); // phpcs:ignore ?>
				</main>
			</div>
		</div>

		<script>
		(function () {
			'use strict';
			var toggle = document.getElementById('office-menu-toggle');
			var sidebar = document.getElementById('office-sidebar');
			if (toggle && sidebar) {
				toggle.addEventListener('click', function () {
					var isOpen = sidebar.classList.toggle('is-open');
					toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
				});
			}
		})();
		</script>
	</body>
	</html>
	<?php
}

function sojib68_office_render_section( $page_key ) {
	switch ( $page_key ) {
		case 'dashboard':
			return '<p>' . esc_html__( 'এখানে পরবর্তীতে সারসংক্ষেপ (সাম্প্রতিক এন্ট্রি, বকেয়ার অবস্থা ইত্যাদি) দেখানো হবে।', 'sojib68' ) . '</p>'
				. '<div class="office-placeholder"><p>' . esc_html__( 'এই মুহূর্তে দেখানোর মতো কোনো রেকর্ড নেই — ফর্ম ও ডেটা স্ট্রাকচার এখনো ঠিক করা হয়নি।', 'sojib68' ) . '</p></div>';

		case 'entry-new':
		case 'entry-old':
		case 'search':
		case 'dues':
		case 'reports':
			return '<div class="office-placeholder"><p>' . esc_html__( 'এই সেকশনের ফর্ম ও কার্যকারিতা পরবর্তী ধাপে তৈরি করা হবে।', 'sojib68' ) . '</p></div>';

		default:
			return '';
	}
}

function sojib68_office_inline_css() {
	return <<<CSS
.office-body {
	margin: 0;
	min-height: 100vh;
	font-family: var(--font-bn, 'Hind Siliguri', sans-serif);
	background: var(--color-cream, #F8F7F1);
	color: var(--color-body, #2C3D44);
}
.office-login-wrap {
	min-height: 100vh;
	display: flex;
	align-items: center;
	justify-content: center;
	padding: 24px;
}
.office-login-card {
	width: 100%;
	max-width: 380px;
	background: #fff;
	border-radius: var(--radius-md, 12px);
	border: 1px solid var(--color-border, #E3E1D6);
	padding: 36px 32px;
	text-align: center;
}
.office-login-logo { margin: 0 auto 20px; }
.office-login-card h1 { font-size: 1.4rem; margin-bottom: 0.3em; }
.office-login-sub { font-size: 0.88rem; color: var(--color-muted, #6E7D77); margin-bottom: 22px; }
.office-notice { border-radius: var(--radius-sm, 6px); padding: 10px 14px; font-size: 0.9rem; margin-bottom: 16px; text-align: left; }
.office-notice--error { background: #fdecea; color: #b3401f; }
.office-field { text-align: left; margin-bottom: 16px; }
.office-field label { display: block; font-weight: 600; font-size: 0.88rem; margin-bottom: 6px; }
.office-field input {
	width: 100%;
	padding: 11px 14px;
	border: 1px solid var(--color-border, #E3E1D6);
	border-radius: var(--radius-sm, 6px);
	font-family: inherit;
	font-size: 1rem;
}
.office-password-row { display: flex; gap: 8px; }
.office-password-row input { flex: 1; }
.office-toggle-password {
	flex-shrink: 0;
	background: var(--color-soft-green, #F2F8F2);
	border: 1px solid var(--color-border, #E3E1D6);
	border-radius: var(--radius-sm, 6px);
	padding: 0 14px;
	font-size: 0.84rem;
	cursor: pointer;
}
.office-login-submit { width: 100%; margin-top: 4px; }
.office-login-forgot { margin-top: 16px; font-size: 0.88rem; }
.office-login-forgot a { color: var(--color-green, #198030); font-weight: 600; }
.office-login-back { margin-top: 10px; font-size: 0.84rem; }
.office-login-back a { color: var(--color-muted, #6E7D77); }

.office-shell { display: flex; min-height: 100vh; }
.office-sidebar {
	width: 230px;
	flex-shrink: 0;
	background: var(--color-green-deep, #0f6023);
	color: #fff;
	padding: 24px 18px;
}
.office-sidebar-logo { filter: brightness(0) invert(1); margin-bottom: 24px; }
.office-sidebar nav ul { list-style: none; margin: 0; padding: 0; }
.office-sidebar nav li { margin-bottom: 4px; }
.office-sidebar nav a {
	display: block;
	padding: 10px 12px;
	border-radius: var(--radius-sm, 6px);
	color: #d6ead9;
	text-decoration: none;
	font-size: 0.94rem;
}
.office-sidebar nav a:hover { background: rgba(255,255,255,0.08); color: #fff; }
.office-sidebar nav a.is-active { background: var(--color-green, #198030); color: #fff; font-weight: 600; }
.office-main { flex: 1; min-width: 0; }
.office-topbar {
	display: flex;
	align-items: center;
	justify-content: space-between;
	padding: 16px 28px;
	border-bottom: 1px solid var(--color-border, #E3E1D6);
	background: #fff;
}
.office-topbar-user { font-size: 0.9rem; color: var(--color-muted, #6E7D77); }
.office-content { padding: 28px; }
.office-content h1 { font-size: 1.3rem; margin-bottom: 0.6em; }
.office-placeholder {
	border: 1px dashed var(--color-border, #E3E1D6);
	border-radius: var(--radius-md, 12px);
	padding: 36px 24px;
	text-align: center;
	color: var(--color-muted, #6E7D77);
	margin-top: 16px;
}
.office-menu-toggle {
	display: none;
	position: fixed;
	top: 14px;
	left: 14px;
	z-index: 20;
	width: 40px;
	height: 40px;
	flex-direction: column;
	justify-content: center;
	align-items: center;
	gap: 5px;
	background: var(--color-green-deep, #0f6023);
	border: none;
	border-radius: var(--radius-sm, 6px);
	cursor: pointer;
}
.office-menu-toggle span { width: 18px; height: 2px; background: #fff; display: block; }

@media (max-width: 860px) {
	.office-shell { flex-direction: column; }
	.office-menu-toggle { display: flex; }
	.office-sidebar {
		position: fixed;
		inset: 0 auto 0 0;
		width: 230px;
		transform: translateX(-100%);
		transition: transform 0.2s ease;
		z-index: 15;
	}
	.office-sidebar.is-open { transform: translateX(0); }
	.office-topbar { padding-left: 64px; }
	.office-content { padding: 20px; }
}
CSS;
}
