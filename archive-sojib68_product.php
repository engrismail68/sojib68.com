<?php
/** ডিজিটাল প্রোডাক্ট — listing (/products/). Data from inc/products.php; styles in assets/css/products-page.css. */
get_header();
$cats   = get_terms( array( 'taxonomy' => SOJIB68_PRODUCT_TAX, 'hide_empty' => true ) );
$active = isset( $_GET['pcat'] ) ? sanitize_title( wp_unslash( $_GET['pcat'] ) ) : '';
$base   = get_post_type_archive_link( SOJIB68_PRODUCT_CPT );
?>
<main class="prd-page">
	<div class="site-container">
		<header class="prd-head">
			<p class="eyebrow"><?php esc_html_e( 'ডিজিটাল প্রোডাক্ট', 'sojib68' ); ?></p>
			<h1><?php esc_html_e( 'জমি ও ব্যবসার কাজের ডিজিটাল টুলস', 'sojib68' ); ?></h1>
		</header>
		<?php if ( have_posts() || $active ) : ?>
		<div class="prd-layout">
			<?php if ( ! is_wp_error( $cats ) && $cats ) : ?>
			<aside class="prd-side" aria-label="<?php esc_attr_e( 'ক্যাটাগরি', 'sojib68' ); ?>">
				<h3><?php esc_html_e( 'ক্যাটাগরি', 'sojib68' ); ?></h3>
				<ul>
					<li><a href="<?php echo esc_url( $base ); ?>" class="<?php echo $active ? '' : 'is-on'; ?>"><?php esc_html_e( 'সব প্রোডাক্ট', 'sojib68' ); ?></a></li>
					<?php foreach ( $cats as $c ) : ?>
						<li><a href="<?php echo esc_url( add_query_arg( 'pcat', $c->slug, $base ) ); ?>" class="<?php echo $active === $c->slug ? 'is-on' : ''; ?>"><?php echo esc_html( $c->name ); ?></a></li>
					<?php endforeach; ?>
				</ul>
			</aside>
			<?php endif; ?>
			<div class="prd-main">
				<?php if ( have_posts() ) : ?>
					<ul class="prd-grid"><?php while ( have_posts() ) { the_post(); sojib68_product_card(); } ?></ul>
					<?php the_posts_pagination( array( 'prev_text' => '‹', 'next_text' => '›' ) ); ?>
				<?php else : ?>
					<p><?php esc_html_e( 'এই ক্যাটাগরিতে এখনো কোনো প্রোডাক্ট নেই।', 'sojib68' ); ?></p>
				<?php endif; ?>
			</div>
		</div>
		<?php else : ?>
			<p class="prd-lead"><?php esc_html_e( 'ডিজিটাল প্রোডাক্টগুলো খুব শীঘ্রই আসছে।', 'sojib68' ); ?></p>
			<p class="prd-cta"><a href="<?php echo esc_url( home_url( '/#contact' ) ); ?>" class="btn btn-primary" data-inq-open><?php esc_html_e( 'আগে জানতে যোগাযোগ করুন', 'sojib68' ); ?></a></p>
		<?php endif; ?>
	</div>
</main>
<?php get_footer();
