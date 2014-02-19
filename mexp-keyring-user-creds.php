<?php
/*
Plugin Name: MEXP Keyring Credentials
Description: MEXP Keyring Credentials
Version:     1.0
Author:      Michael Blouin, Automattic
Author URI:  http://automattic.com

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

*/

function mexp_get_keyring_oauth2_credentials( $service, array $credentials ) {
	
	if ( ! class_exists( 'Keyring') ) {
		return $credentials;
	}

	// Check that the service is set up
	$keyring = Keyring::init()->get_service_by_name( $service );

	if ( is_null( $keyring ) ) {
		return $credentials;
	}

	$keyring_store = Keyring::init()->get_token_store();
	
	// Hacky time, Keyring is designed to handle requests, but we're just stealing its access_token.
	if ( method_exists( $keyring_store, 'get_tokens_by_user' ) ) {
		
		// The wpcom version uses the get_tokens_by_user method
		$users_tokens = $keyring_store->get_tokens_by_user( get_current_user_id() );
		
		if ( isset( $users_tokens[$service] ) ) {
			$credentials['access_token'] = $users_tokens[$service][0]->token;
		}
		
	} elseif ( method_exists( $keyring_store, 'get_tokens' ) ) {
		
		// The released version uses the get_tokens method
		$users_tokens = $keyring_store->get_tokens(
				array(
					'service' => $service,
					'user_id' => get_current_user_id(),
				)
			);
		
		if ( count( $users_tokens ) > 0 ) {
			$credentials['access_token'] = $users_tokens[0]->token;
		}
		
	}
	
	return $credentials;

}

function mexp_get_keyring_oauth1_credentials( $service, array $credentials ) {

	if ( ! class_exists( 'Keyring') ) {
		return $credentials;
	}

	// Check that the service is set up
	$keyring = Keyring::init()->get_service_by_name( $service );

	if ( is_null( $keyring ) ) {
		return $credentials;
	}

	$keyring_store = Keyring::init()->get_token_store();

	if ( method_exists( $keyring_store, 'get_tokens_by_user' ) ) {

		// The wpcom version uses the get_tokens_by_user method
		$users_tokens = $keyring_store->get_tokens_by_user( get_current_user_id() );

		if ( isset( $users_tokens[$service] ) ) {
			$token = $users_tokens[$service];
			$credentials['consumer_key']       = $keyring->key;
			$credentials['consumer_secret']    = $keyring->secret;
			$credentials['oauth_token']        = $token->token->key;
			$credentials['oauth_token_secret'] = $token->token->secret;
		}

	} elseif ( method_exists( $keyring_store, 'get_tokens' ) ) {

		// The released version uses the get_tokens method
		$users_tokens = $keyring_store->get_tokens( array(
			'service' => $service,
		) );

		if ( count( $users_tokens ) > 0 ) {
			$token = $users_tokens[0];
			$credentials['consumer_key']       = $keyring->key;
			$credentials['consumer_secret']    = $keyring->secret;
			$credentials['oauth_token']        = $token->token->key;
			$credentials['oauth_token_secret'] = $token->token->secret;
		}

	}

	return $credentials;

}

add_filter( 'mexp_twitter_credentials', 'mexp_twitter_credentials_callback', 99 );

function mexp_twitter_credentials_callback( array $credentials ) {
	return mexp_get_keyring_oauth1_credentials( 'twitter', $credentials );
}

add_filter( 'mexp_instagram_user_credentials', 'mexp_instagram_user_credentials_callback', 99 );

function mexp_instagram_credentials_callback( array $credentials ) {
	return mexp_get_keyring_oauth2_credentials( 'instagram', $credentials );
}
