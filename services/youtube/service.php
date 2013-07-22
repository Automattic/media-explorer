<?php

namespace EMM\Services\Youtube;

class Service extends \EMM\Service {

	public function __construct() {
		require_once __DIR__ . '/template.php';

		# Go!
		$this->set_template( new Template );
	}

	public function request( array $request ) {
		$youtube = $this->get_connection();
		$params = $request['params'];

		switch ( $params['tab'] ) 
		{
			case 'all':
				$request = array(
					'q' => $params['q'],
					'maxResults' => 10,
				);

				if ( isset( $params['type'] ) )
					$request['type'] = $params['type'];

				// Make the request to the Youtube API
				$search_response = $youtube->get_videos( $request );
			break;
			
			case 'by_user':
				$request = array(
					'channel' => $params['channel'],
					'type' => 'video',
				);

				// Make the request to the Youtube API
				$search_response = $youtube->get_videos_from_channel( $request );
			break;
		}

		// Create the response for the API
		$response = new \EMM\Response();

		foreach ( $search_response['items'] as $index => $search_item ) {
			$item = new \EMM\Response_Item();
			if ( $request['type'] == 'video' ) {
				$item->set_url( esc_url( sprintf( "http://www.youtube.com/watch?v=%s", $search_item['id']['videoId'] ) ) );
			} else {
				$item->set_url( esc_url( sprintf( "http://www.youtube.com/playlist?list=%s", $search_item['id']['playlistId'] ) ) );
			}
			$item->add_meta( 'user', $search_item['snippet']['channelTitle'] );
			$item->set_id( $index );
			$item->set_content( $search_item['snippet']['title'] );
			$item->set_thumbnail( $search_item['snippet']['thumbnails']['medium']['url'] );
			$item->set_date( strtotime( $search_item['snippet']['publishedAt'] ) );
			$item->set_date_format( 'g:i A - j M y' );
			$response->add_item($item);
		}

		return $response;
	}

	public function tabs() {
		return array(
			'all' => array(
				'text'       => _x( 'All', 'Tab title', 'emm'),
				'defaultTab' => true
			),
			'by_user' => array(
				'text'       => _x( 'By user', 'Tab title', 'emm'),
			),
		);
	}

	private function get_connection() {
		// Add the Google API classes to the runtime
		if ( !class_exists( 'Google_Client' ) || !class_exists( 'Google_YoutubeService' ) ) {
			require_once plugin_dir_path( __FILE__) . '/class.wp-youtube-client.php';
		}

		$developer_key = (string) apply_filters( 'emm_youtube_developer_key', '' ) ;

		return new Youtube_Client( $developer_key );
	}

	public function labels() {
		return array(
			'title'     => sprintf( __( 'Insert from %s', 'emm' ), 'Youtube' ),
			'insert'    => __( 'Insert', 'emm' ),
			'noresults' => __( 'No videos matched your search query', 'emm' ),
		);
	}
}

add_filter( 'emm_services', function( $services ) {
	$services['youtube'] = new Service;
	return $services;
} );
