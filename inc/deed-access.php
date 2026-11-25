<?php
/**
 * Access requests for the deed-writing page: a visitor sends name, full address, mobile and e-mail;
 * the owner reviews them under Users → "দলিল লেখার অনুরোধ" and approves (creates a "দলিল লেখক" user and
 * e-mails the password link) or rejects.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function sojib68_dw_req_table() {
	global $wpdb;
	return $wpdb->prefix . 'office_dw_requests';
}

function sojib68_dw_req_install() {
	global $wpdb;
	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	$t = sojib68_dw_req_table();
	dbDelta( "CREATE TABLE {$t} (
		id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
		name VARCHAR(190) NOT NULL DEFAULT '',
		address TEXT NOT NULL,
		mobile VARCHAR(32) NOT NULL DEFAULT '',
		email VARCHAR(190) NOT NULL DEFAULT '',
		status VARCHAR(16) NOT NULL DEFAULT 'pending',
		ip VARCHAR(64) NOT NULL DEFAULT '',
		user_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
		created_at DATETIME NOT NULL,
		decided_at DATETIME NULL DEFAULT NULL,
		PRIMARY KEY  (id),
		KEY status (status)
	) " . $wpdb->get_charset_collate() . ';' );
	update_option( 'sojib68_dw_req_db', '1' );
}
add_action( 'init', function () {
	if ( '1' !== get_option( 'sojib68_dw_req_db' ) ) {
		sojib68_dw_req_install();
	}
}, 7 );

/* ---------------------------------------------------------------------
 * Public form (AJAX, no login). Honeypot + per-IP limit instead of a nonce, because the
 * page is cached for visitors and a cached nonce would expire.
 * ------------------------------------------------------------------ */

function sojib68_dw_bn_digits( $s ) {
	return strtr( (string) $s, array( '০' => '0', '১' => '1', '২' => '2', '৩' => '3', '৪' => '4', '৫' => '5', '৬' => '6', '৭' => '7', '৮' => '8', '৯' => '9' ) );
}

add_action( 'wp_ajax_nopriv_sojib68_dw_request', 'sojib68_dw_ajax_request' );
add_action( 'wp_ajax_sojib68_dw_request', 'sojib68_dw_ajax_request' );
function sojib68_dw_ajax_request() {
	global $wpdb;
	if ( ! empty( $_POST['website'] ) ) { // honeypot
		wp_send_json_success( array( 'message' => 'ok' ) );
	}
	$ip  = isset( $_SERVER['REMOTE_ADDR'] ) ? substr( (string) $_SERVER['REMOTE_ADDR'], 0, 64 ) : '';
	$key = 'sojib68_dwreq_' . md5( $ip );
	$cnt = (int) get_transient( $key );
	if ( $cnt >= 3 ) {
		wp_send_json_error( array( 'message' => 'অনেকবার চেষ্টা হয়েছে। কিছুক্ষণ পর আবার চেষ্টা করুন।' ), 429 );
	}

	$name    = mb_substr( sanitize_text_field( wp_unslash( $_POST['name'] ?? '' ) ), 0, 120 );
	$address = mb_substr( sanitize_textarea_field( wp_unslash( $_POST['address'] ?? '' ) ), 0, 500 );
	$mobile  = preg_replace( '/[^0-9+]/', '', sojib68_dw_bn_digits( sanitize_text_field( wp_unslash( $_POST['mobile'] ?? '' ) ) ) );
	$email   = sanitize_email( wp_unslash( $_POST['email'] ?? '' ) );

	if ( mb_strlen( $name ) < 2 ) {
		wp_send_json_error( array( 'message' => 'আপনার নাম লিখুন।' ), 400 );
	}
	if ( mb_strlen( $address ) < 6 ) {
		wp_send_json_error( array( 'message' => 'পূর্ণ ঠিকানা লিখুন।' ), 400 );
	}
	if ( ! preg_match( '/^(\+?88)?01[3-9][0-9]{8}$/', $mobile ) ) {
		wp_send_json_error( array( 'message' => 'সঠিক মোবাইল নম্বর লিখুন (যেমন 01XXXXXXXXX)।' ), 400 );
	}
	if ( ! is_email( $email ) ) {
		wp_send_json_error( array( 'message' => 'সঠিক ইমেইল ঠিকানা লিখুন।' ), 400 );
	}

	$t = sojib68_dw_req_table();
	$dup = (int) $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$t} WHERE status = 'pending' AND (email = %s OR mobile = %s) LIMIT 1", $email, $mobile ) );
	if ( $dup ) {
		wp_send_json_error( array( 'message' => 'এই ইমেইল বা মোবাইল দিয়ে আগেই একটি অনুরোধ পাঠানো হয়েছে। অনুমোদনের অপেক্ষা করুন।' ), 409 );
	}
	$wpdb->insert( $t, array( 'name' => $name, 'address' => $address, 'mobile' => $mobile, 'email' => $email, 'status' => 'pending', 'ip' => $ip, 'created_at' => current_time( 'mysql' ) ), array( '%s', '%s', '%s', '%s', '%s', '%s', '%s' ) );
	set_transient( $key, $cnt + 1, HOUR_IN_SECONDS );

	$to = get_option( 'admin_email' );
	if ( $to ) {
		wp_mail( $to, 'দলিল লেখার সিস্টেমে নতুন অ্যাক্সেস অনুরোধ', "নাম: {$name}\nমোবাইল: {$mobile}\nইমেইল: {$email}\nঠিকানা: {$address}\n\nঅনুমোদন/বাতিল: " . admin_url( 'users.php?page=sojib68-dw-requests' ) );
	}
	wp_send_json_success( array( 'message' => 'আপনার অনুরোধ পাঠানো হয়েছে। অনুমোদন হলে আপনার ইমেইলে পাসওয়ার্ড সেট করার লিঙ্ক যাবে।' ) );
}

