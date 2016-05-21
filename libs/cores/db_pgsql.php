<?php

/*******************************************************************************

 Functions for DB Driver (pgsql)

*******************************************************************************/

/**
 * Connect to the database.
 *
 * @return void
 */
function db_driver_connect()
{
    global $db;

    $db['resource'][$db['target']]['dbh'] = pg_connect('host=' . $db['resource'][$db['target']]['config']['host'] . ($db['resource'][$db['target']]['config']['port'] ? ' port=' . $db['resource'][$db['target']]['config']['port'] : '') . ' dbname=' . $db['resource'][$db['target']]['config']['name'] . ' user=' . $db['resource'][$db['target']]['config']['username'] . ' password=' . $db['resource'][$db['target']]['config']['password'], true);
    if (!$db['resource'][$db['target']]['dbh']) {
        error('db: pg_connect error.' . (DEBUG_LEVEL ? ' [' . $db['resource'][$db['target']]['config']['host'] . ']' : ''));
    }

    return;
}

/**
 * Query to the database.
 *
 * @param  mixed  $query
 * @return mixed
 */
function db_driver_query($query)
{
    global $db;

    return pg_query($db['resource'][$db['target']]['dbh'], $query);
}

/**
 * Get the result from the database.
 *
 * @param  resource  $resource
 * @return array
 */
function db_driver_result($resource)
{
    global $db;

    $results = array();
    while ($data = pg_fetch_array($resource, null, PGSQL_ASSOC)) {
        $results[] = $data;
    }

    return $results;
}

/**
 * Get the count from the database.
 *
 * @param  resource  $resource
 * @return int
 */
function db_driver_count($resource)
{
    global $db;

    return pg_num_rows($resource);
}

/**
 * Get the affected count from the database.
 *
 * @param  resource  $resource
 * @return int
 */
function db_driver_affected_count($resource)
{
    global $db;

    return pg_affected_rows($resource);
}

/**
 * Get the escaped data for database.
 *
 * @param  string  $data
 * @return string
 */
function db_driver_escape($data)
{
    global $db;

    return '\'' . str_replace('\'', '\'\'', $data) . '\'';
}

/**
 * Get the unescaped data for database.
 *
 * @param  string  $data
 * @return string
 */
function db_driver_unescape($data)
{
    global $db;

    $data = regexp_replace('(^\'|\'$)', '', $data);
    $data = str_replace('\'\'', '\'', $data);

    return $data;
}

/**
 * Get the error from the database.
 *
 * @return string
 */
function db_driver_error()
{
    global $db;

    return pg_result_error($db['resource'][$db['target']]['dbh']);
}

/**
 * Start a transaction.
 *
 * @return mixed
 */
function db_driver_transaction()
{
    global $db;

    return pg_query($db['resource'][$db['target']]['dbh'], 'START TRANSACTION');
}

/**
 * Commit a transaction.
 *
 * @return mixed
 */
function db_driver_commit()
{
    global $db;

    return pg_query($db['resource'][$db['target']]['dbh'], 'COMMIT');
}

/**
 * Rollback a transaction.
 *
 * @return mixed
 */
function db_driver_rollback()
{
    global $db;

    return pg_query($db['resource'][$db['target']]['dbh'], 'ROLLBACK');
}
