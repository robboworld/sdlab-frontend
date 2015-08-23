<div class="row">
	<? if($this->view->form->id == 'create-experiment-form') : ?>
	<div class="col-md-offset-1 col-md-10">
		<a href="/?q=experiment/view" class="btn btn-sm btn-default">
			<span class="glyphicon glyphicon-chevron-left"></span> Все эксперименты
		</a>
	</div>
<? else: ?>
	<div class="col-md-offset-1 col-md-10">
		<a href="/?q=experiment/view/<?print $this->view->form->experiment->id; ?>" class="btn btn-sm btn-default">
			<span class="glyphicon glyphicon-chevron-left"></span> <?print $this->view->form->experiment->title; ?>
		</a>
	</div>
<? endif; ?>
</div>

<div class="row">
	<h1 class="col-md-offset-1 col-md-10"><? print $this->view->content->title; ?></h1>
	<form method="post" action="?<? print $_SERVER['QUERY_STRING']?>">
		<input type="hidden" name="form-id" value="<?print $this->view->form->id;?>">
		<div class="col-md-offset-1 form-group col-md-5">
			<input class="form-control" maxlength="80" type="text" name="experiment_title" placeholder="Название эксперимента" required="true" value="<?print $this->view->form->experiment->title;?>">
		</div>
		<div class="form-group col-md-5">
			<select class="form-control" name="setup_id">
				<option value=""> - Выбрать установку - </option>
				<? foreach ($this->view->form->setups as $setup): ?>
					<option value="<? print $setup->id; ?>" <?if($setup->id == $this->view->form->experiment->setup_id) print 'selected'?>><? print $setup->title; ?></option>
				<? endforeach; ?>
			</select>
		</div>
		<!--
			<div class="form-group col-md-6">
				<div class="row">
					<div class="col-md-6">
						<input type="date" class="form-control" name="experiment_date_start" value="<?print Form::dateToInput($this->view->form->experiment->DateStart_exp);?>">
					</div>
					<div class="col-md-6">
						<input type="date" class="form-control" name="experiment_date_end" value="<?print Form::dateToInput($this->view->form->experiment->DateEnd_exp);?>">
					</div>
				</div>
			</div>
			-->
		<div class="col-md-offset-1 form-group col-md-10">
			<textarea class="text-area form-control" maxlength="2000" name="experiment_comments" placeholder="Комментарий"><?print $this->view->form->experiment->comments;?></textarea>
		</div>
		<div class="button-center col-sm-offset-4 col-sm-4 col-md-offset-4 col-md-4">
			<?if($this->view->form->id == 'edit-experiment-form'): ?>
				<a href="?q=experiment/view/<?print $this->view->form->experiment->id; ?>" class="btn btn-default">Отмена</a>
			<? endif;?>
			<input type="submit" class="btn btn-success" value="<?print $this->view->form->submit->value;?>">
		</div>
	</form>
</div>
