<?php
/**
 * "কী কী কাগজ লাগে" — a paper checklist for each service.
 *
 * - The lists live in one WordPress option and are edited from the wp-admin
 *   page "কাগজপত্র তালিকা" (no code needed). Nothing is invented here: a
 *   service with no list has no button and no page.
 * - Each list is shown at /checklist/<service>/ where staff or the customer
 *   ticks what applies and prints it (the browser's Print → Save as PDF
 *   keeps Bangla conjuncts intact, unlike server-made PDFs).
 * - Nothing the visitor types (name, mobile, note) is sent anywhere or stored.
 *
 * Text format used in the editor, one item per line:
 *   # সেকশনের শিরোনাম        (optional heading for the lines below it)
 *   কাগজের নাম | ছোট নোট      (the note after "|" is optional)
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'SOJIB68_CHECKLISTS_OPTION', 'sojib68_checklists' );
define( 'SOJIB68_CHECKLIST_OFFICE_OPTION', 'sojib68_checklist_office' );

/**
 * The letterhead printed on the slip. Kept apart from the site-wide contact
 * constants on purpose, so setting it here never puts a phone number in the
 * footer or the contact section. Editable in wp-admin → কাগজপত্র তালিকা.
 */
function sojib68_checklist_office() {
	$defaults = array(
		'name'     => 'রামগঞ্জ ভূমি কনসালটেন্ট',
		'person'   => 'ইসমাইল হোসেন (সজিব)',
		'phone'    => '01950087868',
		'channels' => 'WhatsApp · bKash',
	);
	$stored = get_option( SOJIB68_CHECKLIST_OFFICE_OPTION, array() );
	$stored = is_array( $stored ) ? array_intersect_key( $stored, $defaults ) : array();
	$office = array_merge( $defaults, $stored );
	// The two names never print blank; an emptied phone / channels box really hides them.
	foreach ( array( 'name', 'person' ) as $field ) {
		if ( '' === trim( (string) $office[ $field ] ) ) {
			$office[ $field ] = $defaults[ $field ];
		}
	}
	return $office;
}

/** Lists shipped with the theme. Empty on purpose — the real lists come from the owner. */
function sojib68_checklists_defaults() {
	return array();
}

/**
 * Parse the editor text into items: array( array( 'text' =>, 'note' =>, 'group' => ), … ).
 */
function sojib68_checklist_parse( $raw ) {
	$items = array();
	$group = '';
	foreach ( preg_split( '/\r\n|\r|\n/', (string) $raw ) as $line ) {
		$line = trim( $line );
		if ( '' === $line ) {
			continue;
		}
		if ( '#' === $line[0] ) {
			$group = trim( ltrim( $line, '#' ) );
			continue;
		}
		$parts = array_map( 'trim', explode( '|', $line, 2 ) );
		if ( '' === $parts[0] ) {
			continue;
		}
		$items[] = array(
			'text'  => $parts[0],
			'note'  => isset( $parts[1] ) ? $parts[1] : '',
			'group' => $group,
		);
	}
	return $items;
}

/** The reverse of sojib68_checklist_parse(), to fill the editor. */
function sojib68_checklist_to_text( array $items ) {
	$out   = array();
	$group = '';
	foreach ( $items as $item ) {
		if ( $item['group'] !== $group ) {
			$group = $item['group'];
			if ( '' !== $group ) {
				$out[] = '# ' . $group;
			}
		}
		$out[] = $item['text'] . ( '' !== $item['note'] ? ' | ' . $item['note'] : '' );
	}
	return implode( "\n", $out );
}

/**
 * Checklists keyed by service (see sojib68_get_services()); only services that
 * actually have items are returned.
 */
function sojib68_get_checklists() {
	$stored = get_option( SOJIB68_CHECKLISTS_OPTION, array() );
	$lists  = array_merge( sojib68_checklists_defaults(), is_array( $stored ) ? $stored : array() );
	$lists  = apply_filters( 'sojib68_checklists', $lists );

	$services = function_exists( 'sojib68_get_services' ) ? sojib68_get_services() : array();
	$out      = array();
	foreach ( $lists as $key => $items ) {
		if ( isset( $services[ $key ] ) && is_array( $items ) && $items ) {
			$out[ $key ] = $items;
		}
	}
	return $out;
}

function sojib68_checklist_url( $key ) {
	return home_url( '/checklist/' . rawurlencode( $key ) . '/' );
}

/* ---------------------------------------------------------------------
 * wp-admin editor
 * ------------------------------------------------------------------ */

function sojib68_checklists_admin_menu() {
	add_menu_page(
		__( 'কাগজপত্র তালিকা', 'sojib68' ),
		__( 'কাগজপত্র তালিকা', 'sojib68' ),
		'manage_options',
		'sojib68-checklists',
		'sojib68_checklists_admin_page',
		'dashicons-clipboard',
		58
	);
}
add_action( 'admin_menu', 'sojib68_checklists_admin_menu' );

