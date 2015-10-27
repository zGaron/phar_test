<?php
/**
 * Security Exception
 *
*/

namespace Phalcon\Security;

/**
 * Phalcon\Security\Random
 *
 * Secure random number generator class.
 *
 * Provides secure random number generator which is suitable for generating
 * session key in HTTP cookies, etc.
 *
 * It supports following secure random number generators:
 *
 * - libsodium
 * - openssl
 * - /dev/urandom
 *
 *<code>
 *  $random = new \Phalcon\Security\Random();
 *
 *  // Random binary string
 *  $bytes = $random->bytes();
 *
 *  // Random hex string
 *  echo $random->hex(10); // a29f470508d5ccb8e289
 *  echo $random->hex(10); // 533c2f08d5eee750e64a
 *  echo $random->hex(11); // f362ef96cb9ffef150c9cd
 *  echo $random->hex(12); // 95469d667475125208be45c4
 *  echo $random->hex(13); // 05475e8af4a34f8f743ab48761
 *
 *  // Random base64 string
 *  echo $random->base64(12); // XfIN81jGGuKkcE1E
 *  echo $random->base64(12); // 3rcq39QzGK9fUqh8
 *  echo $random->base64();   // DRcfbngL/iOo9hGGvy1TcQ==
 *  echo $random->base64(16); // SvdhPcIHDZFad838Bb0Swg==
 *
 *  // Random URL-safe base64 string
 *  echo $random->base64Safe();           // PcV6jGbJ6vfVw7hfKIFDGA
 *  echo $random->base64Safe();           // GD8JojhzSTrqX7Q8J6uug
 *  echo $random->base64Safe(8);          // mGyy0evy3ok
 *  echo $random->base64Safe(null, true); // DRrAgOFkS4rvRiVHFefcQ==
 *
 *  // Random UUID
 *  echo $random->uuid(); // db082997-2572-4e2c-a046-5eefe97b1235
 *  echo $random->uuid(); // da2aa0e2-b4d0-4e3c-99f5-f5ef62c57fe2
 *  echo $random->uuid(); // 75e6b628-c562-4117-bb76-61c4153455a9
 *  echo $random->uuid(); // dc446df1-0848-4d05-b501-4af3c220c13d
 *
 *  // Random number between 0 and $len
 *  echo $random->number(256); // 84
 *  echo $random->number(256); // 79
 *  echo $random->number(100); // 29
 *  echo $random->number(300); // 40
 *
 *  // Random base58 string
 *  echo $random->base58();   // 4kUgL2pdQMSCQtjE
 *  echo $random->base58();   // Umjxqf7ZPwh765yR
 *  echo $random->base58(24); // qoXcgmw4A9dys26HaNEdCRj9
 *  echo $random->base58(7);  // 774SJD3vgP
 *</code>
 *
 * This class partially borrows SecureRandom library from Ruby
 *
 * @link http://ruby-doc.org/stdlib-2.2.2/libdoc/securerandom/rdoc/SecureRandom.html
 */
class Random
{

    /**
     * Generates a random binary string
     *
     * If $len is not specified, 16 is assumed. It may be larger in future.
     * The result may contain any byte: "x00" - "xFF".
     *
     *<code>
     *  $random = new \Phalcon\Security\Random();
     *
     *  $bytes = $random->bytes();
     *</code>
     *
     * @param int $len
     * @return string
     * @throws Exception If secure random number generator is not available or unexpected partial read
     */
    public function bytes($len = 16)
    {
        if ($len <= 0) {
            $len = 16;
        }

        if (function_exists('openssl_random_pseudo_bytes')) {
            return openssl_random_pseudo_bytes($len);
        }

        throw new Exception("No random device");
    }

    /**
     * Generates a random hex string
     *
     * If $len is not specified, 16 is assumed. It may be larger in future.
     * The length of the result string is usually greater of $len.
     *
     *<code>
     *  $random = new \Phalcon\Security\Random();
     *
     *  echo $random->hex(10); // a29f470508d5ccb8e289
     *</code>
     *
     * @param int $len
     * @return string
     * @throws Exception If secure random number generator is not available or unexpected partial read
     */
    public function hex($len = null)
    {
        $unpack = unpack('H*', $this->bytes($len));
        return array_shift($unpack);
    }

    /**
     * Generates a random base58 string
     *
     * If $len is not specified, 16 is assumed. It may be larger in future.
     * The result may contain alphanumeric characters except 0, O, I and l.
     *
     * It is similar to Base64 but has been modified to avoid both non-alphanumeric
     * characters and letters which might look ambiguous when printed.
     *
     *<code>
     *  $random = new \Phalcon\Security\Random();
     *
     *  echo $random->base58(); // 4kUgL2pdQMSCQtjE
     *</code>
     *
     * @param int $len
     * @return string
     * @link https://en.wikipedia.org/wiki/Base58
     * @throws Exception If secure random number generator is not available or unexpected partial read
     */
    public function base58($len = null)
    {
        $byteString = '';
        $alphabet = '123456789ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz';

        $bytes = unpack('C*', $this->bytes($len));

        foreach ($bytes as $idx) {

            $idx = (int) ($idx % 64);

            if ($idx >= 58) {
                $idx = $this->number(57);
            }

            $byteString .= $alphabet[$idx];

        }

        return $byteString;
    }

