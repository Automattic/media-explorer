<?php
/**
 * Integration tests for the Media Explorer plugin.
 *
 * @package MediaExplorer\Tests\Integration
 */

namespace MediaExplorer\Tests\Integration;

use Yoast\WPTestUtils\WPIntegration\TestCase;

/**
 * Test case for Media Explorer plugin.
 */
class MediaExplorerTest extends TestCase {

	/**
	 * Test that the plugin is loaded.
	 */
	public function test_plugin_is_loaded(): void {
		$this->assertTrue(
			class_exists( 'Media_Explorer' ),
			'Media_Explorer class should exist after plugin is loaded'
		);
	}

	/**
	 * Test that the Media_Explorer instance is available via init().
	 */
	public function test_media_explorer_instance_is_available(): void {
		$instance = \Media_Explorer::init();

		$this->assertInstanceOf(
			'Media_Explorer',
			$instance,
			'Media_Explorer::init() should return an instance of Media_Explorer'
		);
	}

	/**
	 * Test that services property is accessible.
	 */
	public function test_services_property_exists(): void {
		$instance = \Media_Explorer::init();

		$this->assertTrue(
			property_exists( $instance, 'services' ),
			'Media_Explorer instance should have a services property'
		);
	}
}
