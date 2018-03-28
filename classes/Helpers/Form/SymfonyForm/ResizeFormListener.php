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
        return array( FormEvents::PRE_SET_DATA => 'preSetData' );
    }

    public function preSetData(FormEvent $event)
    {
        $form = $event->getForm();
        $data = $event->getData();

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
            $form->add($name, $this->type, array_replace(array(
                'property_path' => '['.$name.']',
				'data' => $value,
            ), $this->options));
        }
    }
}
