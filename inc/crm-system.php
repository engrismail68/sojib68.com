<?php
/**
 * Private "ক্লায়েন্ট ডেস্ক" (CRM / lead management) system.
 *
 * Entirely separate from the office (দলিল) system — own capability,
 * own role, own login, own URL base, own tables. It only reuses core
 * WordPress authentication (wp_signon, auth cookies, core password
 * hashing), the same pattern already used by the office system, so
 * staff accounts work the same way across both.
 *
 * Two kinds of routes:
 *  - /crm-login/ and /crm/... (authenticated staff area)
 *  - /crm/inquiry (public, no login — the lead-capture landing page,
 *    meant to be used as the destination link for Facebook ads, or
 *    embedded/linked from the public site, so no lead is missed).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'SOJIB68_CRM_CAP', 'access_sojib_crm' );
define( 'SOJIB68_CRM_BASE', 'crm' );              // /crm/...
define( 'SOJIB68_CRM_LOGIN_SLUG', 'crm-login' );  // /crm-login/

function sojib68_crm_ensure_role() {
	$admin = get_role( 'administrator' );
	if ( $admin && ! $admin->has_cap( SOJIB68_CRM_CAP ) ) {
		$admin->add_cap( SOJIB68_CRM_CAP );
	}

	if ( ! get_role( 'sojib_crm_staff' ) ) {
		add_role(
			'sojib_crm_staff',
			__( 'CRM Staff', 'sojib68' ),
			array(
				'read'            => true,
				SOJIB68_CRM_CAP   => true,
			)
		);
	}
}
add_action( 'after_switch_theme', 'sojib68_crm_ensure_role' );
add_action( 'init', 'sojib68_crm_ensure_role' );

function sojib68_crm_no_cache_no_index() {
	if ( ! defined( 'DONOTCACHEPAGE' ) ) {
		define( 'DONOTCACHEPAGE', true );
	}
	if ( function_exists( 'nocache_headers' ) ) {
		nocache_headers();
	}
	header( 'X-Robots-Tag: noindex, nofollow, noarchive', true );
}

function sojib68_crm_is_authorized() {
	return is_user_logged_in() && current_user_can( SOJIB68_CRM_CAP );
}

function sojib68_crm_rate_limit_key() {
	$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? $_SERVER['REMOTE_ADDR'] : 'unknown';
	return 'sojib68_crm_rl_' . md5( $ip );
}

function sojib68_crm_is_rate_limited() {
	$data = get_transient( sojib68_crm_rate_limit_key() );
	return is_array( $data ) && isset( $data['locked_until'] ) && $data['locked_until'] > time();
}

function sojib68_crm_register_failed_attempt() {
	$key  = sojib68_crm_rate_limit_key();
	$data = get_transient( $key );
	$data = is_array( $data ) ? $data : array(
		'count'        => 0,
		'locked_until' => 0,
	);

	$data['count']++;

	if ( $data['count'] >= 5 ) {
		$data['locked_until'] = time() + ( 15 * MINUTE_IN_SECONDS );
		$data['count']        = 0;
	}

	set_transient( $key, $data, 30 * MINUTE_IN_SECONDS );
}

function sojib68_crm_clear_rate_limit() {
	delete_transient( sojib68_crm_rate_limit_key() );
}

/**
 * Separate, much looser throttle for the public inquiry form (it has
 * no login, so this is the only thing standing between it and spam
 * submissions): 10 submissions per IP per 10 minutes.
 */
function sojib68_crm_inquiry_is_rate_limited() {
	$ip   = isset( $_SERVER['REMOTE_ADDR'] ) ? $_SERVER['REMOTE_ADDR'] : 'unknown';
	$key  = 'sojib68_crm_inq_' . md5( $ip );
	$count = (int) get_transient( $key );
	return $count >= 10;
}

function sojib68_crm_inquiry_register_submission() {
	$ip    = isset( $_SERVER['REMOTE_ADDR'] ) ? $_SERVER['REMOTE_ADDR'] : 'unknown';
	$key   = 'sojib68_crm_inq_' . md5( $ip );
	$count = (int) get_transient( $key );
	set_transient( $key, $count + 1, 10 * MINUTE_IN_SECONDS );
}

