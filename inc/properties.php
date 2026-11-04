<?php
/**
 * জমি ও প্রপার্টি — seller advertisements the owner posts from wp-admin.
 *
 * A buyer finds an advertisement on /land-property/, reads the full details, and connects through the
 * owner (WhatsApp / call with the advertisement already named in the message). The seller's own name
 * and number are stored in wp-admin only and are never printed on the site.
 *
 * Publishing an advertisement (wp-admin → জমি ও প্রপার্টি → নতুন বিজ্ঞাপন → প্রকাশ করুন) puts it on the page
 * straight away; "খসড়া" keeps it hidden.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'SOJIB68_PROPERTY_CPT', 'sojib68_property' );
define( 'SOJIB68_PROPERTY_ARCHIVE', 'land-property' ); // the listing page: /land-property/
define( 'SOJIB68_PROPERTY_PER_PAGE', 12 );

/* ---------------------------------------------------------------------
 * Choices
 * ------------------------------------------------------------------ */

function sojib68_property_types() {
	return array(
		'sale' => __( 'বিক্রয়', 'sojib68' ),
		'rent' => __( 'ভাড়া', 'sojib68' ),
	);
}

function sojib68_property_categories() {
	return array(
		'land'       => __( 'জমি', 'sojib68' ),
		'farmland'   => __( 'কৃষিজমি', 'sojib68' ),
		'plot'       => __( 'প্লট', 'sojib68' ),
		'house'      => __( 'বাড়ি', 'sojib68' ),
		'flat'       => __( 'ফ্ল্যাট', 'sojib68' ),
		'commercial' => __( 'দোকান / বাণিজ্যিক', 'sojib68' ),
		'other'      => __( 'অন্যান্য', 'sojib68' ),
	);
}

function sojib68_property_units() {
	return array(
		'shotangsho' => __( 'শতাংশ', 'sojib68' ),
		'katha'      => __( 'কাঠা', 'sojib68' ),
		'bigha'      => __( 'বিঘা', 'sojib68' ),
		'acre'       => __( 'একর', 'sojib68' ),
		'sqft'       => __( 'বর্গফুট', 'sojib68' ),
		'other'      => __( 'অন্যান্য', 'sojib68' ),
	);
}

function sojib68_property_statuses() {
	return array(
		'available' => __( 'পাওয়া যাচ্ছে', 'sojib68' ),
		'booked'    => __( 'বুকড / আলোচনায়', 'sojib68' ),
		'sold'      => __( 'বিক্রি হয়ে গেছে', 'sojib68' ),
	);
}

/* ---------------------------------------------------------------------
 * Post type
 * ------------------------------------------------------------------ */

function sojib68_property_register() {
	register_post_type( SOJIB68_PROPERTY_CPT, array(
		'labels'        => array(
			'name'               => __( 'জমি ও প্রপার্টি', 'sojib68' ),
			'singular_name'      => __( 'বিজ্ঞাপন', 'sojib68' ),
			'add_new'            => __( 'নতুন বিজ্ঞাপন', 'sojib68' ),
			'add_new_item'       => __( 'নতুন বিজ্ঞাপন যোগ করুন', 'sojib68' ),
			'edit_item'          => __( 'বিজ্ঞাপন সম্পাদনা', 'sojib68' ),
			'new_item'           => __( 'নতুন বিজ্ঞাপন', 'sojib68' ),
			'view_item'          => __( 'বিজ্ঞাপন দেখুন', 'sojib68' ),
			'search_items'       => __( 'বিজ্ঞাপন খুঁজুন', 'sojib68' ),
			'not_found'          => __( 'কোনো বিজ্ঞাপন নেই', 'sojib68' ),
			'all_items'          => __( 'সব বিজ্ঞাপন', 'sojib68' ),
			'menu_name'          => __( 'জমি ও প্রপার্টি', 'sojib68' ),
			'featured_image'     => __( 'প্রধান ছবি', 'sojib68' ),
			'set_featured_image' => __( 'প্রধান ছবি বাছুন', 'sojib68' ),
		),
		'public'        => true,
		'show_in_rest'  => false,
		'has_archive'   => SOJIB68_PROPERTY_ARCHIVE,
		'rewrite'       => array( 'slug' => 'property', 'with_front' => false ),
		'menu_icon'     => 'dashicons-admin-multisite',
		'menu_position' => 21,
		'supports'      => array( 'title', 'editor', 'thumbnail', 'excerpt' ),
	) );
}
add_action( 'init', 'sojib68_property_register' );

