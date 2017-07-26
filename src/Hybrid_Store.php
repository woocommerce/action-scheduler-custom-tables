<?php


namespace Action_Scheduler\Custom_Tables;


use Action_Scheduler\Custom_Tables\Migration\Migration_Config;
use Action_Scheduler\Custom_Tables\Migration\Migration_Runner;
use ActionScheduler_Action;
use ActionScheduler_ActionClaim;
use ActionScheduler_Store as Store;
use DateTime;

/**
 * Class Hybrid_Store
 *
 * A wrapper around multiple stores that fetches data from both
 */
class Hybrid_Store extends Store {
	private $primary_store;
	private $secondary_store;
	private $migration_runner;

	public function __construct( Migration_Config $config ) {
		$this->primary_store    = $config->get_destination_store();
		$this->secondary_store  = $config->get_source_store();
		$this->migration_runner = new Migration_Runner( $config );
	}

	/**
	 * @codeCoverageIgnore
	 */
	public function init() {
		$this->primary_store->init();
		$this->secondary_store->init();
	}

	/**
	 * Find the first matching action from the secondary store.
	 * If it exists, migrate it to the primary store immediately.
	 * After it migrates, the secondary store will logically contain
	 * the next matching action, so return the result thence.
	 *
	 * @param string $hook
	 * @param array  $params
	 *
	 * @return string
	 */
	public function find_action( $hook, $params = [] ) {
		$found_unmigrated_action = $this->secondary_store->find_action( $hook, $params );
		if ( ! empty( $found_unmigrated_action ) ) {
			$this->migrate( [ $found_unmigrated_action ] );
		}

		return $this->primary_store->find_action( $hook, $params );
	}

	/**
	 * Find actions matching the query in the secondary source first.
	 * If any are found, migrate them immediately. Then the secondary
	 * store will contain the canonical results.
	 *
	 * @param array $query
	 *
	 * @return int[]
	 */
	public function query_actions( $query = [] ) {
		$found_unmigrated_actions = $this->secondary_store->query_actions( $query );
		if ( ! empty( $found_unmigrated_actions ) ) {
			$this->migrate( $found_unmigrated_actions );
		}

		return $this->primary_store->query_actions( $query );
	}

	/**
	 * If any actions would have been claimed by the secondary store,
	 * migrate them immediately, then ask the primary store for the
	 * canonical claim.
	 *
	 * @param int           $max_actions
	 * @param DateTime|null $before_date
	 *
	 * @return ActionScheduler_ActionClaim
	 */
	public function stake_claim( $max_actions = 10, DateTime $before_date = null ) {
		$claim = $this->secondary_store->stake_claim( $max_actions, $before_date );

		$claimed_actions = $claim->get_actions();
		if ( ! empty( $claimed_actions ) ) {
			$this->migrate( $claimed_actions );
		}

		$this->secondary_store->release_claim( $claim );

		return $this->primary_store->stake_claim( $max_actions, $before_date );
	}

	private function migrate( $action_ids ) {
		$this->migration_runner->migrate_actions( $action_ids );
	}

	public function save_action( ActionScheduler_Action $action, DateTime $date = null ) {
		return $this->primary_store->save_action( $action, $date );
	}

	public function fetch_action( $action_id ) {
		return $this->primary_store->fetch_action( $action_id );
	}

	public function cancel_action( $action_id ) {
		$this->primary_store->cancel_action( $action_id );
	}

	public function delete_action( $action_id ) {
		$this->primary_store->delete_action( $action_id );
	}

	public function get_date( $action_id ) {
		return $this->primary_store->get_date( $action_id );
	}

	public function get_claim_count() {
		return $this->primary_store->get_claim_count();
	}

	public function release_claim( ActionScheduler_ActionClaim $claim ) {
		$this->primary_store->release_claim( $claim );
	}

	public function unclaim_action( $action_id ) {
		$this->primary_store->unclaim_action( $action_id );
	}

	public function mark_failure( $action_id ) {
		$this->primary_store->mark_failure( $action_id );
	}

	public function log_execution( $action_id ) {
		$this->primary_store->log_execution( $action_id );
	}

	public function mark_complete( $action_id ) {
		$this->primary_store->mark_complete( $action_id );
	}

	public function find_actions_by_claim_id( $claim_id ) {
		return $this->primary_store->find_actions_by_claim_id( $claim_id );
	}
}