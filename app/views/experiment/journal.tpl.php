<div class="row">
	<div class="col-md-12">
		<a href="/?q=experiment/view/<?php echo (int)$this->view->form->experiment->id; ?>" class="btn btn-sm btn-default">
			<span class="glyphicon glyphicon-chevron-left"></span> <?php echo htmlspecialchars($this->view->form->experiment->title, ENT_QUOTES, 'UTF-8'); ?>
		</a>
	</div>
</div>

<form method="post" id="journalForm" action="?<?php echo $_SERVER['QUERY_STRING'];?>">
<div class="row">
	<div class="col-md-9">
		<h3><?php echo htmlspecialchars($this->view->content->title, ENT_QUOTES, 'UTF-8'); ?></h3>
		<p>
			<button type="button" id="exportDetections" class="btn btn-primary">
				<span class="glyphicon glyphicon-download"></span> <?php echo L::DOWNLOAD; ?>
			</button>
			<button type="button" id="cleanDetections" class="btn btn-danger">
				<span class="glyphicon glyphicon-trash"></span> <?php echo L::CLEAN; ?>
			</button>
		</p>
		<!-- <input type="checkbox"> <?php echo L::INCLUDE_TO_REPORT; ?> -->
		<table class="table-detections table table-striped table-bordered">
			<thead>
				<tr>
					<td>№</td>
					<td><?php echo L::TIME; ?></td>
					<?php foreach ($this->view->content->displayed_sensors as $skey => $sensor) :?>
					<td><?php echo htmlspecialchars($sensor->name, ENT_QUOTES, 'UTF-8'); ?><br/>
						<small><?php 
							echo ((mb_strlen($sensor->value_name, 'utf-8') > 0 ) ? 
									htmlspecialchars(constant('L::sensor_VALUE_NAME_' . strtoupper($sensor->value_name)), ENT_QUOTES, 'UTF-8') : 
									'-')
									. ', '
								. ((mb_strlen($sensor->value_name, 'utf-8') > 0 && mb_strlen($sensor->si_notation, 'utf-8') > 0) ? 
									htmlspecialchars(constant('L::sensor_VALUE_SI_NOTATION_' . strtoupper($sensor->value_name) . '_' . strtoupper($sensor->si_notation)), ENT_QUOTES, 'UTF-8') : 
									'-');
						?></small><br/>
						<small class="muted">(id: <?php echo htmlspecialchars($skey, ENT_QUOTES, 'UTF-8'); ?>)</small>
					</td>
					<?php endforeach; ?>
				</tr>
			</thead>
			<tbody>
			<?php $i=0; foreach($this->view->content->detections as $time => $row) : ?>
				<?php ++$i; ?>
				<tr data-detection-time="<?php echo htmlspecialchars($time, ENT_QUOTES, 'UTF-8'); ?>">
					<td><b class="detection-num"><?php echo (int)$i;?></b>
						<div>
							<a href="javascript:void(0);" class="btn-remove-detection btn btn-xs text-danger pull-left" style="display: none;"><span class="glyphicon glyphicon-remove"></span></a>
						</div>
					</td>
					<td title="<?php echo htmlspecialchars(System::datemsecformat($time, System::DATETIME_FORMAT1NANO, 'now'), ENT_QUOTES, 'UTF-8'); ?>" style="white-space:nowrap;"><?php
						echo htmlspecialchars(
								System::datemsecformat($time, System::DATETIME_FORMAT1, 'now')
								. '.' . (string)substr((string)((int)System::getdatemsec($time)),0,3),
								ENT_QUOTES, 'UTF-8');
					?></td>
					<?php foreach ($this->view->content->displayed_sensors as $skey => $sensor) :?>
					<td><?php
						if (isset($row[$skey]))
						{
							echo ($row[$skey]->error !== 'NaN') ? (float)$row[$skey]->detection : '';
						}
					?></td>
					<?php endforeach; ?>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
	</div>

	<div class="col-md-3">
		<h3><?php echo L::SENSORS; ?>
			<a class="btn btn-link btn-sm" id="collapseSensorsControl" role="button" data-toggle="collapse" href="#collapseSensors" aria-expanded="true" aria-controls="collapseSensors" title="<?php echo L::graph_FILTER_SHOW_HIDE; ?>">
				<span class="glyphicon glyphicon-chevron-down"></span>
			</a>
		</h3>
		<div class="collapse in" id="collapseSensors">
			<ul class="nav">
				<?php foreach ($this->view->content->available_sensors as $skey => $sensor) :?>
					<li>
						<label><input type="checkbox" <?php if (array_key_exists($skey, $this->view->content->displayed_sensors)) echo 'checked';?> name="show-sensor[]" value="<?php echo htmlspecialchars($skey, ENT_QUOTES, 'UTF-8'); ?>"/> <?php echo htmlspecialchars($sensor->name, ENT_QUOTES, 'UTF-8'); ?></label>
					</li>
				<?php endforeach; ?>
			</ul>
			<input type="submit" class="btn btn-primary" value="<?php echo L::REFRESH; ?>" />
		</div>
	</div>
</div>

	<input type="hidden" name="form-id" value="experiment-journal-form"/>
	<input type="hidden" name="exp_id" value="<?php echo (int)$this->view->form->experiment->id; ?>"/>
</form>
