# plite
PHP Lite Framework

Adds some basic libraries for working with console-viewed logging, PHP as a CLI tool, handling basic server-side web API requests, a very small Postgres abstraction, as well as a small framework for abstracting a few AWS services (S3, SecretsManager, and SES) and Twilio/Sinch/Plivo.  Also comes with tools to help with ETL.

The AWS abstraction classes also allow you to use a local, offline version for test and dev, which does NOT require an AWS account or access to its services, but through a series of small config changes, can allow your code to immediately use cloud resources (e.g., AWS S3 instead of your local filesystem).

There are several entry points for using this library, depending on the intended usage.

## Use Case: Web Framework (backend)

When using Plite as a web framework,

>  There is an **ABSOLUTE REQUIREMENT** that DevOps can control Web
>  Server environment variables (e.g., SetEnv on apache).

> Additionally, the app name, used throughout the framework, must be a
> string which matches `[[:alnum:]-_]`.

It uses the web server environment variables to provide two mechanisms
to bootstrap a configuration:

1. **Prod** enviroment: config is loaded from **a PHP Class**, which
   extends `PliteFactory`, implementing a method called
   `loadDefaultConfig()`. This method returns a hash (assoc array) which
   contains all the relevant config for the app, including stuff which
   Plite requires, as well as any arbitrary config.

2. **Dev** environment: config is loaded from **a File in the local,
   developer-controlled, filesystem. That file must reside under a
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



## Use Case: CLI

If you are using this to create a command-line script (e.g., executable with a shebang), then your starting point is the class `vertwo\plite\CLI`.

Just extend `CLI`, and implement three methods:

* `main()`
* `getShortOpts()`
* `getLongOpts()`

The latter two methods are for unix-style options-handling can return empty string and an empty array.  So, for a hello-world, just this will do:

```
class HelloWorld extends CLI
{
    protected function getShortOpts () { return ""; }
    protected function getLongOpts () { return []; }


    /**
     * CLI entry point.
     *
     * @return int
     */
    public function main ()
    {
        echo "Hello, world!\n";
        return 0;
    }
}


HelloWorld::run();
```

And, if you add a shebang, a PHP tag, a `use` statement, and a `require` statement, and then make the script executable, you'll be able to just execute this php file on the CLI (and, obviously, making sure to chmod +x or whatever your OS needs to make things runnable).

Now, obviously, that's a LOT of lines of code for a file that could have just been this:

```
#! /usr/bin/php
<?php
echo "Hello, world!\n";
```

So, why put it up with it?

Well, just 1 reason: options-handling.

In order for PHP to function well in a unix-y shell-environment, scripts often nee to be run with different arguments to have slightly different behavior.

Suppose our PHP script is named `hello`.

Let's say we want to add a *verbose* switch to our program, so that we can call it like this:

`$ hello -v`

Then, we just change one line:

```
    protected function getShortOpts () { return "v"; }
```

And we can use it like this:

```
    public function main ()
    {
        if ( $this->hasopt("v") ) echo "About to print string!\n";
        echo "Hello, world!\n";
        return 0;
    }
```

This makes CLI PHP much more effective.

And suppose we wanted a long option: `--name your_name`

Then, we change the implementation of `getLongOpts()` like this:

```
    protected function getLongOpts ()
    {
        return [
            self::REQ("name"),
        ];
    }
```

and use it like this:

```
    public function main ()
    {
        if ( $this->hasopt("v") ) echo "About to print string!";

        if ( $this->hasopt("name") )
        {
            echo "Hello, world, " . $this->getopt("name") . "!\n";
        }
        else
        {
            echo "Hello, world!\n";
        }
        return 0;
    }
```

Beautiful.

Here's the final, (potentially) executable script:

```
#! /usr/bin/php
<?php



use vertwo\plite\CLI;



require_once __DIR__ . "/../vendor/autoload.php";



class HelloWorld extends CLI
{
    protected function getShortOpts () { return "v"; }

    protected function getLongOpts ()
    {
        return [
            self::REQ("name"),
        ];
    }


    /**
     * CLI entry point.
     *
     * @return int
     */
    public function main ()
    {
        if ( $this->hasopt("v") ) echo "About to print string!\n";

        if ( $this->hasopt("name") )
        {
            echo "Hello, world, " . $this->getopt("name") . "!\n";
        }
        else
        {
            echo "Hello, world!\n";
        }
        return 0;
    }
}



HelloWorld::run();
```


## Use Case: Logging

If you're just using this library for the logger, you just need to import one function: `vertwo\plite\clog`.

It uses the `vertwo\plite\Log` class, and the functions are loaded as a separate function into the top-level `vertwo\plite` namespace.

If you're wondering why it's called `clog`, it's shorthand for the javascript logger: `console.log`.

It's simple to use:

```
            clog("Hello, world!");
```

In CLI mode, `clog()` outputs to `stderr`, and uses ANSI escape sequences to colorize the output.  It can either be used in 1-argument mode (printing a simple string), or, more usefully, in 2-argument mode--which prints arg 1 (the "prompt"), followed by a `: `, followed by arg 2 (the "value").

So, imagine our `main()` function looked like this:

```
    public function main ()
    {
        if ( $this->hasopt("v") ) echo "About to print string!\n";

        if ( $this->hasopt("name") )
        {
            echo "Hello, world, " . $this->getopt("name") . "!\n";
        }
        else
        {
            clog("Hello, world!");
            clog("Hello", "world");
        }
        return 0;
    }
```

Then, if no prompt is given, the output will look like this:

![plite-clog-example](https://user-images.githubusercontent.com/1719707/138364833-ed8eb36b-7b99-435c-b738-2cfc24d3eca0.png)

Beautiful.

