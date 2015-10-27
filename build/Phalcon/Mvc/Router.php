<?php
/**
 * Router
 *
*/
namespace Phalcon\Mvc;

use \Phalcon\Di\InjectionAwareInterface;
use \Phalcon\DiInterface;
use \Phalcon\Mvc\RouterInterface;
use \Phalcon\Mvc\Router\Exception;
use \Phalcon\Mvc\Router\Route;
use \Phalcon\Mvc\Router\GroupInterface;
use \Phalcon\Mvc\Router\RouteInterface;
use \Phalcon\Events\ManagerInterface;
use \Phalcon\Events\EventsAwareInterface;

/**
 * Phalcon\Mvc\Router
 *
 * <p>Phalcon\Mvc\Router is the standard framework router. Routing is the
 * process of taking a URI endpoint (that part of the URI which comes after the base URL) and
 * decomposing it into parameters to determine which module, controller, and
 * action of that controller should receive the request</p>
 *
 *<code>
 *
 *  $router = new Phalcon\Mvc\Router();
 *
 *  $router->add(
 *      "/documentation/{chapter}/{name}.{type:[a-z]+}",
 *      array(
 *          "controller" => "documentation",
 *          "action"     => "show"
 *      )
 *  );
 *
 *  $router->handle();
 *
 *  echo $router->getControllerName();
 *</code>
 *
 */
class Router implements RouterInterface, InjectionAwareInterface, EventsAwareInterface
{
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
     * @var null|Phalcon\Events\ManagerInterface
     * @access protected
    */ 
    protected $_eventsManager;

    /**
     * URI source
     *
     * @var null|int
     * @access protected
    */
    protected $_uriSource;

    /**
     * Namespace
     *
     * @var null|string
     * @access protected
    */
    protected $_namespace = null;

    /**
     * Module
     *
     * @var null|string
     * @access protected
    */
    protected $_module = null;

    /**
     * Controller
     *
     * @var null|string
     * @access protected
    */
    protected $_controller = null;

    /**
     * Action
     *
     * @var null|string
     * @access protected
    */
    protected $_action = null;

    /**
     * Params
     *
     * @var null|array
     * @access protected
    */
    protected $_params;

    /**
     * Routes
     *
     * @var null|array
     * @access protected
    */
    protected $_routes;

    /**
     * Matched route
     *
     * @var null|\Phalcon\Mvc\Router\Route
     * @access protected
    */
    protected $_matchedRoute;

    /**
     * Matches
     *
     * @var null|array
     * @access protected
    */
    protected $_matches;

    /**
     * Was matched?
     *
     * @var boolean
     * @access protected
    */
    protected $_wasMatched = false;

    /**
     * Default namespace
     *
     * @var null|string
     * @access protected
    */
    protected $_defaultNamespace;

    /**
     * Default module
     *
     * @var null|string
     * @access protected
    */
    protected $_defaultModule;

    /**
     * Default controller
     *
     * @var null|string
     * @access protected
    */
    protected $_defaultController;

    /**
     * Default access
     *
     * @var null|string
     * @access protected
    */
    protected $_defaultAction;

    /**
     * Default params
     *
     * @var null|array
     * @access protected
    */
    protected $_defaultParams;

    /**
     * Remove extra slashes?
     *
     * @var null|boolean
     * @access protected
    */
    protected $_removeExtraSlashes;

    /**
     * NotFound-Paths
     *
     * @var null|array
     * @access protected
    */
    protected $_notFoundPaths;

    /**
     * URI source: _url
     *
     * @var int
    */
    const URI_SOURCE_GET_URL = 1;

    /**
     * URI source: REQUEST_URI
     *
     * @var int
    */
    const URI_SOURCE_SERVER_REQUEST_URI = 0;

    /**
     * POSITION_FIRST
     *
     * @var int
    */
    const POSITION_FIRST = 0;

    /**
     * POSITION_LAST
     *
     * @var int
    */
    const POSITION_LAST = 1;

