<?php

namespace Action_Scheduler\Custom_Tables;

/**
 * Class ActionScheduler_dbStore_TableMaker
 * @codeCoverageIgnore
 *
 * Creates custom tables for storing scheduled actions
 */
class DB_Store_Table_Maker {
	const ACTIONS_TABLE = 'actionscheduler_actions';
	const CLAIMS_TABLE  = 'actionscheduler_claims';
	const GROUPS_TABLE  = 'actionscheduler_groups';

	private $schema_version = 1;
	private $tables         = [];

	public function register_tables() {
		global $wpdb;
		$tables = [ self::ACTIONS_TABLE, self::CLAIMS_TABLE, self::GROUPS_TABLE ];

		// make WP aware of our tables
		foreach ( $tables as $table ) {
			$wpdb->tables[] = $table;
			$name           = $this->get_full_table_name( $table );
			$wpdb->$table   = $name;
		}

		// create the tables
		if ( $this->schema_update_required() ) {
			foreach ( $tables as $table ) {
				$this->update_table( $table );
			}
			$this->mark_schema_update_complete();
		}
	}

	private function schema_update_required() {
		$option_name         = 'schema-' . __CLASS__;
		$version_found_in_db = get_option( $option_name, 0 );

		return version_compare( $version_found_in_db, $this->schema_version, '<' );
	}

	private function mark_schema_update_complete() {
		$option_name = 'schema-' . __CLASS__;

		// work around race conditions and ensure that our option updates
		$value_to_save = (string) $this->schema_version . '.0.' . time();

		update_option( $option_name, $value_to_save );
	}

	private function update_table( $table ) {
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		$definition = $this->get_table_definition( $table );
		if ( $definition ) {
			dbDelta( $definition );
		}
	}

	private function get_table_definition( $table ) {
		global $wpdb;
		$table_name = $wpdb->$table;
		$charset_collate = $wpdb->get_charset_collate();
		$max_index_length = 191; // @see wp_get_db_schema()
		switch ( $table ) {

			case self::ACTIONS_TABLE:

				return "CREATE TABLE {$table_name} (
				        action_id bigint(20) unsigned NOT NULL auto_increment,
				        hook varchar(255) NOT NULL,
				        status varchar(20) NOT NULL,
				        scheduled_date_gmt datetime NOT NULL default '0000-00-00 00:00:00',
				        scheduled_date_local datetime NOT NULL default '0000-00-00 00:00:00',
				        args longtext,
				        schedule longtext,
				        group_id bigint(20) unsigned NOT NULL default '0',
				        attempts int(11) NOT NULL default '0',
				        last_attempt_gmt datetime NOT NULL default '0000-00-00 00:00:00',
				        last_attempt_local datetime NOT NULL default '0000-00-00 00:00:00',
				        claim_id bigint(20) unsigned NOT NULL default '0',
				        PRIMARY KEY  (action_id),
				        KEY hook (hook($max_index_length)),
				        KEY status (status),
				        KEY scheduled_date_gmt (scheduled_date_gmt),
				        KEY scheduled_date_local (scheduled_date_local),
				        KEY group_id (group_id),
				        KEY last_attempt_gmt (last_attempt_gmt),
				        KEY last_attempt_local (last_attempt_local),
				        KEY claim_id (claim_id)
				        ) $charset_collate";

			case self::CLAIMS_TABLE:

				return "CREATE TABLE {$table_name} (
				        claim_id bigint(20) unsigned NOT NULL auto_increment,
				        date_created_gmt datetime NOT NULL default '0000-00-00 00:00:00',
				        PRIMARY KEY  (claim_id),
				        KEY date_created_gmt (date_created_gmt)
				        ) $charset_collate";

			case self::GROUPS_TABLE:

				return "CREATE TABLE {$table_name} (
				        group_id bigint(20) unsigned NOT NULL auto_increment,
				        slug varchar(255) NOT NULL,
				        PRIMARY KEY  (group_id),
				        KEY slug (slug($max_index_length))
				        ) $charset_collate";

			default:
				return '';
		}
	}

	private function get_full_table_name( $table ) {
		return $GLOBALS[ 'wpdb' ]->prefix . $table;
	}
}