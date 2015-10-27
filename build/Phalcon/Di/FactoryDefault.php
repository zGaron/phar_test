<?php
/**
 * Factory Default
 *
*/
namespace Phalcon\Di;

use \Phalcon\Di;

/**
 * Phalcon\DI\FactoryDefault
 *
 * This is a variant of the standard Phalcon\DI. By default it automatically
 * registers all the services provided by the framework. Thanks to this, the developer does not need
 * to register each service individually providing a full stack framework
 *
 */
class FactoryDefault extends Di
{
    /**
     * \Phalcon\DI\FactoryDefault constructor
     */
    public function __construct()
    {
        parent::__construct();

        $this->_services = [
            /* Base */ 
            "router" =>             new Service("router", "Phalcon\\Mvc\\Router", true),
            "dispatcher" =>         new Service("dispatcher", "Phalcon\\Mvc\\Dispatcher", true),
            "url" =>                new Service("url", "Phalcon\\Mvc\\Url", true),
            
            /* Models */
            //"modelsManager" =>      new Service("modelsManager", "Phalcon\\Mvc\\Model\\Manager", true),
            //"modelsMetadata" =>     new Service("modelsMetadata", "Phalcon\\Mvc\\Model\\MetaData\\Memory", true),
            
            /* Request/Response */
            "response" =>           new Service("response", "Phalcon\\Http\\Response", true),
            "cookies" =>            new Service("cookies", "Phalcon\\Http\\Response\\Cookies", true),
            "request" =>            new Service("request", "Phalcon\\Http\\Request", true),
            
            /* Filter/Escaper */
            "filter" =>             new Service("filter", "Phalcon\\Filter", true),
            //"escaper" =>            new Service("escaper", "Phalcon\\Escaper", true),
            
            /* Security */
            "security" =>           new Service("security", "Phalcon\\Security", true),
            "crypt" =>              new Service("crypt", "Phalcon\\Crypt", true),
            
            /* Annotations */
            //"annotations" =>        new Service("annotations", "Phalcon\\Annotations\\Adapter\\Memory", true),
            
            /* Flash */
            //"flash" =>              new Service("flash", "Phalcon\\Flash\\Direct", true),
            //"flashSession" =>       new Service("flashSession", "Phalcon\\Flash\\Session", true),
            
            /* Tag/Helpers */
            //"tag" =>                new Service("tag", "Phalcon\\Tag", true),
            
            /* Session */
            "session" =>            new Service("session", "Phalcon\\Session\\Adapter\\Files", true),
            "sessionBag" =>         new Service("sessionBag", "Phalcon\\Session\\Bag"),
            
            /* Managers */
            "eventsManager" =>      new Service("eventsManager", "Phalcon\\Events\\Manager", true),
            //"transactionManager" => new Service("transactionManager", "Phalcon\\Mvc\\Model\\Transaction\\Manager", true),
            
            //"assets" =>             new Service("assets", "Phalcon\\Assets\\Manager", true)
        ];

    }
}
