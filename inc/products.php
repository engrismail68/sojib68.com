<?php
/**
 * ডিজিটাল প্রোডাক্ট — the shop listing (/products/), managed from wp-admin → প্রোডাক্ট.
 * Owner adds a product (title, image, price, old price, category); the page shows a card grid with a
 * category sidebar. "এখনই কিনুন" opens the inquiry form with the product named, until payment is built.
 * No prices or claims are invented here: everything shown comes from what the owner enters.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'SOJIB68_PRODUCT_CPT', 'sojib68_product' );
define( 'SOJIB68_PRODUCT_TAX', 'sojib68_product_cat' );

function sojib68_product_register() {
	register_post_type( SOJIB68_PRODUCT_CPT, array(
		'labels'       => array(
			'name'          => __( 'প্রোডাক্ট', 'sojib68' ),
			'singular_name' => __( 'প্রোডাক্ট', 'sojib68' ),
			'add_new'       => __( 'নতুন প্রোডাক্ট', 'sojib68' ),
			'add_new_item'  => __( 'নতুন প্রোডাক্ট যোগ করুন', 'sojib68' ),
			'edit_item'     => __( 'প্রোডাক্ট সম্পাদনা', 'sojib68' ),
			'not_found'     => __( 'কোনো প্রোডাক্ট নেই', 'sojib68' ),
		),
		'public'       => true,
		'has_archive'  => 'products',
		'rewrite'      => array( 'slug' => 'product', 'with_front' => false ),
		'menu_icon'    => 'dashicons-cart',
		'menu_position'=> 21,
		'supports'     => array( 'title', 'editor', 'thumbnail', 'excerpt' ),
		'show_in_rest' => false,
	) );
	register_taxonomy( SOJIB68_PRODUCT_TAX, SOJIB68_PRODUCT_CPT, array(
		'labels'            => array( 'name' => __( 'প্রোডাক্ট ক্যাটাগরি', 'sojib68' ), 'singular_name' => __( 'ক্যাটাগরি', 'sojib68' ) ),
		'hierarchical'      => true,
		'show_admin_column' => true,
		'public'            => false,
		'show_ui'           => true,
		'rewrite'           => false,
	) );
}
add_action( 'init', 'sojib68_product_register' );

/** Flush rewrite rules once after this feature first ships so /products/ resolves. */
function sojib68_product_flush_once() {
	if ( get_option( 'sojib68_product_rw' ) !== '1' ) {
		flush_rewrite_rules( false );
		update_option( 'sojib68_product_rw', '1', false );
	}
}
add_action( 'init', 'sojib68_product_flush_once', 99 );

/* ---- price fields (wp-admin) ---- */
function sojib68_product_metabox() {
	add_meta_box( 'sojib68_product_price', __( 'দাম', 'sojib68' ), function ( $post ) {
		wp_nonce_field( 'sojib68_product_save', 'sojib68_product_nonce' );
		$p = get_post_meta( $post->ID, '_sojib68_price', true );
		$o = get_post_meta( $post->ID, '_sojib68_old_price', true );
		echo '<p><label>' . esc_html__( 'বিক্রির দাম (৳)', 'sojib68' ) . '<br><input type="number" min="0" step="1" name="sojib68_price" value="' . esc_attr( $p ) . '"></label></p>';
		echo '<p><label>' . esc_html__( 'আগের দাম (৳, ঐচ্ছিক — দিলে ছাড়ের % দেখাবে)', 'sojib68' ) . '<br><input type="number" min="0" step="1" name="sojib68_old_price" value="' . esc_attr( $o ) . '"></label></p>';
	}, SOJIB68_PRODUCT_CPT, 'side' );
}
add_action( 'add_meta_boxes', 'sojib68_product_metabox' );

