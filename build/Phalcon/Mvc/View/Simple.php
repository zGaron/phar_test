<?php
/**
 * Simple
 *
*/

namespace Phalcon\Mvc\View;

use \Phalcon\Di\Injectable;
use \Phalcon\Mvc\ViewBaseInterface;
use \Phalcon\Cache\BackendInterface;
use \Phalcon\Mvc\View\Engine\Php as PhpEngine;
use \Phalcon\Mvc\View\Exception;

/**
 * Phalcon\Mvc\View\Simple
 *
 * This component allows to render views without hicherquical levels
 *
 *<code>
 * $view = new Phalcon\Mvc\View\Simple();
 * echo $view->render('templates/my-view', array('content' => $html));
 * //or with filename with extension
 * echo $view->render('templates/my-view.volt', array('content' => $html));
 *</code>
 *
 */
class Simple extends Injectable implements ViewBaseInterface
{

    /**
     * Options
     *
     * @var null|array
     * @access protected
    */
    protected $_options;

    /**
     * Views Directory
     *
     * @var null|string
     * @access protected
    */
    protected $_viewsDir;

    /**
     * Partials Directory
     *
     * @var null|string
     * @access protected
    */
    protected $_partialsDir;

    /**
     * View Parameters
     *
     * @var null|array
     * @access protected
    */
    protected $_viewParams;

    /**
     * Engines
     *
     * @var boolean
     * @access protected
    */
    protected $_engines = false;

    /**
     * Registered Engines
     *
     * @var null|array
     * @access protected
    */
    protected $_registeredEngines;

    /**
     * Active Render Path
     *
     * @var null|string
     * @access protected
    */
    protected $_activeRenderPath;

    /**
     * Content
     *
     * @var null|string
     * @access protected
    */
    protected $_content;

    /**
     * Cache
     *
     * @var boolean|\Phalcon\Cache\BackendInterface
     * @access protected
    */
    protected $_cache = false;

    /**
     * Cache Options
     *
     * @var null|array
     * @access protected
    */
    protected $_cacheOptions;

    /**
     * \Phalcon\Mvc\View constructor
     *
     * @param array|null $options
     */
    public function __construct($options = null)
    {
        if (is_array($options)) {
            $this->_options = $options;
        }
    }

    /**
     * Sets views directory. Depending of your platform, always add a trailing slash or backslash
     *
     * @param string $viewsDir
     * @throws Exception
     */
    public function setViewsDir($viewsDir)
    {
        if (!is_string($viewsDir)) {
            throw new Exception('Invalid parameter type.');
        }

        $this->_viewsDir = $viewsDir;
    }

    /**
     * Gets views directory
     *
     * @return string|null
     */
    public function getViewsDir()
    {
        return $this->_viewsDir;
    }

    /**
     * Register templating engines
     *
     *<code>
     *$this->view->registerEngines(array(
     *  ".phtml" => "Phalcon\Mvc\View\Engine\Php",
     *  ".volt" => "Phalcon\Mvc\View\Engine\Volt",
     *  ".mhtml" => "MyCustomEngine"
     *));
     *</code>
     *
     * @param array $engines
     * @throws Exception
     */
    public function registerEngines($engines)
    {
        if (!is_array($engines)) {
            throw new Exception('Engines to register must be an array');
        }

        $this->_registeredEngines = $engines;
    }

    /**
     * Get templating engines
     *
     * @return array
     */
    public function getRegisteredEngines()
    {
        return $this->_registeredEngines;
    }

