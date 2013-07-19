<?php

namespace EMM\Services\Youtube;

class Youtube_Client {

	private $developer_key='';

	private $api_url = 'https://www.googleapis.com/youtube/v3';

	private $api_request;

	public function __construct( $developer_key ) {
		$this->developer_key = $developer_key;
	}

	/**
	 * example request:
	 * https://www.googleapis.com/youtube/v3/search
	 *  ?part=snippet
	 *  &q=YouTube+Data+API
	 *  &type=video
	 *  &videoCaption=closedCaption
	 *  &key=AIzaSyDg5EgjniyIn2YaQbBgUtzM7N8Qn1QN3zA
	 *
	 */
	public function get_videos( $query ) {
		$request = $this->create_url( $query );
		$curl = curl_init();
		curl_setopt( $curl, CURLOPT_URL, $request );
		curl_setopt( $curl, CURLOPT_RETURNTRANSFER, true );
		return json_decode( curl_exec( $curl ), true );
	}

	private function create_url( $query ) {
		$params = array();

		if ( isset( $query['q'] ) )
			$params[] = 'q=' . urlencode( $query['q'] );

		// Allow searching for playlists or videos
		if ( isset( $query['type'] ) && $query['type'] == 'playlist' )
			$params[] = 'type=playlist';
		else
			$params[] = 'type=video';

		// Number of results we want to return
		if ( isset( $query['maxResults'] ) )
			$params[] = 'maxResults=' . (int) $query['maxResults'];
		else
			$params[] = 'maxResults=10';

		// Mandatory field "part"
		if ( isset( $query['part'] ) )
			$params[] = 'part=' . urlencode( $query['part'] );
		else
			$params[] = 'part=snippet';

		return $this->api_url . '/search?'. implode( '&', $params ) . '&key=' . $this->developer_key;
	}

}
