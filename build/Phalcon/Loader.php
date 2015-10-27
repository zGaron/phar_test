<?php
/**
 * Loader
 *
*/
namespace Phalcon;

use \Phalcon\Events\EventsAwareInterface;
use \Phalcon\Events\ManagerInterface;
use \Phalcon\Loader\Exception;
use \Phalcon\Text;

/**
 * Phalcon\Loader
 *
 * This component helps to load your project classes automatically based on some conventions
 *
 *<code>
 * //Creates the autoloader
 * $loader = new Phalcon\Loader();
 *
 * //Register some namespaces
 * $loader->registerNamespaces(array(
 *   'Example\Base' => 'vendor/example/base/',
 *   'Example\Adapter' => 'vendor/example/adapter/',
 *   'Example' => 'vendor/example/'
 * ));
 *
 * //register autoloader
 * $loader->register();
 *
 * //Requiring this class will automatically include file vendor/example/adapter/Some.php
 * $adapter = Example\Adapter\Some();
 *</code>
 *
 */
class Loader implements EventsAwareInterface
{
    /**
     * Events Manager
     *
     * @var Phalcon\Events\ManagerInterface|null
     * @access protected
    */
    protected $_eventsManager = null;

    /**
     * Found Path
     *
     * @var string|null
     * @access protected
    */
    protected $_foundPath = null;

    /**
     * Checked Path
     *
     * @var string|null
     * @access protected
    */
    protected $_checkedPath = null;

    /**
     * Prefixes
     *
     * @var array|null
     * @access protected
    */
    protected $_prefixes = null;

    /**
     * Classes
     *
     * @var array|null
     * @access protected
    */
    protected $_classes = null;

    /**
     * Extensions
     *
     * @var array
     * @access protected
    */
    protected $_extensions;

    /**
     * Namespaces
     *
     * @var array|null
     * @access protected
    */
    protected $_namespaces = null;

    /**
     * Directories
     *
     * @var array|null
     * @access protected
    */
    protected $_directories = null;

    /**
     * Registered
     *
     * @var boolean
     * @access protected
    */
    protected $_registered = false;

    /**
     * \Phalcon\Loader constructor
     */
    public function __construct()
    {
        $this->_extensions = ['php'];
    }

