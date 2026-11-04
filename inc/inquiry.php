<?php
/**
 * "পরামর্শ নিন" form (header button, every page): নাম, মোবাইল নং, ঠিকানা, সমস্যার বিস্তারিত বর্ণনা.
 *
 * A submission is saved in wp-admin → Consultation Requests (always) and also as a new lead in the
 * CRM (/crm → লিড, status "নতুন"). The owner is also e-mailed, best effort.
 *
 * No nonce on purpose: pages may be served from a cache and a cached nonce goes stale. Spam is
 * held back by a hidden field, a minimum fill time and a per-IP limit.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// The form opens from any link or button that has the attribute data-inq-open (the header
// "পরামর্শ নিন" button). Set this to false to switch it off: nothing is loaded on the pages
// and the submit endpoint is closed.
define( 'SOJIB68_INQUIRY_ENABLED', true );

define( 'SOJIB68_INQUIRY_MAX_PER_HOUR', 5 );
define( 'SOJIB68_INQUIRY_MIN_MS', 2500 );

function sojib68_inquiry_enqueue() {
	if ( ! SOJIB68_INQUIRY_ENABLED ) {
		return;
	}
	$css = get_theme_file_path( 'assets/css/inquiry.css' );
	$js  = get_theme_file_path( 'assets/js/inquiry.js' );
	wp_enqueue_style( 'sojib68-inquiry', get_theme_file_uri( 'assets/css/inquiry.css' ), array( 'sojib68-style' ), file_exists( $css ) ? filemtime( $css ) : '1.0.0' );
	wp_enqueue_script( 'sojib68-inquiry', get_theme_file_uri( 'assets/js/inquiry.js' ), array(), file_exists( $js ) ? filemtime( $js ) : '1.0.0', true );
	wp_localize_script( 'sojib68-inquiry', 'sojib68Inquiry', array(
		'ajaxUrl' => admin_url( 'admin-ajax.php' ),
		'minMs'   => SOJIB68_INQUIRY_MIN_MS,
	) );
}
add_action( 'wp_enqueue_scripts', 'sojib68_inquiry_enqueue', 30 );

function sojib68_inquiry_modal() {
	if ( ! SOJIB68_INQUIRY_ENABLED ) {
		return;
	}
	?>
	<div class="inq-modal" data-inq-modal hidden>
		<div class="inq-modal__backdrop" data-inq-close></div>
		<div class="inq-dialog" role="dialog" aria-modal="true" aria-labelledby="inq-title" tabindex="-1">
			<header class="inq-dialog__head">
				<h2 id="inq-title"><?php esc_html_e( 'পরামর্শ নিন', 'sojib68' ); ?></h2>
				<button type="button" class="inq-dialog__close" data-inq-close aria-label="<?php esc_attr_e( 'বন্ধ করুন', 'sojib68' ); ?>">&times;</button>
			</header>

			<form class="inq-form" data-inq-form novalidate>
				<div class="inq-body">
					<p class="inq-hint"><?php esc_html_e( 'নিচের তথ্য দিন, আপনার সঙ্গে যোগাযোগ করা হবে।', 'sojib68' ); ?></p>

					<div class="inq-field">
						<label for="inq-name"><?php esc_html_e( 'নাম', 'sojib68' ); ?> <span class="inq-req">*</span></label>
						<input type="text" id="inq-name" name="name" autocomplete="name" required>
						<span class="inq-error" data-inq-error="name"></span>
					</div>
					<div class="inq-field">
						<label for="inq-phone"><?php esc_html_e( 'মোবাইল নং', 'sojib68' ); ?> <span class="inq-req">*</span></label>
						<input type="tel" id="inq-phone" name="phone" inputmode="tel" autocomplete="tel" required>
						<span class="inq-error" data-inq-error="phone"></span>
					</div>
					<div class="inq-field">
						<label for="inq-address"><?php esc_html_e( 'ঠিকানা', 'sojib68' ); ?></label>
						<input type="text" id="inq-address" name="address" autocomplete="street-address">
						<span class="inq-error" data-inq-error="address"></span>
					</div>
					<div class="inq-field">
						<label for="inq-message"><?php esc_html_e( 'সমস্যার বিস্তারিত বর্ণনা', 'sojib68' ); ?> <span class="inq-req">*</span></label>
						<textarea id="inq-message" name="message" rows="4" required></textarea>
						<span class="inq-error" data-inq-error="message"></span>
					</div>

					<div class="inq-hp" aria-hidden="true">
						<label for="inq-website"><?php esc_html_e( 'Website', 'sojib68' ); ?></label>
						<input type="text" id="inq-website" name="website" tabindex="-1" autocomplete="off">
					</div>

					<p class="inq-status" data-inq-status role="status" aria-live="polite"></p>
					<p class="inq-note"><?php esc_html_e( 'দলিলের ছবি, জাতীয় পরিচয়পত্র বা সংবেদনশীল ব্যক্তিগত তথ্য এখানে পাঠাবেন না।', 'sojib68' ); ?></p>
				</div>

				<footer class="inq-foot">
					<button type="submit" class="btn btn-primary inq-submit" data-inq-submit><?php esc_html_e( 'সাবমিট করুন', 'sojib68' ); ?></button>
				</footer>
			</form>

			<div class="inq-done" data-inq-done hidden>
				<p class="inq-done__tick" aria-hidden="true">&#10003;</p>
				<h3><?php esc_html_e( 'ধন্যবাদ!', 'sojib68' ); ?></h3>
				<p><?php esc_html_e( 'আপনার অনুরোধ জমা হয়েছে। শীঘ্রই আপনার সঙ্গে যোগাযোগ করা হবে।', 'sojib68' ); ?></p>
				<button type="button" class="btn btn-outline" data-inq-close><?php esc_html_e( 'বন্ধ করুন', 'sojib68' ); ?></button>
			</div>
		</div>
	</div>
	<?php
}
add_action( 'wp_footer', 'sojib68_inquiry_modal', 8 );

/* ---------------------------------------------------------------------
 * Log of what happened to each submission (wp-admin → Consultation Requests → ফর্ম জমার লগ)
 * ------------------------------------------------------------------ */

