<?php
/*
Plugin Name: Media Explorer
Description: Extends the Media Manager to add support for external media services (currently Twitter, YouTube, and Instagram).
Version:     1.2
Author:      Code For The People Ltd, Automattic
Text Domain: mexp
Domain Path: /languages/
License:     GPL v2 or later

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

*/

defined( 'ABSPATH' ) or die();

foreach ( array( 'plugin', 'mexp', 'service', 'template', 'response' ) as $class )
	require_once sprintf( '%s/class.%s.php', dirname( __FILE__ ), $class );

foreach ( glob( dirname( __FILE__ ) . '/services/*/service.php' ) as $service )
	include $service;

Media_Explorer::init( __FILE__ );
