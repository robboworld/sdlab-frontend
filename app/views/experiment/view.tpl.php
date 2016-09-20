<?php
// Setup status
$setup_exists    = isset($this->view->content->setup) && $this->view->content->setup;
$setup_active    = $setup_exists && Setup::isActive($this->view->content->setup->id, (int)$this->view->content->experiment->id);  //#setup_status_active
$ownSetup        = $setup_exists && ($this->view->content->setup->session_key == $this->session()->getKey());
// TODO: get Setups in Monitors ownership, need additional db queries for array of setups
$masterSetup     = $setup_exists && ($this->view->content->setup->master_exp_id == $this->view->content->experiment->id);
$canSetupControl = $setup_exists && $this->view->content->setup->userCanControl($this->session(), $this->view->content->experiment->id);

$now = new DateTime();

// Init stats
$amount             = 0;
$setup_stopat_date  = null;
$setup_stopat_text  = '';
$setup_stopat_class = '';
$stopat_title_1     = L::experiment_ESTIMATED_FINISH_TIME;
$stopat_title_0     = L::experiment_ESTIMATED_FINISH_TIME_IF_START_NOW;

// Experiment and monitors active
$experiment_active = false;
foreach ($this->view->content->monitors as $mon)
{
	if ($mon->active)
	{
		$experiment_active = true;
		break;
	}
}

if($setup_exists)
{
	// Amount of detections
	$amount = $this->view->content->setup->amount ? (int)$this->view->content->setup->amount : '*';
	// Stop at time
	$setup_stopat_date = new DateTime();
	$setup_stopat_date->modify('+'.$this->view->content->setup->time().' sec');
	$setup_stopat_text = $setup_stopat_date->format(System::DATETIME_FORMAT1);
	$setup_stopat_class .= 'muted ';
}
?>

<script type="text/javascript">
	$(document).ready(function(){
		SDExperiment.exp_id = <?php echo (int)$this->view->content->experiment->id; ?>;
		<?php if ($experiment_active) : ?>

		// Monitoring status polling
		SDExperiment.stopTimer('MonId');
		SDExperiment.updaterMonId = setInterval(function() {
			updateExperimentStatus(SDExperiment.exp_id);
		}, SDExperiment.updaterMonTime*1000);
		<?php endif;?>

	});
</script>
<div class="row">
	<div class="col-md-12">
		<a href="/?q=experiment/view" class="btn btn-sm btn-default">
			<span class="glyphicon glyphicon-chevron-left">&nbsp;</span><?php echo L::experiment_TITLE_ALL; ?>
		</a>
	</div>
</div>

<div class="row">
	<div class="col-md-12">
		<h3 class="text-center"><?php echo mb_strtoupper(htmlspecialchars($this->view->content->session->title, ENT_QUOTES, 'UTF-8'), 'UTF-8'); ?></h3>
	</div>
	<?php
	/*
	<div class="col-md-12 text-right">
		<?php echo L::MEMBERS; ?>: <?php echo htmlspecialchars($this->view->content->session->name, ENT_QUOTES, 'UTF-8'); ?>
	</div>
	*/
	?>

</div>

