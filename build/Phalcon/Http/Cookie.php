<?php
/**
 * Cookies
 *
*/
namespace Phalcon\Http;

use \Phalcon\Di\InjectionAwareInterface;
use \Phalcon\DiInterface;
use \Phalcon\CryptInterface;
use \Phalcon\FilterInterface;
use \Phalcon\Http\Cookie\Exception;
use \Phalcon\Session\AdapterInterface as SessionInterface;

/**
 * Phalcon\Http\Cookie
 *
 * Provide OO wrappers to manage a HTTP cookie
 *
 */
class Cookie implements CookieInterface, InjectionAwareInterface
{

    /**
     * Readed
     *
     * @var boolean
     * @access protected
    */
    protected $_readed = false;

    /**
     * Restored
     *
     * @var boolean
     * @access protected
    */
    protected $_restored = false;

    /**
     * Use Encryption?
     *
     * @var boolean
     * @access protected
    */
    protected $_useEncryption = false;

    /**
     * Dependency Injector
     *
     * @var null|\Phalcon\DiInterface
     * @access protected
    */
    protected $_dependencyInjector;

    /**
     * Filter
     *
     * @var null|\Phalcon\FilterInterface
     * @access protected
    */
    protected $_filter;

    /**
     * Name
     *
     * @var null|string
     * @access protected
    */
    protected $_name;

    /**
     * Value
     *
     * @var null|string
     * @access protected
    */
    protected $_value;

    /**
     * Expire
     *
     * @var null|int
     * @access protected
    */
    protected $_expire;

    /**
     * Path
     *
     * @var string
     * @access protected
    */
    protected $_path = '/';

    /**
     * Domain
     *
     * @var null|string
     * @access protected
    */
    protected $_domain;

    /**
     * Secure
     *
     * @var null|boolean
     * @access protected
    */
    protected $_secure;

    /**
     * HTTP Only?
     *
     * @var boolean
     * @access protected
    */
    protected $_httpOnly = true;

    /**
     * \Phalcon\Http\Cookie constructor
     *
     * @param string $name
     * @param mixed $value
     * @param int $expire
     * @param string $path
     * @param boolean $secure
     * @param string $domain
     * @param boolean $httpOnly
     * @throws Exception
     */
    public function __construct($name, $value = null, $expire = 0, $path = '/', $secure = null, $domain = null, $httpOnly = null)
    {
        /* Type check */
        if (!is_string($name)) {
            throw new Exception('The cookie name must be string');
        }

        if (!is_int($expire)) {
            throw new Exception('Invalid parameter type.');
        }

        if (!is_string($path)) {
            throw new Exception('Invalid parameter type.');
        }

        $this->_name = $name;

        if ($value !== null) {
            $this->_value = $value;
        }

        $this->_expire = $expire;

        if ($path !== null) {
            $this->_path = $path;
        }

        if ($secure !== null) {
            $this->_secure = $secure;
        }

        if ($domain !== null) {
            $this->_domain = $domain;
        }

        if ($httpOnly !== null) {
            $this->_httpOnly = $httpOnly;
        }
    }

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
     * @return \Phalcon\DiInterface|null
     */
    public function getDI()
    {
        return $this->_dependencyInjector;
    }

    /**
     * Sets the cookie's value
     *
     * @param string $value
     * @return \Phalcon\Http\CookieInterface
     * @throws Exception
     */
    public function setValue($value)
    {
        if (!is_string($value)) {
            throw new Exception('Invalid parameter type.');
        }

        $this->_value = $value;
        $this->_readed = true;

        return $this;
    }

