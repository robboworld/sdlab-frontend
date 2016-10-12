/**
 * SDLab core methods
 */

/**
 * Api call
 * @param  string  method
 * @param  array   params
 * @param  func    success callback
 * @param  func    error callback
 * @return jqxhr object
 */
function coreAPICall(method, params, onSuccess, onError){
    return rq = $.ajax({
        url: '?q=api',
        method: 'get',
        data: {
            method: method,
            params: params
        },
        success: function(result, status, jqxhr){
            if (typeof onSuccess === "function") {
                onSuccess(result, status, jqxhr);
            }
        },
        error: function(jqxhr, status, errorThrown){
            console.log('API Call error: Transport error');
            if (typeof onError === "function") {
                onError(jqxhr, status, errorThrown);
            }
        }
    });
}


/**
 * Add error widget to custom place
 * @param  string       holder selector
 * @param  message      html message text
 * @param  type         alert class: success, info, warning, danger or empty (default)
 * @param  append       append or replace all
 * @param  dismissible  show close button
 * @param  autoclose    close timeout
 */
function setInterfaceError(holder, message, type, append, dismissible, autoclose){
    type = (typeof type === "undefined") ? null : (String(type).length ? String(type) : null);
    append = ((typeof append !== "undefined") && append) ? true : false;
    dismissible = ((typeof append !== "undefined") && dismissible) ? true : false;
    // append: 0 - clear-add, 1 - append; 
    // type: null, success, info, warning, danger
    var html = '<div class="alert' + (type !== null ? (' alert-' + type) : '') + (dismissible ? ' alert-dismissible' : '') + '" role="alert"' + '>'
        + (dismissible ? '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>' : '')
        + message
        + '</div>',
        jhtml = $(html);
    if (!append)
        $(holder).empty();
    $(holder).append(jhtml);

    // Auto close message
    if(typeof autoclose === 'number'){
        setTimeout(function(){
            if ($.contains(document.documentElement, jhtml.get(0))){
                jhtml.fadeOut(400, function(){
                    jhtml.remove();
                });
            }
        }, autoclose);
    }
}
function emptyInterfaceError(holder){
    $(holder).empty();
}


var isMobile = {
    Windows: function() {
        return /IEMobile/i.test(navigator.userAgent);
    },
    Android: function() {
        return /Android/i.test(navigator.userAgent);
    },
    BlackBerry: function() {
        return /BlackBerry/i.test(navigator.userAgent);
    },
    iOS: function() {
        return /iPhone|iPad|iPod/i.test(navigator.userAgent);
    },
    any: function() {
        return (isMobile.Android() || isMobile.BlackBerry() || isMobile.iOS() || isMobile.Windows());
    }
};


/**
 * Convert seconds to nanoseconds (nanoseconds used by API)
 * @param   integer  value
 * @return  integer  Number of nanoseconds
 */
function nano(value){
    return value * 1000000000;
}

/*\
|*|
|*|  Base64 / binary data / UTF-8 strings utilities
|*|
|*|  https://developer.mozilla.org/en-US/docs/Web/JavaScript/Base64_encoding_and_decoding
|*|
\*/

/* Array of bytes to base64 string decoding */
function b64ToUint6(nChr){
	return nChr > 64 && nChr < 91 ?
			nChr - 65
			: nChr > 96 && nChr < 123 ?
			nChr - 71
			: nChr > 47 && nChr < 58 ?
			nChr + 4
			: nChr === 43 ?
			62
			: nChr === 47 ?
			63
			:
			0;
}
function base64DecToArr(sBase64, nBlocksSize){
	var sB64Enc = sBase64.replace(/[^A-Za-z0-9\+\/]/g, ""), nInLen = sB64Enc.length,
		nOutLen = nBlocksSize ? Math.ceil((nInLen * 3 + 1 >> 2) / nBlocksSize) * nBlocksSize : nInLen * 3 + 1 >> 2, taBytes = new Uint8Array(nOutLen);
	for(var nMod3, nMod4, nUint24 = 0, nOutIdx = 0, nInIdx = 0; nInIdx < nInLen; nInIdx++){
		nMod4 = nInIdx & 3;
		nUint24 |= b64ToUint6(sB64Enc.charCodeAt(nInIdx)) << 6 * (3 - nMod4);
		if(nMod4 === 3 || nInLen - nInIdx === 1) {
			for(nMod3 = 0; nMod3 < 3 && nOutIdx < nOutLen; nMod3++, nOutIdx++){
				taBytes[nOutIdx] = nUint24 >>> (16 >>> nMod3 & 24) & 255;
			}
			nUint24 = 0;
		}
	}
	return taBytes;
}

