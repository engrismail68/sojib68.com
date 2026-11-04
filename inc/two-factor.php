<?php
/**
 * Two-factor login (TOTP, RFC 6238) — works with Google Authenticator,
 * Microsoft Authenticator, Authy, 1Password etc.
 *
 * Covers every password login on the site: /wp-login.php, the office login
 * and the CRM login (all of them end in wp_signon → the "authenticate"
 * filter below). REST / XML-RPC (Application Passwords) are not affected.
 *
 * Manage it at wp-admin → Users → "টু-স্টেপ ভেরিফিকেশন".
 *
 * Emergency off-switch (lost phone AND backup codes): add this line to
 * wp-config.php, log in, fix it, then remove the line:
 *   define( 'SOJIB68_2FA_DISABLED', true );
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const SOJIB68_2FA_FIELD   = 'sojib68_2fa_code';
const SOJIB68_2FA_PAGE    = 'sojib68-2fa';
const SOJIB68_2FA_MAXFAIL = 5;
const SOJIB68_2FA_LOCK    = 600; // seconds

/* ---------------------------------------------------------------------
 * Crypto helpers
 * ------------------------------------------------------------------ */

function sojib68_2fa_b32_encode( $bin ) {
	$alpha = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
	$bits  = '';
	foreach ( str_split( $bin ) as $ch ) {
		$bits .= str_pad( decbin( ord( $ch ) ), 8, '0', STR_PAD_LEFT );
	}
	$out = '';
	foreach ( str_split( $bits, 5 ) as $chunk ) {
		$out .= $alpha[ bindec( str_pad( $chunk, 5, '0', STR_PAD_RIGHT ) ) ];
	}
	return $out;
}

function sojib68_2fa_b32_decode( $str ) {
	$alpha = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
	$str   = strtoupper( preg_replace( '/[^A-Za-z2-7]/', '', (string) $str ) );
	$bits  = '';
	foreach ( str_split( $str ) as $ch ) {
		$bits .= str_pad( decbin( strpos( $alpha, $ch ) ), 5, '0', STR_PAD_LEFT );
	}
	$out = '';
	foreach ( str_split( $bits, 8 ) as $byte ) {
		if ( 8 === strlen( $byte ) ) {
			$out .= chr( bindec( $byte ) );
		}
	}
	return $out;
}

/** 6-digit TOTP for a raw secret at a given 30s step. */
function sojib68_2fa_totp( $raw_secret, $step ) {
	$hash   = hash_hmac( 'sha1', pack( 'N2', 0, $step ), $raw_secret, true );
	$offset = ord( $hash[19] ) & 0xf;
	$num    = ( ( ord( $hash[ $offset ] ) & 0x7f ) << 24 )
		| ( ord( $hash[ $offset + 1 ] ) << 16 )
		| ( ord( $hash[ $offset + 2 ] ) << 8 )
		| ord( $hash[ $offset + 3 ] );
	return str_pad( (string) ( $num % 1000000 ), 6, '0', STR_PAD_LEFT );
}

/** Matching step (within ±1) or 0. */
function sojib68_2fa_match_step( $raw_secret, $code, $now = null ) {
	$now  = null === $now ? time() : $now;
	$base = (int) floor( $now / 30 );
	for ( $i = -1; $i <= 1; $i++ ) {
		if ( hash_equals( sojib68_2fa_totp( $raw_secret, $base + $i ), $code ) ) {
			return $base + $i;
		}
	}
	return 0;
}

function sojib68_2fa_key() {
	return hash( 'sha256', wp_salt( 'auth' ) . '|sojib68-2fa', true );
}

function sojib68_2fa_seal( $secret_b32 ) {
	if ( function_exists( 'openssl_encrypt' ) ) {
		$iv = random_bytes( 16 );
		$ct = openssl_encrypt( $secret_b32, 'aes-256-cbc', sojib68_2fa_key(), OPENSSL_RAW_DATA, $iv );
		if ( false !== $ct ) {
			return 'v1:' . base64_encode( $iv . $ct );
		}
	}
	return 'p:' . $secret_b32;
}

