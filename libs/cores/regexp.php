<?php

/*******************************************************************************

 Functions for Regexp

*******************************************************************************/

function regexp_match($pattern, $subject)
{
    $regexp = false;

    if (REGEXP_TYPE == 'ereg') {
        if (eregi($pattern, $subject, $matches)) {
            $regexp = true;
        }
    } else {
        if (preg_match('/' . $pattern . '/i', $subject, $matches)) {
            $regexp = true;
        }
    }

    if (!empty($matches)) {
        $regexp = $matches;
    }

    return $regexp;
}

function regexp_replace($pattern, $replacement, $subject, $limit = -1)
{
    if (REGEXP_TYPE == 'ereg') {
        if ($limit != -1) {
            error('REGEXP_TYPE [ereg] is not support $limit.');
        }

        $subject = eregi_replace($pattern, $replacement, $subject);
    } else {
        $subject = preg_replace('/' . $pattern . '/i', $replacement, $subject, $limit);
    }

    return $subject;
}
