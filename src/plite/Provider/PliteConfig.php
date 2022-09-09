<?php



namespace vertwo\plite\Provider;



use Exception;
use vertwo\plite\FJ;
use vertwo\plite\Log;
use function vertwo\plite\clog;
use function vertwo\plite\redlog;



/**
 *
 * NOTE - This is the critical configuration step.
 *
 * NOTE - Two primary considerations:
 *            1. Dev env, possibly working with multiple applications, each having a unique URL on localhost.
 *                   This config should be boostrapped from a file in the filesystem,
 *                   based on URL, which allows for a coupling between url and filesystem,
 *                   but should be under dev's control (so long as there's a web-accessible dir).
 *            2. Prod env, single application, with full-control over SetEnv (apache) variables.
 *                   Assumption is made that a single web environment--with full control over SetEnv
 *                   variables--is available for the app.  Bootstrapping is done there, with a class
 *                   name (well, class name prefix) to load, which contains the config.
 *
 * NOTE - 1. Parse the "app name" from the URL, bootstrape config from filesystem.
 *           App name (plite_app) must be a non-whitespaced,
 *           [:alnum:] only, string.  Then, 2 regexes:
 *               a. Regex to determine app name ('plite_app' SetEnv in Prod)
 *               b. Regex to determine "routing prefix" (unneeded in Prod)
 *
 * NOTE - 2. Give app name & class prefix in SetEnv:
 *               a. plite_app
 *               b. plite_class_prefix
 *
 * Class PliteConfig
 *
 * @package vertwo\plite\Provider
 */
abstract class PliteConfig
{
    const DEBUG_ENV                    = true;
    const DEBUG_CONFIG_INFO            = true;
    const DEBUG_CONFIG_INFO_WITH_DUMPS = false;

    const DEBUG_CONFIG_INFO_JSON = true; // DANGER - In __PRODUCTION__, this must be set to (false)!!!!!



    const ENV_PLITE_APP_KEY    = "plite_app";    // NOTE - Prod
    const ENV_PLITE_PREFIX_KEY = "plite_prefix"; // NOTE - Prod

    const ENV_PLITE_LOCAL_ROOT_KEY    = "plite_local_root";    // NOTE - Dev
    const ENV_PLITE_URL_APP_REGEX_KEY = "plite_url_app_regex"; // NOTE - Dev

    private static $PLITE_APP    = false;
    private static $PLITE_PREFIX = false;

    /** @var array|bool $PARAMS */
    private static $PARAMS = false;

    /** @var bool $IS_LOCAL */
    private static $IS_LOCAL = false;



    /**
     * Expects web server to have 'vertwo_class_prefix' as an
     * environment variable available to PHP via $_SERVER.
     *
     * Then, uses that value to instantiate the relevant
     * ProviderFactory subclass.
     *
     * @return PliteConfig
     * @throws Exception
     */
    public static function newInstance ()
    {
        self::bootstrapParamsFromEnv();
        return self::$IS_LOCAL
            ? new PliteLocalConfig()
            : self::loadCloudConfigClass();
    }



    /**
     * Expects web server to have 'vertwo_class_prefix' as an
     * environment variable available to PHP via $_SERVER.
     *
     * Then, uses that value to instantiate the relevant
     * given subclass.
     *
     * @param string $clazz - Name of type, after prefix (e.g., "ProviderFactory", "Router")
     *
     * @return mixed
     * @throws Exception
     */
    public static function loadPrefixedClass ( $className )
    {
        self::bootstrapParamsFromEnv();

        if ( !array_key_exists(self::ENV_PLITE_PREFIX_KEY, self::$PARAMS) )
            throw new Exception("Cannot find 'plite_prefix' config setting.");

        $prefix = self::$PARAMS[self::ENV_PLITE_PREFIX_KEY];

        clog("prefix", $prefix);

        if ( (strlen($prefix) == 0) || null == $prefix || !$prefix )
            throw new Exception("Invalid 'plite_prefix' config setting.");

        $fqClass = $prefix . $className;

        clog("prefix", $prefix)
        clog("Instantiating sublcass", $fqClass);

        if ( !class_exists($fqClass) ) throw new Exception("Cannot load [ " . $fqClass . " ]");

        return new $fqClass();
    }



