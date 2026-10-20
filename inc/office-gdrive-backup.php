<?php
/**
 * Automatic weekly backup to Google Drive.
 *
 * Originally built around a Google "service account", but that hits a
 * hard wall on an ordinary (non-Workspace) Google account: a service
 * account has zero storage quota of its own, and Google refuses to
 * create files on its behalf even in a folder a human has shared with
 * it (error: "Service Accounts do not have storage quota..."). Shared
 * Drives, which would fix that, are a paid Workspace feature.
 *
 * So instead this connects as the site owner's own Google account via
 * OAuth — a one-time "Google Drive-এর সাথে সংযুক্ত করুন" login, the same
 * pattern as "Sign in with Google" on any ordinary app. Everything it
 * needs (Client ID, Client Secret, the refresh token from that login)
 * is entered through the এক্সপোর্ট / ব্যাকআপ page itself and stored in
 * the database — no wp-config.php editing, no private keys to paste.
 *
 * See the "Google Drive অটো ব্যাকআপ" card for the exact setup steps.
 * Until it's connected, this whole feature quietly does nothing — no
 * cron job, no API calls.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// drive.file — the app only ever touches files/folders it creates
// itself, never anything else in the person's Drive. This is Google's
// "non-sensitive" scope tier, so it needs no app-verification review.
define( 'SOJIB68_GDRIVE_SCOPE', 'https://www.googleapis.com/auth/drive.file' );
define( 'SOJIB68_GDRIVE_REDIRECT_SLUG', 'gdrive-oauth-callback' );

/* ---------------------------------------------------------------------
 * Settings — all stored in wp_options, editable from the এক্সপোর্ট /
 * ব্যাকআপ page itself.
 * ------------------------------------------------------------------ */

function sojib68_gdrive_get( $key, $default = '' ) {
	return get_option( 'sojib68_gdrive_' . $key, $default );
}

function sojib68_gdrive_set( $key, $value ) {
	update_option( 'sojib68_gdrive_' . $key, $value );
}

function sojib68_gdrive_has_client() {
	return (bool) ( sojib68_gdrive_get( 'client_id' ) && sojib68_gdrive_get( 'client_secret' ) );
}

function sojib68_gdrive_configured() {
	return sojib68_gdrive_has_client() && (bool) sojib68_gdrive_get( 'refresh_token' );
}

function sojib68_gdrive_redirect_uri() {
	return home_url( '/' . SOJIB68_OFFICE_BASE . '/' . SOJIB68_GDRIVE_REDIRECT_SLUG );
}

/** The link the "Google Drive-এর সাথে সংযুক্ত করুন" button sends the browser to. */
function sojib68_gdrive_auth_url() {
	$params = array(
		'client_id'              => sojib68_gdrive_get( 'client_id' ),
		'redirect_uri'           => sojib68_gdrive_redirect_uri(),
		'response_type'          => 'code',
		'scope'                  => SOJIB68_GDRIVE_SCOPE,
		'access_type'            => 'offline', // ask for a refresh token, not just a short-lived access token.
		'prompt'                 => 'consent', // force Google to re-issue a refresh token even on a repeat connect.
		'state'                  => wp_create_nonce( 'sojib68_gdrive_oauth' ),
	);
	return 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query( $params );
}

/**
 * Handles the redirect back from Google after the person approves
 * access. Only ever redirects (never exits itself) — the router that
 * calls this exits right after, same as every other route it handles.
 */
function sojib68_gdrive_handle_oauth_callback() {
	$base = home_url( '/' . SOJIB68_OFFICE_BASE . '/export' );

	if ( ! sojib68_office_can_export() ) {
		wp_safe_redirect( $base );
		return;
	}

	$state = isset( $_GET['state'] ) ? sanitize_text_field( wp_unslash( $_GET['state'] ) ) : '';
	if ( ! wp_verify_nonce( $state, 'sojib68_gdrive_oauth' ) ) {
		wp_safe_redirect( add_query_arg( 'gdrive_error', 'state', $base ) );
		return;
	}

	if ( ! empty( $_GET['error'] ) ) {
		wp_safe_redirect( add_query_arg( 'gdrive_error', 'denied', $base ) );
		return;
	}

	$code = isset( $_GET['code'] ) ? sanitize_text_field( wp_unslash( $_GET['code'] ) ) : '';
	if ( ! $code ) {
		wp_safe_redirect( add_query_arg( 'gdrive_error', 'no_code', $base ) );
		return;
	}

	$resp = wp_remote_post( 'https://oauth2.googleapis.com/token', array(
		'timeout' => 30,
		'body'    => array(
			'code'          => $code,
			'client_id'     => sojib68_gdrive_get( 'client_id' ),
			'client_secret' => sojib68_gdrive_get( 'client_secret' ),
			'redirect_uri'  => sojib68_gdrive_redirect_uri(),
			'grant_type'    => 'authorization_code',
		),
	) );

	$body = is_wp_error( $resp ) ? array() : json_decode( wp_remote_retrieve_body( $resp ), true );

	if ( empty( $body['refresh_token'] ) ) {
		// Google omits refresh_token on a repeat consent unless prompt=consent
		// forced it (which we do) — if it's still missing, surface *why*.
		$detail = $body['error_description'] ?? ( is_wp_error( $resp ) ? $resp->get_error_message() : '' );
		wp_safe_redirect( add_query_arg( array( 'gdrive_error' => 'token', 'detail' => rawurlencode( (string) $detail ) ), $base ) );
		return;
	}

	sojib68_gdrive_set( 'refresh_token', $body['refresh_token'] );
	delete_transient( 'sojib68_gdrive_access_token' );
	wp_safe_redirect( add_query_arg( 'gdrive_connected', '1', $base ) );
}