    /**
     * Loads registered template engines, if none is registered it will use \Phalcon\Mvc\View\Engine\Php
     *
     * @return array
     * @throws Exception
     */
    protected function _loadTemplateEngines()
    {
        
        /**
         * If the engines aren't initialized 'engines' is false
         */
        $engines = $this->_engines;
        if ($engines === false) {

            $dependencyInjector = $this->_dependencyInjector;

            $engines = [];

            $registerEngines = $this->_registeredEngines;
            if (!is_array($registerEngines)) {

                /**
                 * We use \Phalcon\Mvc\View\Engine\Php as default
                 * Use .phtml as extension for the PHP engine
                 */
                $engines['.phtml'] = new PhpEngine($this, $dependencyInjector);
            
            } else {

                if (!is_object($dependencyInjector)) {
                    throw new Exception("A dependency injector container is required to obtain the application services");
                }

                /**
                 * Arguments for instantiated engines
                 */
                $arguments = [$this, $dependencyInjector];

                foreach ($registerEngines as $extension => $engineService) {
                    
                    if (is_object($engineService)) {

                        /**
                         * Engine can be a closure
                         */
                        if ($engineService instanceof \Closure) {
                            $engineObject = call_user_func_array($engineService, $arguments);
                        } else {
                            $engineObject = $engineService;
                        }
                    } else {

                        /**
                         * Engine can be a string representing a service in the DI
                         */
                        if (!is_string($engineService)) {
                             throw new Exception('Invalid template engine registration for extension: ' . $extension);
                        }
                        
                        $engineObject = $dependencyInjector->getShared($engineService, $arguments);                        
                    }

                    $engines[$extension] = $engineObject;
                }
            }

            $this->_engines = $engines;
        }

        return $engines;
    }

    /**
     * Tries to render the view with every engine registered in the component
     *
     * @param string $path
     * @param array $params
     * @throws Exception
     */
    protected function _internalRender($path, $params)
    {
        if (!is_string($path)) {
            throw new Exception('Invalid parameter type.');
        }

        $eventsManager = $this->_eventsManager;

        if (is_object($eventsManager)) {
            $this->_activeRenderPath = $path;
        }

        /**
         * Call beforeRender if there is an events manager
         */
        if (is_object($eventsManager)) {
            if ($this->_eventsManager->fire('view:beforeRender', $this) === false) {
                return null;
            }
        }

        $notExists = true;
        $mustClean = true;

        $viewsDirPath = $this->_viewsDir . $path;

        /**
         * Load the template engines
         */
        $engines = $this->_loadTemplateEngines();

        /**
         * Views are rendered in each engine
         */
        foreach ($engines as $extension => $engine) {
            
            if (file_exists($viewsDirPath . $extension)) {
                $viewEnginePath = $viewsDirPath . $extension;
            } else {

                /**
                 * if passed filename with engine extension
                 */
                if ($extension && substr($viewsDirPath, -strlen($extension)) == $extension && file_exists($viewsDirPath)) {
                    $viewEnginePath = $viewsDirPath;
                } else {
                    $viewEnginePath = '';
                }
            }

            if ($viewEnginePath) {

                /**
                 * Call beforeRenderView if there is a events manager available
                 */
                if (is_object($eventsManager)) {
                    if ($this->_eventsManager->fire('view:beforeRenderView', $this, $viewEnginePath) === false) {
                        continue;
                    }
                }

                $engine->render($viewEnginePath, $params, $mustClean);

                /**
                 * Call afterRenderView if there is a events manager available
                 */
                $notExists = false;
                if (is_object($eventsManager)) {
                    $eventsManager->fire('view:afterRenderView', $this);
                }
                break;
            }
        }

        /**
         * Always throw an exception if the view does not exist
         */
        if ($notExists === true) {
            throw new Exception("View '".$this->_viewsDir."' was not found in the views directory");
        }

        /**
         * Call afterRender event
         */
        if (is_object($eventsManager)) {
            $eventsManager->fire('view:afterRender', $this);
        }
    }

    /**
     * Renders a view
     *
     * @param string $path
     * @param array|null $params
     * @return string
     * @throws Exception
     */
    public function render($path, $params = null)
    {
        if (!is_string($path)) {
            throw new Exception('Invalid parameter type.');
        }

        /**
         * Create/Get a cache
         */
        $cache = $this->getCache();

        if (is_object($cache)) {

            /**
             * Check if the cache is started, the first time a cache is started we start the cache
             */
            if ($cache->isStarted() === false) {
                
                $key = null;
                $lifetime = null;

                /**
                 * Check if the user has defined a different options to the default
                 */
                $cacheOptions = $this->_cacheOptions;
                if (is_array($cacheOptions)) {
                    if (isset($cacheOptions['key'])) {
                        $key = $cacheOptions['key'];
                    }

                    if (isset($cacheOptions['lifetime'])) {
                        $lifetime = $cacheOptions['lifetime'];
                    }
                }

                /**
                 * If a cache key is not set we create one using a md5
                 */
                if ($key === null) {
                    $key = md5($path);
                }

                /**
                 * We start the cache using the key set
                 */
                $content = $cache->start($key, $lifetime);
                if ($content !== null) {
                    $this->_content = $content;
                    return $content;
                }
            }
        }

        /**
         * Create a virtual symbol table
         */
        //create_symbol_table();

        ob_start();

        $viewParams = $this->_viewParams;

        /**
         * Merge parameters
         */
        if (is_array($params)) {
            if (is_array($viewParams)) {
                $mergedParams = array_merge($viewParams, $params);
            } else {
                $mergedParams = $params;
            }
        } else {
            $mergedParams = $viewParams;
        }

        /**
         * internalRender is also reused by partials
         */
        $this->_internalRender($path, $mergedParams);

        /**
         * Store the data in output into the cache
         */
        if (is_object($cache)) {
            if ($cache->isStarted === true) {
                if ($cache->isFresh() === true) {
                    $cache->save();
                } else {
                    $cache->stop();
                }
            } else {
                $cache->stop();
            }
        }

        ob_end_clean();

        return $this->_content;
    }

