<?php
/**
 * Micro Model
 *
*/
namespace Phalcon\Mvc;

use \Phalcon\DiInterface;
use \Phalcon\Di\Injectable;
use \Phalcon\Di\ServiceInterface;
use \Phalcon\Di\FactoryDefault;
use \Phalcon\Mvc\Micro\Exception;
use \Phalcon\Mvc\Micro\MiddlewareInterface;
use \Phalcon\Mvc\Micro\Collection;
use \Phalcon\Mvc\Micro\CollectionInterface;
use \Phalcon\Mvc\Micro\LazyLoader;
use \Phalcon\Mvc\Router\RouteInterface;
use \Phalcon\Http\ResponseInterface;


/**
 * Phalcon\Mvc\Micro
 *
 * With Scene you can create "Micro-Framework like" applications. By doing this, you only need to
 * write a minimal amount of code to create a PHP application. Micro applications are suitable
 * to small applications, APIs and prototypes in a practical way.
 *
 *<code>
 *
 * $app = new Phalcon\Mvc\Micro();
 *
 * $app->get('/say/welcome/{name}', function ($name) {
 *    echo "<h1>Welcome $name!</h1>";
 * });
 *
 * $app->handle();
 *
 *</code>
 *
 */
class Micro extends Injectable implements  \ArrayAccess
{
    
    /**
     * Dependency Injector
     *
     * @var \Phalcon\DiInterface|null
     * @access protected
    */
    protected $_dependencyInjector;

    /**
     * Handlers
     *
     * @var null|array
     * @access protected
    */
    protected $_handlers;

    /**
     * Router
     *
     * @var null|\Phalcon\Mvc\RouterInterface
     * @access protected
    */
    protected $_router;

    /**
     * Stopped
     *
     * @var null|boolean
     * @access protected
    */
    protected $_stopped;

    /**
     * NotFound-Handler
     *
     * @var null|callable
     * @access protected
    */
    protected $_notFoundHandler;

    /**
     * Error-Handler
     *
     * @var null|callable
     * @access protected
    */
    protected $_errorHandler;

    /**
     * Active Handler
     *
     * @var null|callable
     * @access protected
    */
    protected $_activeHandler;

    /**
     * Before Handlers
     *
     * @var null|array
     * @access protected
    */
    protected $_beforeHandlers;

    /**
     * After Handlers
     *
     * @var null|array
     * @access protected
    */
    protected $_afterHandlers;

    /**
     * Finish Handlers
     *
     * @var null|array
     * @access protected
    */
    protected $_finishHandlers;

    /**
     * Returned Value
     *
     * @var mixed
     * @access protected
    */
    protected $_returnedValue;

    /**
     * \Phalcon\Mvc\Micro constructor
     *
     * @param \Phalcon\DiInterface|null $dependencyInjector
     */
    public function __construct($dependencyInjector = null)
    {
        if (is_object($dependencyInjector)) {
            if ($dependencyInjector instanceof DiInterface) {
                $this->setDi($dependencyInjector);
            }          
        }
    }

    /**
     * Sets the DependencyInjector container
     *
     * @param \Phalcon\DiInterface $dependencyInjector
     * @throws Exception
     */
    public function setDI($dependencyInjector)
    {
        if (!is_object($dependencyInjector) ||
            $dependencyInjector instanceof DiInterface === false) {
            throw new Exception('The dependency injector must be an object');
    }

        //We automatically set ourselves as applications ervice
    if (!$dependencyInjector->has('application')) {
        $dependencyInjector->set('application', $this);
    }

    $this->_dependencyInjector = $dependencyInjector;
}

    /**
     * Maps a route to a handler without any HTTP method constraint
     *
     * @param string $routePattern
     * @param callable $handler
     * @return \Phalcon\Mvc\Router\RouteInterface
     * @throws Exception
     */
    public function map($routePattern, $handler)
    {
        if (!is_string($routePattern) || !is_callable($handler)) {
            throw new Exception('Invalid parameter type.');
        }

        /**
         * We create a router even if there is no one in the DI
         */
        $router = $this->getRouter();

        /**
         * Routes are added to the router
         */
        $route = $router->add($routePattern);

        /**
         * Using the id produced by the router we store the handler
         */
        $this->_handlers[$route->getRouteId()] = $handler;

        /**
         * The route is returned, the developer can add more things on it
         */
        return $route;
    }

