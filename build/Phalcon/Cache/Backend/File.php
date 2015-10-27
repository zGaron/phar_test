<?php
/**
 * File Cache Backend
 *
*/

namespace Phalcon\Cache\Backend;

use \Phalcon\Cache\Backend;
use \Phalcon\Cache\BackendInterface;
use \Phalcon\Cache\FrontendInterface;
use \Phalcon\Cache\Exception;
use \Phalcon\Text;

/**
 * Phalcon\Cache\Backend\File
 *
 * Allows to cache output fragments using a file backend
 *
 *<code>
 *  //Cache the file for 2 days
 *  $frontendOptions = array(
 *      'lifetime' => 172800
 *  );
 *
 *  //Create a output cache
 *  $frontCache = \Phalcon\Cache\Frontend\Output($frontendOptions);
 *
 *  //Set the cache directory
 *  $backendOptions = array(
 *      'cacheDir' => '../app/cache/'
 *  );
 *
 *  //Create the File backend
 *  $cache = new \Phalcon\Cache\Backend\File($frontCache, $backendOptions);
 *
 *  $content = $cache->start('my-cache');
 *  if ($content === null) {
 *      echo '<h1>', time(), '</h1>';
 *      $cache->save();
 *  } else {
 *      echo $content;
 *  }
 *</code>
 *
 */
class File extends Backend implements BackendInterface
{
    
    /**
     * Default to false for backwards compatibility
     *
     * @var boolean
     */
    private $_useSafeKey = false;

    /**
     * \Phalcon\Cache\Backend\File constructor
     *
     * @param \Phalcon\Cache\FrontendInterface $frontend
     * @param array $options
     * @throws Exception
     */
    public function __construct($frontend, $options = null)
    {
        if (!isset($options['cacheDir'])) {
            throw new Exception('Cache directory must be specified with the option cacheDir');
        }

        if (isset($options['safekey'])) {
            $safekey = $options['safekey'];
            if (!is_bool($safekey)) {
                throw new Exception("safekey option should be a boolean.");
            }

            $this->_useSafeKey = $safekey;
        }

        // added to avoid having unsafe filesystem characters in the prefix
        if (isset($options['prefix'])) {
            $prefix = $options['prefix'];
            if ($this->_useSafeKey && preg_match('/[^a-zA-Z0-9_.-]+/', $prefix)) {
                throw new Exception("FileCache prefix should only use alphanumeric characters.");
            }
        }

        parent::__construct($frontend, $options);
    }

    /**
     * Returns a cached content
     *
     * @param int|string $keyName
     * @param int|null $lifetime
     * @return mixed
     * @throws Exception
     */
    public function get($keyName, $lifetime = null)
    {

        $prefixedkey = $this->_prefix . $this->getkey($keyName);
        $this->_lastKey = $prefixedkey;

        if (!isset($this->_options['cacheDir'])) {
            throw new Exception('Unexpected inconsistency in options');
        } else {
            $cacheDir = $this->_options['cacheDir'];
        }

        $cacheFile = $cacheDir . $prefixedkey;

        if (file_exists($cacheFile)) {

            $frontend = $this->_frontend;

            /**
             * Take the lifetime from the frontend or read it from the set in start()
             */
            if (!$lifetime) {
                $lastLifetime = $this->_lastLifetime;
                if (!$lastLifetime) {
                    $ttl = (int) $frontend->getLifetime();
                } else {
                    $ttl = (int) $lastLifetime;
                }
            } else {
                $ttl = (int) $lifetime;
            }

            $modifiedTime = (int) filemtime($cacheFile);

            /**
             * Check if the file has expired
             * The content is only retrieved if the content has not expired
             */
            if ($modifiedTime + $ttl > time()) {

                /**
                 * Use file-get-contents to control that the openbase_dir can't be skipped
                 */
                $cachedContent = file_get_contents($cacheFile);
                if ($cachedContent === false) {
                    throw new Exception('Cache file ' . $cacheFile . ' could not be openend');
                }

                if (is_numeric($cachedContent)) {
                    return $cachedContent;
                } else {

                    /**
                     * Use the frontend to process the content of the cache
                     */
                    $ret = $frontend->afterRetrieve($cachedContent);
                    return $ret;
                }
            }
        }
    }