    /**
     * \Phalcon\Mvc\Router constructor
     *
     * @param boolean|null $defaultRoutes
     * @throws Exception
     */
    public function __construct($defaultRoutes = true)
    {
        if (!is_bool($defaultRoutes)) {
            throw new Exception('Invalid parameter type.');
        }

        $routes = [];

        if ($defaultRoutes) {
            /*
             * Two routes are added by default to match /:controller/:action and
             * /:controller/:action/:params
             */
            $routes[] = new Route('#^/([\\w0-9\\_\\-]+)[/]{0,1}$#u', ['controller' => 1]);
            $routes[] = new Route(
                '#^/([\\w0-9\\_\\-]+)/([\\w0-9\\.\\_]+)(/.*)*$#u',
                ['controller' => 1, 'action' => 2, 'params' => 3]
                );
        }

        $this->_params = [];
        $this->_defaultParams = [];
        $this->_routes = $routes;
    }

    /**
     * Sets the dependency injector
     *
     * @param \Phalcon\DiInterface $dependencyInjector
     * @throws Exception
     */
    public function setDI($dependencyInjector)
    {
        if (!is_object($dependencyInjector)||
            $dependencyInjector instanceof DiInterface === false) {
            throw new Exception('Dependency Injector is invalid');
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
     * Sets the event manager
     *
     * @param \Phalcon\Events\ManagerInterface $eventsManager
     */
    public function setEventsManager($eventsManager)
    {
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
     * Get rewrite info. This info is read from $_GET['_url']. This returns '/' if the rewrite information cannot be read
     *
     * @return string
     */
    public function getRewriteUri()
    {
        
        /*
         * The developer can change the URI source
         */
        if (!$this->_uriSource) {
            
            /**
             * By default use the standard $_SERVER['REQUEST_URI']
             */
            if (isset($_SERVER['REQUEST_URI'])) {
                $urlParts = explode('?', $_SERVER['REQUEST_URI']);
                if (!empty($urlParts[0])) {
                    return $urlParts[0];
                }
            }
        } else {
            
            /**
            * Otherwise we use $_GET['url'] to obtain the rewrite information
            */
            if (isset($_GET['_url'])) {
                if (!empty($_GET['_url'])) {
                    return $_GET['_url'];
                }
            }
        }

        return '/';
    }

    /**
     * Sets the URI source. One of the URI_SOURCE_* constants
     *
     *<code>
     *  $router->setUriSource(Router::URI_SOURCE_SERVER_REQUEST_URI);
     *</code>
     *
     * @param int $uriSource
     * @return \Phalcon\Mvc\RouterInterface
     * @throws Exception
     */
    public function setUriSource($uriSource)
    {
        if (!is_int($uriSource)) {
            throw new Exception('Invalid parameter type.');
        }

        $this->_uriSource = $uriSource;

        return $this;
    }

    /**
     * Set whether router must remove the extra slashes in the handled routes
     *
     * @param boolean $remove
     * @return \Phalcon\Mvc\RouterInterface
     * @throws Exception
     */
    public function removeExtraSlashes($remove)
    {
        if (!is_bool($remove)) {
            throw new Exception('Invalid parameter type.');
        }

        $this->_removeExtraSlashes = $remove;

        return $this;
    }

    /**
     * Sets the name of the default namespace
     *
     * @param string $namespaceName
     * @return \Phalcon\Mvc\RouterInterface
     * @throws Exception
     */
    public function setDefaultNamespace($namespaceName)
    {
        if (!is_string($namespaceName)) {
            throw new Exception('Invalid parameter type.');
        }

        $this->_defaultNamespace = $namespaceName;

        return $this;
    }

    /**
     * Sets the name of the default module
     *
     * @param string $moduleName
     * @return \Phalcon\Mvc\RouterInterface
     * @throws Exception
     */
    public function setDefaultModule($moduleName)
    {
        if (!is_string($moduleName)) {
            throw new Exception('Invalid parameter type.');
        }

        $this->_defaultModule = $moduleName;

        return $this;
    }

    /**
     * Sets the default controller name
     *
     * @param string $controllerName
     * @return \Phalcon\Mvc\RouterInterface
     * @throws Exception
     */
    public function setDefaultController($controllerName)
    {
        if (!is_string($controllerName)) {
            throw new Exception('Invalid parameter type.');
        }

        $this->_defaultController = $controllerName;

        return $this;
    }

    /**
     * Sets the default action name
     *
     * @param string $actionName
     * @return \Phalcon\Mvc\RouterInterface
     * @throws Exception
     */
    public function setDefaultAction($actionName)
    {
        if (!is_string($actionName)) {
            throw new Exception('Invalid parameter type.');
        }

        $this->_defaultAction = $actionName;

        return $this;
    }

    /**
     * Sets an array of default paths. If a route is missing a path the router will use the defined here
     * This method must not be used to set a 404 route
     *
     *<code>
     * $router->setDefaults(array(
     *      'module' => 'common',
     *      'action' => 'index'
     * ));
     *</code>
     *
     * @param array $defaults
     * @return \Phalcon\Mvc\RouterInterface
     * @throws Exception
     */
    public function setDefaults($defaults)
    {
        if (!is_array($defaults)) {
            throw new Exception('Defaults must be an array');
        }

        //Set a default namespace
        if (isset($defaults['namespace'])) {
            $this->_defaultNamespace = $defaults['namespace'];
        }

        //Set a default module
        if (isset($defaults['module'])) {
            $this->_defaultModule = $defaults['module'];
        }

        //Set a default controller
        if (isset($defaults['controller'])) {
            $this->_defaultController = $defaults['controller'];
        }

        //Set a default action
        if (isset($defaults['action'])) {
            $this->_defaultAction = $defaults['action'];
        }

        //Set default parameters
        if (isset($defaults['params'])) {
            $this->_defaultParams = $defaults['params'];
        }

        return $this;
    }

    /**
     * Returns an array of default parameters
     *
     * @return array
     */
    public function getDefault()
    {
        return [
            "namespace"=> $this->_defaultNamespace,
            "module"=> $this->_defaultModule,
            "controller"=> $this->_defaultController,
            "action"=> $this->_defaultAction,
            "params"=> $this->_defaultParams
        ];
    }

    /**
     * Handles routing information received from the rewrite engine
     *
     *<code>
     * //Read the info from the rewrite engine
     * $router->handle();
     *
     * //Manually passing an URL
     * $router->handle('/posts/edit/1');
     *</code>
     *
     * @param string|null $uri
     * @throws Exception
     */
    public function handle($uri = null)
    {
        if (!is_string($uri) && !is_null($uri)) {
            throw new Exception('Invalid parameter type.');
        }

        if (!$uri) {
            /**
             * If 'uri' isn't passed as parameter it reads _GET['_url']
             */
            $realUri = $this->getRewriteUri();
        } else {
            $realUri = $uri;
        }

        /**
         * Remove extra slashes in the route
         */
        if ($this->_removeExtraSlashes && $realUri != '/') {
            $handledUri = rtrim($realUri, '/');
        } else {
            $handledUri = $realUri;
        }

        $request = null;
        $currentHostName = null;
        $routeFound = false;
        $parts = [];
        $params = [];
        $matches = null;
        $this->_wasMatched = false;
        $this->_matchedRoute = null;

        $eventsManager = $this->_eventsManager;

        if (is_object($eventsManager)) {
            $eventsManager->fire('router:beforeCheckRoutes' , $this);
        }

        /**
         * Routes are traversed in reversed order
         */
        $routes = array_reverse($this->_routes);

        foreach ($routes as $route) {
            
            /**
             * Look for HTTP method constraints
             */
            $methods = $route->getHttpMethods();
            if($methods !== null) {

                /**
                 * Retrieve the request service from the container
                 */
                if ($request === null) {
                    
                    $dependencyInjector = $this->_dependencyInjector;
                    if (!is_object($dependencyInjector)) {
                        throw new Exception("A dependency injection container is required to access the 'request' service");
                    }

                    $request = $dependencyInjector->getShared('request');
                }

                /**
                 * Check if the current method is allowed by the route
                 */
                if ($request->isMethod($methods, true) === false) {
                    continue;
                }
            }

            /**
             * Look for hostname constraints
             */
            $hostname = $route->getHostname();
            if ($hostname !== null) {

                /**
                 * Retrieve the request service from the container
                 */
                if ($request === null) {
                    
                    $dependencyInjector = $this->_dependencyInjector;
                    if (!is_object($dependencyInjector)) {
                        throw new Exception("A dependency injection container is required to access the 'request' service");
                    }

                    $request = $dependencyInjector->getShared('request');
                }

                /**
                 * Check if the current hostname is the same as the route
                 */
                if (!is_object($currentHostName)) {
                    $currentHostName = $request->getHttpHost();
                }

                /**
                 * No HTTP_HOST, maybe in CLI mode?
                 */
                if (is_null($currentHostName)) {
                    continue;
                }

                /**
                 * Check if the hostname restriction is the same as the current in the route
                 */
                if (strpos($hostname, '(') !== false) {
                    if (strpos($hostname, '#') === false) {
                        $regexHostName = '#^' . $hostname . '$#';
                    } else {
                        $regexHostName = $hostname;
                    }
                    $matched = preg_match($regexHostName, $currentHostName);
                } else {
                    $matched = $currentHostName == $hostname;
                }

                if (!$matched) {
                    continue;
                }
            }

            if(is_object($eventsManager)) {
                $eventsManager->fire('router:beforeCheckRoute', $this, $route);
            }

            /**
             * If the route has parentheses use preg_match
             */
            $pattern = $route->getCompiledPattern();

            if (strpos($pattern, '^') !== false) {
                $routeFound = preg_match($pattern, $handledUri, $matches);
            } else {
                $routeFound = $pattern == $handledUri;
            }

            /**
             * Check for beforeMatch conditions
             */
            if ($routeFound) {
                
                if (is_object($eventsManager)) {
                    $eventsManager->fire('router:matchedRoute', $this, $route);
                }

                $beforeMatch = $route->getBeforeMatch();
                if ($beforeMatch !== null) {

                    /**
                     * Check first if the callback is callable
                     */
                    if (!is_callable($beforeMatch)) {
                        throw new Exception("Before-Match callback is not callable in matched route");
                    }

                    /**
                     * Check first if the callback is callable
                     */
                    $routeFound = call_user_func_array($beforeMatch, [$handledUri, $route, $this]);
                }
            } else {
                if (is_object($eventsManager)) {
                    $routeFound = $eventsManager->fire('router:notMatchedRoute', $this, $route);
                }
            }

            if ($routeFound) {

                /**
                 * Start from the default paths
                 */
                $paths = $route->getPaths();
                $parts = $paths;

                /**
                 * Check if the matches has variables
                 */
                if (is_array($matches)) {

                    /**
                     * Get the route converters if any
                     */
                    $converters = $route->getConverters();

                    foreach ($paths as $part => $position) {
                        
                        if (isset($matches[$position])) {
                            $matchPosition = $matches[$position];

                            /**
                             * Check if the part has a converter
                             */
                            if (is_array($converters)) {
                                if (isset($converters[$part])) {
                                    $converter = $converters[$part];
                                    $parts[$parts] = call_user_func_array($converter, [$matchPosition]);
                                    continue;
                                }
                            }

                            /**
                             * Update the parts if there is no converter
                             */
                            $parts[$part] = $matchPosition;

                        } else {

                            /**
                             * Apply the converters anyway
                             */
                            if (is_array($converters)) {
                                if (isset($converters[$part])) {
                                    $parts[$part] = call_user_func_array($converter, [$position]);
                                }
                            }
                        }                       
                    }

                    /**
                     * Update the matches generated by preg_match
                     */
                    $this->_matches = $matches;
                }

                $this->_matchedRoute = $route;
                break;
            }          
        }

        /**
         * Update the wasMatched property indicating if the route was matched
         */
        if ($routeFound) {
            $this->_wasMatched = true;
        } else {
            $this->_wasMatched = false;
        }
        
        /**
         * The route wasn't found, try to use the not-found paths
         */
        if (!$routeFound) {
            $notFoundPaths = $this->_notFoundPaths;
            if ($notFoundPaths !== null) {
                $parts = Route::getRoutePaths($notFoundPaths);
                $routeFound = true;
            }
        }


        /**
         * Use default values before we overwrite them if the route is matched
         */
        $this->_namespace = $this->_defaultNamespace;
        $this->_module = $this->_defaultModule;
        $this->_controller = $this->_defaultController;
        $this->_action = $this->_defaultAction;
        $this->_params = $this->_defaultParams;

        if ($routeFound) {

            /**
             * Check for a namespace
             */
            if (isset($parts['namespace'])) {
                if (!is_numeric($parts['namespace'])) {
                    $this->_namespace = $parts['namespace'];
                }
                unset($parts['namespace']);
            }

            /**
             * Check for a module
             */
            if (isset($parts['module'])) {
                if (!is_numeric($parts['module'])) {
                    $this->_module = $parts['module'];
                }
                unset($parts['module']);
            }

            /**
             * Check for a controller
             */
            if (isset($parts['controller'])) {
                if (!is_numeric($parts['controller'])) {
                    $this->_controller = $parts['controller'];
                }
                unset($parts['controller']);
            }

            /**
             * Check for an action
             */
            if (isset($parts['action'])) {
                if (!is_numeric($parts['action'])) {
                    $this->_action = $parts['action'];
                }
                unset($parts['action']);
            }

            /**
             * Check for parameters
             */
            if (isset($parts['params'])) {
                $paramsStr = $parts['params'];
                if (is_string($paramsStr)) {
                    $strParams = trim($paramsStr, '/');
                    if ($strParams !== '') {
                        $params = explode('/', $strParams);
                    }
                }
                unset($parts['params']);
            }

            if (count($params)) {
                $this->_params = array_merge($params, $parts);
            } else {
                $this->_params = $parts;
            }

            if (is_object($eventsManager)) {
                $eventsManager->fire('router:afterCheckRoutes', $this);
            }
        }
    }

    /**
     * Adds a route to the router without any HTTP constraint
     *
     *<code>
     * $router->add('/about', 'About::index');
     * $router->add('/about', 'About::index', ['GET', 'POST']);
     * $router->add('/about', 'About::index', ['GET', 'POST'], Router::POSITION_FIRST);
     *</code>
     *
     * @param string $pattern
     * @param mixed $paths
     * @param string|null $httpMethods
     * @return \Phalcon\Mvc\Router\RouteInterface
     */
    public function add($pattern, $paths = null, $httpMethods = null, $position = Router::POSITION_LAST)
    {       
        /**
         * Every route is internally stored as a Phalcon\Mvc\Router\Route
         */
        $route = new Route($pattern, $paths, $httpMethods);

        switch ($position) {
            
            case self::POSITION_LAST:
                $this->_routes[] = $route;
                break;

            case self::POSITION_FIRST:
                $this->_routes = array_merge([$route], $this->_routes);
                break;
            
            default:
                throw new Exception("Invalid route position");
        }

        return $route;
    }

    /**
     * Adds a route to the router that only match if the HTTP method is GET
     *
     * @param string $pattern
     * @param mixed $paths
     * @return \Phalcon\Mvc\Router\RouteInterface
     */
    public function addGet($pattern, $paths = null, $position = Router::POSITION_LAST)
    {
        return $this->add($pattern, $paths, 'GET', $position);
    }

    /**
     * Adds a route to the router that only match if the HTTP method is POST
     *
     * @param string $pattern
     * @param mixed $paths
     * @return \Phalcon\Mvc\Router\RouteInterface
     */
    public function addPost($pattern, $paths = null, $position = Router::POSITION_LAST)
    {
        return $this->add($pattern, $paths, 'POST', $position);
    }

    /**
     * Adds a route to the router that only match if the HTTP method is PUT
     *
     * @param string $pattern
     * @param mixed $paths
     * @return \Phalcon\Mvc\Router\RouteInterface
     */
    public function addPut($pattern, $paths = null, $position = Router::POSITION_LAST)
    {
        return $this->add($pattern, $paths, 'PUT', $position);
    }

    /**
     * Adds a route to the router that only match if the HTTP method is PATCH
     *
     * @param string $pattern
     * @param mixed $paths
     * @return \Phalcon\Mvc\Router\RouteInterface
     */
    public function addPatch($pattern, $paths = null, $position = Router::POSITION_LAST)
    {
        return $this->add($pattern, $paths, 'PATCH', $position);
    }

    /**
     * Adds a route to the router that only match if the HTTP method is DELETE
     *
     * @param string $pattern
     * @param mixed $paths
     * @return \Phalcon\Mvc\Router\RouteInterface
     */
    public function addDelete($pattern, $paths = null, $position = Router::POSITION_LAST)
    {
        return $this->add($pattern, $paths, 'DELETE', $position);
    }

    /**
     * Add a route to the router that only match if the HTTP method is OPTIONS
     *
     * @param string $pattern
     * @param mixed $paths
     * @return \Phalcon\Mvc\Router\RouteInterface
     */
    public function addOptions($pattern, $paths = null, $position = Router::POSITION_LAST)
    {
        return $this->add($pattern, $paths, 'OPTIONS', $position);
    }

    /**
     * Adds a route to the router that only match if the HTTP method is HEAD
     *
     * @param string $pattern
     * @param mixed $paths
     * @return \Phalcon\Mvc\Router\RouteInterface
     */
    public function addHead($pattern, $paths = null, $position = Router::POSITION_LAST)
    {
        return $this->add($pattern, $paths, 'HEAD', $position);
    }

    /**
     * Mounts a group of routes in the router
     *
     * @param \Phalcon\Mvc\Router\GroupInterface $group
     * @return \Phalcon\Mvc\RouteInterface
     * @throws Exception
     */
    public function mount($group)
    {
        if (!is_object($group) ||
            $group instanceof GroupInterface === false) {
            throw new Exception('The group of routes is not valid');
        }

        $groupRoutes = $group->getRoutes();
        if (!count($groupRoutes)) {
            throw new Exception('The group of routes does not contain any routes');
        }

        /**
         * Get the before-match condition
         */
        $beforeMatch = $group->getBeforeMatch();
        
        if ($beforeMatch !== null) {
            foreach ($groupRoutes as $route) {
                $route->beforeMatch($beforeMatch);
            }
        }

        //Get the hostname restrictions
        $hostname = $group->getHostname();
        
        if ($hostname !== null) {
            foreach ($groupRoutes as $route) {
                $route->setHostname($hostname);
            }
        }

        $routes = $this->_routes;


        if (is_array($this->_routes)) {
            $this->_routes = array_merge($routes, $groupRoutes);
        } else {
            $this->_routes = $groupRoutes;
        }

        return $this;
    }

    /**
     * Set a group of paths to be returned when none of the defined routes are matched
     *
     * @param array|string $paths
     * @return \Phalcon\Mvc\RouteInterface
     * @throws Exception
     */
    public function notFound($paths)
    {
        if (!is_array($paths) && !is_string($paths)) {
            throw new Exception('The not-found paths must be an array or string');
        }

        $this->_notFoundPaths = $paths;

        return $this;
    }

    /**
     * Removes all the pre-defined routes
     */
    public function clear()
    {
        $this->_routes = [];
    }

    /**
     * Returns the processed namespace name
     *
     * @return string|null
     */
    public function getNamespaceName()
    {
        return $this->_namespace;
    }

    /**
     * Returns the processed module name
     *
     * @return string|null
     */
    public function getModuleName()
    {
        return $this->_module;
    }

    /**
     * Returns the processed controller name
     *
     * @return string|null
     */
    public function getControllerName()
    {
        return $this->_controller;
    }

    /**
     * Returns the processed action name
     *
     * @return string|null
     */
    public function getActionName()
    {
        return $this->_action;
    }

    /**
     * Returns the processed parameters
     *
     * @return array|null
     */
    public function getParams()
    {
        return $this->_params;
    }

    /**
     * Returns the route that matchs the handled URI
     *
     * @return \Phalcon\Mvc\Router\RouteInterface|null
     */
    public function getMatchedRoute()
    {
        return $this->_matchedRoute;
    }

    /**
     * Returns the sub expressions in the regular expression matched
     *
     * @return array|null
     */
    public function getMatches()
    {
        $this->_matches;
    }

    /**
     * Checks if the router macthes any of the defined routes
     *
     * @return boolean
     */
    public function wasMatched()
    {
        return $this->_wasMatched;
    }

    /**
     * Returns all the routes defined in the router
     *
     * @return \Phalcon\Mvc\Router\RouteInterface[]|null
     */
    public function getRoutes()
    {
        return $this->_routes;
    }

    /**
     * Returns a route object by its id
     *
     * @param mixed $id
     * @return \Phalcon\Mvc\Router\RouteInterface|boolean
     * @throws Exception
     */
    public function getRouteById($id)
    {
        foreach ($this->_routes as $route) {
            if ($route->getRouteId() === $id) {
                return $route;
            }
        }

        return false;
    }

    /**
     * Returns a route object by its name
     *
     * @param string $name
     * @return \Phalcon\Mvc\Router\RouteInterface | boolean
     * @throws Exception
     */
    public function getRouteByName($name)
    {
        if (!is_string($name)) {
            throw new Exception('Invalid parameter type.');
        }

        foreach ($this->_routes as $route) {
            if ($route->getName() == $name) {
                return $route;
            }
        }

        return false;
    }

    /**
     * Returns whether controller name should not be mangled
     */
    public function isExactControllerName()
    {
        return true;
    }
}
