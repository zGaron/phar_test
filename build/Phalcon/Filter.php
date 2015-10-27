<?php
/**
 * Filter
 *
*/
namespace Phalcon;

use \Phalcon\FilterInterface;
use \Phalcon\Filter\Exception;

/**
 * Phalcon\Filter
 *
 * The Phalcon\Filter component provides a set of commonly needed data filters. It provides
 * object oriented wrappers to the php filter extension. Also allows the developer to
 * define his/her own filters
 *
 *<code>
 *  $filter = new Phalcon\Filter();
 *  $filter->sanitize("some(one)@exa\\mple.com", "email"); // returns "someone@example.com"
 *  $filter->sanitize("hello<<", "string"); // returns "hello"
 *  $filter->sanitize("!100a019", "int"); // returns "100019"
 *  $filter->sanitize("!100a019.01a", "float"); // returns "100019.01"
 *</code>
 *
 */
class Filter implements FilterInterface
{
    const FILTER_EMAIL      = "email";

    const FILTER_ABSINT     = "absint";

    const FILTER_INT        = "int";

    const FILTER_INT_CAST   = "int!";

    const FILTER_STRING     = "string";

    const FILTER_FLOAT      = "float";

    const FILTER_FLOAT_CAST = "float!";

    const FILTER_ALPHANUM   = "alphanum";

    const FILTER_TRIM       = "trim";

    const FILTER_STRIPTAGS  = "striptags";

    const FILTER_LOWER      = "lower";

    const FILTER_UPPER      = "upper";

    /**
     * Filters
     *
     * @var null|array
     * @access protected
    */
    protected $_filters;

    /**
     * Adds a user-defined filter
     *
     * @param string! $name
     * @param object|callable $handler
     * @return \Phalcon\Filter
     * @throws Exception
     */
    public function add($name, $handler)
    {
        if (!is_string($name)) {
            throw new Exception('Filter name must be string');
        }

        if (!is_object($handler) && !is_callable($handler)) {
            throw new Exception('Filter must be an object or callable');
        }

        $this->_filters[$name] = $handler;

        return $this;
    }

    /**
     * Sanitizes a value with a specified single or set of filters
     *
     * @param mixed $value
     * @param mixed $filters
     * @return mixed
     */
    public function sanitize($value, $filters, $noRecursive = false)
    {
        /**
         * Apply an array of filters
         */
        if (is_array($filters)) {
            if ($value !== null) {
                foreach ($filters as $filter) {
                    /**
                     * If the value to filter is an array we apply the filters recursively
                     */
                    if (is_array($value) && !$noRecursive) {
                        $arrayValue = [];
                        foreach ($value as $itemKey => $itemValue) {
                            $arrayValue[$itemKey] = $this->_sanitize($itemValue, $filter);
                        }
                        $value = $arrayValue;
                    } else {
                        $value = $this->_sanitize($value, $filter);
                    }
                }
            }
            return $value;
        }

        /**
         * Apply a single filter value
         */
        if (is_array($value) && !$noRecursive) {
            $sanizitedValue = [];
            foreach ($value as $itemKey => $itemValue) {
                $sanizitedValue[$itemKey] = $this->_sanitize($itemValue, $filters);
            }
            return $sanizitedValue;
        }

        return $this->_sanitize($value, $filters);
    }

    /**
     * Internal sanitize wrapper to filter_var
     *
     * @param mixed $value
     * @param string $filter
     * @return mixed
     * @throws Exception
     */
    protected function _sanitize($value, $filter)
    {
        if (!is_string($filter)) {
            throw new Exception('Invalid parameter type.');
        }

        if (isset($this->_filters[$filter])) {
            $filterObject = $this->_filters[$filter];

            /**
             * If the filter is a closure we call it in the PHP userland
             */
            if ($filterObject instanceof \Closure || is_callable($filterObject)) {
                return call_user_func_array($filterObject, [$value]);
            }

            return $filterObject->filter($value);
        }

        switch ($filter) {
            case self::FILTER_EMAIL:
                /**
                 * The 'email' filter uses the filter extension
                 */
                return filter_var($value, constant("FILTER_SANITIZE_EMAIL"));

            case self::FILTER_INT;
                /**
                 * 'int' filter sanitizes a numeric input
                 */
                return filter_var($value, FILTER_SANITIZE_NUMBER_INT);

            case self::FILTER_INT_CAST:

                return intval($value);

            case self::FILTER_ABSINT:

                return abs(intval($value));

            case self::FILTER_STRING:

                return filter_var($value, FILTER_SANITIZE_STRING);

            case self::FILTER_FLOAT:
                /**
                 * The 'float' filter uses the filter extension
                 */
                return filter_var($value, FILTER_SANITIZE_NUMBER_FLOAT, ["flags" => FILTER_FLAG_ALLOW_FRACTION]);

            case self::FILTER_FLOAT_CAST:

                return doubleval($value);

            case self::FILTER_ALPHANUM:

                return preg_replace("/[^A-Za-z0-9]/", "", $value);

            case self::FILTER_TRIM:

                return trim($value);

            case self::FILTER_STRIPTAGS:

                return strip_tags($value);

            case self::FILTER_LOWER:

                if (function_exists("mb_strtolower")) {
                    /**
                     * 'lower' checks for the mbstring extension to make a correct lowercase transformation
                     */
                    return mb_strtolower($value);
                }
                return strtolower($value);

            case self::FILTER_UPPER:

                if (function_exists("mb_strtoupper")) {
                    /**
                     * 'upper' checks for the mbstring extension to make a correct lowercase transformation
                     */
                    return mb_strtoupper($value);
                }
                return strtoupper($value);
            
            default:
               throw new Exception("Sanitize filter '" . $filter . "' is not supported");
        }
    }

    /**
     * Return the user-defined filters in the instance
     *
     * @return array
     */
    public function getFilters()
    {
        return $this->_filters;
    }
}
