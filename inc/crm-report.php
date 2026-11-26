<?php
/**
 * CRM → "লিড রিপোর্ট": the leads that match the current filters (same filters as the সব লিড list) as a clean,
 * printable report — monthly / yearly — to hand to the company. Staff who are not admins only get their own leads.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function sojib68_crm_report_args() {
	return array(
		'status'        => isset( $_GET['status'] ) ? sanitize_key( wp_unslash( $_GET['status'] ) ) : '',
		'source'        => isset( $_GET['source'] ) ? sanitize_key( wp_unslash( $_GET['source'] ) ) : '',
		'service_type'  => isset( $_GET['service_type'] ) ? sanitize_key( wp_unslash( $_GET['service_type'] ) ) : '',
		'assigned_to'   => isset( $_GET['assigned_to'] ) ? (int) $_GET['assigned_to'] : 0,
		'q'             => isset( $_GET['q'] ) ? sanitize_text_field( wp_unslash( $_GET['q'] ) ) : '',
		'date_from'     => isset( $_GET['date_from'] ) && preg_match( '/^\d{4}-\d{2}-\d{2}$/', (string) $_GET['date_from'] ) ? sanitize_text_field( wp_unslash( $_GET['date_from'] ) ) : '',
		'date_to'       => isset( $_GET['date_to'] ) && preg_match( '/^\d{4}-\d{2}-\d{2}$/', (string) $_GET['date_to'] ) ? sanitize_text_field( wp_unslash( $_GET['date_to'] ) ) : '',
		'page'          => 1,
		'per_page'      => 5000,
		'scope_user_id' => sojib68_crm_current_user_scope(),
	);
}

/** Quick period links for the filter bar: this / last month, this / last year. */
function sojib68_crm_period_presets() {
	$now   = current_time( 'timestamp' );
	$y     = (int) gmdate( 'Y', $now );
	$m     = (int) gmdate( 'n', $now );
	$first = sprintf( '%04d-%02d-01', $y, $m );
	$pm    = 1 === $m ? 12 : $m - 1;
	$py    = 1 === $m ? $y - 1 : $y;
	$pfirst = sprintf( '%04d-%02d-01', $py, $pm );
	return array(
		'এই মাস'  => array( $first, gmdate( 'Y-m-t', strtotime( $first ) ) ),
		'গত মাস'  => array( $pfirst, gmdate( 'Y-m-t', strtotime( $pfirst ) ) ),
		'এই বছর'  => array( $y . '-01-01', $y . '-12-31' ),
		'গত বছর'  => array( ( $y - 1 ) . '-01-01', ( $y - 1 ) . '-12-31' ),
	);
}

