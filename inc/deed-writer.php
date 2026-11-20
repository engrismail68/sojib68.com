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

const SOJIB68_DW_DB_VERSION = '1.0.0';

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
		PRIMARY KEY  (id),
		KEY updated_at (updated_at)
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

function sojib68_dw_get_draft( $id ) {
	global $wpdb;
	$t = sojib68_dw_drafts_table();
	return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$t} WHERE id = %d", (int) $id ), ARRAY_A );
}

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
			foreach ( array( 'spouse', 'father', 'mother', 'dob', 'religion', 'profession', 'nationality', 'perm', 'present', 'nid_display' ) as $f ) {
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
		$wpdb->delete( sojib68_dw_drafts_table(), array( 'id' => $id ), array( '%d' ) );
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
			$out .= sojib68_dw_fill_tokens( $tpl, function ( $path ) use ( $item ) {
				return 0 === strpos( $path, 'r.' ) ? sojib68_dw_get( $item, substr( $path, 2 ) ) : '';
			} );
		}
		return $out;
	}, $xml );
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

	$rows = function ( $key ) use ( $out ) {
		return ! empty( $out[ $key ] ) && is_array( $out[ $key ] ) ? array_values( $out[ $key ] ) : array( array() );
	};
	$body = sojib68_dw_expand_rows( $body, 'donee_shares', $rows( 'donee_rows' ) );
	$body = sojib68_dw_expand_rows( $body, 'donor_shares', $rows( 'donor_rows' ) );
	$body = sojib68_dw_expand_rows( $body, 'schedule', $rows( 'schedule_rows' ) );
	$body = sojib68_dw_fill_tokens( $body, function ( $path ) use ( $out ) {
		return sojib68_dw_get( $out, $path );
	} );

	$document = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
		. '<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"><w:body>'
		. $body . $sect . '</w:body></w:document>';
	return sojib68_docx_package( $document );
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
	header( 'Content-Disposition: attachment; filename="deed-' . (int) $id . '.docx"' );
	header( 'Content-Length: ' . strlen( $bin ) );
	echo $bin; // phpcs:ignore WordPress.Security.EscapeOutput -- binary file.
}

/* ---------------------------------------------------------------------
 * Office page
 * ------------------------------------------------------------------ */

function sojib68_dw_render() {
	global $wpdb;
	$base = home_url( '/' . SOJIB68_OFFICE_BASE . '/deed-write' );
	$ver  = static function ( $rel ) {
		$p = get_theme_file_path( $rel );
		return file_exists( $p ) ? (int) filemtime( $p ) : 1;
	};
	$edit_id = isset( $_GET['draft'] ) ? (int) $_GET['draft'] : 0;
	$is_new  = isset( $_GET['new'] );

	if ( ! $edit_id && ! $is_new ) {
		$t    = sojib68_dw_drafts_table();
		$rows = $wpdb->get_results( "SELECT id, title, created_at, updated_at FROM {$t} ORDER BY updated_at DESC LIMIT 200", ARRAY_A );
		?>
		<p><a class="btn btn-primary" href="<?php echo esc_url( add_query_arg( 'new', '1', $base ) ); ?>">+ নতুন দলিল লিখুন</a></p>
		<?php if ( ! $rows ) : ?>
			<p class="office-muted">এখনো কোনো দলিল লেখা হয়নি।</p>
		<?php else : ?>
			<div class="office-card"><table class="office-table dw-list">
				<thead><tr><th>দলিল</th><th>শেষ সংরক্ষণ</th><th></th></tr></thead>
				<tbody>
				<?php foreach ( $rows as $r ) : ?>
					<tr>
						<td><a href="<?php echo esc_url( add_query_arg( 'draft', (int) $r['id'], $base ) ); ?>"><?php echo esc_html( '' !== $r['title'] ? $r['title'] : 'নামহীন দলিল #' . (int) $r['id'] ); ?></a></td>
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
		window.sojib68DwList = { ajaxUrl: <?php echo wp_json_encode( admin_url( 'admin-ajax.php' ) ); ?>, nonce: <?php echo wp_json_encode( wp_create_nonce( 'sojib68_dw' ) ); ?> };
		document.addEventListener('click', function (e) {
			var b = e.target.closest && e.target.closest('.dw-del');
			if (!b || !window.confirm('এই দলিলের খসড়া মুছে ফেলবেন? (ফেরত আনা যাবে না)')) { return; }
			var fd = new FormData(); fd.append('action', 'sojib68_dw_delete'); fd.append('id', b.getAttribute('data-id')); fd.append('nonce', window.sojib68DwList.nonce);
			fetch(window.sojib68DwList.ajaxUrl, { method: 'POST', body: fd, credentials: 'same-origin' }).then(function () { location.reload(); });
		});
		</script>
		<?php
		return;
	}

	$draft = $edit_id ? sojib68_dw_get_draft( $edit_id ) : null;
	if ( $edit_id && ! $draft ) {
		echo '<p>খসড়া পাওয়া যায়নি। <a href="' . esc_url( $base ) . '">তালিকায় ফিরুন</a></p>';
		return;
	}
	$data = $draft ? json_decode( $draft['data'], true ) : array();
	$boot = array(
		'ajaxUrl'  => admin_url( 'admin-ajax.php' ),
		'nonce'    => wp_create_nonce( 'sojib68_dw' ),
		'id'       => $draft ? (int) $draft['id'] : 0,
		'data'     => is_array( $data ) ? $data : new stdClass(),
		'defaults' => get_option( 'sojib68_dw_defaults', array() ),
		'listUrl'  => $base,
		'docxUrl'  => home_url( '/' . SOJIB68_OFFICE_BASE . '/deed-docx/' ),
	);
	?>
	<link rel="stylesheet" href="<?php echo esc_url( get_theme_file_uri( 'assets/css/deed-writer.css' ) ); ?>?v=<?php echo (int) $ver( 'assets/css/deed-writer.css' ); ?>">
	<div id="dw-app" class="dw-app" aria-live="polite"></div>
	<script>window.sojib68Dw = <?php echo wp_json_encode( $boot, JSON_UNESCAPED_UNICODE ); ?>;</script>
	<script src="<?php echo esc_url( get_theme_file_uri( 'assets/js/deed-writer.js' ) ); ?>?v=<?php echo (int) $ver( 'assets/js/deed-writer.js' ); ?>"></script>
	<?php
}
