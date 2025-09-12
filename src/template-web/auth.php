<?php
/*
 * Copyright (c) 2025 Troy Wu
 *
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
 *
 *
 * Artwork attributions:
 *
 * <a href="https://www.flaticon.com/free-icons/user" title="user icons">User icons created by Phoenix Group -
 * Flaticon</a>
 * <a href="https://www.flaticon.com/free-icons/lock" title="lock icons">Lock icons created by Those Icons -
 * Flaticon</a>
 * <a href="https://www.flaticon.com/free-icons/question-mark" title="question mark icons">Question mark icons created
 * by exomoon design studio - Flaticon</a>
 */



use vertwo\plite\Modules\FlatFileAuthModule;
use vertwo\plite\Web\Web;
use vertwo\plite\Web\WebConfig;
use function vertwo\plite\clog;



require_once(__DIR__ . "/../../vendor/autoload.php"); // FIXME (see v2web)


session_start();


try
{
    $web = new Web();
    $web->dump();
}
catch ( Exception $e )
{
    clog($e);
    FlatFileAuthModule::logout();
}

$cmd   = $web->testBoth("cmd");
$login = $web->testBoth("login");
$pass1 = $web->testBoth("pass1");
$pass2 = $web->testBoth("pass2");

if ( false === $cmd ) $cmd = "login";

$IS_SIGNUP = "signup" === $cmd;
$IS_LOGOUT = "logout" === $cmd;

clog([
       "Is signup" => $IS_SIGNUP,
       "Is logout" => $IS_LOGOUT,
     ]);

if ( $IS_LOGOUT )
{
    FlatFileAuthModule::logout();
    header("Location: .");
    exit(0);
}

//
// Not an API call
//
if ( false === $login && false === $pass1 && false === $pass2 )
{
    $MAIN_PHRASE = "signup" === $cmd ? "Sign-up for" : "Login to";
    $MAIN_VERB   = "signup" === $cmd ? "Sign-up" : "Login";
    $SWITCH_VERB = "signup" === $cmd ? "Login" : "Sign-up";
    $SWITCH_CMD  = "signup" === $cmd ? "login" : "signup";
    $MAIN_CMD    = $cmd;
}
//
// Just an API call, so process it, and then exit().
//
else
{
    clog("user info", [
      "login" => $login,
      "pass1" => $pass1,
      "pass2" => $pass2,
    ]);
    
    $pass1 = trim($pass1);
    $pass2 = trim($pass2);
    
    $loginlen = strlen($login);
    $p1len    = strlen($pass1);
    $p2len    = strlen($pass2);
    
    if ( $loginlen <= 0 )
    {
        $web->fail("login is empty");
    }
    else
    {
        if ( $IS_SIGNUP )
        {
            // Right size?
            if ( $p1len >= 1 && $p2len >= 1 )
            {
                if ( $p1len == $p2len )
                {
                    if ( 0 == strncmp($pass1, $pass2, $p1len) )
                    {
                        try
                        {
                            $authmod = new FlatFileAuthModule("users");
                            $user    = $authmod->c($login, $pass1);
                            
                            $web->win("Users created.");
    
                            //
                            //
                            //
                            //
                            //
                            //
                            //
                            //
                            //
                            //
                            // DANGER
                            // FIXME
                            // MEAT - Actually add logic to either auto-signin, or go to signin page.
                            //
                            //
                            //
                            //
                            //
                            //
                            //
                            //
                            //
                            //
                        }
                        catch ( Exception $e )
                        {
                            clog($e);
                            
                            $web->fail($e->getMessage());
                            FlatFileAuthModule::logout();
                        }
                    }
                    else
                    {
                        $web->fail("Passwords don't match.");
                        FlatFileAuthModule::logout();
                    }
                }
                else
                {
                    $web->fail("password sizes don't match");
                    FlatFileAuthModule::logout();
                }
            }
            else
            {
                $web->fail("one password is empty");
                FlatFileAuthModule::logout();
            }
        }
        else
        {
            // Do auth.
            if ( $loginlen <= 0 )
            {
                $web->fail("login is empty");
                FlatFileAuthModule::logout();
            }
            else
            {
                try
                {
                    $authmod = new FlatFileAuthModule("users");
                    $user    = $authmod->authenticate($login, $pass1);
                    
                    clog("Logged in user", $user);
                    
                    $web->win("User logged in.");
                }
                catch ( Exception $e )
                {
                    clog($e);
                    
                    $web->fail($e->getMessage());
                }
            }
        }
    }
    
    $web->respond();
    exit(0);
}




