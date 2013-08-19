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
 * Abstract template class. Every service template should implement this class.
 */
abstract class MEXP_Template {

	/**
	 * Outputs the Backbone template for an item within search results.
	 *
	 * @param string $id  The template ID.
	 * @param string $tab The tab ID.
	 * @return null
	 */
	abstract public function item( $id, $tab );

	/**
	 * Outputs the Backbone template for a select item's thumbnail in the footer toolbar.
	 *
	 * @param string $id The template ID.
	 * @return null
	 */
	abstract public function thumbnail( $id );

	/**
	 * Outputs the Backbone template for a tab's search fields.
	 *
	 * @param string $id  The template ID.
	 * @param string $tab The tab ID.
	 * @return null
	 */
	abstract public function search( $id, $tab );

	/**
	 * Outputs the markup needed before a template.
	 *
	 * @param string $id  The template ID.
	 * @param string $tab The tab ID (optional).
	 * @return null
	 */
	final public function before_template( $id, $tab = null ) {
		?>
		<script type="text/html" id="tmpl-<?php echo esc_attr( $id ); ?>">
		<?php
	}

	/**
	 * Outputs the markup needed after a template.
	 *
	 * @param string $id  The template ID.
	 * @param string $tab The tab ID (optional).
	 * @return null
	 */
	final public function after_template( $id, $tab = null ) {
		?>
		</script>
		<?php
	}

}
