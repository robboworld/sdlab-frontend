$(document).ready(function(){
    // Rescan sensors
	$("sensors-list .alert").alert();
    $(document).on('click', '#sensors-rescan', function(){
        coreAPICall('Sensors.getSensors', {rescan: true, getinfo: true}, showRescanResults);
    });
})

function showRescanResults(data) {
    if(typeof data.error == 'undefined'){
        $('.sensors-list').empty();
        var c, sensor, info;
        for (id in data){
            $('.sensors-list').append('\
                <div class="alert alert-info alert-dismissible" role="alert">\
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>\
                </div>');
            c = $('.sensors-list .alert').first();
            break;
        }
        for (id in data){
            sensor = data[id];
            sensor.id = id;
            info = (typeof sensor.sensor_name !== 'undefined') ? true : false;
            $(c).append('\
                <div>\
                    <strong>' + sensor.id + '</strong>\
                        ' + (info ? ('<br/><span>' + sensor.Values[0].value_name + ' (' + sensor.Values[0].si_name + ')' + ' [' + sensor.Values[0].Range.Min + '&nbsp;:&nbsp;' + sensor.Values[0].Range.Max + ']' + '<span>')  : '') +'\
                <div>'
            );
        }
    } else {
        alert('Ошибка');
    }
}
