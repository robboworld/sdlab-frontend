/**
 * SDLab core methods
 */

/**
 * Api call
 * @param  string  method
 * @param  array   params
 * @param  func    callback
 * @return jqxhr object
 */
function coreAPICall(method, params, callback){
    return rq = $.ajax({
        url: '?q=api',
        method: 'get',
        data: {
            method: method,
            params: params
        },
        success: function(result, status, jqxhr){
            callback(result, status, jqxhr);
        },
        error: function(){
            console.log('API Call error: Transport error');
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

/**
 * Graph data class
 * @param   data
 */
function Graph(data) {
    this.data = data;
    this.getMinValue = function(){
        var min = null;
        $.each(this.data, function(si, sensor){
            $.each(sensor.data, function(pi, point){
                var p = parseFloat(point[1]);
                if(p < min || min == null) min = p;
            });
        });
        return min;
    };
    this.getMaxValue = function(){
        var max = null;
        $.each(this.data, function(si, sensor){
            $.each(sensor.data, function(pi, point){
                var p = parseFloat(point[1]);
                if(p > max || max == null) max = p;
            });
        });
        return max;
    };
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