/** One-time rewrite flush when this feature first goes live (or changes), so /land-property/ works without a manual step. */
function sojib68_property_maybe_flush() {
	if ( get_option( 'sojib68_property_rewrite' ) !== '1' ) {
		flush_rewrite_rules( false );
		update_option( 'sojib68_property_rewrite', '1' );
	}
}
add_action( 'init', 'sojib68_property_maybe_flush', 99 );

/* ---------------------------------------------------------------------
 * Values
 * ------------------------------------------------------------------ */

function sojib68_property_meta( $post_id, $key, $default = '' ) {
	$v = get_post_meta( $post_id, '_sp_' . $key, true );
	return ( '' === $v || false === $v ) ? $default : $v;
}

/** The reference the buyer quotes: P-0007. */
function sojib68_property_ref( $post_id ) {
	return 'P-' . str_pad( (string) (int) $post_id, 4, '0', STR_PAD_LEFT );
}

/** 1250000 -> "12,50,000" (Bangladeshi grouping, English digits). */
function sojib68_property_group_number( $n ) {
	$s = preg_replace( '/\D+/', '', (string) $n );
	if ( '' === $s ) {
		return '';
	}
	$s = ltrim( $s, '0' );
	if ( '' === $s ) {
		return '0';
	}
	if ( strlen( $s ) <= 3 ) {
		return $s;
	}
	$last = substr( $s, -3 );
	$rest = substr( $s, 0, -3 );
	return preg_replace( '/\B(?=(\d{2})+(?!\d))/', ',', $rest ) . ',' . $last;
}

/** Bangla digits typed in the editor become 0-9. */
function sojib68_property_digits( $s ) {
	return strtr( (string) $s, array( '০' => '0', '১' => '1', '২' => '2', '৩' => '3', '৪' => '4', '৫' => '5', '৬' => '6', '৭' => '7', '৮' => '8', '৯' => '9' ) );
}

function sojib68_property_price_text( $post_id ) {
	$price = sojib68_property_digits( sojib68_property_meta( $post_id, 'price' ) );
	$note  = sojib68_property_meta( $post_id, 'price_note' );
	if ( '' !== preg_replace( '/\D+/', '', $price ) ) {
		$out = '৳ ' . sojib68_property_group_number( $price );
		if ( 'rent' === sojib68_property_meta( $post_id, 'type', 'sale' ) ) {
			$out .= ' ' . __( '/ মাস', 'sojib68' );
		}
		return $note ? $out . ' (' . $note . ')' : $out;
	}
	return $note ? $note : __( 'আলোচনা সাপেক্ষে', 'sojib68' );
}

function sojib68_property_size_text( $post_id ) {
	$size  = trim( sojib68_property_digits( sojib68_property_meta( $post_id, 'size' ) ) );
	$units = sojib68_property_units();
	$unit  = sojib68_property_meta( $post_id, 'unit', 'shotangsho' );
	if ( '' === $size ) {
		return '';
	}
	return $size . ' ' . ( isset( $units[ $unit ] ) && 'other' !== $unit ? $units[ $unit ] : '' );
}

/** Image ids in the order the owner arranged them: the main image first, then the gallery. */
function sojib68_property_image_ids( $post_id ) {
	$ids = array();
	$thumb = (int) get_post_thumbnail_id( $post_id );
	if ( $thumb ) {
		$ids[] = $thumb;
	}
	foreach ( array_filter( array_map( 'intval', explode( ',', (string) sojib68_property_meta( $post_id, 'gallery' ) ) ) ) as $id ) {
		if ( ! in_array( $id, $ids, true ) ) {
			$ids[] = $id;
		}
	}
	return $ids;
}

