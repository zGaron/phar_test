<?php
/**
 * Dependency Injector
 *
*/
namespace Phalcon;

use \Phalcon\DiInterface;
use \Phalcon\Di\Service;
use \Phalcon\Di\ServiceInterface;
use \Phalcon\Di\Exception;
use \Phalcon\Events\ManagerInterface;
use \Phalcon\Di\InjectionAwareInterface;

/**
 * Phalcon\DI
 *
 * Phalcon\DI is a component that implements Dependency Injection/Service Location
 * of services and it's itself a container for them.
 *
 * Since Scene is highly decoupled, Phalcon\DI is essential to integrate the different
 * components of the framework. The developer can also use this component to inject dependencies
 * and manage global instances of the different classes used in the application.
 *
 * Basically, this component implements the `Inversion of Control` pattern. Applying this,
 * the objects do not receive their dependencies using setters or constructors, but requesting
 * a service dependency injector. This reduces the overall complexity, since there is only one
 * way to get the required dependencies within a component.
 *
 * Additionally, this pattern increases testability in the code, thus making it less prone to errors.
 *
 *<code>
 * $di = new Phalcon\DI();
 *
 * //Using a string definition
 * $di->set('request', 'Phalcon\Http\Request', true);
 *
 * //Using an anonymous function
 * $di->set('request', function(){
 *    return new Phalcon\Http\Request();
 * }, true);
 *
 * $request = $di->getRequest();
 *
 *</code>
 *
 */
class Di implements DiInterface
{
    /**
     * Services
     *
     * @var array
     * @access protected
    */
    protected $_services;

    /**
     * Shared Instances
     *
     * @var array
     * @access protected
    */
    protected $_sharedInstances;

    /**
     * Fresh Instance
     *
     * @var boolean
     * @access protected
    */
    protected $_freshInstance = false;

    /**
     * Events Manager
     *
     * @var \Phalcon\Events\ManagerInterface
     */
    protected $_eventsManager;

    /**
     * Default Instance
     *
     * @var null|\Phalcon\DI
     * @access protected
    */
    protected static $_default;

    /**
     * \Phalcon\DI constructor
     */
    public function __construct()
    {
        $di = self::$_default;
        if(!$di) {
            self::$_default = $this;
        }
    }

    /**
     * Sets the internal event manager
     * @param Phalcon\Events\ManagerInterface $eventsManager
     */
    public function setInternalEventsManager($eventsManager)
    {
        $this->_eventsManager = $eventsManager;
    }

    /**
     * Returns the internal event manager
     * @return Phalcon\Events\ManagerInterface
     */
    public function getInternalEventsManager()
    {
        return $this->_eventsManager;
    }

    /**
     * Registers a service in the services container
     *
     * @param string! $name
     * @param mixed $definition
     * @param boolean $shared
     * @return \Phalcon\Di\ServiceInterface|null
     */
    public function set($name, $definition, $shared = false)
    {
        if (!is_string($name)) {
            throw new Exception('Invalid parameter type.');
        }

        $service = new Service($name, $definition, $shared);
        $this->_services[$name] = $service;
        return $service;
    }

    /**
     * Registers an "always shared" service in the services container
     *
     * @param string! $name
     * @param mixed $definition
     * @return \Phalcon\Di\ServiceInterface|null
     */
    public function setShared($name, $definition)
    {
        if (!is_string($name)) {
            throw new Exception('Invalid parameter type.');
        }

        return $this->set($name, $definition, true);
        /*
        $service = new Service($name, $definition, true);
        $this->_services[$name] = $service;
        return $service;
        */
    }

    /**
     * Removes a service in the services container
     *
     * @param string! $name
     */
    public function remove($name)
    {
        if (!is_string($name)) {
            throw new Exception('Invalid parameter type.');
        }

        unset($this->_services[$name]);
        unset($this->_sharedInstances[$name]);
    }

