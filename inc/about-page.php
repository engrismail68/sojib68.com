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
 * keeps the same 4:5 shape, so the layout never moves.
 *
 * Education: only fields that are filled in are shown. To add more later,
 * fill 'meta' with label => value pairs, e.g. 'বিষয়' => '…', 'পাশের সাল' => '…'.
 *
 * Institute logo: drop institute-logo.(png|webp|svg|jpg) into the same
 * folder; until then a plain text name-plate is shown (no invented logo).
 */
function sojib68_about_content() {
	return array(
		'title'       => 'আমার পরিচয় ও পেশাগত উত্তরাধিকার | Ismail Hossain Sojib',
		'description' => '১৯৫০ সালে দাদার হাত ধরে শুরু হওয়া দলিল লেখার পেশায় আমাদের পরিবারের পথচলা, তথ্যপ্রযুক্তির শিক্ষা এবং বাবার সহকারী হিসেবে আমার কাজ।',

		'hero'        => array(
			'eyebrow'     => 'আমার পরিচয় ও পেশাগত উত্তরাধিকার',
			'heading'     => array( 'দাদার শুরু করা পথে,', 'বাবার পাশে আমার পথচলা।' ),
			'description' => '১৯৫০ সালে দাদা আলী আহাম্মদের হাত ধরে দলিল লেখার পেশায় আমাদের পরিবারের যাত্রা শুরু। সেই ধারাবাহিকতায় বাবা মোঃ নুরুল ইসলাম কাজ করছেন ১৯৮১ সাল থেকে। আর আমি ২০২০ সাল থেকে তাঁর সহকারী হিসেবে এই পেশার বাস্তব কাজের সঙ্গে যুক্ত।',
			'name'        => 'ইসমাইল হোসেন সজিব',
			'role'        => 'Land & IT Consultant',
			'cta_history' => 'তিন প্রজন্মের ইতিহাস',
			'cta_edu'     => 'আমার শিক্ষা ও দক্ষতা',
			'years'       => array( 1950, 1981, 2020 ),
			'years_label' => 'তিন প্রজন্মের কাজ শুরুর বছর',
		),

		'history'     => array(
			'heading' => array( 'একই পেশার সঙ্গে,', 'তিন প্রজন্মের সম্পর্ক।' ),
			'intro'   => 'আমাদের পরিবারের পেশাগত ইতিহাসে প্রত্যেকের আলাদা সময়, আলাদা অভিজ্ঞতা। সেই পথচলার ধারাবাহিকতায় দলিল ও ভূমিসংক্রান্ত কাজে আমার সম্পৃক্ততা।',
			'people'  => array(
				array(
					'slug' => 'ali-ahammad',
					'name' => 'আলী আহাম্মদ',
					'role' => 'আমার দাদা · দলিল লেখক',
					'from' => 1950,
					'to'   => 2003,
					'text' => 'আমাদের পরিবারের দলিল লেখার পেশাগত ইতিহাসের শুরু তাঁর হাত ধরে। ১৯৫০ সাল থেকে ২০০৩ সাল পর্যন্ত তিনি এই পেশায় কাজ করেছেন। তাঁর কর্মজীবন আমাদের পারিবারিক পরিচয়ের একটি গুরুত্বপূর্ণ অধ্যায়।',
				),
				array(
					'slug' => 'nurul-islam',
					'name' => 'মোঃ নুরুল ইসলাম',
					'role' => 'আমার বাবা · দলিল লেখক',
					'from' => 1981,
					'to'   => null,
					'text' => '১৯৮১ সাল থেকে দলিল লেখার পেশায় যুক্ত আছেন। তাঁর কাজের ধারাবাহিকতায় আমি সহকারী হিসেবে যুক্ত হই। বাবার পাশে কাজ করতে গিয়ে দলিলসংক্রান্ত প্রয়োজন, কাগজপত্র এবং কাজের বিভিন্ন ধাপ সম্পর্কে বাস্তব অভিজ্ঞতা অর্জন করছি।',
				),
				array(
					'slug' => 'ismail-sojib',
					'name' => 'ইসমাইল হোসেন সজিব',
					'role' => 'বাবার সহকারী · Land & IT Consultant',
					'from' => 2020,
					'to'   => null,
					'text' => '২০২০ সাল থেকে বাবার সহকারী হিসেবে দলিল ও ভূমিসংক্রান্ত কাজে যুক্ত আছি। পারিবারিক পেশার বাস্তব অভিজ্ঞতার সঙ্গে তথ্যপ্রযুক্তির শিক্ষা কাজে লাগিয়ে তথ্য সংরক্ষণ, কাগজপত্র গোছানো ও গ্রাহকের সঙ্গে যোগাযোগ সহজ করার চেষ্টা করি।',
				),
			),
			'range_title' => 'কাজের সময়কাল',
			'range_note'  => 'বছরগুলো প্রত্যেকের কাজের সময়কাল। এগুলো একে অপরের সঙ্গে মিলে গেছে — ধারাবাহিক দায়িত্ব হস্তান্তরের তারিখ নয়।',
			'statement'   => array( 'পারিবারিক ধারাবাহিকতা আমার পরিচয়ের অংশ।', 'প্রতিটি কাজ বুঝে এগোনো আমার নিজের দায়িত্ব।' ),
		),

		'approach'    => array(
			'heading' => array( 'পুরোনো অভিজ্ঞতার পাশে,', 'নতুনভাবে কাজ গোছানোর চেষ্টা।' ),
			'body'    => 'জমি ও দলিলের কাজে তথ্যের গুরুত্ব অনেক। কার কী প্রয়োজন, কোন কাগজটি পাওয়া গেছে, কোনটি বাকি এবং পরবর্তী করণীয় কী—এসব বিষয় পরিষ্কারভাবে রাখা প্রয়োজন। আমার তথ্যপ্রযুক্তির শিক্ষা এই কাজগুলো গোছানোভাবে করতে সহায়তা করে।',
			'items'   => array(
				array( 'title' => 'তথ্য ও কাগজপত্র', 'text' => 'প্রয়োজনীয় তথ্য একসঙ্গে রাখা এবং কাগজপত্রের ঘাটতি বুঝতে সহায়তা।' ),
				array( 'title' => 'অনলাইন সেবায় সহায়তা', 'text' => 'প্রাসঙ্গিক অনলাইন ভূমিসেবার কাজে প্রযুক্তির দক্ষতা ব্যবহার।' ),
				array( 'title' => 'যোগাযোগ ও অগ্রগতি', 'text' => 'গ্রাহকের প্রয়োজন, আলোচনার বিষয় এবং পরবর্তী করণীয় গোছানো রাখা।' ),
			),
		),

		'education'   => array(
			'eyebrow' => 'শিক্ষাগত যোগ্যতা',
			'heading' => array( 'শিক্ষা তথ্যপ্রযুক্তিতে,', 'প্রয়োগ বাস্তব কাজে।' ),
			'intro'   => 'প্রাতিষ্ঠানিক শিক্ষা আমাকে প্রযুক্তিকে বুঝতে সাহায্য করেছে। পেশাগত কাজে সেই জ্ঞান ব্যবহার করে তথ্য ও কাজের প্রক্রিয়া সহজ রাখার চেষ্টা করি।',
			// Only filled-in fields are shown on the page (see the note above).
			'credentials' => array(
				array(
					'degree'      => 'Diploma in Engineering',
					'field'       => '',
					'institution' => 'Dhaka Polytechnic Institute',
					'meta'        => array(),
				),
				array(
					'degree'      => 'BSc',
					'field'       => 'Computer Science and Engineering',
					'institution' => 'Canadian University of Bangladesh',
					'meta'        => array(),
				),
			),
		),

		'institute'   => array(
			'eyebrow' => 'প্রশিক্ষণে সম্পৃক্ততা',
			'heading' => array( 'প্রযুক্তির জ্ঞান,', 'শেখানোর ক্ষেত্রেও।' ),
			'body'    => 'দলিল ও ভূমিসংক্রান্ত কাজের পাশাপাশি আমার একটি তথ্যপ্রযুক্তি প্রশিক্ষণকেন্দ্র রয়েছে—লাইফ সাপোর্ট আইটি ইন্সটিটিউট। প্রতিষ্ঠানটি আমার কাজের আরেকটি দিক, যেখানে তথ্যপ্রযুক্তি প্রশিক্ষণের সঙ্গে যুক্ত আছি।',
			'name_bn' => 'লাইফ সাপোর্ট আইটি ইন্সটিটিউট',
			'name_en' => 'Life Support IT Institute',
		),

		'services'    => array(
			'heading' => array( 'এই পথচলায়,', 'যেসব কাজে পাশে আছি।' ),
			// Each links to the homepage services section (there are no separate service pages).
			'items'   => array(
				array( 'label' => 'জমি রেজিস্ট্রেশনে সহায়তা', 'icon' => 'document' ),
				array( 'label' => 'জমি কেনাবেচায় সহায়তা', 'icon' => 'handshake' ),
				array( 'label' => 'নামজারি ও ভূমি উন্নয়ন কর', 'icon' => 'stamp' ),
				array( 'label' => 'দলিল অনুসন্ধান ও সার্টিফাইড কপি', 'icon' => 'certificate' ),
				array( 'label' => 'কাগজপত্রসংক্রান্ত জটিলতায় পরামর্শ', 'icon' => 'compass' ),
				array( 'label' => 'জমি পরিমাপ', 'icon' => 'ruler' ),
			),
		),

		'closing'     => array(
			'heading'     => array( 'আপনার জমির বিষয়টি,', 'আগে বুঝে নেওয়া যাক।' ),
			'description' => 'আপনার প্রয়োজন ও কাছে থাকা কাগজপত্র সম্পর্কে সংক্ষেপে জানান। পরবর্তী করণীয় নিয়ে আলোচনা করুন।',
			'button'      => 'যোগাযোগ করুন',
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

/** "১৯৫০–২০০৩" or "১৯৮১–বর্তমান". */
function sojib68_about_period( $from, $to ) {
	return sojib68_about_bn( $from ) . '–' . ( $to ? sojib68_about_bn( $to ) : 'বর্তমান' );
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

/**
 * Bars for the "কাজের সময়কাল" chart: each person's start/end as a
 * percentage of the 1950 → this year scale.
 */
function sojib68_about_range( array $people ) {
	$start = (int) min( array_column( $people, 'from' ) );
	$end   = (int) gmdate( 'Y' );
	$span  = max( 1, $end - $start );

	$bars = array();
	foreach ( $people as $person ) {
		$to     = $person['to'] ? (int) $person['to'] : $end;
		$bars[] = array(
			'name'   => $person['name'],
			'period' => sojib68_about_period( $person['from'], $person['to'] ),
			'left'   => round( ( (int) $person['from'] - $start ) / $span * 100, 2 ),
			'width'  => round( ( $to - (int) $person['from'] ) / $span * 100, 2 ),
		);
	}

	$ticks = array();
	foreach ( array_unique( array_merge( array_column( $people, 'from' ), array_filter( array_column( $people, 'to' ) ) ) ) as $year ) {
		$ticks[] = array(
			'label' => sojib68_about_bn( $year ),
			'left'  => round( ( (int) $year - $start ) / $span * 100, 2 ),
		);
	}
	usort( $ticks, function ( $a, $b ) {
		return $a['left'] <=> $b['left'];
	} );

	return array( 'bars' => $bars, 'ticks' => $ticks );
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
