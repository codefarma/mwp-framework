<?php
/**
 * Plugin Class File
 *
 * Created:   January 25, 2017
 *
 * @package:  MWP Application Framework
 * @author:   Kevin Carwile
 * @since:    1.1.4
 */
namespace MWP\Framework\Helpers\Form;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Access denied.' );
}

use MWP\Framework\Framework;
use MWP\Framework\Symfony;
use MWP\Framework\Helpers\Form;

/**
 * Form Class
 */
class _SymfonyForm extends Form
{	
	/**
	 * @var 	\MWP\Framework\Plugin		Provides access to the plugin instance
	 */
	protected $plugin;
	
	/**
	 * @var	string
	 */
	public $name;
	
	/**
	 * @var	string
	 */
	public $method = "POST";
	
	/**
	 * @var	string
	 */
	public $action = "";
	
	/**
	 * @var submitButton
	 */
	public $submitButton = "Save";
	
	/**
	 * @var	string		Output themes
	 */
	public $themes = array();
	
	/** 
	 * @var	mixed
	 */
	public $data;
	 
	/**
	 * @var	array
	 */
	public $options = array
	(
		'allow_extra_fields' => true,
		'empty_data' => array(),
	);
	
	/**
	 * @var	array
	 */
	protected $container_chain = array();
	
	/**
	 * Set the latest parent in the chain
	 *
	 * @param	string			$parent_name				The latest parent name
	 * @return	void
	 */
	public function setCurrentContainer( $parent_name )
	{
		$this->container_chain[] = $parent_name;
	}
	
	/**
	 * Get the latest parent in the chain
	 *
	 * @return	void
	 */
	public function getCurrentContainer()
	{
		return end( $this->container_chain );
	}
	
	/**
	 * Remove the latest parent in the chain and return new current container
	 *
	 * @return	string|NULL
	 */
	public function endLastContainer()
	{
		array_pop( $this->container_chain );
		return $this->getCurrentContainer();
	}
	
	/**
	 * Set template
	 *
	 * @param	string|array		$themes		The form themes (or themes) to pick templates from
	 * @return	this							Chainable
	 */
	public function setTheme( $themes )
	{
		$themes = (array) $themes;
		$this->themes = $themes;
		
		return $this;
	}
	
	/**
 	 * Get plugin
	 *
	 * @return	\MWP\Framework\Plugin
	 */
	public function getPlugin()
	{
		return $this->plugin;
	}
	
	/**
	 * Set plugin
	 *
	 * @return	this			Chainable
	 */
	public function setPlugin( \MWP\Framework\Plugin $plugin=NULL )
	{
		$this->plugin = $plugin;
		return $this;
	}
	
	/**
	 * Constructor
	 *
	 * @param	string						$name			The name of the form
	 * @param	MWP\Framework\Plugin		$plugin			The plugin to associate this class with, or NULL to auto-associate
	 * @param	array						$options		Set options for the form
	 * @param	object|array				$data			The initial form data state
	 * @return	void
	 */
	public function __construct( $name, \MWP\Framework\Plugin $plugin=NULL, $options=array(), $data=null )
	{
		$this->name = $name;
		$this->plugin = $plugin ?: Framework::instance();
		$this->data = $data;
		$this->options = $options;
		
		$engines = array();
		
		if ( isset( $plugin ) )
		{
			$engines[] = new \MWP\Framework\Symfony\TemplateEngine( $plugin );
		}
		
		$engines[] = new \MWP\Framework\Symfony\TemplateEngine( Framework::instance() );
		$templateEngine = new \Symfony\Component\Templating\DelegatingEngine( $engines );
		
		$this->setTemplateEngine( $templateEngine );
		$this->setEngines( $engines );
	}
	
	/**
	 * Enable or disable csrf protection
	 *
	 * @param	bool			$bool			Either true for ON or false for OFF
	 * @return	this							Chainable
	 */
	public function csrf( $bool )
	{
		$this->options[ 'csrf_protection' ] = $bool;
		
		return $this;
	}
	
	/**
	 * @var		EngineInterface
	 */
	protected $templateEngine;
	
	/**
	 * @var	FormRenderHelper
	 */
	public $renderHelper;
	
	/**
	 * @var	TranslatorHelper
	 */
	public $translatorHelper;
	
