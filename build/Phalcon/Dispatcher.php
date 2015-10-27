<?php
/**
 * Dispatcher
*/

namespace Phalcon;

use \Phalcon\DiInterface;
use \Phalcon\Di\InjectionAwareInterface;
use \Phalcon\Events\ManagerInterface;
use \Phalcon\Events\EventsAwareInterface;
use \Phalcon\DispatcherInterface;
use \Phalcon\FilterInterface;
use \Phalcon\Text;
use \Phalcon\Exception;

/**
 * Phalcon\Dispatcher
 *
 * This is the base class for Phalcon\Mvc\Dispatcher and Phalcon\CLI\Dispatcher.
 * This class can't be instantiated directly, you can use it to create your own dispatchers
 */
abstract class Dispatcher implements DispatcherInterface, InjectionAwareInterface, EventsAwareInterface
{
    
    /**
     * Exception: No DI
     *
     * @var int
    */
    const EXCEPTION_NO_DI = 0;

    /**
     * Exception: Cyclic Routing
     *
     * @var int
    */
    const EXCEPTION_CYCLIC_ROUTING = 1;

    /**
     * Exception: Handler not found
     *
     * @var int
    */
    const EXCEPTION_HANDLER_NOT_FOUND = 2;

    /**
     * Exception: Invalid handler
     *
     * @var int
    */
    const EXCEPTION_INVALID_HANDLER = 3;

    /**
     * Exception: Invalid params
     *
     * @var int
    */
    const EXCEPTION_INVALID_PARAMS = 4;

    /**
     * Exception: Action not found
     *
     * @var int
    */
    const EXCEPTION_ACTION_NOT_FOUND = 5;

    /**
     * Dependency Injector
     *
     * @var null|\Phalcon\DiInterface
     * @access protected
    */
    protected $_dependencyInjector;

    /**
     * Events Manager
     *
     * @var null|\Phalcon\Events\ManagerInterface
     * @access protected
    */
    protected $_eventsManager;

    /**
     * Active Handler
     *
     * @var null|object
     * @access protected
    */
    protected $_activeHandler;

    /**
     * Finished
     *
     * @var null|boolean
     * @access protected
    */
    protected $_finished;

    /**
     * Forwarded
     *
     * @var boolean
     * @access protected
    */
    protected $_forwarded = false;

    /**
     * Module Name
     *
     * @var null|string
     * @access protected
    */
    protected $_moduleName = null;

    /**
     * Namespace Name
     *
     * @var null|string
     * @access protected
    */
    protected $_namespaceName = null;

    /**
     * Handler Name
     *
     * @var null|string
     * @access protected
    */
    protected $_handlerName = null;

    /**
     * Action Name
     *
     * @var null|string
     * @access protected
    */
    protected $_actionName = null;

    /**
     * Params
     *
     * @var null|array
     * @access protected
    */
    protected $_params;

    /**
     * Returned Value
     *
     * @var mixed
     * @access protected
    */
    protected $_returnedValue = null;

    /**
     * Last Handler
     *
     * @var null|object
     * @access protected
    */
    protected $_lastHandler = null;

    /**
     * Default Namespace
     *
     * @var null|string
     * @access protected
    */
    protected $_defaultNamespace = null;

    /**
     * Default Handler
     *
     * @var null|object
     * @access protected
    */
    protected $_defaultHandler = null;

    /**
     * Default Action
     *
     * @var string
     * @access protected
    */
    protected $_defaultAction = '';

    /**
     * Handler Suffix
     *
     * @var string
     * @access protected
    */
    protected $_handlerSuffix = '';

    /**
     * Action Suffix
     *
     * @var string
     * @access protected
    */
    protected $_actionSuffix = 'Action';

    /**
     * Previous Handler Name
     *
     * @var string
     * @access protected
    */
    protected $_previousHandlerName = null;

    /**
     * Previous Action Name
     *
     * @var string
     * @access protected
    */
    protected $_previousActionName = null;

