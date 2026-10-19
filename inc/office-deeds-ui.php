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
		'registration_fee'   => __( 'রেজিস্ট্রেশন ফি', 'sojib68' ),
		'other_govt_fee_tax' => __( 'অন্যান্য সরকারি ফি ও কর', 'sojib68' ),
		'other'              => __( 'অন্যান্য', 'sojib68' ),
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
	$stats = sojib68_office_dashboard_stats();

	$tiles = array(
		array( 'tone' => 'green',  'icon' => 'file',     'label' => __( 'মোট দলিল এন্ট্রি', 'sojib68' ),                  'value' => esc_html( number_format( $stats['total_deeds'] ) ), 'class' => '' ),
		array( 'tone' => 'indigo', 'icon' => 'tag',      'label' => __( 'মোট দলিলের মূল্য', 'sojib68' ),                  'value' => sojib68_money_html( $stats['total_deed_value'] ), 'class' => '' ),
		array( 'tone' => 'teal',   'icon' => 'wallet',   'label' => __( 'গ্রাহক থেকে মোট টাকা আদায়', 'sojib68' ),          'value' => sojib68_money_html( $stats['total_collected'] ), 'class' => '' ),
		array( 'tone' => 'red',    'icon' => 'receipt',  'label' => __( 'অফিস, ফি ও অন্যান্য মোট ব্যয়', 'sojib68' ),      'value' => sojib68_money_html( $stats['total_expenses'] ), 'class' => '' ),
		array( 'tone' => 'green',  'icon' => 'trending', 'label' => __( 'মোট প্রফিট', 'sojib68' ),                          'value' => sojib68_money_html( $stats['total_profit'] ), 'class' => 'is-hero', 'sub' => __( 'আদায়কৃত পারিশ্রমিক − নিজে পরিশোধ করা খরচ', 'sojib68' ) ),
		array( 'tone' => 'amber',  'icon' => 'clock',    'label' => __( 'মোট বকেয়া', 'sojib68' ),                         'value' => sojib68_money_html( $stats['total_due'] ), 'class' => $stats['total_due'] > 0 ? 'is-due' : '' ),
	);
	?>
	<div class="office-stats-grid">
		<?php foreach ( $tiles as $tile ) : ?>
			<div class="office-stat-tile tone-<?php echo esc_attr( $tile['tone'] ); ?> <?php echo esc_attr( $tile['class'] ); ?>">
				<span class="office-stat-tile__icon"><?php echo sojib68_office_icon( $tile['icon'], 22 ); // phpcs:ignore ?></span>
				<span class="office-stat-tile__label"><?php echo esc_html( $tile['label'] ); ?></span>
				<span class="office-stat-tile__value"><?php echo $tile['value']; // phpcs:ignore ?></span>
				<?php if ( ! empty( $tile['sub'] ) ) : ?>
					<span class="office-stat-tile__sub"><?php echo esc_html( $tile['sub'] ); ?></span>
				<?php endif; ?>
			</div>
		<?php endforeach; ?>
	</div>

	<p class="deed-note">
		<?php echo sojib68_office_icon( 'info', 18 ); // phpcs:ignore ?>
		<span><?php esc_html_e( 'প্রফিট হিসাবে গ্রাহকের জমা দেওয়া টাকা বা গ্রাহক নিজে পরিশোধ করা খরচ ধরা হয় না — শুধু আদায়কৃত পারিশ্রমিক থেকে নিজের পরিশোধিত খরচ বাদ দিয়ে দেখানো হয়েছে।', 'sojib68' ); ?></span>
	</p>
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
	<form id="deed-form" class="deed-form" data-base-url="<?php echo esc_attr( home_url( '/' . SOJIB68_OFFICE_BASE . '/' ) ); ?>" novalidate>
		<?php wp_nonce_field( 'sojib68_deed_form', 'nonce' ); ?>
		<input type="hidden" name="deed_id" value="<?php echo esc_attr( $deed_id ); ?>">
		<input type="hidden" name="submission_token" value="<?php echo esc_attr( $submission_token ); ?>">

		<div class="deed-layout">
			<div class="deed-main">

				<div class="deed-notice-area" aria-live="polite">
					<?php if ( $deed ) : ?>
						<div class="office-notice office-notice--info">
							<?php echo esc_html( sprintf( /* translators: %d: deed record id */ __( 'আপনি রেকর্ড #%d সম্পাদনা করছেন।', 'sojib68' ), $deed->id ) ); ?>
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

				<?php sojib68_deed_card_open( 'green', 'wallet', __( 'গ্রাহকের হিসাব', 'sojib68' ) ); ?>
					<div class="deed-grid">
						<div class="deed-field col-6">
							<label for="f-total-due"><?php esc_html_e( 'গ্রাহকের কাছ থেকে নির্ধারিত মোট প্রাপ্য (টাকা)', 'sojib68' ); ?></label>
							<input type="text" id="f-total-due" inputmode="decimal" name="client_total_due" value="<?php echo esc_attr( $deed ? sojib68_poysha_to_display( $deed->client_total_due_poysha ) : '' ); ?>">
						</div>
						<div class="deed-field col-6 deed-field--end">
							<label class="deed-check">
								<input type="checkbox" name="dues_verified" value="1" <?php checked( $deed ? (bool) $deed->dues_verified : false ); ?>>
								<span><?php esc_html_e( 'বকেয়া যাচাইকৃত', 'sojib68' ); ?></span>
							</label>
						</div>
					</div>

					<h3 class="deed-subhead"><?php esc_html_e( 'টাকা গ্রহণের এন্ট্রি', 'sojib68' ); ?></h3>
					<div class="deed-repeat" data-repeat="receipts" data-next="<?php echo count( $receipts ); ?>">
						<?php foreach ( $receipts as $i => $r ) : sojib68_render_receipt_row( $i, $r ); endforeach; ?>
					</div>
					<p class="deed-empty" data-empty-for="receipts" <?php echo $receipts ? 'hidden' : ''; ?>><?php esc_html_e( 'এখনো কোনো টাকা গ্রহণের এন্ট্রি নেই।', 'sojib68' ); ?></p>
					<button type="button" class="deed-add" data-add-row="receipts"><?php echo sojib68_office_icon( 'plus', 18 ); // phpcs:ignore ?> <?php esc_html_e( 'টাকা গ্রহণ যোগ করুন', 'sojib68' ); ?></button>
				<?php sojib68_deed_card_close(); ?>

				<?php sojib68_deed_card_open( 'red', 'receipt', __( 'ব্যয়ের হিসাব', 'sojib68' ) ); ?>
					<div class="deed-repeat" data-repeat="expenses" data-next="<?php echo count( $expenses ); ?>">
						<?php foreach ( $expenses as $i => $e ) : sojib68_render_expense_row( $i, $e ); endforeach; ?>
					</div>
					<p class="deed-empty" data-empty-for="expenses" <?php echo $expenses ? 'hidden' : ''; ?>><?php esc_html_e( 'এখনো কোনো খরচের এন্ট্রি নেই।', 'sojib68' ); ?></p>
					<button type="button" class="deed-add" data-add-row="expenses"><?php echo sojib68_office_icon( 'plus', 18 ); // phpcs:ignore ?> <?php esc_html_e( 'খরচ যোগ করুন', 'sojib68' ); ?></button>
				<?php sojib68_deed_card_close(); ?>

				<?php sojib68_deed_card_open( 'purple', 'briefcase', __( 'আমার পারিশ্রমিক', 'sojib68' ) ); ?>
					<div class="deed-grid">
						<div class="deed-field col-6">
							<label for="f-fee-agreed"><?php esc_html_e( 'নির্ধারিত পারিশ্রমিক (টাকা)', 'sojib68' ); ?></label>
							<input type="text" id="f-fee-agreed" inputmode="decimal" name="fee_agreed" value="<?php echo esc_attr( $deed ? sojib68_poysha_to_display( $deed->fee_agreed_poysha ) : '' ); ?>">
						</div>
						<div class="deed-field col-6">
							<label for="f-fee-collected"><?php esc_html_e( 'আদায়কৃত পারিশ্রমিক (টাকা)', 'sojib68' ); ?></label>
							<input type="text" id="f-fee-collected" inputmode="decimal" name="fee_collected" value="<?php echo esc_attr( $deed ? sojib68_poysha_to_display( $deed->fee_collected_poysha ) : '' ); ?>">
						</div>
					</div>
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
						<div class="deed-summary-row"><span><?php esc_html_e( 'গ্রাহকের মোট প্রাপ্য', 'sojib68' ); ?></span><strong data-sum="total_due">—</strong></div>
						<div class="deed-summary-row"><span><?php esc_html_e( 'মোট জমা', 'sojib68' ); ?></span><strong data-sum="received">0</strong></div>
						<div class="deed-summary-row deed-summary-row--big">
							<span data-sum="due_label"><?php esc_html_e( 'বকেয়া', 'sojib68' ); ?></span>
							<strong data-sum="due">—</strong>
						</div>
						<div class="deed-summary-status"><span class="deed-chip deed-chip--unknown" data-sum="status"><?php esc_html_e( 'প্রাপ্য অজানা', 'sojib68' ); ?></span></div>
						<div class="deed-summary-row"><span><?php esc_html_e( 'মোট খরচ', 'sojib68' ); ?></span><strong data-sum="expenses">0</strong></div>
						<div class="deed-summary-row"><span><?php esc_html_e( '— এর মধ্যে নিজে পরিশোধ', 'sojib68' ); ?></span><strong data-sum="my_expenses">0</strong></div>
					</div>
					<p class="deed-summary-foot"><?php esc_html_e( 'দলিলের মূল্য ও আমার পারিশ্রমিক এই হিসাবে যোগ হয় না।', 'sojib68' ); ?></p>
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
				<a href="<?php echo esc_url( home_url( '/' . SOJIB68_OFFICE_BASE . '/all-deeds' ) ); ?>" class="btn btn-outline"><?php esc_html_e( 'সকল দলিল দেখুন', 'sojib68' ); ?></a>
				<a href="<?php echo esc_url( home_url( '/' . SOJIB68_OFFICE_BASE . '/entry-new' ) ); ?>" class="btn btn-primary"><?php esc_html_e( 'আরেকটি এন্ট্রি করুন', 'sojib68' ); ?></a>
			</div>
		</div>
	</form>

	<template id="tpl-donors"><?php sojib68_render_person_row( 'donors', '__INDEX__', null ); ?></template>
	<template id="tpl-grantees"><?php sojib68_render_person_row( 'grantees', '__INDEX__', null ); ?></template>
	<template id="tpl-lands"><?php sojib68_render_land_row( '__INDEX__', null ); ?></template>
	<template id="tpl-receipts"><?php sojib68_render_receipt_row( '__INDEX__', null ); ?></template>
	<template id="tpl-expenses"><?php sojib68_render_expense_row( '__INDEX__', null ); ?></template>

	<script>window.sojib68DeedAjax = { ajaxUrl: <?php echo wp_json_encode( admin_url( 'admin-ajax.php' ) ); ?> };</script>
	<script src="<?php echo esc_url( get_theme_file_uri( 'assets/js/office-deeds.js' ) ); ?>"></script>
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

