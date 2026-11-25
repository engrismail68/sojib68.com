<?php
/**
 * Deed list tools: status (draft / written / registered), search + date filters, per-day counts, CSV export,
 * and full backup / restore of everything the deed tool stores.
 * A "দলিল লেখক" user only ever touches their own data (see sojib68_dw_scoped()); admin / office staff see all.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function sojib68_dw_statuses() {
	return array( 'draft' => 'খসড়া', 'done' => 'লেখা সম্পন্ন', 'registered' => 'রেজিস্ট্রি হয়েছে' );
}

/** Pull the searchable / reportable facts out of a saved deed. */
function sojib68_dw_row_meta( $d ) {
	$f      = isset( $d['f'] ) && is_array( $d['f'] ) ? $d['f'] : array();
	$out    = isset( $d['out'] ) && is_array( $d['out'] ) ? $d['out'] : array();
	$names  = function ( $key ) use ( $d ) {
		$o = array();
		foreach ( (array) ( $d[ $key ] ?? array() ) as $p ) {
			if ( is_array( $p ) && '' !== trim( (string) ( $p['name'] ?? '' ) ) ) {
				$o[] = trim( (string) $p['name'] );
			}
		}
		return $o;
	};
	$nids = array();
	foreach ( array( 'donors', 'donees' ) as $g ) {
		foreach ( (array) ( $d[ $g ] ?? array() ) as $p ) {
			if ( is_array( $p ) && ! empty( $p['nid'] ) ) {
				$nids[] = sojib68_dw_nid_key( $p['nid'] );
			}
		}
	}
	$st = isset( $f['status'] ) && isset( sojib68_dw_statuses()[ $f['status'] ] ) ? $f['status'] : 'draft';
	return array(
		'status'   => $st,
		'reg_date' => isset( $f['reg_date'] ) ? (string) $f['reg_date'] : '',
		'reg_no'   => isset( $f['reg_no'] ) ? trim( (string) $f['reg_no'] ) : '',
		'exec'     => isset( $f['exec_greg'] ) ? (string) $f['exec_greg'] : '',
		'type'     => isset( $f['deed_type'] ) ? (string) $f['deed_type'] : '',
		'donors'   => $names( 'donors' ),
		'donees'   => $names( 'donees' ),
		'nids'     => $nids,
		'mouza'    => isset( $f['mouza_name'] ) ? (string) $f['mouza_name'] : '',
		'area'     => isset( $out['area_text'] ) ? (string) $out['area_text'] : '',
		'price'    => isset( $out['price_text'] ) ? (string) $out['price_text'] : '',
	);
}

/** All of this user's (non-deleted) deeds with their meta, newest first. */
function sojib68_dw_load_rows( $mouzas ) {
	global $wpdb;
	$t    = sojib68_dw_drafts_table();
	$rows = (array) $wpdb->get_results( "SELECT id, title, data, created_at, updated_at FROM {$t} WHERE deleted_at IS NULL" . sojib68_dw_draft_scope() . ' ORDER BY updated_at DESC LIMIT 2000', ARRAY_A );
	$out  = array();
	foreach ( $rows as $r ) {
		$d          = json_decode( $r['data'], true );
		$d          = is_array( $d ) ? $d : array();
		$r['mid']   = sojib68_dw_draft_mouza_id( $d, $mouzas );
		$r['meta']  = sojib68_dw_row_meta( $d );
		$r['mname'] = $r['meta']['mouza'];
		unset( $r['data'] );
		$out[] = $r;
	}
	return $out;
}

function sojib68_dw_filters() {
	$g  = function ( $k ) {
		return isset( $_GET[ $k ] ) ? sanitize_text_field( wp_unslash( $_GET[ $k ] ) ) : '';
	};
	$dt = $g( 'dt' );
	$st = $g( 'st' );
	$date = function ( $v ) {
		return preg_match( '/^\d{4}-\d{2}-\d{2}$/', $v ) ? $v : '';
	};
	return array(
		'q'    => mb_substr( $g( 'q' ), 0, 80 ),
		'st'   => isset( sojib68_dw_statuses()[ $st ] ) ? $st : '',
		'dt'   => in_array( $dt, array( 'created', 'exec', 'reg' ), true ) ? $dt : 'created',
		'from' => $date( $g( 'from' ) ),
		'to'   => $date( $g( 'to' ) ),
	);
}

