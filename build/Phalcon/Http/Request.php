<?php
/**
 * Request
 *
*/
namespace Phalcon\Http;

use \Phalcon\Http\RequestInterface;
use \Phalcon\http\Request\Exception;
use \Phalcon\Http\Request\File;
use \Phalcon\Di\InjectionAwareInterface;
use \Phalcon\DiInterface;
use \Phalcon\Text;

/**
 * Phalcon\Http\Request
 *
 * <p>Encapsulates request information for easy and secure access from application controllers.</p>
 *
 * <p>The request object is a simple value object that is passed between the dispatcher and controller classes.
 * It packages the HTTP request environment.</p>
 *
 *<code>
 *  $request = new Phalcon\Http\Request();
 *  if ($request->isPost()) {
 *      if ($request->isAjax()) {
 *          echo 'Request was made using POST and AJAX';
 *      }
 *  }
 *</code>
 *
 */
class Request implements RequestInterface, InjectionAwareInterface
{
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
     * @var null
     * @access protected
    */
    protected $_filter;

    /**
     * Raw Body
     *
     * @var null
     * @access protected
    */
    protected $_rawBody;

    /**
     * Put Cache
     *
     * @var null
     * @access protected
    */
    protected $_putCache;

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
     * Gets a variable from the $_REQUEST superglobal applying filters if needed.
     * If no parameters are given the $_REQUEST superglobal is returned
     *
     *<code>
     *  //Returns value from $_REQUEST["user_email"] without sanitizing
     *  $userEmail = $request->get("user_email");
     *
     *  //Returns value from $_REQUEST["user_email"] with sanitizing
     *  $userEmail = $request->get("user_email", "email");
     *</code>
     *
     * @param string|null $name
     * @param string|array|null $filters
     * @param mixed $defaultValue
     * @param boolean $notAllowEmpty
     * @param boolean $noRecursive
     * @return mixed
     * @throws Exception
     */
    public function get($name = null, $filters = null, $defaultValue = null, $notAllowEmpty = false, $noRecursive = false)
    {
        return $this->getHelper($_REQUEST, $name, $filters, $defaultValue, $notAllowEmpty, $noRecursive);
    }

    /**
     * Gets a variable from the $_POST superglobal applying filters if needed
     * If no parameters are given the $_POST superglobal is returned
     *
     *<code>
     *  //Returns value from $_POST["user_email"] without sanitizing
     *  $userEmail = $request->getPost("user_email");
     *
     *  //Returns value from $_POST["user_email"] with sanitizing
     *  $userEmail = $request->getPost("user_email", "email");
     *</code>
     *
     * @param string|null $name
     * @param string|array|null $filters
     * @param mixed $defaultValue
     * @param boolean $notAllowEmpty
     * @param boolean $noRecursive
     * @return mixed
     * @throws Exception
     */
    public function getPost($name = null, $filters = null, $defaultValue = null, $notAllowEmpty = false, $noRecursive = false)
    {
        return $this->getHelper($_POST, $name, $filters, $defaultValue, $notAllowEmpty, $noRecursive);
    }

    /**
     * Gets a variable from put request
     * 
     *<code>
     *  //Returns value from $_PUT["user_email"] without sanitizing
     *  $userEmail = $request->getPut("user_email");
     *
     *  //Returns value from $_PUT["user_email"] with sanitizing
     *  $userEmail = $request->getPut("user_email", "email");
     *</code>
     *
     * @param string|null $name
     * @param string|array|null $filters
     * @param mixed $defaultValue
     * @param boolean $notAllowEmpty
     * @param boolean $noRecursive
     * @return mixed
     * @throws Exception
     */
    public function getPut($name = null, $filters = null, $defaultValue = null, $notAllowEmpty = false, $noRecursive = false)
    {
        $put = $this->_putCache;

        if (!is_array($put)) {
            $put = [];
            parse_str($this->getRawBody(), $put);

            $this->_putCache = $put;
        }

        return $this->getHelper($put, $name, $filters, $defaultValue, $notAllowEmpty, $noRecursive);
    }

