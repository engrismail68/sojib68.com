<?php
/**
 * CRM database layer.
 *
 * Fully self-contained — its own tables, its own version option, its
 * own install/upgrade routine. Nothing here is shared with the office
 * (দলিল) system; the two can be developed independently.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'SOJIB68_CRM_DB_VERSION', '2.4.0' );

function sojib68_crm_table() {
	global $wpdb;
	return $wpdb->prefix . 'crm_leads';
}

function sojib68_crm_followups_table() {
	global $wpdb;
	return $wpdb->prefix . 'crm_followups';
}

function sojib68_crm_activity_table() {
	global $wpdb;
	return $wpdb->prefix . 'crm_lead_activity';
}

function sojib68_crm_works_table() {
	global $wpdb;
	return $wpdb->prefix . 'crm_works';
}

function sojib68_crm_cash_table() {
	global $wpdb;
	return $wpdb->prefix . 'crm_cash_ledger';
}

function sojib68_crm_install() {
	global $wpdb;
	require_once ABSPATH . 'wp-admin/includes/upgrade.php';

	$charset_collate = $wpdb->get_charset_collate();

	$leads_table = sojib68_crm_table();
	$sql_leads   = "CREATE TABLE {$leads_table} (
		id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
		lead_code VARCHAR(20) NOT NULL DEFAULT '',
		name VARCHAR(190) NOT NULL DEFAULT '',
		phone VARCHAR(30) NOT NULL DEFAULT '',
		email VARCHAR(190) NOT NULL DEFAULT '',
		address TEXT NULL,
		source VARCHAR(30) NOT NULL DEFAULT 'facebook_internet',
		referred_by VARCHAR(190) NOT NULL DEFAULT '',
		service_type VARCHAR(30) NOT NULL DEFAULT 'other_land_service',
		details TEXT NULL,
		notes TEXT NULL,
		assigned_to BIGINT(20) UNSIGNED NULL,
		status VARCHAR(30) NOT NULL DEFAULT 'new',
		next_followup_at DATETIME NULL,
		created_by BIGINT(20) UNSIGNED NULL,
		created_at DATETIME NOT NULL,
		updated_at DATETIME NOT NULL,
		PRIMARY KEY  (id),
		UNIQUE KEY lead_code (lead_code),
		KEY status (status),
		KEY source (source),
		KEY service_type (service_type),
		KEY assigned_to (assigned_to),
		KEY phone (phone)
	) {$charset_collate};";
	dbDelta( $sql_leads );

	$followups_table = sojib68_crm_followups_table();
	$sql_followups    = "CREATE TABLE {$followups_table} (
		id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
		lead_id BIGINT(20) UNSIGNED NOT NULL,
		due_at DATETIME NOT NULL,
		note VARCHAR(255) NOT NULL DEFAULT '',
		completed_at DATETIME NULL,
		created_by BIGINT(20) UNSIGNED NULL,
		created_at DATETIME NOT NULL,
		PRIMARY KEY  (id),
		KEY lead_id (lead_id),
		KEY due_at (due_at)
	) {$charset_collate};";
	dbDelta( $sql_followups );

	$activity_table = sojib68_crm_activity_table();
	$sql_activity    = "CREATE TABLE {$activity_table} (
		id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
		lead_id BIGINT(20) UNSIGNED NOT NULL,
		user_id BIGINT(20) UNSIGNED NULL,
		type VARCHAR(30) NOT NULL DEFAULT 'note',
		message TEXT NULL,
		created_at DATETIME NOT NULL,
		PRIMARY KEY  (id),
		KEY lead_id (lead_id)
	) {$charset_collate};";
	dbDelta( $sql_activity );

	$works_table = sojib68_crm_works_table();
	$sql_works    = "CREATE TABLE {$works_table} (
		id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
		work_code VARCHAR(20) NOT NULL DEFAULT '',
		lead_id BIGINT(20) UNSIGNED NULL,
		customer_name VARCHAR(190) NOT NULL DEFAULT '',
		customer_phone VARCHAR(30) NOT NULL DEFAULT '',
		work_type VARCHAR(30) NOT NULL DEFAULT 'other_land_service',
		received_date DATE NULL,
		referred_by VARCHAR(190) NOT NULL DEFAULT '',
		assigned_to BIGINT(20) UNSIGNED NULL,
		status VARCHAR(20) NOT NULL DEFAULT 'running',
		agreed_poysha BIGINT(20) UNSIGNED NULL,
		stage VARCHAR(40) NOT NULL DEFAULT '',
		stage_changed_at DATETIME NULL,
		cancel_reason TEXT NULL,
		pending_reason VARCHAR(255) NOT NULL DEFAULT '',
		next_action VARCHAR(255) NOT NULL DEFAULT '',
		next_action_date DATE NULL,
		docs_needed TEXT NULL,
		docs_received TEXT NULL,
		docs_pending TEXT NULL,
		file_location VARCHAR(190) NOT NULL DEFAULT '',
		delivery_status VARCHAR(20) NOT NULL DEFAULT 'not_ready',
		delivery_date DATE NULL,
		delivery_recipient VARCHAR(190) NOT NULL DEFAULT '',
		created_at DATETIME NOT NULL,
		updated_at DATETIME NOT NULL,
		PRIMARY KEY  (id),
		UNIQUE KEY work_code (work_code),
		KEY lead_id (lead_id),
		KEY work_type (work_type),
		KEY status (status),
		KEY assigned_to (assigned_to)
	) {$charset_collate};";
	dbDelta( $sql_works );

	$cash_table = sojib68_crm_cash_table();
	$sql_cash    = "CREATE TABLE {$cash_table} (
		id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
		category VARCHAR(30) NOT NULL DEFAULT 'general',
		direction VARCHAR(10) NOT NULL DEFAULT 'in',
		amount_poysha BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
		party VARCHAR(190) NOT NULL DEFAULT '',
		note VARCHAR(255) NOT NULL DEFAULT '',
		txn_date DATE NOT NULL,
		work_id BIGINT(20) UNSIGNED NULL,
		created_by BIGINT(20) UNSIGNED NULL,
		created_at DATETIME NOT NULL,
		PRIMARY KEY  (id),
		KEY category (category),
		KEY txn_date (txn_date)
	) {$charset_collate};";
	dbDelta( $sql_cash );

	// Works created before stage tracking existed: start their idle clock from their last update.
	$wpdb->query( "UPDATE {$works_table} SET stage_changed_at = COALESCE(updated_at, created_at) WHERE stage_changed_at IS NULL" );

	update_option( 'sojib68_crm_db_version', SOJIB68_CRM_DB_VERSION );
}

function sojib68_crm_maybe_upgrade() {
	if ( get_option( 'sojib68_crm_db_version' ) !== SOJIB68_CRM_DB_VERSION ) {
		sojib68_crm_install();
	}
}
add_action( 'init', 'sojib68_crm_maybe_upgrade', 5 );
add_action( 'after_switch_theme', 'sojib68_crm_install' );
