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



namespace vertwo\plite\Provider\AWS;



use Aws\S3\Exception\S3Exception;
use Aws\S3\S3Client;
use Exception;
use vertwo\plite\FJ;
use vertwo\plite\Provider\Base\FileProviderBase;
use function vertwo\plite\clog;
use function vertwo\plite\red;



class FileProviderAWS extends FileProviderBase
{
    const DEBUG_INIT = true;
    
    
    
    /** @var S3Client|bool $s3 */
    private $s3     = false;
    private $bucket = false;
    
    
    
    /**
     * FileProviderAWS constructor.
     *
     * @param $creds
     *
     * @throws Exception
     */
    function __construct ( $creds )
    {
        $this->s3 = new S3Client($creds);
    }
    
    
    
    /**
     * @param array|bool $params
     *
     * @throws Exception
     */
    public function init ( $params = false )
    {
        if ( false === $this->s3 )
        {
            clog(red("FP.init(): Cannot establish AWS S3 connection."));
            throw new Exception("Cannot get AWS S3 reference; aborting.");
        }
        
        if ( false === $params )
        {
            throw new Exception("No parameters (thus no bucket) given.");
        }
        
        if ( !array_key_exists("bucket", $params) )
        {
            throw new Exception("No bucket given.");
        }
        
        $this->bucket = $params["bucket"];
        
        if ( self::DEBUG_INIT ) clog("FP.init()", "S3Client successfully init'ed.");
    }
    
    
    
    /**
     * DANGER - Even V2 API now ONLY lists the first 1,000 elements!
     *  When did this change??
     *
     * It would be REALLY nice if this had unix ls semantics:
     *
     *   1. Only list one level down.
     *   2. Exclude itself.
     *   3. Remove trailing slashes for dirs.
     *
     * @param bool|array $params
     *
     * @return array
     */
    public function ls ( $params = false )
    {
        if ( self::DEBUG_S3_VERBOSE ) clog("params", $params);
        
        if ( self::DEBUG_S3_VERBOSE ) clog("ante-iterator");
        
        $paramsS3 = [
          'Bucket'    => $this->bucket,
          'Delimiter' => '/',
        ];
        
        $prefix = (false !== $params && array_key_exists('prefix', $params)) ? $params['prefix'] : false;
        if ( false !== $prefix ) $paramsS3['Prefix'] = $prefix;
        
        $prefixLen = (false === $prefix) ? 0 : strlen($prefix);
        
        if ( self::DEBUG_S3_VERBOSE ) clog("params for S3", $paramsS3);
        
        $list = [];
        $resp = $this->s3->listObjects($paramsS3);
        
        //
        // NOTE - This is some sample code to prep for V3 API.
        //
//        $count = $resp->count();
//        $contents = $resp['Contents'];
//
//        clog("resp", $resp);
//        clog("resp->count()", $count);
//        clog("resp['Contents']", $resp['Contents']);
        
        if ( $resp->hasKey("CommonPrefixes") )
        {
            $commonPrefixes = $resp['CommonPrefixes'];
            foreach ( $commonPrefixes as $cp )
            {
                $p = $cp['Prefix'];
                
                if ( $p === $prefix ) continue; // Skip self (".");
                
                $list[] = substr($p, $prefixLen);
            }
        }
        
        if ( self::DEBUG_S3_VERBOSE ) clog("  S3 'dirs'", $list);
        
        $objects = $resp['Contents'];
        foreach ( $objects as $obj )
        {
            $key = $obj['Key'];
            
            if ( in_array($key, $list) ) continue;
            if ( $key === $prefix ) continue; // Skip self (".");
            
            $list[] = substr($key, $prefixLen);
        }
        
        if ( self::DEBUG_S3_VERBOSE ) clog("  S3 'dirs' + 'files'", $list);
        
        return $list;
    }
    public function lsDirs ( $params = false )
    {
        $dirs = [];
        $list = $this->ls($params);
        foreach ( $list as $obj ) if ( FJ::endsWith("/", $obj) ) $dirs[] = rtrim($obj, "/");
        return $dirs;
    }
    public function lsFiles ( $params = false )
    {
        $files = [];
        $list  = $this->ls($params);
        foreach ( $list as $obj ) if ( !FJ::endsWith("/", $obj) ) $files[] = $obj;
        return $files;
    }
    
    
    
    public function write ( $path, $data, $meta = false )
    {
        $data = trim($data) . "\n";
        
        $params = [
          'Bucket' => $this->bucket,
          'Key'    => $path,
          'Body'   => $data,
        ];
        
        if ( false != $meta ) $params['Metadata'] = $meta;
        
        if ( self::DEBUG_S3_VERBOSE ) clog("S3.put", $params);
        else clog("S3.put", [$params['Bucket'], $params['Key']]);
        
        try
        {
            $result = $this->s3->putObject($params);
            
            clog("S3 Object URL", $result['ObjectURL']);
            
            return true;
        }
        catch ( S3Exception $e )
        {
            //clog($e);
            clog(red("Could not PUT S3 object [$path] in bucket [" . $this->bucket . "]: " . $e->getMessage()));
            return false;
        }
    }
    
    
    
    /**
     * @param $path
     *
     * @return bool|string
     *
     * @throws S3Exception
     */
    public function read ( $path )
    {
        $all  = $this->readWithMeta($path);
        $data = $all['data'];
        
        return $data;
    }
    
    
    
    public function readWithMeta ( $path )
    {
        $params = [
          'Bucket' => $this->bucket,
          'Key'    => $path,
        ];
        
        $result = $this->s3->getObject($params);
        
        $data = $result['Body']->getContents();
        $meta = $result->toArray();
        unset($meta['Body']);
        
        return [
          "meta" => $meta,
          "data" => $data,
        ];
    }
}