    /**
     * Stores cached content into the file backend and stops the frontend
     *
     * @param int|string|null $keyName
     * @param string|null $content
     * @param int|null $lifetime
     * @param boolean|null $stopBuffer
     * @throws Exception
     */
    public function save($keyName = null, $content = null, $lifetime = null, $stopBuffer = true)
    {
        
        if (!$keyName) {
            $lastKey = $this->_lastKey;
        } else {
            $lastKey = $this->_prefix . $this->getkey($keyName);
        }

        if (!$lastKey) {
            throw new Exception('The cache must be started first.');
        }

        $frontend = $this->_frontend;

        if (!isset($this->_options['cacheDir'])) {
            throw new Exception("Unexpected inconsistency in options");
        } else {
            $cacheDir = $this->_options['cacheDir'];
        }

        $cacheFile = $cacheDir . $lastKey;

        if (!$content) {
            $cachedContent = $frontend->getContent();
        } else {
            $cachedContent = $content;
        }

        $preparedContent = $frontend->beforeStore($cachedContent);

        /**
         * We use file_put_contents to respect open-base-dir directive
         */
        if (!is_numeric($cachedContent)) {
            $status = file_put_contents($cacheFile, $preparedContent);
        } else {
            $status = file_put_contents($cacheFile, $cachedContent);
        }

        if ($status === false) {
            throw new Exception('Cache file '. $cacheFile . ' could not be written');
        }

        $isBuffering = $frontend->isBuffering();

        if ($stopBuffer === true) {
            $frontend->stop();
        }

        if ($isBuffering === true) {
            echo $cachedContent;
        }

        $this->_started = false;
    }

    /**
     * Deletes a value from the cache by its key
     *
     * @param int|string $keyName
     * @return boolean
     * @throws Exception
     */
    public function delete($keyName)
    {
        if (!isset($this->_options['cacheDir'])) {
            throw new Exception('Unexpected inconsistency in options');
        } else {
            $cacheDir = $this->_options['cacheDir'];
        }

        $cacheFile = $cacheDir . $this->_prefix . $this->getkey($keyName);
        if (file_exists($cacheFile)) {
            return unlink($cacheFile);
        }

        return false;
    }

    /**
     * Query the existing cached keys
     *
     * @param string|null $prefix
     * @return array
     * @throws Exception
     */
    public function queryKeys($prefix = null)
    {
        
        $keys = [];

        if (!isset($this->_options['cacheDir'])) {
            throw new Exception('Unexpected inconsistency in options');
        } else {
            $cacheDir = $this->_options['cacheDir'];
        }

        /**
         * We use a directory iterator to traverse the cache dir directory
         */
        $iterator = new \DirectoryIterator($cacheDir);

        if ($prefix !== null) {

            //Prefix is set
            foreach ($iterator as $item) {
                if (!is_dir($item)) {
                    $key = $item->getFileName();
                    if (!Text::startsWith($key, $prefix)) {
                        continue;
                    }

                    $keys[] = $key;
                }
            }
        } else {

            //Without using a prefix
            foreach ($iterator as $item) {
                if (!is_dir($item)) {
                    $keys[] = $item->getFileName();
                }
            }
        }

        return $keys;
    }

