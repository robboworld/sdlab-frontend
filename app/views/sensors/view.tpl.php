<div class="my-fluid-container">
	<div class="col-lg-3" id="sensors-list-bar">
		<h3><?php echo L('sensor_LIST'); ?>
			<!--
			<a href="#sensors-list-update" id="sensors-list-update" class="pull-right btn btn-sm btn-primary"><?php echo L('REFRESH'); ?></a>
			-->
		</h3>
		<div class="list-group" id="available-sensors">
		</div>
	</div>
	<div class="col-lg-12" id="workspace">
		<h3><?php echo L('sensor_TITLE_ACTIVE_DETECTIONS'); ?></h3>
		<?php echo L('sensor_INFO_TEXT'); ?>
		<div class="row">
			<div class="col-lg-12 well" id="sensors-workspace">
				<?php echo $this->view->content->sensors_list; ?>
			</div>
		</div>
	</div>

</div>