function sojib68_crm_render_leads_report() {
	$args     = sojib68_crm_report_args();
	$result   = sojib68_crm_leads_search( $args );
	$rows     = $result['rows'];
	$statuses = sojib68_crm_lead_statuses();
	$sources  = sojib68_crm_lead_sources();
	$services = sojib68_crm_service_types();
	$staff    = sojib68_crm_assignable_users();
	$office   = function_exists( 'sojib68_checklist_office' ) ? sojib68_checklist_office() : array( 'name' => '', 'person' => '', 'phone' => '' );

	$by_status  = array();
	$by_source  = array();
	$by_service = array();
	$by_month   = array();
	foreach ( $rows as $r ) {
		$by_status[ $r['status'] ]            = ( $by_status[ $r['status'] ] ?? 0 ) + 1;
		$by_source[ $r['source'] ]            = ( $by_source[ $r['source'] ] ?? 0 ) + 1;
		$by_service[ $r['service_type'] ]     = ( $by_service[ $r['service_type'] ] ?? 0 ) + 1;
		$mk                                   = substr( (string) $r['created_at'], 0, 7 );
		$by_month[ $mk ]                      = ( $by_month[ $mk ] ?? 0 ) + 1;
	}
	ksort( $by_month );
	$total = count( $rows );
	$conf  = isset( $by_status['confirmed'] ) ? (int) $by_status['confirmed'] : 0;

	$fmt  = function ( $d ) {
		return $d ? mysql2date( 'd M, Y', $d ) : '';
	};
	$period = ( $args['date_from'] || $args['date_to'] )
		? trim( ( $args['date_from'] ? $fmt( $args['date_from'] ) : 'শুরু থেকে' ) . ' — ' . ( $args['date_to'] ? $fmt( $args['date_to'] ) : 'আজ পর্যন্ত' ) )
		: 'সব সময়';
	$filters = array();
	if ( $args['service_type'] && isset( $services[ $args['service_type'] ] ) ) { $filters[] = 'সেবা: ' . $services[ $args['service_type'] ]; }
	if ( $args['source'] && isset( $sources[ $args['source'] ] ) ) { $filters[] = 'উৎস: ' . $sources[ $args['source'] ]; }
	if ( $args['status'] && isset( $statuses[ $args['status'] ] ) ) { $filters[] = 'অবস্থা: ' . $statuses[ $args['status'] ]; }
	if ( $args['assigned_to'] && isset( $staff[ $args['assigned_to'] ] ) ) { $filters[] = 'দায়িত্বপ্রাপ্ত: ' . $staff[ $args['assigned_to'] ]; }
	if ( $args['q'] ) { $filters[] = 'খোঁজ: ' . $args['q']; }

	$back = add_query_arg( array_filter( array(
		'status' => $args['status'], 'source' => $args['source'], 'service_type' => $args['service_type'], 'assigned_to' => $args['assigned_to'],
		'q' => $args['q'], 'date_from' => $args['date_from'], 'date_to' => $args['date_to'],
	) ), home_url( '/' . SOJIB68_CRM_BASE . '/leads' ) );

	$month_names = array( '01' => 'জানুয়ারি', '02' => 'ফেব্রুয়ারি', '03' => 'মার্চ', '04' => 'এপ্রিল', '05' => 'মে', '06' => 'জুন', '07' => 'জুলাই', '08' => 'আগস্ট', '09' => 'সেপ্টেম্বর', '10' => 'অক্টোবর', '11' => 'নভেম্বর', '12' => 'ডিসেম্বর' );

	$ver = file_exists( get_theme_file_path( 'assets/css/deed-fonts.css' ) ) ? (int) filemtime( get_theme_file_path( 'assets/css/deed-fonts.css' ) ) : 1;
	header( 'Content-Type: text/html; charset=utf-8' );
	?>
<!DOCTYPE html>
<html lang="bn">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title>লিড রিপোর্ট</title>
<link rel="stylesheet" href="<?php echo esc_url( get_theme_file_uri( 'assets/css/deed-fonts.css' ) ); ?>?v=<?php echo (int) $ver; ?>">
<style>
	:root { --g: #14532d; --line: #cfd8d2; }
	* { box-sizing: border-box; }
	body { margin: 0; background: #eef1ee; color: #111; font: 15px/1.6 'DW Bangla', 'Noto Sans Bengali', 'Nirmala UI', sans-serif; }
	.bar { position: sticky; top: 0; z-index: 5; display: flex; flex-wrap: wrap; gap: 10px 16px; align-items: center; background: #fff; border-bottom: 1px solid var(--line); padding: 10px 18px; }
	.bar a, .bar button { font: inherit; }
	.bar .btn { padding: 8px 16px; border-radius: 8px; border: 1px solid var(--g); background: #fff; color: var(--g); font-weight: 600; cursor: pointer; text-decoration: none; }
	.bar .btn--p { background: var(--g); color: #fff; }
	.bar label { display: inline-flex; gap: 6px; align-items: center; cursor: pointer; }
	.sheet { max-width: 1000px; margin: 18px auto; background: #fff; padding: 28px 34px; box-shadow: 0 2px 14px rgba(0,0,0,.12); }
	.head { display: flex; justify-content: space-between; align-items: center; gap: 16px; border-bottom: 3px solid var(--g); padding-bottom: 14px; margin-bottom: 16px; position: relative; }
	.head::after { content: ""; position: absolute; left: 0; right: 0; bottom: -7px; height: 1px; background: #b84a3c; }
	.brand { display: flex; align-items: center; gap: 16px; }
	.brand__mark { height: 76px; width: auto; display: block; }
	.brand__name { font-size: 1.85rem; font-weight: 800; line-height: 1.15; color: var(--g); letter-spacing: .2px; }
	.brand__title { margin-top: 2px; font-size: .95rem; font-weight: 700; letter-spacing: .22em; text-transform: uppercase; color: #8a2b21; }
	.brand__addr { margin-top: 4px; font-size: .86rem; color: #444; }
	.head p { margin: 2px 0; font-size: .92rem; color: #444; }
	.head .ph { text-align: right; white-space: nowrap; }
	h1 { margin: 12px 0 4px; font-size: 1.5rem; }
	.meta { margin: 0 0 14px; font-size: .95rem; color: #333; }
	.meta b { color: #000; }
	.kpis { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 10px; margin: 0 0 16px; }
	.kpi { border: 1px solid var(--line); border-radius: 10px; padding: 10px 14px; }
	.kpi b { display: block; font-size: 1.7rem; line-height: 1.2; color: var(--g); }
	.kpi span { font-size: .85rem; color: #555; }
	.grid3 { display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 14px; margin: 0 0 16px; }
	table { width: 100%; border-collapse: collapse; }
	th, td { border: 1px solid var(--line); padding: 5px 8px; text-align: left; vertical-align: top; font-size: .92rem; }
	th { background: #eaf2ec; font-weight: 700; }
	td.n, th.n { text-align: right; white-space: nowrap; }
	h3 { margin: 16px 0 6px; font-size: 1.05rem; color: var(--g); break-after: avoid; page-break-after: avoid; }
	.small { font-size: .82rem; color: #666; }
	.hide-phone .c-phone, .hide-name .c-name, .hide-list .list-sec { display: none; }
	.foot { margin-top: 18px; font-size: .8rem; color: #666; border-top: 1px solid var(--line); padding-top: 8px; }
	@media print {
		body { background: #fff; }
		.bar { display: none; }
		.sheet { box-shadow: none; margin: 0; max-width: none; padding: 0; }
		tr { page-break-inside: avoid; }
		thead { display: table-header-group; }
		@page { size: A4; margin: 14mm; }
	}
</style>
</head>
<body class="hide-phone">
<div class="bar">
	<a class="btn" href="<?php echo esc_url( $back ); ?>">← লিডের তালিকা</a>
	<button class="btn btn--p" type="button" onclick="window.print()">🖨 প্রিন্ট / PDF সংরক্ষণ</button>
	<label><input type="checkbox" id="t-phone"> মোবাইল নম্বর দেখান</label>
	<label><input type="checkbox" id="t-name" checked> নাম দেখান</label>
	<label><input type="checkbox" id="t-list" checked> লিডের তালিকা দেখান (বন্ধ করলে শুধু সারাংশ)</label>
	<span class="small">ক্লায়েন্টের তথ্য: কোম্পানিকে দেওয়ার আগে নাম ও মোবাইল দেখানো দরকার কিনা ঠিক করে নিন।</span>
</div>

<div class="sheet">
	<div class="head">
		<div class="brand">
			<img class="brand__mark" src="<?php echo esc_url( get_theme_file_uri( 'assets/images/logo-mark.png' ) ); ?>" alt="">
			<div class="brand__txt">
				<div class="brand__name">Ismail Hossain Sojib</div>
				<div class="brand__title">Land &amp; IT Consultant</div>
				<div class="brand__addr">Address: City Plaza, Lift-3, Ramganj, Lakshmipur.</div>
			</div>
		</div>
		<div class="ph">
			<?php if ( ! empty( $office['phone'] ) ) : ?><p>Mobile: <b><?php echo esc_html( $office['phone'] ); ?></b></p><?php endif; ?>
			<p>রিপোর্ট তৈরি: <?php echo esc_html( mysql2date( 'd M, Y', current_time( 'mysql' ) ) ); ?></p>
		</div>
	</div>

	<h1>লিড রিপোর্ট</h1>
	<p class="meta"><b>সময়কাল:</b> <?php echo esc_html( $period ); ?><?php echo $filters ? ' &nbsp;|&nbsp; <b>ফিল্টার:</b> ' . esc_html( implode( ' · ', $filters ) ) : ''; // phpcs:ignore ?></p>

	<div class="kpis">
		<div class="kpi"><b><?php echo (int) $total; ?></b><span>মোট লিড</span></div>
		<div class="kpi"><b><?php echo (int) $conf; ?></b><span>কাজ নিশ্চিত হয়েছে</span></div>
		<div class="kpi"><b><?php echo $total ? esc_html( round( $conf * 100 / $total, 1 ) ) : 0; ?>%</b><span>নিশ্চিত হওয়ার হার</span></div>
		<div class="kpi"><b><?php echo (int) ( $by_status['cancelled'] ?? 0 ); ?></b><span>বাতিল</span></div>
	</div>

	<?php if ( count( $by_month ) > 1 ) : ?>
		<h3>মাস অনুযায়ী লিড</h3>
		<table>
			<thead><tr><th>মাস</th><th class="n">লিড</th></tr></thead>
			<tbody>
			<?php foreach ( $by_month as $mk => $cnt ) : ?>
				<tr><td><?php echo esc_html( ( $month_names[ substr( $mk, 5, 2 ) ] ?? $mk ) . ' ' . substr( $mk, 0, 4 ) ); ?></td><td class="n"><?php echo (int) $cnt; ?></td></tr>
			<?php endforeach; ?>
			<tr><th>মোট</th><th class="n"><?php echo (int) $total; ?></th></tr>
			</tbody>
		</table>
	<?php endif; ?>

	<div class="grid3">
		<div>
			<h3>অবস্থা অনুযায়ী</h3>
			<table><thead><tr><th>অবস্থা</th><th class="n">লিড</th></tr></thead><tbody>
			<?php foreach ( $statuses as $k => $label ) : if ( empty( $by_status[ $k ] ) ) { continue; } ?>
				<tr><td><?php echo esc_html( $label ); ?></td><td class="n"><?php echo (int) $by_status[ $k ]; ?></td></tr>
			<?php endforeach; ?>
			</tbody></table>
		</div>
		<div>
			<h3>উৎস অনুযায়ী</h3>
			<table><thead><tr><th>উৎস</th><th class="n">লিড</th></tr></thead><tbody>
			<?php foreach ( $sources as $k => $label ) : if ( empty( $by_source[ $k ] ) ) { continue; } ?>
				<tr><td><?php echo esc_html( $label ); ?></td><td class="n"><?php echo (int) $by_source[ $k ]; ?></td></tr>
			<?php endforeach; ?>
			</tbody></table>
		</div>
		<div>
			<h3>সেবা অনুযায়ী</h3>
			<table><thead><tr><th>সেবা</th><th class="n">লিড</th></tr></thead><tbody>
			<?php foreach ( $services as $k => $label ) : if ( empty( $by_service[ $k ] ) ) { continue; } ?>
				<tr><td><?php echo esc_html( $label ); ?></td><td class="n"><?php echo (int) $by_service[ $k ]; ?></td></tr>
			<?php endforeach; ?>
			</tbody></table>
		</div>
	</div>

	<div class="list-sec">
		<h3>লিডের তালিকা (<?php echo (int) $total; ?>টি)</h3>
		<?php if ( ! $rows ) : ?>
			<p class="small">এই ফিল্টারে কোনো লিড পাওয়া যায়নি।</p>
		<?php else : ?>
			<table>
				<thead><tr><th>#</th><th>Lead ID</th><th>তারিখ</th><th class="c-name">নাম</th><th class="c-phone">মোবাইল</th><th>উৎস</th><th>সেবা</th><th>দায়িত্বপ্রাপ্ত</th><th>অবস্থা</th></tr></thead>
				<tbody>
				<?php foreach ( $rows as $i => $r ) : ?>
					<tr>
						<td class="n"><?php echo (int) ( $i + 1 ); ?></td>
						<td><?php echo esc_html( $r['lead_code'] ); ?></td>
						<td><?php echo esc_html( $fmt( $r['created_at'] ) ); ?></td>
						<td class="c-name"><?php echo esc_html( $r['name'] ); ?></td>
						<td class="c-phone"><?php echo esc_html( $r['phone'] ); ?></td>
						<td><?php echo esc_html( $sources[ $r['source'] ] ?? $r['source'] ); ?></td>
						<td><?php echo esc_html( $services[ $r['service_type'] ] ?? $r['service_type'] ); ?></td>
						<td><?php echo esc_html( ! empty( $r['assigned_to'] ) ? ( $staff[ (int) $r['assigned_to'] ] ?? '' ) : '—' ); ?></td>
						<td><?php echo esc_html( $statuses[ $r['status'] ] ?? $r['status'] ); ?></td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		<?php endif; ?>
	</div>

</div>
<script>
( function () {
	var b = document.body, map = { 't-phone': 'hide-phone', 't-name': 'hide-name', 't-list': 'hide-list' };
	Object.keys( map ).forEach( function ( id ) {
		var el = document.getElementById( id ); if ( ! el ) { return; }
		function sync() { b.classList.toggle( map[ id ], ! el.checked ); }
		el.addEventListener( 'change', sync ); sync();
	} );
}() );
</script>
</body>
</html>
	<?php
}
