/**
 * This js is going to handle the infinite scroll for the YouTube service in the
 * EMM plugin
 * */

var emmContentView = wp.media.view.EMM
	flagAjaxExecutions = '';

wp.media.view.EMM = emmContentView.extend({

	initialize: function() {
		emmContentView.prototype.initialize.apply( this, arguments );
	},

	render: function() {
		var selection = this.getSelection(),
			_this = this;

		if ( this.collection && this.collection.models.length ) {

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

		// Infinite scrolling for youtube results
		jQuery( '.emm-content-youtube ul.emm-items' ).scroll( function() {
			var $container = jQuery( 'ul.emm-items' ),
				totalHeight = $container.get( 0 ).scrollHeight,
				position = $container.height() + $container.scrollTop(),
				offset = ( totalHeight / 100 ) * 30;

			// only fires when the position of the scrolled window is at the bottom
			// This is compared to 15 instead of 0 because of the padding-top of the
			// <ul>
			if( totalHeight - position <= offset ) {
				_this.fetchItems.apply( _this );
			}
		} );

		return this;
	},

	fetchItems: function() {

		if ( this.service.id !== 'youtube' ) {
			emmContentView.prototype.fetchItems.apply( this, arguments );
			return;
		}

		this.trigger( 'loading' );

		var params = this.model.get( 'params' );

		params.startIndex = jQuery( '.emm-item' ).length;

		var data = {
			_nonce  : emm._nonce,
			service : this.service.id,
			params  : params,
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
		if ( this.service.id !== 'youtube' ) {
			emmContentView.prototype.fetchedSuccess.apply( this, arguments );
			return;
		}

		if ( !this.model.get( 'page' ) ) {

			if ( !response.items ) {
				this.fetchedEmpty( response );
				return;
			}

			if ( flagAjaxExecutions !== response.meta.page_token )
				flagAjaxExecutions = response.meta.page_token;
			else
				return;

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

			this.collection.reset( response.items );

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

			this.$el.find( '.emm-items' ).append( container );

		}

		this.$el.find( '.emm-pagination' ).show();

		this.model.set( 'max_id', response.meta.max_id );

		this.trigger( 'loaded loaded:success', response );

	},

	fetchedError: function(response) {
		emmContentView.prototype.fetchedError.apply( this, arguments );
	},

	fetchedEmpty: function() {
		emmContentView.prototype.fetchedEmpty.apply( this, arguments );
	},
});
