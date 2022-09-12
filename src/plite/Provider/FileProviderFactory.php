<?php



namespace vertwo\plite\Provider;



use Exception;
use vertwo\plite\Config;
use vertwo\plite\Provider\AWS\FileProviderAWS;
use vertwo\plite\Provider\Local\FileProviderLocal;
use function vertwo\plite\clog;



class FileProviderFactory implements ProviderFactory
{
    const PROVIDER_TYPE         = "file";
    const LOCAL_ROOT_PREFIX_KEY = "file_provider_local_root_prefix";
    const LOCAL_ROOT_SUFFIX_KEY = "file_provider_local_root_suffix";
    const LOCAL_ROOT_KEY        = "file_provider_local_root_dir";



    /**
     * @return string
     */
    static function getProviderType () { return self::PROVIDER_TYPE; }



    /**
     * @return mixed
     * @throws Exception
     */
    public static function getProvider ()
    {
        Config::init();

        $providerSource = Config::getProviderSource(self::getProviderType());

        switch ( $providerSource )
        {
            case NouseFactory::PROVIDER_CLOUD:
                $params   = self::getParamsAWS();
                $fileProv = new FileProviderAWS($params);
                break;

            case NouseFactory::PROVIDER_LOCAL:
                $params   = self::getParamsLocal();
                $fileProv = new FileProviderLocal($params);
                break;

            case NouseFactory::PROVIDER_PROXY:
            default:
                throw new Exception(self::getProviderType() . "Provider source $providerSource not supported.");
        }

        return $fileProv;
    }



    /**
     * @param Config $config
     *
     * @return array
     * @throws Exception
     */
    private static function getParamsLocal ()
    {
        $hasRoot = Config::has(self::LOCAL_ROOT_KEY);

        if ( $hasRoot )
        {
            $localRoot = Config::get(self::LOCAL_ROOT_KEY);
        }
        else
        {
            $hasPrefix = Config::has(self::LOCAL_ROOT_PREFIX_KEY);
            $hasSuffix = Config::has(self::LOCAL_ROOT_SUFFIX_KEY);

            if ( $hasPrefix || $hasSuffix )
            {
                if ( $hasPrefix && $hasSuffix )
                {
                    $prefix = Config::get("file_provider_local_root_prefix");
                    $suffix = Config::get("file_provider_local_root_suffix");
                    $app    = Config::getAppName();

                    $localRoot = $prefix . $app . $suffix;

                    clog("Local root", $localRoot);
                }
                else
                {
                    throw new Exception("Missing either file_provider_local_root_prefix or _suffix; check <app>-config.js.");
                }
            }
            else
            {
                throw new Exception("Missing file_provider_local_root_dir or (_prefix and _suffix); check <app>-config.js.");
            }
        }

        $params = [
            FileProvider::LOCAL_ROOT_KEY => $localRoot,
        ];

        return $params;
    }



    /**
     * @param Config $config
     *
     * @return array
     * @throws Exception
     */
    private static function getParamsAWS ()
    {
        return Config::getCredsAWS();
    }
}
