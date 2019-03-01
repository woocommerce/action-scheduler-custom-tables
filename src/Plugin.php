<?php


namespace Action_Scheduler\Custom_Tables;

use Action_Scheduler\Custom_Tables\Migration\Migration_Scheduler;

/**
 * Class Plugin
 *
 * The main plugin/initialization class for the
 * Action Scheduler Custom Tables plugin.
 *
 * Responsible for hooking everything up with WordPress.
 *
 * @package Action_Scheduler\Custom_Tables
 *
 * @codeCoverageIgnore
 */
class Plugin {
	private static $instance;

	/** @var Dependencies */
	private static $dependency_handler;

	/** @var Migration_Scheduler */
	private $migration_scheduler;

	/**
	 * Plugin constructor.
	 *
	 * @param Migration_Scheduler $migration_scheduler
	 */
	public function __construct( Migration_Scheduler $migration_scheduler, Dependencies $dependency_handler ) {
		$this->migration_scheduler = $migration_scheduler;
		self::$dependency_handler  = $dependency_handler;
	}

	/**
	 * Override the action store with our own
	 *
	 * @param string $class
	 *
	 * @return string
	 */
	public function set_store_class( $class ) {
		if ( $this->migration_scheduler->is_migration_complete() ) {
			return DB_Store::class;
		} else {
			return Hybrid_Store::class;
		}
	}

	/**
	 * Override the logger with our own
	 *
	 * @param string $class
	 *
	 * @return string
	 */
	public function set_logger_class( $class ) {
		return DB_Logger::class;
	}

	/**
	 * Register the WP-CLI command to handle bulk migrations
	 *
	 * @return void
	 */
	public function register_cli_command() {
		if ( class_exists( 'WP_CLI_Command' ) ) {
			$command = new WP_CLI\Migration_Command();
			$command->register();
		}
	}

	/**
	 * Set up the background migration process
	 *
	 * @return void
	 */
	public function schedule_migration() {
		if ( ! self::$dependency_handler->dependencies_met() || $this->migration_scheduler->is_migration_complete() || $this->migration_scheduler->is_migration_scheduled() ) {
			return;
		}

		$this->migration_scheduler->schedule_migration();
	}

	/**
	 * Get the default migration config object
	 *
	 * @return Migration\Migration_Config
	 */
	public function get_migration_config_object() {
		$config = new Migration\Migration_Config();
		$config->set_source_store( new \ActionScheduler_wpPostStore() );
		$config->set_source_logger( new \ActionScheduler_wpCommentLogger() );
		$config->set_destination_store( new DB_Store_Migrator() );
		$config->set_destination_logger( new DB_Logger() );

		return apply_filters( 'action_scheduler/custom_tables/migration_config', $config );
	}

	public function hook_admin_notices() {
		if ( $this->migration_scheduler->is_migration_complete() ) {
			return;
		}
		add_action( 'admin_notices', [ $this, 'display_migration_notice' ], 10, 0 );
	}

	public function display_migration_notice() {
		printf( '<div class="notice notice-warning"><p>%s</p></div>', __( 'Action Scheduler migration in progress. The list of scheduled actions may be incomplete.' ) );
	}

	private function hook() {

		add_filter( 'action_scheduler_store_class', [ $this, 'set_store_class' ], 10, 1 );
		add_filter( 'action_scheduler_logger_class', [ $this, 'set_logger_class' ], 10, 1 );
		add_action( 'plugins_loaded', [ $this, 'register_cli_command' ], 10, 0 );
		add_action( 'init', [ $this, 'maybe_hook_migration' ] );
		add_action( 'shutdown', [ $this, 'schedule_migration' ], 0, 0 );

		// Action Scheduler may be displayed as a Tools screen or WooCommerce > Status administration screen
		add_action( 'load-tools_page_action-scheduler', [ $this, 'hook_admin_notices' ], 10, 0 );
		add_action( 'load-woocommerce_page_wc-status', [ $this, 'hook_admin_notices' ], 10, 0 );
	}

	/**
	 * Possibly hook the migration scheduler action.
	 *
	 * @author Jeremy Pry
	 */
	public function maybe_hook_migration() {
		if ( $this->migration_scheduler->is_migration_complete() ) {
			return;
		}

		$this->migration_scheduler->hook();
	}

	public static function init() {
		self::instance();

		if ( self::$dependency_handler->dependencies_met() ) {
			self::instance()->hook();
		} else {
			self::$dependency_handler->add_notice();
		}
	}

	public static function instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new static( new Migration_Scheduler(), new Dependencies() );
		}

		return self::$instance;
	}
}
