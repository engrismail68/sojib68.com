<?php
/**
 * Renders the two deed-system screens: the one-page entry/edit form,
 * and the "সকল দলিল" search + listing page.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function sojib68_payment_method_options() {
	return array(
		'cash'            => __( 'নগদ', 'sojib68' ),
		'bank'            => __( 'ব্যাংক', 'sojib68' ),
		'mobile_banking'  => __( 'মোবাইল ব্যাংকিং', 'sojib68' ),
		'other'           => __( 'অন্যান্য', 'sojib68' ),
	);
}

function sojib68_expense_category_options() {
	return array(
		'registration_fee'      => __( 'রেজিস্ট্রেশন ফি', 'sojib68' ),
		'other_govt_fee_tax'    => __( 'অন্যান্য সরকারি ফি ও কর', 'sojib68' ),
		'other'                 => __( 'অন্যান্য', 'sojib68' ),
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
 * Dashboard home — summary stat tiles
 * ------------------------------------------------------------------ */

function sojib68_office_render_dashboard_home() {
	$stats = sojib68_office_dashboard_stats();
	?>
	<div class="office-stats-grid">
		<div class="office-stat-tile">
			<span class="office-stat-tile__label"><?php esc_html_e( 'মোট দলিল এন্ট্রি', 'sojib68' ); ?></span>
			<span class="office-stat-tile__value"><?php echo esc_html( number_format( $stats['total_deeds'] ) ); ?></span>
		</div>
		<div class="office-stat-tile">
			<span class="office-stat-tile__label"><?php esc_html_e( 'মোট দলিলের মূল্য', 'sojib68' ); ?></span>
			<span class="office-stat-tile__value"><?php echo esc_html( sojib68_poysha_to_display( $stats['total_deed_value'] ) ?: '0' ); ?></span>
		</div>
		<div class="office-stat-tile">
			<span class="office-stat-tile__label"><?php esc_html_e( 'গ্রাহক থেকে মোট টাকা আদায়', 'sojib68' ); ?></span>
			<span class="office-stat-tile__value"><?php echo esc_html( sojib68_poysha_to_display( $stats['total_collected'] ) ?: '0' ); ?></span>
		</div>
		<div class="office-stat-tile">
			<span class="office-stat-tile__label"><?php esc_html_e( 'অফিস, ফি ও অন্যান্য মোট ব্যয়', 'sojib68' ); ?></span>
			<span class="office-stat-tile__value"><?php echo esc_html( sojib68_poysha_to_display( $stats['total_expenses'] ) ?: '0' ); ?></span>
		</div>
		<div class="office-stat-tile office-stat-tile--accent">
			<span class="office-stat-tile__label"><?php esc_html_e( 'মোট প্রফিট (আদায়কৃত পারিশ্রমিক − নিজ খরচ)', 'sojib68' ); ?></span>
			<span class="office-stat-tile__value"><?php echo esc_html( sojib68_poysha_to_display( $stats['total_profit'] ) ?: '0' ); ?></span>
		</div>
		<div class="office-stat-tile <?php echo $stats['total_due'] > 0 ? 'office-stat-tile--due' : ''; ?>">
			<span class="office-stat-tile__label"><?php esc_html_e( 'মোট বকেয়া', 'sojib68' ); ?></span>
			<span class="office-stat-tile__value"><?php echo esc_html( sojib68_poysha_to_display( $stats['total_due'] ) ?: '0' ); ?></span>
		</div>
	</div>

	<p class="deed-hint">
		<?php esc_html_e( 'প্রফিট হিসাবে গ্রাহকের জমা দেওয়া টাকা বা গ্রাহক নিজে পরিশোধ করা খরচ ধরা হয় না — শুধু আদায়কৃত পারিশ্রমিক থেকে নিজের পরিশোধিত খরচ বাদ দিয়ে দেখানো হয়েছে।', 'sojib68' ); ?>
	</p>

	<div class="office-dashboard-actions">
		<a href="<?php echo esc_url( home_url( '/' . SOJIB68_OFFICE_BASE . '/entry-new' ) ); ?>" class="btn btn-primary"><?php esc_html_e( '+ নতুন দলিল এন্ট্রি', 'sojib68' ); ?></a>
		<a href="<?php echo esc_url( home_url( '/' . SOJIB68_OFFICE_BASE . '/all-deeds' ) ); ?>" class="btn btn-outline"><?php esc_html_e( 'সকল দলিল দেখুন', 'sojib68' ); ?></a>
	</div>
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

	$default_office = 'রামগঞ্জ সাব-রেজিস্ট্রেশন অফিস';
	$selected_office = $deed ? (string) $deed->sub_registry_office : $default_office;

	$submission_token = wp_generate_password( 32, false );
	?>
	<form id="deed-form" class="deed-form" data-base-url="<?php echo esc_attr( home_url( '/' . SOJIB68_OFFICE_BASE . '/' ) ); ?>">
		<?php wp_nonce_field( 'sojib68_deed_form', 'nonce' ); ?>
		<input type="hidden" name="deed_id" value="<?php echo esc_attr( $deed_id ); ?>">
		<input type="hidden" name="submission_token" value="<?php echo esc_attr( $submission_token ); ?>">

		<div class="deed-notice-area" aria-live="polite"></div>

		<fieldset class="deed-section">
			<legend><?php esc_html_e( 'দলিলের পরিচয়', 'sojib68' ); ?></legend>
			<div class="deed-grid-2">
				<div class="deed-field">
					<label for="f-office"><?php esc_html_e( 'সাব-রেজিস্ট্রেশন অফিস', 'sojib68' ); ?></label>
					<select id="f-office" name="sub_registry_office">
						<option value=""><?php esc_html_e( '— ফাঁকা —', 'sojib68' ); ?></option>
						<?php foreach ( $offices as $office ) : ?>
							<option value="<?php echo esc_attr( $office->name ); ?>" <?php selected( $selected_office, $office->name ); ?>>
								<?php echo esc_html( $office->name ); ?>
							</option>
						<?php endforeach; ?>
					</select>
					<button type="button" class="deed-add-link" data-add="office"><?php esc_html_e( '+ নতুন অফিস যোগ করুন', 'sojib68' ); ?></button>
				</div>

				<div class="deed-field">
					<label for="f-deed-type"><?php esc_html_e( 'দলিলের ধরন', 'sojib68' ); ?></label>
					<select id="f-deed-type" name="deed_type">
						<option value=""><?php esc_html_e( '— ফাঁকা —', 'sojib68' ); ?></option>
						<?php foreach ( $types as $type ) : ?>
							<option value="<?php echo esc_attr( $type->name ); ?>" <?php selected( $deed ? $deed->deed_type : '', $type->name ); ?>>
								<?php echo esc_html( $type->name ); ?>
							</option>
						<?php endforeach; ?>
					</select>
					<button type="button" class="deed-add-link" data-add="deed_type"><?php esc_html_e( '+ নতুন ধরন যোগ করুন', 'sojib68' ); ?></button>
				</div>

				<div class="deed-field">
					<label for="f-reg-date"><?php esc_html_e( 'রেজিস্ট্রেশনের তারিখ', 'sojib68' ); ?></label>
					<input type="date" id="f-reg-date" name="reg_date" value="<?php echo esc_attr( $deed ? $deed->reg_date : '' ); ?>">
				</div>

				<div class="deed-field">
					<label for="f-reg-year"><?php esc_html_e( 'সাল (পূর্ণ তারিখ জানা না থাকলে)', 'sojib68' ); ?></label>
					<input type="text" id="f-reg-year" inputmode="numeric" name="reg_year" value="<?php echo esc_attr( $deed ? $deed->reg_year : '' ); ?>" placeholder="<?php esc_attr_e( 'যেমন ২০২৪', 'sojib68' ); ?>">
				</div>

				<div class="deed-field">
					<label for="f-deed-no"><?php esc_html_e( 'দলিল নং', 'sojib68' ); ?></label>
					<input type="text" id="f-deed-no" name="deed_no" value="<?php echo esc_attr( $deed ? $deed->deed_no : '' ); ?>">
				</div>
			</div>
			<p class="deed-hint" id="date-year-warning" hidden><?php esc_html_e( 'তারিখ ও সাল মিলছে না — তবু সংরক্ষণ করা যাবে।', 'sojib68' ); ?></p>
		</fieldset>

		<fieldset class="deed-section">
			<legend><?php esc_html_e( 'দাতার তথ্য', 'sojib68' ); ?></legend>
			<div class="deed-repeat" data-repeat="donors" data-template="donor">
				<?php
				$donors = $deed && $deed->donors ? $deed->donors : array( (object) array( 'name' => '', 'address' => '', 'mobile' => '' ) );
				foreach ( $donors as $i => $donor ) :
					sojib68_render_person_row( 'donors', $i, $donor );
				endforeach;
				?>
			</div>
			<button type="button" class="deed-add-row" data-add-row="donors"><?php esc_html_e( '+ দাতা যোগ করুন', 'sojib68' ); ?></button>
		</fieldset>

		<fieldset class="deed-section">
			<legend><?php esc_html_e( 'গ্রহীতার তথ্য', 'sojib68' ); ?></legend>
			<div class="deed-repeat" data-repeat="grantees" data-template="grantee">
				<?php
				$grantees = $deed && $deed->grantees ? $deed->grantees : array( (object) array( 'name' => '', 'address' => '', 'mobile' => '' ) );
				foreach ( $grantees as $i => $grantee ) :
					sojib68_render_person_row( 'grantees', $i, $grantee );
				endforeach;
				?>
			</div>
			<button type="button" class="deed-add-row" data-add-row="grantees"><?php esc_html_e( '+ গ্রহীতা যোগ করুন', 'sojib68' ); ?></button>
		</fieldset>

		<fieldset class="deed-section">
			<legend><?php esc_html_e( 'জমির তথ্য', 'sojib68' ); ?></legend>
			<div class="deed-repeat" data-repeat="lands" data-template="land">
				<?php
				$lands = $deed && $deed->lands ? $deed->lands : array( (object) array( 'mouza' => '', 'khatian_no' => '', 'quantity' => '', 'unit' => '' ) );
				foreach ( $lands as $i => $land ) :
					sojib68_render_land_row( $i, $land );
				endforeach;
				?>
			</div>
			<button type="button" class="deed-add-row" data-add-row="lands"><?php esc_html_e( '+ জমির তথ্য যোগ করুন', 'sojib68' ); ?></button>
		</fieldset>

		<fieldset class="deed-section">
			<legend><?php esc_html_e( 'দলিলের মূল্য', 'sojib68' ); ?></legend>
			<p class="deed-hint"><?php esc_html_e( 'এটি একটি পৃথক পরিসংখ্যান — এখান থেকে আমার লাভ/আয় হিসাব করা হয় না।', 'sojib68' ); ?></p>
			<div class="deed-grid-2">
				<div class="deed-field">
					<label for="f-deed-value"><?php esc_html_e( 'দলিলে উল্লিখিত মোট মূল্য (টাকা)', 'sojib68' ); ?></label>
					<input type="text" id="f-deed-value" inputmode="decimal" name="deed_value" value="<?php echo esc_attr( $deed ? sojib68_poysha_to_display( $deed->deed_value_poysha ) : '' ); ?>">
				</div>
			</div>
		</fieldset>

		<fieldset class="deed-section">
			<legend><?php esc_html_e( 'গ্রাহকের হিসাব', 'sojib68' ); ?></legend>
			<div class="deed-grid-2">
				<div class="deed-field">
					<label for="f-total-due"><?php esc_html_e( 'গ্রাহকের কাছ থেকে নির্ধারিত মোট প্রাপ্য (টাকা)', 'sojib68' ); ?></label>
					<input type="text" id="f-total-due" inputmode="decimal" name="client_total_due" value="<?php echo esc_attr( $deed ? sojib68_poysha_to_display( $deed->client_total_due_poysha ) : '' ); ?>">
				</div>
				<div class="deed-field deed-field--checkbox">
					<label>
						<input type="checkbox" name="dues_verified" value="1" <?php checked( $deed ? (bool) $deed->dues_verified : false ); ?>>
						<?php esc_html_e( 'বকেয়া যাচাইকৃত', 'sojib68' ); ?>
					</label>
				</div>
			</div>

			<h4><?php esc_html_e( 'টাকা গ্রহণের এন্ট্রি', 'sojib68' ); ?></h4>
			<div class="deed-repeat" data-repeat="receipts" data-template="receipt">
				<?php
				$receipts = $deed && $deed->receipts ? $deed->receipts : array();
				foreach ( $receipts as $i => $r ) :
					sojib68_render_receipt_row( $i, $r );
				endforeach;
				?>
			</div>
			<button type="button" class="deed-add-row" data-add-row="receipts"><?php esc_html_e( '+ গ্রহণের এন্ট্রি যোগ করুন', 'sojib68' ); ?></button>

			<div class="deed-summary">
				<span><?php esc_html_e( 'মোট জমা ও বকেয়া সংরক্ষণের পর স্বয়ংক্রিয়ভাবে দেখানো হবে।', 'sojib68' ); ?></span>
				<?php if ( $deed ) : ?>
					<div class="deed-summary-numbers">
						<span><?php esc_html_e( 'মোট জমা', 'sojib68' ); ?>: <strong><?php echo esc_html( sojib68_poysha_to_display( $deed->total_received_poysha ) ); ?></strong></span>
						<?php if ( null !== $deed->due_poysha ) : ?>
							<span>
								<?php echo $deed->due_poysha >= 0 ? esc_html__( 'বকেয়া', 'sojib68' ) : esc_html__( 'অতিরিক্ত জমা/অগ্রিম', 'sojib68' ); ?>:
								<strong><?php echo esc_html( sojib68_poysha_to_display( abs( $deed->due_poysha ) ) ); ?></strong>
							</span>
						<?php endif; ?>
					</div>
				<?php endif; ?>
			</div>
		</fieldset>

		<fieldset class="deed-section">
			<legend><?php esc_html_e( 'ব্যয়ের হিসাব', 'sojib68' ); ?></legend>
			<div class="deed-repeat" data-repeat="expenses" data-template="expense">
				<?php
				$expenses = $deed && $deed->expenses ? $deed->expenses : array();
				foreach ( $expenses as $i => $e ) :
					sojib68_render_expense_row( $i, $e );
				endforeach;
				?>
			</div>
			<button type="button" class="deed-add-row" data-add-row="expenses"><?php esc_html_e( '+ খরচ যোগ করুন', 'sojib68' ); ?></button>
		</fieldset>

		<fieldset class="deed-section">
			<legend><?php esc_html_e( 'আমার পারিশ্রমিক', 'sojib68' ); ?></legend>
			<div class="deed-grid-2">
				<div class="deed-field">
					<label for="f-fee-agreed"><?php esc_html_e( 'নির্ধারিত পারিশ্রমিক (টাকা)', 'sojib68' ); ?></label>
					<input type="text" id="f-fee-agreed" inputmode="decimal" name="fee_agreed" value="<?php echo esc_attr( $deed ? sojib68_poysha_to_display( $deed->fee_agreed_poysha ) : '' ); ?>">
				</div>
				<div class="deed-field">
					<label for="f-fee-collected"><?php esc_html_e( 'আদায়কৃত পারিশ্রমিক (টাকা)', 'sojib68' ); ?></label>
					<input type="text" id="f-fee-collected" inputmode="decimal" name="fee_collected" value="<?php echo esc_attr( $deed ? sojib68_poysha_to_display( $deed->fee_collected_poysha ) : '' ); ?>">
				</div>
			</div>
		</fieldset>

		<fieldset class="deed-section">
			<legend><?php esc_html_e( 'অন্যান্য', 'sojib68' ); ?></legend>
			<div class="deed-field">
				<label for="f-notes"><?php esc_html_e( 'সাধারণ মন্তব্য', 'sojib68' ); ?></label>
				<textarea id="f-notes" name="notes" rows="3"><?php echo esc_textarea( $deed ? $deed->notes : '' ); ?></textarea>
			</div>
		</fieldset>

		<div class="deed-form-actions">
			<button type="submit" class="btn btn-primary" data-action="submit"><?php esc_html_e( 'সাবমিট করুন', 'sojib68' ); ?></button>
			<button type="submit" class="btn btn-outline" data-action="submit-and-new"><?php esc_html_e( 'সংরক্ষণ করে আরেকটি এন্ট্রি', 'sojib68' ); ?></button>
		</div>

		<div class="deed-success-actions" hidden>
			<a href="<?php echo esc_url( home_url( '/' . SOJIB68_OFFICE_BASE . '/all-deeds' ) ); ?>" class="btn btn-outline"><?php esc_html_e( 'সকল দলিল দেখুন', 'sojib68' ); ?></a>
			<a href="<?php echo esc_url( home_url( '/' . SOJIB68_OFFICE_BASE . '/entry-new' ) ); ?>" class="btn btn-primary"><?php esc_html_e( 'আরেকটি এন্ট্রি করুন', 'sojib68' ); ?></a>
		</div>
	</form>

	<script type="application/json" id="deed-office-options"><?php echo wp_json_encode( wp_list_pluck( $offices, 'name' ) ); ?></script>
	<script type="application/json" id="deed-type-options"><?php echo wp_json_encode( wp_list_pluck( $types, 'name' ) ); ?></script>
	<script>window.sojib68DeedAjax = {
		ajaxUrl: <?php echo wp_json_encode( admin_url( 'admin-ajax.php' ) ); ?>
	};</script>
	<script src="<?php echo esc_url( get_theme_file_uri( 'assets/js/office-deeds.js' ) ); ?>"></script>
	<?php
}

