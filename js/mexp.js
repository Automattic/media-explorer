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

var media = wp.media;

// VIEW: MEDIA ITEM:

media.view.MEXPItem = wp.Backbone.View.extend({

    tagName   : 'li',
    className : 'mexp-item attachment',

    render: function() {

    	this.template = media.template( 'mexp-' + this.options.service.id + '-item-' + this.options.tab );
       	this.$el.html( this.template( this.model.toJSON() ) );

        return this;

    }

});

// VIEW - BOTTOM TOOLBAR

media.view.Toolbar.MEXP = media.view.Toolbar.extend({

	initialize: function() {

		_.defaults( this.options, {
		    event : 'inserter',
		    close : false,
			items : {
			    // See wp.media.view.Button
			    inserter     : {
			        id       : 'mexp-button',
			        style    : 'primary',
			        text     : mexp.labels.insert,
			        priority : 80,
			        click    : function() {
					    this.controller.state().mexpInsert();
					}
			    }
			}
		});

		media.view.Toolbar.prototype.initialize.apply( this, arguments );

		var serviceName = this.controller.state().id.replace( /mexp-service-/g, '');

		this.set( 'pagination', new media.view.Button({
			tagName: 'button',
			classes: 'mexp-pagination button button-secondary',
			id: serviceName + '-loadmore',
			text: mexp.labels.loadmore,
			priority: -20,
		}) );
	},

	refresh: function() {

		var selection = this.controller.state().props.get( '_all' ).get( 'selection' );

		// @TODO i think this is redundant
		this.get( 'inserter' ).model.set( 'disabled', !selection.length );

		media.view.Toolbar.prototype.refresh.apply( this, arguments );

	}

});

// VIEW - MEDIA CONTENT AREA

