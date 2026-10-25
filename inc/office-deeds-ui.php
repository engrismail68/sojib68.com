<?php
/**
 * Renders the deed-system screens: dashboard home, the one-page
 * entry/edit form, and the "সকল দলিল" search + listing page.
 *
 * Visual system lives in assets/css/office.css; behaviour (repeatable
 * rows, live summary, AJAX save) lives in assets/js/office-deeds.js.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* ---------------------------------------------------------------------
 * Option lists
 * ------------------------------------------------------------------ */

function sojib68_payment_method_options() {
	return array(
		'cash'           => __( 'নগদ', 'sojib68' ),
		'bank'           => __( 'ব্যাংক', 'sojib68' ),
		'mobile_banking' => __( 'মোবাইল ব্যাংকিং', 'sojib68' ),
		'other'          => __( 'অন্যান্য', 'sojib68' ),
	);
}

function sojib68_expense_category_options() {
	return array(
		'bank_challan'       => __( 'ব্যাংক চালান', 'sojib68' ),
		'office_fee'         => __( 'অফিসের ফিস', 'sojib68' ),
		'stamp'              => __( 'স্ট্যাম্প', 'sojib68' ),
		'registration_fee'   => __( 'রেজিস্ট্রেশন ফি', 'sojib68' ),
		'other_govt_fee_tax' => __( 'অন্যান্য সরকারি ফি ও কর', 'sojib68' ),
		'other'              => __( 'অন্যান্য খরচ', 'sojib68' ),
	);
}

function sojib68_expense_status_options() {
	return array(
		'paid'    => __( 'পরিশোধিত', 'sojib68' ),
		'unpaid'  => __( 'অপরিশোধিত', 'sojib68' ),
		'unknown' => __( 'অজানা', 'sojib68' ),
	);
}

function sojib68_expense_paid_by_options() {
	return array(
		'me'            => __( 'আমার মাধ্যমে', 'sojib68' ),
		'client_direct' => __( 'গ্রাহক সরাসরি', 'sojib68' ),
		'other'         => __( 'অন্যান্য', 'sojib68' ),
		'unknown'       => __( 'অজানা', 'sojib68' ),
	);
}

function sojib68_land_unit_options() {
	return array(
		'শতক'    => __( 'শতক/ডেসিমেল', 'sojib68' ),
		'একর'    => __( 'একর', 'sojib68' ),
		'বর্গফুট' => __( 'বর্গফুট', 'sojib68' ),
	);
}

/* ---------------------------------------------------------------------
 * Small presentation helpers
 * ------------------------------------------------------------------ */

/**
 * Inline line icons (24x24, currentColor). Kept inline so the office
 * area needs no icon font or extra request.
 */
function sojib68_office_icon( $name, $size = 20 ) {
	$paths = array(
		'file'       => '<path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8z"/><path d="M14 3v5h5"/><path d="M9 13h6M9 17h6"/>',
		'user'       => '<circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 3.6-7 8-7s8 3 8 7"/>',
		'user-check' => '<circle cx="10" cy="8" r="4"/><path d="M2 21c0-4 3.6-7 8-7"/><path d="m16 17 2 2 4-4"/>',
		'map'        => '<path d="M9 4 3 6v14l6-2 6 2 6-2V4l-6 2z"/><path d="M9 4v14M15 6v14"/>',
		'tag'        => '<path d="M20.6 13.4 13.4 20.6a2 2 0 0 1-2.8 0L3 13V3h10l7.6 7.6a2 2 0 0 1 0 2.8z"/><circle cx="7.5" cy="7.5" r="1.5"/>',
		'wallet'     => '<path d="M3 7a2 2 0 0 1 2-2h13v4"/><path d="M3 7v11a2 2 0 0 0 2 2h15a1 1 0 0 0 1-1v-9a1 1 0 0 0-1-1H5a2 2 0 0 1-2-2z"/><circle cx="17" cy="14.5" r="1"/>',
		'receipt'    => '<path d="M6 3h12v18l-3-2-3 2-3-2-3 2z"/><path d="M9 8h6M9 12h6"/>',
		'briefcase'  => '<rect x="3" y="7" width="18" height="13" rx="2"/><path d="M9 7V5a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v2M3 13h18"/>',
		'note'       => '<path d="M4 4h16v12l-4 4H4z"/><path d="M16 20v-4h4M8 9h8M8 13h5"/>',
		'plus'       => '<path d="M12 5v14M5 12h14"/>',
		'trash'      => '<path d="M4 7h16M10 11v6M14 11v6M6 7l1 13h10l1-13M9 7V4h6v3"/>',
		'grid'       => '<rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/>',
		'list'       => '<path d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01"/>',
		'trending'   => '<path d="m3 17 6-6 4 4 8-8"/><path d="M15 7h6v6"/>',
		'clock'      => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
		'filter'     => '<path d="M3 5h18l-7 8v6l-4-2v-4z"/>',
		'search'     => '<circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/>',
		'arrow-left' => '<path d="M19 12H5M12 19l-7-7 7-7"/>',
		'info'       => '<circle cx="12" cy="12" r="9"/><path d="M12 8h.01M11 12h1v5h1"/>',
		'check'      => '<path d="m5 12 5 5 9-10"/>',
		'logout'     => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="m16 17 5-5-5-5M21 12H9"/>',
		'eye'        => '<path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/>',
	);

	if ( ! isset( $paths[ $name ] ) ) {
		return '';
	}

	return sprintf(
		'<svg width="%1$d" height="%1$d" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">%2$s</svg>',
		(int) $size,
		$paths[ $name ]
	);
}

/** "৳ 12,500" or "—" for unknown. */
function sojib68_money_html( $poysha, $unknown = '—' ) {
	if ( null === $poysha || '' === $poysha ) {
		return esc_html( $unknown );
	}
	return '<span class="taka">৳</span> ' . esc_html( sojib68_poysha_to_display( $poysha ) );
}

/** Small chip for a deed's original_status — used in the list, dues cards, print page. */
function sojib68_deed_original_status_chip( $status ) {
	$map = array(
		'not_received' => array( 'cls' => 'deed-chip--unknown', 'label' => __( 'আসেনি', 'sojib68' ) ),
		'in_office'    => array( 'cls' => 'deed-chip--advance', 'label' => __( 'আমাদের কাছে', 'sojib68' ) ),
		'delivered'    => array( 'cls' => 'deed-chip--settled', 'label' => __( 'ডেলিভারি হয়েছে', 'sojib68' ) ),
	);
	$row = isset( $map[ $status ] ) ? $map[ $status ] : $map['not_received'];
	return '<span class="deed-chip ' . esc_attr( $row['cls'] ) . '">' . esc_html( $row['label'] ) . '</span>';
}

function sojib68_deed_card_open( $tone, $icon, $title, $hint = '' ) {
	?>
	<section class="deed-card tone-<?php echo esc_attr( $tone ); ?>">
		<header class="deed-card__head">
			<span class="deed-card__icon"><?php echo sojib68_office_icon( $icon, 19 ); // phpcs:ignore ?></span>
			<div>
				<h2 class="deed-card__title"><?php echo esc_html( $title ); ?></h2>
				<?php if ( $hint ) : ?>
					<p class="deed-card__hint"><?php echo esc_html( $hint ); ?></p>
				<?php endif; ?>
			</div>
		</header>
		<div class="deed-card__body">
	<?php
}

function sojib68_deed_card_close() {
	echo '</div></section>';
}

/* ---------------------------------------------------------------------
 * Dashboard home — summary tiles
 * ------------------------------------------------------------------ */

function sojib68_office_render_dashboard_home() {
	sojib68_office_render_period_stats();

	$stats = sojib68_office_dashboard_stats();

	$tiles = array(
		array( 'tone' => 'green',  'icon' => 'file',     'label' => __( 'মোট দলিল এন্ট্রি', 'sojib68' ),                  'value' => esc_html( number_format( $stats['total_deeds'] ) ), 'class' => '' ),
		array( 'tone' => 'indigo', 'icon' => 'tag',      'label' => __( 'মোট দলিলের মূল্য', 'sojib68' ),                  'value' => sojib68_money_html( $stats['total_deed_value'] ), 'class' => '' ),
		array( 'tone' => 'teal',   'icon' => 'wallet',   'label' => __( 'গ্রাহক থেকে মোট টাকা আদায়', 'sojib68' ),          'value' => sojib68_money_html( $stats['total_collected'] ), 'class' => '' ),
		array( 'tone' => 'red',    'icon' => 'receipt',  'label' => __( 'অফিস, ফি ও অন্যান্য মোট ব্যয়', 'sojib68' ),      'value' => sojib68_money_html( $stats['total_expenses'] ), 'class' => '' ),
		array( 'tone' => 'green',  'icon' => 'trending', 'label' => __( 'মোট প্রফিট', 'sojib68' ),                          'value' => sojib68_money_html( $stats['total_profit'] ), 'class' => 'is-hero', 'sub' => __( 'আদায় − মোট খরচ', 'sojib68' ), 'peek' => home_url( '/' . SOJIB68_OFFICE_BASE . '/profit' ) ),
		array( 'tone' => 'amber',  'icon' => 'clock',    'label' => __( 'মোট বকেয়া', 'sojib68' ),                         'value' => sojib68_money_html( $stats['total_due'] ), 'class' => $stats['total_due'] > 0 ? 'is-due' : '' ),
	);
	?>
	<div class="office-stats-grid">
		<?php foreach ( $tiles as $tile ) : ?>
			<div class="office-stat-tile tone-<?php echo esc_attr( $tile['tone'] ); ?> <?php echo esc_attr( $tile['class'] ); ?>">
				<?php if ( ! empty( $tile['peek'] ) ) : ?>
					<a class="office-stat-tile__peek" href="<?php echo esc_url( $tile['peek'] ); ?>" title="<?php esc_attr_e( 'প্রিভিউ — কোন দলিল থেকে এসেছে দেখুন ও এডিট করুন', 'sojib68' ); ?>">
						<?php echo sojib68_office_icon( 'eye', 16 ); // phpcs:ignore ?>
						<span><?php esc_html_e( 'প্রিভিউ', 'sojib68' ); ?></span>
					</a>
				<?php endif; ?>
				<span class="office-stat-tile__icon"><?php echo sojib68_office_icon( $tile['icon'], 22 ); // phpcs:ignore ?></span>
				<span class="office-stat-tile__label"><?php echo esc_html( $tile['label'] ); ?></span>
				<span class="office-stat-tile__value"><?php echo $tile['value']; // phpcs:ignore ?></span>
				<?php if ( ! empty( $tile['sub'] ) ) : ?>
					<span class="office-stat-tile__sub"><?php echo esc_html( $tile['sub'] ); ?></span>
				<?php endif; ?>
			</div>
		<?php endforeach; ?>
	</div>

	<p class="deed-cta">
		<a href="<?php echo esc_url( home_url( '/' . SOJIB68_OFFICE_BASE . '/dues' ) ); ?>" class="btn btn-outline"><?php echo sojib68_office_icon( 'clock', 17 ); // phpcs:ignore ?> <?php esc_html_e( 'বকেয়া আদায় পেজে যান', 'sojib68' ); ?></a>
	</p>

	<?php
	$original_counts = sojib68_office_original_status_counts();
	$all_deeds_base  = home_url( '/' . SOJIB68_OFFICE_BASE . '/all-deeds' );
	$original_tiles  = array(
		array( 'tone' => 'gray',   'icon' => 'clock', 'status' => 'not_received', 'label' => __( 'অফিস থেকে এখনো আসেনি', 'sojib68' ) ),
		array( 'tone' => 'blue',   'icon' => 'file',  'status' => 'in_office',    'label' => __( 'আমাদের কাছে আছে', 'sojib68' ) ),
		array( 'tone' => 'green',  'icon' => 'check', 'status' => 'delivered',    'label' => __( 'ক্লায়েন্টকে ডেলিভারি হয়েছে', 'sojib68' ) ),
	);
	?>
	<h2 class="period-head__plain"><?php esc_html_e( 'মূল দলিলের অবস্থা', 'sojib68' ); ?></h2>
	<div class="office-stats-grid office-stats-grid--3">
		<?php foreach ( $original_tiles as $tile ) : ?>
			<a class="office-stat-tile tone-<?php echo esc_attr( $tile['tone'] ); ?> office-stat-tile--link" href="<?php echo esc_url( add_query_arg( 'original_status', $tile['status'], $all_deeds_base ) ); ?>">
				<span class="office-stat-tile__icon"><?php echo sojib68_office_icon( $tile['icon'], 22 ); // phpcs:ignore ?></span>
				<span class="office-stat-tile__label"><?php echo esc_html( $tile['label'] ); ?></span>
				<span class="office-stat-tile__value"><?php echo esc_html( number_format( $original_counts[ $tile['status'] ] ) ); ?></span>
			</a>
		<?php endforeach; ?>
	</div>

	<p class="deed-note">
		<?php echo sojib68_office_icon( 'info', 18 ); // phpcs:ignore ?>
		<span><?php esc_html_e( 'প্রফিট = গ্রাহকের কাছ থেকে মোট আদায় − মোট খরচ (ব্যাংক চালান, অফিসের ফিস, স্ট্যাম্প ইত্যাদি)।', 'sojib68' ); ?></span>
	</p>

	<?php sojib68_office_render_report_section(); ?>
	<?php
}

/* ---------------------------------------------------------------------
 * Entry / edit form
 * ------------------------------------------------------------------ */

