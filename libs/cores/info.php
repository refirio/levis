<?php

/*******************************************************************************

 Functions for Info

*******************************************************************************/

/**
 * Output a information page for php.
 *
 */
function info_php()
{
    phpinfo();

    exit;
}

/**
 * Output a information page for framework.
 *
 */
function info_levis()
{
    about();

    exit;
}