    /**
     * Returns the cookie's value
     *
     * @param string|array|null $filters
     * @param string|null $defaultValue
     * @return mixed
     * @throws Exception
     */
    public function getValue($filters = null, $defaultValue = null)
    {

        if (!$this->_restored) {
            $this->restore();
        }

        if ($this->_readed === false) {

            if (isset($_COOKIE[$this->_name])) {
                $value = $_COOKIE[$this->_name];

                if ($this->_useEncryption) {

                    $dependencyInjector = $this->_dependencyInjector;
                    if (!is_object($dependencyInjector)) {
                        throw new Exception("A dependency injection object is required to access the 'filter' service");
                    }

                    $crypt = $dependencyInjector->getShared('crypt');

                    /**
                     * Decrypt the value also decoding it with base64
                     */
                    $decryptedValue = $crypt->decryptBase64($value);
                } else {
                    $decryptedValue = $value;
                }

                /**
                 * Update the decrypted value
                 */
                $this->_value = $decryptedValue;

                if ($filters !== null) {
                    $filter = $this->_filter;
                    if (!is_object($filter)) {

                        if ($dependencyInjector === null) {
                            $dependencyInjector = $this->_dependencyInjector;
                            if (!is_object($dependencyInjector)) {
                                throw new Exception("A dependency injection object is required to access the 'filter' service");
                            }
                        }

                        $filter = $dependencyInjector->getShared('filter');
                        $this->_filter = $filter;
                    }

                    return $filter->sanitize($decryptedValue, $filters);
                }

                /**
                 * Return the value without filtering
                 */
                return $decryptedValue;
            }

            return $defaultValue;
        }

        return $this->_value;
    }

    /**
     * Sends the cookie to the HTTP client
     * Stores the cookie definition in session
     *
     * @return \Phalcon\Http\CookieInterface
     * @throws Exception
     */
    public function send()
    {

        $name = $this->_name;
        $value = $this->_value;
        $expire = $this->_expire;
        $domain = $this->_domain;
        $path = $this->_path;
        $secure = $this->_secure;
        $httpOnly = $this->_httpOnly;

        $dependencyInjector = $this->_dependencyInjector;

        if (!is_object($dependencyInjector)) {
            throw new Exception("A dependency injection object is required to access the 'session' service");
        }

        $definition = [];

        if ($expire != 0) {
            $definition['expire'] = $expire;
        }

        if (!empty($path)) {
            $definition['[path'] = $path;
        }

        if (!empty($domain)) {
            $definition['domain'] = $domain;
        }

        if (!empty($secure)) {
            $definition['secure'] = $secure;
        }

        if (!empty($httpOnly)) {
            $definition['httpOnly'] = $httpOnly;
        }

        /**
         * The definition is stored in session
         */
        if (count($definition)) {
            $session = $dependencyInjector->getShared('session');
            if ($session->isStarted()) {
                $session->set('_PHCOOKIE_' . $name, $definition);
            }
        }

        if ($this->_useEncryption) {

            if (!empty($value)) {

                $crypt = $dependencyInjector->getShared('crypt');

                /**
                 * Encrypt the value also coding it with base64
                 */
                $encryptValue = $crypt->encryptBase64((string) $value);
            } else {
                $encryptValue = $value;
            }
        
        } else {
            $encryptValue = $value;
        }

        /**
         * Sets the cookie using the standard 'setcookie' function
         */
        setcookie($name, $encryptValue, $expire, $path, $domain, $secure, $httpOnly);

        return $this;
    }

    /**
     * Reads the cookie-related info from the SESSION to restore the cookie as it was set
     * This method is automatically called internally so normally you don't need to call it
     *
     * @return \Phalcon\Http\CookieInterface
     */
    public function restore()
    {
        if (!$this->_restored) {

           $dependencyInjector = $this->_dependencyInjector;
            if (is_object($dependencyInjector)) {
                
                $session = $dependencyInjector->getShared('session');

                if ($session->isStarted()) {
                    $definition = $session->get('_PHCOOKIE_'  .$this->_name);
                    if (is_array($definition)) {

                        if (isset($definition['expire'])) {
                            $this->_expire = $definition['expire'];
                        }

                        if (isset($definition['domain'])) {
                            $this->_domain = $definition['domain'];
                        }

                        if (isset($definition['path'])) {
                            $this->_path = $definition['path'];
                        }

                        if (isset($definition['secure'])) {
                            $this->_secure = $definition['secure'];
                        }

                        if (isset($definition['httpOnly'])) {
                            $this->_httpOnly = $definition['httpOnly'];
                        }
                    }
                }
            }

            $this->_restored = true;
        }

        return $this;
    }

