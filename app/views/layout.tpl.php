<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<title><? print htmlspecialchars($this->view->title, ENT_QUOTES, 'UTF-8');
		echo (isset($this->app->config['lab']['page_suffix']) && $this->app->config['lab']['page_suffix']) ? (' - ' . $this->app->config['lab']['page_suffix']) : '';?></title>
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<script src="assets/js/lib/jquery-1.11.1.min.js"></script>
	<link rel="stylesheet" href="assets/bootstrap/css/bootstrap.min.css">
	<link rel="stylesheet" href="assets/bootstrap/css/bootstrap-theme.min.css">
	<link rel="stylesheet" href="assets/css/style.css">
	<script src="assets/bootstrap/js/bootstrap.min.js"></script>
	<script type="text/javascript">
	$(document).ready(function(){
		$('[data-toggle="tooltip"]').tooltip();
	});
	</script>
	<? print $this->collectJs(); ?>

	<? print $this->collectCss(); ?>

	<? print $this->genJsLang(); ?>

</head>
<body>
	<div class="container" id="navigation">
		<div class="navbar navbar-inverse">
			<div class="navbar-text">
				<div class="text-center" data-toggle="tooltip" data-placement="auto" data-delay="100" data-trigger="click hover focus" title="<? print (new DateTime())->format(System::DATETIME_FORMAT3); ?>">
					<span class="glyphicon glyphicon-time"></span>&nbsp;<? print (new DateTime())->format('H:i'); ?>
				</div>
			</div>

			<ul class="nav navbar-nav">
				<? print Menu::render($this->view->main_menu, $this->app->getUserLevel()); ?>
			</ul>

			<? if (is_object($this->app->lang)) :
				echo $this->app->lang->render();
			endif; ?>

			<? 
			$session = $this->session();

			if (is_object($session)) : ?>

				<div class="pull-right text-right col-md-5">
					<div class="btn-group ">
						<a href="?q=session/edit" id="session-name" class="btn btn-sm btn-info navbar-btn" title="<? echo L::session_EDIT; ?>">
							<? print htmlspecialchars($session->name, ENT_QUOTES, 'UTF-8'); ?>
						</a>
						<a href="?q=session/destroy" class="btn btn-sm btn-default navbar-btn"><span class="glyphicon glyphicon-log-out">&nbsp;</span><span class="hidden-xs"><? echo L::LOGOFF; ?></span></a>
						<a href="?q=session/create" class="btn btn-sm btn-default navbar-btn"><span class="glyphicon glyphicon-plus">&nbsp;</span><span class="hidden-xs"><? echo L::session_NEW_SESSION; ?></span></a>
					</div>

				</div>

			<? else : ?>

				<div class="col-md-5 col-sm-6 col-xs-6 pull-right">
					<form id="nav-buttons" class="navbar-form" action="?q=session/create<? if(isset($_GET['q'])) : ?>&destination=<? print urlencode($_GET['q']); endif; ?>" method="post">
						<div class="input-group input-group-sm">
							<input type="password" name="session_key" placeholder="<? echo L::session_KEY_EXAMPLE; ?>" title="<? echo L::session_KEY_EXAMPLE2; ?>" class="form-control">
							<span class="input-group-btn">
								<button type="submit" class="btn btn-success"><span class="glyphicon glyphicon-log-in">&nbsp;</span><span class="hidden-xs"><? echo L::RESTORE; ?></span></button>
								<a href="?q=session/create" class="btn btn-sm btn-success"><span class="glyphicon glyphicon-plus">&nbsp;</span><span class="hidden-xs"><? echo L::session_NEW_SESSION; ?></span></a>
							</span>
						</div>
					</form>
				</div>
			<? endif; ?>

		</div>
	</div>
	<div class="container-fluid" id="data-container">
		<? print $this->render(); ?>
	</div>

</body>
</html>