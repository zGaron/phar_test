<?php
/**
 * Lazy Loader
 *
*/
namespace Phalcon\Mvc\Micro;

use \Phalcon\Mvc\Micro\Exception;

/**
 * Phalcon\Mvc\Micro\LazyLoader
 *
 * Lazy-Load of handlers for Mvc\Micro using auto-loading
 *
 */
class LazyLoader
{
    
    /**
     * Handler
     *
     * @var null|object
     * @access protected
    */
    protected $_handler;

    /**
     * Definition
     *
     * @var null|string
     * @access protected
    */
    protected $_definition;

    /**
     * \Phalcon\Mvc\Micro\LazyLoader constructor
     *
     * @param string $definition
     * @throws Exception
     */
    public function __construct($definition)
    {
        if (!is_string($definition)) {
            throw new Exception('Only strings can be lazy loaded');
        }

        $this->_definition = $definition;
    }

    /**
     * Initializes the internal handler, calling functions on it
     *
     * @param string $method
     * @param array $arguments
     * @return mixed
     * @throws Exception
     */
    public function __call($method, $arguments)
    {
        if (!is_string($method) || !is_array($arguments)) {
            throw new Exception('Invalid parameter type.');
        }

        $handler = $this->_handler;

        if (!is_object($handler)) {
            $definition = $this->_definition;
            $handler = new {$definition}();
            $this->_handler = $handler;
        }

        /**
         * Call the handler
         */
        return call_user_func_array([$handler, $method], $arguments);
    }
}
