<?php

/*********************************************************************

 Functions for DB Driver (pgsql)

*********************************************************************/

function db_driver_connect()
{
    global $db;

    $db['resource'][$db['target']]['dbh'] = pg_connect('host=' . $db['resource'][$db['target']]['config']['host'] . ($db['resource'][$db['target']]['config']['port'] ? ' port=' . $db['resource'][$db['target']]['config']['port'] : '') . ' dbname=' . $db['resource'][$db['target']]['config']['name'] . ' user=' . $db['resource'][$db['target']]['config']['username'] . ' password=' . $db['resource'][$db['target']]['config']['password'], true);
    if (!$db['resource'][$db['target']]['dbh']) {
        error('pg_connect error.' . (DEBUG_LEVEL ? ' [' . $db['resource'][$db['target']]['config']['host'] . ']' : ''));
    }

    return;
}

function db_driver_query($query)
{
    global $db;

    return pg_query($db['resource'][$db['target']]['dbh'], $query);
}

function db_driver_result($resource)
{
    global $db;

    $results = array();
    while ($data = pg_fetch_array($resource, null, PGSQL_ASSOC)) {
        $results[] = $data;
    }

    return $results;
}

function db_driver_count($resource)
{
    global $db;

    return pg_num_rows($resource);
}

function db_driver_affected_count($resource)
{
    global $db;

    return pg_affected_rows($resource);
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

    return pg_result_error($db['resource'][$db['target']]['dbh']);
}

function db_driver_transaction()
{
    global $db;

    return pg_query($db['resource'][$db['target']]['dbh'], 'START TRANSACTION');
}

function db_driver_commit()
{
    global $db;

    return pg_query($db['resource'][$db['target']]['dbh'], 'COMMIT');
}

function db_driver_rollback()
{
    global $db;

    return pg_query($db['resource'][$db['target']]['dbh'], 'ROLLBACK');
}
