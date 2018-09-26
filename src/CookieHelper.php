<?php
namespace Gemingcao\Helper;

/**
 * CookieHelper class
 *
 * This is a general-purpose class that allows to manage PHP built-in cookies
 * and the cookies variables passed via $_COOKIE superglobal.
 *
 * @package Gemingcao\Helper
 */
class CookieHelper implements \ArrayAccess, \Countable, \IteratorAggregate
{
    /**
     * @var array
     */
    protected $settings;

    /**
     * Constructor
     *
     * @param array $settings
     */
    public function __construct($settings = [])
    {
        $defaults = [
            'lifetime'     => 3600,
            'pre'     => 'gmc_',
            'encrypt'    => 'T8boJ9anvC2lnudH',
            'path'         => '/',
            'domain'       => '',
            'mustInt' => [],
            'mustFilter' => [],
        ];
        $settings = array_merge($defaults, $settings);

        if (is_string($lifetime = $settings['lifetime'])) {
            $settings['lifetime'] = strtotime($lifetime) - time();
        }
        $this->settings = $settings;
    }

    public function set($key, $value, $expire = 0)
    {
        $expire = $expire > 0 ? $expire : ($value == '' ? SYS_TIME - $this->settings['lifetime'] : 0);
        $httponly = $_SERVER['SERVER_PORT'] == '443' ? true : false;
        $key = $this->settings['pre'] . $key;
        $_COOKIE[$key] = $value;
        if (is_array($value)) {
            foreach ($value as $k => $v) {
                setcookie($key . '[' . $k . ']', $this->strEncryption($v, 'ENCODE'), $expire, $this->settings['path'], $this->settings['domain'], $httponly);
            }
        } else {
            setcookie($key, $this->strEncryption($value, 'ENCODE'), $expire, $this->settings['path'], $this->settings['domain'], $httponly);
        }
    }

    public function get($key, $default = '')
    {
        $origin_key = $key;
        $key   = $this->settings['pre'] . $key;
        $value = $this->exists($key) ? $this->strEncryption($_COOKIE[$key], 'DECODE') : $default;
        if (in_array($origin_key, $this->settings['mustInt'])) {
            $value = intval($value);
        } elseif (in_array($origin_key, $this->$this->settings['mustFilter'])) {
            $value = $this->safeFilter($value);
        }
        return $value;
    }

    public function strEncryption($string, $operation = 'ENCODE', $key = '', $expiry = 0)
    {
        $ckey_length = 4;
        $key         = md5($key != '' ? $key : $this->settings['encrypt']);
        $keya        = md5(substr($key, 0, 16));
        $keyb        = md5(substr($key, 16, 16));
        $keyc        = $ckey_length ? ($operation == 'DECODE' ? substr($string, 0, $ckey_length) : substr(md5(microtime()), -$ckey_length)) : '';

        $cryptkey   = $keya . md5($keya . $keyc);
        $key_length = strlen($cryptkey);

        $string        = $operation == 'DECODE' ? base64_decode(strtr(substr($string, $ckey_length), '-_', '+/')) : sprintf('%010d', $expiry ? $expiry + time() : 0) . substr(md5($string . $keyb), 0, 16) . $string;
        $string_length = strlen($string);

        $result = '';
        $box    = range(0, 255);

        $rndkey = array();
        for ($i = 0; $i <= 255; $i++) {
            $rndkey[$i] = ord($cryptkey[$i % $key_length]);
        }

        for ($j = $i = 0; $i < 256; $i++) {
            $j       = ($j + $box[$i] + $rndkey[$i]) % 256;
            $tmp     = $box[$i];
            $box[$i] = $box[$j];
            $box[$j] = $tmp;
        }

        for ($a = $j = $i = 0; $i < $string_length; $i++) {
            $a       = ($a + 1) % 256;
            $j       = ($j + $box[$a]) % 256;
            $tmp     = $box[$a];
            $box[$a] = $box[$j];
            $box[$j] = $tmp;
            $result .= chr(ord($string[$i]) ^ ($box[($box[$a] + $box[$j]) % 256]));
        }

        if ($operation == 'DECODE') {
            if ((substr($result, 0, 10) == 0 || substr($result, 0, 10) - time() > 0) && substr($result, 10, 16) == substr(md5(substr($result, 26) . $keyb), 0, 16)) {
                return substr($result, 26);
            } else {
                return '';
            }
        } else {
            return $keyc . rtrim(strtr(base64_encode($result), '+/', '-_'), '=');
        }
    }

