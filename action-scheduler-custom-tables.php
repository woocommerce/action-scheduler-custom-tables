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

// We need to hook in at the obscure 0.5 priority here, because ActionScheduler_Versions::initialize_latest_version() is attached at priority 1, while action_scheduler_register_{version_number_string}() callbacks at attached at priority 0
add_action( 'plugins_loaded', [ Plugin::class, 'init' ], 0.5, 0 );
