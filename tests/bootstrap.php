<?php

$GLOBALS[ 'wp_tests_options' ][ 'active_plugins' ][] = basename( dirname( __DIR__ ) ) . '/action-scheduler-custom-tables.php';

require_once dirname( dirname( __DIR__ ) ) . '/action-scheduler/tests/bootstrap.php';

include_once( __DIR__ . '/UnitTestCase.php' );