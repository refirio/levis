<?php

/*******************************************************************************

 Functions for Rand

*******************************************************************************/

/**
 * Get a random number.
 *
 * @param int $min
 * @param int $max
 *
 * @return int
 */
function rand_number($min = null, $max = null)
{
    if ($min === null) {
        $min = 0;
    }
    if ($max === null) {
        $max = mt_getrandmax();
    }

    return mt_rand($min, $max);
}

/**
 * Get a random string.
 *
 * @param int $max
 *
 * @return string
 */
function rand_string($max = 32)
{
    $bytes = false;

    if (function_exists('random_bytes')) {
        $bytes = random_bytes(16);
    } elseif (function_exists('openssl_random_pseudo_bytes')) {
        $bytes = openssl_random_pseudo_bytes(16);
    }

    if ($bytes === false || strlen($bytes) !== 16) {
        return substr(md5(uniqid(rand_number(), true)), 0, $max);
    }

    return substr(bin2hex($bytes), 0, $max);
}