    /**
     * Renders a partial view
     *
     * <code>
     *  //Show a partial inside another view
     *  $this->partial('shared/footer');
     * </code>
     *
     * <code>
     *  //Show a partial inside another view with parameters
     *  $this->partial('shared/footer', array('content' => $html));
     * </code>
     *
     * @param string $partialPath
     * @param array|null $params
     * @throws Exception
     */
    public function partial($partialPath, $params = null)
    {
        if (is_string($partialPath) === false) {
            throw new Exception('Invalid parameter type.');
        }

        /**
         * Start output buffering
         */
        ob_start();

        /**
         * If the developer pass an array of variables we create a new virtual symbol table
         */
        if (is_array($params)) {

            $viewParams = $this->_viewParams;

            /**
             * Merge or assign the new params as parameters
             */
            if (is_array($viewParams)) {
                $mergedParams = array_merge($viewParams, $params);
            } else {
                $mergedParams = $params;
            }

            /**
             * Create a virtual symbol table
             */
            //create_symbol_table();
        
        } else {
            $mergedParams = $params;
        }

        /**
         * Call engine render, this checks in every registered engine for the partial
         */
        $this->_internalRender($partialPath, $mergedParams);

        /**
         * Now we need to restore the original view parameters
         */
        if (is_array($params)) {
            /**
             * Restore the original view params
             */
            $this->_viewParams = $viewParams;
        }

        ob_end_clean();

        /**
         * Content is output to the parent view
         */
        echo $this->_content;
    }

    /**
     * Sets the cache options
     *
     * @param array $options
     * @return \Phalcon\Mvc\View\Simple
     * @throws Exception
     */
    public function setCacheOptions($options)
    {
        if (!is_array($options) ) {
            throw new Exception('Invalid parameter type.');
        }

        $this->_cacheOptions = $options;
        return $this;
    }

    /**
     * Returns the cache options
     *
     * @return array|null
     */
    public function getCacheOptions()
    {
        return $this->_cacheOptions;
    }

    /**
     * Create a \Phalcon\Cache based on the internal cache options
     *
     * @return \Phalcon\Cache\BackendInterface
     * @throws Exception
     */
    protected function _createCache()
    {
        $dependencyInjector = $this->_dependencyInjector;
        if (!is_object($dependencyInjector)) {
            throw new Exception('A dependency injector container is required to obtain the view cache services');
        }

        $cacheService = 'viewCache';
        
        $cacheOptions = $this->_cacheOptions;
        if (is_array($cacheOptions)) {
            if (isset($cacheOptions['service'])) {
                $cacheService = $cacheOptions['service'];
            }
        }

        /**
         * The injected service must be an object
         */
        $viewCache = $dependencyInjector->getShared($cacheService);
        if (!is_object($viewCache)) {
            throw new Exception('The injected caching service is invalid');
        }

        return $viewCache;
    }

    /**
     * Returns the cache instance used to cache
     *
     * @return \Phalcon\Cache\BackendInterface|boolean
     */
    public function getCache()
    {
        $cache = $this->_cache;
        if ($cache) {
            if (!is_object($cache)) {
                $cache = $this->_createCache();
                $this->_cache = $cache;
            }
        }

        return $cache;
    }

