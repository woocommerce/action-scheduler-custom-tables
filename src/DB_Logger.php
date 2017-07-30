<?php


namespace Action_Scheduler\Custom_Tables;


use ActionScheduler_LogEntry;
use ActionScheduler_NullLogEntry;
use ActionScheduler_TimezoneHelper;
use DateTime;

class DB_Logger extends \ActionScheduler_Logger {

	/**
	 * @param string   $action_id
	 * @param string   $message
	 * @param DateTime $date
	 *
	 * @return string The log entry ID
	 */
	public function log( $action_id, $message, DateTime $date = null ) {
		if ( empty( $date ) ) {
			$date = as_get_datetime_object();
		} else {
			$date = clone $date;
		}

		$date_gmt = $date->format( 'Y-m-d H:i:s' );
		$date->setTimezone( ActionScheduler_TimezoneHelper::get_local_timezone() );
		$date_local = $date->format( 'Y-m-d H:i:s' );

		/** @var \wpdb $wpdb */
		global $wpdb;
		$wpdb->insert( $wpdb->actionscheduler_logs, [
			'action_id'      => $action_id,
			'message'        => $message,
			'log_date_gmt'   => $date_gmt,
			'log_date_local' => $date_local,
		], [ '%d', '%s', '%s', '%s' ] );

		return $wpdb->insert_id;
	}

	/**
	 * @param string $entry_id
	 *
	 * @return ActionScheduler_LogEntry
	 */
	public function get_entry( $entry_id ) {
		/** @var \wpdb $wpdb */
		global $wpdb;
		$entry = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->actionscheduler_logs} WHERE log_id=%d", $entry_id ) );

		return $this->create_entry_from_db_record( $entry );
	}

	/**
	 * @param object $record
	 *
	 * @return ActionScheduler_LogEntry
	 */
	private function create_entry_from_db_record( $record ) {
		if ( empty( $record ) ) {
			return new ActionScheduler_NullLogEntry();
		}

		return new ActionScheduler_LogEntry( $record->action_id, $record->message );
	}

	/**
	 * @param string $action_id
	 *
	 * @return ActionScheduler_LogEntry[]
	 */
	public function get_logs( $action_id ) {
		/** @var \wpdb $wpdb */
		global $wpdb;

		$records = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->actionscheduler_logs} WHERE action_id=%d", $action_id ) );

		return array_map( [ $this, 'create_entry_from_db_record' ], $records );
	}

	/**
	 * @codeCoverageIgnore
	 */
	public function init() {

		$table_maker = new DB_Logger_Table_Maker();
		$table_maker->register_tables();

		add_action( 'action_scheduler_stored_action', [ $this, 'log_stored_action' ], 10, 1 );
		add_action( 'action_scheduler_canceled_action', [ $this, 'log_canceled_action' ], 10, 1 );
		add_action( 'action_scheduler_before_execute', [ $this, 'log_started_action' ], 10, 1 );
		add_action( 'action_scheduler_after_execute', [ $this, 'log_completed_action' ], 10, 1 );
		add_action( 'action_scheduler_failed_execution', [ $this, 'log_failed_action' ], 10, 2 );
		add_action( 'action_scheduler_failed_action', [ $this, 'log_timed_out_action' ], 10, 2 );
		add_action( 'action_scheduler_unexpected_shutdown', [ $this, 'log_unexpected_shutdown' ], 10, 2 );
		add_action( 'action_scheduler_reset_action', [ $this, 'log_reset_action' ], 10, 1 );
		add_action( 'action_scheduler_deleted_action', [ $this, 'clear_deleted_action_logs' ], 10, 1 );
	}

	public function log_stored_action( $action_id ) {
		$this->log( $action_id, __( 'action created', 'action-scheduler' ) );
	}

	public function log_canceled_action( $action_id ) {
		$this->log( $action_id, __( 'action canceled', 'action-scheduler' ) );
	}

	public function log_started_action( $action_id ) {
		$this->log( $action_id, __( 'action started', 'action-scheduler' ) );
	}

	public function log_completed_action( $action_id ) {
		$this->log( $action_id, __( 'action complete', 'action-scheduler' ) );
	}

	public function log_failed_action( $action_id, \Exception $exception ) {
		$this->log( $action_id, sprintf( __( 'action failed: %s', 'action-scheduler' ), $exception->getMessage() ) );
	}

	public function log_timed_out_action( $action_id, $timeout ) {
		$this->log( $action_id, sprintf( __( 'action timed out after %s seconds', 'action-scheduler' ), $timeout ) );
	}

	public function log_unexpected_shutdown( $action_id, $error ) {
		if ( ! empty( $error ) ) {
			$this->log( $action_id, sprintf( __( 'unexpected shutdown: PHP Fatal error %s in %s on line %s', 'action-scheduler' ), $error[ 'message' ], $error[ 'file' ], $error[ 'line' ] ) );
		}
	}

	public function log_reset_action( $action_id ) {
		$this->log( $action_id, __( 'action reset', 'action_scheduler' ) );
	}

	public function clear_deleted_action_logs( $action_id ) {
		/** @var \wpdb $wpdb */
		global $wpdb;
		$wpdb->delete( $wpdb->actionscheduler_logs, [ 'action_id' => $action_id, ], [ '%d' ] );
	}

}