    /**
     * Deletes the cookie by setting an expire time in the past
     *
     * @throws Exception
     */
    public function delete()
    {
        
        $name     = $this->_name;
        $domain   = $this->_domain;
        $path     = $this->_path;
        $secure   = $this->_secure;
        $httpOnly = $this->_httpOnly;


        $dependencyInjector = $this->_dependencyInjector;
        if (is_object($dependencyInjector)) {
            $session = $dependencyInjector->getShared('session');
            if ($session->isStarted()) {
                $session->remove('_PHCOOKIE_' . $this->_name);
            }            
        }

        $this->_value = null;
        setcookie($name, null, time() - 691200, $path, $domain, $secure, $httpOnly);       
    }

    /**
     * Sets if the cookie must be encrypted/decrypted automatically
     *
     * @param boolean $useEncryption
     * @return \Phalcon\Http\CookieInterface
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
     * Check if the cookie is using implicit encryption
     *
     * @return boolean
     */
    public function isUsingEncryption()
    {
        return $this->_useEncryption;
    }

    /**
     * Sets the cookie's expiration time
     *
     * @param int $expire
     * @return \Phalcon\Http\Cookie
     * @throws Exception
     */
    public function setExpiration($expire)
    {
        if (!is_int($expire)) {
            throw new Exception('Invalid parameter type.');
        }

        if (!$this->_restored) {
            $this->restore();
        }

        $this->_expire = $expire;
        return $this;
    }

    /**
     * Returns the current expiration time
     *
     * @return string
     */
    public function getExpiration()
    {
        if (!$this->_restored) {
            $this->restore();
        }

        return (string) $this->_expire;
    }

    /**
     * Sets the cookie's expiration time
     *
     * @param string $path
     * @return \Phalcon\Http\CookieInterface
     * @throws Exception
     */
    public function setPath($path)
    {
        if (!is_string($path)) {
            throw new Exception('Invalid parameter type.');
        }

        if (!$this->_restored) {
            $this->restore();
        }

        $this->_path = $path;
        return $this;
    }

    /**
     * Returns the current cookie's path
     *
     * @return string
     */
    public function getPath()
    {
        if (!$this->_restored) {
            $this->restore();
        }

        return (string) $this->_path;
    }

    /**
     * Returns the current cookie's name
     *
     * @return string
     */
    public function getName()
    {
        return $this->_name;
    }

    /**
     * Sets the domain that the cookie is available to
     *
     * @param string $domain
     * @return \Phalcon\Http\CookieInterface
     * @throws Exception
     */
    public function setDomain($domain)
    {
        if (!is_string($domain)) {
            throw new Exception('Invalid parameter type.');
        }

        if (!$this->_restored) {
            $this->restore();
        }

        $this->_domain = $domain;
        return $this;
    }

    /**
     * Returns the domain that the cookie is available to
     *
     * @return string
     */
    public function getDomain()
    {
        if (!$this->_restored) {
            $this->restore();
        }

        return (string) $this->_domain;
    }

    /**
     * Sets if the cookie must only be sent when the connection is secure (HTTPS)
     *
     * @param boolean $secure
     * @return \Phalcon\Http\CookieInterface
     * @throws Exception
     */
    public function setSecure($secure)
    {
        if (!is_bool($secure)) {
            throw new Exception('Invalid parameter type.');
        }

        if (!$this->_restored) {
            $this->restore();
        }

        $this->_secure = $secure;
    }

    /**
     * Returns whether the cookie must only be sent when the connection is secure (HTTPS)
     *
     * @return boolean
     */
    public function getSecure()
    {
        if (!$this->_restored) {
            $this->restore();
        }

        return $this->_secure;
    }

    /**
     * Sets if the cookie is accessible only through the HTTP protocol
     *
     * @param boolean $httpOnly
     * @return \Phalcon\Http\CookieInterface
     * @throws Exception
     */
    public function setHttpOnly($httpOnly)
    {
        if (!is_bool($httpOnly)) {
            throw new Exception('Invalid parameter type.');
        }

        if (!$this->_restored) {
            $this->restore();
        }

        $this->_httpOnly = $httpOnly;
        return $this;
    }

    /**
     * Returns if the cookie is accessible only through the HTTP protocol
     *
     * @return boolean
     */
    public function getHttpOnly()
    {
        if (!$this->_restored) {
            $this->restore();
        }

        return $this->_httpOnly;
    }

    /**
     * Magic __toString method converts the cookie's value to string
     *
     * @return mixed
     */
    public function __toString()
    {
        return (string) $this->getValue();
    }
}
