<?php

/*******************************************************************************

 Functions for DB Driver (mysql)

*******************************************************************************/

function db_driver_connect()
{
    global $db;

    $db['resource'][$db['target']]['dbh'] = mysql_connect($db['resource'][$db['target']]['config']['host'] . ($db['resource'][$db['target']]['config']['port'] ? ':' . $db['resource'][$db['target']]['config']['port'] : ''), $db['resource'][$db['target']]['config']['username'], $db['resource'][$db['target']]['config']['password'], true);
    if (!$db['resource'][$db['target']]['dbh']) {
        error('mysql_connect error.' . (DEBUG_LEVEL ? ' [' . $db['resource'][$db['target']]['config']['host'] . ']' : ''));
    }

    $resource = mysql_select_db($db['resource'][$db['target']]['config']['name'], $db['resource'][$db['target']]['dbh']);
    if (!$resource) {
        error('mysql_select_db error.' . (DEBUG_LEVEL ? ' [' . $db['resource'][$db['target']]['config']['name'] . ']' : ''));
    }

    return;
}

function db_driver_query($query)
{
    global $db;

    return mysql_query($query, $db['resource'][$db['target']]['dbh']);
}

function db_driver_result($resource)
{
    global $db;

    $results = array();
    while ($data = mysql_fetch_array($resource, MYSQL_ASSOC)) {
        $results[] = $data;
    }

    return $results;
}

function db_driver_count($resource)
{
    global $db;

    return mysql_num_rows($resource);
}

function db_driver_affected_count($resource)
{
    global $db;

    return mysql_affected_rows();
}

function db_driver_escape($data)
{
    global $db;

    return '\'' . addslashes($data) . '\'';
}

function db_driver_unescape($data)
{
    global $db;

    $data = regexp_replace('(^\'|\'$)', '', $data);
    $data = stripslashes($data);

    return $data;
}

function db_driver_error()
{
    global $db;

    return mysql_error($db['resource'][$db['target']]['dbh']);
}

function db_driver_transaction()
{
    global $db;

    return mysql_query('START TRANSACTION', $db['resource'][$db['target']]['dbh']);
}

function db_driver_commit()
{
    global $db;

    return mysql_query('COMMIT', $db['resource'][$db['target']]['dbh']);
}

function db_driver_rollback()
{
    global $db;

    return mysql_query('ROLLBACK', $db['resource'][$db['target']]['dbh']);
}
