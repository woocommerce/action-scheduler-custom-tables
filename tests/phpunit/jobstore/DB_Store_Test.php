<?php

namespace Action_Scheduler\Custom_Tables;
use ActionScheduler_Action;
use ActionScheduler_IntervalSchedule;
use ActionScheduler_SimpleSchedule;
use ActionScheduler_Store;

/**
 * Class DB_Store_Test
 *
 * @group stores
 */
class DB_Store_Test extends UnitTestCase {

	public function test_create_action() {
		$time      = as_get_datetime_object();
		$schedule  = new ActionScheduler_SimpleSchedule( $time );
		$action    = new ActionScheduler_Action( 'my_hook', [], $schedule );
		$store     = new DB_Store();
		$action_id = $store->save_action( $action );

		$this->assertNotEmpty( $action_id );
	}

	public function test_retrieve_action() {
		$time      = as_get_datetime_object();
		$schedule  = new ActionScheduler_SimpleSchedule( $time );
		$action    = new ActionScheduler_Action( 'my_hook', [], $schedule, 'my_group' );
		$store     = new DB_Store();
		$action_id = $store->save_action( $action );

		$retrieved = $store->fetch_action( $action_id );
		$this->assertEquals( $action->get_hook(), $retrieved->get_hook() );
		$this->assertEqualSets( $action->get_args(), $retrieved->get_args() );
		$this->assertEquals( $action->get_schedule()->next()->format( 'U' ), $retrieved->get_schedule()->next()->format( 'U' ) );
		$this->assertEquals( $action->get_group(), $retrieved->get_group() );
	}

	public function test_cancel_action() {
		$time      = as_get_datetime_object();
		$schedule  = new ActionScheduler_SimpleSchedule( $time );
		$action    = new ActionScheduler_Action( 'my_hook', [], $schedule, 'my_group' );
		$store     = new DB_Store();
		$action_id = $store->save_action( $action );
		$store->cancel_action( $action_id );

		$fetched = $store->fetch_action( $action_id );
		$this->assertInstanceOf( 'ActionScheduler_NullAction', $fetched );
	}

	public function test_claim_actions() {
		$created_actions = [];
		$store           = new DB_Store();
		for ( $i = 3; $i > - 3; $i -- ) {
			$time     = as_get_datetime_object( $i . ' hours' );
			$schedule = new ActionScheduler_SimpleSchedule( $time );
			$action   = new ActionScheduler_Action( 'my_hook', [ $i ], $schedule, 'my_group' );

			$created_actions[] = $store->save_action( $action );
		}

		$claim = $store->stake_claim();
		$this->assertInstanceof( 'ActionScheduler_ActionClaim', $claim );

		$this->assertCount( 3, $claim->get_actions() );
		$this->assertEqualSets( array_slice( $created_actions, 3, 3 ), $claim->get_actions() );
	}

	public function test_duplicate_claim() {
		$created_actions = [];
		$store           = new DB_Store();
		for ( $i = 0; $i > - 3; $i -- ) {
			$time     = as_get_datetime_object( $i . ' hours' );
			$schedule = new ActionScheduler_SimpleSchedule( $time );
			$action   = new ActionScheduler_Action( 'my_hook', [ $i ], $schedule, 'my_group' );

			$created_actions[] = $store->save_action( $action );
		}

		$claim1 = $store->stake_claim();
		$claim2 = $store->stake_claim();
		$this->assertCount( 3, $claim1->get_actions() );
		$this->assertCount( 0, $claim2->get_actions() );
	}

	public function test_release_claim() {
		$created_actions = [];
		$store           = new DB_Store();
		for ( $i = 0; $i > - 3; $i -- ) {
			$time     = as_get_datetime_object( $i . ' hours' );
			$schedule = new ActionScheduler_SimpleSchedule( $time );
			$action   = new ActionScheduler_Action( 'my_hook', [ $i ], $schedule, 'my_group' );

			$created_actions[] = $store->save_action( $action );
		}

		$claim1 = $store->stake_claim();

		$store->release_claim( $claim1 );

		$claim2 = $store->stake_claim();
		$this->assertCount( 3, $claim2->get_actions() );
	}

