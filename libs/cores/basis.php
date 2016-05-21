<?php

/*******************************************************************************

 Functions for Basis

*******************************************************************************/

if (!defined('MAIN_PATH')) {
    define('MAIN_PATH', '');
}

$params = array();
$db     = array();
$view   = array();

/**
 * Load the given file.
 *
 * @param  string  $file
 * @param  bool  $once
 * @param  bool  $ignore
 * @return void
 */
function import($file, $once = true, $ignore = false)
{
    global $params, $db, $view;

    if (!empty($GLOBALS['core']['target'])) {
        foreach (array(MAIN_PATH . MAIN_APPLICATION_PATH, MAIN_PATH . MAIN_LIBRARY_PATH) as $dir) {
            if (is_file($dir . $GLOBALS['core']['target'] . '/' . $file)) {
                $file = $GLOBALS['core']['target'] . '/' . $file;

                break;
            }
        }
    }

    $flag = false;

    foreach (array(MAIN_PATH . MAIN_APPLICATION_PATH, MAIN_PATH . MAIN_LIBRARY_PATH) as $dir) {
        if (MAIN_PATH) {
            $target = $dir . $file;
        } else {
            $target = dirname($_SERVER['SCRIPT_FILENAME']) . '/' . $dir . $file;
        }

        if (is_file($target)) {
            if ($once) {
                require_once $target;
            } else {
                require $target;
            }

            $flag = true;

            break;
        }
    }

    if ($ignore === false && $flag === false) {
        error('import error.' . (DEBUG_LEVEL ? ' [' . $target . ']' : ''));
    }

    return;
}

/**
 * Bootstrap.
 *
 * @return void
 */
function bootstrap()
{
    if (is_file(MAIN_PATH . MAIN_APPLICATION_PATH . 'app/bootstrap.php')) {
        import('app/bootstrap.php');
    }

    return;
}

/**
 * Start the session.
 *
 * @return void
 */
function session()
{
    if (SESSION_AUTOSTART === false) {
        return;
    }

    if (regexp_match('[\\|\/]$', SESSION_PATH)) {
        $path = SESSION_PATH;
    } else {
        $path = SESSION_PATH . '/';
    }

    session_set_cookie_params(SESSION_LIFETIME, $path);
    session_cache_limiter(SESSION_CACHE);
    session_start();

    if (is_file(MAIN_PATH . MAIN_APPLICATION_PATH . 'app/session.php')) {
        import('app/session.php');
    }

    return;
}

/**
 * Connect to the database.
 *
 * @return void
 */
function database()
{
    db_connect('default');

    if (is_file(MAIN_PATH . MAIN_APPLICATION_PATH . 'app/database.php')) {
        import('app/database.php');
    }

    return;
}

/**
 * Normalize the superglobals.
 *
 * @return void
 */
function normalize()
{
    if (ini_get('magic_quotes_gpc')) {
        $_GET     = unescape($_GET);
        $_POST    = unescape($_POST);
        $_REQUEST = unescape($_REQUEST);
        $_SERVER  = unescape($_SERVER);
        $_COOKIE  = unescape($_COOKIE);
    }

    $_GET     = sanitize($_GET);
    $_POST    = sanitize($_POST);
    $_REQUEST = sanitize($_REQUEST);
    $_SERVER  = sanitize($_SERVER);
    $_COOKIE  = sanitize($_COOKIE);

    $_GET     = unify($_GET);
    $_POST    = unify($_POST);
    $_REQUEST = unify($_REQUEST);
    $_SERVER  = unify($_SERVER);
    $_COOKIE  = unify($_COOKIE);

    if (is_file(MAIN_PATH . MAIN_APPLICATION_PATH . 'app/normalize.php')) {
        import('app/normalize.php');
    }

    return;
}

/**
 * Routing the url.
 *
 * @return void
 */
