<?php
/**
 * CRM business logic (leads, follow-ups, activity log, works). No HTML
 * in this file.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* ---------------------------------------------------------------------
 * Vocabularies
 * ------------------------------------------------------------------ */

function sojib68_crm_lead_sources() {
	return array(
		'facebook_internet' => __( 'Facebook / ইন্টারনেট', 'sojib68' ),
		'whatsapp'          => __( 'WhatsApp', 'sojib68' ),
		'messenger'         => __( 'Messenger', 'sojib68' ),
		'phone'             => __( 'ফোন কল', 'sojib68' ),
		'walkin'            => __( 'সরাসরি অফিসে এসেছেন', 'sojib68' ),
		'referral'          => __( 'রেফারেন্স / অন্যান্য', 'sojib68' ),
	);
}

function sojib68_crm_service_types() {
	return array(
		'namjari'            => __( 'নামজারি', 'sojib68' ),
		'deed_registration'  => __( 'দলিল রেজিস্ট্রেশন', 'sojib68' ),
		'deed_search'        => __( 'দলিল অনুসন্ধান ও সার্টিফাইড কপি', 'sojib68' ),
		'land_measurement'   => __( 'জমি পরিমাপ', 'sojib68' ),
		'other_land_service' => __( 'অন্যান্য ভূমিসেবা', 'sojib68' ),
		'other_it_service'   => __( 'অন্যান্য / IT সেবা', 'sojib68' ),
	);
}

function sojib68_crm_lead_statuses() {
	return array(
		'new'                 => __( 'নতুন', 'sojib68' ),
		'contacted'           => __( 'যোগাযোগ হয়েছে', 'sojib68' ),
		'need_understood'     => __( 'প্রয়োজন বোঝা হয়েছে', 'sojib68' ),
		'proposal_discussed'  => __( 'খরচ/প্রস্তাব আলোচনা', 'sojib68' ),
		'confirmed'           => __( 'কাজ নিশ্চিত', 'sojib68' ),
		'later'               => __( 'পরে যোগাযোগ', 'sojib68' ),
		'unreachable'         => __( 'যোগাযোগ পাওয়া যায়নি', 'sojib68' ),
		'cancelled'           => __( 'বাতিল', 'sojib68' ),
	);
}

function sojib68_crm_work_statuses() {
	return array(
		'running'   => __( 'চলমান', 'sojib68' ),
		'pending'   => __( 'পেন্ডিং', 'sojib68' ),
		'ready'     => __( 'ডেলিভারির জন্য প্রস্তুত', 'sojib68' ),
		'delivered' => __( 'ডেলিভারি সম্পন্ন', 'sojib68' ),
		'cancelled' => __( 'বাতিল', 'sojib68' ),
	);
}

function sojib68_crm_delivery_statuses() {
	return array(
		'not_ready' => __( 'প্রস্তুত নয়', 'sojib68' ),
		'ready'     => __( 'ডেলিভারির জন্য প্রস্তুত', 'sojib68' ),
		'delivered' => __( 'ডেলিভারি সম্পন্ন', 'sojib68' ),
	);
}

function sojib68_crm_sanitize_from_list( $value, $list, $default ) {
	return isset( $list[ $value ] ) ? $value : $default;
}

/* ---------------------------------------------------------------------
 * Small helpers
 * ------------------------------------------------------------------ */

function sojib68_crm_bn_to_en_digits( $s ) {
	static $map = array( '০' => '0', '১' => '1', '২' => '2', '৩' => '3', '৪' => '4', '৫' => '5', '৬' => '6', '৭' => '7', '৮' => '8', '৯' => '9' );
	return strtr( (string) $s, $map );
}

function sojib68_crm_is_admin() {
	return current_user_can( 'manage_options' );
}

/** Non-admin staff only ever see their own assigned leads/works. Admin sees everything (null = no restriction). */
function sojib68_crm_current_user_scope() {
	if ( sojib68_crm_is_admin() ) {
		return null;
	}
	return get_current_user_id();
}