    /**
     * Gets variable from $_GET superglobal applying filters if needed
     * If no parameters are given the $_GET superglobal is returned
     *
     *<code>
     *  //Returns value from $_GET["id"] without sanitizing
     *  $id = $request->getQuery("id");
     *
     *  //Returns value from $_GET["id"] with sanitizing
     *  $id = $request->getQuery("id", "int");
     *
     *  //Returns value from $_GET["id"] with a default value
     *  $id = $request->getQuery("id", null, 150);
     *</code>
     *
     * @param string|null $name
     * @param string|array|null $filters
     * @param mixed $defaultValue
     * @param boolean $notAllowEmpty
     * @param boolean $noRecursive
     * @return mixed
     * @throws Exception
     */
    public function getQuery($name = null, $filters = null, $defaultValue = null, $notAllowEmpty = false, $noRecursive = false)
    {
        return $this->getHelper($_GET, $name, $filters, $defaultValue, $notAllowEmpty, $noRecursive);
    }

    /**
     * Helper to get data from superglobals, applying filters if needed.
     * If no parameters are given the superglobal is returned.
     *
     * @param array $source
     * @param string! $name
     * @param mixed $filters
     * @param mixed $defaultValue
     * @param boolean $notAllowEmpty
     * @param boolsan $noRecursive
     */
    protected final function getHelper($source, $name = null, $filters = null, $defaultValue = null, $notAllowEmpty = false, $noRecursive = false)
    {
        if (!is_string($name) && !is_null($name)) {
            throw new Exception('Invalid parameter type.');
        }

        if (!is_null($filters) && !is_string($filters) && !is_array($filters)) {
            throw new Exception('Invalid parameter type.');
        }

        if ($name === null) {
            return $source;
        }

        if (!isset($source[$name])) {
            return $defaultValue;
        } else {
            $value = $source[$name];
        }

        if ($filters !== null) {
            $filter = $this->_filter;
            if (!is_object($filter)) {
                $dependencyInjector = $this->_dependencyInjector;
                if (!is_object($dependencyInjector)) {
                    throw new Exception("A dependency injection object is required to access the 'filter' service");
                }
                $filter = $dependencyInjector->getShared("filter");
                $his->_filter = $filter;
            }

            $value = $filter->sanitize($value, $filter, $noRecursive);
        }

        if (empty($value) && $notAllowEmpty === true) {
            return $defaultValue;
        }

        return $value;
    }

    /**
     * Gets variable from $_SERVER superglobal
     *
     * @param string! $name
     * @return mixed
     * @throws Exception
     */
    public function getServer($name)
    {
        if (!is_string($name)) {
            throw new Exception('Invalid parameter type.');
        }

        if (isset($_SERVER[$name])) {
            return $_SERVER[$name];
        }

        return null;
    }

    /**
     * Checks whether $_REQUEST superglobal has certain index
     *
     * @param string! $name
     * @return boolean
     * @throws Exception
     */
    public function has($name)
    {
        if (!is_string($name)) {
            throw new Exception('Invalid parameter type.');
        }

        return isset($_REQUEST[$name]);
    }

    /**
     * Checks whether $_POST superglobal has certain index
     *
     * @param string! $name
     * @return boolean
     * @throws Exception
     */
    public function hasPost($name)
    {
        if (!is_string($name)) {
            throw new Exception('Invalid parameter type.');
        }

        return isset($_POST[$name]);
    }

    /**
     * Checks whether the PUT data has certain index
     *
     * @param string! $name
     * @return boolean
     * @throws Exception
     */
    public function hasPut($name)
    {
        if (!is_string($name)) {
            throw new Exception('Invalid parameter type.');
        }

        $put = $this->getPut();

        return isset($put[$name]);
    }

    /**
     * Checks whether $_GET superglobal has certain index
     *
     * @param string! $name
     * @return boolean
     * @throws Exception
     */
    public function hasQuery($name)
    {
        if (!is_string($name)) {
            throw new Exception('Invalid parameter type.');
        }

        return isset($_GET[$name]);
    }

