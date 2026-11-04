<?php
/**
 * জমির একক রূপান্তর — a small calculator on the homepage ("জমির হিসাব" button on the
 * জমি পরিমাপ card). Everything is worked out in the visitor's browser; nothing is stored.
 *
 * The unit sizes live in sojib68_land_units() (square feet per unit) so they are easy
 * to correct in one place. They are an approximate guide, not an official measurement.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** The service card that shows the "জমির হিসাব" button (key from sojib68_get_services()). */
define( 'SOJIB68_LAND_CALC_SERVICE', 'measurement' );

/**
 * Units, each in square feet.
 *  - শতাংশ: 435.60 sq ft (the owner's local measure)
 *  - কাঠা: Dhaka measure, 720 sq ft; ছটাক = 1/20 কাঠা; বিঘা = 20 কাঠা
 *  - একর = 100 শতাংশ
 */
function sojib68_land_units() {
	$units = array(
		'shotangsho' => array( 'label' => 'শতাংশ', 'sqft' => 435.60 ),
		'katha'      => array( 'label' => 'কাঠা (ঢাকা)', 'sqft' => 720 ),
		'chotak'     => array( 'label' => 'ছটাক (ঢাকা)', 'sqft' => 36 ),
		'bigha'      => array( 'label' => 'বিঘা (ঢাকা)', 'sqft' => 14400 ),
		'acre'       => array( 'label' => 'একর', 'sqft' => 43560 ),
		'sqft'       => array( 'label' => 'বর্গফুট', 'sqft' => 1 ),
		'sqm'        => array( 'label' => 'বর্গমিটার', 'sqft' => 10.7639104 ),
	);
	return apply_filters( 'sojib68_land_units', $units );
}

/**
 * Old local measures, each in শতাংশ — the owner's area values:
 *  1 আনা = 20 গণ্ডা, 1 গণ্ডা = 4 কড়া, 1 কড়া = 3 ক্রান্তি,
 *  1 গণ্ডা = 6 শতাংশ, 1 কড়া = 1.5 শতাংশ (so 1 ক্রান্তি = 0.5, 1 আনা = 120 শতাংশ).
 * 16 আনা is the whole when a share of a property is meant.
 */
function sojib68_land_old_units() {
	$units = array(
		'anna'   => array( 'label' => 'আনা', 'shatangsho' => 120 ),
		'ganda'  => array( 'label' => 'গণ্ডা', 'shatangsho' => 6 ),
		'kora'   => array( 'label' => 'কড়া', 'shatangsho' => 1.5 ),
		'kranti' => array( 'label' => 'ক্রান্তি', 'shatangsho' => 0.5 ),
	);
	return apply_filters( 'sojib68_land_old_units', $units );
}

function sojib68_land_calc_enqueue() {
	if ( ! is_front_page() ) {
		return;
	}
	$css = get_theme_file_path( 'assets/css/land-calc.css' );
	$js  = get_theme_file_path( 'assets/js/land-calc.js' );
	wp_enqueue_style( 'sojib68-land-calc', get_theme_file_uri( 'assets/css/land-calc.css' ), array(), file_exists( $css ) ? filemtime( $css ) : '1.0.0' );
	wp_enqueue_script( 'sojib68-land-calc', get_theme_file_uri( 'assets/js/land-calc.js' ), array(), file_exists( $js ) ? filemtime( $js ) : '1.0.0', true );
}
add_action( 'wp_enqueue_scripts', 'sojib68_land_calc_enqueue', 20 );