    /**
     * Maps a route to a handler that only matches if the HTTP method is GET
     *
     * @param string $routePattern
     * @param callable $handler
     * @return \Phalcon\Mvc\Router\RouteInterface
     * @throws Exception
     */
    public function get($routePattern, $handler)
    {
        if (!is_string($routePattern) || !is_callable($handler)) {
            throw new Exception('Invalid parameter type.');
        }

        /**
         * We create a router even if there is no one in the DI
         */
        $router = $this->getRouter();

        /**
         * Routes are added to the router
         */
        $route = $router->addGet($routePattern);

        /**
         * Using the id produced by the router we store the handler
         */
        $this->_handlers[$route->getRouteId()] = $handler;

        /**
         * The route is returned, the developer can add more things on it
         */
        return $route;
    }

    /**
     * Maps a route to a handler that only matches if the HTTP method is POST
     *
     * @param string $routePattern
     * @param callable $handler
     * @return \Phalcon\Mvc\Router\RouteInterface
     * @throws Exception
     */
    public function post($routePattern, $handler)
    {
        if (!is_string($routePattern) || !is_callable($handler)) {
            throw new Exception('Invalid parameter type.');
        }

        /**
         * We create a router even if there is no one in the DI
         */
        $router = $this->getRouter();

        /**
         * Routes are added to the router
         */
        $route = $router->addPost($routePattern);

        /**
         * Using the id produced by the router we store the handler
         */
        $this->_handlers[$route->getRouteId()] = $handler;

        /**
         * The route is returned, the developer can add more things on it
         */
        return $route;
    }

    /**
     * Maps a route to a handler that only matches if the HTTP method is PUT
     *
     * @param string $routePattern
     * @param callable $handler
     * @return \Phalcon\Mvc\Router\RouteInterface
     * @throws Exception
     */
    public function put($routePattern, $handler)
    {
        if (!is_string($routePattern) || !is_callable($handler)) {
            throw new Exception('Invalid parameter type.');
        }

        /**
         * We create a router even if there is no one in the DI
         */
        $router = $this->getRouter();

        /**
         * Routes are added to the router
         */
        $route = $router->addPut($routePattern);

        /**
         * Using the id produced by the router we store the handler
         */
        $this->_handlers[$route->getRouteId()] = $handler;

        /**
         * The route is returned, the developer can add more things on it
         */
        return $route;
    }

    /**
     * Maps a route to a handler that only matches if the HTTP method is PATCH
     *
     * @param string $routePattern
     * @param callable $handler
     * @return \Phalcon\Mvc\Router\RouteInterface
     * @throws Exception
     */
    public function patch($routePattern, $handler)
    {
        if (!is_string($routePattern) || !is_callable($handler)) {
            throw new Exception('Invalid parameter type.');
        }

        /**
         * We create a router even if there is no one in the DI
         */
        $router = $this->getRouter();

        /**
         * Routes are added to the router
         */
        $route = $router->addPatch($routePattern);

        /**
         * Using the id produced by the router we store the handler
         */
        $this->_handlers[$route->getRouteId()] = $handler;

        /**
         * The route is returned, the developer can add more things on it
         */
        return $route;
    }

    /**
     * Maps a route to a handler that only matches if the HTTP method is HEAD
     *
     * @param string $routePattern
     * @param callable $handler
     * @return \Phalcon\Mvc\Router\RouteInterface
     * @throws Exception
     */
    public function head($routePattern, $handler)
    {
        if (!is_string($routePattern) || !is_callable($handler)) {
            throw new Exception('Invalid parameter type.');
        }

        /**
         * We create a router even if there is no one in the DI
         */
        $router = $this->getRouter();

        /**
         * Routes are added to the router
         */
        $route = $router->addHead($routePattern);

        /**
         * Using the id produced by the router we store the handler
         */
        $this->_handlers[$route->getRouteId()] = $handler;

        /**
         * The route is returned, the developer can add more things on it
         */
        return $route;
    }

