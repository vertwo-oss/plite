<?php
/**
 * Copyright (c) 2012-2021 Troy Wu
 * Copyright (c) 2021      Version2 OÜ
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



namespace vertwo\plite\Provider;



use Aws\Exception\AwsException;
use Aws\S3\S3Client;
use Aws\SecretsManager\SecretsManagerClient;
use Aws\Ses\SesClient;
use Aws\WorkSpaces\Exception\WorkSpacesException;
use Exception;
use vertwo\plite\FJ;
use vertwo\plite\integrations\TrelloIntegration;
use vertwo\plite\Log;
use vertwo\plite\Provider\AWS\CRUDProviderAWS;
use vertwo\plite\Provider\AWS\CSVProviderAWS;
use vertwo\plite\Provider\AWS\EmailProviderAWS;
use vertwo\plite\Provider\AWS\FileProviderAWS;
use vertwo\plite\Provider\Database\PG;
use vertwo\plite\Provider\Local\CRUDProviderLocal;
use vertwo\plite\Provider\Local\CSVProviderLocal;
use vertwo\plite\Provider\Local\EmailProviderLocal;
use vertwo\plite\Provider\Local\FileProviderLocal;
use function vertwo\plite\cclog;
use function vertwo\plite\clog;
use function vertwo\plite\redlog;



abstract class PliteFactory
{
    const DEBUG_ENV                    = true;
    const DEBUG_CONFIG_INFO            = true;
    const DEBUG_CONFIG_INFO_WITH_DUMPS = false;
    const DEBUG_DB_CONN                = false;
    const DEBUG_DB_CONN_VERBOSE        = false;
    const DEBUG_SECRETS_MANAGER        = true;
    const DEBUG_AWS_CREDS              = false;



    const DEBUG_CONFIG_INFO_JSON = false; // DANGER - In __PRODUCTION__, this must be set to (false)!!!!!
    const DEBUG_CREDS_DANGEROUS  = false; // DANGER - In __PRODUCTION__, this must be set to (false)!!!!!



    const AWS_CREDENTIALS_ARRAY_KEY = "credentials";
//
//    const KEY_AUTH_FILE   = "auth_file";
//    const KEY_AUTH_BUCKET = "auth_bucket";
//    const KEY_AUTH_KEY    = "auth_key";
//
//    const KEY_FILE_LOCATION = "file_location";
//    const KEY_FILE_BUCKET   = "file_bucket";

    const AWS_ACCESS_ARRAY_KEY  = "aws_access_key_id";
    const AWS_SECRET_ARRAY_KEY  = "aws_secret_access_key";
    const AWS_REGION_ARRAY_KEY  = "aws_region";
    const AWS_VERSION_ARRAY_KEY = "aws_version";

    const DEFAULT_FJ_AWS_REGION  = "eu-west-1";
    const DEFAULT_FJ_AWS_VERSION = "latest";

    const PROVIDER_LOCAL = "local";
    const PROVIDER_PROXY = "proxy";
    const PROVIDER_CLOUD = "cloud";

    const DB_HOST_ARRAY_KEY = "db_host_";
    const DB_PORT_ARRAY_KEY = "db_port_";
    const DB_NAME_ARRAY_KEY = "db_name_";
    const DB_USER_ARRAY_KEY = "db_user_";
    const DB_PASS_ARRAY_KEY = "db_password_";

    const FILE_TYPE_CONFIG      = "config";
    const PATH_COMPONENT_CONFIG = "/" . self::FILE_TYPE_CONFIG . "/";

    const FILE_TYPE_AUTH      = "auth";
    const PATH_COMPONENT_AUTH = "/" . self::FILE_TYPE_AUTH . "/";

    const PROVIDER_TYPE_FILE   = "file";
    const PROVIDER_TYPE_CRUD   = "crud";
    const PROVIDER_TYPE_DB     = "db";
    const PROVIDER_TYPE_SECRET = "secrets";
//    const PROVIDER_TYPE_EMAIL  = "email";
//    const PROVIDER_TYPE_CSV    = "csv";

    const ENV_VERTWO_APP_KEY          = "vertwo_app";
    const ENV_VERTWO_LOCAL_ROOT_KEY   = "vertwo_local_root";
    const ENV_VERTWO_CLASS_PREFIX_KEY = "vertwo_class_prefix";



    private static $VERTWO_APP          = false;
    private static $VERTWO_CLASS_PREFIX = false;

    /** @var array|bool $VERTWO_PARAMS */
    private static $VERTWO_PARAMS           = false;
    private static $VERTWO_HAS_LOCAL_CONFIG = false;



    private static function _dump ( $mesg = false )
    {
        if ( false === $mesg ) $mesg = "PliteFactory.dump()";
        clog($mesg, self::$VERTWO_PARAMS);
    }



    /**
     * Expects web server to have 'vertwo_class_prefix' as an
     * environment variable available to PHP via $_SERVER.
     *
     * Then, uses that value to instantiate the relevant
     * ProviderFactory subclass.
     *
     * @return PliteFactory
     * @throws Exception
     */
    public static function newInstance () { return self::loadPrefixedClass("PliteFactory"); }



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
    public static function loadPrefixedClass ( $clazz )
    {
        $prefix = self::loadEnv(self::ENV_VERTWO_CLASS_PREFIX_KEY);

        if ( strlen($prefix) <= 0 ) throw new Exception ("No [ vertwo_class_prefix ] from ENV provided.");

        $className = $prefix . $clazz;

        clog("Instantiating $clazz sublcass", $className);

        if ( !class_exists($className) ) throw new Exception("Cannot load $clazz: [ " . $className . " ]");

        return new $className();
    }



    /**
     * @throws Exception
     */
    private function initParamsFromEnv ()
    {
        self::$VERTWO_APP          = self::loadEnv(self::ENV_VERTWO_APP_KEY);
        self::$VERTWO_CLASS_PREFIX = self::loadEnv(self::ENV_VERTWO_CLASS_PREFIX_KEY);

        if ( self::hasEnv(self::ENV_VERTWO_LOCAL_ROOT_KEY) )
        {
            $localRoot = self::loadEnv(self::ENV_VERTWO_LOCAL_ROOT_KEY)
                         . "/" . self::$VERTWO_APP;

            self::$VERTWO_HAS_LOCAL_CONFIG = false !== $localRoot && file_exists($localRoot) && is_dir($localRoot);
        }
        else
        {
            self::$VERTWO_HAS_LOCAL_CONFIG = false;
        }

        if ( self::$VERTWO_HAS_LOCAL_CONFIG )
        {
            if ( self::DEBUG_ENV ) clog("Loading LOCAL config (from filesystem [ " . $localRoot . " ])...");
            self::$VERTWO_PARAMS = $this->loadLocalConfig($localRoot);
        }
        else
        {
            if ( self::DEBUG_ENV ) clog("Loading DEFAULT config. (from subclass [ " . get_class($this) . " ])...");
            self::$VERTWO_PARAMS = $this->loadDefaultConfig();
        }
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
     * Determines if a key exists in the WebServer Environment.
     *
     * @param $key - Environment variable name.
     *
     * @return boolean - Does environment variable exist?
     */
    private static function hasEnv ( $key )
    {
        return array_key_exists($key, $_SERVER);
    }



    /**
     * @return array - JSON object containing both the config and auth info.
     */
    private static function loadLocalConfig ( $localRoot )
    {
        $conf   = self::loadConfigFile(self::getConfigFilePath($localRoot));
        $auth   = self::loadConfigFile(self::getAuthFilePath($localRoot));
        $params = array_merge($conf, $auth);

        return $params;
    }



    private static function getConfigFilePath ( $localRoot )
    {
        $localConfigRoot = $localRoot . self::PATH_COMPONENT_CONFIG;

        $filePath = $localConfigRoot .
                    self::getPathFilename(self::FILE_TYPE_CONFIG);

        if ( self::DEBUG_CONFIG_INFO ) clog("CONFIG file path", $filePath);

        return $filePath;
    }
    private static function getAuthFilePath ( $localRoot )
    {
        $localAuthRoot = $localRoot . self::PATH_COMPONENT_AUTH;

        $filePath = $localAuthRoot .
                    self::getPathFilename(self::FILE_TYPE_AUTH);

        if ( self::DEBUG_CONFIG_INFO ) clog("AUTH file path", $filePath);

        return $filePath;
    }
    private static function getPathFilename ( $file ) { return self::$VERTWO_APP . "-" . $file . ".js"; }



    private static function loadConfigFile ( $file )
    {
        if ( !is_readable($file) )
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



    private static function _has ( $key )
    {
        return array_key_exists($key, self::$VERTWO_PARAMS);
    }
    private static function _no ( $key ) { return !self::_has($key); }



    private static function _get ( $key )
    {
        return array_key_exists($key, self::$VERTWO_PARAMS) ? self::$VERTWO_PARAMS[$key] : null;
    }



    private static function getAWSRegion () { return self::_get(self::AWS_REGION_ARRAY_KEY); }
    private static function getAWSVersion () { return self::_get(self::AWS_VERSION_ARRAY_KEY); }



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
    abstract protected function loadDefaultConfig ();



    ////////////////////////////////////////////////////////////////
    //
    //
    // NOTE - Public Interface
    //
    //
    ////////////////////////////////////////////////////////////////

    /**
     * ProviderFactory constructor.  Works like a singleton.
     *
     * @throws Exception
     */
    private function __construct ()
    {
        if ( false === self::$VERTWO_PARAMS ) $this->initParamsFromEnv();
    }



    public final function getAppName () { return self::$VERTWO_APP; }



    public function getProviderValue ( $provider, $configKeyShort )
    {
        $provKey = $provider . "_provider";

        $prov = $this->get($provKey);

        if ( false === $prov ) return false;

        $configKey = $provider . "_" . $configKeyShort . "_" . $prov;

        return $this->get($configKey);
    }



    private function isUsingProviderSource ( $providerType, $source )
    {
        $key = $providerType . "_provider";
        return $this->matches($key, $source);
    }
    private function isUsingLocalProvider ( $provider ) { return $this->isUsingProviderSource($provider, self::PROVIDER_LOCAL); }
    private function isUsingCloudProvider ( $provider ) { return $this->isUsingProviderSource($provider, self::PROVIDER_CLOUD); }
    private function isUsingProxyProvider ( $provider ) { return $this->isUsingProviderSource($provider, self::PROVIDER_PROXY); }



    public function dump ( $mesg = false ) { self::_dump($mesg); }
    public function has ( $key ) { return self::_has($key); }
    public function no ( $key ) { return self::_no($key); }
    public function get ( $key ) { return self::_get($key); }



    private function matches ( $key, $targetValue )
    {
        return $this->has($key) ? $targetValue === $this->get($key) : false;
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
     * credentials are present.
     */
    private function getCredsAWS ()
    {
        $creds = [
            'region'  => self::getAWSRegion(),
            'version' => self::getAWSVersion(),
        ];

        if ( self::$VERTWO_HAS_LOCAL_CONFIG )
        {
            $access = $this->get(self::AWS_ACCESS_ARRAY_KEY);
            $secret = $this->get(self::AWS_SECRET_ARRAY_KEY);

            if ( self::DEBUG_AWS_CREDS ) clog(self::AWS_ACCESS_ARRAY_KEY, $access);

            $creds[self::AWS_CREDENTIALS_ARRAY_KEY] = [
                'key'    => $access,
                'secret' => $secret,
            ];
        }

        if ( self::DEBUG_CREDS_DANGEROUS ) clog("getCredsAWS() - creds", $creds);

        return $creds;
    }



    /**
     * @return S3Client|bool
     */
    private function getS3Client ()
    {
        $creds = $this->getCredsAWS();
        try
        {
            $s3 = new S3Client($creds);
        }
        catch ( Exception $e )
        {
            clog($e);
            clog("Cannot get AWS S3 Client; returning(false) . ");
            $s3 = false;
        }

        self::clearParams($creds);

        return $s3;
    }



    private function getSESClient ()
    {
        $creds = $this->getCredsAWS();
        try
        {
            $ses = new SesClient($creds);
        }
        catch ( Exception $e )
        {
            clog($e);
            clog("Cannot get AWS SES Client; returning(false) . ");
            $ses = false;
        }

        $creds = self::clearParams($creds);

        return $ses;

    }



    /**
     * @return CRUDProvider
     *
     * @throws Exception
     */
    public function getCRUDProvider ()
    {
        $providerType = self::PROVIDER_TYPE_CRUD;
        $isProvLocal  = $this->isUsingLocalProvider($providerType);

        clog("is $providerType local?", $isProvLocal);

        $provParams = $isProvLocal
            ? $this->getCRUDParamsLocal()
            : $this->getCRUDParamsAWS();

        $prov = $isProvLocal
            ? new CRUDProviderLocal($provParams)
            : new CRUDProviderAWS($provParams);

        $provParams = false;
        $params     = false;

        return $prov;
    }



    /**
     * @return array
     *
     * @throws Exception
     */
    private function getCRUDParamsLocal () { return []; }
    private function getCRUDParamsAWS ()
    {
        $s3 = $this->getS3Client();

        $params = [
            "s3" => $s3,
        ];

        return $params;
    }



//    /**
//     * @return CRUDProvider
//     *
//     * @throws Exception
//     */
//    public function getCSVProvider ()
//    {
//        $providerType = self::PROVIDER_TYPE_CRUD;
//        $isProvLocal  = $this->isUsingLocalProvider($providerType);
//
//        clog("is $providerType local?", $isProvLocal);
//
//        $provParams = $isProvLocal
//            ? $this->getCRUDParamsLocal()
//            : $this->getCRUDParamsAWS();
//
//        $prov = $isProvLocal
//            ? new CSVProviderLocal($provParams)
//            : new CSVProviderAWS($provParams);
//
//        $provParams = false;
//        $params     = false;
//
//        return $prov;
//    }



//    /**
//     * @param string $secretName
//     *
//     * @return EmailProvider
//     * @throws Exception
//     */
//    public function getEmailProvider ( $secretName )
//    {
//        $providerType = self::PROVIDER_TYPE_EMAIL;
//        $isProvLocal  = $this->isUsingLocalProvider($providerType);
//
//        clog("is $providerType local?", $isProvLocal);
//
//        $provParams = $isProvLocal
//            ? $this->getEmailParamsLocal($secretName)
//            : $this->getEmailParamsAWS($params);
//
//        $prov = $isProvLocal
//            ? new EmailProviderLocal($provParams)
//            : new EmailProviderAWS($provParams);
//
//        $provParams = false;
//        $params     = false;
//
//        return $prov;
//    }
//
//
//
//    /**
//     * @param $secretName
//     *
//     * @return bool|mixed
//     * @throws Exception
//     */
//    private function getEmailParamsLocal ( $secretName )
//    {
//        return $this->getSecret($secretName);
//    }
//
//
//
//    /**
//     * @param $secretName - Ignored here.
//     *
//     * @return array
//     */
//    private function getEmailParamsAWS ( $params )
//    {
//        $fromEmail = $params['email_cloud_from_email'];
//        $fromName  = $params['email_cloud_from_email'];
//
//        $ses = $this->getSESClient();
//
//        $params = [
//            "ses"        => $ses,
//            "from-email" => $fromEmail,
//            "from-name"  => $fromName,
//        ];
//
//        return $params;
//    }



//    /**
//     * @param string $secretName
//     *
//     * @return EmailIntegration
//     * @throws Exception
//     */
//    public function getEmailIntegration ( $secretName )
//    {
//        $emailParams = $this->getSecret($secretName);
//
//        $em = new EmailIntegration($emailParams);
//
//        $emailParams = false;
//
//        return $em;
//    }



    /**
     * @param string $secretName
     *
     * @return TrelloIntegration
     * @throws Exception
     */
    public function getTrelloIntegration ( $secretName )
    {
        $trelloAPI = $this->getSecret($secretName);
        $key       = $trelloAPI['public_key'];
        $token     = $trelloAPI['secret_token'];

        $provParams = [
            "key"   => $key,
            "token" => $token,
            "debug" => true,
        ];

        $tp = new TrelloIntegration($provParams);

        $provParams = false;

        return $tp;
    }



    /**
     * @return FileProvider
     */
    public function getFileProvider ()
    {
        $providerType = self::PROVIDER_TYPE_FILE;
        $isProvLocal  = $this->isUsingLocalProvider($providerType);

        clog("is $providerType local?", $isProvLocal);

        $provParams = $isProvLocal
            ? $this->getFileParamsLocal()
            : $this->getFileParamsAWS();

        $prov = $isProvLocal
            ? new FileProviderLocal($provParams)
            : new FileProviderAWS($provParams);

        $provParams = false;
        $params     = false;

        return $prov;
    }



    private function getFileParamsLocal ()
    {
        if ( !$this->has(self::KEY_FILE_LOCATION) )
        {
            Log::error("Cannot find auth file; aborting.");
            return [];
        }
        if ( !$this->has(self::KEY_FILE_BUCKET) )
        {
            Log::error("Cannot find auth bucket; aborting.");
            return [];
        }

        $authFilePath = $this->get(self::KEY_FILE_LOCATION);
        $authBucket   = $this->get(self::KEY_FILE_BUCKET);

        $params = [
            self::KEY_FILE_LOCATION => $authFilePath,
            self::KEY_FILE_BUCKET   => $authBucket,
        ];
        return $params;
    }



    private function getFileParamsAWS ()
    {
        $s3 = $this->getS3Client();

        $params = [
            "s3" => $s3,
        ];

        return $params;
    }



    /**
     * NOTE - This framework is linked to version 3.234.4 of the AWS SDK.
     *
     * WARN - Previous versions weren't working in ElasticBeanstalk with Amazon Linux 2 with IMDSv2.
     *
     * This is a chance from AL1 with IMDSv? (unknown in older version of ElasticBeanstalk).
     *
     * @return SecretsManagerClient|bool
     */
    private function getSecretsManagerClient ()
    {
        $creds = $this->getCredsAWS();
        try
        {
            if ( self::DEBUG_CREDS_DANGEROUS ) clog("creds for SecMan", $creds);

            $secman = new SecretsManagerClient($creds);
        }
        catch ( Exception $e )
        {
            clog($e);
            clog("Cannot get AWS SecMan Client; returning(false)");
            $secman = false;
        }

        self::clearParams($creds);

        return $secman;
    }



    private static function clearParams ( &$params )
    {
        unset($params[self::AWS_CREDENTIALS_ARRAY_KEY]);
        $params = false;
    }



    /**
     * @return PG
     *
     * @throws Exception
     */
    public function getDatabaseConnection ()
    {
        if ( self::DEBUG_DB_CONN ) $this->dump();

        $provKey = self::PROVIDER_TYPE_DB . "_provider";
        $source  = $params[$provKey];

        switch ( $source )
        {
            case self::PROVIDER_CLOUD:
                $dbParams = $this->getRDSParams();
                if ( self::DEBUG_DB_CONN_VERBOSE ) clog("db (CLOUD) params", $dbParams);
                break;

            default:
                $dbParams = $this->getLocalDBParams($params, $source);
                if ( self::DEBUG_DB_CONN_VERBOSE ) clog("db (local) params", $dbParams);
                break;
        }

        if ( self::DEBUG_DB_CONN ) clog("DB params", $dbParams);

        $connString = $this->getDatabaseConnectionString($dbParams);

        $dbParams   = false;
        $pg         = new PG($connString); // <--------- MEAT
        $connString = false;

        return $pg;
    }



//    /**
//     * @return PGNew
//     *
//     * @throws Exception
//     */
//    public function getDatabaseConnectionWithExceptions ()
//    {
//        $params = $this->loadConfigParams();
//
//        clog("params", $params);
//
//        $provKey = self::PROVIDER_TYPE_DB . "_provider";
//        $source  = $params[$provKey];
//
//        switch ( $source )
//        {
//            case self::PROVIDER_CLOUD:
//                $dbParams = $this->getRDSParams();
//                if ( self::DEBUG_DB_CONN_VERBOSE ) clog("db (CLOUD) params", $dbParams);
//                break;
//
//            default:
//                $dbParams = $this->getLocalDBParams($params, $source);
//                if ( self::DEBUG_DB_CONN_VERBOSE ) clog("db (local) params", $dbParams);
//                break;
//        }
//
//        clog("DB params", $dbParams);
//
//        $connString = $this->getDatabaseConnectionString($dbParams);
//
//        $dbParams   = false;
//        $pg         = new PGNew($connString); // <--------- MEAT
//        $connString = false;
//
//        return $pg;
//    }



//    /**
//     * @return PGCursorConn
//     *
//     * @throws Exception
//     */
//    public function getCursorDatabaseConnection ()
//    {
//        $params = $this->loadConfigParams();
//
//        if ( self::DEBUG_DB_CONN ) clog("params", $params);
//
//        $provKey = self::PROVIDER_TYPE_DB . "_provider";
//        $source  = $params[$provKey];
//
//        switch ( $source )
//        {
//            case self::PROVIDER_CLOUD:
//                $dbParams = $this->getRDSParams();
//                if ( self::DEBUG_DB_CONN_VERBOSE ) clog("db (CLOUD) params", $dbParams);
//                break;
//
//            default:
//                $dbParams = $this->getLocalDBParams($params, $source);
//                if ( self::DEBUG_DB_CONN_VERBOSE ) clog("db (local) params", $dbParams);
//                break;
//        }
//
//        if ( self::DEBUG_DB_CONN ) clog("DB params (buffered)", $dbParams);
//
//        $db = PGCursorConn::newInstance($dbParams); // <--------- MEAT
//
//        return $db;
//    }



    /**
     * @param $config - Local config parameters.
     * @param $source - e.g., 'local' or 'proxy'.
     *
     * @return array
     *
     * @throws Exception
     */
    private function getLocalDBParams ( $config, $source )
    {
        $hostKey = self::DB_HOST_ARRAY_KEY . $source;
        $portKey = self::DB_PORT_ARRAY_KEY . $source;
        $nameKey = self::DB_NAME_ARRAY_KEY . $source;

        $host = $config[$hostKey];
        $port = $config[$portKey];
        $name = $config[$nameKey];

        clog("host", $host);
        clog("port", $port);
        clog("name", $name);

        if ( self::PROVIDER_PROXY == $source )
        {
            $params = self::getRDSParams();
            $user   = $params['username'];
            $pass   = $params['password'];
        }
        else
        {
            $userKey = self::DB_USER_ARRAY_KEY . $source;
            $passKey = self::DB_PASS_ARRAY_KEY . $source;

            $user = $config[$userKey];
            $pass = $config[$passKey];
        }

        return [
            'host'     => $host,
            'port'     => $port,
            'username' => $user,
            'password' => $pass,
            'dbname'   => $name,
        ];
    }



    private function getDatabaseConnectionString ( $dbParams )
    {
        if ( false === $dbParams ) return false;

        $host = $dbParams['host'];
        $port = $dbParams['port'];
        $user = $dbParams['username'];
        $pass = $dbParams['password'];
        $db   = $dbParams['dbname'];

        $dbstr = "host = $host port = $port dbname = $db user = $user";

        if ( self::DEBUG_DB_CONN ) clog("getDBConnectionString - DB conn str(no passwd)", $dbstr);

        $dbstr .= " password = $pass";

        return $dbstr;
    }



    /**
     * @param $secretName
     *
     * @return bool|mixed
     * @throws Exception
     */
    public function getSecret ( $secretName )
    {
        if ( self::DEBUG_SECRETS_MANAGER ) $this->dump();

        $provKey = self::PROVIDER_TYPE_SECRET . "_provider";
        $source  = $this->get($provKey);

        switch ( $source )
        {
            case self::PROVIDER_CLOUD:
                return $this->getSecretFromCloud($secretName);

            default:
                return $this->getSecretLocally($secretName);
        }
    }



    /**
     * @param $secretName
     *
     * @throws Exception
     */
    private function getSecretLocally ( $secretName )
    {
        clog("Looking for local secret", $secretName);

        if ( $this->has($secretName) )
        {
            return $this->get($secretName);
        }
        else
        {
            if ( self::DEBUG_SECRETS_MANAGER ) $this->dump($secretName);

            throw new Exception("Cannot find secret [ $secretName ] in local AUTH params.");
        }
    }



    private function getSecretFromCloud ( $secretName )
    {
        if ( self::DEBUG_SECRETS_MANAGER ) clog("getSec...FromCloud() - ANTE AWS SecMan Client", $secretName);

        $client = $this->getSecretsManagerClient();

        if ( self::DEBUG_SECRETS_MANAGER ) clog("getSec...FromCloud() - POST AWS SecMan Client");

        if ( false === $client || !$client )
        {
            redlog("Cannot create SecManClient object; aborting");
            return false;
        }

        try
        {
            clog("getSec...FromCloud()", "Getting secret [$secretName]...");

            $result = $client->getSecretValue(
                [
                    'SecretId' => $secretName,
                ]
            );
        }
        catch ( AwsException $e )
        {
            $error = $e->getAwsErrorCode();
            $this->handleSecManError($error);

            cclog(Log::TEXT_COLOR_BG_RED, "FAIL to get secrets.");
            return false;
        }
        catch ( Exception $e )
        {
            clog($e);
            clog("General error", $e);
            return false;
        }

        // Decrypts secret using the associated KMS CMK.
        // Depending on whether the secret is a string or binary, one of these fields will be populated.
        if ( isset($result['SecretString']) )
        {
            $secret = $result['SecretString'];
        }
        else
        {
            $secret = base64_decode($result['SecretBinary']);
        }

        // Your code goes here;
        if ( self::DEBUG_CREDS_DANGEROUS ) clog("secrets", $secret);

        return FJ::jsDecode($secret);
    }



    protected function handleSecManError ( $error )
    {
        if ( $error == 'DecryptionFailureException' )
        {
            // Secrets Manager can't decrypt the protected secret text using the provided AWS KMS key.
            // Handle the exception here, and/or rethrow as needed.
            clog("AWS SecMan error (handle in subclass)", $error);
        }
        if ( $error == 'InternalServiceErrorException' )
        {
            // An error occurred on the server side.
            // Handle the exception here, and/or rethrow as needed.
            clog("AWS SecMan error (handle in subclass)", $error);
        }
        if ( $error == 'InvalidParameterException' )
        {
            // You provided an invalid value for a parameter.
            // Handle the exception here, and/or rethrow as needed.
            clog("AWS SecMan error (handle in subclass)", $error);
        }
        if ( $error == 'InvalidRequestException' )
        {
            // You provided a parameter value that is not valid for the current state of the resource.
            // Handle the exception here, and/or rethrow as needed.
            clog("AWS SecMan error (handle in subclass)", $error);
        }
        if ( $error == 'ResourceNotFoundException' )
        {
            // We can't find the resource that you asked for.
            // Handle the exception here, and/or rethrow as needed.
            clog("AWS SecMan error (handle in subclass)", $error);
        }

        clog("AWS SecMan Error", $error);
        Log::error($error);
    }



    /**
     * @return bool|array
     *
     * @throws Exception
     */
    private function getRDSParams ( $secretName )
    {
        return $this->getSecret($secretName);
    }
}