/** Disconnects — keeps the saved Client ID/Secret, only forgets the login. */
function sojib68_gdrive_disconnect() {
	sojib68_gdrive_set( 'refresh_token', '' );
	delete_transient( 'sojib68_gdrive_access_token' );
}

/**
 * A short-lived Drive API access token, refreshed from the stored
 * refresh token. Cached in a transient so a weekly cron run (and a
 * manual "এখনই ব্যাকআপ করুন" click) never need more than one request.
 */
function sojib68_gdrive_get_access_token() {
	if ( ! sojib68_gdrive_configured() ) {
		return new WP_Error( 'not_configured', __( 'Google Drive এখনো সংযুক্ত করা হয়নি।', 'sojib68' ) );
	}

	$cached = get_transient( 'sojib68_gdrive_access_token' );
	if ( $cached ) {
		return $cached;
	}

	$resp = wp_remote_post( 'https://oauth2.googleapis.com/token', array(
		'timeout' => 30,
		'body'    => array(
			'client_id'     => sojib68_gdrive_get( 'client_id' ),
			'client_secret' => sojib68_gdrive_get( 'client_secret' ),
			'refresh_token' => sojib68_gdrive_get( 'refresh_token' ),
			'grant_type'    => 'refresh_token',
		),
	) );

	if ( is_wp_error( $resp ) ) {
		return $resp;
	}

	$body = json_decode( wp_remote_retrieve_body( $resp ), true );
	if ( empty( $body['access_token'] ) ) {
		$detail = isset( $body['error_description'] ) ? $body['error_description'] : wp_remote_retrieve_body( $resp );
		if ( isset( $body['error'] ) && 'invalid_grant' === $body['error'] ) {
			// The login was revoked (from the Google Account side) or expired — ask them to reconnect rather than retry forever.
			sojib68_gdrive_disconnect();
		}
		return new WP_Error( 'token_failed', sprintf( /* translators: %s: error detail from Google */ __( 'Google থেকে টোকেন পাওয়া যায়নি: %s', 'sojib68' ), $detail ) );
	}

	$ttl = isset( $body['expires_in'] ) ? max( 60, (int) $body['expires_in'] - 60 ) : 3000;
	set_transient( 'sojib68_gdrive_access_token', $body['access_token'], $ttl );

	return $body['access_token'];
}

/**
 * The folder backups go into — created once, in the account's own My
 * Drive, the first time a backup runs. Because the token only has the
 * drive.file scope, the app can only ever see/use folders it created
 * itself, so this is the one it always reuses afterwards.
 */
function sojib68_gdrive_ensure_backup_folder() {
	$existing = sojib68_gdrive_get( 'folder_id' );
	if ( $existing ) {
		return $existing;
	}

	$token = sojib68_gdrive_get_access_token();
	if ( is_wp_error( $token ) ) {
		return $token;
	}

	$resp = wp_remote_post( 'https://www.googleapis.com/drive/v3/files', array(
		'timeout' => 30,
		'headers' => array(
			'Authorization' => 'Bearer ' . $token,
			'Content-Type'  => 'application/json',
		),
		'body'    => wp_json_encode( array(
			'name'     => 'Sojib68 Backups',
			'mimeType' => 'application/vnd.google-apps.folder',
		) ),
	) );

	if ( is_wp_error( $resp ) ) {
		return $resp;
	}

	$body = json_decode( wp_remote_retrieve_body( $resp ), true );
	if ( empty( $body['id'] ) ) {
		return new WP_Error( 'folder_failed', __( 'ব্যাকআপ ফোল্ডার তৈরি করা যায়নি।', 'sojib68' ) );
	}

	sojib68_gdrive_set( 'folder_id', $body['id'] );
	return $body['id'];
}

/** Uploads raw file bytes into the backup folder via Drive API v3's multipart upload. */
function sojib68_gdrive_upload_file( $filename, $mime_type, $bytes ) {
	$token = sojib68_gdrive_get_access_token();
	if ( is_wp_error( $token ) ) {
		return $token;
	}

	$folder_id = sojib68_gdrive_ensure_backup_folder();
	if ( is_wp_error( $folder_id ) ) {
		return $folder_id;
	}

	$boundary = 'sojib68-' . wp_generate_password( 24, false );
	$metadata = wp_json_encode( array(
		'name'    => $filename,
		'parents' => array( $folder_id ),
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
		$detail = wp_remote_retrieve_body( $resp );
		$parsed = json_decode( $detail, true );
		if ( ! empty( $parsed['error']['message'] ) ) {
			$detail = $parsed['error']['message'];
		}
		return new WP_Error( 'upload_failed', sprintf( /* translators: 1: HTTP status code, 2: Google's own error message */ __( 'Google Drive আপলোড ব্যর্থ (কোড %1$d): %2$s', 'sojib68' ), $code, $detail ) );
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
