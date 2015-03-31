<div class="col-md-12">
	<a href="/?q=experiment/view/<? print $this->view->content->experiment->id; ?>" class="btn btn-sm btn-default">
		<span class="glyphicon glyphicon-chevron-left"></span> <? print $this->view->content->experiment->title; ?>
	</a>
</div>
<div class="col-md-12">
	<h3>Графики для "<?print $this->view->content->experiment->title; ?>"</h3>
</div>

<div class="col-md-8">
	<table class="table">
		<thead>
			<td><label>#</label></td>
			<td><label>Название графика</label></td>
		</thead>
		<tbody>
			<?php foreach($this->view->content->list as $plot):?>
				<tr>
					<td>
						<? print ++$i; ?>
					</td>
					<td>
						<a href="?q=experiment/graph/<?php print $plot->exp_id;?>/<?php print $plot->id;?>">График #<?php print $plot->id;?></a>
					</td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
</div>
<div class="col-md-4">
	<a href="?q=experiment/graph/<?php print $plot->exp_id;?>/add" class="btn btn-primary">Добавить график</a>
</div>