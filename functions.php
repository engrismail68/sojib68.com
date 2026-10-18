<?php
/**
 * Sojib68 theme functions and definitions.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once get_theme_file_path( 'inc/github-sync.php' );

function sojib68_setup() {
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'automatic-feed-links' );
	add_theme_support( 'html5', array( 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption' ) );

	register_nav_menus( array(
		'primary' => __( 'Primary Menu', 'sojib68' ),
		'footer'  => __( 'Footer Menu', 'sojib68' ),
	) );
}
add_action( 'after_setup_theme', 'sojib68_setup' );

function sojib68_enqueue_assets() {
	wp_enqueue_style( 'sojib68-style', get_stylesheet_uri(), array(), '1.0.0' );
}
add_action( 'wp_enqueue_scripts', 'sojib68_enqueue_assets' );

function sojib68_widgets_init() {
	register_sidebar( array(
		'name'          => __( 'Sidebar', 'sojib68' ),
		'id'            => 'sidebar-1',
		'before_widget' => '<div class="widget">',
		'after_widget'  => '</div>',
		'before_title'  => '<h3 class="widget-title">',
		'after_title'   => '</h3>',
	) );
}
add_action( 'widgets_init', 'sojib68_widgets_init' );
