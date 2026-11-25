<?php
/**
 * "দলিল লিখুন" — office tool: fill a deed form (saved instantly as you type), then
 * download it as a Word file in Bijoy / SutonnyMJ.
 *
 *  - drafts and a small people directory (looked up by NID / name) live in two tables
 *  - the Word file is the user's own deed layout, kept as a sanitized template with
 *    {{tokens}} (inc/deed-template/), filled server-side and converted with inc/bijoy.php
 *  - NO external NID lookup: Bangladesh NID data is only available to authorized
 *    organizations through the Election Commission, so the system remembers people
 *    already typed once and fills them in by NID.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/bijoy.php';
require_once __DIR__ . '/docx-writer.php';

const SOJIB68_DW_DB_VERSION = '1.1.0';
const SOJIB68_DW_TRASH_DAYS   = 7;

function sojib68_dw_drafts_table() {
	global $wpdb;
	return $wpdb->prefix . 'office_dw_drafts';
}

function sojib68_dw_parties_table() {
	global $wpdb;
	return $wpdb->prefix . 'office_dw_parties';
}

function sojib68_dw_install() {
	global $wpdb;
	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	$charset = $wpdb->get_charset_collate();
	$drafts  = sojib68_dw_drafts_table();
	$parties = sojib68_dw_parties_table();

	dbDelta( "CREATE TABLE {$drafts} (
		id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
		title VARCHAR(190) NOT NULL DEFAULT '',
		data LONGTEXT NOT NULL,
		created_by BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
		created_at DATETIME NOT NULL,
		updated_at DATETIME NOT NULL,
		deleted_at DATETIME NULL DEFAULT NULL,
		PRIMARY KEY  (id),
		KEY updated_at (updated_at),
		KEY deleted_at (deleted_at)
	) {$charset};" );

	dbDelta( "CREATE TABLE {$parties} (
		id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
		nid VARCHAR(32) NOT NULL,
		name VARCHAR(190) NOT NULL DEFAULT '',
		data LONGTEXT NOT NULL,
		updated_at DATETIME NOT NULL,
		PRIMARY KEY  (id),
		UNIQUE KEY nid (nid),
		KEY name (name)
	) {$charset};" );

	update_option( 'sojib68_dw_db_version', SOJIB68_DW_DB_VERSION );
}

function sojib68_dw_maybe_upgrade() {
	if ( get_option( 'sojib68_dw_db_version' ) !== SOJIB68_DW_DB_VERSION ) {
		sojib68_dw_install();
	}
}
add_action( 'init', 'sojib68_dw_maybe_upgrade', 6 );

/* ---------------------------------------------------------------------
 * Helpers
 * ------------------------------------------------------------------ */

/** Western digits only, for NID matching: "৮২৩ ৫৪১ ৭৩৩৭" and "823 541 7337" are the same person. */
function sojib68_dw_nid_key( $nid ) {
	$nid = strtr( (string) $nid, array( '০' => '0', '১' => '1', '২' => '2', '৩' => '3', '৪' => '4', '৫' => '5', '৬' => '6', '৭' => '7', '৮' => '8', '৯' => '9' ) );
	return preg_replace( '/\D+/', '', $nid );
}

/** Recursively clean decoded JSON: strings lose control characters and are length-capped. */
function sojib68_dw_clean( $value, $depth = 0 ) {
	if ( $depth > 8 ) {
		return null;
	}
	if ( is_array( $value ) ) {
		$out = array();
		$n   = 0;
		foreach ( $value as $k => $v ) {
			if ( ++$n > 400 ) {
				break;
			}
			$key         = is_int( $k ) ? $k : preg_replace( '/[^A-Za-z0-9_.\-]/', '', (string) $k );
			$out[ $key ] = sojib68_dw_clean( $v, $depth + 1 );
		}
		return $out;
	}
	if ( is_bool( $value ) || is_int( $value ) || is_float( $value ) || null === $value ) {
		return $value;
	}
	$s = wp_check_invalid_utf8( (string) $value );
	$s = preg_replace( '/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $s );
	return mb_substr( $s, 0, 20000 );
}

function sojib68_dw_get_draft( $id, $include_deleted = false ) {
	global $wpdb;
	$t = sojib68_dw_drafts_table();
	$w = $include_deleted ? '' : ' AND deleted_at IS NULL';
	return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$t} WHERE id = %d{$w}", (int) $id ), ARRAY_A );
}

/* ---- recycle bin: "delete" only moves a deed to the bin; it can be restored for SOJIB68_DW_TRASH_DAYS days ---- */
function sojib68_dw_now_ts() {
	return strtotime( current_time( 'mysql' ) );
}

function sojib68_dw_purge_old() {
	global $wpdb;
	$t = sojib68_dw_drafts_table();
	$wpdb->query( $wpdb->prepare( "DELETE FROM {$t} WHERE deleted_at IS NOT NULL AND deleted_at < %s", gmdate( 'Y-m-d H:i:s', sojib68_dw_now_ts() - SOJIB68_DW_TRASH_DAYS * DAY_IN_SECONDS ) ) );
}
add_action( 'sojib68_dw_daily_purge', 'sojib68_dw_purge_old' );
add_action( 'init', function () {
	if ( ! wp_next_scheduled( 'sojib68_dw_daily_purge' ) ) {
		wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', 'sojib68_dw_daily_purge' );
	}
} );

function sojib68_dw_party_row_to_array( $row ) {
	if ( ! $row ) {
		return null;
	}
	$d = json_decode( $row['data'], true );
	if ( ! is_array( $d ) ) {
		$d = array();
	}
	$d['nid']  = $row['nid'];
	$d['name'] = $row['name'];
	return $d;
}

/** Remember every donor/donee that has a NID, so the next deed can fill them in. */
function sojib68_dw_remember_parties( $data ) {
	global $wpdb;
	$t   = sojib68_dw_parties_table();
	$now = current_time( 'mysql' );
	foreach ( array( 'donees', 'donors' ) as $group ) {
		if ( empty( $data[ $group ] ) || ! is_array( $data[ $group ] ) ) {
			continue;
		}
		foreach ( $data[ $group ] as $p ) {
			if ( ! is_array( $p ) ) {
				continue;
			}
			$key = sojib68_dw_nid_key( isset( $p['nid'] ) ? $p['nid'] : '' );
			if ( strlen( $key ) < 8 || '' === trim( (string) ( $p['name'] ?? '' ) ) ) {
				continue;
			}
			$keep = array();
			foreach ( array( 'gender', 'spouse', 'father', 'mother', 'dob', 'religion', 'profession', 'nationality', 'perm', 'present', 'nid_display' ) as $f ) {
				$keep[ $f ] = isset( $p[ $f ] ) ? (string) $p[ $f ] : '';
			}
			$wpdb->query( $wpdb->prepare(
				"INSERT INTO {$t} (nid, name, data, updated_at) VALUES (%s, %s, %s, %s)
				 ON DUPLICATE KEY UPDATE name = VALUES(name), data = VALUES(data), updated_at = VALUES(updated_at)",
				$key,
				mb_substr( (string) $p['name'], 0, 190 ),
				wp_json_encode( $keep, JSON_UNESCAPED_UNICODE ),
				$now
			) );
		}
	}
}

/* ---------------------------------------------------------------------
 * AJAX (office users only, nonce-protected)
 * ------------------------------------------------------------------ */

function sojib68_dw_guard() {
	if ( ! sojib68_office_is_authorized() ) {
		wp_send_json_error( array( 'message' => 'লগইন প্রয়োজন।' ), 401 );
	}
	if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( wp_unslash( $_POST['nonce'] ), 'sojib68_dw' ) ) {
		wp_send_json_error( array( 'message' => 'সেশনের মেয়াদ শেষ। পেজ রিলোড করুন।' ), 403 );
	}
}

