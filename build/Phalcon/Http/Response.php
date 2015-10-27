<?php
/**
 * Response
 *
*/
namespace Phalcon\Http;

use \Phalcon\Di\InjectionAwareInterface;
use \Phalcon\DiInterface;
use \Phalcon\Http\ResponseInterface;
use \Phalcon\Http\Response\Exception;
use \Phalcon\Http\Response\HeadersInterface;
use \Phalcon\Http\Response\CookiesInterface;
use \Phalcon\Http\Response\Headers;
use \Phalcon\Mvc\UrlInterface;
use \Phalcon\Mvc\ViewInterface;


/**
 * Phalcon\Http\Response
 *
 * Part of the HTTP cycle is return responses to the clients.
 * Phalcon\HTTP\Response is the Scene component responsible to achieve this task.
 * HTTP responses are usually composed by headers and body.
 *
 *<code>
 *  $response = new Phalcon\Http\Response();
 *  $response->setStatusCode(200, "OK");
 *  $response->setContent("<html><body>Hello</body></html>");
 *  $response->send();
 *</code>
 *
 */
class Response implements ResponseInterface, InjectionAwareInterface
{
    /**
     * Sent
     *
     * @var boolean
     * @access protected
    */
    protected $_sent = false;

    /**
     * Content
     *
     * @var null|string
     * @access protected
    */
    protected $_content;

    /**
     * Headers
     *
     * @var null|\Phalcon\Http\Response\HeadersInterface
     * @access protected
    */
    protected $_headers;

    /**
     * Cookies
     *
     * @var null|\Phalcon\Ḩttp\Response\CookiesInterface
     * @access protected
    */
    protected $_cookies;

    /**
     * File
     *
     * @var null|string
     * @access protected
    */
    protected $_file;

    /**
     * Dependency Injector
     *
     * @var null|\Phalcon\DiInterface
     * @access protected
    */
    protected $_dependencyInjector;

    /**
     * StatusCodes
     *
     * @var null|\Phalcon\DiInterface
     * @access protected
    */
    protected $_statusCodes;

