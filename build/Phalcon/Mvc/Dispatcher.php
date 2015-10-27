<?php
/**
 * Dispatcher
*/

namespace Phalcon\Mvc;

use \Phalcon\Dispatcher as BaseDispatcher;
use \Phalcon\Mvc\DispatcherInterface;
use \Phalcon\Mvc\Dispatcher\Exception;

/**
 * Phalcon\Mvc\Dispatcher
 *
 * Dispatching is the process of taking the request object, extracting the module name,
 * controller name, action name, and optional parameters contained in it, and then
 * instantiating a controller and calling an action of that controller.
 *
 *<code>
 *
 *  $di = new Phalcon\DI();
 *
 *  $dispatcher = new Phalcon\Mvc\Dispatcher();
 *
 *  $dispatcher->setDI($di);
 *
 *  $dispatcher->setControllerName('posts');
 *  $dispatcher->setActionName('index');
 *  $dispatcher->setParams(array());
 *
 *  $controller = $dispatcher->dispatch();
 *
 *</code>
 */
class Dispatcher extends BaseDispatcher implements DispatcherInterface
{

    /**
     * Handler Suffix
     *
     * @var string
     * @access protected
    */
    protected $_handlerSuffix = 'Controller';

    /**
     * Default Handler
     *
     * @var string
     * @access protected
    */
    protected $_defaultHandler = 'index';

    /**
     * Default Action
     *
     * @var string
     * @access protected
    */
    protected $_defaultAction = 'index';

    /**
     * Sets the default controller suffix
     *
     * @param string $controllerSuffix
     * @throws Exception
     */
    public function setControllerSuffix($controllerSuffix)
    {
        if (!is_string($controllerSuffix)) {
            throw new Exception('Invalid parameter type.');
        }

        $this->_handlerSuffix = $controllerSuffix;
    }

    /**
     * Sets the default controller name
     *
     * @param string $controllerName
     * @throws Exception
     */
    public function setDefaultController($controllerName)
    {
        if (!is_string($controllerName)) {
            throw new Exception('Invalid parameter type.');
        }

        $this->_defaultHandler = $controllerName;
    }

    /**
     * Sets the controller name to be dispatched
     *
     * @param string $controllerName
     * @throws Exception
     */
    public function setControllerName($controllerName)
    {
        $this->_handlerName = $controllerName;
    }

    /**
     * Gets last dispatched controller name
     *
     * @return string
     */
    public function getControllerName()
    {
        return $this->_handlerName;
    }

    /**
     * Gets previous dispatched controller name
     *
     * @return string
     */
    public function getPreviousControllerName()
    {
        return $this->_previousHandlerName;
    }

    /**
     * Gets previous dispatched action name
     *
     * @return string
     */
    public function getPreviousActionName()
    {
        return $this->_previousActionName;
    }

    /**
     * Throws an internal exception
     *
     * @param string $message
     * @param int $exceptionCode
     * @return boolean|null
     * @throws Exception
     */
    protected function _throwDispatchException($message, $exceptionCode = 0)
    {

        if (!is_string($message)) {
            throw new Exception('Invalid parameter type.');
        }

        if (!is_int($exceptionCode)) {
            throw new Exception('Invalid parameter type.');
        }

        $dependencyInjector = $this->_dependencyInjector;

        if (!is_object($dependencyInjector)) {
            throw new Exception("A dependency injection container is required to access the 'response' service", BaseDispatcher::EXCEPTION_NO_DI);
        }

        $response = $this->_dependencyInjector->getShared('response');

        /**
         * Dispatcher exceptions automatically sends a 404 status
         */
        $response->setStatusCode(404, 'Not Found');

        /**
         * Create the real exception
         */
        $exception = new Exception($message, $exceptionCode);
        
        if ($this->_handleException($exception) === false) {
            return false;
        }

        /**
         * Throw the exception if it wasn't handled
         */
        throw $exception;
    }

    /**
     * Handles a user exception
     *
     * @param \Exception $exception
     * @throws Exception
     * @return boolean|null
     */
    protected function _handleException($exception)
    {
        if (!is_object($exception) ||
            $exception instanceof \Exception === false) {
            throw new Exception('Invalid parameter type.');
        }

        $eventsManager = $this->_eventsManager;
        if (is_object($eventsManager)) {
            if ($eventsManager->fire('dispatch:beforeException', $this, $exception) === false) {
                return false;
            }
        }
    }

    /**
     * Possible controller class name that will be located to dispatch the request
     *
     * @return string
     */
    public function getControllerClass()
    {
        return $this->getHandlerName();
    }

    /**
     * Returns the lastest dispatched controller
     *
     * @return \Phalcon\Mvc\ControllerInterface|null
     */
    public function getLastController()
    {
        return $this->_lastHandler;
    }

    /**
     * Returns the active controller in the dispatcher
     *
     * @return \Phalcon\Mvc\ControllerInterface|null
     */
    public function getActiveController()
    {
        return $this->_activeHandler;
    }
}
