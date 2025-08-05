# plite

PHP Lite Framework

Adds some basic libraries for working with console-viewed logging, PHP as a CLI
tool, handling basic server-side web API requests, a very small Postgres
abstraction, as well as a small framework for abstracting a few AWS services (
S3, SecretsManager) and Twilio/Sinch/Plivo. Also comes with tools to
help with ETL.

## TL;DR

At its core, `plite` has 2 parts:

1. A CLI framework to make command-line PHP easier.
2. A Web framework to make it easy to have both a dev and prod (especially a
   system like AWS ElasticBeanstalk) simpler, and to allow both local- and
   cloud-based dev.
3. An abstraction framework for AWS S3, SecretsManager, SES, etc, to build
   command-line tools.

It does this by providing local analogues to S3 and SecretsManager and SES which
allows for local dev while offline. S3 is pretty easy to mimic with the
filesystem (especially if we're just talking about BLUB CRUD), and
SecretsManager is just a service that serves JSON BLOBs.

`plite` also adds a bunch of utility functions which I've found useful over the
years, including debugging/logging output utility functions and time-handling
functions, all of which are properly namespaced.

**BUT, one thing to note: it clogs the global namespace with a function called
`clog`.**

If you hate it, extends `BoringCLI`, which does not call `clog()`, and you
can avoid it and its overhead by doing `echo` or `printf` or `error_log()`
or whatever yourself. See below. It doesn't remove the pollution, but you can
avoid the overhead.

Why do I do this, by default? Because:

1. I'm opinionated AF, so the library is opinionated AF.
2. Every language needs a good pretty printer in the global scope.

`plite` forces this symbol to be named `clog`. This was actually the first
thing I built, before it grew into all this other stuff. There are other global
level functions: functions to color strings using ANSI escape sequences, and
two functions `isCLI()` and `isWeb()`.

## As a Command Line "framework"

If you are using this to create a command-line script (e.g., executable with a
shebang), then your starting point is the class `vertwo\plite\CommandLine\CLI`.

Just extend the `CLI` class (or `BoringCLI`), and implement two methods:

* `main()`
* `getShortOpts()`

and add a third method, if you want to work with long options (.e.g.,
`--verbose`):

* `getLongOpts()`

The latter two methods are for unix-style options-handling can return empty
string and an empty array. So, for a hello-world, just this will do:

```php
class HelloWorld extends CLI
{
    protected function getShortOpts () { return ""; }

    public function main () {    // This gets called by the framework...
        echo "Hello, world!\n";  //
        return 0;                //
    }                            //
}                                //
                                 //
HelloWorld::run();               // ...here, by CLI::run().
```

And, if you add a shebang, a PHP tag, a `use` statement, and a `require`
statement, and then make the script executable, you'll be able to just execute
this php file on the CLI (and, obviously, making sure to chmod +x or whatever
your OS needs to make things runnable).

Now, obviously, that's a LOT of lines of code for a file that could have just
been this (12 lines vs 1):

```php
#! /usr/bin/php
<?php
echo "Hello, world!\n";
```

So, why put it up with it?

Well, just 1 reason: options-handling.

In order for PHP to function well in a unix-y shell-environment, scripts often
nee to be run with different arguments to have slightly different behavior.

Suppose our PHP script is named `hello`.

Let's say we want to add a *verbose* switch to our program, so that we can call
it like this:

`$ hello -v`

Then, we just change one line:

```php
    protected function getShortOpts () { return "v"; }
```

And we can use it like this:

```php
    public function main () {
        if ( $this->hasopt("v") ) echo "About to print string!\n";
        echo "Hello, world!\n";
        return 0;
    }
```

This makes CLI PHP much more effective.

And suppose we wanted a long option: `--name your_name`

Then, we change the implementation of `getLongOpts()` like this:

```php
    protected function getLongOpts ()
    {
        return [
            self::req("name"),
        ];
    }
```

and use it like this:

```php
    public function main () {
        if ( $this->hasopt("v") ) echo "About to print string!";

        if ( $this->hasopt("name") ) echo "Hello, world, " . $this->getopt("name") . "!\n";
        else echo "Hello, world!\n";

        return 0;
    }
```

Beautiful.

Here's the final, (potentially) executable script:

```php
#! /usr/bin/php
<?php
use vertwo\plite\CLI;
require_once __DIR__ . "/../vendor/autoload.php";

class HelloWorld extends CLI
{
    protected function getShortOpts () { return "v"; }
    protected function getLongOpts () { return [ self::REQ("name") ]; }

    public function main ()
    {
        if ( $this->hasopt("v") ) echo "About to print string!\n";

        if ( $this->hasopt("name") )
            echo "Hello, world, " . $this->getopt("name") . "!\n";
        else
            echo "Hello, world!\n";
        return 0;
    }
}

HelloWorld::run();
```

### The beauty & the beast:`clog()`

I get that some of you won't like the global namespace to be polluted by
even one more symbol. So, I've created a class called `BoringCLI` that
doesn't call the `clog()` function. And, if you're making
"performance-sensitive" CLI stuff, this is your class.

I like a pretty printer, especially one in the global namespace. I tried to
choose something that wasn't going to get in the way.  Maybe if I cared 
enough to play around with namespaces, etc, but my whole library uses, and 
all my code calls it, so it seems SUPER-onerous to go back and add `use` 
statements.  I should probably do this.  IDC right now.

### Conclusion

Sure, the original is 12 lines (10 real lines) vs 1 line. That's a loss.

But, the version which recognizes a short CLI (`-v`) option and a long CLI
option (`--name`) is 21 lines (17 real lines) vs...IDK...dozens of lines to
use `getopt()` to parse command line options is a big savings. Plus, we get
other benefits, which we'll see below (in the output).

Plus, I can remember how to specify a `getopt()` string (or construct an
array of strings) but never remember how to call `getopt()`. That's the
benefit.

## As a Web Template...Framework

At its core, the Web Framework is a way to let you do local, offline dev, if
you're using AWS. It provides wrappers for S3 and SecretsManager
and SES (AWS Simple Email Service), and it lets you configure BOTH local- and
cloud-based endpoints (called "Providers"), and is simple as a writing a PHP
class which defines a map (i.e., `[]`) of properties.

The S3 abstraction layer uses your local filesystem when S3 isn't available
(or when you want to test without blowing up your bucket).

The SecretsManager abstraction layer uses your local filesystem to provide
"secrets", which are just JSON blobs (when you're not ready to push your
secrets to the cloud).

The SES abstraction allows you to send mail (and it can also connect to any 
SMTP server, per your designation).  SES is a bitch to setup, though, and I 
wouldn't touch it with a 10-foot pole.  It's another one of these: "Even 
though I'm  paying, because SMTP is so often abused to send spam, Amazon 
must "vet" your application before allowing you to use SES.  I hate it.  I'd 
rather setup a host that's running postfix on EC2 than deal with SES.

There are several entry points for using this library, depending on the intended
usage.

### Use Case: Vanilla PHP + some convenience.

In the early days of `plite`, there used to be the case that there was a
simple class called `Ajax` in `plite` that would help you generate pages by
just providing some convenience functions in a class.

In other words, you would use "vanilla" PHP routing; URLs would simply point to
PHP pages, and "paths" in the URL reflected the layout of files in the
application directory. This would be affected by things like `DocumentRoot`
and `VirtualHost`, at least in Apache HTTPD parlance.

Then, you just the `Ajax` class to help you with stuff (like pulling files 
out of POSTs, or seeing request times, etc).

And, you can still use `plite` this way.

### Use Case: Programmatic Routing.

Sure.  `plite` supports arbitrary, programmatic routing, too. To do that,  
you have to do a bunch of crap:

1. Use something like a `.htaccess` file, to push routing to a custom page.
2. Use the `route.php` in the `web-template` directory.
3. Subclass the `PliteRouter` class...
4. ...but for it to work, you have to control **Environment Variables** on
   the server.

*(I could rethink this. Maybe do something like look for a specially-named
class, instead of this machinery of finding the proper subclass through the
use of Web Server env variables. But, it is what it is right now.)*

The `.htaccess` file looks like this:

```apache
RewriteEngine on
RewriteRule ^(.*)$ route.php?url=$1  [L,QSA]
```

And the `route.php` (in the root of the project directory) looks like this:

```php
<?php
use vertwo\plite\Config;
use vertwo\plite\Web\PliteRouter;

require_once(__DIR__ . "/vendor/autoload.php");

try
{
    Config::init();
    $router = PliteRouter::newInstance();
}
catch ( Exception $e )
{
    header("error.php");
    exit(99);
}
$router->abortIfNotRouted("logout");
$router->main();
```

And the final piece is the monstrosity of subclassing `PliteRouter`.  
There's only one main method to implement, which is `handleRequest()`, and
there is an "identifier" method called `getCustomLoggingPrefix()`, which
is...just too long a method for: "Put this 'prefix' string in front of the
log entries in the log."

*[This is one of those cases where I'm doing some Uncle Bob shit to make the
method name "self-documenting", but it isn't, so I'm getting the worst of
all worlds; a relatively long method name, but it's not long enough to
self-document, but is so long that it obscures how simple it is.]*

But, the subclass could be as simple as this (removing comments, etc):

```php
class MyAppRouter extends PliteRouter
{
    public function getCustomLoggingPrefix () { return "MyApp"; }

    public function handleRequest ( $whole, $page )
    {
        switch ( $page )
        {
            case "logout":
                $this->nuke();               // Provided by PliteRouter
                $this->goPage("login");
                break;
            case "login":
                $this->goLogin();
                break;
            case "auth":
                $this->apiAuth();
                break;
            case "":
            case "dashboard":
                $this->abortIfNotAuthenticated("logout");
                $this->goMain();
                break;

            default:
                exit(47); // Should we also display a custom 404 message?
                break;
        }

        return 0;
    }
    
    private function goPage ( $page )
    {
        header("Location: $page");
        exit(0);
    }
    
    //...
}
```

Now, `goPage()`, `goLogin()`, `apiAuth()`, and `goMain()` are just
implementation details in this class. I've just shown one, to give the idea.
The point is, you do whatever you need to do in the `case`s of the `switch`,
if you want to do it that way, or just doing whatever you need to do in
`handleRequest()`, if you want to go beyond just matching some basic part of
the URL.

But,

But, if you want to have programmatic routing so that your URLs "look
better", or you're looking to manage REST-like services and not
have to have a complex filesystem hierarchy just to serve API calls

When using Plite as a web framework,

> There is an **ABSOLUTE REQUIREMENT** that DevOps can control Web
> Server environment variables (e.g., SetEnv on apache).

> Additionally, the app name, used throughout the framework, must be a
> string which matches `[:alnum:]-_]+`.

It uses the web server environment variables to provide two mechanisms
to bootstrap a configuration:

1. **Prod** enviroment: config is loaded from **a PHP Class**, which
   extends `Config`, implementing a method called
   `getConfig()`. This method returns a hash (assoc array) which
   contains all the relevant config for the app, including stuff which
   Plite requires, as well as any arbitrary config.

2. **Dev** environment: config is loaded from **a File in the local,
   developer-controlled, filesystem**. That file must reside under a
   directory which is in the developer's control or access--given as
   `SetEnv vertwo_local_root` (e.g., `/Users/srv` on macOS, or `/srv` on
   Linux). And, the app's configuration must be a subdir of that
   directory (e.g., `/Users/srv/<app>` on macOS or `/srv/<app>` on
   Linux). Because a dev can be working on multiple Plite-based
   application, we have to be able to determine the app name (i.e.,
   `<app>`) file from the URL. Effectively, it allows the URL on the dev
   machine to function as a "VirtualApp", analogous to a "VirtualHost",
   at least in terms of app configuration.

In detail:

1. **Prod** environment. **Web Server env vars** must contain the app
   name (`SetEnv plite_app`) as well as a carefully-constructed,
   fully-qualifed PHP class name prefix of the config class (`SetEnv
   plite_fq_class_prefix`); i.e., including namespace, as well as
   keeping in mind any escaping rules that might apply (e.g., in Apache,
   SetEnv cannot have a bare `\`, so PHP namespace separators must look
   like `\\` (e.g., `org\\project\\Abc`, which is expanded to
   `org\project\AbcPliteFactory`.
2. **DEV** environment. **Web server env var** must contain the
   top-level config root (`plite_local_root`) and a regex for getting
   the app name (`plite_app`) from the localhost testing URL. App name
   (`vertwo_app`) must be a non-whitespace, non-punctuated (mostly)
   string, extractable as `\1` from regex (`vertwo_url_app_regex`), for
   example, if `janedoe` is the username, and dev URL looks like
   `http://localhost/~janedoe/app`, then the capturing regex would look
   like: `,localhost/~janedoe/([[:alnum:]-_]*)/,`, where the leading and
   trailing `,` is the regex delimiter.

### Use Case: Web Framework (AWS ElasticBeanstalk "worker" for SQS)

TODO

## As an Output/Logging Library

If you're just using this library for the logger, you don't have to do 
anything.  I've polluted the global namespace with a function called `clog()`, 
which is inspired from the front-end (`console.log()` in Javascript, shortened 
to `clog()`).

It's simple to use:

```
            clog("Hello, world!");
```

In CLI mode, `clog()` outputs to `stderr`, and uses ANSI escape sequences to
colorize the output. It can either be used in 1-argument mode (printing a simple
string), or, more usefully, in 2-argument mode--which prints arg 1 (the "
prompt"), followed by a `: `, followed by arg 2 (the "value").

So, imagine our `main()` function looked like this:

```
    public function main ()
    {
        if ( $this->hasopt("v") ) echo "About to print string!\n";

        if ( $this->hasopt("name") ) {
            echo "Hello, " . $this->getopt("name") . "!\n";
        } else {
            clog("Hello, world!");
            clog("Hello", "world");
        }
        return 0;
    }
```

Then, if no prompt is given, the output will look like this:

![plite-clog-example](https://user-images.githubusercontent.com/1719707/138364833-ed8eb36b-7b99-435c-b738-2cfc24d3eca0.png)

Nice, right?
