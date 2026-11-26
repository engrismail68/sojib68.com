<?php
/**
 * Template for /products — "ডিজিটাল প্রোডাক্ট".
 * Placeholder until the shop is built: no prices, no payment details, no invented claims.
 */
get_header();
$items = array(
	array( '🗺️', 'বাংলাদেশের মৌজা ম্যাপ' ),
	array( '💼', 'বিজনেস কিট' ),
	array( '📘', 'My Book' ),
	array( '🧮', 'সম্পত্তির হিসাব বের করা' ),
);
?>
<main class="prd-page">
	<div class="site-container">
		<header class="prd-head">
			<p class="eyebrow"><?php esc_html_e( 'ডিজিটাল প্রোডাক্ট', 'sojib68' ); ?></p>
			<h1><?php esc_html_e( 'জমি ও ব্যবসার কাজের ডিজিটাল টুলস', 'sojib68' ); ?></h1>
			<p class="prd-lead"><?php esc_html_e( 'অনলাইনে কিনে সঙ্গে সঙ্গে ডাউনলোড বা ব্যবহার করার প্রোডাক্টগুলো খুব শীঘ্রই আসছে।', 'sojib68' ); ?></p>
		</header>
		<ul class="prd-grid">
			<?php foreach ( $items as $it ) : ?>
				<li class="prd-card">
					<span class="prd-ico" aria-hidden="true"><?php echo esc_html( $it[0] ); ?></span>
					<h2><?php echo esc_html( $it[1] ); ?></h2>
					<span class="prd-soon"><?php esc_html_e( 'শীঘ্রই আসছে', 'sojib68' ); ?></span>
				</li>
			<?php endforeach; ?>
		</ul>
		<p class="prd-cta"><a href="<?php echo esc_url( home_url( '/#contact' ) ); ?>" class="btn btn-primary" data-inq-open><?php esc_html_e( 'আগে জানতে যোগাযোগ করুন', 'sojib68' ); ?></a></p>
	</div>
</main>
<?php
get_footer();
