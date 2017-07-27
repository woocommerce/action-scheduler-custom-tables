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

function get_migration_config_object() {
	$config = new Migration\Migration_Config();
	$config->set_source_store( new \ActionScheduler_wpPostStore() );
	$config->set_source_logger( new \ActionScheduler_wpCommentLogger() );
	$config->set_destination_store( new DB_Store() );
	$config->set_destination_logger( new DB_Logger() );

	return apply_filters( 'action_scheduler_custom_tables_migration_config', $config );
}

add_action( 'plugins_loaded', [ Plugin::class, 'init' ], 0, 0 );