function sojib68_2fa_unseal( $stored ) {
	$stored = (string) $stored;
	if ( 0 === strpos( $stored, 'p:' ) ) {
		return substr( $stored, 2 );
	}
	if ( 0 === strpos( $stored, 'v1:' ) && function_exists( 'openssl_decrypt' ) ) {
		$raw = base64_decode( substr( $stored, 3 ), true );
		if ( false !== $raw && strlen( $raw ) > 16 ) {
			$pt = openssl_decrypt( substr( $raw, 16 ), 'aes-256-cbc', sojib68_2fa_key(), OPENSSL_RAW_DATA, substr( $raw, 0, 16 ) );
			return false === $pt ? '' : $pt;
		}
	}
	return '';
}

function sojib68_2fa_new_secret() {
	return sojib68_2fa_b32_encode( random_bytes( 20 ) );
}

function sojib68_2fa_hash_backup( $code ) {
	return hash_hmac( 'sha256', strtoupper( preg_replace( '/[^A-Za-z0-9]/', '', $code ) ), sojib68_2fa_key() );
}

/** @return string[] plain codes like ABCDE-FGHJK */
function sojib68_2fa_make_backup_codes( $count = 8 ) {
	$alpha = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
	$codes = array();
	for ( $i = 0; $i < $count; $i++ ) {
		$c = '';
		for ( $j = 0; $j < 10; $j++ ) {
			$c .= $alpha[ random_int( 0, strlen( $alpha ) - 1 ) ];
		}
		$codes[] = substr( $c, 0, 5 ) . '-' . substr( $c, 5 );
	}
	return $codes;
}

/* ---------------------------------------------------------------------
 * State
 * ------------------------------------------------------------------ */

function sojib68_2fa_is_bypassed() {
	return defined( 'SOJIB68_2FA_DISABLED' ) && SOJIB68_2FA_DISABLED;
}

function sojib68_2fa_enabled( $user_id ) {
	return '1' === (string) get_user_meta( $user_id, 'sojib68_2fa_enabled', true )
		&& '' !== (string) get_user_meta( $user_id, 'sojib68_2fa_secret', true );
}

function sojib68_2fa_disable( $user_id ) {
	foreach ( array( 'enabled', 'secret', 'last', 'backup', 'fail', 'pending' ) as $k ) {
		delete_user_meta( $user_id, 'sojib68_2fa_' . $k );
	}
}

/** Turn 2FA on with a verified secret; returns the plain backup codes. */
function sojib68_2fa_enable( $user_id, $secret_b32, $last_step = 0 ) {
	$codes = sojib68_2fa_make_backup_codes();
	update_user_meta( $user_id, 'sojib68_2fa_secret', sojib68_2fa_seal( $secret_b32 ) );
	update_user_meta( $user_id, 'sojib68_2fa_enabled', '1' );
	update_user_meta( $user_id, 'sojib68_2fa_last', (int) $last_step );
	update_user_meta( $user_id, 'sojib68_2fa_backup', array_map( 'sojib68_2fa_hash_backup', $codes ) );
	delete_user_meta( $user_id, 'sojib68_2fa_pending' );
	delete_user_meta( $user_id, 'sojib68_2fa_fail' );
	return $codes;
}

function sojib68_2fa_locked_seconds( $user_id ) {
	$fail = get_user_meta( $user_id, 'sojib68_2fa_fail', true );
	if ( is_array( $fail ) && ! empty( $fail['until'] ) && $fail['until'] > time() ) {
		return (int) $fail['until'] - time();
	}
	return 0;
}

