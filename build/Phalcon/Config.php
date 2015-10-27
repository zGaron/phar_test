<?php
/**
 * Config
 *
 */

namespace Phalcon;

use \Phalcon\Config\Exception;

/**
 * Phalcon\Config
 *
 * Phalcon\Config is designed to simplify the access to, and the use of, configuration data within applications.
 * It provides a nested object property based user interface for accessing this configuration data within
 * application code.
 *
 *<code>
 *  $config = new Phalcon\Config(array(
 *      "database" => array(
 *          "adapter" => "Mysql",
 *          "host" => "localhost",
 *          "username" => "scott",
 *          "password" => "cheetah",
 *          "dbname" => "test_db"
 *      ),
 *      "Scene" => array(
 *          "controllersDir" => "../app/controllers/",
 *          "modelsDir" => "../app/models/",
 *          "viewsDir" => "../app/views/"
 *      )
 * ));
 *</code>
 *
 */
class Config implements \ArrayAccess, \Countable
{

    /**
     * \Phalcon\Config constructor
     *
     * @param array $arrayConfig
     * @throws Exception
     */
    public function __construct($arrayConfig = null)
    {
        if (!is_array($arrayConfig)) {
            throw new Exception('The configuration must be an Array');
        }

        foreach ($arrayConfig as $key => $value) {
            $this->offsetSet($key, $value);
        }
    }

    /**
     * Allows to check whether an attribute is defined using the array-syntax
     *
     *<code>
     * var_dump(isset($config['database']));
     *</code>
     *
     * @param mixed $index
     * @return boolean
     */
    public function offsetExists($index)
    {
        $index = strval($index);

        return isset($this->{$index});
    }

    /**
     * Gets an attribute from the configuration, if the attribute isn't defined returns null
     * If the value is exactly null or is not defined the default value will be used instead
     *
     *<code>
     * echo $config->get('controllersDir', '../app/controllers/');
     *</code>
     *
     * @param mixed $index
     * @param mixed $defaultValue
     * @return mixed
     */
    public function get($index, $defaultValue = null)
    {
        $index = strval($index);

        if (isset($this->{$index})) {
            return $this->{$index};
        }

        return $defaultValue;
    }

    /**
     * Gets an attribute using the array-syntax
     *
     *<code>
     * print_r($config['database']);
     *</code>
     *
     * @param mixed $index
     * @return string
     */
    public function offsetGet($index)
    {
        $index = strval($index);

        return $this->{$index};
    }

    /**
     * Sets an attribute using the array-syntax
     *
     *<code>
     * $config['database'] = array('type' => 'Sqlite');
     *</code>
     *
     * @param mixed $index
     * @param mixed $value
     */
    public function offsetSet($index, $value)
    {
        $index = strval($index);

        if (is_array($value)) {
            $this->{$index} = new self($value);
        } else {
            $this->{$index} = $value;
        }
    }

    /**
     * Unsets an attribute using the array-syntax
     *
     *<code>
     * unset($config['database']);
     *</code>
     *
     * @param mixed $index
     */
    public function offsetUnset($index)
    {
        $index = strval($index);

        $this->{$index} = null;
    }

    /**
     * Merges a configuration into the current one
     *
     * @brief void \Phalcon\Config::merge(array|object $with)
     *
     *<code>
     *  $appConfig = new \Phalcon\Config(array('database' => array('host' => 'localhost')));
     *  $globalConfig->merge($config2);
     *</code>
     *
     * @param \Phalcon\Config|array $config
     * @throws Exception Exception
     */
    public function merge($config)
    {
        return $this->_merag($config);
    }

    /**
     * Converts recursively the object to an array
     *
     *<code>
     *  print_r($config->toArray());
     *</code>
     *
     * @return array
     */
    public function toArray()
    {
        
        $arrayConfig = [];
        foreach (get_object_vars($this) as $key => $value) {
            if (is_object($value)) {
                if (method_exists($value, 'toArray')) {
                    $arrayConfig[$key] = $value->toArray();
                } else {
                    $arrayConfig[$key] = $value;
                }
            } else {
                $arrayConfig[$key] = $value;
            }
        }
        return $arrayConfig;
    }

    /**
     * Returns the count of properties set in the config
     *
     *<code>
     * print count($config);
     *</code>
     *
     * or
     *
     *<code>
     * print $config->count();
     *</code>
     *
     * @return int
     */
    public function count()
    {
        return count(get_object_vars($this));
    }

    /**
     * Restores the state of a \Phalcon\Config object
     *
     * @param array $data
     * @return \Phalcon\Config
     */
    public static function __set_state($data)
    {
        return new self($data);
    }

    /**
     * Helper method for merge configs (forwarding nested config instance)
     *
     * @param Config $config
     * @param Config $instance
     * @return Config merged config
     */
    protected final function _merag($config, $instance = null)
    {
        if (!is_object($instance)) {
            $instance = $this;
        }

        $number = $instance->count();

        foreach (get_object_vars($config) as $key => $value) {
            
            if (isset($instance->{$key})) {
                $localObject = $instance->{$key};
                if (is_object($localObject) && is_object($value)) {
                    $this->_merag($value, $localObject);
                    continue;
                }
            }

            if (is_integer($key)) {
                $key = strval($number);
                $number++;
            }
            $instance->{$key} = $value;
        }

        return $instance;
    }
}