add_action( 'wp_ajax_sojib68_dw_save', 'sojib68_dw_ajax_save' );
function sojib68_dw_ajax_save() {
	sojib68_dw_guard();
	global $wpdb;
	$t    = sojib68_dw_drafts_table();
	$id   = isset( $_POST['id'] ) ? (int) $_POST['id'] : 0;
	$raw  = isset( $_POST['data'] ) ? wp_unslash( $_POST['data'] ) : '';
	if ( strlen( $raw ) > 600000 ) {
		wp_send_json_error( array( 'message' => 'তথ্য খুব বড়।' ), 413 );
	}
	$data = json_decode( $raw, true );
	if ( ! is_array( $data ) ) {
		wp_send_json_error( array( 'message' => 'তথ্য পড়া যায়নি।' ), 400 );
	}
	$data  = sojib68_dw_clean( $data );
	$title = isset( $_POST['title'] ) ? mb_substr( sanitize_text_field( wp_unslash( $_POST['title'] ) ), 0, 190 ) : '';
	$json  = wp_json_encode( $data, JSON_UNESCAPED_UNICODE );
	$now   = current_time( 'mysql' );

	if ( $id && sojib68_dw_get_draft( $id ) ) {
		$wpdb->update( $t, array( 'title' => $title, 'data' => $json, 'updated_at' => $now ), array( 'id' => $id ), array( '%s', '%s', '%s' ), array( '%d' ) );
	} else {
		$wpdb->insert( $t, array( 'title' => $title, 'data' => $json, 'created_by' => get_current_user_id(), 'created_at' => $now, 'updated_at' => $now ), array( '%s', '%s', '%d', '%s', '%s' ) );
		$id = (int) $wpdb->insert_id;
	}
	if ( ! $id ) {
		wp_send_json_error( array( 'message' => 'সেভ করা যায়নি।' ), 500 );
	}

	sojib68_dw_remember_parties( $data );
	if ( ! empty( $data['defaults'] ) && is_array( $data['defaults'] ) ) {
		update_option( 'sojib68_dw_defaults', $data['defaults'], false );
	}
	wp_send_json_success( array( 'id' => $id, 'saved_at' => $now ) );
}

add_action( 'wp_ajax_sojib68_dw_party_get', 'sojib68_dw_ajax_party_get' );
function sojib68_dw_ajax_party_get() {
	sojib68_dw_guard();
	global $wpdb;
	$t   = sojib68_dw_parties_table();
	$key = sojib68_dw_nid_key( isset( $_POST['nid'] ) ? wp_unslash( $_POST['nid'] ) : '' );
	if ( strlen( $key ) < 8 ) {
		wp_send_json_success( array( 'party' => null ) );
	}
	$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$t} WHERE nid = %s", $key ), ARRAY_A );
	wp_send_json_success( array( 'party' => sojib68_dw_party_row_to_array( $row ) ) );
}

/** Bulk-add people read from old deed files (browser-side). Existing NIDs are left untouched. */
add_action( 'wp_ajax_sojib68_dw_party_import', 'sojib68_dw_ajax_party_import' );
function sojib68_dw_ajax_party_import() {
	sojib68_dw_guard();
	global $wpdb;
	$in = isset( $_POST['people'] ) ? json_decode( wp_unslash( $_POST['people'] ), true ) : null;
	if ( ! is_array( $in ) || count( $in ) > 400 ) {
		wp_send_json_error( array( 'message' => 'তথ্য পড়া যায়নি (একবারে সর্বোচ্চ ৪০০ জন)।' ), 400 );
	}
	$t     = sojib68_dw_parties_table();
	$now   = current_time( 'mysql' );
	$added = 0;
	$skip  = 0;
	$seen  = array();
	foreach ( $in as $p ) {
		if ( ! is_array( $p ) ) {
			continue;
		}
		$p   = sojib68_dw_clean( $p, 6 );
		$key = sojib68_dw_nid_key( isset( $p['nid'] ) ? $p['nid'] : '' );
		if ( strlen( $key ) < 8 || '' === trim( (string) ( $p['name'] ?? '' ) ) || isset( $seen[ $key ] ) ) {
			continue;
		}
		$seen[ $key ] = true;
		$keep         = array();
		foreach ( array( 'gender', 'spouse', 'father', 'mother', 'dob', 'religion', 'profession', 'nationality', 'perm', 'present', 'nid_display' ) as $f ) {
			$keep[ $f ] = isset( $p[ $f ] ) ? mb_substr( (string) $p[ $f ], 0, 500 ) : '';
		}
		$rows = $wpdb->query( $wpdb->prepare(
			"INSERT IGNORE INTO {$t} (nid, name, data, updated_at) VALUES (%s, %s, %s, %s)",
			$key,
			mb_substr( (string) $p['name'], 0, 190 ),
			wp_json_encode( $keep, JSON_UNESCAPED_UNICODE ),
			$now
		) );
		if ( $rows ) {
			$added++;
		} else {
			$skip++;
		}
	}
	wp_send_json_success( array( 'added' => $added, 'skipped' => $skip ) );
}

add_action( 'wp_ajax_sojib68_dw_party_update', 'sojib68_dw_ajax_party_update' );
function sojib68_dw_ajax_party_update() {
	sojib68_dw_guard();
	global $wpdb;
	$t  = sojib68_dw_parties_table();
	$id = isset( $_POST['id'] ) ? (int) $_POST['id'] : 0;
	$in = isset( $_POST['person'] ) ? json_decode( wp_unslash( $_POST['person'] ), true ) : null;
	if ( ! $id || ! is_array( $in ) || '' === trim( (string) ( $in['name'] ?? '' ) ) ) {
		wp_send_json_error( array( 'message' => 'নাম লিখুন।' ), 400 );
	}
	$key = sojib68_dw_nid_key( $in['nid'] ?? '' );
	if ( strlen( $key ) < 8 ) {
		wp_send_json_error( array( 'message' => 'সঠিক NID লিখুন।' ), 400 );
	}
	$clash = (int) $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$t} WHERE nid = %s AND id <> %d", $key, $id ) );
	if ( $clash ) {
		wp_send_json_error( array( 'message' => 'এই NID অন্য একজনের নামে আগে থেকেই সেভ আছে।' ), 409 );
	}
	$keep = array();
	foreach ( array( 'gender', 'spouse', 'father', 'mother', 'dob', 'religion', 'profession', 'nationality', 'perm', 'present' ) as $f ) {
		$keep[ $f ] = isset( $in[ $f ] ) ? mb_substr( sanitize_textarea_field( (string) $in[ $f ] ), 0, 500 ) : '';
	}
	$keep['nid_display'] = mb_substr( sanitize_text_field( (string) ( $in['nid'] ?? '' ) ), 0, 40 );
	$wpdb->update( $t, array( 'nid' => $key, 'name' => mb_substr( sanitize_text_field( (string) $in['name'] ), 0, 190 ), 'data' => wp_json_encode( $keep, JSON_UNESCAPED_UNICODE ), 'updated_at' => current_time( 'mysql' ) ), array( 'id' => $id ), array( '%s', '%s', '%s', '%s' ), array( '%d' ) );
	wp_send_json_success();
}

add_action( 'wp_ajax_sojib68_dw_party_delete', 'sojib68_dw_ajax_party_delete' );
function sojib68_dw_ajax_party_delete() {
	sojib68_dw_guard();
	global $wpdb;
	$id = isset( $_POST['id'] ) ? (int) $_POST['id'] : 0;
	if ( $id ) {
		$wpdb->delete( sojib68_dw_parties_table(), array( 'id' => $id ), array( '%d' ) );
	}
	wp_send_json_success();
}

add_action( 'wp_ajax_sojib68_dw_party_search', 'sojib68_dw_ajax_party_search' );
function sojib68_dw_ajax_party_search() {
	sojib68_dw_guard();
	global $wpdb;
	$t = sojib68_dw_parties_table();
	$q = isset( $_POST['q'] ) ? trim( sanitize_text_field( wp_unslash( $_POST['q'] ) ) ) : '';
	if ( mb_strlen( $q ) < 2 ) {
		wp_send_json_success( array( 'results' => array() ) );
	}
	$digits = sojib68_dw_nid_key( $q );
	$like   = '%' . $wpdb->esc_like( $q ) . '%';
	if ( strlen( $digits ) >= 4 ) {
		$rows = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$t} WHERE nid LIKE %s OR name LIKE %s ORDER BY updated_at DESC LIMIT 8", '%' . $wpdb->esc_like( $digits ) . '%', $like ), ARRAY_A );
	} else {
		$rows = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$t} WHERE name LIKE %s ORDER BY updated_at DESC LIMIT 8", $like ), ARRAY_A );
	}
	wp_send_json_success( array( 'results' => array_map( 'sojib68_dw_party_row_to_array', (array) $rows ) ) );
}

