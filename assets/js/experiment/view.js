$(document).ready(function(){
    /*
    var sensorList = {}
    for(var i =1; i <=2; i++){
        sensorList[i] = new Sensor({
            Name: '{sensor ' + i + '}'
        });
        //sensorList[i].testCreateWidget($('#widget-workspace'));
        //console.log(sensorList);
    }
    */

    var sensors = [];
    $('.sensor-widget').each(function(){
        var sensorId = $(this).attr('sensor-id');
        sensors.push(sensorId);
    });
    if(sensors.length){
        updateSensorsValues(sensors);
    }

    $('.sensor-widget .sensor-icon-btn').click(function(){
        var sensorId = $(this).parents('.sensor-widget').attr('sensor-id');
        $(this).removeClass('glyphicon-eye-open').addClass('glyphicon-refresh').addClass('spin');
        var el = $(this);
        updateSensorValue(sensorId, function(){el.removeClass('spin').removeClass('glyphicon-refresh').addClass('glyphicon-eye-open');});
    });

    $(document).on('click', '#experiment-strob', function(){
        var id = $(this).attr('experiment-id');
        getExperimentStrob(id);
    })

    $(document).on('click', '#experiment-action', function(){
        var id = $(this).attr('experiment-id');
        var state = $(this).data('experiment-state');
        if((typeof $(this).attr('disabled') !== 'undefined' && ($(this).attr('disabled')=='disabled')) || $(this).hasClass('disabled')){
            return false;
        }
        if (state == 0){
            experimentAction(1, id);
        }
        else if (state == 1){
            experimentAction(0, id);
        }
        else{
            //error
        }
    })

    $(document).on('change', '#experiment-sensors-refresh', function(){
        if(SDExperimentSensors.updaterId !== null){
            clearInterval(SDExperimentSensors.updaterId);
            SDExperimentSensors.updaterId = null;
        }
        if($(this).prop('checked')) {
            SDExperimentSensors.updaterId = setInterval(function() {
                var ids = [];
                $('.sensor-widget').each(function(){
                    var sensorId = $(this).attr('sensor-id');
                    ids.push(sensorId);
                });
                if(ids.length){
                    updateSensorsValues(ids);
                }
            }, SDExperimentSensors.updaterTime*1000);
        }
    });
});
var SDExperimentSensors = {
    updaterId : null,
    updaterTime : 3
};


function updateSensorValue(id, onalways){
    var pos = id.lastIndexOf("#"), idx = 0, sid = id;
    if(pos > 0){
        sid = id.slice(0, pos);
        idx = parseInt(id.substr(pos+1));
    }
    var rq = coreAPICall('Sensors.GetData', {
        Sensor: sid,
        ValueIdx: idx
    }, function(data){
        if(typeof data.Reading !== 'undefined'){
            $('.sensor-widget[sensor-id="'+id+'"]').find('.sensor-value').html(data.Reading);
            $('.sensor-widget[sensor-id="'+id+'"]').find('.panel-body').removeClass('bg-danger');
        }else{
            $('.sensor-widget[sensor-id="'+id+'"]').find('.sensor-value').html('--');
            $('.sensor-widget[sensor-id="'+id+'"]').find('.panel-body').addClass('bg-danger');
        }

    });
    if(typeof onalways === "function"){
        rq.always(function(d,textStatus,err) {onalways();});
    }
}

function updateSensorsValues(ids, onalways){
    var items = [], pos, idx, sid;
    for(var i=0;i<ids.length;i++){
        pos = ids[i].lastIndexOf("#");
        idx = 0;
        sid = ids[i];
        if(pos > 0){
            sid = ids[i].slice(0, pos);
            idx = parseInt(ids[i].substr(pos+1));
        }
        items.push({
            Sensor: sid,
            ValueIdx: idx
        });
    }

    var rq = coreAPICall('Sensors.GetDataItems', items, function(data, status, jqxhr){
        if(typeof data.error === 'undefined'){
            for(var i=0;i<data.length;i++){
                var sel;
                if(typeof data[i].ValueIdx !== 'undefined'){
                    sel = '.sensor-widget[sensor-id="'+data[i].Sensor +'#'+data[i].ValueIdx+'"]';
                }else{
                    sel =  '.sensor-widget[sensor-id="'+data[i].Sensor +'"]';
                }
                if((typeof data[i].result.error === 'undefined') && (typeof data[i].result.Reading !== 'undefined')){
                    $(sel).find('.sensor-value').html(data[i].result.Reading);
                    $(sel).find('.panel-body').removeClass('bg-danger');
                }else{
                    $(sel).find('.sensor-value').html('--');
                    $(sel).find('.panel-body').addClass('bg-danger');
                }
            }
        }else{
            for(var i=0;i<jqxhr.sensor_ids.length;i++){
                $('.sensor-widget[sensor-id="'+jqxhr.sensor_ids[i]+'"]').find('.sensor-value').html('--');
                $('.sensor-widget[sensor-id="'+jqxhr.sensor_ids[i]+'"]').find('.panel-body').addClass('bg-danger');
            }
        }
    });
    rq.sensor_ids = ids;
    if(typeof onalways === "function"){
        rq.always(function(d,textStatus,err) {onalways();});
    }
}

function getExperimentStrob(experiment_id){
    $('#experiment-strob').attr('disabled', true).text('Выполняется...');
    coreAPICall('Sensors.experimentStrob', {
        experiment: experiment_id
    }, function(data){
        //console.log('Sensors.experimentStrob'+experiment_id);console.log(data);
        if(data.result == true){
            $('#experiment-strob').attr('disabled', false).text('Строб');
        }else{
            $('#experiment-strob').attr('disabled', false).text('Строб: Не выполнено').addClass('btn-warning');
        }

    })
}

function experimentAction(act, experiment_id){
    $('#experiment-action').attr('disabled', 'disabled').addClass('disabled').text('Выполняется...');
    coreAPICall('Sensors.experiment'+(act ? 'Start' : 'Stop'), {
        experiment: experiment_id
    }, function(data){
        if(typeof data.result !== 'undefined'){
            if(data.result == true){
                // switch btn
                $('#experiment-action').attr('disabled', false).removeClass('disabled').text($('#experiment-action').data('text-'+(act?'1':'0'))).data('experiment-state',act);
                location.reload();
            }else{
                $('#experiment-action').attr('disabled', false).removeClass('disabled').text($('#experiment-action').data('text-'+(act?'0':'1'))).addClass('btn-warning').data('experiment-state',(act ? 0 : 1));
                alert($('#experiment-action').data('text-'+(act?'0':'1'))+': Не выполнено');
            }
        } else if (typeof data.error !== 'undefined'){
            //error
            $('#experiment-action').attr('disabled', false).removeClass('disabled').text($('#experiment-action').data('text-'+(act?'0':'1'))).addClass('btn-warning').data('experiment-state',(act ? 0 : 1));
            alert($('#experiment-action').data('text-'+(act?'0':'1'))+': Не выполнено: '+data.error);
        }
    })
}
