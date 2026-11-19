<?php
/**
 * Unit tests for the Twitter service's remote requests.
 *
 * @package MediaExplorer\Tests\Unit
 */

declare( strict_types=1 );

namespace MediaExplorer\Tests\Unit;

use Brain\Monkey\Functions;
use Mockery;
use ReflectionClass;

/**
 * Test case for the Twitter service's remote requests.
 */
class TwitterRequestTest extends TestCase {

	/**
	 * Load the service classes under test.
	 */
	protected function set_up() {
		parent::set_up();

		if ( ! defined( 'ABSPATH' ) ) {
			define( 'ABSPATH', '/tmp/' );
		}

		$root = dirname( __DIR__, 2 );
		require_once $root . '/class.service.php';
		require_once $root . '/class.template.php';
		require_once $root . '/class.oauth.php';
		require_once $root . '/services/twitter/service.php';
		require_once $root . '/services/twitter/class.wp-twitter-oauth.php';
	}

	/**
	 * Twitter requests verify the API's TLS certificate.
	 */
	public function test_twitter_requests_verify_ssl(): void {
		$connection = ( new ReflectionClass( 'WP_Twitter_OAuth' ) )->newInstanceWithoutConstructor();

		Functions\expect( 'wp_remote_get' )
			->once()
			->with(
				'https://api.twitter.com/1.1/search/tweets.json',
				Mockery::on(
					static function ( $args ) {
						return true === $args['sslverify'];
					}
				)
			)
			->andReturn( array() );
		Functions\when( 'wp_remote_retrieve_response_code' )->justReturn( 200 );
		Functions\when( 'wp_remote_retrieve_headers' )->justReturn( array() );
		Functions\when( 'wp_remote_retrieve_body' )->justReturn( '' );

		$connection->http( 'https://api.twitter.com/1.1/search/tweets.json', 'GET' );
	}

	/**
	 * HTML in tweet text is escaped before it's linkified, because the item template outputs it unescaped.
	 */
	public function test_tweet_content_is_escaped(): void {
		// Like core's esc_html(), this doesn't double-encode existing entities.
		Functions\when( 'esc_html' )->alias(
			static function ( $text ) {
				return htmlspecialchars( $text, ENT_QUOTES, 'UTF-8', false );
			}
		);
		Functions\when( 'make_clickable' )->returnArg();

		$service = ( new ReflectionClass( 'MEXP_Twitter_Service' ) )->newInstanceWithoutConstructor();
		$status  = (object) array( 'text' => '<img src=x onerror=alert(1)> &amp; more' );

		$content = $service->status_content( $status );

		$this->assertStringNotContainsString( '<img', $content );
		$this->assertStringContainsString( '&lt;img', $content );
		$this->assertStringContainsString( '&amp; more', $content, 'Entities from the API should not be double-encoded.' );
	}
}
