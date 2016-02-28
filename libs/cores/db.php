<?php

/*******************************************************************************

 Functions for DB

*******************************************************************************/

/**
 * Connect to the database.
 *
 * @param  mixed  $info
 * @return void
 */
function db_connect($info)
{
    global $db;

    if (DATABASE_TYPE == '') {
        return;
    }

    if (is_array($info)) {
        foreach ($info as $key => $confg) {
            $db['resource'][$key] = array(
                'config' => array(
                    'type'                => isset($confg['type'])                ? $confg['type']                : DATABASE_TYPE,
                    'host'                => isset($confg['host'])                ? $confg['host']                : DATABASE_HOST,
                    'port'                => isset($confg['port'])                ? $confg['port']                : DATABASE_PORT,
                    'username'            => isset($confg['username'])            ? $confg['username']            : DATABASE_USERNAME,
                    'password'            => isset($confg['password'])            ? $confg['password']            : DATABASE_PASSWORD,
                    'name'                => isset($confg['name'])                ? $confg['name']                : DATABASE_NAME,
                    'prefix'              => isset($confg['prefix'])              ? $confg['prefix']              : DATABASE_PREFIX,
                    'charset'             => isset($confg['charset'])             ? $confg['charset']             : DATABASE_CHARSET,
                    'charset_input_from'  => isset($confg['charset_input_from'])  ? $confg['charset_input_from']  : DATABASE_CHARSET_INPUT_FROM,
                    'charset_input_to'    => isset($confg['charset_input_to'])    ? $confg['charset_input_to']    : DATABASE_CHARSET_INPUT_TO,
                    'charset_output_from' => isset($confg['charset_output_from']) ? $confg['charset_output_from'] : DATABASE_CHARSET_OUTPUT_FROM,
                    'charset_output_to'   => isset($confg['charset_output_to'])   ? $confg['charset_output_to']   : DATABASE_CHARSET_OUTPUT_TO,
                ),
                'dbh' => null,
            );
        }
    } elseif ($info == 'default') {
        $db['target'] = 'default';

        if (isset($db['resource'][$db['target']])) {
            return;
        } else {
            $db['resource'][$db['target']] = array(
                'config' => array(
                    'type'                => DATABASE_TYPE,
                    'host'                => DATABASE_HOST,
                    'port'                => DATABASE_PORT,
                    'username'            => DATABASE_USERNAME,
                    'password'            => DATABASE_PASSWORD,
                    'name'                => DATABASE_NAME,
                    'prefix'              => DATABASE_PREFIX,
                    'charset'             => DATABASE_CHARSET,
                    'charset_input_from'  => DATABASE_CHARSET_INPUT_FROM,
                    'charset_input_to'    => DATABASE_CHARSET_INPUT_TO,
                    'charset_output_from' => DATABASE_CHARSET_OUTPUT_FROM,
                    'charset_output_to'   => DATABASE_CHARSET_OUTPUT_TO,
                ),
                'dbh' => null,
            );
        }
    } else {
        $db['target'] = $info;

        return;
    }

    foreach ($db['resource'] as $key => $data) {
        $db['target'] = $key;

        if (!empty($db['resource'][$db['target']]['dbh'])) {
            continue;
        }

        if ($data['config']['type'] == 'pdo_mysql' || $data['config']['type'] == 'pdo_pgsql' || $data['config']['type'] == 'pdo_sqlite' || $data['config']['type'] == 'pdo_sqlite2') {
            import('libs/cores/db_pdo.php');
        } elseif ($data['config']['type'] == 'mysql') {
            import('libs/cores/db_mysql.php');
        } elseif ($data['config']['type'] == 'pgsql') {
            import('libs/cores/db_pgsql.php');
        } elseif ($data['config']['type'] == 'sqlite') {
            import('libs/cores/db_sqlite.php');
        }

        db_driver_connect();

        if (($data['config']['type'] == 'pdo_mysql' || $data['config']['type'] == 'mysql' || $data['config']['type'] == 'pdo_pgsql' || $data['config']['type'] == 'pgsql') && $data['config']['charset']) {
            $resource = db_query('SET NAMES \'' . $data['config']['charset'] . '\'');
            if (!$resource) {
                error('database set names error.' . (DEBUG_LEVEL ? ' [' . $data['config']['charset'] . ']' : ''));
            }
        }
    }

    return;
}

/**
 * Query to database.
 *
 * @param  mixed  $query
 * @param  bool  $return
 * @param  bool  $error
 * @return mixed
 */
function db_query($query, $return = false, $error = true)
{
    global $db;

    if (DATABASE_TYPE == '') {
        return false;
    }

    if (is_array($query)) {
        $queries = db_placeholder(array(
            'query' => $query,
        ));

        $query = $queries['query'];
    }

    if ($db['resource'][$db['target']]['config']['charset_input_to'] != $db['resource'][$db['target']]['config']['charset_input_from']) {
        $query = convert($query, $db['resource'][$db['target']]['config']['charset_input_to'], $db['resource'][$db['target']]['config']['charset_input_from']);
    }

    if ($return) {
        return $query;
    } else {
        if (DEBUG_LEVEL == 2) {
            echo '<pre><code>' . $query . '</code></pre>';
        }

        $resource = db_driver_query($query);

        if (!$resource && $error) {
            error('database query error.' . (DEBUG_LEVEL ? ' [' . db_error() . ']' : ''));
        }

        return $resource;
    }
}

/**
 * Get the result from database.
 *
 * @param  resource  $resource
 * @return array
 */
function db_result($resource)
{
    global $db;

    if (DATABASE_TYPE == '') {
        return array();
    }

    $results = db_driver_result($resource);

    if ($db['resource'][$db['target']]['config']['charset_output_to'] != $db['resource'][$db['target']]['config']['charset_output_from']) {
        $results = convert($results, $db['resource'][$db['target']]['config']['charset_output_to'], $db['resource'][$db['target']]['config']['charset_output_from']);
    }

    return $results;
}

/**
 * Get the count from database.
 *
 * @param  resource  $resource
 * @return int
 */
function db_count($resource)
{
    global $db;

    if (DATABASE_TYPE == '') {
        return -1;
    }

    return db_driver_count($resource);
}

/**
 * Get the affected count from database.
 *
 * @param  resource  $resource
 * @return int
 */
function db_affected_count($resource)
{
    global $db;

    if (DATABASE_TYPE == '') {
        return -1;
    }

    return db_driver_affected_count($resource);
}

/**
 * Get the escaped data for database.
 *
 * @param  string  $data
 * @return string
 */
function db_escape($data)
{
    global $db;

    if (DATABASE_TYPE == '') {
        return $data;
    }

    if ($data === 0 || regexp_match('^[1-9]+[0-9]*$', $data)) {
        return $data;
    }

    return db_driver_escape($data);
}

/**
 * Get the unescaped data for database.
 *
 * @param  string  $data
 * @return string
 */
function db_unescape($data)
{
    global $db;

    if (DATABASE_TYPE == '') {
        return $data;
    }

    if ($data === 0 || regexp_match('^[1-9]+[0-9]*$', $data)) {
        return $data;
    }

    return db_driver_unescape($data);
}

/**
 * Get the placeholder data for database.
 *
 * @param  string  $data
 * @return string
 */
function db_placeholder($data)
{
    $holder = rand_string();

    foreach ($data as $index => $query) {
        if (is_array($query) && count($query) == 2 && isset($query[1]) && is_array($query[1])) {
            list($query, $holders) = $query;

            $query = str_replace(':', $holder, $query);

            if (isset($holders[0])) {
                foreach ($holders as $key => $value) {
                    if (regexp_match($holder . '\?', $query)) {
                        $query = regexp_replace($holder . '\?', $value, $query, 1);
                    }
                }
            } else {
                uksort($holders, create_function('$a,$b', 'return strlen($b) - strlen($a);'));

                foreach ($holders as $key => $value) {
                    if (regexp_match($holder . $key, $query)) {
                        $value = is_array($value) ? $value[0] : db_escape($value);

                        $query = regexp_replace($holder . $key, $value, $query);
                    }
                }
            }

            $data[$index] = $query;
        }
    }

    return $data;
}

/**
 * Get the error from database.
 *
 * @return string
 */
function db_error()
{
    global $db;

    if (DATABASE_TYPE == '') {
        return '';
    }

    return db_driver_error();
}

/**
 * Select the data from database.
 *
 * @param  array  $queries
 * @param  bool  $return
 * @return mixed
 */
function db_select($queries, $return = false)
{
    global $db;

    if (DATABASE_TYPE == '') {
        return array();
    }

    $queries = db_placeholder($queries);

    if (isset($queries['select']) && $queries['select'] != '') {
        $queries['select'] = 'SELECT ' . $queries['select'] . ' ';
    } else {
        $queries['select'] = 'SELECT * ';
    }
    if (isset($queries['from']) && $queries['from'] != '') {
        $queries['from'] = 'FROM ' . $queries['from'] . ' ';
    } else {
        return array();
    }

    if (isset($queries['where']) && $queries['where'] != '') {
        $queries['where'] = 'WHERE ' . $queries['where'] . ' ';
    } else {
        $queries['where'] = '';
    }
    if (isset($queries['group_by']) && $queries['group_by'] != '') {
        $queries['group_by'] = 'GROUP BY ' . $queries['group_by'] . ' ';
    } else {
        $queries['group_by'] = '';
    }
    if (isset($queries['having']) && $queries['having'] != '') {
        $queries['having'] = 'HAVING ' . $queries['having'] . ' ';
    } else {
        $queries['having'] = '';
    }
    if (isset($queries['order_by']) && $queries['order_by'] != '') {
        $queries['order_by'] = 'ORDER BY ' . $queries['order_by'] . ' ';
    } else {
        $queries['order_by'] = '';
    }
    if (isset($queries['offset']) && $queries['offset'] != '') {
        $queries['offset'] = 'OFFSET ' . $queries['offset'] . ' ';
    } else {
        $queries['offset'] = '';
    }
    if (isset($queries['limit']) && $queries['limit'] != '') {
        $queries['limit'] = 'LIMIT ' . $queries['limit'] . ' ';
    } else {
        $queries['limit'] = '';
    }

    $query = $queries['select'] . $queries['from'] . $queries['where'] . $queries['group_by'] . $queries['having'] . $queries['order_by'] . $queries['offset'] . $queries['limit'];

    if ($return) {
        return $query;
    } else {
        $resource = db_query($query);

        return db_result($resource);
    }
}

/**
 * Insert the data to database.
 *
 * @param  array  $queries
 * @param  bool  $return
 * @return mixed
 */
