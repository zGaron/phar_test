<?php
/**
 * Service Builder
 *
*/
namespace Phalcon\Di\Service;

use \Phalcon\Di\Exception;
use \Phalcon\DiInterface;

/**
 * Phalcon\Di\Service\Builder
 *
 * This class builds instances based on complex definitions
 *
 */
class Builder
{
    /**
     * Resolves a constructor/call parameter
     *
     * @param \Phalcon\DiInterface $dependencyInjector
     * @param int $position
     * @param array $argument
     * @return mixed
     * @throws Exception
     */
    protected function _buildParameter($dependencyInjector, $position, $argument)
    {       
        /**
         * All the arguments must have a type
         */
        if (!isset($argument['type'])) {
            throw new Exception("Argument at position " . $position . " must have a type");
        } else {
            $type = $argument['type'];
        }

        switch ($type) {
            
            /**
             * If the argument type is 'service', we obtain the service from the DI
             */
            case 'service':
                if (!isset($argument['name'])) {
                    throw new Exception("Service 'name' is required in parameter on position " . $position);
                } else {
                    $name = $argument['name'];
                }

                if (!is_object($dependencyInjector)) {
                    throw new Exception("The dependency injector container is not valid");
                }
                return $dependencyInjector->get($name);

            /**
             * If the argument type is 'parameter', we assign the value as it is
             */
            case 'parameter':
                if (!isset($argument['value'])) {
                    throw new Exception("Service 'value' is required in parameter on position " . $position);
                } else {
                    $value = $argument['value'];
                }
                return $value;

            /**
             * If the argument type is 'instance', we assign the value as it is
             */
            case 'instance':
                if(!isset($argument['name'])) {
                    throw new Exception("Service 'className' is required in parameter on position " . $position);
                } else {
                    $name = $argument['name'];
                }

                if (!is_object($dependencyInjector)) {
                    throw new Exception("The dependency injector container is not valid");
                }

                if (isset($argument['arguments'])) {
                    /**
                     * Build the instance with arguments
                     */
                    return $ependencyInjector->get($name, $instanceArguments);
                }

                /**
                 * The instance parameter does not have arguments for its constructor
                 */
                return $dependencyInjector->get($name);
            
            default:
                /**
                 * Unknown parameter type
                 */
                throw new Exception("Unknown service type in parameter on position " . $position);
        }
    }

    /**
     * Resolves an array of parameters
     *
     * @param \Phalcon\DiInterface $dependencyInjector
     * @param array $arguments
     * @return array
     * @throws Exception
     */
    protected function _buildParameters($dependencyInjector, $arguments)
    {
        $buildArguments = [];
        foreach ($arguments as $position => $argument) {
            $buildArguments[] = $this->_buildParameter($dependencyInjector, $position, $argument);
        }
        return $buildArguments;
    }

    /**
     * Builds a service using a complex service definition
     *
     * @param \Phalcon\DiInterface $dependencyInjector
     * @param array $definition
     * @param array|null $parameters
     * @return mixed
     * @throws Exception
     */
    public function build($dependencyInjector, $definition, $parameters = null)
    {
        
        /**
         * The class name is required
         */
        if (!isset($definition["className"])) {
            throw new Exception("Invalid service definition. Missing 'className' parameter");
        } else {
            $className = $definition["className"];
        }

        if (is_array($parameters)) {

            /**
             * Build the instance overriding the definition constructor parameters
             */
            if (count($parameters)) {
                $reflection = new \ReflectionClass($className);
                $instance = $reflection->newInstanceArgs($parameters);
            } else {
                $reflection = new \ReflectionClass($className);
                $instance = $reflection->newInstance();
            }

        } else {

            /**
             * Check if the argument has constructor arguments
             */
            if (isset($definition['arguments'])) {
                /**
                 * Create the instance based on the parameters
                 */
                $arguments = $definition['arguments'];
                $reflection = new \ReflectionClass($className);
                $instance = $reflection->newInstanceArgs($this->_buildParameters($dependencyInjector, $arguments));
            
            } else {
                $reflection = new \ReflectionClass($className);
                $instance = $reflection->newInstance();
            }
        }

        /**
         * The definition has calls?
         */
        if (isset($definition['calls'])) {
            $paramCalls = $definition['calls'];

            if (!is_object($instance)) {
                throw new Exception("The definition has setter injection parameters but the constructor didn't return an instance");
            }

            if (!is_array($paramCalls)) {
                throw new Exception("Setter injection parameters must be an array");
            }

            /**
             * The method call has parameters
             */
            foreach ($paramCalls as $methodPosition => $method) {
                
                /**
                 * The call parameter must be an array of arrays
                 */
                if (!is_array($method)) {
                    throw new Exception("Method call must be an array on position " . $methodPosition);
                }

                /**
                 * A param 'method' is required
                 */
                if (!isset($method['method'])) {
                    throw new Exception("The method name is required on position " . $methodPosition);
                } else {
                    $methodName = $method['method'];
                }

                /**
                 * Create the method call
                 */
                $methodCall = [$instance, $methodName];

                if (isset($method['arguments'])) {
                    $arguments = $method['arguments'];

                    if (!is_array($arguments)) {
                        throw new Exception("Call arguments must be an array " . $methodPosition);
                    }

                    if (count($arguments)) {

                        /**
                         * Call the method on the instance
                         */
                        call_user_func_array($methodCall, $this->_buildParameters($dependencyInjector, $arguments));
                    
                        /**
                         * Go to next method call
                         */
                        continue;
                    }
                }

                /**
                 * Call the method on the instance without arguments
                 */
                call_user_func($methodCall);
            }
        }

        /**
         * The definition has properties?
         */
        if (isset($definition['properties'])) {
            $paramCalls = $definition['properties'];

            if (!is_object($instance)) {
                throw new Exception("The definition has properties injection parameters but the constructor didn't return an instance");
            }

            if (!is_array($paramCalls)) {
                throw new Exception("Setter injection parameters must be an array");
            }

            /**
             * The method call has parameters
             */
            foreach ($paramCalls as $propertyPosition => $property) {
                
                /**
                 * The call parameter must be an array of arrays
                 */
                if (!is_array($property)) {
                    throw new Exception("Property must be an array on position " . $propertyPosition);
                }

                /**
                 * A param 'name' is required
                 */
                if (!isset($property['name'])) {
                    $propretyName = $property['name'];
                } else {
                    throw new Exception("The property name is required on position " . $propertyPosition);
                }

                /**
                 * A param 'value' is required
                 */
                if (!isset($property['value'])) {
                    throw new Exception("The property value is required on position " . $propertyPosition);
                } else {
                    $propertyValue = $property['value'];
                }

                /**
                 * Update the public property
                 */
                $instance->{$propretyName} = $this->_buildParameters($dependencyInjector, $propertyPosition, $propretyName);
            }
        }

        return $instance;        
    }

}