function sojib68_crm_router() {
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

	if ( SOJIB68_CRM_LOGIN_SLUG === $segments[0] ) {
		sojib68_crm_no_cache_no_index();
		sojib68_crm_handle_login_request();
		exit;
	}

	if ( SOJIB68_CRM_BASE === $segments[0] ) {
		sojib68_crm_no_cache_no_index();

		// Public lead-capture landing page — no login required.
		if ( isset( $segments[1] ) && 'inquiry' === $segments[1] ) {
			sojib68_crm_handle_public_inquiry();
			exit;
		}

		if ( ! sojib68_crm_is_authorized() ) {
			wp_safe_redirect( add_query_arg( 'redirect_to', urlencode( home_url( $_SERVER['REQUEST_URI'] ) ), home_url( '/' . SOJIB68_CRM_LOGIN_SLUG . '/' ) ) );
			exit;
		}

		if ( isset( $segments[1] ) && 'logout' === $segments[1] ) {
			wp_safe_redirect( wp_logout_url( home_url( '/' . SOJIB68_CRM_LOGIN_SLUG . '/' ) ) );
			exit;
		}

		if ( isset( $segments[1] ) && 'entry-new' === $segments[1] && 'POST' === $_SERVER['REQUEST_METHOD'] ) {
			sojib68_crm_handle_save_lead();
			exit;
		}

		if ( isset( $segments[1] ) && 'POST' === $_SERVER['REQUEST_METHOD'] && sojib68_crm_entry_page_type( $segments[1] ) ) {
			sojib68_crm_handle_save_work( sojib68_crm_entry_page_type( $segments[1] ) );
			exit;
		}

		if ( isset( $segments[1] ) && 'wa-template' === $segments[1] && 'POST' === $_SERVER['REQUEST_METHOD'] ) {
			sojib68_crm_handle_wa_template();
			exit;
		}

		if ( isset( $segments[1] ) && 'work-txn' === $segments[1] && 'POST' === $_SERVER['REQUEST_METHOD'] ) {
			sojib68_crm_handle_work_txn();
			exit;
		}

		if ( isset( $segments[1] ) && 'cash-add' === $segments[1] && 'POST' === $_SERVER['REQUEST_METHOD'] ) {
			sojib68_crm_handle_cash_add();
			exit;
		}

		$page_key = isset( $segments[1] ) && '' !== $segments[1] ? $segments[1] : 'dashboard';
		sojib68_crm_render_dashboard( $page_key );
		exit;
	}
}
add_action( 'init', 'sojib68_crm_router' );

/* ---------------------------------------------------------------------
 * Login
 * ------------------------------------------------------------------ */

function sojib68_crm_handle_login_request() {
	if ( sojib68_crm_is_authorized() ) {
		$redirect = isset( $_GET['redirect_to'] ) ? esc_url_raw( urldecode( wp_unslash( $_GET['redirect_to'] ) ) ) : home_url( '/' . SOJIB68_CRM_BASE . '/' );
		wp_safe_redirect( $redirect );
		return;
	}

	$error = '';

	if ( 'POST' === $_SERVER['REQUEST_METHOD'] ) {
		if ( sojib68_crm_is_rate_limited() ) {
			$error = __( 'অনেকবার ভুল চেষ্টা হয়েছে। কিছুক্ষণ পর আবার চেষ্টা করুন।', 'sojib68' );
		} elseif ( ! isset( $_POST['sojib68_crm_nonce'] ) || ! wp_verify_nonce( wp_unslash( $_POST['sojib68_crm_nonce'] ), 'sojib68_crm_login' ) ) {
			$error = __( 'সেশনের মেয়াদ শেষ হয়ে গেছে। পাতাটি আবার লোড করে চেষ্টা করুন।', 'sojib68' );
		} else {
			$username = isset( $_POST['username'] ) ? sanitize_user( wp_unslash( $_POST['username'] ) ) : '';
			$password = isset( $_POST['password'] ) ? (string) wp_unslash( $_POST['password'] ) : '';

			$user = wp_signon(
				array(
					'user_login'    => $username,
					'user_password' => $password,
					'remember'      => true,
				),
				is_ssl()
			);

			if ( is_wp_error( $user ) ) {
				sojib68_crm_register_failed_attempt();
				$error = __( 'ইউজারনেম বা পাসওয়ার্ড সঠিক নয়।', 'sojib68' );
			} elseif ( ! user_can( $user, SOJIB68_CRM_CAP ) ) {
				wp_logout();
				sojib68_crm_register_failed_attempt();
				$error = __( 'এই অ্যাকাউন্টের এই সিস্টেমে প্রবেশের অনুমতি নেই।', 'sojib68' );
			} else {
				sojib68_crm_clear_rate_limit();
				$redirect = isset( $_POST['redirect_to'] ) ? esc_url_raw( wp_unslash( $_POST['redirect_to'] ) ) : home_url( '/' . SOJIB68_CRM_BASE . '/' );
				wp_safe_redirect( $redirect );
				return;
			}
		}
	}

	sojib68_crm_render_login( $error );
}