    /**
     * Sets the events manager
     *
     * @param \Phalcon\Events\ManagerInterface $eventsManager
     * @throws \Phalcon\Loader\Exception
     */
    public function setEventsManager($eventsManager)
    {
        if (!is_object($eventsManager) || $eventsManager instanceof ManagerInterface === false) {
            throw new Exception('Invalid parameter type.');
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
     * Sets an array of extensions that the loader must try in each attempt to locate the file
     *
     * @param array! $extensions
     * @return \Phalcon\Loader
     * @throws \Phalcon\Loader\Exception
     */
    public function setExtensions($extensions)
    {
        if (!is_array($extensions)) {
            throw new Exception('Parameter extension must be an array');
        }

        $this->_extensions = $extensions;

        return $this;
    }

    /**
     * Return file extensions registered in the loader
     *
     * @return array
     */
    public function getExtensions()
    {
        return $this->_extensions;
    }

    /**
     * Register namespaces and their related directories
     *
     * @param array! $namespaces
     * @param boolean|null $merge
     * @return \Phalcon\Loader
     * @throws \Phalcon\Loader\Exception
     */
    public function registerNamespaces($namespaces, $merge = false)
    {
        if (!is_array($namespaces)) {
            throw new Exception('Parameter namespaces must be an array');
        }

        if (!is_bool($merge)) {
            throw new Exception('Invalid parameter type.');
        }

        if ($merge) {
            $currentNamespaces = $this->_namespaces;
            if (is_array($currentNamespaces)) {
                $mergedNamespace = array_merge($currentNamespaces, $namespaces);
            } else {
                $mergedNamespace = $namespaces;
            }
            $this->_namespaces = $mergedNamespace;
        } else {
            $this->_namespaces = $namespaces;
        }

        return $this;
    }

    /**
     * Return current namespaces registered in the autoloader
     *
     * @return array|null
     */
    public function getNamespaces()
    {
        return $this->_namespaces;
    }

    /**
     * Register directories on which "not found" classes could be found
     *
     * @param array $prefixes
     * @param boolean|null $merge
     * @return \Phalcon\Loader
     * @throws \Phalcon\Loader\Exception
     */
    public function registerPrefixes($prefixes, $merge = false)
    {
        if (!is_array($prefixes)) {
            throw new Exception('Parameter prefixes must be an array');
        }

        if (!is_bool($merge)) {
            throw new Exception('Invalid parameter type.');
        }

        if ($merge) {
            $currentPrefixes = $this->_prefixes;
            if (is_array($currentPrefixes)) {
                $mergedPrefixes = array_merge($currentPrefixes, $prefixes);
            } else {
                $mergedPrefixes = $prefixes;
            }
            $this->_prefixes = $mergedPrefixes;
        } else {
            $this->_prefixes = $prefixes;
        }

        return $this;

    }

    /**
     * Return current prefixes registered in the autoloader
     *
     * @param array|null
     */
    public function getPrefixes()
    {
        return $this->_prefixes;
    }

    /**
     * Register directories on which "not found" classes could be found
     *
     * @param array $directories
     * @param boolean|null $merge
     * @return \Phalcon\Loader
     * @throws \Phalcon\Loader\Exception
     */
    public function registerDirs($directories, $merge = false)
    {
        if (!is_array($directories)) {
            throw new Exception('Parameter directories must be an array');
        }

        if (!is_bool($merge)) {
            throw new Exception('Invalid parameter type.');
        }

        if ($merge) {
            $currentDirectories = $this->_directories;
            if(is_array($currentDirectories)) {
                $mergedDirectories = array_merge($currentDirectories, $directories);
            } else {
                $mergedDirectories = $directories;
            }
        } else {
            $this->_directories = $directories;
        }

        return $this;
    }

    /**
     * Return current directories registered in the autoloader
     *
     * @return array|null
     */
    public function getDirs()
    {
        return $this->_directories;
    }

    /**
     * Register classes and their locations
     *
     * @param array $classes
     * @param boolean|null $merge
     * @return \Phalcon\Loader
     * @throws \Phalcon\Loader\Exception
     */
    public function registerClasses($classes, $merge = false)
    {
        if (!is_array($classes)) {
            throw new Exception('Parameter classes must be an array');
        }

        if (!is_bool($merge)) {
            throw new Exception('Invalid parameter type.');
        }

        if ($merge) {
            $currentClasses = $this->_classes;
            if (is_array($currentClasses)) {
                $mergedClasses = array_merge($mergedClasses, $classes);
            } else {
                $mergedClasses = $classes;
            }
            $this->_classes = $mergedClasses;
        } else {
            $this->_classes = $classes;
        }
        return $this;
    }

    /**
     * Return the current class-map registered in the autoloader
     *
     * @return array|null
     */
    public function getClasses()
    {
        return $this->_classes;
    }

    /**
     * Register the autoload method
     *
     * @return \Phalcon\Loader
     */
    public function register()
    {
        if ($this->_registered === false) {
            spl_autoload_register(array($this, 'autoLoad'));
            $this->_registered = true;
        }

        return $this;
    }

    /**
     * Unregister the autoload method
     *
     * @return \Phalcon\Loader
     */
    public function unregister()
    {
        if ($this->_registered === true) {
            spl_autoload_unregister(array($this, 'autoLoad'));
            $this->_registered = false;
        }

        return $this;
    }

    /**
     * Makes the work of autoload registered classes
     *
     * @param string! $className
     * @return boolean
     */
    public function autoload($className)
    {

        $eventsManager = $this->_eventsManager;
        if (is_object($eventsManager)) {
            $eventsManager->fire("loader:beforeCheckClass", $this, $className);
        }

        /**
         * First we check for static paths for classes
         */
        $classes = $this->_classes;
        if (is_array($classes)) {
            if (isset($classes[$className])) {
                $filePath = $classes[$className];
                if (is_object($eventsManager)) {
                    $this->_foundPath = $filePath;
                    $eventsManager->fire("loader:pathFound", $this, $filePath);
                }
            }
            require $filePath;
            return true;
        }

        $extensions = $this->_extensions;

        $ds = DIRECTORY_SEPARATOR;
        $namespaceSeparator = "\\";

        /**
         * Checking in namespaces
         */
        $namespaces = $this->_namespaces;
        if (is_array($namespaces)) {

            foreach ($namespaces as $nsPrefix => $directory) {
                
                /**
                 * The class name must start with the current namespace
                 */
                if (Text::startsWith($className, $nsPrefix)) {

                    /**
                     * Append the namespace separator to the prefix
                     */
                    $fileName = substr($className, strlen($nsPrefix . $namespaceSeparator));
                    $fileName = str_replace($namespaceSeparator, $ds, $fileName);

                    if ($fileName) {

                        /**
                         * Add a trailing directory separator if the user forgot to do that
                         */
                        $fixedDirectory = rtrim($directory, $ds) . $ds;

                        foreach ($extensions as $extension) {
                            
                            $filePath = $fixedDirectory . $fileName . "." . $extension;

                            /**
                             * Check if a events manager is available
                             */
                            if (is_object($eventsManager)) {
                                $this->_checkedPath = $filePath;
                                $eventsManager->fire("loader:beforeCheckPath", $this);
                            }

                            /**
                             * This is probably a good path, let's check if the file exists
                             */
                            if (is_file($filePath)) {

                                if (is_object($eventsManager)) {
                                    $this->_foundPath = $filePath;
                                    $eventsManager->fire("loader:pathFound", $this, $filePath);
                                }

                                /**
                                 * Simulate a require
                                 */
                                require $filePath;

                                /**
                                 * Return true mean success
                                 */
                                return true;
                            }
                        }
                    }
                }
                
            }
        }

        /**
         * Checking in prefixes
         */
        $prefixes = $this->_prefixes;

        if (is_array($prefixes)) {

            foreach ($prefixes as $prefix => $directory) {
                
                /**
                 * The class name starts with the prefix?
                 */
                if (Text::startsWith($className, $prefix)) {

                    /**
                     * Get the possible file path
                     */
                    $fileName = str_replace($prefix . $namespaceSeparator, "", $className);
                    $fileName = str_replace($prefix . "_", "", $fileName);
                    $fileName = str_replace("_", $ds, $fileName);

                    //$this->_fileName = $fileName;

                    if ($fileName) {

                        /**
                         * Add a trailing directory separator if the user forgot to do that
                         */
                        $fixedDirectory = rtrim($directory, $ds) . $ds;

                        foreach ($extensions as $extension) {
                            
                            $filePath = $fixedDirectory . $fileName . "." . $extension;

                            if (is_object($eventsManager)) {
                                $this->_checkedPath = $filePath;
                                $eventsManager->fire("loader:beforeCheckPath", $this, $filePath);
                            }

                            if (is_file($filePath)) {

                                /**
                                 * Call 'pathFound' event
                                 */
                                if (is_object($eventsManager)) {
                                    $this->_foundPath = $filePath;
                                    $eventsManager->fire("loader:pathFound", $this, $filePath);
                                }

                                /**
                                 * Simulate a require
                                 */
                                require $filePath;

                                /**
                                 * Return true meaning success
                                 */
                                return true;
                            }
                        }
                    }
                }
            }
        }

        /**
         * Change the pseudo-separator by the directory separator in the class name
         */
        $dsClassName = str_replace("_", $ds, $className);

        /**
         * And change the namespace separator by directory separator too
         */
        $nsClassName = str_replace("\\", $ds, $dsClassName);

        /**
         * Checking in directories
         */
        $directories = $this->_directories;
        if (is_array($directories)) {

            foreach ($directories as $directory) {
                
                /**
                 * Add a trailing directory separator if the user forgot to do that
                 */
                $fixedDirectory = rtrim($directory, $ds) . $ds;

                foreach ($extensions as $extension) {
                    
                    /**
                     * Create a possible path for the file
                     */
                    $filePath = $fixedDirectory . $nsClassName . "." . $extension;

                    if (is_object($eventsManager)) {
                        $this->_checkedPath = $filePath;
                        $eventsManager->fire("loader:beforeCheckPath", $this, $filePath);
                    }

                    /**
                     * Check in every directory if the class exists here
                     */
                    if (is_file($filePath)) {

                        /**
                         * Call 'pathFound' event
                         */
                        if (is_object($eventsManager)) {
                            $this->_foundPath = $filePath;
                            $eventsManager->fire("loader:pathFound", $this, $filePath);
                        }

                        /**
                         * Simulate a require
                         */
                        require $filePath;

                        /**
                         * Return true meaning success
                         */
                        return true;
                    }
                }
            }
        }

        /**
         * Call 'afterCheckClass' event
         */
        if (is_object($eventsManager)) {
            $eventsManager->fire("loader:afterCheckClass", $this, $className);
        }

        /**
         * Cannot find the class, return false
         */
        return false;
        
    }

    /**
     * Get the path when a class was found
     *
     * @return string|null
     */
    public function getFoundPath()
    {
        return $this->_foundPath;
    }

    /**
     * Get the path the loader is checking for a path
     *
     * @return string|null
     */
    public function getCheckedPath()
    {
        return $this->_checkedPath;
    }
}
