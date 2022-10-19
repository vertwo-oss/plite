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



use vertwo\plite\Config;
use vertwo\plite\ConfigInterface;
use vertwo\plite\Web\PliteRouter;
use vertwo\plite\Web\PliteTemplate;
use function vertwo\plite\clog;
use function vertwo\plite\cynlog;
use function vertwo\plite\redulog;



require_once(__DIR__ . "/../../vendor/autoload.php");

define("DEBUG_ROUTE_START", false);



class PliteExampleRouter extends PliteRouter
{
    private static function outputHelloWorld ()
    {
        echo <<<EOF
<html lang="en">
<head>
    <title>Plite Example</title>
    <style>
        html {
            background-color: #555;
            font-family: "Helvetica Neue", Helvetica, Arial, sans-serif;
        }
    </style>
</head>
<body>
    <h1>Hello, world!</h1>
</body>
</html>
EOF;
    }



    /**
     * Subclass returns string to represent app (or other context).
     *
     * Completely arbitrary, meant to facility grep & CLI tools.
     *
     * @return string
     */
    public function getCustomLoggingPrefix () { return "plite-test"; }



    /**
     * Subclass implements to handle HTTP request.
     *
     * @return mixed
     */
    public function handleRequest ()
    {
        clog("  uri", $this->uri);
        clog(" path", $this->path);
        clog("clean", $this->clean);
        clog("query", $this->query);

        $this->outputHelloWorld();

        return true;
    }
}



//
// NOTE - Expects a .htaccess file like this:
//   #----
//   RewriteEngine on
//   RewriteCond %{REQUEST_FILENAME} !-f
//   RewriteRule ^(.*)$ route.php?url=$1  [L,QSA]
//   #----
//
// NOTE - Also expects the use of a PliteRouter.
//
if ( DEBUG_ROUTE_START ) cynlog("--------========[ ROUTING starting ]========--------");


try
{
    Config::init();
    PliteTemplate::init();
    $router = new PliteExampleRouter();
}
catch ( Exception $e )
{
    clog($e);
    redulog("Could not instantiate ROUTER class; redirecting to error page.");
    header("error.php");
    exit(99);
}
$router->abortIfNotRouted("logout");
$router->route();
