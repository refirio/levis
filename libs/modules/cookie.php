<?php

/*******************************************************************************

 Functions for Cookie

*******************************************************************************/

/**
 * Set the cookie.
 *
 * @param string      $name
 * @param string      $value
 * @param int         $expire
 * @param string|null $path
 * @param string|null $domain
 * @param bool        $secure
 * @param bool        $httponly
 * @param string      $samesite
 *
 * @return bool
 */
function cookie_set($name, $value, $expire = 0, $path = null, $domain = null, $secure = false, $httponly = true, $samesite = 'Lax')
{
    if ($path === null) {
        $path = dirname($_SERVER['SCRIPT_NAME']);
    }
    if ($domain === null) {
        if ($_SERVER['SERVER_NAME'] === 'localhost') {
            $domain = '';
        } else {
            $domain = $_SERVER['SERVER_NAME'];
        }
    }

    if (version_compare(PHP_VERSION, '7.3.0', '>=')) {
        return setcookie($name, $value, array(
            'expires'  => $expire,
            'path'     => $path,
            'domain'   => $domain,
            'secure'   => $secure,
            'httponly' => $httponly,
            'samesite' => $samesite,
        ));
    } else {
        return setcookie($name, $value, $expire, $path, $domain, $secure, $httponly);
    }
}
