<?
// Setup status
$setup_exists = isset($this->view->content->setup);
$setup_active = $setup_exists && $this->view->content->setup->flag;
$ownSetup = $setup_exists && ($this->view->content->setup->master_exp_id == $this->view->content->experiment->id);
$canSetupControl = $setup_exists && $ownSetup;

?>
<script>

</script>
<div class="row">
	<div class="col-md-12">
		<a href="/?q=experiment/view" class="btn btn-sm btn-default">
			<span class="glyphicon glyphicon-chevron-left"></span> Все эксперименты
		</a>
	</div>
</div>

<div class="row">
	<div class="col-md-12">
		<h3 class="ses-title"><? print mb_strtoupper(htmlspecialchars($this->view->content->session->title, ENT_QUOTES, 'UTF-8'), 'UTF-8'); ?></h3>
	</div>
	<?
	/*
	<div class="col-md-6 text-right">
		Участники: <? print htmlspecialchars($this->view->content->session->name, ENT_QUOTES, 'UTF-8'); ?>
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
						<a href="/?q=experiment/edit/<? print $this->view->content->experiment->id; ?>" class="btn btn-edit btn-sm btn-default"><span class="glyphicon glyphicon-pencil"></span></a>
						<span><? print htmlspecialchars($this->view->content->experiment->title, ENT_QUOTES, 'UTF-8'); ?></span>
					</h3>
					<div class="period-work">
						<div class="date-block">
							<div id="exp_datestart" class="text-left ln-hgt-16px">
								<? if(!empty($this->view->content->experiment->DateStart_exp))
									print System::dateformat($this->view->content->experiment->DateStart_exp); ?>
							</div>
							<div id="exp_end" class="text-left ln-hgt-16px">
								<? if(!empty($this->view->content->experiment->DateEnd_exp))
									print System::dateformat($this->view->content->experiment->DateEnd_exp); ?>
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
					<div class="">
						Установка: <b><? print htmlspecialchars($this->view->content->setup->title, ENT_QUOTES, 'UTF-8'); ?></b>
						<? if($this->view->content->setup->userCanEdit($this->session())) :?>
							<a href="/?q=setup/edit/<? print $this->view->content->setup->id; ?>" title="Редактировать установку" class="btn btn-xs btn-default"><span class="glyphicon glyphicon-pencil"></span></a>
						<? endif; ?>
					</div>
					<div class="setup-status">
						<? if ($setup_active) : ?>
						<div id="setup_status_active" class="col-md-2">
							<span class="label label-danger">Активна</span>
						</div>
						<? endif; ?>
						<? if ($ownSetup) : ?>
						<div id="setup_status_master" class="col-md-2">
							<span class="label label-info">Мастер</span>
						</div>
						<? endif; ?>
					</div>
				</td>
			</tr>
			<tr>
				<td colspan="2">
					<div class="mrg-top-5px">
						<div class="col-xs-12 col-sm-6 col-md-4">
							<div class="mrg-bot-5px">
								<div class="special-label">Число измерений: <span class="badge"><? print $this->view->content->setup->amount ? $this->view->content->setup->amount : '*'; ?></span></div>
							</div>
							<div class="mrg-bot-5px">
								<div class="special-label">Выполнено: <span id="setup_done_cnt" class="badge"><? 

									$done_cnt= 0;
									// Has monitor data
									if ($this->view->content->monitor && isset($this->view->content->monitor->info))
									{
										// TODO: need from backend API Monitor.Info about last data value (DS.last_ds) and test it to "U" with last_update date

										$created = System::cutdatemsec($this->view->content->monitor->info->Created);
										if ($this->view->content->monitor->info->Last === $created /* && $this->view->content->monitor->info->last_ds == "U" */)
										{
											// No data in rrd
										}
										else
										{
											$timestamp_last    = System::dateformat(System::cutdatemsec($this->view->content->monitor->info->Last),'U');
											$timestamp_created = System::dateformat($created,'U');

											$done_cnt = ($timestamp_last >= $timestamp_created) ?
													(int)(($timestamp_last - $timestamp_created) / $this->view->content->monitor->info->Archives[0]->Step) :
													0;
										}
									}
									echo $done_cnt;
									?></span>
								</div>
							</div>
						</div>
						<div class="col-xs-12 col-sm-6 col-md-4">
							<div class="mrg-bot-5px">
								<div class="special-label">Интервал измерений: <span class="badge"><? print $this->view->content->setup->interval; ?></span></div>
							</div>
							<div class="mrg-bot-5px">
								<div class="special-label">Осталось: <span id="setup_last_cnt" class="badge"><?php 

								$last_cnt = 0;
								// Check Setup mode
								
								if ($this->view->content->setup->amount)
								{
									$last_cnt = $this->view->content->setup->amount - $done_cnt;
									$last_cnt = ($last_cnt >= 0) ? $last_cnt : 0;
									echo $last_cnt;
								}
								else
								{
									echo '*';
								}
								?></span>
							</div>
						</div>
						<div class="col-xs-12 col-sm-6 col-md-4">
							<div class="mrg-bot-5px">
								<div class="special-label">Продолжительность: <span class="badge"><? print System::secToTime($this->view->content->setup->time()); ?></span></div>
							</div><?php 

							$stopat_title_1 = "Ориентировочное время окончания измерений";
							$stopat_title_0 = "Ориентировочное время, если начать измерения прямо сейчас";

							?>
							<div id="setup_stopat" class="mrg-bot-5px" title="<?php echo ($setup_active ? $stopat_title_1 : $stopat_title_0);?>" data-title-0="<?php echo $stopat_title_0;?>" data-title-1="<?php echo $stopat_title_1;?>">
								<div class="special-label">Завершение: <?

								$setup_stopat_text = '';
								$setup_stopat_class = '';

								// Output stop at time by active status
								if ($setup_active)
								{
									// Check Setup mode
									if ($this->view->content->setup->amount)
									{
										// Has monitor data
										if ($this->view->content->monitor && isset($this->view->content->monitor->info))
										{
											$setup_stopat_date = new DateTime(System::cutdatemsec($this->view->content->monitor->info->Created));
											$setup_stopat_date->modify('+'.$this->view->content->setup->time().' sec');
											$setup_stopat_text = $setup_stopat_date->format('Y.m.d H:i:s');

											$now = new DateTime();
											if ($now->format('U') > $setup_stopat_date->format('U'))
											{
												$setup_stopat_class = 'bg-success';
											}
										}
										else
										{
											$setup_stopat_text = 'Неизвестно';
										}
									}
									else
									{
										// Has monitor data
										if ($this->view->content->monitor && isset($this->view->content->monitor->info))
										{
											// TODO: need from backend API Monitor.Info about last data value (DS.last_ds) and test it to "U" with last_update date

											if ($this->view->content->monitor->info->StopAt !== System::nulldate())
											{
												$setup_stopat_date = new DateTime(System::cutdatemsec($this->view->content->monitor->info->StopAt));
												$setup_stopat_text = $setup_stopat_date->format('Y.m.d H:i:s');

												$now = new DateTime();
												if ($now->format('U') > $setup_stopat_date->format('U'))
												{
													$setup_stopat_class = 'bg-success';
												}
											}
											else
											{
												$setup_stopat_date = new DateTime(System::cutdatemsec($this->view->content->monitor->info->Created));
												$setup_stopat_date->modify('+'.$this->view->content->setup->time().' sec');
												$setup_stopat_text = $setup_stopat_date->format('Y.m.d H:i:s');

												$now = new DateTime();
												if ($now->format('U') > $setup_stopat_date->format('U'))
												{
													$setup_stopat_class = 'bg-success';
												}
											}
										}
										else
										{
											$setup_stopat_text = 'Неизвестно';
										}
									}
								}
								else
								{
									$setup_stopat_date = new DateTime();
									$setup_stopat_date->modify('+'.$this->view->content->setup->time().' sec');
									$setup_stopat_text = $setup_stopat_date->format('Y.m.d H:i:s');
								}
								?><span id="setup_stopat" class="badge <? echo $setup_stopat_class;?>"><? echo $setup_stopat_text;?></span></div>
							</div>
						</div>
						<div class="col-xs-12 col-sm-6 col-md-3" style="display: none;">
							<div class="mrg-bot-5px">
								Участники: {value}<? 

								// todo: get Setup Consumers list output? but no consume anymore

								?>
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
</div>
<div class="row">
	<div class="col-sm-10 col-md-10">
		<div class="row" id="widget-workspace">
		<? if( isset($this->view->content->sensors)) :?>
			<? foreach($this->view->content->sensors as $sensor): 
				$skey = '' . $sensor->id . '#' . (int)$sensor->sensor_val_id; ?>
				<div class="col-xs-6 col-sm-4 col-md-3 sensor-widget" sensor-id="<? print $skey; ?>">
					<div class="panel panel-default">
						<div class="panel-heading">
							<span class="panel-title">

								<span class="glyphicon glyphicon-eye-open"></span> <? print $sensor->name; ?>

							</span>
						</div>
						<div class="panel-body">
							<small class="pull-right">id: <? print htmlspecialchars($skey, ENT_QUOTES, 'UTF-8');; ?></small>
							<div class="widget-pane info active ">
								<h3 class="sensor-value">Подождите..</h3>
								<small class="sensor-value-name"><?
									echo ((!empty($sensor->si_notation)) ? 
										$sensor->si_notation . ' (' . $sensor->si_name . ')' : '');
								?></small>
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
			<div class="mrg-bot-5px col-xs-6 col-md-12 col-sm-12">
				<a class="btn btn-default form-control <? echo (!$canSetupControl) ? 'disabled' : ''; 
					?>" id="experiment-action" data-experiment-state="<? echo (int)$setup_active;?>" experiment-id="<? print $this->view->content->experiment->id?>" data-text-0="Старт" data-text-1="Стоп" <? echo (!$canSetupControl) ? 'disabled="disabled"' : ''; ?>><? 
					echo ($setup_active) ? 'Стоп' : 'Старт';
					?></a>
		</div>

		<div class="mrg-bot-5px col-xs-6 col-md-12 col-sm-12">
				<a class="btn btn-default form-control <? echo (!$canSetupControl) ? 'disabled' : '';
					?>" id="experiment-strob" experiment-id="<? print $this->view->content->experiment->id?>" <? echo (!$canSetupControl) ? 'disabled="disabled"' : ''; ?>>Строб</a>
		</div>
		<div class="mrg-bot-5px col-xs-6 col-md-12 col-sm-12">
				<a class="btn btn-default form-control" href="/?q=experiment/journal/<? print $this->view->content->experiment->id; ?>">Журнал</a>
		</div>
		<div class="mrg-bot-5px col-xs-6 col-md-12 col-sm-12">
				<a class="btn btn-default form-control" href="/?q=experiment/graph/<? print $this->view->content->experiment->id; ?>">Графики</a>
		</div>
	</div>
</div>

<div class="row">
	<div class="col-md-5 pull-left text-left">
		<label for="experiment-sensors-refresh" class="checkbox">
			<input type="checkbox" id="experiment-sensors-refresh" value="1" title="Автообновление показаний датчиков раз в 3 секунды"/> Автообновление показаний
		</label>
	</div>
	<div class="col-xs-6 col-sm-2 col-md-2 pull-right text-right">
	<? if(!isset($this->view->content->experiment->DateEnd_exp)) :?>
		<a href="#" class="btn btn-default form-control disabled">Завершить эксперимент</a>
	<? else : ?>
		<h4>Эксперимент завершен.</h4>
	<? endif; ?>
</div>

<? /*todo: релоад после выполнения строба, или удаление кнопки редактирования из dom */?>

<? /*todo: переверстать без таблицы. */?>