function sojib68_render_person_row( $group, $i, $row ) {
	?>
	<div class="deed-repeat-row">
		<div class="deed-grid-3">
			<div class="deed-field">
				<label><?php echo 'donors' === $group ? esc_html__( 'দাতার নাম', 'sojib68' ) : esc_html__( 'গ্রহীতার নাম', 'sojib68' ); ?></label>
				<input type="text" name="<?php echo esc_attr( $group ); ?>[<?php echo (int) $i; ?>][name]" value="<?php echo esc_attr( $row->name ?? '' ); ?>">
			</div>
			<div class="deed-field">
				<label><?php esc_html_e( 'ঠিকানা', 'sojib68' ); ?></label>
				<input type="text" name="<?php echo esc_attr( $group ); ?>[<?php echo (int) $i; ?>][address]" value="<?php echo esc_attr( $row->address ?? '' ); ?>">
			</div>
			<div class="deed-field">
				<label><?php esc_html_e( 'মোবাইল নং', 'sojib68' ); ?></label>
				<input type="text" name="<?php echo esc_attr( $group ); ?>[<?php echo (int) $i; ?>][mobile]" value="<?php echo esc_attr( $row->mobile ?? '' ); ?>">
			</div>
		</div>
		<button type="button" class="deed-remove-row" aria-label="<?php esc_attr_e( 'সরান', 'sojib68' ); ?>">&times;</button>
	</div>
	<?php
}

