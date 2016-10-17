<h3 class="label-page"><?php echo $this->view->content->title; ?></h3>
<div class="col-sm-offset-3 col-sm-6 col-md-4 col-md-offset-4 well"><?php 
//TODO: pass current anchor-fragment to destination url with javascript (window.location.hash)
?>
	<form method="post" class="form-horizontal" action="?q=session/create<?php if(isset($_GET['q'])) : ?>&destination=<?php echo urlencode('?' . $_SERVER['QUERY_STRING']); endif; ?>">
		<div class="form-group">
			<input type="hidden" name="session_new" value="true">
			<label for="session_title" class="col-sm-4 control-label">
				<?php echo L('TITLE'); ?>
			</label>
			<div class="col-sm-8 ">
				<input type="text" name="session_title" class="form-control" value="<?php echo (isset($_POST['session_title']) ? htmlspecialchars($_POST['session_title'], ENT_QUOTES, 'UTF-8') : '');?>">
			</div>
		</div>
		<div class="form-group">
			<label for="session_username" class="col-sm-4 control-label">
				<?php echo L('FULL_NAME'); ?>
			</label>
			<div class="col-sm-8 ">
				<input type="text" name="session_name" class="form-control" value="<?php echo (isset($_POST['session_name']) ? htmlspecialchars($_POST['session_name'], ENT_QUOTES, 'UTF-8') : '');?>">
			</div>
		</div>
		<div class="form-group">
			<div class="col-md-6 col-md-offset-4 col-sm-6 col-sm-offset-4">
				<input type="submit" class="form-control btn-primary" value="<?php echo L('session_START_SESSION'); ?>">
			</div>
		</div>

	</form>
</div>
<!--
<div class="col-md-6">
	<h3><?php echo L('session_RESTORE'); ?></h3>
	<form method="post" class="form-inline">
		<input type="text" name="session_key" class="form-control" placeholder="0000" required="true">
		<input type="submit" class="form-control btn btn-success" value="<?php echo L('RESTORE'); ?>">
	</form>
</div>
-->