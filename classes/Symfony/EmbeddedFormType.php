<?php
/**
 * Plugin Class File
 *
 * Created:   April 10, 2017
 *
 * @package:  MWP Application Framework
 * @author:   Kevin Carwile
 * @since:    1.3.12
 */
namespace MWP\Framework\Symfony;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Access denied.' );
}

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * EmbeddedFormType Class
 */
class EmbeddedFormType extends AbstractType
{
	/**
	 * Build the form represented by this field type
	 *
	 * @param	FormBuilderInterface 		$builder		The builder this form is being added to
	 * @param	array						$option			The options
	 * @return	void
	 */
	public function buildForm( FormBuilderInterface $builder, array $options )
	{
		if ( isset( $options[ 'form' ] ) )
		{
			$form = $options[ 'form' ];
			foreach( $form->getFields() as $field )
			{
				$builder->add( $field[ 'name' ], $field[ 'type' ], $field[ 'options' ] );
			}
		}
	}
	
	/**
	 * Add an option to specify the form which will be embedded
	 *
	 * @param	OptionsResolver		$resolver				The options resolver
	 * @return	void
	 */
	public function configureOptions( OptionsResolver $resolver )
    {
        $resolver->setRequired( 'form' );
    }	
	
}