    /**
     * Checks whether $_SERVER superglobal has certain index
     *
     * @param string! $name
     * @return mixed
     * @throws Exception
     */
    public function hasServer($name)
    {
        if (!is_string($name)) {
            throw new Exception('Invalid parameter type.');
        }

        return isset($_SERVER[$name]);
    }

    /**
     * Gets HTTP header from request data
     *
     * @param string! $header
     * @return string
     * @throws Exception
     */
    public function getHeader($header)
    {
        if (!is_string($header)) {
            throw new Exception('Invalid parameter type.');
        }

        $name = strtoupper(strtr($header, "-", "_"));

        if (isset($_SERVER[$name])) {
            return $_SERVER[$name];
        }

        if (isset($_SERVER["HTTP_" . $name])) {
            return $_SERVER["HTTP_" . $name];
        }

        return '';
    }

    /**
     * Gets HTTP schema (http/https)
     *
     * @return string
     */
    public function getScheme()
    {
        $https = $this->getServer('HTTPS');
        if ($https) {
            if ($https == 'off') {
                $scheme = 'http';
            } else {
                $scheme = 'https';
            }
        } else {
            $scheme = 'http';
        }

        return $scheme;
    }

    /**
     * Checks whether request has been made using ajax. Checks if $_SERVER['HTTP_X_REQUESTED_WITH']=='XMLHttpRequest'
     *
     * @return boolean
     */
    public function isAjax()
    {
        return isset($_SERVER["HTTP_X_REQUESTED_WITH"]) && $_SERVER["HTTP_X_REQUESTED_WITH"] === "XMLHttpRequest";
    }

    /**
     * Checks whether request has been made using SOAP
     *
     * @return boolean
     */
    public function isSoapRequested()
    {
        if (isset($_SERVER["HTTP_SOAPACTION"])) {
            return true;
        } else {
            $contentType = $this->getContentType();
            if (!empty($contentType)) {
                if (strpos($contentType, 'application/soap+xml') !== false) {
                    return true;
                }
            }           
        }

        return false;
    }

    /**
     * Checks whether request has been made using any secure layer
     *
     * @return boolean
     */
    public function isSecureRequest()
    {
        return $this->getScheme() === 'https';
    }

    /**
     * Gets HTTP raw request body
     *
     * @return string
     */
    public function getRawBody()
    {
        $rawBody = $this->_rawBody;
        if (empty($rawBody)) {

            $content = file_get_contents("php://input");

            /**
             * We need store the read raw body because it can't be read again
             */
            $this->_rawBody = $content;
            return $content;
        }
        return $rawBody;
    }

    /**
     * Gets decoded JSON HTTP raw request body
     *
     * @param boolean $associative
     * @return \stdClass | array | boolean
     */
    public function getJsonRawBody($associative = false)
    {
        $rawBody = $this->getRawBody();
        if (!is_string($rawBody)) {
            return false;
        }

        return json_decode($rawBody, $associative);
    }

    /**
     * Gets active server address IP
     *
     * @return string
     */
    public function getServerAddress()
    {
        if (isset($_SERVER['SERVER_ADDR'])) {
            return $_SERVER['SERVER_ADDR'];
        }

        return gethostbyname('localhost');
    }

    /**
     * Gets active server name
     *
     * @return string
     */
    public function getServerName()
    {
        if (isset($_SERVER['SERVER_NAME'])) {
            return $_SERVER['SERVER_NAME'];
        }

        return 'localhost';
    }

