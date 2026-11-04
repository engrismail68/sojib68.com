<?php
/**
 * দলিল এন্ট্রি (office) ↔ ক্লায়েন্ট ডেস্ক (CRM).
 *
 * Every deed saved in the office form becomes one "দলিল রেজিস্ট্রেশন" work in the CRM
 * (crm_works.deed_id), so it shows up on the CRM dashboard card and in "সব কাজ". The accounts
 * come from the office form only:
 *   - মোট টাকা (client_total_due)  -> the work's agreed amount (বকেয়া / প্রফিট are worked out from it)
 *   - each আদায় (receipt)         -> a CRM cash entry "in"
 *   - each খরচ (expense)           -> a CRM cash entry "out"
 * Those cash entries are tagged source_ref "deed:…" and rebuilt on every save, so editing the
 * deed is the one place to fix a number. Cash entries typed by hand in the CRM are left alone.
 *
 * Status: মূল দলিলের অবস্থা in the office form <-> the CRM work status.
 *   রেজিস্ট্রেশন অফিস থেকে এখনো আসেনি = চলমান, আমাদের কাছে আছে = ডেলিভারির জন্য প্রস্তুত,
 *   ডেলিভারি দেওয়া হয়েছে = ডেলিভারি সম্পন্ন. Changing it on either side updates the other.
 *   CRM-only states (পেন্ডিং, বাতিল) are kept when the office form is saved.
 * পুরাতন হিসাব (legacy notebook) deeds are not mirrored — they are not live work.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'SOJIB68_CRM_DEED_TYPE', 'deed_registration' );

/** The CRM tables are on the version that has deed_id / source_ref (else do nothing). */
function sojib68_crm_deed_sync_ready() {
	return function_exists( 'sojib68_crm_works_table' )
		&& function_exists( 'sojib68_deeds_table' )
		&& defined( 'SOJIB68_CRM_DB_VERSION' )
		&& get_option( 'sojib68_crm_db_version' ) === SOJIB68_CRM_DB_VERSION;
}

/** The CRM work linked to a deed, trashed or not (array), or null. */
function sojib68_crm_deed_work( $deed_id ) {
	global $wpdb;
	$table = sojib68_crm_works_table();
	return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE deed_id = %d ORDER BY id ASC LIMIT 1", (int) $deed_id ), ARRAY_A );
}

/** Office মূল দলিলের অবস্থা -> CRM work status. */
function sojib68_crm_deed_status_from_office( $original_status ) {
	switch ( $original_status ) {
		case 'delivered':
			return 'delivered';
		case 'in_office':
			return 'ready';
		default:
			return 'running';
	}
}

/**
 * Create / refresh the CRM work (and its cash entries) for one deed.
 *
 * @return int|null The CRM work id.
 */
