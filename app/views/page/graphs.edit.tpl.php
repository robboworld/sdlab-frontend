<script src="assets/js/lib/jquery.flot.js"></script>
<script src="assets/js/lib/jquery.flot.time.min.js"></script>
<script src="assets/js/chart.js"></script>
<script>
$(document).ready(function(){
	testFlot($('#graph-workspace'))
})
</script>

<h3>Температура с 5.04.2014 по 7.04.2014</h3>
<div class="col-md-6">
	<a href="?q=page/view/experiment">Измерение температуры и атмосферного давления</a>
</div>
<div class="col-md-3">
	<?php echo htmlspecialchars($this->session()->name, ENT_QUOTES, 'UTF-8'); ?>
</div>
<!--
<div class="col-md-3">
	{GROUP}
</div>
-->

<div class="col-md-12">
	<br>
	<form>
		<div class="form-group">
			<input type="text" placeholder="<?php echo L::graph_NAME; ?>" class="form-control" value="Температура с 5.04.2014 по 7.04.2014">
		</div>

		<div class="form-group form-inline">
			<span>
				<?php echo L::graph_ABSCISSA; ?>: <input type="text" placeholder="<?php echo L::graph_NAME; ?>" class="form-control" value="t">
			</span>
			<span>
				<?php echo L::graph_SCALE; ?>: <input type="text" placeholder="<?php echo L::graph_SCALE; ?>" class="form-control" value="1" size="7">
			</span>
		</div>

		<div class="form-group form-inline row">
			<span class="col-md-3">
				<input type="text" placeholder="<?php echo L::graph_NAME; ?>" class="form-control" value="T1(t)" size="20">
			</span>
			<span class="col-md-3">
				<?php echo L::graph_EXPRESSION; ?>: <input type="text" placeholder="<?php echo L::graph_SCALE; ?>" class="form-control" value="T1" size="15">
			</span>
			<span class="col-md-3">
				<select class="form-control">
					<option style="color: rgb(175,216,248);"><?php echo L::graph_COLOR_BLUE; ?></option>
					<option><?php echo L::graph_COLOR; ?></option>
					<option><?php echo L::graph_COLOR_RED; ?></option>
				</select>
			</span>
			<span class="col-md-3">
				<?php echo L::graph_SCALE; ?>: <input type="text" placeholder="<?php echo L::graph_SCALE; ?>" class="form-control" value="1" size="7">
			</span>
		</div>
		<!--
		<div class="form-group form-inline">
			<span class="col-md-3">
				<input type="text" placeholder="<?php echo L::graph_NAME; ?>" class="form-control" value="F(U(t), l(t))" size="20">
			</span>
			<span class="col-md-3">
				<?php echo L::graph_EXPRESSION; ?>: <input type="text" placeholder="<?php echo L::graph_SCALE; ?>" class="form-control" value="{Expression}" size="15">
			</span>
			<span class="col-md-3">
				<select class="form-control">
					<option><?php echo L::graph_COLOR; ?></option>
					<option><?php echo L::graph_COLOR_RED; ?></option>
				</select>
			</span>
			<span class="col-md-3">
				<?php echo L::graph_SCALE; ?>: <input type="text" placeholder="<?php echo L::graph_SCALE; ?>" class="form-control" value="1" size="7">
			</span>
		</div>
		-->
		<a href="#" class="btn btn-default"><?php echo L::ADD; ?></a>
	</form>
</div>

<div class="clearfix"></div>
<br><br>
<div class="col-md-12" id="graph-workspace" style="width: 100%; height: 300px;">
	&nbsp;
</div>

<div class="col-md-12">
	<div class="row">
		<div class="pull-right">
			<a href="#" class="btn btn-success"><?php echo L::SAVE; ?></a>
			<a href="#" class="btn btn-default"><?php echo L::CLOSE; ?></a>
		</div>
		<div class="col-md-3">
			<input type="checkbox" checked> <?php echo L::INCLUDE_TO_REPORT; ?>
		</div>
	</div>
</div>