function sojib68_render_land_row( $i, $row ) {
	$units = sojib68_land_unit_options();
	?>
	<div class="deed-repeat-row">
		<div class="deed-grid-4">
			<div class="deed-field">
				<label><?php esc_html_e( 'মৌজা', 'sojib68' ); ?></label>
				<input type="text" name="lands[<?php echo (int) $i; ?>][mouza]" value="<?php echo esc_attr( $row->mouza ?? '' ); ?>">
			</div>
			<div class="deed-field">
				<label><?php esc_html_e( 'খতিয়ান নং', 'sojib68' ); ?></label>
				<input type="text" name="lands[<?php echo (int) $i; ?>][khatian_no]" value="<?php echo esc_attr( $row->khatian_no ?? '' ); ?>">
			</div>
			<div class="deed-field">
				<label><?php esc_html_e( 'জমির পরিমাণ', 'sojib68' ); ?></label>
				<input type="text" inputmode="decimal" name="lands[<?php echo (int) $i; ?>][quantity]" value="<?php echo esc_attr( $row->quantity ?? '' ); ?>">
			</div>
			<div class="deed-field">
				<label><?php esc_html_e( 'একক', 'sojib68' ); ?></label>
				<select name="lands[<?php echo (int) $i; ?>][unit]">
					<option value=""><?php esc_html_e( '— নির্বাচন করুন —', 'sojib68' ); ?></option>
					<?php foreach ( $units as $val => $label ) : ?>
						<option value="<?php echo esc_attr( $val ); ?>" <?php selected( $row->unit ?? '', $val ); ?>><?php echo esc_html( $label ); ?></option>
					<?php endforeach; ?>
				</select>
			</div>
		</div>
		<button type="button" class="deed-remove-row" aria-label="<?php esc_attr_e( 'সরান', 'sojib68' ); ?>">&times;</button>
	</div>
	<?php
}

