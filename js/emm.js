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

var media = wp.media;

// VIEW: MEDIA ITEM:

media.view.EMMItem = Backbone.View.extend({

    tagName   : 'li',
    className : 'emm-item attachment',

    render: function() {

    	this.template = media.template( 'emm-' + this.options.service.id + '-item-' + this.options.tab );
       	this.$el.html( this.template( this.model.toJSON() ) );

        return this;

    }

});

// VIEW - BOTTOM TOOLBAR

media.view.Toolbar.EMM = media.view.Toolbar.extend({

	initialize: function() {

		_.defaults( this.options, {
		    event : 'inserter',
		    close : false,
			items : {
			    // See wp.media.view.Button
			    inserter     : {
			        id       : 'emm-button',
			        style    : 'primary',
			        text     : emm.labels.insert,
			        priority : 80,
			        click    : function() {
					    this.controller.state().emmInsert();
					}
			    }
			}
		});

		media.view.Toolbar.prototype.initialize.apply( this, arguments );

	},

	refresh: function() {

		var selection = this.controller.state().props.get( '_all' ).get( 'selection' );

		// @TODO i think this is redundant
		this.get( 'inserter' ).model.set( 'disabled', !selection.length );

		media.view.Toolbar.prototype.refresh.apply( this, arguments );

	}

});

// VIEW - MEDIA CONTENT AREA