function sojib68_crm_can_view_finance() {
	return sojib68_crm_is_admin();
}

/** Staff accounts with CRM access, for the "দায়িত্বপ্রাপ্ত ব্যক্তি" dropdown. */
function sojib68_crm_assignable_users() {
	$users = get_users(
		array(
			'role__in' => array( 'administrator', 'sojib_crm_staff' ),
			'orderby'  => 'display_name',
			'fields'   => array( 'ID', 'display_name' ),
		)
	);
	$out = array();
	foreach ( $users as $u ) {
		$out[ (int) $u->ID ] = $u->display_name;
	}
	return $out;
}

function sojib68_crm_next_lead_code( $id ) {
	return 'LD-' . str_pad( (int) $id, 5, '0', STR_PAD_LEFT );
}

function sojib68_crm_next_work_code( $id ) {
	return 'WK-' . str_pad( (int) $id, 5, '0', STR_PAD_LEFT );
}

/* ---------------------------------------------------------------------
 * Activity log
 * ------------------------------------------------------------------ */

function sojib68_crm_lead_log( $lead_id, $type, $message ) {
	global $wpdb;
	$wpdb->insert(
		sojib68_crm_activity_table(),
		array(
			'lead_id'    => (int) $lead_id,
			'user_id'    => get_current_user_id(),
			'type'       => sanitize_key( $type ),
			'message'    => $message,
			'created_at' => current_time( 'mysql' ),
		)
	);
}

function sojib68_crm_lead_activity( $lead_id ) {
	global $wpdb;
	$table = sojib68_crm_activity_table();
	return $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table} WHERE lead_id = %d ORDER BY created_at DESC, id DESC", (int) $lead_id ), ARRAY_A );
}

/* ---------------------------------------------------------------------
 * Follow-ups
 * ------------------------------------------------------------------ */

function sojib68_crm_followup_add( $lead_id, $due_at, $note = '' ) {
	global $wpdb;
	$lead_id = (int) $lead_id;

	$wpdb->insert(
		sojib68_crm_followups_table(),
		array(
			'lead_id'    => $lead_id,
			'due_at'     => $due_at,
			'note'       => sanitize_text_field( $note ),
			'created_by' => get_current_user_id(),
			'created_at' => current_time( 'mysql' ),
		)
	);
	// Captured immediately — sojib68_crm_lead_log() below performs its own
	// insert (into the activity table) which would otherwise overwrite
	// $wpdb->insert_id before we get a chance to read it.
	$followup_id = (int) $wpdb->insert_id;

	$wpdb->update(
		sojib68_crm_table(),
		array( 'next_followup_at' => $due_at, 'updated_at' => current_time( 'mysql' ) ),
		array( 'id' => $lead_id )
	);

	sojib68_crm_lead_log( $lead_id, 'followup_added', sprintf( /* translators: %s: date/time */ __( 'ফলোআপ নির্ধারণ করা হয়েছে: %s', 'sojib68' ), mysql2date( 'd M, Y h:i A', $due_at ) ) );

	return $followup_id;
}

function sojib68_crm_followup_complete( $followup_id ) {
	global $wpdb;
	$table = sojib68_crm_followups_table();

	$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", (int) $followup_id ), ARRAY_A );
	if ( ! $row ) {
		return false;
	}

	$wpdb->update( $table, array( 'completed_at' => current_time( 'mysql' ) ), array( 'id' => (int) $followup_id ) );
	sojib68_crm_lead_log( $row['lead_id'], 'followup_completed', __( 'ফলোআপ সম্পন্ন হিসেবে চিহ্নিত করা হয়েছে।', 'sojib68' ) );

	// Clear next_followup_at on the lead if this was its pending one.
	global $wpdb;
	$still_open = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE lead_id = %d AND completed_at IS NULL", (int) $row['lead_id'] ) );
	if ( ! $still_open ) {
		$wpdb->update( sojib68_crm_table(), array( 'next_followup_at' => null ), array( 'id' => (int) $row['lead_id'] ) );
	}

	return true;
}