add_action( 'wp_ajax_sojib68_dw_delete', 'sojib68_dw_ajax_delete' );
function sojib68_dw_ajax_delete() {
	sojib68_dw_guard();
	global $wpdb;
	$id = isset( $_POST['id'] ) ? (int) $_POST['id'] : 0;
	if ( $id ) {
		$wpdb->update( sojib68_dw_drafts_table(), array( 'deleted_at' => current_time( 'mysql' ) ), array( 'id' => $id ), array( '%s' ), array( '%d' ) );
	}
	wp_send_json_success();
}

add_action( 'wp_ajax_sojib68_dw_restore', 'sojib68_dw_ajax_restore' );
function sojib68_dw_ajax_restore() {
	sojib68_dw_guard();
	global $wpdb;
	$id = isset( $_POST['id'] ) ? (int) $_POST['id'] : 0;
	if ( $id ) {
		$wpdb->query( $wpdb->prepare( 'UPDATE ' . sojib68_dw_drafts_table() . ' SET deleted_at = NULL WHERE id = %d', $id ) );
	}
	wp_send_json_success();
}

add_action( 'wp_ajax_sojib68_dw_purge', 'sojib68_dw_ajax_purge' );
function sojib68_dw_ajax_purge() {
	sojib68_dw_guard();
	global $wpdb;
	$id = isset( $_POST['id'] ) ? (int) $_POST['id'] : 0;
	if ( $id ) {
		$wpdb->query( $wpdb->prepare( 'DELETE FROM ' . sojib68_dw_drafts_table() . ' WHERE id = %d AND deleted_at IS NOT NULL', $id ) );
	}
	wp_send_json_success();
}

/* ---------------------------------------------------------------------
 * Word file
 * ------------------------------------------------------------------ */

/** Dotted-path lookup: 'w1.name' in array( 'w1' => array( 'name' => ... ) ). */
function sojib68_dw_get( $arr, $path ) {
	foreach ( explode( '.', $path ) as $seg ) {
		if ( ! is_array( $arr ) || ! array_key_exists( $seg, $arr ) ) {
			return '';
		}
		$arr = $arr[ $seg ];
	}
	return is_scalar( $arr ) ? trim( (string) $arr ) : '';
}

/** Unicode value → Bijoy text safe to place inside <w:t>. */
function sojib68_dw_xml_value( $value ) {
	$b = sojib68_bijoy_from_unicode( (string) $value );
	$b = htmlspecialchars( $b, ENT_XML1 | ENT_QUOTES, 'UTF-8' );
	$b = str_replace( array( "\r\n", "\r" ), "\n", $b );
	return str_replace( array( "\n", "\t" ), array( '</w:t><w:br/><w:t xml:space="preserve">', '</w:t><w:tab/><w:t xml:space="preserve">' ), $b );
}

/** Replace {{tokens}} in $xml; $lookup is a callable( path ) → string. */
function sojib68_dw_fill_tokens( $xml, $lookup ) {
	return preg_replace_callback( '/\{\{([A-Za-z0-9_.]+)\}\}/', function ( $m ) use ( $lookup ) {
		return sojib68_dw_xml_value( call_user_func( $lookup, $m[1] ) );
	}, $xml );
}

function sojib68_dw_marker( $text ) {
	return '<w:p><w:r><w:t>' . $text . '</w:t></w:r></w:p>';
}

/** Loop block between @@loop:NAME@@ and @@end:NAME@@ marker paragraphs. */
function sojib68_dw_expand_loop( $xml, $name, $items, $item_filler ) {
	$open  = preg_quote( sojib68_dw_marker( '@@loop:' . $name . '@@' ), '#' );
	$close = preg_quote( sojib68_dw_marker( '@@end:' . $name . '@@' ), '#' );
	return preg_replace_callback( '#' . $open . '(.*?)' . $close . '#s', function ( $m ) use ( $items, $item_filler ) {
		$out = '';
		foreach ( $items as $i => $item ) {
			$out .= call_user_func( $item_filler, $m[1], $item, $i );
		}
		return $out;
	}, $xml );
}

/** Table rows carrying @@rows:NAME@@ are repeated once per item. */
function sojib68_dw_expand_rows( $xml, $name, $items ) {
	$marker = '@@rows:' . $name . '@@';
	return preg_replace_callback( '#<w:tr>.*?</w:tr>#s', function ( $m ) use ( $marker, $items ) {
		if ( false === strpos( $m[0], $marker ) ) {
			return $m[0];
		}
		$tpl = str_replace( $marker, '', $m[0] );
		$out = '';
		foreach ( $items as $item ) {
			// Vertical merge of the first cell (one khatian spanning several dag rows).
			$vm  = isset( $item['vm'] ) ? $item['vm'] : '';
			$row = str_replace( '@@vm@@', 'restart' === $vm ? '<w:vMerge w:val="restart"/>' : ( 'cont' === $vm ? '<w:vMerge/>' : '' ), $tpl );
			$out .= sojib68_dw_fill_tokens( $row, function ( $path ) use ( $item ) {
				return 0 === strpos( $path, 'r.' ) ? sojib68_dw_get( $item, substr( $path, 2 ) ) : '';
			} );
		}
		return $out;
	}, $xml );
}

/**
 * Singular / plural wording used in item 7 (আমি/আমরা, আপনি/আপনারা …). Keys starting with d_ follow the
 * number of donors, g_ the number of donees. The form can override any of them per deed.
 */
function sojib68_dw_vb_defaults() {
	return array(
		'd_ami'      => array( 'আমি', 'আমরা' ),
		'd_amar'     => array( 'আমার', 'আমাদের' ),
		'd_datar'    => array( 'দাতার', 'দাতাগণের', 'দাত্রীর', 'দাত্রীগণের' ),
		'g_apni'     => array( 'আপনি', 'আপনারা' ),
		'g_grohitar' => array( 'গ্রহিতার', 'গ্রহিতার', 'গ্রহিত্রীর', 'গ্রহিত্রীর' ),
		'g_grohita'  => array( 'গ্রহিতা', 'গ্রহিতাগণ', 'গ্রহিত্রী', 'গ্রহিত্রীগণ' ),
		'g_apnar'    => array( 'আপনার', 'আপনাদের' ),
	);
}