function db_insert($queries, $return = false)
{
    global $db;

    if (DATABASE_TYPE == '') {
        return false;
    }

    $queries = db_placeholder($queries);

    if (isset($queries['insert_into']) && $queries['insert_into'] != '') {
        $queries['insert_into'] = 'INSERT INTO ' . $queries['insert_into'] . ' ';
    } else {
        return false;
    }

    if (isset($queries['values']) && is_array($queries['values'])) {
        $keys   = array();
        $values = array();

        foreach ($queries['values'] as $key => $value) {
            $keys[] = $key;

            if (is_array($value)) {
                $values[] = $value[0];
            } elseif ($value === null || $value === '' || $value === '\'\'') {
                $values[] = 'NULL';
            } else {
                $values[] = db_escape($value);
            }
        }

        $queries['values'] = '(' . implode(', ', $keys) . ') VALUES(' . implode(', ', $values) . ') ';
    } elseif (isset($queries['values']) && $queries['values'] != '') {
        $queries['values'] = 'VALUES ' . $queries['values'] . ' ';
    } else {
        return false;
    }

    $query = $queries['insert_into'] . $queries['values'];

    if ($return) {
        return $query;
    } else {
        return db_query($query);
    }
}

/**
 * Update the data to database.
 *
 * @param  array  $queries
 * @param  bool  $return
 * @return mixed
 */
function db_update($queries, $return = false)
{
    global $db;

    if (DATABASE_TYPE == '') {
        return false;
    }

    $queries = db_placeholder($queries);

    if (isset($queries['update']) && $queries['update'] != '') {
        $queries['update'] = 'UPDATE ' . $queries['update'] . ' ';
    } else {
        return false;
    }

    if (isset($queries['set']) && is_array($queries['set'])) {
        $sets = array();

        foreach ($queries['set'] as $key => $value) {
            if (is_array($value)) {
                $sets[] = $key . ' = ' . $value[0];
            } elseif ($value === null || $value === '' || $value === '\'\'') {
                $sets[] = $key . ' = NULL';
            } else {
                $sets[] = $key . ' = ' . db_escape($value);
            }
        }

        $queries['set'] = 'SET ' . implode(', ', $sets) . ' ';
    } elseif (isset($queries['set']) && $queries['set'] != '') {
        $queries['set'] = 'SET ' . $queries['set'] . ' ';
    } else {
        return false;
    }

    if (isset($queries['where']) && $queries['where'] != '') {
        $queries['where'] = 'WHERE ' . $queries['where'] . ' ';
    } else {
        $queries['where'] = '';
    }
    if (isset($queries['offset']) && $queries['offset'] != '') {
        $queries['offset'] = 'OFFSET ' . $queries['offset'] . ' ';
    } else {
        $queries['offset'] = '';
    }
    if (isset($queries['limit']) && $queries['limit'] != '') {
        $queries['limit'] = 'LIMIT ' . $queries['limit'] . ' ';
    } else {
        $queries['limit'] = '';
    }

    $query = $queries['update'] . $queries['set'] . $queries['where'] . $queries['offset'] . $queries['limit'];

    if ($return) {
        return $query;
    } else {
        return db_query($query);
    }
}

/**
 * Delete the data to database.
 *
 * @param  array  $queries
 * @param  bool  $return
 * @return mixed
 */
function db_delete($queries, $return = false)
{
    global $db;

    if (DATABASE_TYPE == '') {
        return false;
    }

    $queries = db_placeholder($queries);

    if (isset($queries['delete_from']) && $queries['delete_from'] != '') {
        $queries['delete_from'] = 'DELETE FROM ' . $queries['delete_from'] . ' ';
    } else {
        return false;
    }

    if (isset($queries['where']) && $queries['where'] != '') {
        $queries['where'] = 'WHERE ' . $queries['where'] . ' ';
    } else {
        $queries['where'] = '';
    }
    if (isset($queries['offset']) && $queries['offset'] != '') {
        $queries['offset'] = 'OFFSET ' . $queries['offset'] . ' ';
    } else {
        $queries['offset'] = '';
    }
    if (isset($queries['limit']) && $queries['limit'] != '') {
        $queries['limit'] = 'LIMIT ' . $queries['limit'] . ' ';
    } else {
        $queries['limit'] = '';
    }

    $query = $queries['delete_from'] . $queries['where'] . $queries['offset'] . $queries['limit'];

    if ($return) {
        return $query;
    } else {
        return db_query($query);
    }
}

/**
 * Get the last insert id from database.
 *
 * @param  string  $data
 * @return string
 */
function db_last_insert_id()
{
    global $db;

    if (DATABASE_TYPE == '') {
        return -1;
    }

    return db_driver_last_insert_id();
}

/**
 * Start a transaction.
 *
 * @return mixed
 */
function db_transaction()
{
    global $db;

    if (DATABASE_TYPE == '') {
        return false;
    }

    return db_driver_transaction();
}

/**
 * Commit a transaction.
 *
 * @return mixed
 */
function db_commit()
{
    global $db;

    if (DATABASE_TYPE == '') {
        return false;
    }

    return db_driver_commit();
}

/**
 * Rollback a transaction.
 *
 * @return mixed
 */
function db_rollback()
{
    global $db;

    if (DATABASE_TYPE == '') {
        return false;
    }

    return db_driver_rollback();
}

/**
 * Output a admin page for database.
 *
 */
function db_admin()
{
    global $db;

    if (DATABASE_TYPE == '') {
        return false;
    }

    if ($_REQUEST['work'] == 'import') {
        db_admin_import();
    } elseif ($_REQUEST['work'] == 'export') {
        db_admin_export();
    } else {
        db_admin_sql();
    }

    exit;
}

/**
 * Output a import page for database.
 *
 */
function db_admin_import()
{
    global $db;

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        if ($_POST['means'] == 'upload') {
            if (is_uploaded_file($_FILES['target']['tmp_name'])) {
                $target = $_FILES['target']['tmp_name'];
            } else {
                error('file not found.');
            }
        } else {
            $target = DATABASE_NAME . '.sql';

            if (!is_file($target)) {
                error('file not found.');
            }
        }

        if ($fp = fopen($target, 'r')) {
            $sql  = '';
            $i    = 0;
            $flag = true;

            db_transaction();

            while ($line = fgets($fp)) {
                $line = str_replace("\r\n", "\n", $line);
                $line = str_replace("\r", "\n", $line);

                if ((substr_count($line, '\'') - substr_count($line, '\\\'')) % 2 != 0) {
                    $flag = !$flag;
                }

                $sql .= $line;

                if (preg_match('/;$/', trim($line)) && $flag) {
                    $resource = db_query($sql);
                    if (!$resource) {
                        db_rollback();

                        error('database query error.' . (DEBUG_LEVEL ? ' [' . db_error() . ']' : ''));
                    }

                    $sql = '';
                    $i++;
                }
            }
            fclose($fp);

            db_commit();

            $view['message'] = $i . ' sql executed.';
        } else {
            error('file can\'t read.');
        }
    } else {
        $view['message'] = '';
    }

    echo "<!DOCTYPE html>\n";
    echo "<html>\n";
    echo "<head>\n";
    echo "<meta charset=\"" . t(MAIN_CHARSET, true) . "\" />\n";
    echo "<title>DB</title>\n";

    style();

    echo "</head>\n";
    echo "<body>\n";
    echo "<h1><a href=\"" . t(MAIN_FILE, true) . "/?mode=db_admin\">DB</a></h1>\n";

    echo "<h2>Menu</h2>\n";
    echo "<ul>\n";
    echo "<li><a href=\"" . t(MAIN_FILE, true) . "/?mode=db_admin&amp;work=sql\">SQL</a></li>\n";
    echo "<li>Import</li>\n";
    echo "<li><a href=\"" . t(MAIN_FILE, true) . "/?mode=db_admin&amp;work=export\">Export</a></li>\n";
    echo "</ul>\n";

    echo "<h2>Import</h2>\n";

    if ($view['message']) {
        echo "<ul>\n";
        echo "<li>" . $view['message'] . "</li>\n";
        echo "</ul>\n";
    } else {
        echo "<ul>\n";
        echo "<li>Import from SQL file.</li>\n";
        echo "</ul>\n";
    }

    echo "<form action=\"" . t(MAIN_FILE, true) . "/?mode=db_admin&amp;work=import\" method=\"post\" enctype=\"multipart/form-data\">\n";
    echo "<fieldset>\n";
    echo "<legend>import</legend>\n";
    echo "<dl>\n";
    echo "<dt><label><input type=\"radio\" name=\"means\" value=\"upload\" checked=\"checked\" /> upload</label></dt>\n";
    echo "<dd><input type=\"file\" name=\"target\" size=\"30\" /></dd>\n";
    echo "<dt><label><input type=\"radio\" name=\"means\" value=\"file\" /> read</label></dt>\n";
    echo "<dd><code>" . DATABASE_NAME . ".sql</code></dd>\n";
    echo "</dl>\n";
    echo "<p><input type=\"submit\" value=\"import\" /></p>\n";
    echo "</fieldset>\n";
    echo "</form>\n";

    echo "</body>\n";
    echo "</html>\n";

    return;
}

/**
 * Output a export page for database.
 *
 */
