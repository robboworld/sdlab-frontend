<?

// Check access
$canChangeSetup = true;
if ($this->view->form->id != 'create-experiment-form')
{
	// Edit mode

	// Check Setup change access
	if ($this->view->form->cur_setup)
	{
		$canChangeSetup = !((int)$this->view->form->cur_setup->flag > 0);
	}
}

?>
<div class="row">
	<? if($this->view->form->id == 'create-experiment-form') : ?>
	<div class="col-md-offset-1 col-md-10">
		<a href="/?q=experiment/view" class="btn btn-sm btn-default">
			<span class="glyphicon glyphicon-chevron-left"></span> <? echo L::experiment_TITLE_ALL; ?>
		</a>
	</div>
	<? else: ?>
	<div class="col-md-offset-1 col-md-10">
		<a href="/?q=experiment/view/<? print (int)$this->view->form->experiment->id; ?>" class="btn btn-sm btn-default">
			<span class="glyphicon glyphicon-chevron-left"></span> <? print $this->view->form->experiment->title; ?>
		</a>
	</div>
<? endif; ?>
</div>

<div class="row">
	<h1 class="col-md-offset-1 col-md-10"><? print $this->view->content->title; ?></h1>
	<form method="post" action="?<? print $_SERVER['QUERY_STRING']?>">
		<input type="hidden" name="form-id" value="<?print $this->view->form->id;?>"/>
		<div class="col-md-offset-1 form-group col-md-5">
			<input class="form-control" maxlength="80" type="text" name="experiment_title" placeholder="<? echo L::experiment_NAME; ?>" required="required" value="<? print htmlspecialchars($this->view->form->experiment->title, ENT_QUOTES, 'UTF-8');?>"/>
		</div>
		<div class="form-group col-md-5">
			<select class="form-control" name="setup_id" <? if ($this->view->form->id != 'create-experiment-form' && !$canChangeSetup) echo 'disabled="disabled"';?>>
				<option value=""><? echo L::setup_SELECT_OPTION; ?></option>
				<? foreach ($this->view->form->setups as $setup): ?>
					<option value="<? print (int)$setup->id; ?>" <? if ($setup->id == $this->view->form->experiment->setup_id) print 'selected="selected"'; ?>><? print ((($setup->flag)?'[*] ':'') . htmlspecialchars($setup->title, ENT_QUOTES, 'UTF-8')); ?></option>
				<? endforeach; ?>
			</select>
		</div>
		<? if ($this->view->form->id != 'create-experiment-form' && !$canChangeSetup) : ?>

		<div class="form-group col-md-offset-1 col-md-10">
			<div class="alert alert-warning"><? echo L::experiment_ERROR_SETUP_CANNOT_BE_CHANGED_ACTIVE; ?></div>
		</div>
		<? endif; ?>

		<!--
			<div class="form-group col-md-6">
				<div class="row">
					<div class="col-md-6">
						<input type="date" class="form-control" name="experiment_date_start" value="<? if(!empty($this->view->form->experiment->DateStart_exp)) print Form::dateToInput('@'.$this->view->form->experiment->DateStart_exp, 'now');?>">
					</div>
					<div class="col-md-6">
						<input type="date" class="form-control" name="experiment_date_end" value="<? if(!empty($this->view->form->experiment->DateEnd_exp)) print Form::dateToInput('@'.$this->view->form->experiment->DateEnd_exp, 'now');?>">
					</div>
				</div>
			</div>
		-->
		<div class="col-md-offset-1 form-group col-md-10">
			<textarea class="text-area form-control" maxlength="2000" name="experiment_comments" placeholder="<? echo L::COMMENT; ?>"><? print htmlspecialchars($this->view->form->experiment->comments, ENT_QUOTES, 'UTF-8');?></textarea>
		</div>
		<div class="col-sm-offset-4 col-sm-4 col-md-offset-4 col-md-4 text-center">
			<? if($this->view->form->id == 'edit-experiment-form') : ?>
				<a href="?q=experiment/view/<?print (int)$this->view->form->experiment->id; ?>" class="btn btn-default"><? echo L::CANCEL; ?></a>
			<? endif;?>
			<input type="submit" class="btn btn-success" value="<?print $this->view->form->submit->value;?>"/>
		</div>
	</form>
</div>