/** Build the .docx for a saved draft's data array; '' on failure. */
function sojib68_dw_build_docx( $data ) {
	$dir  = __DIR__ . '/deed-template/';
	$body = (string) file_get_contents( $dir . 'sale-deed.body.xml' );
	$sect = (string) file_get_contents( $dir . 'sale-deed.sect.xml' );
	if ( '' === $body ) {
		return '';
	}
	$out = isset( $data['out'] ) && is_array( $data['out'] ) ? $data['out'] : array();

	$party_filler = function ( $count, $group ) {
		return function ( $block, $p, $i ) use ( $count ) {
			$p = is_array( $p ) ? $p : array();
			$p['idx'] = $count > 1 ? ( $i + 1 ) . '। ' : '';
			// optional spouse line
			$block = preg_replace_callback(
				'#' . preg_quote( sojib68_dw_marker( '@@if:p.spouse@@' ), '#' ) . '(.*?)' . preg_quote( sojib68_dw_marker( '@@end:p.spouse@@' ), '#' ) . '#s',
				function ( $m ) use ( $p ) {
					return '' !== trim( (string) ( $p['spouse'] ?? '' ) ) ? $m[1] : '';
				},
				$block
			);
			return sojib68_dw_fill_tokens( $block, function ( $path ) use ( $p ) {
				if ( 'p.idx' === $path ) {
					return $p['idx'];
				}
				return 0 === strpos( $path, 'p.' ) ? sojib68_dw_get( $p, substr( $path, 2 ) ) : '';
			} );
		};
	};

	$donees = ! empty( $data['donees'] ) && is_array( $data['donees'] ) ? array_values( $data['donees'] ) : array( array() );
	$donors = ! empty( $data['donors'] ) && is_array( $data['donors'] ) ? array_values( $data['donors'] ) : array( array() );
	$body   = sojib68_dw_expand_loop( $body, 'donee', $donees, $party_filler( count( $donees ), 'donee' ) );
	$body   = sojib68_dw_expand_loop( $body, 'donor', $donors, $party_filler( count( $donors ), 'donor' ) );
	$reps   = ! empty( $data['rep'] ) && is_array( $data['rep'] ) ? array_values( $data['rep'] ) : array();
	$body   = sojib68_dw_expand_loop( $body, 'rep', array_slice( $reps, 0, 1 ), $party_filler( 1, 'rep' ) );

	// Singular/plural words for item 7: use what the form saved, else the defaults by number of donors/donees.
	$vb = isset( $out['vb'] ) && is_array( $out['vb'] ) ? $out['vb'] : array();
	foreach ( sojib68_dw_vb_defaults() as $k => $pair ) {
		if ( empty( $vb[ $k ] ) ) {
			$list      = 0 === strpos( $k, 'd_' ) ? $donors : $donees;
			$plural    = count( $list ) > 1 ? 1 : 0;
			$female    = count( $pair ) > 2 && count( $list ) > 0;
			foreach ( $list as $p ) {
				$female = $female && ( 'মহিলা' === ( $p['gender'] ?? '' ) || ( '' === ( $p['gender'] ?? '' ) && '' !== trim( (string) ( $p['spouse'] ?? '' ) ) ) );
			}
			$vb[ $k ]  = $pair[ $female ? $plural + 2 : $plural ];
		}
	}
	$out['vb'] = $vb;

	$rows = function ( $key ) use ( $out ) {
		return ! empty( $out[ $key ] ) && is_array( $out[ $key ] ) ? array_values( $out[ $key ] ) : array( array() );
	};
	$body = sojib68_dw_expand_rows( $body, 'donee_shares', $rows( 'donee_rows' ) );
	$body = sojib68_dw_expand_rows( $body, 'donor_shares', $rows( 'donor_rows' ) );
	$body = sojib68_dw_expand_rows( $body, 'schedule', $rows( 'schedule_rows' ) );

	// Land lines (e.g. ভিটি, পুকুর, বাড়ী …) each with its own area and price, then a total row when there are several.
	$land_rows = ! empty( $out['land_rows'] ) && is_array( $out['land_rows'] ) ? array_values( $out['land_rows'] )
		: array( array( 'area' => sojib68_dw_get( $out, 'area_text' ), 'class' => sojib68_dw_get( $out, 'land_class' ), 'price' => sojib68_dw_get( $out, 'price_text' ) ) );
	$land_total = count( $land_rows ) > 1 ? array( array( 'area' => sojib68_dw_get( $out, 'area_text' ), 'class' => 'মোট', 'price' => sojib68_dw_get( $out, 'price_text' ) ) ) : array();
	$body       = sojib68_dw_expand_rows( $body, 'lands', $land_rows );
	$body       = sojib68_dw_expand_rows( $body, 'lands_total', $land_total );
	// Blank room for the hand-drawn map (হাত নকশা) after item 15: N empty lines, N chosen in the form.
	$lines = isset( $out['sketch_lines'] ) ? (int) $out['sketch_lines'] : 10;
	$lines = max( 0, min( 40, $lines ) );
	$blank = (string) file_get_contents( $dir . 'blank-paragraph.xml' );
	$body  = str_replace( sojib68_dw_marker( '@@space:sketch@@' ), str_repeat( $blank, $lines ), $body );

	$body = sojib68_dw_fill_tokens( $body, function ( $path ) use ( $out ) {
		return sojib68_dw_get( $out, $path );
	} );

	$document = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
		. '<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"><w:body>'
		. $body . $sect . '</w:body></w:document>';
	return sojib68_docx_package( $document );
}

/**
 * File name for the download, by mouza: "<মৌজা নং> <মৌজা> - <দলিলের প্রকৃতি> - <গ্রহীতা> - <তারিখ>".
 * Characters Windows does not allow in file names are removed.
 */
function sojib68_dw_download_name( $data, $id ) {
	$f     = isset( $data['f'] ) && is_array( $data['f'] ) ? $data['f'] : array();
	$donee = isset( $data['donees'][0]['name'] ) ? (string) $data['donees'][0]['name'] : '';
	$no    = preg_replace( '/\s*নং\s*$/u', '', trim( (string) ( $f['mouza_no'] ?? '' ) ) );
	$mouza = trim( ( '' !== $no ? $no . 'নং ' : '' ) . trim( (string) ( $f['mouza_name'] ?? '' ) ) );
	$date  = ! empty( $f['exec_greg'] ) && preg_match( '/^\d{4}-\d{2}-\d{2}$/', $f['exec_greg'] ) ? $f['exec_greg'] : '';
	$parts = array_filter( array(
		$mouza ? 'মৌজা ' . $mouza : '',
		trim( (string) ( $f['deed_type'] ?? '' ) ),
		$donee ? 'গ্রহীতা ' . trim( $donee ) : '',
		$date,
	), 'strlen' );
	$name = implode( ' - ', $parts );
	$name = preg_replace( '#[\\\\/:*?"<>|\\x00-\\x1F]+#u', ' ', $name );
	$name = trim( preg_replace( '/\s+/u', ' ', $name ) );
	$name = trim( mb_substr( $name, 0, 110 ), " .-" );
	return '' !== $name ? $name : 'দলিল-' . (int) $id;
}

/** /office/deed-docx/ID — streams the Word file. */
function sojib68_dw_download( $id ) {
	$row = sojib68_dw_get_draft( $id );
	if ( ! $row ) {
		wp_die( 'খসড়া পাওয়া যায়নি।', '', array( 'response' => 404 ) );
	}
	$data = json_decode( $row['data'], true );
	$bin  = sojib68_dw_build_docx( is_array( $data ) ? $data : array() );
	if ( '' === $bin ) {
		wp_die( 'ফাইল তৈরি করা যায়নি।', '', array( 'response' => 500 ) );
	}
	nocache_headers();
	header( 'Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document' );
	$name = sojib68_dw_download_name( is_array( $data ) ? $data : array(), $id );
	// ASCII fallback + UTF-8 name (RFC 5987) so Bangla file names work in every browser.
	header( 'Content-Disposition: attachment; filename="deed-' . (int) $id . '.docx"; filename*=UTF-8\'\'' . rawurlencode( $name . '.docx' ) );
	header( 'Content-Length: ' . strlen( $bin ) );
	echo $bin; // phpcs:ignore WordPress.Security.EscapeOutput -- binary file.
}

/* ---------------------------------------------------------------------
 * Office page
 * ------------------------------------------------------------------ */

/* ---------------------------------------------------------------------
 * Mouza library (left sidebar): one saved profile per mouza, so a new deed for
 * that mouza starts with its union / JL no. / land office / ownership intro filled.
 * ------------------------------------------------------------------ */

function sojib68_dw_mouza_fields() {
	return array( 'name', 'no', 'union', 'upazila', 'district', 'jl_no', 'village', 'land_office', 'sub_office', 'intro' );
}

function sojib68_dw_get_mouzas() {
	$list = get_option( 'sojib68_dw_mouzas', array() );
	return is_array( $list ) ? $list : array();
}

add_action( 'wp_ajax_sojib68_dw_mouza_save', 'sojib68_dw_ajax_mouza_save' );
function sojib68_dw_ajax_mouza_save() {
	sojib68_dw_guard();
	$in = isset( $_POST['mouza'] ) ? json_decode( wp_unslash( $_POST['mouza'] ), true ) : null;
	if ( ! is_array( $in ) || '' === trim( (string) ( $in['name'] ?? '' ) ) ) {
		wp_send_json_error( array( 'message' => 'মৌজার নাম লিখুন।' ), 400 );
	}
	$clean = array();
	foreach ( sojib68_dw_mouza_fields() as $k ) {
		$clean[ $k ] = isset( $in[ $k ] ) ? mb_substr( sanitize_textarea_field( (string) $in[ $k ] ), 0, 'intro' === $k ? 8000 : 300 ) : '';
	}
	$list = sojib68_dw_get_mouzas();
	$id   = isset( $in['id'] ) ? preg_replace( '/[^a-z0-9]/', '', strtolower( (string) $in['id'] ) ) : '';
	if ( '' === $id || ! isset( $list[ $id ] ) ) {
		$id = 'm' . base_convert( (string) time(), 10, 36 ) . wp_rand( 10, 99 );
	}
	$clean['id']  = $id;
	$list[ $id ]  = $clean;
	update_option( 'sojib68_dw_mouzas', $list, false );
	wp_send_json_success( array( 'id' => $id ) );
}