    private function safeFilter($string)
    {
        $string = str_replace('%20', '', $string);
        $string = str_replace('%27', '', $string);
        $string = str_replace('%2527', '', $string);
        $string = str_replace('*', '', $string);
        $string = str_replace('"', '&quot;', $string);
        $string = str_replace("'", '', $string);
        $string = str_replace('"', '', $string);
        $string = str_replace(';', '', $string);
        $string = str_replace('<', '&lt;', $string);
        $string = str_replace('>', '&gt;', $string);
        $string = str_replace("{", '', $string);
        $string = str_replace('}', '', $string);
        $string = str_replace('\\', '', $string);
        return $string;
    }

    /**
     * Merge values recursively.
     *
     * @param string $key
     * @param mixed  $value
     *
     * @return $this
     */
    public function merge($key, $value)
    {
        if (is_array($value) && is_array($old = $this->get($key))) {
            $value = array_merge_recursive($old, $value);
        }
        return $this->set($key, $value);
    }

    /**
     * Delete a session variable.
     *
     * @param string $key
     *
     * @return $this
     */
    public function delete($key)
    {
        if ($this->exists($key)) {
            unset($_COOKIE[$this->settings['pre'] . $key]);
        }

        return $this;
    }

    /**
     * Clear all session variables.
     *
     * @return $this
     */
    public function clear()
    {
        $_COOKIE = [];
        return $this;
    }

    /**
     * Check if a session variable is set.
     *
     * @param string $key
     *
     * @return bool
     */
    public function exists($key)
    {
        return array_key_exists($this->settings['pre'] . $key, $_COOKIE);
    }

    /**
     * Magic method for get.
     *
     * @param string $key
     *
     * @return mixed
     */
    public function __get($key)
    {
        return $this->get($key);
    }

    /**
     * Magic method for set.
     *
     * @param string $key
     * @param mixed  $value
     */
    public function __set($key, $value)
    {
        $this->set($key, $value);
    }

    /**
     * Magic method for delete.
     *
     * @param string $key
     */
    public function __unset($key)
    {
        $this->delete($key);
    }

    /**
     * Magic method for exists.
     *
     * @param string $key
     *
     * @return bool
     */
    public function __isset($key)
    {
        return $this->exists($key);
    }

    /**
     * Count elements of an object.
     *
     * @return int
     */
    public function count()
    {
        return count($_COOKIE);
    }

    /**
     * Retrieve an external Iterator.
     *
     * @return \Traversable
     */
    public function getIterator()
    {
        return new \ArrayIterator($_COOKIE);
    }

    /**
     * Whether an array offset exists.
     *
     * @param mixed $offset
     *
     * @return boolean
     */
    public function offsetExists($offset)
    {
        return $this->exists($offset);
    }

    /**
     * Retrieve value by offset.
     *
     * @param mixed $offset
     *
     * @return mixed
     */
    public function offsetGet($offset)
    {
        return $this->get($offset);
    }

    /**
     * Set a value by offset.
     *
     * @param mixed $offset
     * @param mixed $value
     */
    public function offsetSet($offset, $value)
    {
        $this->set($offset, $value);
    }

    /**
     * Remove a value by offset.
     *
     * @param mixed $offset
     */
    public function offsetUnset($offset)
    {
        $this->delete($offset);
    }
}