	/**
	 * Set the template rendering engine
	 *
	 * @param	EngineInterface			$templateEngine				The template rendering engine
	 * @return	void
	 */
	public function setTemplateEngine( \Symfony\Component\Templating\EngineInterface $templateEngine )
	{
		$this->templateEngine = $templateEngine;
	}
	
	/**
	 * Get template rendering engine
	 *
	 * @return	EngineInterface
	 */
	public function getTemplateEngine()
	{
		return $this->templateEngine;
	}
	
	/** 
	 * @var		FormBuilderInterface
	 */
	protected $formBuilder;
	
	/**
	 * Set the form builder
	 *
	 * @param	FormBuilderInterface		$formBuilder			The form builder
	 * @return	void
	 */
	public function setFormBuilder( \Symfony\Component\Form\FormBuilderInterface $formBuilder )
	{
		$this->formBuilder = $formBuilder;
	}
	
	/** 
	 * Get the form builder
	 *
	 * @return	\Symfony\Component\Form\FormBuilderInterface
	 */
	public function getFormBuilder()
	{
		if ( ! isset( $this->formBuilder ) ) {
			$this->setFormBuilder( Symfony::instance()->getFormFactory()->createNamedBuilder( $this->name, 'Symfony\Component\Form\Extension\Core\Type\FormType', $this->data, $this->options ) );
		}
		
		return $this->formBuilder;
	}
	
	/**
	 * @var	Form
	 */
	protected $handledForm;
	
	/**
	 * Get the form
	 *
	 * @return	Form
	 */
	public function getForm()
	{
		/* Return the most current version of the form until it's been handled */
		if ( $this->handledForm )
		{
			return $this->handledForm;
		}
		
		return $this->getFormBuilder()->getForm();
	}
	
	/**
	 * Set the form
	 *
	 * @param	Form		$form			The form
	 */
	public function setHandledForm( $form )
	{
		$this->handledForm = $form;
	}
	
	/**
	 * @var		array
	 */
	protected $engines = array();
	
	/**
	 * Set the template engines cache
	 *
	 * @param	array		$engines			The form view
	 * @return	void
	 */
	public function setEngines( $engines )
	{
		$this->engines = $engines;
	}
	
	/** 
	 * Get the template engines
	 *
	 * @return	array
	 */
	public function getEngines()
	{
		return $this->engines;
	}
	
	/**
	 * Get the plugin slug for use in hooks
	 *
	 *@return	string
	 */
	public function getPluginSlug()
	{
		return str_replace( '-', '_', $this->getPlugin()->pluginSlug() );
	}
	
	/**
	 * @var	array		Form field class shorthand map
	 */
	public static $formFieldClasses = array(
		'text'         => 'Symfony\Component\Form\Extension\Core\Type\TextType',
		'textarea'     => 'Symfony\Component\Form\Extension\Core\Type\TextareaType',
		'email'        => 'Symfony\Component\Form\Extension\Core\Type\EmailType',
		'integer'      => 'Symfony\Component\Form\Extension\Core\Type\IntegerType',
		'money'        => 'Symfony\Component\Form\Extension\Core\Type\MoneyType',
		'number'       => 'Symfony\Component\Form\Extension\Core\Type\NumberType',
		'password'     => 'Symfony\Component\Form\Extension\Core\Type\PasswordType',
		'percent'      => 'Symfony\Component\Form\Extension\Core\Type\PercentType',
		'search'       => 'Symfony\Component\Form\Extension\Core\Type\SearchType',
		'url'          => 'Symfony\Component\Form\Extension\Core\Type\UrlType',
		'range'        => 'Symfony\Component\Form\Extension\Core\Type\RangeType',
		'choice'       => 'Symfony\Component\Form\Extension\Core\Type\ChoiceType',
		'entity'       => 'Symfony\Component\Form\Extension\Core\Type\EntityType',
		'country'      => 'Symfony\Component\Form\Extension\Core\Type\CountryType',
		'language'     => 'Symfony\Component\Form\Extension\Core\Type\LanguageType',
		'locale'       => 'Symfony\Component\Form\Extension\Core\Type\LocaleType',
		'timezone'     => 'Symfony\Component\Form\Extension\Core\Type\TimezoneType',
		'currency'     => 'Symfony\Component\Form\Extension\Core\Type\CurrencyType',
		'date'         => 'Symfony\Component\Form\Extension\Core\Type\DateType',
		'dateinterval' => 'Symfony\Component\Form\Extension\Core\Type\DateintervalType',
		'datetime'     => 'Symfony\Component\Form\Extension\Core\Type\DatetimeType',
		'time'         => 'Symfony\Component\Form\Extension\Core\Type\TimeType',
		'birthday'     => 'Symfony\Component\Form\Extension\Core\Type\BirthdayType',
		'checkbox'     => 'Symfony\Component\Form\Extension\Core\Type\CheckboxType',
		'file'         => 'Symfony\Component\Form\Extension\Core\Type\FileType',
		'radio'        => 'Symfony\Component\Form\Extension\Core\Type\RadioType',
		'collection'   => 'Symfony\Component\Form\Extension\Core\Type\CollectionType',
		'repeated'     => 'Symfony\Component\Form\Extension\Core\Type\RepeatedType',
		'hidden'       => 'Symfony\Component\Form\Extension\Core\Type\HiddenType',
		'button'       => 'Symfony\Component\Form\Extension\Core\Type\ButtonType',
		'reset'        => 'Symfony\Component\Form\Extension\Core\Type\ResetType',
		'submit'       => 'Symfony\Component\Form\Extension\Core\Type\SubmitType',
		'fieldgroup'   => 'MWP\Framework\Helpers\Form\SymfonyForm\FieldgroupType',
		'tab'          => 'MWP\Framework\Helpers\Form\SymfonyForm\TabType',
		'html'         => 'MWP\Framework\Helpers\Form\SymfonyForm\HtmlType',
	);
	
