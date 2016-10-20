<script type="text/javascript">
$(document).ready(function(){
    $('#sensor-list-table').on('click', '.sensor-icon-btn', function(){
        var sensorId = $(this).parents('tr').data('sensor-id');
        $(this).removeClass('glyphicon-eye-open').addClass('glyphicon-refresh').addClass('spin');
        var el = $(this);
        updateSensorValue(sensorId, function(){el.removeClass('spin').removeClass('glyphicon-refresh').addClass('glyphicon-eye-open');});
    });
    // Rescan sensors
    $('#sensors_rescan').click(function(){
        emptyInterfaceError('#sensors_msgs');
        $('#sensors_rescan .btn-icon').addClass('fa-spin');
        coreAPICall('Sensors.getSensors', {rescan: true, getinfo: true}, updateSensorsList, updateSensorsListErr);
    });
    toggleSensorsListAlert('#sensor-list-table');
});
function updateSensorsList(resp){
    var data = parseJSON(resp);
    $('#sensor-list-table tbody').empty();
    $('#sensors_rescan .btn-icon').removeClass('fa-spin');
    if(data && typeof data.error === 'undefined'){
        var cnt=0, sensor, info, sid, newrow;
        $('#sensor-list-table tbody').empty();
        for (id in data.result){
            sensor = data.result[id];
            info = (typeof sensor.sensor_name !== 'undefined') ? true : false;
            sensor.id = id;
            for (var i=0;i<sensor.Values.length;i++){
                sid = '' + sensor.id + '#' + i;
                newrow = $('\
                    <tr data-sensor-id="'+ sid +'" class="row-sensor">\
                        <td>' + sid + '</td>\
                        <td>' + (info ? sensor.Values[i].value_name : '-') + '</td>\
                        <td>' + (info ? sensor.Values[i].si_notation : '-') + '</td>\
                        <td>' + (info ? sensor.Values[i].si_name : '-') + '</td>\
                        <td>' + sensor.Values[i].Range.Min + '</td>\
                        <td>' + sensor.Values[i].Range.Max + '</td>\
                        <td>' + ((info && typeof sensor.Values[i].error !== 'undefined') ? sensor.Values[i].error : '-') + '</td>\
                        <td><span class="glyphicon glyphicon-eye-open sensor-icon-btn" style="cursor:pointer;"></span>&nbsp;<span class="sensor-value">--</span></td>\
                    </tr>'
                );
                if(info){
                    newrow.find('tr').data('sensorname',sensor.Values[i].value_name);
                }
                $('#sensor-list-table tbody').append(newrow);
                cnt++;
            }
        }
        if (!cnt) {
            setInterfaceError($('#sensors_msgs'),'<span class="glyphicon glyphicon-info-sign"></span>&nbsp;'+SDLab.Language._('setup_MSG_NO_AVAILABLE_SENSORS'), "info", true);
        }
    } else {
        setInterfaceError($('#sensors_msgs'),'<span class="glyphicon glyphicon-exclamation-sign"></span>&nbsp;'+SDLab.Language._('ERROR'), "danger", true);
    }
    toggleSensorsListAlert('#sensor-list-table');
}
function updateSensorsListErr(){
    $('#sensor-list-table tbody').empty();
    $('#sensors_rescan .btn-icon').removeClass('fa-spin');
    setInterfaceError($('#sensors_msgs'),'<span class="glyphicon glyphicon-exclamation-sign"></span>&nbsp;'+SDLab.Language._('ERROR'), "danger", true);
}
function toggleSensorsListAlert(selector){
    var els = $(selector);
    if(els.length<=0) return;
    els.each(function(){
        $(this).find('tfoot').toggle($(this).find('tfoot .alert').length>0);
    });
}
function updateSensorValue(id, onalways){
    var pos = id.lastIndexOf("#"), idx = 0, sid = id;
    if(pos > 0){
        sid = id.slice(0, pos);
        idx = parseInt(id.substr(pos+1));
    }
    var rq = coreAPICall('Sensors.GetData', {
        "Sensor": sid,
        "ValueIdx": idx
    }, function(data){
        var data = parseJSON(resp);
        if(data && typeof data.result !== 'undefined' && typeof data.result.Reading !== 'undefined'){
            $('#sensor-list-table tr[data-sensor-id="'+id+'"]').find('.sensor-value').html(data.result.Reading);
            $('#sensor-list-table tr[data-sensor-id="'+id+'"]').removeClass('bg-danger');
        }else{
            $('#sensor-list-table tr[data-sensor-id="'+id+'"]').find('.sensor-value').html('--');
            $('#sensor-list-table tr[data-sensor-id="'+id+'"]').addClass('bg-danger');
        }
    });
    if(typeof onalways === "function"){
        rq.always(function(d,textStatus,err) {onalways();});
    }
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
			<a href="javascript:void(0)" id="sensors_rescan" class="btn btn-primary">
				<span class="fa fa-refresh btn-icon"></span><span class="">&nbsp;<?php echo L('sensor_REFRESH_LIST');
			?></span></a>
		</div>
		<?php endif; ?>
	</div>
	<?php if(isset($this->view->content->list )) : ?>

	<br/>
	<table class="table table-responsive table-condensed" id="sensor-list-table">
		<thead>
			<tr>
				<th>ID</th>
				<th><?php echo L('sensor_VALUE_NAME'); ?></th>
				<th><?php echo L('sensor_VALUE_SI_NOTATION'); ?></th>
				<th title="<?php echo L('sensor_VALUE_SI_NAME'); ?>"><?php echo L('sensor_VALUE_SI_NSHORT'); ?></th>
				<th title="<?php echo L('sensor_VALUE_MIN_RANGE'); ?>"><?php echo L('sensor_VALUE_MIN'); ?></th>
				<th title="<?php echo L('sensor_VALUE_MAX_RANGE'); ?>"><?php echo L('sensor_VALUE_MAX'); ?></th>
				<th><?php echo L('sensor_VALUE_ERROR'); ?></th>
				<th class="fa fa-tv"></th>
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
					<span class="glyphicon glyphicon-eye-open sensor-icon-btn" style="cursor:pointer;"></span>&nbsp;<span class="sensor-value">--</span>
				</td>
			</tr>
		<?php $i++; $cnt++; endforeach;
		endforeach; ?>

		</tbody>
		<tfoot style="display: none;">
			<tr>
				<td colspan="7" id="sensors_msgs">
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
