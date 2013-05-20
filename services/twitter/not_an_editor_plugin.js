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

(function() {

	tinymce.create( 'tinymce.plugins.emm_twitter', {

		init : function(ed, url) {
			var t = this;

			ed.onBeforeSetContent.add(function(ed, o) {
				o.content = o.content.replace(/\[gallery([^\]]*)\]/g, function(a,b){
					return '<img src="'+emm.base_url+'/services/twitter/overlay.png" class="wp-gallery mceItem" title="gallery'+tinymce.DOM.encode(b)+'" />';
				});
			});

			ed.onPostProcess.add(function(ed, o) {
				if (o.get) {
					o.content = o.content.replace(/(?:<p[^>]*>)*(<img[^>]+>)(?:<\/p>)*/g, function(a,im) {
						var cls = getAttr(im, 'class');

						if ( cls.indexOf('wp-gallery') != -1 )
							return '<p>['+tinymce.trim(getAttr(im, 'title'))+']</p>';

						return a;
					});
				}
			});

		},

		getInfo : function() {
			return {
				longname  : 'Extended Media Manager: Twitter',
				author    : 'Code For The People',
				authorurl : 'http://codeforthepeople.com/',
				infourl   : '',
				version   : '1.0'
			};
		}
	});

	tinymce.PluginManager.add( 'emm_twitter', tinymce.plugins.emm_twitter );

})();
