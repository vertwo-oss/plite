<?php
/**
 * Copyright (c) 2012-2025 Troy Wu
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
use vertwo\plite\Util\Map;



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
 *
 * Aug, 2025 -- Outline the cases.  Don't just talk about needs.
 *
 * There are a few cases:
 *
 * 1) Prod (web)
 *
 *      Running in AWS ElasticBeanstalk, with only control over
 *      SetEnv variables.
 *
 * 2) Dev (web)
 *
 *      Running local webserver, with control over everything,
 *      but with the caveat that we are running multiple apps
 *      (think contractor with multiple, simultaneous clients)
 *      running on the same machine, same web server.
 *
 * 3) Embedded-CLI
 *
 *      Running stuff on the CLI, so usually things like Unit
 *      Testing, or writing small utilities.  Here, we don't
 *      mind specifying config separately as code.
 *
 * 4) CLI (using Prod or Local config).
 *
 *      This is when we're running utilties against the prod
 *      or local environments (i.e., not testing, but doing
 *      real work).  If it's against Prod, this is easy; we
 *      just use the Prod embedded config.  But, if it's the
 *      local environment, we have a weird situation where we
 *      don't have a URL to parse (with regex), don't have an
 *      embedded config, but only have a local filesystem root.
 *
 * There are various considerations for each:
 *
 * 1) Prod (web)
 *
 *      We can only control Environment Variables.  But, it's
 *      reasonably to assume that each project (i.e., a project
 *      needing separate config has -IT'S OWN- EB environment.
 *      Therefore, we can embed the config in a class file, and
 *      use SetEnv to tell us where to find this class file.
 *      Get the app name from inside this class file.
 *
 *      1 (ONE) variable.
 *
 * 2) Embedded-CLI or CLI-using-Prod-config
 *
 *      Same as Web-Prod.  We're embedding the config in a
 *      class file.  Set Shell env vars to tell us where to
 *      find this class file.  Get the app name from inside
 *      this class file.
 *
 *      1 (ONE) variable.
 *
 * 3) Dev (web)
 *
 *      This is actually the weirdest environment.  Because a
 *      single dev might be working on multiple projects, we
 *      need a way to figure out, based on URL (because we're
 *      not going to setup a virtual host for every client)
 *      what the project is.  FUCK CONTAINERS.  This is a
 *      million times simpler.
 *
 *      So, we need a regex (to extract the app name).
 *      And, we need a local filesystem root--the place to
 *      find the config file.  We're using a config file
 *      here because we don't want to use the Prod environment
 *      config (say, we're going offline, and want local
 *      abstractions for certain services).
 *
 *      2 (TWO) variables.
 *
 * 4) Local-CLI (using Local config)
 *
 *      This is like the Dev (web) config, except that we
 *      can't get the app name from the REQUEST_URI.  Duh.
 *      So, we have to pass it in.
 *
 *      2 (TWO) variables:
 *
 * Class Config
 *
 * @package vertwo\plite\Provider
 */
class Config
{
    const DEBUG_ENV         = true;
    const DEBUG_CONFIG_INFO = true;
    const DEBUG_AWS_CREDS   = false;
    
    const DEBUG_CONFIG_INFO_JSON = false; // DANGER - In __PRODUCTION__, this must be set to (false)!!!!!
    const DEBUG_CREDS_DANGEROUS  = false; // DANGER - In __PRODUCTION__, this must be set to (false)!!!!!
    
    
    const AWS_IMPL_VERSION = 202209;
    
    /*
     * DANGER - New versions should use `plite_config_class_name`,
     *          and not `plite_config`, which does not tell give
     *          us any idea of what this is for.
     */
    const ENV_PLITE_APP_KEY               = "_plite_app";               // NOTE - Prod + CLI
    const ENV_PLITE_CONFIG_KEY            = "_plite_config";            // NOTE - Prod + CLI
    const ENV_PLITE_CONFIG_CLASS_NAME_KEY = "_plite_config_class_name"; // NOTE - Prod + CLI
    
    const ENV_PLITE_LOCAL_ROOT_KEY    = "_plite_local_root";    // NOTE - Dev
    const ENV_PLITE_URL_APP_REGEX_KEY = "_plite_url_app_regex"; // NOTE - Dev
    
    const AWS_REGION_ARRAY_KEY  = "_plite_aws_region";
    const AWS_VERSION_ARRAY_KEY = "_plite_aws_version";
    
    const AWS_ACCESS_ARRAY_KEY = "_plite_aws_access_key_id";
    const AWS_SECRET_ARRAY_KEY = "_plite_aws_secret_access_key";
    