    /**
     * \Phalcon\Dispatcher constructor
     */
    public function __construct()
    {
        $this->_params = [];
    }

    /**
     * Sets the dependency injector
     *
     * @param \Phalcon\DiInterface $dependencyInjector
     */
    public function setDI($dependencyInjector)
    {
        if ($dependencyInjector instanceof DiInterface === false) {
            $this->{'_throwDispatchException'}('Invalid parameter type.');
            return null;
        }

        $this->_dependencyInjector = $dependencyInjector;
    }

    /**
     * Returns the internal dependency injector
     *
     * @return \Phalcon\DiInterface|null
     */
    public function getDI()
    {
        return $this->_dependencyInjector;
    }

    /**
     * Sets the events manager
     *
     * @param \Phalcon\Events\ManagerInterface $eventsManager
     */
    public function setEventsManager($eventsManager)
    {
        if ($eventsManager instanceof ManagerInterface === false) {
            $this->{'_throwDispatchException'}('Invalid parameter type.');
            return null;
        }

        $this->_eventsManager = $eventsManager;
    }

    /**
     * Returns the internal event manager
     *
     * @return \Phalcon\Events\ManagerInterface|null
     */
    public function getEventsManager()
    {
        return $this->_eventsManager;
    }

    /**
     * Sets the default action suffix
     *
     * @param string $actionSuffix
     */
    public function setActionSuffix($actionSuffix)
    {
        if (!is_string($actionSuffix)) {
            $this->{'_throwDispatchException'}('Invalid parameter type.');
            return null;
        }

        $this->_actionSuffix = $actionSuffix;
    }

    /**
     * Sets the module where the controller is (only informative)
     *
     * @param string $moduleName
     */
    public function setModuleName($moduleName)
    {
        $this->_moduleName = $moduleName;
    }

    /**
     * Gets the module where the controller class is
     *
     * @return string
     */
    public function getModuleName()
    {
        return $this->_moduleName;
    }

    /**
     * Sets the namespace where the controller class is
     *
     * @param string $namespaceName
     */
    public function setNamespaceName($namespaceName)
    {
        $this->_namespaceName = $namespaceName;
    }

    /**
     * Gets a namespace to be prepended to the current handler name
     *
     * @return string
     */
    public function getNamespaceName()
    {
        return $this->_namespaceName;
    }

    /**
     * Sets the default namespace
     *
     * @param string $namespace
     */
    public function setDefaultNamespace($namespace)
    {
        if (!is_string($namespace)) {
            $this->{'_throwDispatchException'}('Invalid parameter type.');
            return null;
        }

        $this->_defaultNamespace = $namespace;
    }

    /**
     * Returns the default namespace
     *
     * @return string
     */
    public function getDefaultNamespace()
    {
        return $this->_defaultNamespace;
    }

    /**
     * Sets the default action name
     *
     * @param string $actionName
     */
    public function setDefaultAction($actionName)
    {
        if (!is_string($actionName)) {
            $this->{'_throwDispatchException'}('Invalid parameter type.');
            return null;
        }

        $this->_defaultAction = $actionName;
    }

    /**
     * Sets the action name to be dispatched
     *
     * @param string $actionName
     */
    public function setActionName($actionName)
    {
        $this->_actionName = $actionName;
    }

    /**
     * Gets the lastest dispatched action name
     *
     * @return string
     */
    public function getActionName()
    {
        return $this->_actionName;
    }

    /**
     * Sets action params to be dispatched
     *
     * @param array $params
     */
    public function setParams($params)
    {
        $this->_params = $params;
    }

    /**
     * Gets action params
     *
     * @return array
     */
    public function getParams()
    {
        return $this->_params;
    }

    /**
     * Set a param by its name or numeric index
     *
     * @param mixed $param
     * @param mixed $value
     */
    public function setParam($param, $value)
    {
        $this->_params[$param] = $value;
    }

