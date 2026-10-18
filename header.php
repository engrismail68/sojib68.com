<?php
/**
 * The header for the Sojib68 theme.
 */
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="color-scheme" content="light">
	<?php if ( is_front_page() ) : ?>
		<meta name="description" content="<?php echo esc_attr__( 'জমি রেজিস্ট্রেশন, নামজারি, দলিল অনুসন্ধান, সার্টিফাইড কপি ও ভূমিসংক্রান্ত বিষয়ে পরামর্শ ও সহায়তা।', 'sojib68' ); ?>">
	<?php endif; ?>
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<a class="skip-link screen-reader-text" href="#main-content"><?php esc_html_e( 'মূল কনটেন্টে যান', 'sojib68' ); ?></a>

<header class="site-header" id="site-header">
	<div class="site-container site-header__inner">
		<div class="site-branding">
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="site-logo-link" aria-label="<?php bloginfo( 'name' ); ?>">
				<?php if ( has_custom_logo() ) : ?>
					<?php the_custom_logo(); ?>
				<?php else : ?>
					<img
						src="<?php echo esc_url( get_theme_file_uri( 'assets/images/logo.webp' ) ); ?>"
						alt="<?php echo esc_attr( get_bloginfo( 'name' ) . ' — Land & IT Consultant' ); ?>"
						class="site-logo-img"
						width="220"
						height="73"
					>
				<?php endif; ?>
			</a>
		</div>

		<button type="button" class="menu-toggle" id="menu-toggle" aria-expanded="false" aria-controls="primary-navigation">
			<span class="menu-toggle__bar"></span>
			<span class="menu-toggle__bar"></span>
			<span class="menu-toggle__bar"></span>
			<span class="screen-reader-text"><?php esc_html_e( 'মেনু খুলুন', 'sojib68' ); ?></span>
		</button>

		<nav class="main-navigation" id="primary-navigation" aria-label="<?php esc_attr_e( 'প্রধান মেনু', 'sojib68' ); ?>">
			<ul>
				<li><a href="<?php echo esc_url( is_front_page() ? '#top' : home_url( '/' ) ); ?>"><?php esc_html_e( 'হোম', 'sojib68' ); ?></a></li>
				<li><a href="<?php echo esc_url( home_url( '/#about' ) ); ?>"><?php esc_html_e( 'আমার সম্পর্কে', 'sojib68' ); ?></a></li>
				<li><a href="<?php echo esc_url( home_url( '/#services' ) ); ?>"><?php esc_html_e( 'ভূমিসেবা', 'sojib68' ); ?></a></li>
				<li><a href="<?php echo esc_url( home_url( '/#resources' ) ); ?>"><?php esc_html_e( 'তথ্য ও টুলস', 'sojib68' ); ?></a></li>
				<li><a href="<?php echo esc_url( home_url( '/#contact' ) ); ?>"><?php esc_html_e( 'যোগাযোগ', 'sojib68' ); ?></a></li>
			</ul>
			<a href="<?php echo esc_url( home_url( '/#contact' ) ); ?>" class="btn btn-primary nav-cta"><?php esc_html_e( 'পরামর্শ নিন', 'sojib68' ); ?></a>
		</nav>
	</div>
</header>

<main id="main-content">