    /**
     * Maps a route to a handler that only matches if the HTTP method is DELETE
     *
     * @param string $routePattern
     * @param callable $handler
     * @return \Phalcon\Mvc\Router\RouteInterface
     * @throws Exception
     */
    public function delete($routePattern, $handler)
    {
        if (!is_string($routePattern) || !is_callable($handler)) {
            throw new Exception('Invalid parameter type.');
        }

        /**
         * We create a router even if there is no one in the DI
         */
        $router = $this->getRouter();

        /**
         * Routes are added to the router
         */
        $route = $router->addDelete($routePattern);

        /**
         * Using the id produced by the router we store the handler
         */
        $this->_handlers[$route->getRouteId()] = $handler;

        /**
         * The route is returned, the developer can add more things on it
         */
        return $route;
    }

    /**
     * Maps a route to a handler that only matches if the HTTP method is OPTIONS
     *
     * @param string $routePattern
     * @param callable $handler
     * @return \Phalcon\Mvc\Router\RouteInterface
     * @throws Exception
     */
    public function options($routePattern, $handler)
    {
        if (!is_string($routePattern) || !is_callable($handler)) {
            throw new Exception('Invalid parameter type.');
        }

        /**
         * We create a router even if there is no one in the DI
         */
        $router = $this->getRouter();

        /**
         * Routes are added to the router
         */
        $route = $router->addOptions($routePattern);

        /**
         * Using the id produced by the router we store the handler
         */
        $this->_handlers[$route->getRouteId()] = $handler;

        /**
         * The route is returned, the developer can add more things on it
         */
        return $route;
    }

    /**
     * Mounts a collection of handlers
     *
     * @param \Phalcon\Mvc\CollectionInterface $collection
     * @return \Phalcon\Mvc\Micro
     * @throws Exception
     */
    public function mount($collection)
    {
        if (!is_object($collection) &&
            $collection instanceof CollectionInterface === false) {
            throw new Exception('The collection is not valid');
    }

        /**
         * Get the main handler
         */
        $mainHandler = $collection->getHandler();
        if (empty($mainHandler)) {
            throw new Exception('The collection requires a main handler');
        }

        $handlers = $collection->getHandlers();
        if (count($handlers) === 0) {
            throw new Exception('There are no handlers to mount');
        }

        if (is_array($handlers)) {

            /**
             * Check if handler is lazy
             */
            if ($collection->isLazy()) {
                $lazyHandler = new LazyLoader($mainHandler);
            } else {
                $lazyHandler = $mainHandler;
            }

            /**
             * Get the main prefix for the collection
             */
            $prefix = $collection->getPrefix();
            
            foreach ($handlers as $handler) {

                if (!is_array($handler)) {
                    throw new Exception('One of the registered handlers is invalid');
                }

                $methods = $handler[0];
                $pattern = $handler[1];
                $subHandler = $handler[2];
                $name   =  $handler[3];

                /**
                 * Create a real handler
                 */
                $realHandler = [$lazyHandler, $subHandler];

                if (!empty($prefix)) {
                    if ($pattern == '/') {
                        $prefixedPattern = $prefix;
                    } else {
                        $prefixedPattern = $prefix . $pattern;
                    }
                } else {
                    $prefixedPattern = $pattern;
                }

                /**
                 * Map the route manually
                 */
                $route = $this->map($prefixedPattern, $realHandler);
                
                if ((is_string($methods) && $methods != '') || is_array($methods)) {
                    $route->via($methods);
                }

                if (is_string($name)) {
                    $route->setName($name);
                }
            }
        }

        return $this;
    }

    /**
     * Sets a handler that will be called when the router doesn't match any of the defined routes
     *
     * @param callable $handler
     * @return \Phalcon\Mvc\Micro
     * @throws Exception
     */
    public function notFound($handler)
    {
        if (!is_callable($handler)) {
            throw new Exception('Invalid parameter type.');
        }

        $this->_notFoundHandler = $handler;

        return $this;
    }

    /**
     * Sets a handler that will be called when an exception is thrown handling the route
     *
     * @param callable handler
     * @return \Phalcon\Mvc\Micro
     */
    public function error($handler)
    {
        $this->_errorHandler = $handler;
        return $this;
    }