define( 'SOJIB68_INQUIRY_LOG_OPTION', 'sojib68_inquiry_log' );

/** Keeps the last 30 outcomes, newest first, so a missing lead can be traced. */
function sojib68_inquiry_log( $result, $name = '', $phone = '', $detail = '' ) {
	$log = get_option( SOJIB68_INQUIRY_LOG_OPTION, array() );
	$log = is_array( $log ) ? $log : array();
	array_unshift( $log, array(
		'time'   => current_time( 'mysql' ),
		'result' => $result,
		'name'   => $name,
		'phone'  => $phone,
		'detail' => $detail,
	) );
	update_option( SOJIB68_INQUIRY_LOG_OPTION, array_slice( $log, 0, 30 ), false );
}

function sojib68_inquiry_result_label( $result ) {
	$labels = array(
		'both'      => 'জমা হয়েছে: wp-admin → Consultation Requests এবং CRM লিড, দুই জায়গাতেই',
		'wp_only'   => 'জমা হয়েছে: wp-admin → Consultation Requests-এ (CRM লিড তৈরি হয়নি)',
		'crm_only'  => 'জমা হয়েছে: শুধু CRM লিড (wp-admin তালিকায় হয়নি)',
		'duplicate' => 'একই নম্বর ও একই বিবরণ ১০ মিনিটের মধ্যে আবার এসেছিল, তাই নতুন কিছু হয়নি',
		'too_fast'  => 'আটকানো হয়েছে: খুব দ্রুত জমা (স্বয়ংক্রিয় মনে হয়েছে)',
		'spam'      => 'আটকানো হয়েছে: লুকানো ঘর পূরণ করা (স্প্যাম)',
		'limit'     => 'আটকানো হয়েছে: একই IP থেকে ঘণ্টায় অনেকবার',
		'invalid'   => 'তথ্য ঠিক ছিল না, জমা হয়নি',
		'error'     => 'ত্রুটি: কোথাও জমা হয়নি',
	);
	return isset( $labels[ $result ] ) ? $labels[ $result ] : $result;
}