add_action( 'wp_ajax_sojib68_dw_mouza_delete', 'sojib68_dw_ajax_mouza_delete' );
function sojib68_dw_ajax_mouza_delete() {
	sojib68_dw_guard();
	$id   = isset( $_POST['id'] ) ? preg_replace( '/[^a-z0-9]/', '', strtolower( (string) wp_unslash( $_POST['id'] ) ) ) : '';
	$list = sojib68_dw_get_mouzas();
	unset( $list[ $id ] );
	update_option( 'sojib68_dw_mouzas', $list, false );
	wp_send_json_success();
}

/* ---------------------------------------------------------------------
 * Ownership-chain templates (item 7): one saved text per ownership type
 * (e.g. ওয়ারিশ সূত্র, দলিল মূলে, নামজারি). Boxes are stored as ⟦label§label⟧.
 * ------------------------------------------------------------------ */

function sojib68_dw_get_owntpls() {
	$list = get_option( 'sojib68_dw_owntpls', array() );
	return is_array( $list ) ? $list : array();
}

add_action( 'wp_ajax_sojib68_dw_owntpl_save', 'sojib68_dw_ajax_owntpl_save' );
function sojib68_dw_ajax_owntpl_save() {
	sojib68_dw_guard();
	$name = isset( $_POST['name'] ) ? mb_substr( sanitize_text_field( wp_unslash( $_POST['name'] ) ), 0, 120 ) : '';
	$text = isset( $_POST['text'] ) ? mb_substr( sanitize_textarea_field( wp_unslash( $_POST['text'] ) ), 0, 20000 ) : '';
	if ( '' === trim( $name ) || '' === trim( $text ) ) {
		wp_send_json_error( array( 'message' => 'নাম ও লেখা দুটোই দরকার।' ), 400 );
	}
	$list = sojib68_dw_get_owntpls();
	$id   = isset( $_POST['id'] ) ? preg_replace( '/[^a-z0-9]/', '', strtolower( (string) wp_unslash( $_POST['id'] ) ) ) : '';
	if ( '' === $id || ! isset( $list[ $id ] ) ) {
		$id = 't' . base_convert( (string) time(), 10, 36 ) . wp_rand( 10, 99 );
	}
	$list[ $id ] = array( 'id' => $id, 'name' => $name, 'text' => $text );
	update_option( 'sojib68_dw_owntpls', $list, false );
	wp_send_json_success( array( 'id' => $id, 'tpl' => $list[ $id ] ) );
}

add_action( 'wp_ajax_sojib68_dw_owntpl_delete', 'sojib68_dw_ajax_owntpl_delete' );
function sojib68_dw_ajax_owntpl_delete() {
	sojib68_dw_guard();
	$id   = isset( $_POST['id'] ) ? preg_replace( '/[^a-z0-9]/', '', strtolower( (string) wp_unslash( $_POST['id'] ) ) ) : '';
	$list = sojib68_dw_get_owntpls();
	unset( $list[ $id ] );
	update_option( 'sojib68_dw_owntpls', $list, false );
	wp_send_json_success();
}

/** For matching only: Bangla/English digits alike, no "নং", spaces or punctuation. */
function sojib68_dw_norm( $s ) {
	$s = strtr( (string) $s, array( '০' => '0', '১' => '1', '২' => '2', '৩' => '3', '৪' => '4', '৫' => '5', '৬' => '6', '৭' => '7', '৮' => '8', '৯' => '9' ) );
	$s = preg_replace( '/নং|নম্বর|মৌজা|[\s\.\-:,।\/]+/u', '', $s );
	return mb_strtolower( (string) $s );
}

/**
 * Which mouza does a saved deed belong to? The saved mouza id first; otherwise the mouza name (and number
 * when several mouzas share the name), compared leniently so "১০১নং মামুদপুর" / "101 মামুদপুর" are the same.
 */
function sojib68_dw_draft_mouza_id( $data, $mouzas ) {
	$f = isset( $data['f'] ) && is_array( $data['f'] ) ? $data['f'] : array();
	if ( ! empty( $f['mouza_id'] ) && isset( $mouzas[ $f['mouza_id'] ] ) ) {
		return $f['mouza_id'];
	}
	$name    = sojib68_dw_norm( $f['mouza_name'] ?? '' );
	$name_no = '';
	if ( preg_match( '/\d+/', $name, $mm ) ) { // number typed inside the name ("১০১নং মামুদপুর")
		$name_no = $mm[0];
		$name    = preg_replace( '/\d+/', '', $name );
	}
	if ( '' === $name ) {
		return '';
	}
	$same = array();
	foreach ( $mouzas as $id => $m ) {
		if ( sojib68_dw_norm( $m['name'] ) === $name ) {
			$same[ $id ] = $m;
		}
	}
	if ( 1 === count( $same ) ) {
		return (string) key( $same );
	}
	$no = sojib68_dw_norm( $f['mouza_no'] ?? '' );
	$no = '' !== $no ? $no : $name_no;
	foreach ( $same as $id => $m ) {
		if ( sojib68_dw_norm( $m['no'] ) === $no ) {
			return $id;
		}
	}
	return '';
}

/**
 * @param string|null $base URL of the page that hosts the tool (office page by default, or the public /deed-writing page for signed-in office users).
 */
