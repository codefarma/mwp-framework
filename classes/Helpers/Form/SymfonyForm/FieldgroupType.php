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

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use MWP\Framework\Helpers\Form\SymfonyForm;

/**
 * FieldgroupType Class
 */
class _FieldgroupType extends AbstractType
{
    /**
     * @param OptionsResolverInterface $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver
            ->setDefaults([
				'type' => '',
                'title' => '',
				'data' => NULL,
                'inherit_data' => false,
                'options' => array(),
                'label' => false,
				'fields' => array(),
				'error_bubbling' => true,
            ]);
    }
	
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
		if (!empty($options['fields'])) {
			if ( is_callable( $options['fields'] ) ) {
				call_user_func( $options['fields'], $builder );
			} elseif ( is_array( $options['fields'] ) ) {
				foreach ( $options['fields'] as $field ) {
					$field = SymfonyForm::prepareField( $field['name'], $field['type'], $field['options'] );
					$field_type = SymfonyForm::getFieldClass( $field['type'] );
					$builder->add( $field['name'], $field_type, $field['options'] );
				}
			}
        }
    }
	
    /**
     * @param FormView      $view
     * @param FormInterface $form
     * @param array         $options
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
		$view->vars['title'] = $options['title'];
		$view->vars['type'] = $options['type'];
		$view->vars['has_errors'] = count( $form->getErrors(true) ) > 0;
    }
	
    /**
     * @return string
     */
    public function getName()
    {
        return 'fieldgroup';
    }
}
