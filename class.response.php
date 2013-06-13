<?php
/*
Copyright © 2013 Code for the People Ltd

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

*/

namespace EMM;

final class Response {

	public $items = array();
	public $meta  = array(
		'count'  => null,
		'max_id' => null,
		'min_id' => null,
	);

	public function add_meta( $key, $value = null ) {

		if ( is_array( $key ) ) {

			foreach ( $key as $k => $v )
				$this->meta[$k] = $v;

		} else {

			$this->meta[$key] = $value;

		}

	}

	public function add_item( Response_Item $item ) {
		$this->items[] = $item;
	}

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

final class Response_Item {

	public $id          = null;
	public $url         = null;
	public $thumbnail   = null;
	public $content     = null;
	public $date        = null;
	public $date_format = null;
	public $meta        = array();

	public function set_id( $id ) {
		$this->id = $id;
	}

	public function set_url( $url ) {
		$this->url = esc_url_raw( $url );
	}

	public function set_thumbnail( $thumbnail ) {
		$this->thumbnail = esc_url_raw( $thumbnail );
	}

	public function set_content( $content ) {
		$this->content = $content;
	}

	public function set_date( $date ) {
		$this->date = $date;
	}

	public function set_date_format( $date_format ) {
		$this->date_format = $date_format;
	}

	public function add_meta( $key, $value = null ) {

		if ( is_array( $key ) ) {

			foreach ( $key as $k => $v )
				$this->meta[$k] = $v;

		} else {

			$this->meta[$key] = $value;

		}

	}

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
