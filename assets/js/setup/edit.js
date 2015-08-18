$(document).ready(function(){
    console.log('Edit setup.');

    if($('input[name="setup-type"]:checked').size() > 0){
        $('.setup-type, #setup-type-alert').hide();
        $('input[type="submit"]').removeAttr('disabled');
        $('#'+$('input[name="setup-type"]:checked').attr('data-id')).show();
    }


    $(document).on('change', 'input[name="setup-type"]', function(e){
        $('.setup-type, #setup-type-alert').hide();
        $('#'+$('input[name="setup-type"]:checked').attr('data-id')).show();
        $('input[name="setup-type"]:checked').parent().parent().find('label').removeClass('active');
        $('input[name="setup-type"]:checked').parent().addClass('active');

        $('input[type="submit"]').removeAttr('disabled');
    })



    /* Получаем список доступных датчиков */
    coreAPICall('Sensors.getSensors', {getinfo: true}, updateSensorsList);

    /* Обновляем список доступных датчиков*/
    $(document).on('click', '#sensors-list-update', function(){
        coreAPICall('Sensors.getSensors', {getinfo: true}, updateSensorsList);
    });

    /* Adding sensors to setup-form*/
    $(document).on('click', '#add-sensors', function(){
        addSensorsToSetup();
    });

    /* Removing sensors from setup-form*/
    $(document).on('click', '.remove-sensor', function(){
        removeSensorFromSetup(this);
    });

    /* Input triggering when touching row */
    $(document).on('click', '#sensor-list-table tbody tr', function(e){
        $(this).toggleClass('success');
        if(!$(e.target).is('input')){
            $(this).find('input[type=checkbox]').prop('checked', !$(this).find('input[type=checkbox]').prop('checked'));
        }
    })
})

function updateSensorsList(data){

    if(typeof data.error == 'undefined'){
        $('#sensor-list-table tbody').empty();
        for (id in data){
            var sensor = data[id];
            sensor.id = id;
            var info = (typeof sensor.sensor_name !== 'undefined') ? true : false;
            if($('#sensors-in-setup tbody').find('input[value="'+sensor.id+'"]').size() == 0){
                $('#sensor-list-table tbody').append('\
                    <tr sensor-id="'+ sensor.id +'" class="success">\
                        <td><input type="checkbox" checked="checked"/></td>\
                        <td>' + sensor.id + '</td>\
                        <td>' + (info ? sensor.Values[0].value_name : '-') + '</td>\
                        <td>' + (info ? sensor.Values[0].si_notation : '-') + '</td>\
                        <td>' + (info ? sensor.Values[0].si_name : '-') + '</td>\
                        <td>' + sensor.Values[0].Range.Min + '</td>\
                        <td>' + sensor.Values[0].Range.Max + '</td>\
                        <td>' + ((info && typeof sensor.Values[0].error !== 'undefined') ? sensor.Values[0].error : '-') + '</td>\
                    </tr>'
                );
            }
            else{
                $('#sensor-list-table tbody').append('\
                    <tr sensor-id="'+ sensor.id +'" class="success" style="display: none;">\
                        <td><input type="checkbox"/></td>\
                        <td>' + sensor.id + '</td>\
                        <td>' + (info ? sensor.Values[0].value_name : '-') + '</td>\
                        <td>' + (info ? sensor.Values[0].si_notation : '-') + '</td>\
                        <td>' + (info ? sensor.Values[0].si_name : '-') + '</td>\
                        <td>' + sensor.Values[0].Range.Min + '</td>\
                        <td>' + sensor.Values[0].Range.Max + '</td>\
                        <td>' + ((info && typeof sensor.Values[0].error !== 'undefined') ? sensor.Values[0].error : '-') + '</td>\
                    </tr>'
                );
            }
        }
    } else {
        //error
        alert('Ошибка');
    }
}

function addSensorsToSetup(){
    $('#sensor-list-table').find(':checked').parent().parent().each(function(id){
        var sensorId = $(this).attr('sensor-id');
        $('#sensors-in-setup tbody').append('\
            <tr>\
                <td><input type="hidden" name="sensors['+id+'][id]" value="' + sensorId + '"> ' + sensorId+ '</td>\
                <td><input type="text" placeholder="Имя датчика" name="sensors['+id+'][name]" class="form-control" required="true"></td>\
                <td class="text-right"><a class="btn btn-sm btn-danger remove-sensor">Удалить</a></td>\
            </tr>');
        $(this).find(':checked').removeAttr('checked');
        $(this).hide().removeClass('success');
        console.log('Adding sensor #'+sensorId + ' to setup configuration.');
    });
}

function removeSensorFromSetup(obj){
    var sensorId = $(obj).parent().parent().find('input[type=hidden]').val();
    $(obj).parent().parent().remove();
    $('tr[sensor-id="' +sensorId +'"]').removeClass('success').show();
    console.log('Remove sensor #'+sensorId +' from setup configuration.');
}