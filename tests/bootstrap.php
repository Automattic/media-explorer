<?php
/**
 * PHPUnit bootstrap file for Media Explorer plugin tests.
 *
 * @package Automattic\MediaExplorer
 */

declare( strict_types=1 );

namespace MediaExplorer\Tests;

use Yoast\WPTestUtils\WPIntegration;

// Composer autoloader.
require_once dirname( __DIR__ ) . '/vendor/autoload.php';

// Check for a `--testsuite integration` or `--testsuite=integration` arg when calling phpunit,
// and use it to conditionally load up WordPress.
$argv_local     = $GLOBALS['argv'] ?? [];
$key            = (int) array_search( '--testsuite', $argv_local, true );
$is_integration = false;
$is_unit        = false;

// Check for --testsuite integration (two separate args).
if ( $key && isset( $argv_local[ $key + 1 ] ) ) {
	if ( 'integration' === $argv_local[ $key + 1 ] ) {
		$is_integration = true;
	} elseif ( 'Unit' === $argv_local[ $key + 1 ] ) {
		$is_unit = true;
	}
}

// Check for --testsuite=integration or --testsuite=Unit (single arg with equals).
foreach ( $argv_local as $arg ) {
	if ( '--testsuite=integration' === $arg ) {
		$is_integration = true;
		break;
	}
	if ( '--testsuite=Unit' === $arg ) {
		$is_unit = true;
		break;
	}
}

if ( $is_unit ) {
	// Unit tests use Brain Monkey - no WordPress loaded.
	// Load plugin classes that can be tested without WordPress.
	require_once dirname( __DIR__ ) . '/class.response.php';
	require_once __DIR__ . '/Unit/TestCase.php';
	return;
}

if ( $is_integration ) {
	require_once dirname( __DIR__ ) . '/vendor/yoast/wp-test-utils/src/WPIntegration/bootstrap-functions.php';

	$_tests_dir = WPIntegration\get_path_to_wp_test_dir();

	// Give access to tests_add_filter() function.
	require_once $_tests_dir . '/includes/functions.php';

	// Manually load the plugin being tested.
	\tests_add_filter(
		'muplugins_loaded',
		function (): void {
			require dirname( __DIR__ ) . '/media-explorer.php';
		}
	);

	/*
	 * Bootstrap WordPress. This will also load the Composer autoload file, the PHPUnit Polyfills
	 * and the custom autoloader for the TestCase and the mock object classes.
	 */
	WPIntegration\bootstrap_it();
}
