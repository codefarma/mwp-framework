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
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use MWP\Framework\Helpers\Form\SymfonyForm\FieldgroupType;

/**
 * CollectionTypeExtension Class
 */
class _CollectionTypeExtension extends AbstractTypeExtension
{
    /**
     * Extends the form type which all other types extend
     *
     * @return string The name of the type being extended
     */
    public function getExtendedType()
    {
        return CollectionType::class;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
		$resizeListener = new ResizeFormListener(
            $options['entry_type'],
            $options['entry_options'],
            $options['allow_add'],
            $options['allow_delete'],
            $options['delete_empty']
        );

        $builder->addEventSubscriber( $resizeListener );
	}
	
    /**
     * Add the extra row_attr option
     *
     * @param OptionsResolverInterface $resolver
     */
    public function configureOptions( OptionsResolver $resolver )
    {
        $resolver->setDefaults(array(
			'entry_type' => FieldgroupType::class,
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
		
    }
}
