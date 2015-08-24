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

    $('.sensor-widget').each(function(){
        var sensorId = $(this).attr('sensor-id');
        updateSensorValue(sensorId);
    });
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
                $('.sensor-widget').each(function(){
                    var sensorId = $(this).attr('sensor-id');
                    updateSensorValue(sensorId);
                });
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
        if(typeof data.Reading != 'undefined'){
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