    /**
     * Returns the internal router used by the application
     *
     * @return \Phalcon\Mvc\RouterInterface
     */
    public function getRouter()
    {
        $router = $this->_router;

        if (!is_object($router)) {
            $router = $this->getSharedService('router');

            /**
             * Clear the set routes if any
             */
            $router->clear();

            /**
             * Automatically remove extra slashes
             */
            $router->removeExtraSlashes(true);

            /**
             * Update the internal router
             */
            $this->_router = $router;
        }

        return $router;
    }

    /**
     * Sets a service from the DI
     *
     * @param string $serviceName
     * @param mixed $definition
     * @param boolean|null $shared
     * @return \Phalcon\DI\ServiceInterface
     * @throws Exception
     */
    public function setService($serviceName, $definition, $shared = false)
    {
        if (!is_string($serviceName)) {
            throw new Exception('Invalid parameter type.');
        }

        if (!is_bool($shared)) {
            throw new Exception('Invalid parameter type.');
        }

        $dependencyInjector = $this->_dependencyInjector;
        if (!is_object($dependencyInjector)) {
            $dependencyInjector = new FactoryDefault();
            $this->_dependencyInjector = $dependencyInjector;
        }

        return $dependencyInjector->set($serviceName, $definition, $shared);
    }

    /**
     * Checks if a service is registered in the DI
     *
     * @param string $serviceName
     * @return boolean
     * @throws Exception
     */
    public function hasService($serviceName)
    {
        if (!is_string($serviceName)) {
            throw new Exception('Invalid parameter type.');
        }

        $dependencyInjector = $this->_dependencyInjector;
        if (!is_object($dependencyInjector)) {
            $dependencyInjector = new FactoryDefault();
            $this->_dependencyInjector = $dependencyInjector;
        }

        return $dependencyInjector->has($serviceName);
    }

    /**
     * Obtains a service from the DI
     *
     * @param string $serviceName
     * @return object
     * @throws Exception
     */
    public function getService($serviceName)
    {
        if (!is_string($serviceName)) {
            throw new Exception('Invalid parameter type.');
        }

        $dependencyInjector = $this->_dependencyInjector;
        if (!is_object($dependencyInjector)) {
            $dependencyInjector = new FactoryDefault();
            $this->_dependencyInjector = $dependencyInjector;
        }

        return $dependencyInjector->get($serviceName);
    }

    /**
     * Obtains a shared service from the DI
     *
     * @param string $serviceName
     * @return mixed
     * @throws Exception
     */
    public function getSharedService($serviceName)
    {
        if (!is_string($serviceName)) {
            throw new Exception('Invalid parameter type.');
        }

        $dependencyInjector = $this->_dependencyInjector;
        if (!is_object($dependencyInjector)) {
            $dependencyInjector = new FactoryDefault();
            $this->_dependencyInjector = $dependencyInjector;
        }

        return $dependencyInjector->getShared($serviceName);
    }