?>
<html lang="en">
<head>
    <meta name="color-scheme" content="light dark">
    <link rel="preload" href="assets/fonts/computer_modern/Serif/cmun-serif.css"
          as="font"
          onload="this.onload=null;this.rel='stylesheet'"/>
    <link rel="stylesheet" href="assets/fonts/computer_modern/Bright/cmun-bright.css"
          as="font"
          onload="this.onload=null;this.rel='stylesheet'"/>
    <link rel="stylesheet" href="assets/fonts/computer_modern/Concrete/cmun-concrete.css"
          as="font"
          onload="this.onload=null;this.rel='stylesheet'"/>
    <link rel="stylesheet" href="assets/fonts/computer_modern/Typewriter Light/cmun-typewriter-light.css"
          as="font"
          onload="this.onload=null;this.rel='stylesheet'"/>
    <link rel="stylesheet" href="assets/fonts/computer_modern/Bright Semibold/cmun-bright-semibold.css"
          as="font"
          onload="this.onload=null;this.rel='stylesheet'"/>
    <link rel="stylesheet" href="assets/fonts/computer_modern/Sans/cmun-sans.css"
          as="font"
          onload="this.onload=null;this.rel='stylesheet'"/>
    <link rel="stylesheet" href="assets/fonts/computer_modern/Sans Demi-Condensed/cmun-sans-demicondensed.css"
          as="font"
          onload="this.onload=null;this.rel='stylesheet'"/>
    <link rel="stylesheet" href="assets/fonts/computer_modern/Classical Serif Italic/cmun-classical-serif-italic.css"
          as="font"
          onload="this.onload=null;this.rel='stylesheet'"/>
    <link rel="stylesheet" href="assets/fonts/computer_modern/Typewriter Variable/cmun-typewriter-variable.css"
          as="font"
          onload="this.onload=null;this.rel='stylesheet'"/>
    <link rel="stylesheet" href="assets/fonts/computer_modern/Serif Slanted/cmun-serif-slanted.css"
          as="font"
          onload="this.onload=null;this.rel='stylesheet'"/>
    <link rel="stylesheet" href="assets/fonts/computer_modern/Upright Italic/cmun-upright-italic.css"
          as="font"
          onload="this.onload=null;this.rel='stylesheet'"/>
    <link rel="stylesheet" href="assets/fonts/computer_modern/Typewriter/cmun-typewriter.css"
          as="font"
          onload="this.onload=null;this.rel='stylesheet'"/>
    <link rel="preload" href="assets/css/font-awesome.min.css"
          as="style"
          onload="this.onload=null;this.rel='stylesheet'"/>
    <style>
        @media (prefers-color-scheme: light) {
            html {
                /*background: rgba(205, 205, 205, 1); #cdcdcd, original Netscape background */
                background: #f7f7f7;
                color: black;
            }

            header a {
                color: #aaa;
            }

            #auth_form input {
                color: black;
                background-color: rgba(0, 0, 0, 0.03);
            }

            ::placeholder {
                color: #ccc;
            }

            #auth_form button {
                /*background-color: rgba(0, 255, 255, 0.1);*/
                /*background-color: rgba(255, 0, 0, 0.1);*/
                /*color: rgba(96, 96, 96, 0.5);*/
            }

            .input_bad {
                background-color: rgba(255, 0, 0, 0.1);
                color: rgba(96, 96, 96, 0.5);
                border: 2px solid rgba(255, 0, 0, 0);
            }

            .input_good {
                background-color: rgba(0, 255, 0, .5);
                color: black;
                border: 2px solid rgba(0, 255, 0, 1);
            }

            #auth_form a:link {
                color: rgba(0, 0, 0, 0.3);
                background-color: rgba(0, 0, 0, 0.05);
                opacity: 0.75;
            }

            .change_link {
                color: rgba(0, 0, 0, 0.3);
            }

            #solid_footer {
                color: rgba(96, 96, 96, 0.25);
            }


            .v2 {
                color: rgba(255, 0, 0, 0.5);
            }
        }

        @media (prefers-color-scheme: dark) {
            html {
                background: #333;
                color: #777;
            }

            header a {
                color: #666;
            }

            #auth_form input {
                color: cyan;
                background-color: rgba(255, 255, 255, 0.03);
            }

            ::placeholder {
                color: #666;
            }

            #auth_form button {
                /*background-color: rgba(0, 255, 255, 0.1);*/
                /*background-color: rgba(255, 0, 0, 0.1);*/
                /*color: rgba(96, 96, 96, 0.5);*/
            }

            .input_bad {
                background-color: rgba(255, 0, 0, 0.2);
                color: rgba(96, 96, 96, 0.8);
            }

            .input_good {
                background-color: rgba(0, 255, 0, .5);
                color: black;
            }

            #auth_form a:link {
                color: rgba(255, 255, 255, 0.3);
                background-color: rgba(255, 255, 255, 0.05);
                opacity: 0.75;
            }

            .change_link {
                color: rgba(255, 255, 255, 0.3);
            }

            #solid_footer {
                color: rgba(96, 96, 96, 0.8);
            }

            .v2 {
                color: rgba(255, 64, 64, 0.3);
            }
        }

        html {
            background-size: cover;
            height: 100%;
        }

        body {
            font-family: "Computer Modern Bright", "Helvetica Neue", Helvetica, Arial, sans-serif;
            font-weight: normal;
            font-size: 16px;
        }

        .almost-there {
            visibility: hidden;
        }

        header {
            font-family: "Computer Modern Bright", "Poppins", sans-serif;
            margin: 1em;
            font-size: 1.25em;
        }

        header a {
            text-decoration: none;
        }

        #header > .logo span {
            font-weight: 400;
            font-size: .8em;
        }

        svg {
            width: 128px;
            height: 128px;
            /*color: white;*/
            fill: #ccc;
        }

        #auth_form {
            padding: 16px;
            width: 60%;
            max-width: 600px;
            position: relative;
            top: 5%;
            margin: 0 auto;
            align-content: center;
            text-align: center;
            alignment: center;
        }

        #auth_form_inner {
            width: 70%;
            margin: 0 auto;
            border-radius: 8px;
            padding: <?php echo WebConfig::get("top_pad"); ?> 32px 32px 32px;
        }

        #auth_form_input_box {
            margin-top: 3em;
            margin-bottom: 3em;
        }

        #auth_form > div:first-child > img:first-child {
        <?php
        if ( WebConfig::has("logo_width") ) {
            $w =  WebConfig::get("logo_width");
            echo <<<EOF
            width: $w;
EOF;
        } else {
        echo <<<EOF
            width: 70%;
            max-width: 128px;
EOF;
        }
        ?>;
            margin: 0 auto;
            padding: 0;
        }

        #auth_form button {
            width: 50%;
            height: 2em;
            font-size: 16px;
            border-radius: 16px;
            margin-top: 1em;
            margin-bottom: 3em;
        }

        #auth_form h1 {
            font-size: 24px;
            font-weight: 300;
            padding: 0 32px;
        }

        form label img {
            display: inline;
        }

        #auth_form label img {
            position: absolute;
            height: 24px;
            padding-top: 8px;
            padding-left: 16px;
            alignment: center;
            opacity: 0.25;
        }

        #auth_form input {
            height: 40px;
            border: 0;
            padding: 8px 32px 8px 48px;
            font-size: 24px;
            font-weight: normal;
            margin-bottom: 0.5em;
            border-radius: 4px;
            width: 90% !important;
        }

        .separator {
            margin-top: 2em;
        }

        ::placeholder {
            font-size: 24px;
            font-weight: 300;
        }

        #auth_form a:link {
            padding: 8px 16px;
            text-decoration: none;
        }

        #auth_form a:visited {
        }

        #auth_form a:hover {
            text-decoration: underline;
        }

        #auth_form a:active {
        }

        #solid_footer {
            position: fixed;
            width: 100%;
            height: 48px;
            bottom: 0;
            left: 0;
            margin: 0;
            padding: 0;
        }

        #solid_footer div:first-child {
            position: absolute;
            width: 100%;
            bottom: 0;
            left: 0;
            padding: 0 0 0 8px;
            margin: 0 16px;
            text-align: left;
        }

        #solid_footer div:last-child {
            position: absolute;
            width: 100%;
            bottom: 0;
            right: 0;
            padding: 0 4px 0 0;
            margin: 0 16px;
            text-align: right;
        }

        .v2 {
            font-family: "Computer Modern Bright", sans-serif;
        }

        .cmd_signup {
        <?php
        if ( $cmd === "signup" ) {
            echo <<<EOF
        display: block;
EOF;
        } else {
            echo <<<EOF
        display: none;
EOF;
        }
        ?>
        }


        .hidden {
            visibility: hidden;
        }

        #response {
            font-size: 18px;
            font-style: italic;
            font-weight: 300;
        }

        .win-text {
            /*color: #4f4;*/
        }

        .fail-text {
            /*color: #f44;*/
        }

        @-moz-document url-prefix() {
            /* firefox-only css goes here */

            html {
                height: 100%;
            }


            #auth_form {
                margin-top: 10% !important;
            }
        }
    </style>
    <title><?php echo WebConfig::get("title"); ?></title>
