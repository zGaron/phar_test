<?php
/**
 * Cookies
 *
*/

namespace Phalcon\Http\Response;

//use \Phalcon\Http\Cookie;
use \Phalcon\Http\Cookie\Exception;
use \Phalcon\Http\Response\CookiesInterface;
use \Phalcon\Http\ResponseInterface;
use \Phalcon\Di\InjectionAwareInterface;
use \Phalcon\DiInterface;

/**
 * Phalcon\Http\Response\Cookies
 *
 * This class is a bag to manage the cookies
 * A cookies bag is automatically registered as part of the 'response' service in the DI
 *
 */
class Cookies implements CookiesInterface, InjectionAwareInterface
{
    
    /**
     * Dependency Injector
     *
     * @var null|\Phalcon\DiInterface
     * @access protected
    */
    protected $_dependencyInjector;

    /**
     * Registered
     *
     * @var boolean
     * @access protected
    */
    protected $_registered = false;

    /**
     * Use Encryption
     *
     * @var boolean
     * @access protected
    */
    protected $_useEncryption = true;

    /**
     * Cookies
     *
     * @var null|array
     * @access protected
    */
    protected $_cookies;

    /**
     * Sets the dependency injector
     *
     * @param \Phalcon\DiInterface $dependencyInjector
     * @throws Exception
     */
    public function setDI($dependencyInjector)
    {
        if (!is_object($dependencyInjector) ||
            $dependencyInjector instanceof DiInterface === false) {
            throw new Exception('Invalid parameter type.');
        }

        $this->_dependencyInjector = $dependencyInjector;
    }

    /**
     * Returns the internal dependency injector
     *
     * @return \Phalcon\DiInterface
     */
    public function getDI()
    {
        return $this->_dependencyInjector;
    }

    /**
     * Set if cookies in the bag must be automatically encrypted/decrypted
     *
     * @param boolean $useEncryption
     * @return \Phalcon\Http\Response\CookiesInterface
     * @throws Exception
     */
    public function useEncryption($useEncryption)
    {
        if (!is_bool($useEncryption)) {
            throw new Exception('Invalid parameter type.');
        }

        $this->_useEncryption = $useEncryption;
        return $this;
    }

    /**
     * Returns if the bag is automatically encrypting/decrypting cookies
     *
     * @return boolean
     */
    public function isUsingEncryption()
    {
        return $this->_useEncryption;
    }

    /**
     * Sets a cookie to be sent at the end of the request
     * This method overrides any cookie set before with the same name
     *
     * @param string $name
     * @param mixed $value
     * @param int|null $expire
     * @param string|null $path
     * @param boolean|null $secure
     * @param string|null $domain
     * @param boolean|null $httpOnly
     * @return \Phalcon\Http\Response\Cookies
     * @throws Exception
     */
    public function set($name, $value = null, $expire = 0, $path = '/',
        $secure = null, $domain = null, $httpOnly = null)
    {
        /* Type check */
        if (!is_string($name)) {
            throw new Exception('The cookie name must be a string.');
        }

        if (!is_int($expire)) {
            throw new Exception('Invalid parameter type.');
        }

        if (is_string($path) === false) {
            throw new Exception('Invalid parameter type.');
        }

        if (!is_null($secure) && !is_bool($secure)) {
            throw new Exception('Invalid parameter type.');
        }

        if (!is_null($domain) && !is_string($domain)) {
            throw new Exception('Invalid parameter type.');
        }

        if (!is_null($httpOnly) && !is_bool($httpOnly)) {
            throw new Exception('Invalid parameter type.');
        }

        

        $encryption = $this->_useEncryption;

        /**
         * Check if the cookie needs to be updated or
         */
        if (!isset($this->_cookies[$name])) {
            $cookie = $this->_dependencyInjector->get("Phalcon\\Http\\Cookie",
            [$name, $value, $expire, $path, $secure, $domain, $httpOnly]);

            /**
             * Pass the DI to created cookies
             */
            $cookie->setDi($this->_dependencyInjector);

            /**
             * Enable encryption in the cookie
             */
            if ($encryption) {
                $cookie->useEncryption($encryption);
            }

            $this->_cookies[$name] = $cookie;

        } else {

            $cookie = $this->_cookies[$name];

            /**
             * Override any settings in the cookie
             */
            $cookie->setValue($value);
            $cookie->setExpiration($expire);
            $cookie->setPath($path);
            $cookie->setSecure($secure);
            $cookie->setDomain($domain);
            $cookie->setHttpOnly($httpOnly);
        }

        /**
         * Register the cookies bag in the response
         */
        if ($this->_registered === false) {

            $dependencyInjector = $this->_dependencyInjector;
            if (!is_object($dependencyInjector)) {
                 throw new Exception("A dependency injection object is required to access the 'response' service");
            }

            $response = $dependencyInjector->getShared('response');

            /**
             * Pass the cookies bag to the response so it can send the headers at the of the request
             */
            $response->setCookies($this);
        }

        return $this;

    }

    /**
     * Gets a cookie from the bag
     *
     * @param string $name
     * @return \Phalcon\Http\CookieInterface
     * @throws Exception
     */
    public function get($name)
    {
        if (is_string($name) === false) {
            throw new Exception('The cookie name must be string');
        }

        if (isset($this->_cookies[$name])) {
            return $this->_cookies[$name];
        }

        /**
         * Create the cookie if the it does not exist
         */
        $cookie = $this->_dependencyInjector->get("Phalcon\\Http\\Cookie", [$name]);
        $dependencyInjector = $this->_dependencyInjector;

        if (is_object($dependencyInjector)) {

            /**
             * Pass the DI to created cookies
             */
            $cookie->setDi($dependencyInjector);

            $encryption = $this->_useEncryption;

            /**
             * Enable encryption in the cookie
             */
            if ($encryption) {
                $cookie->useEncryption($encryption);
            }
        }

        $this->_cookies[$name] = $cookie;
        return $cookie;
    }

    /**
     * Check if a cookie is defined in the bag or exists in the $_COOKIE superglobal
     *
     * @param string $name
     * @return boolean
     * @throws Exception
     */
    public function has($name)
    {
        if (!is_string($name)) {
            throw new Exception('Invalid parameter type.');
        }

        /**
         * Check the internal bag
         */
        if (isset($this->_cookies[$name]) === true) {
            return true;
        }

        /**
         * Check the superglobal
         */
        if(isset($_COOKIE[$name])) {
            return true;
        }
        
        return false;
    }

    /**
     * Deletes a cookie by its name
     * This method does not removes cookies from the $_COOKIE superglobal
     *
     * @param string $name
     * @return boolean
     * @throws Exception
     */
    public function delete($name)
    {
        if (!is_string($name)) {
            throw new Exception('Invalid parameter type.');
        }

        /**
         * Check the internal bag
         */
        if (isset($this->_cookies[$name])) {
            $this->_cookies[$name]->delete();
            return true;
        }

        return false;
    }

    /**
     * Sends the cookies to the client
     * Cookies aren't sent if headers are sent in the current request
     *
     * @return boolean
     */
    public function send()
    {
        if (!headers_sent()) {
            foreach ($this->_cookies as $cookie) {
                $cookie->send();
            }

            return true;
        }

        return false;
    }

    /**
     * Reset set cookies
     *
     * @return \Phalcon\Http\Response\CookiesInterface
     */
    public function reset()
    {
        $this->_cookies = array();

        return $this;
    }
}