function sojib68_crm_render_login( $error = '' ) {
	$redirect_to = isset( $_REQUEST['redirect_to'] ) ? esc_url_raw( wp_unslash( $_REQUEST['redirect_to'] ) ) : home_url( '/' . SOJIB68_CRM_BASE . '/' );
	$locked      = sojib68_crm_is_rate_limited();
	?>
	<!DOCTYPE html>
	<html <?php language_attributes(); ?>>
	<head>
		<meta charset="<?php bloginfo( 'charset' ); ?>">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<meta name="robots" content="noindex, nofollow">
		<title><?php esc_html_e( 'ক্লায়েন্ট ডেস্ক লগইন', 'sojib68' ); ?> — <?php bloginfo( 'name' ); ?></title>
		<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Figtree:wght@400;500;600;700&family=Hind+Siliguri:wght@400;500;600;700&display=swap">
		<link rel="stylesheet" href="<?php echo esc_url( get_stylesheet_uri() ); ?>">
		<link rel="stylesheet" href="<?php echo esc_url( get_theme_file_uri( 'assets/css/crm.css' ) ); ?>?v=<?php echo (int) @filemtime( get_theme_file_path( 'assets/css/crm.css' ) ); ?>">
	</head>
	<body class="crm-body">
		<div class="crm-login-wrap">
			<div class="crm-login-card">
				<img
					src="<?php echo esc_url( get_theme_file_uri( 'assets/images/logo.webp' ) ); ?>"
					alt="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>"
					class="crm-login-logo"
					width="170"
					height="47"
				>
				<h1><?php esc_html_e( 'ক্লায়েন্ট ডেস্ক', 'sojib68' ); ?></h1>
				<p class="crm-login-sub"><?php esc_html_e( 'লিড ও ক্লায়েন্ট ব্যবস্থাপনা — শুধুমাত্র অনুমোদিত ব্যবহারকারীদের জন্য।', 'sojib68' ); ?></p>

				<?php if ( $error ) : ?>
					<div class="crm-notice crm-notice--error"><?php echo esc_html( $error ); ?></div>
				<?php endif; ?>

				<form method="post" action="<?php echo esc_url( home_url( '/' . SOJIB68_CRM_LOGIN_SLUG . '/' ) ); ?>" autocomplete="off">
					<?php wp_nonce_field( 'sojib68_crm_login', 'sojib68_crm_nonce' ); ?>
					<input type="hidden" name="redirect_to" value="<?php echo esc_attr( $redirect_to ); ?>">

					<div class="crm-field">
						<label for="crm-username"><?php esc_html_e( 'ইউজারনেম', 'sojib68' ); ?></label>
						<input type="text" id="crm-username" name="username" required autocomplete="username" <?php echo $locked ? 'disabled' : ''; ?>>
					</div>

					<div class="crm-field">
						<label for="crm-password"><?php esc_html_e( 'পাসওয়ার্ড', 'sojib68' ); ?></label>
						<input type="password" id="crm-password" name="password" required autocomplete="current-password" <?php echo $locked ? 'disabled' : ''; ?>>
					</div>

					<button type="submit" class="btn btn-primary crm-login-submit" <?php echo $locked ? 'disabled' : ''; ?>>
						<?php esc_html_e( 'লগইন করুন', 'sojib68' ); ?>
					</button>
				</form>

				<p class="crm-login-forgot">
					<a href="<?php echo esc_url( wp_lostpassword_url( home_url( '/' . SOJIB68_CRM_LOGIN_SLUG . '/' ) ) ); ?>">
						<?php esc_html_e( 'পাসওয়ার্ড ভুলে গেছেন?', 'sojib68' ); ?>
					</a>
				</p>

				<p class="crm-login-back">
					<a href="<?php echo esc_url( home_url( '/' ) ); ?>">&larr; <?php esc_html_e( 'মূল ওয়েবসাইটে ফিরুন', 'sojib68' ); ?></a>
				</p>
			</div>
		</div>
	</body>
	</html>
	<?php
}

/* ---------------------------------------------------------------------
 * Public lead-capture ("inquiry") page — no login
 * ------------------------------------------------------------------ */

