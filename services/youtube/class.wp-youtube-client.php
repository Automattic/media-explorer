<?php

class MEXP_YouTube_Client {

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
	 * this method performs a query to the search endpoint of the YouTube API
	 *
	 * @param array $query an array containing the parameters of the query
	 * @return string
	 */
	public function get_videos( $query ) {
		$request = $this->create_url( $query );
		return self::get_json_as_array( $request );
	}

	/**
	 * This method returns the videos of a channel by channel name.
	 *
	 * @param array $query an array containing the parameters of the query
	 * @return string
	 */
	public function get_videos_from_channel( $query ) {

		$channel_url_query = $this->create_url( $query, 'channels' );

		// First request, in which we are trying to get the uploads playlist id of the user
		$channel_response = self::get_json_as_array( $channel_url_query );

		if ( $channel_response['pageInfo']['totalResults'] == 0 )
			return false;

		// Every YouTube channel has a "uploads" playlist, containing all the uploads of the channel
		$playlist_params['uploads_id'] = $channel_response['items'][0]['contentDetails']['relatedPlaylists']['uploads'];
		$playlist_params['page_token'] = $query['page_token'];

		$playlist_url_query = $this->create_url( $playlist_params, 'playlistItems' );
		
		// Second cURL, in this one we are going to get all the videos inside the uploads playlist of the user
		return self::get_json_as_array( $playlist_url_query );
	}

	/**
	 * This method creates an url from an array of parameters
	 *
	 * @param array $query an array containing the parameters for the request
	 * @param string $resource a string containing the endpoint of the API
	 * @return string
	 */
	private function create_url( $query, $resource = 'search' ) {
		// URL for channels
		if ( $resource == 'channels' ) {
			$channel_url_query = sprintf( '%s/channels?forUsername=%s&part=contentDetails&key=%s', $this->api_url, urlencode( $query['channel'] ), $this->developer_key );
			return $channel_url_query;
		}

		// URL for playlists
		if ( $resource == 'playlistItems' ) {
			$playlist_url_query = sprintf( '%s/playlistItems?maxResults=%s&playlistId=%s&part=snippet&key=%s', $this->api_url, MEXP_YouTube_Service::DEFAULT_MAX_RESULTS, $query['uploads_id'], $this->developer_key );
			if ( isset( $query['page_token'] ) && '' != $query['page_token'] )
				$playlist_url_query .= '&pageToken=' . $query['page_token'];
			return $playlist_url_query;
		}

		$params = array();

		if ( isset( $query['page_token'] ) && '' !== $query['page_token'] ) {
			$params[] = 'pageToken=' . $query['page_token'];
		}

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
			$params[] = 'maxResults=' . MEXP_YouTube_Service::DEFAULT_MAX_RESULTS;

		// Mandatory field "part"
		if ( isset( $query['part'] ) )
			$params[] = 'part=' . urlencode( $query['part'] );
		else
			$params[] = 'part=snippet';

		return $this->api_url . '/' . $resource . '?'. implode( '&', $params ) . '&key=' . $this->developer_key;
	}

	/**
	 * Fetch an url and returns the json parsed as an array
	 *
	 * @param string $url the URL we want to curl
	 * @return array
	 */
	private static function get_json_as_array( $url ) {
		$response = (array) wp_remote_get( $url );
		if ( !isset( $response['response']['code'] ) || 200 != $response['response']['code'] )
			return false;
		else
			return json_decode( $response['body'], true );
	}
}
