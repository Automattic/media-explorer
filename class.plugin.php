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
 * Abstract plugin class. The plugin's main class should extend this to make use of its handy methods.
 */
abstract class MEXP_Plugin {

	/**
	 * Class constructor
	 *
	 * @param string $file The plugin's file path.
	 * @author John Blackbourn
	 */
	protected function __construct( $file ) {
		$this->file = $file;
	}

	/**
	 * Returns the URL for for a file/dir within this plugin.
	 *
	 * @param string $path The path within this plugin, e.g. '/js/clever-fx.js'
	 * @return string URL
	 * @author John Blackbourn
	 */
	public function plugin_url( $file = '' ) {
		return $this->_plugin( 'url', $file );
	}

	/**
	 * Returns the filesystem path for a file/dir within this plugin.
	 *
	 * @param string $path The path within this plugin, e.g. '/js/clever-fx.js'
	 * @return string Filesystem path
	 * @author John Blackbourn
	 */
	public function plugin_path( $file = '' ) {
		return $this->_plugin( 'path', $file );
	}

	/**
	 * Returns a version number for the given plugin file.
	 *
	 * @param string $path The path within this plugin, e.g. '/js/clever-fx.js'
	 * @return string Version
	 * @author John Blackbourn
	 */
	public function plugin_ver( $file ) {
		return filemtime( $this->plugin_path( $file ) );
	}

	/**
	 * Returns the current plugin's basename, eg. 'my_plugin/my_plugin.php'.
	 *
	 * @return string Basename
	 * @author John Blackbourn
	 */
	public function plugin_base() {
		return $this->_plugin( 'base' );
	}

	/**
	 * Populates the current plugin info if necessary, and returns the requested item.
	 *
	 * @param string $item The name of the requested item. One of 'url', 'path', or 'base'.
	 * @param string $file The file name to append to the returned value (optional).
	 * @return string The value of the requested item.
	 * @author John Blackbourn
	 */
	protected function _plugin( $item, $file = '' ) {
		if ( !isset( $this->plugin ) ) {
			$this->plugin = array(
				'url'  => plugin_dir_url( $this->file ),
				'path' => plugin_dir_path( $this->file ),
				'base' => plugin_basename( $this->file )
			);
		}
		return $this->plugin[$item] . ltrim( $file, '/' );
	}

}