function sojib68_office_render_entry_form() {
	$deed_id = isset( $_GET['id'] ) ? (int) $_GET['id'] : 0;
	$deed    = $deed_id ? sojib68_deed_get( $deed_id ) : null;

	if ( $deed_id && ! $deed ) {
		echo '<div class="office-notice office-notice--error">' . esc_html__( 'দলিলটি খুঁজে পাওয়া যায়নি।', 'sojib68' ) . '</div>';
		return;
	}

	$offices = sojib68_offices_list();
	$types   = sojib68_deed_types_list();

	$default_office  = 'রামগঞ্জ সাব-রেজিস্ট্রেশন অফিস';
	$selected_office = $deed ? (string) $deed->sub_registry_office : $default_office;

	$submission_token = wp_generate_password( 32, false );

	$donors   = $deed && $deed->donors ? $deed->donors : array( null );
	$grantees = $deed && $deed->grantees ? $deed->grantees : array( null );
	$lands    = $deed && $deed->lands ? $deed->lands : array( null );
	$receipts = $deed && $deed->receipts ? $deed->receipts : array();
	$expenses = $deed && $deed->expenses ? $deed->expenses : array();
	?>
	<form id="deed-form" class="deed-form" autocomplete="off" data-base-url="<?php echo esc_attr( home_url( '/' . SOJIB68_OFFICE_BASE . '/' ) ); ?>" novalidate>
		<?php wp_nonce_field( 'sojib68_deed_form', 'nonce' ); ?>
		<input type="hidden" name="deed_id" value="<?php echo esc_attr( $deed_id ); ?>">
		<input type="hidden" name="submission_token" value="<?php echo esc_attr( $submission_token ); ?>">

		<div class="deed-layout">
			<div class="deed-main">

				<div class="deed-notice-area" aria-live="polite">
					<?php if ( $deed ) : ?>
						<div class="office-notice office-notice--info">
							<?php echo esc_html( sprintf( /* translators: %d: deed record id */ __( 'আপনি রেকর্ড #%d সম্পাদনা করছেন।', 'sojib68' ), $deed->id ) ); ?>
							<?php if ( $deed->serial_no ) : ?>
								<span class="deed-serial-badge"><?php echo esc_html( sprintf( /* translators: %s: serial number in Bangla digits */ __( 'ক্রমিক নং %s', 'sojib68' ), sojib68_bn_digits( $deed->serial_no ) ) ); ?></span>
							<?php endif; ?>
						</div>
					<?php endif; ?>
				</div>

				<?php sojib68_deed_card_open( 'green', 'file', __( 'দলিলের পরিচয়', 'sojib68' ) ); ?>
					<div class="deed-grid">
						<div class="deed-field col-6">
							<div class="deed-field__head">
								<label for="f-office"><?php esc_html_e( 'সাব-রেজিস্ট্রেশন অফিস', 'sojib68' ); ?></label>
								<span class="deed-field__tools">
									<button type="button" class="deed-link" data-add="office"><?php esc_html_e( '+ নতুন', 'sojib68' ); ?></button>
									<button type="button" class="deed-link deed-link--danger" data-delete="office" data-select="f-office"><?php esc_html_e( 'মুছুন', 'sojib68' ); ?></button>
								</span>
							</div>
							<select id="f-office" name="sub_registry_office">
								<option value=""><?php esc_html_e( '— ফাঁকা (অজানা) —', 'sojib68' ); ?></option>
								<?php foreach ( $offices as $office ) : ?>
									<option value="<?php echo esc_attr( $office->name ); ?>" <?php selected( $selected_office, $office->name ); ?>><?php echo esc_html( $office->name ); ?></option>
								<?php endforeach; ?>
							</select>
						</div>

						<div class="deed-field col-6">
							<div class="deed-field__head">
								<label for="f-deed-type"><?php esc_html_e( 'দলিলের ধরন', 'sojib68' ); ?></label>
								<span class="deed-field__tools">
									<button type="button" class="deed-link" data-add="deed_type"><?php esc_html_e( '+ নতুন', 'sojib68' ); ?></button>
									<button type="button" class="deed-link deed-link--danger" data-delete="deed_type" data-select="f-deed-type"><?php esc_html_e( 'মুছুন', 'sojib68' ); ?></button>
								</span>
							</div>
							<select id="f-deed-type" name="deed_type">
								<option value=""><?php esc_html_e( '— ফাঁকা —', 'sojib68' ); ?></option>
								<?php foreach ( $types as $type ) : ?>
									<option value="<?php echo esc_attr( $type->name ); ?>" <?php selected( $deed ? $deed->deed_type : '', $type->name ); ?>><?php echo esc_html( $type->name ); ?></option>
								<?php endforeach; ?>
							</select>
						</div>

						<div class="deed-field col-4 m-6">
							<label for="f-reg-date"><?php esc_html_e( 'রেজিস্ট্রেশনের তারিখ', 'sojib68' ); ?></label>
							<input type="date" id="f-reg-date" name="reg_date" value="<?php echo esc_attr( $deed ? $deed->reg_date : '' ); ?>">
						</div>

						<div class="deed-field col-4 m-6">
							<label for="f-reg-year"><?php esc_html_e( 'সাল (তারিখ না জানলে)', 'sojib68' ); ?></label>
							<input type="text" id="f-reg-year" inputmode="numeric" name="reg_year" value="<?php echo esc_attr( $deed ? $deed->reg_year : '' ); ?>" placeholder="<?php esc_attr_e( 'যেমন ২০২৪', 'sojib68' ); ?>">
						</div>

						<div class="deed-field col-4">
							<label for="f-deed-no"><?php esc_html_e( 'দলিল নং', 'sojib68' ); ?></label>
							<input type="text" id="f-deed-no" name="deed_no" value="<?php echo esc_attr( $deed ? $deed->deed_no : '' ); ?>">
						</div>
					</div>
					<p class="deed-warning" id="date-year-warning" hidden><?php esc_html_e( 'তারিখ ও সালের বছর মিলছে না — তবু সংরক্ষণ করা যাবে।', 'sojib68' ); ?></p>
				<?php sojib68_deed_card_close(); ?>

				<?php sojib68_deed_card_open( 'blue', 'user', __( 'দাতার তথ্য', 'sojib68' ), __( 'একাধিক দাতা থাকলে নিচের বাটনে যোগ করুন', 'sojib68' ) ); ?>
					<div class="deed-repeat" data-repeat="donors" data-next="<?php echo count( $donors ); ?>">
						<?php foreach ( $donors as $i => $donor ) : sojib68_render_person_row( 'donors', $i, $donor ); endforeach; ?>
					</div>
					<button type="button" class="deed-add" data-add-row="donors"><?php echo sojib68_office_icon( 'plus', 18 ); // phpcs:ignore ?> <?php esc_html_e( 'দাতা যোগ করুন', 'sojib68' ); ?></button>
				<?php sojib68_deed_card_close(); ?>

				<?php sojib68_deed_card_open( 'teal', 'user-check', __( 'গ্রহীতার তথ্য', 'sojib68' ), __( 'একাধিক গ্রহীতা থাকলে নিচের বাটনে যোগ করুন', 'sojib68' ) ); ?>
					<div class="deed-repeat" data-repeat="grantees" data-next="<?php echo count( $grantees ); ?>">
						<?php foreach ( $grantees as $i => $grantee ) : sojib68_render_person_row( 'grantees', $i, $grantee ); endforeach; ?>
					</div>
					<button type="button" class="deed-add" data-add-row="grantees"><?php echo sojib68_office_icon( 'plus', 18 ); // phpcs:ignore ?> <?php esc_html_e( 'গ্রহীতা যোগ করুন', 'sojib68' ); ?></button>
				<?php sojib68_deed_card_close(); ?>

				<?php sojib68_deed_card_open( 'amber', 'map', __( 'জমির তথ্য', 'sojib68' ), __( 'একই দলিলে একাধিক মৌজা/খতিয়ান থাকলে যোগ করুন', 'sojib68' ) ); ?>
					<div class="deed-repeat" data-repeat="lands" data-next="<?php echo count( $lands ); ?>">
						<?php foreach ( $lands as $i => $land ) : sojib68_render_land_row( $i, $land ); endforeach; ?>
					</div>
					<button type="button" class="deed-add" data-add-row="lands"><?php echo sojib68_office_icon( 'plus', 18 ); // phpcs:ignore ?> <?php esc_html_e( 'জমির তথ্য যোগ করুন', 'sojib68' ); ?></button>
				<?php sojib68_deed_card_close(); ?>

				<?php sojib68_deed_card_open( 'indigo', 'tag', __( 'দলিলের মূল্য', 'sojib68' ) ); ?>
					<div class="deed-grid">
						<div class="deed-field col-6">
							<label for="f-deed-value"><?php esc_html_e( 'দলিলে উল্লিখিত মোট মূল্য (টাকা)', 'sojib68' ); ?></label>
							<input type="text" id="f-deed-value" inputmode="decimal" name="deed_value" value="<?php echo esc_attr( $deed ? sojib68_poysha_to_display( $deed->deed_value_poysha ) : '' ); ?>">
						</div>
					</div>
					<p class="deed-note">
						<?php echo sojib68_office_icon( 'info', 18 ); // phpcs:ignore ?>
						<span><?php esc_html_e( 'এটি আলাদা পরিসংখ্যান — এখান থেকে আমার লাভ হয় না, তাই আয়, প্রাপ্য, জমা, ব্যয় বা লাভের হিসাবে যোগ হয় না।', 'sojib68' ); ?></span>
					</p>
				<?php sojib68_deed_card_close(); ?>

				<?php
				// Simple accounting: one "collected" amount and four fixed expense heads.
				$collected_poysha = $deed ? (int) $deed->total_received_poysha : 0;
				$expense_sums     = array( 'bank_challan' => 0, 'office_fee' => 0, 'stamp' => 0, 'other' => 0 );
				if ( $deed ) {
					foreach ( $deed->expenses as $e ) {
						$key = isset( $expense_sums[ $e->category ] ) ? $e->category : 'other';
						$expense_sums[ $key ] += (int) $e->amount_poysha;
					}
				}
				$expense_labels = array(
					'bank_challan' => __( 'ব্যাংক চালান', 'sojib68' ),
					'office_fee'   => __( 'অফিসের ফিস', 'sojib68' ),
					'stamp'        => __( 'স্ট্যাম্প', 'sojib68' ),
					'other'        => __( 'অন্যান্য খরচ', 'sojib68' ),
				);
				$is_legacy     = $deed && (int) $deed->is_legacy;
				$total_display = $deed ? sojib68_poysha_to_display( $deed->client_total_due_poysha ) : '';
				$show_new      = $is_legacy ? 'hidden' : '';
				$show_legacy   = $is_legacy ? '' : 'hidden';
				sojib68_deed_card_open( 'green', 'wallet', __( 'গ্রাহকের হিসাব', 'sojib68' ), __( 'টাকা লিখলেই প্রফিট ও বাকি নিজে নিজেই বেরিয়ে আসবে', 'sojib68' ) );
				?>
					<label class="legacy-toggle" for="f-is-legacy">
						<input type="checkbox" id="f-is-legacy" name="is_legacy" value="1" data-legacy-toggle <?php checked( $is_legacy ); ?>>
						<span class="legacy-toggle__box" aria-hidden="true"></span>
						<span class="legacy-toggle__text">
							<strong><?php esc_html_e( 'এটি পুরাতন হিসাব', 'sojib68' ); ?></strong>
							<small><?php esc_html_e( 'পুরনো খাতার দলিল — শুধু পুরাতন বকেয়া টাকা লিখুন। আয়, ব্যয়, চালান ইত্যাদির তথ্য লাগবে না।', 'sojib68' ); ?></small>
						</span>
					</label>

					<div class="deed-grid">
						<div class="deed-field col-6" data-legacy-show="0" <?php echo $show_new; // phpcs:ignore ?>>
							<label for="f-total-due"><?php esc_html_e( 'মোট টাকা (গ্রাহকের কাছে প্রাপ্য)', 'sojib68' ); ?></label>
							<input type="text" id="f-total-due" inputmode="decimal" name="client_total_due" value="<?php echo esc_attr( $total_display ); ?>" <?php disabled( $is_legacy ); ?>>
						</div>
						<div class="deed-field col-6 deed-field--legacy" data-legacy-show="1" <?php echo $show_legacy; // phpcs:ignore ?>>
							<label for="f-legacy-due"><?php esc_html_e( 'পুরাতন বকেয়া টাকা', 'sojib68' ); ?></label>
							<input type="text" id="f-legacy-due" inputmode="decimal" name="legacy_due" value="<?php echo esc_attr( $total_display ); ?>" <?php disabled( ! $is_legacy ); ?>>
							<small class="deed-fieldnote"><?php esc_html_e( 'এই টাকা সরাসরি মোট বকেয়ায় যোগ হবে', 'sojib68' ); ?></small>
						</div>
						<div class="deed-field col-6" data-legacy-show="0" <?php echo $show_new; // phpcs:ignore ?>>
							<label for="f-collected"><?php esc_html_e( 'আদায় হয়েছে', 'sojib68' ); ?></label>
							<input type="text" id="f-collected" inputmode="decimal" data-money="receipt" <?php echo $deed ? 'readonly' : 'name="receipts[0][amount]"'; ?> <?php disabled( ! $deed && $is_legacy ); ?> value="<?php echo esc_attr( $collected_poysha ? sojib68_poysha_to_display( $collected_poysha ) : '' ); ?>">
							<?php if ( $deed ) : ?><small class="deed-fieldnote"><?php esc_html_e( 'নিচের "টাকা আদায় যোগ করুন" থেকে বাড়ান', 'sojib68' ); ?></small><?php endif; ?>
						</div>
					</div>

					<?php if ( $deed ) : ?>
						<h3 class="deed-subhead"><?php esc_html_e( 'আদায়ের ইতিহাস', 'sojib68' ); ?></h3>
						<?php
						sojib68_render_payment_history( $deed->receipts );
						?>
						<div class="deed-collect-toggle" data-collect-wrap>
							<button type="button" class="deed-link" data-toggle-collect><?php echo sojib68_office_icon( 'plus', 15 ); // phpcs:ignore ?> <?php esc_html_e( 'টাকা আদায় যোগ করুন', 'sojib68' ); ?></button>
							<div class="collect-panel" hidden><?php sojib68_render_collect_form( $deed->id, $deed->due_poysha ); ?></div>
						</div>
					<?php endif; ?>

					<div class="deed-expense-wrap" data-legacy-show="0" <?php echo $show_new; // phpcs:ignore ?>>
					<h3 class="deed-subhead"><?php esc_html_e( 'খরচ', 'sojib68' ); ?></h3>
					<div class="deed-grid">
						<?php $n = 0; foreach ( $expense_labels as $cat => $label ) : ?>
							<div class="deed-field col-3 m-6">
								<label for="f-exp-<?php echo esc_attr( $cat ); ?>"><?php echo esc_html( $label ); ?></label>
								<input type="hidden" name="expenses[<?php echo (int) $n; ?>][category]" value="<?php echo esc_attr( $cat ); ?>">
								<input type="text" id="f-exp-<?php echo esc_attr( $cat ); ?>" inputmode="decimal" data-money="expense" name="expenses[<?php echo (int) $n; ?>][amount]" value="<?php echo esc_attr( $expense_sums[ $cat ] ? sojib68_poysha_to_display( $expense_sums[ $cat ] ) : '' ); ?>">
							</div>
						<?php $n++; endforeach; ?>
					</div>
					</div>

					<div class="deed-result">
						<div class="deed-result__item" data-legacy-show="0" <?php echo $show_new; // phpcs:ignore ?>><span><?php esc_html_e( 'মোট খরচ', 'sojib68' ); ?></span><strong data-sum="expenses">0</strong></div>
						<div class="deed-result__item is-profit" data-legacy-show="0" <?php echo $show_new; // phpcs:ignore ?>><span><?php esc_html_e( 'প্রফিট (আদায় − খরচ)', 'sojib68' ); ?></span><strong data-sum="profit">—</strong></div>
						<div class="deed-result__item is-due"><span data-sum="due_label"><?php esc_html_e( 'বাকি (মোট − আদায়)', 'sojib68' ); ?></span><strong data-sum="due">—</strong></div>
					</div>
				<?php sojib68_deed_card_close(); ?>

				<?php
				$status        = $deed ? $deed->original_status : 'not_received';
				$status_labels = sojib68_deed_original_status_options();
				sojib68_deed_card_open( 'purple', 'briefcase', __( 'মূল দলিলের অবস্থা', 'sojib68' ), __( 'রেজিস্ট্রেশনের পর মূল দলিল সরকারি অফিস থেকে আসতে সাধারণত কয়েক বছর লাগে', 'sojib68' ) );
				?>
					<div class="deed-grid" data-original-status-group>
						<div class="deed-field col-12">
							<label><?php esc_html_e( 'এই মুহূর্তে দলিলটি কোথায়', 'sojib68' ); ?></label>
							<div class="deed-radio-row">
								<?php foreach ( $status_labels as $val => $label ) : ?>
									<label class="deed-radio">
										<input type="radio" name="original_status" value="<?php echo esc_attr( $val ); ?>" data-original-status-radio <?php checked( $status, $val ); ?>>
										<span><?php echo esc_html( $label ); ?></span>
									</label>
								<?php endforeach; ?>
							</div>
						</div>

						<div class="deed-field col-6" data-original-status-field="in_office">
							<label for="f-original-received"><?php esc_html_e( 'আমাদের হাতে পাওয়ার তারিখ', 'sojib68' ); ?></label>
							<input type="date" id="f-original-received" name="original_received_date" value="<?php echo esc_attr( $deed ? $deed->original_received_date : '' ); ?>">
						</div>

						<div class="deed-field col-6" data-original-status-field="delivered">
							<label for="f-delivered-date"><?php esc_html_e( 'ক্লায়েন্টকে ডেলিভারির তারিখ', 'sojib68' ); ?></label>
							<input type="date" id="f-delivered-date" name="original_delivered_date" value="<?php echo esc_attr( $deed ? $deed->original_delivered_date : '' ); ?>">
						</div>
						<div class="deed-field col-6" data-original-status-field="delivered">
							<label for="f-delivered-to"><?php esc_html_e( 'কে বুঝে নিয়েছেন (নাম)', 'sojib68' ); ?></label>
							<input type="text" id="f-delivered-to" name="original_delivered_to" value="<?php echo esc_attr( $deed ? $deed->original_delivered_to : '' ); ?>">
						</div>
						<div class="deed-field col-6" data-original-status-field="delivered">
							<label for="f-delivered-mobile"><?php esc_html_e( 'তার মোবাইল নং', 'sojib68' ); ?></label>
							<input type="text" id="f-delivered-mobile" inputmode="tel" name="original_delivered_mobile" value="<?php echo esc_attr( $deed ? $deed->original_delivered_mobile : '' ); ?>">
						</div>
						<div class="deed-field col-12" data-original-status-field="delivered">
							<label for="f-delivered-note"><?php esc_html_e( 'মন্তব্য (ঐচ্ছিক)', 'sojib68' ); ?></label>
							<input type="text" id="f-delivered-note" name="original_delivered_note" value="<?php echo esc_attr( $deed ? $deed->original_delivered_note : '' ); ?>" placeholder="<?php esc_attr_e( 'যেমন: মূল গ্রহীতার পক্ষে তার ছেলে বুঝে নিয়েছেন', 'sojib68' ); ?>">
						</div>
					</div>

					<?php if ( $deed && 'delivered' === $status ) : ?>
						<p class="deed-note">
							<?php echo sojib68_office_icon( 'info', 18 ); // phpcs:ignore ?>
							<span><?php esc_html_e( 'ডেলিভারির প্রমাণ হিসেবে "প্রিন্ট সারসংক্ষেপ" পেজ থেকে একটি হস্তান্তর রশিদ প্রিন্ট করে গ্রহণকারীর স্বাক্ষর রাখতে পারেন।', 'sojib68' ); ?></span>
						</p>
					<?php endif; ?>
				<?php sojib68_deed_card_close(); ?>

				<?php sojib68_deed_card_open( 'gray', 'note', __( 'অন্যান্য', 'sojib68' ) ); ?>
					<div class="deed-field">
						<label for="f-notes"><?php esc_html_e( 'সাধারণ মন্তব্য', 'sojib68' ); ?></label>
						<textarea id="f-notes" name="notes" rows="3"><?php echo esc_textarea( $deed ? $deed->notes : '' ); ?></textarea>
					</div>
				<?php sojib68_deed_card_close(); ?>
			</div>

			<aside class="deed-aside">
				<div class="deed-summary-card">
					<div class="deed-summary-card__head">
						<?php echo sojib68_office_icon( 'wallet', 18 ); // phpcs:ignore ?>
						<span><?php esc_html_e( 'সংক্ষিপ্ত হিসাব', 'sojib68' ); ?></span>
						<small><?php esc_html_e( 'লিখতে লিখতেই আপডেট হয়', 'sojib68' ); ?></small>
					</div>
					<div class="deed-summary-list">
						<div class="deed-summary-row"><span><?php esc_html_e( 'দলিলের মূল্য', 'sojib68' ); ?></span><strong data-sum="value">—</strong></div>
						<div class="deed-summary-row"><span data-sum="total_label"><?php echo esc_html( $is_legacy ? __( 'পুরাতন বকেয়া', 'sojib68' ) : __( 'মোট টাকা', 'sojib68' ) ); ?></span><strong data-sum="total_due">—</strong></div>
						<div class="deed-summary-row"><span><?php esc_html_e( 'আদায় হয়েছে', 'sojib68' ); ?></span><strong data-sum="received">0</strong></div>
						<div class="deed-summary-row deed-summary-row--big">
							<span data-sum="due_label"><?php esc_html_e( 'বাকি', 'sojib68' ); ?></span>
							<strong data-sum="due">—</strong>
						</div>
						<div class="deed-summary-status"><span class="deed-chip deed-chip--unknown" data-sum="status"><?php esc_html_e( 'প্রাপ্য অজানা', 'sojib68' ); ?></span></div>
						<div class="deed-summary-row" data-legacy-show="0" <?php echo $show_new; // phpcs:ignore ?>><span><?php esc_html_e( 'মোট খরচ', 'sojib68' ); ?></span><strong data-sum="expenses">0</strong></div>
						<div class="deed-summary-row deed-summary-row--big" data-legacy-show="0" <?php echo $show_new; // phpcs:ignore ?>><span><?php esc_html_e( 'প্রফিট', 'sojib68' ); ?></span><strong data-sum="profit">—</strong></div>
					</div>
					<p class="deed-summary-foot"><?php esc_html_e( 'প্রফিট = আদায় − খরচ। বাকি = মোট − আদায়। দলিলের মূল্য এই হিসাবে যোগ হয় না।', 'sojib68' ); ?></p>
				</div>

				<div class="deed-tips">
					<h3><?php echo sojib68_office_icon( 'info', 17 ); // phpcs:ignore ?> <?php esc_html_e( 'টিপস', 'sojib68' ); ?></h3>
					<ul>
						<li><?php esc_html_e( 'সব ঘর ঐচ্ছিক — যতটুকু জানেন ততটুকুই লিখুন।', 'sojib68' ); ?></li>
						<li><?php esc_html_e( 'সংখ্যা বাংলা বা ইংরেজি — যেকোনোভাবে লেখা যাবে।', 'sojib68' ); ?></li>
						<li><?php esc_html_e( 'ভুল করে যোগ হওয়া সারি "মুছুন" বাটনে সরিয়ে দিন।', 'sojib68' ); ?></li>
					</ul>
				</div>
			</aside>
		</div>

		<div class="deed-actionbar">
			<div class="deed-form-actions">
				<button type="submit" class="btn btn-primary" data-action="submit"><?php esc_html_e( 'সাবমিট করুন', 'sojib68' ); ?></button>
				<button type="submit" class="btn btn-outline" data-action="submit-and-new"><?php esc_html_e( 'সংরক্ষণ করে আরেকটি এন্ট্রি', 'sojib68' ); ?></button>
			</div>
			<div class="deed-success-actions" hidden>
				<?php if ( isset( $_GET['from'] ) && 'profit' === $_GET['from'] ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
					<a href="<?php echo esc_url( home_url( '/' . SOJIB68_OFFICE_BASE . '/profit' ) ); ?>" class="btn btn-primary"><?php echo sojib68_office_icon( 'arrow-left', 16 ); // phpcs:ignore ?> <?php esc_html_e( 'প্রফিট প্রিভিউতে ফিরুন', 'sojib68' ); ?></a>
				<?php endif; ?>
				<a href="<?php echo esc_url( home_url( '/' . SOJIB68_OFFICE_BASE . '/all-deeds' ) ); ?>" class="btn btn-outline"><?php esc_html_e( 'সকল দলিল দেখুন', 'sojib68' ); ?></a>
				<a href="<?php echo esc_url( home_url( '/' . SOJIB68_OFFICE_BASE . '/entry-new' ) ); ?>" class="btn btn-primary"><?php esc_html_e( 'আরেকটি এন্ট্রি করুন', 'sojib68' ); ?></a>
			</div>
		</div>
	</form>

	<template id="tpl-donors"><?php sojib68_render_person_row( 'donors', '__INDEX__', null ); ?></template>
	<template id="tpl-grantees"><?php sojib68_render_person_row( 'grantees', '__INDEX__', null ); ?></template>
	<template id="tpl-lands"><?php sojib68_render_land_row( '__INDEX__', null ); ?></template>

	<script>window.sojib68DeedAjax = { ajaxUrl: <?php echo wp_json_encode( admin_url( 'admin-ajax.php' ) ); ?> };</script>
	<script src="<?php echo esc_url( get_theme_file_uri( 'assets/js/office-deeds.js' ) ); ?>?v=<?php echo (int) @filemtime( get_theme_file_path( 'assets/js/office-deeds.js' ) ); ?>"></script>
	<?php sojib68_office_dues_script(); ?>
	<?php sojib68_office_deed_actions_script(); ?>
	<?php
}

/* ---- Repeatable row templates (used for existing rows and as <template>s) ---- */

function sojib68_row_head( $label ) {
	?>
	<div class="deed-row__head">
		<span class="deed-row__title"><?php echo esc_html( $label ); ?></span>
		<button type="button" class="deed-row__remove" aria-label="<?php echo esc_attr( sprintf( /* translators: %s: row type */ __( '%s সারি মুছুন', 'sojib68' ), $label ) ); ?>">
			<?php echo sojib68_office_icon( 'trash', 15 ); // phpcs:ignore ?>
			<?php esc_html_e( 'মুছুন', 'sojib68' ); ?>
		</button>
	</div>
	<?php
}

function sojib68_render_person_row( $group, $i, $row ) {
	$is_donor = 'donors' === $group;
	$label    = $is_donor ? __( 'দাতা', 'sojib68' ) : __( 'গ্রহীতা', 'sojib68' );
	$prefix   = esc_attr( $group ) . '[' . esc_attr( $i ) . ']';
	?>
	<div class="deed-row">
		<?php sojib68_row_head( $label ); ?>
		<div class="deed-grid">
			<div class="deed-field col-4">
				<label><?php echo $is_donor ? esc_html__( 'দাতার নাম', 'sojib68' ) : esc_html__( 'গ্রহীতার নাম', 'sojib68' ); ?></label>
				<input type="text" name="<?php echo $prefix; // phpcs:ignore ?>[name]" value="<?php echo esc_attr( $row->name ?? '' ); ?>">
			</div>
			<div class="deed-field col-3">
				<label><?php esc_html_e( 'মোবাইল নং', 'sojib68' ); ?></label>
				<input type="text" inputmode="tel" name="<?php echo $prefix; // phpcs:ignore ?>[mobile]" value="<?php echo esc_attr( $row->mobile ?? '' ); ?>">
			</div>
			<div class="deed-field col-5">
				<label><?php esc_html_e( 'ঠিকানা', 'sojib68' ); ?></label>
				<input type="text" name="<?php echo $prefix; // phpcs:ignore ?>[address]" value="<?php echo esc_attr( $row->address ?? '' ); ?>">
			</div>
		</div>
	</div>
	<?php
}

function sojib68_render_land_row( $i, $row ) {
	$prefix = 'lands[' . esc_attr( $i ) . ']';
	?>
	<div class="deed-row">
		<?php sojib68_row_head( __( 'জমি', 'sojib68' ) ); ?>
		<div class="deed-grid">
			<div class="deed-field col-4">
				<label><?php esc_html_e( 'মৌজা', 'sojib68' ); ?></label>
				<input type="text" name="<?php echo $prefix; // phpcs:ignore ?>[mouza]" value="<?php echo esc_attr( $row->mouza ?? '' ); ?>">
			</div>
			<div class="deed-field col-3 m-6">
				<label><?php esc_html_e( 'খতিয়ান নং', 'sojib68' ); ?></label>
				<input type="text" name="<?php echo $prefix; // phpcs:ignore ?>[khatian_no]" value="<?php echo esc_attr( $row->khatian_no ?? '' ); ?>">
			</div>
			<div class="deed-field col-3 m-6">
				<label><?php esc_html_e( 'জমির পরিমাণ', 'sojib68' ); ?></label>
				<input type="text" inputmode="decimal" name="<?php echo $prefix; // phpcs:ignore ?>[quantity]" value="<?php echo esc_attr( isset( $row->quantity ) && null !== $row->quantity ? rtrim( rtrim( (string) $row->quantity, '0' ), '.' ) : '' ); ?>">
			</div>
			<div class="deed-field col-2">
				<label><?php esc_html_e( 'একক', 'sojib68' ); ?></label>
				<select name="<?php echo $prefix; // phpcs:ignore ?>[unit]">
					<option value=""><?php esc_html_e( '—', 'sojib68' ); ?></option>
					<?php foreach ( sojib68_land_unit_options() as $val => $label ) : ?>
						<option value="<?php echo esc_attr( $val ); ?>" <?php selected( $row->unit ?? '', $val ); ?>><?php echo esc_html( $label ); ?></option>
					<?php endforeach; ?>
				</select>
			</div>
		</div>
	</div>
	<?php
}

/* ---------------------------------------------------------------------
 * "সকল দলিল" — search + paginated listing
 * ------------------------------------------------------------------ */

function sojib68_office_collect_filters() {
	$keys    = array( 'name', 'mobile', 'address', 'deed_no', 'deed_type', 'office', 'reg_year', 'date_from', 'date_to', 'mouza', 'khatian_no', 'land_unit', 'value_min', 'value_max', 'has_due', 'original_status' );
	$filters = array();
	foreach ( $keys as $key ) {
		$val = isset( $_GET[ $key ] ) ? sanitize_text_field( wp_unslash( $_GET[ $key ] ) ) : '';
		if ( '' !== $val ) {
			$filters[ $key ] = $val;
		}
	}
	return $filters;
}

function sojib68_office_render_all_deeds() {
	$filters = sojib68_office_collect_filters();
	$paged   = isset( $_GET['paged'] ) ? max( 1, (int) $_GET['paged'] ) : 1;
	$result  = sojib68_deed_search( $filters, $paged, 20 );

	$offices = sojib68_offices_list();
	$types   = sojib68_deed_types_list();
	$base    = home_url( '/' . SOJIB68_OFFICE_BASE . '/all-deeds' );

	$advanced_keys   = array( 'address', 'deed_type', 'office', 'date_from', 'date_to', 'mouza', 'khatian_no', 'land_unit', 'value_min', 'value_max', 'has_due', 'original_status' );
	$advanced_active = count( array_intersect_key( $filters, array_flip( $advanced_keys ) ) );
	$f               = function ( $key ) use ( $filters ) {
		return isset( $filters[ $key ] ) ? $filters[ $key ] : '';
	};
	?>
	<form method="get" action="<?php echo esc_url( $base ); ?>" class="deed-card deed-filter-card">
		<div class="deed-card__body">
			<div class="deed-grid">
				<div class="deed-field col-4"><label><?php esc_html_e( 'নাম (দাতা/গ্রহীতা)', 'sojib68' ); ?></label><input type="text" name="name" value="<?php echo esc_attr( $f( 'name' ) ); ?>" placeholder="<?php esc_attr_e( 'নাম বা নামের অংশ', 'sojib68' ); ?>"></div>
				<div class="deed-field col-3"><label><?php esc_html_e( 'মোবাইল', 'sojib68' ); ?></label><input type="text" inputmode="tel" name="mobile" value="<?php echo esc_attr( $f( 'mobile' ) ); ?>"></div>
				<div class="deed-field col-3"><label><?php esc_html_e( 'দলিল নং', 'sojib68' ); ?></label><input type="text" name="deed_no" value="<?php echo esc_attr( $f( 'deed_no' ) ); ?>"></div>
				<div class="deed-field col-2"><label><?php esc_html_e( 'সাল', 'sojib68' ); ?></label><input type="text" inputmode="numeric" name="reg_year" value="<?php echo esc_attr( $f( 'reg_year' ) ); ?>"></div>
			</div>

			<details class="deed-more" <?php echo $advanced_active ? 'open' : ''; ?>>
				<summary>
					<?php echo sojib68_office_icon( 'filter', 16 ); // phpcs:ignore ?>
					<?php esc_html_e( 'আরও ফিল্টার', 'sojib68' ); ?>
					<?php if ( $advanced_active ) : ?><span class="deed-badge"><?php echo (int) $advanced_active; ?></span><?php endif; ?>
				</summary>
				<div class="deed-grid">
					<div class="deed-field col-6"><label><?php esc_html_e( 'ঠিকানা', 'sojib68' ); ?></label><input type="text" name="address" value="<?php echo esc_attr( $f( 'address' ) ); ?>"></div>
					<div class="deed-field col-3">
						<label><?php esc_html_e( 'দলিলের ধরন', 'sojib68' ); ?></label>
						<select name="deed_type">
							<option value=""><?php esc_html_e( 'সব', 'sojib68' ); ?></option>
							<?php foreach ( $types as $type ) : ?>
								<option value="<?php echo esc_attr( $type->name ); ?>" <?php selected( $f( 'deed_type' ), $type->name ); ?>><?php echo esc_html( $type->name ); ?></option>
							<?php endforeach; ?>
						</select>
					</div>
					<div class="deed-field col-3">
						<label><?php esc_html_e( 'সাব-রেজিস্ট্রেশন অফিস', 'sojib68' ); ?></label>
						<select name="office">
							<option value=""><?php esc_html_e( 'সব', 'sojib68' ); ?></option>
							<?php foreach ( $offices as $office ) : ?>
								<option value="<?php echo esc_attr( $office->name ); ?>" <?php selected( $f( 'office' ), $office->name ); ?>><?php echo esc_html( $office->name ); ?></option>
							<?php endforeach; ?>
						</select>
					</div>
					<div class="deed-field col-3 m-6"><label><?php esc_html_e( 'তারিখ থেকে', 'sojib68' ); ?></label><input type="date" name="date_from" value="<?php echo esc_attr( $f( 'date_from' ) ); ?>"></div>
					<div class="deed-field col-3 m-6"><label><?php esc_html_e( 'তারিখ পর্যন্ত', 'sojib68' ); ?></label><input type="date" name="date_to" value="<?php echo esc_attr( $f( 'date_to' ) ); ?>"></div>
					<div class="deed-field col-3 m-6"><label><?php esc_html_e( 'মৌজা', 'sojib68' ); ?></label><input type="text" name="mouza" value="<?php echo esc_attr( $f( 'mouza' ) ); ?>"></div>
					<div class="deed-field col-3 m-6"><label><?php esc_html_e( 'খতিয়ান নং', 'sojib68' ); ?></label><input type="text" name="khatian_no" value="<?php echo esc_attr( $f( 'khatian_no' ) ); ?>"></div>
					<div class="deed-field col-3 m-6">
						<label><?php esc_html_e( 'জমির একক', 'sojib68' ); ?></label>
						<select name="land_unit">
							<option value=""><?php esc_html_e( 'সব', 'sojib68' ); ?></option>
							<?php foreach ( sojib68_land_unit_options() as $val => $label ) : ?>
								<option value="<?php echo esc_attr( $val ); ?>" <?php selected( $f( 'land_unit' ), $val ); ?>><?php echo esc_html( $label ); ?></option>
							<?php endforeach; ?>
						</select>
					</div>
					<div class="deed-field col-3 m-6"><label><?php esc_html_e( 'মূল্য — সর্বনিম্ন', 'sojib68' ); ?></label><input type="text" inputmode="decimal" name="value_min" value="<?php echo esc_attr( $f( 'value_min' ) ); ?>"></div>
					<div class="deed-field col-3 m-6"><label><?php esc_html_e( 'মূল্য — সর্বোচ্চ', 'sojib68' ); ?></label><input type="text" inputmode="decimal" name="value_max" value="<?php echo esc_attr( $f( 'value_max' ) ); ?>"></div>
					<div class="deed-field col-3 m-6">
						<label><?php esc_html_e( 'বকেয়া আছে কি না', 'sojib68' ); ?></label>
						<select name="has_due">
							<option value=""><?php esc_html_e( 'সব', 'sojib68' ); ?></option>
							<option value="1" <?php selected( $f( 'has_due' ), '1' ); ?>><?php esc_html_e( 'বকেয়া আছে', 'sojib68' ); ?></option>
							<option value="0" <?php selected( $f( 'has_due' ), '0' ); ?>><?php esc_html_e( 'বকেয়া নেই', 'sojib68' ); ?></option>
						</select>
					</div>
					<div class="deed-field col-3 m-6">
						<label><?php esc_html_e( 'মূল দলিলের অবস্থা', 'sojib68' ); ?></label>
						<select name="original_status">
							<option value=""><?php esc_html_e( 'সব', 'sojib68' ); ?></option>
							<?php foreach ( sojib68_deed_original_status_options() as $val => $label ) : ?>
								<option value="<?php echo esc_attr( $val ); ?>" <?php selected( $f( 'original_status' ), $val ); ?>><?php echo esc_html( $label ); ?></option>
							<?php endforeach; ?>
						</select>
					</div>
				</div>
			</details>

			<div class="deed-filter-actions">
				<button type="submit" class="btn btn-primary"><?php echo sojib68_office_icon( 'search', 17 ); // phpcs:ignore ?> <?php esc_html_e( 'খুঁজুন', 'sojib68' ); ?></button>
				<?php if ( ! empty( $filters ) ) : ?>
					<a href="<?php echo esc_url( $base ); ?>" class="btn btn-outline"><?php esc_html_e( 'ফিল্টার মুছুন', 'sojib68' ); ?></a>
				<?php endif; ?>
			</div>
		</div>
	</form>

	<p class="deed-result-count">
		<?php echo esc_html( sprintf( /* translators: %d: count */ __( 'মোট %d টি দলিল পাওয়া গেছে', 'sojib68' ), $result['total'] ) ); ?>
		<?php if ( ! empty( $filters ) ) : ?><span class="deed-result-count__note"><?php esc_html_e( '(ফিল্টার চালু আছে)', 'sojib68' ); ?></span><?php endif; ?>
	</p>

	<?php if ( empty( $result['rows'] ) ) : ?>
		<div class="deed-empty-state">
			<?php echo sojib68_office_icon( 'file', 34 ); // phpcs:ignore ?>
			<p><?php esc_html_e( 'কোনো দলিল পাওয়া যায়নি।', 'sojib68' ); ?></p>
			<a href="<?php echo esc_url( home_url( '/' . SOJIB68_OFFICE_BASE . '/entry-new' ) ); ?>" class="btn btn-primary"><?php esc_html_e( '+ নতুন দলিল এন্ট্রি', 'sojib68' ); ?></a>
		</div>
	<?php else : ?>
		<div class="deed-table-card">
			<table class="deed-table">
				<thead>
					<tr>
						<th><?php esc_html_e( 'ক্রমিক', 'sojib68' ); ?></th>
						<th><?php esc_html_e( 'দলিল', 'sojib68' ); ?></th>
						<th><?php esc_html_e( 'দাতা → গ্রহীতা', 'sojib68' ); ?></th>
						<th><?php esc_html_e( 'ধরন ও অফিস', 'sojib68' ); ?></th>
						<th><?php esc_html_e( 'জমি', 'sojib68' ); ?></th>
						<th class="num"><?php esc_html_e( 'মূল্য', 'sojib68' ); ?></th>
						<th><?php esc_html_e( 'বকেয়া', 'sojib68' ); ?></th>
						<th><?php esc_html_e( 'মূল দলিল', 'sojib68' ); ?></th>
						<th></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $result['rows'] as $row ) : ?>
						<tr>
							<td data-label="<?php esc_attr_e( 'ক্রমিক', 'sojib68' ); ?>">
								<span class="deed-serial"><?php echo esc_html( $row->serial_no ? sojib68_bn_digits( $row->serial_no ) : '—' ); ?></span>
							</td>
							<td data-label="<?php esc_attr_e( 'দলিল', 'sojib68' ); ?>">
								<strong class="deed-cell-main"><?php echo esc_html( $row->deed_no ? '#' . $row->deed_no : '—' ); ?></strong>
								<span class="deed-cell-sub"><?php echo esc_html( $row->reg_date ? $row->reg_date : ( $row->reg_year ? $row->reg_year : __( 'সাল অজানা', 'sojib68' ) ) ); ?></span>
							</td>
							<td data-label="<?php esc_attr_e( 'দাতা → গ্রহীতা', 'sojib68' ); ?>">
								<?php if ( ! empty( $row->donor_names ) || ! empty( $row->grantee_names ) ) : ?>
									<span class="deed-party"><?php echo esc_html( implode( ', ', $row->donor_names ) ?: '—' ); ?></span>
									<span class="deed-party-arrow" aria-hidden="true">→</span>
									<span class="deed-party"><?php echo esc_html( implode( ', ', $row->grantee_names ) ?: '—' ); ?></span>
								<?php else : ?>
									<span class="deed-cell-sub">—</span>
								<?php endif; ?>
							</td>
							<td data-label="<?php esc_attr_e( 'ধরন ও অফিস', 'sojib68' ); ?>">
								<?php if ( $row->deed_type ) : ?><span class="deed-type-chip"><?php echo esc_html( $row->deed_type ); ?></span><?php endif; ?>
								<span class="deed-cell-sub"><?php echo esc_html( $row->sub_registry_office ?: '—' ); ?></span>
							</td>
							<td data-label="<?php esc_attr_e( 'জমি', 'sojib68' ); ?>">
								<?php if ( ! empty( $row->lands ) ) : $first = $row->lands[0]; ?>
									<span class="deed-cell-main"><?php echo esc_html( $first->mouza ?: '—' ); ?></span>
									<span class="deed-cell-sub">
										<?php echo esc_html( null !== $first->quantity ? rtrim( rtrim( (string) $first->quantity, '0' ), '.' ) . ' ' . $first->unit : '' ); ?>
										<?php if ( count( $row->lands ) > 1 ) : ?><em>+<?php echo (int) ( count( $row->lands ) - 1 ); ?> <?php esc_html_e( 'আরও', 'sojib68' ); ?></em><?php endif; ?>
									</span>
								<?php else : ?>
									<span class="deed-cell-sub">—</span>
								<?php endif; ?>
							</td>
							<td class="num" data-label="<?php esc_attr_e( 'মূল্য', 'sojib68' ); ?>"><span><?php echo sojib68_money_html( $row->deed_value_poysha ); // phpcs:ignore ?></span></td>
							<td data-label="<?php esc_attr_e( 'বকেয়া', 'sojib68' ); ?>">
								<?php
								if ( null === $row->due_poysha ) {
									echo '<span class="deed-chip deed-chip--unknown">' . esc_html__( 'অজানা', 'sojib68' ) . '</span>';
								} elseif ( $row->due_poysha > 0 ) {
									echo '<span class="deed-chip deed-chip--due">৳ ' . esc_html( sojib68_poysha_to_display( $row->due_poysha ) ) . '</span>';
								} elseif ( $row->due_poysha < 0 ) {
									echo '<span class="deed-chip deed-chip--advance">' . esc_html__( 'অগ্রিম', 'sojib68' ) . ' ৳ ' . esc_html( sojib68_poysha_to_display( abs( $row->due_poysha ) ) ) . '</span>';
								} else {
									echo '<span class="deed-chip deed-chip--settled">' . esc_html__( 'পরিশোধিত', 'sojib68' ) . '</span>';
								}
								?>
							</td>
							<td data-label="<?php esc_attr_e( 'মূল দলিল', 'sojib68' ); ?>">
								<?php echo sojib68_deed_original_status_chip( $row->original_status ); // phpcs:ignore ?>
							</td>
							<td class="act">
								<?php if ( $row->due_poysha > 0 ) : ?>
									<button type="button" class="btn btn-primary btn-sm" data-toggle-collect data-collect-for="deed-<?php echo (int) $row->id; ?>"><?php echo sojib68_office_icon( 'wallet', 15 ); // phpcs:ignore ?> <?php esc_html_e( 'পরিশোধ', 'sojib68' ); ?></button>
								<?php endif; ?>
								<a href="<?php echo esc_url( add_query_arg( 'id', $row->id, home_url( '/' . SOJIB68_OFFICE_BASE . '/entry-new' ) ) ); ?>" class="btn btn-outline btn-sm"><?php esc_html_e( 'দেখুন / এডিট', 'sojib68' ); ?></a>
								<a href="<?php echo esc_url( home_url( '/' . SOJIB68_OFFICE_BASE . '/print/' . $row->id ) ); ?>" target="_blank" rel="noopener" class="btn btn-outline btn-sm btn-icon" title="<?php esc_attr_e( 'প্রিন্ট সারসংক্ষেপ', 'sojib68' ); ?>"><?php echo sojib68_office_icon( 'file', 15 ); // phpcs:ignore ?></a>
							<button type="button" class="btn btn-outline btn-sm btn-icon btn-danger-outline" data-delete-deed="<?php echo (int) $row->id; ?>" title="<?php esc_attr_e( 'মুছুন', 'sojib68' ); ?>"><?php echo sojib68_office_icon( 'trash', 15 ); // phpcs:ignore ?></button>
							</td>
						</tr>
						<?php if ( $row->due_poysha > 0 ) : ?>
							<tr class="deed-collect-row" data-collect-wrap="deed-<?php echo (int) $row->id; ?>">
								<td colspan="9">
									<div class="collect-panel" hidden>
										<?php sojib68_render_collect_form( $row->id, $row->due_poysha ); ?>
									</div>
								</td>
							</tr>
						<?php endif; ?>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>

		<?php if ( $result['total_pages'] > 1 ) : ?>
			<nav class="deed-pagination" aria-label="<?php esc_attr_e( 'পাতা', 'sojib68' ); ?>">
				<?php
				$current = $result['paged'];
				$last    = $result['total_pages'];
				$window  = array_unique( array_filter( array( 1, $current - 1, $current, $current + 1, $last ), function ( $p ) use ( $last ) { return $p >= 1 && $p <= $last; } ) );
				sort( $window );
				$prev = null;
				foreach ( $window as $p ) :
					if ( null !== $prev && $p - $prev > 1 ) :
						echo '<span class="gap">…</span>';
					endif;
					$prev = $p;
					?>
					<a href="<?php echo esc_url( add_query_arg( array_merge( $filters, array( 'paged' => $p ) ), $base ) ); ?>" class="<?php echo $p === $current ? 'is-active' : ''; ?>"><?php echo esc_html( $p ); ?></a>
				<?php endforeach; ?>
			</nav>
		<?php endif; ?>
	<?php endif; ?>
	<?php sojib68_office_dues_script(); ?>
	<?php sojib68_office_deed_actions_script(); ?>
	<?php
}


