<?php
/**
 * Integration tests for the Media_Explorer class.
 *
 * @package MediaExplorer\Tests\Integration
 */

declare( strict_types=1 );

namespace MediaExplorer\Tests\Integration;

use Media_Explorer;
use MEXP_Instagram_Service;
use MEXP_Twitter_Service;
use MEXP_YouTube_Service;
use stdClass;
use WP_Error;
use Yoast\WPTestUtils\WPIntegration\TestCase;

/**
 * Tests for service registration and the media manager integration.
 *
 * @coversDefaultClass \Media_Explorer
 */
final class MediaExplorerTest extends TestCase {

	/**
	 * The plugin instance.
	 *
	 * @var Media_Explorer
	 */
	private $plugin;

	/**
	 * The services registered when the test started, restored afterwards.
	 *
	 * @var array<string, \MEXP_Service>
	 */
	private $services;

	protected function setUp(): void {
		parent::setUp();

		$this->plugin   = Media_Explorer::init();
		$this->services = $this->plugin->services;

		$GLOBALS['wp_scripts'] = null;
		$GLOBALS['wp_styles']  = null;
	}

	protected function tearDown(): void {
		$this->plugin->services = $this->services;

		$GLOBALS['wp_scripts'] = null;
		$GLOBALS['wp_styles']  = null;

		parent::tearDown();
	}

	/**
	 * @covers ::__construct
	 */
	public function test_hooks_into_wordpress(): void {
		$this->assertSame( 10, has_action( 'plugins_loaded', array( $this->plugin, 'action_plugins_loaded' ) ) );
		$this->assertSame( 10, has_action( 'init', array( $this->plugin, 'action_init' ) ) );
		$this->assertSame( 10, has_action( 'wp_enqueue_media', array( $this->plugin, 'action_enqueue_media' ) ) );
		$this->assertSame( 10, has_action( 'print_media_templates', array( $this->plugin, 'action_print_media_templates' ) ) );
	}

	/**
	 * The search endpoint uses the site's API credentials, so it must not be open to visitors.
	 *
	 * @covers ::__construct
	 */
	public function test_ajax_handler_is_only_registered_for_logged_in_users(): void {
		$this->assertSame( 10, has_action( 'wp_ajax_mexp_request', array( $this->plugin, 'ajax_request' ) ) );
		$this->assertFalse( has_action( 'wp_ajax_nopriv_mexp_request' ) );
	}

	/**
	 * @covers ::action_init
	 */
	public function test_registers_bundled_services(): void {
		$this->assertEqualsCanonicalizing( array( 'instagram', 'twitter', 'youtube' ), array_keys( $this->services ) );
		$this->assertInstanceOf( MEXP_Instagram_Service::class, $this->services['instagram'] );
		$this->assertInstanceOf( MEXP_Twitter_Service::class, $this->services['twitter'] );
		$this->assertInstanceOf( MEXP_YouTube_Service::class, $this->services['youtube'] );
	}

	/**
	 * @covers ::action_init
	 */
	public function test_ignores_services_that_do_not_extend_the_base_class(): void {
		add_filter(
			'mexp_services',
			static function ( array $services ): array {
				$services['bogus'] = new stdClass();
				return $services;
			}
		);
		$this->plugin->services = array();

		$this->plugin->action_init();

		$this->assertArrayNotHasKey( 'bogus', $this->plugin->services );
		$this->assertArrayHasKey( 'youtube', $this->plugin->services );
	}

	/**
	 * @covers ::get_service
	 */
	public function test_get_service_returns_the_registered_service(): void {
		$this->assertSame( $this->services['youtube'], $this->plugin->get_service( 'youtube' ) );
	}

	/**
	 * @covers ::get_service
	 */
	public function test_get_service_returns_an_error_for_an_unknown_service(): void {
		$result = $this->plugin->get_service( 'nope' );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'invalid_service', $result->get_error_code() );
	}

	/**
	 * @covers ::action_enqueue_media
	 */
	public function test_enqueue_media_loads_scripts_and_passes_services_to_javascript(): void {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'editor' ) ) );
		$enqueued = did_action( 'mexp_enqueue' );

		$this->plugin->action_enqueue_media();

		$this->assertTrue( wp_script_is( 'mexp', 'enqueued' ) );
		$this->assertTrue( wp_style_is( 'mexp', 'enqueued' ) );
		$this->assertTrue( wp_script_is( 'mexp-service-youtube', 'enqueued' ) );
		$this->assertSame( $enqueued + 1, did_action( 'mexp_enqueue' ) );

		$this->assertSame( 1, preg_match( '/var mexp = (\{.*\});/s', (string) wp_scripts()->get_data( 'mexp', 'data' ), $matches ) );
		$mexp = json_decode( $matches[1], true );

		$this->assertSame( 1, wp_verify_nonce( $mexp['_nonce'], 'mexp_request' ) );
		$this->assertEqualsCanonicalizing( array( 'instagram', 'twitter', 'youtube' ), array_keys( $mexp['services'] ) );
		$this->assertSame( 'youtube', $mexp['services']['youtube']['id'] );
		$this->assertSame( array( 'all', 'by_user' ), array_keys( $mexp['services']['youtube']['tabs'] ) );
		$this->assertSame( 'Insert YouTube', $mexp['services']['youtube']['labels']['title'] );
	}

	/**
	 * @covers ::action_print_media_templates
	 */
	public function test_prints_backbone_templates_for_each_service_tab(): void {
		// The media manager loads the services before printing their templates.
		foreach ( $this->services as $service ) {
			$service->load();
		}

		ob_start();
		$this->plugin->action_print_media_templates();
		$output = (string) ob_get_clean();

		foreach ( array( 'search', 'item' ) as $template ) {
			foreach ( array( 'all', 'by_user' ) as $tab ) {
				$this->assertStringContainsString( sprintf( 'id="tmpl-mexp-youtube-%s-%s"', $template, $tab ), $output );
			}
		}
		$this->assertStringContainsString( 'id="tmpl-mexp-youtube-thumbnail"', $output );
		$this->assertStringContainsString( 'id="tmpl-mexp-twitter-search-hashtag"', $output );
		$this->assertStringContainsString( 'id="tmpl-mexp-instagram-item-popular"', $output );
	}
}