    /**
     * \Phalcon\Http\Response constructor
     *
     * @param string|null $content
     * @param int|null $code
     * @param string|null $status
     * @throws Exception
     */
    public function __construct($content = null, $code = null, $status = null)
    {
        if (is_string($content)) {
            $this->_content = $content;
        } elseif (!is_null($content)) {
            throw new Exception('Invalid parameter type.');
        }

        if (is_int($code) && is_string($status)) {
            $this->setStatusCode($code, $status);
        } elseif (!is_null($code) || !is_null($status)) {
            throw new Exception('Invalid parameter type.');
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
     * @return \Phalcon\DiInterface
     */
    public function getDI()
    {
        return $this->_dependencyInjector;
    }

    /**
     * Sets the HTTP response code
     *
     *<code>
     *  $response->setStatusCode(404, "Not Found");
     *</code>
     *
     * @param int $code
     * @param string $message
     * @return \Phalcon\Http\ResponseInterface
     * @throws Exception
     */
    public function setStatusCode($code, $message = null)
    {
        if (!is_int($code)) {
            throw new Exception('Invalid parameter type.');
        }

        $headers = $this->getHeaders();
        $currentHeadersRaw = $headers->toArray();

        /**
         * We use HTTP/1.1 instead of HTTP/1.0
         *
         * Before that we would like to unset any existing HTTP/x.y headers
         */
        if (is_array($currentHeadersRaw)) {
            foreach ($currentHeadersRaw as $key => $value) {
                if (is_string($key) && strstr($key, 'HTTP/')) {
                    $headers->remove($key);
                }
            }
        }

        // if an empty message is given we try and grab the default for this
        // status code. If a default doesn't exist, stop here.
        if ($message === null) {
            if (!is_array($this->_statusCodes)) {
                $this->_statusCodes = [
                    // INFORMATIONAL CODES
                    100 => "Continue",
                    101 => "Switching Protocols",
                    102 => "Processing",
                    // SUCCESS CODES
                    200 => "OK",
                    201 => "Created",
                    202 => "Accepted",
                    203 => "Non-Authoritative Information",
                    204 => "No Content",
                    205 => "Reset Content",
                    206 => "Partial Content",
                    207 => "Multi-status",
                    208 => "Already Reported",
                    // REDIRECTION CODES
                    300 => "Multiple Choices",
                    301 => "Moved Permanently",
                    302 => "Found",
                    303 => "See Other",
                    304 => "Not Modified",
                    305 => "Use Proxy",
                    306 => "Switch Proxy", // Deprecated
                    307 => "Temporary Redirect",
                    // CLIENT ERROR
                    400 => "Bad Request",
                    401 => "Unauthorized",
                    402 => "Payment Required",
                    403 => "Forbidden",
                    404 => "Not Found",
                    405 => "Method Not Allowed",
                    406 => "Not Acceptable",
                    407 => "Proxy Authentication Required",
                    408 => "Request Time-out",
                    409 => "Conflict",
                    410 => "Gone",
                    411 => "Length Required",
                    412 => "Precondition Failed",
                    413 => "Request Entity Too Large",
                    414 => "Request-URI Too Large",
                    415 => "Unsupported Media Type",
                    416 => "Requested range not satisfiable",
                    417 => "Expectation Failed",
                    418 => "I'm a teapot",
                    422 => "Unprocessable Entity",
                    423 => "Locked",
                    424 => "Failed Dependency",
                    425 => "Unordered Collection",
                    426 => "Upgrade Required",
                    428 => "Precondition Required",
                    429 => "Too Many Requests",
                    431 => "Request Header Fields Too Large",
                    // SERVER ERROR
                    500 => "Internal Server Error",
                    501 => "Not Implemented",
                    502 => "Bad Gateway",
                    503 => "Service Unavailable",
                    504 => "Gateway Time-out",
                    505 => "HTTP Version not supported",
                    506 => "Variant Also Negotiates",
                    507 => "Insufficient Storage",
                    508 => "Loop Detected",
                    511 => "Network Authentication Required"
                ];
            }
        

            if (!isset($this->_statusCodes[$code])) {
                throw new Exception("Non-standard statuscode given without a message");
            }

            $defaultMessage = $this->_statusCodes[$code];
            $message = $defaultMessage;
        }

        $headers->setRaw('HTTP/1.1 ' . $code . ' ' . $message);
        /**
         * We also define a 'Status' header with the HTTP status
         */
        $headers->set('Status', $code . ' ' . $message);

        //$this->_headers = $headers;

        return $this;

    }

    /**
     * Returns the status code
     *
     *<code>
     *  print_r($response->getStatusCode());
     *</code>
     *
     * @return array
     */
    public function getStatusCode()
    {
        return $this->getHeaders()->get('Status');
    }

    /**
     * Sets a headers bag for the response externally
     *
     * @param \Phalcon\Http\Response\HeadersInterface $headers
     * @return \Phalcon\Http\ResponseInterface
     * @throws Exception
     */
    public function setHeaders($headers)
    {
        if (!is_object($headers) || $headers instanceof HeadersInterface === false) {
            throw new Exception('Invalid parameter type.');
        }

        $this->_headers = $headers;

        return $this;
    }

    /**
     * Returns headers set by the user
     *
     * @return \Phalcon\Http\Response\HeadersInterface
     */
    public function getHeaders()
    {
        if (is_null($this->_headers)) {
            /*
             * A Phalcon\Http\Response\Headers bag is temporary used to manage the headers
             * before sent them to the client
            */
            $headers = new Headers();
            $this->_headers = $headers;
        }

        return $this->_headers;
    }

    /**
     * Sets a cookies bag for the response externally
     *
     * @param \Phalcon\Http\Response\CookiesInterface $cookies
     * @return \Phalcon\Http\ResponseInterface
     * @throws Exception
     */
    public function setCookies($cookies)
    {
        if (!is_object($cookies) || $cookies instanceof CookiesInterface === false) {
            throw new Exception('The cookies bag is not valid');
        }

        $this->_cookies = $cookies;

        return $this;
    }

    /**
     * Returns coookies set by the user
     *
     * @return \Phalcon\Http\Response\CookiesInterface|null
     */
    public function getCookies()
    {
        return $this->_cookies;
    }

    /**
     * Overwrites a header in the response
     *
     *<code>
     *  $response->setHeader("Content-Type", "text/plain");
     *</code>
     *
     * @param string $name
     * @param string $value
     * @return \Phalcon\Http\ResponseInterface
     * @throws Exception
     */
    public function setHeader($name, $value)
    {
        if (!is_string($name) || !is_string($value)) {
            throw new Exception('Invalid parameter type.');
        }

        $headers = $this->getHeaders();
        $headers->set($name, $value);

        return $this;
    }

    /**
     * Send a raw header to the response
     *
     *<code>
     *  $response->setRawHeader("HTTP/1.1 404 Not Found");
     *</code>
     *
     * @param string $header
     * @return \Phalcon\Http\ResponseInterface
     * @throws Exception
     */
    public function setRawHeader($header)
    {
        if (!is_string($header)) {
            throw new Exception('Invalid parameter type.');
        }

        $headers = $this->getHeaders();
        $headers->setRaw($header);

        return $this;
    }

    /**
     * Resets all the stablished headers
     *
     * @return \Phalcon\Http\ResponseInterface
     */
    public function resetHeaders()
    {
        $headers = $this->getHeaders();
        $headers->reset();

        return $this;
    }

    /**
     * Sets a Expires header to use HTTP cache
     *
     *<code>
     *  $response->setExpires(new DateTime());
     *</code>
     *
     * @param DateTime $datetime
     * @return \Phalcon\Http\ResponseInterface
     * @throws Exception
     */
    public function setExpires($datetime)
    {
        if (!is_object($datetime) || $datetime instanceof \DateTime === false) {
            throw new Exception('datetime parameter must be an instance of DateTime');
        }

        $date = clone $datetime;

        /**
         * All the expiration times are sent in UTC
         * Change the timezone to utc
         */
        $date->setTimezone(new \DateTimeZone("UTC"));

        /**
         * The 'Expires' header set this info
         */
        $this->setHeader("Expires", $date->format("D, d M Y H:i:s") . " GMT");

        return $this;
    }

    /**
     * Sets Cache headers to use HTTP cache
     *
     *<code>
     *  $response->setCache(60);
     *</code>
     *
     * @param int $minutes
     * @return \Phalcon\Http\ResponseInterface
     */
    public function setCache($minutes)
    {
        $date = new \DateTime();
        $date->modify("+" . $minutes . " minutes");

        $this->setExpires($date);
        $this->setHeader("Cache-Control", "max-age=" . ($minutes * 60));

        return $this;
    }

    /**
     * Sends a Not-Modified response
     *
     * @return \Phalcon\Http\ResponseInterface
     */
    public function setNotModified()
    {
        $this->setStatusCode(304, 'Not modified');

        return $this;
    }

    /**
     * Sets the response content-type mime, optionally the charset
     *
     *<code>
     *  $response->setContentType('application/pdf');
     *  $response->setContentType('text/plain', 'UTF-8');
     *</code>
     *
     * @param string $contentType
     * @param string|null $charset
     * @return \Phalcon\Http\ResponseInterface
     * @throws Exception
     */
    public function setContentType($contentType, $charset = null)
    {
        if (!is_string($contentType)) {
            throw new Exception('Invalid parameter type.');
        }

        $headers = $this->getHeaders();

        if ($charset === null) {
            $headers->set('Content-Type', $contentType);
        } elseif (is_string($charset)) {
            $headers->set('Content-Type', $contentType . '; charset=' . $charset);
        } else {
            throw new Exception('Invalid parameter type.');
        }

        return $this;
    }

    /**
     * Set a custom ETag
     *
     *<code>
     *  $response->setEtag(md5(time()));
     *</code>
     *
     * @param string $etag
     * @throws Exception
     */
    public function setEtag($etag)
    {
        if (is_string($etag) === false) {
            throw new Exception('Invalid parameter type.');
        }

        $headers = $this->getHeaders();
        $headers->set('Etag', $etag);

        return $this;
    }

    /**
     * Redirect by HTTP to another action or URL
     *
     *<code>
     *  //Using a string redirect (internal/external)
     *  $response->redirect("posts/index");
     *  $response->redirect("http://en.wikipedia.org", true);
     *  $response->redirect("http://www.example.com/new-location", true, 301);
     *
     *</code>
     *
     * @param string|array|null $location
     * @param boolean|null $externalRedirect
     * @param int|null $statusCode
     * @return \Phalcon\Http\ResponseInterface
     * @throws Exception
     */
    public function redirect($location = null, $externalRedirect = false, $statusCode = 302)
    {
        if (!is_string($location) && !is_null($location)) {
            throw new Exception('Invalid parameter type.');
        }

        if (!is_bool($externalRedirect)) {
            throw new Exception('Invalid parameter type.');
        }

        if (!is_int($statusCode)) {
            $statusCode = (int)$statusCode;
        }

        if (!$location) {
            $location = '';
        }

        if ($externalRedirect) {
            $header = $location;
        } else {
            if (is_string($location) && strstr($location, '://')) {
                $matched = preg_match('/^[^:\\/?#]++:/', $location);
                if ($matched) {
                    $header = $location;
                } else {
                    $header = null;
                }
            } else {
                $header = null;
            }
        }

        $dependencyInjector = $this->_dependencyInjector;
        if (!is_object($dependencyInjector)) {
            throw new Exception("A dependency injector container is required to obtain the 'url' service");
        }

        if (!$header) {
            $url = $dependencyInjector->getShared('url');
            $header = $url->get($location);
        }

        if ($dependencyInjector->has('view')) {
            $view = $dependencyInjector->getShared('view');
            if ($view instanceof ViewInterface) {
                $view->disable();
            }
        }

        /**
         * The HTTP status is 302 by default, a temporary redirection
         */
        $message = null;

        if ($statusCode < 300 || $statusCode > 308) {
            $statusCode = 302;
            $message = $this->_statusCodes[302];
        } else {
            if (isset($this->_statusCodes[$statusCode])) {
                $message = $this->_statusCodes[$statusCode];
            }
        }

        $this->setStatusCode($statusCode, $message);

        /**
         * Change the current location using 'Location'
         */
        $this->setHeader('Location', $header);

        return $this;

    }

    /**
     * Sets HTTP response body
     *
     *<code>
     *  $response->setContent("<h1>Hello!</h1>");
     *</code>
     *
     * @param string $content
     * @return \Phalcon\Http\ResponseInterface
     * @throws Exception
     */
    public function setContent($content)
    {
        if (!is_string($content)) {
            throw new Exception('Invalid parameter type.');
        }

        $this->_content = $content;
        return $this;
    }

    /**
     * Sets HTTP response body. The parameter is automatically converted to JSON
     *
     *<code>
     *  $response->setJsonContent(array("status" => "OK"));
     *</code>
     *
     * @param mixed $content
     * @param int|null $jsonOptions
     * @return \Phalcon\Http\ResponseInterface
     */
    public function setJsonContent($content, $jsonOptions = 0, $depth = 512)
    {
        $this->_content = json_encode($content, $jsonOptions, $depth);

        return $this;
    }

    /**
     * Appends a string to the HTTP response body
     *
     * @param string $content
     * @return \Phalcon\Http\ResponseInterface
     * @throws Exception
     */
    public function appendContent($content)
    {
        if (!is_string($content)) {
            throw new Exception('Invalid parameter type.');
        }

        $this->_content = $this->getContent() . $content;

        return $this;
    }

    /**
     * Gets the HTTP response body
     *
     * @return string|null
     */
    public function getContent()
    {
        return $this->_content;
    }

    /**
     * Check if the response is already sent
     *
     * @return boolean
     */
    public function isSent()
    {
        return $this->_sent;
    }

    /**
     * Sends headers to the client
     *
     * @return \Phalcon\Http\ResponseInterface
     */
    public function sendHeaders()
    {
        $headers = $this->_headers;
        if (is_object($headers)) {
           $headers->send();
        }

        return $this;
    }

    /**
     * Sends cookies to the client
     *
     * @return \Phalcon\Http\ResponseInterface
     */
    public function sendCookies()
    {
        $cookies = $this->_cookies;
        if (is_object($cookies)) {
            $cookies->send();
        }

        return $this;
    }

    /**
     * Prints out HTTP response to the client
     *
     * @return \Phalcon\Http\ResponseInterface
     * @throws Exception
     */
    public function send()
    {
        if ($this->_sent) {
            throw new Exception('Response was already sent');
        }

        /**
         * Send headers
         */
        $headers = $this->_headers;
        if (is_object($headers)) {
            $headers->send();
        }

        /**
         * Send Cookies/comment>
         */
        $cookies = $this->_cookies;
        if (is_object($cookies)) {
            $cookies->send();
        }

        /**
         * Output the response body
         */
        $content = $this->_content;
        if ($content != null) {
            echo $content;
        } else {
            $file = $this->_file;

            if (is_string($file) && strlen($file)) {
                readfile($file);
            }
        }

        $this->_sent = true;
        return $this;
    }

    /**
     * Sets an attached file to be sent at the end of the request
     *
     * @param string $filePath
     * @param string|null $attachmentName
     * @param boolean|null $attachment
     * @throws Excepiton
     */
    public function setFileToSend($filePath, $attachmentName = null, $attachment = true)
    {
        /* Type check */
        if (!is_string($filePath)) {
            throw new Exception('Invalid parameter type.');
        }

        if (!is_bool($attachment)) {
            throw new Exception('Invalid parameter type.');
        }

        if (!is_string($attachmentName)) {
            $basePath = basename($filePath);
        } else {
            $basePath = $attachmentName;
        }

        /* Execute */
        if ($attachment) {
            $headers = $this->getHeaders();
            
            $headers->setRaw('Content-Description: File Transfer');
            $headers->setRaw("Content-Type: application/octet-stream");
            $headers->setRaw('Content-Disposition: attachment; filename=' . $basePath);
            $headers->setRaw('Content-Transfer-Encoding: binary');
        }

        //@note no check if path is valid
        $this->_file = $filePath;

        return $this;
    }
}
