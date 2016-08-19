<?php
// Setup status
$setup_exists    = isset($this->view->content->setup) && $this->view->content->setup;
$setup_active    = $setup_exists && $this->view->content->setup->flag;  //#setup_status_active
$ownSetup        = $setup_exists && ($this->view->content->setup->session_key == $this->session()->getKey());
$masterSetup     = $setup_exists && ($this->view->content->setup->master_exp_id == $this->view->content->experiment->id);
$canSetupControl = $setup_exists && $this->view->content->setup->userCanControl($this->session(), $this->view->content->experiment->id);

// Init stats
$amount          = 0;       //#setup_amount_cnt
$done_cnt        = 0;       //#setup_done_cnt
                            //#setup_interval
$remain_cnt      = 0;       //#setup_remain_cnt
$remain_cnt_text = '';

//#setup_time_det

$now = new DateTime();
//#setup_stopat_parent
$stopat_title_1 = L::experiment_ESTIMATED_FINISH_TIME;
$stopat_title_0 = L::experiment_ESTIMATED_FINISH_TIME_IF_START_NOW;
$setup_stopat_date  = null;
$setup_stopat_text  = '';   //#setup_stopat
$setup_stopat_class = '';
$finished = null;

#experiment-action

if($setup_exists)
{
	// Amount of detections
	// Check Setup mode
	$amount = $this->view->content->setup->amount ? (int)$this->view->content->setup->amount : '*';

	// Get already done count of detections
	if ($this->view->content->monitor && isset($this->view->content->monitor->info))
	{

		// TODO: need from backend API Monitor.Info about last data value (DS.last_ds) and test it to "U" with last_update date

		$dt_created = new DateTime(System::cutdatemsec($this->view->content->monitor->info->Created));
		$dt_last = new DateTime(System::cutdatemsec($this->view->content->monitor->info->Last));
		if ($dt_last == $dt_created /* && $this->view->content->monitor->info->last_ds == "U" */)
		{
			// No data in rrd
		}
		else
		{
			$timestamp_created = $dt_created->format('U');
			$timestamp_last    = $dt_last->format('U');

			$done_cnt = ($timestamp_last >= $timestamp_created) ?
					(int)(($timestamp_last - $timestamp_created) / $this->view->content->monitor->info->Archives[0]->Step) :
					0;
		}
	}

	// Remain detections
	// Check Setup mode
	if ($this->view->content->setup->amount)
	{
		$remain_cnt = $this->view->content->setup->amount - $done_cnt;
		$remain_cnt = ($remain_cnt >= 0) ? $remain_cnt : 0;
		$remain_cnt_text = $remain_cnt;
	}
	else
	{
		$remain_cnt_text = '*';
	}

	// Stop at time
	if ($setup_active)
	{
		// Check Setup mode
		if ($this->view->content->setup->amount)
		{
			// Amount detections mode

			// Has monitor data
			if ($this->view->content->monitor && isset($this->view->content->monitor->info))
			{
				// Get StopAt from Created + Setup.time(sec)
				$setup_stopat_date = new DateTime(System::cutdatemsec($this->view->content->monitor->info->Created));
				$setup_stopat_date->setTimezone((new DateTime())->getTimezone());
				$setup_stopat_date->modify('+'.$this->view->content->setup->time().' sec');
				$setup_stopat_text = $setup_stopat_date->format(System::DATETIME_FORMAT1);

				if ($now->format('U') > $setup_stopat_date->format('U'))
				{
					$setup_stopat_class = 'alert-success';
					$finished = true;
				}
				else 
				{
					$finished = false;
				}
			}
			else
			{
				$setup_stopat_text = L::TIME_UNKNOWN;
			}
		}
		else
		{
			// StopAt mode

			// Has monitor data
			if ($this->view->content->monitor && isset($this->view->content->monitor->info))
			{
				// TODO: need from backend API Monitor.Info about last data value (DS.last_ds) and test it to "U" with last_update date

				if ($this->view->content->monitor->info->StopAt !== System::nulldate())
				{
					$setup_stopat_date = new DateTime(System::cutdatemsec($this->view->content->monitor->info->StopAt));
					$setup_stopat_date->setTimezone((new DateTime())->getTimezone());
					$setup_stopat_text = $setup_stopat_date->format(System::DATETIME_FORMAT1);

					if ($now->format('U') > $setup_stopat_date->format('U'))
					{
						$setup_stopat_class = 'alert-success';
						$finished = true;
					}
					else
					{
						$finished = false;
					}
				}
				else
				{
					$setup_stopat_date = new DateTime(System::cutdatemsec($this->view->content->monitor->info->Created));
					$setup_stopat_date->setTimezone((new DateTime())->getTimezone());
					$setup_stopat_date->modify('+'.$this->view->content->setup->time().' sec');
					$setup_stopat_text = $setup_stopat_date->format(System::DATETIME_FORMAT1);

					if ($now->format('U') > $setup_stopat_date->format('U'))
					{
						$setup_stopat_class = 'alert-success';
						$finished = true;
					}
					else
					{
						$finished = false;
					}
				}
			}
			else
			{
				$setup_stopat_text = L::TIME_UNKNOWN;
			}
		}
	}
	else
	{
		$setup_stopat_date = new DateTime();
		$setup_stopat_date->modify('+'.$this->view->content->setup->time().' sec');
		$setup_stopat_text = $setup_stopat_date->format(System::DATETIME_FORMAT1);
	}
}
?>