function sojib68_crm_lead_followups( $lead_id ) {
	global $wpdb;
	$table = sojib68_crm_followups_table();
	return $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table} WHERE lead_id = %d ORDER BY due_at DESC", (int) $lead_id ), ARRAY_A );
}

/* ---------------------------------------------------------------------
 * Leads
 * ------------------------------------------------------------------ */

/**
 * $data keys: name, phone, email, address, source, referred_by,
 * service_type, details, notes, assigned_to, status, next_followup_at.
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

	$assigned_to = isset( $data['assigned_to'] ) ? (int) $data['assigned_to'] : 0;
	$assignable  = sojib68_crm_assignable_users();
	if ( $assigned_to && ! isset( $assignable[ $assigned_to ] ) ) {
		$assigned_to = 0;
	}

	$next_followup_at = '';
	if ( ! empty( $data['next_followup_at'] ) ) {
		$ts = strtotime( $data['next_followup_at'] );
		if ( $ts ) {
			$next_followup_at = gmdate( 'Y-m-d H:i:s', $ts );
		}
	}

	$row = array(
		'name'             => $name,
		'phone'            => $phone,
		'email'            => isset( $data['email'] ) ? sanitize_email( $data['email'] ) : '',
		'address'          => isset( $data['address'] ) ? sanitize_textarea_field( $data['address'] ) : '',
		'source'           => sojib68_crm_sanitize_from_list( isset( $data['source'] ) ? $data['source'] : '', sojib68_crm_lead_sources(), 'facebook_internet' ),
		'referred_by'      => isset( $data['referred_by'] ) ? sanitize_text_field( $data['referred_by'] ) : '',
		'service_type'     => sojib68_crm_sanitize_from_list( isset( $data['service_type'] ) ? $data['service_type'] : '', sojib68_crm_service_types(), 'other_land_service' ),
		'details'          => isset( $data['details'] ) ? sanitize_textarea_field( $data['details'] ) : '',
		'notes'            => isset( $data['notes'] ) ? sanitize_textarea_field( $data['notes'] ) : '',
		'assigned_to'      => $assigned_to ? $assigned_to : null,
		'status'           => sojib68_crm_sanitize_from_list( isset( $data['status'] ) ? $data['status'] : 'new', sojib68_crm_lead_statuses(), 'new' ),
		'next_followup_at' => $next_followup_at ? $next_followup_at : null,
		'updated_at'       => current_time( 'mysql' ),
	);

	if ( $id > 0 ) {
		$before = sojib68_crm_lead_get( $id );
		$wpdb->update( $table, $row, array( 'id' => $id ) );

		if ( $before && $before['status'] !== $row['status'] ) {
			$statuses = sojib68_crm_lead_statuses();
			sojib68_crm_lead_log( $id, 'status_change', sprintf( /* translators: 1: old status, 2: new status */ __( 'অবস্থা পরিবর্তন: %1$s থেকে %2$s', 'sojib68' ), $statuses[ $before['status'] ] ?? $before['status'], $statuses[ $row['status'] ] ?? $row['status'] ) );
		}
		if ( $before && (int) $before['assigned_to'] !== $assigned_to ) {
			$name_new = $assigned_to ? ( $assignable[ $assigned_to ] ?? '' ) : __( '(কেউ নয়)', 'sojib68' );
			sojib68_crm_lead_log( $id, 'assigned', sprintf( /* translators: %s: staff name */ __( 'দায়িত্বপ্রাপ্ত করা হয়েছে: %s', 'sojib68' ), $name_new ) );
		}

		return (int) $id;
	}

	$row['created_at'] = current_time( 'mysql' );
	$row['created_by'] = get_current_user_id();
	$row['lead_code']  = ''; // filled in right after insert, once we have the id.

	$wpdb->insert( $table, $row );
	$new_id = (int) $wpdb->insert_id;

	$wpdb->update( $table, array( 'lead_code' => sojib68_crm_next_lead_code( $new_id ) ), array( 'id' => $new_id ) );
	sojib68_crm_lead_log( $new_id, 'created', __( 'লিড তৈরি করা হয়েছে।', 'sojib68' ) );

	return $new_id;
}