</head>

<body>
<!-- Header -->
<header id="header" class="alt">
    <div class="logo"><a href="."><?php echo WebConfig::get("title"); ?></a>
    </div>
</header>

<form id="auth_form">
    <div id="auth_form_inner">
        <?php echo WebConfig::get("logo"); ?>
        <h1><?php echo $MAIN_PHRASE; ?> <b><?php echo WebConfig::get("appname"); ?></b></h1>

        <div id="auth_form_input_box">
            <div>
                <label>
                    <img src="assets/images/account.png" alt="login"/>
                    <input class="auth_input" name="login" id="login" type="text" placeholder="Login" required=""
                           autofocus/>
                </label>
            </div>
            <div>
                <label>
                    <img src="assets/images/lock.png" alt="pass1"/>
                    <input class="auth_input" name="pass1" id="pass1" type="password" placeholder="Password"
                           required=""/>
                </label>
            </div>
            
            <?php
            if ( $IS_SIGNUP )
            {
                echo <<<EOF
            <div class="cmd_signup">
                <label>
                    <img src="assets/images/lock.png" alt="pass2"/>
                    <input class="auth_input" name="pass2" id="pass2" type="password" placeholder="Password, again"
                           required=""/>
                </label>
            </div>
EOF;
            }
            ?>

            <button type="submit" class="btn btn-default submit input_bad"><?php echo $MAIN_VERB; ?></button>
        </div>

        <div class="separator"></div>
        <div>
            <a class="reset_pass" href="#">Lost your password?</a>
            <a href="auth.php?cmd=<?php echo $SWITCH_CMD; ?>" class="to_login"><?php echo $SWITCH_VERB; ?> Instead?</a>
        </div>

        <div class="clearfix"></div>

        <div class="separator">
            <span id="response" class="hidden">yo</span>
        </div>
    </div>
