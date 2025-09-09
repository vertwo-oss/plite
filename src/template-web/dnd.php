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
 * <a href="https://www.flaticon.com/free-icons/user" title="user icons">User icons created by Phoenix Group -
 * Flaticon</a>
 * <a href="https://www.flaticon.com/free-icons/lock" title="lock icons">Lock icons created by Those Icons -
 * Flaticon</a>
 * <a href="https://www.flaticon.com/free-icons/question-mark" title="question mark icons">Question mark icons created
 * by exomoon design studio - Flaticon</a>
 */



use vertwo\plite\Web\WebConfig;



require_once(__DIR__ . "/../../vendor/autoload.php"); // FIXME



try
{
    WebConfig::init();
}
catch ( Exception $e )
{
}



?>
<html lang="en">
<head>
    <title><?php echo WebConfig::get("title"); ?></title>
    <link href="res/vertwo-plite-dz.css" rel="stylesheet"/>
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

        #dnd-box {
            padding: 16px;
            width: 80%;
            position: relative;
            top: 15%;
            margin: 0 auto;
            align-content: center;
            text-align: center;
            alignment: center;
        }

        #dnd-box > div:first-child {
            width: calc(100% - 32px);
            border-radius: 8px;
            background-color: rgba(128, 128, 128, 0);
            padding: <?php echo WebConfig::get("top_pad"); ?> 32px 32px 32px;
        }

        #dnd-box > img:first-child {
            width: 70%;
            max-width: 128px;
            margin: 0 auto;
            padding: 0;
        }

        #dnd-box h1 {
            font-size: 24px;
            color: #aaa;
            font-weight: 300;
            padding: 0 32px;
        }

        form label img {
            display: inline;
            color: #777;
        }

        #dnd-box label img {
            position: absolute;
            height: 24px;
            padding-top: 8px;
            padding-left: 16px;
            alignment: center;
            opacity: 0.25;
        }

        input {
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

        button, a {
            font-size: 14px;
            padding: 8px 24px;
            border: 1px solid darkcyan;
        }

        button,
        a:link {
            color: darkcyan;
            background: #333;
            border-radius: 4px;
            text-decoration: none;
        }

        a:visited {
            color: darkcyan;
        }

        button:hover,
        a:hover {
            color: cyan;
            background-color: #111;
            border: 1px solid darkcyan;
            cursor: pointer;
        }

        a:active {
            color: darkcyan;
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


            #dnd-box {
                margin-top: 64px !important;
            }
        }

        #dnd {
            margin-bottom: 50px;
        }
    </style>
</head>
<body>
<div id="dnd-box">
    <?php echo WebConfig::get("logo"); ?>
    <h1>Drag-and-Drop</h1>

    <div id="dnd">
    </div>
</div>

<div id="solid_footer">
    <?php printf("%s\n", WebConfig::getSolidFooterContents()); ?>
</div>
</body>
<!-- Zepto -->
<script src="js/lib/zepto.min.js"></script>
<!-- Mustache -->
<script src="js/lib/mustache.js"></script>
<!-- Version2 -->
<script src="js/vertwo.js"></script>
<script>


    $(document).ready(function () {
        console.log("DND Test Starting!");

        const $dz = $(document.body);
        var $dzui = $('#dnd');

        const progressHandler = function (ev) {
            var cur = ev['position'];
            var total = ev['totalSize'];
            var progress = cur / total; // This is between 0 and 1.

            if (cur < total) {
                var progressUI = progress * 0.8; // Customize this value for however long the URL call takes.
                clog("  pct: " + progressUI);
            }
        };
        const loadStartHandler = function (ev) {
            clog("XHR -> load START");
            console.log(ev);
        };
        const loadHandler = function (ev) {
            clog("XHR -> load");
            console.log(ev);
        };
        const errorHandler = function (ev) {
            clog("XHR -> error");
            console.log(ev);
        };
        const abortHandler = function (ev) {
            clog("XHR -> abort");
            console.log(ev);
        };


        function formDataHandler(fd) {
            clog("Uploading files...");

            var xhr = new XMLHttpRequest();
            xhr.upload.addEventListener("loadstart", loadStartHandler, false);
            xhr.upload.addEventListener("progress", progressHandler, false);
            xhr.addEventListener("load", loadHandler, false);
            xhr.addEventListener("error", errorHandler, false);
            xhr.addEventListener("abort", abortHandler, false);

            var url = "upload";
            xhr.open("POST", url, true);
            xhr.send(fd);
        }


        var params = {
            "$dz": $dz,
            "$ui": $dzui,
            "formDataHandler": formDataHandler,
            "dndText": "Drop Here!"
        };

        createDropZone(params);
        // createDropZone($dz, $dzInfo, formDataHandler); // MEAT <==


    });


</script>
</html>
