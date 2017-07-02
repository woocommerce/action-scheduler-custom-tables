<?php


namespace Action_Scheduler\Custom_Tables\Migration;


use ActionScheduler_Logger as Logger;
use ActionScheduler_Store as Store;

class Migration_Runner {
	private $source_store;
	private $destination_store;
	private $source_logger;
	private $destination_logger;

	private $batch_fetcher;
	private $action_migrator;
	private $log_migrator;

	public function __construct( Store $source_store, Store $destination_store, Logger $source_logger, Logger $destination_logger ) {
		$this->source_store       = $source_store;
		$this->destination_store  = $destination_store;
		$this->source_logger      = $source_logger;
		$this->destination_logger = $destination_logger;

		$this->batch_fetcher   = new Batch_Fetcher( $this->source_store );
		$this->action_migrator = new Action_Migrator( $this->source_store, $this->destination_store );
		$this->log_migrator    = new Log_Migrator( $this->source_logger, $this->destination_logger );
	}

	public function run( $batch_size = 10 ) {
		$batch = $this->batch_fetcher->fetch( $batch_size );

		foreach ( $batch as $source_action_id ) {
			$destination_action_id = $this->action_migrator->migrate( $source_action_id );
			if ( $destination_action_id ) {
				$this->log_migrator->migrate( $source_action_id, $destination_action_id );
			}
			$this->destination_logger->log( $destination_action_id, sprintf(
				__( 'Migrated action with ID %d in %s to ID %d in %s', 'action-scheduler' ),
				$source_action_id,
				get_class( $this->source_store ),
				$destination_action_id,
				get_class( $this->destination_store )
			) );
		}
	}
}