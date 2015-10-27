<?php
/**
 * Application
*/

namespace Phalcon\Mvc;

use \Phalcon\DiInterface;
use \Phalcon\Di\Injectable;
use \Phalcon\Http\ResponseInterface;
use \Phalcon\Mvc\Application\Exception;


/**
 * Phalcon\Mvc\Application
 *
 * This component encapsulates all the complex operations behind instantiating every component
 * needed and integrating it with the rest to allow the MVC pattern to operate as desired.
 *
 *<code>
 *
 * class Application extends \Phalcon\Mvc\Application
 * {
 *      /\**
 *       * Register the services here to make them general or register
 *       * in the ModuleDefinition to make them module-specific
 *       *\/
 *      protected function _registerServices()
 *      {
 *
 *      }
 *
 *      /\**
 *       * This method registers all the modules in the application
 *       *\/
 *      public function main()
 *      {
 *          $this->registerModules(array(
 *              'frontend' => array(
 *                  'className' => 'Multiple\Frontend\Module',
 *                  'path' => '../apps/frontend/Module.php'
 *              ),
 *              'backend' => array(
 *                  'className' => 'Multiple\Backend\Module',
 *                  'path' => '../apps/backend/Module.php'
 *              )
 *          ));
 *      }
 *  }
 *
 *  $application = new Application();
 *  $application->main();
 *
 *</code>
 */
class Application extends Injectable
{
    
    /**
     * Default Module
     *
     * @var null|string
     * @access protected
     */
    protected $_defaultModule;

    /**
     * Modules
     *
     * @var null|array
     * @access protected
     */
    protected $_modules;

    /**
     * Implicit View?
     *
     * @var bool
     * @access protected
    */
    protected $_implicitView = true;

    /**
     * \Phalcon\Mvc\Application
     *
     * @param \Phalcon\DiInterface|null $dependencyInjector
     */
    public function __construct($dependencyInjector = null)
    {
        if (is_object($dependencyInjector) &&
            $dependencyInjector instanceof DiInterface === true) {
            $this->_dependencyInjector = $dependencyInjector;
        }
    }

    /**
     * By default. The view is implicitly buffering all the output
     * You can full disable the view component using this method
     *
     * @param boolean $implicitView
     * @return \Phalcon\Mvc\Application
     * @throws Exception
     */
    public function useImplicitView($implicitView)
    {
        if (!is_bool($implicitView)) {
            throw new Exception('Invalid parameter type.');
        }

        $this->_implicitView = $implicitView;
        return $this;
    }

    /**
     * Register an array of modules present in the application
     *
     *<code>
     *  $this->registerModules(array(
     *      'frontend' => array(
     *          'className' => 'Multiple\Frontend\Module',
     *          'path' => '../apps/frontend/Module.php'
     *      ),
     *      'backend' => array(
     *          'className' => 'Multiple\Backend\Module',
     *          'path' => '../apps/backend/Module.php'
     *      )
     *  ));
     *</code>
     *
     * @param array $modules
     * @param boolean $merge
     * @return \Phalcon\Mvc\Application
     * @throws Exception
     */
    public function registerModules($modules, $merge = false)
    {
        if (!is_array($modules)) {
            throw new Exception('Modules must be an Array');
        }

        if (!is_bool($merge)) {
            throw new Exception('Invalid parameter type.');
        }

        if ($merge === false) {
            $this->_modules = $modules;
        } else {
            $registeredModules = $this->_modules;
            if (is_array($registeredModules)) {
                $this->_modules = array_merge($registeredModules, $modules);
            } else {
                $this->_modules = $modules;
            }
        }

        return $this;
    }

    /**
     * Return the modules registered in the application
     *
     * @return array
     */
    public function getModules()
    {
        return $this->_modules;
    }

    /**
     * Gets the module definition registered in the application via module name
     *
     * @param string name
     * @return array|object
     */
    public function getModule($name)
    {
        if (!is_string($name)) {
            throw new Exception('Invalid parameter type.');
        }

        if (!isset($this->_modules[$name])) {
            throw new Exception("Module '" . $name . "' isn't registered in the application container");
        } else {
            $module = $this->_modules[$name];
        }

        return $module;
    }

    /**
     * Sets the module name to be used if the router doesn't return a valid module
     *
     * @param string $defaultModule
     * @return \Phalcon\Mvc\Application
     * @throws Exception
     */
    public function setDefaultModule($defaultModule)
    {
        if (!is_string($defaultModule)) {
            throw new Exception('Invalid parameter type.');
        }

        $this->_defaultModule = $defaultModule;
        return $this;
    }

    /**
     * Returns the default module name
     *
     * @return string
     */
    public function getDefaultModule()
    {
        return $this->_defaultModule;
    }