/* ---------------------------------------------------------------------
 * Dues collection (বকেয়া আদায়)
 * ------------------------------------------------------------------ */

/** Loads the shared collect/delete-payment behaviour. */
function sojib68_office_dues_script() {
	?>
	<script>window.sojib68Dues = { ajaxUrl: <?php echo wp_json_encode( admin_url( 'admin-ajax.php' ) ); ?>, nonce: <?php echo wp_json_encode( wp_create_nonce( 'sojib68_deed_form' ) ); ?> };</script>
	<script src="<?php echo esc_url( get_theme_file_uri( 'assets/js/office-dues.js' ) ); ?>?v=<?php echo (int) @filemtime( get_theme_file_path( 'assets/js/office-dues.js' ) ); ?>"></script>
	<?php
}

/** Delete / restore / purge button behaviour — see assets/js/office-deed-actions.js. */
function sojib68_office_deed_actions_script() {
	?>
	<script>window.sojib68DeedActions = { ajaxUrl: <?php echo wp_json_encode( admin_url( 'admin-ajax.php' ) ); ?>, nonce: <?php echo wp_json_encode( wp_create_nonce( 'sojib68_deed_form' ) ); ?> };</script>
	<script src="<?php echo esc_url( get_theme_file_uri( 'assets/js/office-deed-actions.js' ) ); ?>?v=<?php echo (int) @filemtime( get_theme_file_path( 'assets/js/office-deed-actions.js' ) ); ?>"></script>
	<?php
}

