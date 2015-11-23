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
	$(document).ready(function(){
		var sdwc = new WebCams();
		var opts = {cams:<? echo json_encode($cams); ?>,delay:0};
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
		$('.webcam-stream-step-forward').click(function(){
			sdwc.snapshot($(this).data('devid'));
		});
		//$('.webcam-stream-fast-forward').click(function(){
		//	sdwc.start($(this).data('devid'), 200);
		//});
	});
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
						<div id="webcam_<? echo (int)$item->Index; ?>" class="stream-wrapper" style="<? echo $streamed ? "width:320px;height:240px;" : ""; ?>">
							<img <? echo $streamed ? ('id="mjpgstream'.$item->stream->Stream.'"') : ''; ?>/>
						</div>
						<? if ($streamed) : ?>
						<div class="btn-group btn-group-xs btn-group-streamplay" role="group">
							<button type="button" class="webcam-stream-start btn btn-xs btn-success" data-devid="<? echo (int)$item->Index; ?>"><span class="glyphicon glyphicon-play"></span></button>
							<button type="button" class="webcam-stream-step-forward btn btn-xs btn-success" data-devid="<? echo (int)$item->Index; ?>"><span class="glyphicon glyphicon-step-forward"></span></button>
							<button type="button" class="webcam-stream-stop btn btn-xs btn-danger" data-devid="<? echo (int)$item->Index; ?>"><span class="glyphicon glyphicon-stop"></span></button>
						</div>
						<button type="button" class="webcam-stream-fast-forward btn btn-xs btn-success" data-devid="<? echo (int)$item->Index; ?>" style="display:none;"><span class="glyphicon glyphicon-fast-forward"></span></button>
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
