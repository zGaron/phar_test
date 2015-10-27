<?php
/**
 * Group
 *
*/
namespace Phalcon\Mvc\Router;

use \Phalcon\Mvc\Router\Exception;
use \Phalcon\Mvc\Router\Route;

/**
 * Phalcon\Mvc\Router\Group
 *
 * Helper class to create a group of routes with common attributes
 *
 *<code>
 * $router = new Phalcon\Mvc\Router();
 *
 * //Create a group with a common module and controller
 * $blog = new Phalcon\Mvc\Router\Group(array(
 *  'module' => 'blog',
 *  'controller' => 'index'
 * ));
 *
 * //All the routes start with /blog
 * $blog->setPrefix('/blog');
 *
 * //Add a route to the group
 * $blog->add('/save', array(
 *  'action' => 'save'
 * ));
 *
 * //Add another route to the group
 * $blog->add('/edit/{id}', array(
 *  'action' => 'edit'
 * ));
 *
 * //This route maps to a controller different than the default
 * $blog->add('/blog', array(
 *  'controller' => 'about',
 *  'action' => 'index'
 * ));
 *
 * //Add the group to the router
 * $router->mount($blog);
 *</code>
 *
 */
class Group implements GroupInterface
{
    
    /**
     * Prefix
     *
     * @var null|string
     * @access protected
    */
    protected $_prefix;

    /**
     * Hostname
     *
     * @var null|string
     * @access protected
    */
    protected $_hostname;

    /**
     * Paths
     *
     * @var null|array|string
     * @access protected
    */
    protected $_paths;

    /**
     * Routes
     *
     * @var null|array
     * @access protected
    */
    protected $_routes;

    /**
     * Before Match
     *
     * @var null|string
     * @access protected
    */
    protected $_beforeMatch;

    /**
     * \Phalcon\Mvc\Router\Group constructor
     *
     * @param array|null $paths
     * @throws Exception
     */
    public function __construct($paths = null)
    {
        if (is_array($paths) || is_string($paths)) {
            $this->_paths = $paths;
        } else {
            throw new Exception('Invalid parameter type.');
        }

        if (method_exists($this, 'initialize')) {
            //$this->initialize($paths);
            $this->{'initialize'}($paths);
        }
    }

    /**
     * Set a hostname restriction for all the routes in the group
     *
     * @param string $hostname
     * @return \Phalcon\Mvc\Router\GroupInterface
     * @throws Exception
     */
    public function setHostname($hostname)
    {
        if (!is_string($hostname)) {
            throw new Exception('Invalid parameter type.');
        }

        $this->_hostname = $hostname;

        return $this;
    }

    /**
     * Returns the hostname restriction
     *
     * @return string|null
     */
    public function getHostname()
    {
        return $this->_hostname;
    }

    /**
     * Set a common uri prefix for all the routes in this group
     *
     * @param string $prefix
     * @return \Phalcon\Mvc\Router\GroupInterface
     */
    public function setPrefix($prefix)
    {
        if (!is_string($prefix)) {
            throw new Exception('Invalid parameter type.');
        }

        $this->_prefix = $prefix;

        return $this;
    }

    /**
     * Returns the common prefix for all the routes
     *
     * @return string|null
     */
    public function getPrefix()
    {
        return $this->_prefix;
    }

    /**
     * Sets a callback that is called if the route is matched.
     * The developer can implement any arbitrary conditions here
     * If the callback returns false the route is treated as not matched
     *
     * @param callable string $prefix
     * @return \Phalcon\Mvc\Router\GroupInterface
     * @throws Exception
     */
    public function beforeMatch($beforeMatch)
    {
        if (is_string($beforeMatch) === false) {
            throw new Exception('Invalid parameter type.');
        }

        $this->_beforeMatch = $beforeMatch;

        return $this;
    }

    /**
     * Returns the 'before-match' condition if any
     *
     * @return callable string|null
     */
    public function getBeforeMatch()
    {
        return $this->_beforeMatch;
    }

    /**
     * Set common paths for all the routes in the group
     *
     * @param mixed $paths
     * @return \Phalcon\Mvc\Router\GroupInterface
     */
    public function setPaths($paths)
    {
        $this->_paths = $paths;
    }

    /**
     * Returns the common paths defined for this group
     *
     * @return array|string|null
     */
    public function getPaths()
    {
        return $this->_paths;
    }

