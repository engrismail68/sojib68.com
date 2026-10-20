<?php
/**
 * Automatic weekly backup to Google Drive, via a Google "service
 * account" (server-to-server auth — no one has to log in or
 * re-authorize anything after the one-time setup).
 *
 * Needs three constants defined in wp-config.php (NOT in this theme —
 * they're a secret, and this theme is deployed from a public-ish git
 * repo, so they must never live in a theme file):
 *
 *   define( 'SOJIB68_GDRIVE_CLIENT_EMAIL', '...@...iam.gserviceaccount.com' );
 *   define( 'SOJIB68_GDRIVE_PRIVATE_KEY',  "-----BEGIN PRIVATE KEY-----\n...\n-----END PRIVATE KEY-----\n" );
 *   define( 'SOJIB68_GDRIVE_FOLDER_ID',    'the-shared-drive-folder-id' );
 *
 * See the "Google Drive অটো ব্যাকআপ" card on the এক্সপোর্ট / ব্যাকআপ
 * page for the exact setup steps. Until all three are defined, this
 * whole feature quietly does nothing — no cron job, no API calls.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'SOJIB68_GDRIVE_SCOPE', 'https://www.googleapis.com/auth/drive.file' );

function sojib68_gdrive_configured() {
	return defined( 'SOJIB68_GDRIVE_CLIENT_EMAIL' ) && SOJIB68_GDRIVE_CLIENT_EMAIL
		&& defined( 'SOJIB68_GDRIVE_PRIVATE_KEY' ) && SOJIB68_GDRIVE_PRIVATE_KEY
		&& defined( 'SOJIB68_GDRIVE_FOLDER_ID' ) && SOJIB68_GDRIVE_FOLDER_ID;
}

function sojib68_gdrive_base64url( $data ) {
	return rtrim( strtr( base64_encode( $data ), '+/', '-_' ), '=' );
}

/**
 * A signed JWT exchanged for a short-lived Drive API access token.
 * Cached in a transient so a weekly cron run (and a manual "এখনই
 * ব্যাকআপ করুন" click) never need more than one token request.
 */
function sojib68_gdrive_get_access_token() {
	if ( ! sojib68_gdrive_configured() ) {
		return new WP_Error( 'not_configured', __( 'Google Drive ব্যাকআপ এখনো সেটআপ করা হয়নি।', 'sojib68' ) );
	}

	$cached = get_transient( 'sojib68_gdrive_token' );
	if ( $cached ) {
		return $cached;
	}

	if ( ! function_exists( 'openssl_sign' ) ) {
		return new WP_Error( 'no_openssl', __( 'এই সার্ভারে OpenSSL নেই, তাই Google Drive ব্যাকআপ কাজ করবে না।', 'sojib68' ) );
	}

	$now = time();
	$header = sojib68_gdrive_base64url( wp_json_encode( array( 'alg' => 'RS256', 'typ' => 'JWT' ) ) );
	$claims = sojib68_gdrive_base64url( wp_json_encode( array(
		'iss'   => SOJIB68_GDRIVE_CLIENT_EMAIL,
		'scope' => SOJIB68_GDRIVE_SCOPE,
		'aud'   => 'https://oauth2.googleapis.com/token',
		'iat'   => $now,
		'exp'   => $now + 3600,
	) ) );

	$signature = '';
	$signed_ok = openssl_sign( "$header.$claims", $signature, SOJIB68_GDRIVE_PRIVATE_KEY, 'sha256WithRSAEncryption' );
	if ( ! $signed_ok ) {
		return new WP_Error( 'sign_failed', __( 'Private key দিয়ে সাইন করা যায়নি — wp-config.php-তে key টি ঠিকভাবে বসানো হয়েছে কিনা দেখুন।', 'sojib68' ) );
	}

	$jwt = $header . '.' . $claims . '.' . sojib68_gdrive_base64url( $signature );

	$resp = wp_remote_post( 'https://oauth2.googleapis.com/token', array(
		'timeout' => 30,
		'body'    => array(
			'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
			'assertion'  => $jwt,
		),
	) );

	if ( is_wp_error( $resp ) ) {
		return $resp;
	}

	$body = json_decode( wp_remote_retrieve_body( $resp ), true );
	if ( empty( $body['access_token'] ) ) {
		$detail = isset( $body['error_description'] ) ? $body['error_description'] : wp_remote_retrieve_body( $resp );
		return new WP_Error( 'token_failed', sprintf( /* translators: %s: error detail from Google */ __( 'Google থেকে টোকেন পাওয়া যায়নি: %s', 'sojib68' ), $detail ) );
	}

	$ttl = isset( $body['expires_in'] ) ? max( 60, (int) $body['expires_in'] - 60 ) : 3000;
	set_transient( 'sojib68_gdrive_token', $body['access_token'], $ttl );

	return $body['access_token'];
}