</form>

<div id="solid_footer">
    <?php printf("%s\n", WebConfig::getSolidFooterContents()); ?>
</div>
</body>


<script src="js/lib/zepto.min.js"></script>
<script src="js/vertwo.js"></script>
<script>
    let cmd = "<?php echo $MAIN_CMD; ?>";
    let isSignup = ("signup" === cmd);

    clog("Main command: " + cmd);

    let $login = $('#login');
    let $pass1 = $('#pass1');
    let $pass2 = isSignup ? $('#pass2') : null;

    clog("pass2-");
    clog($pass2);

    let $button = $('#auth_form button');
    let $authInputs = $('.auth_input');
    let $resp = $('#response');
    let isRespVisible = true;

    $(document).ready(function () {
        console.log("Starting!");

        $authInputs.on('keyup', function (ev) {
            let login = $login.val().trim();
            let pass1 = $pass1.val().trim();

            clog("login: " + login);
            clog("pass1: " + pass1);

            if ("signup" === cmd) {
                let pass2 = $pass2.val().trim();
                clog("pass2: " + pass2);

                if (login.length >= 1 && (pass1.length === 0 && pass2.length === 0)) {
                    $button.removeClass("input_good").addClass('input_bad');
                    $button.text("Enter password...");
                } else if (login.length >= 1 && pass1 !== pass2) {
                    $button.removeClass("input_good").addClass('input_bad');
                    $button.text("Passwords don't match...");
                } else if (login.length >= 1 && (pass1 === pass2)) {
                    $button.removeClass("input_bad").addClass('input_good');
                    $button.text("Sign-up!");
                }
            } else {
                cmd = "login";

                if (login.length >= 1 && (pass1.length === 0)) {
                    $button.removeClass("input_good").addClass('input_bad');
                    $button.text("Enter password...");
                } else if (login.length >= 1 && pass1.length >= 1) {
                    $button.removeClass("input_bad").addClass('input_good');
                    $button.text("Login!");
                }
            }

            if (isRespVisible) {
                $resp.addClass('hidden');
                isRespVisible = false;
                $resp.html("Please enter your login/password.");
            }
        });

        $('#auth_form').submit(function (ev) {
            console.log("Pressed button...");

            $pass1.blur();
            ev.stopPropagation();
            ev.preventDefault();
            console.log("logging in...");

            let login = $login.val().trim();
            let pass1 = $pass1.val().trim();
            let data = isSignup
                ? {
                    'cmd': cmd,
                    'login': login,
                    'pass1': pass1,
                    'pass2': $pass2.val().trim()
                }
                : {
                    'cmd': cmd,
                    'login': login,
                    'pass1': pass1,
                }
            ;

            let method = "auth.php";

            api(method, data,
                function (resp) {
                    $login.val("");
                    $pass1.val("");
                    if (isSignup) $pass2.val("");
                    $resp.removeClass('hidden');
                    $resp.removeClass('fail-text');
                    $resp.addClass('win-text');
                    $resp.html("Successful login...");
                    isRespVisible = true;

                    clog("Successfully logged in!");
                    clog(resp);

                    let url = "dashboard.php";
                    clog("Logging into: " + url);

                    setTimeout(function () {
                        window.location.href = url;
                    }, 750);
                },
                function (resp) {
                    $resp.removeClass('hidden');
                    $resp.removeClass('win-text');
                    $resp.addClass('fail-text');
                    isRespVisible = true;
                    $resp.html(resp.error);
                    $pass1.val("");
                    if (isSignup) $pass2.val("");
                    $login.val("").focus();
                }
            );
        });
    });
</script>
</html>