media.view.MEXP = media.View.extend({

	events: {
		'click .mexp-item-area'     : 'toggleSelectionHandler',
		'click .mexp-item .check'   : 'removeSelectionHandler',
		'submit .mexp-toolbar form' : 'updateInput'
	},

	initialize: function() {

		/* fired when you switch router tabs */
		
		var _this = this;

		this.collection = new Backbone.Collection();
		this.service    = this.options.service;
		this.tab        = this.options.tab;

		this.createToolbar();
		this.clearItems();

		if ( this.model.get( 'items' ) ) {

			this.collection = new Backbone.Collection();
			this.collection.reset( this.model.get( 'items' ) );

			jQuery( '#' + this.service.id + '-loadmore' ).attr( 'disabled', false ).show();
		} else {
			jQuery( '#' + this.service.id + '-loadmore' ).hide();
		}

		// @TODO do this somewhere else:
		// @TODO this gets reverted anyway when the button model's disabled state changes. look into it.
		//jQuery( '#mexp-button' ).text( this.service.labels.insert );

		this.collection.on( 'reset', this.render, this );

		this.model.on( 'change:params', this.changedParams, this );

		this.on( 'loading',       this.loading, this );
		this.on( 'loaded',        this.loaded, this );
		this.on( 'change:params', this.changedParams, this );
		this.on( 'change:page',   this.changedPage, this );

		jQuery( '.mexp-pagination' ).click( function( event ) {
			_this.paginate( event );
		} );
		
		if ( _this.model.get( 'fetchOnRender' ) ) {
			_this.model.set( 'fetchOnRender', false );
			_this.fetchItems();
		}

	},

	render: function() {

		/* fired when you switch router tabs */

		var selection = this.getSelection();

		if ( this.collection && this.collection.models.length ) {

			this.clearItems();

			var container = document.createDocumentFragment();

			this.collection.each( function( model ) {
				container.appendChild( this.renderItem( model ) );
			}, this );

			this.$el.find( '.mexp-items' ).append( container );

		}

		selection.each( function( model ) {
			var id = '#mexp-item-' + this.service.id + '-' + this.tab + '-' + model.get( 'id' );
			this.$el.find( id ).closest( '.mexp-item' ).addClass( 'selected details' );
		}, this );

		jQuery( '#mexp-button' ).prop( 'disabled', !selection.length );

		return this;

	},

	renderItem : function( model ) {

		var view = new media.view.MEXPItem({
			model   : model,
			service : this.service,
			tab     : this.tab
		});

		return view.render().el;

	},

	createToolbar: function() {

		// @TODO this could be a separate view:
		html = '<div class="mexp-error attachments"></div>';
		this.$el.prepend( html );

		// @TODO this could be a separate view:
		html = '<div class="mexp-empty attachments"></div>';
		this.$el.prepend( html );

		// @TODO this could be a separate view:
		html = '<ul class="mexp-items attachments clearfix"></ul>';
		this.$el.append( html );

		// @TODO this could be a separate view:
		var toolbar_template = media.template( 'mexp-' + this.service.id + '-search-' + this.tab );
		html = '<div class="mexp-toolbar media-toolbar clearfix">' + toolbar_template( this.model.toJSON() ) + '</div>';
		this.$el.prepend( html );

	},

	removeSelectionHandler: function( event ) {

		var target = jQuery( '#' + event.currentTarget.id );
		var id     = target.attr( 'data-id' );

		this.removeFromSelection( target, id );

		event.preventDefault();

	},

	toggleSelectionHandler: function( event ) {

		if ( event.target.href )
			return;

		var target = jQuery( '#' + event.currentTarget.id );
		var id     = target.attr( 'data-id' );

		if ( this.getSelection().get( id ) )
			this.removeFromSelection( target, id );
		else
			this.addToSelection( target, id );

	},

	addToSelection: function( target, id ) {

		target.closest( '.mexp-item' ).addClass( 'selected details' );

		this.getSelection().add( this.collection._byId[id] );

		// @TODO why isn't this triggered by the above line?
		this.controller.state().props.trigger( 'change:selection' );

	},

	removeFromSelection: function( target, id ) {

		target.closest( '.mexp-item' ).removeClass( 'selected details' );

		this.getSelection().remove( this.collection._byId[id] );

		// @TODO why isn't this triggered by the above line?
		this.controller.state().props.trigger( 'change:selection' );

	},

	clearSelection: function() {
		this.getSelection().reset();
	},

	getSelection : function() {
		return this.controller.state().props.get( '_all' ).get( 'selection' );
	},

	clearItems: function() {

		this.$el.find( '.mexp-item' ).removeClass( 'selected details' );
		this.$el.find( '.mexp-items' ).empty();
		this.$el.find( '.mexp-pagination' ).hide();

	},

	loading: function() {

		// show spinner
		this.$el.find( '.spinner' ).addClass( 'is-active' );

		// hide messages
		this.$el.find( '.mexp-error' ).hide().text('');
		this.$el.find( '.mexp-empty' ).hide().text('');

		// disable 'load more' button
		jQuery( '#' + this.service.id + '-loadmore' ).attr( 'disabled', true );
	},

	loaded: function( response ) {

		// hide spinner
		this.$el.find( '.spinner' ).removeClass( 'is-active' );

	},

	fetchItems: function() {

		this.trigger( 'loading' );

		var data = {
			_nonce  : mexp._nonce,
			service : this.service.id,
			tab     : this.tab,
			params  : this.model.get( 'params' ),
			page    : this.model.get( 'page' ),
			max_id  : this.model.get( 'max_id' )
		};

		media.ajax( 'mexp_request', {
			context : this,
			success : this.fetchedSuccess,
			error   : this.fetchedError,
			data    : data
		} );

	},

	fetchedSuccess: function( response ) {

		if ( !this.model.get( 'page' ) ) {

			if ( !response.items ) {
				this.fetchedEmpty( response );
				return;
			}

			this.model.set( 'min_id', response.meta.min_id );
			this.model.set( 'items',  response.items );

			this.collection.reset( response.items );

		} else {

			if ( !response.items ) {
				this.moreEmpty( response );
				return;
			}

			this.model.set( 'items', this.model.get( 'items' ).concat( response.items ) );

			var collection = new Backbone.Collection( response.items );
			var container  = document.createDocumentFragment();

			this.collection.add( collection.models );

			collection.each( function( model ) {
				container.appendChild( this.renderItem( model ) );
			}, this );

			this.$el.find( '.mexp-items' ).append( container );

		}

		jQuery( '#' + this.service.id + '-loadmore' ).attr( 'disabled', false ).show();
		this.model.set( 'max_id', response.meta.max_id );

		this.trigger( 'loaded loaded:success', response );

	},

	fetchedEmpty: function( response ) {

		this.$el.find( '.mexp-empty' ).text( this.service.labels.noresults ).show();
		this.$el.find( '.mexp-pagination' ).hide();

		this.trigger( 'loaded loaded:noresults', response );

	},

	fetchedError: function( response ) {

		this.$el.find( '.mexp-error' ).text( response.error_message ).show();
		jQuery( '#' + this.service.id + '-loadmore' ).attr( 'disabled', false ).show();
		this.trigger( 'loaded loaded:error', response );

	},

	updateInput: function( event ) {

		// triggered when a search is submitted

		var params = this.model.get( 'params' );
		var els = this.$el.find( '.mexp-toolbar' ).find( ':input' ).each( function( k, el ) {
			var n = jQuery(this).attr('name');
			if ( n )
				params[n] = jQuery(this).val();
		} );
		
		this.clearSelection();
		jQuery( '#mexp-button' ).attr( 'disabled', 'disabled' );
		this.model.set( 'params', params );
		this.trigger( 'change:params' ); // why isn't this triggering automatically? might be because params is an object

		event.preventDefault();

	},

	paginate : function( event ) {

		if( 0 == this.collection.length )
			return;

		var page = this.model.get( 'page' ) || 1;

		this.model.set( 'page', page + 1 );
		this.trigger( 'change:page' );

		event.preventDefault();

	},

	changedPage: function() {

		// triggered when the pagination is changed

		this.fetchItems();

	},

	changedParams: function() {

		// triggered when the search parameters are changed

		this.model.set( 'page',   null );
		this.model.set( 'min_id', null );
		this.model.set( 'max_id', null );

		this.clearItems();
		this.fetchItems();

	}

});