function sojib68_crm_lead_get( $id ) {
	global $wpdb;
	$table = sojib68_crm_table();
	return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", (int) $id ), ARRAY_A );
}

function sojib68_crm_find_leads_by_phone( $phone, $exclude_id = 0 ) {
	global $wpdb;
	$phone = trim( (string) $phone );
	if ( '' === $phone ) {
		return array();
	}
	$table = sojib68_crm_table();
	return $wpdb->get_results(
		$wpdb->prepare(
			"SELECT id, lead_code, name, phone, status, created_at FROM {$table} WHERE phone = %s AND id != %d ORDER BY created_at DESC",
			$phone,
			(int) $exclude_id
		),
		ARRAY_A
	);
}

function sojib68_crm_lead_delete( $id ) {
	global $wpdb;
	return $wpdb->delete( sojib68_crm_table(), array( 'id' => (int) $id ) );
}

/**
 * $args: status, source, service_type, assigned_to, q, date_from,
 * date_to, scope_user_id (non-null restricts to that user's assigned
 * leads — used for non-admin staff), page, per_page.
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
	if ( ! empty( $args['service_type'] ) ) {
		$where[]  = 'service_type = %s';
		$params[] = $args['service_type'];
	}
	if ( ! empty( $args['assigned_to'] ) ) {
		$where[]  = 'assigned_to = %d';
		$params[] = (int) $args['assigned_to'];
	} elseif ( isset( $args['scope_user_id'] ) && null !== $args['scope_user_id'] ) {
		$where[]  = 'assigned_to = %d';
		$params[] = (int) $args['scope_user_id'];
	}
	if ( ! empty( $args['q'] ) ) {
		$q        = sojib68_crm_bn_to_en_digits( $args['q'] );
		$like     = '%' . $wpdb->esc_like( $q ) . '%';
		$where[]  = '(name LIKE %s OR phone LIKE %s OR email LIKE %s OR lead_code LIKE %s)';
		$params[] = $like;
		$params[] = $like;
		$params[] = $like;
		$params[] = $like;
	}
	if ( ! empty( $args['date_from'] ) ) {
		$where[]  = 'DATE(created_at) >= %s';
		$params[] = $args['date_from'];
	}
	if ( ! empty( $args['date_to'] ) ) {
		$where[]  = 'DATE(created_at) <= %s';
		$params[] = $args['date_to'];
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

/**
 * Lead summary cards (status-based), source cards, and work-type cards
 * for the dashboard. $scope_user_id restricts to one staff member's
 * assigned leads/works (null = everyone, used for admins).
 */