function routing()
{
    global $params;

    if (!isset($_SERVER['REQUEST_URI'])) {
        $_SERVER['REQUEST_URI'] = '/';
        $_SERVER['SCRIPT_NAME'] = '/index.php';
    }

    $request_uri = explode('/', strtok($_SERVER['REQUEST_URI'], '?'));
    $script_name = explode('/', $_SERVER['SCRIPT_NAME']);

    for ($i = 0; $i < sizeof($script_name); $i++) {
        if ($request_uri[$i] === $script_name[$i]) {
            unset($request_uri[$i]);
        }
    }
    $params = array_values(array_map('urldecode', $request_uri));

    if (count($params)) {
        if ($regexp = regexp_match('(.+)\.([_a-zA-Z0-9\-]*)$', $params[count($params) - 1])) {
            $params[count($params) - 1] = $regexp[1];
            $params[count($params)]     = $regexp[2];
        }
    }

    $_REQUEST = array(
        'mode'  => isset($_POST['mode'])  ? $_POST['mode']  : (isset($_GET['mode'])  ? $_GET['mode']  : null),
        'work'  => isset($_POST['work'])  ? $_POST['work']  : (isset($_GET['work'])  ? $_GET['work']  : null),
        'type'  => isset($_POST['type'])  ? $_POST['type']  : (isset($_GET['type'])  ? $_GET['type']  : null),
        'token' => isset($_POST['token']) ? $_POST['token'] : (isset($_GET['token']) ? $_GET['token'] : null),
        'test'  => isset($_POST['test'])  ? $_POST['test']  : (isset($_GET['test'])  ? $_GET['test']  : null),
    );

    if (isset($params[0]) && empty($_REQUEST['mode'])) {
        $_REQUEST['mode'] = $params[0];
    }
    if (isset($params[1]) && empty($_REQUEST['work'])) {
        $_REQUEST['work'] = $params[1];
    }

    if ($_REQUEST['mode'] === '' || !regexp_match('^[_a-zA-Z0-9\-]+$', $_REQUEST['mode'])) {
        $_REQUEST['mode'] = MAIN_DEFAULT_MODE;
    }
    if ($_REQUEST['work'] === '' || !regexp_match('^[_a-zA-Z0-9\-]+$', $_REQUEST['work'])) {
        $_REQUEST['work'] = MAIN_DEFAULT_WORK;
    }

    if (is_file(MAIN_PATH . MAIN_APPLICATION_PATH . 'app/routing.php')) {
        import('app/routing.php');
    }

    return;
}

/**
 * Load the service files.
 *
 * @param  string|null  $target
 * @return void
 */
function service($target = null)
{
    $dir = 'app/services/';

    if (!file_exists(MAIN_PATH . MAIN_APPLICATION_PATH . $dir)) {
        return;
    }

    if ($dh = opendir(MAIN_PATH . MAIN_APPLICATION_PATH . $dir)) {
        while (($entry = readdir($dh)) !== false) {
            if (!is_file(MAIN_PATH . MAIN_APPLICATION_PATH  . $dir . $entry)) {
                continue;
            }

            if ($target && $target !== $entry) {
                continue;
            }

            if ($regexp = regexp_match('^([_a-zA-Z0-9\-]+)\.php$', $entry)) {
                $name = $regexp[1];
            } else {
                continue;
            }

            import($dir . $entry);
        }
        closedir($dh);
    } else {
        error('opendir error.' . (DEBUG_LEVEL ? ' [' . $dir . ']' : ''));
    }

    return;
}

/**
 * Load the model files.
 *
 * @param  string|null  $target
 * @return void
 */
function model($target = null)
{
    $dir = 'app/models/';
    $php = '';

    if ($dh = opendir(MAIN_PATH . MAIN_APPLICATION_PATH . $dir)) {
        while (($entry = readdir($dh)) !== false) {
            if (!is_file(MAIN_PATH . MAIN_APPLICATION_PATH  . $dir . $entry)) {
                continue;
            }

            if ($target && $target !== $entry) {
                continue;
            }

            if ($regexp = regexp_match('^([_a-zA-Z0-9\-]+)\.php$', $entry)) {
                $name = $regexp[1];
            } else {
                continue;
            }

            import($dir . $entry);

            if (!function_exists('select_' . $name)) {
                $php .= 'function select_' . $name . '($queries)';
                $php .= '{';
                $php .= '  $queries["from"] = "' . DATABASE_PREFIX . $name . '";';
                $php .= '  return db_select($queries);';
                $php .= '}';
            }
            if (!function_exists('insert_' . $name)) {
                $php .= 'function insert_' . $name . '($queries)';
                $php .= '{';
                $php .= '  $queries["insert_into"] = "' . DATABASE_PREFIX . $name . '";';
                $php .= '  return db_insert($queries);';
                $php .= '}';
            }
            if (!function_exists('update_' . $name)) {
                $php .= 'function update_' . $name . '($queries)';
                $php .= '{';
                $php .= '  $queries["update"] = "' . DATABASE_PREFIX . $name . '";';
                $php .= '  return db_update($queries);';
                $php .= '}';
            }
            if (!function_exists('delete_' . $name)) {
                $php .= 'function delete_' . $name . '($queries)';
                $php .= '{';
                $php .= '  $queries["delete_from"] = "' . DATABASE_PREFIX . $name . '";';
                $php .= '  return db_delete($queries);';
                $php .= '}';
            }
            if (!function_exists('normalize_' . $name)) {
                $php .= 'function normalize_' . $name . '($queries)';
                $php .= '{';
                $php .= '  return $queries;';
                $php .= '}';
            }
            if (!function_exists('validate_' . $name)) {
                $php .= 'function validate_' . $name . '($queries)';
                $php .= '{';
                $php .= '  return array();';
                $php .= '}';
            }
        }
        closedir($dh);
    } else {
        error('opendir error.' . (DEBUG_LEVEL ? ' [' . $dir . ']' : ''));
    }

    eval($php);

    return;
}

