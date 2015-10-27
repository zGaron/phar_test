<?php
/**
 * Event
 *
*/
namespace Phalcon\Events;

use \Phalcon\Events\Exception;

/**
 * Phalcon\Events\Event
 *
 * This class offers contextual information of a fired event in the EventsManager
 *
 */
class Event
{
    /**
     * Type
     *
     * @var string|null
     * @access protected
    */
    protected $_type;

    /**
     * Source
     *
     * @var object|null
     * @access protected
    */
    protected $_source;

    /**
     * Data
     *
     * @var mixed
     * @access protected
    */
    protected $_data;

    /**
     * Stopped
     *
     * @var boolean
     * @access protected
    */
    protected $_stopped = false;

    /**
     * Cancelable
     *
     * @var boolean
     * @access protected
    */
    protected $_cancelable = true;

    /**
     * \Phalcon\Events\Event constructor
     *
     * @param string! $type
     * @param object $source
     * @param mixed $data
     * @param boolean|null $cancelable
     * @throws Exception
     */
    public function __construct($type, $source, $data = null, $cancelable = true)
    {
        if (!is_string($type)) {
            throw new Exception('Invalid parameter type.');
        }

        if (!is_object($source)) {
            throw new Exception('Invalid parameter type.');
        }

        if (!is_bool($cancelable)) {
            throw new Exception('Invalid parameter type.');
        }

        $this->_type = $type;
        $this->_source = $source;

        if ($data !== null) {
            $this->_data = $data;
        }

        if ($cancelable !== true) {
            $this->_cancelable = $cancelable;
        }
    }

    /**
     * Set the event's type
     *
     * @param string $eventType
     * @throws Exception
     */
    public function setType($eventType)
    {
        if (!is_string($eventType)) {
            throw new Exception('Invalid parameter type.');
        }

        $this->_type = $eventType;
    }

    /**
     * Returns the event's type
     *
     * @return string
     */
    public function getType()
    {
        return $this->_type;
    }

    /**
     * Returns the event's source
     *
     * @return object
     */
    public function getSource()
    {
        return $this->_source;
    }

    /**
     * Set the event's data
     *
     * @param mixed $data
     */
    public function setData($data)
    {
        $this->_data = $data;
    }

    /**
     * Returns the event's data
     *
     * @return mixed
     */
    public function getData()
    {
        return $this->_data;
    }

    /**
     * Sets if the event is cancelable
     *
     * @param boolean $cancelable
     * @throws Exception
     */
    public function setCancelable($cancelable)
    {
        if (!is_bool($cancelable)) {
            throw new Exception('Invalid parameter type.');
        }

        $this->_cancelable = $cancelable;
    }

    /**
     * Check whether the event is cancelable
     *
     * @return boolean
     */
    public function getCancelable()
    {
        return $this->_cancelable;
    }

    /**
     * Stops the event preventing propagation
     *
     * @throws Exception
     */
    public function stop()
    {
        if (!$this->_cancelable) {
             throw new Exception('Trying to cancel a non-cancelable event');
        }

        $this->_stopped = true;
    }

    /**
     * Check whether the event is currently stopped
     *
     * @return boolean
     */
    public function isStopped()
    {
        return $this->_stopped;
    }
}
