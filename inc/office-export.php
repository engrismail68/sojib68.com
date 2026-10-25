<?php
/**
 * Export / backup for the "দলিল রেকর্ড ও হিসাব" system — a downloadable
 * copy of all the data, kept outside the website, as a safety net
 * against server problems, hosting issues, or anything else going
 * wrong with the live database. Administrator-only (see
 * sojib68_office_can_export()): this is a full copy of every
 * customer's records, so it isn't something every staff login can pull.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Every table this system owns, for a full relational backup. */
function sojib68_office_export_tables() {
	return array(
		'deeds'             => __( 'দলিল (মূল তথ্য)', 'sojib68' ),
		'deed_donors'       => __( 'দাতা', 'sojib68' ),
		'deed_grantees'     => __( 'গ্রহীতা', 'sojib68' ),
		'deed_lands'        => __( 'জমির তথ্য', 'sojib68' ),
		'deed_receipts'     => __( 'টাকা আদায়', 'sojib68' ),
		'deed_expenses'     => __( 'খরচ', 'sojib68' ),
		'deed_audit'        => __( 'অডিট লগ', 'sojib68' ),
		'offices'           => __( 'সাব-রেজিস্ট্রেশন অফিসের তালিকা', 'sojib68' ),
		'deed_types'        => __( 'দলিলের ধরনের তালিকা', 'sojib68' ),
		'deed_writers'      => __( 'লেখকের তালিকা', 'sojib68' ),
	);
}

/** A nonce tied to this one export type, so the download link can't be guessed or replayed from elsewhere. */
function sojib68_office_export_nonce_url( $type ) {
	$url = home_url( '/' . SOJIB68_OFFICE_BASE . '/export-download/' . $type );
	return add_query_arg( 'n', wp_create_nonce( 'sojib68_export_' . $type ), $url );
}