    /**
     * @throws Exception
     */
    private static function bootstrapParamsFromEnv ()
    {
        if ( false !== self::$PARAMS ) return;

        $info = self::getConfigInfo();

        if ( false === $info["isValid"] )
            throw new Exception("Invalid configuration; all fields missing--check Apache config (and SetEnv values).");

        $type = $info['type'];

        switch ( $type )
        {
            case "local":
                self::$IS_LOCAL = true;
                self::$PARAMS   = self::loadFileConfig();
                break;

            case "cloud":
                self::$IS_LOCAL = false;
                self::$PARAMS   = self::loadDefaultConfig();
                break;

            default:
                throw new Exception ("Config type [ $type ]; unknown; check env var values.");
        }

        clog("self::\$IS_LOCAL", self::$IS_LOCAL);
        clog("self::\$PARAMS", self::$PARAMS);
    }



    private static function getConfigInfo ()
    {
        $hasLocalRoot = self::hasEnv(self::ENV_PLITE_LOCAL_ROOT_KEY);
        $hasAppRegex  = self::hasEnv(self::ENV_PLITE_URL_APP_REGEX_KEY);

        $hasApp         = self::hasEnv(self::ENV_PLITE_APP_KEY);
        $hasClassPrefix = self::hasEnv(self::ENV_PLITE_PREFIX_KEY);

        clog("has local root", $hasLocalRoot);
        clog("has app regex", $hasAppRegex);
        clog("has app", $hasApp);
        clog("has class pref", $hasClassPrefix);

        if ( $hasLocalRoot || $hasAppRegex )
        {
            if ( $hasLocalRoot && $hasAppRegex )
            {
                return [
                    "isValid" => true,
                    "type"    => "local",
                ];
            }
            else
            {
                return [
                    "isValid" => false,
                    "missing" => $hasLocalRoot
                        ? self::ENV_PLITE_URL_APP_REGEX_KEY
                        : self::ENV_PLITE_LOCAL_ROOT_KEY,
                ];
            }
        }
        else if ( $hasApp || $hasClassPrefix )
        {
            if ( $hasApp && $hasClassPrefix )
            {
                return [
                    "isValid" => true,
                    "type"    => "cloud",
                ];
            }
            else
            {
                return [
                    "isValid" => false,
                    "missing" => $hasApp
                        ? self::ENV_PLITE_PREFIX_KEY
                        : self::ENV_PLITE_APP_KEY,
                ];
            }
        }
        else
        {
            return [
                "isValid" => false,
            ];
        }
    }



    /**
     * Determines if a key exists in the WebServer Environment.
     *
     * @param $key - Environment variable name.
     *
     * @return boolean - Does environment variable exist?
     */
    private static function hasEnv ( $key ) { return array_key_exists($key, $_SERVER); }



    /**
     * return @mixed
     *
     * @throws Exception
     */
    private static function loadFileConfig ()
    {
        $localRoot = self::loadEnv(self::ENV_PLITE_LOCAL_ROOT_KEY);
        $appRegex  = self::loadEnv(self::ENV_PLITE_URL_APP_REGEX_KEY);
        $app       = self::getAppFromUrlRegex($appRegex);

        if ( self::DEBUG_ENV ) clog("Loading LOCAL config (from filesystem [ " . $localRoot . " ])...");

        $rootDir    = $localRoot . "/" . $app;
        $configPath = $rootDir . "/config/" . "$app-config.js";
        $authPath   = $rootDir . "/auth/" . "$app-auth.js";

        $conf   = self::loadConfigFile($configPath);
        $auth   = self::loadConfigFile($authPath);
        $params = array_merge($conf, $auth);

        if ( !array_key_exists(self::ENV_PLITE_APP_KEY, $params) )
            throw new Exception("Cannot load local config: missing '" . self::ENV_PLITE_APP_KEY . "' in config file.");
        if ( !array_key_exists(self::ENV_PLITE_PREFIX_KEY, $params) )
            throw new Exception("Cannot load local config: missing '" . self::ENV_PLITE_PREFIX_KEY . "' in config file.");

        self::$PLITE_APP    = self::$PARAMS[self::ENV_PLITE_APP_KEY];
        self::$PLITE_PREFIX = self::$PARAMS[self::ENV_PLITE_PREFIX_KEY];

        return $params;
    }



