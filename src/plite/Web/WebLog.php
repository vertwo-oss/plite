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



namespace vertwo\plite\Web;



use vertwo\plite\Log;



class WebLog extends Log
{
    const CLOG_ERROR_LOG_CONSTANT = 'error_log';
    const CLOG_FILENAME           = "php.clog";
    const CLOG_FOPEN_MODE         = "a+";
    
    const CLOG_ALT_FILE_DIRS = [
      "/Users/srv/www/log",   // macOS root filesystem is read-only now...so moving to /Users (singular)
      "/Users/srv/www/logs",  // macOS root filesystem is read-only now...so moving to /Users
      "/srv/www/logs",        // Orig dev    (linux only)
      "/var/log/apache2",     // New apache
      "/var/log/apache",      // Old apache
      "/var/log/httpd",       // Alt apache
    ];
    
    /**
     * @var bool|Resource
     */
    private static $logfp = false;
    
    
    protected static function _outputLog ( $mesg )
    {
        self::initFileHandle();
        if ( self::isFileOpen() ) @fwrite(self::$logfp, $mesg . "\n");
        else error_log($mesg);
    }
    
    
    /**
     * This opens a log file statically.
     *
     * DANGER - This has a side effect of setting the static file handler.
     */
    private static function initFileHandle ()
    {
        //if ( self::CLOG_DEBUG_ERROR_LOG_DEFAULT ) error_log("isCLI? " . (isCli() ? "Y" : "n"));
        
        if ( false !== self::$logfp ) return;
        
        $errorLogPath = ini_get(self::CLOG_ERROR_LOG_CONSTANT);
        
        if ( self::CLOG_DEBUG_ERROR_LOG_DEFAULT ) error_log("Log - error-log-path: $errorLogPath");
        
        $errorLogDir = dirname($errorLogPath);
        
        if ( !$errorLogPath || 0 == strlen($errorLogDir) )
        {
            $logdir = pathinfo(realpath("/proc/" . getmypid() . "/fd/2"), PATHINFO_DIRNAME);
            
            if ( self::CLOG_DEBUG_ERROR_LOG_DEFAULT ) error_log("Log - log-dir: $logdir");
            
            if ( !$logdir || 0 == strlen($logdir) )
            {
                self::initAlternateFileHandles();
                return;
            }
            else
            {
                $clogFilePath = $logdir . DIRECTORY_SEPARATOR . self::CLOG_FILENAME;
            }
        }
        else
        {
            $clogFilePath = $errorLogDir . DIRECTORY_SEPARATOR . self::CLOG_FILENAME;
        }
        
        if ( self::CLOG_DEBUG_ERROR_LOG_DEFAULT ) error_log("Trying to open clog file @ $clogFilePath...");
        
        self::$logfp = @fopen($clogFilePath, self::CLOG_FOPEN_MODE);
    }
    
    
    private static function isFileOpen () { return false !== self::$logfp; }
    
    
    private static function initAlternateFileHandles ()
    {
        self::$logfp = false;
        
        foreach ( self::CLOG_ALT_FILE_DIRS as $dir )
        {
            $path = $dir . DIRECTORY_SEPARATOR . self::CLOG_FILENAME;
            
            if ( self::CLOG_DEBUG_ERROR_LOG_DEFAULT ) error_log("Log - Trying to open [ $path ] ...");
            
            $fp = @fopen($path, self::CLOG_FOPEN_MODE);
            if ( false !== $fp )
            {
                if ( self::CLOG_DEBUG_ERROR_LOG_DEFAULT ) error_log("Log - WIN - opened path [ $path ].");
                
                self::$logfp = $fp;
                return;
            }
        }
    }
    
    
    //
    // DANGER - Don't know if initFileHandle() *MUST BE* called here
    //          before constructing the message.  Not sure.
    //
    //public static function log ()
    //{
    //    self::initFileHandle();
    //
    //    $debugPrefix = (!self::DEBUG_TIMING && !self::DEBUG_REMOTE) ? "" : self::makeDebugPrefix();
    //    $argc        = func_num_args();
    //
    //    if ( 2 == $argc )
    //    {
    //        $key    = func_get_arg(0);
    //        $val    = func_get_arg(1);
    //        $prompt = self::cyan($key . ": ");
    //        $prefix = $debugPrefix . $prompt;
    //    }
    //    else
    //    {
    //        $key    = "";
    //        $val    = func_get_arg(0);
    //        $prefix = $debugPrefix;
    //    }
    //
    //    if ( is_scalar($val) )
    //    {
    //        //
    //        // NOTE - Yes, this gets repeated.  Prob better, for perf sake.
    //        //
    //        $val  = self::obfuscatePasswords($key, $val);
    //        $mesg = is_bool($val)
    //          ? ($val ? self::ulgreen("true") : self::ulred("FALSE"))
    //          : (is_numeric($val)
    //            ? self::yellow($val)
    //            : strval($val));
    //        self::_log($prefix . $mesg);
    //    }
    //    else
    //    {
    //        self::logObject($debugPrefix, $key, $val);
    //    }
    //}
}