/** WhatsApp click-to-chat link for a Bangladeshi mobile number. */
function sojib68_office_wa_link( $mobile ) {
	$digits = preg_replace( '/\D+/', '', sojib68_bn_to_en_digits( (string) $mobile ) );
	if ( '' === $digits ) {
		return '';
	}
	if ( 0 === strpos( $digits, '01' ) && 11 === strlen( $digits ) ) {
		$digits = '88' . $digits;
	}
	return 'https://wa.me/' . $digits;
}

function sojib68_render_payment_history( $payments ) {
	$methods = sojib68_payment_method_options();
	?>
	<?php if ( empty( $payments ) ) : ?>
		<p class="deed-empty"><?php esc_html_e( 'এখনো কোনো টাকা আদায়ের এন্ট্রি নেই।', 'sojib68' ); ?></p>
	<?php else : ?>
		<ul class="pay-list">
			<?php foreach ( $payments as $pay ) : ?>
				<li>
					<span class="pay-list__date"><?php echo esc_html( $pay->received_date ? $pay->received_date : '—' ); ?></span>
					<strong class="pay-list__amt"><?php echo sojib68_money_html( $pay->amount_poysha ); // phpcs:ignore ?></strong>
					<span class="pay-list__meta">
						<?php echo esc_html( isset( $methods[ $pay->method ] ) ? $methods[ $pay->method ] : '' ); ?>
						<?php echo $pay->note ? ' · ' . esc_html( $pay->note ) : ''; ?>
					</span>
					<button type="button" class="pay-list__del" data-del-payment="<?php echo (int) $pay->id; ?>" aria-label="<?php esc_attr_e( 'এন্ট্রি মুছুন', 'sojib68' ); ?>"><?php echo sojib68_office_icon( 'trash', 15 ); // phpcs:ignore ?></button>
				</li>
			<?php endforeach; ?>
		</ul>
	<?php endif; ?>
	<?php
}

