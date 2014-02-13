/**
 * This js is going to handle the infinite scroll for the YouTube service in the
 * MEXP plugin
 * */

var toolbarView = wp.media.view.Toolbar.MEXP,
	mexpContentView = wp.media.view.MEXP,
	flagAjaxExecutions = '',
	isInfiniteScroll = false;

wp.media.view.MEXP = mexpContentView.extend({

	initialize: function() {
		mexpContentView.prototype.initialize.apply( this, arguments );
	},

	render: function() {
		var selection = this.getSelection(),
			_this = this;

		if ( this.collection && this.collection.models.length ) {
			var container = document.createDocumentFragment();

			if ( isInfiniteScroll ) {
				this.collection.each( function( model, index ) {
					// This makes the collection to render only the last 18 items of
					// it, instead of all. Tweak for the infinite scroll to work ok
					if ( index >= this.collection.length - 18 && isInfiniteScroll )
						container.appendChild( this.renderItem( model ) );
				}, this );
			} else {
				this.collection.each( function( model, index ) {
					container.appendChild( this.renderItem( model ) );
				}, this );
			}

			this.$el.find( '.mexp-items' ).append( container );

		}

		selection.each( function( model ) {
			var id = '#mexp-item-' + this.service.id + '-' + this.tab + '-' + model.get( 'id' );
			this.$el.find( id ).closest( '.mexp-item' ).addClass( 'selected details' );
		}, this );

		jQuery( '#mexp-button' ).prop( 'disabled', !selection.length );

		// Infinite scrolling for youtube results
		jQuery( '.mexp-content-youtube ul.mexp-items' ).scroll( function() {
			var $container = jQuery( 'ul.mexp-items' ),
				totalHeight = $container.get( 0 ).scrollHeight,
				position = $container.height() + $container.scrollTop(),
				offset = ( totalHeight / 100 ) * 30;

			if( totalHeight - position <= offset ) {
				_this.fetchItems.apply( _this, [ jQuery( '.tab-all #page_token' ).val() ] );
			}
		} );

		return this;
	},

	fetchItems: function( pageToken ) {

		if ( this.service.id !== 'youtube' ) {
			mexpContentView.prototype.fetchItems.apply( this, arguments );
			return;
		}

		// This if-else block handles the concurrency for not calling to the
		// same set of videos several times.
		if ( "youtube" === this.service.id && pageToken && pageToken === flagAjaxExecutions )
			return;
		else
			flagAjaxExecutions = pageToken;

		this.trigger( 'loading' );

		var params = this.model.get( 'params' );

		if ( undefined === pageToken ) {
			isInfiniteScroll = false;
			jQuery( '.tab-all #page_token' ).val( '' );
			params.page_token = '';
		} else {
			isInfiniteScroll = true;
		}

		params.startIndex = jQuery( '.mexp-item' ).length;

		var data = {
			_nonce  : mexp._nonce,
			service : this.service.id,
			params  : params,
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

		var _this = this;

		if ( this.service.id !== 'youtube' ) {
			mexpContentView.prototype.fetchedSuccess.apply( this, arguments );
			return;
		}

		if ( !this.model.get( 'page' ) ) {

			if ( !response.items ) {
				this.fetchedEmpty( response );
				return;
			}

			if ( response.meta.page_token ) {
				var params = this.model.get( 'params' );

				if ( this.tab == 'all' )
					jQuery( '.tab-all #page_token' ).val( response.meta.page_token );
				if ( this.tab == 'by_user' )
					jQuery( '.tab-by_user #page_token' ).val( response.meta.page_token );

				if ( params.page_token !== response.meta.page_token ) {
					params.page_token = response.meta.page_token;
					this.model.set( 'params', params );
				}
			}

			this.model.set( 'min_id', response.meta.min_id );
			this.model.set( 'items',  response.items );

			// Append the last elements to the collection.
			if ( isInfiniteScroll ) {
				_.each( response.items, function( item ) {
					_this.collection.add( item );
				} );
				this.collection.reset( this.collection.models );
			} else {
				this.collection.reset( response.items );
			}


		} else {

			if ( !response.items ) {
				this.moreEmpty( response );
				return;
			}

			if ( response.meta.page_token ) {
				var params = this.model.get( 'params' );

				if ( this.tab == 'all' )
					jQuery( '.tab-all #page_token' ).val( response.meta.page_token );
				if ( this.tab == 'by_user' )
					jQuery( '.tab-by_user #page_token' ).val( response.meta.page_token );

				if ( params.page_token !== response.meta.page_token ) {
					params.page_token = response.meta.page_token;
					this.model.set( 'params', params );
				}
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

		this.trigger( 'loaded loaded:success', response );

	},

	fetchedError: function(response) {
		mexpContentView.prototype.fetchedError.apply( this, arguments );
	},

	fetchedEmpty: function() {
		mexpContentView.prototype.fetchedEmpty.apply( this, arguments );
	},
	
	loading: function() {
		mexpContentView.prototype.loading.apply( this, arguments );

		if ( 'youtube' !== this.service.id ) return;

		// show bottom spinner
		jQuery( '.spinner-bottom' ).show();
	},
	
	loaded: function() {
		mexpContentView.prototype.loaded.apply( this, arguments );

		if ( 'youtube' !== this.service.id ) return;

		// hide bottom spinner
		jQuery( '.spinner-bottom' ).hide();
	},
});

wp.media.view.Toolbar.MEXP = toolbarView.extend({

	initialize: function() {

		toolbarView.prototype.initialize.apply( this, arguments );

		this.set( 'spinner', new wp.Backbone.View({
			tagName: 'span',
			className: 'spinner spinner-bottom',
			priority: -20,
		}) );

	}
});
