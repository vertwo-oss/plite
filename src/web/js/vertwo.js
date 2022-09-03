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
