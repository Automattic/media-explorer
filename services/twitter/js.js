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

var emm_twitter_location_js_loaded = false;
var emm_twitter_location_map       = null;
var emm_twitter_location_marker    = null;
var emm_twitter_location_timeout   = null;
var pf = wp.media.view.MediaFrame.Post;

jQuery( function( $ ) {

	wp.media.view.MediaFrame.Post = pf.extend({

		initialize: function() {

			pf.prototype.initialize.apply( this, arguments );

			this.on( 'content:render:emm-service-twitter-content-location', _.bind( function() {

				this.state().frame.content.get().on( 'loaded', function( response ) {

					if ( ! response || ! response.meta || ! response.meta.coords )
						return;

					var ll = new google.maps.LatLng( response.meta.coords.lat, response.meta.coords.lng );

					emm_twitter_location_marker.setPosition( ll );
					emm_twitter_location_map.panTo( ll );

				} );

				if ( !emm_twitter_location_js_loaded ) {

					$('#emm_twitter_map_canvas').css( 'background-image', 'url(' + emm.admin_url + '/images/wpspin_light.gif)');

					var script = document.createElement("script");
					script.type = "text/javascript";
					script.src = emm.services.twitter.labels.gmaps_url + '?sensor=false&callback=emm_twitter_location_initialize';
					document.body.appendChild(script);

				} else {

					emm_twitter_location_initialize();

				}

			}, this ) );

		}

	});

});

function emm_twitter_location_initialize() {

	var callback = function() {
		emm_twitter_location_fetch( emm_twitter_location_load );
	};

	if ( navigator.geolocation ) {
		navigator.geolocation.getCurrentPosition( emm_twitter_location_load, callback );
		emm_twitter_location_timeout = window.setTimeout( callback, 8000 )
	} else {
		emm_twitter_location_fetch( callback );
	}

	emm_twitter_location_js_loaded = true;

}

function emm_twitter_location_fetch( callback ) {

	callback( {
		coords : google.loader.ClientLocation
	} );

}

function emm_twitter_location_load( position ) {

	var lat, lng, loc, radius;
	$ = jQuery;

	window.clearTimeout( emm_twitter_location_timeout );

	// Enable the visual refresh
	google.maps.visualRefresh = true;

	if ( loc = $('#emm-twitter-search-location-coords').val() ) {
		ll = loc.split( ',' );
		lat = ll[0];
		lng = ll[1];
	} else {
		lat = position.coords.latitude;
		lng = position.coords.longitude;
		$('#emm-twitter-search-location-coords').val( lat + ',' + lng );
	}

	var radius = $('#emm-twitter-search-location-radius').val();
	var mapOptions = {
		center            : new google.maps.LatLng( lat, lng ),
		zoom              : 10,
		mapTypeId         : google.maps.MapTypeId.ROADMAP,
		mapTypeControl    : false,
		streetViewControl : false
	};
	emm_twitter_location_map = new google.maps.Map( document.getElementById( 'emm_twitter_map_canvas' ), mapOptions );
	emm_twitter_location_marker = new google.maps.Marker({
		position  : new google.maps.LatLng( lat, lng ),
		draggable : true,
		map       : emm_twitter_location_map
	});
	var circle = new google.maps.Circle({
		map          : emm_twitter_location_map,
		radius       : ( radius * 1000 ), // metres
		strokeWeight : 1,
		fillColor    : 'blue',
		fillOpacity  : 0.15,
		strokeColor  : '#fff'
	});
	circle.bindTo( 'center', emm_twitter_location_marker, 'position' );

	$('#emm-twitter-search-location-radius').on('change',function(){
		circle.setRadius( $(this).val() * 1000 );
	});
	$('#emm-twitter-search-location-name').on('change',function(){
		$('#emm-twitter-search-location-coords').val('');
	});

	google.maps.event.addListener(emm_twitter_location_marker, 'dragend', function() {
		p = emm_twitter_location_marker.getPosition();
		emm_twitter_location_map.panTo( p );
		$('#emm-twitter-search-location-coords').val( p.lat() + ',' + p.lng() ).closest('form').submit();
	});

}
