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



namespace vertwo\plite\Provider;



use Exception;
use vertwo\plite\Config;
use vertwo\plite\Provider\AWS\FileProviderAWS;
use vertwo\plite\Provider\Local\FileProviderLocal;



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
