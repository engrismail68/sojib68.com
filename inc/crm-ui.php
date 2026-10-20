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
		'grid'    => '<rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/>',
		'users'   => '<circle cx="9" cy="8" r="3.2"/><path d="M3 20c0-3.3 2.7-6 6-6s6 2.7 6 6"/><circle cx="17" cy="9" r="2.6"/><path d="M15 14.2c2.6.4 4.5 2.6 4.5 5.3"/>',
		'plus'    => '<path d="M12 5v14M5 12h14"/>',
		'phone'   => '<path d="M5 4h3l2 5-2.5 1.5a11 11 0 0 0 5 5L14 13l5 2v3a2 2 0 0 1-2 2C9.5 20 4 14.5 4 7a2 2 0 0 1 1-2z"/>',
		'logout'  => '<path d="M9 5H5a1 1 0 0 0-1 1v12a1 1 0 0 0 1 1h4M16 8l4 4-4 4M20 12H9"/>',
		'trash'   => '<path d="M4 7h16M9 7V4h6v3M6 7l1 13h10l1-13"/>',
		'check'   => '<path d="M20 6L9 17l-5-5"/>',
		'filter'  => '<path d="M4 5h16l-6 8v6l-4-2v-4z"/>',
		'clock'   => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 3"/>',
	);

	if ( ! isset( $icons[ $name ] ) ) {
		$name = 'grid';
	}

	return '<svg width="' . (int) $size . '" height="' . (int) $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $icons[ $name ] . '</svg>';
}

function sojib68_crm_render_dashboard_home() {
	$stats     = sojib68_crm_dashboard_stats();
	$statuses  = sojib68_crm_lead_statuses();
	$sources   = sojib68_crm_lead_sources();
	?>
	<div class="crm-stats-grid">
		<div class="crm-stat-tile">
			<span class="crm-stat-tile__num"><?php echo esc_html( number_format_i18n( $stats['total'] ) ); ?></span>
			<span class="crm-stat-tile__label"><?php esc_html_e( 'সর্বমোট লিড', 'sojib68' ); ?></span>
		</div>
		<div class="crm-stat-tile">
			<span class="crm-stat-tile__num"><?php echo esc_html( number_format_i18n( $stats['today'] ) ); ?></span>
			<span class="crm-stat-tile__label"><?php esc_html_e( 'আজকের নতুন লিড', 'sojib68' ); ?></span>
		</div>
		<div class="crm-stat-tile">
			<span class="crm-stat-tile__num"><?php echo esc_html( number_format_i18n( $stats['this_month'] ) ); ?></span>
			<span class="crm-stat-tile__label"><?php esc_html_e( 'এই মাসের লিড', 'sojib68' ); ?></span>
		</div>
		<div class="crm-stat-tile crm-stat-tile--accent">
			<span class="crm-stat-tile__num"><?php echo esc_html( number_format_i18n( $stats['by_status']['follow_up'] ) ); ?></span>
			<span class="crm-stat-tile__label"><?php esc_html_e( 'ফলোআপ প্রয়োজন', 'sojib68' ); ?></span>
		</div>
	</div>

	<div class="crm-breakdown-grid">
		<div class="crm-breakdown">
			<h3><?php esc_html_e( 'অবস্থা অনুযায়ী', 'sojib68' ); ?></h3>
			<ul>
				<?php foreach ( $statuses as $key => $label ) : ?>
					<li>
						<a href="<?php echo esc_url( add_query_arg( 'status', $key, home_url( '/' . SOJIB68_CRM_BASE . '/leads' ) ) ); ?>">
							<span class="crm-chip crm-chip--<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></span>
							<strong><?php echo esc_html( number_format_i18n( $stats['by_status'][ $key ] ) ); ?></strong>
						</a>
					</li>
				<?php endforeach; ?>
			</ul>
		</div>
		<div class="crm-breakdown">
			<h3><?php esc_html_e( 'উৎস অনুযায়ী', 'sojib68' ); ?></h3>
			<ul>
				<?php foreach ( $sources as $key => $label ) : ?>
					<li>
						<a href="<?php echo esc_url( add_query_arg( 'source', $key, home_url( '/' . SOJIB68_CRM_BASE . '/leads' ) ) ); ?>">
							<span><?php echo esc_html( $label ); ?></span>
							<strong><?php echo esc_html( number_format_i18n( $stats['by_source'][ $key ] ) ); ?></strong>
						</a>
					</li>
				<?php endforeach; ?>
			</ul>
		</div>
	</div>

	<div class="crm-inquiry-note">
		<p>
			<?php esc_html_e( 'ফেসবুক বিজ্ঞাপনের লিংক হিসেবে এই ঠিকানাটি ব্যবহার করুন — ফর্ম পূরণ করলেই লিড এখানে চলে আসবে:', 'sojib68' ); ?>
		</p>
		<code><?php echo esc_html( home_url( '/' . SOJIB68_CRM_BASE . '/inquiry' ) ); ?></code>
	</div>
	<?php
}

