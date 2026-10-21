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
		'bell'     => '<path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/>',
		'edit'     => '<path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4z"/>',
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
	<?php sojib68_crm_render_idle_alert( $scope ); ?>

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

	<?php
	$finance = sojib68_crm_can_view_finance();
	$cash    = $finance ? sojib68_crm_cash_summary() : array();
	$period  = sojib68_crm_money_period(
		isset( $_GET['crm_period'] ) ? sanitize_key( wp_unslash( $_GET['crm_period'] ) ) : 'all',
		isset( $_GET['crm_month'] ) ? sanitize_text_field( wp_unslash( $_GET['crm_month'] ) ) : '',
		isset( $_GET['crm_year'] ) ? (int) $_GET['crm_year'] : 0
	);
	$money   = $finance ? sojib68_crm_work_money_summary( $period['from'], $period['to'], $scope ) : null;
	$taka    = function ( $poysha ) {
		return '৳' . sojib68_crm_poysha_to_taka_display( $poysha );
	};
	?>
	<div class="crm-section-head" id="work-summary">
		<h2 class="crm-section-title"><?php esc_html_e( 'কাজের ধরন অনুযায়ী', 'sojib68' ); ?></h2>
		<?php if ( $finance ) : ?>
			<form method="get" action="<?php echo esc_url( home_url( '/' . SOJIB68_CRM_BASE . '/' ) ); ?>#work-summary" class="crm-period">
				<select name="crm_period" data-period-select>
					<option value="all" <?php selected( $period['period'], 'all' ); ?>><?php esc_html_e( 'সব সময়', 'sojib68' ); ?></option>
					<option value="month" <?php selected( $period['period'], 'month' ); ?>><?php esc_html_e( 'মাসিক', 'sojib68' ); ?></option>
					<option value="year" <?php selected( $period['period'], 'year' ); ?>><?php esc_html_e( 'বাৎসরিক', 'sojib68' ); ?></option>
				</select>
				<input type="month" name="crm_month" value="<?php echo esc_attr( $period['month'] ); ?>" data-period-for="month">
				<select name="crm_year" data-period-for="year">
					<?php for ( $y = (int) current_time( 'Y' ); $y >= 2020; $y-- ) : ?>
						<option value="<?php echo (int) $y; ?>" <?php selected( $period['year'], $y ); ?>><?php echo (int) $y; ?></option>
					<?php endfor; ?>
				</select>
				<button type="submit" class="btn btn-outline btn-sm"><?php esc_html_e( 'দেখুন', 'sojib68' ); ?></button>
			</form>
		<?php endif; ?>
	</div>

	<?php if ( $finance ) :
		$t = $money['total'];
		?>
		<div class="crm-money-strip">
			<div class="crm-money-strip__title">
				<strong><?php esc_html_e( 'সব কাজ মিলে', 'sojib68' ); ?></strong>
				<span><?php echo esc_html( $period['label'] ); ?><?php echo 'all' !== $period['period'] ? ' · ' . esc_html__( 'এই সময়ে আসা কাজ', 'sojib68' ) : ''; ?></span>
			</div>
			<div class="crm-money crm-money--in"><span><?php esc_html_e( 'মোট আয় (আদায়)', 'sojib68' ); ?></span><strong><?php echo esc_html( $taka( $t['collected'] ) ); ?></strong></div>
			<div class="crm-money crm-money--out"><span><?php esc_html_e( 'মোট ব্যয়', 'sojib68' ); ?></span><strong><?php echo esc_html( $taka( $t['spent'] ) ); ?></strong></div>
			<div class="crm-money crm-money--profit<?php echo $t['profit'] < 0 ? ' is-loss' : ''; ?>"><span><?php esc_html_e( 'মোট প্রফিট', 'sojib68' ); ?></span><strong><?php echo esc_html( $taka( $t['profit'] ) ); ?></strong></div>
			<div class="crm-money crm-money--due"><span><?php esc_html_e( 'মোট বকেয়া', 'sojib68' ); ?></span><strong><?php echo esc_html( $taka( $t['due'] ) ); ?></strong></div>
		</div>
		<?php if ( $t['no_agreed'] ) : ?>
			<p class="crm-money-note"><?php echo esc_html( sprintf( /* translators: %s: count */ __( '%s টি কাজে চুক্তির টাকা দেওয়া নেই — সেগুলোর প্রফিট ও বকেয়া এই হিসাবে ধরা হয়নি।', 'sojib68' ), number_format_i18n( $t['no_agreed'] ) ) ); ?></p>
		<?php endif; ?>
	<?php endif; ?>

	<div class="crm-work-grid">
		<?php foreach ( $services as $key => $label ) :
			$w      = $stats['by_work_type'][ $key ];
			$cfg    = sojib68_crm_category_stage_config( $key );
			$labels = $cfg && ! empty( $cfg['labels'] ) ? $cfg['labels'] : array(
				'running' => __( 'চলমান', 'sojib68' ),
				'pending' => __( 'পেন্ডিং', 'sojib68' ),
				'ready'   => __( 'ডেলিভারির জন্য প্রস্তুত', 'sojib68' ),
			);
			?>
			<a class="crm-work-card" href="<?php echo esc_url( add_query_arg( 'work_type', $key, home_url( '/' . SOJIB68_CRM_BASE . '/works' ) ) ); ?>">
				<h3><?php echo esc_html( $label ); ?></h3>
				<?php foreach ( array( 'running', 'pending', 'ready' ) as $bucket ) : ?>
					<div class="crm-work-card__row">
						<span><?php echo esc_html( $labels[ $bucket ] ); ?></span>
						<strong><?php echo esc_html( number_format_i18n( $w[ $bucket ] ) ); ?></strong>
					</div>
				<?php endforeach; ?>
				<?php if ( $finance ) :
					$m = $money['by_type'][ $key ];
					?>
					<div class="crm-work-card__money">
						<span><?php esc_html_e( 'আয়', 'sojib68' ); ?><strong><?php echo esc_html( $taka( $m['collected'] ) ); ?></strong></span>
						<span><?php esc_html_e( 'ব্যয়', 'sojib68' ); ?><strong><?php echo esc_html( $taka( $m['spent'] ) ); ?></strong></span>
						<span class="<?php echo $m['profit'] < 0 ? 'is-loss' : ''; ?>"><?php esc_html_e( 'প্রফিট', 'sojib68' ); ?><strong><?php echo esc_html( $taka( $m['profit'] ) ); ?></strong></span>
						<span class="is-due"><?php esc_html_e( 'বকেয়া', 'sojib68' ); ?><strong><?php echo esc_html( $taka( $m['due'] ) ); ?></strong></span>
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
					<input type="text" inputmode="decimal" autocomplete="off" data-money name="amount" placeholder="<?php esc_attr_e( 'যেমন ১২,৫০০ বা 12,500', 'sojib68' ); ?>" required>
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
							<td class="crm-table__notes"><?php echo esc_html( trim( ( ! empty( $e['work_code'] ) ? $e['work_code'] . ' · ' : '' ) . ( $e['party'] ? $e['party'] . ' — ' : '' ) . $e['note'], ' —·' ) ); ?></td>
							<td><button type="button" class="btn btn-outline btn-sm btn-danger-outline crm-delete-cash" data-cash-id="<?php echo (int) $e['id']; ?>"><?php echo sojib68_crm_icon( 'trash', 14 ); // phpcs:ignore ?></button></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	<?php endif; ?>
	<?php
}

