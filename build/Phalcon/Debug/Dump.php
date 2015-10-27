<?php

namespace Phalcon\Debug;

/**
 * Phalcon\Debug\Dump
 *
 * Dumps information about a variable(s)
 *
 * <code>
 *    $foo = 123;
 *    echo (new \Phalcon\Debug\Dump())->variable($foo, "foo");
 *</code>
 *
 * <code>
 *    $foo = "string";
 *    $bar = ["key" => "value"];
 *    $baz = new stdClass();
 *    echo (new \Phalcon\Debug\Dump())->variables($foo, $bar, $baz);
 *</code>
 */
class Dump
{
	protected $_detailed = false;

	protected $_methods = null;

	protected $_styles;

	/**
	 * Phalcon\Debug\Dump constructor
	 *
	 * @param boolean detailed debug object's private and protected properties
	 * @param array $styles
	 * @param boolean $detailed
	 */
	public function __construct($styles = null, $detailed = false)
	{
		if ($styles && !is_array($styles)) {
		 	throw new Exception("The styles must be an array");
			
		}

		$this->setStyles($styles);
		$this->_methods = [];
		$this->_detailed = $detailed;
	}

	public function setDetailed($detailed)
	{
		$this->_detailed = $detailed;
	}

	public function getDetailed()
	{
		return $this->_detailed;
	}

	/**
	 * Alias of variables() method
	 *
	 * @param mixed variable
	 * @param ...
	 * @return  string
	 */
	public function all()
	{
		return call_user_func_array([$this, "variables"], func_get_args());
	}

	/**
	 * Get style for type
	 * @param string $type
	 * @return  string
	 */
	protected function getStyle($type)
	{
		if (isset($this->_styles[$type])) {
			return $this->_styles[$type];
		} else {
			return "color:gray";
		}
	}

	/**
	 * Set styles for vars type
	 * @return  array
	 */
	public function setStyles($styles = null)
	{
		if (is_null($styles)) {
			$styles = [];
		}
		if (!is_array($styles)) {
			throw new Exception("The styles must be an array");			
		}

		$defaultStyles = [
			"pre" => "background-color:#f3f3f3; font-size:11px; padding:10px; border:1px solid #ccc; text-align:left; color:#333",
		 	"arr" => "color:red",
		 	"bool" =>  "color:green",
		 	"float" => "color:fuchsia",
		 	"int" => "color:blue",
		 	"null" => "color:black",
		 	"num" => "color:navy",
		 	"obj" => "color:purple",
		 	"other" => "color:maroon",
		 	"res" => "color:lime",
		 	"str" => "color:teal"
		];

		$this->_styles = array_merge($defaultStyles, $styles);
		return $this->_styles;
	}

	/**
	 * Alias of variable() method
	 * @param var $variable
	 * @param  string $name
	 * @return  string
	 */
	public function one($variable, $name = null)
	{
		return $this->variable($variable, $name);
	}