/**
 * Load the controller files.
 *
 * @param  string|null  $target
 * @return void
 */
function controller($target = null)
{
    global $params, $db, $view;

    if (is_file(MAIN_PATH . MAIN_APPLICATION_PATH . 'app/controllers/before.php')) {
        import('app/controllers/before.php');
    }
    if (is_file(MAIN_PATH . MAIN_APPLICATION_PATH . 'app/controllers/before_' . $_REQUEST['mode'] . '.php')) {
        import('app/controllers/before_' . $_REQUEST['mode'] . '.php');
    }

    if (empty($GLOBALS['core']['routing'])) {
        $routing = '';
    } else {
        $routing = $GLOBALS['core']['routing'] . '/';
    }

    $directory = 'app/controllers/' . $routing . $_REQUEST['mode'] . '/';
    $file      = $_REQUEST['work'] . '.php';

    if ($target) {
        import('app/controllers/' . $target);
    } elseif (is_file(MAIN_PATH . MAIN_APPLICATION_PATH . $directory . $file)) {
        import($directory . $file);
    } elseif (is_file(MAIN_PATH . MAIN_APPLICATION_PATH . $directory . MAIN_DEFAULT_WORK . '.php')) {
        import($directory . MAIN_DEFAULT_WORK . '.php');
    } elseif (is_file(MAIN_PATH . MAIN_APPLICATION_PATH . 'app/controllers/' . PAGE_CONTROLLER . '.php')) {
        import('app/controllers/' . PAGE_CONTROLLER . '.php');
    }

    if (is_file(MAIN_PATH . MAIN_APPLICATION_PATH . 'app/controllers/after_' . $_REQUEST['mode'] . '.php')) {
        import('app/controllers/after_' . $_REQUEST['mode'] . '.php');
    }
    if (is_file(MAIN_PATH . MAIN_APPLICATION_PATH . 'app/controllers/after.php')) {
        import('app/controllers/after.php');
    }

    return;
}

/**
 * Load the view files.
 *
 * @param  string|null  $target
 * @param  bool  $return
 * @return void
 */
function view($target = null, $return = false)
{
    global $params, $view;

    static $complete = false;

    if ($_REQUEST['mode'] !== 'test_exec' && $complete) {
        return;
    }

    if (empty($GLOBALS['core']['routing'])) {
        $routing = '';
    } else {
        $routing = $GLOBALS['core']['routing'] . '/';
    }

    $directory = 'app/views/' . $routing . $_REQUEST['mode'] . '/';
    $file      = $_REQUEST['work'] . '.php';

    if ($return) {
        ob_start();
    }

    if ($target) {
        import('app/views/' . $target);
    } elseif (is_file(MAIN_PATH . MAIN_APPLICATION_PATH . $directory . $file)) {
        import($directory . $file);
    } elseif (is_file(MAIN_PATH . MAIN_APPLICATION_PATH . $directory . MAIN_DEFAULT_WORK . '.php')) {
        import($directory . MAIN_DEFAULT_WORK . '.php');
    } elseif (is_file(PAGE_PATH . implode('/', $params) . '.php')) {
        import(PAGE_PATH . implode('/', $params) . '.php');
    } elseif ($_REQUEST['mode'] === MAIN_DEFAULT_MODE && $_REQUEST['work'] === MAIN_DEFAULT_WORK) {
        about();
    } elseif (is_file(MAIN_PATH . MAIN_APPLICATION_PATH . 'app/views/404.php')) {
        import('app/views/404.php');
    } else {
        header('HTTP/1.0 404 Not Found');
        error('404 Not Found');
    }

    if ($return === false) {
        $complete = true;
    }

    if ($return) {
        $contents = ob_get_contents();
        ob_end_clean();

        return $contents;
    } else {
        return;
    }
}

