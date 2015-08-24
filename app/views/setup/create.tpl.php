
<h3><? print htmlspecialchars($this->view->content->title, ENT_QUOTES, 'UTF-8'); ?></h3>
<form action="<? print htmlspecialchars($_SERVER['REQUEST_URI'], ENT_QUOTES, 'UTF-8'); ?>" method="post">
<div class="col-md-10">
		<input type="hidden" name="form-id" value="<? print htmlspecialchars($this->view->form->id, ENT_QUOTES, 'UTF-8'); ?>">
		<div class="form-group">
			<input class="form-control" name="setup_title" type="text" required="required" placeholder="Название установки" value="<? print htmlspecialchars($this->view->form->setup->title, ENT_QUOTES, 'UTF-8'); ?>"/>
		</div>
		<? 
		// Check if active
		if($this->view->form->setup->flag) : ?>
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
						<input type="radio" name="setup-type" data-id="setup-type-length" value="setup-type-length" <?if(!empty($this->view->form->setup->time_det)) print 'checked="checked"';?>>
						Продолжительность
					</label>
					<label class="btn btn-default <?if(!empty($this->view->form->setup->amount)) print 'active';?>">
						<input type="radio" name="setup-type" data-id="setup-type-amount" value="setup-type-amount" <?if(!empty($this->view->form->setup->amount)) print 'checked="checked"';?>>
						Число измерений
					</label>
					<!--
					<label class="btn btn-default">
						<input type="radio" name="setup-type" data-id="setup-type-date" value="setup-type-date">
						Завершение
					</label>

					-->
				</div>
				<br>
				<div class="alert alert-warning" id="setup-type-alert">
					Нужно выбрать тип измерений
				</div>
				<div id="setup-type-length" class="setup-type well">
					<div class="row form-group">
						<div class="col-md-4">
							Продолжительность измерений
						</div>
						<div class="col-md-8 form-inline">
							<? $time_det = Form::formTimeObject($this->view->form->setup->time_det) ;?>
							<input type="text" name="time_det_day" class="form-control" size="4" placeholder="0" value="<? print $time_det->d; ?>"> дн.
							<input type="text" name="time_det_hour" class="form-control" size="4" placeholder="0" value="<? print $time_det->h; ?>"> час.
							<input type="text" name="time_det_min" class="form-control" size="4" placeholder="1" value="<? print $time_det->m; ?>"> мин.
							<input type="text" name="time_det_sec" class="form-control" size="4" placeholder="1" value="<? print $time_det->s; ?>"> сек.
						</div>
					</div>
				</div>
				<div id="setup-type-amount" class="setup-type well">
					<div class="row form-group">
						<div class="col-md-4">
							Число измерений
						</div>
						<div class="col-md-8 form-inline">
							<input type="text" name="amount" class="form-control" size="10" placeholder="1" value="<? print $this->view->form->setup->amount; ?>">
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
						<div class="col-md-4">
							Интервал измерений
						</div>
						<div class="col-md-8 form-inline">
							<!--
							<input type="text" class="form-control" size="4" placeholder="0"> дн.
							<input type="text" class="form-control" size="4" placeholder="0"> час.
							-->
							<input type="text" name="interval" class="form-control" required="required" size="4" placeholder="10" value="<? print $this->view->form->setup->interval; ?>"> сек.
						</div>
					</div>
					<?
					// TODO: repeate on errors not realised in backend monitoring, need push this parameters to backend and configure RRD/RRA
					?>
					<div class="row form-group" style="display:none;">
						<div class="col-md-4">
							Число повторных измерений<br>(при обнаружении ошибок)
						</div>
						<div class="col-md-8 form-inline">
							<input type="text" name="number_error" class="form-control" size="10" placeholder="0" value="<? print $this->view->form->setup->number_error; ?>">
						</div>
					</div>
					<div class="row form-group" style="display:none;">
						<div class="col-md-4">
							Интервал повторных измерений
						</div>
						<div class="col-md-8 form-inline">
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
						<tr>
							<td><label>ID</label></td>
							<td>Название физ. вел.</td>
							<td>Имя датчика</td>
							<td></td>
						</tr>
					</thead>
					<tbody>
						<? if(isset($this->view->form->sensors)) : ?>
							<? foreach($this->view->form->sensors as $sensor) :?>
								<tr>
									<td><? print $sensor->id; ?>
										<input type="hidden" name="sensors[<? print $sensor->id; ?>][<? echo (int)$sensor->sensor_val_id; ?>][id]" value="<? print $sensor->id; ?>"/>
										<input type="hidden" name="sensors[<? print $sensor->id; ?>][<? echo (int)$sensor->sensor_val_id; ?>][val_id]" value="<? print (int)$sensor->sensor_val_id; ?>"/>
									</td>
									<td><? print htmlspecialchars($sensor->value_name, ENT_QUOTES, 'UTF-8'); ?></td>
									<td><input type="text" placeholder="Имя датчика" name="sensors[<? print $sensor->id; ?>][<? echo (int)$sensor->sensor_val_id; ?>][name]" class="form-control" required="required" value="<? print !empty($sensor->name) ? $sensor->name : '' ; ?>"/></td>
									<td class="text-right"><a class="btn btn-sm btn-danger remove-sensor">Удалить</a></td>
								</tr>
							<? endforeach; ?>
						<? endif; ?>
					</tbody>
				</table>

				<hr />
				<h4>Доступные датчики:</h4>
				<table class="table table-responsive" id="sensor-list-table">
					<thead>
						<tr>
							<th></th>
							<th>Наименование датчика</th>
							<th>Название физ. вел.</th>
							<th>Обозначение физ. вел.</th>
							<th>Название ед. изм.</th>
							<th>Нижний предел изм.</th>
							<th>Верхний предел изм.</th>
							<th>Погрешность</th>
						</tr>
					</thead>
					<tbody>

					</tbody>
				</table>
				<div class="row">
					<div class="col-md-6">
						<a class="btn btn-default form-control" id="add-sensors"><span class="glyphicon glyphicon-arrow-up"></span>Добавить выбранные</a>
					</div>
					<div class="col-md-6">
						<a class="btn btn-default form-control" id="sensors-list-update"><span class="glyphicon glyphicon-refresh"></span>Обновить список доступных датчиков</a>
					</div>
				</div>
			</div>
		</div>
</div>
<div class="col-md-2">
	<input type="submit" class="btn btn-success form-control" value="<? print $this->view->form->submit->value; ?>" disabled="disabled" />
	<br><br><a href="/?q=experiment/view" class="btn btn-danger form-control">Отменить</a>
	<? if($this->view->form->id == 'edit-setup-form') : ?>
		<br><br><a href="/?q=setup/create" class="btn btn-primary form-control">Новая установка</a>
	<? endif; ?>
	<br><br>
</div>
</form>
