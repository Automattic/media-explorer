<?php

class MEXP_YouTube_Service extends MEXP_Service {

	const DEFAULT_MAX_RESULTS = 18;

	public function __construct() {
		require_once dirname( __FILE__ ) . '/template.php';

		# Go!
		$this->set_template( new MEXP_YouTube_Template );
	}

	public function load() {

		add_action( 'mexp_enqueue', array( $this, 'enqueue_statics' ) );

		add_filter( 'mexp_tabs', array( $this, 'tabs' ), 10, 1 );

		add_filter( 'mexp_labels', array( $this, 'labels' ), 10, 1 );

	}

	public function enqueue_statics() {
	
		$mexp = Media_Explorer::init();

		wp_enqueue_script(
			'mexp-service-youtube',
			$mexp->plugin_url( 'services/youtube/js.js' ),
			array( 'jquery', 'mexp' ),
			$mexp->plugin_ver( 'services/youtube/js.js' ),
			true
		);

	}

	public function request( array $request ) {
		if ( is_wp_error( $youtube = $this->get_connection() ) )
			return $youtube;
		$params = $request['params'];

		switch ( $params['tab'] ) 
		{
			case 'by_user':
				$request = array(
					'channel' => sanitize_text_field( $params['channel'] ),
					'type' => 'video',
					'page_token' => sanitize_text_field( $params['page_token'] ),
				);

				//if ( isset( $params['page_token'] ) && '' !== $params['page_token'] )
					//$request['page_token'] = sanitize_text_field( $params['page_token'] );

				// Make the request to the YouTube API
				$search_response = $youtube->get_videos_from_channel( $request );
			break;

			default:
			case 'all':
				$request = array(
					'q' => sanitize_text_field( $params['q'] ),
					'maxResults' => self::DEFAULT_MAX_RESULTS,
				);

				if ( isset( $params['page_token'] ) && '' !== $params['page_token'] )
					$request['page_token'] = sanitize_text_field( $params['page_token'] );

				if ( isset( $params['type'] ) )
					$request['type'] = sanitize_text_field( $params['type'] );

				// Make the request to the YouTube API
				$search_response = $youtube->get_videos( $request );
			break;
			
		}

		// Create the response for the API
		$response = new MEXP_Response();

		if ( !isset( $search_response['items'] ) )
			return false;

		foreach ( $search_response['items'] as $index => $search_item ) {
			$item = new MEXP_Response_Item();
			if ( $request['type'] == 'video' && isset( $request['q'] ) ) { // For videos searched by query
				$item->set_url( esc_url( sprintf( "https://www.youtube.com/watch?v=%s", $search_item['id']['videoId'] ) ) );
			} elseif( $request['type'] == 'playlist' && isset( $request['q'] ) ) { // For playlists searched by query
				$item->set_url( esc_url( sprintf( "https://www.youtube.com/playlist?list=%s", $search_item['id']['playlistId'] ) ) );
			} else { // For videos searched by channel name
				$item->set_url( esc_url( sprintf( "https://www.youtube.com/watch?v=%s", $search_item['snippet']['resourceId']['videoId'] ) ) );
			}
			$item->add_meta( 'user', $search_item['snippet']['channelTitle'] );
			$item->set_id( (int) $params['startIndex'] + (int) $index );
			$item->set_content( $search_item['snippet']['title'] );
			$item->set_thumbnail( $search_item['snippet']['thumbnails']['medium']['url'] );
			$item->set_date( strtotime( $search_item['snippet']['publishedAt'] ) );
			$item->set_date_format( 'g:i A - j M y' );
			$response->add_item($item);
		}

		if ( isset( $search_response['nextPageToken'] ) )
			$response->add_meta( 'page_token', $search_response['nextPageToken'] );

		return $response;
	}

	public function tabs( array $tabs ) {
		$tabs['youtube'] = array(
			'all' => array(
				'text'       => _x( 'All', 'Tab title', 'mexp'),
				'defaultTab' => true
			),
			'by_user' => array(
				'text'       => _x( 'By User', 'Tab title', 'mexp'),
			),
		);
		return $tabs;
	}

	private function get_connection() {
		// Add the Google API classes to the runtime
		require_once plugin_dir_path( __FILE__) . '/class.wp-youtube-client.php';

		$developer_key = (string) apply_filters( 'mexp_youtube_developer_key', '' ) ;

		if ( empty( $developer_key ) ) {
			return new WP_Error(
				'mexp_youtube_no_connection',
				__( 'API connection to YouTube not found.', 'mexp' )
			);
		}

		return new MEXP_YouTube_Client( $developer_key );
	}

	public function labels( array $labels ) {

		$labels['youtube'] = array(
			'title'     => __( 'Insert YouTube', 'mexp' ),
			'insert'    => __( 'Insert', 'mexp' ),
			'noresults' => __( 'No videos matched your search query.', 'mexp' ),
		);

		return $labels;
	}
}

add_filter( 'mexp_services', 'mexp_service_youtube' );

function mexp_service_youtube( array $services ) {
	$services['youtube'] = new MEXP_YouTube_Service;
	return $services;
}