/**
 * Get the unescaped data.
 *
 * @param  string  $data
 * @return string
 */
function unescape($data)
{
    if (is_array($data)) {
        return array_map('unescape', $data);
    }

    return stripslashes($data);
}

/**
 * Get the sanitized data.
 *
 * @param  string  $data
 * @return string
 */
function sanitize($data)
{
    if (is_array($data)) {
        return array_map('sanitize', $data);
    }

    return str_replace("\0", '', $data);
}

/**
 * Get the unified data.
 *
 * @param  string  $data
 * @return string
 */
function unify($data)
{
    if (is_array($data)) {
        return array_map('unify', $data);
    }

    $data = regexp_replace("\r?\n", "\r", $data);
    $data = regexp_replace("\r", "\n", $data);

    return $data;
}

/**
 * Get the converted data.
 *
 * @param  mixed  $data
 * @param  string  $to_encoding
 * @param  string  $from_encoding
 * @return mixed
 */
function convert($data, $to_encoding = 'UTF-8', $from_encoding = 'UTF-8,EUCJP-WIN,SJIS-WIN')
{
    if (mb_convert_variables($to_encoding, $from_encoding, $data)) {
        return $data;
    } else {
        return array();
    }
}

/**
 * Get the alternative data.
 *
 * @param  mixed  $data
 * @param  string  $to_encoding
 * @param  string|null  $from_encoding
 * @return mixed
 */
function alt($data, $alternative, $pattern = null)
{
    if ($data === null || $data === '') {
        return $alternative;
    } elseif ($pattern !== null && regexp_match($pattern, $data)) {
        return $alternative;
    } else {
        return $data;
    }
}

/**
 * Get the truncated data.
 *
 * @param  string  $data
 * @param  int  $width
 * @param  string  $trimmarker
 * @param  string  $encoding
 * @return string
 */
function truncate($data, $width = 0, $trimmarker = '...', $encoding = 'UTF-8')
{
    if (mb_strlen($data, $encoding) > $width) {
        $data = mb_substr($data, 0, $width, $encoding) . $trimmarker;
    }

    return $data;
}

/**
 * Output the data.
 *
 * @param  string  $data
 * @param  bool  $return
 * @return void|string
 */
function e($data, $return = false)
{
    if ($return) {
        return $data;
    } else {
        echo $data;
    }
}

/**
 * Output the data for text.
 *
 * @param  string  $data
 * @param  bool  $return
 * @return void|string
 */
function t($data, $return = false)
{
    $data = htmlspecialchars($data, ENT_QUOTES, MAIN_INTERNAL_ENCODING);

    if ($return) {
        return $data;
    } else {
        echo $data;
    }
}

/**
 * Output the data for html.
 *
 * @param  string  $data
 * @param  bool  $return
 * @return void|string
 */
function h($data, $return = false)
{
    $data = htmlspecialchars($data, ENT_QUOTES, MAIN_INTERNAL_ENCODING);
    $data = nl2br($data);

    if ($return) {
        return $data;
    } else {
        echo $data;
    }
}

/**
 * Format a local time/date.
 *
 * @param  string|null  $format
 * @param  mixed|null  $timestamp
 * @return mixed
 */
function localdate($format = null, $timestamp = null)
{
    static $time = 0;

    if ($time === 0) {
        $time = time() + MAIN_TIME;
    }

    if ($regexp = regexp_match('^(\d\d\d\d)\-(\d\d)\-(\d\d)', $timestamp)) {
        $year  = intval($regexp[1]);
        $month = intval($regexp[2]);
        $day   = intval($regexp[3]);

        if (!checkdate($month, $day, $year)) {
            return null;
        }
    }

    if ($timestamp === null) {
        $timestamp = $time;
    } elseif (!is_numeric($timestamp)) {
        $timestamp = strtotime($timestamp);
    }

    if ($format) {
        return date($format, $timestamp);
    } else {
        return $timestamp;
    }
}

/**
 * Get a client ip.
 *
 * @param  bool  $proxy
 * @return string
 */
function clientip($proxy = false)
{
    if ($proxy) {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            return $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $addresses = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);

            return $addresses[0];
        }
    }

    return $_SERVER['REMOTE_ADDR'];
}

/**
 * Determine if the request is secure.
 *
 * @param  bool  $proxy
 * @return bool
 */