function sojib68_office_render_export() {
	global $wpdb;

	if ( ! sojib68_office_can_export() ) {
		echo '<div class="office-notice office-notice--error">' . esc_html__( 'এই পাতাটি শুধু প্রশাসকের জন্য।', 'sojib68' ) . '</div>';
		return;
	}

	$has_zip    = class_exists( 'ZipArchive' );
	$deed_count = (int) $wpdb->get_var( 'SELECT COUNT(*) FROM ' . sojib68_deeds_table( 'deeds' ) . ' WHERE deleted_at IS NULL' );
	?>
	<section class="deed-card tone-indigo">
		<header class="deed-card__head">
			<span class="deed-card__icon"><?php echo sojib68_office_icon( 'receipt', 19 ); // phpcs:ignore ?></span>
			<div>
				<h2 class="deed-card__title"><?php esc_html_e( 'সব দলিল ও হিসাবের সারাংশ (CSV)', 'sojib68' ); ?></h2>
				<p class="deed-card__hint"><?php echo esc_html( sprintf( /* translators: %d: deed count */ __( 'বর্তমানে %d টি সক্রিয় দলিল আছে — এক্সেলে খোলার মতো একটি ফাইলে সব দলিলের তথ্য।', 'sojib68' ), $deed_count ) ); ?></p>
			</div>
		</header>
		<div class="deed-card__body">
			<p class="deed-note">
				<?php echo sojib68_office_icon( 'info', 18 ); // phpcs:ignore ?>
				<span><?php esc_html_e( 'প্রতিটি দলিল এক লাইনে — ক্রমিক, দলিল নং, তারিখ, অফিস, দাতা, গ্রহীতা, জমি, মূল্য, আদায়, বাকি, খরচ, প্রফিট ও মূল দলিলের অবস্থা সহ। একাধিক দাতা/গ্রহীতা/জমি থাকলে সেমিকোলন (;) দিয়ে আলাদা করা থাকে।', 'sojib68' ); ?></span>
			</p>
			<a class="btn btn-primary" href="<?php echo esc_url( sojib68_office_export_nonce_url( 'summary' ) ); ?>"><?php echo sojib68_office_icon( 'file', 16 ); // phpcs:ignore ?> <?php esc_html_e( 'সারাংশ CSV ডাউনলোড করুন', 'sojib68' ); ?></a>
		</div>
	</section>

	<section class="deed-card tone-green">
		<header class="deed-card__head">
			<span class="deed-card__icon"><?php echo sojib68_office_icon( 'briefcase', 19 ); // phpcs:ignore ?></span>
			<div>
				<h2 class="deed-card__title"><?php esc_html_e( 'সম্পূর্ণ ব্যাকআপ', 'sojib68' ); ?></h2>
				<p class="deed-card__hint"><?php esc_html_e( 'প্রতিটি টেবিলের পুরো অপরিবর্তিত তথ্য — কিছু হারিয়ে গেলে এখান থেকেই ফিরিয়ে আনার কাজে লাগবে।', 'sojib68' ); ?></p>
			</div>
		</header>
		<div class="deed-card__body">
			<?php if ( $has_zip ) : ?>
				<p class="deed-note">
					<?php echo sojib68_office_icon( 'info', 18 ); // phpcs:ignore ?>
					<span><?php esc_html_e( 'একটি ZIP ফাইলে প্রতিটি তথ্যের ধরন আলাদা CSV হিসেবে থাকবে (দলিল, দাতা, গ্রহীতা, জমি, টাকা আদায়, খরচ ইত্যাদি)।', 'sojib68' ); ?></span>
				</p>
				<a class="btn btn-primary" href="<?php echo esc_url( sojib68_office_export_nonce_url( 'full-zip' ) ); ?>"><?php echo sojib68_office_icon( 'file', 16 ); // phpcs:ignore ?> <?php esc_html_e( 'সম্পূর্ণ ব্যাকআপ (.zip) ডাউনলোড করুন', 'sojib68' ); ?></a>
			<?php else : ?>
				<p class="deed-note">
					<?php echo sojib68_office_icon( 'info', 18 ); // phpcs:ignore ?>
					<span><?php esc_html_e( 'এই সার্ভারে ZIP তৈরির সুবিধা নেই, তাই প্রতিটি টেবিল আলাদা আলাদা CSV হিসেবে ডাউনলোড করুন।', 'sojib68' ); ?></span>
				</p>
				<div class="deed-quickadd">
					<?php foreach ( sojib68_office_export_tables() as $key => $label ) : ?>
						<a class="deed-chipbtn" style="border-color:#198030;background:#eef6ef;color:#14672b" href="<?php echo esc_url( sojib68_office_export_nonce_url( 'table-' . $key ) ); ?>">+ <?php echo esc_html( $label ); ?></a>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</div>
	</section>

	<?php sojib68_office_render_gdrive_card(); ?>

	<p class="deed-note">
		<?php echo sojib68_office_icon( 'info', 18 ); // phpcs:ignore ?>
		<span><?php esc_html_e( 'ম্যানুয়াল ফাইলগুলো ডাউনলোড হওয়ার পর আপনার কম্পিউটারে থাকে — নিয়মিত ডাউনলোড করে নিরাপদ জায়গায় রাখুন।', 'sojib68' ); ?></span>
	</p>
	<?php
}

