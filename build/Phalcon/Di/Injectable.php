<?php
/**
 * Injectable
 *
*/
namespace Phalcon\di;

use \Phalcon\Di;
use \Phalcon\DiInterface;
use \Phalcon\Events\ManagerInterface;
use \Phalcon\Di\InjectionAwareInterface;
use \Phalcon\Events\EventsAwareInterface;
use \Phalcon\Di\Exception;
use \Phalcon\Session\BagInterface;

/**
 * Phalcon\di\Injectable
 *
 * This class allows to access services in the services container by just only accessing a public property
 * with the same name of a registered service
 * 这个类允许在服务容器中访问服务，只需访问一个注册服务的相同名称的公共属性
 * @property \Phalcon\Mvc\Dispatcher|\Phalcon\Mvc\DispatcherInterface $dispatcher;
 * @property \Phalcon\Mvc\Router|\Phalcon\Mvc\RouterInterface $router
 * @property \Phalcon\Mvc\Url|\Phalcon\Mvc\UrlInterface $url
 * @property \Phalcon\Http\Request|\Phalcon\HTTP\RequestInterface $request
 * @property \Phalcon\Http\Response|\Phalcon\HTTP\ResponseInterface $response
 * @property \Phalcon\Http\Response\Cookies|\Phalcon\Http\Response\CookiesInterface $cookies
 * @property \Phalcon\Filter|\Phalcon\FilterInterface $filter
 * @property \Phalcon\Flash\Direct $flash
 * @property \Phalcon\Flash\Session $flashSession
 * @property \Phalcon\Session\Adapter\Files|\Phalcon\Session\Adapter|\Phalcon\Session\AdapterInterface $session
 * @property \Phalcon\Events\Manager $eventsManager
 * @property \Phalcon\Db\AdapterInterface $db
 * @property \Phalcon\Security $security
 * @property \Phalcon\Crypt $crypt
 * @property \Phalcon\Tag $tag
 * @property \Phalcon\Escaper|\Phalcon\EscaperInterface $escaper
 * @property \Phalcon\Annotations\Adapter\Memory|\Phalcon\Annotations\Adapter $annotations
 * @property \Phalcon\Mvc\Model\Manager|\Phalcon\Mvc\Model\ManagerInterface $modelsManager
 * @property \Phalcon\Mvc\Model\MetaData\Memory|\Phalcon\Mvc\Model\MetadataInterface $modelsMetadata
 * @property \Phalcon\Mvc\Model\Transaction\Manager $transactionManager
 * @property \Phalcon\Assets\Manager $assets
 * @property \Phalcon\Di|\Phalcon\DiInterface $di
 * @property \Phalcon\Session\Bag $persistent
 * @property \Phalcon\Mvc\View|\Phalcon\Mvc\ViewInterface $view
 *
 *
 */
abstract class Injectable implements InjectionAwareInterface, EventsAwareInterface
{
    /**
     * Dependency Injector
     *
     * @var null|Phalcon\DiInterface
     * @access protected
    */
    protected $_dependencyInjector;

    /**
     * Events Manager
     *
     * @var null|Phalcon\Events\ManagerInterface
     * @access protected
    */
    protected $_eventsManager;

    /**
     * Sets the dependency injector
     *
     * @param \Phalcon\DiInterface $dependencyInjector
     * @throws Exception
     */
    public function setDI($dependencyInjector)
    {
        if (!is_object($dependencyInjector)||
            $dependencyInjector instanceof DiInterface === false) {
            throw new Exception('Dependency Injector is invalid');
        }

        $this->_dependencyInjector = $dependencyInjector;
    }

    /**
     * Returns the internal dependency injector
     *
     * @return \Phalcon\DiInterface|null
     */
    public function getDI()
    {
        $dependencyInjector = $this->_dependencyInjector;
        if(!is_object($dependencyInjector)) {
            $dependencyInjector = Di::getDefault();
        }
        return $dependencyInjector;
    }

    /**
     * Sets the event manager
     *
     * @param \Phalcon\Events\ManagerInterface $eventsManager
     */
    public function setEventsManager($eventsManager)
    {
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
     * Magic method __get
     *
     * @param string $propertyName
     * @return mixed
     */
    public function __get($propertyName)
    {
        //let dependencyInjector = <DiInterface> this->_dependencyInjector;
        $dependencyInjector = $this->_dependencyInjector;
        if (!is_object($dependencyInjector)) {
            $dependencyInjector = Di::getDefault();
            if (!is_object($dependencyInjector)) {
                throw new Exception("A dependency injection object is required to access the application services");               
            }
        }

        /**
         * Fallback to the PHP userland if the cache is not available
         */
        if ($dependencyInjector->has($propertyName)) {
            $service = $dependencyInjector->getShared($propertyName);
            $this->{$propertyName} = $service;
            return $service;
        }

        if ($propertyName == 'di') {
            $this->{"di"} = $dependencyInjector;
            return $dependencyInjector;
        }

        /**
         * Accessing the persistent property will create a session bag on any class
         */
        if ($propertyName == "persistent") {
            //let persistent = <BagInterface> dependencyInjector->get("sessionBag", [get_class(this)]),
            $persistent = $dependencyInjector->get("sessionBag", [get_class($this)]);
            $this->{"persistent"} = $persistent;
            return $persistent;
        }

        /**
         * A notice is shown if the property is not defined and isn't a valid service
         */
        //A notice is shown if the property is not defined and isn't a valid service
        trigger_error("Access to undefined property " . $propertyName);
        return null;
    }
}