    /**
     * Gets information about schema, host and port used by the request
     *
     * @return string
     */
    public function getHttpHost()
    {
        /**
         * Get the server name from _SERVER['HTTP_HOST']
         */
        $httpHost = $this->getServer('HTTP_HOST');
        if ($httpHost) {
            return $httpHost;
        }

        /**
         * Get current scheme
         */
        $scheme = $this->getScheme();

        /**
         * Get the server name from _SERVER['SERVER_NAME']
         */
        $name = $this->getServer('SERVER_NAME');

        /**
         * Get the server port from _SERVER['SERVER_PORT']
         */
        $port = $this->getServer('SERVER_PORT');

        /**
         * If is standard http we return the server name only
         */
        if ($scheme == 'http' && $port == 80) {
            return $name;
        }

        /**
         * If is standard secure http we return the server name only
         */
        if ($schem == 'https' && $port == "443") {
            return $name;
        }

        return $name . ':' . $port;

    }

    /**
     * Gets HTTP URI which request has been made
     *
     * @return string
     */
    public final function getURI()
    {
        if (isset($_SERVER["REQUEST_URI"])) {
            return $_SERVER["REQUEST_URI"];
        }

        return '';
    }

    /**
     * Gets most possible client IPv4 Address. This method search in $_SERVER['REMOTE_ADDR'] and optionally in $_SERVER['HTTP_X_FORWARDED_FOR']
     *
     * @param boolean|null $trustForwardedHeader
     * @return string|boolean
     * @throws Exception
     */
    public function getClientAddress($trustForwardedHeader = false)
    {
        $address = null;
        /**
         * Proxies uses this IP
         */
        if ($trustForwardedHeader) {
            if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                $address = $_SERVER['HTTP_X_FORWARDED_FOR'];
            }
            if ($address === null) {
                if (isset($_SERVER["HTTP_CLIENT_IP"])) {
                    $address = $_SERVER["HTTP_CLIENT_IP"];
                }
            }
        }

        if ($address === null) {
            if (isset($_SERVER["REMOTE_ADDR"])) {
                $address = $_SERVER["REMOTE_ADDR"];
            }
        }

        if (is_string($address)) {
            if (strpos($address, ',') !== false) {
                /**
                 * The client address has multiples parts, only return the first part
                 */
                return explode(",", $address)[0];

            }
            return $address;
        }

