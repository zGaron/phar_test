<?php
/**
 * Service
 *
*/
namespace Phalcon\Di;

use \Phalcon\DiInterface;
use \Phalcon\Di\Exception;
use \Phalcon\Di\ServiceInterface;
use \Phalcon\Di\Service\Builder;

/**
 * Phalcon\Di\Service
 *
 * Represents individually a service in the services container
 *
 *<code>
 * $service = new Phalcon\Di\Service('request', 'Phalcon\Http\Request');
 * $request = $service->resolve();
 *<code>
 *
 */
class Service implements ServiceInterface
{
    /**
     * Name
     *
     * @var null|string
     * @access protected
    */
    protected $_name;

    /**
     * Definiton
     *
     * @var mixed
     * @access protected
    */
    protected $_definition;

    /**
     * Shared
     *
     * @var null|boolean
     * @access protected
    */
    protected $_shared = false;

    /**
     * Resolved
     *
     * @var null|boolean
     * @access protected
    */
    protected $_resolved = false;

    /**
     * Shared Instance
     *
     * @var null|object
     * @access protected
    */
    protected $_sharedInstance;

    /**
     * \Phalcon\DI\Service
     *
     * @param string! $name
     * @param mixed $definition
     * @param boolean|null $shared
     * @throws Exception
     */
    public function __construct($name, $definition, $shared = false)
    {
        /* Type check */
        if (!is_string($name)) {
            throw new Exception('Invalid parameter type.');
        }

        if (is_null($shared)) {
            $shared = false;
        } elseif (!is_bool($shared)) {
            throw new Exception('Invalid parameter type.');
        }

        /* Update member variables */
        $this->_name = $name;
        $this->_definition = $definition;
        $this->_shared = $shared;
    }

    /**
     * Returns the service's name
     *
     * @return string
     */
    public function getName()
    {
        return $this->_name;
    }

    /**
     * Sets if the service is shared or not
     *
     * @param boolean $shared
     * @throws Exception
     */
    public function setShared($shared)
    {
        if (!is_bool($shared)) {
            throw new Exception('Invalid parameter type.');
        }

        $this->_shared = $shared;
    }

    /**
     * Check whether the service is shared or not
     *
     * @return boolean
     */
    public function isShared()
    {
        return $this->_shared;
    }

    /**
     * Sets/Resets the shared instance related to the service
     *
     * @param object $sharedInstance
     * @throws Exception
     */
    public function setSharedInstance($sharedInstance)
    {
        if (!is_object($sharedInstance)) {
            throw new Exception('Invalid parameter type.');
        }

        $this->_sharedInstance = $sharedInstance;
    }

    /**
     * Set the service definition
     *
     * @param mixed $definition
     */
    public function setDefinition($definition)
    {
        $this->_definition = $definition;
    }

    /**
     * Returns the service definition
     *
     * @return mixed
     */
    public function getDefinition()
    {
        return $this->_definition;
    }

    /**
     * Resolves the service
     *
     * @param array|null $parameters
     * @param \Phalcon\DiInterface|null $dependencyInjector
     * @return mixed
     * @throws Exception
     */
    public function resolve($parameters = null, $dependencyInjector = null)
    {

        $shared = $this->_shared;

        /**
         * Check if the service is shared
         */
        if ($shared) {
            $sharedInstance = $this->_sharedInstance;
            if ($sharedInstance !== null) {
                return $sharedInstance;
            }
        }

        $found = true;
        $instance = null;

        $definition = $this->_definition;
        if (is_string($definition)) {

            /**
             * String definitions can be class names without implicit parameters
             */
            if (class_exists($definition)) {
                if (is_array($parameters)) {
                    if (count($parameters)) {
                        $reflection = new \ReflectionClass($definition);
                        $instance = $reflection->newInstanceArgs($parameters);
                    } else {
                        $reflection = new \ReflectionClass($definition);
                        $instance = $reflection->newInstance();
                    }
                } else {
                    $reflection = new \ReflectionClass($definition);
                    $instance = $reflection->newInstance();
                }
            } else {
                $found = false;
            }
        } else {

            /**
             * Object definitions can be a Closure or an already resolved instance
             */
            if (is_object($definition)) {
                if ($definition instanceof \Closure) {
                    if (is_array($parameters)) {
                        $instance = call_user_func_array($definition, $parameters);
                    } else {
                        $instance = call_user_func($definition);
                    }
                } else {
                    $instance = $definition;
                }
            } else {
                /**
                 * Array definitions require a 'className' parameter
                 */
                if (is_array($definition)) {
                    $builder = new Builder();
                    $instance = $builder->build($dependencyInjector, $definition, $parameters);
                } else {
                    $found = false;
                }
            }
        }

        /**
         * If the service can't be built, we must throw an exception
         */
        if (!$found) {
            throw new Exception("Service '" . $this->_name . "' cannot be resolved");
        }

        /**
         * Update the shared instance if the service is shared
         */
        if ($shared) {
            $this->_sharedInstance = $instance;
        }

        $this->_resolved = true;

        return $instance;
    }

    /**
     * Changes a parameter in the definition without resolve the service
     *
     * @param int $position
     * @param array $parameter
     * @return \Phalcon\DI\Service
     * @throws Exception
     */
    public function setParameter($position, $parameter)
    {
        /* Type check */

        if (is_int($position) === false) {
            throw new Exception('Position must be integer');
        }

        if (is_array($parameter) === false) {
            throw new Exception('The parameter must be an array');
        }

        $definition = $this->_definition;
        if (!is_array($definition)) {
            throw new Exception("Definition must be an array to update its parameters");
        }

        /**
         * Update the parameter
         */
        if (isset($definition['arguments'])) {
            $arguments = $definition['arguments'];
            $arguments[$position] = $parameter;
        } else {
            $arguments = [$position => $parameter];
        }

        /**
         * Re-update the arguments
         */
        $definition['arguments'] = $arguments;

        /**
         * Re-update the definition
         */
        $this->_definition = $definition;

        return $this;
    }

    /**
     * Returns a parameter in a specific position
     *
     * @param int $position
     * @return array|null
     * @throws Exception
     */
    public function getParameter($position)
    {

        if (!is_int($position)) {
            throw new Exception('Position must be integer');
        }

        $definition = $this->_definition;
        if (!is_array($definition)) {
            throw new Exception("Definition must be an array to obtain its parameters");
        }

        /**
         * Update the parameter
         */
        if (isset($definition['arguments'])) {
            $arguments = $definition['arguments'];
            if (isset($arguments[$position])) {
                $parameter = $arguments[$position];
                return $parameter;
            }
        }

        return null;
    }

    /**
     * Returns true if the service was resolved
     */
    public function isResolved()
    {
        return $this->_resolved;
    }

    /**
     * Restore the internal state of a service
     *
     * @param array $attributes
     * @return \Phalcon\DI\Service
     * @throws Exception
     */
    public static function __set_state($attributes)
    {
        
        if (!isset($attributes["_name"])) {
            throw new Exception("The attribute '_name' is required");
        }

        if (!isset($attributes["_definition"])) {
            throw new Exception("The attribute '_definition' is required");
        }

        if (!isset($attributes["_shared"])) {
            throw new Exception("The attribute '_shared' is required");
        }

        return new self($name, $definition, $shared);
    }
}
