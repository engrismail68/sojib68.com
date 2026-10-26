<?php
/**
 * "আমার পরিচয় ও পেশাগত উত্তরাধিকার" page (/about).
 *
 * Everything the page says lives in sojib68_about_content() below, so
 * editing a sentence or adding a photo never means touching the template
 * (page-about.php) or the CSS (assets/css/about.css).
 *
 * Routing: if a WordPress page with the slug "about" exists, WordPress
 * already picks page-about.php for it (template hierarchy) — nothing is
 * duplicated. If no such page exists, /about is served from this same
 * template instead of a 404.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Photos: drop a file into assets/images/about/ named exactly like the
 * slug below (ali-ahammad / nurul-islam / ismail-sojib, any of
 * .jpg .jpeg .png .webp) and it replaces the placeholder — the frame
 * keeps the same shape, so the layout never moves. "ismail-sojib" falls
 * back to the homepage portrait until its own file is added.
 *
 * Text: **double asterisks** make a phrase bold.
 *
 * Education: only fields that are filled in are shown. To add more later,
 * fill 'meta' with label => value pairs, e.g. 'বিষয়' => '…', 'পাশের সাল' => '…'.
 */
function sojib68_about_content() {
	return array(
		'title'       => 'আমার সম্পর্কে | Ismail Hossain Sojib',
		'description' => 'দলিল লেখার পেশায় তিন প্রজন্মের পথচলা, তথ্যপ্রযুক্তির শিক্ষা এবং বাবার সহকারী হিসেবে ইসমাইল হোসেন সজিবের কাজ।',

		'roots'       => array(
			'eyebrow' => 'আমার পেশাগত শিকড়',
			'heading' => 'দলিল লেখার পেশায় তিন প্রজন্ম',
			'intro'   => '১৯৫০ সালে দাদা আলী আহাম্মদের হাত ধরে শুরু হওয়া পেশাগত ধারাবাহিকতায় কাজ করছেন আমার বাবা মোঃ নুরুল ইসলাম। ২০২০ সাল থেকে বাবার সহকারী হিসেবে সেই পথচলায় যুক্ত হয়েছি আমি।',
			'people'  => array(
				array(
					'slug'   => 'ali-ahammad',
					'name'   => 'আলী আহাম্মদ',
					'role'   => 'আমার দাদা · দলিল লেখক',
					'period' => 'কর্মকাল: ১৯৫০–২০০৩',
					'text'   => 'দলিল লেখার পেশায় আমাদের পরিবারের যাত্রা তাঁর হাত ধরেই। দীর্ঘ কর্মজীবনের মধ্য দিয়ে তিনি গড়ে তুলেছেন আমাদের পারিবারিক পেশার ভিত্তি।',
				),
				array(
					'slug'   => 'nurul-islam',
					'name'   => 'মোঃ নুরুল ইসলাম',
					'role'   => 'আমার বাবা · দলিল লেখক',
					'period' => 'কর্মকাল: ১৯৮১–বর্তমান',
					'text'   => '১৯৮১ সাল থেকে দলিল লেখার পেশায় কাজ করছেন। তাঁর সহকারী হিসেবে কাজ করতে গিয়ে দলিল প্রস্তুতি, কাগজপত্র পর্যালোচনা ও ভূমিসংক্রান্ত কাজের বাস্তব অভিজ্ঞতা অর্জন করছি।',
				),
			),
		),

		'about'       => array(
			'heading'    => 'আমার সম্পর্কে',
			'lead'       => 'পারিবারিক অভিজ্ঞতার পাশে, তথ্যপ্রযুক্তির শিক্ষা।',
			'photo_slug' => 'ismail-sojib',
			'photo_name' => 'ইসমাইল হোসেন সজিব',
			'paragraphs' => array(
				'আমি **ইসমাইল হোসেন সজিব**, একজন **Land & IT Consultant**। ২০২০ সাল থেকে বাবার সহকারী হিসেবে দলিল ও ভূমিসংক্রান্ত কাজে যুক্ত আছি।',
				'দলিল ও খতিয়ানের তথ্য মিলিয়ে দেখা, প্রয়োজনীয় নথি অনুসন্ধান এবং কাগজপত্রের ঘাটতি চিহ্নিত করে পরবর্তী করণীয় বুঝতে সহায়তা করি। আমার কাজের ক্ষেত্র জমি রেজিস্ট্রেশন, কেনাবেচায় সহায়তা, নামজারি, ভূমি উন্নয়ন কর, দলিল অনুসন্ধান, সার্টিফাইড কপি সংগ্রহ ও জমি পরিমাপ।',
				'তথ্যপ্রযুক্তির শিক্ষা কাজে লাগাই তথ্য সংরক্ষণ, কাগজপত্র গোছানো, অনলাইন সেবার কাজ এবং গ্রাহকের সঙ্গে যোগাযোগ সহজ রাখতে।',
			),
		),

		'education'   => array(
			'heading'     => 'শিক্ষাগত পটভূমি',
			// Only filled-in fields are shown (see the note above).
			'credentials' => array(
				array(
					'degree'      => 'BSc in Computer Science and Engineering',
					'institution' => 'Canadian University of Bangladesh',
					'meta'        => array(),
				),
				array(
					'degree'      => 'Diploma in Engineering',
					'institution' => 'Dhaka Polytechnic Institute',
					'meta'        => array(),
				),
			),
		),

		'institute'   => array(
			'heading' => 'প্রযুক্তি প্রশিক্ষণে সম্পৃক্ততা',
			'body'    => 'ভূমিসংক্রান্ত কাজের পাশাপাশি আমার একটি তথ্যপ্রযুক্তি প্রশিক্ষণকেন্দ্র রয়েছে—**লাইফ সাপোর্ট আইটি ইন্সটিটিউট**। তথ্যপ্রযুক্তি শিক্ষা ও প্রশিক্ষণ আমার পেশাগত পরিচয়ের আরেকটি অংশ।',
			'name_bn' => 'লাইফ সাপোর্ট আইটি ইন্সটিটিউট',
			'name_en' => 'Life Support IT Institute',
		),

		'cta'         => array(
			'services' => 'আমার সেবাসমূহ',
			'booking'  => 'বিস্তারিত পরামর্শ বুক করুন',
			'note'     => 'বিস্তারিত পরামর্শের জন্য নির্ধারিত ফি প্রযোজ্য।',
		),
	);
}

