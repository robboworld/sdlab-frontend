$(document).ready(function(){
    if($('input[name="setup-type"]:checked').length > 0){
        $('.setup-type, #setup_type_alert').hide();
        $('input[type="submit"]').prop('disabled',false);
        $('#'+$('input[name="setup-type"]:checked').attr('data-id')).show();
    }

    $(document).on('change', 'input[name="setup-type"]', function(e){
        $('.setup-type, #setup_type_alert').hide();
        var schk = $('input[name="setup-type"]:checked');
        $('#'+schk.attr('data-id')).show();
        schk.parent().parent().find('label').removeClass('active');
        schk.parent().addClass('active');

        $('input[type="submit"]').prop('disabled',false);
    })

    // Update list of available sensors
    $(document).on('click', '#sensors_list_update', function(){
        coreAPICall('Sensors.getSensors', {getinfo: true}, updateSensorsList);
    });
    toggleSensorsListAlert('#sensors_in_setup');
    // Get list of available sensors
    $('#sensors_list_update').trigger('click');

    // Adding sensors to setup-form
    $(document).on('click', '#add_sensors', function(){
        addSensorsToSetup();
        toggleSensorsListAlert('#sensors_in_setup, #sensor_list_table');
    });

    // Removing sensors from setup-form
    $(document).on('click', '.remove-sensor', function(){
        removeSensorFromSetup(this);
        toggleSensorsListAlert('#sensors_in_setup, #sensor_list_table');
    });

    // Input triggering when touching row
    $(document).on('click', '#sensor_list_table tbody tr', function(e){
        $(this).toggleClass('success');
        if(!$(e.target).is('input')){
            $(this).find('input[type=checkbox]').prop('checked', !$(this).find('input[type=checkbox]').prop('checked'));
        }
    })
})

function updateSensorsList(data){

    if(typeof data.error === 'undefined'){
        $('#sensor_list_table tbody').empty();
        for (id in data.result){
            var sensor = data.result[id];
            sensor.id = id;
            var info = (typeof sensor.sensor_name !== 'undefined') ? true : false;
            for (var i=0;i<sensor.Values.length;i++){
                var sid = '' + sensor.id + '#' + i;
                var exists = $('#sensors_in_setup tbody').find('input[name="sensors['+sensor.id+']['+i+'][id]"]'), newrow;
                if(exists.size() == 0){
                    newrow = $('\
                        <tr data-sensor-id="'+ sid +'" class="success">\
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

                    $('#sensor_list_table tbody').append(newrow);
                }
                else{
                    var jsensorname = $('#sensors_in_setup tbody').find('input[name="sensors['+sensor.id+']['+i+'][name]"]'), sensorname;
                    if (jsensorname.length>0){
                        sensorname = jsensorname.first().val();
                    }
                    newrow = $('\
                        <tr data-sensor-id="'+ sid +'" class="success" style="display: none;" ' + ((typeof sensorname !== 'undefined' && sensorname !== '') ? ('data-sensorname="'+sensorname+'"') : '') + '>\
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

                    $('#sensor_list_table tbody').append(newrow);
                }
            }
        }
        toggleSensorsListAlert('#sensor_list_table');
    } else {
        toggleSensorsListAlert('#sensor_list_table');
        //error
        alert(SDLab.Language._('ERROR'));
    }
}

function addSensorsToSetup(){
    $('#sensor_list_table').find(':checked').parent().parent().each(function(id){
        var sensorId = $(this).data('sensor-id');
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
                <td class="sensor-setup-name"><input type="text" placeholder="'+SDLab.Language._('sensor.NAME')+'" name="sensors['+sid+']['+idx+'][name]" class="form-control" required="true"/></td>\
                <td class="text-right"><a class="btn btn-sm btn-danger remove-sensor">'+SDLab.Language._('REMOVE')+'</a></td>\
            </tr>'
        );
        row.find('input[type=text][name$="[name]"]').val(sensorname);
        $('#sensors_in_setup tbody').append(row);
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
    $('tr[data-sensor-id="'+ sensorId +'"]').removeClass('success').data('sensorname',sensorname).show();

    console.log('Remove sensor #'+ sensorId +' from setup configuration.');
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
