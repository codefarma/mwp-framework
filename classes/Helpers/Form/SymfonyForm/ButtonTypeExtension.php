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
use Symfony\Component\Form\Extension\Core\Type\ButtonType;

/**
 * ButtonTypeExtension Class
 */
class _ButtonTypeExtension extends AbstractTypeExtension
{
    /**
     * Extends the form type which all other types extend
     *
     * @return string The name of the type being extended
     */
    public function getExtendedType()
    {
        return ButtonType::class;
    }

    /**
     * Add the extra row_attr option
     *
     * @param OptionsResolverInterface $resolver
     */
    public function configureOptions( OptionsResolver $resolver )
    {
        $resolver->setDefaults(array(
            'row_attr' => array(),
            'prefix' => '',
            'suffix' => '',
			'row_prefix' => '',
			'row_suffix' => '',
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
        $view->vars['row_attr']     = $options['row_attr'];
		$view->vars['prefix']       = $options['prefix'];
		$view->vars['suffix']       = $options['suffix'];
		$view->vars['row_prefix']   = $options['row_prefix'];
		$view->vars['row_suffix']   = $options['row_suffix'];
    }
}