function sojib68_crm_work_edit_url( $work ) {
	$slug = $work['work_type'] . '-entry';
	if ( sojib68_crm_entry_page_type( $slug ) ) {
		return add_query_arg( 'id', (int) $work['id'], home_url( '/' . SOJIB68_CRM_BASE . '/' . $slug ) );
	}
	return add_query_arg( 'q', $work['work_code'], home_url( '/' . SOJIB68_CRM_BASE . '/works' ) );
}

/**
 * "ফাইল আটকে আছে" panel at the top of the dashboard. Staff see only
 * their own assigned files; admins see all, with unassigned files
 * flagged since nobody else will ever be reminded about those.
 */
function sojib68_crm_render_idle_alert( $scope, $show_empty = false ) {
	$idle = sojib68_crm_idle_works( $scope );
	if ( empty( $idle ) ) {
		if ( $show_empty ) {
			?>
			<div class="crm-idle crm-idle--ok">
				<div class="crm-idle__head">
					<span class="crm-idle__icon"><?php echo sojib68_crm_icon( 'check', 22 ); // phpcs:ignore ?></span>
					<div>
						<strong><?php esc_html_e( 'এখন কোনো নোটিফিকেশন নেই', 'sojib68' ); ?></strong>
						<span><?php echo esc_html( sprintf( /* translators: 1: warn days, 2: alert days */ __( 'কোনো ফাইল %1$s দিনের বেশি একই ধাপে আটকে নেই। আটকে গেলে এখানে হলুদ (%1$s দিন) বা লাল (%2$s দিন) সতর্কবার্তা আসবে।', 'sojib68' ), number_format_i18n( SOJIB68_CRM_IDLE_WARN_DAYS ), number_format_i18n( SOJIB68_CRM_IDLE_ALERT_DAYS ) ) ); ?></span>
					</div>
				</div>
			</div>
			<?php
		}
		return;
	}

	$types  = sojib68_crm_service_types();
	$staff  = sojib68_crm_assignable_users();
	$alerts = count( array_filter( $idle, function ( $w ) { return 'alert' === $w['idle_level']; } ) );
	$level  = $alerts ? 'alert' : 'warn';
	?>
	<div class="crm-idle crm-idle--<?php echo esc_attr( $level ); ?>" role="alert">
		<div class="crm-idle__head">
			<span class="crm-idle__icon"><?php echo sojib68_crm_icon( 'clock', 22 ); // phpcs:ignore ?></span>
			<div>
				<strong>
					<?php
					echo esc_html( sprintf(
						/* translators: 1: number of files, 2: days */
						__( '%1$s টি ফাইল %2$s দিনের বেশি একই অবস্থায় পড়ে আছে', 'sojib68' ),
						number_format_i18n( count( $idle ) ),
						number_format_i18n( SOJIB68_CRM_IDLE_WARN_DAYS )
					) );
					?>
				</strong>
				<span>
					<?php
					echo $alerts
						? esc_html( sprintf( /* translators: 1: count, 2: days */ __( 'এর মধ্যে %1$s টি %2$s দিন পার হয়েছে — আজই কাজ এগিয়ে নিন।', 'sojib68' ), number_format_i18n( $alerts ), number_format_i18n( SOJIB68_CRM_IDLE_ALERT_DAYS ) ) )
						: esc_html__( 'কাজ শুরু করে ধাপ আপডেট করুন, তাহলে এই তালিকা থেকে সরে যাবে।', 'sojib68' );
					?>
				</span>
			</div>
		</div>
		<ul class="crm-idle__list">
			<?php foreach ( $idle as $w ) : ?>
				<li>
					<span class="crm-idle__days crm-idle__days--<?php echo esc_attr( $w['idle_level'] ); ?>"><?php echo esc_html( sprintf( /* translators: %s: days */ __( '%s দিন', 'sojib68' ), number_format_i18n( $w['idle_days'] ) ) ); ?></span>
					<span class="crm-idle__main">
						<strong><?php echo esc_html( $w['work_code'] ); ?></strong>
						<?php echo esc_html( $w['customer_name'] ? $w['customer_name'] : $w['customer_phone'] ); ?>
						<em><?php echo esc_html( ( $types[ $w['work_type'] ] ?? $w['work_type'] ) . ' · ' . sojib68_crm_work_stage_label( $w ) ); ?></em>
					</span>
					<?php if ( $w['assigned_to'] && isset( $staff[ $w['assigned_to'] ] ) ) : ?>
						<span class="crm-idle__who"><?php echo esc_html( $staff[ $w['assigned_to'] ] ); ?></span>
					<?php else : ?>
						<span class="crm-idle__who crm-idle__who--none"><?php esc_html_e( 'দায়িত্ব দেওয়া হয়নি', 'sojib68' ); ?></span>
					<?php endif; ?>
					<a class="btn btn-outline btn-sm" href="<?php echo esc_url( sojib68_crm_work_edit_url( $w ) ); ?>"><?php esc_html_e( 'খুলুন', 'sojib68' ); ?></a>
				</li>
			<?php endforeach; ?>
		</ul>
	</div>
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

	// Result of the last save: green on success, red with the reason (and the typed input refilled) on failure.
	$prefill = array();
	$notice  = isset( $_GET['crm_notice'] ) ? sanitize_key( wp_unslash( $_GET['crm_notice'] ) ) : '';
	if ( 'saved' === $notice ) {
		echo '<div class="crm-notice crm-notice--ok">' . esc_html__( 'সংরক্ষণ হয়েছে।', 'sojib68' ) . '</div>';
	} elseif ( 'template' === $notice ) {
		echo '<div class="crm-notice crm-notice--ok">' . esc_html__( 'মেসেজের টেমপ্লেট সংরক্ষণ হয়েছে। এখন থেকে সব ক্লায়েন্টের জন্য এটাই ব্যবহার হবে।', 'sojib68' ) . '</div>';
	} elseif ( 'error' === $notice ) {
		$draft = get_transient( sojib68_crm_work_draft_key() );
		delete_transient( sojib68_crm_work_draft_key() );
		$msg = is_array( $draft ) && ! empty( $draft['message'] ) ? $draft['message'] : __( 'সংরক্ষণ হয়নি। আবার চেষ্টা করুন।', 'sojib68' );
		echo '<div class="crm-notice crm-notice--error"><strong>' . esc_html__( 'সংরক্ষণ হয়নি:', 'sojib68' ) . '</strong> ' . esc_html( $msg ) . '</div>';
		if ( is_array( $draft ) && ! empty( $draft['fields'] ) ) {
			$prefill = $draft['fields'];
		}
	}

	if ( 'namjari' === $type ) {
		sojib68_crm_render_namjari_form( $work, $action, $stage_cfg, $staff, $prefill );
	} else {
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
		<?php if ( sojib68_crm_can_view_finance() ) : ?>
			<div class="crm-field">
				<label><?php esc_html_e( 'গ্রাহকের সাথে চুক্তির মোট টাকা (৳)', 'sojib68' ); ?></label>
				<input type="text" inputmode="decimal" autocomplete="off" data-money name="agreed_amount" value="<?php echo $work && null !== $work['agreed_poysha'] ? esc_attr( sojib68_crm_poysha_to_taka_display( $work['agreed_poysha'] ) ) : ''; ?>" placeholder="<?php esc_attr_e( 'ঠিক না হলে ফাঁকা রাখুন', 'sojib68' ); ?>">
			</div>
		<?php endif; ?>
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
	<?php } ?>

	<?php if ( $work && 'namjari' === $type ) : ?>
		<?php sojib68_crm_render_client_notify( $work ); ?>
	<?php endif; ?>

	<?php if ( $work && sojib68_crm_can_view_finance() ) : ?>
		<?php sojib68_crm_render_work_accounts( $work, $type ); ?>
	<?php endif; ?>

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
						<?php if ( 'namjari' === $type ) : ?><th><?php esc_html_e( 'আবেদন নং', 'sojib68' ); ?></th><?php endif; ?>
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
							<?php if ( 'namjari' === $type ) : ?><td><?php echo esc_html( $w['application_no'] ? $w['application_no'] : '—' ); ?></td><?php endif; ?>
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

/**
 * নামজারি entry form, in the two sections the office asked for:
 * 1) customer & money, 2) application details. Money fields are
 * finance-permission only. "আদায়" and "খরচ" add new dated entries to
 * the ledger on save; বাকী and প্রফিট are calculated live as you type.
 */
function sojib68_crm_render_namjari_form( $work, $action, $stage_cfg, $staff, $prefill = array() ) {
	// Raw value for a field: what was just typed (after a failed save) wins over what's stored.
	$raw = function ( $key, $default = '' ) use ( $work, $prefill ) {
		if ( array_key_exists( $key, $prefill ) ) {
			return (string) $prefill[ $key ];
		}
		return $work && isset( $work[ $key ] ) ? (string) $work[ $key ] : $default;
	};
	$v = function ( $key, $default = '' ) use ( $raw ) {
		return esc_attr( $raw( $key, $default ) );
	};
	$finance = sojib68_crm_can_view_finance();
	$acc     = $work ? sojib68_crm_work_accounts( $work['id'] ) : array( 'collected' => 0, 'spent' => 0 );
	$money   = function ( $poysha ) {
		return '৳' . sojib68_crm_poysha_to_taka_display( $poysha );
	};
	?>
	<form method="post" action="<?php echo esc_url( $action ); ?>" class="crm-entry-form crm-entry-form--sections" data-acc-form data-collected="<?php echo (int) $acc['collected']; ?>" data-spent="<?php echo (int) $acc['spent']; ?>">
		<?php wp_nonce_field( 'sojib68_crm_save_work', 'sojib68_crm_work_nonce' ); ?>
		<input type="hidden" name="id" value="<?php echo (int) ( $work ? $work['id'] : 0 ); ?>">

		<fieldset class="crm-fieldset">
			<legend><span>১</span> <?php esc_html_e( 'গ্রাহক ও হিসাব', 'sojib68' ); ?></legend>

			<div class="crm-field">
				<label><?php esc_html_e( 'কাজ আসার তারিখ', 'sojib68' ); ?></label>
				<input type="date" name="received_date" value="<?php echo $v( 'received_date', current_time( 'Y-m-d' ) ); ?>">
			</div>
			<div class="crm-field">
				<label><?php esc_html_e( 'কিভাবে এসেছে (সোর্স)', 'sojib68' ); ?></label>
				<select name="source">
					<option value=""><?php esc_html_e( '— বাছাই করুন —', 'sojib68' ); ?></option>
					<?php foreach ( sojib68_crm_lead_sources() as $key => $label ) : ?>
						<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $raw( 'source' ), $key ); ?>><?php echo esc_html( $label ); ?></option>
					<?php endforeach; ?>
				</select>
			</div>
			<div class="crm-field crm-field--full">
				<label><?php esc_html_e( 'আবেদনকারীগণের নাম', 'sojib68' ); ?></label>
				<input type="text" name="customer_name" value="<?php echo $v( 'customer_name' ); ?>" placeholder="<?php esc_attr_e( 'একাধিক হলে কমা দিয়ে লিখুন', 'sojib68' ); ?>">
			</div>
			<div class="crm-field">
				<label><?php esc_html_e( 'মোবাইল নং', 'sojib68' ); ?></label>
				<input type="text" name="customer_phone" value="<?php echo $v( 'customer_phone' ); ?>">
			</div>
			<div class="crm-field">
				<label><?php esc_html_e( 'দায়িত্বপ্রাপ্ত ব্যক্তি', 'sojib68' ); ?></label>
				<select name="assigned_to">
					<option value=""><?php esc_html_e( '(এখনো ঠিক হয়নি)', 'sojib68' ); ?></option>
					<?php foreach ( $staff as $uid => $uname ) : ?>
						<option value="<?php echo (int) $uid; ?>" <?php selected( (int) $raw( 'assigned_to', 0 ), $uid ); ?>><?php echo esc_html( $uname ); ?></option>
					<?php endforeach; ?>
				</select>
			</div>

			<?php if ( $finance ) : ?>
				<div class="crm-field">
					<label><?php esc_html_e( 'গ্রাহকের সাথে কাজের চুক্তি (৳)', 'sojib68' ); ?></label>
					<input type="text" inputmode="decimal" autocomplete="off" data-money data-acc="agreed" name="agreed_amount" value="<?php echo array_key_exists( 'agreed_amount', $prefill ) ? esc_attr( $prefill['agreed_amount'] ) : ( $work && null !== $work['agreed_poysha'] ? esc_attr( sojib68_crm_poysha_to_taka_display( $work['agreed_poysha'] ) ) : '' ); ?>" placeholder="<?php esc_attr_e( 'ঠিক না হলে ফাঁকা রাখুন', 'sojib68' ); ?>">
				</div>
				<div class="crm-field">
					<label><?php esc_html_e( 'গ্রাহক থেকে টাকা আদায় (৳)', 'sojib68' ); ?></label>
					<input type="text" inputmode="decimal" autocomplete="off" data-money data-acc="collected" name="collected_add" value="<?php echo $v( 'collected_add' ); ?>" placeholder="<?php esc_attr_e( 'নতুন আদায় হলে লিখুন', 'sojib68' ); ?>">
					<small class="crm-field__hint"><?php echo esc_html( sprintf( /* translators: %s: amount */ __( 'এ পর্যন্ত আদায়: %s', 'sojib68' ), $money( $acc['collected'] ) ) ); ?></small>
				</div>
				<div class="crm-field">
					<label><?php esc_html_e( 'গ্রাহকের নিকট বাকী', 'sojib68' ); ?></label>
					<output class="crm-calc" data-out="due">—</output>
				</div>
				<div class="crm-field">
					<label><?php esc_html_e( 'ফাইল প্রসেসিং ও অন্যান্য খরচ (৳)', 'sojib68' ); ?></label>
					<input type="text" inputmode="decimal" autocomplete="off" data-money data-acc="spent" name="expense_add" value="<?php echo $v( 'expense_add' ); ?>" placeholder="<?php esc_attr_e( 'নতুন খরচ হলে লিখুন', 'sojib68' ); ?>">
					<small class="crm-field__hint"><?php echo esc_html( sprintf( /* translators: %s: amount */ __( 'এ পর্যন্ত খরচ: %s', 'sojib68' ), $money( $acc['spent'] ) ) ); ?></small>
				</div>
				<div class="crm-field">
					<label><?php esc_html_e( 'প্রফিট (অটোমেটিক)', 'sojib68' ); ?></label>
					<output class="crm-calc crm-calc--profit" data-out="profit">—</output>
				</div>
			<?php endif; ?>

			<div class="crm-field crm-field--full">
				<label><?php esc_html_e( 'পেন্ডিং থাকার কারণ', 'sojib68' ); ?></label>
				<input type="text" name="pending_reason" value="<?php echo $v( 'pending_reason' ); ?>">
			</div>
		</fieldset>

		<fieldset class="crm-fieldset">
			<legend><span>২</span> <?php esc_html_e( 'আবেদনের তথ্য', 'sojib68' ); ?></legend>

			<div class="crm-field">
				<label><?php esc_html_e( 'আবেদনের তারিখ', 'sojib68' ); ?></label>
				<input type="date" name="application_date" value="<?php echo $v( 'application_date' ); ?>">
			</div>
			<div class="crm-field">
				<label><?php esc_html_e( 'আবেদন নং', 'sojib68' ); ?></label>
				<input type="text" name="application_no" value="<?php echo $v( 'application_no' ); ?>">
			</div>
			<div class="crm-field">
				<label><?php esc_html_e( 'নথি নং', 'sojib68' ); ?></label>
				<input type="text" name="nothi_no" value="<?php echo $v( 'nothi_no' ); ?>">
			</div>
			<div class="crm-field">
				<label><?php esc_html_e( 'আবেদনের মোবাইল নং', 'sojib68' ); ?></label>
				<input type="text" name="application_mobile" value="<?php echo $v( 'application_mobile' ); ?>">
			</div>
			<div class="crm-field">
				<label><?php esc_html_e( 'আবেদনের অবস্থা', 'sojib68' ); ?></label>
				<select name="stage" data-stage-select data-cancel-stage="<?php echo esc_attr( $stage_cfg['cancelled'] ); ?>">
					<?php foreach ( $stage_cfg['stages'] as $key => $label ) : ?>
						<option value="<?php echo esc_attr( $key ); ?>" <?php selected( '' !== $raw( 'stage' ) ? $raw( 'stage' ) : $stage_cfg['default'], $key ); ?>><?php echo esc_html( $label ); ?></option>
					<?php endforeach; ?>
				</select>
			</div>
			<div class="crm-field crm-field--full" data-cancel-reason hidden>
				<label><?php esc_html_e( 'বাতিলের কারণ', 'sojib68' ); ?></label>
				<textarea name="cancel_reason" rows="2"><?php echo esc_textarea( $raw( 'cancel_reason' ) ); ?></textarea>
			</div>
			<div class="crm-field">
				<label><?php esc_html_e( 'ডেলিভারির তারিখ', 'sojib68' ); ?></label>
				<input type="date" name="delivery_date" value="<?php echo $v( 'delivery_date' ); ?>">
			</div>
			<div class="crm-field">
				<label><?php esc_html_e( 'ডেলিভারির মাধ্যম', 'sojib68' ); ?></label>
				<input type="text" name="delivery_method" value="<?php echo $v( 'delivery_method' ); ?>" list="crm-delivery-methods" placeholder="<?php esc_attr_e( 'যেমন: সরাসরি হাতে', 'sojib68' ); ?>">
				<datalist id="crm-delivery-methods">
					<option value="<?php esc_attr_e( 'সরাসরি হাতে', 'sojib68' ); ?>">
					<option value="<?php esc_attr_e( 'কুরিয়ার', 'sojib68' ); ?>">
					<option value="<?php esc_attr_e( 'WhatsApp', 'sojib68' ); ?>">
					<option value="<?php esc_attr_e( 'ইমেইল', 'sojib68' ); ?>">
					<option value="<?php esc_attr_e( 'প্রতিনিধির মাধ্যমে', 'sojib68' ); ?>">
				</datalist>
			</div>
		</fieldset>

		<button type="submit" class="btn btn-primary"><?php esc_html_e( 'সংরক্ষণ করুন', 'sojib68' ); ?></button>
	</form>
	<?php
}