/* ---------------------------------------------------------------------
 * Admin page: Users → দলিল লেখার অনুরোধ
 * ------------------------------------------------------------------ */

function sojib68_dw_req_pending_count() {
	global $wpdb;
	return (int) $wpdb->get_var( 'SELECT COUNT(*) FROM ' . sojib68_dw_req_table() . " WHERE status = 'pending'" );
}

add_action( 'admin_menu', function () {
	$n     = sojib68_dw_req_pending_count();
	$badge = $n ? ' <span class="awaiting-mod">' . (int) $n . '</span>' : '';
	add_users_page( 'দলিল লেখার অনুরোধ', 'দলিল লেখার অনুরোধ' . $badge, 'manage_options', 'sojib68-dw-requests', 'sojib68_dw_req_render' );
} );

function sojib68_dw_req_url( $args = array() ) {
	return add_query_arg( $args, admin_url( 'users.php?page=sojib68-dw-requests' ) );
}

function sojib68_dw_req_render() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	global $wpdb;
	$t    = sojib68_dw_req_table();
	$rows = (array) $wpdb->get_results( "SELECT * FROM {$t} ORDER BY FIELD(status,'pending','approved','rejected'), id DESC LIMIT 200", ARRAY_A );
	$msg  = isset( $_GET['dwmsg'] ) ? sanitize_key( wp_unslash( $_GET['dwmsg'] ) ) : '';
	$notes = array(
		'approved' => 'অনুমোদন হয়েছে। ইউজার তৈরি হয়েছে এবং তাঁর ইমেইলে পাসওয়ার্ড সেট করার লিঙ্ক গেছে।',
		'rejected' => 'অনুরোধটি বাতিল করা হয়েছে।',
		'deleted'  => 'অনুরোধটি মুছে ফেলা হয়েছে।',
		'exists'   => 'এই ইমেইলে আগে থেকেই একটি ইউজার আছে। Users থেকে নিজে ঠিক করুন, বা অনুরোধটি বাতিল করুন।',
		'error'    => 'ইউজার তৈরি করা যায়নি।',
	);
	?>
	<div class="wrap">
		<h1>দলিল লেখার সিস্টেমে অ্যাক্সেস অনুরোধ</h1>
		<?php if ( isset( $notes[ $msg ] ) ) : ?>
			<div class="notice notice-<?php echo in_array( $msg, array( 'exists', 'error' ), true ) ? 'error' : 'success'; ?> is-dismissible"><p><?php echo esc_html( $notes[ $msg ] ); ?></p></div>
		<?php endif; ?>
		<p>অনুমোদন করলে "দলিল লেখক" রোলের নতুন ইউজার তৈরি হয় (শুধু দলিল লেখার পেজ ব্যবহার করতে পারে) এবং তাঁর ইমেইলে পাসওয়ার্ড সেট করার লিঙ্ক যায়।</p>
		<table class="widefat striped" style="max-width:1200px">
			<thead><tr><th>নাম</th><th>মোবাইল</th><th>ইমেইল</th><th>ঠিকানা</th><th>সময়</th><th>অবস্থা</th><th></th></tr></thead>
			<tbody>
			<?php if ( ! $rows ) : ?>
				<tr><td colspan="7">এখনো কোনো অনুরোধ আসেনি।</td></tr>
			<?php endif; ?>
			<?php
			foreach ( $rows as $r ) :
				$label = array( 'pending' => 'অপেক্ষমাণ', 'approved' => 'অনুমোদিত', 'rejected' => 'বাতিল' );
				?>
				<tr>
					<td><b><?php echo esc_html( $r['name'] ); ?></b></td>
					<td><?php echo esc_html( $r['mobile'] ); ?></td>
					<td><?php echo esc_html( $r['email'] ); ?></td>
					<td style="max-width:300px"><?php echo esc_html( $r['address'] ); ?></td>
					<td><?php echo esc_html( $r['created_at'] ); ?></td>
					<td><?php echo esc_html( $label[ $r['status'] ] ?? $r['status'] ); ?></td>
					<td>
						<?php if ( 'pending' === $r['status'] ) : ?>
							<a class="button button-primary" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=sojib68_dw_req&do=approve&id=' . (int) $r['id'] ), 'sojib68_dw_req_' . (int) $r['id'] ) ); ?>">অনুমোদন দিন</a>
							<a class="button" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=sojib68_dw_req&do=reject&id=' . (int) $r['id'] ), 'sojib68_dw_req_' . (int) $r['id'] ) ); ?>">বাতিল</a>
						<?php else : ?>
							<a class="button-link-delete" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=sojib68_dw_req&do=delete&id=' . (int) $r['id'] ), 'sojib68_dw_req_' . (int) $r['id'] ) ); ?>" onclick="return confirm('এই অনুরোধটি তালিকা থেকে মুছবেন?')">মুছুন</a>
						<?php endif; ?>
					</td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
	</div>
	<?php
}

