<?php
/**
 * CRM AJAX endpoints — logged-in staff only.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function sojib68_crm_ajax_update_status() {
	if ( ! is_user_logged_in() || ! current_user_can( SOJIB68_CRM_CAP ) ) {
		wp_send_json_error( array( 'message' => __( 'অনুমতি নেই।', 'sojib68' ) ), 403 );
	}
	check_ajax_referer( 'sojib68_crm_action', 'nonce' );

	$id     = isset( $_POST['id'] ) ? (int) $_POST['id'] : 0;
	$status = isset( $_POST['status'] ) ? sanitize_text_field( wp_unslash( $_POST['status'] ) ) : '';

	if ( ! $id ) {
		wp_send_json_error( array( 'message' => __( 'লিড পাওয়া যায়নি।', 'sojib68' ) ) );
	}

	sojib68_crm_lead_update_status( $id, $status );
	wp_send_json_success();
}
add_action( 'wp_ajax_sojib68_crm_update_status', 'sojib68_crm_ajax_update_status' );

function sojib68_crm_ajax_delete_lead() {
	if ( ! is_user_logged_in() || ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( array( 'message' => __( 'অনুমতি নেই।', 'sojib68' ) ), 403 );
	}
	check_ajax_referer( 'sojib68_crm_action', 'nonce' );

	$id = isset( $_POST['id'] ) ? (int) $_POST['id'] : 0;
	if ( ! $id ) {
		wp_send_json_error();
	}

	sojib68_crm_lead_delete( $id );
	wp_send_json_success();
}
add_action( 'wp_ajax_sojib68_crm_delete_lead', 'sojib68_crm_ajax_delete_lead' );