    /**
     * Checks if cache exists and it isn't expired
     *
     * @param string|null $keyName
     * @param int|null $lifetime
     * @return boolean
     */
    public function exists($keyName = null, $lifetime = null)
    {
        
        if (!$keyName) {
            $lastKey = $this->_lastKey;
        } else {
            $prefix = $this->_prefix;
            $lastKey = $prefix . $this->getkey($keyName);
        }

        if ($lastKey) {

            $cacheFile = $this->_options['cacheDir'] . $lastKey;

            if (file_exists($cacheFile)) {

                /**
                 * Check if the file has expired
                 */
                if (!$lifetime) {
                    $ttl = (int) $this->_frontend->getLifetime();
                } else {
                    $ttl = (int) $lifetime;
                }

                if (filemtime($cacheFile) + $ttl > time()) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Increment of a given key, by number $value
     *
     * @param  string|int $keyName
     * @param  int value
     * @return mixed
     */
    public function Increment($keyName, $value = 1)
    {

        $prefixedkey = $this->_prefix . $this->getkey($keyName);
        $this->_lastKey = $prefixedkey;
        $cacheFile = $this->_options['cacheDir'] . $prefixedkey;

        if (file_exists($cacheFile)) {

            $frontend = $this->_frontend;

            /**
             * Check if the file has expired
             */
            $timestamp = time();

            /**
             * Take the lifetime from the frontend or read it from the set in start()
             */
            $lifetime = $this->_lastLifetime;
            if (!$lifetime) {
                $ttl = $frontend->getLifetime();
            } else {
                $ttl = $lifetime;
            }

            /**
             * The content is only retrieved if the content has not expired
             */
            if (filemtime($cacheFile) + $ttl > $timestamp) {
                
                /**
                 * Use file-get-contents to control that the openbase_dir can't be skipped
                 */
                $cachedContent = file_get_contents($cacheFile);

                if ($cachedContent === false) {
                    throw new Exception('Cache file ' . $cacheFile . ' could not be opened');
                }

                if (is_numeric($cachedContent)) {

                    $result = $cachedContent + $value;
                    if (file_put_contents($cacheFile, $result) === false) {
                        throw new Exception("Cache directory can't be written");
                    }

                    return $result;
                }
            }            
        }
    }

     /**
     * Decrement of a given key, by number $value
     *
     * @param  string|int keyName
     * @param  int value
     * @return mixed
     */
    public function decrement($keyName, $value = 1)
    {

        $prefixedkey = $this->_prefix . $this->getkey($keyName);
        $this->_lastKey = $prefixedkey;
        $cacheFile = $this->_options['cacheDir'] . $prefixedkey;

        if (file_exists($cacheFile)) {

            $frontend = $this->_frontend;

            /**
             * Check if the file has expired
             */
            $timestamp = time();

            /**
             * Take the lifetime from the frontend or read it from the set in start()
             */
            $lifetime = $this->_lastLifetime;
            if (!$lifetime) {
                $ttl = $frontend->getLifetime();
            } else {
                $ttl = $lifetime;
            }

            /**
             * The content is only retrieved if the content has not expired
             */
            if (filemtime($cacheFile) + $ttl > $timestamp) {
                
                /**
                 * Use file-get-contents to control that the openbase_dir can't be skipped
                 */
                $cachedContent = file_get_contents($cacheFile);

                if ($cachedContent === false) {
                    throw new Exception('Cache file ' . $cacheFile . ' could not be opened');
                }

                if (is_numeric($cachedContent)) {

                    $result = $cachedContent - $value;
                    if (file_put_contents($cacheFile, $result) === false) {
                        throw new Exception("Cache directory can't be written");
                    }

                    return $result;
                }
            }            
        }
    }

    /**
     * Immediately invalidates all existing items.
     *
     * @return boolean
     */
    public function flush()
    {

        //$prefix = $this->_prefix;
        $prefix = 'my-cache';

        if (!isset($this->_options['cacheDir'])) {
            throw new Exception('Unexpected inconsistency in options');
        } else {
            $cacheDir = $this->_options['cacheDir'];
        }

        $iterator = new \DirectoryIterator($cacheDir);

        if ($prefix !== null) {

            //Prefix is set
            foreach ($iterator as $item) {
                if (!is_dir($item)) {
                    $key = $item->getFileName();
                    $cacheFile = $item->getPathName();
                    if (Text::startsWith($key, $prefix)) {
                        if (!unlink($cacheFile)) {
                            return false;
                        }
                    }
                }
            }
        } else {

            //Without using a prefix
            foreach ($iterator as $item) {
                if (!is_dir($item)) {
                    $cacheFile = $item->getPathName();
                    if (!unlink($cacheFile)) {
                        return false;
                    }
                }
            }
        }

        return true;
    }


    /**
     * Return a file-system safe identifier for a given key
     *
     * @param mixed key
     * @return string
     */
    public function getkey($key)
    {
        if ($this->_useSafeKey === true) {
            return md5($key);
        }

        return $key;
    }

    /**
     * Set whether to use the safekey or not
     *
     * @return this
     */
    public function useSafeKey($useSafeKey)
    {
        if (!is_bool($useSafeKey)) {
            throw new Exception('The useSafeKey must be a boolean');
        }

        $this->_useSafeKey = $useSafeKey;

        return $this;
    }
}