/** The popup and its unit table, rendered once in the footer of the homepage. */
function sojib68_land_calc_modal() {
	if ( ! is_front_page() ) {
		return;
	}
	$units  = sojib68_land_units();
	$office = function_exists( 'sojib68_checklist_office' ) ? sojib68_checklist_office() : array( 'name' => '', 'person' => '', 'phone' => '', 'channels' => '' );
	?>
	<script type="application/json" id="lc-old"><?php echo wp_json_encode( sojib68_land_old_units(), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE ); ?></script>
	<script type="application/json" id="lc-data"><?php echo wp_json_encode( $units, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE ); ?></script>

	<div class="lc-modal" data-lc-modal hidden>
		<div class="lc-modal__backdrop" data-lc-close></div>
		<div class="lc-dialog" role="dialog" aria-modal="true" aria-labelledby="lc-title" tabindex="-1">
			<header class="lc-dialog__head">
				<div>
					<p class="lc-dialog__eyebrow"><?php esc_html_e( 'জমি পরিমাপ', 'sojib68' ); ?></p>
					<h2 id="lc-title"><?php esc_html_e( 'জমির হিসাব', 'sojib68' ); ?></h2>
				</div>
				<button type="button" class="lc-dialog__close" data-lc-close aria-label="<?php esc_attr_e( 'বন্ধ করুন', 'sojib68' ); ?>">&times;</button>
			</header>

			<div class="lc-tabs" role="tablist">
				<button type="button" class="lc-tab is-on" role="tab" aria-selected="true" data-lc-tab="convert"><?php esc_html_e( 'একক রূপান্তর', 'sojib68' ); ?></button>
				<button type="button" class="lc-tab" role="tab" aria-selected="false" data-lc-tab="area"><?php esc_html_e( 'বাহু থেকে ক্ষেত্রফল', 'sojib68' ); ?></button>
				<button type="button" class="lc-tab" role="tab" aria-selected="false" data-lc-tab="old"><?php esc_html_e( 'পুরোনো মাপ', 'sojib68' ); ?></button>
			</div>

			<div class="lc-dialog__body">

				<div data-lc-panel="convert">
					<p class="lc-hint"><?php esc_html_e( 'জমির পরিমাণ লিখে এককটি বাছুন, সব এককে হিসাব সঙ্গে সঙ্গে দেখা যাবে।', 'sojib68' ); ?></p>

					<div class="lc-form">
						<div class="lc-field lc-field--num">
							<label for="lc-value"><?php esc_html_e( 'পরিমাণ', 'sojib68' ); ?></label>
							<input type="text" id="lc-value" inputmode="decimal" autocomplete="off" placeholder="<?php esc_attr_e( 'যেমন: 5.5', 'sojib68' ); ?>" data-lc-value>
						</div>
						<div class="lc-field">
							<label for="lc-unit"><?php esc_html_e( 'একক', 'sojib68' ); ?></label>
							<select id="lc-unit" data-lc-unit></select>
						</div>
					</div>
					<p class="lc-msg" data-lc-msg role="alert" hidden></p>

					<ul class="lc-results" data-lc-results aria-live="polite"></ul>
				</div>

				<div data-lc-panel="area" hidden>
					<p class="lc-hint"><?php esc_html_e( 'আপনার জমির আকৃতির বাটনটি বাছুন, তারপর ঘরগুলোতে মাপ লিখুন (ফুট বা মিটারে)।', 'sojib68' ); ?></p>

					<div class="lc-shapes" data-lc-shapes></div>
					<p class="lc-shapehint" data-lc-shapehint></p>

					<div class="lc-area-grid">
						<div class="lc-diagram" data-lc-diagram aria-hidden="true"></div>
						<div>
							<div class="lc-field lc-field--unit">
								<label for="lc-lunit"><?php esc_html_e( 'মাপের একক', 'sojib68' ); ?></label>
								<select id="lc-lunit" data-lc-lunit>
									<option value="ft"><?php esc_html_e( 'ফুট', 'sojib68' ); ?></option>
									<option value="m"><?php esc_html_e( 'মিটার', 'sojib68' ); ?></option>
								</select>
							</div>
							<div class="lc-sides" data-lc-sides></div>
						</div>
					</div>
					<p class="lc-msg" data-lc-amsg role="alert" hidden></p>

					<ul class="lc-results" data-lc-aresults aria-live="polite"></ul>
					<p class="lc-note lc-note--quad" data-lc-quadnote hidden><?php esc_html_e( 'কোণাকুণি মাপ হলো জমির ভেতর দিয়ে ক কোণা থেকে গ কোণায় সোজা টানা মাপ (ছবির বিন্দু-বিন্দু দাগ)। শুধু চার দিকের মাপ দিয়ে ক্ষেত্রফল ঠিক হয় না, তাই এটিও লাগে।', 'sojib68' ); ?></p>
				</div>

				<div data-lc-panel="old" hidden>
					<p class="lc-hint"><?php esc_html_e( 'আনা, গণ্ডা, কড়া, ক্রান্তির মাপ শতাংশে বদলে নিন। আপনার প্রয়োজনের বাটনটি বাছুন।', 'sojib68' ); ?></p>
					<div class="lc-shapes" data-lc-omodes></div>
					<p class="lc-shapehint" data-lc-omodehint></p>
					<div class="lc-sides lc-sides--old" data-lc-ofields></div>
					<p class="lc-msg" data-lc-omsg role="alert" hidden></p>
					<ul class="lc-results" data-lc-oresults aria-live="polite"></ul>
					<p class="lc-note lc-note--quad" data-lc-otable></p>
				</div>

				<p class="lc-note">
					<?php
					echo esc_html(
						sprintf(
							/* translators: %s: square feet in one shotangsho */
							__( 'হিসাবের ভিত্তি: 1 শতাংশ = %s বর্গফুট; ঢাকার হিসাবে 1 কাঠা = 720 বর্গফুট (20 ছটাক), 1 বিঘা = 20 কাঠা; 1 একর = 100 শতাংশ। এটি আনুমানিক হিসাব, সরকারি পরিমাপ বা দলিলের বিকল্প নয়।', 'sojib68' ),
							number_format_i18n( 435.6, 2 )
						)
					);
					?>
				</p>
			</div>

			<footer class="lc-dialog__foot">
				<button type="button" class="btn btn-primary lc-print" data-lc-print disabled><?php esc_html_e( 'প্রিন্ট / PDF সংরক্ষণ', 'sojib68' ); ?></button>
				<p class="lc-hint lc-hint--small"><?php esc_html_e( 'হিসাব বের হলে প্রিন্ট করা যাবে। PDF-এর জন্য প্রিন্টের জানালায় "Save as PDF" বেছে নিন।', 'sojib68' ); ?></p>
			</footer>
		</div>

		<!-- Printed slip (hidden on screen, filled by land-calc.js) -->
		<div class="lc-sheet" data-lc-sheet aria-hidden="true">
			<header class="lc-sheet__head">
				<div>
					<p class="lc-sheet__org"><?php echo esc_html( $office['name'] ); ?></p>
					<p class="lc-sheet__role"><?php echo esc_html( $office['person'] ); ?></p>
				</div>
				<?php if ( '' !== $office['phone'] ) : ?>
					<div class="lc-sheet__phone">
						<span class="lc-sheet__phone-label"><?php esc_html_e( 'যোগাযোগ', 'sojib68' ); ?></span>
						<strong><?php echo esc_html( $office['phone'] ); ?></strong>
						<?php if ( '' !== $office['channels'] ) : ?>
							<span class="lc-sheet__channels"><?php echo esc_html( $office['channels'] ); ?></span>
						<?php endif; ?>
					</div>
				<?php endif; ?>
			</header>

			<h2 class="lc-sheet__title" data-sheet-title></h2>
			<p class="lc-sheet__date"><?php esc_html_e( 'তারিখ:', 'sojib68' ); ?> <span data-sheet-date></span></p>

			<div class="lc-sheet__cols">
				<div>
					<h3><?php esc_html_e( 'দেওয়া তথ্য', 'sojib68' ); ?></h3>
					<ul class="lc-sheet__inputs" data-sheet-inputs></ul>
				</div>
				<div class="lc-sheet__diagram" data-sheet-diagram></div>
			</div>

			<h3><?php esc_html_e( 'হিসাবের ফল', 'sojib68' ); ?></h3>
			<table class="lc-sheet__table"><tbody data-sheet-results></tbody></table>

			<p class="lc-sheet__foot"><?php esc_html_e( 'ভিত্তি: 1 শতাংশ = 435.60 বর্গফুট; ঢাকার হিসাবে 1 কাঠা = 720 বর্গফুট (20 ছটাক), 1 বিঘা = 20 কাঠা; 1 একর = 100 শতাংশ। এটি আনুমানিক হিসাব, সরকারি পরিমাপ বা দলিলের বিকল্প নয়।', 'sojib68' ); ?></p>
		</div>
	</div>
	<?php
}
add_action( 'wp_footer', 'sojib68_land_calc_modal', 6 );
