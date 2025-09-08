<?php
/*
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



namespace vertwo\plite\Provider;



use Exception;
use vertwo\plite\Config;
use vertwo\plite\Provider\AWS\FileProviderAWS;
use vertwo\plite\Provider\Base\ProviderFactoryBase;
use vertwo\plite\Provider\Local\FileProviderLocal;



class FileProviderFactory implements ProviderFactory
{
    const PROVIDER_TYPE = "file";
    //const LOCAL_ROOT_KEY        = "file_provider_local_root_dir";
    //const LOCAL_ROOT_PREFIX_KEY = "file_provider_local_root_prefix";
    //const LOCAL_ROOT_SUFFIX_KEY = "file_provider_local_root_suffix";
    
    
    /**
     * @return mixed
     * @throws Exception
     */
    public static function getProvider ( $name )
    {
        $source = Config::verifyProviderAndGetSource($name, self::PROVIDER_TYPE); //
        
        switch ( $source )
        {
            case Config::PROVIDER_LOCAL:
                $params   = self::getParamsLocal($name);
                $fileProv = new FileProviderLocal($params);
                break;
            
            case Config::PROVIDER_AWS:
                $params   = self::getParamsAWS($name);
                $fileProv = new FileProviderAWS($params);
                break;
            
            case Config::PROVIDER_PROXY:
            default:
                throw new Exception("FP - source [$source] not supported.");
        }
        
        return $fileProv;
    }
    
    
    /**
     * @param Config $config
     *
     * @return array
     * @throws Exception
     */
    private static function getParamsLocal ( $name )
    {
        if ( Config::no(Config::ENV_PLITE_LOCAL_ROOT_KEY) )
        {
            throw new Exception("FP - No local root specified; check config.");
        }
        
        $localRoot = Config::get(Config::ENV_PLITE_LOCAL_ROOT_KEY);
        $appName   = Config::getAppName();
        
        $root = $localRoot .
          DIRECTORY_SEPARATOR . $appName .
          DIRECTORY_SEPARATOR . "data" .
          DIRECTORY_SEPARATOR;
        
        $params = [
          "root" => $root,
        ];
        
        return $params;
    }
    
    
    /**
     * @param Config $config
     *
     * @return array
     * @throws Exception
     */
    private static function getParamsAWS ( $name )
    {
        return Config::getCredsAWS();
    }
}