function sojib68_dw_filters_active( $f ) {
	return '' !== $f['q'] || '' !== $f['st'] || '' !== $f['from'] || '' !== $f['to'];
}

function sojib68_dw_row_date( $r, $dt ) {
	if ( 'exec' === $dt ) {
		return $r['meta']['exec'];
	}
	if ( 'reg' === $dt ) {
		return $r['meta']['reg_date'];
	}
	return substr( (string) $r['created_at'], 0, 10 );
}

function sojib68_dw_filter_rows( $rows, $f, $sel_mouza = '', $sel_none = false ) {
	$q = mb_strtolower( sojib68_dw_bn_digits( $f['q'] ) );
	return array_values( array_filter( $rows, function ( $r ) use ( $f, $q, $sel_mouza, $sel_none ) {
		if ( $sel_none ) {
			if ( '' !== $r['mid'] ) {
				return false;
			}
		} elseif ( $sel_mouza && $r['mid'] !== $sel_mouza ) {
			return false;
		}
		if ( '' !== $f['st'] && $r['meta']['status'] !== $f['st'] ) {
			return false;
		}
		if ( '' !== $f['from'] || '' !== $f['to'] ) {
			$d = sojib68_dw_row_date( $r, $f['dt'] );
			if ( '' === $d || ( '' !== $f['from'] && $d < $f['from'] ) || ( '' !== $f['to'] && $d > $f['to'] ) ) {
				return false;
			}
		}
		if ( '' !== $q ) {
			$m   = $r['meta'];
			$hay = mb_strtolower( sojib68_dw_bn_digits( $r['title'] . ' ' . implode( ' ', $m['donors'] ) . ' ' . implode( ' ', $m['donees'] ) . ' ' . implode( ' ', $m['nids'] ) . ' ' . $m['mouza'] . ' ' . $m['reg_no'] . ' ' . $m['type'] ) );
			foreach ( preg_split( '/\s+/u', $q, -1, PREG_SPLIT_NO_EMPTY ) as $word ) {
				if ( false === mb_strpos( $hay, $word ) ) {
					return false;
				}
			}
		}
		return true;
	} ) );
}

/* ---------------------------------------------------------------------
 * Change the status from the list (and the registry date / number)
 * ------------------------------------------------------------------ */
add_action( 'wp_ajax_sojib68_dw_set_status', function () {
	sojib68_dw_guard();
	global $wpdb;
	$id  = isset( $_POST['id'] ) ? (int) $_POST['id'] : 0;
	$st  = isset( $_POST['status'] ) ? sanitize_key( wp_unslash( $_POST['status'] ) ) : '';
	$row = $id ? sojib68_dw_get_draft( $id ) : null;
	if ( ! $row || ! isset( sojib68_dw_statuses()[ $st ] ) ) {
		wp_send_json_error( array( 'message' => 'দলিল পাওয়া যায়নি।' ), 404 );
	}
	$data = json_decode( $row['data'], true );
	if ( ! is_array( $data ) || ! isset( $data['f'] ) ) {
		wp_send_json_error( array( 'message' => 'দলিলের তথ্য পড়া যায়নি।' ), 500 );
	}
	$data['f']['status'] = $st;
	if ( 'registered' === $st ) {
		$rd = isset( $_POST['reg_date'] ) ? sanitize_text_field( wp_unslash( $_POST['reg_date'] ) ) : '';
		$data['f']['reg_date'] = preg_match( '/^\d{4}-\d{2}-\d{2}$/', $rd ) ? $rd : substr( current_time( 'mysql' ), 0, 10 );
		$data['f']['reg_no']   = mb_substr( sanitize_text_field( wp_unslash( $_POST['reg_no'] ?? '' ) ), 0, 60 );
	}
	$wpdb->update( sojib68_dw_drafts_table(), array( 'data' => wp_json_encode( $data, JSON_UNESCAPED_UNICODE ), 'updated_at' => current_time( 'mysql' ) ), array( 'id' => $id ), array( '%s', '%s' ), array( '%d' ) );
	wp_send_json_success( array( 'status' => $st, 'reg_date' => $data['f']['reg_date'] ?? '', 'reg_no' => $data['f']['reg_no'] ?? '' ) );
} );

