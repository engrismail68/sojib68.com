<?php
/**
 * Contact form handling for the Sojib68 theme.
 *
 * No external CRM/API is connected yet, so submissions are saved as a
 * private custom post type inside WordPress (visible under a top-level
 * admin menu) and an email notification is attempted via wp_mail(). A
 * submission is only reported as successful once it is actually saved.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'init', 'sojib68_register_lead_post_type' );
function sojib68_register_lead_post_type() {
	register_post_type( 'sojib68_lead', array(
		'label'           => __( 'Consultation Requests', 'sojib68' ),
		'public'          => false,
		'show_ui'         => true,
		'show_in_menu'    => true,
		'menu_icon'       => 'dashicons-email-alt',
		'capability_type' => 'page',
		'supports'        => array( 'title', 'editor' ),
	) );
}

add_action( 'admin_post_nopriv_sojib68_submit_contact', 'sojib68_handle_contact_submit' );
add_action( 'admin_post_sojib68_submit_contact', 'sojib68_handle_contact_submit' );
add_action( 'wp_ajax_nopriv_sojib68_submit_contact', 'sojib68_handle_contact_submit' );
add_action( 'wp_ajax_sojib68_submit_contact', 'sojib68_handle_contact_submit' );

function sojib68_handle_contact_submit() {
	check_ajax_referer( 'sojib68_contact_form', 'nonce' );

	// Honeypot: a hidden field real visitors never fill in.
	if ( ! empty( $_POST['sojib68_website'] ) ) {
		wp_send_json_success( array( 'message' => __( 'ধন্যবাদ, আপনার অনুরোধ জমা হয়েছে।', 'sojib68' ) ) );
	}

	$name        = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
	$phone       = isset( $_POST['phone'] ) ? sanitize_text_field( wp_unslash( $_POST['phone'] ) ) : '';
	$service_key = isset( $_POST['service'] ) ? sanitize_text_field( wp_unslash( $_POST['service'] ) ) : '';
	$area        = isset( $_POST['area'] ) ? sanitize_text_field( wp_unslash( $_POST['area'] ) ) : '';
	$message     = isset( $_POST['message'] ) ? sanitize_textarea_field( wp_unslash( $_POST['message'] ) ) : '';

	$errors = array();

	if ( '' === $name ) {
		$errors['name'] = __( 'নাম আবশ্যক।', 'sojib68' );
	}
	if ( '' === $phone ) {
		$errors['phone'] = __( 'মোবাইল নম্বর আবশ্যক।', 'sojib68' );
	}

	$services     = function_exists( 'sojib68_get_services' ) ? sojib68_get_services() : array();
	$service_name = __( 'অন্যান্য', 'sojib68' );
	if ( 'other' !== $service_key ) {
		if ( ! isset( $services[ $service_key ] ) ) {
			$errors['service'] = __( 'সেবার ধরন নির্বাচন করুন।', 'sojib68' );
		} else {
			$service_name = $services[ $service_key ]['title'];
		}
	}

	if ( '' === $message ) {
		$errors['message'] = __( 'সমস্যার সংক্ষিপ্ত বিবরণ আবশ্যক।', 'sojib68' );
	}

	if ( ! empty( $errors ) ) {
		wp_send_json_error( array(
			'message' => __( 'দয়া করে চিহ্নিত ঘরগুলো সঠিকভাবে পূরণ করুন।', 'sojib68' ),
			'errors'  => $errors,
		), 422 );
	}

	$post_id = wp_insert_post( array(
		'post_type'   => 'sojib68_lead',
		'post_status' => 'private',
		'post_title'  => sprintf( '%s — %s', $name, $service_name ),
		'post_content' => $message,
	), true );

	if ( is_wp_error( $post_id ) ) {
		wp_send_json_error( array(
			'message' => __( 'দুঃখিত, এই মুহূর্তে জমা দেওয়া যায়নি। পরে আবার চেষ্টা করুন।', 'sojib68' ),
		), 500 );
	}

	update_post_meta( $post_id, 'phone', $phone );
	update_post_meta( $post_id, 'service', $service_name );
	update_post_meta( $post_id, 'area', $area );

	$notify_to = defined( 'SOJIB68_EMAIL' ) && SOJIB68_EMAIL ? SOJIB68_EMAIL : get_option( 'admin_email' );
	$subject   = sprintf( __( 'নতুন পরামর্শের অনুরোধ — %s', 'sojib68' ), $name );
	$body      = implode( "\n", array(
		__( 'নাম:', 'sojib68' ) . ' ' . $name,
		__( 'মোবাইল:', 'sojib68' ) . ' ' . $phone,
		__( 'সেবার ধরন:', 'sojib68' ) . ' ' . $service_name,
		__( 'জমির এলাকা:', 'sojib68' ) . ' ' . ( $area ? $area : '-' ),
		__( 'বিবরণ:', 'sojib68' ) . ' ' . $message,
	) );
	wp_mail( $notify_to, $subject, $body );

	wp_send_json_success( array(
		'message' => __( 'ধন্যবাদ! আপনার অনুরোধ জমা হয়েছে। শীঘ্রই যোগাযোগ করা হবে।', 'sojib68' ),
	) );
}
