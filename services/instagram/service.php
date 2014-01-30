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

class MEXP_Instagram_Service extends MEXP_Service {

	public $user_credentials = null;
	public $generic_credentials = null;
	
	public function __construct() {

		require_once dirname( __FILE__ ) . '/template.php';

		// Go!
		$this->set_template( new MEXP_Instagram_Template );

	}

	public function load() {

		add_action( 'mexp_enqueue', array( $this, 'enqueue_statics' ) );

		add_filter( 'mexp_tabs', array( $this, 'tabs' ), 10, 1 );

		add_filter( 'mexp_labels', array( $this, 'labels' ), 10, 1 );
	}

	public function enqueue_statics() {

		$mexp = Media_Explorer::init();

		wp_enqueue_script(
			'mexp-service-instagram',
			$mexp->plugin_url( 'services/instagram/js.js' ),
			array( 'jquery', 'mexp' ),
			$mexp->plugin_ver( 'services/instagram/js.js' )
		);

		wp_enqueue_style(
			'mexp-service-instagram-css',
			$mexp->plugin_url( 'services/instagram/style.css' ),
			array(),
			$mexp->plugin_ver( 'services/instagram/style.css' )
		);

	}

	public function request( array $request ) {
		$params = $request['params'];
		$tab 	= $request['tab'];

		$query_params = array();

		if ( isset( $params['q'] ) ) {
			$q = $query_params['q'] = sanitize_title_with_dashes( $params['q'] );
		}

		switch ( $tab ) {
			case 'tag':
				$endpoint = "tags/{$q}/media/recent";
				break;

			case 'by_user':
				$user_id = $this->get_user_id( $q );
				$endpoint = "users/{$user_id}/media/recent";
				break;

			case 'mine':
				$credentials = $this->get_user_credentials();
				$query_params['access_token'] = $credentials['access_token'];
				$endpoint = 'users/self/media/recent';
				break;

			case 'feed':
				$credentials = $this->get_user_credentials();
				$query_params['access_token'] = $credentials['access_token'];
				$endpoint = 'users/self/feed';
				break;

			case 'popular':
			default:
				$endpoint = 'media/popular';
		}

		if ( !empty( $request['max_id'] ) ) {
			$query_params['max_id'] = $request['max_id'];
		}
		
		$response = $this->do_request( $endpoint, $query_params );

		if ( is_wp_error( $response ) ) {
			
			return $response;
			
		} elseif ( 200 == $response['code'] || 400 == $response['code'] ) {

			return $this->response( $response );

		} else {

			return new WP_Error(
				'mexp_instagram_failed_request',
				sprintf( __( 'Could not connect to Instagram (error %s).', 'mexp' ),
					esc_html( $response['code'] )
				)
			);

		}

	}

	public function do_request( $endpoint, $params = array() ) {
		$host = 'https://api.instagram.com';
		$version = 'v1';
		if ( !isset( $params['access_token'] ) ) {
			$credentials = $this->get_generic_credentials();

			if ( ! isset( $credentials['access_token'] ) ) {
				return new WP_Error(
					'mexp_instagram_no_connection',
					__( 'oAuth connection to Instagram not found.', 'mexp' )
				);
			}

			$params['access_token'] = $credentials['access_token'];
		}

		$url = add_query_arg( $params, "$host/$version/$endpoint/" );

		$response = wp_remote_get( $url );

		$code = wp_remote_retrieve_response_code( $response );

		$data = array();
		if ( 200 == $code ) {
			$data = json_decode( wp_remote_retrieve_body( $response ) );
		}

		return array(
			'code' => $code,
			'data' => $data,
		);

	}

	public function get_user_id( $username ) {
		$response = $this->do_request( 'users/search', array( 'q' => $username ) );

		if ( ! is_wp_error( $response ) && 200 == $response['code'] ) {
			foreach ( $response['data']->data as $user ) {
				if ( $user->username == $username ) {
					return $user->id;
				}
			}
		}

		return 0;
	}

	public function response( $r ) {

		if ( empty( $r['data'] ) ) {
			return false;
		}

		$response = new MEXP_Response;

		foreach ( $r['data']->data as $result ) {
			$item = new MEXP_Response_Item;

			$item->set_id( $result->id );
			$item->set_url( $result->link );
			
			// Not all results have a caption
			if ( is_object( $result->caption ) ) {
				$item->set_content( $result->caption->text );
			}
			
			$item->set_thumbnail( set_url_scheme( $result->images->thumbnail->url ) );
			$item->set_date( $result->created_time );
			$item->set_date_format( 'g:i A - j M y' );

			$item->add_meta( 'user', array(
				'username' => $result->user->username,
			) );

			$response->add_item( $item );

		}

		// Pagination details
		if ( !empty( $r['data']->pagination ) ) {
			if ( isset( $r['data']->pagination->next_max_id ) ) {
				$response->add_meta( 'max_id', $r['data']->pagination->next_max_id );
			}
			
			if ( isset( $r['data']->pagination->next_min_id ) ) {
				$response->add_meta( 'min_id', $r['data']->pagination->next_min_id );
			}
		}

		return $response;

	}

	public function tabs( array $tabs ) {
		$tabs['instagram'] = array();

		$user_creds = $this->get_user_credentials();
		if ( ! empty( $user_creds ) ) {
			$tabs['instagram']['mine'] = array(
				'text'       => _x( 'My Instagrams', 'Tab title', 'mexp' ),
				'defaultTab' => true,
				'fetchOnRender' => true,
			);
			$tabs['instagram']['feed'] = array(
				'text'       => _x( 'My Feed', 'Tab title', 'mexp' ),
				'fetchOnRender' => true,
			);
		}

		$tabs['instagram']['popular'] = array(
			'text'       => _x( 'Browse Popular', 'Tab title', 'mexp'),
			'defaultTab' => empty( $tabs['instagram'] ),
			'fetchOnRender' => true,
		);
		$tabs['instagram']['tag'] = array(
			'text' => _x( 'With Tag', 'Tab title', 'mexp'),
		);
		$tabs['instagram']['by_user'] = array(
			'text' => _x( 'By User', 'Tab title', 'mexp'),
		);

		return $tabs;
	}

	public function labels( array $labels ) {
		$labels['instagram'] = array(
			'title'     => __( 'Insert Instagram', 'mexp' ),
			// @TODO the 'insert' button text gets reset when selecting items. find out why.
			'insert'    => __( 'Insert Instagram', 'mexp' ),
			'noresults' => __( 'No pics matched your search query', 'mexp' ),
			'loadmore'  => __( 'Load more pics', 'mexp' ),
		);

		return $labels;
	}

	private function get_generic_credentials() {

		if ( is_null( $this->generic_credentials ) ) {
			$this->generic_credentials = (array) apply_filters( 'mexp_instagram_credentials', array() );
		}

		return $this->generic_credentials;

	}

	private function get_user_credentials() {
		
		if ( is_null( $this->user_credentials ) ) {
			$this->user_credentials = (array) apply_filters( 'mexp_instagram_user_credentials', array() );
		}

		return $this->user_credentials;

	}

}

add_filter( 'mexp_services', 'mexp_service_instagram' );

function mexp_service_instagram( array $services ) {
	$services['instagram'] = new MEXP_Instagram_Service;

	return $services;
}
