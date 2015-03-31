
<script>

</script>

<div class="col-md-12">
	<a href="/?q=experiment/view" class="btn btn-sm btn-default">
		<span class="glyphicon glyphicon-chevron-left"></span> Все эксперименты
	</a>
</div>

<div class="col-md-6">
	<h3><? print $this->view->content->session->title; ?></h3>
</div>
<div class="col-md-6 text-right">
	Участники: <? print $this->view->content->session->name; ?>
</div>

<div class="col-md-12">
	<table class="table table-responsive table-bordered table-condensed">
		<tr>
			<td class="col-md-8">
				<h3>
					<a href="/?q=experiment/edit/<? print $this->view->content->experiment->id; ?>" class="btn btn-sm btn-default"><span class="glyphicon glyphicon-pencil"></span></a>
					<? print $this->view->content->experiment->title; ?>
					<br>
					<small><? print $this->view->content->experiment->comments; ?></small>
				</h3>
			</td>
			<td>
				<div class="col-md-6">
					<div class="text-center">
						Начат
					</div>
					<div class="text-center">
						<? if(!empty($this->view->content->experiment->DateStart_exp))
							print System::dateformat($this->view->content->experiment->DateStart_exp); ?>
					</div>
				</div>
				<div class="col-md-6">
					<div class="text-center">
						Завершен
					</div>
					<div class="text-center">
						<?
						if(!empty($this->view->content->experiment->DateEnd_exp))
							print System::dateformat($this->view->content->experiment->DateEnd_exp); ?>
					</div>
				</div>
			</td>
		</tr>
		<? if(isset($this->view->content->setup)) :?>
			<tr>
				<td colspan="2">
					<div class="">
						Установка: <b><? print $this->view->content->setup->title; ?></b>
						<? if($this->view->content->setup->userCanEdit($this->session())) :?>
						<a href="/?q=setup/edit/<? print $this->view->content->setup->id; ?>" title="Редактировать установку" class="btn btn-xs btn-default"><span class="glyphicon glyphicon-pencil"></span></a>
						<? endif; ?>
					</div>
					<!--
					<div class="col-md-2">
						<span class="label label-danger">Активна</span>
					</div>
					<div class="col-md-2">
						<span class="label label-info">Мастер</span>
					</div>
					-->
				</td>
			</tr>
			<tr>
				<td colspan="2">
					<div class="col-md-12">
						<div class="col-md-3">
							<div>
								Число измерений: <? print $this->view->content->setup->amount ? $this->view->content->setup->amount : '*'; ?>
							</div>
							<div>
								Выполнено: {value}
							</div>
						</div>
						<div class="col-md-3">
							<div>
								Интервал измерений: <? print $this->view->content->setup->interval; ?>
							</div>
							<div>
								Осталось: {value}
							</div>
						</div>
						<div class="col-md-3">
							<div>
								Продолжительность: <?
									print System::secToTime($this->view->content->setup->time()); ?>
							</div>
							<div title="Ориентировочное время, если начать измерения прямо сейчас.">
								Завершение: <? print (new DateTime())->modify('+'.$this->view->content->setup->time().' sec')->format('Y.m.d H:i:s')?>
							</div>
						</div>
						<div class="col-md-3">
							<div>
								Участники: {value}
							</div>
						</div>
					</div>
				</td>
			</tr>
		<? else : ?>
			<tr>
				<td colspan="2">
					<div>
						Установка не выбрана. Нужно <a href="?q=experiment/edit/<? print $this->view->content->experiment->id;?>">выбрать установку</a>
						или
						<a href="?q=setup/create&master=<? print $this->view->content->experiment->id;?>">создать новую</a>.
					</div>
				</td>
			</tr>
		<? endif;?>
	</table>
</div>

<div class="row">
	<div class="col-md-10" id="widget-workspace">
		<? if( isset($this->view->content->sensors)) :?>
			<? foreach($this->view->content->sensors as $sensor): ?>
				<div class="col-md-3 sensor-widget" sensor-id="<? print $sensor->id; ?>">
					<div class="panel panel-default">
						<div class="panel-heading">
						<span class="panel-title">

							<span class="glyphicon glyphicon-eye-open"></span> <? print $sensor->name; ?>

						</span>
						</div>
						<div class="panel-body">
							<small class="pull-right">id: <? print $sensor->id; ?></small>
							<div class="widget-pane info active ">
								<h3 class="sensor-value">Подождите..</h3>
							</div>
						</div>
					</div>
				</div>
			<? endforeach; ?>
		<? endif; ?>
	</div>
	<div class="col-md-2">
		<a class="btn btn-default form-control disabled">Старт/Стоп</a><br><br>
		<a class="btn btn-default form-control" id="experiment-strob" experiment-id="<? print $this->view->content->experiment->id?>">Строб</a><br><br>
		<a class="btn btn-default form-control" href="?q=experiment/journal/<? print $this->view->content->experiment->id; ?>">Журнал</a><br><br>
		<a class="btn btn-default form-control " href="/?q=experiment/graph/<? print $this->view->content->experiment->id; ?>">Графики</a><br><br>
	</div>
</div>



<div class="col-md-5 pull-right text-right">
	<? if(!isset($this->view->content->experiment->DateEnd_exp)) :?>
		<a href="#" class="btn btn-default form-control disabled">Завершить эксперимент</a>
	<? else : ?>
		<h4>Эксперимент завершен.</h4>
	<? endif; ?>
</div>

<? /*todo: релоад после выполнения строба, или удаление кнопки редактирования из dom */?>

<? /*todo: переверстать без таблицы. */?>