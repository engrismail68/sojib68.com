<?php
/**
 * Core helpers for the deed records: digit normalization, money
 * handling (integer poysha, never float), saved office/type lists,
 * transactional save, search, and audit logging.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* ---------------------------------------------------------------------
 * Digit normalization (so Bengali and English numerals behave
 * identically for storage and search, and leading zeros survive
 * because everything stays a string — never cast to int).
 * ------------------------------------------------------------------ */

function sojib68_bn_to_en_digits( $str ) {
	static $map = array(
		'০' => '0', '১' => '1', '২' => '2', '৩' => '3', '৪' => '4',
		'৫' => '5', '৬' => '6', '৭' => '7', '৮' => '8', '৯' => '9',
	);
	return strtr( (string) $str, $map );
}

/**
 * Normalizes a free-text field (deed no, khatian no, mobile) for
 * storage in a *_normalized column used by search: Bengali digits to
 * English, trims whitespace. The original, as typed, is kept in the
 * main column for display.
 */
function sojib68_normalize_field( $str ) {
	return trim( sojib68_bn_to_en_digits( (string) $str ) );
}

/* ---------------------------------------------------------------------
 * Money: always integer poysha (1 taka = 100 poysha). Never float.
 * ------------------------------------------------------------------ */

/**
 * Parses a user-entered amount (possibly with Bengali digits, commas,
 * a decimal point) into integer poysha. Returns null for blank input
 * — a blank money field means "unknown", not zero.
 */
function sojib68_money_to_poysha( $input ) {
	$input = trim( sojib68_bn_to_en_digits( (string) $input ) );
	$input = str_replace( ',', '', $input );

	if ( '' === $input ) {
		return null;
	}

	if ( ! preg_match( '/^-?\d+(\.\d{1,2})?$/', $input ) ) {
		return null; // Not a parseable number — treated as not provided.
	}

	$parts = explode( '.', $input );
	$taka  = (int) $parts[0];
	$paisa = isset( $parts[1] ) ? str_pad( substr( $parts[1], 0, 2 ), 2, '0' ) : '00';

	$sign = $taka < 0 ? -1 : 1;
	return $sign * ( abs( $taka ) * 100 + (int) $paisa );
}

/**
 * Formats integer poysha back to a "1,234.56" style taka string for
 * display. Returns '' for null (unknown), and explicitly handles 0
 * ("০" entered on purpose) as a real value, not unknown.
 */
function sojib68_poysha_to_display( $poysha ) {
	if ( null === $poysha || '' === $poysha ) {
		return '';
	}
	$poysha = (int) $poysha;
	$taka   = intdiv( abs( $poysha ), 100 );
	$paisa  = abs( $poysha ) % 100;
	$sign   = $poysha < 0 ? '-' : '';
	return $sign . number_format( $taka ) . ( $paisa ? '.' . str_pad( $paisa, 2, '0', STR_PAD_LEFT ) : '' );
}

/* ---------------------------------------------------------------------
 * Saved offices / deed types
 * ------------------------------------------------------------------ */

function sojib68_offices_list() {
	global $wpdb;
	$table = sojib68_deeds_table( 'offices' );
	return $wpdb->get_results( "SELECT id, name FROM $table ORDER BY name ASC" );
}

function sojib68_deed_types_list() {
	global $wpdb;
	$table = sojib68_deeds_table( 'deed_types' );
	return $wpdb->get_results( "SELECT id, name FROM $table ORDER BY name ASC" );
}

function sojib68_office_add_custom( $name ) {
	global $wpdb;
	$name = trim( (string) $name );
	if ( '' === $name ) {
		return false;
	}
	$table = sojib68_deeds_table( 'offices' );
	$wpdb->query( $wpdb->prepare(
		"INSERT IGNORE INTO $table (name, created_at) VALUES (%s, %s)",
		$name,
		current_time( 'mysql' )
	) );
	return $name;
}

function sojib68_deed_type_add_custom( $name ) {
	global $wpdb;
	$name = trim( (string) $name );
	if ( '' === $name ) {
		return false;
	}
	$table = sojib68_deeds_table( 'deed_types' );
	$wpdb->query( $wpdb->prepare(
		"INSERT IGNORE INTO $table (name, created_at) VALUES (%s, %s)",
		$name,
		current_time( 'mysql' )
	) );
	return $name;
}

/**
 * Removes a saved office/deed-type from the dropdown list only — any
 * deed already saved with that office/type keeps its own copy of the
 * text (it isn't a foreign key), so past records are unaffected and
 * the name can always be re-added later via "+ যোগ করুন".
 */
function sojib68_office_delete_custom( $name ) {
	global $wpdb;
	$name = trim( (string) $name );
	if ( '' === $name ) {
		return false;
	}
	$table = sojib68_deeds_table( 'offices' );
	return false !== $wpdb->query( $wpdb->prepare( "DELETE FROM $table WHERE name = %s", $name ) );
}

function sojib68_deed_type_delete_custom( $name ) {
	global $wpdb;
	$name = trim( (string) $name );
	if ( '' === $name ) {
		return false;
	}
	$table = sojib68_deeds_table( 'deed_types' );
	return false !== $wpdb->query( $wpdb->prepare( "DELETE FROM $table WHERE name = %s", $name ) );
}

/* ---------------------------------------------------------------------
 * Audit log
 * ------------------------------------------------------------------ */

function sojib68_deed_audit_log( $deed_id, $user_id, $action, $summary = '' ) {
	global $wpdb;
	$table = sojib68_deeds_table( 'deed_audit' );
	$wpdb->insert( $table, array(
		'deed_id'    => $deed_id,
		'user_id'    => $user_id,
		'action'     => $action,
		'changed_at' => current_time( 'mysql' ),
		'summary'    => $summary,
	) );
}

