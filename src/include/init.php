<?php

namespace Garradin;

error_reporting(-1);

/*
 * Version de Garradin
 */

function garradin_version()
{
    if (defined('GARRADIN_VERSION'))
    {
        return GARRADIN_VERSION;
    }

    $file = __DIR__ . '/../VERSION';

    if (file_exists($file))
    {
        $version = trim(file_get_contents($file));
    }
    else
    {
        $version = 'unknown';
    }

    define('GARRADIN_VERSION', $version);
    return $version;
}

function garradin_manifest()
{
    $file = __DIR__ . '/../manifest.uuid';

    if (file_exists($file))
    {
        return substr(trim(file_get_contents($file)), 0, 10);
    }

    return false;
}

/*
 * Configuration globale
 */

// Configuration externalisée, pour projets futurs (fermes de garradins ?)
if (file_exists(__DIR__ . '/../config.local.php'))
{
    require __DIR__ . '/../config.local.php';
}

if (!defined('GARRADIN_ROOT'))
{
    define('GARRADIN_ROOT', dirname(__DIR__));
}

if (!defined('GARRADIN_DATA_ROOT'))
{
    define('GARRADIN_DATA_ROOT', GARRADIN_ROOT);
}

if (!defined('GARRADIN_DB_FILE'))
{
    define('GARRADIN_DB_FILE', GARRADIN_DATA_ROOT . '/association.sqlite');
}

if (!defined('GARRADIN_DB_SCHEMA'))
{
    define('GARRADIN_DB_SCHEMA', GARRADIN_ROOT . '/include/data/schema.sql');
}

if (!defined('WWW_URI'))
{
    // Automagic URL discover
    $path = str_replace(GARRADIN_ROOT . '/www', '', getcwd());
    $path = str_replace($path, '', dirname($_SERVER['SCRIPT_NAME']));
    $path = (!empty($path[0]) && $path[0] != '/') ? '/' . $path : $path;
    $path = (substr($path, -1) != '/') ? $path . '/' : $path;
    define('WWW_URI', $path);
}

if (!defined('WWW_URL'))
{
    $host = isset($_SERVER['HTTP_HOST']) 
        ? $_SERVER['HTTP_HOST'] 
        : (isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : 'localhost');
    define('WWW_URL', 'http' . (!empty($_SERVER['HTTPS']) ? 's' : '') . '://' . $host . WWW_URI);
}

if (!defined('GARRADIN_PLUGINS_PATH'))
{
    define('GARRADIN_PLUGINS_PATH', GARRADIN_DATA_ROOT . '/plugins');
}

define('GARRADIN_WEBSITE', 'http://garradin.eu/');
define('GARRADIN_PLUGINS_URL', 'https://garradin.eu/plugins/list.json');

ini_set('error_log', GARRADIN_DATA_ROOT . '/error.log');
ini_set('log_errors', true);
ini_set('display_errors', true);
ini_set('html_errors', false);