function sojib68_2fa_register_fail( $user_id ) {
	$fail  = get_user_meta( $user_id, 'sojib68_2fa_fail', true );
	$count = ( is_array( $fail ) && ! empty( $fail['count'] ) && empty( $fail['until'] ) ) ? (int) $fail['count'] : 0;
	$count++;
	if ( $count >= SOJIB68_2FA_MAXFAIL ) {
		update_user_meta( $user_id, 'sojib68_2fa_fail', array( 'count' => 0, 'until' => time() + SOJIB68_2FA_LOCK ) );
	} else {
		update_user_meta( $user_id, 'sojib68_2fa_fail', array( 'count' => $count, 'until' => 0 ) );
	}
}

/**
 * Check an authenticator code or a backup code for this user.
 * Consumes the TOTP step / backup code on success (no replay).
 */
function sojib68_2fa_verify( $user_id, $input ) {
	$input = trim( (string) $input );
	$plain = preg_replace( '/[\s-]/', '', $input );
	if ( '' === $plain ) {
		return false;
	}

	if ( preg_match( '/^\d{6}$/', $plain ) ) {
		$secret = sojib68_2fa_b32_decode( sojib68_2fa_unseal( get_user_meta( $user_id, 'sojib68_2fa_secret', true ) ) );
		if ( '' === $secret ) {
			return false;
		}
		$step = sojib68_2fa_match_step( $secret, $plain );
		$last = (int) get_user_meta( $user_id, 'sojib68_2fa_last', true );
		if ( $step && $step > $last ) {
			update_user_meta( $user_id, 'sojib68_2fa_last', $step );
			return true;
		}
		return false;
	}

	$hashes = get_user_meta( $user_id, 'sojib68_2fa_backup', true );
	if ( is_array( $hashes ) ) {
		$h = sojib68_2fa_hash_backup( $plain );
		foreach ( $hashes as $i => $stored ) {
			if ( hash_equals( (string) $stored, $h ) ) {
				unset( $hashes[ $i ] );
				update_user_meta( $user_id, 'sojib68_2fa_backup', array_values( $hashes ) );
				return true;
			}
		}
	}
	return false;
}

/* ---------------------------------------------------------------------
 * Login hook (wp-login.php, office login, CRM login)
 * ------------------------------------------------------------------ */

function sojib68_2fa_authenticate( $user, $username = '', $password = '' ) {
	if ( ! ( $user instanceof WP_User ) || '' === (string) $password || sojib68_2fa_is_bypassed() ) {
		return $user;
	}
	if ( ( defined( 'REST_REQUEST' ) && REST_REQUEST ) || ( defined( 'XMLRPC_REQUEST' ) && XMLRPC_REQUEST ) ) {
		return $user;
	}
	if ( ! sojib68_2fa_enabled( $user->ID ) ) {
		return $user;
	}

	$wait = sojib68_2fa_locked_seconds( $user->ID );
	if ( $wait ) {
		return new WP_Error(
			'sojib68_2fa_locked',
			sprintf( 'কোড অনেকবার ভুল হয়েছে। %d মিনিট পর আবার চেষ্টা করুন।', (int) ceil( $wait / 60 ) )
		);
	}

	$code = isset( $_POST[ SOJIB68_2FA_FIELD ] ) ? sanitize_text_field( wp_unslash( $_POST[ SOJIB68_2FA_FIELD ] ) ) : '';
	if ( '' === trim( $code ) ) {
		return new WP_Error( 'sojib68_2fa_required', 'টু-স্টেপ কোড দিন (অথেনটিকেটর অ্যাপের ৬ সংখ্যার কোড বা ব্যাকআপ কোড)।' );
	}
	if ( ! sojib68_2fa_verify( $user->ID, $code ) ) {
		sojib68_2fa_register_fail( $user->ID );
		return new WP_Error( 'sojib68_2fa_invalid', 'টু-স্টেপ কোড সঠিক নয়।' );
	}
	delete_user_meta( $user->ID, 'sojib68_2fa_fail' );
	return $user;
}
add_filter( 'authenticate', 'sojib68_2fa_authenticate', 50, 3 );

