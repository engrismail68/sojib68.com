<?php
/**
 * The template for displaying pages.
 */

get_header();
?>

<div class="site-container page-section">

<?php while ( have_posts() ) : the_post(); ?>

	<article id="post-<?php the_ID(); ?>" <?php post_class( 'page' ); ?>>
		<h1 class="entry-title"><?php the_title(); ?></h1>
		<div class="entry-content">
			<?php the_content(); ?>
		</div>
	</article>

<?php endwhile; ?>

</div>

<?php
get_footer();