function sojib68_crm_handle_public_inquiry() {
	$error     = '';
	$submitted = false;

	if ( 'POST' === $_SERVER['REQUEST_METHOD'] ) {
		if ( sojib68_crm_inquiry_is_rate_limited() ) {
			$error = __( 'অনেকবার জমা দেওয়া হয়েছে। কিছুক্ষণ পর আবার চেষ্টা করুন।', 'sojib68' );
		} elseif ( ! isset( $_POST['sojib68_crm_inquiry_nonce'] ) || ! wp_verify_nonce( wp_unslash( $_POST['sojib68_crm_inquiry_nonce'] ), 'sojib68_crm_inquiry' ) ) {
			$error = __( 'পাতাটি পুরনো হয়ে গেছে। আবার লোড করে চেষ্টা করুন।', 'sojib68' );
		} elseif ( ! empty( $_POST['website'] ) ) {
			// Honeypot field — bots fill every input, real visitors never see it.
			$submitted = true; // Silently "succeed" without storing anything.
		} else {
			$name  = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
			$phone = isset( $_POST['phone'] ) ? sanitize_text_field( wp_unslash( $_POST['phone'] ) ) : '';

			if ( '' === $name && '' === $phone ) {
				$error = __( 'নাম অথবা মোবাইল নম্বর অন্তত একটি দিন।', 'sojib68' );
			} else {
				$source = isset( $_POST['source'] ) ? sanitize_key( wp_unslash( $_POST['source'] ) ) : 'facebook_internet';
				$id     = sojib68_crm_lead_save(
					array(
						'name'   => $name,
						'phone'  => $phone,
						'email'  => isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '',
						'source' => $source,
						'status' => 'new',
						'notes'  => isset( $_POST['message'] ) ? sanitize_textarea_field( wp_unslash( $_POST['message'] ) ) : '',
					)
				);

				if ( is_wp_error( $id ) ) {
					$error = $id->get_error_message();
				} else {
					sojib68_crm_inquiry_register_submission();
					$submitted = true;
				}
			}
		}
	}

	$default_source = isset( $_GET['src'] ) ? sanitize_key( wp_unslash( $_GET['src'] ) ) : 'facebook_internet';
	$sources        = sojib68_crm_lead_sources();
	if ( ! isset( $sources[ $default_source ] ) ) {
		$default_source = 'facebook_internet';
	}
	?>
	<!DOCTYPE html>
	<html <?php language_attributes(); ?>>
	<head>
		<meta charset="<?php bloginfo( 'charset' ); ?>">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<meta name="robots" content="noindex, nofollow">
		<title><?php esc_html_e( 'যোগাযোগ করুন', 'sojib68' ); ?> — <?php bloginfo( 'name' ); ?></title>
		<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Figtree:wght@400;500;600;700&family=Hind+Siliguri:wght@400;500;600;700&display=swap">
		<link rel="stylesheet" href="<?php echo esc_url( get_stylesheet_uri() ); ?>">
		<link rel="stylesheet" href="<?php echo esc_url( get_theme_file_uri( 'assets/css/crm.css' ) ); ?>?v=<?php echo (int) @filemtime( get_theme_file_path( 'assets/css/crm.css' ) ); ?>">
	</head>
	<body class="crm-body">
		<div class="crm-login-wrap">
			<div class="crm-login-card">
				<img
					src="<?php echo esc_url( get_theme_file_uri( 'assets/images/logo.webp' ) ); ?>"
					alt="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>"
					class="crm-login-logo"
					width="170"
					height="47"
				>

				<?php if ( $submitted ) : ?>
					<h1><?php esc_html_e( 'ধন্যবাদ!', 'sojib68' ); ?></h1>
					<p class="crm-login-sub"><?php esc_html_e( 'আপনার তথ্য পেয়েছি। শীঘ্রই আমরা আপনার সাথে যোগাযোগ করবো।', 'sojib68' ); ?></p>
				<?php else : ?>
					<h1><?php esc_html_e( 'যোগাযোগ করুন', 'sojib68' ); ?></h1>
					<p class="crm-login-sub"><?php esc_html_e( 'জমি ও দলিল বিষয়ে পরামর্শের জন্য আপনার তথ্য দিন — আমরা ফোন করবো।', 'sojib68' ); ?></p>

					<?php if ( $error ) : ?>
						<div class="crm-notice crm-notice--error"><?php echo esc_html( $error ); ?></div>
					<?php endif; ?>

					<form method="post" action="<?php echo esc_url( home_url( '/' . SOJIB68_CRM_BASE . '/inquiry' ) . ( isset( $_GET['src'] ) ? '?src=' . rawurlencode( $default_source ) : '' ) ); ?>" autocomplete="off">
						<?php wp_nonce_field( 'sojib68_crm_inquiry', 'sojib68_crm_inquiry_nonce' ); ?>
						<input type="hidden" name="source" value="<?php echo esc_attr( $default_source ); ?>">
						<p class="crm-honeypot" aria-hidden="true"><label>Website <input type="text" name="website" tabindex="-1" autocomplete="off"></label></p>

						<div class="crm-field">
							<label for="inq-name"><?php esc_html_e( 'নাম', 'sojib68' ); ?></label>
							<input type="text" id="inq-name" name="name" required>
						</div>
						<div class="crm-field">
							<label for="inq-phone"><?php esc_html_e( 'মোবাইল নম্বর', 'sojib68' ); ?></label>
							<input type="text" id="inq-phone" name="phone" required>
						</div>
						<div class="crm-field">
							<label for="inq-message"><?php esc_html_e( 'প্রয়োজন কী? (ঐচ্ছিক)', 'sojib68' ); ?></label>
							<textarea id="inq-message" name="message" rows="3"></textarea>
						</div>

						<button type="submit" class="btn btn-primary crm-login-submit"><?php esc_html_e( 'পাঠিয়ে দিন', 'sojib68' ); ?></button>
					</form>
				<?php endif; ?>
			</div>
		</div>
	</body>
	</html>
	<?php
}

/* ---------------------------------------------------------------------
 * Authenticated: save lead (entry-new POST)
 * ------------------------------------------------------------------ */

function sojib68_crm_handle_save_lead() {
	if ( ! isset( $_POST['sojib68_crm_nonce'] ) || ! wp_verify_nonce( wp_unslash( $_POST['sojib68_crm_nonce'] ), 'sojib68_crm_save_lead' ) ) {
		wp_safe_redirect( home_url( '/' . SOJIB68_CRM_BASE . '/entry-new' ) );
		return;
	}

	$id = isset( $_POST['id'] ) ? (int) $_POST['id'] : 0;

	// Non-admin staff may only edit leads already assigned to them.
	if ( $id && ! sojib68_crm_is_admin() ) {
		$existing = sojib68_crm_lead_get( $id );
		if ( ! $existing || (int) $existing['assigned_to'] !== get_current_user_id() ) {
			wp_safe_redirect( home_url( '/' . SOJIB68_CRM_BASE . '/leads' ) );
			return;
		}
	}

	$saved_id = sojib68_crm_lead_save(
		array(
			'name'             => isset( $_POST['name'] ) ? wp_unslash( $_POST['name'] ) : '',
			'phone'            => isset( $_POST['phone'] ) ? wp_unslash( $_POST['phone'] ) : '',
			'email'            => isset( $_POST['email'] ) ? wp_unslash( $_POST['email'] ) : '',
			'address'          => isset( $_POST['address'] ) ? wp_unslash( $_POST['address'] ) : '',
			'source'           => isset( $_POST['source'] ) ? wp_unslash( $_POST['source'] ) : '',
			'referred_by'      => isset( $_POST['referred_by'] ) ? wp_unslash( $_POST['referred_by'] ) : '',
			'service_type'     => isset( $_POST['service_type'] ) ? wp_unslash( $_POST['service_type'] ) : '',
			'details'          => isset( $_POST['details'] ) ? wp_unslash( $_POST['details'] ) : '',
			'notes'            => isset( $_POST['notes'] ) ? wp_unslash( $_POST['notes'] ) : '',
			'assigned_to'      => isset( $_POST['assigned_to'] ) ? wp_unslash( $_POST['assigned_to'] ) : '',
			'status'           => isset( $_POST['status'] ) ? wp_unslash( $_POST['status'] ) : '',
			'next_followup_at' => isset( $_POST['next_followup_at'] ) ? wp_unslash( $_POST['next_followup_at'] ) : '',
		),
		$id
	);

	if ( is_wp_error( $saved_id ) ) {
		wp_safe_redirect( home_url( '/' . SOJIB68_CRM_BASE . '/entry-new' . ( $id ? '?id=' . $id : '' ) ) );
		return;
	}

	wp_safe_redirect( home_url( '/' . SOJIB68_CRM_BASE . '/entry-new?id=' . $saved_id ) );
}

