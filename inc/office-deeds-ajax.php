<?php
/**
 * AJAX endpoints for the deed entry form. Every handler re-checks
 * authorization itself (never trusts that the page that loaded the
 * form was authorized) and verifies a nonce before touching the
 * database.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function sojib68_deeds_ajax_guard() {
	if ( ! sojib68_office_is_authorized() ) {
		wp_send_json_error( array( 'message' => __( 'এই কাজের জন্য লগইন প্রয়োজন।', 'sojib68' ) ), 401 );
	}
	if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( wp_unslash( $_POST['nonce'] ), 'sojib68_deed_form' ) ) {
		wp_send_json_error( array( 'message' => __( 'সেশনের মেয়াদ শেষ হয়ে গেছে। পাতাটি রিলোড করুন।', 'sojib68' ) ), 403 );
	}
}

add_action( 'wp_ajax_sojib68_save_deed', 'sojib68_ajax_save_deed' );
function sojib68_ajax_save_deed() {
	sojib68_deeds_ajax_guard();

	$token   = isset( $_POST['submission_token'] ) ? sanitize_text_field( wp_unslash( $_POST['submission_token'] ) ) : '';
	$deed_id = isset( $_POST['deed_id'] ) ? (int) $_POST['deed_id'] : 0;

	// Double-submit protection: if this exact submission token was
	// already saved, return the existing record instead of saving again.
	if ( ! $deed_id ) {
		$existing = sojib68_deed_token_lookup( $token );
		if ( $existing ) {
			wp_send_json_success( array(
				'deed_id'  => $existing,
				'message'  => __( 'দলিলটি ইতিমধ্যে সংরক্ষিত হয়েছে।', 'sojib68' ),
				'duplicate_warning' => null,
			) );
		}
	}

	$data = sojib68_deed_collect_post_data();

	$office = $data['sub_registry_office'];
	$result = sojib68_deed_save( $data, $deed_id ?: null, get_current_user_id() );

	if ( is_wp_error( $result ) ) {
		wp_send_json_error( array( 'message' => $result->get_error_message() ) );
	}

	$saved_id = $result;
	sojib68_deed_token_store( $token, $saved_id );

	$dup = sojib68_deed_find_possible_duplicate(
		sojib68_normalize_field( $data['deed_no'] ?? '' ),
		! empty( $data['reg_year'] ) ? (int) sojib68_bn_to_en_digits( $data['reg_year'] ) : 0,
		$office,
		$saved_id
	);

	wp_send_json_success( array(
		'deed_id'  => $saved_id,
		'message'  => $deed_id ? __( 'দলিল আপডেট হয়েছে।', 'sojib68' ) : __( 'দলিল সংরক্ষণ হয়েছে।', 'sojib68' ),
		'duplicate_warning' => $dup ? sprintf(
			/* translators: %s: deed number */
			__( 'একই দলিল নং, সাল ও অফিসের আরেকটি রেকর্ড (#%d) আগে থেকেই আছে। এটি কি সত্যিই আলাদা দলিল?', 'sojib68' ),
			$dup->id
		) : null,
	) );
}