/** The owner's WhatsApp link with this advertisement already named in the message. */
function sojib68_property_whatsapp_url( $post_id ) {
	if ( ! defined( 'SOJIB68_WHATSAPP' ) || ! SOJIB68_WHATSAPP ) {
		return '';
	}
	$text = sprintf(
		/* translators: 1: reference, 2: title, 3: link */
		__( "আসসালামু আলাইকুম, \"জমি ও প্রপার্টি\" থেকে এই বিজ্ঞাপনটি সম্পর্কে জানতে চাই:\n%1\$s — %2\$s\n%3\$s", 'sojib68' ),
		sojib68_property_ref( $post_id ),
		get_the_title( $post_id ),
		get_permalink( $post_id )
	);
	return 'https://wa.me/' . preg_replace( '/\D+/', '', SOJIB68_WHATSAPP ) . '?text=' . rawurlencode( $text );
}

/* ---------------------------------------------------------------------
 * wp-admin: the form for one advertisement
 * ------------------------------------------------------------------ */

function sojib68_property_add_boxes() {
	add_meta_box( 'sojib68_property_details', __( 'বিজ্ঞাপনের তথ্য', 'sojib68' ), 'sojib68_property_box_details', SOJIB68_PROPERTY_CPT, 'normal', 'high' );
	add_meta_box( 'sojib68_property_gallery', __( 'ছবির গ্যালারি', 'sojib68' ), 'sojib68_property_box_gallery', SOJIB68_PROPERTY_CPT, 'normal', 'default' );
	add_meta_box( 'sojib68_property_seller', __( 'বিক্রেতার তথ্য — শুধু আপনি দেখবেন, সাইটে আসবে না', 'sojib68' ), 'sojib68_property_box_seller', SOJIB68_PROPERTY_CPT, 'normal', 'default' );
}
add_action( 'add_meta_boxes', 'sojib68_property_add_boxes' );

function sojib68_property_select( $name, $options, $current ) {
	echo '<select name="' . esc_attr( $name ) . '">';
	foreach ( $options as $key => $label ) {
		echo '<option value="' . esc_attr( $key ) . '"' . selected( $current, $key, false ) . '>' . esc_html( $label ) . '</option>';
	}
	echo '</select>';
}

