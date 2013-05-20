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

abstract class Service {

	public $template = null;

	abstract function request( array $request );

	abstract function labels();

	public function requires() {
		return array();
	}

	final public function get_labels() {

		return array_merge( array(
			'title'     => __( 'Insert Media', 'emm' ),
			'insert'    => __( 'Insert', 'emm' ),
			'noresults' => __( 'Nothing matched your search query', 'emm' ),
		), (array) $this->labels() );

	}

	final public function set_template( \EMM\Template $template ) {

		$this->template = $template;

	}

	final public function get_template() {

		return $this->template;

	}

}

