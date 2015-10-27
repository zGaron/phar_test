<?php
/**
 * User Filter Interface
 *
*/
namespace Phalcon\Filter;

/**
 * Phalcon\Filter\UserFilterInterface initializer
 *
 * Interface for Phalcon\Filter user-filters
 */
interface UserFilterInterface
{
    /**
     * Filters a value
     *
     * @param mixed $value
     * @return mixed
     */
    public function filter($value);
}
