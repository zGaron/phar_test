<?php
/**
 * View
 *
*/

namespace Phalcon\Mvc;

use \Phalcon\Di\Injectable;
use \Phalcon\Mvc\ViewInterface;
use \Phalcon\Mvc\View\Exception;
use \Phalcon\Mvc\View\Engine\Php as PhpEngine;
use \Phalcon\Cache\BackendInterface;

/**
 * Phalcon\Mvc\View
 *
 * Phalcon\Mvc\View is a class for working with the "view" portion of the model-view-controller pattern.
 * That is, it exists to help keep the view script separate from the model and controller scripts.
 * It provides a system of helpers, output filters, and variable escaping.
 *
 * <code>
 * //Setting views directory
 * $view = new Phalcon\Mvc\View();
 * $view->setViewsDir('app/views/');
 *
 * $view->start();
 * //Shows recent posts view (app/views/posts/recent.phtml)
 * $view->render('posts', 'recent');
 * $view->finish();
 *
 * //Printing views output
 * echo $view->getContent();
 * </code>
 *
 */
class View extends Injectable implements ViewInterface
{
    
    /**
     * Render Level: To the main layout
     *
     * @var int
     */
    const LEVEL_MAIN_LAYOUT = 5;

    /**
     * Render Level: Render to the templates "after"
     *
     * @var int
     */
    const LEVEL_AFTER_TEMPLATE = 4;

    /**
     * Render Level: To the controller layout
     *
     * @var int
     */
    const LEVEL_LAYOUT = 3;

    /**
     * Render Level: To the templates "before"
     *
     * @var int
     */
    const LEVEL_BEFORE_TEMPLATE = 2;

    /**
     * Render Level: To the action view
     *
     * @var int
     */
    const LEVEL_ACTION_VIEW = 1;

    /**
     * Render Level: No render any view
     *
     * @var int
     */
    const LEVEL_NO_RENDER = 0;

    /**
     * Cache Mode
     *
     * @var int
     */
    const CACHE_MODE_NONE = 0;
    const CACHE_MODE_INVERSE = 1;

    /**
     * Options
     *
     * @var null|array
     * @access protected
    */
    protected $_options;

    /**
     * Base Path
     *
     * @var string
     * @access protected
    */
    protected $_basePath = '';

    /**
     * Content
     *
     * @var string|null
     * @access protected
    */
    protected $_content = '';

    /**
     * Render Level
     *
     * @var int
     * @access protected
    */
    protected $_renderLevel = 5;

    /**
     * Current Render Level
     *
     * @var int
     * @access protected
    */
    protected $_currentRenderLevel = 0;

    /**
     * Disabled Levels
     *
     * @var null|array
     * @access protected
    */
    protected $_disabledLevels;

    /**
     * View Params
     *
     * @var null|array
     * @access protected
    */
    protected $_viewParams;

    /**
     * Layout
     *
     * @var null|string
     * @access protected
    */
    protected $_layout;

    /**
     * Layouts Dir
     *
     * @var string
     * @access protected
    */
    protected $_layoutsDir = '';

    /**
     * Partials Dir
     *
     * @var string
     * @access protected
    */
    protected $_partialsDir = '';

    /**
     * Views Dir
     *
     * @var null|string
     * @access protected
    */
    protected $_viewsDir;

    /**
     * Templates Before
     *
     * @var null|array
     * @access protected
    */
    protected $_templatesBefore;

    /**
     * Templates After
     *
     * @var null|array
     * @access protected
    */
    protected $_templatesAfter;

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
     * Main View
     *
     * @var string
     * @access protected
    */
    protected $_mainView = 'index';

    /**
     * Controller Name
     *
     * @var null|string
     * @access protected
    */
    protected $_controllerName;

    /**
     * Action Name
     *
     * @var null|string
     * @access protected
    */
    protected $_actionName;

    /**
     * Params
     *
     * @var null|string
     * @access protected
    */
    protected $_params;

    /**
     * Pick View
     *
     * @var null|array
     * @access protected
    */
    protected $_pickView;

    /**
     * Cache
     *
     * @var null|\Phalcon\Cache\BackendInterface
     * @access protected
    */
    protected $_cache;

    /**
     * Cache Level
     *
     * @var int
     * @access protected
    */
    protected $_cacheLevel = 0;

    /**
     * Active Render Path
     *
     * @var null|string
     * @access protected
    */
    protected $_activeRenderPath;

