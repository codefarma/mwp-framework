/**
 * Plugin Javascript Module
 *
 * Created     August 1, 2017
 *
 * @package    MWP Application Framework
 * @author     Kevin Carwile
 * @since      1.3.5
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
	 * Settings Controller
	 *
	 * The init() function is called after the page is fully loaded.
	 *
	 * Data passed into your script from the server side is available
	 * by the mainController.local property inside your controller:
	 *
	 * > var ajaxurl = mainController.local.ajaxurl;
	 *
	 * The viewModel of your controller will be bound to any HTML structure
	 * which uses the data-view-model attribute and names this controller.
	 *
	 * Example:
	 *
	 * <div data-view-model="mwp-framework-settings">
	 *   <span data-bind="text: title"></span>
	 * </div>
	 */
	var mwpFrameworkSettings = mwp.controller.model( 'mwp-framework-settings', 
	{
		
		/**
		 * Initialization function
		 *
		 * @return	void
		 */
		init: function()
		{

		}
	
	});
	
	/**
	 * Extend the knockout bindings 
	 */
	_.extend( ko.bindingHandlers, 
	{
		wpMedia: {
			init: function( element, valueAccessor, allBindingsAccessor )
			{
				if ( typeof wp.media == 'undefined' ) {
					console.log( 'MWP: wpMedia binding cannot be used unless the appropriate wp scripts have been included using "wp_enqueue_media()" on the backend' );
					return;
				}
				
				var control = $(element);
				var options = ko.utils.unwrapObservable( valueAccessor() );				
				var _frame = wp.media( options.frame );
				
				var viewModel = {
					mediaFrame: _frame,
					attachments: ko.observableArray( [] ),
				};
				
				// Load and select all of the existing media attachments
				if ( $.isArray( options.attachments ) ) {
					_.each( options.attachments, function( attachment_id ) {
						var attachment = wp.media.attachment( attachment_id );
						attachment.fetch().then( function() {
							viewModel.attachments.push( attachment );
						});
					});
				}
				
				// Auto select current attachments when media frame is opened
				_frame.on( 'open', function() {	_frame.state().get('selection').set( viewModel.attachments() );	});
				
				// Save media frame selections when frame is closed
				_frame.on( 'select', function() {
					var selection = _frame.state().get('selection');
					viewModel.attachments( selection.models );
				});
				
				ko.applyBindingsToDescendants( viewModel, element );
				return { controlsDescendantBindings: true };
			}
		}
	});
	
})( jQuery );
 
