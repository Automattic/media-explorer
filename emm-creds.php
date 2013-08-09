<?php
/*
Plugin Name: EMM oAuth Credentials
Description: EMM oAuth Credentials
Version:     1.0
Author:      John Blackbourn
Author URI:  http://johnblackbourn.com/

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

*/

add_filter( 'emm_twitter_credentials', function() {

	return array(
		'consumer_key'       => '',
		'consumer_secret'    => '',
		'oauth_token'        => '',
		'oauth_token_secret' => ''
	);

} );

add_filter( 'emm_youtube_developer_key', function() {

	// Add your developer key here.
	// Get your developer key at: <https://code.google.com/apis/console>
	return '';

});