/** Quick "টাকা আদায়" panel. A div (not a form) so it can live inside the entry form too. */
function sojib68_render_collect_form( $deed_id, $due_poysha = null ) {
	$full = ( null !== $due_poysha && $due_poysha > 0 ) ? sojib68_poysha_to_display( $due_poysha ) : '';
	?>
	<div class="collect" data-collect="<?php echo (int) $deed_id; ?>">
		<div class="deed-grid">
			<div class="deed-field col-3">
				<label><?php esc_html_e( 'কত টাকা আদায় হলো', 'sojib68' ); ?></label>
				<input type="text" inputmode="decimal" data-money="collect" data-collect-field="amount" placeholder="0">
				<?php if ( $full ) : ?>
					<button type="button" class="deed-link" data-fill="<?php echo esc_attr( str_replace( ',', '', $full ) ); ?>"><?php echo esc_html( sprintf( /* translators: %s: amount */ __( 'পুরো বাকি (৳ %s)', 'sojib68' ), $full ) ); ?></button>
				<?php endif; ?>
			</div>
			<div class="deed-field col-3 m-6">
				<label><?php esc_html_e( 'তারিখ', 'sojib68' ); ?></label>
				<input type="date" data-collect-field="received_date" value="<?php echo esc_attr( current_time( 'Y-m-d' ) ); ?>">
			</div>
			<div class="deed-field col-3 m-6">
				<label><?php esc_html_e( 'মাধ্যম', 'sojib68' ); ?></label>
				<select data-collect-field="method">
					<?php foreach ( sojib68_payment_method_options() as $val => $label ) : ?>
						<option value="<?php echo esc_attr( $val ); ?>"><?php echo esc_html( $label ); ?></option>
					<?php endforeach; ?>
				</select>
			</div>
			<div class="deed-field col-3 deed-field--end">
				<button type="button" class="btn btn-primary" data-collect-save><?php esc_html_e( 'সংরক্ষণ', 'sojib68' ); ?></button>
			</div>
			<div class="deed-field col-12">
				<input type="text" data-collect-field="note" placeholder="<?php esc_attr_e( 'মন্তব্য (ঐচ্ছিক) — যেমন: বিকাশে পাঠিয়েছে', 'sojib68' ); ?>">
			</div>
		</div>
		<p class="collect__msg" hidden></p>
	</div>
	<?php
}

function sojib68_office_render_dues() {
	$q     = isset( $_GET['q'] ) ? sanitize_text_field( wp_unslash( $_GET['q'] ) ) : '';
	$sort  = isset( $_GET['sort'] ) ? sanitize_key( wp_unslash( $_GET['sort'] ) ) : 'due_desc';
	$paged = isset( $_GET['paged'] ) ? max( 1, (int) $_GET['paged'] ) : 1;

	$summary = sojib68_dues_summary();
	$result  = sojib68_dues_list( array( 'q' => $q, 'sort' => $sort ), $paged, 15 );
	$base    = home_url( '/' . SOJIB68_OFFICE_BASE . '/dues' );
	?>
	<div class="office-stats-grid office-stats-grid--3">
		<div class="office-stat-tile tone-red is-due">
			<span class="office-stat-tile__icon"><?php echo sojib68_office_icon( 'clock', 22 ); // phpcs:ignore ?></span>
			<span class="office-stat-tile__label"><?php esc_html_e( 'মোট বকেয়া', 'sojib68' ); ?></span>
			<span class="office-stat-tile__value"><?php echo sojib68_money_html( $summary['total_due'] ); // phpcs:ignore ?></span>
		</div>
		<div class="office-stat-tile tone-amber">
			<span class="office-stat-tile__icon"><?php echo sojib68_office_icon( 'file', 22 ); // phpcs:ignore ?></span>
			<span class="office-stat-tile__label"><?php esc_html_e( 'বকেয়া আছে এমন দলিল', 'sojib68' ); ?></span>
			<span class="office-stat-tile__value"><?php echo esc_html( number_format( $summary['deed_count'] ) ); ?></span>
		</div>
		<div class="office-stat-tile tone-green is-hero">
			<span class="office-stat-tile__icon"><?php echo sojib68_office_icon( 'trending', 22 ); // phpcs:ignore ?></span>
			<span class="office-stat-tile__label"><?php esc_html_e( 'এই মাসে আদায় হয়েছে', 'sojib68' ); ?></span>
			<span class="office-stat-tile__value"><?php echo sojib68_money_html( $summary['month_collected'] ); // phpcs:ignore ?></span>
		</div>
	</div>

	<form method="get" action="<?php echo esc_url( $base ); ?>" class="dues-toolbar">
		<div class="dues-toolbar__search">
			<?php echo sojib68_office_icon( 'search', 18 ); // phpcs:ignore ?>
			<input type="text" name="q" value="<?php echo esc_attr( $q ); ?>" placeholder="<?php esc_attr_e( 'নাম, মোবাইল বা দলিল নং দিয়ে খুঁজুন', 'sojib68' ); ?>">
		</div>
		<select name="sort" onchange="this.form.submit()">
			<option value="due_desc" <?php selected( $sort, 'due_desc' ); ?>><?php esc_html_e( 'বেশি বকেয়া আগে', 'sojib68' ); ?></option>
			<option value="oldest" <?php selected( $sort, 'oldest' ); ?>><?php esc_html_e( 'পুরোনো দলিল আগে', 'sojib68' ); ?></option>
			<option value="newest" <?php selected( $sort, 'newest' ); ?>><?php esc_html_e( 'নতুন দলিল আগে', 'sojib68' ); ?></option>
		</select>
		<button type="submit" class="btn btn-primary"><?php esc_html_e( 'খুঁজুন', 'sojib68' ); ?></button>
	</form>

	<div class="dues-flash" id="dues-flash" hidden></div>

	<?php if ( empty( $result['rows'] ) ) : ?>
		<div class="deed-empty-state">
			<?php echo sojib68_office_icon( 'check', 34 ); // phpcs:ignore ?>
			<p><?php echo $q ? esc_html__( 'এই খোঁজে কোনো বকেয়া পাওয়া যায়নি।', 'sojib68' ) : esc_html__( 'কোনো বকেয়া নেই — সব টাকা আদায় হয়ে গেছে!', 'sojib68' ); ?></p>
		</div>
	<?php else : ?>
		<div class="due-list">
			<?php foreach ( $result['rows'] as $row ) :
				$total   = (int) $row->client_total_due_poysha;
				$percent = $total > 0 ? max( 0, min( 100, round( $row->received_poysha * 100 / $total ) ) ) : 0;
				$people  = array();
				foreach ( $row->grantees as $p ) { $p->role = __( 'গ্রহীতা', 'sojib68' ); $people[] = $p; }
				foreach ( $row->donors as $p ) { $p->role = __( 'দাতা', 'sojib68' ); $people[] = $p; }
				?>
				<article class="due-card" data-collect-wrap>
					<div class="due-card__top">
						<div>
							<h3 class="due-card__title">
								<?php echo esc_html( $row->deed_no ? sprintf( /* translators: %s: deed no */ __( 'দলিল #%s', 'sojib68' ), $row->deed_no ) : __( 'দলিল নং নেই', 'sojib68' ) ); ?>
								<?php if ( $row->deed_type ) : ?><span class="deed-type-chip"><?php echo esc_html( $row->deed_type ); ?></span><?php endif; ?>
							</h3>
							<p class="due-card__sub"><?php echo esc_html( $row->reg_date ? $row->reg_date : ( $row->reg_year ? $row->reg_year : __( 'সাল অজানা', 'sojib68' ) ) ); ?></p>
						</div>
						<div class="due-card__due">
							<span><?php esc_html_e( 'বাকি', 'sojib68' ); ?></span>
							<strong><?php echo sojib68_money_html( $row->due_poysha ); // phpcs:ignore ?></strong>
						</div>
					</div>

					<div class="due-card__people">
						<?php foreach ( $people as $p ) : ?>
							<div class="due-person">
								<span class="due-person__name"><span class="due-person__role"><?php echo esc_html( $p->role ); ?></span> <?php echo esc_html( $p->name ?: '—' ); ?></span>
								<?php if ( $p->mobile ) : $wa = sojib68_office_wa_link( $p->mobile ); ?>
									<span class="due-person__actions">
										<a href="tel:<?php echo esc_attr( preg_replace( '/[^0-9+]/', '', sojib68_bn_to_en_digits( $p->mobile ) ) ); ?>" class="due-act due-act--call"><?php echo esc_html( $p->mobile ); ?></a>
										<?php if ( $wa ) : ?><a href="<?php echo esc_url( $wa ); ?>" target="_blank" rel="noopener" class="due-act due-act--wa">WhatsApp</a><?php endif; ?>
									</span>
								<?php endif; ?>
							</div>
						<?php endforeach; ?>
					</div>

					<div class="due-progress" role="img" aria-label="<?php echo esc_attr( $percent . '%' ); ?>">
						<div class="due-progress__bar"><span style="width: <?php echo (int) $percent; ?>%"></span></div>
						<div class="due-progress__meta">
							<span><?php echo sojib68_money_html( $row->received_poysha ); // phpcs:ignore ?> <?php esc_html_e( 'আদায়', 'sojib68' ); ?></span>
							<span><?php esc_html_e( 'মোট', 'sojib68' ); ?> <?php echo sojib68_money_html( $total ); // phpcs:ignore ?></span>
						</div>
					</div>

					<div class="due-card__actions">
						<button type="button" class="btn btn-primary btn-sm" data-toggle-collect><?php echo sojib68_office_icon( 'wallet', 16 ); // phpcs:ignore ?> <?php esc_html_e( 'টাকা আদায় করুন', 'sojib68' ); ?></button>
						<a href="<?php echo esc_url( add_query_arg( 'id', $row->id, home_url( '/' . SOJIB68_OFFICE_BASE . '/entry-new' ) ) ); ?>" class="btn btn-outline btn-sm"><?php esc_html_e( 'দলিল দেখুন', 'sojib68' ); ?></a>
						<a href="<?php echo esc_url( home_url( '/' . SOJIB68_OFFICE_BASE . '/print/' . $row->id ) ); ?>" target="_blank" rel="noopener" class="btn btn-outline btn-sm"><?php echo sojib68_office_icon( 'file', 15 ); // phpcs:ignore ?> <?php esc_html_e( 'প্রিন্ট', 'sojib68' ); ?></a>
					</div>

					<div class="due-card__collect collect-panel" hidden>
						<?php sojib68_render_collect_form( $row->id, $row->due_poysha ); ?>
					</div>

					<?php if ( ! empty( $row->payments ) ) : ?>
						<details class="due-card__history">
							<summary><?php echo esc_html( sprintf( /* translators: %d: count */ __( 'আদায়ের ইতিহাস (%d)', 'sojib68' ), count( $row->payments ) ) ); ?></summary>
							<?php sojib68_render_payment_history( $row->payments ); ?>
						</details>
					<?php endif; ?>
				</article>
			<?php endforeach; ?>
		</div>

		<?php if ( $result['total_pages'] > 1 ) : ?>
			<nav class="deed-pagination" aria-label="<?php esc_attr_e( 'পাতা', 'sojib68' ); ?>">
				<?php for ( $pg = 1; $pg <= $result['total_pages']; $pg++ ) : ?>
					<a href="<?php echo esc_url( add_query_arg( array( 'q' => $q, 'sort' => $sort, 'paged' => $pg ), $base ) ); ?>" class="<?php echo $pg === $result['paged'] ? 'is-active' : ''; ?>"><?php echo (int) $pg; ?></a>
				<?php endfor; ?>
			</nav>
		<?php endif; ?>
	<?php endif; ?>

	<?php sojib68_office_dues_script(); ?>
	<?php
}

/* ---------------------------------------------------------------------
 * প্রফিট প্রিভিউ — which deeds make up the dashboard's মোট প্রফিট
 * ------------------------------------------------------------------ */

