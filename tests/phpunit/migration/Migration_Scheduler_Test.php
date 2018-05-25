<?php


namespace Action_Scheduler\Custom_Tables;

use Action_Scheduler\Custom_Tables\Migration\Migration_Scheduler;
use ActionScheduler_Action;
use ActionScheduler_SimpleSchedule;
use ActionScheduler_wpPostStore as PostStore;

class Migration_Scheduler_Test extends UnitTestCase {
	public function setUp() {
		parent::setUp();
		if ( ! taxonomy_exists( PostStore::GROUP_TAXONOMY ) ) {
			// register the post type and taxonomy necessary for the store to work
			$store = new PostStore();
			$store->init();
		}
	}

	public function test_migration_is_complete() {
		$scheduler = new Migration_Scheduler();
		update_option( Migration_Scheduler::STATUS_FLAG, Migration_Scheduler::STATUS_COMPLETE );
		$this->assertTrue( $scheduler->is_migration_complete() );
	}

	public function test_migration_is_not_complete() {
		$scheduler = new Migration_Scheduler();
		$this->assertFalse( $scheduler->is_migration_complete() );
		update_option( Migration_Scheduler::STATUS_FLAG, 'something_random' );
		$this->assertFalse( $scheduler->is_migration_complete() );
	}

	public function test_migration_is_scheduled() {
		$scheduler = new Migration_Scheduler();
		$scheduler->schedule_migration();
		$this->assertTrue( $scheduler->is_migration_scheduled() );
	}

	public function test_migration_is_not_scheduled() {
		$scheduler = new Migration_Scheduler();
		$this->assertFalse( $scheduler->is_migration_scheduled() );
	}

	public function test_scheduler_runs_migration() {
		$source_store      = new PostStore();
		$destination_store = new DB_Store();

		$return_5 = function () {
			return 5;
		};
		add_filter( 'action_scheduler/custom_tables/migration_batch_size', $return_5 );

		for ( $i = 0; $i < 10; $i ++ ) {
			$time     = as_get_datetime_object( $i + 1 . ' minutes' );
			$schedule = new ActionScheduler_SimpleSchedule( $time );
			$action   = new ActionScheduler_Action( 'my_hook', [], $schedule );
			$future[] = $source_store->save_action( $action );

			$time     = as_get_datetime_object( $i + 1 . ' minutes ago' );
			$schedule = new ActionScheduler_SimpleSchedule( $time );
			$action   = new ActionScheduler_Action( 'my_hook', [], $schedule );
			$due[]    = $source_store->save_action( $action );
		}

		$this->assertCount( 20, $source_store->query_actions( [ 'per_page' => 0 ] ) );

		$scheduler = new Migration_Scheduler();
		$scheduler->schedule_migration();

		$queue_runner = new \ActionScheduler_QueueRunner( $destination_store );
		$queue_runner->run();

		// 5 actions should have moved from the source store when the queue runner triggered the migration action
		$this->assertCount( 15, $source_store->query_actions( [ 'per_page' => 0 ] ) );

		remove_filter( 'action_scheduler/custom_tables/migration_batch_size', $return_5 );
	}

	public function test_scheduler_marks_itself_complete() {
		$source_store      = new PostStore();
		$destination_store = new DB_Store();

		for ( $i = 0; $i < 5; $i ++ ) {
			$time     = as_get_datetime_object( $i + 1 . ' minutes ago' );
			$schedule = new ActionScheduler_SimpleSchedule( $time );
			$action   = new ActionScheduler_Action( 'my_hook', [], $schedule );
			$due[]    = $source_store->save_action( $action );
		}

		$this->assertCount( 5, $source_store->query_actions( [ 'per_page' => 0 ] ) );

		$scheduler = new Migration_Scheduler();
		$scheduler->schedule_migration();

		$queue_runner = new \ActionScheduler_QueueRunner( $destination_store );
		$queue_runner->run();

		// All actions should have moved from the source store when the queue runner triggered the migration action
		$this->assertCount( 0, $source_store->query_actions( [ 'per_page' => 0 ] ) );

		// schedule another so we can get it to run immediately
		$scheduler->unschedule_migration();
		$scheduler->schedule_migration();

		// run again so it knows that there's nothing left to process
		$queue_runner->run();

		$scheduler->unhook();

		// ensure the flag is set marking migration as complete
		$this->assertTrue( $scheduler->is_migration_complete() );

		// ensure that another instance has not been scheduled
		$this->assertFalse( $scheduler->is_migration_scheduled() );

	}
}