function db_admin_export()
{
    global $db;

    $resource = db_query(db_sql('table_list'));
    $results  = db_result($resource);

    $view['tables'] = array();
    foreach ($results as $result) {
        $view['tables'][] = array_shift($result);
    }

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $text  = '-- Database: ' . DATABASE_NAME . ' (' . DATABASE_TYPE . ")\n";
        $text .= '-- Datetime: ' . localdate('Y-m-d H:i:s') . "\n";
        $text .= '-- Host: ' . gethostbyaddr(clientip()) . "\n";
        $text .= "\n";

        foreach ($view['tables'] as $table) {
            if (empty($_POST['table']) || $_POST['table'] == $table) {
                $resource = db_query(db_sql('table_create', $table));
                $results  = db_result($resource);

                if (DATABASE_TYPE == 'pdo_mysql' || DATABASE_TYPE == 'mysql') {
                    $text .= "DROP TABLE IF EXISTS " . $table . ";\n";
                    $text .= $results[0]['Create Table'] . ";\n";
                    $text .= "\n";
                } elseif (DATABASE_TYPE == 'pdo_pgsql' || DATABASE_TYPE == 'pgsql') {
                    $text .= "DROP TABLE IF EXISTS " . $table . ";\n";
                    $text .= $results[0]['case'] . ";\n";
                    $text .= "\n";
                } elseif (DATABASE_TYPE == 'pdo_sqlite' || DATABASE_TYPE == 'pdo_sqlite2' || DATABASE_TYPE == 'sqlite') {
                    $text .= "DROP TABLE IF EXISTS " . $table . ";\n";
                    $text .= $results[0]['sql'] . ";\n";
                    $text .= "\n";
                }

                $resource = db_query('SELECT * FROM ' . $table . ';');
                $results  = db_result($resource);

                foreach ($results as $result) {
                    $inserts = array();
                    foreach ($result as $data) {
                        if ($data === null) {
                            $inserts[] = 'NULL';
                        } else {
                            $inserts[] = db_escape($data);
                        }
                    }
                    $text .= "INSERT INTO " . $table . " VALUES(" . implode(',', $inserts) . ");\n";
                }

                $text .= "\n";
            }
        }

        if ($_POST['means'] == 'download') {
            header('Content-Type: text/plain');
            header('Content-Disposition: attachment; filename="' . DATABASE_NAME . '.sql"');
            echo $text;
            exit;
        } else {
            if (file_put_contents(DATABASE_NAME . '.sql', $text) === false) {
                error('write error.' . (DEBUG_LEVEL ? ' [' . DATABASE_NAME . '.sql' . ']' : ''));
            }

            $view['message'] = 'exported.';
        }
    }

    echo "<!DOCTYPE html>\n";
    echo "<html>\n";
    echo "<head>\n";
    echo "<meta charset=\"" . t(MAIN_CHARSET, true) . "\" />\n";
    echo "<title>DB</title>\n";

    style();

    echo "</head>\n";
    echo "<body>\n";
    echo "<h1><a href=\"" . t(MAIN_FILE, true) . "/?mode=db_admin\">DB</a></h1>\n";

    echo "<h2>Menu</h2>\n";
    echo "<ul>\n";
    echo "<li><a href=\"" . t(MAIN_FILE, true) . "/?mode=db_admin&amp;work=sql\">SQL</a></li>\n";
    echo "<li><a href=\"" . t(MAIN_FILE, true) . "/?mode=db_admin&amp;work=import\">Import</a></li>\n";
    echo "<li>Export</li>\n";
    echo "</ul>\n";

    echo "<h2>Export</h2>\n";

    if (isset($view['message'])) {
        echo "<ul>\n";
        echo "<li>" . $view['message'] . "</li>\n";
        echo "</ul>\n";
    } else {
        echo "<ul>\n";
        echo "<li>Export to SQL file.</li>\n";
        echo "</ul>\n";
    }

    echo "<form action=\"" . t(MAIN_FILE, true) . "/?mode=db_admin&amp;work=export\" method=\"post\" enctype=\"multipart/form-data\">\n";
    echo "<fieldset>\n";
    echo "<legend>export</legend>\n";

    echo "<dl>\n";
    echo "<dt>table</dt>\n";
    echo "<dd>\n";
    echo "<select name=\"table\">\n";
    echo "<option value=\"\">(all)</option>\n";

    foreach ($view['tables'] as $table) {
        echo "<option value=\"" . $table . "\">" . $table . "</option>\n";
    }

    echo "</select>\n";
    echo "</dd>\n";
    echo "<dt>means</dt>\n";
    echo "<dd>\n";
    echo "<label><input type=\"radio\" name=\"means\" value=\"download\" checked=\"checked\" /> download</label><br />\n";
    echo "<label><input type=\"radio\" name=\"means\" value=\"write\" /> write to <code>" . DATABASE_NAME . ".sql</code></label>\n";
    echo "</dd>\n";
    echo "</dl>\n";
    echo "<p><input type=\"submit\" value=\"export\" /></p>\n";
    echo "</fieldset>\n";
    echo "</form>\n";

    echo "</body>\n";
    echo "</html>\n";

    return;
}

/**
 * Output a sql page for database.
 *
 */
function db_admin_sql()
{
    global $db;

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $sql = $_POST['sql'];
    } else {
        $sql = db_sql('table_list');
    }

    list($micro, $second) = explode(' ', microtime());
    $time_start = $micro + $second;

    $resource = db_query($sql);

    list($micro, $second) = explode(' ', microtime());
    $time_end = $micro + $second;

    $view['time'] = ceil(($time_end - $time_start) * 10000) / 10000;

    $view['sql'] = $sql;

    if ($sql == db_sql('table_list')) {
        $head = '';
        $body = '';

        $results = db_result($resource);

        $head .= '<tr>';
        $head .= '<th>name</th>';

        if (DATABASE_TYPE == 'pdo_mysql' || DATABASE_TYPE == 'mysql') {
            $head .= '<th>engine</th>';
            $head .= '<th>rows</th>';
            $head .= '<th>collation</th>';
            $head .= '<th>comment</th>';
        }

        $head .= '<th>create</th>';
        $head .= '<th>columns</th>';

        if (DATABASE_TYPE == 'pdo_mysql' || DATABASE_TYPE == 'mysql') {
            $head .= '<th>alter</th>';
        }

        $head .= '<th>drop</th>';
        $head .= '<th>insert</th>';
        $head .= '<th>delete</th>';
        $head .= '<th>select</th>';
        $head .= '</tr>';

        foreach ($results as $result) {
            $table = array_shift($result);

            if (DATABASE_TYPE == 'pdo_mysql' || DATABASE_TYPE == 'mysql') {
                $create     = 'SHOW CREATE TABLE';
                $define     = 'SHOW COLUMNS';
            } elseif (DATABASE_TYPE == 'pdo_pgsql' || DATABASE_TYPE == 'pgsql') {
                $create     = 'create';
                $define     = 'columns';
            } elseif (DATABASE_TYPE == 'pdo_sqlite' || DATABASE_TYPE == 'pdo_sqlite2' || DATABASE_TYPE == 'sqlite') {
                $create     = 'SELECT sql';
                $define     = 'PRAGMA TABLE_INFO';
            }
            $create_sql = db_sql('table_create', $table);
            $define_sql = db_sql('table_define', $table);

            $create_sql = preg_replace('/"/', '&quot;', $create_sql);
            $define_sql = preg_replace('/"/', '&quot;', $define_sql);

            $define_resource = db_query($define_sql);
            $define_results  = db_result($define_resource);

            $insert_keys   = array();
            $insert_values = array();

            foreach ($define_results as $define_result) {
                if (DATABASE_TYPE == 'pdo_mysql' || DATABASE_TYPE == 'mysql') {
                    $insert_keys[]   = $define_result['Field'];
                    $insert_values[] = $define_result['Null'] == 'YES' ? 'NULL' : '\\\'\\\'';
                } elseif (DATABASE_TYPE == 'pdo_pgsql' || DATABASE_TYPE == 'pgsql') {
                    $insert_keys[]   = $define_result['column_name'];
                    $insert_values[] = $define_result['is_nullable'] == 'YES' ? 'NULL' : '\\\'\\\'';
                } elseif (DATABASE_TYPE == 'pdo_sqlite' || DATABASE_TYPE == 'pdo_sqlite2' || DATABASE_TYPE == 'sqlite') {
                    $insert_keys[]   = $define_result['name'];
                    $insert_values[] = $define_result['notnull'] == 0 ? 'NULL' : '\\\'\\\'';
                }
            }

            $body .= '<tr>';
            $body .= '<td><span style="font-family:monospace;">' . $table . '</span></td>';

            if (DATABASE_TYPE == 'pdo_mysql' || DATABASE_TYPE == 'mysql') {
                $body .= '<td><span style="font-family:monospace;">' . $result['Engine'] . '</span></td>';
                $body .= '<td><span style="font-family:monospace;">' . $result['Rows'] . '</span></td>';
                $body .= '<td><span style="font-family:monospace;">' . $result['Collation'] . '</span></td>';
                $body .= '<td><span style="font-family:monospace;">' . $result['Comment'] . '</span></td>';
            }

            $body .= '<td><a href="javascript:insertSQL(\'' . str_replace('\'', '\\\'', $create_sql) . '\');">' . $create . '</a></td>';
            $body .= '<td><a href="javascript:insertSQL(\'' . str_replace('\'', '\\\'', $define_sql) . '\');">' . $define . '</a></td>';

            if (DATABASE_TYPE == 'pdo_mysql' || DATABASE_TYPE == 'mysql') {
                $body .= '<td><a href="javascript:insertSQL(\'ALTER TABLE ' . $table . ' COMMENT \\\'\\\';\');">ALTER TABLE</a></td>';
            }

            $body .= '<td><a href="javascript:insertSQL(\'DROP TABLE ' . $table . ';\');">DROP TABLE</a></td>';
            $body .= '<td><a href="javascript:insertSQL(\'INSERT INTO ' . $table . '(' . implode(',', $insert_keys) . ') VALUES(' . implode(',', $insert_values) . ');\');">INSERT</a></td>';
            $body .= '<td><a href="javascript:insertSQL(\'DELETE FROM ' . $table . ';\');">DELETE</a></td>';
            $body .= '<td><a href="javascript:insertSQL(\'SELECT * FROM ' . $table . ' LIMIT 100;\');">SELECT</a></td>';
            $body .= '</tr>';
        }

        $view['result'] = '<table summary="result">' . $head . $body . '</table>';
        $view['count']  = db_count($resource);
    } elseif (regexp_match('^(SELECT|SHOW|EXPLAIN|DESC|PRAGMA)', $sql)) {
        $head = '';
        $body = '';
        $flag = false;

        if ($regexp = regexp_match('^SELECT \* FROM ([_a-zA-Z0-9\-]+)', $sql)) {
            $table = $regexp[1];
            $link  = true;
        } elseif ($regexp = regexp_match('^' . db_sql('table_define', '([_a-zA-Z0-9\-]+)'), $sql)) {
            $table = $regexp[1];
            $link  = false;
        } else {
            $table = null;
            $link  = false;
        }

        $results = db_result($resource);

        foreach ($results as $result) {
            $first_key   = null;
            $first_value = null;

            $body .= '<tr>';

            foreach ($result as $key => $value) {
                if ($first_key === null) {
                    $first_key   = $key;
                    $first_value = $value;
                }

                if (is_string($key)) {
                    if ($value === null) {
                        $value_sql  = 'NULL';
                        $value_html = '<em>NULL</em>';
                    } else {
                        $value_sql = str_replace('\\', '\\\\\\', $value);
                        $value_sql = str_replace("\n", '\n', $value_sql);
                        $value_sql = str_replace('"', '&quot;', $value_sql);

                        if (DATABASE_TYPE == 'pdo_mysql' || DATABASE_TYPE == 'mysql') {
                            $value_sql = str_replace('\'', '\\\\\\\'', $value_sql);
                        } else {
                            $value_sql = str_replace('\'', '\\\'\\\'', $value_sql);
                        }

                        $value_sql  = '\\\'' . $value_sql . '\\\'';
                        $value_html = h($value, true);
                    }

                    if ($link === false) {
                        $value = $value_html;
                    } else {
                        $value = '<a href="javascript:insertSQL(\'UPDATE ' . $table . ' SET ' . $key . ' = ' . $value_sql . ' WHERE ' . $first_key . ' = \\\'' . $first_value . '\\\';\');">' . truncate($value_html, 100) . '</a>';
                    }

                    $body .= '<td><span style="font-family:monospace;">' . $value . '</span></td>';

                    if ($flag == false) {
                        $head .= '<th>' . h($key, true) . '</th>';
                    }
                }
            }

            if (DATABASE_TYPE == 'pdo_mysql' || DATABASE_TYPE == 'mysql') {
                if (regexp_match('^' . db_sql('table_define', '([_a-zA-Z0-9\-]+)'), $sql)) {
                    $add_value    = '<a href="javascript:insertSQL(\'ALTER TABLE ' . $table . ' ADD field INT(1) NOT NULL COMMENT \\\'\\\' AFTER ' . $result['Field'] . ';\');">ADD</a>';
                    $change_value = '<a href="javascript:insertSQL(\'ALTER TABLE ' . $table . ' CHANGE ' . $result['Field'] . ' ' . $result['Field'] . ' INT(1) NOT NULL COMMENT \\\'\\\';\');">CHANGE</a>';
                    $drop_value   = '<a href="javascript:insertSQL(\'ALTER TABLE ' . $table . ' DROP ' . $result['Field'] . ';\');">DROP</a>';

                    $body .= '<td><span style="font-family:monospace;">' . $add_value . ' ' . $change_value . ' ' . $drop_value . '</span></td>';

                    if ($flag == false) {
                        $head .= '<th>alter</th>';
                    }
                }
            }

            $body .= '</tr>';

            $flag = true;
        }

        $view['result'] = '<table summary="result"><tr>' . $head . '</tr>' . $body . '</table>';
        $view['count']  = db_count($resource);
    } else {
        $view['result'] = '<p>OK</p>';
        $view['count']  = db_affected_count($resource);
    }

    echo "<!DOCTYPE html>\n";
    echo "<html>\n";
    echo "<head>\n";
    echo "<meta charset=\"" . t(MAIN_CHARSET, true) . "\" />\n";
    echo "<title>DB</title>\n";

    style();

    echo "<script>\n";
    echo "function insertSQL(sql)\n";
    echo "{\n";
    echo "    document.getElementById('exec_form').sql.value = sql;\n";
    echo "}";
    echo "</script>\n";
    echo "</head>\n";
    echo "<body>\n";
    echo "<h1><a href=\"" . t(MAIN_FILE, true) . "/?mode=db_admin\">DB</a></h1>\n";

    echo "<h2>Menu</h2>\n";
    echo "<ul>\n";
    echo "<li>SQL</li>\n";
    echo "<li><a href=\"" . t(MAIN_FILE, true) . "/?mode=db_admin&amp;work=import\">Import</a></li>\n";
    echo "<li><a href=\"" . t(MAIN_FILE, true) . "/?mode=db_admin&amp;work=export\">Export</a></li>\n";
    echo "</ul>\n";

    echo "<h2>SQL</h2>\n";
    echo "<form action=\"" . t(MAIN_FILE, true) . "/?mode=db_admin\" method=\"post\" id=\"exec_form\">\n";
    echo "<fieldset>\n";
    echo "<legend>execute</legend>\n";
    echo "<dl>\n";
    echo "<dt>SQL</dt>\n";
    echo "<dd><textarea name=\"sql\" cols=\"50\" rows=\"5\">" . t($view['sql'], true) . "</textarea></dd>\n";
    echo "</dl>\n";
    echo "<p><input type=\"submit\" value=\"execute\" /></p>\n";
    echo "</fieldset>\n";
    echo "</form>\n";

    if ($view['result']) {
        echo "<h2>Result</h2>\n";
        echo $view['result'];
    }

    echo "<pre><code>Rows: " . $view['count'] . " rows.\n";
    echo "Time: " . $view['time'] . " sec.</code></pre>\n";

    echo "</body>\n";
    echo "</html>\n";

    return;
}

