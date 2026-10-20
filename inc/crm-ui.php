<?php
/**
 * CRM screen rendering. All markup for the authenticated dashboard
 * lives here; the public inquiry form is rendered from crm-system.php
 * since it shares the standalone login-page shell, not the dashboard
 * shell.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function sojib68_crm_icon( $name, $size = 20 ) {
	$icons = array(
		'grid'     => '<rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/>',
		'users'    => '<circle cx="9" cy="8" r="3.2"/><path d="M3 20c0-3.3 2.7-6 6-6s6 2.7 6 6"/><circle cx="17" cy="9" r="2.6"/><path d="M15 14.2c2.6.4 4.5 2.6 4.5 5.3"/>',
		'plus'     => '<path d="M12 5v14M5 12h14"/>',
		'phone'    => '<path d="M5 4h3l2 5-2.5 1.5a11 11 0 0 0 5 5L14 13l5 2v3a2 2 0 0 1-2 2C9.5 20 4 14.5 4 7a2 2 0 0 1 1-2z"/>',
		'logout'   => '<path d="M9 5H5a1 1 0 0 0-1 1v12a1 1 0 0 0 1 1h4M16 8l4 4-4 4M20 12H9"/>',
		'trash'    => '<path d="M4 7h16M9 7V4h6v3M6 7l1 13h10l1-13"/>',
		'check'    => '<path d="M20 6L9 17l-5-5"/>',
		'filter'   => '<path d="M4 5h16l-6 8v6l-4-2v-4z"/>',
		'clock'    => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 3"/>',
		'briefcase'=> '<rect x="3" y="8" width="18" height="12" rx="2"/><path d="M8 8V6a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>',
		'back'     => '<path d="M19 12H5M11 18l-6-6 6-6"/>',
		'eye'      => '<path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7z"/><circle cx="12" cy="12" r="3"/>',
	);

	if ( ! isset( $icons[ $name ] ) ) {
		$name = 'grid';
	}

	return '<svg width="' . (int) $size . '" height="' . (int) $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $icons[ $name ] . '</svg>';
}

/* ---------------------------------------------------------------------
 * Dashboard home: summary cards + work-type cards + finance placeholder
 * + lead table
 * ------------------------------------------------------------------ */

function sojib68_crm_render_dashboard_home() {
	$scope    = sojib68_crm_current_user_scope();
	$stats    = sojib68_crm_dashboard_stats( $scope );
	$sources  = sojib68_crm_lead_sources();
	$services = sojib68_crm_service_types();
	$leads_url = home_url( '/' . SOJIB68_CRM_BASE . '/leads' );
	?>
	<h2 class="crm-section-title"><?php esc_html_e( 'লিডের সারাংশ', 'sojib68' ); ?></h2>
	<div class="crm-stats-grid">
		<a class="crm-stat-tile" href="<?php echo esc_url( $leads_url ); ?>">
			<span class="crm-stat-tile__num"><?php echo esc_html( number_format_i18n( $stats['total'] ) ); ?></span>
			<span class="crm-stat-tile__label"><?php esc_html_e( 'মোট লিড', 'sojib68' ); ?></span>
		</a>
		<a class="crm-stat-tile" href="<?php echo esc_url( add_query_arg( 'status', 'new', $leads_url ) ); ?>">
			<span class="crm-stat-tile__num"><?php echo esc_html( number_format_i18n( $stats['new'] ) ); ?></span>
			<span class="crm-stat-tile__label"><?php esc_html_e( 'নতুন লিড', 'sojib68' ); ?></span>
		</a>
		<a class="crm-stat-tile" href="<?php echo esc_url( add_query_arg( 'date_from', current_time( 'Y-m-d' ), $leads_url ) ); ?>">
			<span class="crm-stat-tile__num"><?php echo esc_html( number_format_i18n( $stats['today'] ) ); ?></span>
			<span class="crm-stat-tile__label"><?php esc_html_e( 'আজকের লিড', 'sojib68' ); ?></span>
		</a>
		<a class="crm-stat-tile" href="<?php echo esc_url( add_query_arg( 'followup', 'today', $leads_url ) ); ?>">
			<span class="crm-stat-tile__num"><?php echo esc_html( number_format_i18n( $stats['today_followup'] ) ); ?></span>
			<span class="crm-stat-tile__label"><?php esc_html_e( 'আজকের ফলোআপ', 'sojib68' ); ?></span>
		</a>
		<a class="crm-stat-tile crm-stat-tile--warn" href="<?php echo esc_url( add_query_arg( 'followup', 'overdue', $leads_url ) ); ?>">
			<span class="crm-stat-tile__num"><?php echo esc_html( number_format_i18n( $stats['overdue_followup'] ) ); ?></span>
			<span class="crm-stat-tile__label"><?php esc_html_e( 'সময় পার হওয়া ফলোআপ', 'sojib68' ); ?></span>
		</a>
	</div>

	<h2 class="crm-section-title"><?php esc_html_e( 'উৎস অনুযায়ী', 'sojib68' ); ?></h2>
	<div class="crm-stats-grid crm-stats-grid--wrap">
		<?php foreach ( $sources as $key => $label ) : ?>
			<a class="crm-stat-tile crm-stat-tile--sm" href="<?php echo esc_url( add_query_arg( 'source', $key, $leads_url ) ); ?>">
				<span class="crm-stat-tile__num"><?php echo esc_html( number_format_i18n( $stats['by_source'][ $key ] ) ); ?></span>
				<span class="crm-stat-tile__label"><?php echo esc_html( $label ); ?></span>
			</a>
		<?php endforeach; ?>
	</div>

	<?php $cash = sojib68_crm_can_view_finance() ? sojib68_crm_cash_summary() : array(); ?>
	<h2 class="crm-section-title"><?php esc_html_e( 'কাজের ধরন অনুযায়ী', 'sojib68' ); ?></h2>
	<div class="crm-work-grid">
		<?php foreach ( $services as $key => $label ) :
			$w = $stats['by_work_type'][ $key ];
			?>
			<a class="crm-work-card" href="<?php echo esc_url( add_query_arg( 'work_type', $key, home_url( '/' . SOJIB68_CRM_BASE . '/works' ) ) ); ?>">
				<h3><?php echo esc_html( $label ); ?></h3>
				<div class="crm-work-card__row">
					<span><?php esc_html_e( 'চলমান', 'sojib68' ); ?></span>
					<strong><?php echo esc_html( number_format_i18n( $w['running'] ) ); ?></strong>
				</div>
				<div class="crm-work-card__row">
					<span><?php esc_html_e( 'পেন্ডিং', 'sojib68' ); ?></span>
					<strong><?php echo esc_html( number_format_i18n( $w['pending'] ) ); ?></strong>
				</div>
				<div class="crm-work-card__row">
					<span><?php esc_html_e( 'ডেলিভারির জন্য প্রস্তুত', 'sojib68' ); ?></span>
					<strong><?php echo esc_html( number_format_i18n( $w['ready'] ) ); ?></strong>
				</div>
				<?php if ( isset( $cash[ $key ] ) ) : ?>
					<div class="crm-work-card__cash">
						<span><?php esc_html_e( 'আদায়', 'sojib68' ); ?> <strong>৳<?php echo esc_html( sojib68_crm_poysha_to_taka_display( $cash[ $key ]['in'] ) ); ?></strong></span>
						<span><?php esc_html_e( 'জমা আছে', 'sojib68' ); ?> <strong>৳<?php echo esc_html( sojib68_crm_poysha_to_taka_display( $cash[ $key ]['balance'] ) ); ?></strong></span>
					</div>
				<?php endif; ?>
			</a>
		<?php endforeach; ?>
	</div>

	<?php if ( sojib68_crm_can_view_finance() ) : ?>
		<h2 class="crm-section-title"><?php esc_html_e( 'হিসাব — ক্যাটাগরি অনুযায়ী ক্যাশ', 'sojib68' ); ?></h2>
		<?php sojib68_crm_render_cash_ledger( $cash ); ?>
	<?php endif; ?>

	<h2 class="crm-section-title"><?php esc_html_e( 'সব লিড', 'sojib68' ); ?></h2>
	<?php sojib68_crm_render_leads_table(); ?>
	<?php
}

