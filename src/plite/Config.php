<?php
/**
 * Copyright (c) 2012-2022 Troy Wu
 * Copyright (c) 2021-2022 Version2 OÜ
 * All rights reserved.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
 * AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
 * IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE
 * ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE
 * LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR
 * CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
 * SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
 * INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
 * CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
 * ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 */



namespace vertwo\plite;



use Exception;



/**
 *
 * WARN - This is the critical configuration step.
 *
 * NOTE - Two primary considerations:
 *            1. Dev env, possibly working with multiple applications, each having a unique URL on localhost.
 *                   This config should be boostrapped from a file in the filesystem,
 *                   based on URL, which allows for a coupling between url and filesystem,
 *                   but should be under dev's control (so long as there's a web-accessible dir).
 *            2. Prod/CLI env, single application, with full-control over SetEnv (apache) variables.
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
abstract class Config
{
    const DEBUG_ENV         = true;
    const DEBUG_CONFIG_INFO = true;
    const DEBUG_AWS_CREDS   = false;

    const DEBUG_CONFIG_INFO_JSON = false; // DANGER - In __PRODUCTION__, this must be set to (false)!!!!!
    const DEBUG_CREDS_DANGEROUS  = false; // DANGER - In __PRODUCTION__, this must be set to (false)!!!!!


    const AWS_IMPL_VERSION = 202209;

    const ENV_PLITE_APP_KEY    = "plite_app";    // NOTE - Prod + CLI
    const ENV_PLITE_CONFIG_KEY = "plite_config"; // NOTE - Prod + CLI

    const ENV_PLITE_LOCAL_ROOT_KEY    = "plite_local_root";    // NOTE - Dev
    const ENV_PLITE_URL_APP_REGEX_KEY = "plite_url_app_regex"; // NOTE - Dev

    const AWS_REGION_ARRAY_KEY  = "aws_region";
    const AWS_VERSION_ARRAY_KEY = "aws_version";

    const AWS_ACCESS_ARRAY_KEY      = "aws_access_key_id";
    const AWS_SECRET_ARRAY_KEY      = "aws_secret_access_key";
    const AWS_CREDENTIALS_ARRAY_KEY = "credentials";

    const PROVIDER_LOCAL = "local";
    const PROVIDER_PROXY = "proxy";
    const PROVIDER_CLOUD = "cloud";



    /** @var array|bool $PARAMS */
    private static $PARAMS = false;

    private static $APP    = false; // App name (Prod + CLI)
    private static $CONFIG = false; // Fully-qualified ConfigInterface subclass name (Prod + CLI)



    /**
     * @param string $fqClass - Fully-qualified Class Name (including namespaces).
     *
     * @return mixed
     * @throws Exception
     */
    public static function loadClass ( $fqClass )
    {
        clog("Instantiating class", $fqClass);

        if ( !class_exists($fqClass) ) throw new Exception("Cannot load [ " . $fqClass . " ]");

        return new $fqClass();
    }



    /**
     * @throws Exception
     */
    public static function init () { if ( false === self::$PARAMS ) self::loadParams(); }



    /**
     * @throws Exception
     */
    private static function loadParams ()
    {
        $info = self::bootstrapParams();

        if ( false === $info["isValid"] )
            throw new Exception("Invalid configuration; all fields missing--check Apache config (and SetEnv values).");

        $type = $info['type'];

        switch ( $type )
        {
            case "local":
                list($app, $config, $params) = self::getLocalConfig($info);
                break;

            case "cloud":
                list($app, $config, $params) = self::getCloudConfig($info);
                break;

            default:
                throw new Exception ("Config type [ $type ]; unknown; check env var values.");
        }

        self::$APP    = $app;
        self::$CONFIG = $config;

        clog("   APP", self::$APP);
        clog("CONFIG", self::$CONFIG);

        self::$PARAMS = $params;
    }



    /**
     * @return array
     * @throws Exception
     */
    private static function bootstrapParams ()
    {
        //
        // NOTE - Dev (from filesystem + app-from-url)
        //
        $hasLocal = self::hasEnv(self::ENV_PLITE_LOCAL_ROOT_KEY);
        $hasRegex = self::hasEnv(self::ENV_PLITE_URL_APP_REGEX_KEY);
        //
        // NOTE - Prod (from class-which-implements-ConfigInterface) + CLI
        //
        $hasConfig = self::hasEnv(self::ENV_PLITE_CONFIG_KEY);

        clog("has local (root)", $hasLocal);
        clog("has regex", $hasRegex);
        clog("has config", $hasConfig);

        if ( $hasLocal || $hasRegex )
        {
            if ( $hasLocal && $hasRegex )
            {
                $local = self::loadEnv(self::ENV_PLITE_LOCAL_ROOT_KEY);
                $regex = self::loadEnv(self::ENV_PLITE_URL_APP_REGEX_KEY);

                return [
                    "isValid" => true,
                    "type"    => "local",
                    "local"   => $local,
                    "regex"   => $regex,
                ];
            }
            else
            {
                return [
                    "isValid" => false,
                    "missing" => $hasLocal
                        ? self::ENV_PLITE_URL_APP_REGEX_KEY
                        : self::ENV_PLITE_LOCAL_ROOT_KEY,
                ];
            }
        }
        else if ( $hasConfig )
        {
            $config = self::loadEnv(self::ENV_PLITE_CONFIG_KEY);
            return [
                "isValid" => true,
                "type"    => "cloud",
                "config"  => $config,
            ];
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

        if ( self::DEBUG_ENV ) clog("ENV -> $key", $val);

        return $val;
    }




    /**
     * @param array $info
     *
     * @return array
     * @throws Exception
     */
    private static function getLocalConfig ( $info )
    {
        $isLocal     = true;
        $localRoot   = $info['local'];
        $urlAppRegex = $info['regex'];
        $app         = self::getAppFromUrlRegex($urlAppRegex);
        clog("local -    app", $app);

        $params = self::loadFileConfig($app, $localRoot);

        if ( !array_key_exists(self::ENV_PLITE_CONFIG_KEY, $params) )
            throw new Exception("Config (cloud) does not have [ " . self::ENV_PLITE_CONFIG_KEY . " ] defined.");

        $config = $params[self::ENV_PLITE_CONFIG_KEY];
        clog("local - config", $config);

        return [ $app, $config, $params ];
    }



    /**
     * @param string $app
     * @param string $localRoot
     *
     * @return mixed
     *
     * @throws Exception
     */
    private static function loadFileConfig ( $app, $localRoot )
    {
        if ( self::DEBUG_ENV ) clog("Loading LOCAL config (from filesystem [ " . $localRoot . " ])...");

        $rootDir    = $localRoot . "/" . $app;
        $configPath = $rootDir . "/config/" . $app . "-config.js";
        $authPath   = $rootDir . "/auth/" . $app . "-auth.js";

        clog("root    dir", $rootDir);
        clog("config path", $configPath);
        clog("auth   path", $authPath);

        $conf   = self::loadConfigFile($configPath);
        $auth   = self::loadConfigFile($authPath);
        $params = array_merge($conf, $auth);

        if ( !array_key_exists(self::ENV_PLITE_CONFIG_KEY, $params) )
            throw new Exception("Cannot load local config: missing '" . self::ENV_PLITE_CONFIG_KEY . "' in config file.");

        return $params;
    }



    /**
     * @throws Exception
     */
    private static function getAppFromUrlRegex ( $regex )
    {
        $uri = $_SERVER['REQUEST_URI'];

        clog($regex, $uri);

        preg_match($regex, $uri, $matches);

        if ( count($matches) < 2 )
            throw new Exception("Cannot get app from URI (" . $uri . "); check regex [ " . $regex . " ].");

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

        if ( self::DEBUG_CONFIG_INFO ) clog("Trying to load config file", $file);

        $json = file_get_contents($file);

        if ( self::DEBUG_CONFIG_INFO_JSON ) clog("config(json)", $json);

        $params = FJ::jsDecode($json);

        if ( is_array($params) )
        {
            return $params;
        }
        else
        {
            yelulog("Parameters are not an array; check syntax of config file.");
            return [];
        }
    }



    /**
     * @param array $info
     *
     * @return array
     * @throws Exception
     */
    private static function getCloudConfig ( $info )
    {
        $isLocal = false;
        $config  = $info['config'];

        $params = self::loadSubclassConfig($config);

        if ( !array_key_exists(self::ENV_PLITE_APP_KEY, $params) )
            throw new Exception("Config (cloud) does not have [ " . self::ENV_PLITE_APP_KEY . " ] defined.");

        $app = $params[self::ENV_PLITE_APP_KEY];
        clog("cloud - config", $config);
        clog("cloud -    app", $app);

        return [ $app, $config, $params ];
    }



    /**
     * @param string $configClass
     *
     * @return mixed
     * @throws Exception
     */
    private static function loadSubclassConfig ( $configClass )
    {
        if ( self::DEBUG_ENV ) clog("Loading INLINE config", $configClass);

        /** @var ConfigInterface $config */
        $config = self::loadClass($configClass);

        if ( !$config instanceof ConfigInterface )
            throw new Exception("Specified class does not implement ConfigInterface.");

        return $config->getConfig();
    }



    /**
     * @throws Exception
     */
    private static function throwNotInit ()
    {
        throw new Exception("Config not initialized; try Config::init().");
    }



    ////////////////////////////////////////////////////////////////
    //
    //
    // NOTE - Public interface.
    //
    //
    ////////////////////////////////////////////////////////////////



    /**
     * @param bool $mesg
     *
     * @throws Exception
     */
    public static function dump ( $mesg = false )
    {
        if ( false === self::$PARAMS ) Config::init();
        if ( false === $mesg ) $mesg = "Config.dump()";
        clog($mesg, self::$PARAMS);
    }
    /**
     * @param $key
     *
     * @return bool
     * @throws Exception
     */
    public static function has ( $key )
    {
        if ( false === self::$PARAMS ) Config::init();
        return array_key_exists($key, self::$PARAMS);
    }
    /**
     * @param $key
     *
     * @return bool
     * @throws Exception
     */
    public static function no ( $key )
    {
        if ( false === self::$PARAMS ) Config::init();
        return self::has($key);
    }
    /**
     * @param $key
     *
     * @return mixed|null
     * @throws Exception
     */
    public static function get ( $key )
    {
        if ( false === self::$PARAMS ) Config::init();
        return array_key_exists($key, self::$PARAMS) ? self::$PARAMS[$key] : null;
    }
    /**
     * @param $key
     * @param $targetValue
     *
     * @return bool
     * @throws Exception
     */
    public static function matches ( $key, $targetValue )
    {
        if ( false === self::$PARAMS ) Config::init();
        return self::has($key) ? $targetValue === self::get($key) : false;
    }
    /**
     * @return array|bool
     * @throws Exception
     */
    final protected static function getMap ()
    {
        if ( false === self::$PARAMS ) Config::init();
        return self::$PARAMS;
    }
    /**
     * @return mixed|null
     * @throws Exception
     */
    public static function getAppName ()
    {
        if ( false === self::$PARAMS ) Config::init();
        return self::$APP;
    }



    /**
     * @param $providerType
     *
     * @return mixed|null
     * @throws Exception
     */
    public static function getProviderSource ( $providerType )
    {
        if ( false === self::$PARAMS ) Config::init();

        $provKey = $providerType . "_provider";
        $source  = self::get($provKey);

        return $source;
    }



//    private function isUsingProviderSource ( $providerType, $source )
//    {
//        $key = $providerType . "_provider";
//        return $this->matches($key, $source);
//    }
//    private function isUsingLocalProvider ( $provider ) { return $this->isUsingProviderSource($provider, self::PROVIDER_LOCAL); }
//    private function isUsingCloudProvider ( $provider ) { return $this->isUsingProviderSource($provider, self::PROVIDER_CLOUD); }
//    private function isUsingProxyProvider ( $provider ) { return $this->isUsingProviderSource($provider, self::PROVIDER_PROXY); }



    /**
     * If this is running on a local machine with a config file,
     * use the credentials in the config file; otherwise, NOTE: DO NOTHING.
     *
     * When "nothing" is done, then allow AWS Client libraries to try to
     * pickup the role credentials.  This will work on EC2, and with the
     * command line.
     *
     * @return array
     *
     * @throws Exception - Happens when it's LOCAL config, but no AWS
     * credentials are present, and also generally when the Config won't load.
     */
    final public static function getCredsAWS ()
    {
        if ( false === self::$PARAMS ) Config::init();

        $creds = [
            'region'  => self::getAWSRegion(),
            'version' => self::getAWSVersion(),
        ];

        $hasAccess = self::has(self::AWS_ACCESS_ARRAY_KEY);
        $hasSecret = self::has(self::AWS_SECRET_ARRAY_KEY);

        $hasAwsCreds = $hasAccess && $hasSecret;

        if ( $hasAwsCreds )
        {
            $access = self::get(self::AWS_ACCESS_ARRAY_KEY);
            $secret = self::get(self::AWS_SECRET_ARRAY_KEY);

            if ( self::DEBUG_AWS_CREDS ) clog(self::AWS_ACCESS_ARRAY_KEY, $access);

            if ( self::AWS_IMPL_VERSION >= 202208 )
            {
                /*
                [Tue Sep 13 21:20:16.869611 2022] [php7:error] [pid 28314] [client 127.0.0.1:63150] PHP Fatal error:  Uncaught Error: Call to undefined method vertwo\\plite\\Provider\\AWS\\FileProviderAWS::get() in /Users/troy/proj/predictus2/web/src/predictus/DurkheimAPI.php:106
                Stack trace:
                #0 /Users/troy/proj/predictus2/web/src/predictus/DurkheimAPI.php(63): pattpred\\predictus\\DurkheimAPI::getSampleData('grp1-pt-1001')
                #1 /Users/troy/proj/predictus2/web/src/fhios/FhiosRouter.php(523): pattpred\\predictus\\DurkheimAPI::callWithSample(Object(pattpred\\fhios\\FhiosRouter), 'grp1-pt-1001')
                #2 /Users/troy/proj/predictus2/web/src/fhios/FhiosRouter.php(132): pattpred\\fhios\\FhiosRouter->callMentalHealthAPIWithSample()
                #3 /Users/troy/proj/predictus2/web/vendor/vertwo/plite/src/plite/Web/PliteRouter.php(258): pattpred\\fhios\\FhiosRouter->handleRequest()
                #4 /Users/troy/proj/predictus2/web/route.php(61): vertwo\\plite\\Web\\PliteRouter->main()
                #5 {main}\n  thrown in /Users/troy/proj/predictus2/web/src/predictus/DurkheimAPI.php on line 106, referer: http://localhost/~troy/fhios/dashboard
                 */
                $creds["key"]    = $access;
                $creds["secret"] = $secret;
            }
            else
            {
                //
                // NOTE - This worked as of 3.1.128, but is no longer working (2022 Aug)
                //
                $creds[self::AWS_CREDENTIALS_ARRAY_KEY] = [
                    'access' => $access,
                    'secret' => $secret,
                ];
            }
        }

        if ( self::DEBUG_CREDS_DANGEROUS ) clog("getCredsAWS() - creds", $creds);

        return $creds;
    }
    /**
     * @return mixed|null
     * @throws Exception
     */
    private static function getAWSRegion () { return self::get(self::AWS_REGION_ARRAY_KEY); }
    /**
     * @return mixed|null
     * @throws Exception
     */
    private static function getAWSVersion () { return self::get(self::AWS_VERSION_ARRAY_KEY); }
}
