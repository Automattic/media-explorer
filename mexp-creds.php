<?php
/*
Plugin Name: MEXP oAuth Credentials
Description: MEXP oAuth Credentials
Version:     1.0
Author:      John Blackbourn
Author URI:  https://johnblackbourn.com/

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

*/

add_filter( 'mexp_twitter_credentials', 'mexp_twitter_credentials_callback' );

function mexp_twitter_credentials_callback() {

	return array(
		'consumer_key'       => '',
		'consumer_secret'    => '',
		'oauth_token'        => '',
		'oauth_token_secret' => ''
	);

}

add_filter( 'mexp_youtube_developer_key', 'mexp_youtube_developer_key_callback' );

function mexp_youtube_developer_key_callback() {

	// Add your developer key here.
	// Get your developer key at: <https://code.google.com/apis/console>
	return '';

}

add_filter( 'mexp_instagram_credentials', 'mexp_instagram_credentials_callback' );

function mexp_instagram_credentials_callback( $credentials ) {

	// Add your developer key here.
	// Get your developer key at: <https://instagram.com/developer>
	return array( 
		'access_token' => '',
	);

}
