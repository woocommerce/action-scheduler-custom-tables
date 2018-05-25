<?php

namespace Action_Scheduler\Custom_Tables;

use ActionScheduler_FinishedAction;
use ActionScheduler_SimpleSchedule;

/**
 * Class DB_Store_Migrator_Test
 *
 * @group stores
 */
class DB_Store_Migrator_Test extends UnitTestCase {

	public function test_create_action_with_last_attempt_date() {
		$scheduled_date    = as_get_datetime_object( strtotime( '-24 hours' ) );
		$last_attempt_date = as_get_datetime_object( strtotime( '-23 hours' ) );

		$action = new ActionScheduler_FinishedAction( 'my_hook', [], new ActionScheduler_SimpleSchedule( $scheduled_date ) );
		$store  = new DB_Store_Migrator();

		$action_id   = $store->save_action( $action, null, $last_attempt_date );
		$action_date = $store->get_date( $action_id );

		$this->assertEquals( $last_attempt_date->format( 'U' ), $action_date->format( 'U' ) );

		$action_id   = $store->save_action( $action, $scheduled_date, $last_attempt_date );
		$action_date = $store->get_date( $action_id );

		$this->assertEquals( $last_attempt_date->format( 'U' ), $action_date->format( 'U' ) );
	}
}
