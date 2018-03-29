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

use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\Exception\UnexpectedTypeException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormInterface;

/**
 * Resize a collection form element based on the data sent from the client.
 *
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class ResizeFormListener implements EventSubscriberInterface
{
    protected $type;
    protected $options;
    protected $allowAdd;
    protected $allowDelete;

    private $deleteEmpty;

    /**
     * @param string        $type
     * @param array         $options
     * @param bool          $allowAdd    Whether children could be added to the group
     * @param bool          $allowDelete Whether children could be removed from the group
     * @param bool|callable $deleteEmpty
     */
    public function __construct($type, array $options = array(), $allowAdd = false, $allowDelete = false, $deleteEmpty = false)
    {
        $this->type = $type;
        $this->allowAdd = $allowAdd;
        $this->allowDelete = $allowDelete;
        $this->options = $options;
        $this->deleteEmpty = $deleteEmpty;
    }

    public static function getSubscribedEvents()
    {
        return array( 
			FormEvents::PRE_SET_DATA => 'preSetData', 
			FormEvents::PRE_SUBMIT => 'preSubmit',
			FormEvents::SUBMIT => array( 'onSubmit', 50 ),
		);
    }

    public function preSetData(FormEvent $event)
    {
        $form = $event->getForm();
        $data = $event->getData();
		$options = $form->getConfig()->getOptions();

        if (null === $data) {
            $data = array();
        }

        if (!is_array($data) && !($data instanceof \Traversable && $data instanceof \ArrayAccess)) {
            throw new UnexpectedTypeException($data, 'array or (\Traversable and \ArrayAccess)');
        }		

        // First remove all rows
        foreach ($form as $name => $child) {
            $form->remove($name);
        }

        // Then add all rows again in the correct order
        foreach ($data as $name => $value) {
			$field_options = array_replace(array(
                'property_path' => '['.$name.']',
				'data' => $value,
            ), $this->options);
			
            $form->add($name, $this->type, $field_options);
        }
    }
	
    public function preSubmit(FormEvent $event)
    {
        $form = $event->getForm();
        $data = $event->getData();
		$options = $form->getConfig()->getOptions();
		
        if ($data instanceof \Traversable && $data instanceof \ArrayAccess) {
            @trigger_error('Support for objects implementing both \Traversable and \ArrayAccess is deprecated since Symfony 3.1 and will be removed in 4.0. Use an array instead.', E_USER_DEPRECATED);
        }

        if (!is_array($data) && !($data instanceof \Traversable && $data instanceof \ArrayAccess)) {
            $data = array();
        }

        // Remove all empty rows
        if ($this->allowDelete) {
            foreach ($form as $name => $child) {
                if (!isset($data[$name])) {
                    $form->remove($name);
                }
            }
        }

        // Add all additional rows
        if ($this->allowAdd) {
            foreach ($data as $name => $value) {
                if (!$form->has($name)) {
					$field_options = array_replace(array(
						'property_path' => '['.$name.']',
						'data' => $value,
					), $this->options);
					
                    $form->add( $name, $this->type, $field_options );
                }
            }
        }
		
		// Adjust the order
		if ( $options['allow_reorder'] ) {
			$removed_entries = [];
			$key_order = array_keys( $data );
			
			// Make a list of all entries being removed for reordering
			foreach( $form as $name => $child ) {
				if ( in_array( $name, $key_order ) ) {
					$removed_entries[$name] = $child;
					$form->remove( $name );
				}
			}
			
			$removed_entry_keys = array_keys( $removed_entries );
			foreach( $key_order as $key ) {
				if ( in_array( $key, $removed_entry_keys ) ) {
					$form->add($key, $this->type, array_replace(array(
						'property_path' => '['.$key.']',
						'data' => $removed_entries[$key]->getData(),
					), $this->options));
				}
			}
		}	
		
    }

    public function onSubmit(FormEvent $event)
    {
        $form = $event->getForm();
        $data = $event->getData();
		
		$ordered_data = array();
		foreach( $form as $name => $child ) {
			$ordered_data[$name] = $data[$name];
		}
		$data = $ordered_data;
		
        // At this point, $data is an array or an array-like object that already contains the
        // new entries, which were added by the data mapper. The data mapper ignores existing
        // entries, so we need to manually unset removed entries in the collection.

        if (null === $data) {
            $data = array();
        }

        if (!is_array($data) && !($data instanceof \Traversable && $data instanceof \ArrayAccess)) {
            throw new UnexpectedTypeException($data, 'array or (\Traversable and \ArrayAccess)');
        }

        if ($this->deleteEmpty) {
            $previousData = $form->getData();
            /** @var FormInterface $child */
            foreach ($form as $name => $child) {
                $isNew = !isset($previousData[$name]);
                $isEmpty = is_callable($this->deleteEmpty) ? call_user_func($this->deleteEmpty, $child->getData()) : $child->isEmpty();

                // $isNew can only be true if allowAdd is true, so we don't
                // need to check allowAdd again
                if ($isEmpty && ($isNew || $this->allowDelete)) {
                    unset($data[$name]);
                    $form->remove($name);
                }
            }
        }

        // The data mapper only adds, but does not remove items, so do this
        // here
        if ($this->allowDelete) {
            $toDelete = array();

            foreach ($data as $name => $child) {
                if (!$form->has($name)) {
                    $toDelete[] = $name;
                }
            }

            foreach ($toDelete as $name) {
                unset($data[$name]);
            }
        }

        $event->setData($data);
    }
	
}
