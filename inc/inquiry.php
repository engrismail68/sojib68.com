<?php
/**
 * "পরামর্শ নিন" form (header button, every page): নাম, মোবাইল নং, ঠিকানা, সমস্যার বিস্তারিত বর্ণনা.
 *
 * A submission becomes a new lead in the CRM (/crm → লিড, status "নতুন", note says it came from the
 * website). If the CRM cannot take it, it is saved as a private "Consultation Request" (the old
 * place) so nothing is lost. The owner is also e-mailed, best effort.
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
		wp_send_json_success( array( 'message' => 'ok' ) );
	}

	$rate_key = sojib68_inquiry_rate_key();
	$count    = (int) get_transient( $rate_key );
	if ( $count >= SOJIB68_INQUIRY_MAX_PER_HOUR ) {
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
		sojib68_inquiry_fail( __( 'চিহ্নিত ঘরগুলো ঠিক করুন।', 'sojib68' ), $errors );
	}

	set_transient( $rate_key, $count + 1, HOUR_IN_SECONDS );

	$saved = false;

	// 1) The CRM: the lead shows up in /crm as a new lead.
	if ( function_exists( 'sojib68_crm_lead_save' ) ) {
		$recent = false;
		if ( function_exists( 'sojib68_crm_find_leads_by_phone' ) ) {
			foreach ( sojib68_crm_find_leads_by_phone( $phone ) as $existing ) {
				// a double click / resend within 10 minutes is not a second lead
				if ( strtotime( $existing['created_at'] ) > current_time( 'timestamp' ) - 10 * MINUTE_IN_SECONDS ) {
					$recent = true;
					break;
				}
			}
		}
		if ( $recent ) {
			$saved = true;
		} else {
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
				$saved = true;
				if ( function_exists( 'sojib68_crm_lead_log' ) ) {
					sojib68_crm_lead_log( $lead_id, 'web_form', 'ওয়েবসাইটের "পরামর্শ নিন" ফর্ম থেকে এসেছে।' );
				}
			}
		}
	}

	// 2) Fallback: a private "Consultation Request" in wp-admin, so a lead is never lost.
	if ( ! $saved ) {
		$post_id = wp_insert_post( array(
			'post_type'    => 'sojib68_lead',
			'post_status'  => 'private',
			'post_title'   => sprintf( '%s — পরামর্শ নিন', $name ),
			'post_content' => $message,
		), true );
		if ( ! is_wp_error( $post_id ) && $post_id ) {
			update_post_meta( $post_id, 'phone', $phone );
			update_post_meta( $post_id, 'area', $address );
			$saved = true;
		}
	}

	if ( ! $saved ) {
		sojib68_inquiry_fail( __( 'দুঃখিত, এই মুহূর্তে জমা দেওয়া যায়নি। অনুগ্রহ করে WhatsApp-এ জানান।', 'sojib68' ), array(), 500 );
	}

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