function ssl($proxy = false)
{
    if (isset($_SERVER['HTTPS']) && (strtolower($_SERVER['HTTPS']) === 'on' || $_SERVER['HTTPS'] === 1)) {
        return true;
    } elseif ($proxy) {
        if (!empty($_SERVER['HTTP_X_FORWARDED_PORT']) && $_SERVER['HTTP_X_FORWARDED_PORT'] === 443) {
            return true;
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') {
            return true;
        }
    }

    return false;
}

/**
 * Get or check the token.
 *
 * @param  string  $type
 * @param  string  $name
 * @return bool
 */
function token($type, $name = 'default')
{
    if ($type === 'check') {
        if ($_REQUEST['token'] && isset($_SESSION['core']['token'][$name]) && $_REQUEST['token'] === $_SESSION['core']['token'][$name]['value']) {
            $flag = true;
        } else {
            $flag = false;
        }

        if (empty($_SESSION['core']['token'][$name]) || time() - $_SESSION['core']['token'][$name]['time'] > TOKEN_SPAN) {
            $_SESSION['core']['token'][$name] = array();
        }

        return $flag;
    } else {
        if (empty($_SESSION['core']['token'][$name]) || time() - $_SESSION['core']['token'][$name]['time'] > TOKEN_SPAN) {
            $token = rand_string();

            $_SESSION['core']['token'][$name] = array(
                'value' => $token,
                'time'  => time(),
            );
        } else {
            $token = $_SESSION['core']['token'][$name]['value'];
        }

        return $token;
    }
}

/**
 * Redirect to the url.
 *
 * @param  string  $url
 * @return void
 */
function redirect($url)
{
    if (!regexp_match('^https?\:\/\/', $url)) {
        $url = MAIN_FILE . $url;
    }

    header('Location: ' . $url);

    exit;
}

/**
 * Output the data for debug.
 *
 * @param  mixed  $data
 * @param  bool  $return
 * @return void|mixed
 */
function debug($data, $return = false)
{
    if ($return) {
        return print_r($data, true);
    } else {
        print('<pre>');
        print_r($data);
        print('</pre>');

        return;
    }
}

/**
 * Log the message to a logs.
 *
 * @param  string  $message
 * @return void
 */
function logging($message)
{
    $message = regexp_replace("\r", '\r', $message);
    $message = regexp_replace("\n", '\n', $message);

    $uri = str_replace("\n" . $_SERVER['SCRIPT_NAME'], '', "\n" . $_SERVER['REQUEST_URI']);

    if ($uri === '') {
        $uri = '/';
    }

    if ($fp = fopen(LOGGING_FILE, 'a')) {
        fwrite($fp, '[' . localdate('Y-m-d H:i:s') . '] ' . $_SERVER['REQUEST_URI'] . ' ' . $message . "\n");
        fclose($fp);
    }

    return;
}

/**
 * Output the result.
 *
 * @param  string|null  $type
 */
function ok($type = null)
{
    if ($type === null && isset($_REQUEST['type'])) {
        $type = $_REQUEST['type'];
    }

    if ($type === 'json') {
        header('Content-Type: application/json; charset=' . MAIN_CHARSET);

        echo json_encode(array(
            'status' => 'OK',
        ));
    } elseif ($type === 'xml') {
        header('Content-Type: text/xml; charset=' . MAIN_CHARSET);

        echo "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n";
        echo "<response>\n";
        echo "<status>OK</status>\n";
        echo "</response>\n";
    } elseif (is_file(MAIN_PATH . MAIN_APPLICATION_PATH . 'app/views/ok_' . $_REQUEST['mode'] . '.php')) {
        import('app/views/ok_' . $_REQUEST['mode'] . '.php', false, true);
    } elseif (is_file(MAIN_PATH . MAIN_APPLICATION_PATH . 'app/views/ok.php')) {
        import('app/views/ok.php', false, true);
    } else {
        echo "<!DOCTYPE html>\n";
        echo "<html>\n";
        echo "<head>\n";
        echo "<meta charset=\"" . t(MAIN_CHARSET, true) . "\" />\n";
        echo "<title>OK</title>\n";

        style();

        echo "</head>\n";
        echo "<body>\n";
        echo "<h1>OK</h1>\n";
        echo "</body>\n";
        echo "</html>\n";
    }

    exit;
}

/**
 * Output the result for warning.
 *
 * @param  string  $messages
 * @param  string|null  $type
 */