    /**
     * Handle the whole request
     *
     * @param string|null $uri
     * @return mixed
     * @throws Exception
     */
    public function handle($uri = null)
    {
        if (!is_string($uri) && !is_null($uri)) {
            throw new Exception('Invalid parameter type.');
        }

        $dependencyInjector = $this->_dependencyInjector;
        if (!is_object($dependencyInjector)) {
            throw new Exception('A dependency injection container is required to access related dispatching services');
        }

        try {

            $returnedValue = null;

            /**
             * Calling beforeHandle routing
             */
            $eventsManager = $this->_eventsManager;
            if (is_object($eventsManager)) {
                if ($this->_eventsManager->fire('micro:beforeHandleRoute', $this) === false) {
                    return false;
                }
            }

            /**
             * Handling routing information
             */
            $router = $dependencyInjector->getShared('router');

            /**
             * Handle the URI as normal
             */
            $router->handle($uri);

            /**
             * Check if one route was matched
             */
            $matchedRoute = $router->getMatchedRoute();
            if (is_object($matchedRoute)) {

                if (!isset($this->_handlers[$matchedRoute->getRouteId()])) {
                    throw new Exception("Matched route doesn't have an associate handler");
                } else {
                    $handler = $this->_handlers[$matchedRoute->getRouteId()];
                }

                /**
                 * Updating active handler
                 */
                $this->_activeHandler = $handler;

                /**
                 * Calling beforeExecuteRoute event
                 */
                if (is_object($eventsManager)) {
                    if ($eventsManager->fire('micro:beforeExecuteRoute', $this) === false) {
                        return false;
                    } else {
                        $handler = $this->_activeHandler;
                    }
                }

                $beforeHandlers = $this->_beforeHandlers;
                if (is_array($beforeHandlers)) {

                    $this->_stopped = false;

                    /**
                     * Calls the before handlers
                     */
                    foreach ($beforeHandlers as $before) {

                        if (is_object($before)) {
                            if ($before instanceof MiddlewareInterface) {

                                /**
                                 * Call the middleware
                                 */
                                $status = $before->call($this);

                                /**
                                 * Reload the status
                                 * break the execution if the middleware was stopped
                                 */
                                if ($this->_stopped) {
                                    break;
                                }

                                continue;
                            }
                        }

                        if (!is_callable($before)) {
                            throw new Exception("'before' handler is not callable");
                        }

                        /**
                         * Call the before handler, if it returns false exit
                         */
                        if (call_user_func($before) === false) {
                            return false;
                        }

                        /**
                         * Reload the 'stopped' status
                         */
                        if ($this->_stopped) {
                            return $status;
                        }                        
                    }
                }

                /**
                 * Calling the Handler in the PHP userland
                 */
                $params = $router->getParams();
                $returnedValue = call_user_func_array($handler, $params);

                /**
                 * Update the returned value
                 */
                $this->_returnedValue = $returnedValue;

                /**
                 * Calling afterExecuteRoute event
                 */
                if (is_object($eventsManager)) {
                    $eventsManager->fire('micro:afterExecuteRoute', $this);
                }

                $afterHandlers = $this->_afterHandlers;
                if (is_array($afterHandlers)) {

                    $this->_stopped = false;

                    /**
                     * Calls the after handlers
                     */
                    foreach ($afterHandlers as $after) {
                        
                        if (is_object($after)) {
                            if ($after instanceof MiddlewareInterface) {

                                /**
                                 * Call the middleware
                                 */
                                $status = $after->call($this);

                                /**
                                 * break the execution if the middleware was stopped
                                 */
                                if ($this->_stopped) {
                                    break;
                                }

                                continue;
                            }
                        }

                        if (!is_callable($after)) {
                            throw new Exception("'after' handler is not callable");
                        }

                        $status = call_user_func($after);
                    }
                }
            } else {

                /**
                 * Calling beforeNotFound event
                 */
                $eventsManager = $this->_eventsManager;
                if (is_object($eventsManager)) {
                    if ($eventsManager->fire('micro:beforeNotFound', $this) === false) {
                        return false;
                    }
                }

                /**
                 * Check if a notfoundhandler is defined and it's callable
                 */
                $notfoundhandler = $this->_notFoundHandler;
                if (!is_callable($notfoundhandler)) {
                    throw new Exception('Not-Found handler is not callable or is not defined');
                }

                /**
                 * Call the Not-Found handler
                 */
                $returnedValue = call_user_func($notfoundhandler);
            }

            /**
             * Calling afterHandleRoute event
             */
            if (is_object($this->_eventsManager)) {
                $this->_eventsManager->fire('micro:afterExecuteRoute', $this, $returnedValue);
            }

            $finishHandlers = $this->_finishHandlers;
            if (is_array($finishHandlers)) {

                $this->_stopped = false;

                $params = null;

                /**
                 * Calls the finish handlers
                 */
                foreach ($finishHandlers as $finish) {
                    
                    /**
                     * Try to execute middleware as plugins
                     */
                    if (is_object($finish)) {

                        if ($finish instanceof MiddlewareInterface) {

                            /**
                             * Call the middleware
                             */
                            $status = $finish->call($this);

                            /**
                             * break the execution if the middleware was stopped
                             */
                            if ($this->_stopped) {
                                break;
                            }

                            continue;
                        }
                    }

                    if (!is_callable($finish)) {
                        throw new Exception("One of the 'finish' handlers is not callable");
                    }

                    if ($params === null) {
                        $params = [$this];
                    }

                    /**
                     * Call the 'finish' middleware
                     */
                    $status = call_user_func_array($finish, $params);

                    /**
                     * break the execution if the middleware was stopped
                     */
                    if ($this->_stopped) {
                        break;
                    }
                }
            }
            
        } catch (\Exception $e) {

            /**
             * Calling beforeNotFound event
             */
            $eventsManager = $this->_eventsManager;
            if (is_object($eventsManager)) {
                $returnedValue = $eventsManager->fire('micro:beforeException', $this, $e);
            }

            /**
             * Check if an errorhandler is defined and it's callable
             */
            $errorhandler = $this->_errorHandler;
            if ($errorhandler) {

                if (!is_callable($errorhandler)) {
                    throw new Exception('Error handler is not callable');
                }

                /**
                 * Call the Error handler
                 */
                $returnedValue = call_user_func_array($errorhandler, [$e]);
                if (is_object($returnedValue)) {
                    if ($returnedValue instanceof ResponseInterface === false) {
                        throw $e;
                    }
                } else {
                    if ($returnedValue !== false) {
                        throw $e;
                    }
                }
            
            } else {
                if ($returnedValue !== false) {
                    throw $e;
                }
            }
        }

        /**
         * Check if the returned object is already a response
         */
        if (is_object($returnedValue)) {
            if ($returnedValue instanceof ResponseInterface) {
                /**
                 * Automatically send the response
                 */
                $returnedValue->send();
            }
        }

        return $returnedValue;
    }
    /**
     * Stops the middleware execution avoiding than other middlewares be executed
     */
    public function stop()
    {
        $this->_stopped = true;
    }