/* ---------------------------------------------------------------------
 * CSV of the (filtered) list: /deed-writing/?dwcsv=1&…same filters…
 * ------------------------------------------------------------------ */
add_action( 'template_redirect', function () {
	if ( ! isset( $_GET['dwcsv'] ) ) {
		return;
	}
	if ( ! sojib68_dw_can_use() ) {
		wp_safe_redirect( home_url( '/deed-writing/' ) );
		exit;
	}
	$mouzas = sojib68_dw_get_mouzas();
	$sel    = isset( $_GET['mouza'] ) ? preg_replace( '/[^a-z0-9]/', '', strtolower( (string) wp_unslash( $_GET['mouza'] ) ) ) : '';
	$none   = isset( $_GET['mouza'] ) && 'none' === sanitize_key( wp_unslash( $_GET['mouza'] ) );
	$sel    = isset( $mouzas[ $sel ] ) ? $sel : '';
	$f      = sojib68_dw_filters();
	$rows   = sojib68_dw_filter_rows( sojib68_dw_load_rows( $mouzas ), $f, $sel, $none );
	$labels = sojib68_dw_statuses();

	nocache_headers();
	header( 'Content-Type: text/csv; charset=utf-8' );
	header( 'Content-Disposition: attachment; filename="dolil-talika-' . gmdate( 'Y-m-d' ) . '.csv"' );
	echo "\xEF\xBB\xBF"; // UTF-8 BOM so Excel reads Bangla correctly.
	$fh = fopen( 'php://output', 'w' );
	fputcsv( $fh, array( 'ক্রম', 'লেখার তারিখ', 'সম্পাদনের তারিখ', 'দলিলের নাম', 'দলিলের ধরন', 'দাতা', 'গ্রহীতা', 'মৌজা', 'জমির পরিমাণ', 'মূল্য', 'অবস্থা', 'রেজিস্ট্রির তারিখ', 'রেজিস্ট্রি/দলিল নম্বর' ) );
	foreach ( $rows as $i => $r ) {
		$m = $r['meta'];
		fputcsv( $fh, array( $i + 1, substr( (string) $r['created_at'], 0, 10 ), $m['exec'], $r['title'], $m['type'], implode( ', ', $m['donors'] ), implode( ', ', $m['donees'] ), $m['mouza'], $m['area'], $m['price'], $labels[ $m['status'] ], $m['reg_date'], $m['reg_no'] ) );
	}
	fclose( $fh );
	exit;
}, 2 );

/* ---------------------------------------------------------------------
 * Full backup (JSON) and restore
 * ------------------------------------------------------------------ */

function sojib68_dw_backup_payload() {
	global $wpdb;
	$t     = sojib68_dw_drafts_table();
	$p     = sojib68_dw_parties_table();
	$deeds = array();
	foreach ( (array) $wpdb->get_results( "SELECT id, title, data, created_by, created_at, updated_at, deleted_at FROM {$t} WHERE 1=1" . sojib68_dw_draft_scope() . ' ORDER BY id ASC', ARRAY_A ) as $r ) {
		$r['data'] = json_decode( $r['data'], true );
		$deeds[]   = $r;
	}
	$people = array();
	foreach ( (array) $wpdb->get_results( "SELECT nid, owner, name, data, updated_at FROM {$p} WHERE 1=1" . sojib68_dw_party_scope() . ' ORDER BY id ASC', ARRAY_A ) as $r ) {
		$r['data'] = json_decode( $r['data'], true );
		$people[]  = $r;
	}
	return array(
		'app'      => 'sojib68-dolil-backup',
		'version'  => 1,
		'created'  => current_time( 'mysql' ),
		'scope'    => sojib68_dw_scoped() ? 'own' : 'all',
		'deeds'    => $deeds,
		'people'   => $people,
		'mouzas'   => array_values( sojib68_dw_get_mouzas() ),
		'owntpls'  => array_values( sojib68_dw_get_owntpls() ),
	);
}

