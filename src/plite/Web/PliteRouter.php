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



use Exception;
use vertwo\plite\Config;
use vertwo\plite\FJ;
use vertwo\plite\Log;
use function vertwo\plite\clog;
use function vertwo\plite\cynlog;
use function vertwo\plite\grnlog;
use function vertwo\plite\redulog;
use function vertwo\plite\yellog;



/**
 * Routing class, extending RoutedAjax.  Basically, all requests show up here.
 *
 * Expects a .htaccess file like this:
 *
 * #----
 * RewriteEngine on
 * RewriteCond %{REQUEST_FILENAME} !-f
 * RewriteRule ^(.*)$ route.php?url=$1  [L,QSA]
 * #----
 *
 * Class RoutedAjax
 *
 * @package vertwo\plite\Web
 */
abstract class PliteRouter extends Ajax
{
    const DEBUG = false;

    const CONFIG_KEY_ROUTING_ROOT = "plite_routing_root";
    const DEFAULT_INPUT_MAXLEN    = 256;

    protected $uri;
    protected $path;
    protected $clean;
    protected $query;

    private $routingRoot;



    /**
     * Expects web server to have 'vertwo_class_prefix' as an
     * environment variable available to PHP via $_SERVER.
     *
     * Then, uses that value to instantiate the relevant
     * Router subclass.
     *
     * @return Ajax
     * @throws Exception
     */
    public static function newInstance ()
    {
        $routerClass = Config::get("plite_router");
        $router      = Config::loadClass($routerClass);

        if ( !$router instanceof PliteRouter )
            throw new Exception("Specified class [ " . $routerClass . " ] does not implement PliteRouter.");

        return $router;
    }



    /**
     * Subclass returns string to represent app (or other context).
     *
     * Completely arbitrary, meant to facility grep & CLI tools.
     *
     * @return string
     */
    public abstract function getCustomLoggingPrefix ();



    /**
     * Subclass implements to handle HTTP request.
     *
     * @return mixed
     */
    public abstract function handleRequest ();



//    /**
//     * Subclass implements to handle HTTP request.
//     *
//     * @param string $whole - The entire URI.
//     * @param string $page  - The "page" (after first /, before second /).
//     *
//     * @return mixed
//     */
//    public abstract function handleRequest ( $whole, $page );



    static function cleanInput ( $method, $size = self::DEFAULT_INPUT_MAXLEN )
    {
        $m = FJ::stripNon7BitCleanASCII(FJ::stripSpaces(trim($method)));
        $m = substr(trim($m), 0, $size);
        $m = strtolower($m);

        return $m;
    }



    /**
     * RoutedAjax constructor.
     *
     * @throws Exception
     */
    function __construct ()
    {
        parent::__construct();

        $isWorkerEnv = $this->isAWSWorkerEnv();
        $env         = $isWorkerEnv ? "SQS" : "Web";

        Log::setCustomPrefix("[$env] " . $this->getCustomLoggingPrefix());

        $this->uri = $_SERVER['REQUEST_URI'];

        clog("---- URI: [ " . $this->uri . " ] ----");

        $uriTokens   = explode('?', $this->uri, 2);
        $this->path  = $uriTokens[0];
        $this->query = 2 == count($uriTokens) ? $uriTokens[1] : "";

        //
        // Remove "plite_routing_root" prefix from URI (usually in local test env).
        //
        $this->routingRoot = Config::has(self::CONFIG_KEY_ROUTING_ROOT)
            ? Config::get(self::CONFIG_KEY_ROUTING_ROOT)
            : "";

        $this->clean = $this->getRequestWithoutPrefix($this->routingRoot);

        clog("---- CLEAN: [ " . $this->clean . " ] ----");

        if ( self::DEBUG ) clog("routing root", $this->routingRoot);
        if ( self::DEBUG ) clog("cleaned URI", $this->clean);


        //
        // NOTE - Test if this is being routed by the PROPER .htaccess rewrite...
        //
        $rewrite  = $this->testBoth("rewrite");
        $isRouted = false !== $rewrite;

        clog("Is routed by .htaccess", $isRouted);

//        if ( $isRouted )
//        {
//            clog("this->uri", $this->uri);
//            clog("this->clean", $this->clean);
//            clog("this->path", $this->path);
//            clog("this->query", $this->query);
//        }
//        else
//        {
//            $uriTokens = explode('?', $this->uri, 2);
//            $cleanUri  = $this->getRequestWithoutPrefix($this->routingRoot);
//
//            if ( self::DEBUG ) clog("routing root", $this->routingRoot);
//            if ( self::DEBUG ) clog("actual request", $cleanUri);
//
//            $pathTokens = explode("/", $cleanUri, 3);
//
//            if ( self::DEBUG ) clog("path tokens", $pathTokens);
//
//            $this->page = 2 == count($pathTokens) ? $pathTokens[1] : "";
//            $this->path = 3 == count($pathTokens) ? $pathTokens[2] : "";
//        }
//
//        $this->page = self::cleanInput($this->page);

        if ( self::DEBUG ) clog("uri", $this->uri);
        if ( self::DEBUG ) clog("clean", $this->clean);
        if ( self::DEBUG ) clog("path", $this->path);
        if ( self::DEBUG ) clog("query", $this->query);
    }



    /**
     * Called as the "first" thing to happen, before headers & 'main' processing.
     */
    function initSession ()
    {
        if ( PHP_SESSION_NONE === session_status() )
        {
            session_start();
            if ( self::DEBUG ) grnlog("----====[ Session STARTED ]====----");
        }
        else
        {
            if ( self::DEBUG ) yellog("----====[ Session resuming ]====----");
        }
    }



    /**
     * Called after the session init, but before 'main' processing.
     *
     * Override to disable default-handling; a noop is fine.
     */
    function initCacheHeaders ()
    {
        if ( self::DEBUG ) cynlog("----====[ Not touching cache ]====----");
    }



    function sendHeadersToDisableCache ()
    {
        if ( self::DEBUG ) cynlog("----====[ Disabling Cache ]====----");
        header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date in the past
        header("Expires: 0"); // Date in the past
        header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
        header("Cache-Control: no-store, no-cache, must-revalidate, no-transform, max-age=0"); //HTTP 1.1
        header("Cache-Control: post-check=0, pre-check=0"); //HTTP 1.1
        header("Pragma: no-cache"); //HTTP 1.0
    }



    function getRequestWithoutPrefix ( $prefix )
    {
        if ( false === $prefix || null === $prefix ) return "";

        if ( FJ::startsWith($prefix, $this->uri) )
        {
            return substr($this->uri, strlen($prefix));
        }
        else
        {
            return "";
        }
    }



    function abortIfNotRouted ( $abortPage )
    {
        if ( FJ::endsWith(".php", $this->clean) || FJ::endsWith(".html", $this->clean) )
        {
            redulog("NOT-ROUTED: [ " . $this->page . " ]; aborting.");
            header("Location: $abortPage");
            exit(1);
        }
    }



    /**
     *
     * MAIN entry point!
     *
     */
    final public function main () { $this->route(); }
    final public function route ()
    {
        $this->initSession();
        $this->initCacheHeaders();
        $this->handleRequest(); // $this->uri, $this->page);
        exit(0);
    }
}