/** Extra field on wp-login.php. */
function sojib68_2fa_login_field() {
	?>
	<p>
		<label for="sojib68_2fa_code">টু-স্টেপ কোড (চালু থাকলে)</label>
		<input type="text" name="<?php echo esc_attr( SOJIB68_2FA_FIELD ); ?>" id="sojib68_2fa_code" class="input" value="" size="20" autocomplete="one-time-code" inputmode="numeric" placeholder="123456">
	</p>
	<?php
}
add_action( 'login_form', 'sojib68_2fa_login_field' );

/** Is this WP_Error one of ours? (office / CRM handlers show its message.) */
function sojib68_2fa_is_2fa_error( $error ) {
	if ( ! is_wp_error( $error ) ) {
		return false;
	}
	foreach ( $error->get_error_codes() as $code ) {
		if ( 0 === strpos( (string) $code, 'sojib68_2fa' ) ) {
			return true;
		}
	}
	return false;
}

/** Markup of the optional code input for the office / CRM login forms. */
function sojib68_2fa_form_field_html( $field_class, $id, $disabled = false ) {
	return '<div class="' . esc_attr( $field_class ) . '">'
		. '<label for="' . esc_attr( $id ) . '">টু-স্টেপ কোড <small>(চালু থাকলে)</small></label>'
		. '<input type="text" id="' . esc_attr( $id ) . '" name="' . esc_attr( SOJIB68_2FA_FIELD ) . '" autocomplete="one-time-code" inputmode="numeric" placeholder="123456"' . ( $disabled ? ' disabled' : '' ) . '>'
		. '</div>';
}

/* ---------------------------------------------------------------------
 * Admin: Users → টু-স্টেপ ভেরিফিকেশন
 * ------------------------------------------------------------------ */

function sojib68_2fa_menu() {
	add_users_page( 'টু-স্টেপ ভেরিফিকেশন', 'টু-স্টেপ ভেরিফিকেশন', 'read', SOJIB68_2FA_PAGE, 'sojib68_2fa_render_page' );
}
add_action( 'admin_menu', 'sojib68_2fa_menu' );

function sojib68_2fa_page_url( $args = array() ) {
	return add_query_arg( $args, admin_url( 'users.php?page=' . SOJIB68_2FA_PAGE ) );
}

function sojib68_2fa_label() {
	$host = wp_parse_url( home_url(), PHP_URL_HOST );
	return $host ? $host : 'sojib68.com';
}

function sojib68_2fa_otpauth_uri( $user, $secret_b32 ) {
	$issuer = sojib68_2fa_label();
	return 'otpauth://totp/' . rawurlencode( $issuer . ':' . $user->user_login )
		. '?secret=' . $secret_b32 . '&issuer=' . rawurlencode( $issuer ) . '&algorithm=SHA1&digits=6&period=30';
}

function sojib68_2fa_back( $notice, $type = 'ok' ) {
	wp_safe_redirect( sojib68_2fa_page_url( array( 'sojib68_2fa_msg' => $notice, 'sojib68_2fa_t' => $type ) ) );
	exit;
}

function sojib68_2fa_handle_start() {
	check_admin_referer( 'sojib68_2fa_start' );
	if ( ! is_user_logged_in() ) {
		wp_die( 'Forbidden', 403 );
	}
	update_user_meta( get_current_user_id(), 'sojib68_2fa_pending', sojib68_2fa_seal( sojib68_2fa_new_secret() ) );
	sojib68_2fa_back( '' );
}
add_action( 'admin_post_sojib68_2fa_start', 'sojib68_2fa_handle_start' );