function sojib68_crm_render_cash_ledger( $cash ) {
	$categories = sojib68_crm_cash_categories();
	$entries    = sojib68_crm_cash_entries();
	?>
	<div class="crm-cash-grid">
		<div class="crm-table-wrap">
			<table class="crm-table">
				<thead>
					<tr>
						<th><?php esc_html_e( 'ক্যাটাগরি', 'sojib68' ); ?></th>
						<th><?php esc_html_e( 'আদায়', 'sojib68' ); ?></th>
						<th><?php esc_html_e( 'খরচ', 'sojib68' ); ?></th>
						<th><?php esc_html_e( 'জমা আছে', 'sojib68' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $categories as $key => $label ) :
						$c = $cash[ $key ];
						?>
						<tr>
							<td><?php echo esc_html( $label ); ?></td>
							<td>৳<?php echo esc_html( sojib68_crm_poysha_to_taka_display( $c['in'] ) ); ?></td>
							<td>৳<?php echo esc_html( sojib68_crm_poysha_to_taka_display( $c['out'] ) ); ?></td>
							<td><strong>৳<?php echo esc_html( sojib68_crm_poysha_to_taka_display( $c['balance'] ) ); ?></strong></td>
						</tr>
					<?php endforeach; ?>
					<tr class="crm-cash-total-row">
						<td><?php esc_html_e( 'সর্বমোট', 'sojib68' ); ?></td>
						<td>৳<?php echo esc_html( sojib68_crm_poysha_to_taka_display( $cash['__total__']['in'] ) ); ?></td>
						<td>৳<?php echo esc_html( sojib68_crm_poysha_to_taka_display( $cash['__total__']['out'] ) ); ?></td>
						<td><strong>৳<?php echo esc_html( sojib68_crm_poysha_to_taka_display( $cash['__total__']['balance'] ) ); ?></strong></td>
					</tr>
				</tbody>
			</table>
		</div>

		<div class="crm-cash-add">
			<h3><?php esc_html_e( 'নতুন এন্ট্রি', 'sojib68' ); ?></h3>
			<form method="post" action="<?php echo esc_url( home_url( '/' . SOJIB68_CRM_BASE . '/cash-add' ) ); ?>">
				<?php wp_nonce_field( 'sojib68_crm_cash_add', 'sojib68_crm_cash_nonce' ); ?>
				<div class="crm-field">
					<label><?php esc_html_e( 'ক্যাটাগরি', 'sojib68' ); ?></label>
					<select name="category">
						<?php foreach ( $categories as $key => $label ) : ?>
							<option value="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></option>
						<?php endforeach; ?>
					</select>
				</div>
				<div class="crm-field">
					<label><?php esc_html_e( 'ধরন', 'sojib68' ); ?></label>
					<select name="direction">
						<?php foreach ( sojib68_crm_cash_directions() as $key => $label ) : ?>
							<option value="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></option>
						<?php endforeach; ?>
					</select>
				</div>
				<div class="crm-field">
					<label><?php esc_html_e( 'পরিমাণ (৳)', 'sojib68' ); ?></label>
					<input type="number" step="0.01" min="0.01" name="amount" required>
				</div>
				<div class="crm-field">
					<label><?php esc_html_e( 'তারিখ', 'sojib68' ); ?></label>
					<input type="date" name="txn_date" value="<?php echo esc_attr( current_time( 'Y-m-d' ) ); ?>">
				</div>
				<div class="crm-field crm-field--full">
					<label><?php esc_html_e( 'নোট', 'sojib68' ); ?></label>
					<input type="text" name="note" placeholder="<?php esc_attr_e( 'যেমনঃ নামজারির টাকা দিয়ে দলিল রেজিস্ট্রেশনের খরচ', 'sojib68' ); ?>">
				</div>
				<button type="submit" class="btn btn-primary btn-sm"><?php esc_html_e( 'যোগ করুন', 'sojib68' ); ?></button>
			</form>
		</div>
	</div>

	<?php if ( $entries ) : ?>
		<div class="crm-table-wrap crm-cash-log">
			<table class="crm-table">
				<thead>
					<tr>
						<th><?php esc_html_e( 'তারিখ', 'sojib68' ); ?></th>
						<th><?php esc_html_e( 'ক্যাটাগরি', 'sojib68' ); ?></th>
						<th><?php esc_html_e( 'ধরন', 'sojib68' ); ?></th>
						<th><?php esc_html_e( 'পরিমাণ', 'sojib68' ); ?></th>
						<th><?php esc_html_e( 'নোট', 'sojib68' ); ?></th>
						<th></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $entries as $e ) : ?>
						<tr>
							<td><?php echo esc_html( mysql2date( 'd M, Y', $e['txn_date'] ) ); ?></td>
							<td><?php echo esc_html( $categories[ $e['category'] ] ?? $e['category'] ); ?></td>
							<td><span class="crm-chip <?php echo 'in' === $e['direction'] ? 'crm-chip--confirmed' : 'crm-chip--unreachable'; ?>"><?php echo esc_html( sojib68_crm_cash_directions()[ $e['direction'] ] ?? $e['direction'] ); ?></span></td>
							<td>৳<?php echo esc_html( sojib68_crm_poysha_to_taka_display( $e['amount_poysha'] ) ); ?></td>
							<td class="crm-table__notes"><?php echo esc_html( $e['note'] ); ?></td>
							<td><button type="button" class="btn btn-outline btn-sm btn-danger-outline crm-delete-cash" data-cash-id="<?php echo (int) $e['id']; ?>"><?php echo sojib68_crm_icon( 'trash', 14 ); // phpcs:ignore ?></button></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	<?php endif; ?>
	<?php
}

