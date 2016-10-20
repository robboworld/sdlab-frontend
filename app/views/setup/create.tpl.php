<?php 
$setup_type = 'amount';
if (empty($this->view->form->setup->amount))
{
	$setup_type = 'length';
}
?>
<div class="row">
	<div class="col-md-offset-1 col-md-10">
		<h3><?php echo htmlspecialchars($this->view->content->title, ENT_QUOTES, 'UTF-8'); ?></h3>
	</div>
</div>
<form action="<?php echo htmlspecialchars($_SERVER['REQUEST_URI'], ENT_QUOTES, 'UTF-8'); ?>" method="post">
<div class="row setup-create">
	<div class="col-md-offset-1 col-md-10">
		<input type="hidden" name="form-id" value="<?php echo htmlspecialchars($this->view->form->id, ENT_QUOTES, 'UTF-8'); ?>">
		<div class="form-group setup-title">
			<input class="form-control" name="setup_title" type="text" required="required" placeholder="<?php echo L('setup_NAME'); ?>" value="<?php echo htmlspecialchars($this->view->form->setup->title, ENT_QUOTES, 'UTF-8'); ?>"/>
		</div>
		<?php
		// Check if active
		if(isset($this->view->form->setup->active) && $this->view->form->setup->active) : ?>
		<div class="row form-group">
			<div class="col-md-4">
				<?php echo L('setup_CURRENT_STATUS');
				//TODO: add Setup counters: Active in / Used in (current merged with active monitors)
				?>
			</div>
			<div class="col-md-8 form-inline">
				<span class="label label-danger"><?php echo L('setup_STATUS_IN_PROCESS'); ?></span>
			</div>
		</div>
		<?php endif; ?>
		<div class="row">
			<div class="col-md-12 form-group form-horizontal">
				<div class="btn-group btn-group-justified">
					<label class="btn btn-default <?php if($setup_type == 'length') echo 'active';?>">
						<input type="radio" name="setup-type" data-id="setup-type-length" value="setup-type-length" <?php if($setup_type == 'length') echo 'checked="checked"';?>>
						<?php echo L('setup_DURATION'); ?>
					</label>
					<label class="btn btn-default <?php if($setup_type == 'amount') echo 'active';?>">
						<input type="radio" name="setup-type" data-id="setup-type-amount" value="setup-type-amount" <?php if($setup_type == 'amount') echo 'checked="checked"';?>>
						<?php echo L('setup_DETECTIONS_COUNT'); ?>
					</label>
					<!--
					<label class="btn btn-default">
						<input type="radio" name="setup-type" data-id="setup-type-date" value="setup-type-date">
						<?php echo L('FINISHING'); ?>
					</label>

					-->
				</div>
				<div id="setup-type-alert" class="alert alert-warning" role="alert">
					<?php echo L('setup_MSG_SELECT_DETECTION_MODE'); ?>
				</div>
				<div id="setup-type-length" class="setup-type well" style="<?php if($setup_type != 'length') echo 'display:none;';?>">
					<div class="row form-group">
						<div class="col-xs-12 col-md-6 col-sm-5 setup-label-long">
							<?php echo L('setup_DURATION_DETECTIONS'); ?>
						</div>
						<div class="col-xs-12 col-md-6 col-sm-7 form-inline">
							<?php $time_det = Form::formTimeObject($this->view->form->setup->time_det) ;?>
							<input type="text" name="time_det_day" class="form-control" size="1" placeholder="0" value="<?php echo $time_det->d; ?>"> <?php echo L('DAYS_SHORT'); ?>
							<input type="text" name="time_det_hour" class="form-control" size="1" placeholder="0" value="<?php echo $time_det->h; ?>"> <?php echo L('HOURS_SHORT2'); ?>
							<input type="text" name="time_det_min" class="form-control" size="1" placeholder="1" value="<?php echo $time_det->m; ?>"> <?php echo L('MINUTES_SHORT'); ?>
							<input type="text" name="time_det_sec" class="form-control" size="1" placeholder="1" value="<?php echo $time_det->s; ?>"> <?php echo L('SECONDS_SHORT'); ?>
						</div>
					</div>
				</div>
				<div id="setup-type-amount" class="setup-type well" style="<?php if($setup_type != 'amount') echo 'display:none;';?>">
					<div class="row form-group">
						<div class="col-xs-6 col-md-6 col-sm-6 setup-label">
							<?php echo L('setup_DETECTIONS_COUNT'); ?>
						</div>
						<div class="col-xs-6 col-md-6 col-sm-6 form-inline">
							<input type="text" name="amount" class="form-control" size="10" placeholder="1" value="<?php echo htmlspecialchars($this->view->form->setup->amount, ENT_QUOTES, 'UTF-8'); ?>">
						</div>
					</div>

				</div>
				<!--
				<div id="setup-type-date" class="setup-type well">
					<div class="row">
						<div class="col-md-4">
							<?php echo L('FINISHING'); ?>
							<small>{todo: add field to db}</small>
						</div>
						<div class="col-md-8 form-inline">
							<input type="text" class="form-control" placeholder="<?php echo L('DATE'); ?>" size="13">&nbsp;
							<input type="text" class="form-control" placeholder="<?php echo L('TIME'); ?>" size="12">
							<br><small>{todo: add jquery.datepicker & jquery.timepicker}</small>
						</div>
					</div>
				</div>
				-->
				<div class="well">
					<div class="row form-group">
						<div class="col-xs-6 col-md-6 col-sm-6 setup-label">
							<?php echo L('setup_DETECTIONS_PERIOD'); ?>
						</div>
						<div class="col-xs-6 col-md-6 col-sm-6 form-inline">
							<!--
							<input type="text" class="form-control" size="4" placeholder="0"> <?php echo L('DAYS_SHORT'); ?>
							<input type="text" class="form-control" size="4" placeholder="0"> <?php echo L('HOURS_SHORT2'); ?>
							-->
							<input type="text" name="interval" class="form-control" required="required" size="10" placeholder="10" value="<?php echo htmlspecialchars($this->view->form->setup->interval, ENT_QUOTES, 'UTF-8'); ?>"> <?php echo L('SECONDS_SHORT'); ?>
						</div>
					</div>
					<div class="row form-group">
						<div class="col-xs-6 col-md-6 col-sm-6 setup-label">
							<?php echo L('setup_ACCESS'); ?>
						</div>
						<div class="col-xs-6 col-md-6 col-sm-6">
							<div class="radio<?php echo $this->view->form->setup->id ? ' disabled' : '' ?>">
					 			<label>
									<input type="radio" value="0" id="setup_access0" name="access" <?php echo ($this->view->form->setup->access == 0) ? 'checked="checked"' : ''; echo $this->view->form->setup->id ? ' disabled="disabled"' : ''; ?>/><span class="fa fa-users fa-lg" aria-hidden="true">&nbsp;</span> <?php echo L('setup_ACCESS_SHARED'); ?>
								</label>
							</div>
							<div class="radio<?php echo $this->view->form->setup->id ? ' disabled' : '' ?>">
								<label>
									<input type="radio" value="1" id="setup_access1" name="access" <?php echo ($this->view->form->setup->access == 1) ? 'checked="checked"' : ''; echo $this->view->form->setup->id ? ' disabled="disabled"' : ''; ?>/><span class="fa fa-user fa-lg" aria-hidden="true">&nbsp;</span> <?php echo L('setup_ACCESS_PRIVATE'); ?>
								</label>
							</div>
							<div class="radio<?php echo $this->view->form->setup->id ? ' disabled' : '' ?>">
								<label>
									<input type="radio" value="2" id="setup_access2" name="access" <?php echo ($this->view->form->setup->access == 2) ? 'checked="checked"' : ''; echo $this->view->form->setup->id ? ' disabled="disabled"' : ''; ?>/><span class="fa fa-shield fa-lg" aria-hidden="true">&nbsp;</span> <?php echo L('setup_ACCESS_SINGLE'); ?>
								</label>
							</div>
						</div>
					</div>
					<?php
					// TODO: repeate on errors not realised in backend monitoring, need push this parameters to backend and configure RRD/RRA
					?>
					<div class="row form-group" style="display:none;">
						<div class="col-xs-6 col-md-6 col-sm-6 setup-label mrg-top-m5px">
							<?php echo L('setup_DETECTIONS_COUNT_REP'); ?>
						</div>
						<div class="col-xs-6 col-md-6 col-sm-6 form-inline">
							<input type="text" name="number_error" class="form-control" size="10" placeholder="0" value="<?php echo htmlspecialchars($this->view->form->setup->number_error, ENT_QUOTES, 'UTF-8'); ?>">
						</div>
					</div>
					<div class="row form-group" style="display:none;">
						<div class="col-xs-6 col-md-6 col-sm-6 setup-label">
							<?php echo L('setup_DETECTIONS_PERIOD_REP'); ?>
						</div>
						<div class="col-xs-6 col-md-6 col-sm-6 form-inline">
							<input type="text" name="period_repeated_det" class="form-control" size="10" placeholder="0" value="<?php echo htmlspecialchars($this->view->form->setup->period_repeated_det, ENT_QUOTES, 'UTF-8'); ?>"> <?php echo L('SECONDS_SHORT'); ?>
						</div>
					</div>
				</div>
			</div>

		</div>
		<div class="row">
			<div class="col-md-12">
				<h4><?php echo L('setup_SENSORS_IN_SETUP'); ?>:</h4>
				<table class="table table-responsive" id="sensors-in-setup">
					<thead>
						<tr>
							<th>ID</th>
							<th><?php echo L('sensor_VALUE_NAME'); ?></th>
							<th><?php echo L('sensor_NAME'); ?></th>
							<th></th>
						</tr>
					</thead>
					<tbody>
						<?php if(isset($this->view->form->sensors)) : ?>
							<?php foreach($this->view->form->sensors as $sensor) :?>
								<tr>
									<td><?php echo $sensor->sensor_id; ?>
										<input type="hidden" name="sensors[<?php echo $sensor->sensor_id; ?>][<?php echo (int)$sensor->sensor_val_id; ?>][id]" value="<?php echo $sensor->sensor_id; ?>"/>
										<input type="hidden" name="sensors[<?php echo $sensor->sensor_id; ?>][<?php echo (int)$sensor->sensor_val_id; ?>][val_id]" value="<?php echo (int)$sensor->sensor_val_id; ?>"/>
									</td>
									<td><?php
									if (isset($sensor->value_name) && mb_strlen($sensor->value_name,'utf-8')>0)
									{
										$value_name = (string) preg_replace('/[^A-Z0-9_]/i', '_', $sensor->value_name);
										echo htmlspecialchars(L('sensor_VALUE_NAME_' . strtoupper($value_name)), ENT_QUOTES, 'UTF-8');
									}
									else
									{
										echo htmlspecialchars(L('sensor_UNKNOWN'), ENT_QUOTES, 'UTF-8');
									}
									?></td>
									<td class="sensor-setup-name"><input type="text" placeholder="<?php echo L('sensor_NAME'); ?>" name="sensors[<?php echo $sensor->sensor_id; ?>][<?php echo (int)$sensor->sensor_val_id; ?>][name]" class="form-control" required="required" value="<?php
										if (isset($sensor->name) && mb_strlen($sensor->name,'utf-8')>0)
										{
											echo htmlspecialchars($sensor->name, ENT_QUOTES, 'UTF-8');
										}
										else
										{
											if (isset($sensor->value_name) && mb_strlen($sensor->value_name,'utf-8')>0)
											{
												$value_name = (string) preg_replace('/[^A-Z0-9_]/i', '_', $sensor->value_name);
												echo htmlspecialchars(L('sensor_VALUE_NAME_' . strtoupper($value_name)), ENT_QUOTES, 'UTF-8');
											}
										}
									?>"/></td>
									<td class="text-right"><a class="btn btn-sm btn-danger remove-sensor"><?php echo L('REMOVE'); ?></a></td>
								</tr>
							<?php endforeach; ?>
						<?php endif; ?>
					</tbody>
					<tfoot style="display: none;">
						<tr>
							<td colspan="4">
								<div class="alert alert-info" role="alert">
									<span class="glyphicon glyphicon-info-sign"></span>
									<span><?php echo L('setup_MSG_NO_SENSORS_IN_SETUP'); ?></span>
								</div>
							</td>
						</tr>
					</tfoot>
				</table>

				<hr />

				<h4><?php echo L('setup_AVAILABLE_SENSORS'); ?>:</h4>
				<table class="table table-responsive" id="sensor-list-table">
					<thead>
						<tr>
							<th></th>
							<th>ID</th>
							<th><?php echo L('sensor_VALUE_NAME'); ?></th>
							<th><?php echo L('sensor_VALUE_SI_NOTATION'); ?></th>
							<th title="<?php echo L('sensor_VALUE_SI_NAME'); ?>"><?php echo L('sensor_VALUE_SI_NSHORT'); ?></th>
							<th><?php echo L('sensor_VALUE_MIN_RANGE'); ?></th>
							<th><?php echo L('sensor_VALUE_MAX_RANGE'); ?></th>
							<th><?php echo L('sensor_VALUE_ERROR'); ?></th>
						</tr>
					</thead>
					<tbody>

					</tbody>
					<tfoot style="display: none;">
						<tr>
							<td colspan="8">
								<div class="alert alert-info" role="alert">
									<span class="glyphicon glyphicon-info-sign"></span>
									<span><?php echo L('setup_MSG_NO_AVAILABLE_SENSORS'); ?></span>
								</div>
							</td>
						</tr>
					</tfoot>
				</table>

				<div class="row sensor-block">
					<div class="mrg-bot-5px col-xs-12 col-sm-6 col-md-6">
						<a class="btn btn-default form-control" id="add-sensors"><span class="glyphicon glyphicon-arrow-up"></span><?php echo L('setup_ADD_SELECTED_SENSORS'); ?></a>
					</div>
					<div class="mrg-bot-5px col-xs-12 col-sm-6 col-md-6">
						<a class="btn btn-default form-control" id="sensors-list-update"><span class="glyphicon glyphicon-refresh"></span><?php echo L('setup_REFRESH_AVAILABLE_SENSORS_LIST'); ?></a>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
<div class="row mrg-top-20px">
	<div class="col-md-offset-1 col-md-10 text-center">
		<div class="btn-group" style="float: none;">
			<a href="/?q=experiment/view" class="col-md-6 btn-default btn width-auto form-control"><?php echo L('CANCEL'); ?></a>
		<?php if($this->view->form->id == 'edit-setup-form') :
			if((int)$this->view->form->setup->master_exp_id) :
			// TODO: Create Setup w/o master for admin only?
		?>

			<a href="/?q=setup/create&master=<?php echo (int)$this->view->form->setup->master_exp_id; ?>" class="btn btn-primary width-auto form-control"><?php echo L('CREATE'); ?></a>
		<?php endif;
		endif; ?>

			<input type="submit" class="btn btn-success width-auto form-control" value="<?php echo htmlspecialchars($this->view->form->submit->value, ENT_QUOTES, 'UTF-8'); ?>" disabled="disabled" />
		</div>
	</div>
</div>
</form>