function sojib68_property_box_details( $post ) {
	wp_nonce_field( 'sojib68_property_save', 'sojib68_property_nonce' );
	$m = function ( $k, $d = '' ) use ( $post ) {
		return sojib68_property_meta( $post->ID, $k, $d );
	};
	?>
	<p class="description" style="margin-top:0;"><?php esc_html_e( 'শিরোনাম ও পূর্ণ বিবরণ ওপরের বড় ঘরে লিখুন। ডান পাশের "প্রধান ছবি" থেকে কার্ডের ছবি দিন। এখানে বাকি তথ্য।', 'sojib68' ); ?></p>
	<table class="form-table" role="presentation">
		<tr>
			<th><?php esc_html_e( 'বিজ্ঞাপনের ধরন', 'sojib68' ); ?></th>
			<td><?php sojib68_property_select( 'sp_type', sojib68_property_types(), $m( 'type', 'sale' ) ); ?>
			    <?php sojib68_property_select( 'sp_category', sojib68_property_categories(), $m( 'category', 'land' ) ); ?></td>
		</tr>
		<tr>
			<th><label for="sp_location"><?php esc_html_e( 'এলাকা', 'sojib68' ); ?></label></th>
			<td><input type="text" id="sp_location" name="sp_location" class="regular-text" value="<?php echo esc_attr( $m( 'location' ) ); ?>" placeholder="<?php esc_attr_e( 'গ্রাম / ইউনিয়ন, উপজেলা, জেলা', 'sojib68' ); ?>"></td>
		</tr>
		<tr>
			<th><label for="sp_size"><?php esc_html_e( 'পরিমাণ', 'sojib68' ); ?></label></th>
			<td><input type="text" id="sp_size" name="sp_size" class="small-text" style="width:110px;" value="<?php echo esc_attr( $m( 'size' ) ); ?>" placeholder="10 বা 5.5">
			    <?php sojib68_property_select( 'sp_unit', sojib68_property_units(), $m( 'unit', 'shotangsho' ) ); ?></td>
		</tr>
		<tr>
			<th><label for="sp_price"><?php esc_html_e( 'দাম (টাকা)', 'sojib68' ); ?></label></th>
			<td><input type="text" id="sp_price" name="sp_price" class="regular-text" value="<?php echo esc_attr( $m( 'price' ) ); ?>" placeholder="<?php esc_attr_e( 'শুধু সংখ্যা, যেমন 1250000', 'sojib68' ); ?>">
			    <p class="description"><?php esc_html_e( 'ফাঁকা রাখলে "আলোচনা সাপেক্ষে" দেখাবে। ভাড়ার হলে মাসিক ভাড়া লিখুন।', 'sojib68' ); ?></p></td>
		</tr>
		<tr>
			<th><label for="sp_price_note"><?php esc_html_e( 'দামের নোট', 'sojib68' ); ?></label></th>
			<td><input type="text" id="sp_price_note" name="sp_price_note" class="regular-text" value="<?php echo esc_attr( $m( 'price_note' ) ); ?>" placeholder="<?php esc_attr_e( 'যেমন: দর-কষাকষি করা যাবে', 'sojib68' ); ?>"></td>
		</tr>
		<tr>
			<th><?php esc_html_e( 'অবস্থা', 'sojib68' ); ?></th>
			<td><?php sojib68_property_select( 'sp_status', sojib68_property_statuses(), $m( 'status', 'available' ) ); ?>
			    <p class="description"><?php esc_html_e( 'বিক্রি হয়ে গেলে "বিক্রি হয়ে গেছে" বাছুন। তখন এটা তালিকা থেকে সরে যায় (ফিল্টারে খুঁজলে দেখা যায়)।', 'sojib68' ); ?></p></td>
		</tr>
		<tr>
			<th><label for="sp_features"><?php esc_html_e( 'বৈশিষ্ট্য', 'sojib68' ); ?></label></th>
			<td><textarea id="sp_features" name="sp_features" rows="5" class="large-text" placeholder="<?php esc_attr_e( "এক লাইনে একটি, যেমন:\nপাকা রাস্তার পাশে\nবিদ্যুৎ সংযোগ আছে", 'sojib68' ); ?>"><?php echo esc_textarea( $m( 'features' ) ); ?></textarea></td>
		</tr>
	</table>
	<?php
}