	/**
	 * @var	array		Added fields
	 */
	public $fields = array();
	
	/**
	 * @var	array
	 */
	public $fieldRefs = array();
	
	/**
	 * @var	array
	 */
	public $parentRefs = array();
	
	/**
	 * Add a form tab
	 * 
	 * @param	string			$name				The tab name
	 * @param	array			$options			The tab options
	 * @param	string			$parent_name		The parent element to add to
	 * @param	string|NULL		$insert_name		The name of a field around which this field should be inserted
	 * @param	string			$insert_position	The position at which to insert this field if using $insert_name 
	 * @return	object								The added form element
	 */
	public function addTab( $name, $options, $parent_name=NULL, $insert_name=NULL, $insert_position='after' )
	{
		$options['type'] = 'tab';
		
		if ( $parent_name === NULL ) {
			$parent_name = $this->endLastContainer();
		}
		
		$field = $this->addField( $name, 'fieldgroup', $options, $parent_name, $insert_name, $insert_position );
		$this->setCurrentContainer( $field->getName() );
		return $field;
	}
	
	/**
	 * Add a form heading
	 *
	 * @param	string			$name				The field name
	 * @param	string			$heading			The heading html
	 * @param	string			$parent_name		The parent element to add to
	 * @param	string|NULL		$insert_name		The name of a field around which this field should be inserted
	 * @param	string			$insert_position	The position at which to insert this field if using $insert_name 
	 * @return	object								The added form element
	 */
	public function addHeading( $name, $heading, $parent_name=NULL, $insert_name=NULL, $insert_position='after' )
	{
		return $this->addHtml( $name, $this->getPlugin()->getTemplateContent( 'form/heading', array( 'heading' => $heading ) ), $parent_name, $insert_name, $insert_position );
	}
	
	/**
	 * Add some arbitrary html to the form
	 *
	 * @param	string			$name				The field name
	 * @param	string			$html_content		The html content to add
	 * @param	string			$parent_name		The parent element to add to
	 * @param	string|NULL		$insert_name		The name of a field around which this field should be inserted
	 * @param	string			$insert_position	The position at which to insert this field if using $insert_name 
	 * @return	object								The added form element
	 */
	public function addHtml( $name, $html_content, $parent_name=NULL, $insert_name=NULL, $insert_position='after' )
	{
		return $this->addField( $name, 'html', array( 'html_content' => $html_content ), $parent_name, $insert_name, $insert_position );
	}
	
