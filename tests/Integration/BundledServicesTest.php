<?php
/**
 * Integration tests for the bundled services.
 *
 * @package MediaExplorer\Tests\Integration
 */

declare( strict_types=1 );

namespace MediaExplorer\Tests\Integration;

use MEXP_Instagram_Service;
use MEXP_Response;
use MEXP_Twitter_Service;
use MEXP_YouTube_Service;
use WP_Error;
use Yoast\WPTestUtils\WPIntegration\TestCase;

/**
 * Tests for the bundled services. HTTP requests are intercepted, so nothing reaches the real APIs.
 */
final class BundledServicesTest extends TestCase {

	/**
	 * URLs of the HTTP requests made during the test.
	 *
	 * @var string[]
	 */
	private $requested_urls = array();

	/**
	 * Environment variables the services read credentials from.
	 */
	private const ENV_VARS = array(
		'MEXP_TWITTER_CONSUMER_KEY',
		'MEXP_TWITTER_CONSUMER_SECRET',
		'MEXP_TWITTER_OAUTH_TOKEN',
		'MEXP_TWITTER_OAUTH_TOKEN_SECRET',
		'MEXP_YOUTUBE_DEVELOPER_KEY',
		'MEXP_INSTAGRAM_ACCESS_TOKEN',
	);

	protected function setUp(): void {
		parent::setUp();

		$this->requested_urls = array();
	}

	protected function tearDown(): void {
		foreach ( self::ENV_VARS as $name ) {
			putenv( $name );
		}

		parent::tearDown();
	}