function sojib68_crm_dashboard_stats( $scope_user_id = null ) {
	global $wpdb;
	$table = sojib68_crm_table();

	$scope_sql    = '';
	$scope_params = array();
	if ( null !== $scope_user_id ) {
		$scope_sql    = ' AND assigned_to = %d';
		$scope_params = array( (int) $scope_user_id );
	}

	$q = function ( $sql, $params = array() ) use ( $wpdb ) {
		return $params ? (int) $wpdb->get_var( $wpdb->prepare( $sql, $params ) ) : (int) $wpdb->get_var( $sql );
	};

	$today = current_time( 'Y-m-d' );

	$stats = array(
		'total'            => $q( "SELECT COUNT(*) FROM {$table} WHERE 1=1{$scope_sql}", $scope_params ),
		'new'              => $q( "SELECT COUNT(*) FROM {$table} WHERE status = 'new'{$scope_sql}", $scope_params ),
		'today'            => $q( "SELECT COUNT(*) FROM {$table} WHERE DATE(created_at) = %s{$scope_sql}", array_merge( array( $today ), $scope_params ) ),
		'today_followup'   => $q( "SELECT COUNT(*) FROM {$table} WHERE next_followup_at IS NOT NULL AND DATE(next_followup_at) = %s{$scope_sql}", array_merge( array( $today ), $scope_params ) ),
		'overdue_followup' => $q( "SELECT COUNT(*) FROM {$table} WHERE next_followup_at IS NOT NULL AND next_followup_at < %s{$scope_sql}", array_merge( array( current_time( 'mysql' ) ), $scope_params ) ),
		'by_source'        => array(),
		'by_work_type'     => array(),
	);

	foreach ( array_keys( sojib68_crm_lead_sources() ) as $source ) {
		$params = array_merge( array( $source ), $scope_params );
		$stats['by_source'][ $source ] = $q( "SELECT COUNT(*) FROM {$table} WHERE source = %s{$scope_sql}", $params );
	}

	$works_table = sojib68_crm_works_table();
	$work_scope_sql = $scope_sql; // same column name on crm_works.
	foreach ( array_keys( sojib68_crm_service_types() ) as $type ) {
		$params = array_merge( array( $type ), $scope_params );
		$stats['by_work_type'][ $type ] = array(
			'running' => $q( "SELECT COUNT(*) FROM {$works_table} WHERE work_type = %s AND status = 'running'{$work_scope_sql}", $params ),
			'pending' => $q( "SELECT COUNT(*) FROM {$works_table} WHERE work_type = %s AND status = 'pending'{$work_scope_sql}", $params ),
			'ready'   => $q( "SELECT COUNT(*) FROM {$works_table} WHERE work_type = %s AND status = 'ready'{$work_scope_sql}", $params ),
		);
	}

	return $stats;
}

/* ---------------------------------------------------------------------
 * Works (minimal — category-specific fields/forms come later)
 * ------------------------------------------------------------------ */

function sojib68_crm_work_get( $id ) {
	global $wpdb;
	$table = sojib68_crm_works_table();
	return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", (int) $id ), ARRAY_A );
}

function sojib68_crm_lead_active_work( $lead_id ) {
	global $wpdb;
	$table = sojib68_crm_works_table();
	return $wpdb->get_row(
		$wpdb->prepare(
			"SELECT * FROM {$table} WHERE lead_id = %d AND status != 'cancelled' ORDER BY id DESC LIMIT 1",
			(int) $lead_id
		),
		ARRAY_A
	);
}

/**
 * Creates a work record from a confirmed lead. Idempotent — calling it
 * again for the same lead returns the existing work instead of
 * creating a duplicate (guards against a double click).
 */
function sojib68_crm_work_create_from_lead( $lead_id ) {
	$lead = sojib68_crm_lead_get( $lead_id );
	if ( ! $lead ) {
		return new WP_Error( 'crm_lead_not_found', __( 'লিড পাওয়া যায়নি।', 'sojib68' ) );
	}

	$existing = sojib68_crm_lead_active_work( $lead_id );
	if ( $existing ) {
		return (int) $existing['id'];
	}

	global $wpdb;
	$table = sojib68_crm_works_table();

	$row = array(
		'work_code'      => '',
		'lead_id'        => (int) $lead_id,
		'customer_name'  => $lead['name'],
		'customer_phone' => $lead['phone'],
		'work_type'      => $lead['service_type'],
		'received_date'  => current_time( 'Y-m-d' ),
		'referred_by'    => $lead['referred_by'],
		'assigned_to'    => $lead['assigned_to'] ? (int) $lead['assigned_to'] : null,
		'status'         => 'running',
		'created_at'     => current_time( 'mysql' ),
		'updated_at'     => current_time( 'mysql' ),
	);

	$wpdb->insert( $table, $row );
	$id = (int) $wpdb->insert_id;
	$wpdb->update( $table, array( 'work_code' => sojib68_crm_next_work_code( $id ) ), array( 'id' => $id ) );

	sojib68_crm_lead_log( $lead_id, 'work_created', sprintf( /* translators: %s: work code */ __( 'কাজের ফাইল তৈরি হয়েছে: %s', 'sojib68' ), sojib68_crm_next_work_code( $id ) ) );

	return $id;
}

