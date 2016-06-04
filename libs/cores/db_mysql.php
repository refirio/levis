<?php

/*******************************************************************************

 Functions for DB Driver (mysql)

*******************************************************************************/

/**
 * Connect to the database.
 *
 * @return void
 */
function db_driver_connect()
{
    global $db;

    $db['resource'][$db['target']]['dbh'] = mysql_connect($db['resource'][$db['target']]['config']['host'] . ($db['resource'][$db['target']]['config']['port'] ? ':' . $db['resource'][$db['target']]['config']['port'] : ''), $db['resource'][$db['target']]['config']['username'], $db['resource'][$db['target']]['config']['password'], true);
    if (!$db['resource'][$db['target']]['dbh']) {
        if (LOGGING_MESSAGE) {
            logging('message', 'db: Connect error');
        }

        error('db: Connect error');
    }

    $resource = mysql_select_db($db['resource'][$db['target']]['config']['name'], $db['resource'][$db['target']]['dbh']);
    if (!$resource) {
        if (LOGGING_MESSAGE) {
            logging('message', 'db: Connect error');
        }

        error('db: Select DB error');
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

    return mysql_query($query, $db['resource'][$db['target']]['dbh']);
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
    while ($data = mysql_fetch_array($resource, MYSQL_ASSOC)) {
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

    return mysql_num_rows($resource);
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

    return mysql_affected_rows();
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

    return '\'' . addslashes($data) . '\'';
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
    $data = stripslashes($data);

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

    return mysql_error($db['resource'][$db['target']]['dbh']);
}

/**
 * Start a transaction.
 *
 * @return mixed
 */
function db_driver_transaction()
{
    global $db;

    return mysql_query('START TRANSACTION', $db['resource'][$db['target']]['dbh']);
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
