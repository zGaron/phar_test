<?php
/**
 * Text
 *
*/
namespace Phalcon;

use \Phalcon\Exception;

/**
 * Phalcon\Text
 *
 * Provides utilities to work with texts
 *
 */
abstract class Text
{
    /**
     * Random: Alphanumeric
     *
     * @var int
    */
    const RANDOM_ALNUM = 0;

    /**
     * Random: Alpha
     *
     * @var int
    */
    const RANDOM_ALPHA = 1;

    /**
     * Random: Hexdecimal
     *
     * @var int
    */
    const RANDOM_HEXDEC = 2;

    /**
     * Random: Numeric
     *
     * @var int
    */
    const RANDOM_NUMERIC = 3;

    /**
     * Random: No Zero
     *
     * @var int
    */
    const RANDOM_NOZERO = 4;

    /**
     * Converts strings to camelize style
     *
     *<code>
     *  echo \Phalcon\Text::camelize('coco_bongo'); //CocoBongo
     *</code>
     *
     * @param string $str
     * @return string
     * @throws Exception
     */
    public static function camelize($str)
    {
        if (!is_string($str)) {
            //@warning The Exception is an E_ERROR in the original API
            throw new Exception('Invalid arguments supplied for camelize()');
        }

        $l = strlen($str);
        $camelized = '';

        for ($i = 0; $i < $l; ++$i) {
            if ($i === 0 || $str[$i] === '-' || $str[$i] === '_') {
                if ($str[$i] === '-' || $str[$i] === '_') {
                    ++$i;
                }

                if (isset($str[$i]) === true) {
                    $camelized .= strtoupper($str[$i]);
                } else {
                    //Prevent pointer overflow, c emulation of strtoupper
                    $camelized .= chr(0);
                }
            } else {
                $camelized .= strtolower($str[$i]);
            }
        }

        return $camelized;
    }

    /**
     * Uncamelize strings which are camelized
     *
     *<code>
     *  echo \Phalcon\Text::uncamelize('CocoBongo'); //coco_bongo
     *</code>
     *
     * @param string $str
     * @return string
     * @throws Exception
     */
    public static function uncamelize($str)
    {
        if (!is_string($str)) {
            //@warning The Exception is an E_ERROR in the original API
            //@note changed "camelize" to "uncamelize"
            throw new Exception('Invalid arguments supplied for uncamelize()');
        }

        $l = strlen($str);
        $uncamelized = '';

        for ($i = 0; $i < $l; $i++) {
            $ch = ord($str[$i]);

            if ($ch === 0) {
                break;
            }

            if ($ch >= 65 && $ch <= 90) {
                if ($i > 0) {
                    $uncamelized .= '_';
                }
                $uncamelized .= chr($ch + 32);
            } else {
                $uncamelized .= $str[$i];
            }
        }

        return $uncamelized;
    }

    /**
     * Adds a number to a string or increment that number if it already is defined
     *
     *<code>
     *  echo \Phalcon\Text::increment("a"); // "a_1"
     *  echo \Phalcon\Text::increment("a_1"); // "a_2"
     *</code>
     *
     * @param string $str
     * @param string|null $separator
     * @return string
     * @throws Exception
     */
    public static function increment($str, $separator = '_')
    {
        if (!is_string($str)) {
            throw new Exception('Invalid parameter type.');
        }

        if (!is_string($separator)) {
            throw new Exception('Invalid parameter type.');
        }

        $parts = explode($separator, $str);

        if (isset($parts[1])) {
            $number = (int)$parts[1];
            $number++;
        } else {
            $number = 1;
        }

        return $parts[0] . $separator . $number;
    }

    /**
     * Generates a random string based on the given type. Type is one of the RANDOM_* constants
     *
     *<code>
     *  echo \Phalcon\Text::random(Phalcon\Text::RANDOM_ALNUM); //"aloiwkqz"
     *</code>
     *
     * @param int $type
     * @param int|null $length
     * @return string
     * @throws Exception
     */
    public static function random($type = 0, $length = 8)
    {
    	if (!is_int($type) || $type < self::RANDOM_ALNUM ||
            $type > self::RANDOM_NOZERO) {
            //@warning The function returns NULL in the original API
            throw new Exception('Invalid parameter type.');
        }

        if (!is_int($length)) {
            //@warning The function returns NULL in the original API
            throw new Exception('Invalid parameter type.');
        }

    	$str = '';

    	switch ($type) {
    		
    		case Text::RANDOM_ALPHA:
				$pool = array_merge(range("a", "z"), range("A", "Z"));
				break;

			case Text::RANDOM_HEXDEC:
				$pool = array_merge(range(0, 9), range("a", "f"));
				break;

			case Text::RANDOM_NUMERIC:
				$pool = range(0, 9);
				break;

			case Text::RANDOM_NOZERO:
				$pool = range(1, 9);
				break;

			default:
				// Default type \Phalcon\Text::RANDOM_ALNUM
				$pool = array_merge(range(0, 9), range("a", "z"), range("A", "Z"));
				break;
    	}

    	$end = count($pool) - 1;

    	while (strlen($str) < $length) {
    		$str .= $pool[mt_rand(0, $end)];
    	}

    	return $str;
    }

