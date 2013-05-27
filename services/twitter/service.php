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

namespace EMM\Services\Twitter;

defined( 'ABSPATH' ) or die();

class Template extends \EMM\Template {

	public function item( $id, $tab ) {
		?>
		<div id="emm-item-{{ data.id }}" class="emm-item-area" data-id="{{ data.id }}">
			<div class="emm-item-container clearfix">
				<div class="emm-item-thumb">
					<img src="{{ data.thumbnail }}">
				</div>
				<div class="emm-item-main">
					<div class="emm-item-author">
						<span class="emm-item-author-name">{{ data.meta.user.name }}</span>
						<span class="emm-item-author-screen-name"><span class="emm-item-author-at">@</span>{{ data.meta.user.screen_name }}</span>
					</div>
					<div class="emm-item-content">
						{{{ data.content }}}
					</div>
					<div class="emm-item-date">
						{{ data.date }}
					</div>
				</div>
			</div>
		</div>
		<a href="#" id="emm-check-{{ data.id }}" data-id="{{ data.id }}" class="check" title="<?php esc_attr_e( 'Deselect', 'emm' ); ?>">
			<div class="media-modal-icon"></div>
		</a>
		<?php
	}

	public function thumbnail( $id, $tab ) {
		?>
		<?php
	}

	public function search( $id, $tab ) {

		# @TODO move the spinner out of here and into the base class

		switch ( $tab ) {

			case 'hashtag':

				?>
				<div class="emm-toolbar-container clearfix">
					<input
						type="text"
						name="hashtag"
						value="{{ data.params.hashtag }}"
						class="emm-input-text emm-input-search"
						size="30"
						placeholder="<?php esc_attr_e( 'Enter a Hashtag', 'emm' ); ?>"
					>
					<div class="spinner"></div>
				</div>
				<?php

				break;

			case 'by_user':

				?>
				<div class="emm-toolbar-container clearfix">
					<input
						type="text"
						name="by_user"
						value="{{ data.params.by_user }}"
						class="emm-input-text emm-input-search"
						size="30"
						placeholder="<?php esc_attr_e( 'Enter a Twitter Username', 'emm' ); ?>"
					>
					<div class="spinner"></div>
				</div>
				<?php

				break;

			case 'to_user':

				?>
				<div class="emm-toolbar-container clearfix">
					<input
						type="text"
						name="to_user"
						value="{{ data.params.to_user }}"
						class="emm-input-text emm-input-search"
						size="30"
						placeholder="<?php esc_attr_e( 'Enter a Twitter Username', 'emm' ); ?>"
					>
					<div class="spinner"></div>
				</div>
				<?php

				break;

			case 'location':

				?>
				<div id="emm_twitter_map_canvas"></div>
				<div class="emm-toolbar-container clearfix">
					<input
						id="<?php echo esc_attr( $id ); ?>"
						type="hidden"
						name="location"
						value="{{ data.params.location }}"
					>
					<input
						type="text"
						name="q"
						value="{{ data.params.q }}"
						class="emm-input-text emm-input-search"
						size="30"
						placeholder="<?php esc_attr_e( 'Search Twitter', 'emm' ); ?>"
					>
					<select
						id="<?php echo esc_attr( $id ); ?>-radius"
						type="text"
						name="radius"
						class="emm-input-text emm-input-select"
						placeholder="<?php esc_attr_e( 'Search Twitter', 'emm' ); ?>"
					>
					<?php foreach ( array( 1, 5, 10, 20, 50, 100, 200 ) as $km ) { ?>
						<option value="<?php echo absint( $km ); ?>"><?php printf( esc_html__( 'Within %skm', 'emm' ), $km ); ?></option>
					<?php } ?>
					</select>
					<div class="spinner"></div>
				</div>
				<?php

				break;

			case 'all':
			default:

				?>
				<div class="emm-toolbar-container clearfix">
					<input
						type="text"
						name="q"
						value="{{ data.params.q }}"
						class="emm-input-text emm-input-search"
						size="30"
						placeholder="<?php esc_attr_e( 'Search Twitter', 'emm' ); ?>"
					>
					<div class="spinner"></div>
				</div>
				<?php

				break;

		}

	}

}