function sojib68_crm_sync_deed( $deed_id ) {
	global $wpdb;

	if ( ! sojib68_crm_deed_sync_ready() || ! empty( $GLOBALS['sojib68_deed_sync_busy'] ) ) {
		return null;
	}
	$deed_id = (int) $deed_id;
	if ( ! $deed_id ) {
		return null;
	}

	$GLOBALS['sojib68_deed_sync_busy'] = true;

	$deeds = sojib68_deeds_table( 'deeds' );
	$deed  = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$deeds} WHERE id = %d", $deed_id ) );
	$work  = sojib68_crm_deed_work( $deed_id );
	$works = sojib68_crm_works_table();
	$now   = current_time( 'mysql' );

	// Gone, in the recycle bin, or an old-notebook (পুরাতন হিসাব) deed: not live work.
	if ( ! $deed || ! empty( $deed->deleted_at ) || ! empty( $deed->is_legacy ) ) {
		if ( $work && empty( $work['deleted_at'] ) ) {
			$wpdb->update( $works, array( 'deleted_at' => $now, 'deleted_by' => get_current_user_id() ), array( 'id' => (int) $work['id'] ) );
		}
		$GLOBALS['sojib68_deed_sync_busy'] = false;
		return $work ? (int) $work['id'] : null;
	}

	$donors   = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . sojib68_deeds_table( 'deed_donors' ) . ' WHERE deed_id = %d ORDER BY sort_order', $deed_id ) );
	$grantees = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . sojib68_deeds_table( 'deed_grantees' ) . ' WHERE deed_id = %d ORDER BY sort_order', $deed_id ) );
	$lands    = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . sojib68_deeds_table( 'deed_lands' ) . ' WHERE deed_id = %d ORDER BY sort_order', $deed_id ) );
	$receipts = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . sojib68_deeds_table( 'deed_receipts' ) . ' WHERE deed_id = %d ORDER BY sort_order', $deed_id ) );
	$expenses = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . sojib68_deeds_table( 'deed_expenses' ) . ' WHERE deed_id = %d ORDER BY sort_order', $deed_id ) );

	// The client: the first named গ্রহীতা, else the first named দাতা.
	$name  = '';
	$phone = '';
	foreach ( array_merge( (array) $grantees, (array) $donors ) as $party ) {
		if ( '' === $name && ! empty( $party->name ) ) {
			$name = (string) $party->name;
		}
		if ( '' === $phone && ! empty( $party->mobile ) ) {
			$phone = (string) $party->mobile;
		}
	}
	if ( '' === $name ) {
		$name = ! empty( $deed->deed_no ) ? 'দলিল নং ' . $deed->deed_no : 'দলিল #' . $deed_id;
	}

	$mouza = '';
	foreach ( (array) $lands as $land ) {
		if ( ! empty( $land->mouza ) ) {
			$mouza = (string) $land->mouza;
			break;
		}
	}

	$received = ! empty( $deed->reg_date ) ? $deed->reg_date : substr( (string) $deed->created_at, 0, 10 );
	$status   = sojib68_crm_deed_status_from_office( $deed->original_status );
	// পেন্ডিং / বাতিল exist only in the CRM: an office save must not undo them.
	if ( 'running' === $status && $work && in_array( $work['status'], array( 'pending', 'cancelled' ), true ) ) {
		$status = $work['status'];
	}

	$fields = array(
		'customer_name'      => mb_substr( $name, 0, 190 ),
		'customer_phone'     => mb_substr( $phone, 0, 30 ),
		'received_date'      => $received ? $received : current_time( 'Y-m-d' ),
		'mouza'              => mb_substr( $mouza, 0, 190 ),
		'application_no'     => mb_substr( (string) $deed->deed_no, 0, 100 ),
		'agreed_poysha'      => null === $deed->client_total_due_poysha ? null : (int) $deed->client_total_due_poysha,
		'status'             => $status,
		'delivery_status'    => 'delivered' === $deed->original_status ? 'delivered' : ( 'in_office' === $deed->original_status ? 'ready' : 'not_ready' ),
		'delivery_date'      => 'delivered' === $deed->original_status && ! empty( $deed->original_delivered_date ) ? $deed->original_delivered_date : null,
		'delivery_recipient' => 'delivered' === $deed->original_status ? mb_substr( (string) $deed->original_delivered_to, 0, 190 ) : '',
		'updated_at'         => $now,
	);

	if ( $work ) {
		if ( $work['status'] !== $fields['status'] ) {
			$fields['stage_changed_at'] = $now;
		}
		$fields['deleted_at'] = null; // back from the recycle bin along with the deed
		$fields['deleted_by'] = null;
		$wpdb->update( $works, $fields, array( 'id' => (int) $work['id'] ) );
		$work_id = (int) $work['id'];
	} else {
		$fields += array(
			'deed_id'          => $deed_id,
			'work_type'        => SOJIB68_CRM_DEED_TYPE,
			'work_code'        => '',
			'stage'            => '',
			'stage_changed_at' => $now,
			'created_at'       => $now,
		);
		$wpdb->insert( $works, $fields );
		$work_id = (int) $wpdb->insert_id;
		if ( $work_id ) {
			$wpdb->update( $works, array( 'work_code' => sojib68_crm_next_work_code( $work_id ) ), array( 'id' => $work_id ) );
		}
	}

	if ( $work_id ) {
		// Rebuild the money entries that come from the office form.
		$ledger = sojib68_crm_cash_table();
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$ledger} WHERE work_id = %d AND source_ref LIKE %s", $work_id, 'deed:%' ) );

		foreach ( (array) $receipts as $r ) {
			if ( (int) $r->amount_poysha <= 0 ) {
				continue;
			}
			$wpdb->insert( $ledger, array(
				'category'      => SOJIB68_CRM_DEED_TYPE,
				'direction'     => 'in',
				'amount_poysha' => (int) $r->amount_poysha,
				'party'         => mb_substr( $name, 0, 190 ),
				'note'          => mb_substr( 'দলিল আদায় (অফিস ফর্ম)' . ( ! empty( $r->method ) ? ' — ' . $r->method : '' ), 0, 255 ),
				'txn_date'      => ! empty( $r->received_date ) ? $r->received_date : current_time( 'Y-m-d' ),
				'work_id'       => $work_id,
				'source_ref'    => 'deed:r' . (int) $r->id,
				'created_by'    => get_current_user_id(),
				'created_at'    => $now,
			) );
		}
		foreach ( (array) $expenses as $e ) {
			if ( (int) $e->amount_poysha <= 0 ) {
				continue;
			}
			$label = trim( (string) $e->category );
			$wpdb->insert( $ledger, array(
				'category'      => SOJIB68_CRM_DEED_TYPE,
				'direction'     => 'out',
				'amount_poysha' => (int) $e->amount_poysha,
				'party'         => '',
				'note'          => mb_substr( 'দলিল খরচ (অফিস ফর্ম)' . ( '' !== $label ? ' — ' . $label : '' ), 0, 255 ),
				'txn_date'      => ! empty( $e->paid_date ) ? $e->paid_date : $received,
				'work_id'       => $work_id,
				'source_ref'    => 'deed:e' . (int) $e->id,
				'created_by'    => get_current_user_id(),
				'created_at'    => $now,
			) );
		}
	}

	$GLOBALS['sojib68_deed_sync_busy'] = false;
	return $work_id ? $work_id : null;
}

