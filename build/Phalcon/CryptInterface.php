<?php
/**
 * Crypt Interface
 *
*/
namespace Phalcon;

/**
 * Phalcon\CryptInterface initializer
 *
 * Interface for Phalcon\Crypt
 */
interface CryptInterface
{
    /**
     * Sets the cipher algorithm
     *
     * @param string! $cipher
     * @return \Phalcon\CryptInterface
     */
    public function setCipher($cipher);

    /**
     * Returns the current cipher
     *
     * @return string
     */
    public function getCipher();

    /**
     * Sets the encrypt/decrypt mode
     *
     * @param string! $mode
     * @return \Phalcon\CryptInterface
     */
    public function setMode($mode);

    /**
     * Returns the current encryption mode
     *
     * @return string
     */
    public function getMode();

    /**
     * Sets the encryption key
     *
     * @param string! $key
     * @return \Phalcon\CryptInterface
     */
    public function setKey($key);

    /**
     * Returns the encryption key
     *
     * @return string
     */
    public function getKey();

    /**
     * Encrypts a text
     *
     * @param string! $text
     * @param string|null $key
     * @return string
     */
    public function encrypt($text, $key = null);

    /**
     * Decrypts a text
     *
     * @param string! $text
     * @param string|null $key
     * @return string
     */
    public function decrypt($text, $key = null);

    /**
     * Encrypts a text returning the result as a base64 string
     *
     * @param string! $text
     * @param string|null $key
     * @return string
     */
    public function encryptBase64($text, $key = null);

    /**
     * Decrypt a text that is coded as a base64 string
     *
     * @param string! $text
     * @param string|null $key
     * @return string
     */
    public function decryptBase64($text, $key = null);

    /**
     * Returns a list of available cyphers
     *
     * @return array
     */
    public function getAvailableCiphers();

    /**
     * Returns a list of available modes
     *
     * @return array
     */
    public function getAvailableModes();
}
