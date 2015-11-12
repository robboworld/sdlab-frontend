<h1><? print $this->view->content->title; ?></h1>
<div class="row">
	<div class="col-md-6">
		<h3><?php echo L::INFORMATION; ?></h3>
		<table class="table">
			<tbody>
			<tr>
				<td><? echo L::DEVICE_NAME; ?>: </td>
				<td><? print $this->app->config['lab']['name']?></td>
			</tr>
			<? if(isset($this->view->ip_address)) : ?>
			<tr>
				<td><? echo L::NETWORK_INTERFACES; ?>: </td>
				<td><? echo 'eth0 : ' . (empty($this->view->ip_address) ? L::ERROR_NETWORK_ADDRESS_UNKNOWN : $this->view->ip_address); ?></td>
			</tr>
			<? endif; ?>
			</tbody>
		</table>
		<? if(is_object($this->session()) && $this->session()->getUserLevel() == 3) : ?>

		<div>
			<a href="?q=time/edit" class="btn btn-primary">
				<span class="glyphicon glyphicon-cog"></span>&nbsp;<? echo L::time_SETTINGS; ?>
			</a>
			<a href="?q=webcam/view" class="btn btn-primary">
				<span class="glyphicon glyphicon-facetime-video"></span>&nbsp;<? echo L::webcam_WEB_CAMERAS; ?>
			</a>
		</div>
		<? endif; ?>
	</div>
	<!--
	<div class="col-md-6">
		<div class="well">
			<span class="pull-right label label-warning"><? echo L::TIME_REMAIN; ?> 2 d. 5:45</span>
			<h3 >
				<span class="label label-danger">
					<span class="glyphicon glyphicon-exclamation-sign"></span> <? echo L::experiment_ACTIVE_DETECTION; ?>
				</span>
			</h3>
			<div>
				<? echo L::SETUP; ?>: <a href="?q=page/view/config.create">TP1D</a>
			</div>
			<span class="pull-right"><? echo L::experiment_STARTING; ?>: 5.04.2014 13:48</span>
			<br>
			<span class="pull-right"><? echo L::experiment_SCHEDULED_END_TIME; ?>: 7.04.2014 19:33</span>


			<table class="table">
				<tbody>
				<tr>
					<td>{FULL_NAME}</td>
					<td><a href="?q=page/view/experiment">{experiment name}</a></td>
				</tr>
				<tr>
					<td>{FULL_NAME}</td>
					<td><a href="?q=page/view/experiment">{experiment name}</a></td>
				</tr>
				<tr>
					<td>{FULL_NAME}</td>
					<td><a href="?q=page/view/experiment">{experiment name}</a></td>
				</tr>
				</tbody>
			</table>
		</div>
	</div>
	-->
</div>
