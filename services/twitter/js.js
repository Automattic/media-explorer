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

var emm_twitter_location_js_loaded = false;
var pf = wp.media.view.MediaFrame.Post;

jQuery( function( $ ) {	

	wp.media.view.MediaFrame.Post = pf.extend({

		initialize: function() {

			pf.prototype.initialize.apply( this, arguments );

			this.on( 'content:render:emm-service-twitter-content-location', function() {

				if ( !emm_twitter_location_js_loaded ) {

					var script = document.createElement("script");
					script.type = "text/javascript";
					script.src = emm.services.twitter.labels.gmaps_url + '?sensor=false&callback=emm_twitter_location_initialize';
					document.body.appendChild(script);

				} else {

					emm_twitter_location_initialize();

				}

			} );

		}

	});

});

function emm_twitter_location_initialize() {

	emm_twitter_location_js_loaded = true;

	if ( navigator.geolocation )
		navigator.geolocation.getCurrentPosition( emm_twitter_location_load );

}

function emm_twitter_location_load( position ) {

	var lat, lng, loc, radius;
	var pf = window.wp.media.view.MediaFrame.Post;

	if ( loc = jQuery('#emm-twitter-search-location').val() ) {
		ll = loc.split( ',' );
		lat = ll[0];
		lng = ll[1];
	} else {
		lat = position.coords.latitude;
		lng = position.coords.longitude;
		jQuery('#emm-twitter-search-location').val( lat + ',' + lng );
	}

	radius = jQuery('#emm-twitter-search-location-radius').val();

	var center = new google.maps.LatLng( lat, lng );
	var myOptions = {
		center    : center,
		zoom      : 11,
		mapTypeId : google.maps.MapTypeId.ROADMAP,
		//scrollwheel: false,
		streetViewControl: false
	};
	var map = new google.maps.Map(document.getElementById("emm_twitter_map_canvas"), myOptions);
	var marker = new google.maps.Marker({
		position  : new google.maps.LatLng( lat, lng ),
		draggable : true,
		map       : map
	});
	var circle = new google.maps.Circle({
	  map: map,
	  radius: ( radius * 1000 ), // metres
	  strokeWeight: 1,
	  fillColor: 'blue',
	  fillOpacity: 0.15,
	  strokeColor: '#fff'
	});
	circle.bindTo( 'center', marker, 'position' );

	jQuery('#emm-twitter-search-location-radius').change(function(){
		circle.setRadius( jQuery(this).val() * 1000 );
	});
	/*pf.model.on( 'change:params', function() {
		circle.setRadius( this.model.get('params').radius * 1000 );
	}, pf );*/

	google.maps.event.addListener(marker, 'dragend', function() {
		p = marker.getPosition();
		map.panTo( p );
		jQuery('#emm-twitter-search-location').val( p.lat() + ',' + p.lng() ).trigger('change');
	});

}
