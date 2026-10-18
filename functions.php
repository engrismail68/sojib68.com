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
		'https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@400;500;600;700&display=swap',
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
		'buy-sell'       => array(
			'title' => 'জমি কিনবেন বা বিক্রি করবেন?',
			'text'  => 'কেনাবেচার প্রস্তুতি, প্রয়োজনীয় কাগজপত্র ও পরবর্তী করণীয় সম্পর্কে সহায়তা।',
			'icon'  => 'handshake',
		),
		'registration'   => array(
			'title' => 'জমি রেজিস্ট্রেশন করবেন?',
			'text'  => 'দলিল প্রস্তুতি ও রেজিস্ট্রেশন প্রক্রিয়ার প্রয়োজনীয় ধাপে সহায়তা।',
			'icon'  => 'document',
		),
		'mutation-tax'   => array(
			'title' => 'নামজারি বা ভূমি করের কাজ?',
			'text'  => 'নামজারি আবেদন ও ভূমি উন্নয়ন করসংক্রান্ত প্রয়োজনীয় কাজে সহায়তা।',
			'icon'  => 'receipt',
		),
		'lost-deed'      => array(
			'title' => 'দলিল হারিয়ে গেছে?',
			'text'  => 'দলিলের তথ্য অনুসন্ধান ও সার্টিফাইড কপি সংগ্রহে সহায়তা।',
			'icon'  => 'search',
		),
		'document-issue' => array(
			'title' => 'কাগজপত্রে সমস্যা আছে?',
			'text'  => 'দলিল ও সংশ্লিষ্ট কাগজপত্র দেখে পরবর্তী করণীয় সম্পর্কে পরামর্শ।',
			'icon'  => 'alert',
		),
		'measurement'    => array(
			'title' => 'জমি পরিমাপ করতে চান?',
			'text'  => 'জমি পরিমাপ ও পরিমাণ নির্ণয়ে প্রয়োজনীয় সেবার ব্যবস্থা।',
			'icon'  => 'ruler',
		),
	);
}

/**
 * Outputs a small inline SVG line icon by name. Keeping these inline
 * avoids pulling in an icon font/library for six icons.
 */
function sojib68_icon( $name ) {
	$icons = array(
		'handshake' => '<path d="M3 12l4-4 4 4 2-2 2 2 4-4 2 2"/><path d="M3 12v4a2 2 0 0 0 2 2h2"/><path d="M19 12v4a2 2 0 0 1-2 2h-2"/>',
		'document'  => '<path d="M7 3h7l5 5v13a1 1 0 0 1-1 1H7a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1z"/><path d="M14 3v5h5"/><path d="M9 13h6M9 17h6"/>',
		'receipt'   => '<path d="M6 3h12v18l-3-2-3 2-3-2-3 2V3z"/><path d="M9 8h6M9 12h6"/>',
		'search'    => '<circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/>',
		'alert'     => '<path d="M12 3L2 20h20L12 3z"/><path d="M12 10v4"/><circle cx="12" cy="17" r="0.5" fill="currentColor"/>',
		'ruler'     => '<path d="M3 16l10-10 5 5-10 10-5-5z"/><path d="M10.5 8.5l2 2M7.5 11.5l2 2M13.5 5.5l2 2"/>',
	);

	if ( ! isset( $icons[ $name ] ) ) {
		return '';
	}

	return '<svg class="icon" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $icons[ $name ] . '</svg>';
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
