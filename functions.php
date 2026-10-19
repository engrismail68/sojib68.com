<?php
/**
 * Sojib68 theme functions and definitions.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once get_theme_file_path( 'inc/github-sync.php' );
require_once get_theme_file_path( 'inc/contact-form.php' );

/**
 * Business contact details.
 *
 * Edit these once verified details are available. Anything left empty
 * is hidden on the front end instead of showing a placeholder.
 */
if ( ! defined( 'SOJIB68_PHONE' ) ) {
	define( 'SOJIB68_PHONE', '' ); // e.g. '+8801XXXXXXXXX'
}
if ( ! defined( 'SOJIB68_WHATSAPP' ) ) {
	define( 'SOJIB68_WHATSAPP', '' ); // e.g. '8801XXXXXXXXX' (digits only, with country code)
}
if ( ! defined( 'SOJIB68_EMAIL' ) ) {
	define( 'SOJIB68_EMAIL', get_option( 'admin_email' ) );
}
if ( ! defined( 'SOJIB68_ADDRESS' ) ) {
	define( 'SOJIB68_ADDRESS', '' );
}

function sojib68_setup() {
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'automatic-feed-links' );
	add_theme_support( 'custom-logo', array(
		'height'      => 90,
		'width'       => 300,
		'flex-height' => true,
		'flex-width'  => true,
	) );
	add_theme_support( 'html5', array( 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption' ) );

	register_nav_menus( array(
		'primary' => __( 'Primary Menu', 'sojib68' ),
		'footer'  => __( 'Footer Menu', 'sojib68' ),
	) );
}
add_action( 'after_setup_theme', 'sojib68_setup' );

function sojib68_enqueue_assets() {
	wp_enqueue_style(
		'sojib68-fonts',
		'https://fonts.googleapis.com/css2?family=Figtree:wght@400;500;600;700;800&family=Hind+Siliguri:wght@400;500;600;700&display=swap',
		array(),
		null
	);
	wp_enqueue_style( 'sojib68-style', get_stylesheet_uri(), array( 'sojib68-fonts' ), '1.0.0' );

	wp_enqueue_script(
		'sojib68-main',
		get_theme_file_uri( 'assets/js/main.js' ),
		array(),
		'1.0.0',
		true
	);

	wp_localize_script( 'sojib68-main', 'sojib68Contact', array(
		'ajaxUrl' => admin_url( 'admin-ajax.php' ),
		'nonce'   => wp_create_nonce( 'sojib68_contact_form' ),
	) );
}
add_action( 'wp_enqueue_scripts', 'sojib68_enqueue_assets' );

/**
 * Central list of services, shared by the homepage grid and the contact
 * form's service dropdown so the two never drift apart.
 */
function sojib68_get_services() {
	return array(
		'buy-sell-registration' => array(
			'number' => '০১',
			'title'  => 'জমি কেনাবেচা ও রেজিস্ট্রেশন',
			'text'   => 'কেনাবেচার প্রস্তুতি, প্রয়োজনীয় কাগজপত্র এবং দলিল রেজিস্ট্রেশনের ধাপে সহায়তা।',
		),
		'mutation-tax'          => array(
			'number' => '০২',
			'title'  => 'নামজারি ও ভূমি উন্নয়ন কর',
			'text'   => 'নামজারি আবেদন ও ভূমি উন্নয়ন করসংক্রান্ত প্রয়োজনীয় কাজে সহায়তা।',
		),
		'document-search'       => array(
			'number' => '০৩',
			'title'  => 'দলিল অনুসন্ধান ও সার্টিফাইড কপি',
			'text'   => 'হারানো বা পুরোনো দলিলের তথ্য অনুসন্ধান এবং সার্টিফাইড কপি সংগ্রহে সহায়তা।',
		),
		'document-issue'        => array(
			'number' => '০৪',
			'title'  => 'কাগজপত্রের জটিলতা ও পরামর্শ',
			'text'   => 'দলিল ও সংশ্লিষ্ট কাগজপত্র নিয়ে সমস্যায় প্রয়োজনীয় করণীয় বুঝতে সহায়তা।',
		),
		'measurement'           => array(
			'number' => '০৫',
			'title'  => 'জমি পরিমাপ',
			'text'   => 'জমির পরিমাণ নির্ণয় ও পরিমাপসংক্রান্ত সেবার ব্যবস্থা।',
		),
	);
}

/**
 * Verified testimonials go here once available. The testimonials
 * section is hidden entirely while this is empty — no placeholder or
 * fabricated reviews are ever shown.
 */
function sojib68_get_testimonials() {
	return array();
}

/**
 * Real case stories go here once available (with permission to
 * publish). Until then the portfolio section renders an honest
 * "content coming soon" placeholder instead of invented stories.
 */
function sojib68_get_case_stories() {
	return array();
}

/**
 * Verified social links. Add only confirmed URLs — empty entries are
 * hidden instead of rendering a dead icon.
 */
function sojib68_get_social_links() {
	return array(
		// 'facebook' => 'https://facebook.com/...',
	);
}

/**
 * Fixed title/description for the homepage, independent of the Site
 * Title/Tagline set in wp-admin (which may not be filled in yet).
 */
function sojib68_front_page_title( $title ) {
	if ( is_front_page() ) {
		$title = array(
			'title' => __( 'Ismail Hossain Sojib | Land & IT Consultant', 'sojib68' ),
		);
	}
	return $title;
}
add_filter( 'document_title_parts', 'sojib68_front_page_title' );

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
