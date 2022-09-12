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



/**
 * Class PliteFactory
 *
 * @package vertwo\plite\Provider
 */
abstract class NouseFactory
{
    const DEBUG_DB_CONN         = false;
    const DEBUG_DB_CONN_VERBOSE = false;
    const DEBUG_AWS_CREDS       = false;

    const DEBUG_CREDS_DANGEROUS = false; // DANGER - In __PRODUCTION__, this must be set to (false)!!!!!



    const AWS_CREDENTIALS_ARRAY_KEY = "credentials";

    const AWS_ACCESS_ARRAY_KEY = "aws_access_key_id";
    const AWS_SECRET_ARRAY_KEY = "aws_secret_access_key";

    const AWS_REGION_ARRAY_KEY  = "aws_region";
    const AWS_VERSION_ARRAY_KEY = "aws_version";

    const PROVIDER_LOCAL = "local";
    const PROVIDER_PROXY = "proxy";
    const PROVIDER_CLOUD = "cloud";

    const DB_HOST_ARRAY_KEY = "db_host_";
    const DB_PORT_ARRAY_KEY = "db_port_";
    const DB_NAME_ARRAY_KEY = "db_name_";
    const DB_USER_ARRAY_KEY = "db_user_";
    const DB_PASS_ARRAY_KEY = "db_password_";

    const PROVIDER_TYPE_FILE   = "file";
    const PROVIDER_TYPE_CRUD   = "crud";
    const PROVIDER_TYPE_DB     = "db";
    const PROVIDER_TYPE_SECRET = "secrets";
//    const PROVIDER_TYPE_EMAIL  = "email";
//    const PROVIDER_TYPE_CSV    = "csv";



    /** @var Config|bool $config */
    private $config = false;



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
    public function __construct ()
    {
        $this->config = Config::newInstance();
    }



    public function dump ( $mesg = false ) { $this->config->dump($mesg); }
    public function has ( $key ) { return $this->config->has($key); }
    public function get ( $key ) { return $this->config->get($key); }
    public function no ( $key ) { return $this->config->no($key); }
    public function matches ( $key, $targetValue ) { return $this->config->matches($key, $targetValue); }
    public function getAppName () { return $this->config->getAppName(); }



    public function getProviderSource ( $providerType )
    {
        $provKey = $providerType . "_provider";
        $source  = $this->get($provKey);

        return $source;
    }



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
    public function getCredsAWS ()
    {
        $creds = [
            'region'  => self::getAWSRegion(),
            'version' => self::getAWSVersion(),
        ];

        $hasAccess = $this->get(self::AWS_ACCESS_ARRAY_KEY);
        $hasSecret = $this->get(self::AWS_SECRET_ARRAY_KEY);

        $hasAwsCreds = $hasAccess && $hasSecret;

        if ( $hasAwsCreds )
        {
            $access = $this->get(self::AWS_ACCESS_ARRAY_KEY);
            $secret = $this->get(self::AWS_SECRET_ARRAY_KEY);

            if ( self::DEBUG_AWS_CREDS ) clog(self::AWS_ACCESS_ARRAY_KEY, $access);

            $creds[self::AWS_CREDENTIALS_ARRAY_KEY] = [
                'access' => $access,
                'secret' => $secret,
            ];
        }

        if ( self::DEBUG_CREDS_DANGEROUS ) clog("getCredsAWS() - creds", $creds);

        return $creds;
    }
    private function getAWSRegion () { return $this->config->get(self::AWS_REGION_ARRAY_KEY); }
    private function getAWSVersion () { return $this->config->get(self::AWS_VERSION_ARRAY_KEY); }



    /**
     * @param $secretName
     *
     * @return bool|mixed
     * @throws Exception
     */
    public function getSecret ( $secretName ) { return Secrets::get($this, $secretName); }



//    /**
//     * @return FileProvider
//     */
//    public function getFileProvider ()
//    {
//        $providerType = self::PROVIDER_TYPE_FILE;
//        $isProvLocal  = $this->isUsingLocalProvider($providerType);
//
//        clog("is $providerType local?", $isProvLocal);
//
//        $provParams = $isProvLocal
//            ? $this->getFileParamsLocal()
//            : $this->getFileParamsAWS();
//
//        $prov = $isProvLocal
//            ? new FileProviderLocal($provParams)
//            : new FileProviderAWS($provParams);
//
//        $provParams = false;
//        $params     = false;
//
//        return $prov;
//    }
//    private function getFileParamsLocal ()
//    {
//        if ( !$this->has(self::KEY_FILE_LOCATION) )
//        {
//            Log::error("Cannot find auth file; aborting.");
//            return [];
//        }
//        if ( !$this->has(self::KEY_FILE_BUCKET) )
//        {
//            Log::error("Cannot find auth bucket; aborting.");
//            return [];
//        }
//
//        $authFilePath = $this->get(self::KEY_FILE_LOCATION);
//        $authBucket   = $this->get(self::KEY_FILE_BUCKET);
//
//        $params = [
//            self::KEY_FILE_LOCATION => $authFilePath,
//            self::KEY_FILE_BUCKET   => $authBucket,
//        ];
//        return $params;
//    }
//    private function getFileParamsAWS ()
//    {
//        $s3 = $this->getS3Client();
//
//        $params = [
//            "s3" => $s3,
//        ];
//
//        return $params;
//    }
//    /**
//     * @return S3Client|bool
//     */
//    private function getS3Client ()
//    {
//        $creds = $this->getCredsAWS();
//        try
//        {
//            $s3 = new S3Client($creds);
//        }
//        catch ( Exception $e )
//        {
//            clog($e);
//            clog("Cannot get AWS S3 Client; returning(false) . ");
//            $s3 = false;
//        }
//
//        self::clearParams($creds);
//
//        return $s3;
//    }



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
     * @return bool|array
     *
     * @throws Exception
     */
    private function getRDSParams ( $secretName )
    {
        return $this->getSecret($secretName);
    }
}
