<div class="row">
	<h3 class="col-md-offset-1 col-md-10"><? print $this->view->content->title; ?></h3>
</div>
<div class="row setup-create">
<div class="col-md-offset-1 col-md-10">
	<form action="<?print $_SERVER['REQUEST_URI']; ?>" method="post">
		<input type="hidden" name="form-id" value="<? print $this->view->form->id; ?>">
		<div class="form-group setup-title">
			<input class="form-control" name="setup_title" type="text" required="true" placeholder="Название установки" value="<?print $this->view->form->setup->title;?>">
		</div>
		<? //flag нужно проверять
		if(!is_null($this->view->form->setup->flag)) : ?>
		<div class="row form-group">
			<div class="col-md-4">
				Текущее состояние
			</div>
			<div class="col-md-8 form-inline">
				<span class="label label-danger">ПРОВОДЯТСЯ ИЗМЕРЕНИЯ!</span>
			</div>
		</div>
		<? endif; ?>
		<div class="row">
			<div class="col-md-12 form-group form-horizontal">
				<div class="btn-group btn-group-justified">
					<label class="btn btn-default <?if(!empty($this->view->form->setup->time_det)) print 'active';?>">
						<input type="radio" name="setup-type" data-id="setup-type-length" value="setup-type-length" <?if(!empty($this->view->form->setup->time_det)) print 'checked';?>>
						Продолжительность
					</label>
					<label class="btn btn-default <?if(!empty($this->view->form->setup->amount)) print 'active';?>">
						<input type="radio" name="setup-type" data-id="setup-type-amount" value="setup-type-amount" <?if(!empty($this->view->form->setup->amount)) print 'checked';?>>
						Число измерений
					</label>
					<!--
					<label class="btn btn-default">
						<input type="radio" name="setup-type" data-id="setup-type-date" value="setup-type-date">
						Завершение
					</label>

					-->
				</div>
				<div class="alert alert-warning" id="setup-type-alert">
					Нужно выбрать тип измерений
				</div>
				<div id="setup-type-length" class="setup-type well">
					<div class="row form-group">
						<div class="col-xs-12 col-md-6 col-sm-5 setup-label-long">
							Продолжительность измерений
						</div>
						<div class="col-xs-12 col-md-6 col-sm-7 form-inline">
							<? $time_det = Form::formTimeObject($this->view->form->setup->time_det) ;?>
							<input type="text" name="time_det_day" class="form-control" size="1" placeholder="0" value="<? print $time_det->d; ?>"> дн.
							<input type="text" name="time_det_hour" class="form-control" size="1" placeholder="0" value="<? print $time_det->h; ?>"> ч.
							<input type="text" name="time_det_min" class="form-control" size="1" placeholder="1" value="<? print $time_det->m; ?>"> мин.
							<input type="text" name="time_det_sec" class="form-control" size="1" placeholder="1" value="<? print $time_det->s; ?>"> сек.
						</div>
					</div>
				</div>
				<div id="setup-type-amount" class="setup-type well">
					<div class="row form-group">
						<div class="col-xs-6 col-md-6 col-sm-6 setup-label">
							Число измерений
						</div>
						<div class="col-xs-6 col-md-6 col-sm-6 form-inline" >
							<input type="text" name="amount" class="form-control"  size="10" placeholder="1" value="<? print $this->view->form->setup->amount; ?>">
						</div>
					</div>

				</div>
				<!--
				<div id="setup-type-date" class="setup-type well">
					<div class="row">
						<div class="col-md-4">
							Завершение
							<small>{нужно добавить поле в бд}</small>
						</div>
						<div class="col-md-8 form-inline">
							<input type="text" class="form-control" placeholder="Дата" size="13">&nbsp;
							<input type="text" class="form-control" placeholder="Время" size="12">
							<br><small>{подключить jquery.datepicker & jquery.timepicker}</small>
						</div>
					</div>
				</div>
				-->
				<div class="well">
					<div class="row form-group">
						<div class="col-xs-6 col-md-6 col-sm-6 setup-label">
							Интервал измерений
						</div>
						<div class="col-xs-6 col-md-6 col-sm-6 form-inline">
							<!--
							<input type="text" class="form-control" size="4" placeholder="0"> дн.
							<input type="text" class="form-control" size="4" placeholder="0"> час.
							-->
							<input type="text" name="interval" class="form-control"  required="true" size="10" placeholder="10" value="<? print $this->view->form->setup->interval; ?>"> сек.
						</div>
					</div>
					<div class="row form-group">
						<div class="col-xs-6 col-md-6 col-sm-6 setup-label mrg-top-m5px">
							Число повторных измерений<br>(при обнаружении ошибок)
						</div>
						<div class="col-xs-6 col-md-6 col-sm-6 form-inline">
							<input type="text" name="number_error" class="form-control" size="10" placeholder="0" value="<? print $this->view->form->setup->number_error; ?>">
						</div>
					</div>
					<div class="row form-group">
						<div class="col-xs-6 col-md-6 col-sm-6 setup-label">
							Интервал повторных измерений
						</div>
						<div class="col-xs-6 col-md-6 col-sm-6 form-inline">
							<input type="text" name="period_repeated_det" class="form-control" size="10" placeholder="0" value="<? print $this->view->form->setup->period_repeated_det; ?>"> сек.
						</div>
					</div>
				</div>
			</div>

		</div>
		<div class="row">
			<div class="col-md-12">
				<h4>Датчики в установке:</h4>
				<table class="table table-responsive" id="sensors-in-setup">
					<thead>
						<td><label>ID</label></td>
						<td>Имя датчика</td>
						<td></td>
					</thead>
					<tbody>
						<? if($this->view->form->sensors) : ?>
							<? foreach($this->view->form->sensors as $sensor) :?>
								<tr>
									<td><input type="hidden" name="sensors[<? print $sensor->id; ?>][id]" value="<? print $sensor->id; ?>"> <? print $sensor->id; ?></td>
									<td><input type="text" placeholder="Имя датчика" name="sensors[<? print $sensor->id; ?>][name]" class="form-control" required="true" value="<? print !empty($sensor->name) ? $sensor->name : '' ; ?>"></td>
									<td class="text-right"><a class="btn btn-sm btn-danger remove-sensor">Удалить</a></td>
								</tr>
							<? endforeach; ?>
						<? endif; ?>
					</tbody>
				</table>

				<div class="bg-info" style="display: none;">
					<h4>
						<span class="glyphicon glyphicon-info-sign"></span>
						<span>В установке нет датчиков. Выберите нужные датчики из списка и нажмите "Добавить выбранные".</span>
					</h4>
				</div>
				<hr>
				<h4>Доступные датчики:</h4>
				<table class="table table-responsive" id="sensor-list-table">
					<tbody>

					</tbody>
				</table>
				<div class="bg-info" style="display: none;">
					<h4>
						<span class="glyphicon glyphicon-info-sign"></span>
						<span>Доступных датчиков нет. Подсоедините датчики к плате и нажмите "Обновите список доступных датчиков".</span>
					</h4>
				</div>
				<div class="row sensor-block">
					<div class="mrg-bot-5px col-xs-12 col-sm-6 col-md-6">
						<a class="btn btn-default form-control" id="add-sensors"><span class="glyphicon glyphicon-arrow-up"></span> Добавить выбранные</a>
					</div>
					<div class="mrg-bot-5px col-xs-12 col-sm-6 col-md-6">
						<a class="btn btn-default form-control" id="sensors-list-update"><span class="glyphicon glyphicon-refresh"></span> Обновить список доступных датчиков</a>
					</div>
				</div>
			</div>
		</div>
</div>
</div>

<div class="row mrg-top-20px">
	<div class="button-center col-md-12">
	<div class="btn-group">
	<? if($this->view->form->id == 'edit-setup-form') : ?>
		<a href="/?q=setup/create" class="width-auto btn btn-primary form-control">Создать</a>
	<? endif; ?>
		<a href="/?q=experiment/view" class="width-auto col-md-6 btn-default btn form-control">Отменить</a>
		<input type="submit" class="width-auto btn btn-success form-control" value="<? print $this->view->form->submit->value; ?>" disabled>
	</div>
	</div>
	</form>
</div>