function sojib68_checklists_admin_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$services = sojib68_get_services();
	$stored   = get_option( SOJIB68_CHECKLISTS_OPTION, array() );
	$stored   = is_array( $stored ) ? $stored : array();
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'কাগজপত্র তালিকা — কী কী কাগজ লাগে', 'sojib68' ); ?></h1>

		<?php if ( isset( $_GET['saved'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
			<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'তালিকা সংরক্ষণ হয়েছে।', 'sojib68' ); ?></p></div>
		<?php endif; ?>

		<p><?php esc_html_e( 'প্রতিটি সেবার জন্য এক লাইনে একটি কাগজ লিখুন। যে সেবার তালিকা ফাঁকা থাকবে, সাইটে তার "কী কী লাগে" বাটন দেখাবে না।', 'sojib68' ); ?></p>
		<p>
			<code># সেকশনের শিরোনাম</code> &nbsp;—&nbsp; <?php esc_html_e( 'নিচের কাগজগুলোর একটি শিরোনাম (ঐচ্ছিক)', 'sojib68' ); ?><br>
			<code>কাগজের নাম | ছোট নোট</code> &nbsp;—&nbsp; <?php esc_html_e( '"|" এর পরের নোট ঐচ্ছিক, যেমন "দুই কপি" বা "প্রযোজ্য হলে"', 'sojib68' ); ?>
		</p>

		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="sojib68_save_checklists">
			<?php wp_nonce_field( 'sojib68_save_checklists' ); ?>

			<?php $office = sojib68_checklist_office(); ?>
			<h2 style="margin-top:24px;"><?php esc_html_e( 'প্রিন্টের ওপরের অংশ (লেটারহেড)', 'sojib68' ); ?></h2>
			<table class="form-table" role="presentation">
				<tr><th scope="row"><label for="co-name"><?php esc_html_e( 'অফিসের নাম', 'sojib68' ); ?></label></th><td><input type="text" id="co-name" name="office[name]" value="<?php echo esc_attr( $office['name'] ); ?>" class="regular-text"></td></tr>
				<tr><th scope="row"><label for="co-person"><?php esc_html_e( 'আপনার নাম', 'sojib68' ); ?></label></th><td><input type="text" id="co-person" name="office[person]" value="<?php echo esc_attr( $office['person'] ); ?>" class="regular-text"></td></tr>
				<tr><th scope="row"><label for="co-phone"><?php esc_html_e( 'মোবাইল নম্বর', 'sojib68' ); ?></label></th><td><input type="text" id="co-phone" name="office[phone]" value="<?php echo esc_attr( $office['phone'] ); ?>" class="regular-text"></td></tr>
				<tr><th scope="row"><label for="co-channels"><?php esc_html_e( 'নম্বরের পাশের লেখা', 'sojib68' ); ?></label></th><td><input type="text" id="co-channels" name="office[channels]" value="<?php echo esc_attr( $office['channels'] ); ?>" class="regular-text"><p class="description"><?php esc_html_e( 'যেমন: WhatsApp · bKash', 'sojib68' ); ?></p></td></tr>
			</table>

			<h2 style="margin-top:24px;"><?php esc_html_e( 'সেবা অনুযায়ী কাগজপত্রের তালিকা', 'sojib68' ); ?></h2>

			<?php foreach ( $services as $key => $service ) : ?>
				<?php $items = isset( $stored[ $key ] ) && is_array( $stored[ $key ] ) ? $stored[ $key ] : array(); ?>
				<h2 style="margin-top:28px;"><?php echo esc_html( $service['title'] ); ?>
					<?php if ( $items ) : ?>
						<a href="<?php echo esc_url( sojib68_checklist_url( $key ) ); ?>" target="_blank" rel="noopener" style="font-size:13px;font-weight:400;margin-left:10px;"><?php esc_html_e( 'পেজ দেখুন ↗', 'sojib68' ); ?></a>
					<?php endif; ?>
				</h2>
				<textarea name="lists[<?php echo esc_attr( $key ); ?>]" rows="8" class="large-text" style="font-family:inherit;" placeholder="<?php esc_attr_e( "# মূল কাগজ\nকাগজের নাম | ছোট নোট", 'sojib68' ); ?>"><?php echo esc_textarea( sojib68_checklist_to_text( $items ) ); ?></textarea>
			<?php endforeach; ?>

			<p class="submit"><button type="submit" class="button button-primary button-large"><?php esc_html_e( 'সংরক্ষণ করুন', 'sojib68' ); ?></button></p>
		</form>
	</div>
	<?php
}

function sojib68_checklists_handle_save() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'এই কাজের অনুমতি নেই।', 'sojib68' ), 403 );
	}
	check_admin_referer( 'sojib68_save_checklists' );

	$services = sojib68_get_services();
	$posted   = isset( $_POST['lists'] ) && is_array( $_POST['lists'] ) ? wp_unslash( $_POST['lists'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
	$clean    = array();
	foreach ( $services as $key => $service ) {
		$items = sojib68_checklist_parse( isset( $posted[ $key ] ) ? sanitize_textarea_field( $posted[ $key ] ) : '' );
		if ( $items ) {
			$clean[ $key ] = $items;
		}
	}
	update_option( SOJIB68_CHECKLISTS_OPTION, $clean, false );

	$posted_office = isset( $_POST['office'] ) && is_array( $_POST['office'] ) ? wp_unslash( $_POST['office'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
	$office        = array();
	foreach ( array( 'name', 'person', 'phone', 'channels' ) as $field ) {
		$office[ $field ] = isset( $posted_office[ $field ] ) ? sanitize_text_field( $posted_office[ $field ] ) : '';
	}
	update_option( SOJIB68_CHECKLIST_OFFICE_OPTION, $office, false );

	wp_safe_redirect( add_query_arg( array( 'page' => 'sojib68-checklists', 'saved' => 1 ), admin_url( 'admin.php' ) ) );
	exit;
}
add_action( 'admin_post_sojib68_save_checklists', 'sojib68_checklists_handle_save' );

/* ---------------------------------------------------------------------
 * /checklist/<service>/ — same fallback approach as /about: served from
 * the template when WordPress itself has no page at that address.
 * ------------------------------------------------------------------ */

/** The service key requested at /checklist/<key>, or '' when this isn't one. */
function sojib68_checklist_request_key() {
	$request_path = (string) wp_parse_url( add_query_arg( array() ), PHP_URL_PATH );
	$home_path    = rtrim( (string) wp_parse_url( home_url(), PHP_URL_PATH ), '/' );
	if ( $home_path && 0 === strpos( $request_path, $home_path ) ) {
		$request_path = substr( $request_path, strlen( $home_path ) );
	}
	if ( preg_match( '#^/?checklist/([a-z0-9-]+)/?$#', $request_path, $m ) ) {
		return $m[1];
	}
	return '';
}

/** True when this request is a checklist page for a service that has a list. */
function sojib68_is_checklist_page() {
	if ( ! is_404() ) {
		return false;
	}
	$key = sojib68_checklist_request_key();
	return '' !== $key && isset( sojib68_get_checklists()[ $key ] );
}

function sojib68_checklist_template( $template ) {
	if ( sojib68_is_checklist_page() ) {
		global $wp_query;
		$wp_query->is_404 = false;
		status_header( 200 );
		return get_theme_file_path( 'page-checklist.php' );
	}
	return $template;
}
add_filter( 'template_include', 'sojib68_checklist_template' );

function sojib68_checklist_keep_canonical( $redirect_url ) {
	return sojib68_is_checklist_page() ? false : $redirect_url;
}
add_filter( 'redirect_canonical', 'sojib68_checklist_keep_canonical' );

function sojib68_checklist_body_class( $classes ) {
	if ( sojib68_is_checklist_page() ) {
		$classes   = array_diff( $classes, array( 'error404' ) );
		$classes[] = 'page-checklist';
	}
	return $classes;
}
add_filter( 'body_class', 'sojib68_checklist_body_class' );

function sojib68_checklist_title( $title ) {
	if ( sojib68_is_checklist_page() ) {
		$service = sojib68_get_services()[ sojib68_checklist_request_key() ];
		return $service['title'] . ' — কী কী কাগজ লাগে | Ismail Hossain Sojib';
	}
	return $title;
}
add_filter( 'pre_get_document_title', 'sojib68_checklist_title' );

/** Styles and script: the checklist page itself, and the card buttons on the homepage. */
function sojib68_checklist_enqueue() {
	$is_page = sojib68_is_checklist_page();
	if ( ! $is_page && ! is_front_page() ) {
		return;
	}
	$css = get_theme_file_path( 'assets/css/checklist.css' );
	wp_enqueue_style( 'sojib68-checklist', get_theme_file_uri( 'assets/css/checklist.css' ), array( 'sojib68-style' ), file_exists( $css ) ? filemtime( $css ) : '1.0.0' );

	if ( $is_page ) {
		$js = get_theme_file_path( 'assets/js/checklist.js' );
		wp_enqueue_script( 'sojib68-checklist', get_theme_file_uri( 'assets/js/checklist.js' ), array(), file_exists( $js ) ? filemtime( $js ) : '1.0.0', true );
	}
}
add_action( 'wp_enqueue_scripts', 'sojib68_checklist_enqueue', 20 );