/** Saves the Client ID/Secret the person pastes in — POSTed from the form below. Admin-only, nonce-checked. */
function sojib68_office_maybe_save_gdrive_client() {
	if ( 'POST' !== ( $_SERVER['REQUEST_METHOD'] ?? '' ) || empty( $_POST['sojib68_gdrive_client_save'] ) ) {
		return;
	}
	if ( ! sojib68_office_can_export() || ! isset( $_POST['sojib68_gdrive_nonce'] ) || ! wp_verify_nonce( wp_unslash( $_POST['sojib68_gdrive_nonce'] ), 'sojib68_gdrive_client' ) ) {
		return;
	}

	$client_id = isset( $_POST['client_id'] ) ? sanitize_text_field( wp_unslash( $_POST['client_id'] ) ) : '';
	if ( $client_id ) {
		sojib68_gdrive_set( 'client_id', $client_id );
	}

	// Secret field is left blank on reload (never re-displayed) — only overwrite when something new was typed.
	$client_secret = isset( $_POST['client_secret'] ) ? trim( wp_unslash( $_POST['client_secret'] ) ) : '';
	if ( $client_secret ) {
		sojib68_gdrive_set( 'client_secret', $client_secret );
	}

	wp_safe_redirect( home_url( '/' . SOJIB68_OFFICE_BASE . '/export' ) );
	exit;
}
add_action( 'init', 'sojib68_office_maybe_save_gdrive_client', 5 ); // before sojib68_office_router (priority 10), which would otherwise render+exit on this same request first.

