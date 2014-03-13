<? print $content->test; ?>
<div class="my-fluid-container">

	<h1><? print $content->title; ?></h1>
	<div class="col-lg-12 alert alert-info">
		Демонстрация работы связки JS(frontend) -> PHP(backend) -> Unix Socket(backend) -> JSON-RPC в виде программы на Go.
		Доступны два демонстрационных метода <b>Multiply</b> и <b>Divide</b>.
	</div>
</div>
<div class="my-fluid-container">
	<div class="col-lg-6">
		<h2>Arith.Multiply</h2>
		<form class="form-inline" id="form-multiply">
			<input type="text" name="A" class="form-control" required="" value="10">
			<input type="text" name="B" class="form-control" required="" value="42">
			<input type="submit" class="form-control btn-primary" value="Get!">
		</form>
	</div>
	<div class="col-lg-6">
		<h2>Response</h2>
		<div id="multiply-result" class="well"></div>
	</div>
</div>
<div class="my-fluid-container">
	<div class="col-lg-6">
		<h2>Arith.Divide</h2>
		<form class="form-inline" id="form-divide">
			<input type="text" name="A" class="form-control" required="" value="4">
			<input type="text" name="B" class="form-control" required="" value="2">
			<input type="submit" class="form-control btn-primary" value="Get!">
		</form>
	</div>
	<div class="col-lg-6">
		<h2>Response</h2>
		<div id="divide-result" class="well"></div>
	</div>
</div>