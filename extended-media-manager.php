<?php
/*
Plugin Name: Extended Media Manager
Description: Extends the Media Manager to add support for external media services (currently only Twitter).
Version:     1.0
Author:      Code For The People Ltd
Author URI:  http://codeforthepeople.com/
Text Domain: emm
Domain Path: /languages/
License:     GPL v2 or later

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

/* ************** */

# @TODO remove:
add_filter( 'wp_get_attachment_url', 'set_url_scheme' );

/* ************** */

defined( 'ABSPATH' ) or die();

# @TODO we could autoload these instead
foreach ( array( 'plugin', 'emm', 'service', 'template', 'response' ) as $class )
	require_once sprintf( '%s/class.%s.php', __DIR__, $class );

foreach ( glob( __DIR__ . '/services/*/service.php' ) as $service )
	include $service;

Extended_Media_Manager::init( __FILE__ );
