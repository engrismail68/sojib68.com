<?php
/**
 * Template for /checklist/<service>/ — "কী কী কাগজ লাগে".
 *
 * The list comes from the wp-admin page "কাগজপত্র তালিকা" (inc/checklists.php).
 * On screen: tick what applies. On paper: only the ticked items, numbered, with a
 * box to tick off — see assets/css/checklist.css (@media print) and assets/js/checklist.js.
 */

get_header();

$key      = sojib68_checklist_request_key();
$services = sojib68_get_services();
$lists    = sojib68_get_checklists();
$service  = $services[ $key ];
$items    = $lists[ $key ];

$office = sojib68_checklist_office(); // letterhead on the printed slip (wp-admin → কাগজপত্র তালিকা)
?>

<div class="checklist-page">

	<section class="ck-head">
		<div class="site-container">
			<nav class="ck-crumbs" aria-label="<?php esc_attr_e( 'পথ', 'sojib68' ); ?>">
				<a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'হোম', 'sojib68' ); ?></a>
				<span aria-hidden="true">›</span>
				<a href="<?php echo esc_url( home_url( '/#services' ) ); ?>"><?php esc_html_e( 'সেবাসমূহ', 'sojib68' ); ?></a>
				<span aria-hidden="true">›</span>
				<span aria-current="page"><?php echo esc_html( $service['title'] ); ?></span>
			</nav>
			<p class="eyebrow"><?php esc_html_e( 'কী কী কাগজ লাগে', 'sojib68' ); ?></p>
			<h1><?php echo esc_html( $service['title'] ); ?></h1>
			<p class="ck-lead"><?php esc_html_e( 'যে কাগজগুলো প্রযোজ্য শুধু সেগুলো টিক দিন, তারপর প্রিন্ট করুন বা PDF হিসেবে সংরক্ষণ করুন।', 'sojib68' ); ?></p>
		</div>
	</section>

	<section class="ck-main">
		<div class="site-container ck-grid">

			<div class="ck-panel" id="ck-screen">
				<div class="ck-toolbar">
					<strong class="ck-count" data-ck-count aria-live="polite"></strong>
					<span class="ck-toolbar__btns">
						<button type="button" class="ck-link" data-ck-all><?php esc_html_e( 'সব বাছাই', 'sojib68' ); ?></button>
						<button type="button" class="ck-link" data-ck-none><?php esc_html_e( 'সব বাদ', 'sojib68' ); ?></button>
					</span>
				</div>

				<ul class="ck-list" data-ck-list>
					<?php
					$group = null;
					foreach ( $items as $i => $item ) :
						if ( $item['group'] !== $group ) :
							$group = $item['group'];
							if ( '' !== $group ) :
								?>
								<li class="ck-group" data-ck-group="<?php echo esc_attr( $group ); ?>">
									<span><?php echo esc_html( $group ); ?></span>
									<button type="button" class="ck-link" data-ck-group-toggle><?php esc_html_e( 'এই অংশের সব বাছাই', 'sojib68' ); ?></button>
								</li>
								<?php
							endif;
						endif;
						?>
						<li class="ck-item">
							<label for="ck-item-<?php echo (int) $i; ?>">
								<input type="checkbox" id="ck-item-<?php echo (int) $i; ?>"
									data-ck-item
									data-text="<?php echo esc_attr( $item['text'] ); ?>"
									data-note="<?php echo esc_attr( $item['note'] ); ?>"
									data-group="<?php echo esc_attr( $item['group'] ); ?>">
								<span class="ck-item__box" aria-hidden="true"></span>
								<span class="ck-item__text">
									<?php echo esc_html( $item['text'] ); ?>
									<?php if ( '' !== $item['note'] ) : ?>
										<small><?php echo esc_html( $item['note'] ); ?></small>
									<?php endif; ?>
								</span>
							</label>
						</li>
					<?php endforeach; ?>
				</ul>
			</div>

			<aside class="ck-side">
				<div class="ck-card">
					<h2><?php esc_html_e( 'প্রিন্টে যা থাকবে', 'sojib68' ); ?></h2>
					<p class="ck-hint"><?php esc_html_e( 'নিচের ঘরগুলো ঐচ্ছিক। এখানে লেখা তথ্য কোথাও জমা হয় না, শুধু প্রিন্টে আসে।', 'sojib68' ); ?></p>

					<div class="ck-field">
						<label for="ck-name"><?php esc_html_e( 'নাম', 'sojib68' ); ?></label>
						<input type="text" id="ck-name" autocomplete="off" data-ck-name>
					</div>
					<div class="ck-field">
						<label for="ck-mobile"><?php esc_html_e( 'মোবাইল নম্বর', 'sojib68' ); ?></label>
						<input type="text" id="ck-mobile" inputmode="tel" autocomplete="off" data-ck-mobile>
					</div>
					<div class="ck-field">
						<label for="ck-note"><?php esc_html_e( 'অতিরিক্ত নির্দেশনা', 'sojib68' ); ?></label>
						<textarea id="ck-note" rows="3" data-ck-note></textarea>
					</div>

					<button type="button" class="btn btn-primary ck-print" data-ck-print><?php esc_html_e( 'প্রিন্ট / PDF সংরক্ষণ', 'sojib68' ); ?></button>
					<p class="ck-msg" data-ck-msg role="alert" hidden></p>
					<p class="ck-hint ck-hint--small"><?php esc_html_e( 'PDF চাইলে প্রিন্টের জানালায় "Save as PDF" / "PDF হিসেবে সংরক্ষণ" বেছে নিন।', 'sojib68' ); ?></p>
				</div>

				<?php $others = array_diff_key( $lists, array( $key => 1 ) ); ?>
				<?php if ( $others ) : ?>
					<div class="ck-card ck-card--plain">
						<h2><?php esc_html_e( 'অন্য সেবার তালিকা', 'sojib68' ); ?></h2>
						<ul class="ck-others">
							<?php foreach ( $others as $other_key => $other_items ) : ?>
								<li><a href="<?php echo esc_url( sojib68_checklist_url( $other_key ) ); ?>"><?php echo esc_html( $services[ $other_key ]['title'] ); ?> <span aria-hidden="true">→</span></a></li>
							<?php endforeach; ?>
						</ul>
					</div>
				<?php endif; ?>

				<a href="<?php echo esc_url( home_url( '/#contact' ) ); ?>" class="ck-contact"><?php esc_html_e( 'আরও জানতে যোগাযোগ করুন', 'sojib68' ); ?> <span aria-hidden="true">→</span></a>
			</aside>
		</div>
	</section>

	<!-- Printed sheet (hidden on screen, filled from the ticked items by checklist.js) -->
	<div class="ck-sheet" data-ck-sheet aria-hidden="true">
		<header class="ck-sheet__head">
			<div class="ck-sheet__brand">
				<p class="ck-sheet__org"><?php echo esc_html( $office['name'] ); ?></p>
				<p class="ck-sheet__role"><?php echo esc_html( $office['person'] ); ?></p>
			</div>
			<?php if ( '' !== $office['phone'] ) : ?>
				<div class="ck-sheet__phone">
					<span class="ck-sheet__phone-label"><?php esc_html_e( 'যোগাযোগ', 'sojib68' ); ?></span>
					<strong><?php echo esc_html( $office['phone'] ); ?></strong>
					<?php if ( '' !== $office['channels'] ) : ?>
						<span class="ck-sheet__channels"><?php echo esc_html( $office['channels'] ); ?></span>
					<?php endif; ?>
				</div>
			<?php endif; ?>
		</header>

		<h2 class="ck-sheet__title"><?php echo esc_html( $service['title'] ); ?> — <?php esc_html_e( 'প্রয়োজনীয় কাগজপত্র', 'sojib68' ); ?></h2>

		<dl class="ck-sheet__meta">
			<div><dt><?php esc_html_e( 'তারিখ', 'sojib68' ); ?></dt><dd data-sheet-date></dd></div>
			<div data-sheet-name-row hidden><dt><?php esc_html_e( 'নাম', 'sojib68' ); ?></dt><dd data-sheet-name></dd></div>
			<div data-sheet-mobile-row hidden><dt><?php esc_html_e( 'মোবাইল', 'sojib68' ); ?></dt><dd data-sheet-mobile></dd></div>
		</dl>

		<ol class="ck-sheet__list" data-sheet-list></ol>

		<div class="ck-sheet__note" data-sheet-note-row hidden>
			<strong><?php esc_html_e( 'অতিরিক্ত নির্দেশনা', 'sojib68' ); ?></strong>
			<p data-sheet-note></p>
		</div>

		<footer class="ck-sheet__foot">
			<p><?php esc_html_e( 'এটি একটি সাধারণ সহায়ক তালিকা। বিষয় ও পরিস্থিতিভেদে সংশ্লিষ্ট অফিসের নির্দেশনা অনুযায়ী কাগজপত্র বদলাতে পারে।', 'sojib68' ); ?></p>
		</footer>
	</div>

</div>

<?php
get_footer();