    /**
     * Handles a MVC request
     *
     * @param string $uri
     * @return \Phalcon\Http\ResponseInterface|boolean
     * @throws Exception
     */
    public function handle($uri = null)
    {
        
        $dependencyInjector = $this->_dependencyInjector;

        if (!is_object($dependencyInjector)) {
            throw new Exception('A dependency injection object is required to access internal services');
        }

        $eventsManager = $this->_eventsManager;

        /**
         * Call boot event, this allow the developer to perform initialization actions
         */
        if (is_object($eventsManager)) {
            if ($eventsManager->fire('application:boot', $this) === false) {
                return false;
            }
        }

        $router = $dependencyInjector->getShared('router');

        /**
         * Handle the URI pattern (if any)
         */
        $router->handle($uri);

        /**
         * If the router doesn't return a valid module we use the default module
         */
        $moduleName = $router->getModuleName();
        if (!$moduleName) {
            $moduleName = $this->_defaultModule;
        }

        $moduleObject = null;

        /**
         * Process the module definition
         */

        if ($moduleName) {
            
            if (is_object($eventsManager)) {
                if ($eventsManager->fire('application:beforeStartModule', $this, $moduleName) === false) {
                    return false;
                }
            }

            /**
             * Gets the module definition
             */
            $module = $this->getModule($moduleName);

            /**
             * A module definition must ne an array or an object
             */
            if (!is_array($module) && !is_object($module)) {
                throw new Exception('Invalid module definition');
            }

            /**
             * An array module definition contains a path to a module definition class
             */
            if (is_array($module)) {
                
                /**
                 * Class name used to load the module definition
                 */
                if (!isset($module['className'])) {
                    $className = 'Module';
                } else {
                    $className = $module['className'];
                }

                /**
                 * If developer specify a path try to include the file
                 */
                if (isset($module['path'])) {
                    $path = $module['path'];
                    if (!class_exists($className, false)) {
                        if (file_exists($path)) {
                            require $path;
                        } else {
                            throw new Exception("Module definition path '" . $module['path'] . "' doesn't exist");
                        }
                    }
                }

                $moduleObject = $this->_dependencyInjector->get($className);

                /**
                 * 'registerAutoloaders' and 'registerServices' are automatically called
                 */
                $moduleObject->registerAutoloaders($dependencyInjector);
                $moduleObject->registerServices($dependencyInjector);
            
            } else {
                
                /**
                 * A module definition object, can be a Closure instance
                 */
                if ($module instanceof \Closure) {
                    $moduleObject = call_user_func_array($module, [$dependencyInjector]);
                } else {
                    throw new Exception('Invalid module definition');
                }
            }

            /**
             * Calling afterStartModule event
             */
            if (is_object($eventsManager)) {
                $eventsManager->fire('application:afterStartModule', $this, $moduleName);
            }
        }

        /**
         * Check whether use implicit views or not
         */
        $implicitView = $this->_implicitView;
        
        if ($implicitView === true) {
            $view = $dependencyInjector->getShared('view');
        }

        /**
         * We get the parameters from the router and assign them to the dispatcher
         * Assign the values passed from the router
         */
        $dispatcher = $this->_dependencyInjector->getShared('dispatcher');
        $dispatcher->setModuleName($router->getModuleName());
        $dispatcher->setNamespaceName($router->getNamespaceName());
        $dispatcher->setControllerName($router->getControllerName());
        $dispatcher->setActionName($router->getActionName());
        $dispatcher->setParams($router->getParams());

        /**
         * Start the view component (start output buffering)
         */
        if ($implicitView === true) {
            $view->start();
        }

        /**
         * Calling beforeHandleRequest
         */
        if (is_object($eventsManager)) {
            if ($eventsManager->fire('application:beforeHandleRequest', $this, $dispatcher) === false) {
                return false;
            }
        }

        /**
         * The dispatcher must return an object
         */
        $controller = $dispatcher->dispatch();

        /**
         * Get the latest value returned by an action
         */
        $possibleResponse = $dispatcher->getReturnedValue();

        if (is_bool($possibleResponse) && $possibleResponse == false) {
            $response = $dependencyInjector->getShared('response');
        } else {
            if (is_object($possibleResponse)) {

                /**
                 * Check if the returned object is already a response
                 */
                $returnedResponse = $possibleResponse instanceof ResponseInterface;
            } else {
                $returnedResponse = false;
            }

            /**
             * Calling afterHandleRequest
             */
            if (is_object($eventsManager)) {
                $eventsManager->fire('application:afterHandleRequest', $this, $controller);
            }


            /**
             * If the dispatcher returns an object we try to render the view in auto-rendering mode
             */
            if ($returnedResponse === false) {
                if ($implicitView === true) {
                    if (is_object($controller)) {

                        $renderStatus = true;

                        /**
                         * This allows to make a custom view render
                         */
                        if (is_object($eventsManager)) {
                            $renderStatus = $eventsManager->fire('application:viewRender', $this, $view);
                        }

                        /**
                         * Check if the view process has been treated by the developer
                         */
                        if ($renderStatus !== false) {

                            /**
                             * Automatic render based on the latest controller executed
                             */
                            $view->render(
                                $dispatcher->getControllerName(),
                                $dispatcher->getActionName(),
                                $dispatcher->getParams()
                            );
                        }
                    }
                }
            }

            /**
             * Finish the view component (stop output buffering)
             */
            if ($implicitView === true) {
                $view->finish();
            }

            if ($returnedResponse === false) {

                $response = $dependencyInjector->getShared('response');
                if ($implicitView === true) {

                    /**
                     * The content returned by the view is passed to the response service
                     */
                    $response->setContent($view->getContent());
                }
            
            } else {

                /**
                 * We don't need to create a response because there is one already created
                 */
                $response = $possibleResponse;
            }
        }

        /**
         * Calling beforeSendResponse
         */
        if (is_object($eventsManager)) {
            $eventsManager->fire('application:beforeSendResponse', $this, $response);
        }

        /**
         * Headers and Cookies are automatically send
         */
        $response->sendHeaders();
        $response->sendCookies();

        /**
         * Return the response
         */
        return $response;
    }
}
