<?php
/**
 * Unit tests for the YouTube API client.
 *
 * @package MediaExplorer\Tests\Unit
 */

declare( strict_types=1 );

namespace MediaExplorer\Tests\Unit;

use Brain\Monkey\Functions;
use Mockery;

/**
 * Test case for MEXP_YouTube_Client.
 */
class YouTubeClientTest extends TestCase {

	/**
	 * Load the classes under test.
	 */
	protected function set_up() {
		parent::set_up();

		$root = dirname( __DIR__, 2 );
		require_once $root . '/class.service.php';
		require_once $root . '/class.template.php';
		require_once $root . '/services/youtube/service.php';
		require_once $root . '/services/youtube/class.wp-youtube-client.php';
	}

	/**
	 * A search page token can't add parameters to the YouTube API request.
	 */
	public function test_youtube_search_page_token_is_encoded(): void {
		Functions\expect( 'wp_remote_get' )
			->once()
			->with(
				Mockery::on(
					static function ( $url ) {
						return false !== strpos( $url, 'pageToken=abc%26part%3Devil&' )
							&& false === strpos( $url, '&part=evil' );
					}
				)
			)
			->andReturn( array() );

		$client = new \MEXP_YouTube_Client( 'key' );
		$client->get_videos(
			array(
				'q'          => 'cats',
				'page_token' => 'abc&part=evil',
			)
		);
	}

	/**
	 * A channel page token can't add parameters to the YouTube API request.
	 */
	public function test_youtube_channel_page_token_is_encoded(): void {
		$channel = array(
			'response' => array( 'code' => 200 ),
			'body'     => json_encode(
				array(
					'pageInfo' => array( 'totalResults' => 1 ),
					'items'    => array( array( 'contentDetails' => array( 'relatedPlaylists' => array( 'uploads' => 'UU1&x=y' ) ) ) ),
				)
			),
		);

		Functions\expect( 'wp_remote_get' )
			->twice()
			->andReturnUsing(
				static function ( $url ) use ( $channel ) {
					if ( false !== strpos( $url, '/channels?' ) ) {
						return $channel;
					}

					if ( false === strpos( $url, 'playlistId=UU1%26x%3Dy&' ) || false === strpos( $url, 'pageToken=abc%26part%3Devil' ) ) {
						throw new \UnexpectedValueException( 'Unencoded playlist request: ' . $url );
					}

					return array();
				}
			);

		$client = new \MEXP_YouTube_Client( 'key' );
		$client->get_videos_from_channel(
			array(
				'channel'    => 'someone',
				'page_token' => 'abc&part=evil',
			)
		);
	}
}