    /**
     * Dsiabeld
     *
     * @var boolean
     * @access protected
    */
    protected $_disabled = false;

    /**
     * \Phalcon\Mvc\View constructor
     *
     * @param array $options
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
     * @return \Phalcon\Mvc\View
     * @throws Exception
     */
    public function setViewsDir($viewsDir)
    {
        if (!is_string($viewsDir)) {
            throw new Exception('The views directory must be a string');
        }

        if (substr($viewsDir, -1) != DIRECTORY_SEPARATOR) {
            $viewsDir = $viewsDir . DIRECTORY_SEPARATOR;
        }

        $this->_viewsDir = $viewsDir;
        return $this;
    }

    /**
     * Gets views directory
     *
     * @return string
     */
    public function getViewsDir()
    {
        return $this->_viewsDir;
    }

    /**
     * Sets the layouts sub-directory. Must be a directory under the views directory. Depending of your platform, always add a trailing slash or backslash
     *
     *<code>
     * $view->setLayoutsDir('../common/layouts/');
     *</code>
     *
     * @param string $layoutsDir
     * @return \Phalcon\Mvc\View
     * @throws Exception
     */
    public function setLayoutsDir($layoutsDir)
    {
        if (!is_string($layoutsDir)) {
            throw new Exception('Invalid parameter type.');
        }

        $this->_layoutsDir = $layoutsDir;
        return $this;
    }

    /**
     * Gets the current layouts sub-directory
     *
     * @return string
     */
    public function getLayoutsDir()
    {
        return $this->_layoutsDir;
    }

    /**
     * Sets a partials sub-directory. Must be a directory under the views directory. Depending of your platform, always add a trailing slash or backslash
     *
     *<code>
     * $view->setPartialsDir('../common/partials/');
     *</code>
     *
     * @param string $partialsDir
     * @return \Phalcon\Mvc\View
     * @throws Exception
     */
    public function setPartialsDir($partialsDir)
    {
        if (!is_string($partialsDir)) {
            throw new Exception('Invalid parameter type.');
        }

        $this->_partialsDir = $partialsDir;
        return $this;
    }

    /**
     * Gets the current partials sub-directory
     *
     * @return string
     */
    public function getPartialsDir()
    {
        return $this->_partialsDir;
    }

    /**
     * Sets base path. Depending of your platform, always add a trailing slash or backslash
     *
     * <code>
     *  $view->setBasePath(__DIR__ . '/');
     * </code>
     *
     * @param string $basePath
     * @return \Phalcon\Mvc\View
     */
    public function setBasePath($basePath)
    {
        if (!is_string($basePath)) {
            throw new Exception('Invalid parameter type.');
        }

        $this->_basePath = $basePath;
        return $this;
    }

    /**
     * Gets base path
     *
     * @return string
     */
    public function getBasePath()
    {
        return $this->_basePath;
    }

    /**
     * Sets the render level for the view
     *
     * <code>
     *  //Render the view related to the controller only
     *  $this->view->setRenderLevel(View::LEVEL_LAYOUT);
     * </code>
     *
     * @param int $level
     * @return \Phalcon\Mvc\View
     * @throws Exception
     */
    public function setRenderLevel($level)
    {
        if (!is_int($level)) {
            throw new Exception('Invalid parameter type.');
        }

        $this->_renderLevel = $level;
        return $this;
    }

    /**
     * Disables a specific level of rendering
     *
     *<code>
     * //Render all levels except ACTION level
     * $this->view->disableLevel(View::LEVEL_ACTION_VIEW);
     *</code>
     *
     * @param int|array $level
     * @return \Phalcon\Mvc\View
     */
    public function disableLevel($level)
    {
        if (is_array($level)) {
            $this->_disabledLevels = $level;
        } else {
            $this->_disabledLevels[$level] = true;
        }

        return $this;
    }

    /**
     * Sets default view name. Must be a file without extension in the views directory
     *
     * <code>
     *  //Renders as main view views-dir/base.phtml
     *  $this->view->setMainView('base');
     * </code>
     *
     * @param string $viewPath
     * @return \Phalcon\Mvc\View
     * @throws Exception
     */
    public function setMainView($viewPath)
    {
        if (!is_string($viewPath)) {
            throw new Exception('Invalid parameter type.');
        }

        $this->_mainView = $viewPath;

        return $this;
    }

    /**
     * Returns the name of the main view
     *
     * @return string
     */
    public function getMainView()
    {
        return $this->_mainView;
    }