function sojib68_deed_collect_post_data() {
	$text = function ( $key ) {
		return isset( $_POST[ $key ] ) ? wp_unslash( $_POST[ $key ] ) : '';
	};
	$rows = function ( $key, array $fields ) {
		$out = array();
		if ( empty( $_POST[ $key ] ) || ! is_array( $_POST[ $key ] ) ) {
			return $out;
		}
		foreach ( wp_unslash( $_POST[ $key ] ) as $row ) {
			// Money rows with no amount typed are simply not recorded.
			if ( in_array( $key, array( 'receipts', 'expenses' ), true ) && '' === trim( (string) ( $row['amount'] ?? '' ) ) ) {
				continue;
			}
			$clean = array();
			foreach ( $fields as $field ) {
				$clean[ $field ] = isset( $row[ $field ] ) ? $row[ $field ] : '';
			}
			$out[] = $clean;
		}
		return $out;
	};

	return array(
		'sub_registry_office' => sanitize_text_field( $text( 'sub_registry_office' ) ),
		'reg_date'            => sanitize_text_field( $text( 'reg_date' ) ),
		'reg_year'            => sanitize_text_field( $text( 'reg_year' ) ),
		'deed_no'             => sanitize_text_field( $text( 'deed_no' ) ),
		'deed_type'           => sanitize_text_field( $text( 'deed_type' ) ),
		'deed_value'          => sanitize_text_field( $text( 'deed_value' ) ),
		'client_total_due'    => sanitize_text_field( $text( 'client_total_due' ) ),
		'fee_agreed'          => sanitize_text_field( $text( 'fee_agreed' ) ),
		'fee_collected'       => sanitize_text_field( $text( 'fee_collected' ) ),
		'dues_verified'       => sanitize_text_field( $text( 'dues_verified' ) ),
		'original_status'          => sanitize_key( $text( 'original_status' ) ),
		'original_received_date'   => sanitize_text_field( $text( 'original_received_date' ) ),
		'original_delivered_date'  => sanitize_text_field( $text( 'original_delivered_date' ) ),
		'original_delivered_to'    => sanitize_text_field( $text( 'original_delivered_to' ) ),
		'original_delivered_mobile' => sanitize_text_field( $text( 'original_delivered_mobile' ) ),
		'original_delivered_note'  => sanitize_textarea_field( $text( 'original_delivered_note' ) ),
		'notes'               => sanitize_textarea_field( $text( 'notes' ) ),
		'donors'              => $rows( 'donors', array( 'name', 'address', 'mobile' ) ),
		'grantees'            => $rows( 'grantees', array( 'name', 'address', 'mobile' ) ),
		'lands'               => $rows( 'lands', array( 'mouza', 'khatian_no', 'quantity', 'unit' ) ),
		// Only present when the form posts it (new deeds); edits leave payment history alone.
		'receipts'            => isset( $_POST['receipts'] ) ? $rows( 'receipts', array( 'received_date', 'amount', 'method', 'note' ) ) : null,
		'expenses'            => $rows( 'expenses', array( 'category', 'description', 'amount', 'paid_date', 'paid_status', 'paid_by' ) ),
	);
}

add_action( 'wp_ajax_sojib68_add_office', 'sojib68_ajax_add_office' );
function sojib68_ajax_add_office() {
	sojib68_deeds_ajax_guard();

	$name = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
	if ( '' === $name ) {
		wp_send_json_error( array( 'message' => __( 'অফিসের নাম লিখুন।', 'sojib68' ) ) );
	}

	$saved = sojib68_office_add_custom( $name );
	wp_send_json_success( array( 'name' => $saved ) );
}

add_action( 'wp_ajax_sojib68_add_deed_type', 'sojib68_ajax_add_deed_type' );
function sojib68_ajax_add_deed_type() {
	sojib68_deeds_ajax_guard();

	$name = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
	if ( '' === $name ) {
		wp_send_json_error( array( 'message' => __( 'দলিলের ধরনের নাম লিখুন।', 'sojib68' ) ) );
	}

	$saved = sojib68_deed_type_add_custom( $name );
	wp_send_json_success( array( 'name' => $saved ) );
}

add_action( 'wp_ajax_sojib68_delete_office', 'sojib68_ajax_delete_office' );
function sojib68_ajax_delete_office() {
	sojib68_deeds_ajax_guard();

	$name = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
	if ( '' === $name ) {
		wp_send_json_error( array( 'message' => __( 'কোনো অফিস নির্বাচিত নেই।', 'sojib68' ) ) );
	}

	sojib68_office_delete_custom( $name );
	wp_send_json_success();
}

add_action( 'wp_ajax_sojib68_delete_deed_type', 'sojib68_ajax_delete_deed_type' );
function sojib68_ajax_delete_deed_type() {
	sojib68_deeds_ajax_guard();

	$name = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
	if ( '' === $name ) {
		wp_send_json_error( array( 'message' => __( 'কোনো ধরন নির্বাচিত নেই।', 'sojib68' ) ) );
	}

	sojib68_deed_type_delete_custom( $name );
	wp_send_json_success();
}