function sojib68_office_render_profit_preview() {
	$q     = isset( $_GET['q'] ) ? sanitize_text_field( wp_unslash( $_GET['q'] ) ) : '';
	$sort  = isset( $_GET['sort'] ) ? sanitize_key( wp_unslash( $_GET['sort'] ) ) : 'profit_desc';
	$paged = isset( $_GET['paged'] ) ? max( 1, (int) $_GET['paged'] ) : 1;

	$stats  = sojib68_office_dashboard_stats();
	$result = sojib68_profit_deeds( array( 'q' => $q, 'sort' => $sort ), $paged, 20 );
	$base   = home_url( '/' . SOJIB68_OFFICE_BASE . '/profit' );
	$edit   = home_url( '/' . SOJIB68_OFFICE_BASE . '/entry-new' );
	?>
	<p class="profit-back"><a href="<?php echo esc_url( home_url( '/' . SOJIB68_OFFICE_BASE . '/' ) ); ?>" class="deed-link"><?php echo sojib68_office_icon( 'arrow-left', 15 ); // phpcs:ignore ?> <?php esc_html_e( 'ড্যাশবোর্ডে ফিরুন', 'sojib68' ); ?></a></p>

	<div class="office-stats-grid office-stats-grid--3">
		<div class="office-stat-tile tone-teal">
			<span class="office-stat-tile__icon"><?php echo sojib68_office_icon( 'wallet', 22 ); // phpcs:ignore ?></span>
			<span class="office-stat-tile__label"><?php esc_html_e( 'মোট আদায়', 'sojib68' ); ?></span>
			<span class="office-stat-tile__value"><?php echo sojib68_money_html( $stats['total_collected'] ); // phpcs:ignore ?></span>
		</div>
		<div class="office-stat-tile tone-red">
			<span class="office-stat-tile__icon"><?php echo sojib68_office_icon( 'receipt', 22 ); // phpcs:ignore ?></span>
			<span class="office-stat-tile__label"><?php esc_html_e( 'মোট খরচ', 'sojib68' ); ?></span>
			<span class="office-stat-tile__value"><?php echo sojib68_money_html( $stats['total_expenses'] ); // phpcs:ignore ?></span>
		</div>
		<div class="office-stat-tile tone-green is-hero">
			<span class="office-stat-tile__icon"><?php echo sojib68_office_icon( 'trending', 22 ); // phpcs:ignore ?></span>
			<span class="office-stat-tile__label"><?php esc_html_e( 'মোট প্রফিট', 'sojib68' ); ?></span>
			<span class="office-stat-tile__value"><?php echo sojib68_money_html( $stats['total_profit'] ); // phpcs:ignore ?></span>
			<span class="office-stat-tile__sub"><?php echo esc_html( sprintf( /* translators: %s: deed count in Bangla digits */ __( '%s টি দলিল থেকে', 'sojib68' ), sojib68_bn_digits( $result['total'] ) ) ); ?></span>
		</div>
	</div>

	<p class="deed-note">
		<?php echo sojib68_office_icon( 'info', 18 ); // phpcs:ignore ?>
		<span><?php esc_html_e( 'কোনো দলিলের প্রফিট ভুল মনে হলে "এডিট" চাপুন। পুরনো ডায়েরির দলিল হলে "এটি পুরাতন হিসাব"-এ টিক দিয়ে সাবমিট করুন — তখন সেটা এই তালিকা আর প্রফিট থেকে সরে যাবে, বকেয়া ঠিক থাকবে।', 'sojib68' ); ?></span>
	</p>

	<form method="get" action="<?php echo esc_url( $base ); ?>" class="dues-toolbar">
		<div class="dues-toolbar__search">
			<?php echo sojib68_office_icon( 'search', 18 ); // phpcs:ignore ?>
			<input type="text" name="q" value="<?php echo esc_attr( $q ); ?>" placeholder="<?php esc_attr_e( 'নাম, মোবাইল বা দলিল নং দিয়ে খুঁজুন', 'sojib68' ); ?>">
		</div>
		<select name="sort" onchange="this.form.submit()">
			<option value="profit_desc" <?php selected( $sort, 'profit_desc' ); ?>><?php esc_html_e( 'বেশি প্রফিট আগে', 'sojib68' ); ?></option>
			<option value="newest" <?php selected( $sort, 'newest' ); ?>><?php esc_html_e( 'নতুন দলিল আগে', 'sojib68' ); ?></option>
			<option value="oldest" <?php selected( $sort, 'oldest' ); ?>><?php esc_html_e( 'পুরোনো দলিল আগে', 'sojib68' ); ?></option>
		</select>
		<button type="submit" class="btn btn-primary"><?php esc_html_e( 'খুঁজুন', 'sojib68' ); ?></button>
	</form>

	<?php if ( empty( $result['rows'] ) ) : ?>
		<div class="deed-empty-state">
			<?php echo sojib68_office_icon( 'check', 34 ); // phpcs:ignore ?>
			<p><?php echo $q ? esc_html__( 'এই খোঁজে কোনো দলিল পাওয়া যায়নি।', 'sojib68' ) : esc_html__( 'এখনো কোনো দলিলে আদায় বা খরচ লেখা নেই।', 'sojib68' ); ?></p>
		</div>
	<?php else : ?>
		<div class="deed-table-card">
			<table class="deed-table profit-table">
				<thead>
					<tr>
						<th><?php esc_html_e( 'ক্রমিক', 'sojib68' ); ?></th>
						<th><?php esc_html_e( 'দলিল', 'sojib68' ); ?></th>
						<th><?php esc_html_e( 'দাতা → গ্রহীতা', 'sojib68' ); ?></th>
						<th class="num"><?php esc_html_e( 'মোট টাকা', 'sojib68' ); ?></th>
						<th class="num"><?php esc_html_e( 'আদায়', 'sojib68' ); ?></th>
						<th class="num"><?php esc_html_e( 'খরচ', 'sojib68' ); ?></th>
						<th class="num"><?php esc_html_e( 'প্রফিট', 'sojib68' ); ?></th>
						<th></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $result['rows'] as $row ) : ?>
						<tr>
							<td data-label="<?php esc_attr_e( 'ক্রমিক', 'sojib68' ); ?>"><?php echo esc_html( $row->serial_no ? sojib68_bn_digits( $row->serial_no ) : '—' ); ?></td>
							<td data-label="<?php esc_attr_e( 'দলিল', 'sojib68' ); ?>">
								<span class="deed-cell-main"><?php echo esc_html( $row->deed_no ? '#' . $row->deed_no : __( 'নং নেই', 'sojib68' ) ); ?></span>
								<span class="deed-cell-sub"><?php echo esc_html( $row->reg_date ? $row->reg_date : ( $row->reg_year ? $row->reg_year : __( 'সাল অজানা', 'sojib68' ) ) ); ?><?php echo $row->deed_type ? ' · ' . esc_html( $row->deed_type ) : ''; ?></span>
							</td>
							<td data-label="<?php esc_attr_e( 'দাতা → গ্রহীতা', 'sojib68' ); ?>">
								<span class="deed-party"><?php echo esc_html( implode( ', ', $row->donor_names ) ?: '—' ); ?></span>
								<span class="deed-party-arrow" aria-hidden="true">→</span>
								<span class="deed-party"><?php echo esc_html( implode( ', ', $row->grantee_names ) ?: '—' ); ?></span>
							</td>
							<td class="num" data-label="<?php esc_attr_e( 'মোট টাকা', 'sojib68' ); ?>"><span><?php echo sojib68_money_html( $row->client_total_due_poysha ); // phpcs:ignore ?></span></td>
							<td class="num" data-label="<?php esc_attr_e( 'আদায়', 'sojib68' ); ?>"><span><?php echo sojib68_money_html( $row->received_poysha ); // phpcs:ignore ?></span></td>
							<td class="num" data-label="<?php esc_attr_e( 'খরচ', 'sojib68' ); ?>"><span><?php echo sojib68_money_html( $row->spent_poysha ); // phpcs:ignore ?></span></td>
							<td class="num" data-label="<?php esc_attr_e( 'প্রফিট', 'sojib68' ); ?>"><strong class="profit-cell <?php echo $row->profit_poysha < 0 ? 'is-loss' : ''; ?>"><?php echo sojib68_money_html( $row->profit_poysha ); // phpcs:ignore ?></strong></td>
							<td class="act"><a href="<?php echo esc_url( add_query_arg( array( 'id' => $row->id, 'from' => 'profit' ), $edit ) ); ?>" class="btn btn-outline btn-sm"><?php esc_html_e( 'এডিট', 'sojib68' ); ?></a></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>

		<?php if ( $result['total_pages'] > 1 ) : ?>
			<nav class="deed-pagination" aria-label="<?php esc_attr_e( 'পাতা', 'sojib68' ); ?>">
				<?php for ( $pg = 1; $pg <= $result['total_pages']; $pg++ ) : ?>
					<a href="<?php echo esc_url( add_query_arg( array( 'q' => $q, 'sort' => $sort, 'paged' => $pg ), $base ) ); ?>" class="<?php echo $pg === $result['paged'] ? 'is-active' : ''; ?>"><?php echo (int) $pg; ?></a>
				<?php endfor; ?>
			</nav>
		<?php endif; ?>
	<?php endif; ?>
	<?php
}

/* ---------------------------------------------------------------------
 * রিসাইকেল বিন (recycle bin) — soft-deleted deeds, restorable until
 * they auto-purge (see SOJIB68_DEEDS_TRASH_DAYS).
 * ------------------------------------------------------------------ */

function sojib68_office_render_recycle_bin() {
	$rows = sojib68_recycle_bin_list();
	?>
	<div class="dues-flash" id="office-flash" hidden></div>

	<?php if ( empty( $rows ) ) : ?>
		<div class="deed-empty-state">
			<?php echo sojib68_office_icon( 'trash', 34 ); // phpcs:ignore ?>
			<p><?php esc_html_e( 'রিসাইকেল বিন খালি।', 'sojib68' ); ?></p>
		</div>
	<?php else : ?>
		<p class="deed-result-count">
			<?php echo esc_html( sprintf( /* translators: %d: count */ __( 'মোট %d টি দলিল রিসাইকেল বিনে আছে', 'sojib68' ), count( $rows ) ) ); ?>
		</p>
		<div class="deed-table-card">
			<table class="deed-table">
				<thead>
					<tr>
						<th><?php esc_html_e( 'দলিল', 'sojib68' ); ?></th>
						<th><?php esc_html_e( 'দাতা → গ্রহীতা', 'sojib68' ); ?></th>
						<th><?php esc_html_e( 'মুছে ফেলার তারিখ', 'sojib68' ); ?></th>
						<th><?php esc_html_e( 'স্থায়ীভাবে মোছার বাকি', 'sojib68' ); ?></th>
						<th></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $rows as $row ) : ?>
						<tr>
							<td data-label="<?php esc_attr_e( 'দলিল', 'sojib68' ); ?>">
								<strong class="deed-cell-main"><?php echo esc_html( $row->deed_no ? '#' . $row->deed_no : '#' . $row->id ); ?></strong>
								<span class="deed-cell-sub"><?php echo esc_html( $row->reg_date ? $row->reg_date : ( $row->reg_year ? $row->reg_year : __( 'সাল অজানা', 'sojib68' ) ) ); ?></span>
							</td>
							<td data-label="<?php esc_attr_e( 'দাতা → গ্রহীতা', 'sojib68' ); ?>">
								<?php if ( ! empty( $row->donor_names ) || ! empty( $row->grantee_names ) ) : ?>
									<span class="deed-party"><?php echo esc_html( implode( ', ', $row->donor_names ) ?: '—' ); ?></span>
									<span class="deed-party-arrow" aria-hidden="true">→</span>
									<span class="deed-party"><?php echo esc_html( implode( ', ', $row->grantee_names ) ?: '—' ); ?></span>
								<?php else : ?>
									<span class="deed-cell-sub">—</span>
								<?php endif; ?>
							</td>
							<td data-label="<?php esc_attr_e( 'মুছে ফেলার তারিখ', 'sojib68' ); ?>">
								<?php echo esc_html( substr( (string) $row->deleted_at, 0, 10 ) ); ?>
							</td>
							<td data-label="<?php esc_attr_e( 'বাকি', 'sojib68' ); ?>">
								<?php if ( $row->days_left > 0 ) : ?>
									<span class="deed-chip deed-chip--due"><?php echo esc_html( sprintf( /* translators: %s: days left in Bangla digits */ __( '%s দিন', 'sojib68' ), sojib68_bn_digits( $row->days_left ) ) ); ?></span>
								<?php else : ?>
									<span class="deed-chip deed-chip--unknown"><?php esc_html_e( 'আজই মুছে যাবে', 'sojib68' ); ?></span>
								<?php endif; ?>
							</td>
							<td class="act">
								<button type="button" class="btn btn-primary btn-sm" data-restore-deed="<?php echo (int) $row->id; ?>"><?php echo sojib68_office_icon( 'check', 15 ); // phpcs:ignore ?> <?php esc_html_e( 'ফিরিয়ে আনুন', 'sojib68' ); ?></button>
								<button type="button" class="btn btn-outline btn-sm btn-danger-outline" data-purge-deed="<?php echo (int) $row->id; ?>"><?php echo sojib68_office_icon( 'trash', 15 ); // phpcs:ignore ?> <?php esc_html_e( 'এখনই স্থায়ীভাবে মুছুন', 'sojib68' ); ?></button>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	<?php endif; ?>

	<?php sojib68_office_deed_actions_script(); ?>
	<?php
}

/* ---------------------------------------------------------------------
 * Print summary (একক দলিলের প্রিন্ট-যোগ্য সারসংক্ষেপ)
 *
 * A standalone page — no sidebar/topbar — so it prints cleanly and a
 * browser's "Save as PDF" turns it straight into a PDF. Still behind
 * the same office login as everything else in this file.
 * ------------------------------------------------------------------ */

