
<h3 class="label-page"><? print $this->view->content->title; ?></h3>
<div class="col-sm-offset-3 col-sm-6 col-md-4 col-md-offset-4 well">
	<form method="post" class="form-horizontal" action="?q=session/create<? if(isset($_GET['q'])) : ?>&destination=<? print $_GET['q']; endif; ?>">
		<div class="form-group">
			<input type="hidden" name="session_new" value="true">
			<label for="session_title" class="col-sm-4 control-label">
				Название
			</label>
			<div class="col-sm-8 ">
				<input type="text" name="session_title" class="form-control" value="<? print $_POST['session_title']?>">
			</div>
		</div>
		<div class="form-group">
			<label for="session_username" class="col-sm-4 control-label">
				ФИО
			</label>
			<div class="col-sm-8 ">
				<input type="text" name="session_name" class="form-control" value="<? print $_POST['session_name']?>">
			</div>
		</div>
		<div class="form-group">
			<div class="col-md-6 col-md-offset-4 col-sm-6 col-sm-offset-4">
				<input type="submit" class="form-control btn-primary" value="Начать сессию">
			</div>
		</div>

	</form>
</div>
<!--
<div class="col-md-6">
	<h3>Восстановить сессию</h3>
	<form method="post" class="form-inline">
		<input type="text" name="session_key" class="form-control" placeholder="0000" required="true">
		<input type="submit" class="form-control btn btn-success" value="Восстановить">
	</form>
</div>
-->