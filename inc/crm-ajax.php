<?php
/**
 * CRM AJAX endpoints — logged-in CRM staff only (checked per-call, not
 * just by page gating, since admin-ajax.php bypasses the router).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function sojib68_crm_ajax_guard() {
	if ( ! is_user_logged_in() || ! current_user_can( SOJIB68_CRM_CAP ) ) {
		wp_send_json_error( array( 'message' => __( 'অনুমতি নেই।', 'sojib68' ) ), 403 );
	}
	check_ajax_referer( 'sojib68_crm_action', 'nonce' );
}

/** A non-admin staff member may only touch leads/works assigned to them. */
function sojib68_crm_ajax_assert_owns_lead( $lead ) {
	if ( sojib68_crm_is_admin() ) {
		return true;
	}
	if ( ! $lead || (int) $lead['assigned_to'] !== get_current_user_id() ) {
		wp_send_json_error( array( 'message' => __( 'এই লিডে আপনার অনুমতি নেই।', 'sojib68' ) ), 403 );
	}
	return true;
}

function sojib68_crm_ajax_update_status() {
	sojib68_crm_ajax_guard();

	$id     = isset( $_POST['id'] ) ? (int) $_POST['id'] : 0;
	$status = isset( $_POST['status'] ) ? sanitize_text_field( wp_unslash( $_POST['status'] ) ) : '';
	$lead   = $id ? sojib68_crm_lead_get( $id ) : null;

	if ( ! $lead ) {
		wp_send_json_error( array( 'message' => __( 'লিড পাওয়া যায়নি।', 'sojib68' ) ) );
	}
	sojib68_crm_ajax_assert_owns_lead( $lead );

	sojib68_crm_lead_save( array(
		'name'             => $lead['name'],
		'phone'            => $lead['phone'],
		'email'            => $lead['email'],
		'address'          => $lead['address'],
		'source'           => $lead['source'],
		'referred_by'      => $lead['referred_by'],
		'service_type'     => $lead['service_type'],
		'details'          => $lead['details'],
		'notes'            => $lead['notes'],
		'assigned_to'      => $lead['assigned_to'],
		'status'           => $status,
		'next_followup_at' => $lead['next_followup_at'],
	), $id );

	wp_send_json_success();
}
add_action( 'wp_ajax_sojib68_crm_update_status', 'sojib68_crm_ajax_update_status' );

function sojib68_crm_ajax_delete_lead() {
	sojib68_crm_ajax_guard();
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( array( 'message' => __( 'অনুমতি নেই।', 'sojib68' ) ), 403 );
	}

	$id = isset( $_POST['id'] ) ? (int) $_POST['id'] : 0;
	if ( ! $id ) {
		wp_send_json_error();
	}

	sojib68_crm_lead_delete( $id );
	wp_send_json_success();
}
add_action( 'wp_ajax_sojib68_crm_delete_lead', 'sojib68_crm_ajax_delete_lead' );

function sojib68_crm_ajax_add_followup() {
	sojib68_crm_ajax_guard();

	$lead_id = isset( $_POST['lead_id'] ) ? (int) $_POST['lead_id'] : 0;
	$lead    = $lead_id ? sojib68_crm_lead_get( $lead_id ) : null;
	if ( ! $lead ) {
		wp_send_json_error( array( 'message' => __( 'লিড পাওয়া যায়নি।', 'sojib68' ) ) );
	}
	sojib68_crm_ajax_assert_owns_lead( $lead );

	$due_raw = isset( $_POST['due_at'] ) ? sanitize_text_field( wp_unslash( $_POST['due_at'] ) ) : '';
	$ts      = strtotime( $due_raw );
	if ( ! $ts ) {
		wp_send_json_error( array( 'message' => __( 'তারিখ/সময় সঠিক নয়।', 'sojib68' ) ) );
	}

	$note = isset( $_POST['note'] ) ? sanitize_text_field( wp_unslash( $_POST['note'] ) ) : '';
	sojib68_crm_followup_add( $lead_id, gmdate( 'Y-m-d H:i:s', $ts ), $note );

	wp_send_json_success();
}
add_action( 'wp_ajax_sojib68_crm_add_followup', 'sojib68_crm_ajax_add_followup' );

function sojib68_crm_ajax_complete_followup() {
	sojib68_crm_ajax_guard();

	$id = isset( $_POST['id'] ) ? (int) $_POST['id'] : 0;
	global $wpdb;
	$fu_table = sojib68_crm_followups_table();
	$fu       = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$fu_table} WHERE id = %d", $id ), ARRAY_A );
	if ( ! $fu ) {
		wp_send_json_error();
	}
	sojib68_crm_ajax_assert_owns_lead( sojib68_crm_lead_get( $fu['lead_id'] ) );

	sojib68_crm_followup_complete( $id );
	wp_send_json_success();
}
add_action( 'wp_ajax_sojib68_crm_complete_followup', 'sojib68_crm_ajax_complete_followup' );

function sojib68_crm_ajax_add_note() {
	sojib68_crm_ajax_guard();

	$lead_id = isset( $_POST['lead_id'] ) ? (int) $_POST['lead_id'] : 0;
	$lead    = $lead_id ? sojib68_crm_lead_get( $lead_id ) : null;
	if ( ! $lead ) {
		wp_send_json_error( array( 'message' => __( 'লিড পাওয়া যায়নি।', 'sojib68' ) ) );
	}
	sojib68_crm_ajax_assert_owns_lead( $lead );

	$note = isset( $_POST['note'] ) ? sanitize_textarea_field( wp_unslash( $_POST['note'] ) ) : '';
	if ( '' === trim( $note ) ) {
		wp_send_json_error( array( 'message' => __( 'নোট খালি রাখা যাবে না।', 'sojib68' ) ) );
	}

	sojib68_crm_lead_log( $lead_id, 'note', $note );
	wp_send_json_success();
}
add_action( 'wp_ajax_sojib68_crm_add_note', 'sojib68_crm_ajax_add_note' );

function sojib68_crm_ajax_create_work() {
	sojib68_crm_ajax_guard();

	$lead_id = isset( $_POST['lead_id'] ) ? (int) $_POST['lead_id'] : 0;
	$lead    = $lead_id ? sojib68_crm_lead_get( $lead_id ) : null;
	if ( ! $lead ) {
		wp_send_json_error( array( 'message' => __( 'লিড পাওয়া যায়নি।', 'sojib68' ) ) );
	}
	sojib68_crm_ajax_assert_owns_lead( $lead );

	$work_id = sojib68_crm_work_create_from_lead( $lead_id );
	if ( is_wp_error( $work_id ) ) {
		wp_send_json_error( array( 'message' => $work_id->get_error_message() ) );
	}

	$work = sojib68_crm_work_get( $work_id );
	wp_send_json_success( array( 'work_code' => $work ? $work['work_code'] : '', 'work_id' => $work_id ) );
}
add_action( 'wp_ajax_sojib68_crm_create_work', 'sojib68_crm_ajax_create_work' );

function sojib68_crm_ajax_delete_cash() {
	sojib68_crm_ajax_guard();
	if ( ! sojib68_crm_can_view_finance() ) {
		wp_send_json_error( array( 'message' => __( 'অনুমতি নেই।', 'sojib68' ) ), 403 );
	}

	$id = isset( $_POST['id'] ) ? (int) $_POST['id'] : 0;
	if ( ! $id ) {
		wp_send_json_error();
	}

	sojib68_crm_cash_delete( $id );
	wp_send_json_success();
}
add_action( 'wp_ajax_sojib68_crm_delete_cash', 'sojib68_crm_ajax_delete_cash' );