<div class="row exp-view">
	<div class="col-md-12">
		<table class="table table-responsive table-bordered table-condensed exp-table" data-experiment-id="<?php echo (int)$this->view->content->experiment->id;
		?>" data-setup-id="<?php echo (int)$this->view->content->experiment->setup_id;
		?>">
			<tbody>
			<tr>
				<td class="padng-3px exp-header">
					<h3 class="exp-title">
						<span class="glyphicon glyphicon-record experiment-icon-record <?php if ($experiment_active) : ?>blink text-danger<?php endif; ?>"></span>
						<span><?php echo htmlspecialchars($this->view->content->experiment->title, ENT_QUOTES, 'UTF-8'); 
						?></span><a href="/?q=experiment/edit/<?php echo (int)$this->view->content->experiment->id; ?>" class="btn btn-edit"><span class="glyphicon glyphicon-pencil"></span></a>
					</h3>
					<div class="period-work">
						<div class="date-block">
							<div id="exp_datestart" class="text-left ln-hgt-16px">
								<?php if (!empty($this->view->content->experiment->DateStart_exp))
									echo System::dateformat('@'.$this->view->content->experiment->DateStart_exp, System::DATETIME_FORMAT2, 'now'); ?>
							</div>
							<div id="exp_end" class="text-left ln-hgt-16px">
								<?php if (!empty($this->view->content->experiment->DateEnd_exp))
									echo System::dateformat('@'.$this->view->content->experiment->DateEnd_exp, System::DATETIME_FORMAT2, 'now'); ?>
							</div>
						</div>
						<div class="label-block">
							<div class="text-right ln-hgt-16px">
								<span class="label label-primary"><?php echo L::STARTED_; ?></span>
							</div>
							<div class="text-right ln-hgt-16px">
								<span class="label label-primary"><?php echo L::FINISHED_; ?></span>
							</div>
						</div>
					</div>
				</td>
			</tr>
		<?php if (isset($this->view->content->experiment->comments)) : ?>
			<tr>
				<td class="bg-default">
					<small><?php echo htmlspecialchars($this->view->content->experiment->comments, ENT_QUOTES, 'UTF-8'); ?></small>
				</td>
			</tr>
		<?php endif;?>
		<?php if ($setup_exists) :
			$setup_access_class = '';
			$setup_access_title = '';
			switch ($this->view->content->setup->access)
			{
				case Setup::$ACCESS_PRIVATE:
					$setup_access_class = 'fa fa-user fa-lg';
					$setup_access_title = L::setup_ACCESS_PRIVATE;
				break;
				case Setup::$ACCESS_SINGLE:
					$setup_access_class = 'fa fa-shield fa-lg';
					$setup_access_title = L::setup_ACCESS_SINGLE;
				break;
				case Setup::$ACCESS_SHARED:
				default:
					$setup_access_class = 'fa fa-users fa-lg';
					$setup_access_title = L::setup_ACCESS_SHARED;
				break;
			}
			?>

			<tr>
				<td>
					<div class="setup-access pull-right">
						<span class="<?php echo $setup_access_class; ?>" title="<?php echo $setup_access_title; ?>" aria-hidden="true">&nbsp;</span>
					</div>
					<div class="setup-title">
						<?php echo L::SETUP; ?>: <b><?php echo htmlspecialchars($this->view->content->setup->title, ENT_QUOTES, 'UTF-8'); ?></b>
						<?php if($this->view->content->setup->userCanEdit($this->session())) :?>
							<a href="/?q=setup/edit/<?php echo (int)$this->view->content->setup->id; ?>" title="<?php echo L::setup_EDIT; ?>" class="btn btn-xs"><span class="glyphicon glyphicon-pencil"></span></a>
						<?php endif; ?>
					</div>
					<div class="setup-status">
						<?php if ($ownSetup) : ?>
						<div id="setup_status_owner" class="col-md-2">
							<span class="label label-info"><?php echo L::setup_OWNER; ?></span>
						</div>
						<?php endif; ?>
						<div id="setup_status_active" class="col-md-2" style="<?php if (!$setup_active) : ?>display:none;<?php endif; ?>">
							<span class="label label-danger"><?php echo L::setup_ACTIVE; ?></span>
						</div>
						<?php if ($masterSetup) : ?>
						<div id="setup_status_master" class="col-md-2">
							<span class="label label-info"><?php echo L::experiment_MASTER; ?></span>
						</div>
						<?php endif; ?>
					</div>
				</td>
			</tr>
			<tr>
				<td>
					<div class="mrg-top-5px">
						<div class="col-xs-12 col-sm-6 col-md-4">
							<div class="mrg-bot-5px">
								<div class="special-label"><?php echo L::setup_DETECTIONS_COUNT; ?>: <span id="setup_amount_cnt" class="badge"><?php echo $amount; ?></span></div>
							</div>
						</div>
						<div class="col-xs-12 col-sm-6 col-md-4">
							<div class="mrg-bot-5px">
								<div class="special-label"><?php echo L::setup_DETECTIONS_PERIOD; ?>: <span id="setup_interval" class="badge"><?php echo (int)$this->view->content->setup->interval; ?></span></div>
							</div>
						</div>
						<div class="col-xs-12 col-sm-6 col-md-4">
							<div class="mrg-bot-5px">
								<div class="special-label"><?php echo L::setup_DURATION; ?>: <span id="setup_time_det" class="badge"><?php echo System::secToTime($this->view->content->setup->time()); ?></span></div>
							</div>
							<div class="mrg-bot-5px" id="setup_stopat_parent" title="<?php echo $stopat_title_0;?>">
								<div class="special-label"><?php echo L::FINISHING; ?>: <span id="setup_stopat" class="badge <?php echo $setup_stopat_class;?>"><?php echo $setup_stopat_text;?></span></div>
							</div>
						</div>
						<div class="col-xs-12 col-sm-6 col-md-3" style="display: none;">
							<div class="mrg-bot-5px">
								<?php
								// TODO: get Setup Consumers list output? but no consumers anymore
								echo L::MEMBERS; ?>: {value}
							</div>
						</div>
					</div>
				</td>
			</tr>
		<?php else : ?>
			<tr>
				<td>
					<div>
						<?php echo L::experiment_ERROR_SETUP_NOT_SELECTED . ' '
							. L::experiment_YOU_MUST_SELECT_OR_CREATE(
									'<a href="?q=experiment/edit/' . (int) $this->view->content->experiment->id . '">' . L::experiment_TO_SELECT_SETUP . '</a>',
									'<a href="?q=setup/create&master=' . (int) $this->view->content->experiment->id . '">' . L::experiment_TO_CREATE_NEW_SETUP . '</a>'
							); ?>
					</div>
				</td>
			</tr>
		<?php endif;?>
			</tbody>
		</table>

		<?php if (!empty($this->view->content->monitors)) : ?>
		<h4><?php echo L::MONITORING; ?>:</h4>
		<!-- Monitors -->
		<div class="panel-group panel-group-monitors" id="accordion-monitors" role="tablist" aria-multiselectable="true">
			<?php foreach ($this->view->content->monitors as $i => $mon) :

			// Get StopAt from Created + Setup.time(sec)
			$created = new DateTime(System::cutdatemsec($mon->info->Created));
			$created->setTimezone((new DateTime())->getTimezone());
			$created_text = $created->format(System::DATETIME_FORMAT1);

			$heading_class = ($this->view->content->experiment->setup_id && ($mon->setup_id == $this->view->content->experiment->setup_id)) ? 'heading-setup-current' : '';

			$setup_mon_exists   = isset($mon->setup) && $mon->setup && $mon->setup->id;
			$ownMonSetup        = $setup_mon_exists && ($mon->setup->session_key == $this->session()->getKey());
			$canMonSetupControl = !$setup_mon_exists || ($setup_mon_exists && $mon->setup->userCanControl($this->session(), $this->view->content->experiment->id));
			$masterMonSetup     = $setup_mon_exists && ($mon->setup->master_exp_id == $this->view->content->experiment->id);

			// Can set as current the Setup if not set as current yet and exists
			$canSetSetup = $setup_mon_exists && (!isset($this->view->content->setup) || $this->view->content->setup->id != $mon->setup_id);

			// Init stats
			$mon_done_cnt        = 0;
			$mon_remain_cnt      = 0;
			$mon_remain_cnt_text = '';
			$mon_err_cnt         = 0;
			$mon_stopat_date     = null;
			$mon_stopat_text     = '';
			$mon_stopat_class    = '';

			// Get already done count of detections
			if (isset($mon->info))
			{
				$mon_done_cnt = $mon->info->Counters->Done;
				$mon_err_cnt  = $mon->info->Counters->Err;

				// Check Setup mode
				if ($mon->amount)
				{
					$mon_remain_cnt = $mon->amount - $mon_done_cnt;
					$mon_remain_cnt = ($mon_remain_cnt >= 0) ? $mon_remain_cnt : 0;
					$mon_remain_cnt_text = $mon_remain_cnt;
				}
				else
				{
					$mon_remain_cnt_text = '*';
				}
			}

			// Stop at time calc

			// Check mode
			if ($mon->amount)
			{
				// Amount detections mode

				// + Stop At condition
				if ($mon->stopat !== System::nulldate())
				{
					$mon_stopat_date = new DateTime(System::cutdatemsec($mon->stopat));
					$mon_stopat_date->setTimezone((new DateTime())->getTimezone());
					$mon_stopat_text = $mon_stopat_date->format(System::DATETIME_FORMAT1);

					if ($now->format('U') > $mon_stopat_date->format('U'))
					{
						$mon_stopat_class .= 'alert-success ';
					}
				}
				else
				{
					// Get Approximately Stop At: Created + Monitor.time(sec)
					$mon_stopat_class .= 'muted ';

					$mon_stopat_date = new DateTime(System::cutdatemsec($mon->created));
					$mon_stopat_date->setTimezone((new DateTime())->getTimezone());
					$mon_stopat_date->modify('+'.$mon->time().' sec');
					$mon_stopat_text = $mon_stopat_date->format(System::DATETIME_FORMAT1);

					if ($now->format('U') > $mon_stopat_date->format('U'))
					{
						//$mon_stopat_class .= 'alert-success ';
					}
				}
			}
			else
			{
				// StopAt mode

				if ($mon->stopat !== System::nulldate())
				{
					$mon_stopat_date = new DateTime(System::cutdatemsec($mon->stopat));
					$mon_stopat_date->setTimezone((new DateTime())->getTimezone());
					$mon_stopat_text = $mon_stopat_date->format(System::DATETIME_FORMAT1);

					if ($now->format('U') > $mon_stopat_date->format('U'))
					{
						$mon_stopat_class .= 'alert-success ';
					}
				}
				else
				{
					$mon_stopat_class .= 'muted ';
					$mon_stopat_text   = L::TIME_UNKNOWN;
				}
			}
			?>

			<div id="panelMon<?php echo $i; ?>" class="panel panel-primary monitor-panel <?php if ($mon->active) : ?>monitor-active<?php endif;
					?>" data-monitor-id="<?php echo (int)$mon->id;
					?>" data-monitor-uuid="<?php echo $mon->uuid;
					?>" data-monitor-setupid="<?php echo (int)$mon->setup_id;
					?>" data-monitor-expid="<?php echo (int)$mon->exp_id;
			?>">
				<div class="panel-heading <?php echo $heading_class; ?>" role="tab" id="panelMonHeading<?php echo $i; ?>">
					<div class="pull-right panel-collapse-control">
						<a href="#collapseMon<?php echo $i; ?>" role="button" class="btn-none" data-toggle="collapse" aria-expanded="<?php echo $mon->active ? 'true' : 'false'; ?>" aria-controls="#collapseMon<?php echo $i; ?>">
							<span class="glyphicon <?php echo $mon->active ? 'glyphicon-chevron-down' : 'glyphicon-chevron-up'; ?>"></span>
						</a>
					</div>
					<div class="btn-group dropdown-monitor pull-right hidden">
						<a href="#" class="btn-none dropdown-toggle" data-target="#" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">
							<span class="glyphicon glyphicon-cog"></span>
						</a>
						<ul class="dropdown-menu dropdown-menu-right">
							<li class="monitor-active-hidden" style="<?php if ($mon->active) : ?>display:none;<?php endif; ?>">
								<a href="#" onclick="alert('TODO: Remove monitor info');" ><?php echo L::REMOVE; ?></a>
							</li>
							<li role="separator" class="divider"></li>
							<?php if ($setup_mon_exists) : ?>
							<li>
								<a href="#"><?php echo L::setup_EDIT; ?></a>
							</li>
							<?php endif; ?>
							<li>
								<a href="#" onclick="alert('TODO: Show sensors info');"><?php echo L::sensor_SHOW_ITEMS; ?></a>
							</li>
							<li style="<?php if (!$canSetSetup) : ?>display:none;<?php endif; ?>">
								<a href="#"><?php echo L::setup_SET_; ?></a>
							</li>
						</ul>
					</div>
					<h5 class="panel-title">
						<span class="glyphicon glyphicon-record monitor-icon-record <?php
							if ($mon->active) : ?>blink text-danger<?php endif; ?>"></span>
						<span class="glyphicon glyphicon-exclamation-sign monitor-icon-errors" title="<?php
							echo L::ERRORS . ': ' . (int)$mon_err_cnt; ?>" <?php
							if ((int)$mon_err_cnt == 0) : ?>style="display:none;"<?php endif; ?>></span>&nbsp;
						<a role="button" title="<?php echo L::MONITORING . ': ' . $mon->uuid; ?>" data-toggle="collapse" <?php
							//echo 'data-parent="#accordion-monitors"';  // uncomment for single panel incollapsed
							?> href="#collapseMon<?php echo $i; ?>" aria-expanded="<?php echo $mon->active ? 'true' : 'false'; ?>" aria-controls="#collapseMon<?php echo $i; ?>"><?php
							echo L::MONITORING; ?>: <b><?php echo L::FROM_ . '&nbsp;' . $created_text; ?></b>
						</a>
					</h5>
				</div>
				<div id="collapseMon<?php echo $i; ?>" class="panel-collapse collapse<?php echo $mon->active ? ' in' : ''; ?>" role="tabpanel" aria-labelledby="panelMonHeading<?php echo $i; ?>">
					<table class="table table-responsive table-bordered table-condensed mon-table">
						<tbody>
						<tr>
							<td>
								<div>
									<?php if ($setup_mon_exists) : ?>
										<?php echo L::SETUP; ?>: <b><?php echo htmlspecialchars($mon->setup->title, ENT_QUOTES, 'UTF-8'); ?></b>
									<?php endif; ?>
									<?php if ($canSetSetup) :?>
										<div class="" style="display:none;">
											<a href="/?q=experiment/setsetup/<?php echo (int)$this->view->content->experiment->id; ?>&setup_id=<?php echo (int)$mon->setup_id; ?>" class="small" title="<?php echo L::setup_SET_; ?>"><span class="glyphicon glyphicon-new-window"></span>&nbsp;<?php echo L::setup_SET_; ?></a>
										</div>
									<?php endif; ?>
									<?php if ($setup_mon_exists) :
										$setup_access_class = '';
										$setup_access_title = '';
										switch ($mon->setup->access)
										{
											case Setup::$ACCESS_PRIVATE:
												$setup_access_class = 'fa fa-user fa-lg';
												$setup_access_title = L::setup_ACCESS_PRIVATE;
											break;
											case Setup::$ACCESS_SINGLE:
												$setup_access_class = 'fa fa-shield fa-lg';
												$setup_access_title = L::setup_ACCESS_SINGLE;
											break;
											case Setup::$ACCESS_SHARED:
											default:
												$setup_access_class = 'fa fa-users fa-lg';
												$setup_access_title = L::setup_ACCESS_SHARED;
											break;
										}
									?>
										<div class="setup-access pull-right">
											<span class="<?php echo $setup_access_class; ?>" title="<?php echo $setup_access_title; ?>" aria-hidden="true">&nbsp;</span>
										</div>
									<?php endif; ?>
								</div>
								<div class="col-xs-10 col-md-10">
									<div class="setup-status">
										<?php if ($ownMonSetup) : ?>
										<div class="col-md-2 setup-status-owner">
											<span class="label label-info"><?php echo L::setup_OWNER; ?></span>
										</div>
										<?php endif; ?>
										<?php if ($masterMonSetup) : ?>
										<div class="col-md-2 setup-status-master">
											<span class="label label-info"><?php echo L::experiment_MASTER; ?></span>
										</div>
										<?php endif; ?>
									</div>
								</div>
								<div class="col-xs-2 col-md-2">
									<div class="text-right monitor-control-state">
										<span class="monitor-control-waiting" style="display:none;"><span class="glyphicon glyphicon-refresh spin"></span><span class="btn-text hidden">&nbsp;<?php echo L::RUNNING_; ?></span></span>
									</div>
								</div>
								<div class="col-xs-12 col-md-12">
									<div class="text-right">
										<div class="monitor-error-text text-danger" style="display:none;"></div>
									</div>
								</div>
							</td>
						</tr>
						<tr>
							<td>
								<div class="mrg-top-5px">
									<div class="col-xs-12 col-sm-10 col-md-10">
										<div class="col-xs-12 col-sm-6 col-md-4">
											<?php if ($mon->amount) : ?>
											<div class="mrg-bot-5px">
												<div class="special-label"><?php echo L::setup_DETECTIONS_COUNT; ?>: <span class="badge monitor-amount-cnt"><?php echo $mon->amount; ?></span></div>
											</div>
											<?php endif; ?>

											<div class="mrg-bot-5px">
												<div class="special-label"><?php echo L::DONE; ?>: <span class="badge monitor-done-cnt"><?php echo $mon_done_cnt; ?></span></div>
											</div>

											<?php if ($mon->amount) : ?>
											<div class="mrg-bot-5px">
												<div class="special-label"><?php echo L::TIME_REMAIN; ?>: <span class="badge monitor-remain-cnt"><?php echo $mon_remain_cnt_text; ?></span></div>
											</div>
											<?php endif; ?>
										</div>
										<div class="col-xs-12 col-sm-6 col-md-4">
											<div class="mrg-bot-5px">
												<div class="special-label"><?php echo L::setup_DETECTIONS_PERIOD; ?>: <span class="badge"><?php echo (int)$mon->interval; ?></span></div>
											</div>
										</div>
										<div class="col-xs-12 col-sm-6 col-md-4">
											<?php if ($mon->duration) : ?>
											<div class="mrg-bot-5px">
												<div class="special-label"><?php echo L::setup_DURATION; ?>: <span class="badge monitor-duration"><?php echo System::secToTime($mon->duration); ?></span></div>
											</div>
											<?php endif; ?>

											<div class="mrg-bot-5px" class="monitor-stopat-parent" title="<?php echo $stopat_title_1;?>">
												<div class="special-label"><?php echo L::FINISHING; ?>: <span class="badge monitor-stopat <?php echo $mon_stopat_class;?>"><?php echo $mon_stopat_text;?></span></div>
											</div>
										</div>
									</div>
									<div class="col-xs-12 col-sm-2 col-md-2">
										<!-- Monitor control -->
										<div class="pull-right btn-group-vertical btn-group-sm monitor-control" role="group" aria-label="...">
											<button type="button" class="btn btn-default monitor-stop <?php
												echo ((!$canMonSetupControl || !$mon->active) ? 'disabled' : '');
												?>" data-text="<?php echo L::STOP; ?>" <?php
												echo ((!$canMonSetupControl || !$mon->active) ? 'disabled="disabled"' : '');
											?>><span class="glyphicon glyphicon-stop"></span><span class="btn-text hidden">&nbsp;<?php echo L::STOP; ?></span></button>
											<button type="button" class="btn btn-default monitor-strob <?php
												echo ((!$canMonSetupControl) ? 'disabled' : '');
												?>" data-text="<?php echo L::STROBE; ?>" <?php
												echo ((!$canMonSetupControl) ? 'disabled="disabled"' : '');
											?>><span class="glyphicon glyphicon-step-forward"></span><span class="btn-text hidden">&nbsp;<?php echo L::STROBE; ?></span></button>
											<button type="button" class="btn btn-default monitor-remove <?php
												echo ($mon->active ? 'disabled' : '');
												?>" data-text="<?php echo L::REMOVE; ?>" <?php
												echo ($mon->active ? 'disabled="disabled"' : '');
											?>><span class="glyphicon glyphicon-trash"></span><span class="btn-text hidden">&nbsp;<?php echo L::REMOVE; ?></span></button>
										</div>
										<!-- End Monitor control -->
									</div>
									<div class="col-xs-12 col-sm-12 col-md-12" style="display: none;">
										<div class="mrg-bot-5px">
											<?php
											// TODO: get Setup Consumers list output? but no consumers anymore
											echo L::MEMBERS; ?>: {value}
										</div>
									</div>
								</div>
							</td>
						</tr>
						<?php
						/*
						?>
						<tr>
							<td>
								<div class="pull-right text-right">
								<?php
								if (!isset($this->view->content->experiment->DateEnd_exp) || empty($this->view->content->experiment->DateEnd_exp)) : ?>
									<!-- <a href="#" class="btn btn-default form-control disabled"><?php echo L::FINISH; ?></a> -->
								<?php else : ?>
									<span><?php echo L::experiment_FINISHED; ?></span>
								<?php endif;
								</div>
							</td>
						</tr>
						<?php
						*/
						?>
						</tbody>
					</table>
				</div>
			</div>
			<?php endforeach; ?>

		</div>
		<!-- End Monitors -->
		<?php endif; ?>

	</div>
