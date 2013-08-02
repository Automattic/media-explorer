/**
 * This js is going to handle the infinite scroll for the Youtube service in the
 * EMM plugin
 * */

jQuery( function( $ ) {

	wp.media.view.MediaFrame.Post = pf.extend({

		initialize: function() {

			pf.prototype.initialize.apply( this, arguments );

			this.on( 'content:render:emm-service-youtube-content-all content:render:emm-service-youtube-content-by_user', _.bind( function() {

				var $container = jQuery( 'ul.emm-items' );

				// Handler for the scroll event. We'll have to check here how to
				// make a infinite scroll here...
				$container.scroll( callback_scroll_handler );

			}, this ) );

		}

	});

});

function callback_scroll_handler() {

	var $container = jQuery( 'ul.emm-items' ),
		total_height = $container.get( 0 ).scrollHeight,
		position = $container.height() + $container.scrollTop();

	// only fires when the position of the scrolled window is at the bottom
	// This is compared to 15 instead of 0 because of the padding of the
	// <ul>
	if( total_height - position == 15 ) {
		infinite_scroll();
	}

}

function infinite_scroll() {
	var params = {
		q: jQuery( '.emm-toolbar-container .emm-input-search' ).val(),
		maxResults: 18,
		key: 'AIzaSyDg5EgjniyIn2YaQbBgUtzM7N8Qn1QN3zA', // Add here your developer key
		startIndex: jQuery( '.emm-item' ).length,
		pageToken: jQuery( '#emm-item-youtube-all-0 #next-page' ).val(),
	};

	// get new data of the Youtube API
	new_youtube_videos( params );
}

function new_youtube_videos( params ){
	var youtube_endpoint  = 'https://www.googleapis.com/youtube/v3/search/?',
		youtube_video = 'http://www.youtube.com/watch?v=',
		query_string = [ 'type=video', 'part=snippet' ],
		base_index = params.startIndex,
		json_videos = '';

	for (var key in params) {
		var value = params[key];
		query_string.push( key + '=' + value );
	}

	jQuery.getJSON(
		youtube_endpoint + query_string.join( '&' ),
		function( data ){
			var items = jQuery( data.items );
			items.each( function( i, item ){
				var html = '<li class="emm-item attachment"><div id="emm-item-youtube-all-' + ( base_index + i ) + '" class="emm-item-area emm-item-youtube" data-id="' + item.id.videoId + '"> <div class="emm-item-container clearfix"> <div class="emm-item-thumb">';
				html += '<img src="' + item.snippet.thumbnails.medium.url + '">';
				html += '</div>';
				html += '<div class="emm-item-main">';
				html += '<div class="emm-item-content">' + item.snippet.title + '</div>';
				html += '<div class="emm-item-channel"> by ' + item.snippet.channelTitle + ' </div>';
				html += '<div class="emm-item-date">' + item.snippet.publishedAt + '</div>';
				html += '</div>';
				html += '</div>';
				html += '</div>';
				html += '<a href="#" id="emm-check-' + item.id.videoId + '" data-id="' + item.id.videoId + '" class="check" title="Deselect">';
				html += '<div class="media-modal-icon"></div>';
				html += '</a></li>';

				jQuery( jQuery( 'ul.emm-items' ) [0] ).append(html);

			} );
			jQuery('#emm-item-youtube-all-0 #next-page').val( data.nextPageToken );
		}
	);
}
