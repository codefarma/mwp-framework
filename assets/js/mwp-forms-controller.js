/**
 * Plugin Javascript Module
 *
 * Created     December 22, 2017
 *
 * @package    MWP Application Framework
 * @author     Kevin Carwile
 * @since      1.4.0
 */

/**
 * Controller Design Pattern
 *
 * Note: This pattern has a dependency on the "mwp" script
 * i.e. @Wordpress\Script( deps={"mwp"} )
 */
(function( $, undefined ) {
	
	"use strict";

	/**
	 * Forms Controller
	 *
	 */
	mwp.controller.model( 'mwp-forms-controller', 
	{
		
		/**
		 * Initialization function
		 *
		 * @return	void
		 */
		init: function()
		{
			var self = this;
			var ajaxurl = this.local.ajaxurl;			
			
			this.viewModel = {};
			
			mwp.on( 'views.ready', function() {
				self.applyToggles();
				self.setupCollections();
				self.setupAjaxTables();
			});
		},
		
		/**
		 * Setup collection elements
		 *
		 * @return	void
		 */
		setupCollections: function()
		{
			var collections = $('[data-collection-config]');
			
			collections.each( function() {
				var container = $(this);
				var config = container.data('collection-config');
				var collection = container.find('[data-role="collection"]').first();
				
				if ( config.allow_reorder ) {
					collection.sortable( config.sort_options );
				}
				
				if ( config.allow_add ) {
					container.on( 'click', '[data-role="add-entry"]', function(e) {
						e.preventDefault();
						e.stopPropagation();
						var counter = collection.data('entry-counter') || collection.children().length;
						var newEntry = collection.attr('data-prototype')
							.replace(/__name__/g, counter)
							.replace(/__number__/g, counter+1);
						collection.data('entry-counter', ++counter);
						collection.append( newEntry );
					});
				}
				
				if ( config.allow_delete ) {
					container.on( 'click', '[data-role="delete-entry"]', function(e) {
						e.preventDefault();
						e.stopPropagation();
						var entry = $(this).closest('[data-role="collection-entry"]');
						if ( confirm('Are you sure?') ) {
							entry.remove();
						}
					});
				}
				
			});
		},
		
		/**
		 * Setup display tables to load via ajax
		 *
		 * @return	void
		 */
		setupAjaxTables: function()
		{
			var self = this;
			
			$(document).on( 'submit', 'form[data-table-nav="ajax"]', function(e) {
				e.preventDefault();
				var formEl = $(this);
				self.updateTableViaAjax( formEl, window.location, new FormData(this) )
			});
			
			$(document).on( 'click', 'form[data-table-nav="ajax"] .pagination-links a, form[data-table-nav="ajax"] .manage-column a', function(e) {
				e.preventDefault();
				var linkEl = $(this);
				var formEl = linkEl.closest('form');
				var formData = new FormData( formEl[0] );
				formData.delete('paged');
				self.updateTableViaAjax( formEl, linkEl.attr('href'), formData );
			});
		},
		
		/**
		 * Perform an ajax request to update a table
		 *
		 * @param 	jQuery		formEl			The jquery wrapped form element which contains the display table
		 * @param	string		url				The url which should be posted to
		 * @return	$.Deferred
		 */
		updateTableViaAjax: function( formEl, url, formData )
		{
			var table = formEl.find('table.wp-list-table');
			var topNav = formEl.find('.tablenav.top');
			var bottomNav = formEl.find('.tablenav.bottom');
			var overlayAvailable = typeof $.fn.LoadingOverlay !== 'undefined';
			
			overlayAvailable && table.LoadingOverlay('show');
			
			return $.ajax({
				url: url,
				type: 'post',
				data: formData,
				processData: false,
				contentType: false
			}).done( function( response ) {
				var page = $(response);
				var _table = page.find('table.wp-list-table');
				var _topNav = page.find('.tablenav.top');
				var _bottomNav = page.find('.tablenav.bottom');
				
				if ( _table.length && table.length ) { table.replaceWith( _table.eq(0) ); }
				if ( _topNav.length && topNav.length ) { topNav.replaceWith( _topNav.eq(0) ); }
				if ( _bottomNav.length && bottomNav.length ) { bottomNav.replaceWith( _bottomNav.eq(0) ); }
			}).always( function() {
				overlayAvailable && table.LoadingOverlay('hide');
			});
		},
		
		/**
		 * Resequence records
		 *
		 * @return	void
		 */
		resequenceRecords: function( event, ui, sortableElement, config )
		{
			var self = this;
			var sortedArray = sortableElement.sortable( 'toArray' );
			
			$.post( this.local.ajaxurl, {
				nonce: this.local.ajaxnonce,
				action: 'mwp_resequence_records',
				class: config.class,
				sequence: sortedArray
			});
		},
		
		/**
		 * Apply form toggling functionality
		 *
		 * @param	jQuery|dom|undefined		scope			The scope which to apply functionality to
		 * @return	void
		 */
		applyToggles: function( scope ) 
		{
			var self = this;
			scope = scope ? $(scope) : $(document);
			
			scope.find('.mwp-form [form-toggles]').each( function() {				
				var element = $(this);
				if ( ! element.data( 'toggles-applied' ) ) {
					element.on( 'change', function() { self.doToggles( element ); } ).trigger( 'change' );
					element.data( 'toggles-applied', true );
				}
			});
		},
		
		/**
		 * Do the toggles for a given field
		 *
		 * @param	jQuery			element				jQuery wrapped dom element
		 * @return	void
		 */
		doToggles: function( element )
		{
			var value_toggles = JSON.parse( element.attr('form-toggles') );
			var toggles = {	selected: { show: [], hide: [] }, other: { show: [], hide: [] } };
			
			var current_value = this.getElementValue( element );
			
			/**
			 * If an input value toggles another field to 'show', we wanto to hide it if that value
			 * is not currently selected. So we need to sort everything out to know what needs to
			 * be hidden and shown based on the field state.
			 *
			 */
			$.each( value_toggles, function( value, actions ) {
				_.each( ['show','hide'], function( action ) {
					if ( typeof actions[action] !== 'undefined' ) {
						var selectors = $.isArray( actions[action] ) ? actions[action] : [ actions[action] ];
						var arr = toggles[ ( current_value.indexOf( (value).toString() ) >= 0 ? 'selected' : 'other' ) ][ action ];
						arr.push.apply( arr, selectors );
					}
				});				
			});

			/* Do the toggles */
			_.each( toggles.other.show, function( selector ) { $(selector).hide(); mwp.trigger('forms.toggle.hidden', selector); } );
			_.each( toggles.other.hide, function( selector ) { $(selector).show(); mwp.trigger('forms.toggle.shown', selector); } );
			_.each( toggles.selected.show, function( selector ) { $(selector).show(); mwp.trigger('forms.toggle.shown', selector); } );
			_.each( toggles.selected.hide, function( selector ) { $(selector).hide(); mwp.trigger('forms.toggle.hidden', selector); } );
			
		},
		
		/**
		 * Get the value for a toggling element
		 *
		 * @param	jQuery			element				The element with the toggles settings
		 * @return	array
		 */
		getElementValue: function( element )
		{
			var current_value = $.isArray( element.val() ) ? element.val() : [ element.val() ];
			
			if ( element.is('div[form-type="choice"]') ) {
				current_value = $.map( element.find( ':selected,:checked' ), function( el ) { return $(el).val().toString(); } );
			}
			
			if ( element.is('input[type="checkbox"]') ) {
				current_value = element.is(':checked') ? [ element.val() ] : [];
			}
			
			return current_value;
		}
	
	});
	
	/**
	 * Add forms related knockout bindings
	 *
	 */
	$.extend( ko.bindingHandlers, 
	{
		sequenceableRecords: {
			init: function( element, valueAccessor ) 
			{
				var config = ko.unwrap( valueAccessor() );
				if ( typeof $.fn.sortable !== 'undefined' ) 
				{
					var sortableElement = config.find ? $(element).find(config.find) : $(element);
					var options = $.extend( {
						placeholder: 'mwp-sortable-placeholder'
					}, config.options || {} );
					
					var updateCallback = config.callback || function( event, ui, sortableElement, config ) {
						var formsController = mwp.controller.get( 'mwp-forms-controller' );
						formsController.resequenceRecords( event, ui, sortableElement, config );
					};
					
					try {
						sortableElement.sortable( options );
						sortableElement.on( 'sortupdate', function( event, ui ) {
							if ( typeof updateCallback === 'function' ) {
								updateCallback( event, ui, sortableElement, config );
							}
						});
					}
					catch(e) {
						console.log( e );
					}
				}
			}
		}
	});	
})( jQuery );