/**
 * Is arrays equal
 * @param   array a
 * @param   array b
 * @returns boolean
 */
function arraysEqual(a, b) {
	if (a === b) return true;
	if (a == null || b == null) return false;
	if (a.length != b.length) return false;

	// Arrays must by sorted
	for (var i = 0; i < a.length; ++i) {
		if (a[i] !== b[i]) return false;
	}
	return true;
}

// Only define the SDLab namespace if not defined.
SDLab = window.SDLab || {};

SDLab.Language = {
    strings: {},
    '_': function(key, def) {
        return typeof this.strings[key.toUpperCase()] !== 'undefined' ? this.strings[key.toUpperCase()] : def;
    },
    load: function(object) {
        for (var key in object) {
            this.strings[key.toUpperCase()] = object[key];
        }
        return this;
    },
    format: function(str) {
        var tstr = this._(str);
        var args = Array.prototype.slice.call(arguments, 1);
        return tstr.replace(/{(\d+)}/g, function(match, number) { 
            return typeof args[number] != 'undefined' ? args[number] : match;
        });
    }
};

function downloadData(dataURI, filename, mimeType) {
    var mt = mimeType || "text/plain";

    // try window.MSBlobBuilder
    if (window.MSBlobBuilder) {
        /* Saves a text string as a blob file*/  
        var ie = navigator.userAgent.match(/MSIE\s([\d.]+)/),
            ie11 = navigator.userAgent.match(/Trident\/7.0/) && navigator.userAgent.match(/rv:11/),
            ieEDGE = navigator.userAgent.match(/Edge/g),
            ieVer = (ie ? ie[1] : (ie11 ? 11 : (ieEDGE ? 12 : -1)));

        if (ie && ieVer<10) {
            //console.log("No blobs on IE ver<10");
            return false;
        }

        // atob to base64_decode the data-URI
        var b_data = atob(dataURI.split(',')[1]);
        // Use typed arrays to convert the binary data to a Blob
        var arraybuffer = new ArrayBuffer(b_data.length);
        var view = new Uint8Array(arraybuffer);
        for (var i=0; i<b_data.length; i++) {
            view[i] = b_data.charCodeAt(i) & 0xff;
        }

        // The BlobBuilder API has been deprecated in favour of Blob, but older
        // browsers don't know about the Blob constructor
        // IE10 also supports BlobBuilder, but since the `Blob` constructor
        // also works, there's no need to add `MSBlobBuilder`.
        var dataFileAsBlob = new Blob([arraybuffer], {type: 'application/octet-stream'});
        //var dataFileAsBlob = new MSBlobBuilder();
        //dataFileAsBlob.append(dataURI);

        if (ieVer>-1) {
            return window.navigator.msSaveBlob(dataFileAsBlob, filename);
        } else {
            return false
            // try a.download method next?
        }
    }

    // try use a.download prop:
    //build download link:
    var a = document.createElement("a");
    a.href = dataURI;
    if ('download' in a) {
        a.setAttribute("download", filename);
        a.style.display = "none";
        a.innerHTML = "downloading...";
        document.body.appendChild(a);
        setTimeout(function() {
            var e = document.createEvent("MouseEvents");
            e.initMouseEvent("click", true, false, window, 0, 0, 0, 0, 0, false, false, false, false, 0, null);
            a.dispatchEvent(e);
            document.body.removeChild(a);
        }, 100);
        return true;
    }

    //try iframe dataURL download:
    var f = document.createElement("iframe");
    document.body.appendChild(f);
    f.src = "data:" + (mt ? mt : "application/octet-stream") + (window.btoa ? ";base64" : "") + "," + (window.btoa ? window.btoa : escape)(dataURI);
    setTimeout(function() {
        document.body.removeChild(f);
    }, 400);

    return true;
}

