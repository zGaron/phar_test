<?php
/**
 * Version
 *
*/
namespace Phalcon;

/**
 * Phalcon\Version
 *
 * This class allows to get the installed version of the framework
 *
 */
class Version
{
    
    /**
     * The constant referencing the major version. Returns 0
     * <code>
     * echo Phalcon\Version::getPart(Phalcon\Version::VERSION_MAJOR);
     * </code>
     */
    const VERSION_MAJOR = 0;

    /**
     * The constant referencing the major version. Returns 1
     * <code>
     * echo Phalcon\Version::getPart(Phalcon\Version::VERSION_MEDIUM);
     * </code>
     */
    const VERSION_MEDIUM = 1;

    /**
     * The constant referencing the major version. Returns 2
     * <code>
     * echo Phalcon\Version::getPart(Phalcon\Version::VERSION_MINOR);
     * </code>
     */
    const VERSION_MINOR = 2;

    /**
     * The constant referencing the major version. Returns 3
     * <code>
     * echo Phalcon\Version::getPart(Phalcon\Version::VERSION_SPECIAL);
     * </code>
     */
    const VERSION_SPECIAL = 3;

    /**
     * The constant referencing the major version. Returns 4
     * <code>
     * echo Phalcon\Version::getPart(Phalcon\Version::VERSION_SPECIAL_NUMBER);
     * </code>
     */
    const VERSION_SPECIAL_NUMBER = 4;
    /**
     * Area where the version number is set. The format is as follows:
     * ABBCCDE
     *
     * A - Major version
     * B - Med version (two digits)
     * C - Min version (two digits)
     * D - Special release: 1 = Alpha, 2 = Beta, 3 = RC, 4 = Stable
     * E - Special release version i.e. RC1, Beta2 etc.
     *
     * @return array
     */
    protected static function _getVersion()
    {
        return array(0, 0, 1, 1, 1);
    }

    /**
     * Translates a number to a special release
     *
     * If Special release = 1 this function will return ALPHA
     *
     * @return  array
     */
    protected final static function _getSpecial($special)
    {
        $suffix = '';
        switch ($special) {
            case 1:
                $suffix = 'ALPHA';
                break;
            case 2:
                $suffix = 'BETA';
                break;
            case 3:
                $suffix = 'RC';
                break;                        
        }

        return $suffix;
    }
    
    /**
     * Returns the active version (string)
     *
     * <code>
     * echo \Phalcon\Version::get();
     * </code>
     *
     * @return string
     */
    public static function get()
    {

        $version = static::_getVersion();

        $major = $version[self::VERSION_MAJOR];
        $medium = $version[self::VERSION_MEDIUM];
        $minor = $version[self::VERSION_MINOR];
        $special = $version[self::VERSION_SPECIAL];
        $specialNumber = $version[self::VERSION_SPECIAL_NUMBER];

        $result = $major . '.' . $medium . '.' .$minor . ' ';
        $suffix = static::_getSpecial($special);

        if (!empty($suffix)) {
            $result .= $suffix . ' ' . $specialNumber;
        }

        return trim($result);
    }

    /**
     * Returns the numeric active version
     *
     * <code>
     * echo \Phalcon\Version::getId();
     * </code>
     *
     * @return string
     */
    public static function getId()
    {
        $version = static::_getVersion();

        $major = $version[self::VERSION_MAJOR];
        $medium = $version[self::VERSION_MEDIUM];
        $minor = $version[self::VERSION_MINOR];
        $special = $version[self::VERSION_SPECIAL];
        $specialNumber = $version[self::VERSION_SPECIAL_NUMBER];

        return $major . sprintf("%02s", $medium) . sprintf("%02s", $minor) . $special . $specialNumber;
    }

    /**
     * Returns a specific part of the version. If the wrong parameter is passed
     * it will return the full version
     *
     * <code>
     * echo Phalcon\Version::getPart(Phalcon\Version::VERSION_MAJOR);
     * </code>
     *
     * @return  string
     */
    public static function getPart($part)
    {
        $version = static::_getVersion();

        switch ($part) {
            
            case self::VERSION_MAJOR:
            case self::VERSION_MEDIUM:
            case self::VERSION_MINOR:
            case self::VERSION_SPECIAL_NUMBER:
                $result = $version[$part];
                break;
            
            case self::VERSION_SPECIAL:
                $result = ststic::_getSpecial($version[self::VERSION_SPECIAL]);
                break;

            default:
                $result = ststci::get();
                break;
        }

        return $result;
    }
}