function sojib68_crm_work_draft_key() {
	return 'sojib68_crm_work_draft_' . get_current_user_id();
}

/**
 * On a failed save, keep what was typed (and why it failed) for a few
 * minutes so the form can show the reason and refill itself instead of
 * silently losing the input.
 */
function sojib68_crm_work_save_failed( $back, $id, $message ) {
	$draft = array();
	foreach ( $_POST as $k => $val ) {
		if ( is_string( $val ) && 0 !== strpos( $k, 'sojib68_crm_' ) && '_wp_http_referer' !== $k ) {
			$draft[ sanitize_key( $k ) ] = sanitize_textarea_field( wp_unslash( $val ) );
		}
	}
	set_transient( sojib68_crm_work_draft_key(), array( 'message' => $message, 'fields' => $draft ), 10 * MINUTE_IN_SECONDS );

	$url = $id ? add_query_arg( 'id', $id, $back ) : $back;
	wp_safe_redirect( add_query_arg( 'crm_notice', 'error', $url ) );
}

function sojib68_crm_handle_save_work( $type ) {
	$back = home_url( '/' . SOJIB68_CRM_BASE . '/' . $type . '-entry' );
	$id   = isset( $_POST['id'] ) ? (int) $_POST['id'] : 0;

	if ( ! isset( $_POST['sojib68_crm_work_nonce'] ) || ! wp_verify_nonce( wp_unslash( $_POST['sojib68_crm_work_nonce'] ), 'sojib68_crm_save_work' ) ) {
		sojib68_crm_work_save_failed( $back, $id, __( 'পাতাটি অনেকক্ষণ খোলা ছিল, তাই নিরাপত্তার কারণে সংরক্ষণ হয়নি। আপনার লেখা ফিরিয়ে দেওয়া হয়েছে — আবার "সংরক্ষণ করুন" চাপুন।', 'sojib68' ) );
		return;
	}

	if ( $id ) {
		$existing = sojib68_crm_work_get( $id );
		if ( ! $existing || $existing['work_type'] !== $type || ( ! sojib68_crm_is_admin() && (int) $existing['assigned_to'] !== get_current_user_id() ) ) {
			sojib68_crm_work_save_failed( $back, 0, __( 'এই কাজটি সম্পাদনার অনুমতি নেই বা কাজটি পাওয়া যায়নি।', 'sojib68' ) );
			return;
		}
	}

	$fields = array( 'customer_name', 'customer_phone', 'received_date', 'source', 'referred_by', 'assigned_to', 'status', 'stage', 'cancel_reason', 'pending_reason', 'next_action', 'next_action_date', 'docs_needed', 'docs_received', 'docs_pending', 'file_location', 'delivery_status', 'delivery_date', 'delivery_method', 'delivery_recipient', 'application_date', 'application_no', 'nothi_no', 'application_mobile' );
	// Only fields this category's form actually shows are passed on; the rest keep their stored values.
	$data = array();
	foreach ( $fields as $f ) {
		if ( isset( $_POST[ $f ] ) ) {
			$data[ $f ] = wp_unslash( $_POST[ $f ] );
		}
	}

	// Money fields are finance-permission only; for everyone else the key is left out so a staff save never touches it.
	if ( sojib68_crm_can_view_finance() && isset( $_POST['agreed_amount'] ) ) {
		$data['agreed_amount'] = wp_unslash( $_POST['agreed_amount'] );
	}

	$saved = sojib68_crm_work_save( $data, $id, $type );
	if ( is_wp_error( $saved ) ) {
		sojib68_crm_work_save_failed( $back, $id, $saved->get_error_message() );
		return;
	}

	// "আদায়" / "খরচ" typed on the form are new amounts, recorded as dated ledger entries
	// (so the category cash totals and the per-work history stay the single source of truth).
	if ( sojib68_crm_can_view_finance() ) {
		$work  = sojib68_crm_work_get( $saved );
		$moves = array(
			'collected_add' => array( 'in', $work ? $work['customer_name'] : '', __( 'এন্ট্রি ফর্ম থেকে আদায়', 'sojib68' ) ),
			'expense_add'   => array( 'out', '', __( 'ফাইল প্রসেসিং ও অন্যান্য খরচ', 'sojib68' ) ),
		);
		foreach ( $moves as $field => $move ) {
			$amount = isset( $_POST[ $field ] ) ? sojib68_crm_parse_amount( wp_unslash( $_POST[ $field ] ) ) : null;
			if ( $amount && $amount > 0 ) {
				$entry = sojib68_crm_cash_add( array(
					'work_id'   => $saved,
					'direction' => $move[0],
					'amount'    => $amount,
					'party'     => $move[1],
					'note'      => $move[2],
				) );
				if ( is_wp_error( $entry ) ) {
					sojib68_crm_work_save_failed( $back, $saved, __( 'কাজের তথ্য সংরক্ষণ হয়েছে, কিন্তু টাকার এন্ট্রি হয়নি:', 'sojib68' ) . ' ' . $entry->get_error_message() );
					return;
				}
			}
		}
	}

	delete_transient( sojib68_crm_work_draft_key() );
	wp_safe_redirect( add_query_arg( array( 'id' => $saved, 'crm_notice' => 'saved' ), $back ) );
}