/* ---------------------------------------------------------------------
 * Double-submit protection
 * ------------------------------------------------------------------ */

function sojib68_deed_token_lookup( $token ) {
	global $wpdb;
	if ( '' === (string) $token ) {
		return null;
	}
	$table = sojib68_deeds_table( 'submission_tokens' );
	$deed_id = $wpdb->get_var( $wpdb->prepare( "SELECT deed_id FROM $table WHERE token = %s", $token ) );
	return $deed_id ? (int) $deed_id : null;
}

function sojib68_deed_token_store( $token, $deed_id ) {
	global $wpdb;
	if ( '' === (string) $token ) {
		return;
	}
	$table = sojib68_deeds_table( 'submission_tokens' );
	$wpdb->query( $wpdb->prepare(
		"INSERT IGNORE INTO $table (token, deed_id, created_at) VALUES (%s, %d, %s)",
		$token,
		$deed_id,
		current_time( 'mysql' )
	) );
}

/* ---------------------------------------------------------------------
 * Duplicate detection (warn, never block, never auto-merge)
 * ------------------------------------------------------------------ */

function sojib68_deed_find_possible_duplicate( $deed_no_normalized, $reg_year, $office, $exclude_id = 0 ) {
	global $wpdb;

	if ( '' === (string) $deed_no_normalized || empty( $reg_year ) || '' === (string) $office ) {
		return null; // Not enough information to meaningfully compare.
	}

	$table = sojib68_deeds_table( 'deeds' );
	$sql   = "SELECT id, deed_no, reg_year, sub_registry_office FROM $table
		WHERE deed_no_normalized = %s AND reg_year = %d AND sub_registry_office = %s
		AND deleted_at IS NULL";
	$args  = array( $deed_no_normalized, $reg_year, $office );

	if ( $exclude_id ) {
		$sql   .= ' AND id != %d';
		$args[] = $exclude_id;
	}

	$sql .= ' LIMIT 1';

	return $wpdb->get_row( $wpdb->prepare( $sql, $args ) );
}

/* ---------------------------------------------------------------------
 * Save (create or update) — one deed plus all of its donors,
 * grantees, lands, receipts and expenses, inside a single
 * transaction: either everything saves, or nothing does.
 * ------------------------------------------------------------------ */

function sojib68_deed_save( array $data, $deed_id, $user_id ) {
	global $wpdb;

	$deeds_table = sojib68_deeds_table( 'deeds' );
	$now         = current_time( 'mysql' );
	$is_update   = ! empty( $deed_id );

	$deed_no            = isset( $data['deed_no'] ) ? trim( (string) $data['deed_no'] ) : '';
	$deed_no_normalized = sojib68_normalize_field( $deed_no );
	$office              = isset( $data['sub_registry_office'] ) ? trim( (string) $data['sub_registry_office'] ) : '';
	$reg_year            = ! empty( $data['reg_year'] ) ? (int) sojib68_bn_to_en_digits( $data['reg_year'] ) : null;
	$reg_date            = ! empty( $data['reg_date'] ) ? $data['reg_date'] : null;

	$deed_row = array(
		'sub_registry_office'      => '' !== $office ? $office : null,
		'reg_date'                 => $reg_date,
		'reg_year'                 => $reg_year,
		'deed_no'                  => '' !== $deed_no ? $deed_no : null,
		'deed_no_normalized'       => '' !== $deed_no_normalized ? $deed_no_normalized : null,
		'deed_type'                => ! empty( $data['deed_type'] ) ? trim( $data['deed_type'] ) : null,
		'deed_value_poysha'        => sojib68_money_to_poysha( $data['deed_value'] ?? '' ),
		'client_total_due_poysha'  => sojib68_money_to_poysha( $data['client_total_due'] ?? '' ),
		'fee_agreed_poysha'        => sojib68_money_to_poysha( $data['fee_agreed'] ?? '' ),
		'fee_collected_poysha'     => sojib68_money_to_poysha( $data['fee_collected'] ?? '' ),
		'dues_verified'            => ! empty( $data['dues_verified'] ) ? 1 : 0,
		'notes'                    => isset( $data['notes'] ) ? trim( $data['notes'] ) : null,
		'updated_by'               => $user_id,
		'updated_at'               => $now,
	);

	$wpdb->query( 'START TRANSACTION' );

	if ( $is_update ) {
		$result = $wpdb->update( $deeds_table, $deed_row, array( 'id' => $deed_id ) );
	} else {
		$deed_row['created_by'] = $user_id;
		$deed_row['created_at'] = $now;
		$result  = $wpdb->insert( $deeds_table, $deed_row );
		$deed_id = $wpdb->insert_id;
	}

	if ( false === $result ) {
		$wpdb->query( 'ROLLBACK' );
		return new WP_Error( 'deed_save_failed', __( 'দলিল সংরক্ষণ করা যায়নি। আবার চেষ্টা করুন।', 'sojib68' ) );
	}

	$ok = true;
	$ok = $ok && sojib68_deed_replace_children( 'deed_donors', $deed_id, $data['donors'] ?? array(), array(
		'name', 'address', 'mobile',
	) );
	$ok = $ok && sojib68_deed_replace_children( 'deed_grantees', $deed_id, $data['grantees'] ?? array(), array(
		'name', 'address', 'mobile',
	) );
	$ok = $ok && sojib68_deed_replace_lands( $deed_id, $data['lands'] ?? array() );
	// null = receipts were not part of this submission (edit form keeps the
	// payment history untouched; payments are managed from the dues page).
	if ( isset( $data['receipts'] ) ) {
		$ok = $ok && sojib68_deed_replace_receipts( $deed_id, $data['receipts'] );
	}
	$ok = $ok && sojib68_deed_replace_expenses( $deed_id, $data['expenses'] ?? array() );

	if ( ! $ok ) {
		$wpdb->query( 'ROLLBACK' );
		return new WP_Error( 'deed_save_failed', __( 'দলিল সংরক্ষণ করা যায়নি। আবার চেষ্টা করুন।', 'sojib68' ) );
	}

	$wpdb->query( 'COMMIT' );

	sojib68_deed_audit_log( $deed_id, $user_id, $is_update ? 'update' : 'create' );

	return $deed_id;
}