    const PROVIDER_LOCAL = "local";
    const PROVIDER_PROXY = "proxy";
    const PROVIDER_CLOUD = "cloud";
    const PROVIDER_AWS   = "aws";
    
    
    private static $APP = false; // App name (Prod + CLI)
    
    
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
     * @return Map
     * @throws Exception
     */
    public static function load ()
    {
        $hasLocal           = self::hasEnv(self::ENV_PLITE_LOCAL_ROOT_KEY);     // Local + CLI(local)
        $hasRegex           = self::hasEnv(self::ENV_PLITE_URL_APP_REGEX_KEY);  // Local
        $hasApp             = self::hasEnv(self::ENV_PLITE_APP_KEY);            // Web (Local or Prod)
        $hasConfigClassName =                                                        // Prod + CLI(embed)
          self::hasEnv(self::ENV_PLITE_CONFIG_KEY)
          ||
          self::hasEnv(self::ENV_PLITE_CONFIG_CLASS_NAME_KEY);
        
        clog([
               "has local (root)"      => $hasLocal,
               "has regex"             => $hasRegex,
               "has app"               => $hasApp,
               "has config class name" => $hasConfigClassName,
             ]);
        
        if ( $hasLocal && $hasRegex )
        {
            //
            // Local-Web.
            //
            $localRoot = self::loadEnv(self::ENV_PLITE_LOCAL_ROOT_KEY);
            $regex     = self::loadEnv(self::ENV_PLITE_URL_APP_REGEX_KEY);
            $appName   = self::getAppFromUrlRegex($regex);
            
            $params = self::loadFileConfig($appName, $localRoot);
        }
        else if ( $hasLocal && $hasApp )
        {
            //
            // Local-CLI, or Prod-Web-with-hosted-filesystem
            //
            $localRoot = self::loadEnv(self::ENV_PLITE_LOCAL_ROOT_KEY);
            $appName   = self::loadEnv(self::ENV_PLITE_APP_KEY);
            
            $params = self::loadFileConfig($appName, $localRoot);
        }
        else if ( $hasConfigClassName )
        {
            //
            // Prod-Web / Embed-CLI.
            //
            $configClassName = self::loadEnv(self::ENV_PLITE_CONFIG_KEY);
            
            $params = self::loadSubclassConfig($configClassName);
            
            if ( !array_key_exists(self::ENV_PLITE_APP_KEY, $params) )
                throw new Exception("Config (cloud) does not have [ " . self::ENV_PLITE_APP_KEY . " ] defined.");
            
            $appName = $params[self::ENV_PLITE_APP_KEY];
        }
        else
        {
            throw new Exception("Cannot load config; must be missing bootstrap elements; see Config::load().");
        }
        
        self::$APP = $appName;
        
        return new Map($params);
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
            clog(red("Could not read config file: $file"));
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
            clog(yel("Parameters are not an array; check syntax of config file."));
            return [];
        }
    }
    
    
    /**
     * @param string $configClassName
     *
     * @return mixed
     * @throws Exception
     */
    private static function loadSubclassConfig ( string $configClassName )
    {
        if ( self::DEBUG_ENV ) clog("Loading INLINE config", $configClassName);
        
        /** @var ConfigClass $config */
        $config = self::loadClass($configClassName);
        
        if ( !$config instanceof ConfigClass )
            throw new Exception("Specified class does not implement ConfigInterface.");
        
        return $config->getConfig();
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
    public static function dump ( $mesg = false ) { return self::load()->dump($mesg); }
    public static function has ( $key ) { return self::load()->has($key); }
    public static function no ( $key ) { return self::load()->no($key); }
    public static function get ( $key ) { return self::load()->get($key); }
    public static function getWithPrefix ( $prefix ) { return self::load()->getWithPrefix($prefix); }
    public static function matches ( $key, $targetValue ) { return self::load()->matches($key, $targetValue); }
    
    
    /**
     * @return array|bool
     * @throws Exception
     */
    public static function getAppName ()
    {
        self::load();
        return self::$APP;
    }
    
    
    /**
     * Crazy function with side-effect of throwing exception if
     * the found-type (in config) doesn't match the expected type (in code).
     *
     * @param $name
     * @param $expectedType
     *
     * @return string
     * @throws Exception
     */
    public static function verifyProviderAndGetSource ( $name, $expectedType )
    {
        $params = Config::load();
        
        if ( $params->no($name) )
        {
            throw new Exception("No provider entry [$name] exists; check config.");
        }
        
        $type = $params->get($name);
        $et   = strtolower($expectedType);
        $t    = strtolower($type);
        
        $isok = $et == $t;
        
        if ( !$isok )
        {
            throw new Exception("Provider entry [$name] has [" . $t . "], not '" . $et . "'; check config.");
        }
        
        $sourceKey = "{$name}_provider";
        $map       = $params->get($sourceKey);
        
        return $map;
    }
    
    
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
        $map = Config::load();
        
        $creds = [
          'region'  => self::getAWSRegion($map),
          'version' => self::getAWSVersion($map),
        ];
        
        $hasAccess = $map->has(self::AWS_ACCESS_ARRAY_KEY);
        $hasSecret = $map->has(self::AWS_SECRET_ARRAY_KEY);
        
        $hasAwsCreds = $hasAccess && $hasSecret;
        
        if ( $hasAwsCreds )
        {
            $access = $map->get(self::AWS_ACCESS_ARRAY_KEY);
            $secret = $map->get(self::AWS_SECRET_ARRAY_KEY);
            
            if ( self::DEBUG_AWS_CREDS ) clog(self::AWS_ACCESS_ARRAY_KEY, $access);
            
            if ( self::AWS_IMPL_VERSION >= 202208 )
            {
                //
                // NOTE - This worked as of 2022 Aug.
                //
                // * https://docs.aws.amazon.com/sdk-for-php/v3/developer-guide/guide_credentials_hardcoded.html
                //
                $creds["credentials"] = [
                  'key'    => $access, // WARN - WTF ARE YOU KIDDING ME, AWS???
                  'secret' => $secret,
                ];
            }
            else
            {
                //
                // NOTE - This worked as of 3.1.128, but is no longer working (2022 Aug)
                //
                $creds["credentials"] = [
                  'access' => $access,
                  'secret' => $secret,
                ];
            }
        }
        else
        {
            clog(red("Cannot get AWS creds; is config loading properly?"));
        }
        
        if ( self::DEBUG_CREDS_DANGEROUS ) clog("getCredsAWS() - creds", $creds);
        
        return $creds;
    }
    
    
    
    /**
     * @return mixed|null
     * @throws Exception
     */
    private static function getAWSRegion ( $map ) { return $map->get(self::AWS_REGION_ARRAY_KEY); }
    /**
     * @return mixed|null
     * @throws Exception
     */
    private static function getAWSVersion ( $map ) { return $map->get(self::AWS_VERSION_ARRAY_KEY); }
}
