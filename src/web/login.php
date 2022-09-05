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
 *
 *
 * Artwork attributions:
 *
 * <a href="https://www.flaticon.com/free-icons/user" title="user icons">User icons created by Phoenix Group - Flaticon</a>
 * <a href="https://www.flaticon.com/free-icons/lock" title="lock icons">Lock icons created by Those Icons - Flaticon</a>
 * <a href="https://www.flaticon.com/free-icons/question-mark" title="question mark icons">Question mark icons created by exomoon design studio - Flaticon</a>
 */



use vertwo\plite\Web\PliteTemplate;



require_once(__DIR__ . "/../../vendor/autoload.php"); // FIXME



PliteTemplate::init();



?>
<html lang="en">
<head>
    <style>
        html {
            background: <?php echo PliteTemplate::$css_value_BACKGROUND; ?> no-repeat center center;
            background-size: cover;
            height: 100%;
        }

        body {
            font-family: "Helvetica Neue", Helvetica, Arial, sans-serif;
            font-weight: normal;
            font-size: 16px;
            color: #aaa;
        }

        svg {
            width: 128px;
            height: 128px;
            color: white;
            fill: #ccc;
        }

        #login_form {
            padding: 16px;
            width: 40%;
            position: relative;
            top: 15%;
            margin: 0 auto;
            align-content: center;
            text-align: center;
            alignment: center;
        }

        #login_form div:first-child {
            width: 70%;
            margin: 0 auto;
            border-radius: 8px;
            background-color: rgba(128, 128, 128, 0);
            padding: <?php echo PliteTemplate::$css_value_PADDING_TOP_LOGO; ?> 32px 32px 32px;
        }

        #login_form > div:first-child > img:first-child {
            width: 70%;
            max-width: 128px;
            margin: 0 auto;
            padding: 0;
        }

        #login_form button {
            visibility: hidden;
        }

        #login_form h1 {
            font-size: 24px;
            color: #aaa;
            font-weight: 300;
            padding: 0 32px;
        }

        form label img {
            display: inline;
            color: #777;
        }

        #login_form label img {
            position: absolute;
            height: 24px;
            padding-top: 8px;
            padding-left: 16px;
            alignment: center;
            opacity: 0.25;
        }

        #login_form input {
            height: 40px;
            border: 0;
            padding: 8px 32px 8px 48px;
            font-size: 24px;
            font-weight: normal;
            margin-bottom: 0.5em;
            color: #aaa;
            background-color: rgba(255, 255, 255, 0.05);
            border-radius: 4px;
            width: 90% !important;
        }

        .separator {
            margin-top: 2em;
        }

        ::placeholder {
            color: #555;
            font-size: 24px;
            font-weight: 300;
        }

        a:link {
            color: cyan;
            background: #333;
            padding: 8px;
            border-radius: 4px;
            text-decoration: none;
            opacity: 0.25;
        }

        a:visited {
            color: royalblue;
        }

        a:hover {
            text-decoration: underline;
            opacity: 0.5;
        }

        a:active {
            color: royalblue;
        }

        #solid_footer {
            position: fixed;
            width: 100%;
            height: 48px;
            bottom: 0;
            left: 0;
            margin: 0;
            padding: 0;
            background-color: rgba(0, 0, 0, 0.5);
            color: #aaa;
        }

        #solid_footer div:first-child {
            position: absolute;
            width: 100%;
            bottom: 0;
            left: 0;
            padding: 0 0 0 8px;
            margin: 0 16px;
            text-align: left;
            opacity: 0.5;
        }

        #solid_footer div:last-child {
            position: absolute;
            width: 100%;
            bottom: 0;
            right: 0;
            padding: 0 4px 0 0;
            margin: 0 16px;
            text-align: right;
            opacity: 0.5;
        }

        .v2 {
            color: #f77;
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
            color: #4f4;
        }

        .fail-text {
            color: #f44;
        }

        @-moz-document url-prefix() {
            /* firefox-only css goes here */

            html {
                height: 100%;
            }


            #login_form {
                margin-top: 10% !important;
            }
        }
    </style>
    <title><?php echo PliteTemplate::$string_html_TITLE; ?></title>
    <script src="js/lib/zepto.min.js"></script>
    <script src="js/vertwo.js"></script>
</head>
<body>
<form id="login_form">
    <div id="login_form_inner">
        <?php echo PliteTemplate::$html_elem_LOGO; ?>
        <h1>Sign in to <b><?php echo PliteTemplate::$string_APP_NAME; ?></b></h1>
        <div>
            <label>
                <!--                <img src="http://s.predictus.xyz/res/account.png" alt="profile"/>-->
                <img src="res/account.png" alt="log"/>
                <input id="username" type="text" placeholder="Username" required="" autofocus/>
            </label>
        </div>
        <div>
            <label>
                <!--                <img src="http://s.predictus.xyz/res/lock.png" alt="lock"/>-->
                <img src="res/lock.png" alt="pw"/>
                <input id="password" type="password" placeholder="Password" required=""/>
            </label>
        </div>
        <button type="submit" class="btn btn-default submit">Log In</button>


        <div class="separator"></div>
        <div>
            <a class="reset_pass" href="#">Lost your password?</a>
        </div>

        <div class="clearfix"></div>

        <div class="separator">
            <p class="change_link">New to site?
                <a href="#signup" class="to_register"> Create Account </a>
            </p>

            <div class="clearfix"></div>
            <br/>

            <span id="response" class="hidden">yo</span>
        </div>
    </div>
</form>

<div id="solid_footer">
    <?php printf("%s\n", PliteTemplate::getSolidFooterContents()); ?>
</div>
</body>
<script>
    $u = $('#username');
    $p = $('#password');
    $resp = $('#response');
    isRespVisible = true;

    $(document).ready(function () {
        console.log("Starting!");

        $u.on('keyup', function (ev) {
            if (isRespVisible) {
                $resp.addClass('hidden');
                isRespVisible = false;
                $resp.html("Please enter your login/password.");
            }
        });

        $('#login_form').submit(function (ev) {
            $p.blur();
            ev.preventDefault();
            console.log("logging in...");

            var method = "auth";
            var data = {
                'user': $u.val(),
                'pass': $p.val()
            };

            api(method, data,
                function (resp) {
                    $p.val("");
                    $u.val("");
                    $resp.removeClass('hidden');
                    $resp.removeClass('fail-text');
                    $resp.addClass('win-text');
                    $resp.html("Successful login...");
                    isRespVisible = true;

                    clog("Successfully logged in!");
                    clog(resp);

                    let url = "dashboard";
                    clog("Logging into: " + url);

                    setTimeout(function () { window.location.href = url; }, 250);
                },
                function (resp) {
                    $resp.removeClass('hidden');
                    $resp.removeClass('win-text');
                    $resp.addClass('fail-text');
                    isRespVisible = true;
                    $resp.html("Login / password incorrect; please try again.");
                    $p.val("");
                    $u.val("").focus();
                }
            );
        });
    });
</script>
</html>
