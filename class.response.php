<?php
/*
This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

*/

/**
 * Response class. A service's request() method should return a Response object. This
 * is used to populate the AJAX request's JSON response.
 */
final class MEXP_Response {

	public $items = array();
	public $meta  = array(
		'count'  => null,
		'max_id' => null,
		'min_id' => null,
	);

	/**
	 * Add a meta value to the response. Accepts a key/value pair or an associative array.
	 *
	 * @param string|array $key The meta key, or an associative array of meta keys/values.
	 * @param mixed $value The meta value.
	 * @return null
	 */
	public function add_meta( $key, $value = null ) {

		if ( is_array( $key ) ) {

			foreach ( $key as $k => $v )
				$this->meta[$k] = $v;

		} else {

			$this->meta[$key] = $value;

		}

	}

	/**
	 * Add a response item to the response.
	 *
	 * @param Response_Item A response item.
	 * @return null
	 */
	public function add_item( MEXP_Response_Item $item ) {
		$this->items[] = $item;
	}

	/**
	 * Retrieve the response output.
	 *
	 * @return array|bool The response output, or boolean false if there's nothing to output.
	 */
	public function output() {

		if ( empty( $this->items ) )
			return false;

		if ( is_null( $this->meta['count'] ) )
			$this->meta['count'] = count( $this->items );
		if ( is_null( $this->meta['min_id'] ) )
			$this->meta['min_id'] = reset( $this->items )->id;

		$output = array(
			'meta'  => $this->meta,
			'items' => array()
		);

		foreach ( $this->items as $item )
			$output['items'][] = $item->output();

		return $output;

	}

}

/**
 * Response Item class. Used within the Response class to populate the items in a response.
 */
final class MEXP_Response_Item {

	public $id          = null;
	public $url         = null;
	public $thumbnail   = null;
	public $content     = null;
	public $date        = null;
	public $date_format = null;
	public $meta        = array();

	/**
	 * Set the ID for the response item.
	 *
	 * @param int $id The response item ID.
	 * @return null
	 */
	public function set_id( $id ) {
		$this->id = $id;
	}

	/**
	 * Set the URL for the response item.
	 *
	 * @param string $url The response item URL.
	 * @return null
	 */
	public function set_url( $url ) {
		$this->url = esc_url_raw( $url );
	}

	/**
	 * Set the thumbnail URL for the response item.
	 *
	 * @param string $thumbnail The response item thumbnail URL.
	 * @return null
	 */
	public function set_thumbnail( $thumbnail ) {
		$this->thumbnail = esc_url_raw( $thumbnail );
	}

	/**
	 * Set the content for the response item.
	 *
	 * @param string $content The response item content.
	 * @return null
	 */
	public function set_content( $content ) {
		$this->content = $content;
	}

	/**
	 * Set the date for the response item.
	 *
	 * @param int $date The response item date in UNIX timestamp format.
	 * @return null
	 */
	public function set_date( $date ) {
		$this->date = $date;
	}

	/**
	 * Set the date format for the response item date.
	 *
	 * @param string $date_format The date format in PHP date() format.
	 * @return null
	 */
	public function set_date_format( $date_format ) {
		$this->date_format = $date_format;
	}

	/**
	 * Add a meta value to the response item. Accepts a key/value pair or an associative array.
	 *
	 * @param string|array $key The meta key, or an associative array of meta keys/values.
	 * @param mixed $value The meta value.
	 * @return null
	 */
	public function add_meta( $key, $value = null ) {

		if ( is_array( $key ) ) {

			foreach ( $key as $k => $v )
				$this->meta[$k] = $v;

		} else {

			$this->meta[$key] = $value;

		}

	}

	/**
	 * Retrieve the response item output.
	 *
	 * @return array The response item output.
	 */
	public function output() {

		if ( is_null( $this->date_format ) )
			$this->date_format = get_option( 'date_format' );

		return array(
			'id'        => $this->id,
			'url'       => $this->url,
			'thumbnail' => $this->thumbnail,
			'content'   => $this->content,
			'date'      => date( $this->date_format, $this->date ),
			'meta'      => $this->meta,
		);

	}

}
