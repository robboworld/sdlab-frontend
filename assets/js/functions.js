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
 * @param  string     holder selector
 * @param  message    html message text
 * @param  autoclose  close timeout
 */
function setInterfaceError(holder, message, autoclose){
    $(holder).html('<div class="alert alert-danger">' + message + '</div>');
    var error = $(holder).find('div.alert');
    // Auto close message
    if(typeof autoclose === 'number'){
        setTimeout(function(){
            error.fadeOut(400, function(){
                error.remove();
            });
        }, autoclose);
    }
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