add_action( 'admin_post_sojib68_dw_req', function () {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'অনুমতি নেই।' );
	}
	global $wpdb;
	$id = isset( $_GET['id'] ) ? (int) $_GET['id'] : 0;
	check_admin_referer( 'sojib68_dw_req_' . $id );
	$do = isset( $_GET['do'] ) ? sanitize_key( wp_unslash( $_GET['do'] ) ) : '';
	$t  = sojib68_dw_req_table();
	$r  = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$t} WHERE id = %d", $id ), ARRAY_A );
	if ( ! $r ) {
		wp_safe_redirect( sojib68_dw_req_url() );
		exit;
	}
	$now = current_time( 'mysql' );

	if ( 'reject' === $do && 'pending' === $r['status'] ) {
		$wpdb->update( $t, array( 'status' => 'rejected', 'decided_at' => $now ), array( 'id' => $id ) );
		wp_safe_redirect( sojib68_dw_req_url( array( 'dwmsg' => 'rejected' ) ) );
		exit;
	}
	if ( 'delete' === $do && 'pending' !== $r['status'] ) {
		$wpdb->delete( $t, array( 'id' => $id ), array( '%d' ) );
		wp_safe_redirect( sojib68_dw_req_url( array( 'dwmsg' => 'deleted' ) ) );
		exit;
	}
	if ( 'approve' === $do && 'pending' === $r['status'] ) {
		sojib68_dw_ensure_role();
		$email = sanitize_email( $r['email'] );
		if ( ! is_email( $email ) || email_exists( $email ) ) {
			wp_safe_redirect( sojib68_dw_req_url( array( 'dwmsg' => 'exists' ) ) );
			exit;
		}
		$login = sanitize_user( strstr( $email, '@', true ), true );
		$login = '' !== $login ? $login : 'dolil' . $id;
		$base  = $login;
		for ( $i = 2; username_exists( $login ); $i++ ) {
			$login = $base . $i;
		}
		$uid = wp_insert_user( array(
			'user_login'   => $login,
			'user_pass'    => wp_generate_password( 24 ),
			'user_email'   => $email,
			'display_name' => $r['name'],
			'first_name'   => $r['name'],
			'role'         => 'sojib_deed_writer',
		) );
		if ( is_wp_error( $uid ) ) {
			wp_safe_redirect( sojib68_dw_req_url( array( 'dwmsg' => 'error' ) ) );
			exit;
		}
		update_user_meta( $uid, 'sojib68_dw_mobile', $r['mobile'] );
		update_user_meta( $uid, 'sojib68_dw_address', $r['address'] );
		wp_new_user_notification( $uid, null, 'user' ); // sends the "set your password" link to the new user
		$wpdb->update( $t, array( 'status' => 'approved', 'user_id' => (int) $uid, 'decided_at' => $now ), array( 'id' => $id ) );
		wp_safe_redirect( sojib68_dw_req_url( array( 'dwmsg' => 'approved' ) ) );
		exit;
	}
	wp_safe_redirect( sojib68_dw_req_url() );
	exit;
} );