function sojib68_inquiry_admin_menu() {
	add_submenu_page(
		'edit.php?post_type=sojib68_lead',
		__( 'ফর্ম জমার লগ', 'sojib68' ),
		__( 'ফর্ম জমার লগ', 'sojib68' ),
		'manage_options',
		'sojib68-inquiry-log',
		'sojib68_inquiry_log_page'
	);
}
add_action( 'admin_menu', 'sojib68_inquiry_admin_menu' );

function sojib68_inquiry_log_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$log = get_option( SOJIB68_INQUIRY_LOG_OPTION, array() );
	$log = is_array( $log ) ? $log : array();
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'পরামর্শ ফর্ম — জমার লগ', 'sojib68' ); ?></h1>
		<p><?php esc_html_e( 'ওয়েবসাইটের "পরামর্শ নিন" ফর্মে যা জমা পড়েছে (শেষ ৩০টি) এবং প্রতিটির কী হয়েছে। CRM-এ লিড না দেখলে এখানে কারণ দেখুন।', 'sojib68' ); ?></p>
		<table class="widefat striped" style="max-width:1000px;">
			<thead><tr><th><?php esc_html_e( 'সময়', 'sojib68' ); ?></th><th><?php esc_html_e( 'নাম', 'sojib68' ); ?></th><th><?php esc_html_e( 'মোবাইল', 'sojib68' ); ?></th><th><?php esc_html_e( 'কী হয়েছে', 'sojib68' ); ?></th></tr></thead>
			<tbody>
			<?php if ( ! $log ) : ?>
				<tr><td colspan="4"><?php esc_html_e( 'এখনো কিছু জমা পড়েনি।', 'sojib68' ); ?></td></tr>
			<?php endif; ?>
			<?php foreach ( $log as $row ) : ?>
				<tr>
					<td><?php echo esc_html( $row['time'] ); ?></td>
					<td><?php echo esc_html( $row['name'] ); ?></td>
					<td><?php echo esc_html( $row['phone'] ); ?></td>
					<td><?php echo esc_html( sojib68_inquiry_result_label( $row['result'] ) . ( '' !== $row['detail'] ? ' — ' . $row['detail'] : '' ) ); ?></td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
	</div>
	<?php
}

/* ---------------------------------------------------------------------
 * wp-admin → Consultation Requests: show the phone and address in the list
 * ------------------------------------------------------------------ */

function sojib68_inquiry_list_columns( $columns ) {
	$out = array();
	foreach ( $columns as $key => $label ) {
		$out[ $key ] = $label;
		if ( 'title' === $key ) {
			$out['sojib68_phone'] = __( 'মোবাইল', 'sojib68' );
			$out['sojib68_area']  = __( 'ঠিকানা', 'sojib68' );
		}
	}
	return $out;
}
add_filter( 'manage_sojib68_lead_posts_columns', 'sojib68_inquiry_list_columns' );

function sojib68_inquiry_list_column( $column, $post_id ) {
	if ( 'sojib68_phone' === $column ) {
		echo esc_html( (string) get_post_meta( $post_id, 'phone', true ) );
	} elseif ( 'sojib68_area' === $column ) {
		echo esc_html( (string) get_post_meta( $post_id, 'area', true ) );
	}
}
add_action( 'manage_sojib68_lead_posts_custom_column', 'sojib68_inquiry_list_column', 10, 2 );

/* ---------------------------------------------------------------------
 * Submission
 * ------------------------------------------------------------------ */

add_action( 'wp_ajax_nopriv_sojib68_inquiry', 'sojib68_inquiry_handle' );
add_action( 'wp_ajax_sojib68_inquiry', 'sojib68_inquiry_handle' );

