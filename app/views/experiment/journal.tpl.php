<div class="row">
	<div class="col-md-12">
		<a href="/?q=experiment/view/<? print (int)$this->view->form->experiment->id; ?>" class="btn btn-sm btn-default">
			<span class="glyphicon glyphicon-chevron-left"></span> <? print $this->view->form->experiment->title; ?>
		</a>
	</div>
</div>

<form method="post" id="journalForm" action="?<? print $_SERVER['QUERY_STRING'];?>">
<div class="row">
	<div class="col-md-9">
		<h3><? print htmlspecialchars($this->view->content->title, ENT_QUOTES, 'UTF-8'); ?></h3>
		<p>
			<button type="button" id="cleanDetections" class="btn btn-danger">
				<span class="glyphicon glyphicon-trash"></span> <? echo L::CLEAN; ?>
			</button>
		</p>
		<!-- <input type="checkbox"> <? echo L::INCLUDE_TO_REPORT; ?> -->
		<table class="table-detections table table-striped table-bordered">
			<thead>
				<tr>
					<td>№</td>
					<td><? echo L::TIME; ?></td>
					<? foreach ($this->view->content->displayed_sensors as $skey => $sensor) :?>
					<td><? print htmlspecialchars($sensor->name, ENT_QUOTES, 'UTF-8'); ?><br/>
						<small><? echo htmlspecialchars(constant('L::sensor_VALUE_NAME_' . strtoupper($sensor->value_name)), ENT_QUOTES, 'UTF-8')
								. ', ' . htmlspecialchars(constant('L::sensor_VALUE_SI_NOTATION_' . strtoupper($sensor->value_name) . '_' . strtoupper($sensor->si_notation)), ENT_QUOTES, 'UTF-8'); ?></small><br/>
						<small class="muted">(id: <? print htmlspecialchars($skey, ENT_QUOTES, 'UTF-8'); ?>)</small>
					</td>
					<? endforeach; ?>
				</tr>
			</thead>
			<tbody>
			<? $i=0; foreach($this->view->content->detections as $time => $row) : ?>
				<? ++$i; ?>
				<tr data-detection-time="<? print htmlspecialchars($time, ENT_QUOTES, 'UTF-8'); ?>">
					<td><b class="detection-num"><? print (int)$i;?></b>
						<div>
							<a href="javascript:void(0);" class="btn-remove-detection btn btn-xs text-danger pull-left" style="display: none;"><span class="glyphicon glyphicon-remove"></span></a>
						</div>
					</td>
					<td><? print htmlspecialchars($time, ENT_QUOTES, 'UTF-8'); ?></td>
					<? foreach ($this->view->content->displayed_sensors as $skey => $sensor) :?>
					<td><? 
						if (isset($row[$skey]))
						{
							print ($row[$skey]->error !== 'NaN') ? (float)$row[$skey]->detection : '';
						}
					?></td>
					<? endforeach; ?>
				</tr>
			<? endforeach; ?>
			</tbody>
		</table>
	</div>

	<div class="col-md-3">
		<h3><? echo L::SENSORS; ?>
			<a class="btn btn-link btn-sm" id="collapseSensorsControl" role="button" data-toggle="collapse" href="#collapseSensors" aria-expanded="true" aria-controls="collapseSensors" title="<? echo L::graph_FILTER_SHOW_HIDE; ?>">
				<span class="glyphicon glyphicon-chevron-down"></span>
			</a>
		</h3>
		<div class="collapse in" id="collapseSensors">
			<ul class="nav">
				<? foreach ($this->view->content->available_sensors as $skey => $sensor) :?>
					<li>
						<label><input type="checkbox" <? if (array_key_exists($skey, $this->view->content->displayed_sensors)) print 'checked';?> name="show-sensor[]" value="<? print htmlspecialchars($skey, ENT_QUOTES, 'UTF-8'); ?>"/> <? print htmlspecialchars($sensor->name, ENT_QUOTES, 'UTF-8'); ?></label>
					</li>
				<? endforeach; ?>
			</ul>
			<input type="submit" class="btn btn-primary" value="<? echo L::REFRESH; ?>" />
		</div>
	</div>
</div>

	<input type="hidden" name="form-id" value="experiment-journal-form"/>
	<input type="hidden" name="exp_id" value="<? echo (int)$this->view->form->experiment->id; ?>"/>
</form>