	/**
	 * Embed records using an active record controller
	 *
	 * @param	string						$name				The form item name
	 * @param	ActiveRecordController		$controller			The CRUD controller to use for the records
	 * @param	$array 						$options			An array of configuration options
	 *	@param	array|NULL					 itemsWhere			A where clause to filter the records by or NULL for all records
	 * 	@param	array|NULL					 actionParams		An associative array of params to add to controller action links
	 * 	@param	array|NULL					 tableConfig		The configuration to pass to the createDisplayTable controller method
	 * @param	string			$parent_name		The parent element to add to
	 * @param	string|NULL		$insert_name		The name of a field around which this field should be inserted
	 * @param	string			$insert_position	The position at which to insert this field if using $insert_name 
	 * @return	object
	 */
	public function embedRecords( $name, $controller, $options=NULL, $parent_name=NULL, $insert_name=NULL, $insert_position='after' )
	{
		$options = $options ?: [];
		$itemsWhere = @$options['itemsWhere'] ?: ['1=1'];
		$actionParams = @$options['actionParams'] ?: NULL;
		$tableConfig = @$options['tableConfig'] ?: NULL;

		$actions = $controller->getActions();
		if ( is_array( $actionParams ) ) {
			foreach( $actions as $key => &$action ) {
				$action['params'] = array_merge( $action['params'], $actionParams );
			}
		}
		else if ( is_callable( $actionParams ) ) {
			$actions = call_user_func( $actionParams, $actions );
		}

		$tableConfig = $tableConfig ?: [
			'bulkActions' => [],
			'perPage' => 1000,
		];

		$table = $controller->createDisplayTable( $tableConfig );
		$table->prepare_items( $itemsWhere );
		
		return $this->addHtml( $name, "
			<div style='padding:25px 0'>
				{$controller->getActionsHtml($actions)} 
			</div>
			{$table->getDisplay()}
		");				
	}
	
	/**
	 * Prepare a field to be added to the form
	 *
	 * @param	string			$name				The field name
	 * @param	string			$type				The field type (registered shorthand or a class name)
	 * @param	array			$options			The field options
	 * @return	array 								The prepared field associative array
	 */
	public static function prepareField( $name, $type, $options )
	{	
		$field = array( 
			'name' => $name, 
			'type' => $type, 
			'options' => $options, 
		);
		
		/* Automatically add a NonBlank constraint to required fields */
		if ( ! isset( $field['options']['constraints'] ) and isset( $field['options']['required'] ) and $field['options']['required'] ) {
			$field['options']['constraints'] = array( 'NotBlank' );
		}
		
		/* Collection field enhancement */
		if ( $field['type'] == 'collection' ) {
			$field['options']['entry_options']['row_attr']['data-role'] = "collection-entry";
			if ( isset( $field['options']['allow_add'] ) and $field['options']['allow_add'] ) {
				$add_label = isset( $field['options']['add_label'] ) ? $field['options']['add_label'] : 'Add Entry';
				$field['options']['field_suffix'] .= '<div class="row"><button data-role="add-entry" type="button" class="btn btn-default">' . $add_label . '</button></div>';
			}
			if ( isset( $field['options']['allow_delete'] ) and $field['options']['allow_delete'] ) {
				$delete_label = isset( $field['options']['delete_label'] ) ? $field['options']['delete_label'] : 'Delete Entry';
				$field['options']['entry_options']['field_prefix'] .= '<div class="row"><div class="col-sm-10">';
				$field['options']['entry_options']['field_suffix'] .= '</div><div class="col-sm-2 text-right">
					<button type="button" data-role="delete-entry" class="btn btn-danger">
						<i class="glyphicon glyphicon-trash"></i> ' . $delete_label . '
					</button>
				</div></div>';
			}
			if ( isset( $field['options']['allow_reorder'] ) and $field['options']['allow_reorder'] ) {
				$field['options']['entry_options']['row_attr']['class'] .= ' orderable';
			}
		}

		/* Prepare any provided constraints */
		if ( isset( $field['options']['constraints'] ) ) {
			if ( ! is_array( $field['options']['constraints'] ) ) {
				$field['options']['constraints'] = array( $field['options']['constraints'] );
			}
			$processed_constraints = array();
			foreach( $field['options']['constraints'] as $class => $config ) {
				if ( is_string( $config ) ) {
					$_class = class_exists( $config ) ? $config : 'Symfony\Component\Validator\Constraints\\' . $config;
					if ( class_exists( $_class ) ) {
						$processed_constraints[] = new $_class;
					}
				} 
				else if ( is_callable( $config ) ) {
					$processed_constraints[] = new \Symfony\Component\Validator\Constraints\Callback( $config );					
				}
				else if ( is_array( $config ) ) {
					$_class = class_exists( $class ) ? $class : 'Symfony\Component\Validator\Constraints\\' . $class;
					if ( class_exists( $_class ) ) {
						$processed_constraints[] = new $_class( $config );
					}
				}
				else {
					$processed_constraints[] = $config;
				}
			}
			$field['options']['constraints'] = $processed_constraints;
		}
		
		/**
		 * Translate toggles 
		 */
		if ( isset( $field['options']['toggles'] ) ) {
			$field['options']['attr']['form-toggles'] = $field['options']['toggles'];
			$field['options']['attr']['form-type'] = $field['type'];
		}
		
		/**
		 * Automatically wrap certain fields with bootstrap classes for display purposes unless asked not to
		 */
		$wrap_bootstrap = isset( $field['options']['wrap_bootstrap'] ) ? $field['options']['wrap_bootstrap'] : in_array( $field['type'], array(
			'text', 'textarea', 'email', 'integer', 'money', 'number', 'password', 'url', 'choice', 'date', 'checkbox', 'radio', 'file', 'date', 'time', 'datetime', 'birthday',
			'submit', 'button', 'reset'
		) );
		
		unset( $field['options']['wrap_bootstrap'] );
		
		if ( $wrap_bootstrap ) {
			
			if ( ! in_array( $field['type'], array( 'submit', 'reset', 'button' ) ) ) {
				$field['options']['row_attr']['class'] = ( isset( $field['options']['row_attr']['class'] ) ? $field['options']['row_attr']['class'] . ' ' : '' ) . 'form-group row';
				$field['options']['row_attr']['class'] = ( isset( $field['options']['row_attr']['class'] ) ? $field['options']['row_attr']['class'] . ' ' : '' ) . 'form-group';
				$field['options']['label_attr']['class'] = ( isset( $field['options']['label_attr']['class'] ) ? $field['options']['label_attr']['class'] . ' ' : '' ) . 'col-lg-2 col-md-3 col-sm-4 form-label';
				$field['options']['field_prefix'] = '<div class="col-lg-6 col-md-7 col-sm-8">' . ( isset( $field['options']['field_prefix'] ) ? $field['options']['field_prefix'] : '' );
				$field['options']['field_suffix'] = ( isset( $field['options']['field_suffix'] ) ? $field['options']['field_suffix'] : '' ) . '</div>';
			} else {
				if ( ! isset( $field['options']['attr']['class'] ) ) {
					switch( $field['type'] ) {
						case 'submit':
							$field['options']['attr']['class'] = 'btn btn-success';
							break;
						case 'reset':
							$field['options']['attr']['class'] = 'btn btn-danger';
							break;
						case 'button':
							$field['options']['attr']['class'] = 'btn btn-primary';
							break;
					}
				}				
			}
			
			if ( $field['type'] == 'choice' and isset( $field['options']['expanded'] ) and $field['options']['expanded'] == true ) {
				$field['options']['choice_prefix'] = ( isset( $field['options']['choice_prefix'] ) ? $field['options']['choice_prefix'] : '' ) . (( isset( $field['options']['multiple'] ) and $field['options']['multiple'] == true ) ? '<div class="checkbox">' : '<div class="radio">');
				$field['options']['choice_suffix'] = '</div>' . ( isset( $field['options']['choice_suffix'] ) ? $field['options']['choice_suffix'] : '' );
			} else {
				if ( ! in_array( $field['type'], array( 'checkbox', 'radio', 'date', 'time', 'datetime', 'birthday', 'submit', 'button', 'reset', 'file' ) ) ) {
					$field['options']['attr']['class'] = ( isset( $field['options']['attr']['class'] ) ? $field['options']['attr']['class'] . ' ' : '' ) . 'form-control';
				}
			}
		}

		return $field;
	}
	
	/**
	 * Add a field to the form
	 *
	 * @param	string			$name				The field name
	 * @param	string			$type				The field type (registered shorthand or a class name)
	 * @param	array			$options			The field options
	 * @param	string|NULL		$parent_name		The parent field name to add this field to
	 * @param	string|NULL		$insert_name		The name of a field around which this field should be inserted
	 * @param	string			$insert_position	The position at which to insert this field if using $insert_name 
	 * @return	object 								The added form element
	 */
	public function addField( $name, $type='text', $options=array(), $parent_name=NULL, $insert_name=NULL, $insert_position='after' )
	{
		$builder = $this->getFormBuilder();	
		$options = array_merge( array( 'translation_domain' => $this->getPlugin()->pluginSlug() ), $options );
		$parent_name = $parent_name !== NULL ? $parent_name : $this->getCurrentContainer(); 
		
		$field = $this->applyFilters( 'field', array_merge( static::prepareField( $name, $type, $options ), array( 
			'parent_name' => $parent_name, 
			'insert_name' => $insert_name, 
			'insert_position' => $insert_position,
		)));
		
		/* Adding a child element requires us to get the reference to the parent element */
		if ( $field['parent_name'] ) {
			if ( array_key_exists( $field['parent_name'], $this->fieldRefs ) ) {				
				$builder = $this->fieldRefs[ $field['parent_name'] ];
			}
		}
		
		/* Adding a fieldgroup as a tab requires an intermediate 'tab' form element to exist */
		if ( $field['type'] == 'fieldgroup' and ( isset( $field['options']['type'] ) and $field['options']['type'] == 'tab' ) ) 
		{
			try {
				$builder = $builder->get( $field['parent_name'] . '_tabs' );
			} catch ( \InvalidArgumentException $e ) {
				$builder->add( $field['parent_name'] . '_tabs', static::getFieldClass( 'tab' ), array( 
					'attr' => array( 
						'class' => 'mwp-form-tabs', 
						'initial-tab' => isset( $_REQUEST[ $field['parent_name'] . '_tab' ] ) ? $_REQUEST[ $field['parent_name'] . '_tab' ] : $field['name'], 
					) 
				));
				$builder = $builder->get( $field['parent_name'] . '_tabs' );
			}			
		}
		
		$field_type = static::getFieldClass( $field['type'] );
		
		/* Are we attempting to insert the field in a specific position? */
		if( $field['insert_name'] ) 
		{
			$inserted = false;
			foreach( $builder->all() as $formField ) {
				// We must remove each field and re-add it to create a new field order
				$builder->remove( $formField->getName() );
				
				// Are we at the insert point?
				if( $formField->getName() == $field['insert_name'] ) 
				{
					if ( $field['insert_position'] == 'before' ) {
						$builder->add( $field['name'], $field_type, $field['options'] );
						$builder->add( $formField );
					} else {
						$builder->add( $formField );
						$builder->add( $field['name'], $field_type, $field['options'] );						
					}
					$inserted = true;
				} 
				else {
					$builder->add( $formField );
				}
			}
			
			// If the insert point wasn't found, just add it to the end
			if ( ! $inserted ) {
				$builder->add( $field['name'], $field_type, $field['options'] );
			}
		} 
		else {
			$builder->add( $field['name'], $field_type, $field['options'] );
		}
		
		try {
			$fieldRef = $builder->get( $field['name'] );
		}
		catch( \Exception $e ) {
			$builder->remove( $field['name'] );
			return $this->addHtml( $name, "<div class=\"alert alert-danger\"><p>Error adding widget for: " . esc_html( $field['name'] ) . "</p><p>" . $e->getMessage() . "</p></div>" );
		}
		
		/* Cache field references */
		$this->fields[ $field['name'] ] = $field;
		$this->fieldRefs[ $field['name'] ] = $fieldRef;
		$this->parentRefs[ $field['name'] ] = $builder;
		
		return $this->fieldRefs[ $field['name'] ];
	}
	
	/**
	 * Remove a field from the form
	 * 
	 * @param	string			$name			The name of the field to remove
	 * @return	bool
	 */
	public function removeField( $name )
	{
		if ( isset( $this->fieldRefs[ $name ] ) and isset( $this->parentRefs[ $name ] ) ) {
			$builder = $this->parentRefs[ $name ];
			$builder->remove( $name );
			unset( $this->fields[ $name ] );
			unset( $this->fieldRefs[ $name ] );
			unset( $this->parentRefs[ $name ] );
			
			return true;
		}
		
		return false;
	}
	
	/**
	 * Get a field type class
	 *
	 * @param	string			$type			Either a class or a shorthand key to lookup in the types array
	 * @return	string
	 */
	public static function getFieldClass( $type )
	{
		if ( isset( static::$formFieldClasses[ $type ] ) ) {
			return static::$formFieldClasses[ $type ];
		}
		
		/**
		 * Get the class to use for an unrecognized form field $type
		 *
		 * @param   string   $type    The form field type
		 * @return  string
		 */
		return apply_filters( 'mwp_form_field_class', $type );
	}
	
	/**
	 * Get the added fields
	 *
	 * @return	array
	 */
	public function getFields()
	{
		return $this->fields;
	}
	
	/**
	 * @var	bool		Request handled
	 */
	public $requestHandled = false;
	
	/**
	 * Handle the request
	 *
	 * @param	Request		$request				The request to handle
	 * @return	void
	 */
	public function handleRequest( \Symfony\Component\HttpFoundation\Request $request=NULL )
	{
		if ( ! isset( $request ) )
		{
			$request = Framework::instance()->getRequest();
		}
		
		$form = $this->getFormBuilder()->getForm();
		$form->handleRequest( $request );
		$this->setHandledForm( $form );
		$this->requestHandled = true;
	}
	
	/**
	 * Check if form was submitted
	 *
	 * @return	bool
	 */
	public function isSubmitted()
	{
		if ( ! $this->requestHandled ) {
			$this->handleRequest();
		}
		
		try {
			return $this->getForm()->isSubmitted();
		}
		catch( \Throwable $t ) { }
		catch( \Exception $e ) { }

		return false;
	}
	
	/**
	 * Check for valid form submission
	 *
	 * @return	bool
	 */
	public function isValidSubmission()
	{	
		try {
			return $this->isSubmitted() and $this->getForm()->isValid();
		}
		catch( \Throwable $t ) { }
		catch( \Exception $e ) { }
		
		return false;
	}
	
	/**
	 * Get the form submission data
	 *
	 * @return	array|false
	 */
	public function getSubmissionData()
	{
		if ( $this->isSubmitted() )
		{
			return $this->getForm()->getData();
		}
		
		return array();
	}
	
	/**
	 * Get submitted form values
	 *
	 * @return	array
	 */
	public function getValues()
	{
		$values = array();
		
		if ( $this->isValidSubmission() ) {
			$values = $this->applyFilters( 'values', $this->getSubmissionData() ); 
		}
		
		return $values;
	}
	
	/**
	 * Get form submission errors
	 *
	 * @return	array			Fields that had errors
	 */
	public function getErrors()
	{
		$errors = array();
		
		if ( $this->isSubmitted() ) {
			return $this->applyFilters( 'errors', $this->getForm()->getErrors() );
		}
		
		return $errors;
	}
	
	/**
	 * Get form output
	 *
	 * @return	string
	 */
	public function render()
	{
		try {
			$template_vars = $this->applyFilters( 'render', array( 
				'formWrapper' => $this,
				'form' => $this->getForm()->createView(),
			) );
			
			$this->renderHelper = new \MWP\Framework\Symfony\FormRenderHelper( 
				new \Symfony\Component\Form\FormRenderer( 
					new \Symfony\Component\Form\Extension\Templating\TemplatingRendererEngine(
						$this->getTemplateEngine(), array_merge( $this->themes, array( 'form/symfony' ) )
					)
				)
			);
			
			$this->translatorHelper = new \MWP\Framework\Symfony\TranslatorHelper();
			
			foreach( $this->engines as $engine ) {
				$engine->addHelpers( array( $this->renderHelper, $this->translatorHelper ) );
			}
			
			return $this->getPlugin()->getTemplateContent( 'form/wrapper', array( 'form' => $this, 'form_html' => $this->renderHelper->form( $template_vars[ 'form' ], $template_vars ) ) );
		}
		catch( \Throwable $t ) {
			return "<strong>Form Render Error:</strong> <pre>" . $t->getMessage() . "</pre>";
		}
		catch( \Exception $e ) {
			return "<strong>Form Render Error:</strong> <pre>" . $e->getMessage() . "</pre>";
		}
	}
	
}
