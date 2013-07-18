<?php

namespace EMM\Services\Youtube;

class Service extends \EMM\Service {

	public $credentials = null;
	public $response_meta = array();

	public function __construct() {
		require_once __DIR__ . '/template.php';

		# Go!
		$this->set_template( new Template );
	}

	public function request( array $request ) {
		$youtube = $this->get_connection();
		$params = $request['params'];

		try {
			// Make the request to the Youtube API
			$search_response = $youtube->search->listSearch( 'id,snippet', array(
				'q'          => $params['q'],
				'maxResults' => 10,
				'type'       => 'video',
			) );

			// Create the response for the API
			$response = new \EMM\Response();

			foreach ( $search_response['items'] as $index => $search_item ) {
				$item = new \EMM\Response_Item();
				$item->set_id( $index );
				$item->set_url( esc_url( sprintf( "http://www.youtube.com/watch?v=%s", $search_item['id']['videoId'] ) ) );
				$item->set_content( $search_item['snippet']['title'] );
				$item->set_thumbnail( $search_item['snippet']['thumbnails']['medium']['url'] );
				$item->set_date( strtotime( $search_item['snippet']['publishedAt'] ) );
				$item->set_date_format( 'g:i A - j M y' );
				$response->add_item($item);
			}

			return $response;
		} catch (Google_ServiceException $e) {
			return new \WP_Error(
				'emm_youtube_failed_request',
				'Could not connect to Youtube'
			);
		} catch (Google_Exception $e) {
			return new \WP_Error(
				'emm_youtube_google_error',
				'something went wrong with the Youtube SDK'
			);
		}
	}

	public function tabs() {
		return array(
			'all' => array(
				'text'       => _x( 'All', 'Tab title', 'emm'),
				'defaultTab' => true
			),
			'by_channel' => array(
				'text' => _x( 'By Channel', 'Tab title', 'emm'),
			),
			'by_freebase_topic' => array(
				'text' => _x( 'By Freebase Topic', 'Tab title', 'emm'),
			),
		);
	}

	private function get_connection() {
		// Add the Google API classes to the runtime
		if ( !class_exists( 'Google_Client' ) || !class_exists( 'Google_YoutubeService' ) ) {
			require_once plugin_dir_path( __FILE__ ) . '/google-api-php-client/src/Google_Client.php';
			require_once plugin_dir_path( __FILE__ ) . '/google-api-php-client/src/contrib/Google_YoutubeService.php';
		}

		$developer_key = (string) apply_filters( 'emm_youtube_developer_key', '' ) ;
		$client = new \Google_Client();
		$client->setDeveloperKey( $developer_key );

		return new \Google_YoutubeService( $client );
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