    /**
     * Gets a param by its name or numeric index
     *
     * @param mixedd $param
     * @param string|array|null $filters
     * @param mixed $defaultValue
     * @return mixed
     */
    public function getParam($param, $filters = null, $defaultValue = null)
    {        
        $params = $this->_params;
        
        if (!isset($params[$param])) {
            return $defaultValue;
        } else {
            $paramValue = $params[$param];
        }

        if ($filters === null) {
            return $paramValue;
        }

        $dependencyInjector = $this->_dependencyInjector;
        if (!is_object($dependencyInjector)) {
            $this->{'_throwDispatchException'}("A dependency injection object is required to access the 'filter' service", self::EXCEPTION_NO_DI);
        }
        $filter = $dependencyInjector->getShared('filter');
        return $filter->sanitize($paramValue, $filter);
    }

    /**
     * Returns the current method to be/executed in the dispatcher
     *
     * @return string
     */
    public function getActiveMethod()
    {
        return $this->_actionName . $this->_actionSuffix;
    }

    /**
     * Checks if the dispatch loop is finished or has more pendent controllers/tasks to disptach
     *
     * @return boolean
     */
    public function isFinished()
    {
        return $this->_finished;
    }

    /**
     * Sets the latest returned value by an action manually
     *
     * @param mixed $value
     */
    public function setReturnedValue($value)
    {
        $this->_returnedValue = $value;
    }

    /**
     * Returns value returned by the lastest dispatched action
     *
     * @return mixed
     */
    public function getReturnedValue()
    {
        return $this->_returnedValue;
    }