function sojib68_office_render_gdrive_card() {
	$has_client = sojib68_gdrive_has_client();
	$connected  = sojib68_gdrive_configured();
	$last       = sojib68_gdrive_last_run_status();
	?>
	<section class="deed-card tone-blue">
		<header class="deed-card__head">
			<span class="deed-card__icon"><?php echo sojib68_office_icon( 'clock', 19 ); // phpcs:ignore ?></span>
			<div>
				<h2 class="deed-card__title"><?php esc_html_e( 'Google Drive অটো ব্যাকআপ', 'sojib68' ); ?></h2>
				<p class="deed-card__hint"><?php esc_html_e( 'সাপ্তাহিক স্বয়ংক্রিয়ভাবে সম্পূর্ণ ব্যাকআপ আপনার Google Drive-এর একটি ফোল্ডারে আপলোড হয়ে যাবে — ডাউনলোড করার দরকার নেই।', 'sojib68' ); ?></p>
			</div>
		</header>
		<div class="deed-card__body">
			<?php if ( ! empty( $_GET['gdrive_connected'] ) ) : ?>
				<div class="office-notice office-notice--success"><?php esc_html_e( 'Google Drive সফলভাবে সংযুক্ত হয়েছে!', 'sojib68' ); ?></div>
			<?php elseif ( ! empty( $_GET['gdrive_error'] ) ) : ?>
				<div class="office-notice office-notice--error">
					<?php
					$err    = sanitize_key( wp_unslash( $_GET['gdrive_error'] ) );
					$detail = isset( $_GET['detail'] ) ? sanitize_text_field( wp_unslash( $_GET['detail'] ) ) : '';
					switch ( $err ) {
						case 'denied':
							esc_html_e( 'আপনি Google-এ অনুমতি দেননি, তাই সংযুক্ত হয়নি।', 'sojib68' );
							break;
						case 'state':
							esc_html_e( 'লিংকটির মেয়াদ শেষ হয়ে গেছে, আবার চেষ্টা করুন।', 'sojib68' );
							break;
						default:
							echo esc_html( __( 'সংযুক্ত করা যায়নি।', 'sojib68' ) . ( $detail ? ' — ' . $detail : '' ) );
					}
					?>
				</div>
			<?php endif; ?>

			<?php if ( ! $has_client ) : ?>
				<details class="deed-row__more" open>
					<summary><?php esc_html_e( 'কীভাবে সেটআপ করবেন (একবারের কাজ)', 'sojib68' ); ?></summary>
					<ol class="gdrive-steps">
						<li><?php esc_html_e( 'console.cloud.google.com-এ গিয়ে একটি নতুন প্রজেক্ট খুলুন (যেমন নাম দিন "Sojib68 Backup")।', 'sojib68' ); ?></li>
						<li><?php esc_html_e( '"APIs & Services → Library"-তে গিয়ে "Google Drive API" খুঁজে Enable করুন।', 'sojib68' ); ?></li>
						<li><?php esc_html_e( '"APIs & Services → OAuth consent screen"-এ গিয়ে User type "External" দিয়ে একটি বেসিক ফর্ম পূরণ করুন (App name, আপনার ইমেইল), Scopes-এ ".../auth/drive.file" যোগ করুন, আর নিজের ইমেইলকে Test user হিসেবে যোগ করুন।', 'sojib68' ); ?></li>
						<li><?php esc_html_e( 'একই পেজে "PUBLISH APP" চাপুন (drive.file একটি সাধারণ/non-sensitive স্কোপ, তাই Google-এর আলাদা যাচাই লাগবে না) — এতে লগইন টোকেন বারবার মেয়াদ শেষ হবে না।', 'sojib68' ); ?></li>
						<li><?php esc_html_e( '"APIs & Services → Credentials → Create Credentials → OAuth client ID" — Application type "Web application" সিলেক্ট করুন।', 'sojib68' ); ?></li>
						<li>
							<?php esc_html_e( '"Authorized redirect URIs"-এ ঠিক এই লিংকটা বসান:', 'sojib68' ); ?>
							<code class="gdrive-inline-code"><?php echo esc_html( sojib68_gdrive_redirect_uri() ); ?></code>
						</li>
						<li><?php esc_html_e( 'Create চাপার পর যে Client ID আর Client Secret দেখাবে, সেগুলো নিচের ফর্মে বসিয়ে সংরক্ষণ করুন।', 'sojib68' ); ?></li>
					</ol>
				</details>

				<form method="post" action="<?php echo esc_url( home_url( '/' . SOJIB68_OFFICE_BASE . '/export' ) ); ?>" class="gdrive-client-form">
					<?php wp_nonce_field( 'sojib68_gdrive_client', 'sojib68_gdrive_nonce' ); ?>
					<input type="hidden" name="sojib68_gdrive_client_save" value="1">
					<div class="deed-grid">
						<div class="deed-field col-6">
							<label for="f-gdrive-client-id"><?php esc_html_e( 'Client ID', 'sojib68' ); ?></label>
							<input type="text" id="f-gdrive-client-id" name="client_id" value="<?php echo esc_attr( sojib68_gdrive_get( 'client_id' ) ); ?>">
						</div>
						<div class="deed-field col-6">
							<label for="f-gdrive-client-secret"><?php esc_html_e( 'Client Secret', 'sojib68' ); ?></label>
							<input type="password" id="f-gdrive-client-secret" name="client_secret" autocomplete="off" placeholder="<?php echo sojib68_gdrive_get( 'client_secret' ) ? esc_attr__( '(সংরক্ষিত আছে — বদলাতে চাইলে নতুন ভ্যালু লিখুন)', 'sojib68' ) : ''; ?>">
						</div>
					</div>
					<button type="submit" class="btn btn-outline btn-sm"><?php esc_html_e( 'সংরক্ষণ করুন', 'sojib68' ); ?></button>
				</form>
			<?php elseif ( ! $connected ) : ?>
				<div class="office-notice office-notice--info"><?php esc_html_e( 'Client ID/Secret সংরক্ষিত আছে — এখন নিজের Google অ্যাকাউন্ট দিয়ে সংযুক্ত করুন।', 'sojib68' ); ?></div>
				<a class="btn btn-primary" href="<?php echo esc_url( sojib68_gdrive_auth_url() ); ?>"><?php echo sojib68_office_icon( 'check', 16 ); // phpcs:ignore ?> <?php esc_html_e( 'Google Drive-এর সাথে সংযুক্ত করুন', 'sojib68' ); ?></a>
			<?php else : ?>
				<?php if ( $last ) : ?>
					<div class="office-notice office-notice--<?php echo 'success' === $last['status'] ? 'success' : 'error'; ?>">
						<?php echo esc_html( sprintf( /* translators: 1: date/time, 2: status message */ __( 'সর্বশেষ: %1$s — %2$s', 'sojib68' ), $last['time'], $last['message'] ) ); ?>
					</div>
				<?php else : ?>
					<div class="office-notice office-notice--info"><?php esc_html_e( 'সংযুক্ত আছে — এখনো কোনো ব্যাকআপ চালানো হয়নি। পরের সাপ্তাহিক সময়ে বা এখনই নিচের বাটনে চালাতে পারেন।', 'sojib68' ); ?></div>
				<?php endif; ?>
				<div class="office-pagehead__actions">
					<a class="btn btn-primary" href="<?php echo esc_url( sojib68_office_export_nonce_url( 'gdrive-now' ) ); ?>"><?php echo sojib68_office_icon( 'clock', 16 ); // phpcs:ignore ?> <?php esc_html_e( 'এখনই ব্যাকআপ করুন', 'sojib68' ); ?></a>
					<a class="btn btn-outline btn-sm" href="<?php echo esc_url( sojib68_office_export_nonce_url( 'gdrive-disconnect' ) ); ?>"><?php esc_html_e( 'সংযোগ বিচ্ছিন্ন করুন', 'sojib68' ); ?></a>
				</div>
			<?php endif; ?>
		</div>
	</section>
	<?php
}