    /**
     * Change the layout to be used instead of using the name of the latest controller name
     *
     * <code>
     *  $this->view->setLayout('main');
     * </code>
     *
     * @param string $layout
     * @return \Phalcon\Mvc\View
     * @throws Exception
     */
    public function setLayout($layout)
    {
        if (!is_string($layout)) {
            throw new Exception('Invalid parameter type.');
        }

        $this->_layout = $layout;

        return $this;
    }

    /**
     * Returns the name of the main view
     *
     * @return string
     */
    public function getLayout()
    {
        return $this->_layout;
    }

    /**
     * Appends template before controller layout
     *
     * @param string|array $templateBefore
     * @return \Phalcon\Mvc\View
     */
    public function setTemplateBefore($templateBefore)
    {
        if (!is_array($templateBefore)) {
            $this->_templatesBefore = [$templateBefore];
        } else {
            $this->_templatesBefore = $templateBefore;
        }

        return $this;
    }

    /**
     * Resets any "template before" layouts
     *
     * @return \Phalcon\Mvc\View
     */
    public function cleanTemplateBefore()
    {
        $this->_templatesBefore = null;
        return $this;
    }

    /**
     * Appends "template after" controller layout
     *
     * @param string|array $templateAfter
     * @return \Phalcon\Mvc\View
     */
    public function setTemplateAfter($templateAfter)
    {
        if (!is_array($templateAfter)) {
            $this->_templatesAfter = [$templateAfter];
        } else {
            $tgis->_templatesAfter = $templateAfter;
        }

        return $this;
    }