    /**
     * Dispatches a handle action taking into account the routing parameters
     *
     * @return object|boolean
     */
    public function dispatch()
    {
        $dependencyInjector = $this->_dependencyInjector;
        if (!is_object($dependencyInjector)) {
            $this->{'_throwDispatchException'}('A dependency injection container is required to access related dispatching services', self::EXCEPTION_NO_DI);
            return false;
        }

        /*
         * Calling beforeDispatchLoop
         */
        $eventsManager = $this->_eventsManager;
        if (is_object($eventsManager)) {
            if ($this->_eventsManager->fire('dispatch:beforeDispatchLoop', $this) === false) {
                return false;
            }
        }

        $value = null;
        $handler = null;
        $numberDispatches = 0;
        $actionSuffix = $this->_actionSuffix;

        $this->_finished = false;

        while (!$this->_finished) {

            $numberDispatches++;

            /*
             * Throw an exception after 256 consecutive forwards
             */
            if ($numberDispatches == 256) {
                $this->{'_throwDispatchException'}('Dispatcher has detected a cyclic routing causing stability problems', self::EXCEPTION_CYCLIC_ROUTING);
                break;
            }

            $this->_finished = true;

            $this->_resolveEmptyProperties();
            
            $namespaceName = $this->_namespaceName;
            $handlerName = $this->_handlerName;
            $actionName = $this->_actionName;
            $handlerClass = $this->getHandlerClass();

            /*
             * Calling beforeDispatch
             */
            if (is_object($eventsManager)) {
                if ($this->_eventsManager->fire('dispatch:beforeDispatch', $this) === false) {
                    continue;
                }

                /*
                 * Check if the user made a forward in the listener
                 */
                if ($this->finished === false) {
                    continue;
                }
            }

            /*
             * Handlers are retrieved as shared instances from the Service Container
             */
            $hasService = (bool) $dependencyInjector->has($handlerClass);
            if (!$hasService) {
                /*
                 * DI doesn't have a service with that name, try to load it using an autoloader
                 */
                $hasService = (bool) class_exists($handlerClass);
            }

            /*
             * If the service can be loaded we throw an exception
             */
            if (!$hasService) {
                $status = $this->{'_throwDispatchException'}($handlerClass . ' handler class cannot be loaded', self::EXCEPTION_HANDLER_NOT_FOUND);
                if ($status === false) {

                    /*
                     * Check if the user made a forward in the listener
                     */
                    if ($this->_finished === false) {
                        continue;
                    }
                }
                break;
            }

            /*
             * Handlers must be only objects
             */
            $handler = $dependencyInjector->getShared($handlerClass);

            /*
             * If the object was recently created in the DI we initialize it
             */
            if ($dependencyInjector->wasFreshInstance() === true) {
                $wasFresh = true;
            }

            if (!is_object($handler)) {
                $status = $this->{'_throwDispatchException'}('Invalid handler returned from the services container', self::EXCEPTION_INVALID_HANDLER);
                if ($status === false) {
                    if ($this->_finished === false) {
                        continue;
                    }
                }
                break;
            }

            $this->_activeHandler = $handler;

            /*
             * Check if the params is an array
             */
            $params = $this->_params;
            if (!is_array($params)) {

                /*
                 * An invalid parameter variable was passed throw an exception
                 */
                $status = $this->{'_throwDispatchException'}('Action parameters must be an Array', self::EXCEPTION_INVALID_PARAMS);
                if ($status === false) {
                    if ($this->_finished === false) {
                        continue;
                    }
                }
                break;
            }

            /*
             * Check if the method exists in the handler
             */
            $actionMethod = $actionName . $actionSuffix;

            if (!method_exists($handler, $actionMethod)) {

                /*
                 * Call beforeNotFoundAction
                 */
                if (is_object($eventsManager)) {

                    if ($this->_eventsManager->fire('dispatch:beforeNotFoundAction', $this) === false) {
                        continue;
                    }

                    if ($this->_finished === false) {
                        continue;
                    }
                }

                /*
                 * Try to throw an exception when an action isn't defined on the object
                 */
                $status = $this->{"_throwDispatchException"}("Action '" . $actionName . "' was not found on handler '" . $handlerName . "'", self::EXCEPTION_ACTION_NOT_FOUND);
                if ($status === false) {
                    if ($this->_finished === false) {
                        continue;
                    }
                }
                break;
            }

            /*
             * Calling beforeExecuteRoute
             */
            if (is_object($eventsManager)) {

                if ($this->_eventsManager->fire('dispatch:beforeExecuteRoute', $this) === false) {
                    continue;
                }

                /*
                 * Check if the user made a forward in the listener
                 */
                if ($this->_finished === false) {
                    continue;
                }
            }

            /*
             * Calling beforeExecuteRoute as callback and event
             */
            if (method_exists($handler, 'beforeExecuteRoute')) {

                if ($handler->beforeExecuteRoute($this) === false) {
                    continue;
                }

                /*
                 * Check if the user made a forward in the listener
                 */
                if ($this->_finished === false) {
                    continue;
                }
            }

            /**
             * Call the 'initialize' method just once per request
             */
            if ($wasFresh === true) {

                if (method_exists($handler, 'initialize')) {
                    $handler->initialize();
                }

                /**
                 * Calling afterInitialize
                 */
                if (is_object($eventsManager)) {
                    if ($eventsManager->fire('dispatch:afterInitialize', $this) === false) {
                        continue;
                    }

                    /*
                     * Check if the user made a forward in the listener
                     */
                    if ($this->_finished === false) {
                        continue;
                    }
                }
            }

            try {

                /*
                 * We update the latest value produced by the latest handler
                 */
                $this->_returnedValue = call_user_func_array([$handler, $actionMethod], $params);
                $this->_lastHandler = $handler;

            } catch (\Exception $e) {
                if ($this->{'_handleException'}($e) === false) {
                    if ($this->_finished === false) {
                        continue;
                    }
                } else {
                    throw new Exception($e);
                    
                }
            }

            /*
             * Calling afterExecuteRoute
             */
            if (is_object($eventsManager)) {
                if ($eventsManager->fire('dispatch:afterExecuteRoute', $this, $value) === false) {
                    continue;
                }

                if ($this->_finished === false) {
                    continue;
                }

                /*
                 * Calling afetDispatch
                 */
                $eventsManager->fire('dispatch:afterDispatch', $this);
            }

            /*
             * Calling afterExecuteRoute as callback and event
             */
            if (method_exists($handler, 'afterExecuteRoute')) {
                
                if ($handler->afterExecuteRoute($this, $value) === false) {
                    continue;
                }

                if ($this->_finished === false) {
                    continue;
                }
            }
        }

        /*
         * Call afterDispatchLoop
         */
        if (is_object($this->_eventsManager)) {
            $this->_eventsManager->fire('dispatch:afterDispatchLoop', $this);
        }

        return $handler;
    }

