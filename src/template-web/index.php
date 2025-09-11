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
 *
 *
 * Artwork attributions:
 *
 * <a href="https://www.flaticon.com/free-icons/user" title="user icons">User icons created by Phoenix Group - Flaticon</a>
 * <a href="https://www.flaticon.com/free-icons/lock" title="lock icons">Lock icons created by Those Icons - Flaticon</a>
 * <a href="https://www.flaticon.com/free-icons/question-mark" title="question mark icons">Question mark icons created by exomoon design studio - Flaticon</a>
 */



use vertwo\plite\Web\WebConfig;
use function vertwo\plite\clog;



require_once(__DIR__ . "/../../vendor/autoload.php"); // FIXME



try
{
    $HAS_CONFIG    = true;
    $configWarning = "";
    WebConfig::load();
}
catch ( Exception $e )
{
    clog($e);
    $HAS_CONFIG = false;
}



?>
<html lang="en">
<head>
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
    <link rel="preload" href="assets/fonts/font-awesome.min.css"
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

            .change_link {
                color: rgba(0, 0, 0, 0.3);
            }

            .v2 {
                color: rgba(255, 0, 0, 0.5);
            }

            .key_tile {
                color: darkcyan;
            }

            .value_tile {
                color: purple;
            }

            #solid_footer {
                background: #f7f7f7;
                color: rgba(0, 0, 0, .25);
            }

            table tr td {
                background: linen;
            }

            #config-warning {
                background: red;
                color: white;
                border-color: yellow;
            }

            #config-warning h2 {
                color: yellow;
            }

            #config-warning code,
            #config-warning pre {
                background: antiquewhite;
                color: black;
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

            .change_link {
                color: rgba(255, 255, 255, 0.3);
            }

            .v2 {
                color: rgba(255, 64, 64, 0.3);
            }

            .key_tile {
                color: cyan;
            }

            .value_tile {
                color: yellow;
            }


            #solid_footer {
                background: #333;
                color: rgba(255, 255, 255, 0.15);
            }

            table tr td {
                background: #444;
            }

            #config-warning {
                background: darkred;
                color: #bbb;
                border-color: #777;
            }

            #config-warning h2 {
                color: rgba(255, 255, 0, 0.75);
            }

            #config-warning code,
            #config-warning pre {
                background: #222;
                color: red;
            }
        }

        html {
            font-family: "Computer Modern Sans", sans-serif;
            font-weight: 100;
            font-size: 14pt;
        }

        #header {
            font-family: "Computer Modern Bright", sans-serif;
            font-size: 1.25em;
            margin: 1em;
            position: relative;
        }

        #header li {
            list-style-type: none;
        }

        #header a {
            text-decoration: none;
        }

        #header ul:last-child {
            position: absolute;
            margin: 0;
            padding: 0;
            top: 0;
            right: 1.5em;
        }


        body {
            width: 100%;
        }

        body section {
            width: 90%;
            margin: 3em auto 3em auto;
            padding: 1em 0 2em 0;
        }

        .separator {
            margin-top: 2em;
        }

        .clearfix {
            clear: both;
        }

        #solid_footer {
            font-family: "Computer Modern Bright", sans-serif;
            position: fixed;
            width: 100%;
            height: 48px;
            bottom: 0;
            left: 0;
            margin: 2em 0 0 0;
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
            /*opacity: 0.5;*/
        }

        #solid_footer div:last-child {
            position: absolute;
            width: 100%;
            bottom: 0;
            right: 0;
            padding: 0 4px 0 0;
            margin: 0 16px;
            text-align: right;
            /*opacity: 0.5;*/
        }

        #plite-dump h2 {
        }

        #plite-dump-table {
        }

        code,
        pre,
        .kv_tile {
            font-family: "Computer Modern Typewriter Light", monospace;
            padding: 1px 6px;
            border-radius: 4px;
            font-weight: 100;
        }

        pre,
        .kv_tile {
            font-size: 16pt;
        }

        .key_tile {
            text-align: right;
        }

        .value_tile {
        }

        .value_tile img {
            max-height: 128px;
        }

        table tr td {
            padding: 1em 2em;
            border-radius: 12px;
        }

        .v2 {
            font-family: "Computer Modern Bright", sans-serif;
        }

        .exists {
            display: block;
        }

        .no-exists {
            display: none;
        }

        #config-warning {
            width: 60%;
            padding: 2em;
            border-radius: 16px;
            border-width: 1px;
            border-style: solid;
            margin: 2em auto;
            font-weight: 900;
        }

    </style>
    <title><?php echo WebConfig::get("title"); ?></title>
</head>
<body>
<!-- Header -->
<header id="header" class="alt">
    <div class="logo"><a href="."><?php echo WebConfig::get("title"); ?></a>
    </div>
    <ul>
        <li><a href="auth.php">Login Test</a></li>
    </ul>
</header>

<section>
    <h1>Hello to Plite</h1>

    <p> Routing is disabled. Do a: </p>

    <pre>
    $ make routed
</pre>

    <p> inside the project root dir to enable. </p>

    <p> If you're not going to enable routing, then get rid of this page!! </p>


    <div style="display:none;" id="config-warning">
        <h2>OH NO! No configuration found!</h2>

        <p>
            In the plite config files, either in the subclass config or a local
            filesystem config, create config settings with a prefix of <code>wl_</code>
            for each of the settings in in the below. For example, create a
            setting of <code>wl_title</code> to set the HTML title of the page!
        </p>
    </div>


    <div style="display: none;" id="config-table">
        <h2>Plite Configuration Parameters</h2>
        <table id="plite-dump-table">
            
            <?php
            $map  = WebConfig::getMap();
            $html = "";
            
            foreach ( $map as $k => $v )
            {
                $row  = <<<EOF
            <tr>
                <td><div class="kv_tile key_tile">$k</div></td>
                <td><div class="kv_tile value_tile">$v</div></td>
            </tr>

EOF;
                $html .= "$row\n";
            }
            
            printf("%s", $html);
            ?>

        </table>
    </div>


</section>

<div class="separator"></div>
<div class="clearfix"></div>


<div id="solid_footer">
    <?php printf("%s\n", WebConfig::getSolidFooterContents()); ?>
</div>

</body>


<script src="js/lib/zepto.min.js"></script>
<script src="js/vertwo.js"></script>
<script>


    $(document).ready(function () {

        console.log("Main Test Starting!");
        let hasConfig = <?php echo $HAS_CONFIG ? "true" : "false"; ?>;

        clog("has config? " + hasConfig);

        if (hasConfig) {
            $("#config-warning").removeClass("exists").addClass("no-exists").hide();
            $("#config-table").removeClass("no-exists").addClass("exists").show();
        } else {
            $("#config-table").removeClass("exists").addClass("no-exists").hide();
            $("#config-warning").removeClass("no-exists").addClass("exists").show();
        }

    });


</script>
</html>