    /**
     * Check if a string starts with a given string
     *
     *<code>
	 *  echo Phalcon\Text::startsWith("Hello", "He"); // true
	 *  echo Phalcon\Text::startsWith("Hello", "he", false); // false
	 *  echo Phalcon\Text::startsWith("Hello", "he"); // true
     *</code>
     *
     * @param string $str
     * @param string $start
     * @param boolean|null $ignoreCase
     * @return boolean
     * @throws Exception
     */
    public static function startsWith($str, $start, $ignoreCase = true)
    {
        if (!is_string($str)|| !is_string($start)) {
            throw new Exception('Invalid parameter type.');
        }
        
        if ($ignoreCase === true) {
            return (stripos($str, $start) === 0 ? true : false);
        } else {
            return (strpos($str, $start) === 0 ? true : false);
        }
    }

    /**
     * Check if a string ends with a given string
     *
     *<code>
     *  echo Phalcon\Text::endsWith("Hello", "llo"); // true
	 *  echo Phalcon\Text::endsWith("Hello", "LLO", false); // false
	 *  echo Phalcon\Text::endsWith("Hello", "LLO"); // true
     *</code>
     *
     * @param string $str
     * @param string $end
     * @param boolean|null $ignoreCase
     * @return boolean
     * @throws Exception
     */
    public static function endsWith($str, $end, $ignoreCase = true)
    {
        if (!is_string($str)|| !is_string($end)) {
            throw new Exception('Invalid parameter type.');
        }

        $g = strlen($str) - strlen($end);
        if ($ignoreCase === true) {
            return (strripos($str, $end) === $g ? true : false);
        } else {
            return (strrpos($str, $end) === $g ? true : false);
        }
    }

    /**
     * Lowercases a string, this function makes use of the mbstring extension if available
     *
     * <code>
	 *    echo Phalcon\Text::lower("HELLO"); // hello
	 * </code>
	 * 
     * @param string! $str
     * @param string $encoding
     * @return string
     * @throws Exception
     */
    public static function lower($str, $encoding = "UTF-8")
    {
        if (!is_string($str)) {
            throw new Exception('Invalid parameter type.');
        }

        if (function_exists('mb_strtolower')) {
            return mb_strtolower($str, $encoding);
        }

        return strtolower($str);
    }

    /**
     * Uppercases a string, this function makes use of the mbstring extension if available
     *
     * <code>
	 *    echo Phalcon\Text::upper("hello"); // HELLO
	 * </code>
     * @param string! $str
     * @return string
     * @throws Exception
     */
    public static function upper($str, $encoding = "UTF-8")
    {
        if (!is_string($str)) {
            throw new Exception('Invalid parameter type.');
        }

        if (function_exists('mb_strtoupper')) {
            return mb_strtoupper($str, $encoding);
        }

        return strtoupper($str);
    }

    /**
	 * Reduces multiple slashes in a string to single slashes
	 *
	 * <code>
	 *    echo Phalcon\Text::reduceSlashes("foo//bar/baz"); // foo/bar/baz
	 *    echo Phalcon\Text::reduceSlashes("http://foo.bar///baz/buz"); // http://foo.bar/baz/buz
	 * </code>
	 *
	 * @param  string $str
	 * @return  string
	 */
	public static function reduceSlashes($str)
	{
		return preg_replace("#(?<!:)//+#", "/", $str);
	}

	/**
	 * Concatenates strings using the separator only once without duplication in places concatenation
	 *
	 * <code>
	 *    $str = Phalcon\Text::concat("/", "/tmp/", "/folder_1/", "/folder_2", "folder_3/");
	 *    echo $str; // /tmp/folder_1/folder_2/folder_3/
	 * </code>
	 *
	 * @param string separator
	 * @param string a
	 * @param string b
	 * @param string ...N
	 * @return string
	 */
	public static function concat()
	{
		$separator = func_get_arg(0);
		$a = func_get_arg(1);
		$b = func_get_arg(2);

		if (func_num_args() > 3) {
			foreach (array_slice(func_get_args(), 3) as $c) {
				$b = rtrim($b, $separator) . $separator . ltrim($c, $separator);
			}
		}

		return rtrim($a , $separator) . $separator . ltrim($b, $separator);
	}

}