/** Bangla digits to 0-9, so "০১৯৫…" is accepted. */
function sojib68_inquiry_digits( $value ) {
	return strtr( (string) $value, array(
		'০' => '0', '১' => '1', '২' => '2', '৩' => '3', '৪' => '4',
		'৫' => '5', '৬' => '6', '৭' => '7', '৮' => '8', '৯' => '9',
	) );
}

function sojib68_inquiry_rate_key() {
	$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? (string) $_SERVER['REMOTE_ADDR'] : '';
	return 'sojib68_inq_' . md5( $ip );
}

function sojib68_inquiry_fail( $message, $errors = array(), $code = 422 ) {
	wp_send_json_error( array( 'message' => $message, 'errors' => $errors ), $code );
}

function sojib68_inquiry_handle() {
	if ( ! SOJIB68_INQUIRY_ENABLED ) {
		wp_send_json_error( array( 'message' => '' ), 404 );
	}

	// Hidden field filled in, or sent too fast to be a person: pretend all is well, store nothing.
	$hp   = isset( $_POST['website'] ) ? trim( (string) wp_unslash( $_POST['website'] ) ) : '';
	$took = isset( $_POST['t'] ) ? (int) $_POST['t'] : 0;
	if ( '' !== $hp || $took < SOJIB68_INQUIRY_MIN_MS ) {
		sojib68_inquiry_log( '' !== $hp ? 'spam' : 'too_fast', isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '', isset( $_POST['phone'] ) ? sanitize_text_field( wp_unslash( $_POST['phone'] ) ) : '', $took . ' ms' );
		wp_send_json_success( array( 'message' => 'ok' ) );
	}

	$rate_key = sojib68_inquiry_rate_key();
	$count    = (int) get_transient( $rate_key );
	if ( $count >= SOJIB68_INQUIRY_MAX_PER_HOUR ) {
		sojib68_inquiry_log( 'limit', isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '', isset( $_POST['phone'] ) ? sanitize_text_field( wp_unslash( $_POST['phone'] ) ) : '' );
		sojib68_inquiry_fail( __( 'অনেকবার চেষ্টা হয়েছে। একটু পরে আবার চেষ্টা করুন, অথবা সরাসরি WhatsApp-এ জানান।', 'sojib68' ), array(), 429 );
	}

	$name    = isset( $_POST['name'] ) ? mb_substr( sanitize_text_field( wp_unslash( $_POST['name'] ) ), 0, 100 ) : '';
	$phone   = isset( $_POST['phone'] ) ? sanitize_text_field( wp_unslash( $_POST['phone'] ) ) : '';
	$address = isset( $_POST['address'] ) ? mb_substr( sanitize_text_field( wp_unslash( $_POST['address'] ) ), 0, 300 ) : '';
	$message = isset( $_POST['message'] ) ? mb_substr( sanitize_textarea_field( wp_unslash( $_POST['message'] ) ), 0, 2000 ) : '';

	$phone = preg_replace( '/[\s\-()]+/', '', sojib68_inquiry_digits( $phone ) );

	$errors = array();
	if ( '' === $name ) {
		$errors['name'] = __( 'নাম লিখুন।', 'sojib68' );
	}
	if ( ! preg_match( '/^\+?\d{10,14}$/', $phone ) ) {
		$errors['phone'] = __( 'সঠিক মোবাইল নম্বর লিখুন, যেমন 01950087868।', 'sojib68' );
	}
	if ( '' === trim( $message ) ) {
		$errors['message'] = __( 'সমস্যার বিবরণ লিখুন।', 'sojib68' );
	}
	if ( $errors ) {
		sojib68_inquiry_log( 'invalid', $name, $phone, implode( ', ', array_keys( $errors ) ) );
		sojib68_inquiry_fail( __( 'চিহ্নিত ঘরগুলো ঠিক করুন।', 'sojib68' ), $errors );
	}

	set_transient( $rate_key, $count + 1, HOUR_IN_SECONDS );

	// The same words from the same number within 10 minutes is a double click, not a second request.
	$dup_key = 'sojib68_inq_dup_' . md5( $phone . '|' . $message );
	if ( get_transient( $dup_key ) ) {
		sojib68_inquiry_log( 'duplicate', $name, $phone );
		wp_send_json_success( array( 'message' => 'ok' ) );
	}

	// 1) wp-admin → Consultation Requests: always. Everything is in the content, so it reads at a glance.
	$content = implode( "\n", array(
		'নাম: ' . $name,
		'মোবাইল: ' . $phone,
		'ঠিকানা: ' . ( '' !== $address ? $address : '-' ),
		'',
		'সমস্যার বিবরণ:',
		$message,
	) );
	$post_id = wp_insert_post( array(
		'post_type'    => 'sojib68_lead',
		'post_status'  => 'private',
		'post_title'   => sprintf( '%s — %s', $name, $phone ),
		'post_content' => $content,
	), true );
	$wp_ok = ! is_wp_error( $post_id ) && $post_id;
	if ( $wp_ok ) {
		update_post_meta( $post_id, 'phone', $phone );
		update_post_meta( $post_id, 'area', $address );
	}

	// 2) The CRM as well, so the lead is in /crm too (best effort; the wp-admin entry is the safety net).
	$crm_note = '';
	$crm_ok   = false;
	if ( function_exists( 'sojib68_crm_lead_save' ) ) {
		$lead_id = sojib68_crm_lead_save( array(
			'name'         => $name,
			'phone'        => $phone,
			'address'      => $address,
			'details'      => $message,
			'notes'        => 'ওয়েবসাইটের "পরামর্শ নিন" ফর্ম থেকে এসেছে।',
			'source'       => 'facebook_internet',
			'service_type' => 'other_land_service',
			'status'       => 'new',
		) );
		if ( ! is_wp_error( $lead_id ) && $lead_id ) {
			$crm_ok   = true;
			$crm_note = 'CRM লিড #' . (int) $lead_id;
			if ( function_exists( 'sojib68_crm_lead_log' ) ) {
				sojib68_crm_lead_log( $lead_id, 'web_form', 'ওয়েবসাইটের "পরামর্শ নিন" ফর্ম থেকে এসেছে।' );
			}
		} else {
			$crm_note = is_wp_error( $lead_id ) ? 'CRM: ' . $lead_id->get_error_message() : 'CRM লিড তৈরি করতে পারেনি';
		}
	} else {
		$crm_note = 'CRM উপলব্ধ নয়';
	}

	if ( $wp_ok && $crm_ok ) {
		$result = 'both';
	} elseif ( $wp_ok ) {
		$result = 'wp_only';
	} elseif ( $crm_ok ) {
		$result = 'crm_only';
	} else {
		$result = 'error';
	}
	sojib68_inquiry_log( $result, $name, $phone, trim( ( $wp_ok ? 'Consultation Request #' . (int) $post_id . '. ' : '' ) . $crm_note ) );

	if ( ! $wp_ok && ! $crm_ok ) {
		sojib68_inquiry_fail( __( 'দুঃখিত, এই মুহূর্তে জমা দেওয়া যায়নি। অনুগ্রহ করে WhatsApp-এ জানান।', 'sojib68' ), array(), 500 );
	}
	set_transient( $dup_key, 1, 10 * MINUTE_IN_SECONDS );

	// Best-effort e-mail to the owner.
	$notify_to = defined( 'SOJIB68_EMAIL' ) && SOJIB68_EMAIL ? SOJIB68_EMAIL : get_option( 'admin_email' );
	if ( $notify_to ) {
		wp_mail(
			$notify_to,
			sprintf( 'নতুন পরামর্শের অনুরোধ — %s', $name ),
			implode( "\n", array(
				'নাম: ' . $name,
				'মোবাইল: ' . $phone,
				'ঠিকানা: ' . ( '' !== $address ? $address : '-' ),
				'বিবরণ: ' . $message,
			) )
		);
	}

	wp_send_json_success( array( 'message' => 'ok' ) );
}
