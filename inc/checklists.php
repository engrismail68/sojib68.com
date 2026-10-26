<?php
/**
 * "কী কী কাগজ লাগে" — a paper checklist for each service.
 *
 * - The lists live in one WordPress option and are edited from the wp-admin
 *   page "কাগজপত্র তালিকা" (no code needed). Nothing is invented here: a
 *   service with no list has no button and no page.
 * - On the homepage each service card with a list has a "কী কী লাগে" button.
 *   It opens a popup where staff or the customer ticks what applies and prints
 *   it (the browser's Print → Save as PDF keeps Bangla conjuncts intact,
 *   unlike server-made PDFs).
 * - Nothing the visitor types (name, mobile, note) is sent anywhere or stored.
 *
 * Text format used in the editor, one item per line:
 *   # সেকশনের শিরোনাম        (optional heading for the lines below it)
 *   # @ দলিলের ধরন : সাফ কবলা  (from here on, the lines belong to this choice; they show
 *                             only when the visitor picks it. "দলিলের ধরন" is the question,
 *                             "সাফ কবলা" one answer. Other questions work the same way,
 *                             e.g. "# @ এলাকা : পৌর এলাকা". Without the ":" the question is
 *                             "দলিলের ধরন". Lines above the first "# @" are common to all)
 *   কাগজের নাম | ছোট নোট      (the note after "|" is optional)
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'SOJIB68_CHECKLISTS_OPTION', 'sojib68_checklists' );
define( 'SOJIB68_CHECKLIST_OFFICE_OPTION', 'sojib68_checklist_office' );
define( 'SOJIB68_CHECKLIST_DEFAULT_KIND', 'দলিলের ধরন' ); // the question a bare "# @ X" belongs to

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
	$type  = '';
	$kind  = '';
	foreach ( preg_split( '/\r\n|\r|\n/', (string) $raw ) as $line ) {
		$line = trim( $line );
		if ( '' === $line ) {
			continue;
		}
		if ( '#' === $line[0] ) {
			$head = trim( ltrim( $line, '#' ) );
			if ( '' !== $head && '@' === $head[0] ) {
				$spec  = trim( ltrim( $head, '@' ) );
				$parts = preg_split( '/\s*[:：]\s*/u', $spec, 2 );
				if ( isset( $parts[1] ) && '' !== $parts[0] && '' !== $parts[1] ) {
					$kind = $parts[0];
					$type = $parts[1];
				} else {
					$kind = SOJIB68_CHECKLIST_DEFAULT_KIND;
					$type = $spec;
				}
				$group = '';
			} else {
				$group = $head;
			}
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
			'type'  => $type,
			'kind'  => '' === $type ? '' : $kind,
		);
	}
	return $items;
}

