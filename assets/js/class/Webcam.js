function WebCam(id,stream,paused,imageNr,finished){
	this.id = (((typeof id === "number") && (id >= 0)) ? id : -1);  // Device id
	this.stream = (((typeof stream === "number") && (stream >= 0)) ? stream : -1);  // Stream id
	this.paused = (paused ? true : false);
	this.imageNr = (typeof imageNr === "number") ? imageNr : 0;
	this.finished = finished || []; // References to img objects which have finished downloading
};
function WebCams(){
	this.form              = null;
	this.form_dev_input    = "dev_id";
	this.container_prefix  = "webcam_";
	this.delay             = 0; //ms
	this.cams              = [];

	this.init = function(opts){
		opts = opts || {};
		this.form             = opts.form || document.getElementById('sdform');
		this.form_dev_input   = opts.form_dev_input || "dev_id";
		this.container_prefix = opts.container_prefix || "webcam_";
		this.delay            = opts.delay || 0;
		var ids               = opts.cams || [];
		this.cams = [];
		for(var i=0,len=ids.length;i<len;i++){
			this.cams.push(new WebCam(ids[i].id, ids[i].stream, true));
		}
	};

	this.on = function(devid){
		this.form.action = '?q=webcam/start' + ((devid==-1)?'all':'');
		this.form.elements[this.form_dev_input].value = devid;
		this.form.submit();
	};

	this.off = function(devid){
		this.form.action = '?q=webcam/stop' + ((devid==-1)?'all':'');
		this.form.elements[this.form_dev_input].value = devid;
		this.form.submit();
	};


	this.start = function(devid){
		var i = this.getCamObjIndex(devid);
		if((i<0) || (this.cams[i].id<0)) return false;

		if(this.cams[i].paused){
			this.cams[i].paused = false;
			var img = this._createImageLayer(i, this.container_prefix + this.cams[i].id);
			if(!img) return false;
		}
		return true;
	};

	this.stop = function(devid){
		var i = this.getCamObjIndex(devid);
		if((i<0) || (this.cams[i].id<0)) return false;

		this.cams[i].paused = true;
	};

	this.getCamObj = function(devid){
		if(devid<0) return -1;
		for(var i=0,len=this.cams.length;i<len;i++){
			if(this.cams[i].id == devid){
				return this.cams[i];
			}
		}
		return -1;
	};

	this.getCamObjIndex = function(devid){
		if(devid<0) return -1;
		for(var i=0,len=this.cams.length;i<len;i++){
			if(this.cams[i].id == devid){
				return i;
			}
		}
		return -1;
	};

	this._createImageLayer = function(i, parentId, single){
		var img = new Image();
		img.style.position = "absolute";
		img.style.zIndex = -1;
		img.camsObj = this;
		img.camId = i;
		img.camSnapshot = (single === true ? true : false);
		img.onload = imageCamOnload;
		//img.onclick = imageCamOnclick;
		img.src = window.location.protocol + "//" + window.location.hostname + ":8090?action=snapshot_" + this.cams[i].stream + "&n=" + (++this.cams[i].imageNr);
		var webcam = document.getElementById(parentId);
		webcam.insertBefore(img, webcam.firstChild);
		return img;
	};

	this.snapshot = function(devid){
		var i = this.getCamObjIndex(devid);
		if((i<0) || (this.cams[i].id<0)) return false;
		// Stop playing
		if(!this.cams[i].paused){
			this.cams[i].paused = true;
		}
		var img = this._createImageLayer(i, this.container_prefix + this.cams[i].id, true);
		if(!img) return false;
		return img;
	};

	// Streaming src mode
	this.setStreamImg = function(ids){
		var cams = ids || this.cams;
		for(var i=0,len=cams.length;i<len;i++){
			var img = document.getElementById('mjpgstream'+cams[i].stream);
			if(img){
				img.src = "";
				img.src = window.location.protocol + "//" + window.location.hostname + ":8090?action=stream_"+cams[i].stream;
			}
		}
	};
}

// Two layers are always present (except at the very beginning), to avoid flicker
function imageCamOnload(){
	if (typeof this.camsObj == "undefined"
		|| typeof this.camId == "undefined"
		|| typeof this.camsObj.cams[this.camId] == "undefined")
		return;

	var cam = this.camsObj.cams[this.camId];

	this.style.zIndex = cam.imageNr; // Image finished, bring to front!
	while (1 < cam.finished.length) {
		var del = cam.finished.shift(); // Delete old image(s) from document
		del.src = ""; // Fix memory leak?
		del.parentNode.removeChild(del);
	}
	cam.finished.push(this);
	if (!this.camSnapshot && !cam.paused){
		if(this.camsObj.delay>0){
			var self = this;
			setTimeout(function(){self.camsObj._createImageLayer(self.camId, self.camsObj.container_prefix + cam.id);}, this.camsObj.delay); // Timeout for hide loading image message
		}
		else{
			this.camsObj._createImageLayer(this.camId, this.camsObj.container_prefix + cam.id);
		}
	}
}

// TODO: onclick picture - stop/pause?
/*
// Clicking on the image will pause the stream
function imageCamOnclick(){
	if (typeof this.camsObj == "undefined"
		|| typeof this.camId == "undefined"
		|| typeof this.camsObj.cams[this.camId] == "undefined")
		return;

	var cam = this.camsObj.cams[this.camId];

	if(!cam.paused){
		this.camsObj.stop(cam.id);
	} else {
		this.camsObj.start(cam.id);
	}
}
*/