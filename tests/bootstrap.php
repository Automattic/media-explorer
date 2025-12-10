<?php
/**
 * PHPUnit bootstrap file for Media Explorer plugin tests.
 *
 * @package Automattic\MediaExplorer
 */

declare( strict_types=1 );

namespace MediaExplorer\Tests;

use Yoast\WPTestUtils\WPIntegration;

require_once dirname( __DIR__ ) . '/vendor/yoast/wp-test-utils/src/WPIntegration/bootstrap-functions.php';

// Check for a `--testsuite integration` or `--testsuite=integration` arg when calling phpunit,
// and use it to conditionally load up WordPress.
$argv_local     = $GLOBALS['argv'] ?? [];
$key            = (int) array_search( '--testsuite', $argv_local, true );
$is_integration = false;

// Check for --testsuite integration (two separate args).
if ( $key && isset( $argv_local[ $key + 1 ] ) && 'integration' === $argv_local[ $key + 1 ] ) {
	$is_integration = true;
}

// Check for --testsuite=integration (single arg with equals).
foreach ( $argv_local as $arg ) {
	if ( '--testsuite=integration' === $arg ) {
		$is_integration = true;
		break;
	}
}

if ( $is_integration ) {
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