function sojib68_crm_lead_works( $lead_id ) {
	global $wpdb;
	$table = sojib68_crm_works_table();
	return $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table} WHERE lead_id = %d ORDER BY created_at DESC", (int) $lead_id ), ARRAY_A );
}

/**
 * $args: work_type, status, assigned_to, q, scope_user_id, page, per_page.
 */
function sojib68_crm_works_search( $args = array() ) {
	global $wpdb;
	$table = sojib68_crm_works_table();

	$where  = array( '1=1' );
	$params = array();

	if ( ! empty( $args['work_type'] ) ) {
		$where[]  = 'work_type = %s';
		$params[] = $args['work_type'];
	}
	if ( ! empty( $args['status'] ) ) {
		$where[]  = 'status = %s';
		$params[] = $args['status'];
	}
	if ( ! empty( $args['assigned_to'] ) ) {
		$where[]  = 'assigned_to = %d';
		$params[] = (int) $args['assigned_to'];
	} elseif ( isset( $args['scope_user_id'] ) && null !== $args['scope_user_id'] ) {
		$where[]  = 'assigned_to = %d';
		$params[] = (int) $args['scope_user_id'];
	}
	if ( ! empty( $args['q'] ) ) {
		$like     = '%' . $wpdb->esc_like( sojib68_crm_bn_to_en_digits( $args['q'] ) ) . '%';
		$where[]  = '(customer_name LIKE %s OR customer_phone LIKE %s OR work_code LIKE %s)';
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

/* ---------------------------------------------------------------------
 * Cash ledger — category-wise money in/out, since taka collected for
 * one service is often spent on another (e.g. নামজারির টাকা দিয়ে দলিল
 * রেজিস্ট্রেশন করা). Each entry is tagged with the category the money
 * belongs to, independent of which work it was actually spent on, so
 * "কোন ক্যাটাগরির কত আদায়, কত জমা আছে" stays answerable.
 * ------------------------------------------------------------------ */

function sojib68_crm_cash_categories() {
	$categories = sojib68_crm_service_types();
	$categories['general'] = __( 'সাধারণ (নির্দিষ্ট ক্যাটাগরি নয়)', 'sojib68' );
	return $categories;
}

function sojib68_crm_cash_directions() {
	return array(
		'in'  => __( 'আদায় / জমা', 'sojib68' ),
		'out' => __( 'খরচ / উত্তোলন', 'sojib68' ),
	);
}

function sojib68_crm_taka_to_poysha( $taka ) {
	$taka = (float) str_replace( ',', '', (string) $taka );
	return (int) round( $taka * 100 );
}

function sojib68_crm_poysha_to_taka_display( $poysha ) {
	return number_format( ( (int) $poysha ) / 100, 2 );
}

/**
 * $data: category, direction ('in'/'out'), amount (taka), note,
 * txn_date (Y-m-d), work_id (optional).
 *
 * @return int|WP_Error
 */
function sojib68_crm_cash_add( $data ) {
	global $wpdb;

	$amount_poysha = sojib68_crm_taka_to_poysha( isset( $data['amount'] ) ? $data['amount'] : 0 );
	if ( $amount_poysha <= 0 ) {
		return new WP_Error( 'crm_cash_invalid_amount', __( 'সঠিক পরিমাণ টাকা দিন।', 'sojib68' ) );
	}

	$txn_date = ! empty( $data['txn_date'] ) && strtotime( $data['txn_date'] ) ? gmdate( 'Y-m-d', strtotime( $data['txn_date'] ) ) : current_time( 'Y-m-d' );

	$row = array(
		'category'      => sojib68_crm_sanitize_from_list( isset( $data['category'] ) ? $data['category'] : '', sojib68_crm_cash_categories(), 'general' ),
		'direction'     => sojib68_crm_sanitize_from_list( isset( $data['direction'] ) ? $data['direction'] : '', sojib68_crm_cash_directions(), 'in' ),
		'amount_poysha' => $amount_poysha,
		'note'          => isset( $data['note'] ) ? sanitize_text_field( $data['note'] ) : '',
		'txn_date'      => $txn_date,
		'work_id'       => ! empty( $data['work_id'] ) ? (int) $data['work_id'] : null,
		'created_by'    => get_current_user_id(),
		'created_at'    => current_time( 'mysql' ),
	);

	$wpdb->insert( sojib68_crm_cash_table(), $row );
	return (int) $wpdb->insert_id;
}

function sojib68_crm_cash_delete( $id ) {
	global $wpdb;
	return $wpdb->delete( sojib68_crm_cash_table(), array( 'id' => (int) $id ) );
}

/**
 * Per-category in/out/balance (poysha), plus an 'all' total row.
 */
function sojib68_crm_cash_summary() {
	global $wpdb;
	$table = sojib68_crm_cash_table();

	$summary      = array();
	$total_in     = 0;
	$total_out    = 0;

	foreach ( array_keys( sojib68_crm_cash_categories() ) as $cat ) {
		$in  = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COALESCE(SUM(amount_poysha),0) FROM {$table} WHERE category = %s AND direction = 'in'", $cat ) );
		$out = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COALESCE(SUM(amount_poysha),0) FROM {$table} WHERE category = %s AND direction = 'out'", $cat ) );

		$summary[ $cat ] = array(
			'in'      => $in,
			'out'     => $out,
			'balance' => $in - $out,
		);

		$total_in  += $in;
		$total_out += $out;
	}

	$summary['__total__'] = array(
		'in'      => $total_in,
		'out'     => $total_out,
		'balance' => $total_in - $total_out,
	);

	return $summary;
}

function sojib68_crm_cash_entries( $category = '', $limit = 30 ) {
	global $wpdb;
	$table = sojib68_crm_cash_table();

	if ( $category ) {
		return $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table} WHERE category = %s ORDER BY txn_date DESC, id DESC LIMIT %d", $category, (int) $limit ), ARRAY_A );
	}
	return $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table} ORDER BY txn_date DESC, id DESC LIMIT %d", (int) $limit ), ARRAY_A );
}