function sojib68_render_receipt_row( $i, $row ) {
	$methods = sojib68_payment_method_options();
	?>
	<div class="deed-repeat-row">
		<div class="deed-grid-4">
			<div class="deed-field">
				<label><?php esc_html_e( 'টাকা গ্রহণের তারিখ', 'sojib68' ); ?></label>
				<input type="date" name="receipts[<?php echo (int) $i; ?>][received_date]" value="<?php echo esc_attr( $row->received_date ?? '' ); ?>">
			</div>
			<div class="deed-field">
				<label><?php esc_html_e( 'জমার পরিমাণ', 'sojib68' ); ?></label>
				<input type="text" inputmode="decimal" name="receipts[<?php echo (int) $i; ?>][amount]" value="<?php echo esc_attr( isset( $row->amount_poysha ) ? sojib68_poysha_to_display( $row->amount_poysha ) : '' ); ?>">
			</div>
			<div class="deed-field">
				<label><?php esc_html_e( 'মাধ্যম', 'sojib68' ); ?></label>
				<select name="receipts[<?php echo (int) $i; ?>][method]">
					<option value=""><?php esc_html_e( '— নির্বাচন করুন —', 'sojib68' ); ?></option>
					<?php foreach ( $methods as $val => $label ) : ?>
						<option value="<?php echo esc_attr( $val ); ?>" <?php selected( $row->method ?? '', $val ); ?>><?php echo esc_html( $label ); ?></option>
					<?php endforeach; ?>
				</select>
			</div>
			<div class="deed-field">
				<label><?php esc_html_e( 'মন্তব্য', 'sojib68' ); ?></label>
				<input type="text" name="receipts[<?php echo (int) $i; ?>][note]" value="<?php echo esc_attr( $row->note ?? '' ); ?>">
			</div>
		</div>
		<button type="button" class="deed-remove-row" aria-label="<?php esc_attr_e( 'সরান', 'sojib68' ); ?>">&times;</button>
	</div>
	<?php
}

