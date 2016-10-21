<div class="col-md-offset-1 col-md-6">
	<h3><?php echo L('session_TITLE_EDIT'); ?></h3>
</div>
<div class="col-md-4 pull-right">
	<h3>
		<?php echo L('session_KEY'); ?>:
		<span class="text-danger"><?php echo htmlspecialchars($this->session()->getKey(), ENT_QUOTES, 'UTF-8'); ?></span>
	</h3>
</div>
<div class="col-md-offset-1 col-md-10">
	<form class="row" action="?<?php echo $_SERVER['QUERY_STRING'];?>" method="post">
		<input type="hidden" name="form-id" value="edit-session-form">
		<div class="form-group col-md-6">
			<label><?php echo L('MEMBER'); ?></label>
			<input type="text" class="form-control" name="session_name" placeholder="<?php echo L('FULL_NAME'); ?>" value="<?php echo htmlspecialchars($this->session()->name, ENT_QUOTES, 'UTF-8');?>" <?php if($this->session()->getUserLevel() >1 ) echo 'disabled="disabled"';?>/>
		</div>
		<div class="form-group col-md-3">
			<label><?php echo L('session_WORK_NAME'); ?></label>
			<input type="text" class="form-control" name="session_title" placeholder="<?php echo L('TITLE'); ?>" value="<?php echo htmlspecialchars($this->session()->title, ENT_QUOTES, 'UTF-8');?>" <?php if($this->session()->getUserLevel() >1 ) echo 'disabled="disabled"';?>/>
		</div>
		<div class="form-group col-md-3">
			<label><?php echo L('session_EXPIRES_TIME_DAYS'); ?></label>
			<input type="text" class="form-control" name="session_expiry" placeholder="<?php echo L('session_EXPIRES_TIME'); ?>" value="<?php echo htmlspecialchars($this->session()->expiry, ENT_QUOTES, 'UTF-8');?>" size="3" <?php if($this->session()->getUserLevel() >1 ) echo 'disabled="disabled"';?>/>
		</div>

		<div class="form-group col-md-12">
			<label><?php echo L('COMMENT'); ?></label>
			<textarea class="text-area form-control" maxlength="2000" name="session_comments" placeholder="<?php echo L('COMMENT'); ?>"><?php echo htmlspecialchars($this->session()->comments, ENT_QUOTES, 'UTF-8');?></textarea>
		</div>

		<div class="col-sm-offset-4 col-sm-4 col-md-offset-4 col-md-4 text-center">
			<div class="btn-group" style="float:none;">
				<input type="submit" class="form-control btn btn-success" value="<?php echo L('SAVE'); ?>"/>
			</div>
		</div>
	</form>
</div>
<?php if($this->session()->getUserLevel() == 3) : ?>

<div class="col-md-offset-1 col-md-10">
	<a href="?q=users/list" class="btn btn-primary">
		<span class="glyphicon glyphicon-list"></span>&nbsp;<?php echo L('users_LIST'); ?>
	</a>
</div>
<?php endif; ?>

<?php
/*
<div class="col-lg-12">
	<hr class="clearfix">
</div>
*/
?>
<div class="col-md-offset-1 col-md-10">
	<h3><?php echo L('session_EXPERIMENTS_IN_SESSION'); ?></h3>

	<?php if(isset($this->view->experiments_in_session) && !empty($this->view->experiments_in_session)) : ?>
	<table class="table">
		<thead>
			<tr>
				<td><label>#ID</label></td>
				<td><label><?php echo L('experiment_DATE_START'); ?> / <?php echo L('experiment_DATE_END'); ?></label></td>
				<td><label><?php echo L('experiment_NAME'); ?></label></td>
				<td><label><?php echo L('SETUP'); ?></label></td>
			</tr>
		</thead>
		<tbody>
			<?php foreach ($this->view->experiments_in_session as $item) : if($item) : ?>
			<tr>
				<td>#<?php echo (int)$item->id?></td>
				<td><?php
					if(!empty($item->DateStart_exp)) { echo System::dateformat('@'.$item->DateStart_exp, System::DATETIME_FORMAT1, 'now'); } ?> / <?php
					if(!empty($item->DateEnd_exp))   { echo System::dateformat('@'.$item->DateEnd_exp, System::DATETIME_FORMAT1, 'now'); } ?></td>
				<td><a href="?q=experiment/view/<?php echo (int)$item->id; ?>"><?php echo htmlspecialchars($item->title, ENT_QUOTES, 'UTF-8'); ?></a></td>
				<td><?php if ($item->_setup) { echo htmlspecialchars($item->_setup->title, ENT_QUOTES, 'UTF-8'); } ?></td>
			</tr>
			<?php endif; endforeach; ?>
		</tbody>
	</table>
	<?php endif; ?>
	<div class="col-sm-offset-4 col-sm-4 col-md-offset-4 col-md-4 text-center">
		<div class="btn-group" style="float: none;">
			<a class="btn btn-primary" href="?q=experiment"><?php echo L('experiment_NEW_EXPERIMENT'); ?></a>
		</div>
	</div>

	<div class="col-md-12 text-right">
	</div>
</div>