function sojib68_product_save( $post_id ) {
	if ( ! isset( $_POST['sojib68_product_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['sojib68_product_nonce'] ) ), 'sojib68_product_save' ) || ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}
	foreach ( array( 'sojib68_price' => '_sojib68_price', 'sojib68_old_price' => '_sojib68_old_price' ) as $field => $meta ) {
		$v = isset( $_POST[ $field ] ) ? absint( wp_unslash( $_POST[ $field ] ) ) : 0;
		if ( $v ) {
			update_post_meta( $post_id, $meta, $v );
		} else {
			delete_post_meta( $post_id, $meta );
		}
	}
}
add_action( 'save_post_' . SOJIB68_PRODUCT_CPT, 'sojib68_product_save' );

function sojib68_product_prices( $id ) {
	$p = (int) get_post_meta( $id, '_sojib68_price', true );
	$o = (int) get_post_meta( $id, '_sojib68_old_price', true );
	return array(
		'price' => $p,
		'old'   => $o > $p ? $o : 0,
		'off'   => ( $p && $o > $p ) ? (int) round( ( $o - $p ) * 100 / $o ) : 0,
	);
}

/** Bangla digits for display. */
function sojib68_bn_num( $n ) {
	return strtr( (string) $n, array( '0' => '০', '1' => '১', '2' => '২', '3' => '৩', '4' => '৪', '5' => '৫', '6' => '৬', '7' => '৭', '8' => '৮', '9' => '৯' ) );
}

/** Archive query: optional category filter (?pcat=slug), 12 per page. */
function sojib68_product_pre_get( $q ) {
	if ( is_admin() || ! $q->is_main_query() || ! $q->is_post_type_archive( SOJIB68_PRODUCT_CPT ) ) {
		return;
	}
	$q->set( 'posts_per_page', 12 );
	$cat = isset( $_GET['pcat'] ) ? sanitize_title( wp_unslash( $_GET['pcat'] ) ) : '';
	if ( $cat ) {
		$q->set( 'tax_query', array( array( 'taxonomy' => SOJIB68_PRODUCT_TAX, 'field' => 'slug', 'terms' => $cat ) ) );
	}
}
add_action( 'pre_get_posts', 'sojib68_product_pre_get' );

function sojib68_product_enqueue() {
	if ( ! is_post_type_archive( SOJIB68_PRODUCT_CPT ) && ! is_singular( SOJIB68_PRODUCT_CPT ) ) {
		return;
	}
	$path = get_theme_file_path( 'assets/css/products-page.css' );
	wp_enqueue_style( 'sojib68-products-page', get_theme_file_uri( 'assets/css/products-page.css' ), array( 'sojib68-style' ), file_exists( $path ) ? filemtime( $path ) : '1.0.0' );
}
add_action( 'wp_enqueue_scripts', 'sojib68_product_enqueue', 20 );

function sojib68_product_title( $title ) {
	return is_post_type_archive( SOJIB68_PRODUCT_CPT ) ? 'ডিজিটাল প্রোডাক্ট | Ismail Hossain Sojib' : $title;
}
add_filter( 'pre_get_document_title', 'sojib68_product_title' );

/** One product card (shared by the archive). */
function sojib68_product_card() {
	$id = get_the_ID();
	$pr = sojib68_product_prices( $id );
	$t  = get_the_title();
	?>
	<li class="prd-card">
		<a class="prd-card__img" href="<?php the_permalink(); ?>">
			<?php if ( $pr['off'] ) : ?><span class="prd-badge">-<?php echo esc_html( sojib68_bn_num( $pr['off'] ) ); ?>% ছাড়</span><?php endif; ?>
			<?php if ( has_post_thumbnail() ) { the_post_thumbnail( 'medium_large', array( 'loading' => 'lazy', 'alt' => $t ) ); } else { echo '<span class="prd-noimg" aria-hidden="true">📦</span>'; } ?>
		</a>
		<h2 class="prd-card__title"><a href="<?php the_permalink(); ?>"><?php echo esc_html( $t ); ?></a></h2>
		<?php if ( $pr['price'] ) : ?>
			<p class="prd-price"><strong>৳ <?php echo esc_html( sojib68_bn_num( $pr['price'] ) ); ?></strong><?php if ( $pr['old'] ) : ?> <s>৳ <?php echo esc_html( sojib68_bn_num( $pr['old'] ) ); ?></s><?php endif; ?></p>
		<?php endif; ?>
		<a class="prd-buy" href="<?php echo esc_url( home_url( '/#contact' ) ); ?>" data-inq-open data-lead="product_buy" data-inq-text="<?php echo esc_attr( sprintf( /* translators: %s product title */ __( "আমি এই প্রোডাক্টটি কিনতে চাই: %s", 'sojib68' ), $t ) ); ?>"><?php esc_html_e( 'এখনই কিনুন', 'sojib68' ); ?></a>
	</li>
	<?php
}
