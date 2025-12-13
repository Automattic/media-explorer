<?php
/**
 * Unit tests for the MEXP_Response_Item class.
 *
 * @package MediaExplorer\Tests\Unit
 */

declare( strict_types=1 );

namespace MediaExplorer\Tests\Unit;

use Brain\Monkey\Functions;
use MEXP_Response_Item;

/**
 * Test case for MEXP_Response_Item.
 */
class ResponseItemTest extends TestCase {

	/**
	 * Test set_id.
	 */
	public function test_set_id(): void {
		$item = new MEXP_Response_Item();
		$item->set_id( 42 );

		$this->assertSame( 42, $item->id );
	}

	/**
	 * Test set_url sanitizes with esc_url_raw.
	 */
	public function test_set_url_sanitizes(): void {
		Functions\expect( 'esc_url_raw' )
			->once()
			->with( 'https://example.com/test' )
			->andReturn( 'https://example.com/test' );

		$item = new MEXP_Response_Item();
		$item->set_url( 'https://example.com/test' );

		$this->assertSame( 'https://example.com/test', $item->url );
	}

	/**
	 * Test set_thumbnail sanitizes with esc_url_raw.
	 */
	public function test_set_thumbnail_sanitizes(): void {
		Functions\expect( 'esc_url_raw' )
			->once()
			->with( 'https://example.com/thumb.jpg' )
			->andReturn( 'https://example.com/thumb.jpg' );

		$item = new MEXP_Response_Item();
		$item->set_thumbnail( 'https://example.com/thumb.jpg' );

		$this->assertSame( 'https://example.com/thumb.jpg', $item->thumbnail );
	}

	/**
	 * Test set_content.
	 */
	public function test_set_content(): void {
		$item = new MEXP_Response_Item();
		$item->set_content( 'Test content' );

		$this->assertSame( 'Test content', $item->content );
	}

	/**
	 * Test set_date.
	 */
	public function test_set_date(): void {
		$timestamp = strtotime( '2024-06-15 12:00:00' );
		$item      = new MEXP_Response_Item();
		$item->set_date( $timestamp );

		$this->assertSame( $timestamp, $item->date );
	}

	/**
	 * Test set_date_format.
	 */
	public function test_set_date_format(): void {
		$item = new MEXP_Response_Item();
		$item->set_date_format( 'Y-m-d H:i:s' );

		$this->assertSame( 'Y-m-d H:i:s', $item->date_format );
	}

	/**
	 * Test add_meta with key/value.
	 */
	public function test_add_meta_with_key_value(): void {
		$item = new MEXP_Response_Item();
		$item->add_meta( 'author', 'John Doe' );

		$this->assertSame( 'John Doe', $item->meta['author'] );
	}

	/**
	 * Test add_meta with array.
	 */
	public function test_add_meta_with_array(): void {
		$item = new MEXP_Response_Item();
		$item->add_meta( array(
			'author'   => 'John Doe',
			'platform' => 'Twitter',
		) );

		$this->assertSame( 'John Doe', $item->meta['author'] );
		$this->assertSame( 'Twitter', $item->meta['platform'] );
	}

	/**
	 * Test output returns correct structure.
	 */
	public function test_output_structure(): void {
		Functions\expect( 'esc_url_raw' )
			->andReturnFirstArg();

		Functions\expect( 'get_option' )
			->once()
			->with( 'date_format' )
			->andReturn( 'Y-m-d' );

		$timestamp = strtotime( '2024-06-15' );

		$item = new MEXP_Response_Item();
		$item->set_id( 123 );
		$item->set_url( 'https://example.com/media' );
		$item->set_thumbnail( 'https://example.com/thumb.jpg' );
		$item->set_content( 'Test content' );
		$item->set_date( $timestamp );
		$item->add_meta( 'source', 'YouTube' );

		$output = $item->output();

		$this->assertSame( 123, $output['id'] );
		$this->assertSame( 'https://example.com/media', $output['url'] );
		$this->assertSame( 'https://example.com/thumb.jpg', $output['thumbnail'] );
		$this->assertSame( 'Test content', $output['content'] );
		$this->assertSame( '2024-06-15', $output['date'] );
		$this->assertSame( array( 'source' => 'YouTube' ), $output['meta'] );
	}

	/**
	 * Test output uses custom date format.
	 */
	public function test_output_uses_custom_date_format(): void {
		Functions\stubs( [ 'esc_url_raw' => null ] );

		$timestamp = strtotime( '2024-06-15 14:30:00' );

		$item = new MEXP_Response_Item();
		$item->set_id( 1 );
		$item->set_date( $timestamp );
		$item->set_date_format( 'F j, Y' );

		$output = $item->output();

		$this->assertSame( 'June 15, 2024', $output['date'] );
	}
}