    /**
     * Returns the routes added to the group
     *
     * @return \Phalcon\Mvc\Router\RouteInterface[]|null
     */
    public function getRoutes()
    {
        return $this->_routes;
    }

    /**
     * Adds a route to the router on any HTTP method
     *
     *<code>
     * $router->add('/about', 'About::index');
     *</code>
     *
     * @param string $pattern
     * @param mixed $paths
     * @param string|null $httpMethods
     * @return \Phalcon\Mvc\Router\RouteInterface
     */
    public function add($pattern, $paths = null, $httpMethods = null)
    {
        return $this->_addRoute($pattern, $paths, $httpMethods);
    }

    /**
     * Adds a route to the router that only match if the HTTP method is GET
     *
     * @param string $pattern
     * @param mixed $paths
     * @return \Phalcon\Mvc\Router\RouteInterface
     */
    public function addGet($pattern, $paths = null)
    {
        return $this->_addRoute($pattern, $paths, 'GET');
    }

    /**
     * Adds a route to the router that only match if the HTTP method is POST
     *
     * @param string $pattern
     * @param mixed $paths
     * @return \Phalcon\Mvc\Router\RouteInterface
     */
    public function addPost($pattern, $paths = null)
    {
        return $this->_addRoute($pattern, $paths, 'POST');
    }

    /**
     * Adds a route to the router that only match if the HTTP method is PUT
     *
     * @param string $pattern
     * @param mixed $paths
     * @return \Phalcon\Mvc\Router\RouteInterface
     */
    public function addPut($pattern, $paths = null)
    {
        return $this->_addRoute($pattern, $paths, 'PUT');
    }

    /**
     * Adds a route to the router that only match if the HTTP method is PATCH
     *
     * @param string $pattern
     * @param mixed $paths
     * @return \Phalcon\Mvc\Router\RouteInterface
     */
    public function addPatch($pattern, $paths = null)
    {
        return $this->_addRoute($pattern, $paths, 'PATCH');
    }

    /**
     * Adds a route to the router that only match if the HTTP method is DELETE
     *
     * @param string $pattern
     * @param mixed $paths
     * @return \Phalcon\Mvc\Router\RouteInterface
     */
    public function addDelete($pattern, $paths = null)
    {
        return $this->_addRoute($pattern, $paths, 'DELETE');
    }

    /**
     * Add a route to the router that only match if the HTTP method is OPTIONS
     *
     * @param string $pattern
     * @param mixed $paths
     * @return \Phalcon\Mvc\Router\RouteInterface
     */
    public function addOptions($pattern, $paths = null)
    {
        return $this->_addRoute($pattern, $paths, 'OPTIONS');
    }

    /**
     * Adds a route to the router that only match if the HTTP method is HEAD
     *
     * @param string $pattern
     * @param mixed $paths
     * @return \Phalcon\Mvc\Router\RouteInterface
     */
    public function addHead($pattern, $paths = null)
    {
        return $this->_addRoute($pattern, $paths, 'HEAD');
    }

    /**
     * Removes all the pre-defined routes
     */
    public function clear()
    {
        $this->_routes = [];
    }

    /**
     * Adds a route applying the common attributes
     *
     * @param string $patten
     * @param Mixed $paths
     * @param Mixed $httpMethods
     * @return \Phalcon\Mvc\Router\RouteInterface
     * @throws Exception
     */
    protected function _addRoute($pattern, $paths = null, $httpMethods = null)
    {
        if (!is_string($pattern)) {
            throw new Exception('Invalid parameter type.');
        }

        /**
         * Check if the paths need to be merged with current paths
         */
        $defaultPaths = $this->_paths;

        if (is_array($defaultPaths)) {

            if (is_string($paths)) {
                $processedPaths = Route::getRoutePaths($paths);
            } else {
                $processedPaths = $paths;
            }

            if (is_array($processedPaths)) {

                /**
                 * Merge the paths with the default paths
                 */
                $mergedPaths = array_merge($defaultPaths, $processedPaths);
            } else {
                $mergedPaths = $defaultPaths;
            }
        } else {
            $mergedPaths = $paths;
        }

        /**
         * Every route is internally stored as a Phalcon\Mvc\Router\Route
         */
        $route = new Route($this->_prefix . $pattern, $mergedPaths, $httpMethods);
        $this->_routes[] = $route;

        $route->setGroup($this);
        return $route;
    }
}
