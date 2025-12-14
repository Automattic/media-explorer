<?php
/**
 * Unit tests for the MEXP_Response class.
 *
 * @package MediaExplorer\Tests\Unit
 */

declare( strict_types=1 );

namespace MediaExplorer\Tests\Unit;

use Brain\Monkey\Functions;
use MEXP_Response;
use MEXP_Response_Item;

/**
 * Test case for MEXP_Response.
 */
class ResponseTest extends TestCase {

	/**
	 * Test that add_meta with key/value pair works.
	 */
	public function test_add_meta_with_key_value(): void {
		$response = new MEXP_Response();
		$response->add_meta( 'test_key', 'test_value' );

		$this->assertSame( 'test_value', $response->meta['test_key'] );
	}

	/**
	 * Test that add_meta with array works.
	 */
	public function test_add_meta_with_array(): void {
		$response = new MEXP_Response();
		$response->add_meta( array(
			'key1' => 'value1',
			'key2' => 'value2',
		) );

		$this->assertSame( 'value1', $response->meta['key1'] );
		$this->assertSame( 'value2', $response->meta['key2'] );
	}

	/**
	 * Test that output returns false when no items.
	 */
	public function test_output_returns_false_when_empty(): void {
		$response = new MEXP_Response();

		$this->assertFalse( $response->output() );
	}

	/**
	 * Test that output returns correct structure with items.
	 */
	public function test_output_with_items(): void {
		Functions\expect( 'esc_url_raw' )
			->andReturnFirstArg();

		Functions\expect( 'get_option' )
			->with( 'date_format' )
			->andReturn( 'Y-m-d' );

		$response = new MEXP_Response();

		$item = new MEXP_Response_Item();
		$item->set_id( 123 );
		$item->set_url( 'https://example.com/image.jpg' );
		$item->set_date( strtotime( '2024-01-15' ) );

		$response->add_item( $item );

		$output = $response->output();

		$this->assertIsArray( $output );
		$this->assertArrayHasKey( 'meta', $output );
		$this->assertArrayHasKey( 'items', $output );
		$this->assertSame( 1, $output['meta']['count'] );
		$this->assertCount( 1, $output['items'] );
		$this->assertSame( 123, $output['items'][0]['id'] );
	}

	/**
	 * Test that count meta is auto-calculated.
	 */
	public function test_count_is_auto_calculated(): void {
		Functions\stubs( [ 'esc_url_raw' => null ] );

		Functions\stubs( [ 'get_option' => 'Y-m-d' ] );

		$response = new MEXP_Response();

		for ( $i = 1; $i <= 3; $i++ ) {
			$item = new MEXP_Response_Item();
			$item->set_id( $i );
			$item->set_date( time() );
			$response->add_item( $item );
		}

		$output = $response->output();

		$this->assertSame( 3, $output['meta']['count'] );
	}

	/**
	 * Test that min_id is auto-calculated from first item.
	 */
	public function test_min_id_is_auto_calculated(): void {
		Functions\stubs( [ 'esc_url_raw' => null ] );

		Functions\stubs( [ 'get_option' => 'Y-m-d' ] );

		$response = new MEXP_Response();

		$item1 = new MEXP_Response_Item();
		$item1->set_id( 100 );
		$item1->set_date( time() );

		$item2 = new MEXP_Response_Item();
		$item2->set_id( 200 );
		$item2->set_date( time() );

		$response->add_item( $item1 );
		$response->add_item( $item2 );

		$output = $response->output();

		$this->assertSame( 100, $output['meta']['min_id'] );
	}
}