function sojib68_render_receipt_row( $i, $row ) {
	$prefix = 'receipts[' . esc_attr( $i ) . ']';
	?>
	<div class="deed-row">
		<?php sojib68_row_head( __( 'জমা', 'sojib68' ) ); ?>
		<div class="deed-grid">
			<div class="deed-field col-3 m-6">
				<label><?php esc_html_e( 'টাকা গ্রহণের তারিখ', 'sojib68' ); ?></label>
				<input type="date" name="<?php echo $prefix; // phpcs:ignore ?>[received_date]" value="<?php echo esc_attr( $row->received_date ?? '' ); ?>">
			</div>
			<div class="deed-field col-3 m-6">
				<label><?php esc_html_e( 'জমার পরিমাণ', 'sojib68' ); ?></label>
				<input type="text" inputmode="decimal" data-money="receipt" name="<?php echo $prefix; // phpcs:ignore ?>[amount]" value="<?php echo esc_attr( isset( $row->amount_poysha ) ? sojib68_poysha_to_display( $row->amount_poysha ) : '' ); ?>">
			</div>
			<div class="deed-field col-3">
				<label><?php esc_html_e( 'মাধ্যম', 'sojib68' ); ?></label>
				<select name="<?php echo $prefix; // phpcs:ignore ?>[method]">
					<option value=""><?php esc_html_e( '— নির্বাচন করুন —', 'sojib68' ); ?></option>
					<?php foreach ( sojib68_payment_method_options() as $val => $label ) : ?>
						<option value="<?php echo esc_attr( $val ); ?>" <?php selected( $row->method ?? '', $val ); ?>><?php echo esc_html( $label ); ?></option>
					<?php endforeach; ?>
				</select>
			</div>
			<div class="deed-field col-3">
				<label><?php esc_html_e( 'মন্তব্য', 'sojib68' ); ?></label>
				<input type="text" name="<?php echo $prefix; // phpcs:ignore ?>[note]" value="<?php echo esc_attr( $row->note ?? '' ); ?>">
			</div>
		</div>
	</div>
	<?php
}

