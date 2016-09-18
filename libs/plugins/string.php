<?php

/*******************************************************************************

 Functions for String

*******************************************************************************/

/**
 * Get a linked string.
 *
 * @param string $string
 *
 * @return string
 */
function string_autolink($string)
{
    $string = preg_replace('/(^|[^\"\w\.\~\-\/\?\&\#\+\=\:\;\@\%\!])(https?\:\/\/[\w\.\~\-\/\?\&\#\+\=\:\;\@\%\!]+)/', '$1<a href="$2">$2</a>', $string);
    $string = preg_replace('/(^|[^\"\w\.\~\-\/\?\&\#\+\=\:\;\@\%\!])([\w\.\+\-]+@[\w\.\+\-]+)/', '$1<a href="mailto:$2">$2</a>', $string);

    return $string;
}