<script type="text/javascript">
<!--
    $(document).ready(function(){
        SDExperiment.exp_id = <?php echo (int)$this->view->content->experiment->id; ?>;
        <?php if($setup_exists && $setup_active && $masterSetup && ($finished === false)) : ?>

        // Monitoring status polling
        SDExperiment.stopTimer('MonId');
        SDExperiment.updaterMonId = setInterval(function() {
            updateExperimentStatus(SDExperiment.exp_id);
        }, SDExperiment.updaterMonTime*1000);
        $('#setup_status_active i.glyphicon').addClass('blink').show();
        <?php endif;?>

    });
//-->
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
	<div class="col-md-6 text-right">
		<?php echo L::MEMBERS; ?>: <?php echo htmlspecialchars($this->view->content->session->name, ENT_QUOTES, 'UTF-8'); ?>
	</div>
	*/
	?>
</div>

<div class="row exp-view">
	<div class="col-md-12">
		<table class="table table-responsive table-bordered table-condensed exp-table">
			<tbody>
			<tr>
				<td class="col-md-12 padng-3px">
					<h3 class="exp-title">
						<a href="/?q=experiment/edit/<?php echo (int)$this->view->content->experiment->id; ?>" class="btn btn-edit btn-sm btn-default"><span class="glyphicon glyphicon-pencil"></span></a>
						<span><?php echo htmlspecialchars($this->view->content->experiment->title, ENT_QUOTES, 'UTF-8'); ?></span>
					</h3>
					<div class="period-work">
						<div class="date-block">
							<div id="exp_datestart" class="text-left ln-hgt-16px">
								<?php if(!empty($this->view->content->experiment->DateStart_exp))
									echo System::dateformat('@'.$this->view->content->experiment->DateStart_exp, System::DATETIME_FORMAT2, 'now'); ?>
							</div>
							<div id="exp_end" class="text-left ln-hgt-16px">
								<?php if(!empty($this->view->content->experiment->DateEnd_exp))
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
		<?php if(isset($this->view->content->experiment->comments)) : ?>
			<tr>
				<td colspan="2" class="bg-default">
					<small><?php echo htmlspecialchars($this->view->content->experiment->comments, ENT_QUOTES, 'UTF-8'); ?></small>
				</td>
			</tr>
		<?php endif;?>
		<?php if ($setup_exists) :?>
			<tr>
				<td colspan="2">
					<div class="">
						<?php echo L::SETUP; ?>: <b><?php echo htmlspecialchars($this->view->content->setup->title, ENT_QUOTES, 'UTF-8'); ?></b>
						<?php if($this->view->content->setup->userCanEdit($this->session())) :?>
							<a href="/?q=setup/edit/<?php echo (int)$this->view->content->setup->id; ?>" title="<?php echo L::setup_EDIT; ?>" class="btn btn-xs btn-default"><span class="glyphicon glyphicon-pencil"></span></a>
						<?php endif; ?>
					</div>
					<div class="setup-status">
						<?php if ($ownSetup) : ?>
						<div id="setup_status_owner" class="col-md-2">
							<span class="label label-info"><?php echo L::setup_OWNER; ?></span>
						</div>
						<?php endif; ?>
						<?php if ($setup_active) : ?>
						<div id="setup_status_active" class="col-md-2">
							<span class="label label-danger"><i class="glyphicon glyphicon-exclamation-sign" style="display:none;">&nbsp;</i><?php 
								echo ($this->view->content->setup->master_exp_id == $this->view->content->experiment->id) ? L::setup_ACTIVE : L::setup_BUSY;
							?></span>
						</div>
						<?php endif; ?>
						<?php if ($masterSetup) : ?>
						<div id="setup_status_master" class="col-md-2">
							<span class="label label-info"><?php echo L::experiment_MASTER; ?></span>
						</div>
						<?php endif; ?>
					</div>
				</td>
			</tr>
			<tr>
				<td colspan="2">
					<div class="mrg-top-5px">
						<div class="col-xs-12 col-sm-6 col-md-4">
							<div class="mrg-bot-5px">
								<div class="special-label"><?php echo L::setup_DETECTIONS_COUNT; ?>: <span id="setup_amount_cnt" class="badge"><?php echo $amount; ?></span></div>
							</div>
							<div class="mrg-bot-5px">
								<div class="special-label"><?php echo L::DONE; ?>: <span id="setup_done_cnt" class="badge"><?php echo $done_cnt; ?></span></div>
							</div>
						</div>
						<div class="col-xs-12 col-sm-6 col-md-4">
							<div class="mrg-bot-5px">
								<div class="special-label"><?php echo L::setup_DETECTIONS_PERIOD; ?>: <span id="setup_interval" class="badge"><?php echo (int)$this->view->content->setup->interval; ?></span></div>
							</div>
							<div class="mrg-bot-5px">
								<div class="special-label"><?php echo L::TIME_REMAIN; ?>: <span id="setup_remain_cnt" class="badge"><?php echo $remain_cnt_text; ?></span></div>
							</div>
						</div>
						<div class="col-xs-12 col-sm-6 col-md-4">
							<div class="mrg-bot-5px">
								<div class="special-label"><?php echo L::setup_DURATION; ?>: <span id="setup_time_det" class="badge"><?php echo System::secToTime($this->view->content->setup->time()); ?></span></div>
							</div>
							<div class="mrg-bot-5px" id="setup_stopat_parent" title="<?php echo ($setup_active ? $stopat_title_1 : $stopat_title_0);?>" data-title-0="<?php echo $stopat_title_0;?>" data-title-1="<?php echo $stopat_title_1;?>">
								<div class="special-label"><?php echo L::FINISHING; ?>: <span id="setup_stopat" class="badge <?php echo $setup_stopat_class;?>"><?php echo $setup_stopat_text;?></span></div>
							</div>
						</div>
						<div class="col-xs-12 col-sm-6 col-md-3" style="display: none;">
							<div class="mrg-bot-5px">
								<?php echo L::MEMBERS; ?>: {value}<?php 

								// todo: get Setup Consumers list output? but no consume anymore

								?>
							</div>
						</div>
					</div>
				</td>
			</tr>
		<?php else : ?>
			<tr>
				<td colspan="2">
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
	</div>
