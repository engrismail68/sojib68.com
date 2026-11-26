<?php
/** Single product page. */
get_header();
while ( have_posts() ) :
	the_post();
	$pr = sojib68_product_prices( get_the_ID() );
	$t  = get_the_title();
	?>
<main class="prd-page">
	<div class="site-container prd-single">
		<div class="prd-single__img"><?php if ( has_post_thumbnail() ) { the_post_thumbnail( 'large', array( 'alt' => $t ) ); } ?></div>
		<div class="prd-single__body">
			<h1><?php echo esc_html( $t ); ?></h1>
			<?php if ( $pr['price'] ) : ?><p class="prd-price"><strong>৳ <?php echo esc_html( sojib68_bn_num( $pr['price'] ) ); ?></strong><?php if ( $pr['old'] ) : ?> <s>৳ <?php echo esc_html( sojib68_bn_num( $pr['old'] ) ); ?></s> <span class="prd-badge prd-badge--inline">-<?php echo esc_html( sojib68_bn_num( $pr['off'] ) ); ?>%</span><?php endif; ?></p><?php endif; ?>
			<div class="prd-content"><?php the_content(); ?></div>
			<a class="prd-buy prd-buy--lg" href="<?php echo esc_url( home_url( '/#contact' ) ); ?>" data-inq-open data-lead="product_buy" data-inq-text="<?php echo esc_attr( sprintf( __( 'আমি এই প্রোডাক্টটি কিনতে চাই: %s', 'sojib68' ), $t ) ); ?>"><?php esc_html_e( 'এখনই কিনুন', 'sojib68' ); ?></a>
			<p><a href="<?php echo esc_url( get_post_type_archive_link( SOJIB68_PRODUCT_CPT ) ); ?>">← <?php esc_html_e( 'সব প্রোডাক্ট', 'sojib68' ); ?></a></p>
		</div>
	</div>
</main>
<?php endwhile; get_footer();