function sojib68_crm_render_leads_list() {
	$statuses = sojib68_crm_lead_statuses();
	$sources  = sojib68_crm_lead_sources();

	$args = array(
		'status' => isset( $_GET['status'] ) ? sanitize_key( wp_unslash( $_GET['status'] ) ) : '',
		'source' => isset( $_GET['source'] ) ? sanitize_key( wp_unslash( $_GET['source'] ) ) : '',
		'q'      => isset( $_GET['q'] ) ? sanitize_text_field( wp_unslash( $_GET['q'] ) ) : '',
		'page'   => isset( $_GET['paged'] ) ? (int) $_GET['paged'] : 1,
	);

	$result    = sojib68_crm_leads_search( $args );
	$base_url  = home_url( '/' . SOJIB68_CRM_BASE . '/leads' );
	$total_pg  = (int) ceil( $result['total'] / max( 1, $result['per_page'] ) );
	?>
	<form method="get" action="<?php echo esc_url( $base_url ); ?>" class="crm-filters">
		<input type="search" name="q" placeholder="<?php esc_attr_e( 'নাম, মোবাইল বা ইমেইল দিয়ে খুঁজুন', 'sojib68' ); ?>" value="<?php echo esc_attr( $args['q'] ); ?>">
		<select name="status">
			<option value=""><?php esc_html_e( 'সব অবস্থা', 'sojib68' ); ?></option>
			<?php foreach ( $statuses as $key => $label ) : ?>
				<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $args['status'], $key ); ?>><?php echo esc_html( $label ); ?></option>
			<?php endforeach; ?>
		</select>
		<select name="source">
			<option value=""><?php esc_html_e( 'সব উৎস', 'sojib68' ); ?></option>
			<?php foreach ( $sources as $key => $label ) : ?>
				<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $args['source'], $key ); ?>><?php echo esc_html( $label ); ?></option>
			<?php endforeach; ?>
		</select>
		<button type="submit" class="btn btn-outline btn-sm"><?php echo sojib68_crm_icon( 'filter', 16 ); // phpcs:ignore ?> <?php esc_html_e( 'ফিল্টার', 'sojib68' ); ?></button>
	</form>

	<?php if ( empty( $result['rows'] ) ) : ?>
		<p class="crm-empty"><?php esc_html_e( 'কোনো লিড পাওয়া যায়নি।', 'sojib68' ); ?></p>
	<?php else : ?>
		<div class="crm-table-wrap">
			<table class="crm-table">
				<thead>
					<tr>
						<th><?php esc_html_e( 'নাম', 'sojib68' ); ?></th>
						<th><?php esc_html_e( 'মোবাইল', 'sojib68' ); ?></th>
						<th><?php esc_html_e( 'উৎস', 'sojib68' ); ?></th>
						<th><?php esc_html_e( 'অবস্থা', 'sojib68' ); ?></th>
						<th><?php esc_html_e( 'তারিখ', 'sojib68' ); ?></th>
						<th></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $result['rows'] as $row ) : ?>
						<tr>
							<td>
								<a href="<?php echo esc_url( home_url( '/' . SOJIB68_CRM_BASE . '/entry-new?id=' . (int) $row['id'] ) ); ?>"><?php echo esc_html( $row['name'] ? $row['name'] : __( '(নাম নেই)', 'sojib68' ) ); ?></a>
								<?php if ( $row['notes'] ) : ?><div class="crm-table__notes"><?php echo esc_html( wp_trim_words( $row['notes'], 10 ) ); ?></div><?php endif; ?>
							</td>
							<td><?php echo $row['phone'] ? '<a href="tel:' . esc_attr( $row['phone'] ) . '">' . esc_html( $row['phone'] ) . '</a>' : '&mdash;'; ?></td>
							<td><?php echo esc_html( isset( $sources[ $row['source'] ] ) ? $sources[ $row['source'] ] : $row['source'] ); ?></td>
							<td>
								<select class="crm-status-select" data-lead-id="<?php echo (int) $row['id']; ?>">
									<?php foreach ( $statuses as $key => $label ) : ?>
										<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $row['status'], $key ); ?>><?php echo esc_html( $label ); ?></option>
									<?php endforeach; ?>
								</select>
							</td>
							<td><?php echo esc_html( mysql2date( 'd M, Y', $row['created_at'] ) ); ?></td>
							<td>
								<?php if ( current_user_can( 'manage_options' ) ) : ?>
									<button type="button" class="btn btn-outline btn-sm btn-danger-outline crm-delete-lead" data-lead-id="<?php echo (int) $row['id']; ?>"><?php echo sojib68_crm_icon( 'trash', 15 ); // phpcs:ignore ?></button>
								<?php endif; ?>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>

		<?php if ( $total_pg > 1 ) : ?>
			<nav class="crm-pagination">
				<?php for ( $p = 1; $p <= $total_pg; $p++ ) : ?>
					<a href="<?php echo esc_url( add_query_arg( 'paged', $p, $base_url . '?' . http_build_query( array_filter( array( 'status' => $args['status'], 'source' => $args['source'], 'q' => $args['q'] ) ) ) ) ); ?>" class="<?php echo $p === $result['page'] ? 'is-active' : ''; ?>"><?php echo esc_html( $p ); ?></a>
				<?php endfor; ?>
			</nav>
		<?php endif; ?>
	<?php endif; ?>
	<?php
}