/* ---------------------------------------------------------------------
 * Lead table (filters + table), shared by dashboard and the "সব লিড"
 * page.
 * ------------------------------------------------------------------ */

function sojib68_crm_render_leads_table() {
	$statuses = sojib68_crm_lead_statuses();
	$sources  = sojib68_crm_lead_sources();
	$services = sojib68_crm_service_types();
	$staff    = sojib68_crm_assignable_users();

	$args = array(
		'status'       => isset( $_GET['status'] ) ? sanitize_key( wp_unslash( $_GET['status'] ) ) : '',
		'source'       => isset( $_GET['source'] ) ? sanitize_key( wp_unslash( $_GET['source'] ) ) : '',
		'service_type' => isset( $_GET['service_type'] ) ? sanitize_key( wp_unslash( $_GET['service_type'] ) ) : '',
		'assigned_to'  => isset( $_GET['assigned_to'] ) ? (int) $_GET['assigned_to'] : 0,
		'q'            => isset( $_GET['q'] ) ? sanitize_text_field( wp_unslash( $_GET['q'] ) ) : '',
		'date_from'    => isset( $_GET['date_from'] ) ? sanitize_text_field( wp_unslash( $_GET['date_from'] ) ) : '',
		'date_to'      => isset( $_GET['date_to'] ) ? sanitize_text_field( wp_unslash( $_GET['date_to'] ) ) : '',
		'page'         => isset( $_GET['paged'] ) ? (int) $_GET['paged'] : 1,
		'scope_user_id'=> sojib68_crm_current_user_scope(),
	);

	$followup_filter = isset( $_GET['followup'] ) ? sanitize_key( wp_unslash( $_GET['followup'] ) ) : '';

	$result   = sojib68_crm_leads_search( $args );
	$base_url = home_url( '/' . SOJIB68_CRM_BASE . '/leads' );
	$total_pg = (int) ceil( $result['total'] / max( 1, $result['per_page'] ) );

	// Follow-up quick filters are applied in PHP after the DB query (small volumes; keeps the search function simple).
	if ( 'today' === $followup_filter || 'overdue' === $followup_filter ) {
		$now   = current_time( 'mysql' );
		$today = current_time( 'Y-m-d' );
		$result['rows'] = array_values( array_filter( $result['rows'], function ( $r ) use ( $followup_filter, $now, $today ) {
			if ( empty( $r['next_followup_at'] ) ) {
				return false;
			}
			if ( 'today' === $followup_filter ) {
				return substr( $r['next_followup_at'], 0, 10 ) === $today;
			}
			return $r['next_followup_at'] < $now;
		} ) );
	}
	?>
	<form method="get" action="<?php echo esc_url( $base_url ); ?>" class="crm-filters">
		<input type="search" name="q" placeholder="<?php esc_attr_e( 'নাম, মোবাইল বা Lead ID দিয়ে খুঁজুন', 'sojib68' ); ?>" value="<?php echo esc_attr( $args['q'] ); ?>">
		<select name="service_type">
			<option value=""><?php esc_html_e( 'সব সেবা', 'sojib68' ); ?></option>
			<?php foreach ( $services as $key => $label ) : ?>
				<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $args['service_type'], $key ); ?>><?php echo esc_html( $label ); ?></option>
			<?php endforeach; ?>
		</select>
		<select name="source">
			<option value=""><?php esc_html_e( 'সব উৎস', 'sojib68' ); ?></option>
			<?php foreach ( $sources as $key => $label ) : ?>
				<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $args['source'], $key ); ?>><?php echo esc_html( $label ); ?></option>
			<?php endforeach; ?>
		</select>
		<?php if ( sojib68_crm_is_admin() ) : ?>
			<select name="assigned_to">
				<option value=""><?php esc_html_e( 'সব দায়িত্বপ্রাপ্ত', 'sojib68' ); ?></option>
				<?php foreach ( $staff as $uid => $uname ) : ?>
					<option value="<?php echo (int) $uid; ?>" <?php selected( $args['assigned_to'], $uid ); ?>><?php echo esc_html( $uname ); ?></option>
				<?php endforeach; ?>
			</select>
		<?php endif; ?>
		<select name="status">
			<option value=""><?php esc_html_e( 'সব অবস্থা', 'sojib68' ); ?></option>
			<?php foreach ( $statuses as $key => $label ) : ?>
				<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $args['status'], $key ); ?>><?php echo esc_html( $label ); ?></option>
			<?php endforeach; ?>
		</select>
		<input type="date" name="date_from" value="<?php echo esc_attr( $args['date_from'] ); ?>" title="<?php esc_attr_e( 'তারিখ থেকে', 'sojib68' ); ?>">
		<input type="date" name="date_to" value="<?php echo esc_attr( $args['date_to'] ); ?>" title="<?php esc_attr_e( 'তারিখ পর্যন্ত', 'sojib68' ); ?>">
		<button type="submit" class="btn btn-outline btn-sm"><?php echo sojib68_crm_icon( 'filter', 16 ); // phpcs:ignore ?> <?php esc_html_e( 'ফিল্টার', 'sojib68' ); ?></button>
		<a href="<?php echo esc_url( $base_url ); ?>" class="btn btn-outline btn-sm"><?php esc_html_e( 'Clear', 'sojib68' ); ?></a>
		<a href="<?php echo esc_url( home_url( '/' . SOJIB68_CRM_BASE . '/entry-new' ) ); ?>" class="btn btn-primary btn-sm"><?php echo sojib68_crm_icon( 'plus', 16 ); // phpcs:ignore ?> <?php esc_html_e( 'নতুন লিড', 'sojib68' ); ?></a>
	</form>

	<?php if ( empty( $result['rows'] ) ) : ?>
		<p class="crm-empty"><?php esc_html_e( 'কোনো লিড পাওয়া যায়নি।', 'sojib68' ); ?></p>
	<?php else : ?>
		<div class="crm-table-wrap">
			<table class="crm-table">
				<thead>
					<tr>
						<th>#</th>
						<th><?php esc_html_e( 'Lead ID', 'sojib68' ); ?></th>
						<th><?php esc_html_e( 'আসার তারিখ', 'sojib68' ); ?></th>
						<th><?php esc_html_e( 'নাম', 'sojib68' ); ?></th>
						<th><?php esc_html_e( 'মোবাইল', 'sojib68' ); ?></th>
						<th><?php esc_html_e( 'উৎস', 'sojib68' ); ?></th>
						<th><?php esc_html_e( 'কার মাধ্যমে', 'sojib68' ); ?></th>
						<th><?php esc_html_e( 'সেবা', 'sojib68' ); ?></th>
						<th><?php esc_html_e( 'দায়িত্বপ্রাপ্ত', 'sojib68' ); ?></th>
						<th><?php esc_html_e( 'পরবর্তী ফলোআপ', 'sojib68' ); ?></th>
						<th><?php esc_html_e( 'অবস্থা', 'sojib68' ); ?></th>
						<th></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $result['rows'] as $i => $row ) : ?>
						<tr>
							<td><?php echo esc_html( $i + 1 + ( ( $result['page'] - 1 ) * $result['per_page'] ) ); ?></td>
							<td><a href="<?php echo esc_url( home_url( '/' . SOJIB68_CRM_BASE . '/entry-new?id=' . (int) $row['id'] ) ); ?>"><?php echo esc_html( $row['lead_code'] ); ?></a></td>
							<td><?php echo esc_html( mysql2date( 'd M, Y', $row['created_at'] ) ); ?></td>
							<td><?php echo esc_html( $row['name'] ? $row['name'] : __( '(নাম নেই)', 'sojib68' ) ); ?></td>
							<td><?php echo $row['phone'] ? '<a href="tel:' . esc_attr( $row['phone'] ) . '">' . esc_html( $row['phone'] ) . '</a>' : '&mdash;'; ?></td>
							<td><?php echo esc_html( $sources[ $row['source'] ] ?? $row['source'] ); ?></td>
							<td><?php echo esc_html( $row['referred_by'] ? $row['referred_by'] : '—' ); ?></td>
							<td><?php echo esc_html( $services[ $row['service_type'] ] ?? $row['service_type'] ); ?></td>
							<td><?php echo esc_html( $row['assigned_to'] && isset( $staff[ $row['assigned_to'] ] ) ? $staff[ $row['assigned_to'] ] : '—' ); ?></td>
							<td><?php echo esc_html( $row['next_followup_at'] ? mysql2date( 'd M, h:i A', $row['next_followup_at'] ) : '—' ); ?></td>
							<td><span class="crm-chip crm-chip--<?php echo esc_attr( $row['status'] ); ?>"><?php echo esc_html( $statuses[ $row['status'] ] ?? $row['status'] ); ?></span></td>
							<td><a href="<?php echo esc_url( home_url( '/' . SOJIB68_CRM_BASE . '/entry-new?id=' . (int) $row['id'] ) ); ?>" class="btn btn-outline btn-sm"><?php echo sojib68_crm_icon( 'eye', 15 ); // phpcs:ignore ?> <?php esc_html_e( 'দেখুন', 'sojib68' ); ?></a></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>

		<?php if ( $total_pg > 1 ) :
			$keep = array_filter( array(
				'status'       => $args['status'],
				'source'       => $args['source'],
				'service_type' => $args['service_type'],
				'assigned_to'  => $args['assigned_to'],
				'q'            => $args['q'],
				'date_from'    => $args['date_from'],
				'date_to'      => $args['date_to'],
			) );
			?>
			<nav class="crm-pagination">
				<?php for ( $p = 1; $p <= $total_pg; $p++ ) : ?>
					<a href="<?php echo esc_url( add_query_arg( array_merge( $keep, array( 'paged' => $p ) ), $base_url ) ); ?>" class="<?php echo $p === $result['page'] ? 'is-active' : ''; ?>"><?php echo esc_html( $p ); ?></a>
				<?php endfor; ?>
			</nav>
		<?php endif; ?>
	<?php endif; ?>
	<?php
}

