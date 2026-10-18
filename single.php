<?php
/**
 * The template for displaying single posts.
 */

get_header();
?>

<div class="site-container page-section">

<?php while ( have_posts() ) : the_post(); ?>

	<article id="post-<?php the_ID(); ?>" <?php post_class( 'post' ); ?>>
		<h1 class="entry-title"><?php the_title(); ?></h1>
		<div class="entry-meta">
			<?php echo esc_html( get_the_date() ); ?> &middot; <?php the_author(); ?>
		</div>
		<div class="entry-content">
			<?php the_content(); ?>
		</div>
	</article>

	<?php if ( comments_open() || get_comments_number() ) : ?>
		<?php comments_template(); ?>
	<?php endif; ?>

<?php endwhile; ?>

</div>

<?php
get_footer();