function sojib68_deed_replace_children( $table_key, $deed_id, $rows, $text_fields ) {
	global $wpdb;
	$table = sojib68_deeds_table( $table_key );

	if ( false === $wpdb->delete( $table, array( 'deed_id' => $deed_id ) ) && $wpdb->last_error ) {
		return false;
	}

	$order = 0;
	foreach ( (array) $rows as $row ) {
		$has_content = false;
		$values      = array( 'deed_id' => $deed_id, 'sort_order' => $order );
		foreach ( $text_fields as $field ) {
			$val = isset( $row[ $field ] ) ? trim( (string) $row[ $field ] ) : '';
			if ( 'mobile' === $field ) {
				$val = sojib68_normalize_field( $val );
			}
			if ( '' !== $val ) {
				$has_content = true;
			}
			$values[ $field ] = '' !== $val ? $val : null;
		}

		if ( ! $has_content ) {
			continue; // Skip fully empty repeated rows (e.g. a blank "+ add" group left untouched).
		}

		if ( false === $wpdb->insert( $table, $values ) ) {
			return false;
		}
		$order++;
	}

	return true;
}

function sojib68_deed_replace_lands( $deed_id, $rows ) {
	global $wpdb;
	$table = sojib68_deeds_table( 'deed_lands' );

	if ( false === $wpdb->delete( $table, array( 'deed_id' => $deed_id ) ) && $wpdb->last_error ) {
		return false;
	}

	$order = 0;
	foreach ( (array) $rows as $row ) {
		$mouza       = isset( $row['mouza'] ) ? trim( (string) $row['mouza'] ) : '';
		$khatian     = isset( $row['khatian_no'] ) ? trim( (string) $row['khatian_no'] ) : '';
		$khatian_norm = sojib68_normalize_field( $khatian );
		$quantity_raw = isset( $row['quantity'] ) ? trim( sojib68_bn_to_en_digits( $row['quantity'] ) ) : '';
		$unit        = isset( $row['unit'] ) ? trim( (string) $row['unit'] ) : '';

		$quantity = ( '' !== $quantity_raw && is_numeric( $quantity_raw ) ) ? (float) $quantity_raw : null;

		if ( '' === $mouza && '' === $khatian && null === $quantity && '' === $unit ) {
			continue; // Empty row, skip.
		}

		$values = array(
			'deed_id'               => $deed_id,
			'mouza'                 => '' !== $mouza ? $mouza : null,
			'khatian_no'            => '' !== $khatian ? $khatian : null,
			'khatian_no_normalized' => '' !== $khatian_norm ? $khatian_norm : null,
			'quantity'              => $quantity,
			'unit'                  => '' !== $unit ? $unit : null,
			'sort_order'            => $order,
		);

		if ( false === $wpdb->insert( $table, $values ) ) {
			return false;
		}
		$order++;
	}

	return true;
}

function sojib68_deed_replace_receipts( $deed_id, $rows ) {
	global $wpdb;
	$table = sojib68_deeds_table( 'deed_receipts' );

	if ( false === $wpdb->delete( $table, array( 'deed_id' => $deed_id ) ) && $wpdb->last_error ) {
		return false;
	}

	$order = 0;
	foreach ( (array) $rows as $row ) {
		$date   = ! empty( $row['received_date'] ) ? $row['received_date'] : null;
		$amount = sojib68_money_to_poysha( $row['amount'] ?? '' );
		$method = isset( $row['method'] ) ? trim( (string) $row['method'] ) : '';
		$note   = isset( $row['note'] ) ? trim( (string) $row['note'] ) : '';

		if ( null === $date && null === $amount && '' === $method && '' === $note ) {
			continue;
		}

		$values = array(
			'deed_id'       => $deed_id,
			'received_date' => $date,
			'amount_poysha' => null !== $amount ? $amount : 0,
			'method'        => '' !== $method ? $method : null,
			'note'          => '' !== $note ? $note : null,
			'sort_order'    => $order,
		);

		if ( false === $wpdb->insert( $table, $values ) ) {
			return false;
		}
		$order++;
	}

	return true;
}

function sojib68_deed_replace_expenses( $deed_id, $rows ) {
	global $wpdb;
	$table = sojib68_deeds_table( 'deed_expenses' );

	if ( false === $wpdb->delete( $table, array( 'deed_id' => $deed_id ) ) && $wpdb->last_error ) {
		return false;
	}

	$order = 0;
	foreach ( (array) $rows as $row ) {
		$category    = isset( $row['category'] ) ? trim( (string) $row['category'] ) : '';
		$description = isset( $row['description'] ) ? trim( (string) $row['description'] ) : '';
		$amount      = sojib68_money_to_poysha( $row['amount'] ?? '' );
		$paid_date   = ! empty( $row['paid_date'] ) ? $row['paid_date'] : null;
		$paid_status = isset( $row['paid_status'] ) ? trim( (string) $row['paid_status'] ) : '';
		$paid_by     = isset( $row['paid_by'] ) ? trim( (string) $row['paid_by'] ) : '';

		if ( '' === $category && '' === $description && null === $amount && null === $paid_date ) {
			continue;
		}

		$values = array(
			'deed_id'      => $deed_id,
			'category'     => '' !== $category ? $category : null,
			'description'  => '' !== $description ? $description : null,
			'amount_poysha' => null !== $amount ? $amount : 0,
			'paid_date'    => $paid_date,
			'paid_status'  => '' !== $paid_status ? $paid_status : null,
			'paid_by'      => '' !== $paid_by ? $paid_by : null,
			'sort_order'   => $order,
		);

		if ( false === $wpdb->insert( $table, $values ) ) {
			return false;
		}
		$order++;
	}

	return true;
}

