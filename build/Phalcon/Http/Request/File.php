<?php
/**
 * File
 *
*/
namespace Phalcon\Http\Request;

use \Phalcon\Http\Request\FileInterface;
use \Phalcon\Http\Request\Exception;

/**
 * Phalcon\Http\Request\File
 *
 * Provides OO wrappers to the $_FILES superglobal
 *
 *<code>
 *  class PostsController extends \Phalcon\Mvc\Controller
 *  {
 *
 *      public function uploadAction()
 *      {
 *          //Check if the user has uploaded files
 *          if ($this->request->hasFiles()) {
 *              //Print the real file names and their sizes
 *              foreach ($this->request->getUploadedFiles() as $file){
 *                  echo $file->getName(), " ", $file->getSize(), "\n";
 *              }
 *          }
 *      }
 *
 *  }
 *</code>
 *
 */
class File implements FileInterface
{
    /**
     * Name
     *
     * @var null|string
     * @access protected
    */
    protected $_name;

    /**
     * Temp
     *
     * @var null|string
     * @access protected
    */
    protected $_tmp;

    /**
     * Size
     *
     * @var null|int
     * @access protected
    */
    protected $_size;

    /**
     * Type
     *
     * @var null|string
     * @access protected
    */
    protected $_type;

    /**
     * RealType
     *
     * @var null|string
     * @access protected
    */
    protected $_realType;

    /**
     * Error
     *
     * @var null|array
     * @access protected
    */
    protected $_error;

    /**
     * Key
     *
     * @var null|string
     * @access protected
    */
    protected $_key;

    /**
     * Key
     *
     * @var null|string
     * @access protected
    */
    protected $_extension;

    /**
     * \Phalcon\Http\Request\File constructor
     *
     * @param array! $file
     * @param string|null $key
     * @throws Exception
     */
    public function __construct($file, $key = null)
    {
        if (!is_array($file)) {
            throw new Exception("Phalcon\\Http\\Request\\File requires a valid uploaded file");
        }

        if (isset($file['name'])) {
            $name = $file['name'];
            $this->_name = $name;

            if (defined('PATHINFO_EXTENSION')) {
                $this->_extension = pathinfo($name, PATHINFO_EXTENSION);
            }
        }

        if (isset($file['tmp_name'])) {
            $this->_tmp = (string)$file['tmp_name'];
        }

        if (isset($file['size'])) {
            $this->_size = (int)$file['size'];
        }

        if (isset($file['type'])) {
            $this->_type = (string)$file['type'];
        }

        if (isset($file['error'])) {
            $this->_error = $file['error'];
        }

        if ($key) {
            $this->_key = $key;
        }
    }

    /**
     * Returns the file size of the uploaded file
     *
     * @return int|null
     */
    public function getSize()
    {
        return $this->_size;
    }

    /**
     * Returns the real name of the uploaded file
     *
     * @return string|null
     */
    public function getName()
    {
        return $this->_name;
    }

    /**
     * Returns the temporal name of the uploaded file
     *
     * @return string|null
     */
    public function getTempName()
    {
        return $this->_tmp;
    }

    /**
     * Returns the mime type reported by the browser
     * This mime type is not completely secure, use getRealType() instead
     *
     * @return string|null
     */
    public function getType()
    {
        return $this->_type;
    }

    /**
     * Gets the real mime type of the upload file using finfo
     *
     * @return null
     */
    public function getRealType()
    {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        if (!is_resource($finfo)) {
            return '';
        }

        $mime = finfo_file($finfo, $this->_tmp);
        finfo_close($finfo);

        return $mime;
    }

    /**
     * Returns the error code
     *
     * @return string|null
     */
    public function getError()
    {
        return $this->_error;
    }

    /**
     * Returns the file key
     *
     * @return string|null
     */
    public function getKey()
    {
        return $this->_key;
    }

    /**
     * Returns the extension
     *
     * @return string|null
     */
    public function getExtension()
    {
        return $this->_extension;
    }

    /**
     * Checks whether the file has been uploaded via Post.
     *
     * @return boolean
    */
    public function isUploadedFile()
    {
        $tmp = $this->getTempName();
        return is_string($tmp) && is_uploaded_file($tmp);
    }

    /**
     * Moves the temporary file to a destination within the application
     *
     * @param string! $destination
     * @return boolean
     * @throws Exception
     */
    public function moveTo($destination)
    {
        //@note no path check
        if (!is_string($destination)) {
            throw new Exception('Invalid parameter type.');
        }

        //@note _tmp can be NULL
        return move_uploaded_file($this->_tmp, $destination);
    }
}
