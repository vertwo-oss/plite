<?php
/*
 * Copyright (c) 2012-2025 Troy Wu
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



namespace vertwo\plite\Web;



use vertwo\plite\FJ;
use vertwo\plite\Log;
use vertwo\plite\Util\PrecTime;
use function vertwo\plite\clog;
use function vertwo\plite\yel;



class Web
{
    const DEBUG_TIMESTAMP = false;
    const DEBUG_POST      = false;
    const DEBUG_GET       = false;
    const DEBUG_FILES     = true;
    const DEBUG_COOKIES   = true;
    
    const DEBUG_NUKE                  = true;
    const DEBUG_AJAX_RESPONSE         = true;
    const DEBUG_AJAX_RESPONSE_VERBOSE = false;
    const AJAX_RESPONSE_VERBOSE_LEN   = 2048;
    
    const MIME_TEXT_PLAIN       = "text/plain";
    const MIME_APPLICATION_JSON = "application/json";
    
    
    
    public static function cookie () { return session_get_cookie_params(); }
    
    
    
    /**
     * ************************************************************
     *
     * Invalidate and destroy SESSION, including cookies.
     *
     * ************************************************************
     */
    public static function nukeSession ()
    {
        if ( self::DEBUG_NUKE ) clog("nukeSession - ANTE - COOKIES", self::cookie());
        
        session_unset();
        
        // If it's desired to kill the session, also delete the session cookie.
        // Note: This will destroy the session, and not just the session data!
        if ( ini_get("session.use_cookies") )
        {
            $params = session_get_cookie_params();
            setcookie(
              session_name(), '', time() - 42000,
              $params["path"], $params["domain"],
              $params["secure"], $params["httponly"]
            );
        }
        
        session_destroy();
        
        if ( self::DEBUG_NUKE ) clog("nukeSession - POST - COOKIES", self::cookie());
    }
    function nuke () { self::nukeSession(); }
    
    
    public static function isElasticBeanstalkWorkerEnv ()
    {
        $userAgent = array_key_exists("HTTP_USER_AGENT", $_SERVER)
          ? $_SERVER['HTTP_USER_AGENT']
          : false;
        $mesgID    = array_key_exists("HTTP_X_AWS_SQSD_MSGID", $_SERVER)
          ? $_SERVER['HTTP_X_AWS_SQSD_MSGID']
          : false;
        $qID       = array_key_exists("HTTP_X_AWS_SQSD_QUEUE", $_SERVER)
          ? $_SERVER['HTTP_X_AWS_SQSD_QUEUE']
          : false;
        
        if ( false === $userAgent || false === $mesgID || false === $qID ) return false;
        
        $isok = "aws-sqsd/2.4" === $userAgent;
        
        return $isok;
    }
    
    
    protected $hasFiles  = false;
    protected $post      = [];
    protected $get       = [];
    protected $jsonInput = [];
    
    protected $now         = null;
    protected $at          = null;
    protected $success     = false;
    protected $mesg        = null;
    protected $data        = [];
    protected $isLocalhost = false;
    
    protected $apiName      = "?";
    protected $hasResponded = false;
    
    protected $isElasticBeanstalkWorkerEnv = false;
    
    
    /**
     * ################################################################
     * ################################################################
     *
     * NOTE - This is the --==> ENTRY POINT <==-- for the web stuff.
     *
     * Analogous to how CLI is the entry point for command-line stuff,
     * while also providing utility functions and capturing some very
     * basic state (like the command line arguments).
     *
     * Except here we have an object
     *
     * ################################################################
     * ################################################################
     *
     *
     * @param $apiName
     * @param $jsonInput
     *
     * @throws \Exception
     */
    public function __construct ( $apiName = null, $jsonInput = null )
    {
        $server            = strtolower($_SERVER["HTTP_HOST"]);
        $this->isLocalhost = "localhost" === $server || "127.0.0.1" === $server;
        $isProd            = !$this->isLocalhost;
        
        //
        // Enable secure session handling
        //
        // NOTE - Safari broken (see below for excerpt):
        //
        // * https://stackoverflow.com/questions/58525719/safari-not-sending-cookie-even-after-setting-samesite-none-secure
        //
        ini_set('session.cookie_secure', 1); // NOTE - Does not work on Safari.  Figure out localhost workaround.
        ini_set('session.cookie_httponly', 1);
        
        /*
         * The issue is not about Safari sending or not the cookie,
         * it's about Safari not storing the cookie.
         *
         * This is related to a specific combination of cookie config,
         * it's working with this setup for localhost:
         *
         * Set-Cookie: your=cookie;
         *             Domain=localhost;
         *             Path=/;
         *             Expires=Mon, 26 Dec 2022 12:53:02 GMT;
         *             HttpOnly;
         *             SameSite=Lax
         *
         * and this setup for prod:
         *
         * set-cookie: your=cookie;
         *             Domain=something.com;
         *             Path=/;
         *             Expires=Thu, 22 Dec 2022 04:17:44 GMT;
         *             HttpOnly;
         *             Secure;
         *             SameSite=Lax
         *
         * you need to include Domain on both and Secure for your
         * prod (ssl) env. You can use different values for
         * SameSite but Lax is what works for me
         */
        
        $samesite    = 'Lax';
        $maxlifetime = time() +
          180 /* days */ * 24 /* hours/day */ * 60 /* min/hr */ * 60 /* sec/min */
        ;
        
        if ( $isProd )
        {
            //
            // PROD environment
            //
            $secure   = true; // if you only want to receive the cookie over HTTPS
            $httponly = true; // prevent JavaScript access to session cookie
        }
        else
        {
            //
            // DEV (localhost/127.0.0.1) env
            //
            $secure   = false;
            $httponly = true;
        }
        
        //
        // Set cookie params
        //
        if ( PHP_VERSION_ID < 70300 ) // NOTE - Is this really necessary??
        {
            session_set_cookie_params(
              $maxlifetime,
              '/; samesite=' . $samesite,
              $_SERVER['HTTP_HOST'],
              $secure,
              $httponly
            );
        }
        else
        {
            session_set_cookie_params(
              [
                'lifetime' => $maxlifetime,
                'path'     => '/',
                'domain'   => $_SERVER['HTTP_HOST'],
                'secure'   => $secure,
                'httponly' => $httponly,
                'samesite' => $samesite,
              ]
            );
        }
        
        session_start();
        
        clog("_SESSION      (post setting cookie params)", $_SESSION);
        clog("COOKIE params (post setting cookie params)", self::cookie());
        
        WebConfig::load();
        
        $this->now = $_SERVER['REQUEST_TIME'];
        $this->at  = new PrecTime();
        
        if ( self::DEBUG_TIMESTAMP ) clog("Web.ctor()", [
          "at"                       => $this->at,
          "at.getWholeMicros"        => $this->at->getWholeMicros(),
          "at.getWholeMillis"        => $this->at->getWholeMillis(),
          "at.getFractionalMillis"   => $this->at->getFractionalMillis(),
          "at.getWholeSeconds"       => $this->at->getWholeSeconds(),
          "at.getFractionalSeconds"  => $this->at->getFractionalSeconds(),
          "now: server-request-time" => $this->now,
        ]);
        
        if ( self::DEBUG_COOKIES ) clog("Cookies", self::cookie());
        
        $this->hasFiles = isset($_FILES) && (0 < count($_FILES));
        
        if ( isset($apiName) )
            $this->apiName = $apiName;
        
        if ( isset($jsonInput) )
            foreach ( $jsonInput as $jk => $jv )
            {
                $this->jsonInput[$jk] = $jv;
            }
        
        
        if ( self::DEBUG_POST ) clog("Web.ctor/_POST", $_POST);
        if ( self::DEBUG_GET ) clog("Web.ctor/_GET", $_GET);
        if ( self::DEBUG_FILES ) clog("Web.ctor/_FILES", $_FILES);
        
        if ( isset($_POST) )
            $this->post = FJ::deepCopy($_POST);
        
        if ( isset($_GET) )
            $this->get = FJ::deepCopy($_GET);
        
        $this->apiName = $_SERVER["SCRIPT_NAME"];
        
        $this->isElasticBeanstalkWorkerEnv = self::isElasticBeanstalkWorkerEnv();
    }
    
    
    
    public function dump ( $mesg = false )
    {
        $mesg = false === $mesg ? "" : "$mesg ";
        
        clog($mesg . "[Web/POST]", $_POST);
        clog($mesg . "[Web/GET ]", $_GET);
    }
    
    
    
    public function isLocalhost () { return $this->isLocalhost; }
    public function isProd () { return !$this->isLocalhost(); }
    public function isAWSWorkerEnv () { return $this->isElasticBeanstalkWorkerEnv; }
    
    
    
    /**
     * Clears status of response.
     */
    public function clear ()
    {
        $this->data    = [];
        $this->success = false;
        $this->mesg    = null;
    }
    
    
    
    /**
     * Signal that request failed.  Outputs error.
     *
     * @param $mesg - Message to output & deliver.
     *
     * @return Web - For chaining.
     */
    public function fail ( $mesg )
    {
        Log::error($mesg);
        $this->success = false;
        $this->mesg    = $mesg;
        return $this;
    }
    
    
    
    const HTTP_OK                        = 200;
    const HTTP_ERROR_GENERIC             = 500;
    const HTTP_ERROR_NOT_IMPLEMENTED     = 501;
    const HTTP_ERROR_SERVICE_UNAVAILABLE = 503;
    
    
    
    public function http ( $status, $mesg = false )
    {
        clog("HTTP/1.1 $status (ignoring following mesg)", $mesg);
        http_response_code($status);
        
        flush();
        ob_flush();
        exit();
    }
    
    
    
    public function resp ( $code = 200, $mesg = false )
    {
        if ( false === $mesg || null === $mesg )
        {
            switch ( $code )
            {
                case 100:
                    $mesg = 'Continue';
                    break;
                case 101:
                    $mesg = 'Switching Protocols';
                    break;
                case 200:
                    $mesg = 'OK';
                    break;
                case 201:
                    $mesg = 'Created';
                    break;
                case 202:
                    $mesg = 'Accepted';
                    break;
                case 203:
                    $mesg = 'Non-Authoritative Information';
                    break;
                case 204:
                    $mesg = 'No Content';
                    break;
                case 205:
                    $mesg = 'Reset Content';
                    break;
                case 206:
                    $mesg = 'Partial Content';
                    break;
                case 300:
                    $mesg = 'Multiple Choices';
                    break;
                case 301:
                    $mesg = 'Moved Permanently';
                    break;
                case 302:
                    $mesg = 'Moved Temporarily';
                    break;
                case 303:
                    $mesg = 'See Other';
                    break;
                case 304:
                    $mesg = 'Not Modified';
                    break;
                case 305:
                    $mesg = 'Use Proxy';
                    break;
                case 400:
                    $mesg = 'Bad Request';
                    break;
                case 401:
                    $mesg = 'Unauthorized';
                    break;
                case 402:
                    $mesg = 'Payment Required';
                    break;
                case 403:
                    $mesg = 'Forbidden';
                    break;
                case 404:
                    $mesg = 'Not Found';
                    break;
                case 405:
                    $mesg = 'Method Not Allowed';
                    break;
                case 406:
                    $mesg = 'Not Acceptable';
                    break;
                case 407:
                    $mesg = 'Proxy Authentication Required';
                    break;
                case 408:
                    $mesg = 'Request Time-out';
                    break;
                case 409:
                    $mesg = 'Conflict';
                    break;
                case 410:
                    $mesg = 'Gone';
                    break;
                case 411:
                    $mesg = 'Length Required';
                    break;
                case 412:
                    $mesg = 'Precondition Failed';
                    break;
                case 413:
                    $mesg = 'Request Entity Too Large';
                    break;
                case 414:
                    $mesg = 'Request-URI Too Large';
                    break;
                case 415:
                    $mesg = 'Unsupported Media Type';
                    break;
                case 500:
                    $mesg = 'Internal Server Error';
                    break;
                case 501:
                    $mesg = 'Not Implemented';
                    break;
                case 502:
                    $mesg = 'Bad Gateway';
                    break;
                case 503:
                    $mesg = 'Service Unavailable';
                    break;
                case 504:
                    $mesg = 'Gateway Time-out';
                    break;
                case 505:
                    $mesg = 'HTTP Version not supported';
                    break;
                default:
                    Log::error('Unknown http status code "' . htmlentities($code) . '"');
                    break;
            }
        }
        
        $protocol = (isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0');
        
        header($protocol . ' ' . $code . ' ' . $mesg);
        
        $GLOBALS['http_response_code'] = $code;
        
    }
    public function ok ( $mesg = false ) { $this->resp(200, $mesg); }
    public function bad ( $mesg = false ) { $this->resp(500, $mesg); }
    
    
    
    
    /**
     * Gets information for a specific file-to-upload.
     *
     * @param string $fileKey - Specific file-to-upload - should be an entry from files().
     *
     * @return bool|array - Assoc array of file info, if it exists; (false) otherwise.
     */
    public function fileInfo ( $fileKey ) { return ($this->hasFiles && isset($_FILES[$fileKey])) ? $_FILES[$fileKey] : false; }
    
    
    
    /**
     * Gets files-to-upload, if any.
     *
     * @return array|bool - Array of files-to-upload; (false) otherwise).
     */
    public function files () { return $this->hasFiles() ? array_keys($_FILES) : false; }
    
    
    
    /**
     * Determines if GET contains specified key.
     *
     * @param string $key - Specified key.
     *
     * @return bool - (true) if GET contains key; (false) otherwise.
     */
    protected function hasGet ( $key ) { return array_key_exists($key, $_GET); }
    
    
    
    /**
     * Determines if POST contains files-to-upload.
     *
     * @return bool - (true) if POST contains files-to-upload; (false) otherwise;
     */
    public function hasFiles () { return $this->hasFiles; }
    
    
    
    /**
     * Determines if POST contains specified key.
     *
     * @param string $key - Specified key.
     *
     * @return bool - (true) if POST contains key; (false) otherwise.
     */
    protected function hasPost ( $key ) { return array_key_exists($key, $_POST); }
    
    
    
    /**
     * Detects if script is called via HTTPS.
     *
     * @return bool - (true) if HTTPS; (false) otherwise.
     */
    public function isHTTPS () { return isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']; }
    
    
    
    ///**
    // * Detects if script is called via 'localhost' server name.
    // *
    // * @return bool - (true) if 'localhost'; (false) otherwise.
    // */
    //public function isLocalhost () { return isset($_SERVER['SERVER_NAME']) && ("localhost" === $_SERVER['SERVER_NAME']); }
    
    
    
    public function getConnectionID ( $info = false )
    {
        $tuple = $_SERVER['REMOTE_ADDR'] . "_" . $_SERVER['REMOTE_PORT'];
        
        if ( $this->isHTTPS() )
        {
            $tlsver = $_SERVER['SSL_PROTOCOL'];
            $tuple  = $tlsver . "_" . $tuple;
        }
        else
        {
            $tuple = "http_" . $tuple;
        }
        
        $shouldUseInfo = (false !== $info) && isset($info) && (0 < strlen($info));
        $infoString    = ($shouldUseInfo ? $info : "NO-AD-INFO");
        
        $id = $this->at->getWholeMicros() . "_" . $infoString . "_" . $tuple;
        return $id;
    }
    
    
    
    /**
     * Converts the response data into an AJAX JSON response.
     *
     * @return string
     */
    public function json ()
    {
        $resp = [];
        
        $resp['now']     = $this->at->getWholeMillis();
        $resp['success'] = $this->success;
        if ( isset($this->mesg) )
            $resp['mesg'] = $this->mesg;
        
        if ( $this->success )
        {
            if ( isset($this->data) )
                $resp['data'] = $this->data;
            else
                $resp['data'] = [];
        }
        else
        {
            $resp['error'] = $this->mesg;
        }
        
        $json = FJ::jsEncode($resp);
        
        if ( DEBUG_AJAX_RESPONSE ) clog("Web.json/output", $json);
        
        return $json;
    }
    
    
    
    /**
     * Gets the current, high-precision, time.
     *
     * @return PrecTime|null
     */
    public function now () { return $this->at; }
    
    
    
    /**
     * Send the AJAX response (JSON-encoded), and EXIT script (unrolling callers).
     *
     * NOTE - Aborts callers!
     *
     * @param string $contentType - MIME Type
     * @param null   $content
     */
    public function respond ( $contentType = self::MIME_APPLICATION_JSON, $content = null )
    {
        $response = (null == $content) ? $this->json() : $content;
        $this->raw($contentType, $response);
    }
    
    
    
    /**
     * @param string $message
     * @param string $mimeType
     * @param null   $content
     */
    public function fastfail ( $message, $mimeType = "application/json", $content = null )
    {
        $this->fail($message);
        $this->respond($mimeType, $content);
    }
    
    
    
    /**
     * Send the AJAX response (JSON-encoded), closes the client connection, but keeps running.
     *
     * DANGER - DOES NOT STOP processing.
     *
     * @return int - Length of output string (not including headers).
     */
    public function respondAndContinue ()
    {
        if ( !$this->hasResponded )
        {
            $this->hasResponded = true;
            $response           = $this->json();
            $length             = strlen($response);
            
            header('Content-Type: application/json');
            header('Connection: close');
            header("Content-Length: $length");
            echo $response;
            
            flush();
            
            return $length;
        }
        else
        {
            return 0;
        }
    }
    
    
    
    /**
     * Set a return value in the 'data' hash.
     *
     * @param mixed $key   - Key
     * @param mixed $value - Value
     *
     * @return - Ajax object for builder pattern.
     */
    public function set ( $key, $value )
    {
        $this->data[strval($key)] = $value;
        return $this;
    }
    
    
    
    public function getAll () { return array_merge($_GET, $_POST); }
    public function getAllAsJSON () { return FJ::js(array_merge($_GET, $_POST)); }
    
    
    
    /**
     * Sets a value in the $_SESSION.
     *
     * @param string $key   - Key to use
     * @param string $value - Value to set
     */
    public function setSession ( $key, $value ) { $_SESSION[$key] = $value; }
    
    
    
    public function hasSession ( $key ) { return array_key_exists($key, $_SESSION); }
    
    
    
    public function getSession ( $key ) { return array_key_exists($key, $_SESSION) ? $_SESSION[$key] : false; }
    
    
    
    public function clearSession ( $key ) { if ( array_key_exists($key, $_SESSION) ) unset($_SESSION[$key]); }
    
    
    
    /**
     * Sets the number of seconds this script is allowed to run.
     *
     * @param $seconds
     */
    public function setSessionTimeout ( $seconds ) { set_time_limit($seconds); }
    
    
    
    /**
     * Fail-fast test-and-get of an HTTP GET key.
     *
     * NOTE - Aborts callers if specified key does not exist!
     *
     * @param mixed $key
     *
     * @return string value if 'key' exists; EXIT script otherwise (unrolling callers).
     */
    public function testAbortGet ( $key )
    {
        $val = $this->testGet($key);
        if ( false === $val )
        {
            clog("Web/{$this->apiName} - GET key [ $key ] unspecified; exiting");
            $this->fail("GET key [ $key ] unspecified");
            $this->respond();
        }
        return $val;
    }
    
    
    
    /**
     * Fail-fast test-and-get of an HTTP POST or GET key.
     *
     * NOTE - Aborts callers if specified key does not exist!
     *
     * @param string $key
     *
     * @return string|array value of 'key' if it exists; EXIT script otherwise (literal script exit).
     */
    public function testAbortBoth ( $key )
    {
        $val = $this->testPost($key);
        if ( false === $val )
        {
            $val = $this->testGet($key);
            
            if ( false === $val )
            {
                clog("Web/{$this->apiName} - POST/GET key [ $key ] unspecified; exiting");
                $this->fail("POST/GET key [ $key ] unspecified");
                $this->respond();
            }
        }
        
        return $val;
    }
    
    
    
    /**
     * Fail-fast test-and-get of an HTTP POST key.
     *
     * NOTE - Aborts callers if specified key does not exist!
     *
     * @param string $key
     *
     * @return string value of 'key' if it exists; EXIT script otherwise (literal script exit).
     */
    public function testAbortPost ( $key )
    {
        $val = $this->testPost($key);
        if ( false === $val )
        {
            clog("Web/{$this->apiName} - POST key [ $key ] unspecified; exiting");
            $this->fail("POST key [ $key ] unspecified");
            $this->respond();
        }
        return $val;
    }
    
    
    
    /**
     * Fail-fast test-and-get of a PHP SESSION key.
     *
     * NOTE - Aborts callers if specified key does not exist!
     *
     * @param string $key
     *
     * @return string value of 'key' if it exists; EXIT script otherwise (literal script exit).
     */
    public function testAbortSession ( $key )
    {
        $val = $this->testSession($key);
        if ( false === $val )
        {
            clog("Web/{$this->apiName} - SESSION key [ $key ] unspecified; exiting");
            $this->fail("SESSION key [ $key ] unspecified");
            $this->respond();
        }
        return $val;
    }
    
    
    
    /**
     * Test-and-get of an HTTP POST or GET key.
     *
     * @param string $key
     *
     * @return string|bool - value of 'key' exists; otherwise, (false)
     */
    public function testBoth ( $key )
    {
        if ( $this->hasPost($key) ) return $_POST[$key];
        if ( $this->hasGet($key) ) return $_GET[$key];
        return false;
    }
    
    
    
    /**
     * Test-and-get of an HTTP GET key.
     *
     * @param string $key
     *
     * @return string|array - String value if 'key' exists; otherwise, (false)
     */
    public function testGet ( $key ) { return $this->hasGet($key) ? $this->get[$key] : false; }
    
    
    
    /**
     * Test-and-get of an HTTP POST key.
     *
     * @param string $key
     *
     * @return String value if 'key' exists; otherwise, (false)
     */
    public function testPost ( $key ) { return $this->hasPost($key) ? $this->post[$key] : false; }
    
    
    
    /**
     * Gets a value in the $_SESSION.
     *
     * @param string $key - Key to use
     *
     * @return mixed
     */
    public function testSession ( $key ) { return isset($_SESSION[$key]) ? $_SESSION[$key] : false; }
    
    
    
    /**
     * Indicate AJAX call is successful (from the server's POV).
     *
     * @param string $mesg - Optional message
     *
     * @return Web - For chaining.
     */
    public function win ( $mesg = null )
    {
        $this->success = true;
        $this->mesg    = $mesg;
        return $this;
    }
    
    
    
    /**
     * Dumps file through HTTP.
     *
     * @param string $filename
     * @param string $content
     */
    public function textFile ( $filename, $content )
    {
        $content = trim($content) . "\n";
        $this->file($filename, $content);
    }
    
    
    
    /**
     * Dumps file through HTTP.
     *
     * @param string $filename
     * @param string $content
     */
    public function file ( $filename, $content )
    {
        $len = strlen($content);
        
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename=' . $filename);
        header('Content-Transfer-Encoding: binary');
        header('Expires: 0');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Pragma: public');
        header('Content-Length: ' . $len);
        
        echo $content;
        
        flush();
        ob_flush();
        
        exit(); // NOTE - Exit PHP - Immediately terminate execution
    }
    
    
    
    public function raw ( $contentType, $content )
    {
        if ( !$this->hasResponded )
        {
            $this->hasResponded = true;
            
            $contenTypeString = 'Content-Type: ' . $contentType;
            
            header($contenTypeString);
            
            clog("Content-Type (string)", $contenTypeString);
            
            if ( self::DEBUG_AJAX_RESPONSE )
            {
                $displayMesg = self::DEBUG_AJAX_RESPONSE_VERBOSE
                  ? $content
                  : FJ::clipString($content, self::AJAX_RESPONSE_VERBOSE_LEN);
                
                clog("AJAX response", $displayMesg);
            }
            
            echo "$content" . "\n";
            flush();
            ob_flush();
        }
        
        exit(); // NOTE - Exit PHP - Immediately terminate execution
    }
}
