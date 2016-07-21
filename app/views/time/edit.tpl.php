<div class="row">
<? if (isset($this->view->content->reboot) && $this->view->content->reboot == true) : ?>
	<script type="text/javascript">
	<!--
	$(document).ready(function(){
		setTimeout(function() { window.location.assign("/?q=time/edit"); },120000);/*wait 120 sec (must be > (delay sheduler 1 min + delay reboot 60 sec))*/
	});
	//-->
	</script>
	<div class="col-md-offset-1 col-md-10">
		<div class="alert alert-warning"><span class="glyphicon glyphicon-refresh spin"></span>&nbsp;<? echo L::time_WAIT_FOR_REBOOT; ?></div>
	</div>
<? else : ?>
	<div class="col-md-offset-1 col-md-10">
		<h3><? echo htmlspecialchars($this->view->content->title, ENT_QUOTES, 'UTF-8'); ?></h3>
	</div>
	<div class="col-md-offset-1 col-md-10">
		<form class="row" action="?<? echo $_SERVER['QUERY_STRING'];?>" method="post">
			<input type="hidden" name="form-id" value="edit-time-form">
			<div class="form-group col-md-4">
				<label for="time_datetime_id"><? echo L::DATETIME; ?></label><br/>
				<input type="text" class="form-control" id="time_datetime_id" name="time_datetime" placeholder="<? 
					echo L::time_INPUT_DATETIME;?>" value="<? 
					echo htmlspecialchars($this->view->form->datetime->format('Y-m-d H:i'), ENT_QUOTES, 'UTF-8');?>" title="<? 
					echo L::time_FORMAT;?>"/>
			</div>
			<div class="form-group col-md-4">
				<label for="time_timezone_id"><? echo L::TIMEZONE; ?></label><br/>
				<? echo $this->view->form->timezones_html; ?>
			</div>
			<div class="form-group col-md-4">
				<div class="alert alert-info"><? echo L::time_REBOOT_NEEDED; ?></div>
			</div>
			<div class="clearfix"></div>
			<div class="col-sm-offset-4 col-sm-4 col-md-offset-4 col-md-4 text-center">
				<div class="btn-group" style="float:none;">
					<input type="submit" class="form-control btn btn-success" value="<? echo htmlspecialchars($this->view->form->submit->value, ENT_QUOTES, 'UTF-8'); ?>"/>
				</div>
			</div>
		</form>
	</div>
<? endif;?>
</div>
