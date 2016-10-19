<script type="text/javascript">
$(document).ready(function(){
    // Rescan sensors
    $('#sensors-rescan').click(function(){
        coreAPICall('Sensors.getSensors', {rescan: true, getinfo: true}, updateSensorsList);
    });
});
function updateSensorsList(data){
    if(typeof data.error === 'undefined'){
        $('#sensor-list-table tbody').empty();
        for (id in data.result){
            var sensor = data.result[id],
                info = (typeof sensor.sensor_name !== 'undefined') ? true : false;
            sensor.id = id;
            for (var i=0;i<sensor.Values.length;i++){
                var sid = '' + sensor.id + '#' + i,
                    newrow = $('\
                    <tr data-sensor-id="'+ sid +'" class="row-sensor">\
                        <td>' + sensor.id + '</td>\
                        <td>' + (info ? sensor.Values[i].value_name : '-') + '</td>\
                        <td>' + (info ? sensor.Values[i].si_notation : '-') + '</td>\
                        <td>' + (info ? sensor.Values[i].si_name : '-') + '</td>\
                        <td>' + sensor.Values[i].Range.Min + '</td>\
                        <td>' + sensor.Values[i].Range.Max + '</td>\
                        <td>' + ((info && typeof sensor.Values[i].error !== 'undefined') ? sensor.Values[i].error : '-') + '</td>\
                    </tr>'
                );
                if(info){
                    newrow.find('tr').data('sensorname',sensor.Values[i].value_name);
                }
                $('#sensor-list-table tbody').append(newrow);
            }
        }
        toggleSensorsListAlert('#sensor-list-table');
    } else {
        toggleSensorsListAlert('#sensor-list-table');
        //error
        alert(SDLab.Language._('ERROR'));
    }
}
function toggleSensorsListAlert(selector){
    var els = $(selector);
    if(els.length<=0) return;
    els.each(function(){
        if($(this).find('tfoot .alert').length>0){
            var rows = $(this).find('tbody tr:visible');
            if(rows.length==0){
                $(this).find('tfoot').show();
            }else{
                $(this).find('tfoot').hide();
            }
        }
    });
}
</script>
<div class="col-md-12">
	<div class="row">
		<div class="col-md-6">
			<h1><?php echo L('sensor_LIST'); ?></h1>
		</div>
	</div>
	<div class="row">
		<?php if($this->session()->getUserLevel() == 3) : ?>
		<div class="col-md-4 text-left">
			<a href="javascript:void(0)" id="sensors-rescan" class="btn btn-primary"><?php echo L('sensor_REFRESH_LIST'); ?></a>
		</div>
		<?php endif; ?>
	</div>
	<?php if(isset($this->view->content->list )) : ?>

	<table class="table table-responsive" id="sensor-list-table">
		<thead>
			<tr>
				<th>ID</th>
				<th><?php echo L('sensor_VALUE_NAME'); ?></th>
				<th><?php echo L('sensor_VALUE_SI_NOTATION'); ?></th>
				<th><?php echo L('sensor_VALUE_SI_NAME'); ?></th>
				<th><?php echo L('sensor_VALUE_MIN_RANGE'); ?></th>
				<th><?php echo L('sensor_VALUE_MAX_RANGE'); ?></th>
				<th><?php echo L('sensor_VALUE_ERROR'); ?></th>
			</tr>
		</thead>
		<tbody>
		<?php foreach($this->view->content->list as $sensor_id => $item) :
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
					<?php echo htmlspecialchars($sensor_id, ENT_QUOTES, 'UTF-8');?>
				</td>
				<td class="sensor-setup-valname>
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
			</tr>
		<?php $i++; endforeach;
		endforeach; ?>

		</tbody>
		<tfoot style="display: none;">
			<tr>
				<td colspan="7">
					<div class="alert alert-info" role="alert">
						<span class="glyphicon glyphicon-info-sign"></span>
						<span><?php echo L('setup_MSG_NO_AVAILABLE_SENSORS'); ?></span>
					</div>
				</td>
			</tr>
		</tfoot>
	</table>
	<?php endif; ?>
</div>