function formatDate(date, format, utc){
    var MMMM = ["\x00", "January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];
    var MMM = ["\x01", "Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];
    var dddd = ["\x02", "Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday"];
    var ddd = ["\x03", "Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat"];
    function ii(i, len) { var s = i + ""; len = len || 2; while (s.length < len) s = "0" + s; return s; }

    var y = utc ? date.getUTCFullYear() : date.getFullYear();
    format = format.replace(/(^|[^\\])yyyy+/g, "$1" + y);
    format = format.replace(/(^|[^\\])yy/g, "$1" + y.toString().substr(2, 2));
    format = format.replace(/(^|[^\\])y/g, "$1" + y);

    var M = (utc ? date.getUTCMonth() : date.getMonth()) + 1;
    format = format.replace(/(^|[^\\])MMMM+/g, "$1" + MMMM[0]);
    format = format.replace(/(^|[^\\])MMM/g, "$1" + MMM[0]);
    format = format.replace(/(^|[^\\])MM/g, "$1" + ii(M));
    format = format.replace(/(^|[^\\])M/g, "$1" + M);

    var d = utc ? date.getUTCDate() : date.getDate();
    format = format.replace(/(^|[^\\])dddd+/g, "$1" + dddd[0]);
    format = format.replace(/(^|[^\\])ddd/g, "$1" + ddd[0]);
    format = format.replace(/(^|[^\\])dd/g, "$1" + ii(d));
    format = format.replace(/(^|[^\\])d/g, "$1" + d);

    var H = utc ? date.getUTCHours() : date.getHours();
    format = format.replace(/(^|[^\\])HH+/g, "$1" + ii(H));
    format = format.replace(/(^|[^\\])H/g, "$1" + H);

    var h = H > 12 ? H - 12 : H == 0 ? 12 : H;
    format = format.replace(/(^|[^\\])hh+/g, "$1" + ii(h));
    format = format.replace(/(^|[^\\])h/g, "$1" + h);

    var m = utc ? date.getUTCMinutes() : date.getMinutes();
    format = format.replace(/(^|[^\\])mm+/g, "$1" + ii(m));
    format = format.replace(/(^|[^\\])m/g, "$1" + m);

    var s = utc ? date.getUTCSeconds() : date.getSeconds();
    format = format.replace(/(^|[^\\])ss+/g, "$1" + ii(s));
    format = format.replace(/(^|[^\\])s/g, "$1" + s);

    var f = utc ? date.getUTCMilliseconds() : date.getMilliseconds();
    format = format.replace(/(^|[^\\])fff+/g, "$1" + ii(f, 3));
    f = Math.round(f / 10);
    format = format.replace(/(^|[^\\])ff/g, "$1" + ii(f));
    f = Math.round(f / 10);
    format = format.replace(/(^|[^\\])f/g, "$1" + f);

    var T = H < 12 ? "AM" : "PM";
    format = format.replace(/(^|[^\\])TT+/g, "$1" + T);
    format = format.replace(/(^|[^\\])T/g, "$1" + T.charAt(0));

    var t = T.toLowerCase();
    format = format.replace(/(^|[^\\])tt+/g, "$1" + t);
    format = format.replace(/(^|[^\\])t/g, "$1" + t.charAt(0));

    var tz = -date.getTimezoneOffset();
    var K = utc || !tz ? "Z" : tz > 0 ? "+" : "-";
    if (!utc)
    {
        tz = Math.abs(tz);
        var tzHrs = Math.floor(tz / 60);
        var tzMin = tz % 60;
        K += ii(tzHrs) + ":" + ii(tzMin);
    }
    format = format.replace(/(^|[^\\])K/g, "$1" + K);

    var day = (utc ? date.getUTCDay() : date.getDay()) + 1;
    format = format.replace(new RegExp(dddd[0], "g"), dddd[day]);
    format = format.replace(new RegExp(ddd[0], "g"), ddd[day]);

    format = format.replace(new RegExp(MMMM[0], "g"), MMMM[M]);
    format = format.replace(new RegExp(MMM[0], "g"), MMM[M]);

    format = format.replace(/\\(.)/g, "$1");

    return format;
};
