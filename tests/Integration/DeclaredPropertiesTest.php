<?php
/**
 * Integration tests for property declarations.
 *
 * @package MediaExplorer\Tests\Integration
 */

declare( strict_types=1 );

namespace MediaExplorer\Tests\Integration;

use MEXP_Plugin;
use OAuthConsumer;
use WP_Twitter_OAuth;
use Yoast\WPTestUtils\WPIntegration\TestCase;

/**
 * PHP 8.2 deprecates creating dynamic properties, and PHPUnit turns the deprecation into a
 * failure, so these tests fail on PHP 8.2+ if a class sets a property it doesn't declare.
 */
final class DeclaredPropertiesTest extends TestCase {

	public static function setUpBeforeClass(): void {
		parent::setUpBeforeClass();

		$plugin_dir = dirname( __DIR__, 2 );
		require_once $plugin_dir . '/class.oauth.php';
		require_once $plugin_dir . '/services/twitter/class.wp-twitter-oauth.php';
	}

	/**
	 * @covers \MEXP_Plugin::__construct
	 * @covers \MEXP_Plugin::plugin_url
	 */
	public function test_plugin_base_class_declares_its_properties(): void {
		$plugin = new class( __FILE__ ) extends MEXP_Plugin {
			public function __construct( $file ) {
				parent::__construct( $file );
			}
		};

		$this->assertSame( plugin_dir_url( __FILE__ ) . 'js/a.js', $plugin->plugin_url( 'js/a.js' ) );
	}

	/**
	 * @covers \OAuthConsumer::__construct
	 */
	public function test_oauth_consumer_declares_its_properties(): void {
		$consumer = new OAuthConsumer( 'key', 'secret', 'https://example.com/callback' );

		$this->assertSame( 'https://example.com/callback', $consumer->callback_url );
	}

	/**
	 * @covers \WP_Twitter_OAuth::__construct
	 * @covers \WP_Twitter_OAuth::getHeader
	 */
	public function test_twitter_oauth_declares_its_properties(): void {
		$oauth = new WP_Twitter_OAuth( 'key', 'secret', 'token', 'token-secret' );
		$oauth->getHeader( null, 'X-Rate-Limit: 15' );

		$this->assertSame( 'key', $oauth->consumer->key );
		$this->assertSame( 'token', $oauth->token->key );
		$this->assertSame( array( 'x_rate_limit' => '15' ), $oauth->http_header );
	}
}