/* ---------------------------------------------------------------------
 * Fetch a single deed (with all children) for the edit form / detail view.
 * ------------------------------------------------------------------ */

function sojib68_deed_get( $deed_id ) {
	global $wpdb;

	$deeds_table = sojib68_deeds_table( 'deeds' );
	$deed        = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $deeds_table WHERE id = %d AND deleted_at IS NULL", $deed_id ) );

	if ( ! $deed ) {
		return null;
	}

	$deed->donors   = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . sojib68_deeds_table( 'deed_donors' ) . ' WHERE deed_id = %d ORDER BY sort_order', $deed_id ) );
	$deed->grantees = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . sojib68_deeds_table( 'deed_grantees' ) . ' WHERE deed_id = %d ORDER BY sort_order', $deed_id ) );
	$deed->lands    = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . sojib68_deeds_table( 'deed_lands' ) . ' WHERE deed_id = %d ORDER BY sort_order', $deed_id ) );
	$deed->receipts = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . sojib68_deeds_table( 'deed_receipts' ) . ' WHERE deed_id = %d ORDER BY sort_order', $deed_id ) );
	$deed->expenses = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . sojib68_deeds_table( 'deed_expenses' ) . ' WHERE deed_id = %d ORDER BY sort_order', $deed_id ) );

	$total_received = 0;
	foreach ( $deed->receipts as $r ) {
		$total_received += (int) $r->amount_poysha;
	}
	$deed->total_received_poysha = $total_received;
	$deed->due_poysha            = null !== $deed->client_total_due_poysha
		? ( (int) $deed->client_total_due_poysha - $total_received )
		: null;

	return $deed;
}

/* ---------------------------------------------------------------------
 * Search / "সকল দলিল" listing
 * ------------------------------------------------------------------ */

