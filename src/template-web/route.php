<?php
/**
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



use vertwo\plite\Config;
use vertwo\plite\Web\PliteRouter;
use vertwo\plite\Web\WebLog;



require_once(__DIR__ . "/vendor/autoload.php");

const DEBUG_ROUTE_START = false;


//
// NOTE - Expects a .htaccess file like this:
//   #----
//   RewriteEngine on
//   RewriteRule ^(.*)$ route.php?url=$1  [L,QSA]
//   #----
//
// NOTE - Also expects the use of a RoutedAjax.
//
if ( DEBUG_ROUTE_START ) clog(cyn("--------========[ ROUTING starting ]========--------"));


try
{
    Config::init();
    $router = PliteRouter::newInstance();
}
catch ( Exception $e )
{
    clog($e);
    clog(red("Could not instantiate ROUTER class; redirecting to error page."));
    header("error.php");
    exit(99);
}
$router->abortIfNotRouted("logout");
$router->main();