function sojib68_2fa_handle_confirm() {
	check_admin_referer( 'sojib68_2fa_confirm' );
	$uid     = get_current_user_id();
	$pending = sojib68_2fa_unseal( get_user_meta( $uid, 'sojib68_2fa_pending', true ) );
	if ( ! $uid || '' === $pending ) {
		sojib68_2fa_back( 'আবার শুরু করুন।', 'err' );
	}
	$code = preg_replace( '/\D/', '', isset( $_POST['code'] ) ? wp_unslash( $_POST['code'] ) : '' );
	$step = strlen( $code ) === 6 ? sojib68_2fa_match_step( sojib68_2fa_b32_decode( $pending ), $code ) : 0;
	if ( ! $step ) {
		sojib68_2fa_back( 'কোড মেলেনি। অ্যাপে এখন যে ৬ সংখ্যা দেখাচ্ছে সেটি দিন।', 'err' );
	}
	$codes = sojib68_2fa_enable( $uid, $pending, $step );
	set_transient( 'sojib68_2fa_codes_' . $uid, $codes, 10 * MINUTE_IN_SECONDS );
	sojib68_2fa_back( 'টু-স্টেপ ভেরিফিকেশন চালু হয়েছে। নিচের ব্যাকআপ কোডগুলো এখনই সংরক্ষণ করুন।' );
}
add_action( 'admin_post_sojib68_2fa_confirm', 'sojib68_2fa_handle_confirm' );

function sojib68_2fa_handle_regen() {
	check_admin_referer( 'sojib68_2fa_regen' );
	$uid = get_current_user_id();
	if ( ! $uid || ! sojib68_2fa_enabled( $uid ) ) {
		sojib68_2fa_back( 'আগে টু-স্টেপ চালু করুন।', 'err' );
	}
	$codes = sojib68_2fa_make_backup_codes();
	update_user_meta( $uid, 'sojib68_2fa_backup', array_map( 'sojib68_2fa_hash_backup', $codes ) );
	set_transient( 'sojib68_2fa_codes_' . $uid, $codes, 10 * MINUTE_IN_SECONDS );
	sojib68_2fa_back( 'নতুন ব্যাকআপ কোড তৈরি হয়েছে (পুরোনোগুলো বাতিল)।' );
}
add_action( 'admin_post_sojib68_2fa_regen', 'sojib68_2fa_handle_regen' );

function sojib68_2fa_handle_disable() {
	check_admin_referer( 'sojib68_2fa_disable' );
	$uid = get_current_user_id();
	if ( ! $uid || ! sojib68_2fa_enabled( $uid ) ) {
		sojib68_2fa_back( 'টু-স্টেপ চালু নেই।', 'err' );
	}
	$code = isset( $_POST['code'] ) ? sanitize_text_field( wp_unslash( $_POST['code'] ) ) : '';
	if ( sojib68_2fa_locked_seconds( $uid ) || ! sojib68_2fa_verify( $uid, $code ) ) {
		sojib68_2fa_register_fail( $uid );
		sojib68_2fa_back( 'কোড সঠিক নয়, বন্ধ করা হয়নি।', 'err' );
	}
	sojib68_2fa_disable( $uid );
	sojib68_2fa_back( 'টু-স্টেপ ভেরিফিকেশন বন্ধ করা হয়েছে।' );
}
add_action( 'admin_post_sojib68_2fa_disable', 'sojib68_2fa_handle_disable' );

/** Administrator resets another user's 2FA (lost phone). */
function sojib68_2fa_handle_reset() {
	$target = isset( $_POST['user_id'] ) ? absint( $_POST['user_id'] ) : 0;
	check_admin_referer( 'sojib68_2fa_reset_' . $target );
	if ( ! current_user_can( 'manage_options' ) || ! $target || ! get_userdata( $target ) ) {
		wp_die( 'Forbidden', 403 );
	}
	sojib68_2fa_disable( $target );
	sojib68_2fa_back( 'ইউজারের টু-স্টেপ রিসেট হয়েছে। সে আবার চালু করতে পারবে।' );
}
add_action( 'admin_post_sojib68_2fa_reset', 'sojib68_2fa_handle_reset' );