function sojib68_property_box_gallery( $post ) {
	$ids = array_filter( array_map( 'intval', explode( ',', (string) sojib68_property_meta( $post->ID, 'gallery' ) ) ) );
	?>
	<input type="hidden" name="sp_gallery" id="sp_gallery" value="<?php echo esc_attr( implode( ',', $ids ) ); ?>">
	<div id="sp_gallery_thumbs" style="display:flex;flex-wrap:wrap;gap:8px;margin-bottom:10px;">
		<?php foreach ( $ids as $id ) : ?>
			<span data-id="<?php echo (int) $id; ?>" style="position:relative;"><?php echo wp_get_attachment_image( $id, array( 90, 90 ), false, array( 'style' => 'border-radius:4px;' ) ); ?><button type="button" class="button-link-delete sp-remove" style="position:absolute;top:2px;right:2px;background:#fff;border-radius:50%;width:20px;height:20px;line-height:18px;">&times;</button></span>
		<?php endforeach; ?>
	</div>
	<button type="button" class="button" id="sp_gallery_add"><?php esc_html_e( 'ছবি যোগ করুন', 'sojib68' ); ?></button>
	<p class="description"><?php esc_html_e( 'একসঙ্গে একাধিক ছবি বাছা যায় (Ctrl চেপে)। প্রধান ছবি আলাদা, ডান পাশে। বিক্রেতার অনুমতি নিয়ে ছবি দিন।', 'sojib68' ); ?></p>
	<script>
	( function () {
		var field = document.getElementById( 'sp_gallery' );
		var box = document.getElementById( 'sp_gallery_thumbs' );
		var frame;
		function sync() {
			var ids = [];
			box.querySelectorAll( '[data-id]' ).forEach( function ( el ) { ids.push( el.getAttribute( 'data-id' ) ); } );
			field.value = ids.join( ',' );
		}
		box.addEventListener( 'click', function ( e ) {
			if ( e.target.closest( '.sp-remove' ) ) { e.target.closest( '[data-id]' ).remove(); sync(); }
		} );
		document.getElementById( 'sp_gallery_add' ).addEventListener( 'click', function () {
			if ( ! window.wp || ! wp.media ) { return; }
			if ( ! frame ) {
				frame = wp.media( { title: 'ছবি বাছুন', button: { text: 'যোগ করুন' }, multiple: true, library: { type: 'image' } } );
				frame.on( 'select', function () {
					frame.state().get( 'selection' ).each( function ( a ) {
						a = a.toJSON();
						if ( box.querySelector( '[data-id="' + a.id + '"]' ) ) { return; }
						var span = document.createElement( 'span' );
						span.setAttribute( 'data-id', a.id );
						span.style.position = 'relative';
						var img = document.createElement( 'img' );
						img.src = ( a.sizes && a.sizes.thumbnail ) ? a.sizes.thumbnail.url : a.url;
						img.style.cssText = 'width:90px;height:90px;object-fit:cover;border-radius:4px;';
						var x = document.createElement( 'button' );
						x.type = 'button';
						x.className = 'button-link-delete sp-remove';
						x.style.cssText = 'position:absolute;top:2px;right:2px;background:#fff;border-radius:50%;width:20px;height:20px;line-height:18px;';
						x.innerHTML = '&times;';
						span.appendChild( img );
						span.appendChild( x );
						box.appendChild( span );
					} );
					sync();
				} );
			}
			frame.open();
		} );
	} )();
	</script>
	<?php
}

function sojib68_property_box_seller( $post ) {
	?>
	<p class="description" style="margin-top:0;"><?php esc_html_e( 'এগুলো শুধু আপনার নিজের রেকর্ডের জন্য। সাইটে কোথাও দেখানো হয় না, ক্রেতা বিক্রেতার নম্বর পান না। ক্রেতা আপনার মাধ্যমেই যুক্ত হন।', 'sojib68' ); ?></p>
	<table class="form-table" role="presentation">
		<tr><th><label for="sp_seller_name"><?php esc_html_e( 'বিক্রেতার নাম', 'sojib68' ); ?></label></th><td><input type="text" id="sp_seller_name" name="sp_seller_name" class="regular-text" value="<?php echo esc_attr( sojib68_property_meta( $post->ID, 'seller_name' ) ); ?>"></td></tr>
		<tr><th><label for="sp_seller_phone"><?php esc_html_e( 'বিক্রেতার মোবাইল', 'sojib68' ); ?></label></th><td><input type="text" id="sp_seller_phone" name="sp_seller_phone" class="regular-text" value="<?php echo esc_attr( sojib68_property_meta( $post->ID, 'seller_phone' ) ); ?>"></td></tr>
		<tr><th><label for="sp_seller_note"><?php esc_html_e( 'নোট', 'sojib68' ); ?></label></th><td><textarea id="sp_seller_note" name="sp_seller_note" rows="3" class="large-text" placeholder="<?php esc_attr_e( 'যেমন: কমিশনের কথা, কাগজপত্র যাচাইয়ের অবস্থা', 'sojib68' ); ?>"><?php echo esc_textarea( sojib68_property_meta( $post->ID, 'seller_note' ) ); ?></textarea></td></tr>
	</table>
	<?php
}

