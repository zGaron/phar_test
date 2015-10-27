<?php
/**
 * Module Definition Interface
*/

namespace Phalcon\Mvc;

/**
 * Phalcon\Mvc\ModuleDefinitionInterface
 *
 * This interface must be implemented by class module definitions
 */
interface ModuleDefinitionInterface
{
    /**
     * Registers an autoloader related to the module
     */
    public function registerAutoloaders($dependencyInjector = null);

    /**
     * Registers services related to the module
     *
     * @param \Phalcon\DiInterface $dependencyInjector
     */
    public function registerServices($dependencyInjector);
}