/**
 * "ক্লায়েন্টকে জানান" — once the work is complete, one click opens
 * WhatsApp to the client's application mobile with the message already
 * written, and records that the client was told (when, by whom).
 */
function sojib68_crm_render_client_notify( $work ) {
	$complete = sojib68_crm_work_is_complete( $work );
	$number   = sojib68_crm_work_whatsapp_number( $work );
	$message  = sojib68_crm_work_whatsapp_message( $work );
	$staff    = sojib68_crm_assignable_users();
	$done     = ! empty( $work['client_notified_at'] );
	?>
	<div class="crm-works-box crm-notify<?php echo $complete ? ' is-ready' : ''; ?>">
		<h3><?php esc_html_e( 'ক্লায়েন্টকে জানান (WhatsApp)', 'sojib68' ); ?></h3>
		<?php if ( ! $complete ) : ?>
			<p class="crm-notify__hint"><?php esc_html_e( 'আবেদনের অবস্থা "খতিয়ান প্রস্তুত" করে সংরক্ষণ করলে এখানে WhatsApp বোতাম চালু হবে।', 'sojib68' ); ?></p>
		<?php elseif ( '' === $number ) : ?>
			<p class="crm-notify__hint crm-notify__hint--warn"><?php esc_html_e( 'আবেদনের মোবাইল নং বা গ্রাহকের মোবাইল সঠিক নয়। 01XXXXXXXXX আকারে (১১ সংখ্যা) দিয়ে সংরক্ষণ করুন।', 'sojib68' ); ?></p>
		<?php else : ?>
			<label class="crm-notify__label" for="crm-wa-text"><?php esc_html_e( 'যে মেসেজ যাবে — পাঠানোর আগে এখানে বদলে নিতে পারেন', 'sojib68' ); ?></label>
			<textarea id="crm-wa-text" class="crm-notify__text" rows="6" data-wa-text><?php echo esc_textarea( $message ); ?></textarea>
			<div class="crm-notify__row">
				<a class="btn crm-btn-whatsapp" href="<?php echo esc_url( sojib68_crm_work_whatsapp_url( $work, $message ) ); ?>" target="_blank" rel="noopener" data-wa-notify="<?php echo (int) $work['id']; ?>" data-wa-number="<?php echo esc_attr( $number ); ?>">
					<svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2a10 10 0 0 0-8.6 15.1L2 22l5-1.3A10 10 0 1 0 12 2zm0 18.2a8.2 8.2 0 0 1-4.2-1.2l-.3-.2-3 .8.8-2.9-.2-.3A8.2 8.2 0 1 1 12 20.2zm4.5-6.1c-.2-.1-1.5-.7-1.7-.8-.2-.1-.4-.1-.6.1l-.8 1c-.1.2-.3.2-.5.1a6.7 6.7 0 0 1-3.3-2.9c-.3-.4.2-.4.7-1.4.1-.2 0-.3 0-.4l-.8-1.8c-.2-.5-.4-.4-.6-.4h-.5a1 1 0 0 0-.7.3 3 3 0 0 0-.9 2.2 5.2 5.2 0 0 0 1.1 2.7 11.9 11.9 0 0 0 4.6 4c1.7.7 2.4.8 3.2.7.5-.1 1.5-.6 1.7-1.2.2-.6.2-1.1.2-1.2-.1-.1-.2-.2-.5-.3z"/></svg>
					<?php echo $done ? esc_html__( 'আবার WhatsApp-এ জানান', 'sojib68' ) : esc_html__( 'WhatsApp-এ জানান', 'sojib68' ); ?>
				</a>
				<span class="crm-notify__status<?php echo $done ? ' is-done' : ''; ?>" data-wa-status>
					<?php
					if ( $done ) {
						$by = $work['client_notified_by'] && isset( $staff[ $work['client_notified_by'] ] ) ? $staff[ $work['client_notified_by'] ] : '';
						echo esc_html( sprintf( /* translators: 1: date/time, 2: name */ __( 'জানানো হয়েছে — %1$s (%2$s)', 'sojib68' ), mysql2date( 'd M, Y h:i A', $work['client_notified_at'] ), $by ) );
					} else {
						esc_html_e( 'এখনো জানানো হয়নি', 'sojib68' );
					}
					?>
				</span>
			</div>
		<?php endif; ?>

		<?php if ( sojib68_crm_is_admin() ) : ?>
			<?php sojib68_crm_render_wa_template_editor( $work ); ?>
		<?php endif; ?>
	</div>
	<?php
}