function sojib68_office_render_print( $deed_id ) {
	$deed = $deed_id ? sojib68_deed_get( $deed_id ) : null;

	if ( ! $deed ) {
		wp_die( esc_html__( 'দলিলটি খুঁজে পাওয়া যায়নি।', 'sojib68' ), '', array( 'response' => 404 ) );
	}

	$user = wp_get_current_user();
	?>
	<!DOCTYPE html>
	<html <?php language_attributes(); ?>>
	<head>
		<meta charset="<?php bloginfo( 'charset' ); ?>">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<meta name="robots" content="noindex, nofollow">
		<title><?php echo esc_html( sprintf( /* translators: %s: deed number or id */ __( 'দলিল সারসংক্ষেপ — %s', 'sojib68' ), $deed->deed_no ? $deed->deed_no : '#' . $deed->id ) ); ?></title>
		<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Figtree:wght@400;500;600;700&family=Hind+Siliguri:wght@400;500;600;700&display=swap">
		<link rel="stylesheet" href="<?php echo esc_url( get_theme_file_uri( 'assets/css/office-print.css' ) ); ?>?v=<?php echo (int) @filemtime( get_theme_file_path( 'assets/css/office-print.css' ) ); // phpcs:ignore ?>">
	</head>
	<body class="print-body">
		<div class="print-bar no-print">
			<a href="<?php echo esc_url( add_query_arg( 'id', $deed->id, home_url( '/' . SOJIB68_OFFICE_BASE . '/entry-new' ) ) ); ?>" class="btn btn-outline btn-sm"><?php echo sojib68_office_icon( 'arrow-left', 16 ); // phpcs:ignore ?> <?php esc_html_e( 'দলিলে ফিরুন', 'sojib68' ); ?></a>
			<button type="button" class="btn btn-primary btn-sm" onclick="window.print()"><?php echo sojib68_office_icon( 'file', 16 ); // phpcs:ignore ?> <?php esc_html_e( 'প্রিন্ট করুন / PDF সেভ করুন', 'sojib68' ); ?></button>
		</div>

		<article class="print-page">
			<header class="print-head">
				<?php if ( get_theme_file_path( 'assets/images/logo.webp' ) && file_exists( get_theme_file_path( 'assets/images/logo.webp' ) ) ) : ?>
					<img src="<?php echo esc_url( get_theme_file_uri( 'assets/images/logo.webp' ) ); ?>" alt="" class="print-head__logo" width="150" height="41">
				<?php endif; ?>
				<div class="print-head__biz">
					<strong><?php bloginfo( 'name' ); ?></strong>
					<?php if ( SOJIB68_ADDRESS ) : ?><span><?php echo esc_html( SOJIB68_ADDRESS ); ?></span><?php endif; ?>
					<?php if ( SOJIB68_PHONE ) : ?><span><?php esc_html_e( 'ফোন', 'sojib68' ); ?>: <?php echo esc_html( SOJIB68_PHONE ); ?></span><?php endif; ?>
				</div>
			</header>

			<h1 class="print-title">
				<?php esc_html_e( 'দলিল সারসংক্ষেপ', 'sojib68' ); ?>
				<span><?php echo esc_html( $deed->deed_no ? '#' . $deed->deed_no : '#' . $deed->id ); ?></span>
			</h1>
			<p class="print-meta">
				<?php echo esc_html( sprintf( /* translators: 1: date, 2: printed-by name */ __( 'প্রিন্ট করা হয়েছে: %1$s, %2$s কর্তৃক', 'sojib68' ), date_i18n( 'd-m-Y h:i A' ), $user->display_name ) ); ?>
				<?php if ( $deed->serial_no ) : ?>
					— <?php echo esc_html( sprintf( /* translators: %s: serial number in Bangla digits */ __( 'ক্রমিক নং %s', 'sojib68' ), sojib68_bn_digits( $deed->serial_no ) ) ); ?>
				<?php endif; ?>
			</p>

			<section class="print-section">
				<h2><?php esc_html_e( 'দলিলের পরিচয়', 'sojib68' ); ?></h2>
				<table class="print-kv">
					<tr><th><?php esc_html_e( 'সাব-রেজিস্ট্রেশন অফিস', 'sojib68' ); ?></th><td><?php echo esc_html( $deed->sub_registry_office ?: '—' ); ?></td></tr>
					<tr><th><?php esc_html_e( 'দলিলের ধরন', 'sojib68' ); ?></th><td><?php echo esc_html( $deed->deed_type ?: '—' ); ?></td></tr>
					<tr><th><?php esc_html_e( 'রেজিস্ট্রেশনের তারিখ', 'sojib68' ); ?></th><td><?php echo esc_html( $deed->reg_date ? $deed->reg_date : ( $deed->reg_year ? $deed->reg_year : '—' ) ); ?></td></tr>
					<tr><th><?php esc_html_e( 'দলিলের মূল্য', 'sojib68' ); ?></th><td><?php echo sojib68_money_html( $deed->deed_value_poysha ); // phpcs:ignore ?></td></tr>
				</table>
			</section>

			<?php if ( array_filter( $deed->donors, fn( $d ) => '' !== trim( (string) ( $d->name ?? '' ) ) ) ) : ?>
				<section class="print-section">
					<h2><?php esc_html_e( 'দাতার তথ্য', 'sojib68' ); ?></h2>
					<table class="print-table">
						<thead><tr><th><?php esc_html_e( 'নাম', 'sojib68' ); ?></th><th><?php esc_html_e( 'মোবাইল', 'sojib68' ); ?></th><th><?php esc_html_e( 'ঠিকানা', 'sojib68' ); ?></th></tr></thead>
						<tbody>
						<?php foreach ( $deed->donors as $p ) : if ( '' === trim( (string) ( $p->name ?? '' ) ) ) { continue; } ?>
							<tr><td><?php echo esc_html( $p->name ); ?></td><td><?php echo esc_html( $p->mobile ?: '—' ); ?></td><td><?php echo esc_html( $p->address ?: '—' ); ?></td></tr>
						<?php endforeach; ?>
						</tbody>
					</table>
				</section>
			<?php endif; ?>

			<?php if ( array_filter( $deed->grantees, fn( $d ) => '' !== trim( (string) ( $d->name ?? '' ) ) ) ) : ?>
				<section class="print-section">
					<h2><?php esc_html_e( 'গ্রহীতার তথ্য', 'sojib68' ); ?></h2>
					<table class="print-table">
						<thead><tr><th><?php esc_html_e( 'নাম', 'sojib68' ); ?></th><th><?php esc_html_e( 'মোবাইল', 'sojib68' ); ?></th><th><?php esc_html_e( 'ঠিকানা', 'sojib68' ); ?></th></tr></thead>
						<tbody>
						<?php foreach ( $deed->grantees as $p ) : if ( '' === trim( (string) ( $p->name ?? '' ) ) ) { continue; } ?>
							<tr><td><?php echo esc_html( $p->name ); ?></td><td><?php echo esc_html( $p->mobile ?: '—' ); ?></td><td><?php echo esc_html( $p->address ?: '—' ); ?></td></tr>
						<?php endforeach; ?>
						</tbody>
					</table>
				</section>
			<?php endif; ?>

			<?php if ( array_filter( $deed->lands, fn( $l ) => '' !== trim( (string) ( $l->mouza ?? '' ) ) ) ) : ?>
				<section class="print-section">
					<h2><?php esc_html_e( 'জমির তথ্য', 'sojib68' ); ?></h2>
					<table class="print-table">
						<thead><tr><th><?php esc_html_e( 'মৌজা', 'sojib68' ); ?></th><th><?php esc_html_e( 'খতিয়ান নং', 'sojib68' ); ?></th><th><?php esc_html_e( 'পরিমাণ', 'sojib68' ); ?></th></tr></thead>
						<tbody>
						<?php foreach ( $deed->lands as $l ) : if ( '' === trim( (string) ( $l->mouza ?? '' ) ) ) { continue; } ?>
							<tr>
								<td><?php echo esc_html( $l->mouza ); ?></td>
								<td><?php echo esc_html( $l->khatian_no ?: '—' ); ?></td>
								<td><?php echo esc_html( null !== $l->quantity ? rtrim( rtrim( (string) $l->quantity, '0' ), '.' ) . ' ' . $l->unit : '—' ); ?></td>
							</tr>
						<?php endforeach; ?>
						</tbody>
					</table>
				</section>
			<?php endif; ?>

			<?php if ( $deed->notes ) : ?>
				<section class="print-section">
					<h2><?php esc_html_e( 'মন্তব্য', 'sojib68' ); ?></h2>
					<p class="print-note"><?php echo nl2br( esc_html( $deed->notes ) ); // phpcs:ignore ?></p>
				</section>
			<?php endif; ?>

			<?php if ( 'delivered' === $deed->original_status ) : ?>
				<section class="print-section print-section--handover">
					<h2><?php esc_html_e( 'মূল দলিল হস্তান্তরের প্রমাণ', 'sojib68' ); ?></h2>
					<table class="print-kv">
						<tr><th><?php esc_html_e( 'ডেলিভারির তারিখ', 'sojib68' ); ?></th><td><?php echo esc_html( $deed->original_delivered_date ?: '—' ); ?></td></tr>
						<tr><th><?php esc_html_e( 'বুঝে নিয়েছেন', 'sojib68' ); ?></th><td><?php echo esc_html( $deed->original_delivered_to ?: '—' ); ?></td></tr>
						<?php if ( $deed->original_delivered_mobile ) : ?>
							<tr><th><?php esc_html_e( 'মোবাইল নং', 'sojib68' ); ?></th><td><?php echo esc_html( $deed->original_delivered_mobile ); ?></td></tr>
						<?php endif; ?>
						<?php if ( $deed->original_delivered_note ) : ?>
							<tr><th><?php esc_html_e( 'মন্তব্য', 'sojib68' ); ?></th><td><?php echo esc_html( $deed->original_delivered_note ); ?></td></tr>
						<?php endif; ?>
					</table>
					<p class="print-hint"><?php esc_html_e( 'আমি উপরোক্ত দলিলের মূল কপিটি সম্পূর্ণ ও সঠিক অবস্থায় বুঝে পেয়েছি।', 'sojib68' ); ?></p>
				</section>

				<footer class="print-sign">
					<div><span></span><p><?php echo esc_html( $deed->original_delivered_to ? sprintf( /* translators: %s: receiver's name */ __( '%s — এর স্বাক্ষর', 'sojib68' ), $deed->original_delivered_to ) : __( 'গ্রহণকারীর স্বাক্ষর', 'sojib68' ) ); ?></p></div>
					<div><span></span><p><?php esc_html_e( 'অফিসের পক্ষে স্বাক্ষর / সীল', 'sojib68' ); ?></p></div>
				</footer>
			<?php else : ?>
				<footer class="print-sign">
					<div><span></span><p><?php esc_html_e( 'গ্রাহকের স্বাক্ষর', 'sojib68' ); ?></p></div>
					<div><span></span><p><?php esc_html_e( 'অফিসের স্বাক্ষর / সীল', 'sojib68' ); ?></p></div>
				</footer>
			<?php endif; ?>
		</article>
	</body>
	</html>
	<?php
}

/* ---------------------------------------------------------------------
 * Dashboard report charts (মাসিক / বার্ষিক)
 * ------------------------------------------------------------------ */

/** "৳ 1.2L" / "৳ 45k" / "৳ 300" — compact axis labels only; full figures stay in the tooltip and table. */
function sojib68_compact_taka( $poysha ) {
	$taka = $poysha / 100;
	$neg  = $taka < 0;
	$taka = abs( $taka );
	if ( $taka >= 100000 ) {
		$label = rtrim( rtrim( number_format( $taka / 100000, 1 ), '0' ), '.' ) . 'L';
	} elseif ( $taka >= 1000 ) {
		$label = round( $taka / 1000 ) . 'k';
	} else {
		$label = (string) round( $taka );
	}
	return ( $neg ? '-' : '' ) . '৳' . $label;
}

/** Round a max value up to a clean step (for axis gridlines), never 0. */
function sojib68_chart_nice_max( $value ) {
	$value = max( (int) $value, 100 ); // poysha; floor so an all-zero chart still draws a scale.
	$magnitude = pow( 10, floor( log10( $value ) ) );
	$steps     = array( 1, 2, 2.5, 5, 10 );
	foreach ( $steps as $step ) {
		$candidate = $step * $magnitude;
		if ( $candidate >= $value ) {
			return (int) $candidate;
		}
	}
	return (int) ( 10 * $magnitude );
}

/**
 * "আদায় ও খরচ" grouped column chart — two series sharing one taka
 * axis (validated categorical pair: blue for আদায়, orange for খরচ;
 * ΔE 24.7+ under deutan/protan simulation — see the dataviz palette
 * validator). Direct labels + legend mean color is never the only cue.
 */
function sojib68_office_render_collect_expense_chart( $periods, $chart_id ) {
	$w = 960;
	$h = 280;
	$pad_l = 48;
	$pad_r = 10;
	$pad_t = 14;
	$pad_b = 34;
	$plot_w = $w - $pad_l - $pad_r;
	$plot_h = $h - $pad_t - $pad_b;

	$count = max( 1, count( $periods ) );
	$slot  = $plot_w / $count;
	$bar_w = min( 22, $slot * 0.32 );
	$gap   = 3;

	$max_val = 0;
	foreach ( $periods as $p ) {
		$max_val = max( $max_val, $p['collected'], $p['expenses'] );
	}
	$scale_max = sojib68_chart_nice_max( $max_val );
	?>
	<div class="chart-card" data-chart="<?php echo esc_attr( $chart_id ); ?>">
		<div class="chart-card__head">
			<h3><?php esc_html_e( 'আদায় ও খরচ', 'sojib68' ); ?></h3>
			<ul class="chart-legend">
				<li><span class="chart-legend__swatch" style="background:#2a78d6"></span><?php esc_html_e( 'আদায়', 'sojib68' ); ?></li>
				<li><span class="chart-legend__swatch" style="background:#eb6834"></span><?php esc_html_e( 'খরচ', 'sojib68' ); ?></li>
			</ul>
		</div>
		<svg viewBox="0 0 <?php echo (int) $w; ?> <?php echo (int) $h; ?>" class="chart-svg" role="img" aria-label="<?php esc_attr_e( 'আদায় ও খরচের কলাম চার্ট', 'sojib68' ); ?>">
			<?php for ( $g = 1; $g <= 4; $g++ ) : $gy = $pad_t + $plot_h - ( $plot_h * $g / 4 ); ?>
				<line class="chart-grid" x1="<?php echo (int) $pad_l; ?>" y1="<?php echo esc_attr( $gy ); ?>" x2="<?php echo (int) ( $w - $pad_r ); ?>" y2="<?php echo esc_attr( $gy ); ?>"></line>
				<text class="chart-axislabel" x="<?php echo (int) ( $pad_l - 8 ); ?>" y="<?php echo esc_attr( $gy + 3 ); ?>" text-anchor="end"><?php echo esc_html( sojib68_compact_taka( $scale_max * $g / 4 ) ); ?></text>
			<?php endfor; ?>
			<line class="chart-baseline" x1="<?php echo (int) $pad_l; ?>" y1="<?php echo (int) ( $pad_t + $plot_h ); ?>" x2="<?php echo (int) ( $w - $pad_r ); ?>" y2="<?php echo (int) ( $pad_t + $plot_h ); ?>"></line>

			<?php foreach ( $periods as $i => $p ) :
				$slot_x  = $pad_l + $i * $slot;
				$cx      = $slot_x + $slot / 2 - $gap / 2 - $bar_w;
				$ex      = $slot_x + $slot / 2 + $gap / 2;
				$c_h     = $scale_max > 0 ? ( $p['collected'] / $scale_max ) * $plot_h : 0;
				$e_h     = $scale_max > 0 ? ( $p['expenses'] / $scale_max ) * $plot_h : 0;
				$base_y  = $pad_t + $plot_h;
				?>
				<g class="chart-group" tabindex="0"
					data-label="<?php echo esc_attr( $p['label'] ); ?>"
					data-collected="<?php echo esc_attr( round( $p['collected'] / 100 ) ); ?>"
					data-expenses="<?php echo esc_attr( round( $p['expenses'] / 100 ) ); ?>">
					<rect class="chart-bar chart-bar--blue" x="<?php echo esc_attr( $cx ); ?>" y="<?php echo esc_attr( $base_y - $c_h ); ?>" width="<?php echo esc_attr( $bar_w ); ?>" height="<?php echo esc_attr( max( 0, $c_h ) ); ?>" rx="3"></rect>
					<rect class="chart-bar chart-bar--orange" x="<?php echo esc_attr( $ex ); ?>" y="<?php echo esc_attr( $base_y - $e_h ); ?>" width="<?php echo esc_attr( $bar_w ); ?>" height="<?php echo esc_attr( max( 0, $e_h ) ); ?>" rx="3"></rect>
					<rect class="chart-hit" x="<?php echo esc_attr( $slot_x + 1 ); ?>" y="<?php echo (int) $pad_t; ?>" width="<?php echo esc_attr( $slot - 2 ); ?>" height="<?php echo (int) $plot_h; ?>" fill="transparent"></rect>
					<text class="chart-axislabel chart-axislabel--x" x="<?php echo esc_attr( $slot_x + $slot / 2 ); ?>" y="<?php echo (int) ( $h - 12 ); ?>" text-anchor="middle"><?php echo esc_html( $p['label'] ); ?></text>
				</g>
			<?php endforeach; ?>
		</svg>
	</div>
	<?php
}