add_action( 'template_redirect', function () {
	if ( ! isset( $_GET['dwbackup'] ) ) {
		return;
	}
	if ( ! sojib68_dw_can_use() || ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'sojib68_dw_backup' ) ) {
		wp_safe_redirect( home_url( '/deed-writing/' ) );
		exit;
	}
	$payload = sojib68_dw_backup_payload();
	update_user_meta( get_current_user_id(), 'sojib68_dw_last_backup', current_time( 'mysql' ) );
	nocache_headers();
	header( 'Content-Type: application/json; charset=utf-8' );
	header( 'Content-Disposition: attachment; filename="dolil-backup-' . gmdate( 'Y-m-d-His' ) . '.json"' );
	echo wp_json_encode( $payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT ); // phpcs:ignore WordPress.Security.EscapeOutput
	exit;
}, 2 );

add_action( 'wp_ajax_sojib68_dw_restore_backup', function () {
	sojib68_dw_guard();
	global $wpdb;
	if ( empty( $_FILES['file']['tmp_name'] ) || ! is_uploaded_file( $_FILES['file']['tmp_name'] ) || (int) $_FILES['file']['size'] > 25 * MB_IN_BYTES ) {
		wp_send_json_error( array( 'message' => 'ফাইল পাওয়া যায়নি বা খুব বড় (সর্বোচ্চ ২৫ MB)।' ), 400 );
	}
	$j = json_decode( (string) file_get_contents( $_FILES['file']['tmp_name'] ), true ); // phpcs:ignore WordPress.WP.AlternativeFunctions
	if ( ! is_array( $j ) || ( $j['app'] ?? '' ) !== 'sojib68-dolil-backup' ) {
		wp_send_json_error( array( 'message' => 'এটি এই সিস্টেমের ব্যাকআপ ফাইল নয়।' ), 400 );
	}
	$scoped = sojib68_dw_scoped();
	$me     = get_current_user_id();
	$dt     = sojib68_dw_drafts_table();
	$pt     = sojib68_dw_parties_table();
	$n      = array( 'deeds' => 0, 'deeds_skipped' => 0, 'people' => 0, 'mouzas' => 0, 'tpls' => 0 );

	foreach ( (array) ( $j['deeds'] ?? array() ) as $r ) {
		if ( ! is_array( $r ) || ! is_array( $r['data'] ?? null ) ) {
			continue;
		}
		$data = sojib68_dw_clean( $r['data'] );
		$json = wp_json_encode( $data, JSON_UNESCAPED_UNICODE );
		$by   = $scoped ? $me : ( get_userdata( (int) ( $r['created_by'] ?? 0 ) ) ? (int) $r['created_by'] : $me );
		if ( (int) $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$dt} WHERE created_by = %d AND MD5(data) = %s LIMIT 1", $by, md5( $json ) ) ) ) {
			$n['deeds_skipped']++;
			continue;
		}
		$ts = function ( $v ) {
			return preg_match( '/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', (string) $v ) ? $v : current_time( 'mysql' );
		};
		$wpdb->insert( $dt, array( 'title' => mb_substr( sanitize_text_field( (string) ( $r['title'] ?? '' ) ), 0, 190 ), 'data' => $json, 'created_by' => $by, 'created_at' => $ts( $r['created_at'] ?? '' ), 'updated_at' => $ts( $r['updated_at'] ?? '' ), 'deleted_at' => ( ! empty( $r['deleted_at'] ) ? $ts( $r['deleted_at'] ) : null ) ), array( '%s', '%s', '%d', '%s', '%s', '%s' ) );
		$n['deeds']++;
	}
	foreach ( (array) ( $j['people'] ?? array() ) as $r ) {
		if ( ! is_array( $r ) ) {
			continue;
		}
		$key = sojib68_dw_nid_key( $r['nid'] ?? '' );
		if ( strlen( $key ) < 8 || '' === trim( (string) ( $r['name'] ?? '' ) ) ) {
			continue;
		}
		$owner = $scoped ? $me : ( (int) ( $r['owner'] ?? 0 ) && get_userdata( (int) $r['owner'] ) ? (int) $r['owner'] : 0 );
		$rows  = $wpdb->query( $wpdb->prepare( "INSERT IGNORE INTO {$pt} (nid, owner, name, data, updated_at) VALUES (%s, %d, %s, %s, %s)", $key, $owner, mb_substr( sanitize_text_field( (string) $r['name'] ), 0, 190 ), wp_json_encode( sojib68_dw_clean( (array) ( $r['data'] ?? array() ) ), JSON_UNESCAPED_UNICODE ), current_time( 'mysql' ) ) );
		$n['people']  += $rows ? 1 : 0;
	}
	$mz = sojib68_dw_get_mouzas();
	foreach ( (array) ( $j['mouzas'] ?? array() ) as $m ) {
		$id = is_array( $m ) ? preg_replace( '/[^a-z0-9]/', '', strtolower( (string) ( $m['id'] ?? '' ) ) ) : '';
		if ( '' === $id || isset( $mz[ $id ] ) || '' === trim( (string) ( $m['name'] ?? '' ) ) ) {
			continue;
		}
		$clean = array( 'id' => $id, 'by' => $me );
		foreach ( sojib68_dw_mouza_fields() as $k ) {
			$clean[ $k ] = isset( $m[ $k ] ) ? mb_substr( sanitize_textarea_field( (string) $m[ $k ] ), 0, 'intro' === $k ? 8000 : 300 ) : '';
		}
		$mz[ $id ] = $clean;
		$n['mouzas']++;
	}
	update_option( 'sojib68_dw_mouzas', $mz, false );
	$tp = sojib68_dw_get_owntpls();
	foreach ( (array) ( $j['owntpls'] ?? array() ) as $t ) {
		$id = is_array( $t ) ? preg_replace( '/[^a-z0-9]/', '', strtolower( (string) ( $t['id'] ?? '' ) ) ) : '';
		if ( '' === $id || isset( $tp[ $id ] ) || '' === trim( (string) ( $t['text'] ?? '' ) ) ) {
			continue;
		}
		$tp[ $id ] = array( 'id' => $id, 'name' => mb_substr( sanitize_text_field( (string) ( $t['name'] ?? '' ) ), 0, 120 ), 'text' => mb_substr( sanitize_textarea_field( (string) $t['text'] ), 0, 20000 ), 'by' => $me );
		$n['tpls']++;
	}
	update_option( 'sojib68_dw_owntpls', $tp, false );
	wp_send_json_success( $n );
} );

