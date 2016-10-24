$(document).ready(function(){
	$('#sensor_list_table').on('click', '.sensor-icon-btn', function(){
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
	toggleSensorsListAlert('#sensor_list_table');
});
function updateSensorsList(resp){
	var data = parseJSON(resp);
	$('#sensor_list_table tbody').empty();
	$('#sensors_rescan .btn-icon').removeClass('fa-spin');
	if(data && typeof data.error === 'undefined'){
		var cnt=0, sensor, info, sid, newrow;
		$('#sensor_list_table tbody').empty();
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
						<td><span class="glyphicon glyphicon-eye-open sensor-icon-btn" style="cursor:pointer;"></span>&nbsp;<span class="sensor-value"></span></td>\
					</tr>'
				);
				if(info){
					newrow.find('tr').data('sensorname',sensor.Values[i].value_name);
				}
				$('#sensor_list_table tbody').append(newrow);
				cnt++;
			}
		}
		if (!cnt) {
			setInterfaceError($('#sensors_msgs'),'<span class="glyphicon glyphicon-info-sign"></span>&nbsp;'+SDLab.Language._('setup_MSG_NO_AVAILABLE_SENSORS'), "info", true);
		}
	} else {
		setInterfaceError($('#sensors_msgs'),'<span class="glyphicon glyphicon-exclamation-sign"></span>&nbsp;'+SDLab.Language._('ERROR'), "danger", true);
	}
	toggleSensorsListAlert('#sensor_list_table');
}
function updateSensorsListErr(){
	$('#sensor_list_table tbody').empty();
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
	}, function(resp){
		var data = parseJSON(resp);
		if(data && typeof data.result !== 'undefined' && typeof data.result.Reading !== 'undefined'){
			$('#sensor_list_table tr[data-sensor-id="'+id+'"]').find('.sensor-value').html(data.result.Reading);
			$('#sensor_list_table tr[data-sensor-id="'+id+'"]').removeClass('bg-danger');
		}else{
			$('#sensor_list_table tr[data-sensor-id="'+id+'"]').find('.sensor-value').html('--');
			$('#sensor_list_table tr[data-sensor-id="'+id+'"]').addClass('bg-danger');
		}
	});
	if(typeof onalways === "function"){
		rq.always(function(d,textStatus,err) {onalways();});
	}
}