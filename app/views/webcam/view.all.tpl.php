<? 
// todo: can start and stop access check, edit only for admin or owner
$show_action = true;

$stream_ids = array();
if(isset($this->view->content->list))
{
	foreach($this->view->content->list as $item)
	{
		$stream_ids[] = array('id' => (int)$item->Index, 'stream' => (!empty($item->stream) && ($item->stream->Stream >= 0)) ? (int)$item->stream->Stream : -1);
	}
}
?>
<script type="text/javascript">
<!--
	var SDWebCam = function(id,stream,paused,imageNr,finished)
		this.id = id || -1;  // Device id
		this.stream = stream || -1;  // Stream id
		this.paused = paused || true;
		this.imageNr = imageNr || 0;
		this.finished = finished || []; // References to img objects which have finished downloading
	}
	var SDWebCams = {
		form:            null;
		form_dev_input:  "dev_id";
		container_prefix:  "webcam_";
		cams:            [];

		init: function(opts){
			opts = opts || {};
			this.form           = opts.form || document.getElementById('sdform');
			this.form_dev_input = opts.form_dev_input || "dev_id";
			this.container_prefix = opts.container_prefix || "webcam_";
			var ids             = opts.cams || [];
			this.cams = [];
			for(var i=0,len=ids.length;i<len;i++){
				this.cams.push(new SDWebCam(ids[i].id, ids[i].stream));
			}
		};

		on: function(devid){
			this.form.action = '?q=webcam/start' + ((devid==-1)?'all':'');
			this.form.elements[this.form_dev_input].value = devid;
			this.form.submit();
		};

		off: function(devid){
			this.form.action = '?q=webcam/stop' + ((devid==-1)?'all':'');
			this.form.elements[this.form_dev_input].value = devid;
			this.form.submit();
		};


		start: function(devid){
			var i = this.getCamObjIndex(devid);
			if((i<0) || (this.cams[i].id<0)) return false;

			if(this.cam[i].paused){
				this.cam[i].paused = !this.cam[i].paused;
				var img = this._createImageLayer(i, this.container_prefix + this.cam[i].id);
				if (!img) return false;
			}
			return true;
		};

		stop(): function(devid){
			var i = this.getCamObjIndex(devid);
			if((i<0) || (this.cams[i].id<0)) return false;

			this.cam[i].paused = true;
		};

		getCamObj: function(devid){
			if(devid<0) return -1;
			for(var i=0,len=cams.length;i<len;i++){
				if(cams[i].id == devid){
					return cams[i];
				}
			}
			return -1;
		}

		getCamObjIndex: function(devid){
			if(devid<0) return -1;
			for(var i=0,len=cams.length;i<len;i++){
				if(cams[i].id == devid){
					return i;
				}
			}
			return -1;
		}

		_createImageLayer: function(i, parentId) {
			var img = new Image();
			img.style.position = "absolute";
			img.style.zIndex = -1;
			img.camsObj = this;
			img.camId = i;
			img.onload = imageCamOnload;
			//img.onclick = imageOnclick;
			img.src = window.location.protocol + "//" + window.location.hostname + ":8090?action=snapshot_" + this.cam[i].stream+"&n=" + (++this.cam[i].imageNr);
			var webcam = document.getElementById(parentId);
			webcam.insertBefore(img, webcam.firstChild);
			return img;
		}

		// TODO: first load snapshot all
		// TODO: start onload?

		snapshot: function(ids){
			var cams = ids || this.cams;
			for(var i=0,len=cams.length;i<len;i++){
				var img = document.getElementById('mjpgstream'+cams[i]);
				if(img){
					img.src = "";
					img.src = window.location.protocol + "//" + window.location.hostname + ":8090?action=stream_"+cams[i];
				}
			}
		}

		setStreamImg: function(ids){
			var cams = ids || this.cams;
			for(var i=0,len=cams.length;i<len;i++){
				var img = document.getElementById('mjpgstream'+cams[i]);
				if(img){
					img.src = "";
					img.src = window.location.protocol + "//" + window.location.hostname + ":8090?action=stream_"+cams[i];
				}
			}
		}
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
			del.parentNode.removeChild(del);
		}
		cam.finished.push(this);
		if (!cam.paused) this.camsObj._createImageLayer(this.camId, this.camsObj.container_prefix + cam.id);
	}

	$(document).ready(function(){
		var sdwc = new SDWebCams();
		sdwc.init({cams:<? echo json_encode($stream_ids); ?>});
		sdwc.setStreamImg();

		//sdwc.start(all)

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
	});

	// TODO: onclick picture - stop/pause
	function imageOnclick() { // Clicking on the image will pause the stream
		paused = !paused;
		if (!paused) createImageLayer();
	}
//-->
</script>
<div class="col-md-12">
	<div class="row">
		<div class="col-md-6">
			<h1><? echo L::webcam_TITLE_ALL; ?></h1>
		</div>
	</div>
	<div>
		<button type="button" class="webcam-stream-startall btn btn-success"><span class="glyphicon glyphicon-off">&nbsp;</span><? echo L::webcam_START_ALL; ?></button>
		<button type="button" class="webcam-stream-stopall btn btn-danger"><span class="glyphicon glyphicon-ban-circle">&nbsp;</span><? echo L::webcam_STOP_ALL; ?></button>
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
			<? foreach($this->view->content->list as $item) : ?>

				<tr class="row-webcam <? echo (empty($item->stream) || ($item->stream->Stream < 0)) ? 'warning' : 'success'; ?>">
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
						<div id="webcam_<? echo (int)$item->Index; ?>" class="stream-wrapper">
							<img <? echo (!empty($item->stream) && ($item->stream->Stream >= 0)) ? ('id="mjpgstream'.$item->stream->Stream.'"') : ''; ?>/>
						</div>
						<button type="button" class="webcam-stream-start btn btn-sm btn-success" data-devid="<? echo (int)$item->Index; ?>" style="display:none;"><span class="glyphicon glyphicon-play"></span></button>
						<button type="button" class="webcam-stream-stop btn btn-sm btn-danger" data-devid="<? echo (int)$item->Index; ?>" style="display:none;"><span class="glyphicon glyphicon-stop"></span></button>
						<button type="button" class="webcam-stream-stop btn btn-sm btn-primary" data-devid="<? echo (int)$item->Index; ?>" style="display:none;"><span class="glyphicon glyphicon-picture"></span></button>
					</td>
					<td class="text-right">
						<button type="button" class="webcam-stream-start btn btn-sm btn-success" data-devid="<? echo (int)$item->Index; ?>"><span class="glyphicon glyphicon-off"></span></button>
						<button type="button" class="webcam-stream-stop btn btn-sm btn-danger" data-devid="<? echo (int)$item->Index; ?>"><span class="glyphicon glyphicon-ban-circle"></span></button>
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