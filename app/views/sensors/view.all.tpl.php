<div class="col-md-12">
	<div class="row">
		<div class="col-md-6">
			<h1><?php echo L('sensor_LIST'); ?></h1>
		</div>
	</div>
	<div class="row">
		<?php if($this->session()->getUserLevel() == 3) : ?>
		<div class="col-md-4 text-left">
			<a href="javascript:void(0)" id="sensors_rescan" class="btn btn-primary">
				<span class="fa fa-refresh btn-icon"></span><span class="">&nbsp;<?php echo L('sensor_REFRESH_LIST');
			?></span></a>
		</div>
		<?php endif; ?>
	</div>
	<?php if(isset($this->view->content->list )) : ?>

	<br/>
	<table class="table table-responsive table-condensed" id="sensor_list_table">
		<thead>
			<tr>
				<th>ID</th>
				<th><?php echo L('sensor_VALUE_NAME'); ?></th>
				<th><?php echo L('sensor_VALUE_SI_NOTATION'); ?></th>
				<th title="<?php echo L('sensor_VALUE_SI_NAME'); ?>"><?php echo L('sensor_VALUE_SI_NSHORT'); ?></th>
				<th title="<?php echo L('sensor_VALUE_MIN_RANGE'); ?>"><?php echo L('sensor_VALUE_MIN'); ?></th>
				<th title="<?php echo L('sensor_VALUE_MAX_RANGE'); ?>"><?php echo L('sensor_VALUE_MAX'); ?></th>
				<th><?php echo L('sensor_VALUE_ERROR'); ?></th>
				<th><span class="fa fa-tv"></span></th>
			</tr>
		</thead>
		<tbody>
		<?php
		$cnt = 0;
		foreach($this->view->content->list as $sensor_id => $item) :
			$sensor_name = (string) preg_replace('/\-.*/i', '', $sensor_id);
			$i = 0;
			foreach($item->{'Values'} as $sensor_val_id => &$data) :
				if (strlen($sensor_name) == 0)
				{
					continue;
				}
				$key = '' . $sensor_id . '#' . (int)$sensor_val_id;
			?>

			<tr class="row-sensor" data-sensor-id="<?php echo htmlspecialchars($key, ENT_QUOTES, 'UTF-8');?>">
				<td>
					<?php echo htmlspecialchars($key, ENT_QUOTES, 'UTF-8');?>
				</td>
				<td class="sensor-setup-valname">
					<?php echo htmlspecialchars($data->value_name, ENT_QUOTES, 'UTF-8'); ?>
				</td>
				<td>
					<?php echo htmlspecialchars($data->si_notation, ENT_QUOTES, 'UTF-8'); ?>
				</td>
				<td>
					<?php echo htmlspecialchars($data->si_name, ENT_QUOTES, 'UTF-8'); ?>
				</td>
				<td>
					<?php echo htmlspecialchars($data->{'Range'}->{'Min'}, ENT_QUOTES, 'UTF-8'); ?>
				</td>
				<td>
					<?php echo htmlspecialchars($data->{'Range'}->{'Max'}, ENT_QUOTES, 'UTF-8'); ?>
				</td>
				<td>
					<?php echo isset($data->error) ? htmlspecialchars($data->error, ENT_QUOTES, 'UTF-8') : '-'; ?>
				</td>
				<td>
					<span class="glyphicon glyphicon-eye-open sensor-icon-btn" style="cursor:pointer;"></span>&nbsp;<span class="sensor-value"></span>
				</td>
			</tr>
		<?php $i++; $cnt++; endforeach;
		endforeach; ?>

		</tbody>
		<tfoot style="display: none;">
			<tr>
				<td colspan="8" id="sensors_msgs">
					<?php if (!$cnt) : ?>
					<div class="alert alert-info" role="alert">
						<span class="glyphicon glyphicon-info-sign"></span>&nbsp;<?php echo L('setup_MSG_NO_AVAILABLE_SENSORS'); ?>
					</div>
					<?php endif; ?>
				</td>
			</tr>
		</tfoot>
	</table>
	<?php endif; ?>
</div>
