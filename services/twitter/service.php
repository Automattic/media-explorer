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

defined( 'ABSPATH' ) or die();

class MEXP_Twitter_Service extends MEXP_Service {

	public $credentials = null;
	public $response_meta = array();

	public function __construct() {

		require_once dirname( __FILE__ ) . '/template.php';

		# Go!
		$this->set_template( new MEXP_Twitter_Template );

	}

	public function load() {

		add_action( 'mexp_enqueue', array( $this, 'enqueue_statics' ) );

		add_filter( 'mexp_tabs', array( $this, 'tabs' ), 10, 1 );

		add_filter( 'mexp_labels', array( $this, 'labels' ), 10, 1 );

	}

	public function enqueue_statics() {

		$mexp = Media_Explorer::init();

		wp_enqueue_script(
			'google-jsapi',
			'https://www.google.com/jsapi',
			array(),
			false
		);
		wp_enqueue_script(
			'mexp-service-twitter',
			$mexp->plugin_url( 'services/twitter/js.js' ),
			array( 'jquery', 'mexp' ),
			$mexp->plugin_ver( 'services/twitter/js.js' )
		);

	}

	public function request( array $request ) {

		if ( is_wp_error( $connection = $this->get_connection() ) )
			return $connection;

		$params = $request['params'];

		if ( isset( $params['location'] ) and empty( $params['coords'] ) ) {
			if ( is_wp_error( $coords = $this->get_coords( $params['location'] ) ) ) {
				return $coords;
			} else {
				$this->response_meta['coords'] = $coords;
				$params['coords'] = sprintf( '%s,%s', $coords->lat, $coords->lng );
			}
		}

		# operators: https://dev.twitter.com/docs/using-search
		# @TODO +exclude:retweets

		$q = array();

		if ( isset( $params['q'] ) )
			$q[] = trim( $params['q'] );

		if ( isset( $params['hashtag'] ) )
			$q[] = sprintf( '#%s', ltrim( $params['hashtag'], '#' ) );

		if ( isset( $params['by_user'] ) )
			$q[] = sprintf( 'from:%s', ltrim( $params['by_user'], '@' ) );

		if ( isset( $params['to_user'] ) )
			$q[] = sprintf( '@%s', ltrim( $params['to_user'], '@' ) );

		if ( 'images' == $request['tab'] )
			$q[] = 'filter:images';

		// Exclude retweets from certain searches
		if ( ! isset( $params['by_user'] ) && ! isset( $params['to_user'] ) )
			$q[] = '+exclude:retweets';

		$args = array(
			'q'           => implode( ' ', $q ),
			'result_type' => 'recent',
			'count'       => 20,
		);

		if ( isset( $params['coords'] ) and isset( $params['radius'] ) ) {
			if ( is_array( $params['radius'] ) )
				$params['radius'] = reset( $params['radius'] );
			$args['geocode'] = sprintf( '%s,%dkm', $params['coords'], $params['radius'] );
		}

		if ( !empty( $request['min_id'] ) )
			$args['since_id'] = $request['min_id'];
		else if ( !empty( $request['max_id'] ) )
			$args['max_id'] = $request['max_id'];

		$response = $connection->get( sprintf( '%s/search/tweets.json', untrailingslashit( $connection->host ) ), $args );

		if ( 200 == $connection->http_code ) {

			return $this->response( $response );

		} else {

			return new WP_Error(
				'mexp_twitter_failed_request',
				sprintf( __( 'Could not connect to Twitter (error %s).', 'mexp' ),
					esc_html( $connection->http_code )
				)
			);

		}

	}

	public function get_coords( $location ) {

		$url = sprintf( 'https://maps.googleapis.com/maps/api/geocode/json?address=%s&sensor=false',
			urlencode( trim( $location ) )
		);
		$result = wp_remote_get( $url );

		if ( is_wp_error( $result ) )
			return $result;

		$error = new WP_Error(
			'mexp_twitter_failed_location',
			__( 'Could not find your requested location.', 'mexp' )
		);

		if ( 200 != wp_remote_retrieve_response_code( $result ) )
			return $error;
		if ( ! $data = wp_remote_retrieve_body( $result ) )
			return $error;

		$data = json_decode( $data );

		if ( 'OK' != $data->status )
			return $error;

		$location = reset( $data->results );

		if ( ! isset( $location->geometry->location ) )
			return $error;

		return $location->geometry->location;

	}

	public function status_url( $status ) {

		return sprintf( 'https://twitter.com/%s/status/%s',
			$status->user->screen_name,
			$status->id_str
		);

	}

	public function status_content( $status ) {

		$text = $status->text;

		# @TODO more processing (hashtags, @s etc)
		$text = make_clickable( $text );
		$text = str_replace( ' href="', ' target="_blank" href="', $text );

		return $text;

	}

