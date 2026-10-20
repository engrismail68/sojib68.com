<?php
/**
 * CRM lead business logic (no HTML here).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function sojib68_crm_lead_sources() {
	return array(
		'facebook' => __( 'ফেসবুক বিজ্ঞাপন', 'sojib68' ),
		'website'  => __( 'ওয়েবসাইট', 'sojib68' ),
		'walkin'   => __( 'সরাসরি অফিসে এসেছেন', 'sojib68' ),
		'referral' => __( 'পরিচিত কারো মাধ্যমে', 'sojib68' ),
		'other'    => __( 'অন্যান্য', 'sojib68' ),
	);
}

function sojib68_crm_lead_statuses() {
	return array(
		'new'       => __( 'নতুন', 'sojib68' ),
		'contacted' => __( 'যোগাযোগ হয়েছে', 'sojib68' ),
		'follow_up' => __( 'ফলোআপ প্রয়োজন', 'sojib68' ),
		'converted' => __( 'ক্লায়েন্ট হয়েছেন', 'sojib68' ),
		'lost'      => __( 'আগ্রহী নন', 'sojib68' ),
	);
}

function sojib68_crm_sanitize_source( $source ) {
	$sources = sojib68_crm_lead_sources();
	return isset( $sources[ $source ] ) ? $source : 'other';
}

function sojib68_crm_sanitize_status( $status ) {
	$statuses = sojib68_crm_lead_statuses();
	return isset( $statuses[ $status ] ) ? $status : 'new';
}

/**
 * Saves a lead (insert when $id is 0, update otherwise). $data keys:
 * name, phone, email, source, status, notes.
 *
 * @return int|WP_Error Lead ID on success.
 */
function sojib68_crm_lead_save( $data, $id = 0 ) {
	global $wpdb;
	$table = sojib68_crm_table();

	$name  = isset( $data['name'] ) ? sanitize_text_field( $data['name'] ) : '';
	$phone = isset( $data['phone'] ) ? sanitize_text_field( $data['phone'] ) : '';

	if ( '' === $name && '' === $phone ) {
		return new WP_Error( 'crm_missing_fields', __( 'নাম অথবা মোবাইল নম্বর — অন্তত একটি দিতে হবে।', 'sojib68' ) );
	}

	$row = array(
		'name'       => $name,
		'phone'      => $phone,
		'email'      => isset( $data['email'] ) ? sanitize_email( $data['email'] ) : '',
		'source'     => sojib68_crm_sanitize_source( isset( $data['source'] ) ? $data['source'] : '' ),
		'status'     => sojib68_crm_sanitize_status( isset( $data['status'] ) ? $data['status'] : 'new' ),
		'notes'      => isset( $data['notes'] ) ? sanitize_textarea_field( $data['notes'] ) : '',
		'updated_at' => current_time( 'mysql' ),
	);

	if ( $id > 0 ) {
		$wpdb->update( $table, $row, array( 'id' => $id ) );
		return (int) $id;
	}

	$row['created_at'] = current_time( 'mysql' );
	$wpdb->insert( $table, $row );
	return (int) $wpdb->insert_id;
}

function sojib68_crm_lead_get( $id ) {
	global $wpdb;
	$table = sojib68_crm_table();
	return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", (int) $id ), ARRAY_A );
}

function sojib68_crm_lead_update_status( $id, $status ) {
	global $wpdb;
	$table = sojib68_crm_table();
	return $wpdb->update(
		$table,
		array(
			'status'     => sojib68_crm_sanitize_status( $status ),
			'updated_at' => current_time( 'mysql' ),
		),
		array( 'id' => (int) $id )
	);
}

function sojib68_crm_lead_delete( $id ) {
	global $wpdb;
	$table = sojib68_crm_table();
	return $wpdb->delete( $table, array( 'id' => (int) $id ) );
}

/**
 * $args: status, source, q, page, per_page.
 */
function sojib68_crm_leads_search( $args = array() ) {
	global $wpdb;
	$table = sojib68_crm_table();

	$where  = array( '1=1' );
	$params = array();

	if ( ! empty( $args['status'] ) ) {
		$where[]  = 'status = %s';
		$params[] = $args['status'];
	}
	if ( ! empty( $args['source'] ) ) {
		$where[]  = 'source = %s';
		$params[] = $args['source'];
	}
	if ( ! empty( $args['q'] ) ) {
		$like     = '%' . $wpdb->esc_like( $args['q'] ) . '%';
		$where[]  = '(name LIKE %s OR phone LIKE %s OR email LIKE %s)';
		$params[] = $like;
		$params[] = $like;
		$params[] = $like;
	}

	$per_page = isset( $args['per_page'] ) ? (int) $args['per_page'] : 30;
	$page     = isset( $args['page'] ) ? max( 1, (int) $args['page'] ) : 1;
	$offset   = ( $page - 1 ) * $per_page;

	$where_sql = implode( ' AND ', $where );

	$count_sql = "SELECT COUNT(*) FROM {$table} WHERE {$where_sql}";
	$total     = $params ? (int) $wpdb->get_var( $wpdb->prepare( $count_sql, $params ) ) : (int) $wpdb->get_var( $count_sql );

	$list_sql    = "SELECT * FROM {$table} WHERE {$where_sql} ORDER BY created_at DESC LIMIT %d OFFSET %d";
	$list_params = array_merge( $params, array( $per_page, $offset ) );
	$rows        = $wpdb->get_results( $wpdb->prepare( $list_sql, $list_params ), ARRAY_A );

	return array(
		'rows'     => $rows ? $rows : array(),
		'total'    => $total,
		'page'     => $page,
		'per_page' => $per_page,
	);
}

function sojib68_crm_dashboard_stats() {
	global $wpdb;
	$table = sojib68_crm_table();

	$stats = array(
		'total'      => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" ),
		'today'      => (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE DATE(created_at) = %s", current_time( 'Y-m-d' ) ) ),
		'this_month' => (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE DATE_FORMAT(created_at, '%%Y-%%m') = %s", current_time( 'Y-m' ) ) ),
		'by_status'  => array(),
		'by_source'  => array(),
	);

	foreach ( array_keys( sojib68_crm_lead_statuses() ) as $status ) {
		$stats['by_status'][ $status ] = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE status = %s", $status ) );
	}
	foreach ( array_keys( sojib68_crm_lead_sources() ) as $source ) {
		$stats['by_source'][ $source ] = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE source = %s", $source ) );
	}

	return $stats;
}