/* ---------------------------------------------------------------------
 * New / edit lead
 * ------------------------------------------------------------------ */

function sojib68_crm_render_entry_form() {
	$id   = isset( $_GET['id'] ) ? (int) $_GET['id'] : 0;
	$lead = $id ? sojib68_crm_lead_get( $id ) : null;

	if ( $id && ! $lead ) {
		echo '<p class="crm-empty">' . esc_html__( 'লিড পাওয়া যায়নি।', 'sojib68' ) . '</p>';
		return;
	}

	if ( $id && ! sojib68_crm_is_admin() && (int) $lead['assigned_to'] !== get_current_user_id() ) {
		echo '<p class="crm-empty">' . esc_html__( 'এই লিড দেখার অনুমতি আপনার নেই।', 'sojib68' ) . '</p>';
		return;
	}

	$sources  = sojib68_crm_lead_sources();
	$services = sojib68_crm_service_types();
	$statuses = sojib68_crm_lead_statuses();
	$staff    = sojib68_crm_assignable_users();
	$v        = function ( $key, $default = '' ) use ( $lead ) {
		return $lead ? esc_attr( $lead[ $key ] ) : esc_attr( $default );
	};

	$dupes = $lead ? sojib68_crm_find_leads_by_phone( $lead['phone'], $id ) : array();
	?>
	<?php if ( $dupes ) : ?>
		<div class="crm-notice crm-notice--warn">
			<strong><?php esc_html_e( 'এই নম্বরে আরও লিড আছে:', 'sojib68' ); ?></strong>
			<?php foreach ( $dupes as $d ) : ?>
				<a href="<?php echo esc_url( home_url( '/' . SOJIB68_CRM_BASE . '/entry-new?id=' . (int) $d['id'] ) ); ?>"><?php echo esc_html( $d['lead_code'] . ' — ' . ( $d['name'] ? $d['name'] : __( '(নাম নেই)', 'sojib68' ) ) ); ?></a>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>

	<form method="post" action="<?php echo esc_url( home_url( '/' . SOJIB68_CRM_BASE . '/entry-new' ) ); ?>" class="crm-entry-form">
		<?php wp_nonce_field( 'sojib68_crm_save_lead', 'sojib68_crm_nonce' ); ?>
		<input type="hidden" name="id" value="<?php echo (int) $id; ?>">

		<div class="crm-field">
			<label><?php esc_html_e( 'নাম', 'sojib68' ); ?></label>
			<input type="text" name="name" value="<?php echo $v( 'name' ); ?>">
		</div>
		<div class="crm-field">
			<label><?php esc_html_e( 'মোবাইল নম্বর', 'sojib68' ); ?></label>
			<input type="text" name="phone" value="<?php echo $v( 'phone' ); ?>">
		</div>
		<div class="crm-field">
			<label><?php esc_html_e( 'ইমেইল', 'sojib68' ); ?></label>
			<input type="email" name="email" value="<?php echo $v( 'email' ); ?>">
		</div>
		<div class="crm-field crm-field--full">
			<label><?php esc_html_e( 'ঠিকানা / জমির এলাকা', 'sojib68' ); ?></label>
			<textarea name="address" rows="2"><?php echo $lead ? esc_textarea( $lead['address'] ) : ''; ?></textarea>
		</div>
		<div class="crm-field">
			<label><?php esc_html_e( 'উৎস', 'sojib68' ); ?></label>
			<select name="source">
				<?php foreach ( $sources as $key => $label ) : ?>
					<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $lead ? $lead['source'] : 'walkin', $key ); ?>><?php echo esc_html( $label ); ?></option>
				<?php endforeach; ?>
			</select>
		</div>
		<div class="crm-field">
			<label><?php esc_html_e( 'কার মাধ্যমে এসেছেন', 'sojib68' ); ?></label>
			<input type="text" name="referred_by" value="<?php echo $v( 'referred_by' ); ?>" placeholder="<?php esc_attr_e( 'নাম (ঐচ্ছিক)', 'sojib68' ); ?>">
		</div>
		<div class="crm-field">
			<label><?php esc_html_e( 'প্রয়োজনীয় সেবা', 'sojib68' ); ?></label>
			<select name="service_type">
				<?php foreach ( $services as $key => $label ) : ?>
					<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $lead ? $lead['service_type'] : '', $key ); ?>><?php echo esc_html( $label ); ?></option>
				<?php endforeach; ?>
			</select>
		</div>
		<div class="crm-field">
			<label><?php esc_html_e( 'দায়িত্বপ্রাপ্ত ব্যক্তি', 'sojib68' ); ?></label>
			<select name="assigned_to">
				<option value=""><?php esc_html_e( '(এখনো ঠিক হয়নি)', 'sojib68' ); ?></option>
				<?php foreach ( $staff as $uid => $uname ) : ?>
					<option value="<?php echo (int) $uid; ?>" <?php selected( $lead ? (int) $lead['assigned_to'] : 0, $uid ); ?>><?php echo esc_html( $uname ); ?></option>
				<?php endforeach; ?>
			</select>
		</div>
		<div class="crm-field crm-field--full">
			<label><?php esc_html_e( 'প্রয়োজনের বিবরণ', 'sojib68' ); ?></label>
			<textarea name="details" rows="3"><?php echo $lead ? esc_textarea( $lead['details'] ) : ''; ?></textarea>
		</div>
		<div class="crm-field">
			<label><?php esc_html_e( 'অবস্থা', 'sojib68' ); ?></label>
			<select name="status">
				<?php foreach ( $statuses as $key => $label ) : ?>
					<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $lead ? $lead['status'] : 'new', $key ); ?>><?php echo esc_html( $label ); ?></option>
				<?php endforeach; ?>
			</select>
		</div>
		<div class="crm-field">
			<label><?php esc_html_e( 'পরবর্তী ফলোআপ (তারিখ ও সময়)', 'sojib68' ); ?></label>
			<input type="datetime-local" name="next_followup_at" value="<?php echo $lead && $lead['next_followup_at'] ? esc_attr( str_replace( ' ', 'T', substr( $lead['next_followup_at'], 0, 16 ) ) ) : ''; ?>">
		</div>
		<div class="crm-field crm-field--full">
			<label><?php esc_html_e( 'নোট', 'sojib68' ); ?></label>
			<textarea name="notes" rows="3"><?php echo $lead ? esc_textarea( $lead['notes'] ) : ''; ?></textarea>
		</div>

		<button type="submit" class="btn btn-primary"><?php esc_html_e( 'সংরক্ষণ করুন', 'sojib68' ); ?></button>
	</form>

	<?php if ( $lead ) : ?>
		<?php sojib68_crm_render_lead_detail_extras( $lead ); ?>
	<?php endif; ?>
	<?php
}