    /**
     * Resets any template before layouts
     *
     * @return \Phalcon\Mvc\View
     */
    public function cleanTemplateAfter()
    {
        $this->_templatesAfter = null;
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
     * @return \Phalcon\Mvc\View
     * @throws Exception
     */
    public function setParamToView($key, $value)
    {
        if (!is_string($key)) {
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
     * @param boolean $merge
     * @return \Phalcon\Mvc\View
     * @throws Exception
     */
    public function setVars($params, $merge = true)
    {
        if (!is_array($params)) {
            throw new Exception('The render parameters must be an array');
        }

        if (!is_bool($merge)) {
            throw new Exception('Invalid parameter type.');
        }

        if ($merge) {
            $viewParams = $this->_viewParams;
            if (is_array($viewParams)) {
                $this->_viewParams = array_merge($viewParams, $params);
            } else {
                $this->_viewParams = $params;
            }
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
     * @return \Phalcon\Mvc\View
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

        $params = $this->_viewParams;
        if (isset($params[$key])) {
            return $params[$key];
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
     * Gets the name of the controller rendered
     *
     * @return string|null
     */
    public function getControllerName()
    {
        return $this->_controllerName;
    }

    /**
     * Gets the name of the action rendered
     *
     * @return string
     */
    public function getActionName()
    {
        return $this->_actionName;
    }

    /**
     * Gets extra parameters of the action rendered
     *
     * @return array
     */
    public function getParams()
    {
        return $this->_params;
    }

    /**
     * Starts rendering process enabling the output buffering
     *
     * @return \Phalcon\Mvc\View
     */
    public function start()
    {       
        ob_start();
        $this->_content = null;
        return $this;
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
     * Checks whether view exists on registered extensions and render it
     *
     * @param array $engines
     * @param string $viewPath
     * @param boolean $silence
     * @param boolean $mustClean
     * @param \Phalcon\Cache\BackendInterface|null $cache
     * @throws Exception
     */
    protected function _engineRender($engines, $viewPath, $silence, $mustClean, $cache = null)
    {
        
        if (!is_string($viewPath) ||
            !is_bool($silence) ||
            !is_bool($mustClean)) {
            throw new Exception('Invalid parameter type.');
        }

        $notExists = true;
        $viewDir = $this->_viewsDir;
        $basePath = $this->_basePath;
        $viewsDirPath = $basePath . $viewDir . $viewPath;

        if (is_object($cache)) {
            
            $renderLevel = (int) $this->_renderLevel;
            $cacheLevel = (int) $this->_cacheLevel;

            if ($renderLevel >= $cacheLevel) {

                /**
                 * Check if the cache is started, the first time a cache is started we start the
                 * cache
                 */
                if ($cache->isStarted() == false) {

                    $key = null;
                    $lifetime = null;

                    $viewOptions = $this->_options;

                    /**
                     * Check if the user has defined a different options to the default
                     */
                    if (is_array($viewOptions)) {
                        if (isset($viewOptions['cache'])) {
                            $cacheOptions = $viewOptions['cache'];
                            if (is_array($cacheOptions)) {
                                if (isset($cacheOptions['key'])) {
                                    $key = $cacheOptions['key'];
                                }
                                if (isset($cacheOptions['lifetime'])) {
                                    $lifetime = $cacheOptions['lifetime'];
                                }
                            }
                        }
                    }

                    /**
                     * If a cache key is not set we create one using a md5
                     */
                    if ($key === null) {
                        $key = md5($viewPath);
                    }

                    /**
                     * We start the cache using the key set
                     */
                    $cachedView = $cache->start($key, $lifetime);
                    if ($cachedView !== null) {
                        $this->_content = $cachedView;
                        return null;
                    }
                }

                /**
                 * This method only returns true if the cache has not expired
                 */
                if (!$cache->isFresh()) {
                    return null;
                }               
            }
        }

        $viewParams = $this->_viewParams;
        $eventsManager = $this->_eventsManager;

        /**
         * Views are rendered in each engine
         */
        foreach ($engines as $entension => $engine) {
            
            $viewEnginePath = $viewsDirPath . $entension;
            if (file_exists($viewEnginePath)) {

                /**
                 * Call beforeRenderView if there is a events manager available
                 */
                if (is_object($eventsManager)) {
                    $this->_activeRenderPath = $viewEnginePath;
                    if ($this->_eventsManager->fire('view:beforeRenderView', $this, $viewEnginePath) === false) {
                        continue;
                    }
                }

                $engine->render($viewEnginePath, $viewParams, $mustClean);

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

        if ($notExists === true) {

            /**
             * Notify about not found views
             */
            if (is_object($eventsManager)) {
                $this->_activeRenderPath = $viewEnginePath;
                $eventsManager->fire('view:notFoundView', $this, $viewEnginePath);
            }

            if (!$silence) {
                throw new Exception("View '".$viewsDirPath."' was not found in the views directory");
            }
        }
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
     * @return \Phalcon\Mvc\View
     * @throws Exception
     */
    public function registerEngines($engines)
    {
        if (!is_array($engines)) {
            throw new Exception('Engines to register must be an array');
        }

        $this->_registeredEngines = $engines;
        return $this;
    }

    /**
     * Get Registered Engines
     *
     * @return array
    */
    public function getRegisterEngines()
    {
        return $this->_registeredEngines;
    }

    /**
     * Checks whether view exists
     *
     * @param string view
     * @return boolean
     */
    public function exists($view)
    {
        if (!is_string($view)) {
            throw new Exception('Invalid parameter type.');
        }
        
        $basePath = $this->_basePath;
        $viewDir = $this->_viewsDir;
        $engines = $this->_registeredEngines;

        if (!is_array($engines)) {
            $engines = [];
            $engines['.phtml'] = 'Phalcon\\Mvc\\View\\Engine\\Php';
            $this->_registeredEngines = $engines;
        }

        $exists = false;
        foreach ($engines as $extension => $value) {
            $exists = (boolean) file_exists($basePath . $viewsDir . $view . $extension);
            if ($exists) {
                break;
            }
        }

        return $exists;
    }

    /**
     * Executes render process from dispatching data
     *
     *<code>
     * //Shows recent posts view (app/views/posts/recent.phtml)
     * $view->start()->render('posts', 'recent')->finish();
     *</code>
     *
     * @param string $controllerName
     * @param string $actionName
     * @param array|null $params
     * @return boolean|null
     * @return \Phalcon\Mvc\View|boolean
     */
    public function render($controllerName, $actionName, $params = null)
    {
        if (!is_string($controllerName) ||
            !is_string($actionName)) {
            throw new Exception('Invalid parameter type.');
        }

        $this->_currentRenderLevel = 0;

        /**
         * If the view is disabled we simply update the buffer from any output produced in the controller
         */
        if ($this->_disabled != false) {
            $this->_content = ob_get_contents();
            return false;
        }

        $this->_controllerName = $controllerName;
        $this->_actionName = $actionName;
        $this->_params = $params;

        /**
         * Check if there is a layouts directory set
         */
        $layoutsDir = $this->_layoutsDir;
        if (!$layoutsDir) {
            $layoutsDir = 'layouts/';
        }

        /**
         * Check if the user has defined a custom layout
         */
        $layout = $this->_layout;
        if ($layout) {
            $layoutName = $layout;
        } else {
            $layoutName = $controllerName;
        }

        /**
         * Load the template engines
         */
        $engines = $this->_loadTemplateEngines();

        /**
         * Check if the user has picked a view diferent than the automatic
         */
        $pickView = $this->_pickView;

        if ($pickView === null) {
            $renderView = $controllerName . '/' . $actionName;
        } else {

            /**
             * The 'picked' view is an array, where the first element is controller and the second the action
             */
            $renderView = $pickView[0];
            if (isset($pickView[1])) {
                $layoutName = $pickView[1];
            }
        }

        /**
         * Start the cache if there is a cache level enabled
         */
        if ($this->_cacheLevel) {
            $cache = $this->getCache();
        } else {
            $cache = null;
        }

        $eventsManager = $this->_eventsManager;

        /**
         * Create a virtual symbol table
         */
        //create_symbol_table();
        
        /**
         * Call beforeRender if there is an events manager
         */
        if (is_object($eventsManager)) {
            if ($this->_eventsManager->fire('view:beforeRender', $this) === false) {
                return false;
            }
        }

        /**
         * Get the current content in the buffer maybe some output from the controller?
         */
        $this->_content = ob_get_contents();

        $mustClean = true;
        $silence = true;

        /**
         * Disabled levels allow to avoid an specific level of rendering
         */
        $disableLevels = $this->_disabledLevels;

        /**
         * Render level will tell use when to stop
         */
        $renderLevel = (int) $this->_renderLevel;
        if ($renderLevel) {

            /**
             * Inserts view related to action
             */
            if ($renderLevel >= self::LEVEL_ACTION_VIEW) {
                if (!isset($disableLevels[self::LEVEL_ACTION_VIEW])) {
                    $this->_currentRenderLevel = self::LEVEL_ACTION_VIEW;
                    $this->_engineRender($engines, $renderView, $silence, $mustClean, $cache);
                }
            }

            /**
             * Inserts templates before layout
             */
            if ($renderLevel >= self::LEVEL_BEFORE_TEMPLATE) {
                if (!isset($disableLevels[self::LEVEL_BEFORE_TEMPLATE])) {
                    $this->_currentRenderLevel = self::LEVEL_BEFORE_TEMPLATE;
                    $templatesBefore = $this->_templatesBefore;

                    /**
                     * Templates before must be an array
                     */
                    if (is_array($templatesBefore)) {
                        $silence = false;
                        foreach ($templatesBefore as $templateBefore) {
                            $this->_engineRender($engines, $layoutsDir . $templateBefore, $silence, $mustClean, $cache);
                        }
                        $silence = true;
                    }
                }
            }

            /**
             * Inserts controller layout
             */
            if ($renderLevel >= self::LEVEL_LAYOUT) {
                if (!isset($disableLevels[self::LEVEL_LAYOUT])) {
                    $this->_currentRenderLevel = self::LEVEL_LAYOUT;
                    $this->_engineRender($engines, $layoutsDir . $layoutName, $silence, $mustClean, $cache);
                }
            }

            /**
             * Inserts templates after layout
             */
            if ($renderLevel >= self::LEVEL_AFTER_TEMPLATE) {
                if (!isset($disableLevels[self::LEVEL_AFTER_TEMPLATE])) {
                    $this->_currentRenderLevel = self::LEVEL_AFTER_TEMPLATE;

                    /**
                     * Templates after must be an array
                     */
                    $templatesAfter = $this->_templatesAfter;
                    if (is_array($templatesAfter)) {
                        $silence = false;
                        foreach ($templatesAfter as $templateAfter) {
                            $this->_engineRender($engines, $layoutsDir . $templateAfter, $silence, $mustClean, $cache);
                        }
                        $silence = true;
                    }
                }
            }

            /**
             * Inserts main view
             */
            if ($renderLevel >= self::LEVEL_MAIN_LAYOUT) {
                if (!isset($disableLevels[self::LEVEL_MAIN_LAYOUT])) {
                    $this->_currentRenderLevel = self::LEVEL_MAIN_LAYOUT;
                    $this->_engineRender($engines, $this->_mainView, $silence, $mustClean, $cache);
                }
            }

            $this->_currentRenderLevel = 0;

            /**
             * Store the data in the cache
             */
            if (is_object($cache)) {
                if ($cache->isStarted() == true) {
                    if ($cache->isFresh() == true) {
                        $cache->save();
                    } else {
                        $cache->stop();
                    }
                } else {
                    $cache->stop();
                }
            }
        }

        /**
         * Call afterRender event
         */
        if (is_object($eventsManager)) {
            $eventsManager->fire('view:afterRender', $this);
        }

        return $this;
    }

    /**
     * Choose a different view to render instead of last-controller/last-action
     *
     * <code>
     * class ProductsController extends \Phalcon\Mvc\Controller
     * {
     *
     *    public function saveAction()
     *    {
     *
     *         //Do some save stuff...
     *
     *         //Then show the list view
     *         $this->view->pick("products/list");
     *    }
     * }
     * </code>
     *
     * @param string|array $renderView
     * @return \Phalcon\Mvc\View
     * @throws Exception
     */
    public function pick($renderView)
    {
        if (is_array($renderView)) {
            $pickView = $renderView;
        } else {
            
            $layout = null;
            if (strpos($renderView, '/') !== false) {
                $parts = explode('/', $renderView);
                $layout = $parts[0];
            }

            $pickView = [$renderView];
            if (!is_null($layout)) {
                $pickView[] = $layout;
            }
        }

        $this->_pickView = $pickView;;
        return $this;
    }

    /**
     * Renders a partial view
     *
     * <code>
     *  //Retrieve the contents of a partial
     *  echo $this->getPartial('shared/footer');
     * </code>
     *
     * <code>
     *  //Retrieve the contents of a partial with arguments
     *  echo $this->getPartial('shared/footer', array('content' => $html));
     * </code>
     *
     * @param string partialPath
     * @param array params
     * @return string
     */
    public function getPartial($partialPath, $params = null)
    {
        if (!is_string($partialPath)) {
            throw new Exception('Invalid parameter type.');
        }

        ob_start();
        $this->partial($partialPath, $params);
        return ob_get_clean();
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
        if (!is_string($partialPath)) {
            throw new Exception('Invalid parameter type.');
        }

        /**
         * If the developer pass an array of variables we create a new virtual symbol table
         */
        if (is_array($params)) {

            /**
             * Merge or assign the new params as parameters
             */
            $viewParams = $this->_viewParams;
            if (is_array($viewParams)) {
                $this->_viewParams = array_merge($viewParams, $params);
            } else {
                $this->_viewParams = $params;
            }

            /**
             * Create a virtual symbol table
             */
            //create_symbol_table();
        }

        /**
         * Partials are looked up under the partials directory
         * We need to check if the engines are loaded first, this method could be called outside of 'render'
         * Call engine render, this checks in every registered engine for the partial
         */
        $this->_engineRender($this->_loadTemplateEngines(), $this->_partialsDir . $partialPath, false, false);

        /**
         * Now we need to restore the original view parameters
         */
        if (is_array($params)) {
            /**
             * Restore the original view params
             */
            $this->_viewParams = $viewParams;
        }
    }

    /**
     * Perform the automatic rendering returning the output as a string
     *
     * <code>
     *  $template = $this->view->getRender('products', 'show', array('products' => $products));
     * </code>
     *
     * @param string $controllerName
     * @param string $actionName
     * @param array $params
     * @param mixed $configCallback
     * @return string
     * @throws Exception
     */
    public function getRender($controllerName, $actionName, $params = null, $configCallback = null)
    {
        if (!is_string($controllerName) ||
            !is_string($actionName)) {
            throw new Exception('Invalid parameter type.');
        }

        /**
         * We must to clone the current view to keep the old state
         */
        $view = clone $this;

        /**
         * The component must be reset to its defaults
         */
        $view->reset();

        /**
         * Set the render variables
         */
        if (is_array($params)) {
            $view->setVars($params);
        }

        /**
         * Perform extra configurations over the cloned object
         */
        if (is_object($configCallback)) {
            call_user_func_array($configCallback, [$view]);
        }

        /**
         * Start the output buffering
         */
        $view->start();

        /**
         * Perform the render passing only the controller and action
         */
        $view->render($controllerName, $actionName);

        /**
         * Stop the output buffering
         */
        ob_end_clean();

        /**
         * Get the processed content
         */
        return $view->getContent();
    }

    /**
     * Finishes the render process by stopping the output buffering
     *
     * @return \Phalcon\Mvc\View
     */
    public function finish()
    {
        ob_end_clean();
        return $this;
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

        $viewOptions = $this->_options;
        if (is_array($viewOptions)) {
            if (isset($viewOptions['cache'])) {
                $cacheOptions = $this->_options['cache'];
                if (isset($cacheOptions['service'])) {
                     $cacheService = $cacheOptions['service'];
                }
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
     * Check if the component is currently caching the output content
     *
     * @return boolean
     */
    public function isCaching()
    {
        return $this->_cacheLevel > 0;
    }

    /**
     * Returns the cache instance used to cache
     *
     * @return \Phalcon\Cache\BackendInterface
     */
    public function getCache()
    {
        $cache = $this->_cache;
        if ($cache) {
            if (!is_object($cache)) {
                $cache = $this->_createCache();
                $this->_cache = $cache;
            }
        } else {
            $cache = $this->_createCache();
            $this->_cache = $cache;
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
     * @param boolean|array $options
     * @return \Phalcon\Mvc\View
     * @throws Exception
     */
    public function cache($options = true)
    {
        
        if (is_array($options)) {
            
            $viewOptions = $this->_options;
            if (!is_array($viewOptions)) {
                $viewOptions = [];
            }

            /**
             * Get the default cache options
             */
            if (isset($viewOptions['cache'])) {
                $cacheOptions = $viewOptions['cache'];
            } else {
                $cacheOptions = [];
            }

            foreach ($options as $key => $value) {
                $cacheOptions[$key] = $value;
            }

            /**
             * Check if the user has defined a default cache level or use self::LEVEL_MAIN_LAYOUT as default
             */
            if (isset($cacheOptions['level'])) {
                $this->_cacheLevel = $cacheOptions['level'];
            } else {
                $this->_cacheLevel = self::LEVEL_MAIN_LAYOUT;
            }

            $viewOptions['cache'] = $cacheOptions;
            $this->_options = $viewOptions;
        } else {

            /**
             * If 'options' isn't an array we enable the cache with the default options
             */
            if ($options) {
                $this->_cacheLevel = self::LEVEL_MAIN_LAYOUT;
            } else {
                $this->_cacheLevel = self::LEVEL_NO_RENDER;
            }
        }

        return $this;
    }

    /**
     * Externally sets the view content
     *
     *<code>
     *  $this->view->setContent("<h1>hello</h1>");
     *</code>
     *
     * @param string $content
     * @return \Phalcon\Mvc\View
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
     * Returns cached output from another view stage
     *
     * @return string
     */
    public function getContent()
    {
        return $this->_content;
    }

    /**
     * Returns the path of the view that is currently rendered
     *
     * @return string
     */
    public function getActiveRenderPath()
    {
        return $this->_activeRenderPath;
    }

    /**
     * Get Render Level
     *
     * @return int
     */
    public function getRenderLevel()
    {
        return $this->_renderLevel;
    }

    /**
     * Get Current Render Level
     *
     * @return int
     */
    public function getCurrentRenderLevel()
    {
        return $this->_currentRenderLevel;
    }

    /**
     * Disables the auto-rendering process
     *
     * @return \Phalcon\Mvc\View
     */
    public function disable()
    {
        $this->_disabled = true;
        return $this;
    }

    /**
     * Enables the auto-rendering process
     *
     * @return \Phalcon\Mvc\View
     */
    public function enable()
    {
        $this->_disabled = false;
        return $this;
    }

    /**
     * Resets the view component to its factory default values
     *
     * @return \Phalcon\Mvc\View
     */
    public function reset()
    {
        $this->_disabled = false;
        $this->_engines = false;
        $this->_cache = null;
        $this->_renderLevel = self::LEVEL_MAIN_LAYOUT;
        $this->_cacheLevel = self::LEVEL_NO_RENDER;
        $this->_content = null;
        $this->_templatesBefore = null;
        $this->_templatesAfter = null;

        return $this;
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

        if (isset($this->_viewParams[$key]) === true) {
            return $this->_viewParams[$key];
        }
        return null;
    }

    /**
     * Whether automatic rendering is enabled
     */
    public function isDisabled()
    {
        return $this->_disabled;
    }

    /**
     * Magic method to retrieve if a variable is set in the view
     *
     *<code>
     *  echo isset($this->view->products);
     *</code>
     *
     * @param string key
     * @return boolean
     */
    public function __isset($key)
    {
        return isset($this->_viewParams[$key]);
    }
}