</div>
<div class="row">
	<div class="col-sm-10 col-md-10">
		<div class="row" id="widget-workspace">
		<?php if (isset($this->view->content->sensors)) :?>
			<?php foreach($this->view->content->sensors as $sensor): 
				$skey = '' . $sensor->id . '#' . (int)$sensor->sensor_val_id; ?>
				<div class="col-xs-6 col-sm-4 col-md-3 sensor-widget" data-sensor-id="<?php echo $skey; ?>">
					<div class="panel panel-default">
						<div class="panel-heading">
							<span class="panel-title">

								<span class="glyphicon glyphicon-eye-open sensor-icon-btn" style="cursor:pointer;"></span> <?php echo htmlspecialchars($sensor->name, ENT_QUOTES, 'UTF-8'); ?>

							</span>
						</div>
						<div class="panel-body">
							<small class="pull-right">id: <?php echo htmlspecialchars($skey, ENT_QUOTES, 'UTF-8'); ?></small>
							<div class="widget-pane info active ">
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
		<div class="row">
			<div class="mrg-bot-5px col-xs-6 col-md-12 col-sm-12">
				<a class="btn btn-default form-control <?php echo (!$canSetupControl) ? 'disabled' : ''; 
					?>" id="experiment-action" data-experiment-state="<?php echo (int)$setup_active;?>" data-experiment-id="<?php echo (int)$this->view->content->experiment->id; ?>" data-text-0="<?php echo L::START; ?>" data-text-1="<?php echo L::STOP; ?>" <?php echo (!$canSetupControl) ? 'disabled="disabled"' : ''; ?>><?php 
					echo ($setup_active) ? L::STOP : L::START;
					?></a>
			</div>

			<div class="mrg-bot-5px col-xs-6 col-md-12 col-sm-12">
					<a class="btn btn-default form-control <?php echo (!$canSetupControl) ? 'disabled' : '';
						?>" id="experiment-strob" data-experiment-id="<?php echo (int)$this->view->content->experiment->id; ?>" <?php echo (!$canSetupControl) ? 'disabled="disabled"' : ''; ?>><?php echo L::STROBE; ?></a>
			</div>
			<div class="mrg-bot-5px col-xs-6 col-md-12 col-sm-12">
					<a class="btn btn-default form-control" href="/?q=experiment/journal/<?php echo (int)$this->view->content->experiment->id; ?>"><?php echo L::JOURNAL; ?></a>
			</div>
			<div class="mrg-bot-5px col-xs-6 col-md-12 col-sm-12">
					<a class="btn btn-default form-control" href="/?q=experiment/graph/<?php echo (int)$this->view->content->experiment->id; ?>"><?php echo L::GRAPHS; ?></a>
			</div>
		</div>
	</div>
</div>

<div class="row">
	<div class="col-md-5 pull-left text-left">
		<label for="experiment-sensors-refresh" class="checkbox">
			<input type="checkbox" id="experiment-sensors-refresh" value="1" title="<?php echo L::experiment_AUTO_REFRESH_VALUES_TITLE('3'); ?>"/> <?php echo L::experiment_AUTO_REFRESH_VALUES; ?>
		</label>
	</div>
	<div class="col-xs-6 col-sm-2 col-md-2 pull-right text-right">
	<?php if (!isset($this->view->content->experiment->DateEnd_exp) || empty($this->view->content->experiment->DateEnd_exp)) :?>
		<!-- <a href="#" class="btn btn-default form-control disabled"><?php echo L::FINISH; ?></a> -->
	<?php else : ?>
		<span><?php echo L::experiment_FINISHED; ?></span>
	<?php endif; ?>
	</div>
</div>