function sojib68_deed_search( array $filters, $paged = 1, $per_page = 20 ) {
	global $wpdb;

	$deeds    = sojib68_deeds_table( 'deeds' );
	$donors   = sojib68_deeds_table( 'deed_donors' );
	$grantees = sojib68_deeds_table( 'deed_grantees' );
	$lands    = sojib68_deeds_table( 'deed_lands' );
	$receipts = sojib68_deeds_table( 'deed_receipts' );

	$where = array( 'd.deleted_at IS NULL' );
	$args  = array();

	if ( ! empty( $filters['name'] ) ) {
		$like = '%' . $wpdb->esc_like( trim( $filters['name'] ) ) . '%';
		$where[] = "(EXISTS (SELECT 1 FROM $donors dn WHERE dn.deed_id = d.id AND dn.name LIKE %s)
			OR EXISTS (SELECT 1 FROM $grantees gr WHERE gr.deed_id = d.id AND gr.name LIKE %s))";
		$args[] = $like;
		$args[] = $like;
	}

	if ( ! empty( $filters['mobile'] ) ) {
		$mobile_norm = sojib68_normalize_field( $filters['mobile'] );
		$like        = '%' . $wpdb->esc_like( $mobile_norm ) . '%';
		$where[] = "(EXISTS (SELECT 1 FROM $donors dn WHERE dn.deed_id = d.id AND dn.mobile LIKE %s)
			OR EXISTS (SELECT 1 FROM $grantees gr WHERE gr.deed_id = d.id AND gr.mobile LIKE %s))";
		$args[] = $like;
		$args[] = $like;
	}

	if ( ! empty( $filters['address'] ) ) {
		$like = '%' . $wpdb->esc_like( trim( $filters['address'] ) ) . '%';
		$where[] = "(EXISTS (SELECT 1 FROM $donors dn WHERE dn.deed_id = d.id AND dn.address LIKE %s)
			OR EXISTS (SELECT 1 FROM $grantees gr WHERE gr.deed_id = d.id AND gr.address LIKE %s))";
		$args[] = $like;
		$args[] = $like;
	}

	if ( ! empty( $filters['deed_no'] ) ) {
		$norm = sojib68_normalize_field( $filters['deed_no'] );
		$where[] = 'd.deed_no_normalized LIKE %s';
		$args[]  = '%' . $wpdb->esc_like( $norm ) . '%';
	}

	if ( ! empty( $filters['deed_type'] ) ) {
		$where[] = 'd.deed_type = %s';
		$args[]  = $filters['deed_type'];
	}

	if ( ! empty( $filters['office'] ) ) {
		$where[] = 'd.sub_registry_office = %s';
		$args[]  = $filters['office'];
	}

	if ( ! empty( $filters['reg_year'] ) ) {
		$where[] = 'd.reg_year = %d';
		$args[]  = (int) sojib68_bn_to_en_digits( $filters['reg_year'] );
	}

	if ( ! empty( $filters['date_from'] ) ) {
		$where[] = 'd.reg_date >= %s';
		$args[]  = $filters['date_from'];
	}
	if ( ! empty( $filters['date_to'] ) ) {
		$where[] = 'd.reg_date <= %s';
		$args[]  = $filters['date_to'];
	}

	if ( ! empty( $filters['value_min'] ) ) {
		$where[] = 'd.deed_value_poysha >= %d';
		$args[]  = sojib68_money_to_poysha( $filters['value_min'] );
	}
	if ( ! empty( $filters['value_max'] ) ) {
		$where[] = 'd.deed_value_poysha <= %d';
		$args[]  = sojib68_money_to_poysha( $filters['value_max'] );
	}

	// Mouza + khatian + quantity/unit must all match on the SAME land row,
	// so one deed is never listed twice for matching two different rows.
	if ( ! empty( $filters['mouza'] ) || ! empty( $filters['khatian_no'] ) || ! empty( $filters['land_unit'] ) ) {
		$land_conditions = array();

		if ( ! empty( $filters['mouza'] ) ) {
			$land_conditions[] = 'l.mouza LIKE %s';
			$args[]            = '%' . $wpdb->esc_like( trim( $filters['mouza'] ) ) . '%';
		}
		if ( ! empty( $filters['khatian_no'] ) ) {
			$land_conditions[] = 'l.khatian_no_normalized LIKE %s';
			$args[]            = '%' . $wpdb->esc_like( sojib68_normalize_field( $filters['khatian_no'] ) ) . '%';
		}
		if ( ! empty( $filters['land_unit'] ) ) {
			$land_conditions[] = 'l.unit = %s';
			$args[]            = $filters['land_unit'];
		}

		$where[] = "EXISTS (SELECT 1 FROM $lands l WHERE l.deed_id = d.id AND " . implode( ' AND ', $land_conditions ) . ')';
	}

	if ( isset( $filters['has_due'] ) && '' !== $filters['has_due'] ) {
		if ( '1' === (string) $filters['has_due'] ) {
			$where[] = "d.client_total_due_poysha IS NOT NULL
				AND (d.client_total_due_poysha - COALESCE((SELECT SUM(r.amount_poysha) FROM $receipts r WHERE r.deed_id = d.id), 0)) > 0";
		} elseif ( '0' === (string) $filters['has_due'] ) {
			$where[] = "d.client_total_due_poysha IS NOT NULL
				AND (d.client_total_due_poysha - COALESCE((SELECT SUM(r.amount_poysha) FROM $receipts r WHERE r.deed_id = d.id), 0)) <= 0";
		}
	}

	$where_sql = implode( ' AND ', $where );

	$per_page = max( 1, (int) $per_page );
	$paged    = max( 1, (int) $paged );
	$offset   = ( $paged - 1 ) * $per_page;

	$count_sql = "SELECT COUNT(*) FROM $deeds d WHERE $where_sql";
	$total     = (int) ( empty( $args ) ? $wpdb->get_var( $count_sql ) : $wpdb->get_var( $wpdb->prepare( $count_sql, $args ) ) );

	$list_sql  = "SELECT d.* FROM $deeds d WHERE $where_sql ORDER BY d.reg_date DESC, d.id DESC LIMIT %d OFFSET %d";
	$list_args = array_merge( $args, array( $per_page, $offset ) );
	$rows      = $wpdb->get_results( $wpdb->prepare( $list_sql, $list_args ) );

	foreach ( $rows as $row ) {
		$received = (int) $wpdb->get_var( $wpdb->prepare(
			"SELECT COALESCE(SUM(amount_poysha),0) FROM $receipts WHERE deed_id = %d",
			$row->id
		) );
		$row->total_received_poysha = $received;
		$row->due_poysha            = null !== $row->client_total_due_poysha
			? ( (int) $row->client_total_due_poysha - $received )
			: null;

		// Display data for the listing: party names and land rows.
		$donor_table   = sojib68_deeds_table( 'deed_donors' );
		$grantee_table = sojib68_deeds_table( 'deed_grantees' );
		$land_table    = sojib68_deeds_table( 'deed_lands' );

		$row->donor_names   = array_values( array_filter( array_map( 'strval', (array) $wpdb->get_col( $wpdb->prepare( "SELECT name FROM $donor_table WHERE deed_id = %d AND name <> '' ORDER BY sort_order", $row->id ) ) ) ) );
		$row->grantee_names = array_values( array_filter( array_map( 'strval', (array) $wpdb->get_col( $wpdb->prepare( "SELECT name FROM $grantee_table WHERE deed_id = %d AND name <> '' ORDER BY sort_order", $row->id ) ) ) ) );
		$row->lands         = (array) $wpdb->get_results( $wpdb->prepare( "SELECT mouza, khatian_no, quantity, unit FROM $land_table WHERE deed_id = %d ORDER BY sort_order", $row->id ) );
	}

	return array(
		'rows'       => $rows,
		'total'      => $total,
		'per_page'   => $per_page,
		'paged'      => $paged,
		'total_pages' => (int) ceil( $total / $per_page ),
	);
}

/* ---------------------------------------------------------------------
 * Dashboard summary stats.
 *
 * "Profit" = amount collected from clients minus recorded expenses
 * (bank challan, office fee, stamp, other).
 * ------------------------------------------------------------------ */