/** Bangla digits for any number / string. */
function sojib68_about_bn( $value ) {
	return strtr( (string) $value, array(
		'0' => '০', '1' => '১', '2' => '২', '3' => '৩', '4' => '৪',
		'5' => '৫', '6' => '৬', '7' => '৭', '8' => '৮', '9' => '৯',
	) );
}

/** Escaped text where **phrase** becomes bold. */
function sojib68_about_rich( $text ) {
	return preg_replace( '/\*\*(.+?)\*\*/u', '<strong>$1</strong>', esc_html( $text ) );
}

/**
 * URL of a photo/logo dropped into assets/images/about/, or '' when there
 * isn't one yet (the template then shows the placeholder).
 */
function sojib68_about_image( $slug, $extensions = array( 'jpg', 'jpeg', 'png', 'webp' ) ) {
	foreach ( $extensions as $ext ) {
		$relative = 'assets/images/about/' . $slug . '.' . $ext;
		if ( file_exists( get_theme_file_path( $relative ) ) ) {
			return get_theme_file_uri( $relative );
		}
	}
	return '';
}

/* ---------------------------------------------------------------------
 * Routing / head
 * ------------------------------------------------------------------ */

/** Request path relative to the site root, e.g. "about". */
function sojib68_about_request_path() {
	$request_path = (string) wp_parse_url( add_query_arg( array() ), PHP_URL_PATH );
	$home_path    = rtrim( (string) wp_parse_url( home_url(), PHP_URL_PATH ), '/' );

	if ( $home_path && 0 === strpos( $request_path, $home_path ) ) {
		$request_path = substr( $request_path, strlen( $home_path ) );
	}
	return trim( $request_path, '/' );
}

/** True when WordPress has no page for /about and we serve the built-in one. */
function sojib68_about_is_fallback_request() {
	return is_404() && 'about' === sojib68_about_request_path();
}

/** True on the /about page, whether it is a real WP page or the fallback. */
function sojib68_is_about_page() {
	return is_page( 'about' ) || sojib68_about_is_fallback_request();
}

function sojib68_about_template( $template ) {
	if ( sojib68_about_is_fallback_request() ) {
		global $wp_query;
		$wp_query->is_404 = false;
		status_header( 200 );
		return get_theme_file_path( 'page-about.php' );
	}
	return $template;
}
add_filter( 'template_include', 'sojib68_about_template' );

/** Don't let WordPress "guess" a different page (e.g. about-us) for /about. */
function sojib68_about_keep_canonical( $redirect_url ) {
	return sojib68_about_is_fallback_request() ? false : $redirect_url;
}
add_filter( 'redirect_canonical', 'sojib68_about_keep_canonical' );

function sojib68_about_body_class( $classes ) {
	if ( sojib68_is_about_page() ) {
		$classes   = array_diff( $classes, array( 'error404' ) );
		$classes[] = 'page-about-us';
	}
	return $classes;
}
add_filter( 'body_class', 'sojib68_about_body_class' );

function sojib68_about_document_title( $title ) {
	if ( sojib68_is_about_page() ) {
		$content = sojib68_about_content();
		return $content['title'];
	}
	return $title;
}
add_filter( 'pre_get_document_title', 'sojib68_about_document_title' );

function sojib68_about_head() {
	if ( ! sojib68_is_about_page() ) {
		return;
	}
	$content = sojib68_about_content();
	echo '<meta name="description" content="' . esc_attr( $content['description'] ) . '">' . "\n";
}
add_action( 'wp_head', 'sojib68_about_head', 1 );

/** about.css: the /about page itself, plus the small "তিন প্রজন্ম" teaser on the homepage. */
function sojib68_about_enqueue() {
	if ( ! sojib68_is_about_page() && ! is_front_page() ) {
		return;
	}
	$path = get_theme_file_path( 'assets/css/about.css' );
	wp_enqueue_style(
		'sojib68-about',
		get_theme_file_uri( 'assets/css/about.css' ),
		array( 'sojib68-style' ),
		file_exists( $path ) ? filemtime( $path ) : '1.0.0'
	);
}
add_action( 'wp_enqueue_scripts', 'sojib68_about_enqueue', 20 );