function sojib68_crm_render_entry_form() {
	$id   = isset( $_GET['id'] ) ? (int) $_GET['id'] : 0;
	$lead = $id ? sojib68_crm_lead_get( $id ) : null;

	if ( $id && ! $lead ) {
		echo '<p class="crm-empty">' . esc_html__( 'লিড পাওয়া যায়নি।', 'sojib68' ) . '</p>';
		return;
	}

	$sources  = sojib68_crm_lead_sources();
	$statuses = sojib68_crm_lead_statuses();
	$v        = function ( $key, $default = '' ) use ( $lead ) {
		return $lead ? esc_attr( $lead[ $key ] ) : esc_attr( $default );
	};
	?>
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
		<div class="crm-field">
			<label><?php esc_html_e( 'উৎস', 'sojib68' ); ?></label>
			<select name="source">
				<?php foreach ( $sources as $key => $label ) : ?>
					<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $lead ? $lead['source'] : 'walkin', $key ); ?>><?php echo esc_html( $label ); ?></option>
				<?php endforeach; ?>
			</select>
		</div>
		<div class="crm-field">
			<label><?php esc_html_e( 'অবস্থা', 'sojib68' ); ?></label>
			<select name="status">
				<?php foreach ( $statuses as $key => $label ) : ?>
					<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $lead ? $lead['status'] : 'new', $key ); ?>><?php echo esc_html( $label ); ?></option>
				<?php endforeach; ?>
			</select>
		</div>
		<div class="crm-field crm-field--full">
			<label><?php esc_html_e( 'নোট', 'sojib68' ); ?></label>
			<textarea name="notes" rows="4"><?php echo $lead ? esc_textarea( $lead['notes'] ) : ''; ?></textarea>
		</div>

		<button type="submit" class="btn btn-primary"><?php esc_html_e( 'সংরক্ষণ করুন', 'sojib68' ); ?></button>
	</form>
	<?php
}
