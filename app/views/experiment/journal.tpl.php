
<div class="col-md-12">
	<a href="/?q=experiment/view/<? print (int)$this->view->form->experiment->id; ?>" class="btn btn-sm btn-default">
		<span class="glyphicon glyphicon-chevron-left"></span> <? print $this->view->form->experiment->title; ?>
	</a>
</div>
<div class="col-md-12">

</div>
<div class="col-md-9">
	<h3><? print htmlspecialchars($this->view->content->title, ENT_QUOTES, 'UTF-8'); ?></h3>
	<!-- <input type="checkbox"> Включить в отчет -->
	<table class="table table-striped table-bordered">
		<thead>
			<tr>
				<td>№</td>
				<td>Время</td>
				<? foreach ($this->view->content->displayed_sensors as $sensor) :?>
				<td><? print htmlspecialchars($sensor->name, ENT_QUOTES, 'UTF-8'); ?><br/>
					<small><? echo htmlspecialchars($sensor->value_name, ENT_QUOTES, 'UTF-8') . ', ' . htmlspecialchars($sensor->si_notation, ENT_QUOTES, 'UTF-8'); ?></small><br/>
					<small class="muted">(id: <? print htmlspecialchars($sensor->id, ENT_QUOTES, 'UTF-8'); ?>)</small>
				</td>
				<? endforeach; ?>
			</tr>
		</thead>
		<tbody>
		<? $i=0; foreach($this->view->content->detections as $time => $row) : ?>
			<? ++$i; ?>
			<tr>
				<td><? print (int)$i;?></td>
				<td><? print htmlspecialchars($time, ENT_QUOTES, 'UTF-8'); ?></td>
				<? foreach ($this->view->content->displayed_sensors as $sensor) :?>
				<td><? 
					if (isset($row[$sensor->id]))
					{
						print ($row[$sensor->id]->error !== 'NaN') ? (float)$row[$sensor->id]->detection : '';
					}
				 ?></td>
				<? endforeach; ?>
			</tr>
		<? endforeach; ?>
		</tbody>
	</table>
</div>

<div class="col-md-3">
	<h3>Датчики</h3>
	<form method="post" action="?<? print $_SERVER['QUERY_STRING'];?>">
		<input type="hidden" name="form-id" value="experiment-journal-form">
		<ul class="nav">
			<? foreach ($this->view->content->available_sensors as $sensor) :?>
				<li>
					<label><input type="checkbox" <? if (array_key_exists($sensor->id, $this->view->content->displayed_sensors)) print 'checked';?> name="show-sensor[]" value="<? print htmlspecialchars($sensor->id, ENT_QUOTES, 'UTF-8'); ?>"> <? print htmlspecialchars($sensor->name, ENT_QUOTES, 'UTF-8'); ?></label>
				</li>
			<? endforeach; ?>
		</ul>

		<input type="submit" class="btn btn-primary" value="Обновить">
	</form>

</div>