function sojib68_2fa_render_page() {
	$uid     = get_current_user_id();
	$user    = wp_get_current_user();
	$enabled = sojib68_2fa_enabled( $uid );
	$pending = $enabled ? '' : sojib68_2fa_unseal( get_user_meta( $uid, 'sojib68_2fa_pending', true ) );
	$codes   = get_transient( 'sojib68_2fa_codes_' . $uid );
	if ( $codes ) {
		delete_transient( 'sojib68_2fa_codes_' . $uid );
	}
	$msg  = isset( $_GET['sojib68_2fa_msg'] ) ? sanitize_text_field( wp_unslash( $_GET['sojib68_2fa_msg'] ) ) : '';
	$type = ( isset( $_GET['sojib68_2fa_t'] ) && 'err' === $_GET['sojib68_2fa_t'] ) ? 'error' : 'success';
	$post = esc_url( admin_url( 'admin-post.php' ) );
	?>
	<div class="wrap">
		<h1>টু-স্টেপ ভেরিফিকেশন (2FA)</h1>
		<?php if ( $msg ) : ?>
			<div class="notice notice-<?php echo esc_attr( $type ); ?>"><p><?php echo esc_html( $msg ); ?></p></div>
		<?php endif; ?>
		<?php if ( sojib68_2fa_is_bypassed() ) : ?>
			<div class="notice notice-warning"><p>জরুরি সুইচ <code>SOJIB68_2FA_DISABLED</code> wp-config.php-তে চালু আছে — এখন কোড চাওয়া হচ্ছে না। কাজ শেষে লাইনটি সরিয়ে দিন।</p></div>
		<?php endif; ?>

		<p>পাসওয়ার্ডের পাশাপাশি মোবাইলের অথেনটিকেটর অ্যাপের ৬ সংখ্যার কোড লাগবে। এটি <strong>wp-login, অফিস লগইন ও ক্লায়েন্ট ডেস্ক</strong> — তিন জায়গাতেই কাজ করে।</p>

		<?php if ( $codes ) : ?>
			<div class="card" style="max-width:640px">
				<h2>ব্যাকআপ কোড — এখনই সংরক্ষণ করুন</h2>
				<p>ফোন হারালে এই কোড দিয়ে ঢুকতে পারবেন। প্রতিটি কোড <strong>একবারই</strong> কাজ করে। এই কোড আর দেখানো হবে না।</p>
				<pre style="font-size:16px;line-height:1.9;background:#f6f7f7;padding:12px"><?php echo esc_html( implode( "\n", $codes ) ); ?></pre>
			</div>
		<?php endif; ?>

		<?php if ( $enabled ) : ?>
			<div class="card" style="max-width:640px">
				<h2>স্ট্যাটাস: ✅ চালু</h2>
				<p>অবশিষ্ট ব্যাকআপ কোড: <strong><?php echo (int) count( (array) get_user_meta( $uid, 'sojib68_2fa_backup', true ) ); ?></strong>টি</p>
				<form method="post" action="<?php echo $post; ?>" style="margin-bottom:14px">
					<input type="hidden" name="action" value="sojib68_2fa_regen">
					<?php wp_nonce_field( 'sojib68_2fa_regen' ); ?>
					<button class="button">নতুন ব্যাকআপ কোড তৈরি করুন</button>
				</form>
				<form method="post" action="<?php echo $post; ?>">
					<input type="hidden" name="action" value="sojib68_2fa_disable">
					<?php wp_nonce_field( 'sojib68_2fa_disable' ); ?>
					<p><label>বন্ধ করতে বর্তমান কোড দিন: <input type="text" name="code" autocomplete="off" inputmode="numeric" required></label></p>
					<button class="button button-link-delete">টু-স্টেপ বন্ধ করুন</button>
				</form>
			</div>
		<?php elseif ( $pending ) : ?>
			<div class="card" style="max-width:640px">
				<h2>ধাপ ২: অ্যাপে যোগ করে কোড দিন</h2>
				<ol>
					<li>ফোনে Google Authenticator / Microsoft Authenticator / Authy খুলুন।</li>
					<li>“Add account” → “Enter a setup key” বেছে নিন। নাম: <code><?php echo esc_html( sojib68_2fa_label() ); ?></code>, ধরন: <em>Time based</em>।</li>
					<li>এই কী-টি বসান:</li>
				</ol>
				<p style="font-size:20px;letter-spacing:2px;font-family:monospace;background:#f6f7f7;padding:10px;word-break:break-all"><?php echo esc_html( trim( chunk_split( $pending, 4, ' ' ) ) ); ?></p>
				<p>ফোনেই এই পেজ খুলে থাকলে: <a href="<?php echo esc_attr( sojib68_2fa_otpauth_uri( $user, $pending ) ); ?>">অ্যাপে সরাসরি যোগ করুন</a>।</p>
				<form method="post" action="<?php echo $post; ?>">
					<input type="hidden" name="action" value="sojib68_2fa_confirm">
					<?php wp_nonce_field( 'sojib68_2fa_confirm' ); ?>
					<p><label>অ্যাপে এখন যে ৬ সংখ্যা দেখাচ্ছে: <input type="text" name="code" maxlength="7" autocomplete="off" inputmode="numeric" required></label></p>
					<button class="button button-primary">যাচাই করে চালু করুন</button>
				</form>
			</div>
		<?php else : ?>
			<div class="card" style="max-width:640px">
				<h2>স্ট্যাটাস: ❌ বন্ধ</h2>
				<p>চালু করলে পাসওয়ার্ড চুরি হলেও কেউ ঢুকতে পারবে না।</p>
				<form method="post" action="<?php echo $post; ?>">
					<input type="hidden" name="action" value="sojib68_2fa_start">
					<?php wp_nonce_field( 'sojib68_2fa_start' ); ?>
					<button class="button button-primary">টু-স্টেপ চালু করুন</button>
				</form>
			</div>
		<?php endif; ?>

		<?php if ( current_user_can( 'manage_options' ) ) : ?>
			<h2 style="margin-top:28px">সব ইউজারের অবস্থা</h2>
			<table class="widefat striped" style="max-width:640px">
				<thead><tr><th>ইউজার</th><th>2FA</th><th></th></tr></thead>
				<tbody>
				<?php foreach ( get_users( array( 'fields' => array( 'ID', 'user_login', 'display_name' ) ) ) as $u ) : ?>
					<?php $on = sojib68_2fa_enabled( $u->ID ); ?>
					<tr>
						<td><?php echo esc_html( $u->display_name . ' (' . $u->user_login . ')' ); ?></td>
						<td><?php echo $on ? '✅ চালু' : '—'; ?></td>
						<td>
							<?php if ( $on && (int) $u->ID !== $uid ) : ?>
								<form method="post" action="<?php echo $post; ?>" onsubmit="return confirm('এই ইউজারের 2FA রিসেট করবেন?');">
									<input type="hidden" name="action" value="sojib68_2fa_reset">
									<input type="hidden" name="user_id" value="<?php echo (int) $u->ID; ?>">
									<?php wp_nonce_field( 'sojib68_2fa_reset_' . (int) $u->ID ); ?>
									<button class="button button-small">রিসেট (ফোন হারালে)</button>
								</form>
							<?php endif; ?>
						</td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		<?php endif; ?>
	</div>
	<?php
}

/** Reminder for administrators who haven't turned it on. */
function sojib68_2fa_admin_notice() {
	if ( ! current_user_can( 'manage_options' ) || sojib68_2fa_enabled( get_current_user_id() ) ) {
		return;
	}
	$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
	if ( ! $screen || ! in_array( $screen->id, array( 'dashboard', 'users' ), true ) ) {
		return;
	}
	echo '<div class="notice notice-warning"><p><strong>নিরাপত্তা:</strong> আপনার অ্যাকাউন্টে টু-স্টেপ ভেরিফিকেশন বন্ধ। <a href="' . esc_url( sojib68_2fa_page_url() ) . '">এখনই চালু করুন</a>।</p></div>';
}
add_action( 'admin_notices', 'sojib68_2fa_admin_notice' );