function sojib68_office_dashboard_stats() {
	global $wpdb;

	$deeds    = sojib68_deeds_table( 'deeds' );
	$receipts = sojib68_deeds_table( 'deed_receipts' );
	$expenses = sojib68_deeds_table( 'deed_expenses' );

	$total_deeds = (int) $wpdb->get_var( "SELECT COUNT(*) FROM $deeds WHERE deleted_at IS NULL" );

	$total_deed_value = (int) $wpdb->get_var(
		"SELECT COALESCE(SUM(deed_value_poysha),0) FROM $deeds WHERE deleted_at IS NULL"
	);

	$total_collected = (int) $wpdb->get_var(
		"SELECT COALESCE(SUM(r.amount_poysha),0) FROM $receipts r
		 INNER JOIN $deeds d ON d.id = r.deed_id WHERE d.deleted_at IS NULL"
	);

	$total_expenses = (int) $wpdb->get_var(
		"SELECT COALESCE(SUM(e.amount_poysha),0) FROM $expenses e
		 INNER JOIN $deeds d ON d.id = e.deed_id WHERE d.deleted_at IS NULL"
	);

	// Profit = money actually collected from clients minus recorded expenses.
	$total_profit = $total_collected - $total_expenses;

	// Total outstanding due: per deed, max(0, due) — so an advance on
	// one deed never offsets a due on another when summed.
	$due_rows = $wpdb->get_results(
		"SELECT d.client_total_due_poysha,
			COALESCE((SELECT SUM(r.amount_poysha) FROM $receipts r WHERE r.deed_id = d.id), 0) AS received
		 FROM $deeds d
		 WHERE d.deleted_at IS NULL AND d.client_total_due_poysha IS NOT NULL"
	);
	$total_due = 0;
	foreach ( $due_rows as $row ) {
		$due = (int) $row->client_total_due_poysha - (int) $row->received;
		if ( $due > 0 ) {
			$total_due += $due;
		}
	}

	return array(
		'total_deeds'       => $total_deeds,
		'total_deed_value'  => $total_deed_value,
		'total_collected'   => $total_collected,
		'total_expenses'    => $total_expenses,
		'total_profit'      => $total_profit,
		'total_due'         => $total_due,
	);
}

/* ---------------------------------------------------------------------
 * Monthly / yearly report (ড্যাশবোর্ডের গ্রাফ)
 *
 * Aggregated in PHP rather than with SQL date-grouping so the same
 * code runs the same way regardless of the database's SQL dialect —
 * the raw rows are small in number for an office this size.
 * ------------------------------------------------------------------ */

function sojib68_bn_digits( $n ) {
	static $map = array( '0' => '০', '1' => '১', '2' => '২', '3' => '৩', '4' => '৪', '5' => '৫', '6' => '৬', '7' => '৭', '8' => '৮', '9' => '৯' );
	return strtr( (string) $n, $map );
}

function sojib68_bn_month_label( $month_num ) {
	static $labels = array(
		1 => 'জানু', 2 => 'ফেব্রু', 3 => 'মার্চ', 4 => 'এপ্রিল', 5 => 'মে', 6 => 'জুন',
		7 => 'জুলাই', 8 => 'আগস্ট', 9 => 'সেপ্ট', 10 => 'অক্টো', 11 => 'নভে', 12 => 'ডিসে',
	);
	return isset( $labels[ $month_num ] ) ? $labels[ $month_num ] : (string) $month_num;
}

/** Deeds/receipts/expenses for one calendar year, as raw rows (one SQL round trip each). */
function sojib68_office_report_raw_rows( $year ) {
	global $wpdb;

	$deeds    = sojib68_deeds_table( 'deeds' );
	$receipts = sojib68_deeds_table( 'deed_receipts' );
	$expenses = sojib68_deeds_table( 'deed_expenses' );
	$like     = $year . '-%';

	return array(
		'deeds'    => $wpdb->get_results( $wpdb->prepare( "SELECT reg_date, deed_value_poysha FROM $deeds WHERE deleted_at IS NULL AND reg_date LIKE %s", $like ) ),
		'receipts' => $wpdb->get_results( $wpdb->prepare(
			"SELECT r.received_date, r.amount_poysha FROM $receipts r INNER JOIN $deeds d ON d.id = r.deed_id WHERE d.deleted_at IS NULL AND r.received_date LIKE %s",
			$like
		) ),
		'expenses' => $wpdb->get_results( $wpdb->prepare(
			"SELECT e.paid_date, e.amount_poysha FROM $expenses e INNER JOIN $deeds d ON d.id = e.deed_id WHERE d.deleted_at IS NULL AND e.paid_date LIKE %s",
			$like
		) ),
	);
}

/**
 * Month-by-month breakdown for one calendar year. Only rows with an
 * exact date count here (a deed with only reg_year, or a payment with
 * no date, can't be placed in a month) — the yearly view below counts
 * those too.
 */
function sojib68_office_monthly_stats( $year = null ) {
	$year = $year ? (int) $year : (int) current_time( 'Y' );
	$raw  = sojib68_office_report_raw_rows( $year );

	$months = array();
	for ( $m = 1; $m <= 12; $m++ ) {
		$months[ $m ] = array(
			'key'         => str_pad( (string) $m, 2, '0', STR_PAD_LEFT ),
			'label'       => sojib68_bn_month_label( $m ),
			'deeds_count' => 0,
			'deed_value'  => 0,
			'collected'   => 0,
			'expenses'    => 0,
			'profit'      => 0,
		);
	}

	foreach ( $raw['deeds'] as $row ) {
		$m = (int) substr( (string) $row->reg_date, 5, 2 );
		if ( isset( $months[ $m ] ) ) {
			$months[ $m ]['deeds_count']++;
			$months[ $m ]['deed_value'] += (int) $row->deed_value_poysha;
		}
	}
	foreach ( $raw['receipts'] as $row ) {
		$m = (int) substr( (string) $row->received_date, 5, 2 );
		if ( isset( $months[ $m ] ) ) {
			$months[ $m ]['collected'] += (int) $row->amount_poysha;
		}
	}
	foreach ( $raw['expenses'] as $row ) {
		$m = (int) substr( (string) $row->paid_date, 5, 2 );
		if ( isset( $months[ $m ] ) ) {
			$months[ $m ]['expenses'] += (int) $row->amount_poysha;
		}
	}
	foreach ( $months as &$row ) {
		$row['profit'] = $row['collected'] - $row['expenses'];
	}
	unset( $row );

	return array(
		'year'    => $year,
		'periods' => array_values( $months ),
	);
}

