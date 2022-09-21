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



    function highlightDropzone($feedback) {
        $feedback.css("border", "1px solid cyan");
        $feedback.css("background-color", "rgba(0, 255, 255, 0.1)");
        $feedback.find('div:first-child')
                 .html("Let go at any time to drop files here.")
                 .css("color", "white")
    }



    function resetDropzone($feedback) {
        $feedback.css("border", "1px dashed rgba(0, 255, 255, 0.5)");
        $feedback.css("background-color", "rgba(0, 0, 0, 0)");
        $feedback.find('div:first-child')
                 .html("Drag and drop files here for uploading!")
                 .css("color", "#ccc")
    }



    function fileSize(size) {
        var i = Math.floor(Math.log(size) / Math.log(1024));
        return Math.ceil(size / Math.pow(1024, i)).toFixed(0) * 1 + ' ' + ['B', 'kB', 'MB', 'GB', 'TB'][i];
    }



    function updateFileList($tbody, fileList) {
        var tableRows = "";

        $.each(fileList, function (idx, file) {
            var filename = file['name'];
            var size = file['size'];
            var niceSize = fileSize(size);
            var type = file['type'];

            var entry = '<tr class="vertwo-plite-dz-file-entry">\n'
                + '<td>' + '<input type="checkbox" readonly/>' + '</td>\n'
                + '<td>' + filename + '</td>\n'
                + '<td>' + type + '</td>\n'
                + '<td>' + niceSize + '</td>\n'
                + '</tr>\n';
            tableRows = tableRows + entry;
        });

        $tbody.html(tableRows);
    }



    const dropzoneHtml = `
        <div id="dz-info" class="vertwo-plite-dz-info">
            <div>
                Drag and drop files here to select them for upload.
            </div>
        </div>

        <div id="dz-list" class="vertwo-plite-dz-list">
            <div>
                <div>
                    <div class="vertwo-plite-dz-file-meta-header">Files to upload <span
                                class="vertwo-plite-dz-file-meta">(1 Total, 6.0 MB)</span></div>
                    All files here will be uploaded.
                </div>
                <div class="clear"></div>
            </div>
            <div>
                <table>
                    <thead>
                    <th><input type="checkbox" readonly/></th>
                    <th>Name</th>
                    <th>Type</th>
                    <th>Size</th>
                    </thead>
                    <tbody>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="separator"></div>
        <div>
            <button id="dz-upload-button" class="reset_pass">Upload Files</button>
        </div>
`;

    $info.html(dropzoneHtml);

    $uploadButton.on("click", (ev) => {
        uploadFiles(pendingFileList);
    });


    const $feedback = $info.find("div.vertwo-plite-dz-info");
    const $list = $info.find("div.vertwo-plite-dz-list > div:nth-child(2)");


    $dz.on("drop", (ev) => {
        ev.stopPropagation();
        ev.preventDefault();

        console.log("drop");
        console.log(ev);

        resetDropzone($feedback);

        const fileList = ev.dataTransfer.files;

        $tbody = $list.find("table:first-child > tbody");

        updateFileList($tbody, fileList);

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

        highlightDropzone($feedback);

        // Style the drag-and-drop as a "copy file" operation.
        ev.dataTransfer.dropEffect = 'copy';
    });
    $dz.on("dragend", (ev) => {
        ev.stopPropagation();
        ev.preventDefault();
        resetDropzone($feedback);
    });
    $dz.on("dragleave", (ev) => {
        ev.stopPropagation();
        ev.preventDefault();
        resetDropzone($feedback);
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