function sojib68_dw_render( $base = null ) {
	global $wpdb;
	$base = $base ? $base : home_url( '/' . SOJIB68_OFFICE_BASE . '/deed-write' );
	$ver  = static function ( $rel ) {
		$p = get_theme_file_path( $rel );
		return file_exists( $p ) ? (int) filemtime( $p ) : 1;
	};
	$edit_id   = isset( $_GET['draft'] ) ? (int) $_GET['draft'] : 0;
	$is_new    = isset( $_GET['new'] );
	$people_view = isset( $_GET['people'] ) && ! $edit_id && ! $is_new;
	$trash_view  = isset( $_GET['trash'] ) && ! $edit_id && ! $is_new && ! $people_view;
	sojib68_dw_purge_old();
	$n_trash = (int) $wpdb->get_var( 'SELECT COUNT(*) FROM ' . sojib68_dw_drafts_table() . ' WHERE deleted_at IS NOT NULL' );
	$mouzas    = sojib68_dw_get_mouzas();
	uasort( $mouzas, static function ( $x, $y ) {
		return strnatcasecmp( (string) $x['name'] . ' ' . (string) $x['no'], (string) $y['name'] . ' ' . (string) $y['no'] );
	} );
	$sel_mouza = isset( $_GET['mouza'] ) ? preg_replace( '/[^a-z0-9]/', '', strtolower( (string) wp_unslash( $_GET['mouza'] ) ) ) : '';
	$sel_none = isset( $_GET['mouza'] ) && 'none' === sanitize_key( wp_unslash( $_GET['mouza'] ) );
	if ( $sel_mouza && ! isset( $mouzas[ $sel_mouza ] ) ) {
		$sel_mouza = '';
	}
	$nonce = wp_create_nonce( 'sojib68_dw' );
	$ajax  = admin_url( 'admin-ajax.php' );

	// All saved deeds once: used for the list, and for the per-mouza counts in the sidebar.
	$draft_rows = array();
	$counts     = array();
	$n_none     = 0;
	if ( ! $edit_id && ! $is_new ) {
		$t    = sojib68_dw_drafts_table();
		$rows = (array) $wpdb->get_results( "SELECT id, title, data, created_at, updated_at FROM {$t} WHERE deleted_at IS NULL ORDER BY updated_at DESC LIMIT 500", ARRAY_A );
		foreach ( $rows as $r ) {
			$d   = json_decode( $r['data'], true );
			$mid = sojib68_dw_draft_mouza_id( is_array( $d ) ? $d : array(), $mouzas );
			$r['mid']   = $mid;
			$r['mname'] = is_array( $d ) && isset( $d['f']['mouza_name'] ) ? (string) $d['f']['mouza_name'] : '';
			unset( $r['data'] );
			$draft_rows[] = $r;
			if ( $mid ) {
				$counts[ $mid ] = ( $counts[ $mid ] ?? 0 ) + 1;
			} else {
				$n_none++;
			}
		}
	}
	?>
	<link rel="stylesheet" href="<?php echo esc_url( get_theme_file_uri( 'assets/css/deed-writer.css' ) ); ?>?v=<?php echo (int) $ver( 'assets/css/deed-writer.css' ); ?>">
	<div class="dw-layout">
		<aside class="dw-side">
			<h3>মৌজা তালিকা <small>(<?php echo (int) count( $mouzas ); ?>)</small></h3>
				<?php if ( $mouzas ) : ?>
					<input type="search" id="dw-mz-search" class="dw-mz-search" placeholder="মৌজা খুঁজুন…" autocomplete="off">
				<?php endif; ?>
			<ul class="dw-mouzas">
				<li class="<?php echo ! $sel_mouza && ! $sel_none && ! $edit_id && ! $is_new ? 'is-on' : ''; ?>"><a href="<?php echo esc_url( $base ); ?>">সব দলিল</a></li>
				<?php foreach ( $mouzas as $id => $m ) : ?>
					<li class="<?php echo $sel_mouza === $id ? 'is-on' : ''; ?>">
						<a class="dw-mz-name" href="<?php echo esc_url( add_query_arg( 'mouza', $id, $base ) ); ?>"><?php echo esc_html( ( $m['no'] ? preg_replace( '/\s*নং\s*$/u', '', $m['no'] ) . 'নং ' : '' ) . $m['name'] ); ?> <small>(<?php echo (int) ( $counts[ $id ] ?? 0 ); ?>)</small></a>
						<a class="dw-mz-new" href="<?php echo esc_url( add_query_arg( array( 'new' => '1', 'mouza' => $id ), $base ) ); ?>" title="এই মৌজায় নতুন দলিল">+ দলিল</a>
						<button type="button" class="dw-mz-edit" data-mouza-edit="<?php echo esc_attr( $id ); ?>" title="মৌজার তথ্য বদলান">✎</button>
					</li>
				<?php endforeach; ?>
				<?php if ( $n_none > 0 ) : ?>
					<li class="<?php echo $sel_none ? 'is-on' : ''; ?>"><a class="dw-mz-name" href="<?php echo esc_url( add_query_arg( 'mouza', 'none', $base ) ); ?>" title="মৌজা তালিকার কোনো মৌজার সঙ্গে মেলেনি">মৌজা মেলেনি <small>(<?php echo (int) $n_none; ?>)</small></a></li>
				<?php endif; ?>
			</ul>
			<?php if ( ! $mouzas ) : ?>
				<p class="dw-hint">এখনো কোনো মৌজা সেভ করা নেই। নিচের বোতামে মৌজা যোগ করুন, তারপর সেই মৌজার "+ দলিল" চাপলে ইউনিয়ন, জে.এল. নং, ভূমি অফিস ইত্যাদি নিজে নিজে ভরা থাকবে।</p>
			<?php endif; ?>
			<button type="button" class="btn btn-outline btn-sm" id="dw-mouza-add">+ নতুন মৌজা</button>
				<a class="btn btn-outline btn-sm" href="<?php echo esc_url( add_query_arg( 'people', '1', $base ) ); ?>">👥 সেভ করা ব্যক্তি (NID) দেখুন</a>
				<a class="btn btn-outline btn-sm" href="<?php echo esc_url( add_query_arg( 'trash', '1', $base ) ); ?>">🗑 রিসাইকেল বিন (<?php echo (int) $n_trash; ?>)</a>
				<button type="button" class="btn btn-outline btn-sm dw-imp-open" id="dw-import-open">📥 পুরোনো ফাইল থেকে তথ্য তুলুন</button>
		</aside>
		<div class="dw-main">
	<?php

	if ( $people_view ) {
		$q    = isset( $_GET['q'] ) ? trim( sanitize_text_field( wp_unslash( $_GET['q'] ) ) ) : '';
		$pg   = isset( $_GET['pg'] ) ? max( 1, (int) $_GET['pg'] ) : 1;
		$per  = 50;
		$pt   = sojib68_dw_parties_table();
		$where = '';
		$args  = array();
		if ( '' !== $q ) {
			$digits = sojib68_dw_nid_key( $q );
			$like   = '%' . $wpdb->esc_like( $q ) . '%';
			if ( strlen( $digits ) >= 3 ) {
				$where  = 'WHERE nid LIKE %s OR name LIKE %s OR data LIKE %s';
				$args[] = '%' . $wpdb->esc_like( $digits ) . '%';
			} else {
				$where  = 'WHERE name LIKE %s OR data LIKE %s';
			}
			$args[] = $like;
			$args[] = $like;
		}
		$total = (int) ( $args ? $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$pt} {$where}", $args ) ) : $wpdb->get_var( "SELECT COUNT(*) FROM {$pt}" ) );
		$sql   = "SELECT * FROM {$pt} {$where} ORDER BY updated_at DESC LIMIT %d OFFSET %d";
		$rows  = (array) $wpdb->get_results( $wpdb->prepare( $sql, array_merge( $args, array( $per, ( $pg - 1 ) * $per ) ) ), ARRAY_A );
		$pages = max( 1, (int) ceil( $total / $per ) );
		?>
		<h2 class="dw-ph">সেভ করা ব্যক্তি (NID) <small>— মোট <?php echo (int) $total; ?> জন<?php echo '' !== $q ? ' পাওয়া গেছে' : ''; ?></small></h2>
		<form method="get" action="<?php echo esc_url( $base ); ?>" class="dw-psearch">
			<input type="hidden" name="people" value="1">
			<input type="search" name="q" value="<?php echo esc_attr( $q ); ?>" placeholder="NID, নাম, পিতার নাম বা ঠিকানা দিয়ে খুঁজুন…">
			<button class="btn btn-primary btn-sm" type="submit">খুঁজুন</button>
			<a class="btn btn-outline btn-sm" href="<?php echo esc_url( $base ); ?>">← দলিলের তালিকা</a>
		</form>
		<?php if ( ! $rows ) : ?>
			<p class="office-muted">কোনো ব্যক্তি পাওয়া যায়নি।</p>
		<?php else : ?>
			<div class="office-card dw-ptable"><table class="office-table">
				<thead><tr><th>NID</th><th>নাম</th><th>পিতা</th><th>মাতা</th><th>স্বামী/স্ত্রী</th><th>স্থায়ী ঠিকানা</th><th></th></tr></thead>
				<tbody>
				<?php
				foreach ( $rows as $r ) :
					$d = json_decode( $r['data'], true );
					$d = is_array( $d ) ? $d : array();
					$p = array_merge( array( 'id' => (int) $r['id'], 'nid' => ! empty( $d['nid_display'] ) ? $d['nid_display'] : $r['nid'], 'name' => $r['name'] ), $d );
					?>
					<tr>
						<td><?php echo esc_html( $p['nid'] ); ?></td>
						<td><b><?php echo esc_html( $r['name'] ); ?></b></td>
						<td><?php echo esc_html( $d['father'] ?? '' ); ?></td>
						<td><?php echo esc_html( $d['mother'] ?? '' ); ?></td>
						<td><?php echo esc_html( $d['spouse'] ?? '' ); ?></td>
						<td class="dw-paddr"><?php echo esc_html( $d['perm'] ?? '' ); ?></td>
						<td class="dw-actions">
							<button type="button" class="btn btn-outline btn-sm dw-pedit" data-p="<?php echo esc_attr( wp_json_encode( $p, JSON_UNESCAPED_UNICODE ) ); ?>">বদলান</button>
							<button type="button" class="btn btn-outline btn-sm dw-pdel" data-id="<?php echo (int) $r['id']; ?>">মুছুন</button>
						</td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table></div>
			<?php if ( $pages > 1 ) : ?>
				<p class="dw-pager">
					<?php if ( $pg > 1 ) : ?><a class="btn btn-outline btn-sm" href="<?php echo esc_url( add_query_arg( array( 'people' => 1, 'q' => $q, 'pg' => $pg - 1 ), $base ) ); ?>">← আগের</a><?php endif; ?>
					পাতা <?php echo (int) $pg; ?> / <?php echo (int) $pages; ?>
					<?php if ( $pg < $pages ) : ?><a class="btn btn-outline btn-sm" href="<?php echo esc_url( add_query_arg( array( 'people' => 1, 'q' => $q, 'pg' => $pg + 1 ), $base ) ); ?>">পরের →</a><?php endif; ?>
				</p>
			<?php endif; ?>
		<?php endif; ?>
		<script>
		( function () {
			var cfg = { ajaxUrl: <?php echo wp_json_encode( $ajax ); ?>, nonce: <?php echo wp_json_encode( $nonce ); ?> };
			var F = [ [ 'nid', 'NID' ], [ 'name', 'নাম' ], [ 'spouse', 'স্বামী/স্ত্রী' ], [ 'father', 'পিতা' ], [ 'mother', 'মাতা' ], [ 'dob', 'জন্ম তারিখ' ], [ 'religion', 'ধর্ম' ], [ 'profession', 'পেশা' ], [ 'nationality', 'জাতীয়তা' ], [ 'perm', 'স্থায়ী ঠিকানা' ], [ 'present', 'বর্তমান ঠিকানা' ] ];
			function esc( s ) { return String( s == null ? '' : s ).replace( /[&<>"']/g, function ( c ) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[ c ]; } ); }
			function post( action, extra ) { var fd = new FormData(); fd.append( 'action', action ); fd.append( 'nonce', cfg.nonce ); Object.keys( extra ).forEach( function ( k ) { fd.append( k, extra[ k ] ); } ); return fetch( cfg.ajaxUrl, { method: 'POST', body: fd, credentials: 'same-origin' } ).then( function ( r ) { return r.json(); } ); }
			document.addEventListener( 'click', function ( e ) {
				var del = e.target.closest && e.target.closest( '.dw-pdel' ), ed = e.target.closest && e.target.closest( '.dw-pedit' );
				if ( del && window.confirm( 'এই ব্যক্তির সেভ করা তথ্য মুছবেন? (আগের দলিলে প্রভাব পড়বে না, ফেরত আনা যাবে না)' ) ) { post( 'sojib68_dw_party_delete', { id: del.getAttribute( 'data-id' ) } ).then( function () { location.reload(); } ); }
				if ( ed ) {
					var p = JSON.parse( ed.getAttribute( 'data-p' ) ), d = document.createElement( 'div' ); d.className = 'dw-tpl-dlg';
					d.innerHTML = '<div class="dw-tpl-box"><h3>ব্যক্তির তথ্য বদলান</h3><div class="dw-imp-g">' + F.map( function ( f ) { return '<label>' + f[ 1 ] + '<input type="text" data-k="' + f[ 0 ] + '" value="' + esc( p[ f[ 0 ] ] ) + '"></label>'; } ).join( '' ) +
						'<label>লিঙ্গ<select data-k="gender"><option value="">—</option><option' + ( p.gender === 'পুরুষ' ? ' selected' : '' ) + '>পুরুষ</option><option' + ( p.gender === 'মহিলা' ? ' selected' : '' ) + '>মহিলা</option></select></label></div>' +
						'<div class="dw-tpl-act"><button type="button" class="btn btn-primary btn-sm" data-ok>সেভ করুন</button> <button type="button" class="btn btn-outline btn-sm" data-no>বাতিল</button> <span class="dw-hint" data-msg></span></div></div>';
					document.body.appendChild( d );
					d.querySelector( '[data-no]' ).addEventListener( 'click', function () { d.remove(); } );
					d.querySelector( '[data-ok]' ).addEventListener( 'click', function () {
						var o = {}; [].forEach.call( d.querySelectorAll( '[data-k]' ), function ( i ) { o[ i.getAttribute( 'data-k' ) ] = i.value.trim(); } );
						post( 'sojib68_dw_party_update', { id: p.id, person: JSON.stringify( o ) } ).then( function ( res ) { if ( res && res.success ) { location.reload(); } else { d.querySelector( '[data-msg]' ).textContent = '⚠ ' + ( ( res && res.data && res.data.message ) || 'সেভ হয়নি' ); } } );
					} );
				}
			} );
		}() );
		</script>
		<?php
	} elseif ( $trash_view ) {
		$t    = sojib68_dw_drafts_table();
		$rows = (array) $wpdb->get_results( "SELECT id, title, data, updated_at, deleted_at FROM {$t} WHERE deleted_at IS NOT NULL ORDER BY deleted_at DESC LIMIT 500", ARRAY_A );
		?>
		<h2 class="dw-ph">🗑 রিসাইকেল বিন <small>— মোছা দলিল <?php echo (int) SOJIB68_DW_TRASH_DAYS; ?> দিন পর্যন্ত এখানে থাকে, তারপর নিজে নিজে স্থায়ীভাবে মুছে যায়</small></h2>
		<p><a class="btn btn-outline btn-sm" href="<?php echo esc_url( $base ); ?>">← দলিলের তালিকা</a></p>
		<?php if ( ! $rows ) : ?>
			<p class="office-muted">রিসাইকেল বিন খালি।</p>
		<?php else : ?>
			<div class="office-card"><table class="office-table dw-list">
				<thead><tr><th>দলিল</th><th>মোছা হয়েছে</th><th>ফেরত আনার বাকি সময়</th><th></th></tr></thead>
				<tbody>
				<?php
				foreach ( $rows as $r ) :
					$d    = json_decode( $r['data'], true );
					$left = max( 0, (int) ceil( ( strtotime( $r['deleted_at'] ) + SOJIB68_DW_TRASH_DAYS * DAY_IN_SECONDS - sojib68_dw_now_ts() ) / DAY_IN_SECONDS ) );
					$mn   = is_array( $d ) && isset( $d['f']['mouza_name'] ) ? (string) $d['f']['mouza_name'] : '';
					?>
					<tr>
						<td><?php echo esc_html( '' !== $r['title'] ? $r['title'] : 'নামহীন দলিল #' . (int) $r['id'] ); ?><?php echo '' !== $mn ? ' <small>(' . esc_html( $mn ) . ')</small>' : ''; ?></td>
						<td><?php echo esc_html( $r['deleted_at'] ); ?></td>
						<td><?php echo (int) $left; ?> দিন</td>
						<td class="dw-actions">
							<button type="button" class="btn btn-primary btn-sm dw-restore" data-id="<?php echo (int) $r['id']; ?>">↩ ফেরত আনুন</button>
							<button type="button" class="btn btn-outline btn-sm dw-purge" data-id="<?php echo (int) $r['id']; ?>">স্থায়ীভাবে মুছুন</button>
						</td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table></div>
		<?php endif; ?>
		<script>
		( function () {
			var cfg = { ajaxUrl: <?php echo wp_json_encode( $ajax ); ?>, nonce: <?php echo wp_json_encode( $nonce ); ?> };
			document.addEventListener( 'click', function ( e ) {
				var r = e.target.closest && e.target.closest( '.dw-restore' ), p = e.target.closest && e.target.closest( '.dw-purge' ), b = r || p;
				if ( ! b ) { return; }
				if ( p && ! window.confirm( 'এই দলিল স্থায়ীভাবে মুছে ফেলবেন? আর ফেরত আনা যাবে না।' ) ) { return; }
				var fd = new FormData(); fd.append( 'action', r ? 'sojib68_dw_restore' : 'sojib68_dw_purge' ); fd.append( 'id', b.getAttribute( 'data-id' ) ); fd.append( 'nonce', cfg.nonce );
				fetch( cfg.ajaxUrl, { method: 'POST', body: fd, credentials: 'same-origin' } ).then( function () { location.reload(); } );
			} );
		}() );
		</script>
		<?php
	} elseif ( ! $edit_id && ! $is_new ) {
		$show = array_values( array_filter( $draft_rows, function ( $r ) use ( $sel_mouza, $sel_none ) {
			if ( $sel_none ) {
				return '' === $r['mid'];
			}
			return ! $sel_mouza || $r['mid'] === $sel_mouza;
		} ) );
		$new_url  = add_query_arg( array_filter( array( 'new' => '1', 'mouza' => $sel_mouza ) ), $base );
		$n_deeds  = (int) $wpdb->get_var( 'SELECT COUNT(*) FROM ' . sojib68_dw_drafts_table() . ' WHERE deleted_at IS NULL' );
		$n_people = (int) $wpdb->get_var( 'SELECT COUNT(*) FROM ' . sojib68_dw_parties_table() );
		$n_week   = (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM ' . sojib68_dw_drafts_table() . ' WHERE deleted_at IS NULL AND updated_at >= %s', gmdate( 'Y-m-d H:i:s', time() + (int) ( get_option( 'gmt_offset', 0 ) * HOUR_IN_SECONDS ) - 7 * DAY_IN_SECONDS ) ) );
		?>
		<div class="dw-stats">
			<div><b><?php echo (int) $n_deeds; ?></b><span>মোট দলিল</span></div>
			<a class="dw-stat-link" href="<?php echo esc_url( add_query_arg( 'people', '1', $base ) ); ?>"><b><?php echo (int) $n_people; ?></b><span>সেভ করা NID (ব্যক্তি) — দেখুন →</span></a>
			<div><b><?php echo (int) count( $mouzas ); ?></b><span>মৌজা</span></div>
			<div><b><?php echo (int) count( sojib68_dw_get_owntpls() ); ?></b><span>মালিকানা টেমপ্লেট</span></div>
			<div><b><?php echo (int) $n_week; ?></b><span>গত ৭ দিনে বদলানো দলিল</span></div>
		</div>
		<?php if ( $sel_none ) : ?>
			<p class="office-muted">এই দলিলগুলোর মৌজার নাম/নম্বর সেভ করা কোনো মৌজার সঙ্গে মেলেনি, তাই কোনো মৌজার তালিকায় আসেনি। দলিল খুলে "মৌজা" অংশ থেকে সেভ করা মৌজাটি বেছে নিলে ঠিক মৌজার তালিকায় চলে যাবে।</p>
		<?php endif; ?>
		<p><a class="btn btn-primary" href="<?php echo esc_url( $new_url ); ?>">+ নতুন দলিল লিখুন<?php echo $sel_mouza ? ' (' . esc_html( $mouzas[ $sel_mouza ]['name'] ) . ' মৌজা)' : ''; ?></a></p>
		<?php if ( ! $show ) : ?>
			<p class="office-muted">এখানে এখনো কোনো দলিল নেই।</p>
		<?php else : ?>
			<div class="office-card"><table class="office-table dw-list">
				<thead><tr><th>দলিল</th><th>মৌজা</th><th>শেষ সংরক্ষণ</th><th></th></tr></thead>
				<tbody>
				<?php foreach ( $show as $r ) : ?>
					<tr>
						<td><a href="<?php echo esc_url( add_query_arg( 'draft', (int) $r['id'], $base ) ); ?>"><?php echo esc_html( '' !== $r['title'] ? $r['title'] : 'নামহীন দলিল #' . (int) $r['id'] ); ?></a></td>
						<td><?php echo esc_html( $r['mname'] ); ?></td>
						<td><?php echo esc_html( $r['updated_at'] ); ?></td>
						<td class="dw-actions">
							<a class="btn btn-outline btn-sm" href="<?php echo esc_url( add_query_arg( 'draft', (int) $r['id'], $base ) ); ?>">খুলুন</a>
							<a class="btn btn-primary btn-sm" href="<?php echo esc_url( home_url( '/' . SOJIB68_OFFICE_BASE . '/deed-docx/' . (int) $r['id'] ) ); ?>">Word ডাউনলোড</a>
							<button type="button" class="btn btn-outline btn-sm dw-del" data-id="<?php echo (int) $r['id']; ?>">মুছুন</button>
						</td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table></div>
		<?php endif; ?>
		<script>
		window.sojib68DwList = { ajaxUrl: <?php echo wp_json_encode( $ajax ); ?>, nonce: <?php echo wp_json_encode( $nonce ); ?> };
		document.addEventListener('click', function (e) {
			var b = e.target.closest && e.target.closest('.dw-del');
			if (!b || !window.confirm('এই দলিল রিসাইকেল বিনে পাঠাবেন? (৭ দিনের মধ্যে ফেরত আনা যাবে)')) { return; }
			var fd = new FormData(); fd.append('action', 'sojib68_dw_delete'); fd.append('id', b.getAttribute('data-id')); fd.append('nonce', window.sojib68DwList.nonce);
			fetch(window.sojib68DwList.ajaxUrl, { method: 'POST', body: fd, credentials: 'same-origin' }).then(function () { location.reload(); });
		});
		</script>
		<?php
	} else {
		$draft = $edit_id ? sojib68_dw_get_draft( $edit_id ) : null;
		if ( $edit_id && ! $draft ) {
			echo '<p>খসড়া পাওয়া যায়নি। <a href="' . esc_url( $base ) . '">তালিকায় ফিরুন</a></p>';
		} else {
			$data = $draft ? json_decode( $draft['data'], true ) : array();
			$boot = array(
				'ajaxUrl'   => $ajax,
				'nonce'     => $nonce,
				'id'        => $draft ? (int) $draft['id'] : 0,
				'data'      => is_array( $data ) ? $data : new stdClass(),
				'defaults'  => get_option( 'sojib68_dw_defaults', array() ),
				'newMouza'  => ( ! $draft && $sel_mouza ) ? $mouzas[ $sel_mouza ] : null,
				'mouzas'    => array_values( $mouzas ),
				'owntpls'   => array_values( sojib68_dw_get_owntpls() ),
				'listUrl'   => $base,
				'docxUrl'   => home_url( '/' . SOJIB68_OFFICE_BASE . '/deed-docx/' ),
			);
			?>
			<div id="dw-app" class="dw-app" aria-live="polite"></div>
			<script>window.sojib68Dw = <?php echo wp_json_encode( $boot, JSON_UNESCAPED_UNICODE ); ?>;</script>
			<script src="<?php echo esc_url( get_theme_file_uri( 'assets/js/deed-writer.js' ) ); ?>?v=<?php echo (int) $ver( 'assets/js/deed-writer.js' ); ?>"></script>
			<?php
		}
	}
	?>
		</div>
	</div>

	<dialog id="dw-mouza-dlg" class="dw-dlg">
		<form method="dialog" id="dw-mouza-form">
			<h3 id="dw-mouza-title">মৌজার তথ্য</h3>
			<div class="dw-grid dw-dlg-grid">
				<label class="dw-f"><span>মৌজার নাম *</span><input name="name" required placeholder="যেমন তিয়রী"></label>
				<label class="dw-f"><span>মৌজা নম্বর</span><input name="no" placeholder="যেমন 99"></label>
				<label class="dw-f"><span>ইউনিয়ন/ওয়ার্ড</span><input name="union" placeholder="যেমন ৯ নং ভোলাকোট"></label>
				<label class="dw-f"><span>উপজেলা/থানা</span><input name="upazila"></label>
				<label class="dw-f"><span>জেলা</span><input name="district"></label>
				<label class="dw-f"><span>জে.এল. নং</span><input name="jl_no" placeholder="যেমন সি.এস ১৪১ আর.এস ৯৯ নং"></label>
				<label class="dw-f"><span>গ্রাম</span><input name="village"></label>
				<label class="dw-f dw-wide"><span>ভূমি অফিসের নাম</span><input name="land_office"></label>
				<label class="dw-f dw-wide"><span>রেজিস্ট্রি অফিসের নাম</span><input name="sub_office"></label>
				<label class="dw-f dw-full"><span>মালিকানার বিবরণের শুরুর অংশ (এই মৌজার সব দলিলে একই থাকে)</span><textarea name="intro" rows="5" placeholder="যেমন: জেলা-…, উপজেলা ও সাব-রেজিস্ট্রি অফিস … পরগনা … তৌজিভুক্ত … সি.এস … আর.এস …"></textarea></label>
			</div>
			<input type="hidden" name="id">
			<div class="dw-dlg-actions">
				<button type="submit" class="btn btn-primary btn-sm" value="save">সেভ করুন</button>
				<button type="button" class="btn btn-outline btn-sm" id="dw-mouza-cancel">বাতিল</button>
				<button type="button" class="btn btn-outline btn-sm dw-x" id="dw-mouza-del" hidden>এই মৌজা মুছুন</button>
			</div>
		</form>
	</dialog>
	<script>window.sojib68DwM = { ajaxUrl: <?php echo wp_json_encode( $ajax ); ?>, nonce: <?php echo wp_json_encode( $nonce ); ?>, mouzas: <?php echo wp_json_encode( $mouzas, JSON_UNESCAPED_UNICODE ); ?> };</script>
	<script src="<?php echo esc_url( get_theme_file_uri( 'assets/js/deed-mouzas.js' ) ); ?>?v=<?php echo (int) $ver( 'assets/js/deed-mouzas.js' ); ?>"></script>
	<script src="<?php echo esc_url( get_theme_file_uri( 'assets/js/deed-import.js' ) ); ?>?v=<?php echo (int) $ver( 'assets/js/deed-import.js' ); ?>"></script>
	<?php
}
