<? if($this->view->form->id == 'create-experiment-form') : ?>
<div class="col-md-12">
	<a href="/?q=experiment/view" class="btn btn-sm btn-default">
		<span class="glyphicon glyphicon-chevron-left"></span> Все эксперименты
	</a>
</div>
<? else: ?>
<div class="col-md-12">
	<a href="/?q=experiment/view/<?print $this->view->form->experiment->id; ?>" class="btn btn-sm btn-default">
		<span class="glyphicon glyphicon-chevron-left"></span> <?print $this->view->form->experiment->title; ?>
	</a>
</div>
<? endif; ?>

<div class="col-md-12">
	<h1><? print $this->view->content->title; ?></h1>
	<form method="post" action="?<? print $_SERVER['QUERY_STRING']?>">
		<input type="hidden" name="form-id" value="<?print $this->view->form->id;?>">
		<div class="form-group col-md-6">
			<input class="form-control" type="text" name="experiment_title" placeholder="Название эксперимента" required="true" value="<?print $this->view->form->experiment->title;?>">
		</div>
		<div class="form-group col-md-6">
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
		<div class="form-group col-md-12">
			<input type="text" class="form-control" name="experiment_comments" placeholder="Комментарий" value="<?print $this->view->form->experiment->comments;?>">
		</div>
		<div class="btn-group pull-right">
			<?if($this->view->form->id == 'edit-experiment-form'): ?>
				<a href="?q=experiment/view/<?print $this->view->form->experiment->id; ?>" class="btn btn-default">Отмена</a>
			<? endif;?>
			<input type="submit" class="btn btn-success" value="<?print $this->view->form->submit->value;?>">
		</div>
	</form>
</div>