/** Days since this user's last backup (null = never). */
function sojib68_dw_days_since_backup() {
	$v = get_user_meta( get_current_user_id(), 'sojib68_dw_last_backup', true );
	if ( ! $v ) {
		return null;
	}
	return (int) floor( ( sojib68_dw_now_ts() - strtotime( $v ) ) / DAY_IN_SECONDS );
}

/** The backup page (?backup=1). */
function sojib68_dw_render_backup( $base, $ajax, $nonce ) {
	global $wpdb;
	$n_d  = (int) $wpdb->get_var( 'SELECT COUNT(*) FROM ' . sojib68_dw_drafts_table() . ' WHERE 1=1' . sojib68_dw_draft_scope() );
	$n_p  = (int) $wpdb->get_var( 'SELECT COUNT(*) FROM ' . sojib68_dw_parties_table() . ' WHERE 1=1' . sojib68_dw_party_scope() );
	$last = get_user_meta( get_current_user_id(), 'sojib68_dw_last_backup', true );
	$dl   = add_query_arg( array( 'dwbackup' => '1', '_wpnonce' => wp_create_nonce( 'sojib68_dw_backup' ) ), $base );
	?>
	<h2 class="dw-ph">💾 ব্যাকআপ ও ফেরত আনা</h2>
	<p><a class="btn btn-outline btn-sm" href="<?php echo esc_url( $base ); ?>">← দলিলের তালিকা</a></p>

	<div class="dw-bk">
		<section class="dw-bk__card">
			<h3>১. পুরো ব্যাকআপ নামান</h3>
			<p>এখনকার <b><?php echo (int) $n_d; ?></b>টি দলিল (মোছা দলিলসহ), <b><?php echo (int) $n_p; ?></b> জন সেভ করা ব্যক্তি, মৌজা তালিকা ও মালিকানা টেমপ্লেট একটা ফাইলে নামবে।<?php echo sojib68_dw_scoped() ? ' (আপনি শুধু নিজের ডাটার ব্যাকআপ পাবেন।)' : ''; ?></p>
			<p class="dw-hint"><?php echo $last ? 'শেষ ব্যাকআপ: ' . esc_html( $last ) : 'এখনো কোনো ব্যাকআপ নেওয়া হয়নি।'; ?></p>
			<p><a class="btn btn-primary" href="<?php echo esc_url( $dl ); ?>">⬇ ব্যাকআপ ডাউনলোড (JSON)</a></p>
			<p class="dw-bk__warn">⚠ এই ফাইলে সবার NID, নাম ও ঠিকানা থাকে। ফাইলটি নিরাপদ জায়গায় (পাসওয়ার্ড-দেওয়া ড্রাইভ বা পেনড্রাইভ) রাখুন, কাউকে পাঠাবেন না, সোশ্যাল মিডিয়ায় দেবেন না।</p>
		</section>

		<section class="dw-bk__card">
			<h3>২. দলিলের তালিকা Excel-এ</h3>
			<p>লেখার তারিখ, দাতা-গ্রহীতা, মৌজা, জমি, মূল্য, অবস্থা ও রেজিস্ট্রির তথ্যসহ সব দলিলের তালিকা (CSV, Excel-এ খোলে)। নির্দিষ্ট তারিখ বা অবস্থার তালিকা চাইলে দলিলের তালিকা পেজে ফিল্টার করে সেখান থেকে নামান।</p>
			<p><a class="btn btn-outline" href="<?php echo esc_url( add_query_arg( 'dwcsv', '1', $base ) ); ?>">⬇ সব দলিলের তালিকা (CSV)</a></p>
		</section>

		<section class="dw-bk__card">
			<h3>৩. ব্যাকআপ থেকে ফেরত আনুন</h3>
			<p>আগে নামানো ব্যাকআপ ফাইল (.json) বাছুন। যে দলিল ও ব্যক্তি আগে থেকেই আছে সেগুলো দ্বিগুণ হবে না, শুধু যেগুলো নেই সেগুলো যোগ হবে। কিছু মুছে যাবে না।</p>
			<p><input type="file" id="dw-bk-file" accept=".json,application/json"></p>
			<p><button type="button" class="btn btn-primary" id="dw-bk-restore">ফেরত আনুন</button> <span class="dw-hint" id="dw-bk-msg"></span></p>
		</section>
	</div>
	<script>
	( function () {
		var cfg = { ajaxUrl: <?php echo wp_json_encode( $ajax ); ?>, nonce: <?php echo wp_json_encode( $nonce ); ?> };
		var btn = document.getElementById( 'dw-bk-restore' ), msg = document.getElementById( 'dw-bk-msg' ), file = document.getElementById( 'dw-bk-file' );
		btn.addEventListener( 'click', function () {
			if ( ! file.files.length ) { msg.textContent = '⚠ আগে ফাইল বাছুন'; return; }
			var fd = new FormData(); fd.append( 'action', 'sojib68_dw_restore_backup' ); fd.append( 'nonce', cfg.nonce ); fd.append( 'file', file.files[ 0 ] );
			btn.disabled = true; msg.textContent = 'ফেরত আনা হচ্ছে…';
			fetch( cfg.ajaxUrl, { method: 'POST', body: fd, credentials: 'same-origin' } ).then( function ( r ) { return r.json(); } ).then( function ( res ) {
				btn.disabled = false;
				if ( res && res.success ) { var d = res.data; msg.textContent = '✓ নতুন যোগ হয়েছে: ' + d.deeds + 'টি দলিল, ' + d.people + ' জন ব্যক্তি, ' + d.mouzas + 'টি মৌজা, ' + d.tpls + 'টি টেমপ্লেট। আগে থেকে ছিল (বাদ): ' + d.deeds_skipped + 'টি দলিল।'; }
				else { msg.textContent = '⚠ ' + ( ( res && res.data && res.data.message ) || 'ফেরত আনা যায়নি' ); }
			}, function () { btn.disabled = false; msg.textContent = '⚠ সংযোগ সমস্যা'; } );
		} );
	}() );
	</script>
	<?php
}