media.view.EMM = media.View.extend({

	events: {
		'click .emm-item-area'     : 'toggleSelectionHandler',
		'click .emm-item .check'   : 'removeSelectionHandler',
		'click .emm-pagination a'  : 'paginate',
		'submit .emm-toolbar form' : 'updateInput'
	},

	initialize: function() {

		/* fired when you switch router tabs */

		this.collection = new Backbone.Collection();
		this.service    = this.options.service;
		this.tab        = this.options.tab;

		this.createToolbar();
		this.clearItems();

		if ( this.model.get( 'items' ) ) {

			this.collection = new Backbone.Collection();
			this.collection.reset( this.model.get( 'items' ) );

		}

		// @TODO do this somewhere else:
		// @TODO this gets reverted anyway when the button model's disabled state changes. look into it.
		//jQuery( '#emm-button' ).text( this.service.labels.insert );

		this.collection.on( 'reset', this.render, this );

		this.model.on( 'change:params', this.changedParams, this );

		this.on( 'loading',       this.loading, this );
		this.on( 'loaded',        this.loaded, this );
		this.on( 'change:params', this.changedParams, this );
		this.on( 'change:page',   this.changedPage, this );

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

			this.$el.find( '.emm-items' ).append( container );

		}

		selection.each( function( model ) {
			var id = '#emm-item-' + this.service.id + '-' + this.tab + '-' + model.get( 'id' );
			this.$el.find( id ).closest( '.emm-item' ).addClass( 'selected details' );
		}, this );

		jQuery( '#emm-button' ).prop( 'disabled', !selection.length );

		return this;

	},

	renderItem : function( model ) {

		var view = new media.view.EMMItem({
			model   : model,
			service : this.service,
			tab     : this.tab
		});

		return view.render().el;

	},

	createToolbar: function() {

		// @TODO this could be a separate view:
		html = '<div class="emm-error attachments"></div>';
		this.$el.prepend( html );

		// @TODO this could be a separate view:
		html = '<div class="emm-empty attachments"></div>';
		this.$el.prepend( html );

		// @TODO this could be a separate view:
		html = '<ul class="emm-items attachments clearfix"></ul>';
		this.$el.append( html );

		// @TODO this could be a separate view:
		var toolbar_template = media.template( 'emm-' + this.service.id + '-search-' + this.tab );
		html = '<div class="emm-toolbar media-toolbar clearfix">' + toolbar_template( this.model.toJSON() ) + '</div>';
		this.$el.prepend( html );

		// @TODO this could be a separate view:
		html = '<div class="emm-pagination clearfix"><a href="#" class="button button-secondary button-large">' + this.service.labels.loadmore + '</a><div class="spinner"></div></div>';
		this.$el.append( html );

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

		target.closest( '.emm-item' ).addClass( 'selected details' );

		this.getSelection().add( this.collection._byId[id] );

		// @TODO why isn't this triggered by the above line?
		this.controller.state().props.trigger( 'change:selection' );

	},

	removeFromSelection: function( target, id ) {

		target.closest( '.emm-item' ).removeClass( 'selected details' );

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

		this.$el.find( '.emm-item' ).removeClass( 'selected details' );
		this.$el.find( '.emm-items' ).empty();
		this.$el.find( '.emm-pagination' ).hide();

	},

	loading: function() {

		// show spinner
		this.$el.find( '.spinner' ).show();

		// hide messages
		this.$el.find( '.emm-error' ).hide().text('');
		this.$el.find( '.emm-empty' ).hide().text('');

	},

	loaded: function( response ) {

		// hide spinner
		this.$el.find( '.spinner' ).hide();

	},

	fetchItems: function() {

		this.trigger( 'loading' );

		var data = {
			_nonce  : emm._nonce,
			service : this.service.id,
			tab     : this.tab,
			params  : this.model.get( 'params' ),
			page    : this.model.get( 'page' ),
			max_id  : this.model.get( 'max_id' )
		};

		media.ajax( 'emm_request', {
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

			this.$el.find( '.emm-items' ).append( container );

		}

		this.$el.find( '.emm-pagination' ).show();

		this.model.set( 'max_id', response.meta.max_id );

		this.trigger( 'loaded loaded:success', response );

	},

	fetchedEmpty: function( response ) {

		this.$el.find( '.emm-empty' ).text( this.service.labels.noresults ).show();
		this.$el.find( '.emm-pagination' ).hide();

		this.trigger( 'loaded loaded:noresults', response );

	},

	fetchedError: function( response ) {

		this.$el.find( '.emm-error' ).text( response.error_message ).show();

		this.trigger( 'loaded loaded:error', response );

	},

	updateInput: function( event ) {

		// triggered when a search is submitted

		var params = this.model.get( 'params' );
		var els = this.$el.find( '.emm-toolbar' ).find( ':input' ).each( function( k, el ) {
			var n = jQuery(this).attr('name');
			if ( n )
				params[n] = jQuery(this).val();
		} );

		this.model.set( 'params', params );
		this.trigger( 'change:params' ); // why isn't this triggering automatically? might be because params is an object

		event.preventDefault();

	},

	paginate : function( event ) {

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

		_.each( emm.services, function( service, service_id ) {

			var id = 'emm-service-' + service.id;
			var controller = {
				id      : id,
				router  : id + '-router',
				toolbar : id + '-toolbar',
				menu    : 'default',
				title   : service.labels.title,
				tabs    : service.tabs,
				priority: 100 // places it above Insert From URL
			};

			for ( tab in service.tabs ) {

				// Content
				this.on( 'content:render:' + id + '-content-' + tab, _.bind( this.emmContentRender, this, service, tab ) );

				// Set the default tab
				if ( service.tabs[tab].defaultTab )
					controller.content = id + '-content-' + tab;

			}

			this.states.add([
				new media.controller.EMM( controller )
			]);

			// Tabs
			this.on( 'router:create:' + id + '-router', this.createRouter, this );
			this.on( 'router:render:' + id + '-router', _.bind( this.emmRouterRender, this, service ) );

			// Toolbar
			this.on( 'toolbar:create:' + id + '-toolbar', this.emmToolbarCreate, this );
			//this.on( 'toolbar:render:' + id + '-toolbar', _.bind( this.emmToolbarRender, this, service ) );

		}, this );

	},

	emmRouterRender : function( service, view ) {

		var id   = 'emm-service-' + service.id;
		var tabs = {};

		for ( tab in service.tabs ) {
			tab_id = id + '-content-' + tab;
			tabs[tab_id] = {
				text : service.tabs[tab].text
			};
		}

		view.set( tabs );

	},

	emmToolbarRender : function( service, view ) {

		view.set( 'selection', new media.view.Selection.EMM({
			service    : service,
			controller : this,
			collection : this.state().props.get('_all').get('selection'),
			priority   : -40
		}).render() );

	},

	emmContentRender : function( service, tab ) {

		/* called when a tab becomes active */

		this.content.set( new media.view.EMM( {
			service    : service,
			controller : this,
			model      : this.state().props.get( tab ),
			tab        : tab,
			className  : 'clearfix attachments-browser emm-content emm-content-' + service.id + ' emm-content-' + service.id + '-' + tab
		} ) );

	},

	emmToolbarCreate : function( toolbar ) {

		toolbar.view = new media.view.Toolbar.EMM( {
			controller : this
		} );

	}

});

// CONTROLLER:

media.controller.EMM = media.controller.State.extend({

	initialize: function( options ) {

		this.props = new Backbone.Collection();

		for ( tab in options.tabs ) {

			this.props.add( new Backbone.Model({
				id     : tab,
				params : {},
				page   : null,
				min_id : null,
				max_id : null
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

	emmInsert: function() {

		var insert    = '';
		var selection = this.frame.content.get().getSelection();

		selection.each( function( model ) {
			insert += '<p>' + model.get( 'url' ) + '</p>';
		}, this );

		media.editor.insert( insert );
		selection.reset();
		this.frame.close();

	}

});
