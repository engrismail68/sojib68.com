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

	<p class="deed-note">
		<?php echo sojib68_office_icon( 'info', 18 ); // phpcs:ignore ?>
		<span><?php esc_html_e( 'এই ফাইলগুলো ডাউনলোড হওয়ার পর আপনার কম্পিউটারে থাকে — নিয়মিত ডাউনলোড করে নিরাপদ জায়গায় (যেমন গুগল ড্রাইভ) রাখুন।', 'sojib68' ); ?></span>
	</p>
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
		'মূল দলিলের অবস্থা', 'মন্তব্য',
	) );

	$result = sojib68_deed_search( array(), 1, 1000000 );
	$status_labels = sojib68_deed_original_status_options();
	$expenses_table = sojib68_deeds_table( 'deed_expenses' );

	foreach ( $result['rows'] as $row ) {
		$expenses_total = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COALESCE(SUM(amount_poysha),0) FROM $expenses_table WHERE deed_id = %d", $row->id ) );
		$profit         = null !== $row->total_received_poysha ? ( (int) $row->total_received_poysha - $expenses_total ) : null;

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
	global $wpdb;

	if ( ! class_exists( 'ZipArchive' ) ) {
		wp_die( esc_html__( 'এই সার্ভারে ZIP তৈরি করা যাচ্ছে না। পৃথক CSV ডাউনলোড ব্যবহার করুন।', 'sojib68' ) );
	}

	$tmp_path = wp_tempnam( 'sojib68-backup.zip' );
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

	header( 'Content-Type: application/zip' );
	header( 'Content-Disposition: attachment; filename="sojib68-full-backup-' . gmdate( 'Y-m-d' ) . '.zip"' );
	header( 'Content-Length: ' . filesize( $tmp_path ) );
	readfile( $tmp_path ); // phpcs:ignore WordPressVIPMinimum.Performance.FetchingRemoteData -- local temp file we just wrote, not a remote fetch.
	@unlink( $tmp_path ); // phpcs:ignore WordPress.PHP.NoSilencedErrors -- best-effort cleanup of a temp file; a failure here isn't worth surfacing to the download.
}
