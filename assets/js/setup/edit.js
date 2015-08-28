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

    if(typeof data.error === 'undefined'){
        $('#sensor-list-table tbody').empty();
        for (id in data){
            var sensor = data[id];
            sensor.id = id;
            var info = (typeof sensor.sensor_name !== 'undefined') ? true : false;
            for (var i=0;i<sensor.Values.length;i++){
                var sid = '' + sensor.id + '#' + i;
                var exists = $('#sensors-in-setup tbody').find('input[name="sensors['+sensor.id+']['+i+'][id]"]'), newrow;
                if(exists.size() == 0){
                    newrow = $('\
                        <tr sensor-id="'+ sid +'" class="success">\
                            <td><input type="checkbox" checked="checked"/></td>\
                            <td>' + sensor.id + '</td>\
                            <td class="sensor-setup-valname">' + (info ? sensor.Values[i].value_name : '-') + '</td>\
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
                else{
                    var jsensorname = $('#sensors-in-setup tbody').find('input[name="sensors['+sensor.id+']['+i+'][name]"]'), sensorname;
                    if (jsensorname.length>0){
                        sensorname = jsensorname.first().val();
                    }
                    newrow = $('\
                        <tr sensor-id="'+ sid +'" class="success" style="display: none;" ' + ((typeof sensorname !== 'undefined' && sensorname !== '') ? ('data-sensorname="'+sensorname+'"') : '') + '>\
                            <td><input type="checkbox"/></td>\
                            <td>' + sensor.id + '</td>\
                            <td class="sensor-setup-valname">' + (info ? sensor.Values[i].value_name : '-') + '</td>\
                            <td>' + (info ? sensor.Values[i].si_notation : '-') + '</td>\
                            <td>' + (info ? sensor.Values[i].si_name : '-') + '</td>\
                            <td>' + sensor.Values[i].Range.Min + '</td>\
                            <td>' + sensor.Values[i].Range.Max + '</td>\
                            <td>' + ((info && typeof sensor.Values[i].error !== 'undefined') ? sensor.Values[i].error : '-') + '</td>\
                        </tr>'
                    );
                    if(typeof sensorname !== 'undefined' && sensorname !== ''){
                        newrow.find('tr').data('sensorname',sensorname);
                    }

                    $('#sensor-list-table tbody').append(newrow);
                }
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
        var pos = sensorId.lastIndexOf("#"), idx = 0, sid = sensorId;
        if(pos > 0){
            sid = sensorId.slice(0, pos);
            idx = parseInt(sensorId.substr(pos+1));
        }
        var sensorname = $(this).data('sensorname');
        if(typeof sensorname === 'undefined') {
            sensorname = $(this).find('.sensor-setup-valname').first().text();
        }
        var row = $('\
            <tr>\
                <td>' + sid+ '\
                    <input type="hidden" name="sensors['+sid+']['+idx+'][id]" value="' + sid + '"/>\
                    <input type="hidden" name="sensors['+sid+']['+idx+'][val_id]" value="' + idx + '"/>\
                </td>\
                <td>' + $(this).find('.sensor-setup-valname').first().text() + '</td>\
                <td class="sensor-setup-name"><input type="text" placeholder="Имя датчика" name="sensors['+sid+']['+idx+'][name]" class="form-control" required="true"/></td>\
                <td class="text-right"><a class="btn btn-sm btn-danger remove-sensor">Удалить</a></td>\
            </tr>'
        );
        row.find('input[type=text][name$="[name]"]').val(sensorname);
        $('#sensors-in-setup tbody').append(row);
        $(this).find(':checked').removeAttr('checked');
        $(this).hide().removeClass('success');
        console.log('Adding sensor #'+sensorId + ' to setup configuration.');
    });
}

function removeSensorFromSetup(obj){
    var rmrow = $(obj).parent().parent();
    var sensor = rmrow.find('input[type=hidden][name$="[id]"]').val();
    var idx = rmrow.find('input[type=hidden][name$="[val_id]"]').val();
    var sensorId = '' + sensor + '#' + idx;
    var sensorname = rmrow.find('.sensor-setup-name input[type=text]').val();

    rmrow.remove();
    $('tr[sensor-id="'+ sensorId +'"]').removeClass('success').data('sensorname',sensorname).show();

    console.log('Remove sensor #'+ sensorId +' from setup configuration.');
}
