<?php


namespace Action_Scheduler\Custom_Tables;

use Action_Scheduler\Custom_Tables\Migration\Migration_Config;

class Migration_Config_Test extends UnitTestCase {
	public function test_source_store_required() {
		$config = new Migration_Config();
		$this->expectException( \RuntimeException::class );
		$config->get_source_store();
	}

	public function test_source_logger_required() {
		$config = new Migration_Config();
		$this->expectException( \RuntimeException::class );
		$config->get_source_logger();
	}

	public function test_destination_store_required() {
		$config = new Migration_Config();
		$this->expectException( \RuntimeException::class );
		$config->get_destination_store();
	}

	public function test_destination_logger_required() {
		$config = new Migration_Config();
		$this->expectException( \RuntimeException::class );
		$config->get_destination_logger();
	}
}