/** Uploads raw file bytes into the shared backup folder via Drive API v3's multipart upload. */
function sojib68_gdrive_upload_file( $filename, $mime_type, $bytes ) {
	$token = sojib68_gdrive_get_access_token();
	if ( is_wp_error( $token ) ) {
		return $token;
	}

	$boundary = 'sojib68-' . wp_generate_password( 24, false );
	$metadata = wp_json_encode( array(
		'name'    => $filename,
		'parents' => array( SOJIB68_GDRIVE_FOLDER_ID ),
	) );

	$body  = "--$boundary\r\n";
	$body .= "Content-Type: application/json; charset=UTF-8\r\n\r\n";
	$body .= $metadata . "\r\n";
	$body .= "--$boundary\r\n";
	$body .= "Content-Type: $mime_type\r\n\r\n";
	$body .= $bytes . "\r\n";
	$body .= "--$boundary--";

	$resp = wp_remote_post( 'https://www.googleapis.com/upload/drive/v3/files?uploadType=multipart', array(
		'timeout' => 60,
		'headers' => array(
			'Authorization' => 'Bearer ' . $token,
			'Content-Type'  => "multipart/related; boundary=$boundary",
		),
		'body'    => $body,
	) );

	if ( is_wp_error( $resp ) ) {
		return $resp;
	}

	$code = wp_remote_retrieve_response_code( $resp );
	if ( $code < 200 || $code >= 300 ) {
		return new WP_Error( 'upload_failed', sprintf( /* translators: %d: HTTP status code */ __( 'Google Drive আপলোড ব্যর্থ (কোড %d)।', 'sojib68' ), $code ) );
	}

	$result = json_decode( wp_remote_retrieve_body( $resp ), true );
	return isset( $result['id'] ) ? $result['id'] : true;
}

/* ---------------------------------------------------------------------
 * Building the backup itself and running the scheduled job
 * ------------------------------------------------------------------ */

/** Every table as one ZIP, returned as raw bytes (not streamed) — shared by the download button and the Drive upload. */
function sojib68_build_full_backup_zip_bytes() {
	global $wpdb;

	if ( ! class_exists( 'ZipArchive' ) ) {
		return false;
	}

	$tmp_path = tempnam( sys_get_temp_dir(), 'sojib68-backup-' );
	$zip      = new ZipArchive();
	$zip->open( $tmp_path, ZipArchive::OVERWRITE );

	foreach ( sojib68_office_export_tables() as $table_key => $label ) {
		$table = sojib68_deeds_table( $table_key );
		$rows  = $wpdb->get_results( "SELECT * FROM $table", ARRAY_A );

		$csv = fopen( 'php://temp', 'r+' );
		fwrite( $csv, "\xEF\xBB\xBF" );
		if ( $rows ) {
			fputcsv( $csv, array_keys( $rows[0] ) );
			foreach ( $rows as $row ) {
				fputcsv( $csv, $row );
			}
		}
		rewind( $csv );
		$zip->addFromString( $table_key . '.csv', stream_get_contents( $csv ) );
		fclose( $csv );
	}

	$zip->close();

	$bytes = file_get_contents( $tmp_path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- local temp file we just wrote.
	@unlink( $tmp_path ); // phpcs:ignore WordPress.PHP.NoSilencedErrors -- best-effort cleanup; a failure here doesn't affect the backup already read into $bytes.

	return $bytes;
}

function sojib68_gdrive_last_run_status() {
	return get_option( 'sojib68_gdrive_last_backup', null );
}

function sojib68_gdrive_run_scheduled_backup() {
	if ( ! sojib68_gdrive_configured() ) {
		return;
	}

	$bytes = sojib68_build_full_backup_zip_bytes();
	if ( false === $bytes ) {
		update_option( 'sojib68_gdrive_last_backup', array(
			'time'    => current_time( 'mysql' ),
			'status'  => 'error',
			'message' => __( 'এই সার্ভারে ZIP তৈরি করা যায়নি (ZipArchive নেই)।', 'sojib68' ),
		) );
		return;
	}

	$filename = 'sojib68-backup-' . gmdate( 'Y-m-d_His' ) . '.zip';
	$result   = sojib68_gdrive_upload_file( $filename, 'application/zip', $bytes );

	if ( is_wp_error( $result ) ) {
		update_option( 'sojib68_gdrive_last_backup', array(
			'time'    => current_time( 'mysql' ),
			'status'  => 'error',
			'message' => $result->get_error_message(),
		) );
		return;
	}

	update_option( 'sojib68_gdrive_last_backup', array(
		'time'    => current_time( 'mysql' ),
		'status'  => 'success',
		'message' => sprintf( /* translators: %s: file name */ __( '"%s" আপলোড হয়েছে।', 'sojib68' ), $filename ),
	) );
}

/* ---------------------------------------------------------------------
 * WP-Cron: once a week, automatically — no one has to remember.
 * ------------------------------------------------------------------ */

add_filter( 'cron_schedules', function ( $schedules ) {
	$schedules['sojib68_weekly'] = array(
		'interval' => 7 * DAY_IN_SECONDS,
		'display'  => __( 'সাপ্তাহিক (Sojib68 ব্যাকআপ)', 'sojib68' ),
	);
	return $schedules;
} );

add_action( 'init', function () {
	if ( sojib68_gdrive_configured() && ! wp_next_scheduled( 'sojib68_gdrive_backup_event' ) ) {
		wp_schedule_event( time(), 'sojib68_weekly', 'sojib68_gdrive_backup_event' );
	}
} );

add_action( 'sojib68_gdrive_backup_event', 'sojib68_gdrive_run_scheduled_backup' );
