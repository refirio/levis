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
    if (!DEBUG_LEVEL || !regexp_match(DEBUG_ADDR, clientip())) {
        return;
    }

    phpinfo();

    exit;
}

/**
 * Output a information page for framework.
 *
 */
function info_levis()
{
    if (!DEBUG_LEVEL || !regexp_match(DEBUG_ADDR, clientip())) {
        return;
    }

    about();

    exit;
}
