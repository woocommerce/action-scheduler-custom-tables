<?php
/**
 * Plugin Name: Action Scheduler - Custom Tables
 * Plugin URI: https://github.com/prospress/action-scheduler-custom-tables
 * Description: Improved scalability for the Action Scheduler library using custom tables
 * Author: Prospress
 * Author URI: http://prospress.com/
 * Version: 1.0.0-dev
 */

namespace Action_Scheduler\Custom_Tables;

require_once( __DIR__ . '/vendor/autoload.php' );

add_action( 'plugins_loaded', [ Plugin::class, 'init' ], 0, 0 );
