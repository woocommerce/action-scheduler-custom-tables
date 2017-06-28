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
add_filter( 'action_scheduler_store_class', function( $class ) {
	return DB_Store::class;
}, 10, 1 );
add_filter( 'action_scheduler_logger_class', function( $class ) {
	return DB_Logger::class;
}, 10, 1 );