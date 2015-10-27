<?php
/**
 * Json Cache Frontend
 *
*/

namespace Phalcon\Cache\Frontend;

use \Phalcon\Cache\FrontendInterface;
use \Phalcon\Cache\Exception;

/**
 * Phalcon\Cache\Frontend\Json
 *
 * Allows to cache data converting/deconverting them to JSON.
 *
 * This adapters uses the json_encode/json_decode PHP's functions
 *
 * As the data is encoded in JSON other systems accessing the same backend could
 * process them
 *
 *<code>
 *
 * // Cache the data for 2 days
 * $frontCache = new Phalcon\Cache\Frontend\Json(array(
 *    "lifetime" => 172800
 * ));
 *
 * //Create the Cache setting memcached connection options
 * $cache = new Phalcon\Cache\Backend\Memcache($frontCache, array(
 *      'host' => 'localhost',
 *      'port' => 11211,
 *      'persistent' => false
 * ));
 *
 * //Cache arbitrary data
 * $cache->save('my-data', array(1, 2, 3, 4, 5));
 *
 * //Get data
 * $data = $cache->get('my-data');
 *</code>
 *
 */
class Json implements FrontendInterface
{
    /**
     * Frontend Options
     *
     * @var array|null
     * @access protected
    */
    protected $_frontendOptions;

    /**
     * \Phalcon\Cache\Frontend\Base64 constructor
     *
     * @param array|null $frontendOptions
     */
    public function __construct($frontendOptions = null)
    {
        $this->_frontendOptions = $frontendOptions;
    }

    /**
     * Returns the cache lifetime
     *
     * @return integer
     */
    public function getLifetime()
    {
        $options = $this->_frontendOptions;
        if (is_array($options)) {
            if (isset($options['lifetime'])) {
                return $options['lifetime'];
            }
        }
        return 1;
    }

    /**
     * Check whether if frontend is buffering output
     *
     * @return boolean
     */
    public function isBuffering()
    {
        return false;
    }

    /**
     * Starts output frontend. Actually, does nothing
     */
    public function start()
    {
    
    }

    /**
     * Returns output cached content
     *
     * @return string|null
     */
    public function getContent()
    {
        return null;
    }

    /**
     * Stops output frontend
     */
    public function stop()
    {
    
    }

    /**
     * Serializes data before storing it
     *
     * @param mixed $data
     * @return string
     */
    public function beforeStore($data)
    {
        return json_encode($data);
    }

    /**
     * Unserializes data after retrieving it
     *
     * @param mixed $data
     * @return mixed
     */
    public function afterRetrieve($data)
    {
        return json_decode($data);
    }
}
