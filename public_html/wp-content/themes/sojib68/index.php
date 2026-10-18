<?php
/**
 * The main template file.
 */

get_header();
?>

<?php if ( have_posts() ) : ?>

	<?php while ( have_posts() ) : the_post(); ?>

		<article id="post-<?php the_ID(); ?>" <?php post_class( 'post' ); ?>>
			<h2 class="entry-title">
				<a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
			</h2>
			<div class="entry-meta">
				<?php echo esc_html( get_the_date() ); ?> &middot; <?php the_author(); ?>
			</div>
			<div class="entry-content">
				<?php the_excerpt(); ?>
			</div>
		</article>

	<?php endwhile; ?>

	<?php the_posts_pagination(); ?>

<?php else : ?>

	<p><?php esc_html_e( 'No content found.', 'sojib68' ); ?></p>

<?php endif; ?>

<?php
get_footer();
