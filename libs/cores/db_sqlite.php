<?php

/*********************************************************************

 Functions for DB Driver (sqlite)

*********************************************************************/

function db_driver_connect()
{
    global $db;

    $db['resource'][$db['target']]['dbh'] = sqlite_open($db['resource'][$db['target']]['config']['name'], 0666, $error);
    if (!$db['resource'][$db['target']]['dbh']) {
        error('sqlite_connect error.' . (DEBUG_LEVEL ? ' [' . $error . ']' : ''));
    }

    return;
}

function db_driver_query($query)
{
    global $db;

    return sqlite_query($db['resource'][$db['target']]['dbh'], $query);
}

function db_driver_result($resource)
{
    global $db;

    $results = array();
    while ($data = sqlite_fetch_array($resource, SQLITE_ASSOC)) {
        $results[] = $data;
    }

    return $results;
}

function db_driver_count($resource)
{
    global $db;

    return sqlite_num_rows($resource);
}

function db_driver_affected_count($resource)
{
    global $db;

    return sqlite_changes($db['resource'][$db['target']]['dbh']);
}

function db_driver_escape($data)
{
    global $db;

    return '\'' . str_replace('\'', '\'\'', $data) . '\'';
}

function db_driver_unescape($data)
{
    global $db;

    $data = regexp_replace('(^\'|\'$)', '', $data);
    $data = str_replace('\'\'', '\'', $data);

    return $data;
}

function db_driver_error()
{
    global $db;

    return sqlite_last_error($db['resource'][$db['target']]['dbh']);
}

function db_driver_transaction()
{
    global $db;

    return mysql_query('EXCLUSIVE', $db['resource'][$db['target']]['dbh']);
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
