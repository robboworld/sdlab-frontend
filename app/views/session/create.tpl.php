
<h3><? print $this->view->content->title; ?></h3>
<div class="col-md-8 col-md-offset-2 well">
	<form method="post" class="form-horizontal" action="?q=session/create<? if(isset($_GET['q'])) : ?>&destination=<? print $_GET['q']; endif; ?>">
		<div class="form-group">
			<input type="hidden" name="session_new" value="true">
			<label for="session_title" class="col-sm-4 control-label">
				Название сессии
			</label>
			<div class="col-sm-8 ">
				<input type="text" name="session_title" class="form-control" value="<? print htmlspecialchars($_POST['session_title'], ENT_QUOTES, 'UTF-8');?>">
			</div>
		</div>
		<div class="form-group">
			<label for="session_username" class="col-sm-4 control-label">
				ФИО
			</label>
			<div class="col-sm-8 ">
				<input type="text" name="session_name" class="form-control" value="<? print htmlspecialchars($_POST['session_name'], ENT_QUOTES, 'UTF-8');?>">
			</div>
		</div>
		<div class="form-group">
			<div class="col-md-8 col-md-offset-4">
				<input type="submit" class="form-control btn-primary" value="Начать новую сессию">
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