function sojib68_crm_render_lead_detail_extras( $lead ) {
	$followups = sojib68_crm_lead_followups( $lead['id'] );
	$activity  = sojib68_crm_lead_activity( $lead['id'] );
	$works     = sojib68_crm_lead_works( $lead['id'] );
	$statuses  = sojib68_crm_lead_statuses();
	$services  = sojib68_crm_service_types();
	?>
	<div class="crm-detail-grid">
		<div class="crm-detail-col">
			<h3><?php esc_html_e( 'ফলোআপ', 'sojib68' ); ?></h3>
			<form class="crm-inline-form" data-add-followup data-lead-id="<?php echo (int) $lead['id']; ?>">
				<input type="datetime-local" name="due_at" required>
				<input type="text" name="note" placeholder="<?php esc_attr_e( 'সংক্ষিপ্ত নোট (ঐচ্ছিক)', 'sojib68' ); ?>">
				<button type="submit" class="btn btn-outline btn-sm"><?php echo sojib68_crm_icon( 'plus', 14 ); // phpcs:ignore ?> <?php esc_html_e( 'যোগ করুন', 'sojib68' ); ?></button>
			</form>
			<?php if ( empty( $followups ) ) : ?>
				<p class="crm-empty"><?php esc_html_e( 'কোনো ফলোআপ নেই।', 'sojib68' ); ?></p>
			<?php else : ?>
				<ul class="crm-followup-list">
					<?php foreach ( $followups as $f ) : ?>
						<li class="<?php echo $f['completed_at'] ? 'is-done' : ''; ?>">
							<span><?php echo esc_html( mysql2date( 'd M, Y h:i A', $f['due_at'] ) ); ?><?php echo $f['note'] ? ' — ' . esc_html( $f['note'] ) : ''; ?></span>
							<?php if ( ! $f['completed_at'] ) : ?>
								<button type="button" class="btn btn-outline btn-sm" data-complete-followup="<?php echo (int) $f['id']; ?>"><?php echo sojib68_crm_icon( 'check', 14 ); // phpcs:ignore ?></button>
							<?php else : ?>
								<span class="crm-done-tag"><?php esc_html_e( 'সম্পন্ন', 'sojib68' ); ?></span>
							<?php endif; ?>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</div>

		<div class="crm-detail-col">
			<h3><?php esc_html_e( 'নোট ও ইতিহাস', 'sojib68' ); ?></h3>
			<form class="crm-inline-form" data-add-note data-lead-id="<?php echo (int) $lead['id']; ?>">
				<textarea name="note" rows="2" placeholder="<?php esc_attr_e( 'নতুন নোট লিখুন…', 'sojib68' ); ?>" required></textarea>
				<button type="submit" class="btn btn-outline btn-sm"><?php esc_html_e( 'যোগ করুন', 'sojib68' ); ?></button>
			</form>
			<?php if ( empty( $activity ) ) : ?>
				<p class="crm-empty"><?php esc_html_e( 'কোনো ইতিহাস নেই।', 'sojib68' ); ?></p>
			<?php else : ?>
				<ul class="crm-activity-list">
					<?php foreach ( $activity as $a ) : ?>
						<li>
							<span class="crm-activity-time"><?php echo esc_html( mysql2date( 'd M, h:i A', $a['created_at'] ) ); ?></span>
							<span><?php echo esc_html( $a['message'] ); ?></span>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</div>
	</div>

	<div class="crm-works-box">
		<h3><?php esc_html_e( 'সংশ্লিষ্ট কাজ', 'sojib68' ); ?></h3>
		<?php if ( empty( $works ) ) : ?>
			<p class="crm-empty"><?php esc_html_e( 'এখনো কোনো কাজের ফাইল তৈরি হয়নি।', 'sojib68' ); ?></p>
		<?php else : ?>
			<ul class="crm-works-list">
				<?php foreach ( $works as $w ) : ?>
					<li><strong><?php echo esc_html( $w['work_code'] ); ?></strong> — <?php echo esc_html( $services[ $w['work_type'] ] ?? $w['work_type'] ); ?> — <span class="crm-chip"><?php echo esc_html( sojib68_crm_work_statuses()[ $w['status'] ] ?? $w['status'] ); ?></span></li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>
		<?php if ( 'confirmed' === $lead['status'] ) : ?>
			<button type="button" class="btn btn-primary btn-sm" data-create-work="<?php echo (int) $lead['id']; ?>"><?php echo sojib68_crm_icon( 'briefcase', 15 ); // phpcs:ignore ?> <?php esc_html_e( 'কাজের ফাইল তৈরি করুন', 'sojib68' ); ?></button>
		<?php endif; ?>
	</div>
	<?php
}

