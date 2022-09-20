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
 * ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF
 * THE POSSIBILITY OF SUCH DAMAGE.
 */



function clog(mesg) { console.log(mesg);}



function api(method, data, onWin, onFail, onDone) {
    if (!method || !onWin) {
        console.log("AJAX FASTFAIL - no method/successHandler; aborting.");
        return;
    }

    var url = method;
    var params = {
        type    : "POST",
        url     : url,
        data    : data,
        success : function (d, textStatus, xhr) {
            parseOkResponse(url, d, onWin, onFail);
        },
        error   : function (xhr, textStatus, errorThrown) {
            console.log("[HTTP ERROR] - [ " + url + " ] - " + textStatus + " (" + errorThrown + ")");
            if (onFail) { onFail(); }
        },
        complete: function (xhr, textStatus) {
            if (onDone) { onDone(); }
        }
    };

    console.log("url: " + url);
    console.log(params);

    $.ajax(params);
}



function parseOkResponse(url, data, onWin, onFail) {
    if (data.success) {
        console.log("[win] - [ " + url + " ] - " + data.mesg);
        onWin(data.data);
    } else {
        console.log("[fail] - [ " + url + " ] - " + data.error);
        onFail(data);
    }
}



function shortenText(text, maxlen, showQuestionMark) {
    if (maxlen < text.length) {
        text = text.substr(0, maxlen);
        var pos = text.lastIndexOf(' ');
        text = text.substring(0, pos) + "..." + (showQuestionMark ? "?" : ".");
    }

    return text;
}



function px(n) { return ('' + n + 'px');}



function computeDuration(now, then) {
    var n = now.getTime();
    var t = then.getTime();

    var secsMin = 60;
    var secsHour = 60 * secsMin;
    var secsDay = 24 * secsHour;
    var secsWeek = 7 * secsDay;
    var secsMonth = 30 * secsDay;
    var secsYear = 12 * secsMonth;

    /*
     * Number of seconds separating these two values.
     */
    var diff = (n - t) / 1000;

    var unit = "";
    var dur = 0;
    var prefix = "";

    if (diff < secsMin) {
        return "just now";
    }

    if (diff < secsHour) {
        unit = "min";
        dur = diff / secsMin;
    } else if (diff < secsDay) {
        unit = "hour";
        dur = diff / secsHour;
    } else if (diff < secsWeek) {
        unit = "day";
        dur = diff / secsDay;
    } else if (diff < secsMonth) {
        unit = "week";
    } else {
        unit = "month";
        dur = diff / secsMonth;

        if (dur > 3) {
            dur = 3;
            prefix = "> ";
        }
    }

    /*
     * Make plural as necessary.
     */
    if (dur > 1) {
        unit = unit + "s";
    }

    dur = new Number(dur).toFixed(0);

    return prefix + dur + "&nbsp;" + unit + "&nbsp;ago";
}



function computePercentage(numerator, denominator, precision = 1) {
    const scale = precision * 10;
    let readablePct = (100 * numerator) / denominator;
    let roundedPct = Math.round(scale * readablePct) / scale;
    return roundedPct;
}



