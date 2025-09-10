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



require_once(__DIR__ . "/../../vendor/autoload.php"); // FIXME



try
{
    $configWarning = "";
    WebConfig::load();
}
catch ( Exception $e )
{
    $configWarning = <<<EOF
<h2 class="config_warning">OH NO!  No configuration found!</h2>

<p>
In the plite config files, either in the subclass config or a local
filesystem config, create config settings with a prefix of <code>wl_</code>
for each of the settings in in the below.  For example, create a
setting of <code>wl_title</code> to set the HTML title of the page!
</p>

EOF;
}



?>
<html lang="en">
<head>
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
                background: #fff;
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
        }

        html {
            font-family: "Poppins", "Helvetica Neue", "Helvetica", "Arial", sans-serif;
            font-weight: 300;
        }

        header {
            font-family: "Poppins", "Helvetica Neue", "Helvetica", "Arial", sans-serif;
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

        body {
            width: 100%;
        }

        body section {
            width: 90%;
            margin: 0 auto 3em auto;
            padding: 1em 0 2em 0;
        }

        .separator {
            margin-top: 2em;
        }

        .clearfix {
            clear: both;
        }

        #solid_footer {
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

        .kv_tile {
            font-family: "Fira Code", "Monaco", monospace;
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

    </style>
    <title><?php echo WebConfig::get("title"); ?></title>
    <!--
    <script src="js/lib/zepto.min.js"></script>
    <script src="js/vertwo.js"></script>
    -->
</head>
<body>
<!-- Header -->
<header id="header" class="alt">
    <div class="logo"><a href="."><?php echo WebConfig::get("title"); ?></a>
    </div>
</header>

<section>
    <h1>Hello to Plite</h1>

    <p>
        Routing is disabled. Do a:
    </p>

    <pre>
    $ make routed
</pre>

    <p>
        inside the project root dir to enable.
    </p>

    <p>
        If you're not going to enable routing, then get rid of this page!!
    </p>
    
    <?php echo $configWarning; ?>

    <div id="plite-dump">
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
<script>
</script>
</html>
