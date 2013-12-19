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
 * Abstract service class. Every service should implement this class.
 */
abstract class MEXP_Service {

	public $template = null;

	/**
	 * Handles the AJAX request and returns an appropriate response. This should be used, for
	 * example, to perform an API request to the service provider and return the results.
	 *
	 * @param array $request The request parameters.
	 * @return Response|false|WP_Error A Response object should be returned on success, boolean false should be returned if there are no results to show, and a WP_Error should be returned if there is an error.
	 */
	abstract function request( array $request );

	/**
	 * Returns an array of custom text labels for this service. See the get_labels() method for default labels.
	 *
	 * @return array Associative array of labels.
	 */
	abstract function labels( array $labels );

	/**
	 * Returns an array of tabs (routers) for the service's media manager panel.
	 *
	 * @return array Associative array of tabs. The key is the tab ID and the value is an array of tab attributes.
	 */
	abstract function tabs( array $tabs );

	/**
	 * A *very* simple dependency system that allows a plugin to return an array of classes and filename that it requires. Currently 
	 * only the OAuth class is available as a dependency.
	 *
	 * @return array Associative array of required classes. The array key is the portion of the filename and the value is the class name.
	 */
	public function requires() {
		return array();
	}

	/**
	 * Fired when the service is loaded. Allows the service to enqueue JS/CSS only when it's required. Akin to WordPress' load action.
	 *
	 * @return null
	 */
	public function load() {
	}

	/**
	 * Sets the template object for this service. Should be called by the child class on initialisation.
	 *
	 * @return null
	 */
	final public function set_template( MEXP_Template $template ) {

		$this->template = $template;

	}

	/**
	 * Returns the template object for this service.
	 *
	 * @return Template|null A Template object, or null if a template isn't set.
	 */
	final public function get_template() {

		return $this->template;

	}

}

