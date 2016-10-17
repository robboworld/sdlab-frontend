<?php

// Check access
$canChangeSetup = true;
if ($this->view->form->id != 'create-experiment-form')
{
	// Edit mode

	// Check Setup change access
	if ($this->view->form->cur_setup)
	{
		//$canChangeSetup = !$this->view->form->cur_setup->active;
	}
}

?>
<div class="row">
	<?php if($this->view->form->id == 'create-experiment-form') : ?>
	<div class="col-md-offset-1 col-md-10">
		<a href="/?q=experiment/view" class="btn btn-sm btn-default">
			<span class="glyphicon glyphicon-chevron-left"></span> <?php echo L('experiment_TITLE_ALL'); ?>
		</a>
	</div>
	<?php else: ?>
	<div class="col-md-offset-1 col-md-10">
		<a href="/?q=experiment/view/<?php echo (int)$this->view->form->experiment->id; ?>" class="btn btn-sm btn-default">
			<span class="glyphicon glyphicon-chevron-left"></span> <?php echo htmlspecialchars($this->view->form->experiment->title, ENT_QUOTES, 'UTF-8'); ?>
		</a>
	</div>
<?php endif; ?>
</div>

<div class="row">
	<h1 class="col-md-offset-1 col-md-10"><?php echo htmlspecialchars($this->view->content->title, ENT_QUOTES, 'UTF-8'); ?></h1>
	<form method="post" action="?<?php echo $_SERVER['QUERY_STRING']?>">
		<input type="hidden" name="form-id" value="<?php echo htmlspecialchars($this->view->form->id, ENT_QUOTES, 'UTF-8');?>"/>
		<div class="col-md-offset-1 form-group col-md-5">
			<input class="form-control" maxlength="80" type="text" name="experiment_title" placeholder="<?php echo L('experiment_NAME'); ?>" required="required" value="<?php echo htmlspecialchars($this->view->form->experiment->title, ENT_QUOTES, 'UTF-8');?>"/>
		</div>
		<div class="form-group col-md-5">
			<select class="form-control" name="setup_id" <?php if ($this->view->form->id != 'create-experiment-form' && !$canChangeSetup) echo 'disabled="disabled"';?>>
				<option value=""><?php echo L('setup_SELECT_OPTION'); ?></option>
				<?php foreach ($this->view->form->setups as $setup): ?>
					<option value="<?php echo (int)$setup->id; ?>" <?php if ($setup->id == $this->view->form->experiment->setup_id) echo 'selected="selected"'; ?>><?php 
						$flags = array();
						if (($this->view->form->id != 'create-experiment-form') && ($setup->master_exp_id == $this->view->form->experiment->id))
						{
							$flags[] = 'M';
						}
						if ($setup->session_key == $this->session()->getKey())
						{
							$flags[] = 'O';
						}
						//TODO: add Active in this experiment/anywhere(?) flag to item title (need get active monitors for all setups in list before)
						//$flags[] = '*';
						if (!empty($flags))
						{
							echo  '[' . implode('', $flags) . '] ';
						}

						echo htmlspecialchars($setup->title, ENT_QUOTES, 'UTF-8');
					?></option>
				<?php endforeach; ?>
			</select>
		</div>
		<?php if ($this->view->form->id != 'create-experiment-form' && !$canChangeSetup) : ?>

		<div class="form-group col-md-offset-1 col-md-10">
			<div class="alert alert-warning" role="alert"><?php echo L('experiment_ERROR_SETUP_CANNOT_BE_CHANGED_ACTIVE'); ?></div>
		</div>
		<?php endif; ?>

		<!--
			<div class="form-group col-md-6">
				<div class="row">
					<div class="col-md-6">
						<input type="date" class="form-control" name="experiment_date_start" value="<?php if(!empty($this->view->form->experiment->DateStart_exp)) echo Form::dateToInput('@'.$this->view->form->experiment->DateStart_exp, 'now');?>">
					</div>
					<div class="col-md-6">
						<input type="date" class="form-control" name="experiment_date_end" value="<?php if(!empty($this->view->form->experiment->DateEnd_exp)) echo Form::dateToInput('@'.$this->view->form->experiment->DateEnd_exp, 'now');?>">
					</div>
				</div>
			</div>
		-->
		<div class="col-md-offset-1 form-group col-md-10">
			<textarea class="text-area form-control" maxlength="2000" name="experiment_comments" placeholder="<?php echo L('COMMENT'); ?>"><?php echo htmlspecialchars($this->view->form->experiment->comments, ENT_QUOTES, 'UTF-8');?></textarea>
		</div>
		<div class="col-sm-offset-4 col-sm-4 col-md-offset-4 col-md-4 text-center">
			<?php if($this->view->form->id == 'edit-experiment-form') : ?>
				<a href="?q=experiment/view/<?php echo (int)$this->view->form->experiment->id; ?>" class="btn btn-default"><?php echo L('CANCEL'); ?></a>
			<?php endif;?>
			<input type="submit" class="btn btn-success" value="<?php echo htmlspecialchars($this->view->form->submit->value, ENT_QUOTES, 'UTF-8');?>"/>
		</div>
	</form>
</div>
