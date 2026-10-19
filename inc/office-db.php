<?php
/**
 * Database schema for the "দলিল রেকর্ড ও হিসাব" system.
 *
 * Uses WordPress's own schema-migration tool, dbDelta(), which compares
 * the declared schema against what exists and alters it without
 * dropping data — so re-running this (every version bump) is always
 * safe and never destroys existing records.
 *
 * All money fields are stored as BIGINT "poysha" (1 taka = 100 poysha)
 * so every amount is an integer and PHP never does float arithmetic on
 * money — avoids floating-point rounding drift entirely. Land
 * quantities use DECIMAL, which MySQL stores as exact fixed-point (not
 * binary float), so that's safe as-is.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'SOJIB68_DEEDS_DB_VERSION', '1.1.0' ); // 1.1.0: + deeds.serial_no (chronological serial, see sojib68_deeds_resequence())

function sojib68_deeds_table( $name ) {
	global $wpdb;
	return $wpdb->prefix . 'sojib_' . $name;
}

function sojib68_deeds_install() {
	global $wpdb;

	$charset_collate = $wpdb->get_charset_collate();

	$deeds   = sojib68_deeds_table( 'deeds' );
	$donors  = sojib68_deeds_table( 'deed_donors' );
	$grants  = sojib68_deeds_table( 'deed_grantees' );
	$lands   = sojib68_deeds_table( 'deed_lands' );
	$recpts  = sojib68_deeds_table( 'deed_receipts' );
	$expense = sojib68_deeds_table( 'deed_expenses' );
	$offices = sojib68_deeds_table( 'offices' );
	$types   = sojib68_deeds_table( 'deed_types' );
	$tokens  = sojib68_deeds_table( 'submission_tokens' );
	$audit   = sojib68_deeds_table( 'deed_audit' );

	$sql = array();

	$sql[] = "CREATE TABLE $deeds (
		id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
		serial_no bigint(20) unsigned DEFAULT NULL,
		sub_registry_office varchar(190) DEFAULT NULL,
		reg_date date DEFAULT NULL,
		reg_year smallint(5) DEFAULT NULL,
		deed_no varchar(50) DEFAULT NULL,
		deed_no_normalized varchar(50) DEFAULT NULL,
		deed_type varchar(100) DEFAULT NULL,
		deed_value_poysha bigint(20) DEFAULT NULL,
		client_total_due_poysha bigint(20) DEFAULT NULL,
		fee_agreed_poysha bigint(20) DEFAULT NULL,
		fee_collected_poysha bigint(20) DEFAULT NULL,
		dues_verified tinyint(1) NOT NULL DEFAULT 0,
		notes text,
		created_by bigint(20) unsigned DEFAULT NULL,
		created_at datetime NOT NULL,
		updated_by bigint(20) unsigned DEFAULT NULL,
		updated_at datetime NOT NULL,
		deleted_at datetime DEFAULT NULL,
		PRIMARY KEY  (id),
		KEY deed_no_normalized (deed_no_normalized),
		KEY serial_no (serial_no),
		KEY reg_year (reg_year),
		KEY reg_date (reg_date),
		KEY deleted_at (deleted_at)
	) $charset_collate;";

	$sql[] = "CREATE TABLE $donors (
		id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
		deed_id bigint(20) unsigned NOT NULL,
		name varchar(190) DEFAULT NULL,
		address text,
		mobile varchar(30) DEFAULT NULL,
		sort_order smallint(5) NOT NULL DEFAULT 0,
		PRIMARY KEY  (id),
		KEY deed_id (deed_id),
		KEY mobile (mobile)
	) $charset_collate;";

	$sql[] = "CREATE TABLE $grants (
		id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
		deed_id bigint(20) unsigned NOT NULL,
		name varchar(190) DEFAULT NULL,
		address text,
		mobile varchar(30) DEFAULT NULL,
		sort_order smallint(5) NOT NULL DEFAULT 0,
		PRIMARY KEY  (id),
		KEY deed_id (deed_id),
		KEY mobile (mobile)
	) $charset_collate;";

	$sql[] = "CREATE TABLE $lands (
		id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
		deed_id bigint(20) unsigned NOT NULL,
		mouza varchar(190) DEFAULT NULL,
		khatian_no varchar(50) DEFAULT NULL,
		khatian_no_normalized varchar(50) DEFAULT NULL,
		quantity decimal(14,4) DEFAULT NULL,
		unit varchar(20) DEFAULT NULL,
		sort_order smallint(5) NOT NULL DEFAULT 0,
		PRIMARY KEY  (id),
		KEY deed_id (deed_id),
		KEY mouza (mouza),
		KEY khatian_no_normalized (khatian_no_normalized)
	) $charset_collate;";

	$sql[] = "CREATE TABLE $recpts (
		id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
		deed_id bigint(20) unsigned NOT NULL,
		received_date date DEFAULT NULL,
		amount_poysha bigint(20) NOT NULL DEFAULT 0,
		method varchar(30) DEFAULT NULL,
		note text,
		sort_order smallint(5) NOT NULL DEFAULT 0,
		PRIMARY KEY  (id),
		KEY deed_id (deed_id),
		KEY received_date (received_date)
	) $charset_collate;";

	$sql[] = "CREATE TABLE $expense (
		id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
		deed_id bigint(20) unsigned NOT NULL,
		category varchar(50) DEFAULT NULL,
		description text,
		amount_poysha bigint(20) NOT NULL DEFAULT 0,
		paid_date date DEFAULT NULL,
		paid_status varchar(20) DEFAULT NULL,
		paid_by varchar(20) DEFAULT NULL,
		sort_order smallint(5) NOT NULL DEFAULT 0,
		PRIMARY KEY  (id),
		KEY deed_id (deed_id)
	) $charset_collate;";

	$sql[] = "CREATE TABLE $offices (
		id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
		name varchar(190) NOT NULL,
		created_at datetime NOT NULL,
		PRIMARY KEY  (id),
		UNIQUE KEY name (name)
	) $charset_collate;";

	$sql[] = "CREATE TABLE $types (
		id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
		name varchar(190) NOT NULL,
		created_at datetime NOT NULL,
		PRIMARY KEY  (id),
		UNIQUE KEY name (name)
	) $charset_collate;";

	$sql[] = "CREATE TABLE $tokens (
		token varchar(64) NOT NULL,
		deed_id bigint(20) unsigned NOT NULL,
		created_at datetime NOT NULL,
		PRIMARY KEY  (token)
	) $charset_collate;";

	$sql[] = "CREATE TABLE $audit (
		id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
		deed_id bigint(20) unsigned NOT NULL,
		user_id bigint(20) unsigned DEFAULT NULL,
		action varchar(20) NOT NULL,
		changed_at datetime NOT NULL,
		summary text,
		PRIMARY KEY  (id),
		KEY deed_id (deed_id)
	) $charset_collate;";

	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	foreach ( $sql as $statement ) {
		dbDelta( $statement );
	}

	sojib68_deeds_seed_defaults();

	// Backfills serial_no for every existing deed — covers both a fresh
	// install and any version bump that changes how deeds are ordered.
	sojib68_deeds_resequence();

	update_option( 'sojib68_deeds_db_version', SOJIB68_DEEDS_DB_VERSION );
}

function sojib68_deeds_seed_defaults() {
	global $wpdb;

	$offices = sojib68_deeds_table( 'offices' );
	$types   = sojib68_deeds_table( 'deed_types' );

	$default_office = 'রামগঞ্জ সাব-রেজিস্ট্রেশন অফিস';
	$existing       = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $offices WHERE name = %s", $default_office ) );
	if ( ! $existing ) {
		$wpdb->insert( $offices, array(
			'name'       => $default_office,
			'created_at' => current_time( 'mysql' ),
		) );
	}

	$default_types = array( 'সাফ কবলা', 'হেবার ঘোষণা', 'পাওয়ার', 'বণ্টননামা', 'বিনিময়' );
	foreach ( $default_types as $type_name ) {
		$existing = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $types WHERE name = %s", $type_name ) );
		if ( ! $existing ) {
			$wpdb->insert( $types, array(
				'name'       => $type_name,
				'created_at' => current_time( 'mysql' ),
			) );
		}
	}
}

/**
 * Runs the installer whenever the stored schema version is behind the
 * code's version — i.e. on first load after this file is deployed,
 * and again on any future version bump. dbDelta() only adds/alters,
 * never drops data, so this is safe to run repeatedly.
 */
function sojib68_deeds_maybe_upgrade() {
	if ( get_option( 'sojib68_deeds_db_version' ) !== SOJIB68_DEEDS_DB_VERSION ) {
		sojib68_deeds_install();
	}
}
add_action( 'init', 'sojib68_deeds_maybe_upgrade', 5 );