    /**
     * Cache the actual view render to certain level
     *
     *<code>
     *  $this->view->cache(array('key' => 'my-key', 'lifetime' => 86400));
     *</code>
     *
     * @param boolean|array|null $options
     * @return \Phalcon\Mvc\View\Simple
     * @throws Exception
     */
    public function cache($options = true)
    {
        if (is_array($options)) {
            $this->_cache = true;
            $this->_cacheOptions = $options;
        } else {
            if ($options) {
                $this->_cache = true;
            } else {
                $this->_cache = false;
            }
        }

        return $this;
    }

    /**
     * Adds parameters to views (alias of setVar)
     *
     *<code>
     *  $this->view->setParamToView('products', $products);
     *</code>
     *
     * @param string $key
     * @param mixed $value
     * @return \Phalcon\Mvc\View\Simple
     * @throws Exception
     */
    public function setParamToView($key, $value)
    {
        if (is_string($key) === false) {
            throw new Exception('Invalid parameter type.');
        }

        $this->_viewParams[$key] = $value;
        return $this;
    }

    /**
     * Set all the render params
     *
     *<code>
     *  $this->view->setVars(array('products' => $products));
     *</code>
     *
     * @param array $params
     * @param boolean|null $merge
     * @return \Phalcon\Mvc\View\Simple
     * @throws Exception
     */
    public function setVars($params, $merge = true)
    {
        if (!is_bool($merge)) {
            throw new Exception('Invalid parameter type.');
        }

        if (!is_array($params)) {
            throw new Exception('The render parameters must be an array');
        }

        if ($merge) {
            $viewParams = $this->_viewParams;
            if (is_array($viewParams)) {
                $mergedParams = array_merge($viewParams, $params);
            } else {
                $mergedParams = $params;
            }
            $this->_viewParams = $mergedParams;
        } else {
            $this->_viewParams = $params;
        }

        return $this;
    }

    /**
     * Set a single view parameter
     *
     *<code>
     *  $this->view->setVar('products', $products);
     *</code>
     *
     * @param string $key
     * @param mixed $value
     * @return \Phalcon\Mvc\View\Simple
     * @throws Exception
     */
    public function setVar($key, $value)
    {
        if (!is_string($key)) {
            throw new Exception('Invalid parameter type.');
        }

        $this->_viewParams[$key] = $value;
        return $this;
    }

    /**
     * Returns a parameter previously set in the view
     *
     * @param string $key
     * @return mixed
     * @throws Exception
     */
    public function getVar($key)
    {
        if (!is_string($key)) {
            throw new Exception('Invalid parameter type.');
        }

        if (isset($this->_viewParams[$key])) {
            return $this->_viewParams[$key];
        }

        return null;
    }

    /**
     * Returns parameters to views
     *
     * @return array|null
     */
    public function getParamsToView()
    {
        return $this->_viewParams;
    }

    /**
     * Externally sets the view content
     *
     *<code>
     *  $this->view->setContent("<h1>hello</h1>");
     *</code>
     *
     * @param string $content
     * @return \Phalcon\Mvc\View\Simple
     * @throws Exception
     */
    public function setContent($content)
    {
        if (!is_string($content)) {
            throw new Exception('Content must be a string');
        }

        $this->_content = $content;
        return $this;
    }

    /**
     * Returns cached ouput from another view stage
     *
     * @return string|null
     */
    public function getContent()
    {
        return $this->_content;
    }

    /**
     * Returns the path of the view that is currently rendered
     *
     * @return string|null
     */
    public function getActiveRenderPath()
    {
        return $this->_activeRenderPath;
    }

    /**
     * Magic method to pass variables to the views
     *
     *<code>
     *  $this->view->products = $products;
     *</code>
     *
     * @param string $key
     * @param mixed $value
     * @throws Exception
     */
    public function __set($key, $value)
    {
        if (!is_string($key)) {
            throw new Exception('Invalid parameter type.');
        }

        $this->_viewParams[$key] = $value;
    }

    /**
     * Magic method to retrieve a variable passed to the view
     *
     *<code>
     *  echo $this->view->products;
     *</code>
     *
     * @param string $key
     * @return mixed
     * @throws Exception
     */
    public function __get($key)
    {
        if (!is_string($key)) {
            throw new Exception('Invalid parameter type.');
        }

        if (isset($this->_viewParams[$key])) {
            return $this->_viewParams[$key];
        }

        return null;
    }
}