function sojib68_property_save( $post_id ) {
	if ( ! isset( $_POST['sojib68_property_nonce'] ) || ! wp_verify_nonce( wp_unslash( $_POST['sojib68_property_nonce'] ), 'sojib68_property_save' ) ) {
		return;
	}
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	$pick = function ( $key, $allowed, $default ) {
		$v = isset( $_POST[ $key ] ) ? sanitize_key( wp_unslash( $_POST[ $key ] ) ) : '';
		return isset( $allowed[ $v ] ) ? $v : $default;
	};
	$text = function ( $key ) {
		return isset( $_POST[ $key ] ) ? sanitize_text_field( wp_unslash( $_POST[ $key ] ) ) : '';
	};

	update_post_meta( $post_id, '_sp_type', $pick( 'sp_type', sojib68_property_types(), 'sale' ) );
	update_post_meta( $post_id, '_sp_category', $pick( 'sp_category', sojib68_property_categories(), 'land' ) );
	update_post_meta( $post_id, '_sp_unit', $pick( 'sp_unit', sojib68_property_units(), 'shotangsho' ) );
	update_post_meta( $post_id, '_sp_status', $pick( 'sp_status', sojib68_property_statuses(), 'available' ) );
	update_post_meta( $post_id, '_sp_location', $text( 'sp_location' ) );
	update_post_meta( $post_id, '_sp_size', sojib68_property_digits( $text( 'sp_size' ) ) );
	update_post_meta( $post_id, '_sp_price', preg_replace( '/\D+/', '', sojib68_property_digits( $text( 'sp_price' ) ) ) );
	update_post_meta( $post_id, '_sp_price_note', $text( 'sp_price_note' ) );
	update_post_meta( $post_id, '_sp_features', isset( $_POST['sp_features'] ) ? sanitize_textarea_field( wp_unslash( $_POST['sp_features'] ) ) : '' );
	update_post_meta( $post_id, '_sp_seller_name', $text( 'sp_seller_name' ) );
	update_post_meta( $post_id, '_sp_seller_phone', $text( 'sp_seller_phone' ) );
	update_post_meta( $post_id, '_sp_seller_note', isset( $_POST['sp_seller_note'] ) ? sanitize_textarea_field( wp_unslash( $_POST['sp_seller_note'] ) ) : '' );

	$ids = isset( $_POST['sp_gallery'] ) ? array_filter( array_map( 'intval', explode( ',', (string) wp_unslash( $_POST['sp_gallery'] ) ) ) ) : array();
	update_post_meta( $post_id, '_sp_gallery', implode( ',', $ids ) );
}
add_action( 'save_post_' . SOJIB68_PROPERTY_CPT, 'sojib68_property_save' );

/** A new or changed advertisement must show on the page at once, even behind a page cache (LiteSpeed). */
function sojib68_property_purge_cache( $post_id ) {
	if ( SOJIB68_PROPERTY_CPT !== get_post_type( $post_id ) ) {
		return;
	}
	if ( has_action( 'litespeed_purge_all' ) ) {
		do_action( 'litespeed_purge_all' );
	} elseif ( has_action( 'litespeed_purge_post' ) ) {
		do_action( 'litespeed_purge_post', $post_id );
	}
}
add_action( 'save_post', 'sojib68_property_purge_cache', 99 );
add_action( 'trashed_post', 'sojib68_property_purge_cache', 99 );

/** wp-admin list: show the reference, area, price and status next to the title. */
function sojib68_property_admin_columns( $columns ) {
	$out = array();
	foreach ( $columns as $key => $label ) {
		$out[ $key ] = $label;
		if ( 'title' === $key ) {
			$out['sp_ref']      = __( 'কোড', 'sojib68' );
			$out['sp_location'] = __( 'এলাকা', 'sojib68' );
			$out['sp_price']    = __( 'দাম', 'sojib68' );
			$out['sp_status']   = __( 'অবস্থা', 'sojib68' );
		}
	}
	return $out;
}
add_filter( 'manage_' . SOJIB68_PROPERTY_CPT . '_posts_columns', 'sojib68_property_admin_columns' );

