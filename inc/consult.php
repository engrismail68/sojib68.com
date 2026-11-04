<?php
/**
 * "ভূমিবিষয়ক পরামর্শ" card: a small form that writes the visitor's message and opens
 * it in WhatsApp (to SOJIB68_WHATSAPP), ready to send. Nothing is stored or sent by
 * the site itself.
 *
 * The topic buttons come from wp-admin → কাগজপত্র তালিকা → "পরামর্শের বিষয়"
 * (one per line). With no topics listed, the form simply has no topic buttons.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** The service card that opens the form (key from sojib68_get_services()). */
define( 'SOJIB68_CONSULT_SERVICE', 'consultation' );
define( 'SOJIB68_CONSULT_TOPICS_OPTION', 'sojib68_consult_topics' );

/** Topics, in the order the owner wrote them. */
function sojib68_consult_topics() {
	$stored = get_option( SOJIB68_CONSULT_TOPICS_OPTION, array() );
	$topics = array();
	foreach ( is_array( $stored ) ? $stored : array() as $topic ) {
		$topic = trim( (string) $topic );
		if ( '' !== $topic ) {
			$topics[] = $topic;
		}
	}
	return array_values( array_unique( $topics ) );
}

/** Editor text (one per line) -> list, for the wp-admin save. */
function sojib68_consult_topics_from_text( $raw ) {
	$topics = array();
	foreach ( preg_split( '/\r\n|\r|\n/', (string) $raw ) as $line ) {
		$line = trim( sanitize_text_field( $line ) );
		if ( '' !== $line ) {
			$topics[] = $line;
		}
	}
	return array_values( array_unique( $topics ) );
}

function sojib68_consult_enabled() {
	return is_front_page() && defined( 'SOJIB68_WHATSAPP' ) && SOJIB68_WHATSAPP;
}

function sojib68_consult_enqueue() {
	if ( ! sojib68_consult_enabled() ) {
		return;
	}
	$css = get_theme_file_path( 'assets/css/consult.css' );
	$js  = get_theme_file_path( 'assets/js/consult.js' );
	wp_enqueue_style( 'sojib68-consult', get_theme_file_uri( 'assets/css/consult.css' ), array( 'sojib68-land-calc' ), file_exists( $css ) ? filemtime( $css ) : '1.0.0' );
	wp_enqueue_script( 'sojib68-consult', get_theme_file_uri( 'assets/js/consult.js' ), array(), file_exists( $js ) ? filemtime( $js ) : '1.0.0', true );
}
add_action( 'wp_enqueue_scripts', 'sojib68_consult_enqueue', 25 );

function sojib68_consult_modal() {
	if ( ! sojib68_consult_enabled() ) {
		return;
	}
	$services = sojib68_get_services();
	$title    = isset( $services[ SOJIB68_CONSULT_SERVICE ]['title'] ) ? $services[ SOJIB68_CONSULT_SERVICE ]['title'] : '';
	$data     = array(
		'wa'     => preg_replace( '/\D+/', '', SOJIB68_WHATSAPP ),
		'title'  => $title,
		'topics' => sojib68_consult_topics(),
	);
	?>
	<script type="application/json" id="cs-data"><?php echo wp_json_encode( $data, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE ); ?></script>

	<div class="lc-modal cs-modal" data-cs-modal hidden>
		<div class="lc-modal__backdrop" data-cs-close></div>
		<div class="lc-dialog" role="dialog" aria-modal="true" aria-labelledby="cs-title" tabindex="-1">
			<header class="lc-dialog__head">
				<div>
					<p class="lc-dialog__eyebrow"><?php echo esc_html( $title ); ?></p>
					<h2 id="cs-title"><?php esc_html_e( 'WhatsApp-এ সংক্ষেপে জানান', 'sojib68' ); ?></h2>
				</div>
				<button type="button" class="lc-dialog__close" data-cs-close aria-label="<?php esc_attr_e( 'বন্ধ করুন', 'sojib68' ); ?>">&times;</button>
			</header>

			<div class="lc-dialog__body">
				<p class="lc-hint"><?php esc_html_e( 'নিচের তথ্য দিলে আপনার বার্তা লেখা অবস্থায় WhatsApp খুলবে, শুধু "পাঠান" চাপতে হবে। এখানে লেখা তথ্য এই সাইটে জমা হয় না।', 'sojib68' ); ?></p>

				<div class="cs-topics" data-cs-topics hidden>
					<p class="cs-label"><?php esc_html_e( 'বিষয় বাছুন', 'sojib68' ); ?></p>
					<div class="lc-shapes" data-cs-topiclist></div>
				</div>

				<div class="lc-form cs-form">
					<div class="lc-field">
						<label for="cs-name"><?php esc_html_e( 'আপনার নাম', 'sojib68' ); ?></label>
						<input type="text" id="cs-name" autocomplete="off" data-cs-name>
					</div>
					<div class="lc-field">
						<label for="cs-place"><?php esc_html_e( 'জমির এলাকা', 'sojib68' ); ?></label>
						<input type="text" id="cs-place" autocomplete="off" placeholder="<?php esc_attr_e( 'গ্রাম / উপজেলা / জেলা', 'sojib68' ); ?>" data-cs-place>
					</div>
				</div>
				<div class="lc-field cs-msgfield">
					<label for="cs-msg"><?php esc_html_e( 'সংক্ষেপে কী জানতে চান', 'sojib68' ); ?></label>
					<textarea id="cs-msg" rows="4" data-cs-msg></textarea>
				</div>
				<p class="lc-msg" data-cs-err role="alert" hidden></p>
			</div>

			<footer class="lc-dialog__foot">
				<button type="button" class="btn btn-primary lc-print" data-cs-send><?php esc_html_e( 'WhatsApp-এ পাঠান', 'sojib68' ); ?></button>
				<p class="lc-hint lc-hint--small"><?php esc_html_e( 'WhatsApp আলাদা ট্যাব বা অ্যাপে খুলবে।', 'sojib68' ); ?></p>
			</footer>
		</div>
	</div>
	<?php
}
add_action( 'wp_footer', 'sojib68_consult_modal', 7 );
