<?php
/**
 * The template for displaying 404 pages (not found).
 */

get_header();
?>

<article class="page">
	<h1 class="entry-title"><?php esc_html_e( 'Page Not Found', 'sojib68' ); ?></h1>
	<p><?php esc_html_e( 'The page you are looking for does not exist.', 'sojib68' ); ?></p>
	<p>
		<a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Back to homepage', 'sojib68' ); ?></a>
	</p>
</article>

<?php
get_footer();