	/**
	 * @covers \MEXP_YouTube_Service::request
	 */
	public function test_youtube_returns_an_error_without_an_api_key(): void {
		$this->fail_any_http_request();

		$result = ( new MEXP_YouTube_Service() )->request( $this->youtube_search( 'cats' ) );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'mexp_youtube_no_connection', $result->get_error_code() );
	}

	/**
	 * @covers \MEXP_Twitter_Service::request
	 */
	public function test_twitter_returns_an_error_without_credentials(): void {
		$this->fail_any_http_request();

		$result = ( new MEXP_Twitter_Service() )->request(
			array(
				'params' => array( 'q' => 'cats' ),
				'tab'    => 'all',
			)
		);

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'mexp_twitter_no_connection', $result->get_error_code() );
	}

	/**
	 * @covers \MEXP_Instagram_Service::request
	 */
	public function test_instagram_returns_an_error_without_credentials(): void {
		$this->fail_any_http_request();

		$result = ( new MEXP_Instagram_Service() )->request(
			array(
				'params' => array(),
				'tab'    => 'popular',
			)
		);

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'mexp_instagram_no_connection', $result->get_error_code() );
	}

	/**
	 * @covers \MEXP_YouTube_Service::request
	 */
	public function test_youtube_search_returns_videos_from_the_api(): void {
		add_filter(
			'mexp_youtube_developer_key',
			static function (): string {
				return 'test-key';
			}
		);
		$this->respond_to_http_requests_with(
			'/search?',
			array(
				'nextPageToken' => 'NEXT',
				'items'         => array(
					array(
						'id'      => array( 'videoId' => 'abc123' ),
						'snippet' => array(
							'channelTitle' => 'Cat Channel',
							'title'        => 'Cats being cats',
							'publishedAt'  => '2026-01-02T03:04:05Z',
							'thumbnails'   => array(
								'medium' => array( 'url' => 'https://i.ytimg.com/vi/abc123/mqdefault.jpg' ),
							),
						),
					),
				),
			)
		);

		$result = ( new MEXP_YouTube_Service() )->request( $this->youtube_search( 'cats' ) );

		$this->assertCount( 1, $this->requested_urls );
		$this->assertStringStartsWith( 'https://www.googleapis.com/youtube/v3/search?', $this->requested_urls[0] );
		$this->assertStringContainsString( 'q=cats', $this->requested_urls[0] );
		$this->assertStringContainsString( 'key=test-key', $this->requested_urls[0] );

		$this->assertInstanceOf( MEXP_Response::class, $result );
		$output = $result->output();
		$this->assertSame( 'NEXT', $output['meta']['page_token'] );
		$this->assertCount( 1, $output['items'] );
		$this->assertSame( 'https://www.youtube.com/watch?v=abc123', $output['items'][0]['url'] );
		$this->assertSame( 'Cats being cats', $output['items'][0]['content'] );
		$this->assertSame( 'https://i.ytimg.com/vi/abc123/mqdefault.jpg', $output['items'][0]['thumbnail'] );
		$this->assertSame( 'Cat Channel', $output['items'][0]['meta']['user'] );
	}

	/**
	 * @covers \MEXP_YouTube_Service::request
	 */
	public function test_youtube_by_user_returns_the_channels_uploads(): void {
		add_filter(
			'mexp_youtube_developer_key',
			static function (): string {
				return 'test-key';
			}
		);
		$this->respond_to_http_requests_with(
			'/channels?',
			array(
				'pageInfo' => array( 'totalResults' => 1 ),
				'items'    => array(
					array( 'contentDetails' => array( 'relatedPlaylists' => array( 'uploads' => 'UPLOADS123' ) ) ),
				),
			)
		);
		$this->respond_to_http_requests_with(
			'/playlistItems?',
			array(
				'items' => array(
					array(
						'snippet' => array(
							'channelTitle' => 'Cat Channel',
							'title'        => 'Latest upload',
							'publishedAt'  => '2026-01-02T03:04:05Z',
							'resourceId'   => array( 'videoId' => 'up1' ),
							'thumbnails'   => array(
								'medium' => array( 'url' => 'https://i.ytimg.com/vi/up1/mqdefault.jpg' ),
							),
						),
					),
				),
			)
		);

		$result = ( new MEXP_YouTube_Service() )->request(
			array(
				'tab'    => 'by_user',
				'params' => array(
					'tab'        => 'by_user',
					'channel'    => 'catchannel',
					'page_token' => '',
					'startIndex' => '0',
				),
			)
		);

		$this->assertCount( 2, $this->requested_urls );
		$this->assertStringContainsString( '/channels?forUsername=catchannel&', $this->requested_urls[0] );
		$this->assertStringContainsString( '/playlistItems?', $this->requested_urls[1] );
		$this->assertStringContainsString( 'playlistId=UPLOADS123', $this->requested_urls[1] );

		$this->assertInstanceOf( MEXP_Response::class, $result );
		$output = $result->output();
		$this->assertSame( 'https://www.youtube.com/watch?v=up1', $output['items'][0]['url'] );
		$this->assertSame( 'Latest upload', $output['items'][0]['content'] );
	}

	/**
	 * @covers \MEXP_YouTube_Service::request
	 * @covers \MEXP_Service::get_env_var
	 */
	public function test_youtube_reads_the_api_key_from_an_environment_variable(): void {
		putenv( 'MEXP_YOUTUBE_DEVELOPER_KEY=env-key' );
		$this->respond_to_http_requests_with( '/search?', array( 'items' => array() ) );

		( new MEXP_YouTube_Service() )->request( $this->youtube_search( 'cats' ) );

		$this->assertCount( 1, $this->requested_urls );
		$this->assertStringContainsString( 'key=env-key', $this->requested_urls[0] );
	}

	/**
	 * @covers \MEXP_Twitter_Service::request
	 * @covers \MEXP_Service::get_env_var
	 */
	public function test_twitter_reads_credentials_from_environment_variables(): void {
		putenv( 'MEXP_TWITTER_CONSUMER_KEY=key' );
		putenv( 'MEXP_TWITTER_CONSUMER_SECRET=secret' );
		putenv( 'MEXP_TWITTER_OAUTH_TOKEN=token' );
		putenv( 'MEXP_TWITTER_OAUTH_TOKEN_SECRET=token-secret' );
		// The AJAX handler loads this through MEXP_Twitter_Service::requires().
		require_once dirname( __DIR__, 2 ) . '/class.oauth.php';
		$this->respond_to_http_requests_with( '/search/tweets.json', array( 'statuses' => array() ) );

		$result = ( new MEXP_Twitter_Service() )->request(
			array(
				'params' => array( 'q' => 'cats' ),
				'tab'    => 'all',
			)
		);

		$this->assertNotInstanceOf( WP_Error::class, $result );
		$this->assertCount( 1, $this->requested_urls );
	}

	/**
	 * @covers \MEXP_Instagram_Service::request
	 * @covers \MEXP_Service::get_env_var
	 */
	public function test_instagram_reads_the_access_token_from_an_environment_variable(): void {
		putenv( 'MEXP_INSTAGRAM_ACCESS_TOKEN=env-token' );
		$this->respond_to_http_requests_with( 'api.instagram.com', array( 'data' => array() ) );

		( new MEXP_Instagram_Service() )->request(
			array(
				'params' => array(),
				'tab'    => 'popular',
			)
		);

		$this->assertCount( 1, $this->requested_urls );
		$this->assertStringContainsString( 'access_token=env-token', $this->requested_urls[0] );
	}

	/**
	 * Builds a YouTube search request as the media manager sends it.
	 *
	 * @param string $query Search terms.
	 * @return array
	 */
	private function youtube_search( string $query ): array {
		return array(
			'tab'    => 'all',
			'params' => array(
				'tab'        => 'all',
				'q'          => $query,
				'type'       => 'video',
				'page_token' => '',
				'startIndex' => '0',
			),
		);
	}

	/**
	 * Fails the test if any HTTP request is made.
	 */
	private function fail_any_http_request(): void {
		add_filter(
			'pre_http_request',
			function ( $preempt, $args, $url ) {
				$this->fail( 'Unexpected HTTP request to ' . $url );
			},
			10,
			3
		);
	}

	/**
	 * Answers HTTP requests whose URL contains the given fragment with a 200 response
	 * containing the given data as JSON.
	 *
	 * @param string $url_fragment Part of the URL to match.
	 * @param array  $body         Response data.
	 */
	private function respond_to_http_requests_with( string $url_fragment, array $body ): void {
		add_filter(
			'pre_http_request',
			function ( $preempt, $args, $url ) use ( $url_fragment, $body ) {
				if ( false === strpos( $url, $url_fragment ) ) {
					return $preempt;
				}

				$this->requested_urls[] = $url;

				return array(
					'headers'  => array(),
					'body'     => wp_json_encode( $body ),
					'response' => array(
						'code'    => 200,
						'message' => 'OK',
					),
					'cookies'  => array(),
					'filename' => null,
				);
			},
			10,
			3
		);
	}
}