/* ---------------------------------------------------------------------
 * Streaming the actual files — called before the dashboard shell is
 * rendered (see sojib68_office_router()), so these exit with raw file
 * output and never touch the HTML template.
 * ------------------------------------------------------------------ */

function sojib68_office_stream_export( $type ) {
	if ( ! sojib68_office_can_export() ) {
		wp_die( esc_html__( 'এই পাতাটি শুধু প্রশাসকের জন্য।', 'sojib68' ), '', array( 'response' => 403 ) );
	}

	$nonce = isset( $_GET['n'] ) ? sanitize_text_field( wp_unslash( $_GET['n'] ) ) : '';
	if ( ! wp_verify_nonce( $nonce, 'sojib68_export_' . $type ) ) {
		wp_die( esc_html__( 'লিংকটির মেয়াদ শেষ হয়ে গেছে। এক্সপোর্ট পাতা থেকে আবার চেষ্টা করুন।', 'sojib68' ), '', array( 'response' => 403 ) );
	}

	nocache_headers();

	if ( 'summary' === $type ) {
		sojib68_office_stream_summary_csv();
		exit;
	}

	if ( 'full-zip' === $type ) {
		sojib68_office_stream_full_zip();
		exit;
	}

	if ( 'gdrive-now' === $type ) {
		sojib68_gdrive_run_scheduled_backup();
		wp_safe_redirect( home_url( '/' . SOJIB68_OFFICE_BASE . '/export' ) );
		exit;
	}

	if ( 'gdrive-disconnect' === $type ) {
		sojib68_gdrive_disconnect();
		wp_safe_redirect( home_url( '/' . SOJIB68_OFFICE_BASE . '/export' ) );
		exit;
	}

	if ( 0 === strpos( $type, 'table-' ) ) {
		$table_key = substr( $type, strlen( 'table-' ) );
		$tables    = sojib68_office_export_tables();
		if ( isset( $tables[ $table_key ] ) ) {
			sojib68_office_stream_table_csv( $table_key );
			exit;
		}
	}

	wp_die( esc_html__( 'অজানা ধরনের এক্সপোর্ট।', 'sojib68' ), '', array( 'response' => 404 ) );
}

/** Opens a CSV download and writes the UTF-8 BOM Excel needs to show Bangla correctly. */
function sojib68_office_csv_headers( $filename ) {
	header( 'Content-Type: text/csv; charset=utf-8' );
	header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
	echo "\xEF\xBB\xBF"; // phpcs:ignore WordPress.Security.EscapeOutput -- raw BOM bytes, not markup.
}

