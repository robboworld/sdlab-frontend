<?
// todo: can start and stop access check, edit only for admin or owner
$show_action = true;

$cams = array();
if(isset($this->view->content->item))
{
	$cams[] = array(
			'id'     => (int)$this->view->content->item->Index,
			'stream' => (!empty($this->view->content->item->stream) && ($this->view->content->item->stream->Stream >= 0)) ? (int)$this->view->content->item->stream->Stream : -1
	);
}
$streamed = (!empty($this->view->content->item->stream) && ($this->view->content->item->stream->Stream >= 0));
?>
<? if (isset($this->view->content->item)) : ?>
<script type="text/javascript">
	$(document).ready(function(){
		var sdwc = new WebCams();
		var opts = {cams:<? echo json_encode($cams); ?>,delay:30};
		sdwc.init(opts);

		//sdwc.setStreamImg();

		// First show snapshot/play
		var autoplay = false;
		for(var i=0,len=sdwc.cams.length;i<len;i++){
			if (sdwc.cams[i].stream>=0){
				if(autoplay){
					// Start all
					//sdwc.start(sdwc.cams[i].id); //xxx: always shows browser status bar with loading images

					// Timeout for hide loading image browser messages
					(function(ind){
						setTimeout(function(){sdwc.start(sdwc.cams[ind].id);}, 100);
					})(i);
				}
				else{
					// Show snapshot all
					sdwc.snapshot(sdwc.cams[i].id);
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
		$('.webcam-stream-start').click(function(){
			sdwc.start($(this).data('devid'));
		});
		$('.webcam-stream-stop').click(function(){
			sdwc.stop($(this).data('devid'));
		});
		$('.webcam-stream-step-forward').click(function(){
			sdwc.snapshot($(this).data('devid'));
		});
		//$('.webcam-stream-fast-forward').click(function(){
		//	sdwc.start($(this).data('devid'), 200);
		//});
	});
</script>
<? endif; ?>
<div class="col-md-12">
	<div class="row">
		<div class="col-md-6">
			<h1><? echo L::webcam_TITLE; ?></h1>
		</div>
	</div>

	<div class="row">
		<div class="col-md-12">
			<a href="/?q=webcam/view" class="btn btn-sm btn-default">
				<span class="glyphicon glyphicon-chevron-left">&nbsp;</span><? echo L::webcam_TITLE_ALL; ?>
			</a>
		</div>
	</div>
	<br/>

	<? if (isset($this->view->content->item)) : ?>

	<form id="sdform" method="post" action="?<? echo htmlentities($_SERVER['QUERY_STRING'], ENT_COMPAT | ENT_HTML401, 'UTF-8'); ?>" class="form-horizontal">
		<input type="hidden" name="form-id" value="action-webcam-form"/>
		<input type="hidden" name="dev_id" value="-1"/>
		<input type="hidden" name="destination" value="<? echo 'webcam/view/' . (int)$this->view->content->item->Index; ?>"/>

		<div class="btn-toolbar" role="toolbar">
			<div class="btn-group btn-group-sm" role="group">
				<button type="button" class="webcam-stream-on btn btn-primary" data-devid="<? echo (int)$this->view->content->item->Index; ?>"><span class="glyphicon glyphicon-off"></span></button>
				<button type="button" class="webcam-stream-off btn btn-default" data-devid="<? echo (int)$this->view->content->item->Index; ?>"><span class="glyphicon glyphicon-ban-circle"></span></button>
			</div>
			<? if ($streamed) : ?>

			<div class="btn-group btn-group-sm btn-group-streamplay" role="group">
				<button type="button" class="webcam-stream-start btn btn-xs btn-success" data-devid="<? echo (int)$this->view->content->item->Index; ?>"><span class="glyphicon glyphicon-play"></span></button>
				<button type="button" class="webcam-stream-step-forward btn btn-xs btn-success" data-devid="<? echo (int)$this->view->content->item->Index; ?>"><span class="glyphicon glyphicon-step-forward"></span></button>
				<button type="button" class="webcam-stream-stop btn btn-xs btn-danger" data-devid="<? echo (int)$this->view->content->item->Index; ?>"><span class="glyphicon glyphicon-stop"></span></button>
			</div>
			<button type="button" class="webcam-stream-fast-forward btn btn-sm btn-success" data-devid="<? echo (int)$this->view->content->item->Index; ?>" style="display:none;"><span class="glyphicon glyphicon-fast-forward"></span></button>
			<button type="button" class="webcam-stream-step btn btn-sm btn-primary" data-devid="<? echo (int)$this->view->content->item->Index; ?>" style="display:none;"><span class="glyphicon glyphicon-picture"></span></button>
			<? endif; ?>

		</div>
		<br/>

		<dl class="webcam-info dl-horizontal">
			<dt><? echo L::webcam_INDEX; ?></dt>
			<dd><? echo (int)$this->view->content->item->Index; ?></dd>
			<dt><? echo L::webcam_DEVICE_NAME; ?></dt>
			<dd><? echo htmlspecialchars($this->view->content->item->Device, ENT_QUOTES, 'UTF-8'); ?></dd>
			<dt><? echo L::webcam_NAME; ?></dt>
			<dd><? echo htmlspecialchars($this->view->content->item->Name, ENT_QUOTES, 'UTF-8'); ?></dd>
		</dl>

		<div class="row">
			<? if (!$streamed) : ?>
			<div class="alert alert-warning">
				<span><? echo L::webcam_CAMERA_SWITCHED_OFF; ?></span>
			</div>
			<? endif; ?>

			<div class="col-md-12">
				<div id="webcam_<? echo (int)$this->view->content->item->Index; ?>" class="stream-wrapper" style="<? echo $streamed ? "width:320px;height:240px;" : ""; ?>">
					<img <? echo $streamed ? ('id="mjpgstream'.$this->view->content->item->stream->Stream.'"') : ''; ?>/>
					<img id="stream_image_error_<? echo (int)$this->view->content->item->Index; ?>" style="display:none;position:absolute;" src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABgAAAAYCAYAAADgdz34AAAB+ElEQVR42s3WQUgUYRQA4PfeJApdCqJLJy+iaO46Q4J46OBBTyqyO2UX8ZCC4MEuBR0k6JKHgvAgehFB0R2pQ0EG3Tql7ewiFCl2FermepCsea/nWIuLuzvjOgv+DDMw//zzvf+9/x8GocoNLwzACatdQNb8QcTdlMpuRAZIMmkwfv9MAHEfY3apOXsLnwBHArDdNoqAMwWowAg57ty5Abl38yofGttEdK0ABfhJgg3opPfOBXhJ8yUhjBedGcALI+U+qBjgu7FmYcwS0KWi/cy/iY1WfJX+VhGg0X/Q6LvyN1bSx9c71knkvbGa7TkzILbVr+fXBTeLAP6zIL2UyrwJDcjQ7To+2P+qy7I+DKC12MFcroXWdn6FAjjR9hgJn57qKAH4QYk8IifzLBDgROyGgLFFBJfPAjDIPhI00nJmtyzg2eaSpmaw2MzKAT4isGA47lBJgG2rUwv2kUoVPwjQTOksOmjZ/XQKkEkg/hLf0B1rQqkWAPxL1To1ZTr+f6fyACfN+4gwC+VaCOA4WhhGx53PA9IXu8K1WliA6xBBY5YfWFvTQIvrOR/Qwj7Xl08Ejgw7A/ALPqUFf4gyYDUyeZua+5pIAYBD/MMtqNG/0+h7AkdUkirht+glTE83FVUFOFq2nh2fBqAxivgH4OjlesxU/a/iLzY3w5AskOJNAAAAAElFTkSuQmCC"/>
				</div>
			</div>
		</div>
	</form>
	<? else : ?>

	<div class="alert alert-danger">
		<span><? echo L::webcam_CAMERA_NOT_FOUND; ?></span>
	</div>
	<? endif; ?>

</div>