/**
 * Output a migrate page for database.
 *
 */
function db_migrate()
{
    global $db;

    if (!file_exists(DATABASE_MIGRATE_PATH)) {
        error(DATABASE_MIGRATE_PATH . ' is not found.');
    }

    //initialize
    if (DATABASE_TYPE == 'pdo_mysql' || DATABASE_TYPE == 'mysql') {
        db_query('
            CREATE TABLE IF NOT EXISTS ' . DATABASE_PREFIX . 'levis_migrations(
                id          INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT \'id\',
                version     VARCHAR(14)  NOT NULL UNIQUE         COMMENT \'version\',
                description VARCHAR(255) NOT NULL                COMMENT \'description\',
                status      VARCHAR(80)  NOT NULL                COMMENT \'status\',
                installed   DATETIME                             COMMENT \'installed\',
                PRIMARY KEY(id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT \'migration\';
        ');
    } elseif (DATABASE_TYPE == 'pdo_pgsql' || DATABASE_TYPE == 'pgsql') {
        db_query('
            CREATE TABLE IF NOT EXISTS ' . DATABASE_PREFIX . 'levis_migrations(
                id          SERIAL       NOT NULL,
                version     VARCHAR(14)  NOT NULL UNIQUE,
                description VARCHAR(255) NOT NULL,
                status      VARCHAR(80)  NOT NULL,
                installed   TIMESTAMP,
                PRIMARY KEY(id)
            );
        ');
    } else {
        db_query('
            CREATE TABLE IF NOT EXISTS ' . DATABASE_PREFIX . 'levis_migrations(
                id          INTEGER,
                version     VARCHAR  NOT NULL UNIQUE,
                description VARCHAR  NOT NULL,
                status      VARCHAR  NOT NULL,
                installed   DATETIME,
                PRIMARY KEY(id)
            );
        ');
    }

    //succeeded
    $resource = db_query('SELECT * FROM ' . DATABASE_PREFIX . 'levis_migrations WHERE status = \'success\'');
    $results  = db_result($resource);

    $succeeded = array();
    foreach ($results as $result) {
        $succeeded[$result['version']] = true;
    }

    //target
    $targets = array();
    if ($dh = opendir(DATABASE_MIGRATE_PATH)) {
        while (($entry = readdir($dh)) !== false) {
            if (!is_file(DATABASE_MIGRATE_PATH  . $entry)) {
                continue;
            }

            if ($regexp = regexp_match('^([0-9\-]{14})-[_a-zA-Z0-9\-]+\.sql$', $entry)) {
                $version = $regexp[1];
            } else {
                continue;
            }

            if (isset($succeeded[$version])) {
                continue;
            }

            $targets[] = $entry;
        }
        closedir($dh);
    } else {
        error('opendir error.' . (DEBUG_LEVEL ? ' [' . $dir . ']' : ''));
    }

    sort($targets, SORT_STRING);

    //migrate
    $resource = db_query('DELETE FROM ' . DATABASE_PREFIX . 'levis_migrations WHERE status = ' . db_escape('pending') . ';');
    if (!$resource) {
        error('database query error.' . (DEBUG_LEVEL ? ' [' . db_error() . ']' : ''));
    }

    $migrate = '';
    foreach ($targets as $target) {
        if ($regexp = regexp_match('^([0-9\-]{14})-([_a-zA-Z0-9\-]+)\.sql$', $target)) {
            $version     = $regexp[1];
            $description = $regexp[2];
        } else {
            continue;
        }

        $resource = db_query('INSERT INTO ' . DATABASE_PREFIX . 'levis_migrations(version, description, status) VALUES(' . db_escape($version) . ', ' . db_escape($description) . ', ' . db_escape('pending') . ');');
        if (!$resource) {
            error('database query error.' . (DEBUG_LEVEL ? ' [' . db_error() . ']' : ''));
        }

        $error = false;
        if ($fp = fopen(DATABASE_MIGRATE_PATH  . $target, 'r')) {
            $sql  = '';
            $flag = true;

            db_transaction();

            while ($line = fgets($fp)) {
                $line = str_replace("\r\n", "\n", $line);
                $line = str_replace("\r", "\n", $line);

                if ((substr_count($line, '\'') - substr_count($line, '\\\'')) % 2 != 0) {
                    $flag = !$flag;
                }

                $sql .= $line;

                if (preg_match('/;$/', trim($line)) && $flag) {
                    $resource = db_query($sql, false, false);
                    if (!$resource) {
                        db_rollback();

                        $error = true;

                        $migrate .= $target . " ... NG\n";

                        break;
                    }

                    $sql = '';
                }
            }
            fclose($fp);

            if ($error == true) {
                break;
            }

            db_commit();
        } else {
            error('file can\'t read.');
        }

        if ($error == false) {
            $resource = db_query('UPDATE ' . DATABASE_PREFIX . 'levis_migrations SET status = ' . db_escape('success') . ', installed = ' . db_escape(localdate('Y-m-d H:i:s')) . ' WHERE version = ' . db_escape($version) . ';');
            if (!$resource) {
                error('database query error.' . (DEBUG_LEVEL ? ' [' . db_error() . ']' : ''));
            }

            $migrate .= $target . " ... OK\n";
        }
    }

    $resource = db_query('SELECT version FROM ' . DATABASE_PREFIX . 'levis_migrations WHERE status = \'success\' ORDER BY version DESC LIMIT 1');
    $results  = db_result($resource);

    if (empty($results)) {
        $version = '-';
    } else {
        $version = $results[0]['version'];
    }

    if ($migrate) {
        $migrate .= "\n";
    }
    $migrate .= "Database: " . DATABASE_NAME . "\n";
    $migrate .= "Version: " . $version . "\n";

    //history
    $resource   = db_query('SELECT * FROM ' . DATABASE_PREFIX . 'levis_migrations ORDER BY version');
    $migrations = db_result($resource);

    //result
    echo "<!DOCTYPE html>\n";
    echo "<html>\n";
    echo "<head>\n";
    echo "<meta charset=\"" . t(MAIN_CHARSET, true) . "\" />\n";
    echo "<title>DB Migrate</title>\n";

    style();

    echo "</head>\n";
    echo "<body>\n";

    echo "<h1>DB Migrate</h1>\n";
    echo "<pre><code>" . t($migrate, true) . "</code></pre>\n";
    echo "<table summary=\"migrations\">\n";
    echo "<tr>\n";
    echo "<th>version</th>\n";
    echo "<th>description</th>\n";
    echo "<th>status</th>\n";
    echo "<th>installed</th>\n";
    echo "</tr>\n";

    foreach ($migrations as $migration) {
        echo "<tr>\n";
        echo "<td><span style=\"font-family:monospace;\">" . h($migration['version'], true) . "</span></td>\n";
        echo "<td><span style=\"font-family:monospace;\">" . h($migration['description'], true) . "</span></td>\n";
        echo "<td><span style=\"font-family:monospace;\">" . h($migration['status'], true) . "</span></td>\n";
        echo "<td><span style=\"font-family:monospace;\">" . h($migration['installed'], true) . "</span></td>\n";
        echo "</tr>\n";
    }

    echo "</table>\n";
    echo "</body>\n";
    echo "</html>\n";

    exit;
}

/**
 * Output a scaffold page for database.
 *
 */
function db_scaffold()
{
    global $db;

    if (!file_exists(DATABASE_SCAFFOLD_PATH)) {
        error(DATABASE_SCAFFOLD_PATH . ' is not found.');
    }

    //initialize
    $app = 'app/';

    $models      = $app . 'models/';
    $views       = $app . 'views/';
    $controllers = $app . 'controllers/';

    $header_file = $views . 'header.php';
    $footer_file = $views . 'footer.php';

    $before_file = $app . 'controllers/before.php';
    $config_file = $app . 'config.php';

    $exclude_prefix = 'levis_';
    $primary_key    = 'id';

    $test = 'test/';

    //table
    $resource = db_query(db_sql('table_list'));
    $results  = db_result($resource);

    if (count($results) == 0) {
        error('table not found.');
    }

    $scaffold = '';

    foreach ($results as $result) {
        if (DATABASE_TYPE == 'pdo_mysql' || DATABASE_TYPE == 'mysql') {
            $table_comment = $result['Comment'];
        } else {
            $table_comment = null;
        }

        $table = array_shift($result);

        if (regexp_match('^' . DATABASE_PREFIX . $exclude_prefix, $table)) {
            continue;
        }

        //initialize
        $primary_flag = false;

        $model_file             = $models . $table . '.php';
        $view_index_file        = $views . $table . '/' . MAIN_DEFAULT_WORK . '.php';
        $view_post_file         = $views . $table . '/post.php';
        $controller_index_file  = $controllers . $table . '/' . MAIN_DEFAULT_WORK . '.php';
        $controller_post_file   = $controllers . $table . '/post.php';
        $controller_delete_file = $controllers . $table . '/delete.php';
        $test_model_file        = $test . 'model_' . $table . '.php';
        $test_view_file         = $test . 'view_' . $table . '.php';
        $test_controller_file   = $test . 'controller_' . $table . '.php';

        $define_sql = db_sql('table_define', $table);

        $define_resource = db_query($define_sql);
        $define_results  = db_result($define_resource);

        $model_validate = '';
        $model_default  = '';

        $view_head = '';
        $view_data = '';

        $view_form = '';

        $controller_validate = '';
        $controller_insert   = '';
        $controller_update   = '';

        $test_data = '';

        //indent
        $max_length = 0;
        foreach ($define_results as $define_result) {
            $field = '';
            if (DATABASE_TYPE == 'pdo_mysql' || DATABASE_TYPE == 'mysql') {
                $field = $define_result['Field'];
            } elseif (DATABASE_TYPE == 'pdo_pgsql' || DATABASE_TYPE == 'pgsql') {
                $field = $define_result['column_name'];
            } elseif (DATABASE_TYPE == 'pdo_sqlite' || DATABASE_TYPE == 'pdo_sqlite2' || DATABASE_TYPE == 'sqlite') {
                $field = $define_result['name'];
            }

            $max_length = strlen($field) > $max_length ? strlen($field) : $max_length;
        }

        foreach ($define_results as $define_result) {
            //define
            $field = '';
            $null  = false;
            if (DATABASE_TYPE == 'pdo_mysql' || DATABASE_TYPE == 'mysql') {
                $field   = $define_result['Field'];
                $type    = $define_result['Type'];
                $null    = $define_result['Null'] == 'YES' ? true : false;
                $comment = $define_result['Comment'];
            } elseif (DATABASE_TYPE == 'pdo_pgsql' || DATABASE_TYPE == 'pgsql') {
                $field   = $define_result['column_name'];
                $type    = $define_result['data_type'];
                $null    = $define_result['is_nullable'] == 'YES' ? true : false;
                $comment = null;
            } elseif (DATABASE_TYPE == 'pdo_sqlite' || DATABASE_TYPE == 'pdo_sqlite2' || DATABASE_TYPE == 'sqlite') {
                $field   = $define_result['name'];
                $type    = $define_result['type'];
                $null    = $define_result['notnull'] == 0 ? true : false;
                $comment = null;
            }

            if ($field == $primary_key) {
                $primary_flag = true;
            }

            //model
            if ($field == $primary_key || !$null) {
                $model_validate .= '    //' . ($comment ? $comment : $field) . "\n";
                $model_validate .= '    if (isset($queries[\'' . $field . '\'])) {' . "\n";
                $model_validate .= '        if ($queries[\'' . $field . '\'] == \'\') {' . "\n";
                $model_validate .= '            $messages[] = \'The ' . ($comment ? $comment : $field) . ' is required.\';' . "\n";
                $model_validate .= '        }' . "\n";
                $model_validate .= '    }' . "\n";
                $model_validate .= "\n";
            } else {
                $model_validate .= '    //' . ($comment ? $comment : $field) . "\n";
                $model_validate .= '    if (isset($queries[\'' . $field . '\'])) {' . "\n";
                $model_validate .= '    }' . "\n";
                $model_validate .= "\n";
            }

            $space = str_repeat(' ', $max_length - strlen($field));;

            if ($field == $primary_key) {
                $model_default .= '        \'' . $field . '\' ' . $space . '=> null,' . "\n";
            } elseif ($null) {
                $model_default .= '        \'' . $field . '\' ' . $space . '=> null,' . "\n";
            } elseif (regexp_match('(BLOB|TEXT|CHAR)', $type)) {
                $model_default .= '        \'' . $field . '\' ' . $space . '=> \'\',' . "\n";
            } else {
                $model_default .= '        \'' . $field . '\' ' . $space . '=> 0,' . "\n";
            }

            //view
            $view_head .= '                <th>' . ($comment ? $comment : $field) . '</th>' . "\n";

            if ($field == $primary_key) {
                $view_data .= '                <td><a href="<?php t(MAIN_FILE) ?>/' . $table . '/post?' . $primary_key . '=<?php t($data[\'' . $primary_key . '\']) ?>"><?php h($data[\'' . $field . '\']) ?></a></td>' . "\n";
            } else {
                $view_data .= '                <td><?php h($data[\'' . $field . '\']) ?></td>' . "\n";
            }

            if (regexp_match('(BLOB|TEXT)', $type)) {
                $input = '<textarea name="' . $field . '" rows="10" cols="50"><?php t($view[\'data\'][\'' . $field . '\']) ?></textarea>';
            } elseif (regexp_match('(CHAR)', $type)) {
                $input = '<input type="text" name="' . $field . '" size="30" value="<?php t($view[\'data\'][\'' . $field . '\']) ?>" />';
            } else {
                $input = '<input type="text" name="' . $field . '" size="10" value="<?php t($view[\'data\'][\'' . $field . '\']) ?>" />';
            }

            if ($field == $primary_key) {
                $view_form .= '                    <dt>' . ($comment ? $comment : $field) . ($null ? '' : '(required)') . '</dt>' . "\n";
                $view_form .= '                        <dd>' . "\n";
                $view_form .= '                            <?php if (empty($_GET[\'' . $primary_key . '\'])) : ?>' . "\n";
                $view_form .= '                            ' . $input . "\n";
                $view_form .= '                            <?php else : ?>' . "\n";
                $view_form .= '                            <em><?php h($view[\'data\'][\'' . $field . '\']) ?></em><input type="hidden" name="' . $field . '" value="<?php t($view[\'data\'][\'' . $field . '\']) ?>" />' . "\n";
                $view_form .= '                            <?php endif ?>' . "\n";
                $view_form .= '                        </dd>' . "\n";
            } else {
                $view_form .= '                    <dt>' . ($comment ? $comment : $field) . ($null ? '' : '(required)') . '</dt>' . "\n";
                $view_form .= '                        <dd>' . $input . '</dd>' . "\n";
            }

            //controller
            $controller_validate .= '        \'' . $field . '\' ' . $space . '=> $_POST[\'' . $field . '\'],' . "\n";
            $controller_insert   .= '                \'' . $field . '\' ' . $space . '=> $_POST[\'' . $field . '\'],' . "\n";

            if ($field != $primary_key) {
                $controller_update .= '                \'' . $field . '\' ' . $space . '=> $_POST[\'' . $field . '\'],' . "\n";
            }

            //test
            if ($field == $primary_key) {
                $test_data .= '            \'' . $field . '\' ' . $space . '=> [N],' . "\n";
            } elseif ($null) {
                $test_data .= '            \'' . $field . '\' ' . $space . '=> null,' . "\n";
            } elseif (regexp_match('(BLOB|TEXT|CHAR)', $type)) {
                $test_data .= '            \'' . $field . '\' ' . $space . '=> \'TEST[N]\',' . "\n";
            } else {
                $test_data .= '            \'' . $field . '\' ' . $space . '=> [N],' . "\n";
            }
        }

        //heading
        $scaffold .= '[' . $table . ']' . "\n";

        //model
        $buffer  = '<?php' . "\n";
        $buffer .= "\n";

        $buffer .= '/**' . "\n";
        $buffer .= ' * Validate for ' . ($table_comment ? $table_comment : $table) . "\n";
        $buffer .= ' *' . "\n";
        $buffer .= ' * @param  array  $queries' . "\n";
        $buffer .= ' * @return array' . "\n";
        $buffer .= ' */' . "\n";
        $buffer .= 'function validate_' . $table . '($queries)' . "\n";
        $buffer .= '{' . "\n";
        $buffer .= '    $messages = array();' . "\n";
        $buffer .= '' . "\n";
        $buffer .= $model_validate;
        $buffer .= '    return $messages;' . "\n";
        $buffer .= '}' . "\n";
        $buffer .= "\n";

        $buffer .= '/**' . "\n";
        $buffer .= ' * Default for ' . ($table_comment ? $table_comment : $table) . "\n";
        $buffer .= ' *' . "\n";
        $buffer .= ' * @return array' . "\n";
        $buffer .= ' */' . "\n";
        $buffer .= 'function default_' . $table . '()' . "\n";
        $buffer .= '{' . "\n";
        $buffer .= '    return array(' . "\n";
        $buffer .= $model_default;
        $buffer .= '    );' . "\n";
        $buffer .= '}' . "\n";

        $scaffold .= db_scaffold_output($model_file, $buffer);

        //view
        $buffer  = '<?php import(\'app/views/header.php\') ?>' . "\n";
        $buffer .= "\n";
        $buffer .= '        <h2>' . ($table_comment ? $table_comment : $table) . '</h2>' . "\n";
        $buffer .= '        <ul>' . "\n";
        $buffer .= '            <li><a href="<?php t(MAIN_FILE) ?>/' . $table . '/post">post</a></li>' . "\n";
        $buffer .= '        </ul>' . "\n";
        $buffer .= '        <table summary="' . ($table_comment ? $table_comment : $table) . '">' . "\n";
        $buffer .= '            <tr>' . "\n";
        $buffer .= $view_head;
        $buffer .= '            </tr>' . "\n";
        $buffer .= '            <?php foreach ($view[\'' . $table . '\'] as $data) : ?>' . "\n";
        $buffer .= '            <tr>' . "\n";
        $buffer .= $view_data;
        $buffer .= '            </tr>' . "\n";
        $buffer .= '            <?php endforeach ?>' . "\n";
        $buffer .= '        </table>' . "\n";
        $buffer .= "\n";
        $buffer .= '<?php import(\'app/views/footer.php\') ?>' . "\n";

        $scaffold .= db_scaffold_output($view_index_file, $buffer);

        $buffer  = '<?php import(\'app/views/header.php\') ?>' . "\n";
        $buffer .= "\n";
        $buffer .= '        <h2>' . ($table_comment ? $table_comment : $table) . '</h2>' . "\n";
        $buffer .= '        <form action="<?php t(MAIN_FILE) ?>/' . $table . '/post' . ($primary_flag ? '<?php $view[\'data\'][\'' . $primary_key . '\'] ? t(\'?' . $primary_key . '=\' . $view[\'data\'][\'' . $primary_key . '\']) : \'\' ?>' : '') . '" method="post">' . "\n";
        $buffer .= '            <fieldset>' . "\n";
        $buffer .= '                <legend>' . ($table_comment ? $table_comment : $table) . '</legend>' . "\n";
        $buffer .= '                <dl>' . "\n";
        $buffer .= $view_form;
        $buffer .= '                </dl>' . "\n";
        $buffer .= '                <p><input type="submit" value="post" /></p>' . "\n";
        $buffer .= '            </fieldset>' . "\n";
        $buffer .= '        </form>' . "\n";

        if ($primary_flag) {
            $buffer .= '        <?php if (!empty($_GET[\'' . $primary_key . '\'])) : ?>' . "\n";
            $buffer .= '        <h2>delete</h2>' . "\n";
            $buffer .= '        <form action="<?php t(MAIN_FILE) ?>/' . $table . '/delete?' . $primary_key . '=\' . t($view[\'data\'][\'' . $primary_key . '\']) ?>" method="post">' . "\n";
            $buffer .= '            <fieldset>' . "\n";
            $buffer .= '                <legend>' . ($table_comment ? $table_comment : $table) . '</legend>' . "\n";
            $buffer .= '                <input type="hidden" name="' . $primary_key . '" value="<?php t($view[\'data\'][\'' . $primary_key . '\']) ?>" /></dd>' . "\n";
            $buffer .= '                <p><input type="submit" value="delete" /></p>' . "\n";
            $buffer .= '            </fieldset>' . "\n";
            $buffer .= '        </form>' . "\n";
            $buffer .= '        <?php endif ?>' . "\n";
        }

        $buffer .= "\n";
        $buffer .= '<?php import(\'app/views/footer.php\') ?>' . "\n";

        $scaffold .= db_scaffold_output($view_post_file, $buffer);

        //controller
        $buffer  = '<?php' . "\n";
        $buffer .= "\n";
        $buffer .= '$view[\'' . $table . '\'] = select_' . $table . '(array(' . "\n";
        $buffer .= '    \'limit\' => array(' . "\n";
        $buffer .= '        \':limit\',' . "\n";
        $buffer .= '        array(' . "\n";
        $buffer .= '            \'limit\' => $GLOBALS[\'limits\'][\'' . $table . '\'],' . "\n";
        $buffer .= '        ),' . "\n";
        $buffer .= '    ),' . "\n";
        $buffer .= '));' . "\n";

        $scaffold .= db_scaffold_output($controller_index_file, $buffer);

        $buffer  = '<?php' . "\n";
        $buffer .= "\n";
        $buffer .= 'if ($_SERVER[\'REQUEST_METHOD\'] == \'POST\') {' . "\n";
        $buffer .= '    $warnings = validate_' . $table . '(array(' . "\n";
        $buffer .= $controller_validate;
        $buffer .= '    ));' . "\n";
        $buffer .= '    if (!empty($warnings)) {' . "\n";
        $buffer .= '        warning($warnings);' . "\n";
        $buffer .= '    }' . "\n";
        $buffer .= '    if (isset($_GET[\'' . $primary_key . '\'])) {' . "\n";

        if ($primary_flag) {
            $buffer .= '        $resource = update_' . $table . '(array(' . "\n";
            $buffer .= '            \'set\' => array(' . "\n";
            $buffer .= $controller_update;
            $buffer .= '            ),' . "\n";
            $buffer .= '            \'where\' => array(' . "\n";
            $buffer .= '                \'' . $primary_key . ' = :' . $primary_key . '\',' . "\n";
            $buffer .= '                array(' . "\n";
            $buffer .= '                    \'' . $primary_key . '\' => $_POST[\'' . $primary_key . '\'],' . "\n";
            $buffer .= '                ),' . "\n";
            $buffer .= '            ),' . "\n";
            $buffer .= '        ));' . "\n";
            $buffer .= '        if (!$resource) {' . "\n";
            $buffer .= '            error(\'update error.\');' . "\n";
            $buffer .= '        }' . "\n";
        }

        $buffer .= '    } else {' . "\n";
        $buffer .= '        $resource = insert_' . $table . '(array(' . "\n";
        $buffer .= '            \'values\' => array(' . "\n";
        $buffer .= $controller_insert;
        $buffer .= '            ),' . "\n";
        $buffer .= '        ));' . "\n";
        $buffer .= '        if (!$resource) {' . "\n";
        $buffer .= '            error(\'insert error.\');' . "\n";
        $buffer .= '        }' . "\n";
        $buffer .= '    }' . "\n";
        $buffer .= "\n";
        $buffer .= '    redirect(\'/' . $table . '/' . MAIN_DEFAULT_WORK . '\');' . "\n";
        $buffer .= '} else {' . "\n";
        $buffer .= '    if (isset($_GET[\'' . $primary_key . '\'])) {' . "\n";

        if ($primary_flag) {
            $buffer .= '        $' . $table . ' = select_' . $table . '(array(' . "\n";
            $buffer .= '            \'where\' => array(' . "\n";
            $buffer .= '                \'' . $primary_key . ' = :' . $primary_key . '\',' . "\n";
            $buffer .= '                array(' . "\n";
            $buffer .= '                    \'' . $primary_key . '\' => $_GET[\'' . $primary_key . '\'],' . "\n";
            $buffer .= '                ),' . "\n";
            $buffer .= '            ),' . "\n";
            $buffer .= '        ));' . "\n";
            $buffer .= '        if (empty($' . $table . ')) {' . "\n";
            $buffer .= '            error(\'data not found.\');' . "\n";
            $buffer .= '        } else {' . "\n";
            $buffer .= '            $view[\'data\'] = $' . $table . '[0];' . "\n";
            $buffer .= '        }' . "\n";
        }

        $buffer .= '    } else {' . "\n";
        $buffer .= '        $view[\'data\'] = default_' . $table . '();' . "\n";
        $buffer .= '    }' . "\n";
        $buffer .= '}' . "\n";

        $scaffold .= db_scaffold_output($controller_post_file, $buffer);

        if ($primary_flag) {
            $buffer  = '<?php' . "\n";
            $buffer .= "\n";
            $buffer .= 'if ($_SERVER[\'REQUEST_METHOD\'] == \'POST\') {' . "\n";
            $buffer .= '    $resource = delete_' . $table . '(array(' . "\n";
            $buffer .= '        \'where\' => array(' . "\n";
            $buffer .= '            \'' . $primary_key . ' = :' . $primary_key . '\',' . "\n";
            $buffer .= '            array(' . "\n";
            $buffer .= '                \'' . $primary_key . '\' => $_POST[\'' . $primary_key . '\'],' . "\n";
            $buffer .= '            ),' . "\n";
            $buffer .= '        ),' . "\n";
            $buffer .= '    ));' . "\n";
            $buffer .= '    if (!$resource) {' . "\n";
            $buffer .= '        error(\'delete error.\');' . "\n";
            $buffer .= '    }' . "\n";
            $buffer .= '} else {' . "\n";
            $buffer .= '    error(\'method error.\');' . "\n";
            $buffer .= '}' . "\n";
            $buffer .= "\n";
            $buffer .= 'redirect(\'/' . $table . '/' . MAIN_DEFAULT_WORK . '\');' . "\n";

            $scaffold .= db_scaffold_output($controller_delete_file, $buffer);
        }

        //test data
        $test_insert  = '';
        $test_insert .= '    $insert_' . $table . ' = array(' . "\n";
        $test_insert .= '        1 => array(' . "\n";
        $test_insert .= str_replace('[N]', 1, $test_data);
        $test_insert .= '        ),' . "\n";
        $test_insert .= '        2 => array(' . "\n";
        $test_insert .= str_replace('[N]', 2, $test_data);
        $test_insert .= '        ),' . "\n";
        $test_insert .= '        3 => array(' . "\n";
        $test_insert .= str_replace('[N]', 3, $test_data);
        $test_insert .= '        ),' . "\n";
        $test_insert .= '    );' . "\n";

        $test_update = '';
        $test_update .= '    $update_' . $table . ' = array(' . "\n";
        $test_update .= '        3 => array(' . "\n";
        $test_update .= str_replace('[N]', 3, $test_data);
        $test_update .= '        ),' . "\n";
        $test_update .= '    );' . "\n";

        //test model
        $buffer  = '<?php' . "\n";
        $buffer .= "\n";
        $buffer .= 'model(\'' . $table . '.php\');' . "\n";
        $buffer .= "\n";
        $buffer .= 'db_transaction();' . "\n";
        $buffer .= "\n";
        $buffer .= '//insert' . "\n";
        $buffer .= '{' . "\n";
        $buffer .= '    //data' . "\n";
        $buffer .= $test_insert;
        $buffer .= "\n";
        $buffer .= '    //insert' . "\n";
        $buffer .= '    foreach ($insert_' . $table . ' as $insert_data) {' . "\n";
        $buffer .= '        $warnings = validate_' . $table . '($insert_data);' . "\n";
        $buffer .= '        if (empty($warnings)) {' . "\n";
        $buffer .= '            insert_' . $table . '(array(' . "\n";
        $buffer .= '                \'values\' => $insert_data,' . "\n";
        $buffer .= '            ));' . "\n";
        $buffer .= '        } else {' . "\n";
        $buffer .= '            debug($warnings);' . "\n";
        $buffer .= '        }' . "\n";
        $buffer .= '    }' . "\n";
        $buffer .= "\n";
        $buffer .= '    //test' . "\n";
        $buffer .= '    $' . $table . ' = select_' . $table . '(array(' . "\n";
        $buffer .= '        \'limit\' => 10,' . "\n";
        $buffer .= '    ));' . "\n";
        $buffer .= "\n";
        $buffer .= '    test_equals(\'count_' . $table . '\', count($' . $table . '), 3);' . "\n";
        $buffer .= "\n";
        $buffer .= '    for ($i = 1; $i <= 3; $i++) {' . "\n";
        $buffer .= '        $inserted_data = array_shift($' . $table . ');' . "\n";
        $buffer .= '        $test_data     = array(' . "\n";
        $buffer .= '            $i => $inserted_data,' . "\n";
        $buffer .= '        );' . "\n";
        $buffer .= '        test_array_subset(\'insert_' . $table . ' \' . $i, $test_data, $insert_' . $table . '[$i]);' . "\n";
        $buffer .= '    }' . "\n";
        $buffer .= '}' . "\n";
        $buffer .= "\n";
        $buffer .= '//update' . "\n";
        $buffer .= '{' . "\n";
        $buffer .= '    //data' . "\n";
        $buffer .= $test_update;
        $buffer .= "\n";
        $buffer .= '    //update' . "\n";
        $buffer .= '    $warnings = validate_' . $table . '($update_' . $table . '[3]);' . "\n";
        $buffer .= '    if (empty($warnings)) {' . "\n";
        $buffer .= '        update_' . $table . '(array(' . "\n";
        $buffer .= '            \'set\'   => $update_' . $table . '[3],' . "\n";
        $buffer .= '            \'where\' => \'id = 3\',' . "\n";
        $buffer .= '        ));' . "\n";
        $buffer .= '    } else {' . "\n";
        $buffer .= '        debug($warnings);' . "\n";
        $buffer .= '    }' . "\n";
        $buffer .= "\n";
        $buffer .= '    //test' . "\n";
        $buffer .= '    $' . $table . ' = select_' . $table . '(array(' . "\n";
        $buffer .= '        \'limit\' => 10,' . "\n";
        $buffer .= '    ));' . "\n";
        $buffer .= "\n";
        $buffer .= '    $updated_data = array_pop($' . $table . ');' . "\n";
        $buffer .= '    $test_data    = array(' . "\n";
        $buffer .= '        3 => $updated_data,' . "\n";
        $buffer .= '    );' . "\n";
        $buffer .= '    test_array_subset(\'update_' . $table . '\', $test_data, $update_' . $table . '[3]);' . "\n";
        $buffer .= '}' . "\n";
        $buffer .= "\n";
        $buffer .= '//delete' . "\n";
        $buffer .= '{' . "\n";
        $buffer .= '    //delete' . "\n";
        $buffer .= '    delete_' . $table . '(array(' . "\n";
        $buffer .= '        \'where\' => \'id = 3\',' . "\n";
        $buffer .= '    ));' . "\n";
        $buffer .= "\n";
        $buffer .= '    //test' . "\n";
        $buffer .= '    $' . $table . ' = select_' . $table . '(array(' . "\n";
        $buffer .= '        \'limit\' => 10,' . "\n";
        $buffer .= '    ));' . "\n";
        $buffer .= "\n";
        $buffer .= '    test_equals(\'delete_' . $table . '\', count($' . $table . '), 2);' . "\n";
        $buffer .= '}' . "\n";
        $buffer .= "\n";
        $buffer .= 'db_rollback();' . "\n";

        $scaffold .= db_scaffold_output($test_model_file, $buffer);

        //test view
        $buffer  = '<?php' . "\n";
        $buffer .= "\n";
        $buffer .= '//index' . "\n";
        $buffer .= '{' . "\n";
        $buffer .= '    //data' . "\n";
        $buffer .= $test_insert;
        $buffer .= "\n";
        $buffer .= '    //assign' . "\n";
        $buffer .= '    $view[\'' . $table . '\'] = $insert_' . $table . ';' . "\n";
        $buffer .= "\n";
        $buffer .= '    //test' . "\n";
        $buffer .= '    $html = view(\'' . $table . '/index.php\', true);' . "\n";
        $buffer .= "\n";
        $buffer .= '    test_contains(\'' . $table . '/index 1\', $html, \'<td>\' . $insert_' . $table . '[1][\'' . $primary_key . '\'] . \'</td>\');' . "\n";
        $buffer .= '    test_contains(\'' . $table . '/index 2\', $html, \'<td>\' . $insert_' . $table . '[2][\'' . $primary_key . '\'] . \'</td>\');' . "\n";
        $buffer .= '    test_contains(\'' . $table . '/index 3\', $html, \'<td>\' . $insert_' . $table . '[3][\'' . $primary_key . '\'] . \'</td>\');' . "\n";
        $buffer .= '}' . "\n";
        $buffer .= "\n";
        $buffer .= '//post' . "\n";
        $buffer .= '{' . "\n";
        $buffer .= '    $view[\'data\'] = $insert_' . $table . '[1];' . "\n";
        $buffer .= "\n";
        $buffer .= '    $html = view(\'' . $table . '/post.php\', true);' . "\n";
        $buffer .= "\n";
        $buffer .= '    test_contains(\'' . $table . '/post\', $html, \'value="\' . $insert_' . $table . '[1][\'' . $primary_key . '\'] . \'"\');' . "\n";
        $buffer .= '}' . "\n";

        $scaffold .= db_scaffold_output($test_view_file, $buffer);

        //test controller
        $buffer  = '<?php' . "\n";
        $buffer .= "\n";
        $buffer .= 'model();' . "\n";
        $buffer .= "\n";
        $buffer .= 'db_transaction();' . "\n";
        $buffer .= "\n";
        $buffer .= '//index' . "\n";
        $buffer .= '{' . "\n";
        $buffer .= '    //data' . "\n";
        $buffer .= $test_insert;
        $buffer .= "\n";
        $buffer .= '    //insert' . "\n";
        $buffer .= '    foreach ($insert_' . $table . ' as $insert_data) {' . "\n";
        $buffer .= '        $warnings = validate_' . $table . '($insert_data);' . "\n";
        $buffer .= '        if (empty($warnings)) {' . "\n";
        $buffer .= '            insert_' . $table . '(array(' . "\n";
        $buffer .= '                \'values\' => $insert_data,' . "\n";
        $buffer .= '            ));' . "\n";
        $buffer .= '        } else {' . "\n";
        $buffer .= '            debug($warnings);' . "\n";
        $buffer .= '        }' . "\n";
        $buffer .= '    }' . "\n";
        $buffer .= "\n";
        $buffer .= '    //test' . "\n";
        $buffer .= '    $params = array(\'' . $table . '\', \'index\');' . "\n";
        $buffer .= '    controller(\'' . $table . '/index.php\');' . "\n";
        $buffer .= '    $html = view(\'' . $table . '/index.php\', true);' . "\n";
        $buffer .= "\n";
        $buffer .= '    test_contains(\'' . $table . '/index\', $html, \'<td>\' . $insert_' . $table . '[1][\'' . $primary_key . '\'] . \'</td>\');' . "\n";
        $buffer .= '    test_contains(\'' . $table . '/index\', $html, \'<td>\' . $insert_' . $table . '[2][\'' . $primary_key . '\'] . \'</td>\');' . "\n";
        $buffer .= '    test_contains(\'' . $table . '/index\', $html, \'<td>\' . $insert_' . $table . '[3][\'' . $primary_key . '\'] . \'</td>\');' . "\n";
        $buffer .= '}' . "\n";
        $buffer .= "\n";
        $buffer .= '//post' . "\n";
        $buffer .= '{' . "\n";
        $buffer .= '    //test' . "\n";
        $buffer .= '    $params = array(\'' . $table . '\', \'post\');' . "\n";
        $buffer .= '    $_GET[\'' . $primary_key . '\'] = 3;' . "\n";
        $buffer .= '    controller(\'' . $table . '/post.php\');' . "\n";
        $buffer .= '    $html = view(\'' . $table . '/post.php\', true);' . "\n";
        $buffer .= "\n";
        $buffer .= '    test_contains(\'' . $table . '/post\', $html, \'value="\' . $insert_' . $table . '[3][\'' . $primary_key . '\'] . \'"\');' . "\n";
        $buffer .= '}' . "\n";
        $buffer .= "\n";
        $buffer .= 'db_rollback();' . "\n";

        $scaffold .= db_scaffold_output($test_controller_file, $buffer);

        $scaffold .= "\n";
    }

    //home
    $view_home_file       = $views . MAIN_DEFAULT_MODE . '/' . MAIN_DEFAULT_WORK . '.php';
    $controller_home_file = $controllers . MAIN_DEFAULT_MODE . '/' . MAIN_DEFAULT_WORK . '.php';

    $scaffold .= '[index]' . "\n";

    $buffer  = '<?php import(\'app/views/header.php\') ?>' . "\n";
    $buffer .= "\n";
    $buffer .= '        <ul>' . "\n";

    foreach ($results as $result) {
        if (DATABASE_TYPE == 'pdo_mysql' || DATABASE_TYPE == 'mysql') {
            $table_comment = $result['Comment'];
        } else {
            $table_comment = null;
        }

        $table = array_shift($result);

        if (regexp_match('^' . DATABASE_PREFIX . $exclude_prefix, $table)) {
            continue;
        }

        $buffer .= '            <li><a href="<?php t(MAIN_FILE) ?>/' . $table . '">' . ($table_comment ? $table_comment : $table) . '</a></li>' . "\n";
    }

    $buffer .= '        </ul>' . "\n";

    $buffer .= "\n";
    $buffer .= '<?php import(\'app/views/footer.php\') ?>' . "\n";

    $scaffold .= db_scaffold_output($view_home_file, $buffer);

    $buffer  = '<?php' . "\n";

    $scaffold .= db_scaffold_output($controller_home_file, $buffer);

    //header
    $buffer  = '<!DOCTYPE html>' . "\n";
    $buffer .= '<html>' . "\n";
    $buffer .= '    <head>' . "\n";
    $buffer .= '        <meta charset="<?php t(MAIN_CHARSET) ?>" />' . "\n";
    $buffer .= '        <title>scaffold</title>' . "\n";
    $buffer .= '    </head>' . "\n";
    $buffer .= '    <body>' . "\n";
    $buffer .= '        <h1>scaffold</h1>' . "\n";

    $scaffold .= db_scaffold_output($header_file, $buffer);

    //footer
    $buffer  = '    </body>' . "\n";
    $buffer .= '</html>' . "\n";

    $scaffold .= db_scaffold_output($footer_file, $buffer);

    //config
    $buffer  = '<?php' . "\n";
    $buffer .= "\n";
    $buffer .= 'import(\'' . $config_file . '\');' . "\n";

    $scaffold .= db_scaffold_output($before_file, $buffer);

    $buffer  = '<?php' . "\n";
    $buffer .= "\n";
    $buffer .= '$GLOBALS[\'limits\'] = array(' . "\n";

    $max_length = 0;
    foreach ($results as $result) {
        $table = array_shift($result);

        if (regexp_match('^' . DATABASE_PREFIX . $exclude_prefix, $table)) {
            continue;
        }

        $max_length = strlen($table) > $max_length ? strlen($table) : $max_length;
    }

    foreach ($results as $result) {
        $table = array_shift($result);

        if (regexp_match('^' . DATABASE_PREFIX . $exclude_prefix, $table)) {
            continue;
        }

        $space = str_repeat(' ', $max_length - strlen($table));;

        $buffer .= '    \'' . $table . '\' ' . $space . '=> 10,' . "\n";
    }

    $buffer .= ');' . "\n";

    $scaffold .= db_scaffold_output($config_file, $buffer);

    $scaffold .= "\n";
    $scaffold .= "Complete\n";

    //result
    echo "<!DOCTYPE html>\n";
    echo "<html>\n";
    echo "<head>\n";
    echo "<meta charset=\"" . t(MAIN_CHARSET, true) . "\" />\n";
    echo "<title>DB Scaffold</title>\n";

    style();

    echo "</head>\n";
    echo "<body>\n";
    echo "<h1>DB Scaffold</h1>\n";
    echo "<pre><code>" . t($scaffold, true) . "</code></pre>\n";
    echo "</body>\n";
    echo "</html>\n";

    exit;
}

/**
 * Output the scaffolded data from database.
 *
 */
function db_scaffold_output($file, $data)
{
    $file = DATABASE_SCAFFOLD_PATH . $file;
    $info = pathinfo($file);

    $result = '';

    if (!is_dir($info['dirname'])) {
        if (mkdir($info['dirname'], 0755, true)) {
            $result .= 'OK: ' . $info['dirname'] . "\n";
        } else {
            $result .= 'NG: ' . $info['dirname'] . "\n";
        }
    }

    if ($fp = fopen($file, 'w')) {
        fwrite($fp, $data);
        fclose($fp);

        $result .= 'OK: ' . $file . "\n";
    } else {
        $result .= 'NG: ' . $file . "\n";
    }

    return $result;
}

/**
 * Get the sql data for database.
 *
 * @param  string  $type
 * @param  string|null  $table
 * @return string
 */
function db_sql($type, $table = null)
{
    global $db;

    if (DATABASE_TYPE == 'pdo_mysql' || DATABASE_TYPE == 'mysql') {
        if ($type == 'table_list') {
            $sql = '
                SHOW TABLE STATUS;
            ';
        } elseif ($type == 'table_create') {
            $sql = '
                SHOW CREATE TABLE ' . $table . ';
            ';
        } elseif ($type == 'table_define') {
            $sql = '
                SHOW FULL COLUMNS FROM ' . $table . ';
            ';
        }
    } elseif (DATABASE_TYPE == 'pdo_pgsql' || DATABASE_TYPE == 'pgsql') {
        if ($type == 'table_list') {
            $sql = '
                SELECT
                    pg_class.relname AS relname
                FROM
                    pg_class INNER JOIN pg_namespace ON pg_class.relnamespace = pg_namespace.oid
                WHERE
                    pg_class.relkind = \'r\' AND pg_namespace.nspname = \'public\';
            ';
        } elseif ($type == 'table_create') {
            $sql = '
                SELECT
                    CASE
                        WHEN tb.relkind = \'r\' THEN(
                            SELECT \'CREATE TABLE "' . $table . '"(\' || chr(10) || array_to_string(
                                ARRAY(
                                    SELECT \' "\' || "Column" || \'" \'|| "Type" || "Modifiers" || "Index" FROM(
                                        /* Column */
                                        SELECT
                                            at.attnum, ns.nspname AS schema, tb.relname AS table, at.attname AS "Column",
                                            /* Type */
                                            CASE
                                                WHEN at.attinhcount <> 0 OR at.attisdropped THEN null
                                                ELSE
                                                    CASE
                                                        WHEN tp.typname = \'int2\'   THEN \'SMALLINT\'
                                                        WHEN tp.typname = \'int4\'   THEN \'INTEGER\'
                                                        WHEN tp.typname = \'int8\'   THEN \'BIGINT\'
                                                        WHEN tp.typname = \'float4\' THEN \'REAL\'
                                                        WHEN tp.typname = \'float8\' THEN \'DOUBLE PRECISION\'
                                                        WHEN tp.typname = \'bpchar\' THEN \'CHAR\'
                                                        ELSE UPPER(tp.typname)
                                                    END ||
                                                    CASE
                                                        WHEN at.attlen >= 0             THEN \'\'
                                                        WHEN at.atttypmod < 4           THEN \'\'
                                                        WHEN tp.typname <> \'numeric\'  THEN \'(\' || at.atttypmod - 4 || \')\'
                                                        WHEN (at.atttypmod & 65535) = 4 THEN \'(\' || (at.atttypmod >> 16) || \')\'
                                                        ELSE \'(\' || (at.atttypmod >> 16) || \',\' || (at.atttypmod & 65535) - 4 || \')\'
                                                    END
                                            END AS "Type",
                                            /* Modifiers */
                                            CASE
                                                WHEN at.attnotnull THEN \' NOT NULL\'
                                                ELSE \'\'
                                            END ||
                                            CASE
                                                WHEN ad.adbin IS NULL THEN \'\'
                                                ELSE \' DEFAULT \' || UPPER(pg_get_expr(ad.adbin, tb.oid))
                                            END AS "Modifiers",
                                            /* one-column Index */
                                            CASE
                                                WHEN ix.indexrelid IS NULL THEN \'\'
                                                ELSE
                                                    CASE
                                                        WHEN ix.indisprimary THEN \' PRIMARY KEY\'
                                                        WHEN ix.indisunique  THEN \' UNIQUE\'
                                                        ELSE \'\'
                                                    END
                                            END AS "Index"
                                        FROM
                                            pg_attribute at
                                            INNER JOIN pg_type tp ON at.atttypid = tp.oid
                                            LEFT OUTER JOIN pg_attrdef ad ON ad.adrelid = tb.oid AND ad.adnum = at.attnum
                                            LEFT OUTER JOIN pg_index ix ON ix.indrelid = tb.oid AND ix.indnatts = 1 AND at.attnum = ix.indkey[0]
                                            LEFT OUTER JOIN pg_class ic ON ix.indexrelid = ic.oid
                                            LEFT OUTER JOIN pg_am    am ON ic.relam = am.oid
                                        WHERE
                                            tb.oid = at.attrelid AND at.attnum >= 1
                                    ) AS columns ORDER BY attnum
                                ), \',\' || chr(10)
                            )
                            ||
                            (
                                SELECT
                                    CASE
                                        WHEN COUNT(*) = 0 THEN \'\'
                                        ELSE \',\' || chr(10) || \' \' || array_to_string(
                                            ARRAY(
                                                SELECT
                                                    CASE
                                                        WHEN indisprimary THEN \'PRIMARY KEY \'
                                                        ELSE \'UNIQUE \'
                                                    END
                                                    || substr(indexdef, strpos(indexdef, \'(\'), strpos(indexdef, \')\') - strpos(indexdef, \'(\') + 1) || \' /* \'||index||\' */\'
                                                FROM
                                                (
                                                    SELECT
                                                        ic.relname AS index, ns.nspname AS schema, tb.relname AS table, ix.indnatts, ix.indisunique, ix.indisprimary, am.amname, ix.indkey, pg_get_indexdef(ic.oid) AS indexdef
                                                    FROM
                                                        pg_index ix
                                                        INNER JOIN pg_class ic ON ix.indexrelid = ic.oid
                                                        INNER JOIN pg_am    am ON ic.relam = am.oid
                                                    WHERE
                                                        ix.indrelid = tb.oid AND ix.indnatts > 1 AND (ix.indisprimary OR ix.indisunique)
                                                ) AS def ORDER BY indisprimary desc, index
                                            ), \',\'||chr(10)
                                        )
                                    END
                                FROM
                                    pg_index ix
                                WHERE
                                    ix.indrelid = tb.oid AND ix.indnatts > 1 AND (ix.indisprimary OR ix.indisunique)
                            ) || chr(10) || \');\'
                        )
                        END
                FROM
                    pg_class tb
                    INNER JOIN pg_namespace ns ON tb.relnamespace = ns.oid
                WHERE
                    tb.relname = \'' . $table . '\';
            ';
        } elseif ($type == 'table_define') {
            $sql = '
                SELECT
                    column_name, data_type, is_nullable
                FROM
                    information_schema.columns
                WHERE
                    table_schema = \'public\' AND table_name = \'' . $table . '\';
            ';
        }
    } elseif (DATABASE_TYPE == 'pdo_sqlite' || DATABASE_TYPE == 'pdo_sqlite2' || DATABASE_TYPE == 'sqlite') {
        if ($type == 'table_list') {
            $sql = '
                SELECT
                    name
                FROM
                    sqlite_master
                WHERE
                    type = \'table\';
            ';
        } elseif ($type == 'table_create') {
            $sql = '
                SELECT
                    sql
                FROM
                    sqlite_master
                WHERE
                    tbl_name = \'' . $table . '\';
            ';
        } elseif ($type == 'table_define') {
            $sql = '
                PRAGMA TABLE_INFO(\'' . $table . '\');
            ';
        }
    }

    $sql = preg_replace('/\s+/', ' ', $sql);
    $sql = preg_replace('/^\s+/', '', $sql);
    $sql = preg_replace('/\s+$/', '', $sql);

    return $sql;
}