    /**
     * Gets a key from the WebServer Environment.
     *
     * @param $key - Environment variable name.
     *
     * @return string - Environment variable value.
     *
     * @throws Exception
     */
    private static function loadEnv ( $key )
    {
        if ( !array_key_exists($key, $_SERVER) )
            throw new Exception("Environment variable [ $key ] doesn't exist.");

        $val = trim($_SERVER[$key]);

        if ( self::DEBUG_ENV ) clog("ENV $key", $val);

        return $val;
    }



    /**
     * @throws Exception
     */
    private static function getAppFromUrlRegex ( $regex )
    {
        $uri = $_SERVER['REQUEST_URI'];

        clog($uri, $regex);

        preg_match($regex, $uri, $matches);

        if ( count($matches) < 2 )
            throw new Exception("Cannot get app from url; check regex [ $regex ].");

        $app = $matches[1];

        return $app;
    }



    private static function loadConfigFile ( $file )
    {
        if ( !file_exists($file) || !is_readable($file) )
        {
            redlog("Could not read config file: $file");
            return [];
        }

        if ( self::DEBUG_CONFIG_INFO_WITH_DUMPS ) Log::dump();
        if ( self::DEBUG_CONFIG_INFO ) clog("Trying to load config file", $file);

        $json = file_get_contents($file);

        if ( self::DEBUG_CONFIG_INFO_JSON ) clog("config(json)", $json);

        return FJ::jsDecode($json);
    }



    /**
     * @return mixed
     * @throws Exception
     */
    private static function loadDefaultConfig ()
    {
        self::$PLITE_APP    = self::loadEnv(self::ENV_PLITE_APP_KEY);
        self::$PLITE_PREFIX = self::loadEnv(self::ENV_PLITE_PREFIX_KEY);

        $config = self::loadCloudConfigClass();
        $params = $config->loadInlineConfig();

        return $params;
    }



    /**
     * @throws Exception
     */
    private static function loadCloudConfigClass ()
    {
        if ( self::DEBUG_ENV ) clog("Loading INLINE config from subclass...");

        $config = self::loadPrefixedClass("PliteConfig");

        if ( !$config instanceof PliteConfig )
            throw new Exception("Specified class does not subclass PliteConfig.");

        return $config;
    }



    private static function _dump ( $mesg = false )
    {
        if ( false === $mesg ) $mesg = "PliteFactory.dump()";
        clog($mesg, self::$PARAMS);
    }





    ////////////////////////////////////////////////////////////////
    //
    //
    // NOTE - Abstract Interface
    //
    //
    ////////////////////////////////////////////////////////////////

    /**
     * @return array - Map of param keys to values.
     */
    abstract protected function loadInlineConfig ();





    ////////////////////////////////////////////////////////////////
    //
    //
    // ctor()
    //
    //
    ////////////////////////////////////////////////////////////////

    /**
     * PliteConfig constructor.
     *
     * @throws Exception
     */
    protected function __construct () { }

    public function dump ( $mesg = false ) { self::_dump($mesg); }
    public function has ( $key ) { return array_key_exists($key, self::$PARAMS); }
    public function no ( $key ) { return !$this->has($key); }
    public function get ( $key ) { return array_key_exists($key, self::$PARAMS) ? self::$PARAMS[$key] : null; }
    public function matches ( $key, $targetValue )
    {
        return $this->has($key) ? $targetValue === $this->get($key) : false;
    }

    protected final function getMap () { return self::$PARAMS; }
}
