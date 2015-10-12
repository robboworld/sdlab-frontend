<div class="row">
	<div class="col-md-offset-1 col-md-10">
		<h3><? print htmlspecialchars($this->view->content->title, ENT_QUOTES, 'UTF-8'); ?></h3>
	</div>
</div>
<form action="<? print htmlspecialchars($_SERVER['REQUEST_URI'], ENT_QUOTES, 'UTF-8'); ?>" method="post">
<div class="row setup-create">
	<div class="col-md-offset-1 col-md-10">
		<input type="hidden" name="form-id" value="<? print $this->view->form->id; ?>">
		<div class="form-group setup-title">
			<input class="form-control" name="setup_title" type="text" required="required" placeholder="<? echo L::setup_NAME; ?>" value="<? print htmlspecialchars($this->view->form->setup->title, ENT_QUOTES, 'UTF-8'); ?>"/>
		</div>
		<? 
		// Check if active
		if($this->view->form->setup->flag) : ?>
		<div class="row form-group">
			<div class="col-md-4">
				<? echo L::setup_CURRENT_STATUS; ?>
			</div>
			<div class="col-md-8 form-inline">
				<span class="label label-danger"><? echo L::setup_STATUS_IN_PROCESS; ?></span>
			</div>
		</div>
		<? endif; ?>
		<div class="row">
			<div class="col-md-12 form-group form-horizontal">
				<div class="btn-group btn-group-justified">
					<label class="btn btn-default <?if(!empty($this->view->form->setup->time_det)) print 'active';?>">
						<input type="radio" name="setup-type" data-id="setup-type-length" value="setup-type-length" <?if(!empty($this->view->form->setup->time_det)) print 'checked="checked"';?>>
						<? echo L::setup_DURATION; ?>
					</label>
					<label class="btn btn-default <?if(!empty($this->view->form->setup->amount)) print 'active';?>">
						<input type="radio" name="setup-type" data-id="setup-type-amount" value="setup-type-amount" <?if(!empty($this->view->form->setup->amount)) print 'checked="checked"';?>>
						<? echo L::setup_DETECTIONS_COUNT; ?>
					</label>
					<!--
					<label class="btn btn-default">
						<input type="radio" name="setup-type" data-id="setup-type-date" value="setup-type-date">
						<? echo L::FINISHING; ?>
					</label>

					-->
				</div>
				<div class="alert alert-warning" id="setup-type-alert">
					<? echo L::setup_MSG_SELECT_DETECTION_MODE; ?>
				</div>
				<div id="setup-type-length" class="setup-type well">
					<div class="row form-group">
						<div class="col-xs-12 col-md-6 col-sm-5 setup-label-long">
							<? echo L::setup_DURATION_DETECTIONS; ?>
						</div>
						<div class="col-xs-12 col-md-6 col-sm-7 form-inline">
							<? $time_det = Form::formTimeObject($this->view->form->setup->time_det) ;?>
							<input type="text" name="time_det_day" class="form-control" size="1" placeholder="0" value="<? print $time_det->d; ?>"> <? echo L::DAYS_SHORT; ?>
							<input type="text" name="time_det_hour" class="form-control" size="1" placeholder="0" value="<? print $time_det->h; ?>"> <? echo L::HOURS_SHORT2; ?>
							<input type="text" name="time_det_min" class="form-control" size="1" placeholder="1" value="<? print $time_det->m; ?>"> <? echo L::MINUTES_SHORT; ?>
							<input type="text" name="time_det_sec" class="form-control" size="1" placeholder="1" value="<? print $time_det->s; ?>"> <? echo L::SECONDS_SHORT; ?>
						</div>
					</div>
				</div>
				<div id="setup-type-amount" class="setup-type well">
					<div class="row form-group">
						<div class="col-xs-6 col-md-6 col-sm-6 setup-label">
							<? echo L::setup_DETECTIONS_COUNT; ?>
						</div>
						<div class="col-xs-6 col-md-6 col-sm-6 form-inline">
							<input type="text" name="amount" class="form-control" size="10" placeholder="1" value="<? print $this->view->form->setup->amount; ?>">
						</div>
					</div>

				</div>
				<!--
				<div id="setup-type-date" class="setup-type well">
					<div class="row">
						<div class="col-md-4">
							<? echo L::FINISHING; ?>
							<small>{todo: add field to db}</small>
						</div>
						<div class="col-md-8 form-inline">
							<input type="text" class="form-control" placeholder="<? echo L::DATE; ?>" size="13">&nbsp;
							<input type="text" class="form-control" placeholder="<? echo L::TIME; ?>" size="12">
							<br><small>{todo: add jquery.datepicker & jquery.timepicker}</small>
						</div>
					</div>
				</div>
				-->
				<div class="well">
					<div class="row form-group">
						<div class="col-xs-6 col-md-6 col-sm-6 setup-label">
							<? echo L::setup_DETECTIONS_PERIOD; ?>
						</div>
						<div class="col-xs-6 col-md-6 col-sm-6 form-inline">
							<!--
							<input type="text" class="form-control" size="4" placeholder="0"> <? echo L::DAYS_SHORT; ?>
							<input type="text" class="form-control" size="4" placeholder="0"> <? echo L::HOURS_SHORT2; ?>
							-->
							<input type="text" name="interval" class="form-control" required="required" size="10" placeholder="10" value="<? print $this->view->form->setup->interval; ?>"> <? echo L::SECONDS_SHORT; ?>
						</div>
					</div>
					<?
					// TODO: repeate on errors not realised in backend monitoring, need push this parameters to backend and configure RRD/RRA
					?>
					<div class="row form-group" style="display:none;">
						<div class="col-xs-6 col-md-6 col-sm-6 setup-label mrg-top-m5px">
							<? echo L::setup_DETECTIONS_COUNT_REP; ?>
						</div>
						<div class="col-xs-6 col-md-6 col-sm-6 form-inline">
							<input type="text" name="number_error" class="form-control" size="10" placeholder="0" value="<? print $this->view->form->setup->number_error; ?>">
						</div>
					</div>
					<div class="row form-group" style="display:none;">
						<div class="col-xs-6 col-md-6 col-sm-6 setup-label">
							<? echo L::setup_DETECTIONS_PERIOD_REP; ?>
						</div>
						<div class="col-xs-6 col-md-6 col-sm-6 form-inline">
							<input type="text" name="period_repeated_det" class="form-control" size="10" placeholder="0" value="<? print $this->view->form->setup->period_repeated_det; ?>"> <? echo L::SECONDS_SHORT; ?>
						</div>
					</div>
				</div>
			</div>

		</div>
		<div class="row">
			<div class="col-md-12">
				<h4><? echo L::setup_SENSORS_IN_SETUP; ?>:</h4>
				<table class="table table-responsive" id="sensors-in-setup">
					<thead>
						<tr>
							<th>ID</th>
							<th><? echo L::sensor_VALUE_NAME; ?></th>
							<th><? echo L::sensor_NAME; ?></th>
							<th></th>
						</tr>
					</thead>
					<tbody>
						<? if(isset($this->view->form->sensors)) : ?>
							<? foreach($this->view->form->sensors as $sensor) :?>
								<tr>
									<td><? print $sensor->id; ?>
										<input type="hidden" name="sensors[<? print $sensor->id; ?>][<? echo (int)$sensor->sensor_val_id; ?>][id]" value="<? print $sensor->id; ?>"/>
										<input type="hidden" name="sensors[<? print $sensor->id; ?>][<? echo (int)$sensor->sensor_val_id; ?>][val_id]" value="<? print (int)$sensor->sensor_val_id; ?>"/>
									</td>
									<td><? print htmlspecialchars($sensor->value_name, ENT_QUOTES, 'UTF-8'); ?></td>
									<td class="sensor-setup-name"><input type="text" placeholder="<? echo L::sensor_NAME; ?>" name="sensors[<? print $sensor->id; ?>][<? echo (int)$sensor->sensor_val_id; ?>][name]" class="form-control" required="required" value="<? 
										if (isset($sensor->name) && mb_strlen($sensor->name,'utf-8')>0)
										{
											echo htmlspecialchars($sensor->name, ENT_QUOTES, 'UTF-8');
										}
										else
										{
											if (isset($sensor->value_name) && mb_strlen($sensor->value_name,'utf-8')>0)
											{
												echo htmlspecialchars($sensor->value_name, ENT_QUOTES, 'UTF-8');
											}
										}
									?>"/></td>
									<td class="text-right"><a class="btn btn-sm btn-danger remove-sensor"><? echo L::REMOVE; ?></a></td>
								</tr>
							<? endforeach; ?>
						<? endif; ?>
					</tbody>
					<tfoot style="display: none;">
						<tr>
							<td colspan="4">
								<div class="alert alert-info">
									<span class="glyphicon glyphicon-info-sign"></span>
									<span><? echo L::setup_MSG_NO_SENSORS_IN_SETUP; ?></span>
								</div>
							</td>
						</tr>
					</tfoot>
				</table>

				<hr />

				<h4><? echo L::setup_AVAILABLE_SENSORS; ?>:</h4>
				<table class="table table-responsive" id="sensor-list-table">
					<thead>
						<tr>
							<th></th>
							<th>ID</th>
							<th><? echo L::sensor_VALUE_NAME; ?></th>
							<th><? echo L::sensor_VALUE_SI_NOTATION; ?></th>
							<th><? echo L::sensor_VALUE_SI_NAME; ?></th>
							<th><? echo L::sensor_VALUE_MIN_RANGE; ?></th>
							<th><? echo L::sensor_VALUE_MAX_RANGE; ?></th>
							<th><? echo L::sensor_VALUE_ERROR; ?></th>
						</tr>
					</thead>
					<tbody>

					</tbody>
					<tfoot style="display: none;">
						<tr>
							<td colspan="8">
								<div class="alert alert-info">
									<span class="glyphicon glyphicon-info-sign"></span>
									<span><? echo L::setup_MSG_NO_AVAILABLE_SENSORS; ?></span>
								</div>
							</td>
						</tr>
					</tfoot>
				</table>

				<div class="row sensor-block">
					<div class="mrg-bot-5px col-xs-12 col-sm-6 col-md-6">
						<a class="btn btn-default form-control" id="add-sensors"><span class="glyphicon glyphicon-arrow-up"></span><? echo L::setup_ADD_SELECTED_SENSORS; ?></a>
					</div>
					<div class="mrg-bot-5px col-xs-12 col-sm-6 col-md-6">
						<a class="btn btn-default form-control" id="sensors-list-update"><span class="glyphicon glyphicon-refresh"></span><? echo L::setup_REFRESH_AVAILABLE_SENSORS_LIST; ?></a>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
<div class="row mrg-top-20px">
	<div class="col-md-offset-1 col-md-10 text-center">
		<div class="btn-group" style="float: none;">
			<a href="/?q=experiment/view" class="col-md-6 btn-default btn width-auto form-control"><? echo L::CANCEL; ?></a>
		<? if($this->view->form->id == 'edit-setup-form') : ?>
			<a href="/?q=setup/create" class="btn btn-primary width-auto form-control"><? echo L::CREATE; ?></a>
		<? endif; ?>
			<input type="submit" class="btn btn-success width-auto form-control" value="<? print $this->view->form->submit->value; ?>" disabled="disabled" />
		</div>
	</div>
</div>
</form>
