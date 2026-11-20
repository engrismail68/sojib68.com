<?php
/**
 * Public /deed-writing page — "দলিল লিখুন".
 * WordPress page with slug "deed-writing" → page-deed-writing.php; with no such
 * page the same template is served instead of a 404 (same approach as /about).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function sojib68_deed_request_path() {
	$request_path = (string) wp_parse_url( add_query_arg( array() ), PHP_URL_PATH );
	$home_path    = rtrim( (string) wp_parse_url( home_url(), PHP_URL_PATH ), '/' );
	if ( $home_path && 0 === strpos( $request_path, $home_path ) ) {
		$request_path = substr( $request_path, strlen( $home_path ) );
	}
	return trim( $request_path, '/' );
}

function sojib68_deed_is_fallback() {
	return is_404() && 'deed-writing' === sojib68_deed_request_path();
}

function sojib68_is_deed_page() {
	return is_page( 'deed-writing' ) || sojib68_deed_is_fallback();
}

function sojib68_deed_template( $template ) {
	if ( sojib68_deed_is_fallback() ) {
		global $wp_query;
		$wp_query->is_404 = false;
		status_header( 200 );
		return get_theme_file_path( 'page-deed-writing.php' );
	}
	return $template;
}
add_filter( 'template_include', 'sojib68_deed_template' );

function sojib68_deed_keep_canonical( $url ) {
	return sojib68_deed_is_fallback() ? false : $url;
}
add_filter( 'redirect_canonical', 'sojib68_deed_keep_canonical' );

function sojib68_deed_body_class( $classes ) {
	if ( sojib68_is_deed_page() ) {
		$classes   = array_diff( $classes, array( 'error404' ) );
		$classes[] = 'page-deed-writing';
	}
	return $classes;
}
add_filter( 'body_class', 'sojib68_deed_body_class' );

function sojib68_deed_title( $title ) {
	return sojib68_is_deed_page() ? 'দলিল লিখুন | Ismail Hossain Sojib' : $title;
}
add_filter( 'pre_get_document_title', 'sojib68_deed_title' );

function sojib68_deed_head() {
	if ( sojib68_is_deed_page() ) {
		echo '<meta name="description" content="' . esc_attr( 'দলিল লেখার পেশায় তিন প্রজন্মের অভিজ্ঞতা। দলিল লেখাতে চাইলে যোগাযোগ করুন।' ) . '">' . "\n";
	}
}
add_action( 'wp_head', 'sojib68_deed_head', 1 );

function sojib68_deed_enqueue() {
	if ( ! sojib68_is_deed_page() ) {
		return;
	}
	$path = get_theme_file_path( 'assets/css/deed-page.css' );
	wp_enqueue_style( 'sojib68-deed-page', get_theme_file_uri( 'assets/css/deed-page.css' ), array( 'sojib68-style' ), file_exists( $path ) ? filemtime( $path ) : '1.0.0' );
}
add_action( 'wp_enqueue_scripts', 'sojib68_deed_enqueue', 20 );