function sojib68_render_expense_row( $i, $row ) {
	$categories = sojib68_expense_category_options();
	$statuses   = sojib68_expense_status_options();
	$paid_bys   = sojib68_expense_paid_by_options();
	?>
	<div class="deed-repeat-row">
		<div class="deed-grid-3">
			<div class="deed-field">
				<label><?php esc_html_e( 'খরচের খাত', 'sojib68' ); ?></label>
				<select name="expenses[<?php echo (int) $i; ?>][category]">
					<option value=""><?php esc_html_e( '— নির্বাচন করুন —', 'sojib68' ); ?></option>
					<?php foreach ( $categories as $val => $label ) : ?>
						<option value="<?php echo esc_attr( $val ); ?>" <?php selected( $row->category ?? '', $val ); ?>><?php echo esc_html( $label ); ?></option>
					<?php endforeach; ?>
				</select>
			</div>
			<div class="deed-field">
				<label><?php esc_html_e( 'খরচের বিবরণ', 'sojib68' ); ?></label>
				<input type="text" name="expenses[<?php echo (int) $i; ?>][description]" value="<?php echo esc_attr( $row->description ?? '' ); ?>">
			</div>
			<div class="deed-field">
				<label><?php esc_html_e( 'টাকার পরিমাণ', 'sojib68' ); ?></label>
				<input type="text" inputmode="decimal" name="expenses[<?php echo (int) $i; ?>][amount]" value="<?php echo esc_attr( isset( $row->amount_poysha ) ? sojib68_poysha_to_display( $row->amount_poysha ) : '' ); ?>">
			</div>
			<div class="deed-field">
				<label><?php esc_html_e( 'পরিশোধের তারিখ', 'sojib68' ); ?></label>
				<input type="date" name="expenses[<?php echo (int) $i; ?>][paid_date]" value="<?php echo esc_attr( $row->paid_date ?? '' ); ?>">
			</div>
			<div class="deed-field">
				<label><?php esc_html_e( 'পরিশোধের অবস্থা', 'sojib68' ); ?></label>
				<select name="expenses[<?php echo (int) $i; ?>][paid_status]">
					<option value=""><?php esc_html_e( '— নির্বাচন করুন —', 'sojib68' ); ?></option>
					<?php foreach ( $statuses as $val => $label ) : ?>
						<option value="<?php echo esc_attr( $val ); ?>" <?php selected( $row->paid_status ?? '', $val ); ?>><?php echo esc_html( $label ); ?></option>
					<?php endforeach; ?>
				</select>
			</div>
			<div class="deed-field">
				<label><?php esc_html_e( 'কে পরিশোধ করেছেন', 'sojib68' ); ?></label>
				<select name="expenses[<?php echo (int) $i; ?>][paid_by]">
					<option value=""><?php esc_html_e( '— নির্বাচন করুন —', 'sojib68' ); ?></option>
					<?php foreach ( $paid_bys as $val => $label ) : ?>
						<option value="<?php echo esc_attr( $val ); ?>" <?php selected( $row->paid_by ?? '', $val ); ?>><?php echo esc_html( $label ); ?></option>
					<?php endforeach; ?>
				</select>
			</div>
		</div>
		<button type="button" class="deed-remove-row" aria-label="<?php esc_attr_e( 'সরান', 'sojib68' ); ?>">&times;</button>
	</div>
	<?php
}