    /**
     * Generates a random base64 string
     *
     * If $len is not specified, 16 is assumed. It may be larger in future.
     * The length of the result string is usually greater of $len.
     * Size formula: 4 *( $len / 3) and this need to be rounded up to a multiple of 4.
     *
     *<code>
     *  $random = new \Phalcon\Security\Random();
     *
     *  echo $random->base64(12); // 3rcq39QzGK9fUqh8
     *</code>
     *
     * @param int $len
     * @return string
     * @throws Exception If secure random number generator is not available or unexpected partial read
     */
    public function base64($len = null)
    {
        return base64_encode($this->bytes($len));
    }

    /**
     * Generates a random URL-safe base64 string
     *
     * If $len is not specified, 16 is assumed. It may be larger in future.
     * The length of the result string is usually greater of $len.
     *
     * By default, padding is not generated because "=" may be used as a URL delimiter.
     * The result may contain A-Z, a-z, 0-9, "-" and "_". "=" is also used if $padding is true.
     * See RFC 3548 for the definition of URL-safe base64.
     *
     *<code>
     *  $random = new \Phalcon\Security\Random();
     *
     *  echo $random->base64Safe(); // GD8JojhzSTrqX7Q8J6uug
     *</code>
     *
     * @param int $len
     * @param boolean $padding
     * @return string
     * @link https://www.ietf.org/rfc/rfc3548.txt
     * @throws Exception If secure random number generator is not available or unexpected partial read
     */
    public function base64Safe($len, $padding = false)
    {
        $s = preg_replace('#[^a-z0-9_=-]+#i', '', $this->base64($len));

        if (!$padding) {
            return trim($s, '=');
        }

        return $s;
    }

    /**
     * Generates a v4 random UUID (Universally Unique IDentifier)
     *
     * The version 4 UUID is purely random (except the version). It doesn't contain meaningful
     * information such as MAC address, time, etc. See RFC 4122 for details of UUID.
     *
     * This algorithm sets the version number (4 bits) as well as two reserved bits.
     * All other bits (the remaining 122 bits) are set using a random or pseudorandom data source.
     * Version 4 UUIDs have the form xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx where x is any hexadecimal
     * digit and y is one of 8, 9, A, or B (e.g., f47ac10b-58cc-4372-a567-0e02b2c3d479).
     *
     *<code>
     *  $random = new \Phalcon\Security\Random();
     *
     *  echo $random->uuid(); // 1378c906-64bb-4f81-a8d6-4ae1bfcdec22
     *</code>
     *
     * @return  string
     * @link https://www.ietf.org/rfc/rfc4122.txt
     * @throws Exception If secure random number generator is not available or unexpected partial read
     */
    public function uuid()
    {
        $ary = array_values(unpack('N1a/n1b/n1c/n1d/n1e/N1f', $this->bytes(16)));
        $ary[2] = ($ary[2] & 0x0fff) | 0x4000;
        $ary[3] = ($ary[3] & 0x3fff) | 0x8000;

        array_unshift($ary, '%08x-%04x-%04x-%04x-%04x%08x');

        return call_user_func_array('sprintf', $ary);
    }

    /**
     * Generates a random number between 0 and $len
     *
     * Returns an integer: 0 <= result <= $len.
     *
     *<code>
     *  $random = new \Phalcon\Security\Random();
     *
     *  echo $random->number(16); // 8
     *</code>
     * @param int $len
     * @return int
     * @throws Exception If secure random number generator is not available, unexpected partial read or $len <= 0
     */
    public function number($len)
    {
        $bin = '';

        if ($len <= 0) {
            throw new Exception("Require a positive integer > 0");
        }

        $hex = dechex($len);

        if ((strlen($hex) & 1) == 1) {
            $hex = '0' . $hex;
        }

        $bin .= pack('H*', $hex);

        $mask = ord($bin[0]);
        $mask = $mask | ($mask >> 1);
        $mask = $mask | ($mask >> 2);
        $mask = $mask | ($mask >> 4);

        do {
            $rnd = $this->bytes(strlen($bin));
            $rnd = substr_replace($rnd, chr(ord(substr($rnd, 0, 1)) & $mask), 0, 1);
        } while ($bin < $rnd);

        $ret = unpack('H*', $rnd);

        return hexdec(array_shift($ret));
    }

}