function sojib68_property_admin_column( $column, $post_id ) {
	if ( 'sp_ref' === $column ) {
		echo esc_html( sojib68_property_ref( $post_id ) );
	} elseif ( 'sp_location' === $column ) {
		echo esc_html( sojib68_property_meta( $post_id, 'location' ) );
	} elseif ( 'sp_price' === $column ) {
		echo esc_html( sojib68_property_price_text( $post_id ) );
	} elseif ( 'sp_status' === $column ) {
		$st = sojib68_property_statuses();
		echo esc_html( $st[ sojib68_property_meta( $post_id, 'status', 'available' ) ] ?? '' );
	}
}
add_action( 'manage_' . SOJIB68_PROPERTY_CPT . '_posts_custom_column', 'sojib68_property_admin_column', 10, 2 );

/* ---------------------------------------------------------------------
 * The listing page: filters, order, paging
 * ------------------------------------------------------------------ */

function sojib68_property_filters() {
	$types = sojib68_property_types();
	$cats  = sojib68_property_categories();
	$sts   = sojib68_property_statuses();
	$type  = isset( $_GET['ptype'] ) ? sanitize_key( wp_unslash( $_GET['ptype'] ) ) : '';
	$cat   = isset( $_GET['pcat'] ) ? sanitize_key( wp_unslash( $_GET['pcat'] ) ) : '';
	$st    = isset( $_GET['pstatus'] ) ? sanitize_key( wp_unslash( $_GET['pstatus'] ) ) : '';
	return array(
		'type'   => isset( $types[ $type ] ) ? $type : '',
		'cat'    => isset( $cats[ $cat ] ) ? $cat : '',
		'status' => isset( $sts[ $st ] ) ? $st : '',
		'q'      => isset( $_GET['pq'] ) ? sanitize_text_field( wp_unslash( $_GET['pq'] ) ) : '',
	);
}

function sojib68_property_pre_get_posts( $query ) {
	if ( is_admin() || ! $query->is_main_query() || ! $query->is_post_type_archive( SOJIB68_PROPERTY_CPT ) ) {
		return;
	}
	$f    = sojib68_property_filters();
	$meta = array( 'relation' => 'AND' );
	if ( $f['type'] ) {
		$meta[] = array( 'key' => '_sp_type', 'value' => $f['type'] );
	}
	if ( $f['cat'] ) {
		$meta[] = array( 'key' => '_sp_category', 'value' => $f['cat'] );
	}
	if ( $f['status'] ) {
		$meta[] = array( 'key' => '_sp_status', 'value' => $f['status'] );
	} else {
		// by default what has been sold stays out of sight (it is still there under the "বিক্রি হয়ে গেছে" filter)
		$meta[] = array( 'relation' => 'OR', array( 'key' => '_sp_status', 'value' => 'sold', 'compare' => '!=' ), array( 'key' => '_sp_status', 'compare' => 'NOT EXISTS' ) );
	}
	if ( $f['q'] ) {
		$query->set( 's', $f['q'] );
	}
	$query->set( 'meta_query', $meta );
	$query->set( 'posts_per_page', SOJIB68_PROPERTY_PER_PAGE );
	$query->set( 'orderby', 'date' );
	$query->set( 'order', 'DESC' );
}
add_action( 'pre_get_posts', 'sojib68_property_pre_get_posts' );

function sojib68_property_is_page() {
	return is_post_type_archive( SOJIB68_PROPERTY_CPT ) || is_singular( SOJIB68_PROPERTY_CPT );
}

function sojib68_property_enqueue() {
	if ( ! sojib68_property_is_page() ) {
		return;
	}
	$css = get_theme_file_path( 'assets/css/property.css' );
	$js  = get_theme_file_path( 'assets/js/property.js' );
	wp_enqueue_style( 'sojib68-property', get_theme_file_uri( 'assets/css/property.css' ), array( 'sojib68-style' ), file_exists( $css ) ? filemtime( $css ) : '1.0.0' );
	wp_enqueue_script( 'sojib68-property', get_theme_file_uri( 'assets/js/property.js' ), array(), file_exists( $js ) ? filemtime( $js ) : '1.0.0', true );
}
add_action( 'wp_enqueue_scripts', 'sojib68_property_enqueue', 20 );