    /**
     * Attempts to register a service in the services container
     * Only is successful if a service hasn't been registered previously
     * with the same name
     *
     * @param string! $name
     * @param mixed $definition
     * @param boolean $shared
     * @return \Phalcon\Di\ServiceInterface|boolean
     */
    public function attempt($name, $definition, $shared = false)
    {
        if (!is_string($name)) {
            throw new Exception('Invalid parameter type.');
        }

        if (!isset($this->_services[$name])) {
            $service = new Service($name, $definition, $shared);
            $this->_services[$name] = $service;
            return $service;
        }
        return false;
    }

    /**
     * Sets a service using a raw \Phalcon\DI\Service definition
     *
     * @param string! $name
     * @param \Phalcon\Di\ServiceInterface $rawDefinition
     * @return \Phalcon\Di\ServiceInterface
     */
    public function setRaw($name, $rawDefinition)
    {
        if (!is_string($name)) {
            throw new Exception('Invalid parameter type.');
        }

        $this->_services[$name] = $rawDefinition;
        return $rawDefinition;
    }

    /**
     * Returns a service definition without resolving
     *
     * @param string! $name
     * @return mixed
     * @throws Exception
     */
    public function getRaw($name)
    {
        if (!is_string($name)) {
            throw new Exception('Invalid parameter type.');
        }

        if (isset($this->_services[$name])) {
            $service = $this->_services[$name];
            return $service->getDefinition();
        }

        throw new Exception("Service '" . $name . "' wasn't found in the dependency injection container");
    }

    /**
     * Returns a \Phalcon\Di\Service instance
     *
     * @param string! $name
     * @return \Phalcon\Di\ServiceInterface
     * @throws Exception
     */
    public function getService($name)
    {
        if (!is_string($name)) {
            throw new Exception('Invalid parameter type.');
        }

        if (isset($this->_services[$name])) {
            $service = $this->_services[$name];
            return $service;
        }

        throw new Exception("Service '" . name . "' wasn't found in the dependency injection container");
    }

    /**
     * Resolves the service based on its configuration
     *
     * @param string! $name
     * @param array|null $parameters
     * @return mixed
     * @throws Exception
     */
    public function get($name, $parameters = null)
    {
        if (!is_string($name)) {
            throw new Exception('Invalid parameter type.');
        }

        //let eventsManager = <ManagerInterface> this->_eventsManager;
        
        $eventsManager = $this->_eventsManager;
        if (is_object($eventsManager)) {
            $eventsManager->fire("di:beforeServiceResolve", $this, ["name" => $name, "parameters" => $parameters]);
        }
        
        if (isset($this->_services[$name])) {
            /**
             * The service is registered in the DI
             */
            $service = $this->_services[$name];
            $instance = $service->resolve($parameters, $this);
        } else {
            /**
             * The DI also acts as builder for any class even if it isn't defined in the Di
             */
            if(!class_exists($name)) {
                throw new Exception("Service '" . $name . "' wasn't found in the dependency injection container");
            }

            if (is_array($parameters)) {
                if (count($parameters)) {
                    $reflection = new \ReflectionClass($name);
                    $instance = $reflection->newInstanceArgs($parameters);
                } else {
                    $reflection = new \ReflectionClass($name);
                    $instance = $reflection->newInstance();
                }
            } else {
                $reflection = new \ReflectionClass($name);
                $instance = $reflection->newInstance();
            }
        }

        /**
         * Pass the Di itself if the instance implements \Phalcon\Di\InjectionAwareInterface
         */
        
        if(is_object($instance)) {
            if ($instance instanceof InjectionAwareInterface) {
                $instance->setDI($this);
            }
        }

        
        if (is_object($eventsManager)) {
            $eventsManager->fire(
                "di:afterServiceResolve",
                $this,
                [
                    "name" => $name,
                    "parameters" => $parameters,
                    "instance" => $instance
                ]
            );
        }
        
        return $instance;
    }