if (PHP_SAPI != 'cli')
{
    ini_set('error_prepend_string', '<!DOCTYPE html><meta charset="utf-8" /><style type="text/css">body { font-family: sans-serif; } h3 { color: darkred; } 
        pre { text-shadow: 2px 2px 5px black; color: darkgreen; font-size: 2em; float: left; margin: 0 1em 0 0; padding: 1em; background: #cfc; border-radius: 50px; }</style>
        <pre> \__/<br /> (xx)<br />//||\\\\</pre>
        <h1>Erreur fatale</h1>
        <p>Une erreur fatale s\'est produite à l\'exécution de Garradin. Pour rapporter ce bug
        merci d\'inclure le message ci-dessous :</p>
        <h3>');
    ini_set('error_append_string', '</h3><hr />
        <p><a href="http://dev.kd2.org/garradin/Rapporter%20un%20bug">Comment rapporter un bug</a></p>');
}

/*
 * Gestion des erreurs et exceptions
 */

class UserException extends \LogicException
{
}

function exception_error_handler($errno, $errstr, $errfile, $errline )
{
    // For @ ignored errors
    if (error_reporting() === 0) return;
    throw new \ErrorException($errstr, 0, $errno, $errfile, $errline);
}

function exception_handler($e)
{
    if ($e instanceOf UserException || $e instanceOf miniSkelMarkupException)
    {
        try {
            $tpl = Template::getInstance();

            $tpl->assign('error', $e->getMessage());
            $tpl->display('error.tpl');
            exit;
        }
        catch (Exception $e)
        {
        }
    }

    $file = str_replace(GARRADIN_ROOT, '', $e->getFile());

    $error = "Exception of type ".get_class($e)." happened !\n\n".
        $e->getCode()." - ".$e->getMessage()."\n\nIn: ".
        $file . ":" . $e->getLine()."\n\n";

    if (!empty($_SERVER['HTTP_HOST']) && !empty($_SERVER['REQUEST_URI']))
        $error .= 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']."\n\n";

    $error .= $e->getTraceAsString();
    $error .= "\n-------------\n";
    $error .= 'Garradin version: ' . garradin_version() . "\n";
    $error .= 'Garradin manifest: ' . garradin_manifest() . "\n";
    $error .= 'PHP version: ' . phpversion() . "\n";

    foreach ($_SERVER as $key=>$value)
    {
        $error .= $key . ': ' . $value . "\n";
    }
    
    $error = str_replace("\r", '', $error);
    
    if (PHP_SAPI == 'cli')
    {
        echo $error;
    }
    else
    {
        echo '<!DOCTYPE html><meta charset="utf-8" /><style type="text/css">body { font-family: sans-serif; } h3 { color: darkred; }
        pre { text-shadow: 2px 2px 5px black; color: darkgreen; font-size: 2em; float: left; margin: 0 1em 0 0; padding: 1em; background: #cfc; border-radius: 50px; }</style>
        <pre> \__/<br /> (xx)<br />//||\\\\</pre>
        <h1>Erreur d\'exécution</h1>
        <p>Une erreur s\'est produite à l\'exécution de Garradin. Pour rapporter ce bug
        merci d\'inclure le message suivant :</p>
        <textarea cols="70" rows="'.substr_count($error, "\n").'">'.htmlspecialchars($error, ENT_QUOTES, 'UTF-8').'</textarea>
        <hr />
        <p><a href="http://dev.kd2.org/garradin/Rapporter%20un%20bug">Comment rapporter un bug</a></p>';
    }

    exit;
}

set_error_handler('Garradin\exception_error_handler');
set_exception_handler('Garradin\exception_handler');

/**
 * Auto-load classes and libs
 */
class Loader
{
    /**
     * Already loaded filenames
     * @var array
     */
    static protected $loaded = array();

    static protected $libs = array(
        'utils',
        'squelette_filtres',
        'static_cache',
        'template'
        );

    /**
     * Loads a class from the $name
     * @param  stringg $classname
     * @return bool true
     */
    static public function load($classname)
    {
        $classname = ltrim($classname, '\\');
        $filename  = '';
        $namespace = '';

        if ($lastnspos = strripos($classname, '\\')) 
        {
            $namespace = substr($classname, 0, $lastnspos);
            $classname = substr($classname, $lastnspos + 1);

            if ($namespace != 'Garradin')
            {
                $filename  = str_replace('\\', '/', $namespace) . '/';
            }
        }

        $classname = strtolower($classname);

        if (in_array($classname, self::$libs)) {
            $filename = 'lib.' . $classname . '.php';
        } else {
            $filename .= 'class.' . $classname . '.php';
        }

        $filename = GARRADIN_ROOT . '/include/' . $filename;

        if (array_key_exists($filename, self::$loaded))
        {
            return true;
        }

        if (!file_exists($filename)) {
            throw new \Exception('File '.$filename.' doesn\'t exists');
        }

        self::$loaded[$filename] = true;

        require $filename;
    }
}

\spl_autoload_register(array('Garradin\Loader', 'load'), true);

$n = new Membres;

/*
 * Inclusion des fichiers de base
 */

if (!defined('GARRADIN_INSTALL_PROCESS') && !defined('GARRADIN_UPGRADE_PROCESS'))
{
    if (!file_exists(GARRADIN_DB_FILE))
    {
        utils::redirect('/admin/install.php');
    }

    $config = Config::getInstance();

    if (version_compare($config->getVersion(), garradin_version(), '<'))
    {
        utils::redirect('/admin/upgrade.php');
    }
}

?>