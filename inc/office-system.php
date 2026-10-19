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
/**
 * Empties expired recycle-bin entries at most once an hour — cheap
 * enough to check on every office page load, never heavy enough to
 * run the actual sweep more than that.
 */
function sojib68_office_maybe_purge_trash() {
	if ( get_transient( 'sojib68_trash_swept' ) ) {
		return;
	}
	set_transient( 'sojib68_trash_swept', 1, HOUR_IN_SECONDS );
	sojib68_deeds_purge_expired();
}

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

		sojib68_office_maybe_purge_trash();

		if ( isset( $segments[1] ) && 'logout' === $segments[1] ) {
			sojib68_office_handle_logout();
			exit;
		}

		if ( isset( $segments[1] ) && 'print' === $segments[1] ) {
			sojib68_office_render_print( isset( $segments[2] ) ? (int) $segments[2] : 0 );
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
		<link rel="stylesheet" href="<?php echo esc_url( get_theme_file_uri( 'assets/css/office.css' ) ); ?>?v=<?php echo (int) @filemtime( get_theme_file_path( 'assets/css/office.css' ) ); ?>">
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
		'all-deeds'  => __( 'সকল দলিল', 'sojib68' ),
		'dues'       => __( 'বকেয়া আদায়', 'sojib68' ),
		'recycle-bin' => __( 'রিসাইকেল বিন', 'sojib68' ),
	);
}

function sojib68_office_render_dashboard( $page_key ) {
	$menu = sojib68_office_menu_items();
	if ( ! isset( $menu[ $page_key ] ) ) {
		$page_key = 'dashboard';
	}

	$user  = wp_get_current_user();
	$icons = array( 'dashboard' => 'grid', 'entry-new' => 'plus', 'all-deeds' => 'list', 'dues' => 'clock', 'recycle-bin' => 'trash' );
	$subs  = array(
		'dashboard'    => __( 'আপনার সব দলিল ও হিসাবের এক নজরে সারসংক্ষেপ', 'sojib68' ),
		'entry-new'    => __( 'যতটুকু তথ্য জানেন ততটুকুই লিখুন — সব ঘর ঐচ্ছিক', 'sojib68' ),
		'all-deeds'    => __( 'নাম, মোবাইল, দলিল নং বা সাল দিয়ে দ্রুত খুঁজুন', 'sojib68' ),
		'dues'         => __( 'কার কাছ থেকে কত টাকা পাওনা — ফোন করুন, টাকা আদায় লিখুন', 'sojib68' ),
		'recycle-bin'  => __( 'মুছে ফেলা দলিল ৭ দিন পর্যন্ত এখানে থাকে, চাইলে ফিরিয়ে আনুন', 'sojib68' ),
	);

	$page_title = $menu[ $page_key ];
	if ( 'entry-new' === $page_key && ! empty( $_GET['id'] ) ) {
		$page_title = __( 'দলিল সম্পাদনা', 'sojib68' );
	}
	?>
	<!DOCTYPE html>
	<html <?php language_attributes(); ?>>
	<head>
		<meta charset="<?php bloginfo( 'charset' ); ?>">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<meta name="robots" content="noindex, nofollow">
		<title><?php echo esc_html( $page_title ); ?> — <?php esc_html_e( 'অফিস', 'sojib68' ); ?></title>
		<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Figtree:wght@400;500;600;700&family=Hind+Siliguri:wght@400;500;600;700&display=swap">
		<link rel="stylesheet" href="<?php echo esc_url( get_stylesheet_uri() ); ?>">
		<link rel="stylesheet" href="<?php echo esc_url( get_theme_file_uri( 'assets/css/office.css' ) ); ?>?v=<?php echo (int) @filemtime( get_theme_file_path( 'assets/css/office.css' ) ); ?>">
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
									<?php echo sojib68_office_icon( $icons[ $key ] ?? 'file', 19 ); // phpcs:ignore ?>
									<span><?php echo esc_html( $label ); ?></span>
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
					<div class="office-pagehead">
						<div>
							<h1><?php echo esc_html( $page_title ); ?></h1>
							<?php if ( ! empty( $subs[ $page_key ] ) ) : ?><p><?php echo esc_html( $subs[ $page_key ] ); ?></p><?php endif; ?>
						</div>
						<?php if ( 'entry-new' === $page_key && ! empty( $_GET['id'] ) ) : ?>
							<span class="office-pagehead__actions">
								<a href="<?php echo esc_url( home_url( '/' . SOJIB68_OFFICE_BASE . '/print/' . (int) $_GET['id'] ) ); ?>" target="_blank" rel="noopener" class="btn btn-outline btn-sm"><?php echo sojib68_office_icon( 'file', 16 ); // phpcs:ignore ?> <?php esc_html_e( 'প্রিন্ট সারসংক্ষেপ', 'sojib68' ); ?></a>
								<button type="button" class="btn btn-outline btn-sm btn-danger-outline" data-delete-deed="<?php echo (int) $_GET['id']; ?>" data-after="<?php echo esc_url( home_url( '/' . SOJIB68_OFFICE_BASE . '/all-deeds' ) ); ?>"><?php echo sojib68_office_icon( 'trash', 16 ); // phpcs:ignore ?> <?php esc_html_e( 'মুছুন', 'sojib68' ); ?></button>
							</span>
						<?php elseif ( 'entry-new' !== $page_key ) : ?>
							<a href="<?php echo esc_url( home_url( '/' . SOJIB68_OFFICE_BASE . '/entry-new' ) ); ?>" class="btn btn-primary btn-sm"><?php echo sojib68_office_icon( 'plus', 16 ); // phpcs:ignore ?> <?php esc_html_e( 'নতুন দলিল', 'sojib68' ); ?></a>
						<?php endif; ?>
					</div>
					<div class="dues-flash" id="office-flash" hidden></div>
					<?php echo sojib68_office_render_section( $page_key ); // phpcs:ignore ?>
				</main>
			</div>
		</div>

		<script src="<?php echo esc_url( get_theme_file_uri( 'assets/js/office-inputs.js' ) ); ?>?v=<?php echo (int) @filemtime( get_theme_file_path( 'assets/js/office-inputs.js' ) ); ?>"></script>
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
			ob_start();
			sojib68_office_render_dashboard_home();
			return ob_get_clean();

		case 'entry-new':
			ob_start();
			sojib68_office_render_entry_form();
			return ob_get_clean();

		case 'all-deeds':
			ob_start();
			sojib68_office_render_all_deeds();
			return ob_get_clean();

		case 'dues':
			ob_start();
			sojib68_office_render_dues();
			return ob_get_clean();

		case 'recycle-bin':
			ob_start();
			sojib68_office_render_recycle_bin();
			return ob_get_clean();

		default:
			return '';
	}
}