    /**
     * Forwards the execution flow to another controller/action
     * Dispatchers are unique per module. Forwarding between modules is not allowed
     *
     *<code>
     *  $this->dispatcher->forward(array('controller' => 'posts', 'action' => 'index'));
     *</code>
     *
     * @param array $forward
     */
    public function forward($forward)
    {
        if (!is_array($forward)) {
            $this->{'_throwDispatchException'}('Forward parameter must be an Array');
            return null;
        }

        //Check if we need to forward to another namespace
        if (isset($forward['namespace'])) {
            $this->_namespaceName = $forward['namespace'];
        }

        //Check if we need to forward to another controller
        if (isset($forward['controller'])) {
            $this->_previousHandlerName = $this->_handlerName;
            $this->_handlerName = $forward['controller'];
        } else {
            if (isset($forward['task'])) {
                $this->_previousHandlerName = $this->_handlerName;
                $this->_handlerName = $forward['task'];
            }
        }

        //Check if we need to forward to another action
        if (isset($forward['action'])) {
            $this->_previousActionName = $this->_actionName;
            $this->_actionName = $forward['action'];
        }

        //Check if we need to forward changing the current parameters
        if (isset($forward['params'])) {
            $this->_params = $forward['params'];
        }

        $this->_finished = false;
        $this->_forwarded = true;
    }

    /**
     * Check if the current executed action was forwarded by another one
     *
     * @return boolean
     */
    public function wasForwarded()
    {
        return $this->_forwarded;
    }

    /**
     * Possible class name that will be located to dispatch the request
     *
     * @return string
     */
    public function getHandlerClass()
    {
        
        $this->_resolveEmptyProperties();

        $handlerSuffix = $this->_handlerSuffix;
        $handlerName = $this->_handlerName;
        $namespaceName = $this->_namespaceName;

        //We don't camelize the classes if they are in namespaces
        $p = strpos($this->_handlerName, '\\');
        if ($p === false) {
            $camelizedClass = Text::camelize($handlerName);
        } elseif ($p === 0) {
            //@note this only handles one leading slash
            $camelizedClass = substr($handlerName, strlen($handlerName) + 1);
        } else {
            $camelizedClass = $this->_handlerName;
        }

        //Create the complete controller class name prepending the namespace
        if ($namespaceName) {
            if (Text::endsWith($namespaceName, '\\')) {
                return $namespaceName . $camelizedClass . $handlerSuffix;
            } else {
                return $namespaceName . '\\' . $camelizedClass . $handlerSuffix;
            }
        } else {
            return $camelizedClass . $handlerSuffix;
        }

        return $handlerClass;
    }

    /**
     * Set empty properties to their defaults (where defaults are available)
     */
    protected function _resolveEmptyProperties()
    {
        // If the current namespace is null we used the set in this->_defaultNamespace
        if (!$this->_namespaceName) {
            $this->_namespaceName = $this->_defaultNamespace;
        }

        // If the handler is null we use the set in this->_defaultHandler
        if (!$this->_handlerName) {
            $this->_handlerName = $this->_defaultHandler;
        }

        // If the action is null we use the set in this->_defaultAction
        if (!$this->_actionName) {
            $this->_actionName = $this->_defaultAction;
        }
    }
}
