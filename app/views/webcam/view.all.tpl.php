<? 
// todo: can start and stop access check, edit only for admin or owner
$show_action = true;

$cams = array();
if(isset($this->view->content->list))
{
	foreach($this->view->content->list as $item)
	{
		$cams[] = array(
				'id'     => (int)$item->Index,
				'stream' => (!empty($item->stream) && ($item->stream->Stream >= 0)) ? (int)$item->stream->Stream : -1
		);
	}
}
?>
<script type="text/javascript">
	var SDWebCam = function(id,stream,paused,imageNr,finished){
		this.id = (((typeof id === "number") && (id >= 0)) ? id : -1);  // Device id
		this.stream = (((typeof stream === "number") && (stream >= 0)) ? stream : -1);  // Stream id
		this.paused = (paused ? true : false);
		this.imageNr = (typeof imageNr === "number") ? imageNr : 0;
		this.finished = finished || []; // References to img objects which have finished downloading
	};
	var SDWebCams = function(){
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
				this.cams.push(new SDWebCam(ids[i].id, ids[i].stream, true));
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
			//img.onclick = imageOnclick;
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
	};

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

	$(document).ready(function(){
		var sdwc = new SDWebCams();
		var opts = {cams:<? echo json_encode($cams); ?>/*,delay:1000*/};
		sdwc.init(opts);

		//sdwc.setStreamImg();

		// First show
		var autostart = false;
		for(var i=0,len=opts.cams.length;i<len;i++){
			if (opts.cams[i].stream>=0){
				if(autostart){
					// Start all
					//sdwc.start(opts.cams[i].id); //xxx: always shows browser status bar with loading images

					// Timeout for hide loading image messages
					(function(ind){
						setTimeout(function(){sdwc.start(opts.cams[ind].id);}, 100);
					})(i);
				}
				else{
					// Show snapshot all
					sdwc.snapshot(opts.cams[i].id);
				}
			}
		}

		// Bind btns
		$('.webcam-stream-on').click(function(){
			sdwc.on($(this).data('devid'));
		});
		$('.webcam-stream-off').click(function(){
			sdwc.off($(this).data('devid'));
		});
		$('.webcam-stream-onall').click(function(){
			sdwc.on(-1);
		});
		$('.webcam-stream-offall').click(function(){
			sdwc.off(-1);
		});
		$('.webcam-stream-start').click(function(){
			sdwc.start($(this).data('devid'));
		});
		$('.webcam-stream-stop').click(function(){
			sdwc.stop($(this).data('devid'));
		});
	});

	// TODO: onclick picture - stop/pause
	function imageOnclick() { // Clicking on the image will pause the stream
		paused = !paused;
		if (!paused) createImageLayer();
	}
</script>
<div class="col-md-12">
	<div class="row">
		<div class="col-md-6">
			<h1><? echo L::webcam_TITLE_ALL; ?></h1>
		</div>
	</div>
	<div>
		<button type="button" class="webcam-stream-onall btn btn-primary"><span class="glyphicon glyphicon-off">&nbsp;</span><? echo L::webcam_ON_ALL; ?></button>
		<button type="button" class="webcam-stream-offall btn btn-default"><span class="glyphicon glyphicon-ban-circle">&nbsp;</span><? echo L::webcam_OFF_ALL; ?></button>
	</div>
	<form id="sdform" method="post" action="?<? print $_SERVER['QUERY_STRING']?>" >
		<input type="hidden" name="form-id" value="action-webcam-form"/>
		<input type="hidden" name="dev_id" value="-1"/>
		<? if(isset($this->view->content->list )) : ?>

		<table class="table">
			<thead>
			<tr>
				<td>
					<label><? echo L::webcam_INDEX; ?></label>
				</td>
				<td>
					<label><? echo L::webcam_NAME; ?></label>
				</td>
				<td>
					<label><? echo L::webcam_DEVICE_NAME; ?></label>
				</td>
				<td>
					<label><? echo L::webcam_IMAGE; ?></label>
				</td>
				<td class="text-right">
					<label><? echo L::ACTION; ?></label>
				</td>
			</tr>
			</thead>
			<tbody>
			<? foreach($this->view->content->list as $item) : 
				$streamed = (!empty($item->stream) && ($item->stream->Stream >= 0)); ?>

				<tr class="row-webcam <? echo (!$streamed ? 'warning' : 'success'); ?>">
					<td>
						<? echo (int)$item->Index; ?>
					</td>
					<td>
						<a href="/?q=webcam/view/<? print $item->Index; ?>">
							<? print htmlspecialchars($item->Name, ENT_QUOTES, 'UTF-8'); ?>
						</a>
					</td>
					<td>
						<? print htmlspecialchars($item->Device, ENT_QUOTES, 'UTF-8'); ?>
					</td>
					<td>
						<div id="webcam_<? echo (int)$item->Index; ?>" class="stream-wrapper" style="position:relative;overflow:hidden;margin:2px 0;<? echo $streamed ? "width:320px;height:240px;" : ""; ?>">
							<img <? echo $streamed ? ('id="mjpgstream'.$item->stream->Stream.'"') : ''; ?>/>
						</div>
						<? if ($streamed) : ?>
						<button type="button" class="webcam-stream-start btn btn-xs btn-success" data-devid="<? echo (int)$item->Index; ?>"><span class="glyphicon glyphicon-play"></span></button>
						<button type="button" class="webcam-stream-stop btn btn-xs btn-danger" data-devid="<? echo (int)$item->Index; ?>"><span class="glyphicon glyphicon-stop"></span></button>
						<button type="button" class="webcam-stream-step btn btn-xs btn-primary" data-devid="<? echo (int)$item->Index; ?>" style="display:none;"><span class="glyphicon glyphicon-picture"></span></button>
						<? endif; ?>
					</td>
					<td class="text-right">
						<button type="button" class="webcam-stream-on btn btn-sm btn-primary" data-devid="<? echo (int)$item->Index; ?>"><span class="glyphicon glyphicon-off"></span></button>
						<button type="button" class="webcam-stream-off btn btn-sm btn-default" data-devid="<? echo (int)$item->Index; ?>"><span class="glyphicon glyphicon-ban-circle"></span></button>
					</td>
				</tr>
			<? endforeach; ?>

			</tbody>
			<? if(empty($this->view->content->list )) : ?>

			<tfoot>
			<tr>
				<td colspan="5">
					<div class="alert alert-danger">
						<span><? echo L::webcam_CAMERAS_NOT_FOUND; ?></span>
					</div>
				</td>
			</tr>
			</tfoot>
			<? endif; ?>

		</table>
		<? else : ?>
		<div class="alert alert-danger">
			<span><? echo L::webcam_FATAL_ERROR_LIST; ?></span>
		</div>
		<? endif; ?>

	</form>
</div>
