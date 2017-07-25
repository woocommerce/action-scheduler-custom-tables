<?php


namespace Action_Scheduler\Custom_Tables\Migration;


class Dry_Run_Log_Migrator extends Log_Migrator {
	public function migrate( $source_action_id, $destination_action_id ) {
		// no-op
	}

}