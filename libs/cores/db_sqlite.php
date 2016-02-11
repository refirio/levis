<?php

/*******************************************************************************

 Functions for DB Driver (sqlite)

*******************************************************************************/

/**
 * Connect to the database.
 *
 * @return void
 */
function db_driver_connect()
{
    global $db;

    $db['resource'][$db['target']]['dbh'] = sqlite_open($db['resource'][$db['target']]['config']['name'], 0666, $error);
    if (!$db['resource'][$db['target']]['dbh']) {
        error('sqlite_connect error.' . (DEBUG_LEVEL ? ' [' . $error . ']' : ''));
    }

    return;
}

/**
 * Query to database.
 *
 * @param  mixed  $query
 * @return mixed
 */
function db_driver_query($query)
{
    global $db;

    return sqlite_query($db['resource'][$db['target']]['dbh'], $query);
}

/**
 * Get the result from database.
 *
 * @param  resource  $resource
 * @return array
 */
function db_driver_result($resource)
{
    global $db;

    $results = array();
    while ($data = sqlite_fetch_array($resource, SQLITE_ASSOC)) {
        $results[] = $data;
    }

    return $results;
}

/**
 * Get the count from database.
 *
 * @param  resource  $resource
 * @return int
 */
function db_driver_count($resource)
{
    global $db;

    return sqlite_num_rows($resource);
}

/**
 * Get the affected count from database.
 *
 * @param  resource  $resource
 * @return int
 */
function db_driver_affected_count($resource)
{
    global $db;

    return sqlite_changes($db['resource'][$db['target']]['dbh']);
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
 * Get the error from database.
 *
 * @return string
 */
function db_driver_error()
{
    global $db;

    return sqlite_last_error($db['resource'][$db['target']]['dbh']);
}

/**
 * Start a transaction.
 *
 * @return mixed
 */
function db_driver_transaction()
{
    global $db;

    return mysql_query('EXCLUSIVE', $db['resource'][$db['target']]['dbh']);
}

/**
 * Commit a transaction.
 *
 * @return mixed
 */
function db_driver_commit()
{
    global $db;

    return mysql_query('COMMIT', $db['resource'][$db['target']]['dbh']);
}

/**
 * Rollback a transaction.
 *
 * @return mixed
 */
function db_driver_rollback()
{
    global $db;

    return mysql_query('ROLLBACK', $db['resource'][$db['target']]['dbh']);
}