/* ---------------------------------------------------------------------
 * Category entry page (e.g. নামজারি এন্ট্রি): add/edit a work of one
 * fixed category using the shared work fields, plus that category's
 * list below. Category-specific fields get added here per category as
 * they're defined.
 * ------------------------------------------------------------------ */

function sojib68_crm_render_category_entry( $type ) {
	$types    = sojib68_crm_service_types();
	$statuses = sojib68_crm_work_statuses();
	$delivery = sojib68_crm_delivery_statuses();
	$staff    = sojib68_crm_assignable_users();
	$slug     = $type . '-entry';
	$action   = home_url( '/' . SOJIB68_CRM_BASE . '/' . $slug );
	$stage_cfg = sojib68_crm_category_stage_config( $type );

	$id   = isset( $_GET['id'] ) ? (int) $_GET['id'] : 0;
	$work = $id ? sojib68_crm_work_get( $id ) : null;

	if ( $id && ( ! $work || $work['work_type'] !== $type ) ) {
		echo '<p class="crm-empty">' . esc_html__( 'কাজ পাওয়া যায়নি।', 'sojib68' ) . '</p>';
		return;
	}
	if ( $work && ! sojib68_crm_is_admin() && (int) $work['assigned_to'] !== get_current_user_id() ) {
		echo '<p class="crm-empty">' . esc_html__( 'এই কাজ দেখার অনুমতি আপনার নেই।', 'sojib68' ) . '</p>';
		return;
	}

	$v = function ( $key, $default = '' ) use ( $work ) {
		return $work ? esc_attr( (string) $work[ $key ] ) : esc_attr( $default );
	};
	?>
	<form method="post" action="<?php echo esc_url( $action ); ?>" class="crm-entry-form">
		<?php wp_nonce_field( 'sojib68_crm_save_work', 'sojib68_crm_work_nonce' ); ?>
		<input type="hidden" name="id" value="<?php echo (int) $id; ?>">

		<div class="crm-field">
			<label><?php esc_html_e( 'গ্রাহকের নাম', 'sojib68' ); ?></label>
			<input type="text" name="customer_name" value="<?php echo $v( 'customer_name' ); ?>">
		</div>
		<div class="crm-field">
			<label><?php esc_html_e( 'মোবাইল নম্বর', 'sojib68' ); ?></label>
			<input type="text" name="customer_phone" value="<?php echo $v( 'customer_phone' ); ?>">
		</div>
		<div class="crm-field">
			<label><?php esc_html_e( 'কাজ আসার তারিখ', 'sojib68' ); ?></label>
			<input type="date" name="received_date" value="<?php echo $v( 'received_date', current_time( 'Y-m-d' ) ); ?>">
		</div>
		<div class="crm-field">
			<label><?php esc_html_e( 'কার মাধ্যমে এসেছেন', 'sojib68' ); ?></label>
			<input type="text" name="referred_by" value="<?php echo $v( 'referred_by' ); ?>">
		</div>
		<div class="crm-field">
			<label><?php esc_html_e( 'দায়িত্বপ্রাপ্ত ব্যক্তি', 'sojib68' ); ?></label>
			<select name="assigned_to">
				<option value=""><?php esc_html_e( '(এখনো ঠিক হয়নি)', 'sojib68' ); ?></option>
				<?php foreach ( $staff as $uid => $uname ) : ?>
					<option value="<?php echo (int) $uid; ?>" <?php selected( $work ? (int) $work['assigned_to'] : 0, $uid ); ?>><?php echo esc_html( $uname ); ?></option>
				<?php endforeach; ?>
			</select>
		</div>
		<?php if ( $stage_cfg ) : ?>
			<div class="crm-field">
				<label><?php esc_html_e( 'কাজের অবস্থা', 'sojib68' ); ?></label>
				<select name="stage" data-stage-select data-cancel-stage="<?php echo esc_attr( $stage_cfg['cancelled'] ); ?>">
					<?php foreach ( $stage_cfg['stages'] as $key => $label ) : ?>
						<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $work && $work['stage'] ? $work['stage'] : $stage_cfg['default'], $key ); ?>><?php echo esc_html( $label ); ?></option>
					<?php endforeach; ?>
				</select>
			</div>
			<div class="crm-field crm-field--full" data-cancel-reason hidden>
				<label><?php esc_html_e( 'বাতিলের কারণ (ব্যাখ্যা)', 'sojib68' ); ?></label>
				<textarea name="cancel_reason" rows="2"><?php echo $work ? esc_textarea( (string) $work['cancel_reason'] ) : ''; ?></textarea>
			</div>
		<?php else : ?>
			<div class="crm-field">
				<label><?php esc_html_e( 'কাজের অবস্থা', 'sojib68' ); ?></label>
				<select name="status">
					<?php foreach ( $statuses as $key => $label ) : ?>
						<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $work ? $work['status'] : 'running', $key ); ?>><?php echo esc_html( $label ); ?></option>
					<?php endforeach; ?>
				</select>
			</div>
		<?php endif; ?>
		<div class="crm-field">
			<label><?php esc_html_e( 'পেন্ডিং থাকার কারণ', 'sojib68' ); ?></label>
			<input type="text" name="pending_reason" value="<?php echo $v( 'pending_reason' ); ?>">
		</div>
		<div class="crm-field">
			<label><?php esc_html_e( 'পরবর্তী করণীয়', 'sojib68' ); ?></label>
			<input type="text" name="next_action" value="<?php echo $v( 'next_action' ); ?>">
		</div>
		<div class="crm-field">
			<label><?php esc_html_e( 'করণীয়ের তারিখ', 'sojib68' ); ?></label>
			<input type="date" name="next_action_date" value="<?php echo $v( 'next_action_date' ); ?>">
		</div>
		<div class="crm-field crm-field--full">
			<label><?php esc_html_e( 'প্রয়োজনীয় কাগজপত্র', 'sojib68' ); ?></label>
			<textarea name="docs_needed" rows="2"><?php echo $work ? esc_textarea( $work['docs_needed'] ) : ''; ?></textarea>
		</div>
		<div class="crm-field">
			<label><?php esc_html_e( 'প্রাপ্ত কাগজপত্র', 'sojib68' ); ?></label>
			<textarea name="docs_received" rows="2"><?php echo $work ? esc_textarea( $work['docs_received'] ) : ''; ?></textarea>
		</div>
		<div class="crm-field">
			<label><?php esc_html_e( 'বাকি কাগজপত্র', 'sojib68' ); ?></label>
			<textarea name="docs_pending" rows="2"><?php echo $work ? esc_textarea( $work['docs_pending'] ) : ''; ?></textarea>
		</div>
		<div class="crm-field">
			<label><?php esc_html_e( 'ফাইল এখন কোথায় / কার কাছে', 'sojib68' ); ?></label>
			<input type="text" name="file_location" value="<?php echo $v( 'file_location' ); ?>">
		</div>
		<div class="crm-field">
			<label><?php esc_html_e( 'ডেলিভারির অবস্থা', 'sojib68' ); ?></label>
			<select name="delivery_status">
				<?php foreach ( $delivery as $key => $label ) : ?>
					<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $work ? $work['delivery_status'] : 'not_ready', $key ); ?>><?php echo esc_html( $label ); ?></option>
				<?php endforeach; ?>
			</select>
		</div>
		<div class="crm-field">
			<label><?php esc_html_e( 'ডেলিভারির তারিখ', 'sojib68' ); ?></label>
			<input type="date" name="delivery_date" value="<?php echo $v( 'delivery_date' ); ?>">
		</div>
		<div class="crm-field">
			<label><?php esc_html_e( 'কে গ্রহণ করেছেন', 'sojib68' ); ?></label>
			<input type="text" name="delivery_recipient" value="<?php echo $v( 'delivery_recipient' ); ?>">
		</div>

		<button type="submit" class="btn btn-primary"><?php esc_html_e( 'সংরক্ষণ করুন', 'sojib68' ); ?></button>
	</form>

	<h2 class="crm-section-title"><?php echo esc_html( $types[ $type ] ); ?> — <?php esc_html_e( 'সব কাজ', 'sojib68' ); ?></h2>
	<?php
	$result = sojib68_crm_works_search( array(
		'work_type'     => $type,
		'scope_user_id' => sojib68_crm_current_user_scope(),
	) );
	if ( empty( $result['rows'] ) ) :
		?>
		<p class="crm-empty"><?php esc_html_e( 'এখনো কোনো এন্ট্রি নেই।', 'sojib68' ); ?></p>
	<?php else : ?>
		<div class="crm-table-wrap">
			<table class="crm-table">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Work ID', 'sojib68' ); ?></th>
						<th><?php esc_html_e( 'গ্রাহক', 'sojib68' ); ?></th>
						<th><?php esc_html_e( 'মোবাইল', 'sojib68' ); ?></th>
						<th><?php esc_html_e( 'দায়িত্বপ্রাপ্ত', 'sojib68' ); ?></th>
						<th><?php esc_html_e( 'অবস্থা', 'sojib68' ); ?></th>
						<th><?php esc_html_e( 'ডেলিভারি', 'sojib68' ); ?></th>
						<th></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $result['rows'] as $w ) : ?>
						<tr>
							<td><?php echo esc_html( $w['work_code'] ); ?></td>
							<td><?php echo esc_html( $w['customer_name'] ? $w['customer_name'] : '—' ); ?></td>
							<td><?php echo esc_html( $w['customer_phone'] ? $w['customer_phone'] : '—' ); ?></td>
							<td><?php echo esc_html( $w['assigned_to'] && isset( $staff[ $w['assigned_to'] ] ) ? $staff[ $w['assigned_to'] ] : '—' ); ?></td>
							<td><span class="crm-chip"><?php echo esc_html( ( $stage_cfg && $w['stage'] && isset( $stage_cfg['stages'][ $w['stage'] ] ) ) ? $stage_cfg['stages'][ $w['stage'] ] : ( $statuses[ $w['status'] ] ?? $w['status'] ) ); ?></span></td>
							<td><?php echo esc_html( $delivery[ $w['delivery_status'] ] ?? $w['delivery_status'] ); ?></td>
							<td><a href="<?php echo esc_url( add_query_arg( 'id', (int) $w['id'], $action ) ); ?>" class="btn btn-outline btn-sm"><?php echo sojib68_crm_icon( 'eye', 15 ); // phpcs:ignore ?> <?php esc_html_e( 'দেখুন', 'sojib68' ); ?></a></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	<?php endif; ?>
	<?php
}

