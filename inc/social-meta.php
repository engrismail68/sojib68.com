<?php
/**
 * Link previews (Messenger, WhatsApp, Facebook, X ...): Open Graph + Twitter card tags with a proper
 * 1200x630 share image, so the logo is shown whole instead of a cropped piece of the page.
 * Skipped when an SEO plugin already prints its own tags.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function sojib68_social_meta() {
	if ( is_admin() || is_feed() || defined( 'WPSEO_VERSION' ) || defined( 'RANK_MATH_VERSION' ) || defined( 'AIOSEO_VERSION' ) ) {
		return;
	}
	$image = get_theme_file_uri( 'assets/images/og-share.jpg' );
	$title = wp_get_document_title();
	$desc  = 'জমি রেজিস্ট্রেশন, নামজারি, দলিল অনুসন্ধান, সার্টিফাইড কপি ও ভূমিসংক্রান্ত বিষয়ে পরামর্শ ও সহায়তা।';
	if ( function_exists( 'sojib68_is_deed_page' ) && sojib68_is_deed_page() ) {
		$desc = 'দলিল লেখার সিস্টেম — শুধু অনুমোদিত দলিল লেখক ও অফিসের কর্মীদের জন্য। দলিল লেখার জন্য লগইন করুন।';
	} elseif ( is_singular() ) {
		$ex = has_excerpt() ? get_the_excerpt() : '';
		if ( '' !== trim( wp_strip_all_tags( $ex ) ) ) {
			$desc = wp_trim_words( wp_strip_all_tags( $ex ), 35, '…' );
		}
	}
	global $wp;
	$url = home_url( add_query_arg( array(), $wp->request ? '/' . ltrim( $wp->request, '/' ) . '/' : '/' ) );

	$tags = array(
		array( 'property', 'og:type', is_front_page() ? 'website' : 'article' ),
		array( 'property', 'og:site_name', 'Ismail Hossain Sojib' ),
		array( 'property', 'og:locale', 'bn_BD' ),
		array( 'property', 'og:title', $title ),
		array( 'property', 'og:description', $desc ),
		array( 'property', 'og:url', $url ),
		array( 'property', 'og:image', $image ),
		array( 'property', 'og:image:secure_url', $image ),
		array( 'property', 'og:image:type', 'image/jpeg' ),
		array( 'property', 'og:image:width', '1200' ),
		array( 'property', 'og:image:height', '630' ),
		array( 'property', 'og:image:alt', 'Ismail Hossain Sojib — Land & IT Consultant' ),
		array( 'name', 'twitter:card', 'summary_large_image' ),
		array( 'name', 'twitter:title', $title ),
		array( 'name', 'twitter:description', $desc ),
		array( 'name', 'twitter:image', $image ),
	);
	foreach ( $tags as $t ) {
		echo '<meta ' . esc_attr( $t[0] ) . '="' . esc_attr( $t[1] ) . '" content="' . esc_attr( $t[2] ) . '">' . "\n";
	}
}
add_action( 'wp_head', 'sojib68_social_meta', 2 );