/**
 * Year-by-year breakdown for the last $years_back calendar years. A
 * deed with only reg_year (no exact date) is still counted here under
 * that year — dated rows (receipts/expenses) need an exact date.
 */
function sojib68_office_yearly_stats( $years_back = 6 ) {
	global $wpdb;

	$current_year = (int) current_time( 'Y' );
	$start_year   = $current_year - $years_back + 1;

	$years = array();
	for ( $y = $start_year; $y <= $current_year; $y++ ) {
		$years[ $y ] = array(
			'key'         => (string) $y,
			'label'       => sojib68_bn_digits( $y ),
			'deeds_count' => 0,
			'deed_value'  => 0,
			'collected'   => 0,
			'expenses'    => 0,
			'profit'      => 0,
		);
	}

	$deeds    = sojib68_deeds_table( 'deeds' );
	$receipts = sojib68_deeds_table( 'deed_receipts' );
	$expenses = sojib68_deeds_table( 'deed_expenses' );

	$deed_rows = $wpdb->get_results( $wpdb->prepare(
		"SELECT reg_date, reg_year, deed_value_poysha FROM $deeds WHERE deleted_at IS NULL AND (reg_date >= %s OR ( reg_date IS NULL AND reg_year >= %d ))",
		$start_year . '-01-01',
		$start_year
	) );
	foreach ( $deed_rows as $row ) {
		$y = $row->reg_date ? (int) substr( (string) $row->reg_date, 0, 4 ) : (int) $row->reg_year;
		if ( isset( $years[ $y ] ) ) {
			$years[ $y ]['deeds_count']++;
			$years[ $y ]['deed_value'] += (int) $row->deed_value_poysha;
		}
	}

	$receipt_rows = $wpdb->get_results( $wpdb->prepare(
		"SELECT r.received_date, r.amount_poysha FROM $receipts r INNER JOIN $deeds d ON d.id = r.deed_id WHERE d.deleted_at IS NULL AND r.received_date >= %s",
		$start_year . '-01-01'
	) );
	foreach ( $receipt_rows as $row ) {
		$y = (int) substr( (string) $row->received_date, 0, 4 );
		if ( isset( $years[ $y ] ) ) {
			$years[ $y ]['collected'] += (int) $row->amount_poysha;
		}
	}

	$expense_rows = $wpdb->get_results( $wpdb->prepare(
		"SELECT e.paid_date, e.amount_poysha FROM $expenses e INNER JOIN $deeds d ON d.id = e.deed_id WHERE d.deleted_at IS NULL AND e.paid_date >= %s",
		$start_year . '-01-01'
	) );
	foreach ( $expense_rows as $row ) {
		$y = (int) substr( (string) $row->paid_date, 0, 4 );
		if ( isset( $years[ $y ] ) ) {
			$years[ $y ]['expenses'] += (int) $row->amount_poysha;
		}
	}

	foreach ( $years as &$row ) {
		$row['profit'] = $row['collected'] - $row['expenses'];
	}
	unset( $row );

	return array( 'periods' => array_values( $years ) );
}

/* ---------------------------------------------------------------------
 * Dues collection (বকেয়া আদায়)
 * ------------------------------------------------------------------ */

/** Record one payment against a deed. Returns payment id or WP_Error. */
function sojib68_deed_add_payment( $deed_id, $amount_input, $date, $method, $note, $user_id ) {
	global $wpdb;

	$deed_id = (int) $deed_id;
	$deeds   = sojib68_deeds_table( 'deeds' );
	$table   = sojib68_deeds_table( 'deed_receipts' );

	if ( ! $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $deeds WHERE id = %d AND deleted_at IS NULL", $deed_id ) ) ) {
		return new WP_Error( 'no_deed', __( 'দলিলটি খুঁজে পাওয়া যায়নি।', 'sojib68' ) );
	}

	$amount = sojib68_money_to_poysha( $amount_input );
	if ( null === $amount || $amount <= 0 ) {
		return new WP_Error( 'bad_amount', __( 'সঠিক টাকার পরিমাণ লিখুন।', 'sojib68' ) );
	}

	$date = trim( (string) $date );
	if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ) {
		$date = current_time( 'Y-m-d' );
	}

	$method = trim( (string) $method );
	$note   = trim( (string) $note );
	$order  = 1 + (int) $wpdb->get_var( $wpdb->prepare( "SELECT COALESCE(MAX(sort_order), 0) FROM $table WHERE deed_id = %d", $deed_id ) );

	$ok = $wpdb->insert( $table, array(
		'deed_id'       => $deed_id,
		'received_date' => $date,
		'amount_poysha' => $amount,
		'method'        => '' !== $method ? $method : null,
		'note'          => '' !== $note ? $note : null,
		'sort_order'    => $order,
	) );

	if ( false === $ok ) {
		return new WP_Error( 'save_failed', __( 'টাকা সংরক্ষণ করা যায়নি। আবার চেষ্টা করুন।', 'sojib68' ) );
	}

	$payment_id = (int) $wpdb->insert_id;
	sojib68_deed_audit_log( $deed_id, $user_id, 'payment_add', sojib68_poysha_to_display( $amount ) );

	return $payment_id;
}