function warning($messages, $type = null)
{
    global $view;

    if (!is_array($messages)) {
        $messages = array($messages);
    }
    if ($type === null && isset($_REQUEST['type'])) {
        $type = $_REQUEST['type'];
    }

    if ($type === 'json') {
        header('Content-Type: application/json; charset=' . MAIN_CHARSET);

        echo json_encode(array(
            'status'   => 'WARNING',
            'messages' => $messages,
        ));
    } elseif ($type === 'xml') {
        header('Content-Type: text/xml; charset=' . MAIN_CHARSET);

        echo "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n";
        echo "<response>\n";
        echo "<status>WARNING</status>\n";
        echo "<messages>\n";

        foreach ($messages as $message) {
            echo "<message>" . h($message, true) . "</message>\n";
        }

        echo "</messages>\n";
        echo "</response>\n";
    } elseif (is_file(MAIN_PATH . MAIN_APPLICATION_PATH . 'app/views/warning_' . $_REQUEST['mode'] . '.php')) {
        $view['messages'] = $messages;

        import('app/views/warning_' . $_REQUEST['mode'] . '.php', false, true);
    } elseif (is_file(MAIN_PATH . MAIN_APPLICATION_PATH . 'app/views/warning.php')) {
        $view['messages'] = $messages;

        import('app/views/warning.php', false, true);
    } else {
        echo "<!DOCTYPE html>\n";
        echo "<html>\n";
        echo "<head>\n";
        echo "<meta charset=\"" . t(MAIN_CHARSET, true) . "\" />\n";
        echo "<title>WARNING</title>\n";

        style();

        echo "</head>\n";
        echo "<body>\n";
        echo "<h1>WARNING</h1>\n";
        echo "<ul>\n";

        foreach ($messages as $message) {
            echo "<li>" . h($message, true) . "</li>\n";
        }

        echo "</ul>\n";
        echo "</body>\n";
        echo "</html>\n";
    }

    exit;
}

/**
 * Output the result for error.
 *
 * @param  string  $message
 * @param  string|null  $type
 */
function error($message, $type = null)
{
    global $view;

    if (DEBUG_LOG) {
        logging($message);
    }
    if ($type === null && isset($_REQUEST['type'])) {
        $type = $_REQUEST['type'];
    }

    if ($type === 'json') {
        header('Content-Type: application/json; charset=' . MAIN_CHARSET);

        echo json_encode(array(
            'status'  => 'ERROR',
            'message' => $message,
        ));
    } elseif ($type === 'xml') {
        header('Content-Type: text/xml; charset=' . MAIN_CHARSET);

        echo "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n";
        echo "<response>\n";
        echo "<status>ERROR</status>\n";
        echo "<message>" . h($message, true) . "</message>\n";
        echo "</response>\n";
    } elseif (is_file(MAIN_PATH . MAIN_APPLICATION_PATH . 'app/views/error_' . $_REQUEST['mode'] . '.php')) {
        $view['message'] = $message;

        import('app/views/error_' . $_REQUEST['mode'] . '.php', false, true);
    } elseif (is_file(MAIN_PATH . MAIN_APPLICATION_PATH . 'app/views/error.php')) {
        $view['message'] = $message;

        import('app/views/error.php', false, true);
    } else {
        echo "<!DOCTYPE html>\n";
        echo "<html>\n";
        echo "<head>\n";
        echo "<meta charset=\"" . t(MAIN_CHARSET, true) . "\" />\n";
        echo "<title>ERROR</title>\n";

        style();

        echo "</head>\n";
        echo "<body>\n";
        echo "<h1>ERROR</h1>\n";
        echo "<p>" . h($message, true) . "</p>\n";
        echo "</body>\n";
        echo "</html>\n";
    }

    exit;
}

/**
 * Output a page for this framework.
 *
 */