/** Admin-only: the saved message template every client of this category gets. */
function sojib68_crm_render_wa_template_editor( $work ) {
	$type   = $work['work_type'];
	$tokens = array_keys( sojib68_crm_wa_placeholders( $work ) );
	$help   = array(
		'{নাম}'        => __( 'আবেদনকারীর নাম', 'sojib68' ),
		'{সেবা}'       => __( 'সেবার নাম (যেমন নামজারি)', 'sojib68' ),
		'{আবেদন_নং}'   => __( 'আবেদন নং', 'sojib68' ),
		'{নথি_নং}'     => __( 'নথি নং', 'sojib68' ),
		'{কাজের_আইডি}' => __( 'Work ID', 'sojib68' ),
		'{অফিস_ফোন}'   => __( 'অফিসের ফোন', 'sojib68' ),
	);
	?>
	<details class="crm-wa-template"<?php echo isset( $_GET['crm_notice'] ) && 'template' === $_GET['crm_notice'] ? ' open' : ''; ?>>
		<summary><?php esc_html_e( 'সব ক্লায়েন্টের জন্য মেসেজের টেমপ্লেট বদলান', 'sojib68' ); ?></summary>
		<form method="post" action="<?php echo esc_url( home_url( '/' . SOJIB68_CRM_BASE . '/wa-template' ) ); ?>">
			<?php wp_nonce_field( 'sojib68_crm_wa_template', 'sojib68_crm_wa_nonce' ); ?>
			<input type="hidden" name="type" value="<?php echo esc_attr( $type ); ?>">
			<input type="hidden" name="work_id" value="<?php echo (int) $work['id']; ?>">
			<p class="crm-notify__hint"><?php esc_html_e( 'নিচের কোডে ক্লিক করলে লেখায় বসে যাবে; পাঠানোর সময় প্রতিটি ক্লায়েন্টের আসল তথ্য বসবে। "আবেদন নং: {আবেদন_নং}" ধরনের লাইনের তথ্য ফাঁকা থাকলে সেই লাইনটা বাদ যাবে।', 'sojib68' ); ?></p>
			<div class="crm-wa-tokens">
				<?php foreach ( $tokens as $t ) : ?>
					<button type="button" class="crm-wa-token" data-wa-token="<?php echo esc_attr( $t ); ?>" title="<?php echo esc_attr( $help[ $t ] ?? '' ); ?>"><?php echo esc_html( $t ); ?> <small><?php echo esc_html( $help[ $t ] ?? '' ); ?></small></button>
				<?php endforeach; ?>
			</div>
			<textarea name="template" rows="7" class="crm-notify__text" data-wa-template><?php echo esc_textarea( sojib68_crm_wa_template( $type ) ); ?></textarea>
			<div class="crm-notify__row">
				<button type="submit" class="btn btn-primary btn-sm"><?php esc_html_e( 'টেমপ্লেট সংরক্ষণ করুন', 'sojib68' ); ?></button>
				<button type="submit" name="reset" value="1" class="btn btn-outline btn-sm" onclick="return confirm('<?php echo esc_js( __( 'আগের মূল মেসেজে ফিরিয়ে নেবেন?', 'sojib68' ) ); ?>');"><?php esc_html_e( 'মূল মেসেজে ফেরত নিন', 'sojib68' ); ?></button>
			</div>
		</form>
	</details>
	<?php
}