	public function get_max_id( $next ) {

		parse_str( ltrim( $next, '?' ), $vars );

		if ( isset( $vars['max_id'] ) )
			return $vars['max_id'];
		else
			return null;

	}

	public function response( $r ) {

		if ( !isset( $r->statuses ) or empty( $r->statuses ) )
			return false;

		$response = new MEXP_Response;

		if ( isset( $r->search_metadata->next_results ) )
			$response->add_meta( 'max_id', self::get_max_id( $r->search_metadata->next_results ) );

		if ( isset( $this->response_meta ) )
			$response->add_meta( $this->response_meta );

		foreach ( $r->statuses as $status ) {

			$item = new MEXP_Response_Item;

			$item->set_id( $status->id_str );
			$item->set_url( self::status_url( $status ) );
			$item->set_content( self::status_content( $status ) );
			$item->set_thumbnail( is_ssl() ? $status->user->profile_image_url_https : $status->user->profile_image_url );
			$item->set_date( strtotime( $status->created_at ) );
			$item->set_date_format( 'g:i A - j M y' );

			$item->add_meta( 'user', array(
				'name'        => $status->user->name,
				'screen_name' => $status->user->screen_name,
			) );

			$response->add_item( $item );

		}

		return $response;

	}

	public function tabs( array $tabs ) {
		$tabs['twitter'] = array(
			'all' => array(
				'text'       => _x( 'All', 'Tab title', 'mexp'),
				'defaultTab' => true
			),
			'hashtag' => array(
				'text' => _x( 'With Hashtag', 'Tab title', 'mexp'),
			),
			#'images' => array(
			#	'text' => _x( 'With Images', 'Tab title', 'mexp'),
			#),
			'by_user' => array(
				'text' => _x( 'By User', 'Tab title', 'mexp'),
			),
			'to_user' => array(
				'text' => _x( 'To User', 'Tab title', 'mexp'),
			),
			'location' => array(
				'text' => _x( 'By Location', 'Tab title', 'mexp'),
			),
		);

		return $tabs;
	}

	public function requires() {
		return array(
			'oauth' => 'OAuthConsumer'
		);
	}

	public function labels( array $labels ) {
		$labels['twitter'] = array(
			'title'     => __( 'Insert Tweet', 'mexp' ),
			# @TODO the 'insert' button text gets reset when selecting items. find out why.
			'insert'    => __( 'Insert Tweet', 'mexp' ),
			'noresults' => __( 'No tweets matched your search query', 'mexp' ),
			'gmaps_url' => set_url_scheme( 'https://maps.google.com/maps/api/js', 'https' ),
			'loadmore'  => __( 'Load more tweets', 'mexp' ),
		);

		return $labels;
	}

	private function get_connection() {

		$credentials = $this->get_credentials();

		# Despite saying that application-only authentication for search would be available by the
		# end of March 2013, Twitter has still not implemented it. This means that for API v1.1 we
		# still need user-level authentication in addition to application-level authentication.
		#
		# If the time comes that application-only authentication is made available for search, the
		# use of the oauth_token and oauth_token_secret fields below can simply be removed.
		#
		# Further bedtime reading:
		#
		# https://dev.twitter.com/discussions/11079
		# https://dev.twitter.com/discussions/13210
		# https://dev.twitter.com/discussions/14016
		# https://dev.twitter.com/discussions/15744

		foreach ( array( 'consumer_key', 'consumer_secret', 'oauth_token', 'oauth_token_secret' ) as $field ) {
			if ( !isset( $credentials[$field] ) or empty( $credentials[$field] ) ) {
				return new WP_Error(
					'mexp_twitter_no_connection',
					__( 'oAuth connection to Twitter not found.', 'mexp' )
				);
			}
		}

		if ( !class_exists( 'WP_Twitter_OAuth' ) )
			require_once dirname( __FILE__ ) . '/class.wp-twitter-oauth.php';

		$connection = new WP_Twitter_OAuth(
			$credentials['consumer_key'],
			$credentials['consumer_secret'],
			$credentials['oauth_token'],
			$credentials['oauth_token_secret']
		);

		$connection->useragent = sprintf( 'Extended Media Manager at %s', home_url() );

		return $connection;

	}

	private function get_credentials() {

		if ( is_null( $this->credentials ) )
			$this->credentials = (array) apply_filters( 'mexp_twitter_credentials', array() );

		return $this->credentials;

	}

}

add_filter( 'mexp_services', 'mexp_service_twitter' );

function mexp_service_twitter( array $services ) {
	$services['twitter'] = new MEXP_Twitter_Service;
	return $services;
}
