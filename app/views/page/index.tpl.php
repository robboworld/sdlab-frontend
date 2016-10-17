<h1><?php echo $this->view->content->title; ?></h1>
<div class="row">
	<div class="col-md-6">
		<h3><?php echo L('INFORMATION'); ?></h3>
		<table class="table">
			<tbody>
			<tr>
				<td><?php echo L('DEVICE_NAME'); ?>: </td>
				<td><?php echo htmlspecialchars($this->app->config['lab']['name'], ENT_QUOTES, 'UTF-8')?></td>
			</tr>
			<?php if(isset($this->view->ip_address)) : ?>
			<tr>
				<td><?php echo L('NETWORK_INTERFACES'); ?>: </td>
				<td><?php echo 'eth0 : ' . (empty($this->view->ip_address) ? L('ERROR_NETWORK_ADDRESS_UNKNOWN') : htmlspecialchars($this->view->ip_address, ENT_QUOTES, 'UTF-8')); ?></td>
			</tr>
			<?php endif; ?>
			</tbody>
		</table>
		<?php if(is_object($this->session()) && $this->session()->getUserLevel() == 3) : ?>

		<div>
			<div class="btn-group mrg-bot-5px" role="group">
				<a href="?q=time/edit" class="btn btn-primary">
					<span class="glyphicon glyphicon-cog"></span>&nbsp;<?php echo L('time_SETTINGS'); ?>
				</a>
			</div>
			<div class="btn-group mrg-bot-5px" role="group">
				<a href="?q=webcam/view" class="btn btn-primary">
					<span class="glyphicon glyphicon-facetime-video"></span>&nbsp;<?php echo L('webcam_WEB_CAMERAS'); ?>
				</a>
			</div>
		</div>
		<?php endif; ?>
	</div>
	<!--
	<div class="col-md-6">
		<div class="well">
			<span class="pull-right label label-warning"><?php echo L('TIME_REMAIN'); ?> 2 d. 5:45</span>
			<h3 >
				<span class="label label-danger">
					<span class="glyphicon glyphicon-exclamation-sign"></span> <?php echo L('experiment_ACTIVE_DETECTION'); ?>
				</span>
			</h3>
			<div>
				<?php echo L('SETUP'); ?>: <a href="?q=page/view/config.create">TP1D</a>
			</div>
			<span class="pull-right"><?php echo L('experiment_STARTING'); ?>: 5.04.2014 13:48</span>
			<br>
			<span class="pull-right"><?php echo L('experiment_SCHEDULED_END_TIME'); ?>: 7.04.2014 19:33</span>


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
