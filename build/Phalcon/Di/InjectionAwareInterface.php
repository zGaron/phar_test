<?php
/**
 * Injection Aware Interface
 *
*/
namespace Phalcon\Di;

use \Phalcon\DiInterface;

/**
 * Phalcon\DI\InjectionAwareInterface initializer
 * 
 * This interface must be implemented in those classes that uses internally the Phalcon\Di that creates them
 */
interface InjectionAwareInterface
{
    /**
     * Sets the dependency injector
     *
     * @param \Phalcon\DiInterface $dependencyInjector
     */
    public function setDI($dependencyInjector);

    /**
     * Returns the internal dependency injector
     *
     * @return \Phalcon\DiInterface
     */
    public function getDI();
}