/**
 * "প্রফিট" diverging column chart — single measure, sign carries the
 * meaning (green = profit, red = loss) around a zero baseline.
 */
function sojib68_office_render_profit_chart( $periods, $chart_id ) {
	$w = 960;
	$h = 280;
	$pad_l = 48;
	$pad_r = 10;
	$pad_t = 14;
	$pad_b = 34;
	$plot_w = $w - $pad_l - $pad_r;
	$plot_h = $h - $pad_t - $pad_b;

	$count = max( 1, count( $periods ) );
	$slot  = $plot_w / $count;
	$bar_w = min( 24, $slot * 0.4 );

	$max_abs = 1;
	foreach ( $periods as $p ) {
		$max_abs = max( $max_abs, abs( $p['profit'] ) );
	}
	$scale_max = sojib68_chart_nice_max( $max_abs );
	$zero_y    = $pad_t + $plot_h / 2;
	$half_h    = $plot_h / 2;
	?>
	<div class="chart-card" data-chart="<?php echo esc_attr( $chart_id ); ?>">
		<div class="chart-card__head">
			<h3><?php esc_html_e( 'প্রফিট', 'sojib68' ); ?></h3>
			<ul class="chart-legend chart-legend--sign">
				<li><span class="chart-legend__swatch" style="background:#198030"></span><?php esc_html_e( 'লাভ', 'sojib68' ); ?></li>
				<li><span class="chart-legend__swatch" style="background:#b3261e"></span><?php esc_html_e( 'ক্ষতি', 'sojib68' ); ?></li>
			</ul>
		</div>
		<svg viewBox="0 0 <?php echo (int) $w; ?> <?php echo (int) $h; ?>" class="chart-svg" role="img" aria-label="<?php esc_attr_e( 'প্রফিটের কলাম চার্ট', 'sojib68' ); ?>">
			<?php for ( $g = -2; $g <= 2; $g++ ) :
				if ( 0 === $g ) { continue; }
				$gy = $zero_y - ( $half_h * $g / 2 );
				?>
				<line class="chart-grid" x1="<?php echo (int) $pad_l; ?>" y1="<?php echo esc_attr( $gy ); ?>" x2="<?php echo (int) ( $w - $pad_r ); ?>" y2="<?php echo esc_attr( $gy ); ?>"></line>
				<text class="chart-axislabel" x="<?php echo (int) ( $pad_l - 8 ); ?>" y="<?php echo esc_attr( $gy + 3 ); ?>" text-anchor="end"><?php echo esc_html( sojib68_compact_taka( $scale_max * $g / 2 ) ); ?></text>
			<?php endfor; ?>
			<line class="chart-baseline" x1="<?php echo (int) $pad_l; ?>" y1="<?php echo esc_attr( $zero_y ); ?>" x2="<?php echo (int) ( $w - $pad_r ); ?>" y2="<?php echo esc_attr( $zero_y ); ?>"></line>

			<?php foreach ( $periods as $i => $p ) :
				$slot_x = $pad_l + $i * $slot;
				$bx     = $slot_x + $slot / 2 - $bar_w / 2;
				$bar_h  = $scale_max > 0 ? ( abs( $p['profit'] ) / $scale_max ) * $half_h : 0;
				$neg    = $p['profit'] < 0;
				$by     = $neg ? $zero_y : $zero_y - $bar_h;
				?>
				<g class="chart-group" tabindex="0"
					data-label="<?php echo esc_attr( $p['label'] ); ?>"
					data-profit="<?php echo esc_attr( round( abs( $p['profit'] ) / 100 ) ); ?>"
					data-sign="<?php echo $neg ? 'neg' : 'pos'; ?>">
					<rect class="chart-bar <?php echo $neg ? 'chart-bar--loss' : 'chart-bar--profit'; ?>" x="<?php echo esc_attr( $bx ); ?>" y="<?php echo esc_attr( $by ); ?>" width="<?php echo esc_attr( $bar_w ); ?>" height="<?php echo esc_attr( max( 0, $bar_h ) ); ?>" rx="3"></rect>
					<rect class="chart-hit" x="<?php echo esc_attr( $slot_x + 1 ); ?>" y="<?php echo (int) $pad_t; ?>" width="<?php echo esc_attr( $slot - 2 ); ?>" height="<?php echo (int) $plot_h; ?>" fill="transparent"></rect>
					<text class="chart-axislabel chart-axislabel--x" x="<?php echo esc_attr( $slot_x + $slot / 2 ); ?>" y="<?php echo (int) ( $h - 12 ); ?>" text-anchor="middle"><?php echo esc_html( $p['label'] ); ?></text>
				</g>
			<?php endforeach; ?>
		</svg>
	</div>
	<?php
}

function sojib68_office_render_report_table( $periods ) {
	?>
	<details class="chart-table-wrap">
		<summary><?php esc_html_e( 'টেবিল আকারে দেখুন', 'sojib68' ); ?></summary>
		<div class="chart-table-scroll">
			<table class="deed-table">
				<thead>
					<tr>
						<th><?php esc_html_e( 'সময়কাল', 'sojib68' ); ?></th>
						<th class="num"><?php esc_html_e( 'দলিল', 'sojib68' ); ?></th>
						<th class="num"><?php esc_html_e( 'দলিলের মূল্য', 'sojib68' ); ?></th>
						<th class="num"><?php esc_html_e( 'আদায়', 'sojib68' ); ?></th>
						<th class="num"><?php esc_html_e( 'খরচ', 'sojib68' ); ?></th>
						<th class="num"><?php esc_html_e( 'প্রফিট', 'sojib68' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $periods as $p ) : ?>
						<tr>
							<td><?php echo esc_html( $p['label'] ); ?></td>
							<td class="num"><?php echo esc_html( number_format( $p['deeds_count'] ) ); ?></td>
							<td class="num"><?php echo sojib68_money_html( $p['deed_value'] ); // phpcs:ignore ?></td>
							<td class="num"><?php echo sojib68_money_html( $p['collected'] ); // phpcs:ignore ?></td>
							<td class="num"><?php echo sojib68_money_html( $p['expenses'] ); // phpcs:ignore ?></td>
							<td class="num"><?php echo sojib68_money_html( $p['profit'] ); // phpcs:ignore ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	</details>
	<?php
}

function sojib68_office_render_report_section() {
	$view = isset( $_GET['report_view'] ) && 'yearly' === $_GET['report_view'] ? 'yearly' : 'monthly';
	$year = isset( $_GET['report_year'] ) ? (int) $_GET['report_year'] : (int) current_time( 'Y' );

	$monthly = sojib68_office_monthly_stats( $year );
	$yearly  = sojib68_office_yearly_stats( 6 );
	$base    = home_url( '/' . SOJIB68_OFFICE_BASE . '/' );
	?>
	<section class="report-section" data-report-view="<?php echo esc_attr( $view ); ?>">
		<div class="report-head">
			<h2><?php esc_html_e( 'রিপোর্ট', 'sojib68' ); ?></h2>
			<div class="report-tabs">
				<button type="button" class="report-tab <?php echo 'monthly' === $view ? 'is-active' : ''; ?>" data-report-tab="monthly"><?php esc_html_e( 'মাসিক', 'sojib68' ); ?></button>
				<button type="button" class="report-tab <?php echo 'yearly' === $view ? 'is-active' : ''; ?>" data-report-tab="yearly"><?php esc_html_e( 'বার্ষিক', 'sojib68' ); ?></button>
			</div>
		</div>

		<div class="report-view" data-report-panel="monthly" <?php echo 'monthly' !== $view ? 'hidden' : ''; ?>>
			<div class="report-year-nav">
				<a href="<?php echo esc_url( add_query_arg( array( 'report_view' => 'monthly', 'report_year' => $year - 1 ), $base . 'dashboard' ) ); ?>" class="report-year-nav__btn" aria-label="<?php esc_attr_e( 'আগের বছর', 'sojib68' ); ?>">‹</a>
				<strong><?php echo esc_html( sojib68_bn_digits( $year ) ); ?></strong>
				<?php if ( $year < (int) current_time( 'Y' ) ) : ?>
					<a href="<?php echo esc_url( add_query_arg( array( 'report_view' => 'monthly', 'report_year' => $year + 1 ), $base . 'dashboard' ) ); ?>" class="report-year-nav__btn" aria-label="<?php esc_attr_e( 'পরের বছর', 'sojib68' ); ?>">›</a>
				<?php else : ?>
					<span class="report-year-nav__btn is-disabled" aria-hidden="true">›</span>
				<?php endif; ?>
			</div>
			<div class="report-charts">
				<?php
				sojib68_office_render_collect_expense_chart( $monthly['periods'], 'monthly-ce' );
				sojib68_office_render_profit_chart( $monthly['periods'], 'monthly-profit' );
				?>
			</div>
			<?php sojib68_office_render_report_table( $monthly['periods'] ); ?>
		</div>

		<div class="report-view" data-report-panel="yearly" <?php echo 'yearly' !== $view ? 'hidden' : ''; ?>>
			<div class="report-charts">
				<?php
				sojib68_office_render_collect_expense_chart( $yearly['periods'], 'yearly-ce' );
				sojib68_office_render_profit_chart( $yearly['periods'], 'yearly-profit' );
				?>
			</div>
			<?php sojib68_office_render_report_table( $yearly['periods'] ); ?>
		</div>
	</section>

	<div class="chart-tooltip" id="chart-tooltip" hidden></div>
	<script src="<?php echo esc_url( get_theme_file_uri( 'assets/js/office-dashboard.js' ) ); ?>?v=<?php echo (int) @filemtime( get_theme_file_path( 'assets/js/office-dashboard.js' ) ); // phpcs:ignore ?>"></script>
	<?php
}

/* ---------------------------------------------------------------------
 * Filterable period totals (এই মাসে / এই বছরে, or any month/year)
 * ------------------------------------------------------------------ */

function sojib68_office_hidden_get_fields( array $skip ) {
	foreach ( $_GET as $key => $val ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( in_array( $key, $skip, true ) || ! is_scalar( $val ) ) {
			continue;
		}
		echo '<input type="hidden" name="' . esc_attr( $key ) . '" value="' . esc_attr( sanitize_text_field( wp_unslash( $val ) ) ) . '">';
	}
}

function sojib68_office_render_period_card( $tone, $icon, $title, $stats ) {
	?>
	<div class="period-card tone-<?php echo esc_attr( $tone ); ?>">
		<div class="period-card__head">
			<span class="period-card__icon"><?php echo sojib68_office_icon( $icon, 18 ); // phpcs:ignore ?></span>
			<h3><?php echo esc_html( $title ); ?></h3>
		</div>
		<div class="period-card__grid">
			<div><span><?php esc_html_e( 'দলিল', 'sojib68' ); ?></span><strong><?php echo esc_html( number_format( $stats['deeds_count'] ) ); ?></strong></div>
			<div><span><?php esc_html_e( 'মূল্য', 'sojib68' ); ?></span><strong><?php echo sojib68_money_html( $stats['deed_value'] ); // phpcs:ignore ?></strong></div>
			<div><span><?php esc_html_e( 'আয়', 'sojib68' ); ?></span><strong><?php echo sojib68_money_html( $stats['collected'] ); // phpcs:ignore ?></strong></div>
			<div><span><?php esc_html_e( 'ব্যয়', 'sojib68' ); ?></span><strong><?php echo sojib68_money_html( $stats['expenses'] ); // phpcs:ignore ?></strong></div>
		</div>
	</div>
	<?php
}

function sojib68_office_render_period_stats() {
	$now_year  = (int) current_time( 'Y' );
	$now_month = (int) current_time( 'n' );

	$m_month = isset( $_GET['period_month'] ) ? max( 1, min( 12, (int) $_GET['period_month'] ) ) : $now_month;
	$m_year  = isset( $_GET['period_month_year'] ) ? (int) $_GET['period_month_year'] : $now_year;
	$y_year  = isset( $_GET['period_year'] ) ? (int) $_GET['period_year'] : $now_year;

	$monthly = sojib68_office_period_stats( $m_year, $m_month );
	$yearly  = sojib68_office_period_stats( $y_year, null );

	$base        = home_url( '/' . SOJIB68_OFFICE_BASE . '/dashboard' );
	$year_choices = range( $now_year, $now_year - 9 );
	$is_current_month = ( $m_month === $now_month && $m_year === $now_year );
	$is_current_year  = ( $y_year === $now_year );
	?>
	<section class="period-section">
		<div class="period-head">
			<h2><?php esc_html_e( 'নির্দিষ্ট সময়ের হিসাব', 'sojib68' ); ?></h2>
		</div>
		<div class="period-filters">
			<form method="get" action="<?php echo esc_url( $base ); ?>" class="period-filter">
				<span class="period-filter__label"><?php echo sojib68_office_icon( 'filter', 15 ); // phpcs:ignore ?> <?php esc_html_e( 'মাসিক হিসাব', 'sojib68' ); ?></span>
				<?php sojib68_office_hidden_get_fields( array( 'period_month', 'period_month_year' ) ); ?>
				<select name="period_month">
					<?php for ( $mn = 1; $mn <= 12; $mn++ ) : ?>
						<option value="<?php echo (int) $mn; ?>" <?php selected( $m_month, $mn ); ?>><?php echo esc_html( sojib68_bn_month_label( $mn ) ); ?></option>
					<?php endfor; ?>
				</select>
				<select name="period_month_year">
					<?php foreach ( $year_choices as $yr ) : ?>
						<option value="<?php echo (int) $yr; ?>" <?php selected( $m_year, $yr ); ?>><?php echo esc_html( sojib68_bn_digits( $yr ) ); ?></option>
					<?php endforeach; ?>
				</select>
				<button type="submit" class="btn btn-outline btn-sm"><?php esc_html_e( 'দেখান', 'sojib68' ); ?></button>
			</form>

			<form method="get" action="<?php echo esc_url( $base ); ?>" class="period-filter">
				<span class="period-filter__label"><?php echo sojib68_office_icon( 'filter', 15 ); // phpcs:ignore ?> <?php esc_html_e( 'বার্ষিক হিসাব', 'sojib68' ); ?></span>
				<?php sojib68_office_hidden_get_fields( array( 'period_year' ) ); ?>
				<select name="period_year">
					<?php foreach ( $year_choices as $yr ) : ?>
						<option value="<?php echo (int) $yr; ?>" <?php selected( $y_year, $yr ); ?>><?php echo esc_html( sojib68_bn_digits( $yr ) ); ?></option>
					<?php endforeach; ?>
				</select>
				<button type="submit" class="btn btn-outline btn-sm"><?php esc_html_e( 'দেখান', 'sojib68' ); ?></button>
			</form>
		</div>

		<div class="period-cards">
			<?php
			sojib68_office_render_period_card(
				'blue',
				'clock',
				$is_current_month ? __( 'এই মাসে', 'sojib68' ) : sojib68_bn_month_label( $m_month ) . ' ' . sojib68_bn_digits( $m_year ),
				$monthly
			);
			sojib68_office_render_period_card(
				'green',
				'trending',
				$is_current_year ? __( 'এই বছরে', 'sojib68' ) : sojib68_bn_digits( $y_year ),
				$yearly
			);
			?>
		</div>
	</section>
	<?php
}