function sojib68_office_stream_summary_csv() {
	global $wpdb;

	sojib68_office_csv_headers( 'sojib68-dolil-summary-' . gmdate( 'Y-m-d' ) . '.csv' );

	$out = fopen( 'php://output', 'w' );
	fputcsv( $out, array(
		'ক্রমিক', 'দলিল নং', 'রেজিস্ট্রেশন তারিখ', 'সাল', 'অফিস', 'ধরন',
		'দাতা', 'গ্রহীতা', 'জমি (মৌজা)', 'দলিলের মূল্য (টাকা)',
		'মোট প্রাপ্য (টাকা)', 'আদায় (টাকা)', 'বাকি (টাকা)', 'মোট খরচ (টাকা)', 'প্রফিট (টাকা)',
		'মূল দলিলের অবস্থা', 'লেখকের নাম', 'মন্তব্য',
	) );

	$result = sojib68_deed_search( array(), 1, 1000000 );
	$status_labels = sojib68_deed_original_status_options();
	$expenses_table = sojib68_deeds_table( 'deed_expenses' );

	foreach ( $result['rows'] as $row ) {
		$expenses_total = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COALESCE(SUM(amount_poysha),0) FROM $expenses_table WHERE deed_id = %d", $row->id ) );
		// পুরাতন হিসাব has no আয়/ব্যয় detail, so it has no profit figure.
		$profit         = ! (int) $row->is_legacy ? ( (int) $row->total_received_poysha - $expenses_total ) : null;

		fputcsv( $out, array(
			$row->serial_no ? (int) $row->serial_no : '',
			$row->deed_no ?: '',
			$row->reg_date ?: '',
			$row->reg_year ?: '',
			$row->sub_registry_office ?: '',
			$row->deed_type ?: '',
			implode( '; ', $row->donor_names ),
			implode( '; ', $row->grantee_names ),
			implode( '; ', array_filter( array_map( fn( $l ) => $l->mouza, $row->lands ) ) ),
			null !== $row->deed_value_poysha ? sojib68_poysha_to_display( $row->deed_value_poysha ) : '',
			null !== $row->client_total_due_poysha ? sojib68_poysha_to_display( $row->client_total_due_poysha ) : '',
			sojib68_poysha_to_display( $row->total_received_poysha ),
			null !== $row->due_poysha ? sojib68_poysha_to_display( $row->due_poysha ) : '',
			sojib68_poysha_to_display( $expenses_total ),
			null !== $profit ? sojib68_poysha_to_display( $profit ) : '',
			isset( $status_labels[ $row->original_status ] ) ? $status_labels[ $row->original_status ] : '',
			(string) $row->writer_name,
			(string) $row->notes,
		) );
	}

	fclose( $out );
}

/** One table's rows, verbatim, as CSV — the header row names the real column names. */
function sojib68_office_stream_table_csv( $table_key ) {
	global $wpdb;

	sojib68_office_csv_headers( 'sojib68-' . $table_key . '-' . gmdate( 'Y-m-d' ) . '.csv' );

	$table = sojib68_deeds_table( $table_key );
	$rows  = $wpdb->get_results( "SELECT * FROM $table", ARRAY_A );

	$out = fopen( 'php://output', 'w' );
	if ( $rows ) {
		fputcsv( $out, array_keys( $rows[0] ) );
		foreach ( $rows as $row ) {
			fputcsv( $out, $row );
		}
	}
	fclose( $out );
}

/** Every table, each as its own CSV, bundled into one ZIP. */
function sojib68_office_stream_full_zip() {
	if ( ! class_exists( 'ZipArchive' ) ) {
		wp_die( esc_html__( 'এই সার্ভারে ZIP তৈরি করা যাচ্ছে না। পৃথক CSV ডাউনলোড ব্যবহার করুন।', 'sojib68' ) );
	}

	$bytes = sojib68_build_full_backup_zip_bytes(); // shared with the Google Drive auto-backup, see office-gdrive-backup.php.

	header( 'Content-Type: application/zip' );
	header( 'Content-Disposition: attachment; filename="sojib68-full-backup-' . gmdate( 'Y-m-d' ) . '.zip"' );
	header( 'Content-Length: ' . strlen( $bytes ) );
	echo $bytes; // phpcs:ignore WordPress.Security.EscapeOutput -- raw binary zip bytes, not markup.
}