</div>
<?php if (isset($this->view->content->sensors) && !empty($this->view->content->sensors)) : ?>
<h4><?php echo L::SENSORS; ?>:</h4>
<?php endif; ?>
<div class="row">
	<div class="col-sm-10 col-md-10">
		<div class="row" id="widget-workspace">
		<?php if (isset($this->view->content->sensors)) :?>
			<?php foreach($this->view->content->sensors as $sensor):
				$skey = '' . $sensor->sensor_id . '#' . (int)$sensor->sensor_val_id; ?>
				<div class="col-xs-6 col-sm-4 col-md-3 sensor-widget" data-sensor-id="<?php echo $skey; ?>">
					<div class="panel panel-default">
						<div class="panel-heading">
							<span class="panel-title">

								<span class="glyphicon glyphicon-eye-open sensor-icon-btn" style="cursor:pointer;"></span> <?php echo htmlspecialchars($sensor->name, ENT_QUOTES, 'UTF-8'); ?>

							</span>
						</div>
						<div class="panel-body">
							<small class="pull-right">id: <?php echo htmlspecialchars($skey, ENT_QUOTES, 'UTF-8'); ?></small>
							<div class="widget-pane info active">
								<h3 class="sensor-value"><?php echo L::PLEASE_WAIT; ?></h3>
								<small class="sensor-value-name"><?php
									echo htmlspecialchars(((!empty($sensor->si_notation)) ?
											constant('L::sensor_VALUE_SI_NOTATION_' . strtoupper($sensor->value_name) . '_' . strtoupper($sensor->si_notation))
											. ' (' . constant('L::sensor_VALUE_SI_NAME_' . strtoupper($sensor->value_name) . '_' . strtoupper($sensor->si_name)) . ')' : '')
										, ENT_QUOTES, 'UTF-8');
								?></small>
							</div>
						</div>
					</div>
				</div>
			<?php endforeach; ?>
		<?php endif; ?>
		</div>
	</div>
	<div class="col-xs-12 col-sm-2 col-md-2 pull-left">
		<!-- Experiment control -->
		<div class="row">
			<div class="mrg-bot-5px col-xs-6 col-md-12 col-sm-12">
				<button type="button" id="experiment_action" class="btn btn-default form-control <?php
					echo (!$canSetupControl) ? 'disabled' : '';?>" <?php
					echo (!$canSetupControl) ? 'disabled="disabled"' : '';
					?> data-text-0="<?php echo L::START; ?>" data-text-1="<?php echo L::STOP; ?>" data-icon-0="glyphicon-play" data-icon-1="glyphicon-stop">
					<span class="glyphicon <?php echo ($setup_active) ? 'glyphicon-stop' : 'glyphicon-play'; ?>"></span>&nbsp;<span class="btn-text" ><?php echo ($setup_active) ? L::STOP : L::START;?></span>
				</button>
			</div>
			<div class="mrg-bot-5px col-xs-6 col-md-12 col-sm-12">
				<button type="button" id="experiment_strob" class="btn btn-default form-control <?php echo (!$canSetupControl) ? 'disabled' : '';
					?>" <?php echo (!$canSetupControl) ? 'disabled="disabled"' : ''; ?>>
					<span class="glyphicon glyphicon-step-forward"></span>&nbsp;<span class="btn-text"><?php echo L::STROBE;?></span>
				</button>
			</div>
			<div class="mrg-bot-5px col-xs-6 col-md-12 col-sm-12">
				<a class="btn btn-default form-control" href="/?q=experiment/journal/<?php echo (int)$this->view->content->experiment->id; ?>">
					<span class="glyphicon glyphicon-list-alt"></span>&nbsp;<?php echo L::JOURNAL; ?>
				</a>
			</div>
			<div class="mrg-bot-5px col-xs-6 col-md-12 col-sm-12">
				<a class="btn btn-default form-control" href="/?q=experiment/graph/<?php echo (int)$this->view->content->experiment->id; ?>">
					<span class="glyphicon glyphicon-stats"></span>&nbsp;<?php echo L::GRAPHS; ?>
				</a>
			</div>
		</div>
		<!-- End Experiment control -->
		<div class="row">
			<div class="col-xs-12 col-md-12">
				<div id="experiment_control_state" class="text-center">
					<span id="experiment_control_waiting" style="display:none;"><span class="glyphicon glyphicon-refresh spin"></span><span class="btn-text hidden">&nbsp;<?php echo L::RUNNING_; ?></span></span>
				</div>
			</div>
		</div>
	</div>
	<div class="col-xs-12 col-md-12">
		<div class="text-right">
			<div id="experiment_error_text" class="text-danger" style="display:none;"></div>
		</div>
	</div>
</div>

<div class="row">
	<div class="col-md-5 pull-left text-left">
		<label for="experiment_sensors_refresh" class="checkbox">
			<input type="checkbox" id="experiment_sensors_refresh" value="1" title="<?php echo L::experiment_AUTO_REFRESH_VALUES_TITLE('3'); ?>"/> <?php echo L::experiment_AUTO_REFRESH_VALUES; ?>
		</label>
	</div>
</div>
