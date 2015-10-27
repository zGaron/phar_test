<?php
/**
 * Headers
 *
*/
namespace Phalcon\Http\Response;

/**
 * Phalcon\Http\Response\Headers
 *
 * This class is a bag to manage the response headers
 *
 */
class Headers implements HeadersInterface
{
    /**
     * Headers
     *
     * @var null|array
     * @access protected
    */
    protected $_headers = [];

    /**
     * Sets a header to be sent at the end of the request
     *
     * @param string $name
     * @param string $value
     * @throws Exception
     */
    public function set($name, $value)
    {
        if (!is_string($name)) {
            throw new Exception('Invalid parameter type.');
        }

        $this->_headers[$name] = $value;
    }

    /**
     * Gets a header value from the internal bag
     *
     * @param string $name
     * @return string|boolean
     * @throws Exception
     */
    public function get($name)
    {
        if (!is_string($name)) {
            throw new Exception('Invalid parameter type.');
        }

        if (isset($this->_headers[$name])) {
            return $this->_headers[$name];
        }

        return false;
    }

    /**
     * Sets a raw header to be sent at the end of the request
     *
     * @param string $header
     * @throws Exception
     */
    public function setRaw($header)
    {
        if (!is_string($header)) {
            throw new Exception('Invalid parameter type.');
        }

        $this->_headers[$header] = null;
    }

    /**
     * Removes a header to be sent at the end of the request
     *
     * @param string $header
     * @throws Exception
     */
    public function remove($header)
    {
        if (!is_string($header)) {
            throw new Exception('Invalid parameter type.');
        }

        $headers = $this->_headers;
        unset($headers[$header]);
        $this->_headers = $headers;
    }

    /**
     * Sends the headers to the client
     *
     * @return boolean
     */
    public function send()
    {
        if (!headers_sent()) {
            foreach ($this->_headers as $header => $value) {
                if (!empty($value)) {
                    //Default header
                    header($header . ': ' . $value, true);
                } else {
                    //Raw header
                    header($header, true);
                }
            }
            return true;
        }
        return false;
    }

    /**
     * Reset set headers
     */
    public function reset()
    {
        $this->_headers = [];
    }

    /**
     * Returns the current headers as an array
     *
     * @return array
     */
    public function toArray()
    {
        return $this->_headers;
    }

    /**
     * Restore a \Phalcon\Http\Response\Headers object
     *
     * @param array $data
     * @return \Phalcon\Http\Response\Headers
     * @throws Exception
     */
    public static function __set_state($data)
    {
        if (!is_array($data)) {
            throw new Exception('Invalid parameter type.');
        }

        $headers = new self();

        if (isset($data['_headers'])) {
            $dataHeaders = $data['_headers'];
            foreach ($dataHeaders as $key => $value) {
                $headers->set($key, $value);
            }
            
        }

        return $headers;
    }
}
