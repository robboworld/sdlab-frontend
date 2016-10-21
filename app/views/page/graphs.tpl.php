<div class="alert alert-warning">
<span class="glyphicon glyphicon-exclamation-sign"></span>
{Page template}
</div>
<div class="col-md-12">
	<div class="col-md-4 pull-right">
		<h3>{FULL_NAME} {GROUP}</h3>
	</div>
	<h3><?php echo L('graph_TITLE_GRAPHS_FOR_2',array('<a href="?q=page/view/experiment">{Experiment name}</a>')); ?></h3>

</div>
<div class="col-md-8">
	<table class="table table-responsive">
		<thead>
			<tr>
				<td></td>
				<td></td>
				<td><?php echo L('INCLUDE_TO_REPORT'); ?></td>
			</tr>
		</thead>
		<tbody>
			<tr>
				<td><a href="?q=page/view/graphs.edit">{Graph name}</a></td>
				<td>{Date/time create/edit}</td>
				<td><input type="checkbox"></td>
			</tr>
			<tr>
				<td><a href="?q=page/view/graphs.edit">{Graph name}</a></td>
				<td>{Date/time create/edit}</td>
				<td><input type="checkbox"></td>
			</tr>
			<tr>
				<td><a href="?q=page/view/graphs.edit">{Graph name}</a></td>
				<td>{Date/time create/edit}</td>
				<td><input type="checkbox"></td>
			</tr>
			<tr>
				<td><a href="?q=page/view/graphs.edit">{Graph name}</a></td>
				<td>{Date/time create/edit}</td>
				<td><input type="checkbox"></td>
			</tr>
		</tbody>
	</table>
	<a href="?q=page/view/graphs.edit" class="btn btn-default"><?php echo L('ADD'); ?></a>
</div>