<?php
/**
 * Public /products page — "ডিজিটাল প্রোডাক্ট" (coming soon, no prices).
 * WordPress page with slug "products" → page-products.php; with no such
 * page the same template is served instead of a 404 (same approach as /about).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function sojib68_products_request_path() {
	$request_path = (string) wp_parse_url( add_query_arg( array() ), PHP_URL_PATH );
	$home_path    = rtrim( (string) wp_parse_url( home_url(), PHP_URL_PATH ), '/' );
	if ( $home_path && 0 === strpos( $request_path, $home_path ) ) {
		$request_path = substr( $request_path, strlen( $home_path ) );
	}
	return trim( $request_path, '/' );
}

function sojib68_products_is_fallback() {
	return is_404() && 'products' === sojib68_products_request_path();
}

function sojib68_is_products_page() {
	return is_page( 'products' ) || sojib68_products_is_fallback();
}

function sojib68_products_template( $template ) {
	if ( sojib68_products_is_fallback() ) {
		global $wp_query;
		$wp_query->is_404 = false;
		status_header( 200 );
		return get_theme_file_path( 'page-products.php' );
	}
	return $template;
}
add_filter( 'template_include', 'sojib68_products_template' );

function sojib68_products_keep_canonical( $url ) {
	return sojib68_products_is_fallback() ? false : $url;
}
add_filter( 'redirect_canonical', 'sojib68_products_keep_canonical' );

function sojib68_products_body_class( $classes ) {
	if ( sojib68_is_products_page() ) {
		$classes   = array_diff( $classes, array( 'error404' ) );
		$classes[] = 'page-products';
	}
	return $classes;
}
add_filter( 'body_class', 'sojib68_products_body_class' );

function sojib68_products_title( $title ) {
	return sojib68_is_products_page() ? 'ডিজিটাল প্রোডাক্ট | Ismail Hossain Sojib' : $title;
}
add_filter( 'pre_get_document_title', 'sojib68_products_title' );

function sojib68_products_head() {
	if ( sojib68_is_products_page() ) {
		echo '<meta name="description" content="' . esc_attr( 'জমি ও ব্যবসা সংক্রান্ত ডিজিটাল প্রোডাক্ট — মৌজা ম্যাপ, বিজনেস কিট, বই ও সম্পত্তির হিসাব। শীঘ্রই আসছে।' ) . '">' . "\n";
	}
}
add_action( 'wp_head', 'sojib68_products_head', 1 );

function sojib68_products_enqueue() {
	if ( ! sojib68_is_products_page() ) {
		return;
	}
	$path = get_theme_file_path( 'assets/css/products-page.css' );
	wp_enqueue_style( 'sojib68-products-page', get_theme_file_uri( 'assets/css/products-page.css' ), array( 'sojib68-style' ), file_exists( $path ) ? filemtime( $path ) : '1.0.0' );
}
add_action( 'wp_enqueue_scripts', 'sojib68_products_enqueue', 20 );
