<?php

namespace Garradin;

error_reporting(-1);

/*
 * Version de Garradin
 */

function garradin_version()
{
    if (defined('Garradin\VERSION'))
    {
        return VERSION;
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

    define('Garradin\VERSION', $version);
    return $version;
}

function garradin_manifest()
{
    $file = __DIR__ . '/../../manifest.uuid';

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

if (!defined('Garradin\ROOT'))
{
    define('Garradin\ROOT', dirname(__DIR__));
}

if (!defined('Garradin\DATA_ROOT'))
{
    define('Garradin\DATA_ROOT', ROOT);
}

if (!defined('Garradin\DB_FILE'))
{
    define('Garradin\DB_FILE', DATA_ROOT . '/association.sqlite');
}

if (!defined('Garradin\DB_SCHEMA'))
{
    define('Garradin\DB_SCHEMA', ROOT . '/include/data/schema.sql');
}

if (!defined('Garradin\WWW_URI'))
{
    // Automagic URL discover
    $path = str_replace(ROOT . '/www', '', getcwd());
    $path = str_replace($path, '', dirname($_SERVER['SCRIPT_NAME']));
    $path = (!empty($path[0]) && $path[0] != '/') ? '/' . $path : $path;
    $path = (substr($path, -1) != '/') ? $path . '/' : $path;
    define('Garradin\WWW_URI', $path);
}

if (!defined('Garradin\WWW_URL'))
{
    $host = isset($_SERVER['HTTP_HOST']) 
        ? $_SERVER['HTTP_HOST'] 
        : (isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : 'localhost');
    define('Garradin\WWW_URL', 'http' . (!empty($_SERVER['HTTPS']) ? 's' : '') . '://' . $host . WWW_URI);
}

if (!defined('Garradin\PLUGINS_PATH'))
{
    define('Garradin\PLUGINS_PATH', DATA_ROOT . '/plugins');
}

// Affichage des erreurs par défaut
if (!defined('Garradin\SHOW_ERRORS'))
{
    define('Garradin\SHOW_ERRORS', true);
}

define('Garradin\WEBSITE', 'http://garradin.eu/');
define('Garradin\PLUGINS_URL', 'https://garradin.eu/plugins/list.json');

// PHP devrait être assez intelligent pour chopper la TZ système mais nan
// il sait pas faire (sauf sur Debian qui a le bon patch pour ça), donc pour 
// éviter le message d'erreur à la con on définit une timezone par défaut
// Pour utiliser une autre timezone, il suffit de définir date.timezone dans
// un .htaccess ou dans config.local.php
if (!ini_get('date.timezone'))
{
    if ($tz = @date_default_timezone_get())
    {
        ini_set('date.timezone', $tz);
    }
    else
    {
        ini_set('date.timezone', 'Europe/Paris');
    }
}

if (SHOW_ERRORS)
{
    // Gestion par défaut des erreurs
    ini_set('error_log', DATA_ROOT . '/error.log');
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

    $file = str_replace(ROOT, '', $e->getFile());

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
    elseif (SHOW_ERRORS)
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
    static protected $loaded = [];

    static protected $libs = [
        'utils',
        'squelette_filtres',
        'static_cache',
        'template'
    ];

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

        $filename = ROOT . '/include/' . $filename;

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

\spl_autoload_register(['Garradin\Loader', 'load'], true);

$n = new Membres;

/*
 * Inclusion des fichiers de base
 */

if (!defined('Garradin\INSTALL_PROCESS') && !defined('Garradin\UPGRADE_PROCESS'))
{
    if (!file_exists(DB_FILE))
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