        return false;
    }

    /**
     * Gets HTTP method which request has been made
     *
     * @return string
     */
    public function getMethod()
    {
        if (isset($_SERVER['REQUEST_METHOD'])) {
            return $_SERVER['REQUEST_METHOD'];
        }

        return '';
    }

    /**
     * Gets HTTP user agent used to made the request
     *
     * @return string
     */
    public function getUserAgent()
    {
        if (isset($_SERVER['HTTP_USER_AGENT'])) {
            return $_SERVER['HTTP_USER_AGENT'];
        } else {
            return '';
        }
    }

    /**
     * Checks if a method is a valid HTTP method
     *
     * @param string $method
     * @return boolean
     */
    public function isVslidHttpMethod($method)
    {
        if (!is_string($method)) {
            throw new Exception('Invalid parameter type.');
        }

        $lowerMethod = strtoupper($method);

        switch ($lowerMethod) {
            
            case "GET":
            case "POST":
            case "PUT":
            case "DELETE":
            case "HEAD":
            case "OPTIONS":
            case "PATCH":
                return true;
        }

        return false;
    }

    /**
     * Check if HTTP method match any of the passed methods
     * When strict is true it checks if validated methods are real HTTP methods
     *
     * @param string|array $methods
     * @param boolean $strict
     * @return boolean
     */
    public function isMethod($methods, $strict = false)
    {
        $httpMethod = $this->getMethod();

        if (is_string($methods)) {
            if ($strict && !$this->isVslidHttpMethod($methods)) {
                throw new Exception("Invalid HTTP method: " . $methods);
            }
            return $methods == $httpMethod;
        }

        if (is_array($methods)) {
            foreach ($methods as $method) {
                if ($strict && !$this->isVslidHttpMethod($method)) {
                    if (is_string($method)) {
                        throw new Exception("Invalid HTTP method: " . $method);
                    } else {
                        throw new Exception("Invalid HTTP method: non-string");
                    }
                }
                if ($method == $httpMethod) {
                    return true;
                }
            }
            return false;
        }

        if ($strict) {
            throw new Exception("Invalid HTTP method: non-string");
        }

        return false;
    }

    /**
     * Checks whether HTTP method is POST. if $_SERVER['REQUEST_METHOD']=='POST'
     *
     * @return boolean
     */
    public function isPost()
    {
        return $this->getMethod() === 'POST';
    }

    /**
     * Checks whether HTTP method is GET. if $_SERVER['REQUEST_METHOD']=='GET'
     *
     * @return boolean
     */
    public function isGet()
    {
        return $this->getMethod() === 'GET';
    }

    /**
     * Checks whether HTTP method is PUT. if $_SERVER['REQUEST_METHOD']=='PUT'
     *
     * @return boolean
     */
    public function isPut()
    {
        return $this->getMethod() === 'PUT';
    }

    /**
     * Checks whether HTTP method is PATCH. if $_SERVER['REQUEST_METHOD']=='PATCH'
     *
     * @return boolean
     */
    public function isPatch()
    {
        return $this->getMethod() === 'PATCH';
    }

    /**
     * Checks whether HTTP method is HEAD. if $_SERVER['REQUEST_METHOD']=='HEAD'
     *
     * @return boolean
     */
    public function isHead()
    {
        return $this->getMethod() === 'HEAD';
    }

    /**
     * Checks whether HTTP method is DELETE. if $_SERVER['REQUEST_METHOD']=='DELETE'
     *
     * @return boolean
     */
    public function isDelete()
    {
        return $this->getMethod() === 'DELETE';
    }

    /**
     * Checks whether HTTP method is OPTIONS. if $_SERVER['REQUEST_METHOD']=='OPTIONS'
     *
     * @return boolean
     */
    public function isOptions()
    {
        return $this->getMethod() === 'OPTIONS';
    }

    /**
     * Checks whether request includes attached files
     *
     * @param boolean $onlySuccessful
     * @return long
     * @throws Exception
     */
    public function hasFiles($onlySuccessful = false)
    {
        if (!is_bool($onlySuccessful)) {
            throw new Exception('Invalid parameter type.');
        }

        $numberFiles = 0;
        $files = $_FILES;
        
        if (!is_array($files)) {
            return 0;
        }

        foreach ($files as $file) {
            if (isset($file['error'])) {
                $error = $file['error'];

                if (!is_array($error)) {
                    if (!$error || !$onlySuccessful) {
                        $numberFiles++;
                    }
                }

                if (is_array($error)) {
                    $numberFiles += $this->hasFileHelper($error, $onlySuccessful);
                }
            }
        }

        return $numberFiles;
    }

    /**
     * Recursively counts file in an array of files
     *
     * @param mixed $data
     * @param boolean $onlySuccessful
     * @return long
     */
    protected final function hasFileHelper($data, $onlySuccessful)
    {
        $numberFiles = 0;

        if (!is_array($data)) {
            return 1;
        }

        foreach ($data as $value) {
            if (!is_array($value)) {
                if (!$value || !$onlySuccessful) {
                    $numberFiles++;
                }
            }

            if (is_array($value)) {
                $numberFiles += $this->hasFileHelper($value, $onlySuccessful);
            }
        }

        return $numberFiles;
    }

    /**
     * Gets attached files as \Phalcon\Http\Request\File instances
     *
     * @param boolean $onlySuccessful
     * @return \Phalcon\Http\Request\File[]|null
     * @throws Exception
     */
    public function getUploadedFiles($onlySuccessful = false)
    {
        if (!is_bool($onlySuccessful)) {
            throw new Exception('Invalid parameter type.');
        }

        if (is_array($_FILES) === false ||
            count($_FILES) === 0) {
            return;
        }

        $files = [];
        $superFiles = $_FILES;

        if (count($superFiles) > 0) {

            foreach ($superFiles as $perfix => $input) {
                if (is_array($input['name'])) {
                    $smoothInput = $this->smoothFiles($input['name'], $input['type'], $input['tmp_name'], $input['size'],$input['error'], $perfix);

                    foreach ($smoothInput as $file) {
                        if ($onlySuccessful == false || $file['error'] == UPLOAD_ERR_OK) {
                            $dataFile = [
                                'name' => $file['name'],
                                'type' => $file['type'],
                                'tmp_name' => $file['tmp_name'],
                                'size' => $file['size'],
                                'error' => $file['error']

                            ];

                            $files[] = new File($dataFile, $file['key']);
                        }
                    }
                } else {
                    if ($onlySuccessful == false || $input['error'] == UPLOAD_ERR_OK) {
                        $files[] = new File($input, $perfix);
                    }
                }
            }
        }

        return $files;
    }

    /**
     * Smooth out $_FILES to have plain array with all files uploaded
     *
     * @param array! $names
     * @param array! $types
     * @param array! $tmp_names
     * @param array! $errors
     * @param string $prefix
     * @return array
     */
    protected final function smoothFiles($names, $types, $tmp_names, $sizes, $errors, $prefix)
    {
        $files = [];

        foreach ($names as $idx => $name) {
            $p = $prefix . '.' . $idx;

            if (is_string($name)) {
                $files[] = [
                    'name' => $name,
                    'type' => $types[$idx],
                    'tmp_name' => $tmp_names[$idx],
                    'size' => $sizes[$idx],
                    'error' => $errors[$idx],
                    'key' => $p

                ];
            }

            if (is_array($name)) {
                $parentFiles = $this->smoothFiles($names[$idx], $types[$idx], $tmp_names[$idx], $sizes[$idx], $error[$idx], $p);

                foreach ($parentFiles as $file) {
                    $files[] = $file;
                }
            }
        }

        return $files;
    }

    /**
     * Returns the available headers in the request
     *
     * @return array
     */
    public function getHeaders()
    {
        $headers = [];
        $contentHeaders = ['CONTENT_TYPE' => true, 'CONTENT_LENGTH' => true];

        foreach ($_SERVER as $name => $value) {
            if (Text::startsWith($name, 'HTTP_')) {
                $name = ucwords(strtolower(str_replace('_', ' ', substr($name, 5))));
                $name = str_replace(' ', '-', $name);
                $headers[$name] = $value;
            } else {
                $name = ucwords(strtolower(str_replace('_', ' ', $name)));
                $name = str_replace(' ', '-', $name);
                $headers[$name] = $value;
            }
        }

        return $headers;
    }

    /**
     * Gets web page that refers active request. ie: http://www.google.com
     *
     * @return string
     */
    public function getHTTPReferer()
    {
        if (isset($_SERVER['HTTP_REFERER'])) {
            return $_SERVER['HTTP_REFERER'];
        }

        return '';
    }

    /**
     * Process a request header and return an array of values with their qualities
     *
     * @param string! $serverIndex
     * @param string! $name
     * @return array
     * @throws Exception
     */
    protected final function _getQualityHeader($serverIndex, $name)
    {
        if (!is_string($serverIndex) || !is_string($name)) {
            throw new Exception('Invalid parameter type.');
        }

        $returnedParts = [];
        foreach (preg_split("/,\\s*/", $this->getServer($serverIndex), -1, PREG_SPLIT_NO_EMPTY) as $part) {
            
            $headerParts = [];
            foreach (preg_split("/\s*;\s*/", trim($part), -1, PREG_SPLIT_NO_EMPTY) as $headerPart) {
                if (strpos($headerPart, '=') !== false) {
                    $split = explode('=', $headerPart, 2);
                    if ($split[0] === 'q') {
                        $headerParts['quality'] = (double) $split[1];
                    } else {
                        $headerParts[$split[0]] = $split[1];
                    }
                } else {
                    $headerParts[$name] = $headerPart;
                    $headerParts['quality'] = 1.0;
                }
            }

            $returnedParts[] = $headerParts;

        }

        return $returnedParts;
    }

    /**
     * Process a request header and return the one with best quality
     *
     * @param array $qualityParts
     * @param string! $name
     * @return string
     * @throws Exception
     */
    protected final function _getBestQuality($qualityParts, $name)
    {
        if (!is_array($qualityParts) || !is_string($name)) {
            throw new Exception('Invalid parameter type.');
        }

        $i = 0;
        $quality = 0.0;
        $selectedName = '';

        foreach ($qualityParts as $accept) {
            if ($i == 0) {
                $quality = (double) $accept['quality'];
                $selectedName = $accept[$name];
            } else {
                $acceptQuality = (double) $accept['quality'];
                if ($acceptQuality > $quality) {
                    $quality = $acceptQuality;
                    $selectedName = $accept[$name];
                }
            }

            $i++;
        }
        
        return $selectedName;
    }

    /**
     * Gets content type which request has been made
     *
     * @return string
     */
    public function getContentType()
    {
        if (isset($_SERVER["CONTENT_TYPE"])) {
            return $_SERVER["CONTENT_TYPE"];
        } else {
            if (isset($_SERVER["HTTP_CONTENT_TYPE"])) {
                return $_SERVER["HTTP_CONTENT_TYPE"];
            }
        }

        return null;
    }

    /**
     * Gets array with mime/types and their quality accepted by the browser/client from $_SERVER['HTTP_ACCEPT']
     *
     * @return array
     */
    public function getAcceptableContent()
    {
        return $this->_getQualityHeader('HTTP_ACCEPT', 'accept');
    }

    /**
     * Gets best mime/type accepted by the browser/client from $_SERVER['HTTP_ACCEPT']
     *
     * @return array
     */
    public function getBestAccept()
    {
        return $this->_getBestQuality($this->getAcceptableContent(), 'accept');
    }

    /**
     * Gets charsets array and their quality accepted by the browser/client from $_SERVER['HTTP_ACCEPT_CHARSET']
     *
     * @return array
     */
    public function getClientCharsets()
    {
        return $this->_getQualityHeader('HTTP_ACCEPT_CHARSET', 'charset');
    }

    /**
     * Gets best charset accepted by the browser/client from $_SERVER['HTTP_ACCEPT_CHARSET']
     *
     * @return string
     */
    public function getBestCharset()
    {
        return $this->_getBestQuality($this->getClientCharsets(), 'charset');
    }

    /**
     * Gets languages array and their quality accepted by the browser/client from $_SERVER['HTTP_ACCEPT_LANGUAGE']
     *
     * @return array
     */
    public function getLanguages()
    {
        return $this->_getQualityHeader('HTTP_ACCEPT_LANGUAGE', 'language');
    }

    /**
     * Gets best language accepted by the browser/client from $_SERVER['HTTP_ACCEPT_LANGUAGE']
     *
     * @return string
     */
    public function getBestLanguage()
    {
        return $this->_getBestQuality($this->getLanguages(), 'language');
    }

    /**
     * Gets auth info accepted by the browser/client from $_SERVER['PHP_AUTH_USER']
     *
     * @return array
     */
    public function getBasicAuth()
    {
        if (isset($_SERVER["PHP_AUTH_USER"]) && isset($_SERVER["PHP_AUTH_PW"])) {
            $auth = [];
            $auth['username'] = $_SERVER["PHP_AUTH_USER"];
            $auth['password'] = $_SERVER["PHP_AUTH_PW"];
            return $auth;
        }

        return null;
    }

    /**
     * Gets auth info accepted by the browser/client from $_SERVER['PHP_AUTH_DIGEST']
     *
     * @return array
     */
    public function getDigestAuth()
    {
        $auth = [];
        if (isset($_SERVER["PHP_AUTH_DIGEST"])) {
            $digest = $_SERVER["PHP_AUTH_DIGEST"];
            $matches = [];
            if (!preg_match_all("#(\\w+)=(['\"]?)([^'\" ,]+)\\2#", $digest, $matches, 2)) {
                return $auth;
            }
            if (is_array($matches)) {
                foreach ($matches as $match) {
                    $auth[$match[1]] = $match[3];
                }
            }
        }

        return $auth;
    }
}
