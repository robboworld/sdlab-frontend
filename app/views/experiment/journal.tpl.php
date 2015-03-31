
<div class="col-md-12">
	<a href="/?q=experiment/view/<? print $this->view->form->experiment->id; ?>" class="btn btn-sm btn-default">
		<span class="glyphicon glyphicon-chevron-left"></span> <? print $this->view->form->experiment->title; ?>
	</a>
</div>
<div class="col-md-12">

</div>
<div class="col-md-9">
	<h3><? print $this->view->content->title; ?></h3>
	<!-- <input type="checkbox"> Включить в отчет -->
	<table class="table table-striped table-bordered">
		<thead>
		<td>№</td>
		<td>Время</td>
		<? foreach ($this->view->content->displayed_sensors as $sensor) :?>
		<td><? print $sensor->name; ?> <small>(id: <? print $sensor->id; ?>)</small></td>
		<? endforeach; ?>
		</thead>
		<tbody>
		<? foreach($this->view->content->detections as $time => $row) : ?>
			<? ++$i; ?>
			<tr>
				<td><? print $i;?></td>
				<td><? print $time; ?></td>
				<? foreach ($this->view->content->displayed_sensors as $sensor) :?>
					<td><? print $row[$sensor->id]->detection; ?></td>
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
					<label><input type="checkbox" <? if (array_key_exists($sensor->id, $this->view->content->displayed_sensors)) print 'checked';?> name="show-sensor[]" value="<? print $sensor->id; ?>"> <? print $sensor->name; ?></label>
				</li>
			<? endforeach; ?>
		</ul>

		<input type="submit" class="btn btn-primary" value="Обновить">
	</form>

</div>