function sojib68_crm_render_work_accounts( $work, $type ) {
	$acc        = sojib68_crm_work_accounts( $work['id'] );
	$entries    = sojib68_crm_work_cash_entries( $work['id'] );
	$categories = sojib68_crm_cash_categories();
	$money      = function ( $poysha ) {
		return '৳' . sojib68_crm_poysha_to_taka_display( $poysha );
	};
	?>
	<div class="crm-works-box crm-work-accounts">
		<h3><?php esc_html_e( 'এই কাজের হিসাব', 'sojib68' ); ?> — <?php echo esc_html( $work['work_code'] ); ?></h3>

		<div class="crm-acc-summary">
			<div><span><?php esc_html_e( 'চুক্তির মোট', 'sojib68' ); ?></span><strong><?php echo null === $acc['agreed'] ? esc_html__( 'ঠিক হয়নি', 'sojib68' ) : esc_html( $money( $acc['agreed'] ) ); ?></strong></div>
			<div><span><?php esc_html_e( 'মোট আদায়', 'sojib68' ); ?></span><strong><?php echo esc_html( $money( $acc['collected'] ) ); ?></strong></div>
			<div class="<?php echo null !== $acc['due'] && $acc['due'] > 0 ? 'is-due' : ''; ?>">
				<span><?php echo null !== $acc['due'] && $acc['due'] < 0 ? esc_html__( 'অতিরিক্ত জমা', 'sojib68' ) : esc_html__( 'বকেয়া', 'sojib68' ); ?></span>
				<strong><?php echo null === $acc['due'] ? esc_html__( 'হিসাব অসম্পূর্ণ', 'sojib68' ) : esc_html( $money( abs( $acc['due'] ) ) ); ?></strong>
			</div>
			<div><span><?php esc_html_e( 'মোট খরচ', 'sojib68' ); ?></span><strong><?php echo esc_html( $money( $acc['spent'] ) ); ?></strong></div>
			<div><span><?php esc_html_e( 'প্রফিট (চুক্তি − খরচ)', 'sojib68' ); ?></span><strong><?php echo null === $acc['profit'] ? esc_html__( 'হিসাব অসম্পূর্ণ', 'sojib68' ) : esc_html( $money( $acc['profit'] ) ); ?></strong></div>
			<div><span><?php esc_html_e( 'হাতে আছে (আদায় − খরচ)', 'sojib68' ); ?></span><strong><?php echo esc_html( $money( $acc['balance'] ) ); ?></strong></div>
		</div>

		<form method="post" action="<?php echo esc_url( home_url( '/' . SOJIB68_CRM_BASE . '/work-txn' ) ); ?>" class="crm-txn-form">
			<?php wp_nonce_field( 'sojib68_crm_work_txn', 'sojib68_crm_txn_nonce' ); ?>
			<input type="hidden" name="work_id" value="<?php echo (int) $work['id']; ?>">
			<select name="direction">
				<?php foreach ( sojib68_crm_cash_directions() as $key => $label ) : ?>
					<option value="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( 'in' === $key ? __( 'আদায় (গ্রাহক দিলেন)', 'sojib68' ) : __( 'খরচ (কাউকে দিলাম)', 'sojib68' ) ); ?></option>
				<?php endforeach; ?>
			</select>
			<input type="text" inputmode="decimal" autocomplete="off" data-money name="amount" placeholder="<?php esc_attr_e( 'টাকা', 'sojib68' ); ?>" required>
			<input type="date" name="txn_date" value="<?php echo esc_attr( current_time( 'Y-m-d' ) ); ?>">
			<input type="text" name="party" placeholder="<?php esc_attr_e( 'কার কাছ থেকে / কাকে', 'sojib68' ); ?>">
			<input type="text" name="note" placeholder="<?php esc_attr_e( 'নোট (যেমন: ভূমি অফিস ফি)', 'sojib68' ); ?>">
			<select name="category" title="<?php esc_attr_e( 'কোন ক্যাটাগরির টাকা', 'sojib68' ); ?>">
				<?php foreach ( $categories as $key => $label ) : ?>
					<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $type, $key ); ?>><?php echo esc_html( $label ); ?></option>
				<?php endforeach; ?>
			</select>
			<button type="submit" class="btn btn-primary btn-sm"><?php echo sojib68_crm_icon( 'plus', 14 ); // phpcs:ignore ?> <?php esc_html_e( 'যোগ করুন', 'sojib68' ); ?></button>
		</form>

		<?php if ( $entries ) : ?>
			<div class="crm-table-wrap">
				<table class="crm-table">
					<thead>
						<tr>
							<th><?php esc_html_e( 'তারিখ', 'sojib68' ); ?></th>
							<th><?php esc_html_e( 'ধরন', 'sojib68' ); ?></th>
							<th><?php esc_html_e( 'কার সাথে', 'sojib68' ); ?></th>
							<th><?php esc_html_e( 'টাকা', 'sojib68' ); ?></th>
							<th><?php esc_html_e( 'নোট', 'sojib68' ); ?></th>
							<th><?php esc_html_e( 'টাকার ক্যাটাগরি', 'sojib68' ); ?></th>
							<th></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $entries as $e ) : ?>
							<tr>
								<td><?php echo esc_html( mysql2date( 'd M, Y', $e['txn_date'] ) ); ?></td>
								<td><span class="crm-chip <?php echo 'in' === $e['direction'] ? 'crm-chip--confirmed' : 'crm-chip--unreachable'; ?>"><?php echo esc_html( 'in' === $e['direction'] ? __( 'আদায়', 'sojib68' ) : __( 'খরচ', 'sojib68' ) ); ?></span></td>
								<td><?php echo esc_html( $e['party'] ? $e['party'] : '—' ); ?></td>
								<td><?php echo esc_html( $money( $e['amount_poysha'] ) ); ?></td>
								<td class="crm-table__notes"><?php echo esc_html( $e['note'] ); ?></td>
								<td><?php echo esc_html( $categories[ $e['category'] ] ?? $e['category'] ); ?></td>
								<td><button type="button" class="btn btn-outline btn-sm btn-danger-outline crm-delete-cash" data-cash-id="<?php echo (int) $e['id']; ?>"><?php echo sojib68_crm_icon( 'trash', 14 ); // phpcs:ignore ?></button></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		<?php endif; ?>
	</div>
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
						<th></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $result['rows'] as $w ) : ?>
						<tr>
							<td><a href="<?php echo esc_url( sojib68_crm_work_edit_url( $w ) ); ?>"><?php echo esc_html( $w['work_code'] ); ?></a></td>
							<td><?php echo esc_html( $w['customer_name'] ? $w['customer_name'] : $w['customer_phone'] ); ?></td>
							<td><?php echo esc_html( $types[ $w['work_type'] ] ?? $w['work_type'] ); ?></td>
							<td><?php echo esc_html( $w['assigned_to'] && isset( $staff[ $w['assigned_to'] ] ) ? $staff[ $w['assigned_to'] ] : '—' ); ?></td>
							<td><span class="crm-chip"><?php echo esc_html( sojib68_crm_work_stage_label( $w ) ); ?></span></td>
							<td><?php echo esc_html( $delivery[ $w['delivery_status'] ] ?? $w['delivery_status'] ); ?></td>
							<td><?php echo esc_html( $w['file_location'] ? $w['file_location'] : '—' ); ?></td>
							<td><a class="btn btn-outline btn-sm" href="<?php echo esc_url( sojib68_crm_work_edit_url( $w ) ); ?>"><?php echo sojib68_crm_icon( 'edit', 15 ); // phpcs:ignore ?> <?php esc_html_e( 'এডিট', 'sojib68' ); ?></a></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	<?php endif; ?>
	<?php
}