add_action( 'wp_ajax_sojib68_add_payment', 'sojib68_ajax_add_payment' );
function sojib68_ajax_add_payment() {
	sojib68_deeds_ajax_guard();

	$result = sojib68_deed_add_payment(
		isset( $_POST['deed_id'] ) ? (int) $_POST['deed_id'] : 0,
		isset( $_POST['amount'] ) ? sanitize_text_field( wp_unslash( $_POST['amount'] ) ) : '',
		isset( $_POST['received_date'] ) ? sanitize_text_field( wp_unslash( $_POST['received_date'] ) ) : '',
		isset( $_POST['method'] ) ? sanitize_text_field( wp_unslash( $_POST['method'] ) ) : '',
		isset( $_POST['note'] ) ? sanitize_text_field( wp_unslash( $_POST['note'] ) ) : '',
		get_current_user_id()
	);

	if ( is_wp_error( $result ) ) {
		wp_send_json_error( array( 'message' => $result->get_error_message() ) );
	}

	wp_send_json_success( array( 'payment_id' => $result, 'message' => __( 'টাকা আদায় সংরক্ষিত হয়েছে।', 'sojib68' ) ) );
}

add_action( 'wp_ajax_sojib68_delete_payment', 'sojib68_ajax_delete_payment' );
function sojib68_ajax_delete_payment() {
	sojib68_deeds_ajax_guard();

	$result = sojib68_deed_delete_payment( isset( $_POST['payment_id'] ) ? (int) $_POST['payment_id'] : 0, get_current_user_id() );
	if ( is_wp_error( $result ) ) {
		wp_send_json_error( array( 'message' => $result->get_error_message() ) );
	}

	wp_send_json_success( array( 'message' => __( 'এন্ট্রি মুছে ফেলা হয়েছে।', 'sojib68' ) ) );
}

add_action( 'wp_ajax_sojib68_delete_deed', 'sojib68_ajax_delete_deed' );
function sojib68_ajax_delete_deed() {
	sojib68_deeds_ajax_guard();

	$result = sojib68_deed_soft_delete( isset( $_POST['deed_id'] ) ? (int) $_POST['deed_id'] : 0, get_current_user_id() );
	if ( is_wp_error( $result ) ) {
		wp_send_json_error( array( 'message' => $result->get_error_message() ) );
	}

	wp_send_json_success( array( 'message' => __( 'দলিলটি রিসাইকেল বিনে পাঠানো হয়েছে। ৭ দিনের মধ্যে ফিরিয়ে আনা যাবে।', 'sojib68' ) ) );
}

add_action( 'wp_ajax_sojib68_restore_deed', 'sojib68_ajax_restore_deed' );
function sojib68_ajax_restore_deed() {
	sojib68_deeds_ajax_guard();

	$result = sojib68_deed_restore( isset( $_POST['deed_id'] ) ? (int) $_POST['deed_id'] : 0, get_current_user_id() );
	if ( is_wp_error( $result ) ) {
		wp_send_json_error( array( 'message' => $result->get_error_message() ) );
	}

	wp_send_json_success( array( 'message' => __( 'দলিলটি ফিরিয়ে আনা হয়েছে।', 'sojib68' ) ) );
}

add_action( 'wp_ajax_sojib68_purge_deed', 'sojib68_ajax_purge_deed' );
function sojib68_ajax_purge_deed() {
	sojib68_deeds_ajax_guard();

	$deed_id = isset( $_POST['deed_id'] ) ? (int) $_POST['deed_id'] : 0;
	if ( ! $deed_id ) {
		wp_send_json_error( array( 'message' => __( 'দলিলটি পাওয়া যায়নি।', 'sojib68' ) ) );
	}

	sojib68_deed_purge( $deed_id );
	wp_send_json_success( array( 'message' => __( 'দলিলটি স্থায়ীভাবে মুছে ফেলা হয়েছে।', 'sojib68' ) ) );
}