// VIEW - MEDIA FRAME (MENU BAR)	

var post_frame = media.view.MediaFrame.Post;

media.view.MediaFrame.Post = post_frame.extend({

	initialize: function() {

		post_frame.prototype.initialize.apply( this, arguments );

		_.each( mexp.services, function( service, service_id ) {

			var id = 'mexp-service-' + service.id;
			var controller = {
				id      : id,
				router  : id + '-router',
				toolbar : id + '-toolbar',
				menu    : 'default',
				title   : service.labels.title,
				tabs    : service.tabs,
				priority: 100 // places it above Insert From URL
			};

			for ( var tab in service.tabs ) {

				// Content
				this.on( 'content:render:' + id + '-content-' + tab, _.bind( this.mexpContentRender, this, service, tab ) );

				// Set the default tab
				if ( service.tabs[tab].defaultTab )
					controller.content = id + '-content-' + tab;

			}

			this.states.add([
				new media.controller.MEXP( controller )
			]);

			// Tabs
			this.on( 'router:create:' + id + '-router', this.createRouter, this );
			this.on( 'router:render:' + id + '-router', _.bind( this.mexpRouterRender, this, service ) );

			// Toolbar
			this.on( 'toolbar:create:' + id + '-toolbar', this.mexpToolbarCreate, this );
			//this.on( 'toolbar:render:' + id + '-toolbar', _.bind( this.mexpToolbarRender, this, service ) );

		}, this );

	},

	mexpRouterRender : function( service, view ) {

		var id   = 'mexp-service-' + service.id;
		var tabs = {};

		for ( var tab in service.tabs ) {
			tab_id = id + '-content-' + tab;
			tabs[tab_id] = {
				text : service.tabs[tab].text
			};
		}

		view.set( tabs );

	},

	mexpToolbarRender : function( service, view ) {

		view.set( 'selection', new media.view.Selection.MEXP({
			service    : service,
			controller : this,
			collection : this.state().props.get('_all').get('selection'),
			priority   : -40
		}).render() );

	},

	mexpContentRender : function( service, tab ) {

		/* called when a tab becomes active */

		this.content.set( new media.view.MEXP( {
			service    : service,
			controller : this,
			model      : this.state().props.get( tab ),
			tab        : tab,
			className  : 'clearfix attachments-browser mexp-content mexp-content-' + service.id + ' mexp-content-' + service.id + '-' + tab
		} ) );

	},

	mexpToolbarCreate : function( toolbar ) {

		toolbar.view = new media.view.Toolbar.MEXP( {
			controller : this
		} );

	}

});

// CONTROLLER:

media.controller.MEXP = media.controller.State.extend({

	initialize: function( options ) {

		this.props = new Backbone.Collection();

		for ( var tab in options.tabs ) {

			this.props.add( new Backbone.Model({
				id     : tab,
				params : {},
				page   : null,
				min_id : null,
				max_id : null,
				fetchOnRender : options.tabs[ tab ].fetchOnRender,
			}) );

		}

		this.props.add( new Backbone.Model({
			id        : '_all',
			selection : new Backbone.Collection()
		}) );

		this.props.on( 'change:selection', this.refresh, this );

	},

	refresh: function() {
		this.frame.toolbar.get().refresh();
	},

	mexpInsert: function() {

		var selection = this.frame.content.get().getSelection(),
		urls          = [];

		selection.each( function( model ) {
			urls.push( model.get( 'url' ) );
		}, this );

		if ( typeof(tinymce) === 'undefined' || tinymce.activeEditor === null || tinymce.activeEditor.isHidden() ) {
			media.editor.insert( _.toArray( urls ).join( "\n\n" ) );
		} else {
			media.editor.insert( "<p>" + _.toArray( urls ).join( "</p><p>" ) + "</p>" );
		}

		selection.reset();
		this.frame.close();

	}

});
