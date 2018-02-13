<?php
/**
 * Plugin Class File
 *
 * Created:   December 14, 2017
 *
 * @package:  MWP Application Framework
 * @author:   Kevin Carwile
 * @since:    1.4.0
 */
namespace MWP\Framework\Helpers\Form\SymfonyForm;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Access denied.' );
}

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\FormType;

/**
 * FormTypeExtension Class
 */
class _FormTypeExtension extends AbstractTypeExtension
{
    /**
     * Extends the form type which all other types extend
     *
     * @return string The name of the type being extended
     */
    public function getExtendedType()
    {
        return FormType::class;
    }

    /**
     * Add the extra row_attr option
     *
     * @param OptionsResolverInterface $resolver
     */
    public function configureOptions( OptionsResolver $resolver )
    {
        $resolver->setDefaults(array(
			'required' => false,
			'description' => '',
            'row_attr' => array(),
            'prefix' => '',
            'suffix' => '',
			'row_prefix' => '',
			'row_suffix' => '',
			'label_prefix' => '',
			'label_suffix' => '',
			'field_prefix' => '',
			'field_suffix' => '',
			'choice_prefix' => '',
			'choice_suffix' => '',
			'toggles' => array(),
        ));
    }

    /**
     * Pass the set row_attr options to the view
     *
     * @param FormView $view
     * @param FormInterface $form
     * @param array $options
     */
    public function buildView( FormView $view, FormInterface $form, array $options )
    {
		$view->vars['description']  = $options['description'];
        $view->vars['row_attr']     = $options['row_attr'];
		$view->vars['prefix']       = $options['prefix'];
		$view->vars['suffix']       = $options['suffix'];
		$view->vars['row_prefix']   = $options['row_prefix'];
		$view->vars['row_suffix']   = $options['row_suffix'];
		$view->vars['label_prefix'] = $options['label_prefix'];
		$view->vars['label_suffix'] = $options['label_suffix'];
		$view->vars['field_prefix'] = $options['field_prefix'];
		$view->vars['field_suffix'] = $options['field_suffix'];
		$view->vars['choice_prefix'] = $options['choice_prefix'];
		$view->vars['choice_suffix'] = $options['choice_suffix'];
		$view->vars['toggles']      = $options['toggles'];
    }
}
