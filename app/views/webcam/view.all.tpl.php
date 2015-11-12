<? 
// todo: can start and stop access check, edit only for admin or owner
$show_action = true;

$stream_ids = array();
if(isset($this->view->content->list))
{
	foreach($this->view->content->list as $item)
	{
		if (!empty($item->stream) && ($item->stream->Stream >= 0))
		{
			$stream_ids[] = (int)$item->stream->Stream;
		}
	}
}
?>
<script type="text/javascript">
<!--
	var cams = <? echo json_encode($stream_ids); ?>;
	function setStreamImg(ids) {
		for(var i=0,len=cams.length;i<len;i++){
			var img = document.getElementById('mjpgstream'+cams[i]);
			if(img){
				img.src = window.location.protocol + "//" + window.location.hostname + ":8090?action=stream_"+cams[i];
			}
		}
	}
	$(document).ready(function(){
		setStreamImg(cams);
		$('.webcam-stream-start').click(function(){
			var form = document.getElementById('sdform');
			form.action = '?q=webcam/start';
			form.elements["dev_id"].value = $(this).data('devid');
			form.submit();
		});
		$('.webcam-stream-stop').click(function(){
			var form = document.getElementById('sdform');
			form.action = '?q=webcam/stop';
			form.elements["dev_id"].value = $(this).data('devid');
			form.submit();
		});
		$('.webcam-stream-startall').click(function(){
			var form = document.getElementById('sdform');
			form.action = '?q=webcam/startall';
			form.elements["dev_id"].value = -1;
			form.submit();
		});
		$('.webcam-stream-stopall').click(function(){
			var form = document.getElementById('sdform');
			form.action = '?q=webcam/stopall';
			form.elements["dev_id"].value = -1;
			form.submit();
		});
	});
//-->
</script>
<div class="col-md-12">
	<div class="row">
		<div class="col-md-6">
			<h1><? echo L::webcam_TITLE_ALL; ?></h1>
		</div>
	</div>
	<div>
		<button type="button" class="webcam-stream-startall btn btn-primary"><span class="glyphicon glyphicon-play">&nbsp;</span><? echo L::webcam_START_ALL; ?></button>
		<button type="button" class="webcam-stream-stopall btn btn-warning"><span class="glyphicon glyphicon-stop">&nbsp;</span><? echo L::webcam_STOP_ALL; ?></button>
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
						<div>
							<img <? echo (!empty($item->stream) && ($item->stream->Stream >= 0)) ? ('id="mjpgstream'.$item->stream->Stream.'"') : ''; ?>/>
						</div>
					</td>
					<td class="text-right">
						<button type="button" class="webcam-stream-start btn btn-sm btn-success" data-devid="<? echo (int)$item->Index; ?>"><span class="glyphicon glyphicon-play"></span></button>
						<button type="button" class="webcam-stream-stop btn btn-sm btn-danger" data-devid="<? echo (int)$item->Index; ?>"><span class="glyphicon glyphicon-stop"></span></button>
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