    /**
     * Sets externally the handler that must be called by the matched route
     *
     * @param callable $activeHandler
     * @throws Exception
     */
    public function setActiveHandler($activeHandler)
    {
        if (!is_callable($activeHandler)) {
            throw new Exception('Invalid parameter type.');
        }

        $this->_activeHandler = $activeHandler;
    }

    /**
     * Return the handler that will be called for the matched route
     *
     * @return callable|null
     */
    public function getActiveHandler()
    {
        return $this->_activeHandler;
    }

    /**
     * Returns the value returned by the executed handler
     *
     * @return mixed
     */
    public function getReturnedValue()
    {
        return $this->_returnedValue;
    }

    /**
     * Check if a service is registered in the internal services container using the array syntax.
     * Alias for \Phalcon\Mvc\Micro::hasService()
     *
     * @param string $alias
     * @return boolean
     */
    public function offsetExists($alias)
    {
        return $this->hasService($alias);
    }

    /**
     * Allows to register a shared service in the internal services container using the array syntax.
     * Alias for \Phalcon\Mvc\Micro::setService()
     *
     *<code>
     *  $app['request'] = new \Phalcon\Http\Request();
     *</code>
     *
     * @param string $alias
     * @param mixed $definition
     */
    public function offsetSet($alias, $definition)
    {
        return $this->setService($alias, $definition);
    }

    /**
     * Allows to obtain a shared service in the internal services container using the array syntax.
     * Alias for \Phalcon\Mvc\Micro::getService()
     *
     *<code>
     *  var_dump($di['request']);
     *</code>
     *
     * @param string $alias
     * @return mixed
     */
    public function offsetGet($alias)
    {
        return $this->getService($alias);
    }

    /**
     * Removes a service from the internal services container using the array syntax
     *
     * @param string $alias
     * @todo Not implemented
     */
    public function offsetUnset($alias)
    {
        return $alias;
    }

    /**
     * Appends a before middleware to be called before execute the route
     *
     * @param callable $handler
     * @return \Phalcon\Mvc\Micro
     * @throws Exception
     */
    public function before($handler)
    {
        $this->_beforeHandlers[] = $handler;

        return $this;
    }

    /**
     * Appends an 'after' middleware to be called after execute the route
     *
     * @param callable $handler
     * @return \Phalcon\Mvc\Micro
     * @throws Exception
     */
    public function after($handler)
    {
        $this->_afterHandlers[] = $handler;

        return $this;
    }

    /**
     * Appends a 'finish' middleware to be called when the request is finished
     *
     * @param callable $handler
     * @return \Phalcon\Mvc\Micro
     * @throws Exception
     */
    public function finish($handler)
    {
        $this->_finishHandlers[] = $handler;

        return $this;
    }

    /**
     * Returns the internal handlers attached to the application
     *
     * @return array|null
     */
    public function getHandlers()
    {
        return $this->_handlers;
    }
}