function sojib68_crm_work_update( $id, $data ) {
	global $wpdb;
	$table = sojib68_crm_works_table();

	$row = array( 'updated_at' => current_time( 'mysql' ) );

	foreach ( array( 'pending_reason', 'next_action', 'file_location', 'delivery_recipient' ) as $f ) {
		if ( isset( $data[ $f ] ) ) {
			$row[ $f ] = sanitize_text_field( $data[ $f ] );
		}
	}
	foreach ( array( 'docs_needed', 'docs_received', 'docs_pending' ) as $f ) {
		if ( isset( $data[ $f ] ) ) {
			$row[ $f ] = sanitize_textarea_field( $data[ $f ] );
		}
	}
	if ( isset( $data['status'] ) ) {
		$row['status'] = sojib68_crm_sanitize_from_list( $data['status'], sojib68_crm_work_statuses(), 'running' );
	}
	if ( isset( $data['delivery_status'] ) ) {
		$row['delivery_status'] = sojib68_crm_sanitize_from_list( $data['delivery_status'], sojib68_crm_delivery_statuses(), 'not_ready' );
	}
	if ( isset( $data['assigned_to'] ) ) {
		$assignable = sojib68_crm_assignable_users();
		$aid        = (int) $data['assigned_to'];
		$row['assigned_to'] = isset( $assignable[ $aid ] ) ? $aid : null;
	}
	if ( ! empty( $data['next_action_date'] ) ) {
		$row['next_action_date'] = $data['next_action_date'];
	}
	if ( ! empty( $data['delivery_date'] ) ) {
		$row['delivery_date'] = $data['delivery_date'];
	}

	return $wpdb->update( $table, $row, array( 'id' => (int) $id ) );
}
