<?php
/**
 * Engine
 *
*/

namespace Phalcon\Mvc\View;

use \Phalcon\Di\Injectable;
use \Phalcon\Di\InjectionAwareInterface;
use \Phalcon\Mvc\ViewBaseInterface;
use \Phalcon\Mvc\View\Exception;

/**
 * Phalcon\Mvc\View\Engine
 *
 * All the template engine adapters must inherit this class. This provides
 * basic interfacing between the engine and the Phalcon\Mvc\View component.
 *
 */
abstract class Engine extends Injectable
{
    
    /**
     * View
     *
     * @var null|\Phalcon\Mvc\ViewBaseInterface
     * @access protected
    */
    protected $_view;

    /**
     * \Phalcon\Mvc\View\Engine constructor
     *
     * @param \Phalcon\Mvc\ViewBaseInterface $view
     * @param \Phalcon\DiInterface|null $dependencyInjector
     * @throws Exception
     */
    public function __construct($view, $dependencyInjector = null)
    {
        if ($view instanceof ViewBaseInterface === false) {
            throw new Exception('Invalid parameter type.');
        }

        if (!is_null($dependencyInjector) &&
            !is_object($dependencyInjector)) {
            throw new Exception('Invalid parameter type.');
        }

        $this->_view = $view;
        $this->_dependencyInjector = $dependencyInjector;
    }

    /**
     * Returns cached ouput on another view stage
     *
     * @return string
     */
    public function getContent()
    {
        return $this->_view->getContent();
    }

    /**
     * Renders a partial inside another view
     *
     * @param string $partialPath
     * @param mixed $params
     * @return string
     * @throws Exception
     */
    public function partial($partialPath, $params = null)
    {
        if (!is_string($partialPath)) {
            throw new Exception('Invalid parameter type.');
        }

        return $this->_view->partial($partialPath, $params);
    }

    /**
     * Returns the view component related to the adapter
     *
     * @return \Phalcon\Mvc\ViewBaseInterface
     */
    public function getView()
    {
        return $this->_view;
    }
}
