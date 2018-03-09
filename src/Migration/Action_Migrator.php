<?php


namespace Action_Scheduler\Custom_Tables\Migration;


class Action_Migrator {

	/** @var \ActionScheduler_Store */
	private $source;

	/** @var \ActionScheduler_Store */
	private $destination;

	public function __construct( \ActionScheduler_Store $source_store, \ActionScheduler_Store $destination_store ) {
		$this->source      = $source_store;
		$this->destination = $destination_store;
	}

	public function migrate( $source_action_id ) {
		$action = $this->source->fetch_action( $source_action_id );

		try {
			$status = $this->source->get_status( $source_action_id );
		} catch ( \Exception $e ) {
			$status = '';
		}

		if ( empty( $status ) || ! $action->get_schedule()->next() ) {
			// empty status means the action didn't exist
			// null schedule means it's missing vital data
			// delete it and move on
			try {
				$this->source->delete_action( $source_action_id );
			} catch ( \Exception $e ) {
				// nothing to do, it didn't exist in the first place
			}
			do_action( 'action_scheduler/custom_tables/no_action_to_migrate', $source_action_id, $this->source, $this->destination );

			return 0;
		}

		try {
			$destination_action_id = $this->destination->save_action( $action );
		} catch ( \Exception $e ) {
			do_action( 'action_scheduler/custom_tables/migrate_action_failed', $source_action_id, $this->source, $this->destination );

			return 0; // could not save the action in the new store
		}


		try {
			if ( $status == \ActionScheduler_Store::STATUS_FAILED ) {
				$this->destination->mark_failure( $destination_action_id );
			}
			$this->source->delete_action( $source_action_id );

			do_action( 'action_scheduler/custom_tables/migrated_action', $source_action_id, $destination_action_id, $this->source, $this->destination );

			return $destination_action_id;
		} catch ( \Exception $e ) {
			// could not delete from the old store
			do_action( 'action_scheduler/custom_tables/migrate_action_incomplete', $source_action_id, $destination_action_id, $this->source, $this->destination );
			do_action( 'action_scheduler/custom_tables/migrated_action', $source_action_id, $destination_action_id, $this->source, $this->destination );

			return $destination_action_id;
		}
	}
}