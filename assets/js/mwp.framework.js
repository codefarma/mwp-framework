/**
 * Plugin Javascript Module
 *
 * Created     December 30, 2016
 *
 * @package    MWP Application Framework
 * @author     Kevin Carwile
 * @since      1.0.3
 */
;(function( $, undefined ) {
	
	"use strict";

	window.mwp = _.extend( {}, Backbone.Events );
	
	// Auto initialize controllers on document ready
	$(document).ready( function() { mwp.trigger( 'init.controllers' ); });

	var controllers = {};
	var models = {};
	var collections = {};
	var local = window.mwp.local = mw_localized_data;
	
	var BaseModel = Backbone.Model.extend(
	{
		/**
		 * Return self for knockback binding compatibility
		 *
		 * @return	this
		 */
		model: function() {
			return this;
		}
	},
	{
		/**
		 * Allow model methods to be overridden
		 *
		 * When a method is overridden, the overriding function will be passed the function it is overriding as its
		 * first parameter, which can be called to retrieve the value of the overridden (parent) method.
		 *
		 * @param	string|object		method			The method name to override, or an object with properties corresponding to methods to override
		 * @param	function			newMethod		The new function to override the method with
		 * @return	void
		 */
		override: function( method, newMethod )
		{
			var self = this;
			
			if ( typeof method == 'object' ) {
				$.each( method, function( method, newMethod ) {
					self.override( method, newMethod );
				});
			}
			else if ( typeof method == 'string' ) {
				var parentMethod = this.prototype[method] || function(){};
				this.prototype[method] = function() {
					return _.wrap( _.bind( parentMethod, this ), _.bind( newMethod, this ) ).apply( this, arguments );
				};
			}
		}
	});
	
	/**
	 * Base Classes
	 */
	mwp.classes = {
	
		/**
		 * Model
		 */
		Model: BaseModel,
		
		/**
		 * Controller (singleton)
		 */
		Controller: BaseModel.extend({
		
			/**
			 * Constructor
			 *
			 * @return	void
			 */
			initialize: function()
			{
				var self = this;
				this.viewModel = {};
				mwp.trigger( self.get( 'name' ) + '.instance', self );
				
				$(document).ready( function() {
					mwp.trigger( self.get( 'name' ) + '.ready', self );
					if( typeof self.init == 'function' ) {
						self.init();
						mwp.trigger( self.get( 'name' ) + '.init', self );
					}
					self.viewModel._controller = self;
				});
			}
		},
		{
			/**
			 * @var	object
			 */
			_instance: null,
			
			/**
			 * @var	string
			 */
			_name: null,
			
			/**
			 * Get singleton instance
			 */
			instance: function()
			{
				if ( this._instance === null ) {
					this._instance = new this({ name: this._name });
					mwp.controller.set( this._name, this._instance );
				}
				
				return this._instance;
			}
		}),
		
		/**
		 * Collection
		 */
		Collection: Backbone.Collection.extend({
		
		})
		
	};
	
	/**
	 * Controller Manager
	 */
	mwp.controller = _.extend( function( name, properties, classProperties ) 
	{
		var controllerClass = mwp.controller.model( name, properties, classProperties );
		return controllerClass.instance();
	},
	{
		/**
		 * Controller Factory
		 *
		 * @param   string    name              The controller name
		 * @param   object    properties        Methods and properties of model instances
		 * @param   object    classProperties   Methods and properties of the model itself
		 * @return  Backbone.Model
		 */
		model: _.extend( function( name, properties, classProperties ) 
		{
			if ( name in controllers ) {
				throw new Error( 'Controller already exists. Use mwp.controller.model.get(\'' + name + '\') to get the controller model' );
			}
			
			properties = properties || {};
			classProperties = classProperties || {};
			
			$.extend( properties, { local: $.extend( {}, local, mw_localized_data ) } );
			$.extend( classProperties, { _name: name } );

			var controller = mwp.classes.Controller.extend( properties, classProperties );
			controllers[name] = controller;
			
			mwp.trigger( 'controller.model', controller, name );
			mwp.trigger( name + '.controller.model', controller );
			
			mwp.on( 'init.controllers', function() {
				controller.instance();
			});
			
			return controllers[ name ];	
		}, 
		{
			/**
			 * Get a controller model
			 *
			 * @param	string				name		The name of a registered controller
			 * @return	Backbone.Model
			 */
			get: function( name )
			{
				if ( typeof controllers[ name ] !== 'undefined' ) {
					return controllers[ name ];
				}
				
				return undefined;
			},
			
			/**
			 * Set a controller model
			 *
			 * @param	string				name			The registered name
			 * @param	object				controller		The controller instance
			 * @return	object
			 */
			set: function( name, controller )
			{
				controllers[ name ] = controller;
				return controller;
			}
		}),
		
		/**
		 * Get a controller
		 *
		 * @param	string				name		The name of a registered controller
		 * @return	Backbone.Model
		 */
		get: function( name )
		{
			if ( typeof controllers[ name ] !== 'undefined' ) {
				return controllers[ name ].instance();
			}
		},
		
		/**
		 * Set a controller
		 *
		 * @param	string				name			The registered name
		 * @param	object				controller		The controller instance
		 * @return	object
		 */
		set: function( name, controller )
		{
			if ( typeof controllers[ name ] === 'undefined' ) {
				return;
			}
			
			controllers[ name ]._instance = controller;
			return controller;
		}
	});
	
	/**
	 * Model Factory
	 *
	 * @param   string    name              The controller name
	 * @param   object    properties        Methods and properties of model instances
	 * @param   object    classProperties   Methods and properties of the model itself
	 * @return  Backbone.Model
	 */
	mwp.model = _.extend( function( name, properties, classProperties )
	{
		models[name] = mwp.classes.Model.extend( properties, classProperties );
		mwp.trigger( 'model', models[name], name );
		mwp.trigger( name + '.model', models[name] );
		return models[name];
	},
	{
		/**
		 * Get a model
		 *
		 * @param	string				name		The name of a registered model
		 * @return	Backbone.Model
		 */
		get: function( name )
		{
			if ( typeof models[ name ] !== 'undefined' ) {
				return models[ name ];
			}
			
			return undefined;
		},
		
		/**
		 * Set a model
		 *
		 * @param	string				name			The registered name
		 * @param	Backbone.Model		model			The model
		 * @return	Backbone.Model
		 */
		set: function( name, model )
		{
			models[ name ] = model;
			return model;
		}
	});
	
	/**
	 * Collection Factory
	 *
	 * @param   string    name              The controller name
	 * @param   object    properties        Methods and properties of model instances
	 * @param   object    classProperties   Methods and properties of the model itself
	 * @return  Backbone.Collection
	 */
	mwp.collection = _.extend( function( name, properties, classProperties )
	{
		collections[ name ] = mwp.classes.Collection.extend( properties, classProperties );
		return collections[ name ];
	},
	{
		/**
		 * Get a collection
		 *
		 * @param	string				name		The name of a registered model
		 * @return	Backbone.Model
		 */
		get: function( name )
		{
			if ( typeof collections[ name ] !== 'undefined' ) {
				return collections[ name ];
			}
			
			return undefined;
		},
		
		/**
		 * Set a collection
		 *
		 * @param	string					name		The registered name
		 * @param	Backbone.Collection		model
		 * @return	Backbone.Collection
		 */
		set: function( name, collection )
		{
			collections[ name ] = collection;
			return collection;
		}
	});
	
	/**
	 * Apply view models to a new scope
	 *
	 * @param	object		scope			DOM Scope to apply views to
	 */
	mwp.applyViews = function( scope )
	{
		if ( typeof ko !== 'undefined' )
		{
			var views = $(scope).find('[data-view-model]').addBack('[data-view-model]');
			
			/* Wrap all views with a protective shield */
			views.each( function() {
				$(this).before( '<!-- ko stopBinding: true -->' );
				$(this).after( '<!-- /ko -->' );
			});
			
			/* Bind all views */
			views.each( function() {
				var view = $(this);
				var view_name = view.data( 'view-model' );
				var controller = mwp.controller.get( view_name );
				if ( typeof controller !== 'undefined' && typeof controller.viewModel !== 'undefined' ) {
					ko.applyBindings( controller.viewModel, this );
				}
			});
		}
	};
	
	if ( typeof ko !== 'undefined' )
	{
		/**
		 * Custom knockout bindings
		 */
		_.extend( ko.bindingHandlers, 
		{
			/**
			 * Bind an arbitrary callback
			 */
			init: {
				init: function( element, valueAccessor, allBindingsAccessor ) {
					var callback = ko.utils.unwrapObservable( valueAccessor() );
					if ( typeof callback == 'function' ) {
						callback.call( element, allBindingsAccessor );
					}
				}
			},
			
			/**
			 * Bind an arbitrary callback
			 */
			callback: {
				update: function( element, valueAccessor, allBindingsAccessor ) {
					var callback = ko.utils.unwrapObservable( valueAccessor() );
					if ( typeof callback == 'function' ) {
						callback.call( element, allBindingsAccessor );
					}
				}
			},
			
			/**
			 * jQuery proxy
			 */
			jquery: {
				update: function( element, valueAccessor, allBindingsAccessor ) {
					var options = ko.utils.unwrapObservable( valueAccessor() );
					var el = $(element);
					$.each( options, function( key, props ) {
						if ( typeof el[key] == 'function' ) {
							el[key](props);
						}
					});
				}
			},
			
			/**
			 * Protect from further binding
			 */
			stopBinding: {
				init: function() {
					return { controlsDescendantBindings: true };
				}
			}
		});

		ko.virtualElements.allowedBindings.stopBinding = true;
		
		$(document).ready( function() {			
			/* Double ready means that this will be executed last */
			$(document).ready( function() {
				mwp.applyViews( document );
				mwp.trigger( 'views.ready' );
			});
		});
	}
	else
	{
		console.log( 'Bindings not available because knockout was not found.' );
	}
	
	$(document).ready( function() {
		mwp.trigger( 'mwp.ready' );
	});
	
})( jQuery );