	/**
	 * Prepare an HTML string of information about a single variable.
	 * @param  var $variable
	 * @param  string $name
	 * @param  int $tab
	 * @return string
	 */
	protected function output($variable, $name = null, $tab = 1)
	{
		$space = '  ';
		$output = '';

		if ($name) {
			$output = $name . ' ';
		}

		if (is_array($variable)) {
			$output .= strtr("<b style =':style'>Array</b> (<span style =':style'>:count</span>) (\n", [":style" => $this->getStyle("arr"), ":count" => count($variable)]);
			
			foreach ($variable as $key => $value) {
				$output .= str_repeat($space, $tab) . strtr("[<span style=':style'>:key</span>] => ", [":style" => $this->getStyle("arr"), ":key" => $key]);
			
				if ($tab == 1 && $name != "" && !is_int($key) && $name == $key) {
					continue;
				} else {
					$output .= $this->output($value, "", $tab + 1) . "\n";
				}
			}
			return $output . str_repeat($space, $tab - 1) . ")";
		}

		if (is_object($variable)) {
			$output .= strtr("<b style=':style'>Object</b> :class", [":style" => $this->getStyle("obj"), ":class" => get_class($variable)]);
			
			if (get_parent_class($variable)) {
				$output .= strtr(" <b style=':style'>extends</b> :parent", [":style" => $this->getStyle("obj"), ":parent" => get_parent_class($variable)]);
			}
			$output .= ' (\n';

			if (!$this->_detailed) {
				foreach (get_object_vars($variable) as $key => $value) {
					$output .= str_repeat($space, $tab) . strtr("-><span style=':style'>:key</span> (<span style=':style'>:type</span>) = ", [":style" => $this->getStyle("obj"), ":key" => $key, ":type" => "public"]);
					$output .= $this->output($value, "", $tab + 1) . '\n';
				}
			} else {
				do {
					
					$attr = each($variable);

					if (!$attr) {
						continue;
					}

					$key = $attr['key'];
					$value = $attr['value'];

					if (!$key) {
						continue;
					}
					$key = explode(chr(ord("\x00")), $key);
					$type = 'public';

					if (isset($key[1])) {
						$type = "private";

						if ($key[1] == '*') {
							$type = "protected";
						}
					}

					$output .= str_repeat($space, $tab) . strtr("-><span style=':style'>:key</span> (<span style=':style'>:type</span>) = ", [":style"=> $this->getStyle("obj"), ":key" => end($key), ":type" => $type]);
					$output .= $this->output($value, "", $tab + 1) . "\n";

				} while ($sttr);
			}

			$attr = get_class_methods($variable);
			$output .= str_repeat($space, $tab) . strtr(":class <b style=':style'>methods</b>: (<span style=':style'>:count</span>) (\n", [":style" => $this->getStyle("obj"), ":class" => get_class($variable), ":count" => count($attr)]);
		
			if (in_array(get_class($variable), $this->_methods)) {
				$output .= str_repeat($space, $tab) . '[already listed\n';
			} else {
				foreach ($attr as $value) {
					$this->_methods[] = get_class($variable);

					if ($value == '__construct') {
						$output .= str_repeat($space, $tab + 1) . strtr("-><span style=':style'>:method</span>(); [<b style=':style'>constructor</b>]\n", [":style" => $this->getStyle("obj"), ":method" => $value]);
					} else {
						$output .= str_repeat($space, $tab + 1) . strtr("-><span style=':style'>:method</span>();\n", [":style" => $this->getStyle("obj"), ":method" => $value]);
					}
				}
				$output .= str_repeat($space, $tab) . ")\n";
			}

			return $output . str_repeat($space, $tab - 1) . ")";
		}

		if (is_int($variable)) {
			return $output . strtr("<b style=':style'>Integer</b> (<span style=':style'>:var</span>)", [":style" => $this->getStyle("int"), ":var" => $variable]);
		}

		if (is_float($variable)) {
			return $output . strtr("<b style=':style'>Float</b> (<span style=':style'>:var</span>)", [":style" => $this->getStyle("float"), ":var" => $variable]);
		}

		if (is_string($variable)) {
			return $output . strtr("<b style=':style'>String</b> (<span style=':style'>:length</span>) \"<span style=':style'>:var</span>\"", [":style" => $this->getStyle("str"), ":length" => strlen($variable), ":var" => nl2br(htmlentities($variable, ENT_IGNORE, "utf-8"))]);
		}

		if (is_bool($variable)) {
			return $output . strtr("<b style=':style'>Boolean</b> (<span style=':style'>:var</span>)", [":style" => $this->getStyle("bool"), ":var" => ($variable ? "TRUE" : "FALSE")]);
		}

		if (is_null($variable)) {
			return $output . strtr("<b style=':style'>NULL</b>", [":style" => $this->getStyle("null")]);
		}

		return $output . strtr("(<span style=':style'>:var</span>)", [":style" => $this->getStyle("other"), ":var" => $variable]);
	}

	/**
	 * Returns an HTML string of information about a single variable.
	 *
	 * <code>
	 *    echo (new \Phalcon\Debug\Dump())->variable($foo, "foo");
	 * </code>
	 * @param  var $variable
	 * @param  string $name
	 * @return  string
	 */
	public function variable($variable, $name = null)
	{
		return strtr("<pre style=':style'>:output</pre>", [
			":style" => $this->getStyle("pre"),
			":output" => $this->output($variable, $name)
		]);
	}

	/**
	 * Returns an HTML string of debugging information about any number of
	 * variables, each wrapped in a "pre" tag.
	 *
	 * <code>
	 *    $foo = "string";
	 *    $bar = ["key" => "value"];
	 *    $baz = new stdClass();
	 *    echo (new \Phalcon\Debug\Dump())->variables($foo, $bar, $baz);
	 *</code>
	 *
	 * @param mixed variable
	 * @param ...
	 * @return  string
	 */
	public function variables()
	{
		$output = '';
		foreach (func_get_args() as $key => $value) {
			$output .= $this->one($value, "var " . $key);
		}

		return $output;
	}

	/**
	 * Returns an JSON string of information about a single variable.
	 *
	 * <code>
	 *    $foo = ["key" => "value"];
	 *    echo (new \Phalcon\Debug\Dump())->toJson($foo);
	 *    $foo = new stdClass();
	 *    $foo->bar = 'buz';
	 *    echo (new \Phalcon\Debug\Dump())->toJson($foo);
	 * </code>
	 *
	 * @param var variable
	 * @return  string
	 */
	public function toJson($variable)
	{
		return json_encode($variable, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
	}
}