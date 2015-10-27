<?php
/**
 * URL
 *
*/
namespace Phalcon\Mvc;

use \Phalcon\Mvc\Url\Exception;
use \Phalcon\Mvc\Url\UrlReplace;
use \Phalcon\Di\InjectionAwareInterface;
use \Phalcon\DiInterface;

/**
 * Phalcon\Mvc\Url
 *
 * This components aids in the generation of: URIs, URLs and Paths
 *
 *<code>
 *
 * //Generate a URL appending the URI to the base URI
 * echo $url->get('products/edit/1');
 *
 *
 *</code>
 *
 */
class Url implements UrlInterface, InjectionAwareInterface
{
    /**
     * Dependency Injector
     *
     * @var null|\Phalcon\DiInterface
     * @access protected
    */
    protected $_dependencyInjector;

    /**
     * Base URI
     *
     * @var string|null
     * @access protected
    */
    protected $_baseUri = null;

    /**
     * Static Base URI
     *
     * @var string|null
     * @access protected
    */
    protected $_staticBaseUri = null;

    /**
     * Base Path
     *
     * @var string|null
     * @access protected
    */
    protected $_basePath = null;

    /**
     * Sets the DependencyInjector container
     *
     * @param \Phalcon\DiInterface $dependencyInjector
     * @throws Exception
     */
    public function setDI($dependencyInjector)
    {
        if (!is_object($dependencyInjector) || $dependencyInjector instanceof DiInterface === false) {
            throw new Exception('The dependency injector must be an Object');
        }

        $this->_dependencyInjector = $dependencyInjector;
    }

    /**
     * Returns the DependencyInjector container
     *
     * @return \Phalcon\DiInterface|null
     */
    public function getDI()
    {
        return $this->_dependencyInjector;
    }

    /**
     * Sets a prefix for all the URIs to be generated
     *
     *<code>
     *  $url->setBaseUri('/invo/');
     *  $url->setBaseUri('/invo/index.php/');
     *</code>
     *
     * @param string $baseUri
     * @return \Phalcon\Mvc\Url
     * @throws Exception
     */
    public function setBaseUri($baseUri)
    {
        if (!is_string($baseUri)) {
            throw new Exception('Invalid parameter type.');
        }

        $this->_baseUri = $baseUri;

        if ($this->_staticBaseUri === null) {
            $this->_staticBaseUri = $baseUri;
        }

        return $this;
    }

    /**
     * Sets a prefix for all static URLs generated
     *
     *<code>
     *  $url->setStaticBaseUri('/invo/');
     *</code>
     *
     * @param string $staticBaseUri
     * @return \Phalcon\Mvc\Url
     * @throws Exception
     */
    public function setStaticBaseUri($staticBaseUri)
    {
        if (!is_string($staticBaseUri)) {
            throw new Exception('Invalid parameter type.');
        }

        $this->_staticBaseUri = $staticBaseUri;

        return $this;
    }

    /**
     * Returns the prefix for all the generated urls. By default /
     *
     * @return string
     */
    public function getBaseUri()
    {
        $baseUri = $this->_baseUri;

        if ($baseUri === null) {
            if (isset($_SERVER['PHP_SELF'])) {
                $uri = self::getUri($_SERVER['PHP_SELF']);
            } else {
                $uri = null;
            }

            if (!$uri) {
                $baseUri = '/';
            } else {
                $baseUri = '/' . $uri . '/';
            }

            $this->_baseUri = $baseUri;
        }

        return $baseUri;
    }

    /**
     * Returns the prefix for all the generated static urls. By default /
     *
     * @return string
     */
    public function getStaticBaseUri()
    {
        if (!is_null($this->_staticBaseUri)) {
            return $this->_staticBaseUri;
        }

        return $this->getBaseUri();
    }

    /**
     * Sets a base path for all the generated paths
     *
     *<code>
     *  $url->setBasePath('/var/www/htdocs/');
     *</code>
     *
     * @param string $basePath
     * @return \Phalcon\Mvc\Url
     * @throws Exception
     */
    public function setBasePath($basePath)
    {
        if (!is_string($basePath)) {
            throw new Exception('Invalid parameter type.');
        }

        $this->_basePath = $basePath;
    }

    /**
     * Returns the base path
     *
     * @return string|null
     */
    public function getBasePath()
    {
        return $this->_basePath;
    }

    /**
     * Get URI
     *
     * @param string $path
    */
    public static function getUri($path)
    {
        if (!is_string($path)) {
            return '';
        }

        $found = 0;
        $mark = 0;

        if (!empty($path)) {
            for ($i = strlen($path); $i > 0; $i--) {
                $ch = $path[$i - 1];
                if ($ch === '/' || $ch === '\\') {
                    $found++;

                    if ($found === 1) {
                        $mark = $i - 1;
                    } else {
                        return substr($path, 1, $mark - $i) . chr(0);
                    }
                }
            }
        }

        return '';
    }

    /**
     * Generates a URL
     *
     *<code>
     *
     * //Generate a URL appending the URI to the base URI
     * echo $url->get('products/edit/1');
     *
     *</code>
     *
     * @param string|array|null $uri
     * @param array|object|null $args Optional arguments to be appended to the query string
     * @param boolean $local
     * @return string
     * @throws Exception
     */
    public function get($uri = null, $args = null, $local = null, $baseUri = null)
    {

        if ($local == null) {
            if (is_string($uri) && (strpos($uri, '//') !== false || strpos($uri, ':') !== false)) {
                if (preg_match("#^(//)|([a-z0-9]+://)|([a-z0-9]+:)#i", $uri)) {
                    $local = false;
                } else {
                    $local = true;
                }
            } else {
                $local = true;
            }
        }

        if (!is_string($baseUri)) {
            $baseUri = $this->getBaseUri();
        }

        if (is_array($uri)) {
            throw new Exception('Invalid parameter type.');
        }

        if ($local) {
            $strUri = (string) $uri;
            if ($baseUri == '/' && strlen($strUri) > 2 && $strUri[0] == '/' && $strUri[1] != '/') {
                $uri = $baseUri . substr($strUri, 1);
            } else {
                if ($baseUri == '/' && strlen($strUri) == 1 && $strUri[0] == '/') {
                    $uri = $baseUri;
                } else {
                    $uri = $baseUri . $strUri; 
                }
            }
        }

        if ($args) {
            $querySrting = http_build_query($args);
            if (is_string($querySrting) && !empty($querySrting)) {
                if (strpos($return, '?') !== false) {
                    $return .= '&' . $query;
                } else {
                    $return .= '?' . $query;
                }
            }
        }

        return $uri;
    }

    /**
     * Generates a URL for a static resource
     *
     * <code>
     * // Generate a URL for a static resource
     * echo $url->getStatic("img/logo.png");
     *
     * </code>
     *
     * @param string|null $uri
     * @return string
     * @throws Exception
     */
    public function getStatic($uri = null)
    {
        return $this->get($uri, null, null, $this->getStaticBaseUri());
    }

    /**
     * Generates a local path
     *
     * @param string|null $path
     * @return string
     */
    public function path($path = null)
    {
        //@note added NULL fallback
        if (is_null($path)) {
            $path = '';
        } elseif (!is_string($path)) {
            throw new Exception('Invalid parameter type.');
        }

        return $this->_basePath . $path;
    }
}