	public function test_search() {
		$created_actions = [];
		$store           = new DB_Store();
		for ( $i = - 3; $i <= 3; $i ++ ) {
			$time     = as_get_datetime_object( $i . ' hours' );
			$schedule = new ActionScheduler_SimpleSchedule( $time );
			$action   = new ActionScheduler_Action( 'my_hook', [ $i ], $schedule, 'my_group' );

			$created_actions[] = $store->save_action( $action );
		}

		$next_no_args = $store->find_action( 'my_hook' );
		$this->assertEquals( $created_actions[ 0 ], $next_no_args );

		$next_with_args = $store->find_action( 'my_hook', [ 'args' => [ 1 ] ] );
		$this->assertEquals( $created_actions[ 4 ], $next_with_args );

		$non_existent = $store->find_action( 'my_hook', [ 'args' => [ 17 ] ] );
		$this->assertNull( $non_existent );
	}

	public function test_search_by_group() {
		$store    = new DB_Store();
		$schedule = new ActionScheduler_SimpleSchedule( as_get_datetime_object( 'tomorrow' ) );

		$abc = $store->save_action( new ActionScheduler_Action( 'my_hook', [ 1 ], $schedule, 'abc' ) );
		$def = $store->save_action( new ActionScheduler_Action( 'my_hook', [ 1 ], $schedule, 'def' ) );
		$ghi = $store->save_action( new ActionScheduler_Action( 'my_hook', [ 1 ], $schedule, 'ghi' ) );

		$this->assertEquals( $abc, $store->find_action( 'my_hook', [ 'group' => 'abc' ] ) );
		$this->assertEquals( $def, $store->find_action( 'my_hook', [ 'group' => 'def' ] ) );
		$this->assertEquals( $ghi, $store->find_action( 'my_hook', [ 'group' => 'ghi' ] ) );
	}

	public function test_get_run_date() {
		$time      = as_get_datetime_object( '-10 minutes' );
		$schedule  = new ActionScheduler_IntervalSchedule( $time, HOUR_IN_SECONDS );
		$action    = new ActionScheduler_Action( 'my_hook', [], $schedule );
		$store     = new DB_Store();
		$action_id = $store->save_action( $action );

		$this->assertEquals( $time->format( 'U' ), $store->get_date( $action_id )->format( 'U' ) );

		$action = $store->fetch_action( $action_id );
		$action->execute();
		$now = as_get_datetime_object();
		$store->mark_complete( $action_id );

		$this->assertEquals( $now->format( 'U' ), $store->get_date( $action_id )->format( 'U' ) );

		$next          = $action->get_schedule()->next( $now );
		$new_action_id = $store->save_action( $action, $next );

		$this->assertEquals( (int) ( $now->format( 'U' ) ) + HOUR_IN_SECONDS, $store->get_date( $new_action_id )->format( 'U' ) );
	}

	public function test_get_status() {
		$time = as_get_datetime_object('-10 minutes');
		$schedule = new ActionScheduler_IntervalSchedule($time, HOUR_IN_SECONDS);
		$action = new ActionScheduler_Action('my_hook', array(), $schedule);
		$store = new DB_Store();
		$action_id = $store->save_action($action);

		$this->assertEquals( ActionScheduler_Store::STATUS_PENDING, $store->get_status( $action_id ) );

		$store->mark_complete( $action_id );
		$this->assertEquals( ActionScheduler_Store::STATUS_COMPLETE, $store->get_status( $action_id ) );

		$store->mark_failure( $action_id );
		$this->assertEquals( ActionScheduler_Store::STATUS_FAILED, $store->get_status( $action_id ) );
	}
}
 