function about()
{
    echo "<!DOCTYPE html>\n";
    echo "<html>\n";
    echo "<head>\n";
    echo "<meta charset=\"" . t(MAIN_CHARSET, true) . "\" />\n";
    echo "<title>levis: PHP Framework</title>\n";

    style();

    echo "</head>\n";
    echo "<body>\n";
    echo "<h1>levis: PHP Framework</h1>\n";
    echo "<p>Version " . VERSION_NUMBER . ' (' . VERSION_UPDATE . ")</p>\n";

    echo "<h2>Menu</h2>\n";
    echo "<ul>\n";
    echo "<li><a href=\"" . t(MAIN_FILE, true) . "/?mode=info_php\">phpinfo</a></li>\n";

    if (DATABASE_TYPE) {
        echo "<li><a href=\"" . t(MAIN_FILE, true) . "/?mode=db_admin\">database</a></li>\n";
    }
    if (file_exists(DATABASE_MIGRATE_PATH)) {
        echo "<li><a href=\"" . t(MAIN_FILE, true) . "/?mode=db_migrate\">migrate</a></li>\n";
    }
    if (file_exists(DATABASE_SCAFFOLD_PATH)) {
        echo "<li><a href=\"" . t(MAIN_FILE, true) . "/?mode=db_scaffold\">scaffold</a></li>\n";
    }
    if (file_exists(TEST_PATH)) {
        echo "<li><a href=\"" . t(MAIN_FILE, true) . "/?mode=test_index\">test</a></li>\n";
    }

    echo "</ul>\n";

    echo "<h2>Configuration</h2>\n";

    echo "<h3>Main</h3>\n";
    echo "<dl>\n";
    echo "<dt>main file</dt>\n";
    echo "<dd><code>" . alt(MAIN_FILE, '-') . "</code></dd>\n";
    echo "<dt>path to libraries</dt>\n";
    echo "<dd><code>" . alt(MAIN_LIBRARY_PATH, '-') . "</code></dd>\n";
    echo "<dt>path to application</dt>\n";
    echo "<dd><code>" . alt(MAIN_APPLICATION_PATH, '-') . "</code></dd>\n";
    echo "<dt>default mode</dt>\n";
    echo "<dd><code>" . alt(MAIN_DEFAULT_MODE, '-') . "</code></dd>\n";
    echo "<dt>default work</dt>\n";
    echo "<dd><code>" . alt(MAIN_DEFAULT_WORK, '-') . "</code></dd>\n";
    echo "<dt>internal encoding</dt>\n";
    echo "<dd><code>" . alt(MAIN_INTERNAL_ENCODING, '-') . "</code></dd>\n";
    echo "<dt>charset</dt>\n";
    echo "<dd><code>" . alt(MAIN_CHARSET, '-') . "</code></dd>\n";
    echo "<dt>time</dt>\n";
    echo "<dd><code>" . MAIN_TIME . "</code></dd>\n";
    echo "</dl>\n";
    echo "<dl>\n";
    echo "<dt>path to models</dt>\n";
    echo "<dd><code>" . MAIN_APPLICATION_PATH . 'app/models/' . "</code></dd>\n";
    echo "<dt>path to views</dt>\n";
    echo "<dd><code>" . MAIN_APPLICATION_PATH . 'app/views/' . "</code></dd>\n";
    echo "<dt>path to controllers</dt>\n";
    echo "<dd><code>" . MAIN_APPLICATION_PATH . 'app/controllers/' . "</code></dd>\n";
    echo "<dt>path to core libraries</dt>\n";
    echo "<dd><code>" . MAIN_LIBRARY_PATH . 'libs/cores/' . "</code></dd>\n";
    echo "<dt>path to plugins</dt>\n";
    echo "<dd><code>" . MAIN_LIBRARY_PATH . 'libs/plugins/' . "</code></dd>\n";
    echo "</dl>\n";

    if (DATABASE_TYPE) {
        echo "<h3>Database</h3>\n";
        echo "<dl>\n";
        echo "<dt>type</dt>\n";
        echo "<dd><code>" . DATABASE_TYPE . "</code></dd>\n";
        echo "<dt>name</dt>\n";
        echo "<dd><code>" . alt(DATABASE_NAME, '-') . "</code></dd>\n";
        echo "<dt>prefix</dt>\n";
        echo "<dd><code>" . alt(DATABASE_PREFIX, '-') . "</code></dd>\n";
        echo "<dt>charset</dt>\n";
        echo "<dd><code>" . alt(DATABASE_CHARSET, '-') . "</code></dd>\n";
        echo "<dt>charset input from</dt>\n";
        echo "<dd><code>" . alt(DATABASE_CHARSET_INPUT_FROM, '-') . "</code></dd>\n";
        echo "<dt>charset input to</dt>\n";
        echo "<dd><code>" . alt(DATABASE_CHARSET_INPUT_TO, '-') . "</code></dd>\n";
        echo "<dt>charset output from</dt>\n";
        echo "<dd><code>" . alt(DATABASE_CHARSET_OUTPUT_FROM, '-') . "</code></dd>\n";
        echo "<dt>charset output to</dt>\n";
        echo "<dd><code>" . alt(DATABASE_CHARSET_OUTPUT_TO, '-') . "</code></dd>\n";
        echo "<dt>migrate path</dt>\n";
        echo "<dd><code>" . alt(DATABASE_MIGRATE_PATH, '-') . "</code></dd>\n";
        echo "<dt>scaffold path</dt>\n";
        echo "<dd><code>" . alt(DATABASE_SCAFFOLD_PATH, '-') . "</code></dd>\n";
        echo "</dl>\n";
    }

    echo "<h3>Session</h3>\n";
    echo "<dl>\n";
    echo "<dt>autostart</dt>\n";
    echo "<dd><code>" . (SESSION_AUTOSTART ? 'true' : 'false') . "</code></dd>\n";
    echo "<dt>lifetime</dt>\n";
    echo "<dd><code>" . SESSION_LIFETIME . "</code></dd>\n";
    echo "<dt>path</dt>\n";
    echo "<dd><code>" . alt(SESSION_PATH, '-') . "</code></dd>\n";
    echo "<dt>cache</dt>\n";
    echo "<dd><code>" . alt(SESSION_CACHE, '-') . "</code></dd>\n";
    echo "</dl>\n";

    echo "<h3>Token</h3>\n";
    echo "<dl>\n";
    echo "<dt>span</dt>\n";
    echo "<dd><code>" . TOKEN_SPAN . "</code></dd>\n";
    echo "</dl>\n";

    echo "<h3>Regexp</h3>\n";
    echo "<dl>\n";
    echo "<dt>type</dt>\n";
    echo "<dd><code>" . alt(REGEXP_TYPE, '-') . "</code></dd>\n";
    echo "</dl>\n";

    echo "<h3>Page</h3>\n";
    echo "<dl>\n";
    echo "<dt>path</dt>\n";
    echo "<dd><code>" . alt(PAGE_PATH, '-') . "</code></dd>\n";
    echo "<dt>controller</dt>\n";
    echo "<dd><code>" . alt(PAGE_CONTROLLER, '-') . "</code></dd>\n";
    echo "</dl>\n";

    echo "<h3>Test</h3>\n";
    echo "<dl>\n";
    echo "<dt>path</dt>\n";
    echo "<dd><code>" . alt(TEST_PATH, '-') . "</code></dd>\n";
    echo "</dl>\n";

    echo "<h3>Logging</h3>\n";
    echo "<dl>\n";
    echo "<dt>file</dt>\n";
    echo "<dd><code>" . alt(LOGGING_FILE, '-') . "</code></dd>\n";
    echo "</dl>\n";

    echo "<h3>Debug</h3>\n";
    echo "<dl>\n";
    echo "<dt>level</dt>\n";
    echo "<dd><code>" . DEBUG_LEVEL . "</code></dd>\n";
    echo "<dt>addr</dt>\n";
    echo "<dd><code>" . alt(DEBUG_ADDR, '-') . "</code></dd>\n";
    echo "<dt>log</dt>\n";
    echo "<dd><code>" . (DEBUG_LOG ? 'true' : 'false') . "</code></dd>\n";
    echo "</dl>\n";

    echo "</body>\n";
    echo "</html>\n";

    exit;
}

