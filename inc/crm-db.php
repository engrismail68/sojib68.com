<?php
/**
 * CRM database layer.
 *
 * Fully self-contained — its own table, its own version option, its own
 * install/upgrade routine. Nothing here is shared with the office
 * (দলিল) system; the two can be developed independently.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'SOJIB68_CRM_DB_VERSION', '1.0.0' );

function sojib68_crm_table() {
	global $wpdb;
	return $wpdb->prefix . 'crm_leads';
}

function sojib68_crm_install() {
	global $wpdb;
	require_once ABSPATH . 'wp-admin/includes/upgrade.php';

	$table           = sojib68_crm_table();
	$charset_collate = $wpdb->get_charset_collate();

	$sql = "CREATE TABLE {$table} (
		id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
		name VARCHAR(190) NOT NULL DEFAULT '',
		phone VARCHAR(30) NOT NULL DEFAULT '',
		email VARCHAR(190) NOT NULL DEFAULT '',
		source VARCHAR(30) NOT NULL DEFAULT 'website',
		status VARCHAR(20) NOT NULL DEFAULT 'new',
		notes TEXT NULL,
		created_at DATETIME NOT NULL,
		updated_at DATETIME NOT NULL,
		PRIMARY KEY  (id),
		KEY status (status),
		KEY source (source),
		KEY phone (phone)
	) {$charset_collate};";

	dbDelta( $sql );

	update_option( 'sojib68_crm_db_version', SOJIB68_CRM_DB_VERSION );
}

function sojib68_crm_maybe_upgrade() {
	if ( get_option( 'sojib68_crm_db_version' ) !== SOJIB68_CRM_DB_VERSION ) {
		sojib68_crm_install();
	}
}
add_action( 'init', 'sojib68_crm_maybe_upgrade', 5 );
add_action( 'after_switch_theme', 'sojib68_crm_install' );
