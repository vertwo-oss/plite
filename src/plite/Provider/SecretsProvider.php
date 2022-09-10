<?php



namespace vertwo\plite\Provider;



use Aws\Exception\AwsException;
use Aws\SecretsManager\SecretsManagerClient;
use Exception;
use vertwo\plite\Ball;
use vertwo\plite\FJ;
use vertwo\plite\Log;
use function vertwo\plite\cclog;
use function vertwo\plite\clog;
use function vertwo\plite\redlog;



class SecretsProvider
{
    const DEBUG_CREDS_DANGEROUS = false; // DANGER - In __PRODUCTION__, this must be set to (false)!!!!!



    const DEBUG_SECRETS_MANAGER = false;



    const PROVIDER_TYPE_SECRET = "secrets";



    /**
     * Returns a value if 'get' is semantically well-defined.
     *
     * In the case of AWS Secrets Manager, get() just retrieves a single secret.
     * In the case of S3, get() would retrieve the client.
     *
     * @param PliteFactory $pf
     * @param mixed        $secretName - Name of secret "blob"
     * @param string|bool  $secretPath - Path into blob for specific "sub-secret".
     *
     * @return mixed
     * @throws Exception
     */
    public static function get ( $pf, $secretName, $secretPath = false )
    {
        $providerTypeKey = self::PROVIDER_TYPE_SECRET . "_provider";
        $providerType    = $pf->get($providerTypeKey);

        if ( self::DEBUG_SECRETS_MANAGER ) $pf->dump();

        switch ( $providerType )
        {
            case PliteFactory::PROVIDER_CLOUD:
                $secretBlob = self::getSecretFromCloud($pf, $secretName);
                break;

            default:
                $secretBlob = self::getSecretLocally($pf, $secretName);
                break;
        }

        if ( false === $secretPath )
        {
            return $secretBlob;
        }
        else
        {
            $secretBall = new Ball($secretBlob);
            return $secretBall->get($secretPath);
        }
    }



    /**
     * @param PliteFactory $pf
     * @param string       $secretName
     *
     * @return bool|mixed
     * @throws Exception
     */
    private static function getSecretFromCloud ( $pf, $secretName )
    {
        if ( self::DEBUG_SECRETS_MANAGER ) clog("getSecr*tFromCloud() - ANTE AWS SecMan Client", $secretName);

        $client = self::getSecretsManagerClient($pf);

        if ( self::DEBUG_SECRETS_MANAGER ) clog("getSecr*tFromCloud() - POST AWS SecMan Client");

        if ( false === $client || !$client )
        {
            redlog("Cannot create SecManClient object; aborting");
            return false;
        }

        try
        {
            clog("getSecr*tFromCloud()", "Getting secret [$secretName]...");

            $result = $client->getSecretValue(
                [
                    'SecretId' => $secretName,
                ]
            );
        }
        catch ( AwsException $e )
        {
            $error = $e->getAwsErrorCode();
            self::handleSecManError($error);

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



    /**
     * NOTE - This framework is linked to version 3.234.4 of the AWS SDK.
     *
     * WARN - Previous versions weren't working in ElasticBeanstalk with Amazon Linux 2 with IMDSv2.
     *
     * This is a chance from AL1 with IMDSv? (unknown in older version of ElasticBeanstalk).
     *
     * @param PliteFactory $pf
     *
     * @return SecretsManagerClient|bool
     * @throws Exception
     */
    private static function getSecretsManagerClient ( $pf )
    {
        $creds = $pf->getCredsAWS();
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

        return $secman;
    }



    protected static function handleSecManError ( $error )
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
     * @param PliteFactory $pf
     * @param string       $secretName
     *
     * @return mixed
     * @throws Exception
     */
    private static function getSecretLocally ( $pf, $secretName )
    {
        clog("Looking for local secret", $secretName);

        if ( $pf->has($secretName) )
        {
            return $pf->get($secretName);
        }
        else
        {
            if ( self::DEBUG_SECRETS_MANAGER ) $pf->dump($secretName);

            throw new Exception("Cannot find secret [ $secretName ] in local AUTH params.");
        }
    }
}