function sojib68_crm_handle_wa_template() {
	$type    = isset( $_POST['type'] ) ? sanitize_key( wp_unslash( $_POST['type'] ) ) : '';
	$work_id = isset( $_POST['work_id'] ) ? (int) $_POST['work_id'] : 0;
	$back    = home_url( '/' . SOJIB68_CRM_BASE . '/' . $type . '-entry' );
	if ( ! sojib68_crm_entry_page_type( $type . '-entry' ) ) {
		$back = home_url( '/' . SOJIB68_CRM_BASE . '/' );
	}

	if ( ! sojib68_crm_is_admin() || ! isset( sojib68_crm_service_types()[ $type ] )
		|| ! isset( $_POST['sojib68_crm_wa_nonce'] ) || ! wp_verify_nonce( wp_unslash( $_POST['sojib68_crm_wa_nonce'] ), 'sojib68_crm_wa_template' ) ) {
		wp_safe_redirect( $back );
		return;
	}

	sojib68_crm_wa_template_save( $type, ! empty( $_POST['reset'] ) ? '' : wp_unslash( isset( $_POST['template'] ) ? $_POST['template'] : '' ) );

	wp_safe_redirect( add_query_arg( array_filter( array( 'id' => $work_id, 'crm_notice' => 'template' ) ), $back ) );
}

function sojib68_crm_handle_work_txn() {
	if ( ! sojib68_crm_can_view_finance() ) {
		wp_safe_redirect( home_url( '/' . SOJIB68_CRM_BASE . '/' ) );
		return;
	}
	if ( ! isset( $_POST['sojib68_crm_txn_nonce'] ) || ! wp_verify_nonce( wp_unslash( $_POST['sojib68_crm_txn_nonce'] ), 'sojib68_crm_work_txn' ) ) {
		wp_safe_redirect( home_url( '/' . SOJIB68_CRM_BASE . '/' ) );
		return;
	}

	$work_id = isset( $_POST['work_id'] ) ? (int) $_POST['work_id'] : 0;
	$work    = $work_id ? sojib68_crm_work_get( $work_id ) : null;
	if ( ! $work ) {
		wp_safe_redirect( home_url( '/' . SOJIB68_CRM_BASE . '/' ) );
		return;
	}

	sojib68_crm_cash_add(
		array(
			'work_id'   => $work_id,
			'category'  => isset( $_POST['category'] ) ? wp_unslash( $_POST['category'] ) : '',
			'direction' => isset( $_POST['direction'] ) ? wp_unslash( $_POST['direction'] ) : '',
			'amount'    => isset( $_POST['amount'] ) ? wp_unslash( $_POST['amount'] ) : 0,
			'txn_date'  => isset( $_POST['txn_date'] ) ? wp_unslash( $_POST['txn_date'] ) : '',
			'party'     => isset( $_POST['party'] ) ? wp_unslash( $_POST['party'] ) : '',
			'note'      => isset( $_POST['note'] ) ? wp_unslash( $_POST['note'] ) : '',
		)
	);

	wp_safe_redirect( add_query_arg( 'id', $work_id, home_url( '/' . SOJIB68_CRM_BASE . '/' . $work['work_type'] . '-entry' ) ) );
}

/* ---------------------------------------------------------------------
 * Authenticated: add cash ledger entry (admin/finance only)
 * ------------------------------------------------------------------ */

function sojib68_crm_handle_cash_add() {
	if ( ! sojib68_crm_can_view_finance() ) {
		wp_safe_redirect( home_url( '/' . SOJIB68_CRM_BASE . '/' ) );
		return;
	}

	if ( ! isset( $_POST['sojib68_crm_cash_nonce'] ) || ! wp_verify_nonce( wp_unslash( $_POST['sojib68_crm_cash_nonce'] ), 'sojib68_crm_cash_add' ) ) {
		wp_safe_redirect( home_url( '/' . SOJIB68_CRM_BASE . '/' ) );
		return;
	}

	sojib68_crm_cash_add(
		array(
			'category'  => isset( $_POST['category'] ) ? wp_unslash( $_POST['category'] ) : '',
			'direction' => isset( $_POST['direction'] ) ? wp_unslash( $_POST['direction'] ) : '',
			'amount'    => isset( $_POST['amount'] ) ? wp_unslash( $_POST['amount'] ) : 0,
			'note'      => isset( $_POST['note'] ) ? wp_unslash( $_POST['note'] ) : '',
			'txn_date'  => isset( $_POST['txn_date'] ) ? wp_unslash( $_POST['txn_date'] ) : '',
		)
	);

	wp_safe_redirect( home_url( '/' . SOJIB68_CRM_BASE . '/' ) );
}