function sojib68_render_expense_row( $i, $row ) {
	$prefix = 'expenses[' . esc_attr( $i ) . ']';
	?>
	<div class="deed-row">
		<?php sojib68_row_head( __( 'খরচ', 'sojib68' ) ); ?>
		<div class="deed-grid">
			<div class="deed-field col-4">
				<label><?php esc_html_e( 'খরচের খাত', 'sojib68' ); ?></label>
				<select name="<?php echo $prefix; // phpcs:ignore ?>[category]">
					<option value=""><?php esc_html_e( '— নির্বাচন করুন —', 'sojib68' ); ?></option>
					<?php foreach ( sojib68_expense_category_options() as $val => $label ) : ?>
						<option value="<?php echo esc_attr( $val ); ?>" <?php selected( $row->category ?? '', $val ); ?>><?php echo esc_html( $label ); ?></option>
					<?php endforeach; ?>
				</select>
			</div>
			<div class="deed-field col-5">
				<label><?php esc_html_e( 'খরচের বিবরণ', 'sojib68' ); ?></label>
				<input type="text" name="<?php echo $prefix; // phpcs:ignore ?>[description]" value="<?php echo esc_attr( $row->description ?? '' ); ?>">
			</div>
			<div class="deed-field col-3">
				<label><?php esc_html_e( 'টাকার পরিমাণ', 'sojib68' ); ?></label>
				<input type="text" inputmode="decimal" data-money="expense" name="<?php echo $prefix; // phpcs:ignore ?>[amount]" value="<?php echo esc_attr( isset( $row->amount_poysha ) ? sojib68_poysha_to_display( $row->amount_poysha ) : '' ); ?>">
			</div>
			<div class="deed-field col-4">
				<label><?php esc_html_e( 'পরিশোধের তারিখ', 'sojib68' ); ?></label>
				<input type="date" name="<?php echo $prefix; // phpcs:ignore ?>[paid_date]" value="<?php echo esc_attr( $row->paid_date ?? '' ); ?>">
			</div>
			<div class="deed-field col-4 m-6">
				<label><?php esc_html_e( 'পরিশোধের অবস্থা', 'sojib68' ); ?></label>
				<select name="<?php echo $prefix; // phpcs:ignore ?>[paid_status]" data-expense="status">
					<option value=""><?php esc_html_e( '— নির্বাচন করুন —', 'sojib68' ); ?></option>
					<?php foreach ( sojib68_expense_status_options() as $val => $label ) : ?>
						<option value="<?php echo esc_attr( $val ); ?>" <?php selected( $row->paid_status ?? '', $val ); ?>><?php echo esc_html( $label ); ?></option>
					<?php endforeach; ?>
				</select>
			</div>
			<div class="deed-field col-4 m-6">
				<label><?php esc_html_e( 'কে পরিশোধ করেছেন', 'sojib68' ); ?></label>
				<select name="<?php echo $prefix; // phpcs:ignore ?>[paid_by]" data-expense="paid_by">
					<option value=""><?php esc_html_e( '— নির্বাচন করুন —', 'sojib68' ); ?></option>
					<?php foreach ( sojib68_expense_paid_by_options() as $val => $label ) : ?>
						<option value="<?php echo esc_attr( $val ); ?>" <?php selected( $row->paid_by ?? '', $val ); ?>><?php echo esc_html( $label ); ?></option>
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
	$keys    = array( 'name', 'mobile', 'address', 'deed_no', 'deed_type', 'office', 'reg_year', 'date_from', 'date_to', 'mouza', 'khatian_no', 'land_unit', 'value_min', 'value_max', 'has_due' );
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

	$advanced_keys   = array( 'address', 'deed_type', 'office', 'date_from', 'date_to', 'mouza', 'khatian_no', 'land_unit', 'value_min', 'value_max', 'has_due' );
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
						<th><?php esc_html_e( 'দলিল', 'sojib68' ); ?></th>
						<th><?php esc_html_e( 'দাতা → গ্রহীতা', 'sojib68' ); ?></th>
						<th><?php esc_html_e( 'ধরন ও অফিস', 'sojib68' ); ?></th>
						<th><?php esc_html_e( 'জমি', 'sojib68' ); ?></th>
						<th class="num"><?php esc_html_e( 'মূল্য', 'sojib68' ); ?></th>
						<th><?php esc_html_e( 'বকেয়া', 'sojib68' ); ?></th>
						<th></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $result['rows'] as $row ) : ?>
						<tr>
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
							<td class="act">
								<a href="<?php echo esc_url( add_query_arg( 'id', $row->id, home_url( '/' . SOJIB68_OFFICE_BASE . '/entry-new' ) ) ); ?>" class="btn btn-outline btn-sm"><?php esc_html_e( 'দেখুন / এডিট', 'sojib68' ); ?></a>
							</td>
						</tr>
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
	<?php
}
