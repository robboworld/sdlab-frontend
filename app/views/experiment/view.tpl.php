<? 
/*
<div class="row">
	<div class="col-md-12">
		<a href="/?q=experiment/view" class="btn btn-sm btn-default">
			<span class="glyphicon glyphicon-chevron-left"></span> Все эксперименты
		</a>
	</div>
</div>
*/
?>
<div class="row">
	<div class="col-md-12">
		<h3 class="ses-title"><? print mb_strtoupper($this->view->content->session->title, 'UTF-8'); ?></h3>
	</div>
	<?
	/*
	<div class="col-md-6 text-right">
		Участники: <? print $this->view->content->session->name; ?>
	</div>
	*/
	?>
</div>

<div class="row exp-view">
	<div class="col-md-12">
		<table class="table table-responsive table-bordered table-condensed exp-table">
			<tr>
				<td class="col-md-12 padng-3px">
					<h3 class="exp-title">
						<a href="/?q=experiment/edit/<? print $this->view->content->experiment->id; ?>" class="btn-edit btn btn-sm btn-default"><span class="glyphicon glyphicon-pencil"></span></a>
						<span><? print $this->view->content->experiment->title; ?></span>
					</h3>
					<div class="period-work">
						<div class="date-block">
							<div class="text-left ln-hgt-16px">
								<? if(!empty($this->view->content->experiment->DateStart_exp))
									print System::dateformat($this->view->content->experiment->DateStart_exp); ?>
								<span> 03.08.2015 16:45 </span>
							</div>
							<div class="text-left ln-hgt-16px">
								<? if(!empty($this->view->content->experiment->DateEnd_exp))
									print System::dateformat($this->view->content->experiment->DateEnd_exp); ?>
								<span> 04.08.2015 18:03 </span>
							</div>
						</div>
						<div class="label-block">
							<div class="text-right ln-hgt-16px">
								<span class="label label-primary"> начат</span>
							</div>
							<div class="text-right ln-hgt-16px">
								<span class="label label-primary">завершен</span>
							</div>
						</div>
					</div>
				</td>
			</tr>

		<? if(isset($this->view->content->experiment->comments)) : ?>
			<tr>
				<td colspan="2" class="bg-default">
					<small><? print $this->view->content->experiment->comments; ?></small>
				</td>
			</tr>
		<? endif;?>

		<? if(isset($this->view->content->setup)) :?>
			<tr>
				<td colspan="2">
					<div>
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
					Участники: {value}
				</td>
			</tr>
			<tr>
				<td colspan="2">
					<div class="mrg-top-5px">
						<div class="col-xs-12 col-sm-6 col-md-4"> 
							<div class="mrg-bot-5px">
								<div class="special-label">Число измерений &nbsp <span class="badge"><? print $this->view->content->setup->amount ? $this->view->content->setup->amount : '*'; ?></span></div>
							</div>
							<div class="mrg-bot-5px">
								<div class="special-label">Выполнено &nbsp <span class="badge">0</span></div>
							</div>
						</div>

						<div class="col-xs-12 col-sm-6 col-md-4">
							<div class="mrg-bot-5px">
								<div class="special-label">Интервал измерений &nbsp <span class="badge"><? print $this->view->content->setup->interval; ?></span></div>
							</div>
							<div class="mrg-bot-5px">
								<div class="special-label">Осталось &nbsp <span class="badge">0</span></div>
							</div>
						</div>

						<div class="col-xs-12 col-sm-6 col-md-4">
							<div class="mrg-bot-5px">
								<div class="special-label"> Продолжительность  &nbsp <span class="badge"> <? print System::secToTime($this->view->content->setup->time()); ?> </span></div>
							</div>
							<div class="mrg-bot-5px" title="Ориентировочное время, если начать измерения прямо сейчас.">
								<div class="special-label"> Завершение  &nbsp <span class="badge"> <? print (new DateTime())->modify('+'.$this->view->content->setup->time().' sec')->format('Y.m.d H:i:s')?> </span></div>
							</div>
						</div>

						<?
						/*
						<div class="col-xs-12 col-sm-6 col-md-3">
							<div class="mrg-bot-5px">
								Участники: {value}
							</div>
						</div>
						*/
						?>

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
</div>
<div class="row">
	<div class="col-sm-10 col-md-10" >
		<div class="row" id="widget-workspace">
		<? if( isset($this->view->content->sensors)) :?>
			<? foreach($this->view->content->sensors as $sensor): ?>
				<div class="col-xs-6 col-sm-4 col-md-3 sensor-widget" sensor-id="<? print $sensor->id; ?>">
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
	</div>
	<div class="float-left col-xs-12 col-sm-2 col-md-2">
		<div class="row">
			<div class="mrg-bot-5px col-xs-6 col-md-12 col-sm-12"><a class="btn btn-default form-control disabled">Старт/Стоп</a></div>
			<div class="mrg-bot-5px col-xs-6 col-md-12 col-sm-12"><a class="btn btn-default form-control" id="experiment-strob" experiment-id="<? print $this->view->content->experiment->id?>">Строб</a></div>
			<div class="mrg-bot-5px col-xs-6 col-md-12 col-sm-12"><a class="btn btn-default form-control" href="?q=experiment/journal/<? print $this->view->content->experiment->id; ?>">Журнал</a></div>
			<div class="mrg-bot-5px col-xs-6 col-md-12 col-sm-12"><a class="btn btn-default form-control" href="/?q=experiment/graph/<? print $this->view->content->experiment->id; ?>">Графики</a></div>
		</div>
	</div>
</div>


<div class="row">
	<div class="col-xs-6 col-sm-2 col-md-2 pull-right text-right">
		<? if(!isset($this->view->content->experiment->DateEnd_exp)) :?>
			<a href="#" class="btn btn-default form-control disabled">Завершить</a>
		<? else : ?>
			<h4>Эксперимент завершен.</h4>
		<? endif; ?>
	</div>
</div>

<? /*todo: релоад после выполнения строба, или удаление кнопки редактирования из dom */?>

<? /*todo: переверстать без таблицы. */?>