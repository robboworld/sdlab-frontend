<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<title><?php echo htmlspecialchars($this->view->title, ENT_QUOTES, 'UTF-8');
		echo (isset($this->app->config['lab']['page_suffix']) && $this->app->config['lab']['page_suffix']) ? (' - ' . $this->app->config['lab']['page_suffix']) : '';?></title>
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<script src="assets/js/lib/jquery-1.11.1.min.js"></script>
	<link rel="stylesheet" href="assets/bootstrap/css/bootstrap.min.css">
	<link rel="stylesheet" href="assets/bootstrap/css/bootstrap-theme.min.css">
	<link rel="stylesheet" href="assets/font-awesome/css/font-awesome.min.css">
	<link rel="stylesheet" href="assets/css/style.css">
	<script src="assets/bootstrap/js/bootstrap.min.js"></script>
	<script type="text/javascript">
	$(document).ready(function(){
		$('[data-toggle="tooltip"]').tooltip();
	});
	</script>
	<?php echo $this->collectJs(); ?>

	<?php echo $this->collectCss(); ?>

	<?php echo $this->genJsLang(); ?>

</head>
<body>
	<div class="container" id="navigation">
		<div class="navbar navbar-inverse">
			<div class="navbar-text">
				<div class="text-center" data-toggle="tooltip" data-placement="auto" data-delay="100" data-trigger="click hover focus" title="<?php echo (new DateTime())->format(System::DATETIME_FORMAT3); ?>">
					<span class="glyphicon glyphicon-time"></span>&nbsp;<?php echo (new DateTime())->format('H:i'); ?>
				</div>
			</div>

			<ul class="nav navbar-nav">
				<?php echo Menu::render($this->view->main_menu, $this->app->getUserLevel()); ?>
			</ul>

			<?php if (is_object($this->app->lang)) :
				echo $this->app->lang->render();
			endif; ?>

			<?php 
			$session = $this->session();

			if (is_object($session)) : ?>

				<div class="pull-right text-right col-md-5">
					<div class="btn-group ">
						<a href="?q=session/edit" id="session_name" class="btn btn-sm btn-info navbar-btn" title="<?php echo L('session_EDIT'); ?>">
							<?php echo htmlspecialchars($session->name, ENT_QUOTES, 'UTF-8'); ?>
						</a>
						<a href="?q=session/destroy" class="btn btn-sm btn-default navbar-btn" title="<?php echo L('LOGOFF'); ?>"><span class="glyphicon glyphicon-log-out"></span>&nbsp;<span class="hidden-sm hidden-xs"><?php echo L('LOGOFF'); ?></span></a>
						<a href="?q=session/create" class="btn btn-sm btn-default navbar-btn" title="<?php echo L('session_NEW_SESSION'); ?>"><span class="glyphicon glyphicon-plus"></span>&nbsp;<span class="hidden-sm hidden-xs"><?php echo L('session_NEW_SESSION'); ?></span></a>
					</div>

				</div>

			<?php else : ?>

				<div class="col-md-5 col-sm-6 col-xs-6 pull-right"><?php 
					//TODO: pass current anchor-fragment to destination url with javascript (window.location.hash)
				?>

					<form id="nav_buttons" class="navbar-form" action="?q=session/create<?php if(isset($_GET['q'])) : ?>&destination=<?php echo urlencode('?' . $_SERVER['QUERY_STRING']); endif; ?>" method="post">
						<div class="input-group input-group-sm">
							<input type="password" name="session_key" placeholder="<?php echo L('session_KEY_EXAMPLE'); ?>" title="<?php echo L('session_KEY_EXAMPLE2'); ?>" class="form-control">
							<span class="input-group-btn">
								<button type="submit" class="btn btn-success"><span class="glyphicon glyphicon-log-in">&nbsp;</span><span class="hidden-xs"><?php echo L('LOGIN'); ?></span></button>
								<a href="?q=session/create" class="btn btn-sm btn-success"><span class="glyphicon glyphicon-plus">&nbsp;</span><span class="hidden-xs"><?php echo L('session_NEW_SESSION'); ?></span></a>
							</span>
						</div>
					</form>
				</div>
			<?php endif; ?>

		</div>
	</div>
	<div class="container-fluid" id="data_container">
		<?php echo $this->render(); ?>
	</div>

</body>
</html>