/** Remove a mistakenly entered payment. */
function sojib68_deed_delete_payment( $payment_id, $user_id ) {
	global $wpdb;

	$table = sojib68_deeds_table( 'deed_receipts' );
	$row   = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE id = %d", (int) $payment_id ) );
	if ( ! $row ) {
		return new WP_Error( 'no_payment', __( 'এন্ট্রিটি খুঁজে পাওয়া যায়নি।', 'sojib68' ) );
	}

	if ( false === $wpdb->delete( $table, array( 'id' => (int) $payment_id ) ) ) {
		return new WP_Error( 'delete_failed', __( 'মুছে ফেলা যায়নি।', 'sojib68' ) );
	}

	sojib68_deed_audit_log( (int) $row->deed_id, $user_id, 'payment_delete', sojib68_poysha_to_display( $row->amount_poysha ) );
	return true;
}

/** Summary numbers shown above the dues list. */
function sojib68_dues_summary() {
	global $wpdb;

	$deeds    = sojib68_deeds_table( 'deeds' );
	$receipts = sojib68_deeds_table( 'deed_receipts' );

	$rows = $wpdb->get_results(
		"SELECT d.client_total_due_poysha AS total,
			COALESCE((SELECT SUM(r.amount_poysha) FROM $receipts r WHERE r.deed_id = d.id), 0) AS received
		 FROM $deeds d WHERE d.deleted_at IS NULL AND d.client_total_due_poysha IS NOT NULL"
	);

	$total_due = 0;
	$count     = 0;
	foreach ( $rows as $row ) {
		$due = (int) $row->total - (int) $row->received;
		if ( $due > 0 ) {
			$total_due += $due;
			$count++;
		}
	}

	$month_start     = current_time( 'Y-m-01' );
	$month_collected = (int) $wpdb->get_var( $wpdb->prepare(
		"SELECT COALESCE(SUM(r.amount_poysha), 0) FROM $receipts r
		 INNER JOIN $deeds d ON d.id = r.deed_id
		 WHERE d.deleted_at IS NULL AND r.received_date >= %s",
		$month_start
	) );

	return array(
		'total_due'       => $total_due,
		'deed_count'      => $count,
		'month_collected' => $month_collected,
	);
}

/**
 * Deeds that still owe money. $filters: q (name / mobile / deed no),
 * sort: due_desc | oldest | newest.
 */
function sojib68_dues_list( array $filters, $paged = 1, $per_page = 15 ) {
	global $wpdb;

	$deeds    = sojib68_deeds_table( 'deeds' );
	$donors   = sojib68_deeds_table( 'deed_donors' );
	$grantees = sojib68_deeds_table( 'deed_grantees' );
	$receipts = sojib68_deeds_table( 'deed_receipts' );

	$received_sql = "COALESCE((SELECT SUM(r.amount_poysha) FROM $receipts r WHERE r.deed_id = d.id), 0)";

	$where = array(
		'd.deleted_at IS NULL',
		'd.client_total_due_poysha IS NOT NULL',
		"(d.client_total_due_poysha - $received_sql) > 0",
	);
	$args = array();

	if ( ! empty( $filters['q'] ) ) {
		$q    = trim( sojib68_bn_to_en_digits( $filters['q'] ) );
		$like = '%' . $wpdb->esc_like( $q ) . '%';
		$where[] = "(EXISTS (SELECT 1 FROM $donors dn WHERE dn.deed_id = d.id AND (dn.name LIKE %s OR dn.mobile LIKE %s))
			OR EXISTS (SELECT 1 FROM $grantees gr WHERE gr.deed_id = d.id AND (gr.name LIKE %s OR gr.mobile LIKE %s))
			OR d.deed_no_normalized LIKE %s)";
		array_push( $args, $like, $like, $like, $like, $like );
	}

	$where_sql = implode( ' AND ', $where );

	switch ( $filters['sort'] ?? 'due_desc' ) {
		case 'oldest':
			$order = 'd.reg_date IS NULL, d.reg_date ASC, d.id ASC';
			break;
		case 'newest':
			$order = 'd.reg_date DESC, d.id DESC';
			break;
		default:
			$order = "(d.client_total_due_poysha - $received_sql) DESC, d.id DESC";
	}

	$per_page = max( 1, (int) $per_page );
	$paged    = max( 1, (int) $paged );
	$offset   = ( $paged - 1 ) * $per_page;

	$count_sql = "SELECT COUNT(*) FROM $deeds d WHERE $where_sql";
	$total     = (int) ( $args ? $wpdb->get_var( $wpdb->prepare( $count_sql, $args ) ) : $wpdb->get_var( $count_sql ) );

	$list_sql = "SELECT d.*, $received_sql AS received_poysha FROM $deeds d WHERE $where_sql ORDER BY $order LIMIT %d OFFSET %d";
	$rows     = $wpdb->get_results( $wpdb->prepare( $list_sql, array_merge( $args, array( $per_page, $offset ) ) ) );

	foreach ( $rows as $row ) {
		$row->received_poysha = (int) $row->received_poysha;
		$row->due_poysha      = (int) $row->client_total_due_poysha - $row->received_poysha;

		$row->donors   = (array) $wpdb->get_results( $wpdb->prepare( "SELECT name, mobile FROM $donors WHERE deed_id = %d AND (name <> '' OR mobile <> '') ORDER BY sort_order", $row->id ) );
		$row->grantees = (array) $wpdb->get_results( $wpdb->prepare( "SELECT name, mobile FROM $grantees WHERE deed_id = %d AND (name <> '' OR mobile <> '') ORDER BY sort_order", $row->id ) );
		$row->payments = (array) $wpdb->get_results( $wpdb->prepare( "SELECT id, received_date, amount_poysha, method, note FROM $receipts WHERE deed_id = %d ORDER BY received_date DESC, id DESC", $row->id ) );
	}

	return array(
		'rows'        => $rows,
		'total'       => $total,
		'per_page'    => $per_page,
		'paged'       => $paged,
		'total_pages' => (int) ceil( $total / $per_page ),
	);
}