function sojib68_property_document_title( $title ) {
	if ( is_post_type_archive( SOJIB68_PROPERTY_CPT ) ) {
		return 'জমি ও প্রপার্টি | Ismail Hossain Sojib';
	}
	return $title;
}
add_filter( 'pre_get_document_title', 'sojib68_property_document_title' );

function sojib68_property_head() {
	if ( is_post_type_archive( SOJIB68_PROPERTY_CPT ) ) {
		echo '<meta name="description" content="' . esc_attr__( 'বিক্রি ও ভাড়ার জন্য জমি ও প্রপার্টির বিজ্ঞাপন। পছন্দের বিজ্ঞাপন সম্পর্কে জানতে আমার মাধ্যমে বিক্রেতার সঙ্গে যুক্ত হোন।', 'sojib68' ) . '">' . "\n";
	} elseif ( is_singular( SOJIB68_PROPERTY_CPT ) ) {
		$desc = wp_strip_all_tags( get_the_excerpt() ? get_the_excerpt() : get_the_title() );
		echo '<meta name="description" content="' . esc_attr( mb_substr( $desc, 0, 160 ) ) . '">' . "\n";
	}
}
add_action( 'wp_head', 'sojib68_property_head', 1 );

/** A small "বিজ্ঞাপন" card used by the listing page. */
function sojib68_property_card( $post_id ) {
	$types = sojib68_property_types();
	$cats  = sojib68_property_categories();
	$sts   = sojib68_property_statuses();
	$ids   = sojib68_property_image_ids( $post_id );
	$type  = sojib68_property_meta( $post_id, 'type', 'sale' );
	$cat   = sojib68_property_meta( $post_id, 'category', 'land' );
	$st    = sojib68_property_meta( $post_id, 'status', 'available' );
	$size  = sojib68_property_size_text( $post_id );
	$loc   = sojib68_property_meta( $post_id, 'location' );
	?>
	<article class="prop-card<?php echo 'sold' === $st ? ' is-sold' : ''; ?>">
		<a class="prop-card__media" href="<?php echo esc_url( get_permalink( $post_id ) ); ?>">
			<?php if ( $ids ) : ?>
				<?php echo wp_get_attachment_image( $ids[0], 'medium_large', false, array( 'loading' => 'lazy', 'alt' => get_the_title( $post_id ) ) ); // phpcs:ignore ?>
			<?php else : ?>
				<span class="prop-card__noimg"><?php esc_html_e( 'ছবি নেই', 'sojib68' ); ?></span>
			<?php endif; ?>
			<span class="prop-badges">
				<span class="prop-badge prop-badge--type"><?php echo esc_html( $types[ $type ] ?? '' ); ?></span>
				<?php if ( 'available' !== $st ) : ?>
					<span class="prop-badge prop-badge--status"><?php echo esc_html( $sts[ $st ] ?? '' ); ?></span>
				<?php endif; ?>
			</span>
		</a>
		<div class="prop-card__body">
			<p class="prop-card__cat"><?php echo esc_html( $cats[ $cat ] ?? '' ); ?> · <?php echo esc_html( sojib68_property_ref( $post_id ) ); ?></p>
			<h2 class="prop-card__title"><a href="<?php echo esc_url( get_permalink( $post_id ) ); ?>"><?php echo esc_html( get_the_title( $post_id ) ); ?></a></h2>
			<?php if ( $loc ) : ?><p class="prop-card__loc"><?php echo esc_html( $loc ); ?></p><?php endif; ?>
			<p class="prop-card__meta">
				<?php if ( $size ) : ?><span><?php echo esc_html( $size ); ?></span><?php endif; ?>
				<strong><?php echo esc_html( sojib68_property_price_text( $post_id ) ); ?></strong>
			</p>
			<a class="btn btn-outline btn-sm prop-card__more" href="<?php echo esc_url( get_permalink( $post_id ) ); ?>"><?php esc_html_e( 'বিস্তারিত দেখুন', 'sojib68' ); ?></a>
		</div>
	</article>
	<?php
}
