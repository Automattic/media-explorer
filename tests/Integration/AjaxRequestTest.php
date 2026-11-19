<?php
/**
 * Integration tests for the media search AJAX endpoint.
 *
 * @package MediaExplorer\Tests\Integration
 */

declare( strict_types=1 );

namespace MediaExplorer\Tests\Integration;

use Media_Explorer;
use MEXP_Response;
use MEXP_Response_Item;
use MEXP_Service;
use WP_Ajax_UnitTestCase;
use WP_Error;
use WPAjaxDieContinueException;
use WPAjaxDieStopException;

/**
 * Tests for Media_Explorer::ajax_request(), using a stub service so no external API is called.
 *
 * @coversDefaultClass \Media_Explorer
 * @covers ::ajax_request
 */
final class AjaxRequestTest extends WP_Ajax_UnitTestCase {

	/**
	 * The plugin instance.
	 *
	 * @var Media_Explorer
	 */
	private $plugin;

	/**
	 * The services registered when the test started, restored afterwards.
	 *
	 * @var array<string, MEXP_Service>
	 */
	private $services;

	/**
	 * Stub service that records the request it receives and returns a preset response.
	 *
	 * @var MEXP_Service
	 */
	private $stub;

	protected function setUp(): void {
		parent::setUp();

		$this->stub = new class() extends MEXP_Service {
			/**
			 * What request() returns.
			 *
			 * @var mixed
			 */
			public $response = false;

			/**
			 * The request passed to request(), or null if it wasn't called.
			 *
			 * @var array|null
			 */
			public $received = null;

			public function request( array $request ) {
				$this->received = $request;
				return $this->response;
			}

			public function labels( array $labels ) {
				return $labels;
			}

			public function tabs( array $tabs ) {
				return $tabs;
			}
		};

		$this->plugin                   = Media_Explorer::init();
		$this->services                 = $this->plugin->services;
		$this->plugin->services['stub'] = $this->stub;

		wp_set_current_user( self::factory()->user->create( array( 'role' => 'editor' ) ) );
	}

	protected function tearDown(): void {
		$this->plugin->services = $this->services;

		parent::tearDown();
	}

	public function test_refuses_a_request_without_a_valid_nonce(): void {
		$this->assertSame( '-1', $this->dispatch_expecting_die( array( '_nonce' => 'invalid' ) ) );
		$this->assertNull( $this->stub->received );
	}

	public function test_refuses_a_nonce_created_for_another_user(): void {
		$nonce = wp_create_nonce( 'mexp_request' );
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'editor' ) ) );

		$this->assertSame( '-1', $this->dispatch_expecting_die( array( '_nonce' => $nonce ) ) );
		$this->assertNull( $this->stub->received );
	}

	public function test_returns_an_error_for_an_unknown_service(): void {
		$errors = array();
		add_action(
			'mexp_ajax_request_error',
			static function ( $error ) use ( &$errors ): void {
				$errors[] = $error;
			}
		);

		$response = $this->dispatch( array( 'service' => 'nope' ) );

		$this->assertFalse( $response['success'] );
		$this->assertSame( 'invalid_service', $response['data']['error_code'] );
		$this->assertCount( 1, $errors );
		$this->assertSame( 'invalid_service', $errors[0]->get_error_code() );
	}

	public function test_passes_a_normalized_request_to_the_service(): void {
		$this->dispatch(
			array(
				'tab'    => 'all',
				'page'   => '2',
				'params' => array( 'q' => "it\\'s" ),
			)
		);

		$this->assertSame( 'all', $this->stub->received['tab'] );
		$this->assertSame( 2, $this->stub->received['page'] );
		$this->assertSame( get_current_user_id(), $this->stub->received['user_id'] );
		$this->assertSame( array( 'q' => "it's" ), $this->stub->received['params'] );
		$this->assertNull( $this->stub->received['min_id'] );
		$this->assertNull( $this->stub->received['max_id'] );
	}

	public function test_request_args_filter_can_change_the_request(): void {
		$filtered_service = null;
		add_filter(
			'mexp_ajax_request_args',
			static function ( array $request, $service ) use ( &$filtered_service ): array {
				$filtered_service = $service;
				$request['tab']   = 'filtered';
				return $request;
			},
			10,
			2
		);

		$this->dispatch( array( 'tab' => 'all' ) );

		$this->assertSame( $this->stub, $filtered_service );
		$this->assertSame( 'filtered', $this->stub->received['tab'] );
	}

	public function test_returns_the_service_response_as_json(): void {
		$item = new MEXP_Response_Item();
		$item->set_id( 7 );
		$item->set_url( 'https://www.youtube.com/watch?v=abc' );
		$item->set_content( 'A video' );
		$this->stub->response = new MEXP_Response();
		$this->stub->response->add_item( $item );

		$successes = array();
		add_action(
			'mexp_ajax_request_success',
			static function ( $response ) use ( &$successes ): void {
				$successes[] = $response;
			}
		);

		$response = $this->dispatch();

		$this->assertTrue( $response['success'] );
		$this->assertSame( 'https://www.youtube.com/watch?v=abc', $response['data']['items'][0]['url'] );
		$this->assertSame( 'A video', $response['data']['items'][0]['content'] );
		$this->assertSame( 1, $response['data']['meta']['count'] );
		$this->assertSame( array( $this->stub->response ), $successes );
	}

	public function test_returns_a_service_error_as_json(): void {
		$this->stub->response = new WP_Error( 'mexp_test_failed', 'The service failed.' );

		$response = $this->dispatch();

		$this->assertFalse( $response['success'] );
		$this->assertSame(
			array(
				'error_code'    => 'mexp_test_failed',
				'error_message' => 'The service failed.',
			),
			$response['data']
		);
	}

	public function test_returns_success_with_no_data_when_the_service_has_no_results(): void {
		$response = $this->dispatch();

		$this->assertSame(
			array(
				'success' => true,
				'data'    => false,
			),
			$response
		);
	}

	/**
	 * Sends a request to the endpoint, with a valid nonce for the stub service unless overridden.
	 *
	 * @param array $post Request parameters.
	 * @return array The decoded JSON response.
	 */
	private function dispatch( array $post = array() ): array {
		$this->set_request( $post );

		try {
			$this->_handleAjax( 'mexp_request' );
		} catch ( WPAjaxDieContinueException $e ) {
			// wp_send_json() ends the request this way.
			unset( $e );
		}

		return json_decode( $this->_last_response, true );
	}

	/**
	 * Sends a request that should be stopped before it produces any JSON.
	 *
	 * @param array $post Request parameters.
	 * @return string|null The message the request died with, or null if it didn't die.
	 */
	private function dispatch_expecting_die( array $post ): ?string {
		$this->set_request( $post );
		$message = null;

		try {
			$this->_handleAjax( 'mexp_request' );
		} catch ( WPAjaxDieStopException $e ) {
			$message = $e->getMessage();
		}

		return $message;
	}

	/**
	 * Populates the request superglobals.
	 *
	 * @param array $post Request parameters.
	 */
	private function set_request( array $post ): void {
		$_POST    = array_merge(
			array(
				'_nonce'  => wp_create_nonce( 'mexp_request' ),
				'service' => 'stub',
			),
			$post
		);
		$_REQUEST = $_POST;
	}
}