class Service extends \EMM\Service {

	public $credentials = null;

	public function __construct() {

		# Go!
		$this->set_template( new Template );

	}

	public function load() {

		$emm = \Extended_Media_Manager::init();

		wp_enqueue_script(
			'emm-service-twitter',
			$emm->plugin_url( 'services/twitter/js.js' ),
			array( 'jquery', 'emm' ),
			$emm->plugin_ver( 'services/twitter/js.js' )
		);

	}

	public function request( array $request ) {

		if ( is_wp_error( $connection = $this->get_connection() ) )
			return $connection;

		# +exclude:retweets

		# operators: https://dev.twitter.com/docs/using-search

		$params = $request['params'];

		$q = array();

		if ( isset( $params['q'] ) )
			$q[] = trim( $params['q'] );

		if ( isset( $params['hashtag'] ) )
			$q[] = sprintf( '#%s', ltrim( $params['hashtag'], '#' ) );

		if ( isset( $params['by_user'] ) )
			$q[] = sprintf( 'from:%s', ltrim( $params['by_user'], '@' ) );

		if ( isset( $params['to_user'] ) )
			$q[] = sprintf( '@%s', ltrim( $params['to_user'], '@' ) );

		$args = array(
			'q'           => implode( ' ', $q ),
			'result_type' => 'recent',
			'count'       => 20,
		);

		if ( isset( $params['location'] ) and isset( $params['radius'] ) )
			$args['geocode'] = sprintf( '%s,%dkm', $params['location'], $params['radius'] );

		if ( !empty( $request['min_id'] ) )
			$args['since_id'] = $request['min_id'];
		else if ( !empty( $request['max_id'] ) )
			$args['max_id'] = $request['max_id'];

		$response = $connection->get( sprintf( '%s/search/tweets.json', untrailingslashit( $connection->host ) ), $args );

		# @TODO switch the twitter oauth class over to wp http api:
		if ( 200 == $connection->http_code ) {

			return $this->response( $response );

		} else {

			return new \WP_Error(
				'emm_twitter_failed_request',
				sprintf( __( 'Could not connect to Twitter (error %s).', 'emm' ),
					esc_html( $connection->http_code )
				)
			);

		}

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

		$response = new \EMM\Response;

		# @TODO $r->search_metadata->next_results isn't always set, causes notice
		$response->add_meta( 'max_id', self::get_max_id( $r->search_metadata->next_results ) );

		foreach ( $r->statuses as $status ) {

			$item = new \EMM\Response_Item;

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

	public function tabs() {
		return array(
			#'welcome' => array(
			#	'text' => _x( 'Welcome', 'Tab title', 'emm'),
			#),
			'all' => array(
				'text'    => _x( 'All', 'Tab title', 'emm'),
				'default' => true
			),
			'hashtag' => array(
				'text' => _x( 'With Hashtag', 'Tab title', 'emm'),
			),
			#'images' => array(
			#	'text' => _x( 'With Images', 'Tab title', 'emm'),
			#),
			'by_user' => array(
				'text' => _x( 'By User', 'Tab title', 'emm'),
			),
			'to_user' => array(
				'text' => _x( 'To User', 'Tab title', 'emm'),
			),
			'location' => array(
				'text' => _x( 'By Location', 'Tab title', 'emm'),
			),
		);
	}

	public function requires() {
		return array(
			'oauth' => '\OAuthConsumer'
		);
	}

	public function labels() {
		return array(
			'title'     => sprintf( __( 'Insert from %s', 'emm' ), 'Twitter' ),
			# @TODO the 'insert' button text gets reset when selecting items. find out why.
			'insert'    => __( 'Insert Tweet', 'emm' ),
			'noresults' => __( 'No tweets matched your search query', 'emm' ),
			'gmaps_url' => set_url_scheme( 'http://maps.google.com/maps/api/js' )
		);
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
				return new \WP_Error(
					'emm_twitter_no_connection',
					__( 'oAuth connection to Twitter not found.', 'emm' )
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
			$this->credentials = (array) apply_filters( 'emm_twitter_credentials', array() );

		return $this->credentials;

	}

}

add_filter( 'emm_services', function( $services ) {
	$services['twitter'] = new Service;
	return $services;
} );
