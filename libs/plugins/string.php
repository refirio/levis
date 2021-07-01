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
    $string = preg_replace('/(^|[^\w\~\-\/\&\#\+\=\@\%])(https?\:\/\/[\w\.\~\-\/\?\&\#\+\=\:\;\@\%\!]+)/', '$1<a href="$2">$2</a>', $string);
    $string = preg_replace('/(^|[^\w\~\-\/\&\#\+\=\@\%])([\w\.\+\-]+@[\w\.\+\-]+)/', '$1<a href="mailto:$2">$2</a>', $string);

    return $string;
}

/**
 * Get a word-wrapped string.
 *
 * @param string $string
 * @param int    $width
 * @param string $break
 *
 * @return string
 */
function string_wordwrap($string, $width = 256, $break = "\n")
{
    $pattern = "/(.{1,{$width}})(?:\\s|$)|(.{{$width}})/uS";
    $replace = '$1$2' . $break;
    $wrapped = preg_replace($pattern, $replace, $string);

    return $wrapped;
}