/* ---------------------------------------------------------------------
 * "সকল দলিল" — search + paginated listing
 * ------------------------------------------------------------------ */

function sojib68_office_render_all_deeds() {
	$filters = array(
		'name'       => isset( $_GET['name'] ) ? sanitize_text_field( wp_unslash( $_GET['name'] ) ) : '',
		'mobile'     => isset( $_GET['mobile'] ) ? sanitize_text_field( wp_unslash( $_GET['mobile'] ) ) : '',
		'address'    => isset( $_GET['address'] ) ? sanitize_text_field( wp_unslash( $_GET['address'] ) ) : '',
		'deed_no'    => isset( $_GET['deed_no'] ) ? sanitize_text_field( wp_unslash( $_GET['deed_no'] ) ) : '',
		'deed_type'  => isset( $_GET['deed_type'] ) ? sanitize_text_field( wp_unslash( $_GET['deed_type'] ) ) : '',
		'office'     => isset( $_GET['office'] ) ? sanitize_text_field( wp_unslash( $_GET['office'] ) ) : '',
		'reg_year'   => isset( $_GET['reg_year'] ) ? sanitize_text_field( wp_unslash( $_GET['reg_year'] ) ) : '',
		'date_from'  => isset( $_GET['date_from'] ) ? sanitize_text_field( wp_unslash( $_GET['date_from'] ) ) : '',
		'date_to'    => isset( $_GET['date_to'] ) ? sanitize_text_field( wp_unslash( $_GET['date_to'] ) ) : '',
		'mouza'      => isset( $_GET['mouza'] ) ? sanitize_text_field( wp_unslash( $_GET['mouza'] ) ) : '',
		'khatian_no' => isset( $_GET['khatian_no'] ) ? sanitize_text_field( wp_unslash( $_GET['khatian_no'] ) ) : '',
		'land_unit'  => isset( $_GET['land_unit'] ) ? sanitize_text_field( wp_unslash( $_GET['land_unit'] ) ) : '',
		'value_min'  => isset( $_GET['value_min'] ) ? sanitize_text_field( wp_unslash( $_GET['value_min'] ) ) : '',
		'value_max'  => isset( $_GET['value_max'] ) ? sanitize_text_field( wp_unslash( $_GET['value_max'] ) ) : '',
		'has_due'    => isset( $_GET['has_due'] ) ? sanitize_text_field( wp_unslash( $_GET['has_due'] ) ) : '',
	);
	$filters = array_filter( $filters, function ( $v ) { return '' !== $v; } );

	$paged  = isset( $_GET['paged'] ) ? max( 1, (int) $_GET['paged'] ) : 1;
	$result = sojib68_deed_search( $filters, $paged, 20 );

	$offices = sojib68_offices_list();
	$types   = sojib68_deed_types_list();
	$base    = home_url( '/' . SOJIB68_OFFICE_BASE . '/all-deeds' );
	?>
	<details class="deed-filters" <?php echo ! empty( $filters ) ? 'open' : ''; ?>>
		<summary><?php esc_html_e( 'অনুসন্ধান / Filter', 'sojib68' ); ?></summary>
		<form method="get" action="<?php echo esc_url( $base ); ?>" class="deed-filter-form">
			<div class="deed-grid-3">
				<div class="deed-field"><label><?php esc_html_e( 'নাম (দাতা/গ্রহীতা)', 'sojib68' ); ?></label><input type="text" name="name" value="<?php echo esc_attr( $filters['name'] ?? '' ); ?>"></div>
				<div class="deed-field"><label><?php esc_html_e( 'মোবাইল', 'sojib68' ); ?></label><input type="text" name="mobile" value="<?php echo esc_attr( $filters['mobile'] ?? '' ); ?>"></div>
				<div class="deed-field"><label><?php esc_html_e( 'ঠিকানা', 'sojib68' ); ?></label><input type="text" name="address" value="<?php echo esc_attr( $filters['address'] ?? '' ); ?>"></div>
				<div class="deed-field"><label><?php esc_html_e( 'দলিল নং', 'sojib68' ); ?></label><input type="text" name="deed_no" value="<?php echo esc_attr( $filters['deed_no'] ?? '' ); ?>"></div>
				<div class="deed-field">
					<label><?php esc_html_e( 'দলিলের ধরন', 'sojib68' ); ?></label>
					<select name="deed_type">
						<option value=""><?php esc_html_e( 'সব', 'sojib68' ); ?></option>
						<?php foreach ( $types as $type ) : ?>
							<option value="<?php echo esc_attr( $type->name ); ?>" <?php selected( $filters['deed_type'] ?? '', $type->name ); ?>><?php echo esc_html( $type->name ); ?></option>
						<?php endforeach; ?>
					</select>
				</div>
				<div class="deed-field">
					<label><?php esc_html_e( 'সাব-রেজিস্ট্রেশন অফিস', 'sojib68' ); ?></label>
					<select name="office">
						<option value=""><?php esc_html_e( 'সব', 'sojib68' ); ?></option>
						<?php foreach ( $offices as $office ) : ?>
							<option value="<?php echo esc_attr( $office->name ); ?>" <?php selected( $filters['office'] ?? '', $office->name ); ?>><?php echo esc_html( $office->name ); ?></option>
						<?php endforeach; ?>
					</select>
				</div>
				<div class="deed-field"><label><?php esc_html_e( 'সাল', 'sojib68' ); ?></label><input type="text" name="reg_year" value="<?php echo esc_attr( $filters['reg_year'] ?? '' ); ?>"></div>
				<div class="deed-field"><label><?php esc_html_e( 'তারিখ থেকে', 'sojib68' ); ?></label><input type="date" name="date_from" value="<?php echo esc_attr( $filters['date_from'] ?? '' ); ?>"></div>
				<div class="deed-field"><label><?php esc_html_e( 'তারিখ পর্যন্ত', 'sojib68' ); ?></label><input type="date" name="date_to" value="<?php echo esc_attr( $filters['date_to'] ?? '' ); ?>"></div>
				<div class="deed-field"><label><?php esc_html_e( 'মৌজা', 'sojib68' ); ?></label><input type="text" name="mouza" value="<?php echo esc_attr( $filters['mouza'] ?? '' ); ?>"></div>
				<div class="deed-field"><label><?php esc_html_e( 'খতিয়ান নং', 'sojib68' ); ?></label><input type="text" name="khatian_no" value="<?php echo esc_attr( $filters['khatian_no'] ?? '' ); ?>"></div>
				<div class="deed-field">
					<label><?php esc_html_e( 'জমির একক', 'sojib68' ); ?></label>
					<select name="land_unit">
						<option value=""><?php esc_html_e( 'সব', 'sojib68' ); ?></option>
						<?php foreach ( sojib68_land_unit_options() as $val => $label ) : ?>
							<option value="<?php echo esc_attr( $val ); ?>" <?php selected( $filters['land_unit'] ?? '', $val ); ?>><?php echo esc_html( $label ); ?></option>
						<?php endforeach; ?>
					</select>
				</div>
				<div class="deed-field"><label><?php esc_html_e( 'দলিলের মূল্য (সর্বনিম্ন)', 'sojib68' ); ?></label><input type="text" name="value_min" value="<?php echo esc_attr( $filters['value_min'] ?? '' ); ?>"></div>
				<div class="deed-field"><label><?php esc_html_e( 'দলিলের মূল্য (সর্বোচ্চ)', 'sojib68' ); ?></label><input type="text" name="value_max" value="<?php echo esc_attr( $filters['value_max'] ?? '' ); ?>"></div>
				<div class="deed-field">
					<label><?php esc_html_e( 'বকেয়া আছে কি না', 'sojib68' ); ?></label>
					<select name="has_due">
						<option value=""><?php esc_html_e( 'সব', 'sojib68' ); ?></option>
						<option value="1" <?php selected( $filters['has_due'] ?? '', '1' ); ?>><?php esc_html_e( 'বকেয়া আছে', 'sojib68' ); ?></option>
						<option value="0" <?php selected( $filters['has_due'] ?? '', '0' ); ?>><?php esc_html_e( 'বকেয়া নেই', 'sojib68' ); ?></option>
					</select>
				</div>
			</div>
			<div class="deed-filter-actions">
				<button type="submit" class="btn btn-primary btn-sm"><?php esc_html_e( 'খুঁজুন', 'sojib68' ); ?></button>
				<a href="<?php echo esc_url( $base ); ?>" class="btn btn-outline btn-sm"><?php esc_html_e( 'মুছে ফেলুন', 'sojib68' ); ?></a>
			</div>
		</form>
	</details>

	<p class="deed-result-count">
		<?php echo esc_html( sprintf( /* translators: %d: count */ __( 'মোট %d টি দলিল পাওয়া গেছে।', 'sojib68' ), $result['total'] ) ); ?>
	</p>

	<?php if ( empty( $result['rows'] ) ) : ?>
		<div class="office-placeholder"><p><?php esc_html_e( 'কোনো দলিল পাওয়া যায়নি।', 'sojib68' ); ?></p></div>
	<?php else : ?>
		<div class="deed-table-wrap">
			<table class="deed-table">
				<thead>
					<tr>
						<th><?php esc_html_e( 'দলিল নং', 'sojib68' ); ?></th>
						<th><?php esc_html_e( 'তারিখ/সাল', 'sojib68' ); ?></th>
						<th><?php esc_html_e( 'ধরন', 'sojib68' ); ?></th>
						<th><?php esc_html_e( 'অফিস', 'sojib68' ); ?></th>
						<th><?php esc_html_e( 'মূল্য', 'sojib68' ); ?></th>
						<th><?php esc_html_e( 'বকেয়া', 'sojib68' ); ?></th>
						<th></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $result['rows'] as $row ) : ?>
						<tr>
							<td data-label="<?php esc_attr_e( 'দলিল নং', 'sojib68' ); ?>"><?php echo esc_html( $row->deed_no ?: '—' ); ?></td>
							<td data-label="<?php esc_attr_e( 'তারিখ/সাল', 'sojib68' ); ?>"><?php echo esc_html( $row->reg_date ? $row->reg_date : ( $row->reg_year ? $row->reg_year : __( 'সাল অজানা', 'sojib68' ) ) ); ?></td>
							<td data-label="<?php esc_attr_e( 'ধরন', 'sojib68' ); ?>"><?php echo esc_html( $row->deed_type ?: '—' ); ?></td>
							<td data-label="<?php esc_attr_e( 'অফিস', 'sojib68' ); ?>"><?php echo esc_html( $row->sub_registry_office ?: '—' ); ?></td>
							<td data-label="<?php esc_attr_e( 'মূল্য', 'sojib68' ); ?>"><?php echo esc_html( sojib68_poysha_to_display( $row->deed_value_poysha ) ?: '—' ); ?></td>
							<td data-label="<?php esc_attr_e( 'বকেয়া', 'sojib68' ); ?>">
								<?php
								if ( null === $row->due_poysha ) {
									esc_html_e( 'অজানা', 'sojib68' );
								} elseif ( $row->due_poysha > 0 ) {
									echo '<span class="deed-due-chip deed-due-chip--due">' . esc_html( sojib68_poysha_to_display( $row->due_poysha ) ) . '</span>';
								} elseif ( $row->due_poysha < 0 ) {
									echo '<span class="deed-due-chip deed-due-chip--advance">' . esc_html__( 'অগ্রিম', 'sojib68' ) . ' ' . esc_html( sojib68_poysha_to_display( abs( $row->due_poysha ) ) ) . '</span>';
								} else {
									echo '<span class="deed-due-chip deed-due-chip--settled">' . esc_html__( 'পরিশোধিত', 'sojib68' ) . '</span>';
								}
								?>
							</td>
							<td data-label="">
								<a href="<?php echo esc_url( add_query_arg( 'id', $row->id, home_url( '/' . SOJIB68_OFFICE_BASE . '/entry-new' ) ) ); ?>" class="btn btn-outline btn-sm"><?php esc_html_e( 'দেখুন/Edit', 'sojib68' ); ?></a>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>

		<?php if ( $result['total_pages'] > 1 ) : ?>
			<nav class="deed-pagination" aria-label="<?php esc_attr_e( 'পাতা', 'sojib68' ); ?>">
				<?php for ( $p = 1; $p <= $result['total_pages']; $p++ ) : ?>
					<a
						href="<?php echo esc_url( add_query_arg( array_merge( $filters, array( 'paged' => $p ) ), $base ) ); ?>"
						class="<?php echo $p === $result['paged'] ? 'is-active' : ''; ?>"
					><?php echo esc_html( $p ); ?></a>
				<?php endfor; ?>
			</nav>
		<?php endif; ?>
	<?php endif; ?>
	<?php
}