function createDropZone($dz, $info, $uploadButton, formDataHandler) {
    const progressHandler = function (ev) {
        console.log(ev);
    };
    const loadStartHandler = function (ev) {
        console.log(ev);
    };
    const loadHandler = function (ev) {
        console.log(ev);
    };
    const errorHandler = function (ev) {
        console.log(ev);
    };
    const abortHandler = function (ev) {
        console.log(ev);
    };



    var pendingFileList;



    function uploadFiles(fileList) {
        var fd = new FormData();
        $.each(fileList, function (idx, file) {
            var filename = file['name'];
            var size = file['size'];
            var niceSize = fileSize(size);
            var type = file['type'];

            clog("  >>> " + filename + ": " + niceSize + " (" + type + ")");

            fd.append("file-" + idx, file);
        });

        if ('undefined' !== typeof formDataHandler) {



            formDataHandler(fd); // MEAT <==



        } else {
            var xhr = new XMLHttpRequest();
            xhr.upload.addEventListener("loadstart", loadStartHandler, false);
            xhr.upload.addEventListener("progress", progressHandler, false);
            xhr.addEventListener("load", loadHandler, false);
            xhr.addEventListener("error", errorHandler, false);
            xhr.addEventListener("abort", abortHandler, false);

            var url = "upload"; // FIXME
            xhr.open("POST", url, true);
            xhr.send(fd);
        }
    }



    function highlightDropzone($info) {
        $info.css("border", "4px solid cyan");
        $info.css("background-color", "rgba(0, 255, 255, 0.25)");
        $info.find('div:first-child')
             .html("Let go at any time to drop these files!")
             .css("color", "white")
             .css("font-weight", "900");
    }



    function resetDropzone($info) {
        $info.css("border", "4px dashed rgba(0, 255, 255, 0.5)");
        $info.css("background-color", "rgba(0, 0, 0, 0)");
        $info.find('div:first-child')
             .html("Drag and drop files anywhere on this page!")
             .css("color", "#ccc")
             .css("font-weight", "100");
    }



    function fileSize(size) {
        var i = Math.floor(Math.log(size) / Math.log(1024));
        return Math.ceil(size / Math.pow(1024, i)).toFixed(0) * 1 + ' ' + ['B', 'kB', 'MB', 'GB', 'TB'][i];
    }



    function updateFileList($info, fileList) {
        var tableContents = "";

        $.each(fileList, function (idx, file) {
            var filename = file['name'];
            var size = file['size'];
            var niceSize = fileSize(size);
            var type = file['type'];

            var entry = '<tr class="vertwo-plite-dz-file-entry">'
                + '<td>' + '<input type="checkbox" readonly/>' + '</td>'
                + '<td>' + filename + '</td>'
                + '<td>' + niceSize + '</td>'
                + '<td>' + type + '</td>'
                + '</tr>\n';
            tableContents = tableContents + entry;
        });

        var table = '<table class="vertwo-plite-dz-file-list">\n'
            + tableContents
            + '</table>';

        $info.html(table);
    }



    $uploadButton.on("click", (ev) => {
        uploadFiles(pendingFileList);
    });



    $dz.on("drop", (ev) => {
        ev.stopPropagation();
        ev.preventDefault();

        console.log("drop");
        console.log(ev);

        resetDropzone($info);

        const fileList = ev.dataTransfer.files;

        updateFileList($info, fileList);

        pendingFileList = [];

        $.each(fileList, function (idx, file) {
            var filename = file['name'];
            var size = file['size'];
            var niceSize = fileSize(size);
            var type = file['type'];

            clog("  -> " + filename + ": " + niceSize + " (" + type + ")");

            pendingFileList.push(file);
        });

        //uploadFiles(fileList);
    });
    $dz.on("dragover", (ev) => {
        ev.stopPropagation();
        ev.preventDefault();

        highlightDropzone($info);

        // Style the drag-and-drop as a "copy file" operation.
        ev.dataTransfer.dropEffect = 'copy';
    });
    $dz.on("dragend", (ev) => {
        ev.stopPropagation();
        ev.preventDefault();
        resetDropzone($info);
    });
    $dz.on("dragleave", (ev) => {
        ev.stopPropagation();
        ev.preventDefault();
        resetDropzone($info);
    });
}



//
// NOTE - Use the createDropZone() function like this:
//
// const $dz = $(document.body);
// var $dzInfo = $('#dz-info');
// var $dzUploadButton = $('#dz-upload-button');
//
//
// createDropZone($dz, $dzInfo, $dzUploadButton);
//
// NOTE - Install custom event handlers (for the XHR events) as desired, with additional parameters
//
