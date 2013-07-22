<?php

namespace EMM\Services\Youtube;

class Youtube_Client {

	private $developer_key='';

	private $api_url = 'https://www.googleapis.com/youtube/v3';

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
	 *
	 */
	public function get_videos( $query ) {
		$request = $this->create_url( $query );
		$curl = curl_init();
		curl_setopt( $curl, CURLOPT_URL, $request );
		curl_setopt( $curl, CURLOPT_RETURNTRANSFER, true );
		return json_decode( curl_exec( $curl ), true );
	}

	/**
	 * This method returns the videos of a channel by channel name.
	 *
	 */
	public function get_videos_from_channel( $channel ) {
		$channel_endpoint  = $this->api_url . '/channels';
		$playlist_endpoint = $this->api_url . '/playlistItems';
		$channel_url_query = $channel_endpoint  .'?forUsername=' . urlencode( $channel ) . '&part=contentDetails&key=' . $this->developer_key;

		// First cURL, in which we are trying to get the uploads playlist id of the user
		$curl = curl_init();
		curl_setopt( $curl, CURLOPT_URL, $channel_url_query );
		curl_setopt( $curl, CURLOPT_RETURNTRANSFER, true );
		$channel_response = json_decode( curl_exec( $curl ), true );

		// Every Youtube channel has a "uploads" playlist, containing all the uploads of the channel
		$uploads_id = $channel_response['items'][0]['contentDetails']['relatedPlaylists']['uploads'];

		$playlist_url_query = $playlist_endpoint . '?playlistId=' . $uploads_id . '&part=snippet&key=' . $this->developer_key;
		
		// Second cURL, in this one we are going to get all the videos inside the uploads playlist of the user
		$curl = curl_init();
		curl_setopt( $curl, CURLOPT_URL, $playlist_url_query );
		curl_setopt( $curl, CURLOPT_RETURNTRANSFER, true );
		return json_decode( curl_exec( $curl ), true );
	}

	/**
	 * This method creates an url from an array of parameters
	 *
	 */
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