/**
 * Output a style for this framework.
 *
 */
function style()
{
    echo "<style>\n";
    echo "html {\n";
    echo "    background-color: #EEEEEE;\n";
    echo "}\n";
    echo "body {\n";
    echo "    margin: 20px;\n";
    echo "    padding: 0 20px 20px 20px;\n";
    echo "    border: 1px solid #CCCCCC;\n";
    echo "    background-color: #FFFFFF;\n";
    echo "    color: #222222;\n";
    echo "}\n";
    echo "h1 {\n";
    echo "    padding-bottom: 10px;\n";
    echo "    border-bottom: 1px solid #CCCCCC;\n";
    echo "    font-size: 150%;\n";
    echo "}\n";
    echo "h2 {\n";
    echo "    font-size: 120%;\n";
    echo "}\n";
    echo "h3 {\n";
    echo "    font-size: 110%;\n";
    echo "}\n";
    echo "h4, h5, h6 {\n";
    echo "    font-size: 100%;\n";
    echo "}\n";
    echo "code {\n";
    echo "    color: #333366;\n";
    echo "}\n";
    echo "table tr th {\n";
    echo "    padding: 3px;\n";
    echo "    border: 1px solid #CCCCCC;\n";
    echo "    background-color: #EEEEEE;\n";
    echo "}\n";
    echo "table tr td {\n";
    echo "    padding: 3px;\n";
    echo "    border: 1px solid #CCCCCC;\n";
    echo "}\n";
    echo "fieldset {\n";
    echo "    border: 1px solid #CCCCCC;\n";
    echo "}\n";
    echo "a {\n";
    echo "    color: #003399;\n";
    echo "}\n";
    echo "</style>\n";
}
