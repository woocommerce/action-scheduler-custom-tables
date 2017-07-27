<?php
/*
Plugin Name: Action Scheduler - Custom Tables
Plugin URI: https://github.com/prospress/action-scheduler-custom-tables
Description: Improved scalability for the Action Scheduler library using custom tables
Author: Prospress
Author URI: http://prospress.com/
Version: 1.0.0-dev
*/

namespace Action_Scheduler\Custom_Tables;

require_once( __DIR__ . '/vendor/autoload.php' );

function set_store_class( $class ) {
	$scheduler = new Migration\Migration_Scheduler();
	if ( $scheduler->is_migration_complete() ) {
		return DB_Store::class;
	} else {
		return Hybrid_Store::class;
	}
}

function set_logger_class( $class ) {
	return DB_Logger::class;
}

function register_cli_command() {
	if ( class_exists( 'WP_CLI_Command' ) ) {
		$command = new WP_CLI\Migration_Command();
		$command->register();
	}
}

function schedule_migration() {
	if ( ! apply_filters( 'action_scheduler_custom_tables_do_background_migration', true ) ) {
		return;
	}

	$scheduler = new Migration\Migration_Scheduler();
	if ( $scheduler->is_migration_complete() ) {
		return;
	}
	$scheduler->hook();

	if ( $scheduler->is_migration_scheduled() ) {
		return;
	}
	$scheduler->schedule_migration();
}

function get_migration_config_object() {
	$config = new Migration\Migration_Config();
	$config->set_source_store( new \ActionScheduler_wpPostStore() );
	$config->set_source_logger( new \ActionScheduler_wpCommentLogger() );
	$config->set_destination_store( new DB_Store() );
	$config->set_destination_logger( new DB_Logger() );

	return apply_filters( 'action_scheduler_custom_tables_migration_config', $config );
}

add_filter( 'action_scheduler_store_class', __NAMESPACE__ . '\set_store_class', 10, 1 );
add_filter( 'action_scheduler_logger_class', __NAMESPACE__ . '\set_logger_class', 10, 1 );
add_action( 'plugins_loaded', __NAMESPACE__ . '\register_cli_command', 10, 0 );
add_action( 'shutdown', __NAMESPACE__ . '\schedule_migration', 0, 0 );