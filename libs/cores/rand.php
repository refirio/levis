<?php

/*********************************************************************

 Functions for Rand

*********************************************************************/

function rand_number($min = null, $max = null)
{
    if ($min == null) {
        $min = 0;
    }
    if ($max == null) {
        $max = mt_getrandmax();
    }

    return mt_rand($min, $max);
}

function rand_string($max = 32)
{
    return substr(md5(uniqid(rand_number(), true)), 0, $max);
}