/** The reverse of sojib68_checklist_parse(), to fill the editor. */
function sojib68_checklist_to_text( array $items ) {
	$out   = array();
	$group = '';
	$type  = '';
	$kind  = '';
	foreach ( $items as $item ) {
		$item_type = isset( $item['type'] ) ? $item['type'] : '';
		$item_kind = '' === $item_type ? '' : ( ! empty( $item['kind'] ) ? $item['kind'] : SOJIB68_CHECKLIST_DEFAULT_KIND );
		if ( $item_type !== $type || $item_kind !== $kind ) {
			$type  = $item_type;
			$kind  = $item_kind;
			$group = '';
			if ( '' !== $type ) {
				$out[] = '# @ ' . $kind . ' : ' . $type;
			}
		}
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

/**
 * Stored items -> what the editor shows: the common papers as text, and one
 * block per "question : answer" (e.g. দলিলের ধরন : হেবা) with its papers as text.
 */
function sojib68_checklist_split( array $items ) {
	$common = array();
	$blocks = array();
	foreach ( $items as $item ) {
		$type = isset( $item['type'] ) ? $item['type'] : '';
		if ( '' === $type ) {
			$common[] = $item;
			continue;
		}
		$kind = ! empty( $item['kind'] ) ? $item['kind'] : SOJIB68_CHECKLIST_DEFAULT_KIND;
		$id   = $kind . "\n" . $type;
		if ( ! isset( $blocks[ $id ] ) ) {
			$blocks[ $id ] = array( 'kind' => $kind, 'type' => $type, 'items' => array() );
		}
		$item['type']              = '';
		$item['kind']              = '';
		$blocks[ $id ]['items'][] = $item;
	}
	foreach ( $blocks as $id => $block ) {
		$blocks[ $id ]['text'] = sojib68_checklist_to_text( $block['items'] );
		unset( $blocks[ $id ]['items'] );
	}
	return array( 'common' => sojib68_checklist_to_text( $common ), 'blocks' => array_values( $blocks ) );
}

/** One editor block (an existing one, or the template the "+" buttons clone). */
function sojib68_checklist_block_html( $service_key, $index, $kind, $type, $text ) {
	$base = 'lists[' . $service_key . '][blocks][' . $index . ']';
	?>
	<div class="ck-ed-block" style="margin:10px 0;padding:12px 14px;border:1px solid #c3c4c7;border-radius:4px;background:#f6f7f7;">
		<div style="display:flex;flex-wrap:wrap;gap:10px;align-items:flex-end;margin-bottom:8px;">
			<label style="flex:1 1 150px;"><span style="display:block;font-weight:600;"><?php esc_html_e( 'কীসের বাছাই', 'sojib68' ); ?></span>
				<input type="text" name="<?php echo esc_attr( $base ); ?>[kind]" value="<?php echo esc_attr( $kind ); ?>" class="regular-text" style="width:100%;"></label>
			<label style="flex:1 1 200px;"><span style="display:block;font-weight:600;"><?php esc_html_e( 'বাটনের নাম', 'sojib68' ); ?></span>
				<input type="text" name="<?php echo esc_attr( $base ); ?>[type]" value="<?php echo esc_attr( $type ); ?>" class="regular-text" style="width:100%;" placeholder="<?php esc_attr_e( 'যেমন: সাফ কবলা', 'sojib68' ); ?>"></label>
			<button type="button" class="button" data-ck-remove><?php esc_html_e( 'মুছুন', 'sojib68' ); ?></button>
		</div>
		<textarea name="<?php echo esc_attr( $base ); ?>[text]" rows="5" class="large-text" style="font-family:inherit;" placeholder="<?php esc_attr_e( 'এই বাছাইয়ে যা যা লাগে — এক লাইনে একটি', 'sojib68' ); ?>"><?php echo esc_textarea( $text ); ?></textarea>
	</div>
	<?php
}

/**
 * What the editor posts for one service -> the stored text format. An older
 * plain-text post is still accepted.
 */
function sojib68_checklist_posted_text( $posted ) {
	if ( ! is_array( $posted ) ) {
		return sanitize_textarea_field( (string) $posted );
	}
	$lines = array();
	if ( ! empty( $posted['common'] ) ) {
		$lines[] = sanitize_textarea_field( $posted['common'] );
	}
	$blocks = isset( $posted['blocks'] ) && is_array( $posted['blocks'] ) ? $posted['blocks'] : array();
	// the first question is required on the site, so "দলিলের ধরন" blocks always lead
	$default = array();
	$others  = array();
	foreach ( $blocks as $block ) {
		if ( ! is_array( $block ) ) {
			continue;
		}
		$kind = trim( str_replace( array( ':', '：', '@', '#' ), '', sanitize_text_field( isset( $block['kind'] ) ? $block['kind'] : '' ) ) );
		$type = trim( ltrim( sanitize_text_field( isset( $block['type'] ) ? $block['type'] : '' ), '@#' ) );
		$text = sanitize_textarea_field( isset( $block['text'] ) ? $block['text'] : '' );
		if ( '' === $type || '' === trim( $text ) ) {
			continue;
		}
		$kind = '' === $kind ? SOJIB68_CHECKLIST_DEFAULT_KIND : $kind;
		$out  = '# @ ' . $kind . ' : ' . $type . "\n" . $text;
		if ( SOJIB68_CHECKLIST_DEFAULT_KIND === $kind ) {
			$default[] = $out;
		} else {
			$others[] = $out;
		}
	}
	return implode( "\n", array_merge( $lines, $default, $others ) );
}

function sojib68_checklists_admin_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$services = sojib68_get_services();
	$stored   = get_option( SOJIB68_CHECKLISTS_OPTION, array() );
	$stored   = is_array( $stored ) ? $stored : array();
	$office   = sojib68_checklist_office();
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'কাগজপত্র তালিকা — কী কী কাগজ লাগে', 'sojib68' ); ?></h1>

		<?php if ( isset( $_GET['saved'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
			<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'তালিকা সংরক্ষণ হয়েছে।', 'sojib68' ); ?></p></div>
		<?php endif; ?>

		<div style="max-width:900px;margin:12px 0 18px;padding:12px 16px;background:#fff;border-left:4px solid #198030;">
			<strong><?php esc_html_e( 'কীভাবে কাজ করে', 'sojib68' ); ?></strong>
			<ol style="margin:8px 0 0 18px;">
				<li><?php esc_html_e( 'নিচে সেবার নামে চাপ দিয়ে খুলুন।', 'sojib68' ); ?></li>
				<li><?php esc_html_e( '"সব ধরনের জন্য কাগজ" ঘরে এক লাইনে একটি করে কাগজ লিখুন (যা সবার লাগে)।', 'sojib68' ); ?></li>
				<li><?php esc_html_e( '"+ দলিলের ধরন যোগ করুন" চেপে ধরনের নাম (যেমন হেবা) ও তার বাড়তি কাগজ লিখুন।', 'sojib68' ); ?></li>
				<li><?php esc_html_e( 'ওয়ারিশ সূত্রে বা ক্রয় সূত্রে মালিকানার আলাদা কাগজ লাগলে "+ মালিকানার সূত্র যোগ করুন" চেপে প্রতিটির জন্য একটি বক্স করুন। পৌর এলাকার মতো অন্য বাছাইয়ের জন্য "+ অন্য বাছাই যোগ করুন"।', 'sojib68' ); ?></li>
				<li><?php esc_html_e( 'ধরন বা সূত্র বাছাই করলে তার কাগজগুলো নিজে থেকেই টিক পড়ে যায়; সাধারণ কাগজ গ্রাহক নিজে টিক দেন।', 'sojib68' ); ?></li>
				<li><?php esc_html_e( 'সবশেষে "সংরক্ষণ করুন"। যে সেবার সব ঘর ফাঁকা, সাইটে তার "কী কী লাগে" বাটন আসে না।', 'sojib68' ); ?></li>
			</ol>
			<p class="description" style="margin-top:8px;"><?php esc_html_e( 'কাগজের নামের পরে "|" দিয়ে ছোট নোট লেখা যায়, যেমন: দাতার ছবি | ২ কপি।', 'sojib68' ); ?></p>
		</div>

		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="sojib68_save_checklists">
			<?php wp_nonce_field( 'sojib68_save_checklists' ); ?>

			<?php foreach ( $services as $key => $service ) : ?>
				<?php
				$items = isset( $stored[ $key ] ) && is_array( $stored[ $key ] ) ? $stored[ $key ] : array();
				$split = sojib68_checklist_split( $items );
				?>
				<details class="ck-ed" data-ck-service="<?php echo esc_attr( $key ); ?>" style="max-width:900px;margin:0 0 10px;padding:0;background:#fff;border:1px solid #c3c4c7;border-radius:4px;" <?php echo $items ? 'open' : ''; ?>>
					<summary style="padding:12px 16px;font-size:15px;font-weight:600;cursor:pointer;">
						<?php echo esc_html( $service['title'] ); ?>
						<?php if ( $items ) : ?>
							<span style="font-weight:400;color:#198030;margin-left:8px;"><?php echo esc_html( sprintf( /* translators: %d: number of papers */ __( '%d টি কাগজ', 'sojib68' ), count( $items ) ) ); ?></span>
						<?php endif; ?>
					</summary>
					<div style="padding:4px 16px 16px;">
						<label for="ck-common-<?php echo esc_attr( $key ); ?>" style="display:block;font-weight:600;"><?php esc_html_e( 'সব ধরনের জন্য কাগজ', 'sojib68' ); ?></label>
						<textarea id="ck-common-<?php echo esc_attr( $key ); ?>" name="lists[<?php echo esc_attr( $key ); ?>][common]" rows="6" class="large-text" style="margin-top:4px;font-family:inherit;" placeholder="<?php esc_attr_e( 'এক লাইনে একটি কাগজ', 'sojib68' ); ?>"><?php echo esc_textarea( $split['common'] ); ?></textarea>

						<div data-ck-blocks>
							<?php foreach ( $split['blocks'] as $i => $block ) : ?>
								<?php sojib68_checklist_block_html( $key, 'e' . $i, $block['kind'], $block['type'], $block['text'] ); ?>
							<?php endforeach; ?>
						</div>

						<p style="margin:10px 0 0;">
							<button type="button" class="button" data-ck-add="<?php echo esc_attr( SOJIB68_CHECKLIST_DEFAULT_KIND ); ?>">+ <?php esc_html_e( 'দলিলের ধরন যোগ করুন', 'sojib68' ); ?></button>
							<button type="button" class="button" data-ck-add="মালিকানার সূত্র">+ <?php esc_html_e( 'মালিকানার সূত্র যোগ করুন (ওয়ারিশ / ক্রয়)', 'sojib68' ); ?></button>
							<button type="button" class="button" data-ck-add="এলাকা">+ <?php esc_html_e( 'অন্য বাছাই যোগ করুন (যেমন এলাকা)', 'sojib68' ); ?></button>
						</p>
					</div>
				</details>
			<?php endforeach; ?>

			<details style="max-width:900px;margin:14px 0 0;padding:0;background:#fff;border:1px solid #c3c4c7;border-radius:4px;">
				<summary style="padding:12px 16px;font-size:15px;font-weight:600;cursor:pointer;"><?php esc_html_e( 'প্রিন্টের ওপরের অংশ (লেটারহেড)', 'sojib68' ); ?></summary>
				<table class="form-table" role="presentation" style="margin:0 16px 12px;width:auto;">
					<tr><th scope="row"><label for="co-name"><?php esc_html_e( 'অফিসের নাম', 'sojib68' ); ?></label></th><td><input type="text" id="co-name" name="office[name]" value="<?php echo esc_attr( $office['name'] ); ?>" class="regular-text"></td></tr>
					<tr><th scope="row"><label for="co-person"><?php esc_html_e( 'আপনার নাম', 'sojib68' ); ?></label></th><td><input type="text" id="co-person" name="office[person]" value="<?php echo esc_attr( $office['person'] ); ?>" class="regular-text"></td></tr>
					<tr><th scope="row"><label for="co-phone"><?php esc_html_e( 'মোবাইল নম্বর', 'sojib68' ); ?></label></th><td><input type="text" id="co-phone" name="office[phone]" value="<?php echo esc_attr( $office['phone'] ); ?>" class="regular-text"></td></tr>
					<tr><th scope="row"><label for="co-channels"><?php esc_html_e( 'নম্বরের পাশের লেখা', 'sojib68' ); ?></label></th><td><input type="text" id="co-channels" name="office[channels]" value="<?php echo esc_attr( $office['channels'] ); ?>" class="regular-text"><p class="description"><?php esc_html_e( 'যেমন: WhatsApp · bKash', 'sojib68' ); ?></p></td></tr>
				</table>
			</details>

			<p class="submit"><button type="submit" class="button button-primary button-large"><?php esc_html_e( 'সংরক্ষণ করুন', 'sojib68' ); ?></button></p>
		</form>

		<template id="ck-block-template"><?php sojib68_checklist_block_html( '__K__', '__I__', '__KIND__', '', '' ); ?></template>
		<script>
		( function () {
			var tpl = document.getElementById( 'ck-block-template' );
			var n = 0;
			document.addEventListener( 'click', function ( e ) {
				var add = e.target.closest( '[data-ck-add]' );
				if ( add ) {
					var card = add.closest( '[data-ck-service]' );
					n += 1;
					var html = tpl.innerHTML
						.replace( /__K__/g, card.getAttribute( 'data-ck-service' ) )
						.replace( /__I__/g, 'n' + n )
						.replace( /__KIND__/g, add.getAttribute( 'data-ck-add' ) );
					var holder = document.createElement( 'div' );
					holder.innerHTML = html;
					var block = holder.firstElementChild;
					card.querySelector( '[data-ck-blocks]' ).appendChild( block );
					var first = block.querySelector( 'input[name$="[type]"]' );
					if ( first ) { first.focus(); }
					return;
				}
				var rm = e.target.closest( '[data-ck-remove]' );
				if ( rm && window.confirm( 'এই বাছাই ও তার কাগজগুলো মুছে ফেলবেন?' ) ) {
					rm.closest( '.ck-ed-block' ).remove();
				}
			} );
		} )();
		</script>
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
		$items = sojib68_checklist_parse( sojib68_checklist_posted_text( isset( $posted[ $key ] ) ? $posted[ $key ] : '' ) );
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
 * Homepage popup
 * ------------------------------------------------------------------ */

/** Styles + script: homepage only, and only when at least one list exists. */
function sojib68_checklist_enqueue() {
	if ( ! is_front_page() || ! sojib68_get_checklists() ) {
		return;
	}
	$css = get_theme_file_path( 'assets/css/checklist.css' );
	$js  = get_theme_file_path( 'assets/js/checklist.js' );
	wp_enqueue_style( 'sojib68-checklist', get_theme_file_uri( 'assets/css/checklist.css' ), array(), file_exists( $css ) ? filemtime( $css ) : '1.0.0' );
	wp_enqueue_script( 'sojib68-checklist', get_theme_file_uri( 'assets/js/checklist.js' ), array(), file_exists( $js ) ? filemtime( $js ) : '1.0.0', true );
}
add_action( 'wp_enqueue_scripts', 'sojib68_checklist_enqueue', 20 );

/** The popup shell, the lists as JSON, and the (print-only) slip. Rendered once, in the footer. */
function sojib68_checklist_modal() {
	$lists = is_front_page() ? sojib68_get_checklists() : array();
	if ( ! $lists ) {
		return;
	}
	$services = sojib68_get_services();
	$office   = sojib68_checklist_office();
	$data     = array();
	foreach ( $lists as $key => $items ) {
		$data[ $key ] = array(
			'title' => $services[ $key ]['title'],
			'items' => array_values( $items ),
		);
	}
	?>
	<script type="application/json" id="ck-data"><?php echo wp_json_encode( $data, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE ); ?></script>

	<div class="ck-modal" data-ck-modal hidden>
		<div class="ck-modal__backdrop" data-ck-close></div>
		<div class="ck-dialog" role="dialog" aria-modal="true" aria-labelledby="ck-title" tabindex="-1">
			<header class="ck-dialog__head">
				<div>
					<p class="ck-dialog__eyebrow"><?php esc_html_e( 'কী কী কাগজ লাগে', 'sojib68' ); ?></p>
					<h2 id="ck-title" data-ck-title></h2>
				</div>
				<button type="button" class="ck-dialog__close" data-ck-close aria-label="<?php esc_attr_e( 'বন্ধ করুন', 'sojib68' ); ?>">&times;</button>
			</header>

			<div class="ck-dialog__body">
				<p class="ck-hint"><?php esc_html_e( 'যে কাগজগুলো প্রযোজ্য শুধু সেগুলো টিক দিন, তারপর প্রিন্ট করুন বা PDF হিসেবে সংরক্ষণ করুন।', 'sojib68' ); ?></p>

				<div class="ck-types" data-ck-types hidden></div>
				<p class="ck-gate" data-ck-gate hidden><?php esc_html_e( 'ধরন বাছাই করলে ওই দলিলের জন্য যা যা লাগবে তার তালিকা এখানে আসবে।', 'sojib68' ); ?></p>

				<div class="ck-toolbar">
					<strong class="ck-count" data-ck-count aria-live="polite"></strong>
					<span class="ck-toolbar__btns">
						<button type="button" class="ck-link" data-ck-all><?php esc_html_e( 'সব বাছাই', 'sojib68' ); ?></button>
						<button type="button" class="ck-link" data-ck-none><?php esc_html_e( 'সব বাদ', 'sojib68' ); ?></button>
					</span>
				</div>

				<ul class="ck-list" data-ck-list></ul>

				<div class="ck-extra">
					<p class="ck-hint"><?php esc_html_e( 'নিচের ঘরগুলো ঐচ্ছিক। এখানে লেখা তথ্য কোথাও জমা হয় না, শুধু প্রিন্টে আসে।', 'sojib68' ); ?></p>
					<div class="ck-extra__row">
						<div class="ck-field">
							<label for="ck-name"><?php esc_html_e( 'নাম', 'sojib68' ); ?></label>
							<input type="text" id="ck-name" autocomplete="off" data-ck-name>
						</div>
						<div class="ck-field">
							<label for="ck-mobile"><?php esc_html_e( 'মোবাইল নম্বর', 'sojib68' ); ?></label>
							<input type="text" id="ck-mobile" inputmode="tel" autocomplete="off" data-ck-mobile>
						</div>
					</div>
					<div class="ck-field">
						<label for="ck-note"><?php esc_html_e( 'অতিরিক্ত নির্দেশনা', 'sojib68' ); ?></label>
						<textarea id="ck-note" rows="2" data-ck-note></textarea>
					</div>
				</div>
			</div>

			<footer class="ck-dialog__foot">
				<p class="ck-msg" data-ck-msg role="alert" hidden></p>
				<button type="button" class="btn btn-primary ck-print" data-ck-print><?php esc_html_e( 'প্রিন্ট / PDF সংরক্ষণ', 'sojib68' ); ?></button>
				<p class="ck-hint ck-hint--small"><?php esc_html_e( 'PDF চাইলে প্রিন্টের জানালায় "Save as PDF" / "PDF হিসেবে সংরক্ষণ" বেছে নিন।', 'sojib68' ); ?></p>
			</footer>
		</div>

		<!-- Printed slip (hidden on screen, filled from the ticked items by checklist.js) -->
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

			<h2 class="ck-sheet__title"><span data-sheet-title></span> — <?php esc_html_e( 'প্রয়োজনীয় কাগজপত্র', 'sojib68' ); ?></h2>

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
}
add_action( 'wp_footer', 'sojib68_checklist_modal', 5 );
