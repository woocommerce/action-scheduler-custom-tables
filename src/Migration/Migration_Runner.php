<?php


namespace Action_Scheduler\Custom_Tables\Migration;

class Migration_Runner {
	private $source_store;
	private $destination_store;
	private $source_logger;
	private $destination_logger;

	private $batch_fetcher;
	private $action_migrator;
	private $log_migrator;

	public function __construct( Migration_Config $config ) {
		$this->source_store       = $config->get_source_store();
		$this->destination_store  = $config->get_destination_store();
		$this->source_logger      = $config->get_source_logger();
		$this->destination_logger = $config->get_destination_logger();

		$this->batch_fetcher = new Batch_Fetcher( $this->source_store );
		if ( $config->get_dry_run() ) {
			$this->log_migrator    = new Dry_Run_Log_Migrator( $this->source_logger, $this->destination_logger );
			$this->action_migrator = new Dry_Run_Action_Migrator( $this->source_store, $this->destination_store, $this->log_migrator );
		} else {
			$this->log_migrator    = new Log_Migrator( $this->source_logger, $this->destination_logger );
			$this->action_migrator = new Action_Migrator( $this->source_store, $this->destination_store, $this->log_migrator );
		}
	}

	public function run( $batch_size = 10 ) {
		$batch = $this->batch_fetcher->fetch( $batch_size );

		$this->migrate_actions( $batch );

		return count( $batch );
	}

	public function migrate_actions( array $action_ids ) {
		do_action( 'action_scheduler/custom_tables/migration_batch_starting', $action_ids );

		remove_action( 'action_scheduler_stored_action', array( \ActionScheduler::logger(), 'log_stored_action', 10 ) );
		remove_action( 'action_scheduler_stored_action', array( $this->destination_logger, 'log_stored_action', 10 ) );

		foreach ( $action_ids as $source_action_id ) {
			$destination_action_id = $this->action_migrator->migrate( $source_action_id );
			if ( $destination_action_id ) {
				$this->destination_logger->log( $destination_action_id, sprintf(
					__( 'Migrated action with ID %d in %s to ID %d in %s', 'action-scheduler' ),
					$source_action_id,
					get_class( $this->source_store ),
					$destination_action_id,
					get_class( $this->destination_store )
				) );
			}
		}

		do_action( 'action_scheduler/custom_tables/migration_batch_complete', $action_ids );
	}
}
