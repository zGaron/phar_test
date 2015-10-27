<?php
/**
 * PHP
 *
*/

namespace Phalcon\Mvc\View\Engine;

use \Phalcon\Mvc\View\Engine;
use \Phalcon\Mvc\View\EngineInterface;
use \Phalcon\Mvc\View\Exception;

/**
 * Phalcon\Mvc\View\Engine\Php
 *
 * Adapter to use PHP itself as templating engine
 */
class Php extends Engine implements EngineInterface
{
    
    /**
     * Renders a view using the template engine
     *
     * @param string $path
     * @param mixed $params
     * @param boolean $mustClean
     * @throws Exception
     */
    public function render($path, $params, $mustClean = false)
    {
        if (!is_string($path)) {
            throw new Exception('Invalid parameter type.');
        }

        if (!is_bool($mustClean)) {
            throw new Exception('Invalid parameter type.');
        }

        if ($mustClean === true) {
            ob_clean();
        }

        /**
         * Create the variables in local symbol table
         */
        if (is_array($params) === true) {
            foreach ($params as $key => $value) {
                ${$key} = $value;
            }
        }

        /**
         * Require the file
         */
        require($path);

        if ($mustClean === true) {
            $this->_view->setContent(ob_get_contents());
        }
    }
}