    /**
     * Resolves a service, the resolved service is stored in the DI, subsequent requests for this service will return the same instance
     *
     * @param string! $name
     * @param array|null $parameters
     * @return mixed
     * @throws Exception
     */
    public function getShared($name, $parameters = null)
    {
        if (!is_string($name)) {
            throw new Exception('Invalid parameter type.');
        }

        /**
         * This method provides a first level to shared instances allowing to use non-shared services as shared
         */
        if (isset($this->_sharedInstances[$name])) {
            $instance = $this->_sharedInstances[$name];
            $this->_freshInstance = false;
        } else {

            /**
             * Resolve the instance normally
             */
            $instance = $this->get($name, $parameters);

            /**
             * Save the instance in the first level shared
             */
            $this->_sharedInstances[$name] = $instance;
            $this->_freshInstance = true;
        }

        return $instance;
    }

    /**
     * Check whether the Di contains a service by a name
     *
     * @param string! $name
     * @return boolean
     */
    public function has($name)
    {
        if (!is_string($name)) {
            throw new Exception('Invalid parameter type.');
        }
        
        return isset($this->_services[$name]);
    }

    /**
     * Check whether the last service obtained via getShared produced a fresh instance or an existing one
     *
     * @return boolean
     */
    public function wasFreshInstance()
    {
        return $this->_freshInstance;
    }

    /**
     * Return the services registered in the DI
     *
     * @return \Phalcon\Di\Service[]
     */
    public function getServices()
    {
        return $this->_services;
    }

    /**
     * Check if a service is registered using the array syntax.
     * Alias for \Phalcon\Di::has()
     *
     * @param string! $name
     * @return boolean
     */
    public function offsetExists($name)
    {
        return $this->has($name);
    }

    /**
     * Allows to register a shared service using the array syntax.
     * Alias for \Phalcon\Di::setShared()
     *
     *<code>
     *  $di['request'] = new \Phalcon\Http\Request();
     *</code>
     *
     * @param string $name
     * @param mixed $definition
     * @return boolean
     */
    public function offsetSet($name, $definition)
    {
        $this->setShared($name, $definition);
        return true;
    }

    /**
     * Allows to obtain a shared service using the array syntax.
     * Alias for \Phalcon\Di::getShared()
     *
     *<code>
     *  var_dump($di['request']);
     *</code>
     *
     * @param string $name
     * @return mixed
     */
    public function offsetGet($name)
    {
        return $this->getShared($name, null);
    }

    /**
     * Removes a service from the services container using the array syntax.
     * Alias for \Phalcon\Di::remove()
     *
     * @param string $name
     */
    public function offsetUnset($name)
    {
        return false;
    }

    /**
     * Magic method to get or set services using setters/getters
     *
     * @param string $method
     * @param array|null $arguments
     * @return mixed
     * @throws Exception
     */
    public function __call($method, $arguments = null)
    {

        /**
         * If the magic method starts with "get" we try to get a service with that name
         */
        if (strpos($method, 'get') === 0) {
            $service = $this->_services;
            $possibleService = lcfirst(substr($method, 3));
            
            if(isset($service[$possibleService])) {
                
                if (count($arguments)) {
                    $instance = $this->get($possibleService, $arguments);
                } else {
                    $instance = $this->get($possibleService);
                }
                
                return $instance;
            }
        }

        if (strpos($method, 'set') === 0) {
            
            if (isset($arguments[0])) {
                $definition = $arguments[0];
                $this->set(lcfirst(substr($method, 3)), $definition);
                
                return null;
            }
        }

        throw new Exception('Call to undefined method or service \''.$method."'");
        
    }

    /**
     * Set a default dependency injection container to be obtained into static methods
     *
     * @param \Phalcon\DiInterface $dependencyInjector
     */
    public static function setDefault($dependencyInjector)
    {
        if ($dependencyInjector instanceof DiInterface) {
            self::$_default = $dependencyInjector;
        }
    }

    /**
     * Return the lastest DI created
     *
     * @return \Phalcon\DiInterface
     */
    public static function getDefault()
    {
        return self::$_default;
    }

    /**
     * Resets the internal default DI
     */
    public static function reset()
    {
        self::$_default = null;
    }
}
