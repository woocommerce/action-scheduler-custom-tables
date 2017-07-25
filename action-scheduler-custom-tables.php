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
	return DB_Store::class;
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

add_filter( 'action_scheduler_store_class', __NAMESPACE__ . '\set_store_class', 10, 1 );
add_filter( 'action_scheduler_logger_class', __NAMESPACE__ . '\set_logger_class', 10, 1 );
add_action( 'plugins_loaded', __NAMESPACE__ . '\register_cli_command', 10, 0 );