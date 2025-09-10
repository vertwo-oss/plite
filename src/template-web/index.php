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



require_once(__DIR__ . "/vendor/autoload.php"); // FIXME



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
        html {
            background: <?php echo WebConfig::get("bg"); ?> no-repeat center center;
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
            width: 60%;
            max-width: 600px;
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
            padding: <?php echo WebConfig::get("top_pad"); ?> 32px 32px 32px;
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


        table {
            border-collapse: collapse;
            border: 1px solid #666;
        }

        table td {
            border: 1px solid #666;
        }

        th, td {
            padding: 15px;
        }

        #plite-dump-table td img {
            width: 64px;
            height: 64px;
        }

        .config_warning {
            color: red;
        }

    </style>
    <title><?php echo WebConfig::get("title"); ?></title>
    <!--
    <script src="js/lib/zepto.min.js"></script>
    <script src="js/vertwo.js"></script>
    -->
</head>
<body>

<h1>Hello to Plite</h1>

<p>
    Routing is disabled. Do a:

<pre>
    $ make routed
</pre>

inside the project root dir to enable.
</p>

<p>
    If you're not going to enable routing, then get rid of this page!!
</p>

<?php printf($configWarning); ?>

<table id="plite-dump-table">
    <?php
    $map  = WebConfig::getMap();
    $html = "";
    
    foreach ( $map as $k => $v )
    {
        $line = "<tr>  <td>$k</td> <td>$v</td>  </tr>";
        $html .= "$line\n";
    }
    
    printf("%s", $html);
    ?>
</table>

<div id="solid_footer">
    <?php printf("%s\n", WebConfig::getSolidFooterContents()); ?>
</div>

</body>
<script>
</script>
</html>
