<?php

namespace Action_Scheduler\Custom_Tables;

use ActionScheduler_Action;
use ActionScheduler_ActionClaim;
use ActionScheduler_FinishedAction;
use ActionScheduler_NullAction;
use ActionScheduler_NullSchedule;
use ActionScheduler_Store;

class DB_Store_Migrator extends DB_Store {

	/**
	 * Save an action with optional last attempt date.
	 *
	 * Normally, saving an action sets its attempted date to 0000-00-00 00:00:00 because when an action is first saved,
	 * it can't have been attempted yet, but migrated completed actions will have an attempted date, so we need to save
	 * that when first saving the action.
	 *
	 * @param ActionScheduler_Action $action
	 * @param DateTime $scheduled_date Optional date of the first instance to store.
	 * @param DateTime $last_attempt_date Optional date the action was last attempted.
	 * @return string The action ID
	 */
	public function save_action( ActionScheduler_Action $action, \DateTime $scheduled_date = null, \DateTime $last_attempt_date = null ){
		try {
			/** @var wpdb $wpdb */
			global $wpdb;

			$action_id = parent::save_action( $action, $scheduled_date );

			if ( null !== $last_attempt_date ) {
				$data = [
					'last_attempt_gmt'   => $this->get_timestamp( $action, $last_attempt_date ),
					'last_attempt_local' => $this->get_local_timestamp( $action, $last_attempt_date ),
				];

				$wpdb->update( $wpdb->actionscheduler_actions, $data, array( 'action_id' => $action_id ), array( '%s', '%s' ), array( '%d' ) );
			}

			return $action_id;
		} catch ( \Exception $e ) {
			throw new \RuntimeException( sprintf( __( 'Error saving action: %s', 'action-scheduler' ), $e->getMessage() ), 0 );
		}
	}
}