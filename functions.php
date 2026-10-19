<?php
/**
 * Sojib68 theme functions and definitions.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once get_theme_file_path( 'inc/github-sync.php' );
require_once get_theme_file_path( 'inc/contact-form.php' );
require_once get_theme_file_path( 'inc/office-system.php' );

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
	$style_version = file_exists( get_stylesheet_directory() . '/style.css' )
		? filemtime( get_stylesheet_directory() . '/style.css' )
		: '1.0.0';
	wp_enqueue_style( 'sojib68-style', get_stylesheet_uri(), array( 'sojib68-fonts' ), $style_version );

	$script_path = get_theme_file_path( 'assets/js/main.js' );
	$script_version = file_exists( $script_path ) ? filemtime( $script_path ) : '1.0.0';
	wp_enqueue_script(
		'sojib68-main',
		get_theme_file_uri( 'assets/js/main.js' ),
		array(),
		$script_version,
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
		'registration'     => array(
			'title' => 'জমি রেজিস্ট্রেশন',
			'text'  => 'প্রয়োজনীয় কাগজপত্র ও দলিল রেজিস্ট্রেশনের বিভিন্ন ধাপে সহায়তা।',
			'icon'  => 'document',
		),
		'buy-sell'         => array(
			'title' => 'জমি কেনাবেচা',
			'text'  => 'জমি কেনা বা বিক্রির প্রস্তুতি ও প্রয়োজনীয় প্রক্রিয়ায় পরামর্শ এবং সহায়তা।',
			'icon'  => 'handshake',
		),
		'mutation'         => array(
			'title' => 'নামজারি',
			'text'  => 'নামজারি আবেদন ও সংশ্লিষ্ট প্রক্রিয়ায় প্রয়োজনীয় সহায়তা।',
			'icon'  => 'stamp',
		),
		'land-tax'         => array(
			'title' => 'ভূমি উন্নয়ন কর',
			'text'  => 'ভূমি উন্নয়ন করসংক্রান্ত অনলাইন ও প্রয়োজনীয় অন্যান্য কাজে সহায়তা।',
			'icon'  => 'receipt',
		),
		'lost-deed'        => array(
			'title' => 'হারানো দলিল অনুসন্ধান',
			'text'  => 'উপলব্ধ তথ্যের ভিত্তিতে হারানো বা পুরোনো দলিল অনুসন্ধানে সহায়তা।',
			'icon'  => 'search',
		),
		'certified-copy'   => array(
			'title' => 'দলিলের সার্টিফাইড কপি',
			'text'  => 'সংশ্লিষ্ট অফিস থেকে দলিলের সার্টিফাইড কপি সংগ্রহে সহায়তা।',
			'icon'  => 'certificate',
		),
		'document-issue'   => array(
			'title' => 'জমির কাগজপত্রের জটিলতা',
			'text'  => 'দলিল ও সংশ্লিষ্ট কাগজপত্র নিয়ে সমস্যায় করণীয় সম্পর্কে পরামর্শ।',
			'icon'  => 'alert',
		),
		'consultation'     => array(
			'title' => 'ভূমিবিষয়ক পরামর্শ',
			'text'  => 'জমিসংক্রান্ত বিষয় বুঝে প্রয়োজনীয় পদক্ষেপ ও প্রাসঙ্গিক বিশেষজ্ঞের সহায়তা সম্পর্কে নির্দেশনা।',
			'icon'  => 'compass',
		),
		'measurement'      => array(
			'title' => 'জমি পরিমাপ',
			'text'  => 'জমি পরিমাপ ও পরিমাণ নির্ণয়সংক্রান্ত সেবার ব্যবস্থা।',
			'icon'  => 'ruler',
		),
	);
}

/**
 * Inline line icons used as the service card "media" area, in place of
 * stock photography we don't have real/licensed images for.
 */
function sojib68_service_icon( $name ) {
	$icons = array(
		'document'    => '<path d="M14 4h14l10 10v26a2 2 0 0 1-2 2H14a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2z"/><path d="M28 4v10h10"/><path d="M18 26h12M18 33h12"/>',
		'handshake'   => '<path d="M6 24l8-8 8 8 4-4 4 4 8-8 4 4"/><path d="M6 24v8a4 4 0 0 0 4 4h4"/><path d="M38 24v8a4 4 0 0 1-4 4h-4"/>',
		'stamp'       => '<circle cx="24" cy="16" r="8"/><path d="M14 40l4-10h12l4 10"/><path d="M24 24v6"/>',
		'receipt'     => '<path d="M12 6h24v36l-6-4-6 4-6-4-6 4z"/><path d="M18 16h12M18 24h12"/>',
		'search'      => '<circle cx="21" cy="21" r="13"/><path d="M40 40l-8-8"/>',
		'certificate' => '<rect x="8" y="8" width="32" height="24" rx="2"/><circle cx="24" cy="34" r="6"/><path d="M20 38l-2 6 6-3 6 3-2-6"/>',
		'alert'       => '<path d="M24 6L4 40h40L24 6z"/><path d="M24 20v10"/><circle cx="24" cy="34" r="1" fill="currentColor"/>',
		'compass'     => '<circle cx="24" cy="24" r="16"/><path d="M30 18l-4 10-10 4 4-10z"/>',
		'ruler'       => '<path d="M6 30l24-24 12 12-24 24z"/><path d="M20 16l4 4M14 22l4 4M26 10l4 4"/>',
	);

	if ( ! isset( $icons[ $name ] ) ) {
		$name = 'document';
	}

	return '<svg viewBox="0 0 48 48" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $icons[ $name ] . '</svg>';
}

/**
 * Inline icons for the "যেভাবে কাজ করি" row.
 */
function sojib68_work_icon( $name ) {
	$icons = array(
		'discuss' => '<path d="M6 8h24v16H14l-5 5v-5H6z"/><path d="M18 28h18l5 5v-5h5V12H24"/>',
		'folder'  => '<path d="M4 10h11l3 4h18v20H4z"/>',
		'clarity' => '<circle cx="18" cy="15" r="9"/><path d="M18 24v5M14 32h8M15 29h6"/>',
		'update'  => '<path d="M6 18a12 12 0 0 1 20-9l4-4v10H20l4-4a8 8 0 0 0-13 6"/><path d="M30 18a12 12 0 0 1-20 9l-4 4V21h10l-4 4a8 8 0 0 0 13-6"/>',
	);

	if ( ! isset( $icons[ $name ] ) ) {
		$name = 'discuss';
	}

	return '<svg viewBox="0 0 36 36" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $icons[ $name ] . '</svg>';
}

/**
 * Verified stat counters (e.g. years of experience). Hidden entirely
 * while empty — no estimated or invented numbers are ever shown.
 */
function sojib68_get_stats() {
	return array();
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
