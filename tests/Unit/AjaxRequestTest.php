<?php
/**
 * Unit tests for the mexp_request AJAX handler.
 *
 * @package MediaExplorer\Tests\Unit
 */

declare( strict_types=1 );

namespace MediaExplorer\Tests\Unit;

use Brain\Monkey\Functions;
use Mockery;
use ReflectionClass;

/**
 * Test case for Media_Explorer::ajax_request().
 */
class AjaxRequestTest extends TestCase {

	/**
	 * The plugin instance, built without its constructor so no hooks are added.
	 *
	 * @var \Media_Explorer
	 */
	private $mexp;

	/**
	 * The service the request is routed to.
	 *
	 * @var \Mockery\MockInterface
	 */
	private $service;

	/**
	 * Set up a plugin instance with one service and a request for it.
	 */
	protected function set_up() {
		parent::set_up();

		$root = dirname( __DIR__, 2 );
		require_once $root . '/class.plugin.php';
		require_once $root . '/class.mexp.php';
		require_once $root . '/class.service.php';

		$this->service = Mockery::mock( 'MEXP_Service' );
		$this->service->allows( 'requires' )->andReturn( array() );

		$this->mexp           = ( new ReflectionClass( 'Media_Explorer' ) )->newInstanceWithoutConstructor();
		$this->mexp->services = array( 'test' => $this->service );

		$_POST = array(
			'_nonce'  => 'nonce',
			'service' => 'test',
		);

		Functions\when( 'check_ajax_referer' )->justReturn( 1 );
		Functions\when( 'stripslashes_deep' )->returnArg();
		Functions\when( 'wp_unslash' )->returnArg();
		Functions\when( 'sanitize_text_field' )->returnArg();
		Functions\when( 'wp_parse_args' )->alias( 'array_merge' );
		Functions\when( 'get_current_user_id' )->justReturn( 1 );
		$this->stubTranslationFunctions();
	}

	/**
	 * Clean up the request.
	 */
	protected function tear_down() {
		$_POST = array();
		parent::tear_down();
	}

	/**
	 * A user who can't edit posts can't make requests to the services.
	 */
	public function test_request_is_refused_without_edit_posts(): void {
		Functions\expect( 'current_user_can' )->once()->with( 'edit_posts' )->andReturn( false );
		Functions\expect( 'wp_send_json_error' )->once()->andThrow( new \RuntimeException( 'refused' ) );
		$this->service->shouldNotReceive( 'request' );

		$this->expectException( \RuntimeException::class );
		$this->expectExceptionMessage( 'refused' );

		$this->mexp->ajax_request();
	}

	/**
	 * A user who can edit posts gets the service's response.
	 */
	public function test_request_is_passed_to_service_with_edit_posts(): void {
		Functions\when( 'current_user_can' )->justReturn( true );
		$this->service->expects( 'request' )->once()->andReturn( false );
		Functions\expect( 'wp_send_json_success' )->once()->with( false );

		$this->mexp->ajax_request();
	}
}