/* ---------------------------------------------------------------------
 * Works list (minimal — category-specific detail forms come later)
 * ------------------------------------------------------------------ */

function sojib68_crm_render_works_list() {
	$types     = sojib68_crm_service_types();
	$statuses  = sojib68_crm_work_statuses();
	$delivery  = sojib68_crm_delivery_statuses();
	$staff     = sojib68_crm_assignable_users();

	$args = array(
		'work_type'     => isset( $_GET['work_type'] ) ? sanitize_key( wp_unslash( $_GET['work_type'] ) ) : '',
		'status'        => isset( $_GET['status'] ) ? sanitize_key( wp_unslash( $_GET['status'] ) ) : '',
		'q'             => isset( $_GET['q'] ) ? sanitize_text_field( wp_unslash( $_GET['q'] ) ) : '',
		'page'          => isset( $_GET['paged'] ) ? (int) $_GET['paged'] : 1,
		'scope_user_id' => sojib68_crm_current_user_scope(),
	);

	$result   = sojib68_crm_works_search( $args );
	$base_url = home_url( '/' . SOJIB68_CRM_BASE . '/works' );
	?>
	<form method="get" action="<?php echo esc_url( $base_url ); ?>" class="crm-filters">
		<input type="search" name="q" placeholder="<?php esc_attr_e( 'নাম, মোবাইল বা Work ID দিয়ে খুঁজুন', 'sojib68' ); ?>" value="<?php echo esc_attr( $args['q'] ); ?>">
		<select name="work_type">
			<option value=""><?php esc_html_e( 'সব ধরন', 'sojib68' ); ?></option>
			<?php foreach ( $types as $key => $label ) : ?>
				<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $args['work_type'], $key ); ?>><?php echo esc_html( $label ); ?></option>
			<?php endforeach; ?>
		</select>
		<select name="status">
			<option value=""><?php esc_html_e( 'সব অবস্থা', 'sojib68' ); ?></option>
			<?php foreach ( $statuses as $key => $label ) : ?>
				<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $args['status'], $key ); ?>><?php echo esc_html( $label ); ?></option>
			<?php endforeach; ?>
		</select>
		<button type="submit" class="btn btn-outline btn-sm"><?php echo sojib68_crm_icon( 'filter', 16 ); // phpcs:ignore ?> <?php esc_html_e( 'ফিল্টার', 'sojib68' ); ?></button>
		<a href="<?php echo esc_url( $base_url ); ?>" class="btn btn-outline btn-sm"><?php esc_html_e( 'Clear', 'sojib68' ); ?></a>
	</form>

	<?php if ( empty( $result['rows'] ) ) : ?>
		<p class="crm-empty"><?php esc_html_e( 'কোনো কাজ পাওয়া যায়নি। লিড "কাজ নিশ্চিত" হলে লিডের পাতা থেকে কাজের ফাইল তৈরি করুন।', 'sojib68' ); ?></p>
	<?php else : ?>
		<div class="crm-table-wrap">
			<table class="crm-table">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Work ID', 'sojib68' ); ?></th>
						<th><?php esc_html_e( 'গ্রাহক', 'sojib68' ); ?></th>
						<th><?php esc_html_e( 'ধরন', 'sojib68' ); ?></th>
						<th><?php esc_html_e( 'দায়িত্বপ্রাপ্ত', 'sojib68' ); ?></th>
						<th><?php esc_html_e( 'অবস্থা', 'sojib68' ); ?></th>
						<th><?php esc_html_e( 'ডেলিভারি', 'sojib68' ); ?></th>
						<th><?php esc_html_e( 'ফাইল কোথায়', 'sojib68' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $result['rows'] as $w ) : ?>
						<tr>
							<td><?php echo esc_html( $w['work_code'] ); ?></td>
							<td><?php echo esc_html( $w['customer_name'] ? $w['customer_name'] : $w['customer_phone'] ); ?></td>
							<td><?php echo esc_html( $types[ $w['work_type'] ] ?? $w['work_type'] ); ?></td>
							<td><?php echo esc_html( $w['assigned_to'] && isset( $staff[ $w['assigned_to'] ] ) ? $staff[ $w['assigned_to'] ] : '—' ); ?></td>
							<td><span class="crm-chip"><?php echo esc_html( $statuses[ $w['status'] ] ?? $w['status'] ); ?></span></td>
							<td><?php echo esc_html( $delivery[ $w['delivery_status'] ] ?? $w['delivery_status'] ); ?></td>
							<td><?php echo esc_html( $w['file_location'] ? $w['file_location'] : '—' ); ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	<?php endif; ?>
	<?php
}