function sojib68_crm_deed_changed_hook( $deed_id ) {
	sojib68_crm_sync_deed( $deed_id );
}
add_action( 'sojib68_deed_changed', 'sojib68_crm_deed_changed_hook' );
add_action( 'sojib68_deed_trashed', 'sojib68_crm_deed_changed_hook' );

/** The deed is erased for good: its CRM work and the money entries go with it. */
function sojib68_crm_deed_purged_hook( $deed_id ) {
	global $wpdb;
	if ( ! sojib68_crm_deed_sync_ready() ) {
		return;
	}
	$work = sojib68_crm_deed_work( $deed_id );
	if ( $work ) {
		$wpdb->delete( sojib68_crm_cash_table(), array( 'work_id' => (int) $work['id'] ) );
		$wpdb->delete( sojib68_crm_works_table(), array( 'id' => (int) $work['id'] ) );
	}
}
add_action( 'sojib68_deed_purged', 'sojib68_crm_deed_purged_hook' );

/**
 * A status change made in the CRM goes back to the office record (মূল দলিলের অবস্থা).
 * পেন্ডিং and বাতিল have no office equivalent and are left in the CRM only.
 */
function sojib68_crm_work_saved_hook( $work_id ) {
	global $wpdb;

	if ( ! sojib68_crm_deed_sync_ready() || ! empty( $GLOBALS['sojib68_deed_sync_busy'] ) ) {
		return;
	}
	$work = sojib68_crm_work_get( $work_id );
	if ( ! $work || empty( $work['deed_id'] ) ) {
		return;
	}

	$deeds = sojib68_deeds_table( 'deeds' );
	$deed  = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$deeds} WHERE id = %d AND deleted_at IS NULL", (int) $work['deed_id'] ) );
	if ( ! $deed ) {
		return;
	}

	$target = null;
	switch ( $work['status'] ) {
		case 'delivered':
			$target = 'delivered';
			break;
		case 'ready':
			$target = 'in_office';
			break;
		case 'running':
		case 'pending':
			$target = 'not_received';
			break;
	}
	if ( null === $target || $target === $deed->original_status ) {
		return;
	}

	$update = array( 'original_status' => $target, 'updated_at' => current_time( 'mysql' ), 'updated_by' => get_current_user_id() );
	if ( 'delivered' === $target ) {
		$update['original_delivered_date'] = ! empty( $work['delivery_date'] ) ? $work['delivery_date'] : current_time( 'Y-m-d' );
		if ( ! empty( $work['delivery_recipient'] ) ) {
			$update['original_delivered_to'] = $work['delivery_recipient'];
		}
	}
	if ( 'in_office' === $target && empty( $deed->original_received_date ) ) {
		$update['original_received_date'] = current_time( 'Y-m-d' );
	}

	$GLOBALS['sojib68_deed_sync_busy'] = true;
	$wpdb->update( $deeds, $update, array( 'id' => (int) $deed->id ) );
	if ( function_exists( 'sojib68_deed_audit_log' ) ) {
		sojib68_deed_audit_log( (int) $deed->id, get_current_user_id(), 'update', 'CRM থেকে মূল দলিলের অবস্থা বদলানো হয়েছে' );
	}
	$GLOBALS['sojib68_deed_sync_busy'] = false;
}
add_action( 'sojib68_crm_work_saved', 'sojib68_crm_work_saved_hook' );

/**
 * One-off helper: bring the office's existing deeds into the CRM (those that are not there yet).
 *
 * @return int How many works were created.
 */
function sojib68_crm_backfill_deeds( $limit = 500 ) {
	global $wpdb;
	if ( ! sojib68_crm_deed_sync_ready() ) {
		return 0;
	}
	$deeds = sojib68_deeds_table( 'deeds' );
	$works = sojib68_crm_works_table();
	$ids   = $wpdb->get_col( $wpdb->prepare(
		"SELECT d.id FROM {$deeds} d LEFT JOIN {$works} w ON w.deed_id = d.id
		 WHERE d.deleted_at IS NULL AND d.is_legacy = 0 AND w.id IS NULL ORDER BY d.id ASC LIMIT %d",
		(int) $limit
	) );
	$made = 0;
	foreach ( (array) $ids as $id ) {
		if ( sojib68_crm_sync_deed( (int) $id ) ) {
			$made++;
		}
	}
	return $made;
}

/** Admin-only button on the CRM's দলিল রেজিস্ট্রেশন page: bring the old deeds in. */
function sojib68_crm_handle_backfill_deeds() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'এই কাজের অনুমতি নেই।', 'sojib68' ), 403 );
	}
	check_admin_referer( 'sojib68_crm_backfill_deeds' );
	$made = sojib68_crm_backfill_deeds();
	wp_safe_redirect( add_query_arg( array( 'backfilled' => (int) $made ), home_url( '/' . ( defined( 'SOJIB68_CRM_BASE' ) ? SOJIB68_CRM_BASE : 'crm' ) . '/' . SOJIB68_CRM_DEED_TYPE . '-entry' ) ) );
	exit;
}
add_action( 'admin_post_sojib68_crm_backfill_deeds', 'sojib68_crm_handle_backfill_deeds' );