/* ---------------------------------------------------------------------
 * Dashboard shell
 * ------------------------------------------------------------------ */

function sojib68_crm_menu_items() {
	return array(
		'dashboard' => __( 'ড্যাশবোর্ড', 'sojib68' ),
		'notifications' => __( 'নোটিফিকেশন', 'sojib68' ),
		'leads'     => __( 'সব লিড', 'sojib68' ),
		'works'     => __( 'সব কাজ', 'sojib68' ),
		'entry-new' => __( 'নতুন লিড যোগ করুন', 'sojib68' ),
		'namjari-entry' => __( 'নামজারি এন্ট্রি', 'sojib68' ),
	);
}

/**
 * Returns the service-type key for a "<type>-entry" page key, or '' if it
 * isn't one. Every service type has an entry/edit page (so any work can be
 * edited from "সব কাজ"); only some are also listed in the sidebar menu.
 */
function sojib68_crm_entry_page_type( $page_key ) {
	if ( '-entry' !== substr( (string) $page_key, -6 ) ) {
		return '';
	}
	$type = substr( $page_key, 0, -6 );
	return isset( sojib68_crm_service_types()[ $type ] ) ? $type : '';
}

function sojib68_crm_render_dashboard( $page_key ) {
	$menu = sojib68_crm_menu_items();
	$entry_type = sojib68_crm_entry_page_type( $page_key );
	if ( ! isset( $menu[ $page_key ] ) && ! $entry_type ) {
		$page_key = 'dashboard';
	}
	// Editing a work of a category that has no menu item of its own: keep "সব কাজ" highlighted.
	$active_menu = isset( $menu[ $page_key ] ) ? $page_key : 'works';

	$user       = wp_get_current_user();
	$idle_count = count( sojib68_crm_idle_works( sojib68_crm_current_user_scope() ) );
	$icons      = array(
		'dashboard' => 'grid',
		'notifications' => 'bell',
		'leads'     => 'users',
		'works'     => 'briefcase',
		'entry-new' => 'plus',
		'namjari-entry' => 'briefcase',
	);
	$subs  = array(
		'dashboard' => __( 'লিড, কাজ ও ক্লায়েন্টের এক নজরে সারসংক্ষেপ', 'sojib68' ),
		'notifications' => sprintf( /* translators: %s: days */ __( 'যে ফাইল %s দিনের বেশি একই ধাপে পড়ে আছে, সেগুলো এখানে আসে', 'sojib68' ), number_format_i18n( SOJIB68_CRM_IDLE_WARN_DAYS ) ),
		'leads'     => __( 'ফেসবুক, হোয়াটসঅ্যাপ, ফোন ও সরাসরি — সব লিড এক জায়গায়', 'sojib68' ),
		'works'     => __( 'নিশ্চিত হওয়া কাজের তালিকা ও বর্তমান অবস্থা', 'sojib68' ),
		'entry-new' => __( 'সরাসরি/ফোনে যোগাযোগ হওয়া ক্লায়েন্টের তথ্য এখানে যোগ করুন', 'sojib68' ),
		'namjari-entry' => __( 'নামজারির কাজের তথ্য এন্ট্রি ও তালিকা', 'sojib68' ),
	);

	$page_title = isset( $menu[ $page_key ] ) ? $menu[ $page_key ] : sprintf( /* translators: %s: service */ __( '%s এন্ট্রি', 'sojib68' ), sojib68_crm_service_types()[ $entry_type ] );
	if ( 'entry-new' === $page_key && ! empty( $_GET['id'] ) ) {
		$page_title = __( 'লিড সম্পাদনা', 'sojib68' );
	} elseif ( $entry_type && ! empty( $_GET['id'] ) ) {
		$page_title = sprintf( /* translators: %s: service */ __( '%s — কাজ সম্পাদনা', 'sojib68' ), sojib68_crm_service_types()[ $entry_type ] );
	}
	?>
	<!DOCTYPE html>
	<html <?php language_attributes(); ?>>
	<head>
		<meta charset="<?php bloginfo( 'charset' ); ?>">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<meta name="robots" content="noindex, nofollow">
		<title><?php echo esc_html( $page_title ); ?> — <?php esc_html_e( 'ক্লায়েন্ট ডেস্ক', 'sojib68' ); ?></title>
		<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Figtree:wght@400;500;600;700&family=Hind+Siliguri:wght@400;500;600;700&display=swap">
		<link rel="stylesheet" href="<?php echo esc_url( get_stylesheet_uri() ); ?>">
		<link rel="stylesheet" href="<?php echo esc_url( get_theme_file_uri( 'assets/css/crm.css' ) ); ?>?v=<?php echo (int) @filemtime( get_theme_file_path( 'assets/css/crm.css' ) ); ?>">
	</head>
	<body class="crm-body crm-dashboard-body">
		<div class="crm-shell">
			<button type="button" class="crm-menu-toggle" id="crm-menu-toggle" aria-expanded="false" aria-controls="crm-sidebar">
				<span></span><span></span><span></span>
			</button>

			<aside class="crm-sidebar" id="crm-sidebar">
				<img
					src="<?php echo esc_url( get_theme_file_uri( 'assets/images/logo.webp' ) ); ?>"
					alt="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>"
					class="crm-sidebar-logo"
					width="150"
					height="41"
				>
				<p class="crm-sidebar-tag"><?php esc_html_e( 'ক্লায়েন্ট ডেস্ক', 'sojib68' ); ?></p>
				<nav aria-label="<?php esc_attr_e( 'ক্লায়েন্ট ডেস্ক মেনু', 'sojib68' ); ?>">
					<ul>
						<?php foreach ( $menu as $key => $label ) : ?>
							<li>
								<a
									href="<?php echo esc_url( home_url( '/' . SOJIB68_CRM_BASE . '/' . ( 'dashboard' === $key ? '' : $key ) ) ); ?>"
									class="<?php echo $active_menu === $key ? 'is-active' : ''; ?>"
								>
									<?php echo sojib68_crm_icon( $icons[ $key ] ?? 'grid', 19 ); // phpcs:ignore ?>
									<span><?php echo esc_html( $label ); ?></span>
									<?php if ( 'notifications' === $key && $idle_count ) : ?>
										<span class="crm-nav-badge" title="<?php esc_attr_e( 'আটকে থাকা ফাইল', 'sojib68' ); ?>"><?php echo esc_html( number_format_i18n( $idle_count ) ); ?></span>
									<?php endif; ?>
								</a>
							</li>
						<?php endforeach; ?>
					</ul>
				</nav>
			</aside>

			<div class="crm-main">
				<header class="crm-topbar">
					<a href="<?php echo esc_url( home_url( '/' . SOJIB68_CRM_BASE . '/notifications' ) ); ?>" class="crm-bell<?php echo $idle_count ? ' has-alerts' : ''; ?>" title="<?php esc_attr_e( 'নোটিফিকেশন', 'sojib68' ); ?>" aria-label="<?php echo esc_attr( sprintf( /* translators: %s: count */ __( 'নোটিফিকেশন: %s টি', 'sojib68' ), number_format_i18n( $idle_count ) ) ); ?>">
						<?php echo sojib68_crm_icon( 'bell', 20 ); // phpcs:ignore ?>
						<?php if ( $idle_count ) : ?><span class="crm-bell__count"><?php echo esc_html( number_format_i18n( $idle_count ) ); ?></span><?php endif; ?>
					</a>
					<span class="crm-topbar-user">
						<?php echo esc_html( sprintf( /* translators: %s: display name */ __( '%s হিসেবে লগইন করা আছেন', 'sojib68' ), $user->display_name ) ); ?>
					</span>
					<a href="<?php echo esc_url( home_url( '/' . SOJIB68_CRM_BASE . '/logout' ) ); ?>" class="btn btn-outline btn-sm">
						<?php echo sojib68_crm_icon( 'logout', 15 ); // phpcs:ignore ?> <?php esc_html_e( 'লগআউট', 'sojib68' ); ?>
					</a>
				</header>

				<main class="crm-content">
					<div class="crm-pagehead">
						<div>
							<h1><?php echo esc_html( $page_title ); ?></h1>
							<?php if ( ! empty( $subs[ $page_key ] ) ) : ?><p><?php echo esc_html( $subs[ $page_key ] ); ?></p><?php endif; ?>
						</div>
						<?php if ( 'entry-new' !== $page_key ) : ?>
							<a href="<?php echo esc_url( home_url( '/' . SOJIB68_CRM_BASE . '/entry-new' ) ); ?>" class="btn btn-primary btn-sm"><?php echo sojib68_crm_icon( 'plus', 16 ); // phpcs:ignore ?> <?php esc_html_e( 'নতুন লিড', 'sojib68' ); ?></a>
						<?php endif; ?>
					</div>
					<div class="crm-flash" id="crm-flash" hidden></div>
					<?php echo sojib68_crm_render_section( $page_key ); // phpcs:ignore ?>
				</main>
			</div>
		</div>

		<script>
		var sojib68Crm = {
			ajaxUrl: <?php echo wp_json_encode( admin_url( 'admin-ajax.php' ) ); ?>,
			nonce: <?php echo wp_json_encode( wp_create_nonce( 'sojib68_crm_action' ) ); ?>
		};
		</script>
		<script src="<?php echo esc_url( get_theme_file_uri( 'assets/js/crm.js' ) ); ?>?v=<?php echo (int) @filemtime( get_theme_file_path( 'assets/js/crm.js' ) ); ?>"></script>
	</body>
	</html>
	<?php
}

function sojib68_crm_render_section( $page_key ) {
	switch ( $page_key ) {
		case 'notifications':
			ob_start();
			sojib68_crm_render_idle_alert( sojib68_crm_current_user_scope(), true );
			return ob_get_clean();

		case 'dashboard':
			ob_start();
			sojib68_crm_render_dashboard_home();
			return ob_get_clean();

		case 'leads':
			ob_start();
			sojib68_crm_render_leads_table();
			return ob_get_clean();

		case 'works':
			ob_start();
			sojib68_crm_render_works_list();
			return ob_get_clean();

		case 'entry-new':
			ob_start();
			sojib68_crm_render_entry_form();
			return ob_get_clean();

		default:
			$type = sojib68_crm_entry_page_type( $page_key );
			if ( $type ) {
				ob_start();
				sojib68_crm_render_category_entry( $type );
				return ob_get_clean();
			}
			return '';
	}
}
