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
        var sensorId = $(this).data('sensor-id');
        sensors.push(sensorId);
    });
    if(sensors.length){
        updateSensorsValues(sensors);
    }

    $('.sensor-widget .sensor-icon-btn').click(function(){
        var sensorId = $(this).parents('.sensor-widget').data('sensor-id');
        $(this).removeClass('glyphicon-eye-open').addClass('glyphicon-refresh').addClass('spin');
        var el = $(this);
        updateSensorValue(sensorId, function(){el.removeClass('spin').removeClass('glyphicon-refresh').addClass('glyphicon-eye-open');});
    });

    $(document).on('click', '#experiment-strob', function(){
        var id = $(this).data('experiment-id');
        getExperimentStrob(id);
    })

    $(document).on('click', '#experiment-action', function(){
        var id = $(this).data('experiment-id');
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
        SDExperiment.stopTimer('SensorId');  // Stop polling
        if($(this).prop('checked')) {
            SDExperiment.updaterSensorId = setInterval(function() {
                var ids = [];
                $('.sensor-widget').each(function(){
                    var sensorId = $(this).data('sensor-id');
                    ids.push(sensorId);
                });
                if(ids.length){
                    updateSensorsValues(ids);
                }
            }, SDExperiment.updaterSensorTime*1000);
        }
    });
});
var SDExperiment = {
    updaterSensorId : null,
    updaterSensorTime : 3,
    updaterMonId : null,
    updaterMonTime : 5,
    stopTimer : function(name){
        if((typeof name !== 'string') || (name.length == 0) || !this.hasOwnProperty('updater'+name)) return;
        var id = 'updater'+name;
        if(this[id] !== null){
            clearInterval(this[id]);
            this[id] = null;
        }
    }
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
        if(typeof data.result !== 'undefined' && typeof data.result.Reading !== 'undefined'){
            $('.sensor-widget[data-sensor-id="'+id+'"]').find('.sensor-value').html(data.result.Reading);
            $('.sensor-widget[data-sensor-id="'+id+'"]').find('.panel-body').removeClass('bg-danger');
        }else{
            $('.sensor-widget[data-sensor-id="'+id+'"]').find('.sensor-value').html('--');
            $('.sensor-widget[data-sensor-id="'+id+'"]').find('.panel-body').addClass('bg-danger');
        }
    });
    if(typeof onalways === "function"){
        rq.always(function(d,textStatus,err) {onalways();});
    }
}

// Batch sensors update
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
            for(var i=0;i<data.result.length;i++){
                var sel;
                if(typeof data.result[i].ValueIdx !== 'undefined'){
                    sel = '.sensor-widget[data-sensor-id="'+data.result[i].Sensor +'#'+data.result[i].ValueIdx+'"]';
                }else{
                    sel =  '.sensor-widget[data-sensor-id="'+data.result[i].Sensor +'"]';
                }
                if(data.result[i].result && (typeof data.result[i].result.Reading !== 'undefined')){
                    $(sel).find('.sensor-value').html(data.result[i].result.Reading);
                    $(sel).find('.panel-body').removeClass('bg-danger');
                }else{
                    $(sel).find('.sensor-value').html('--');
                    $(sel).find('.panel-body').addClass('bg-danger');
                }
            }
        }else{
            for(var i=0;i<jqxhr.sensor_ids.length;i++){
                $('.sensor-widget[data-sensor-id="'+jqxhr.sensor_ids[i]+'"]').find('.sensor-value').html('--');
                $('.sensor-widget[data-sensor-id="'+jqxhr.sensor_ids[i]+'"]').find('.panel-body').addClass('bg-danger');
            }
        }
    });
    rq.sensor_ids = ids;
    if(typeof onalways === "function"){
        rq.always(function(d,textStatus,err) {onalways();});
    }
}

function getExperimentStrob(experiment_id){
    $('#experiment-strob').attr('disabled', true).text(SDLab.Language._('RUNNING_'));
    coreAPICall('Sensors.experimentStrob', {
        experiment: experiment_id
    }, function(data){
        //console.log('Sensors.experimentStrob'+experiment_id);console.log(data);
        if(typeof data.result !== 'undefined' && data.result == true){
            $('#experiment-strob').attr('disabled', false).text(SDLab.Language._('STROBE'));
        }else{
            $('#experiment-strob').attr('disabled', false).text(SDLab.Language._('STROBE') + ': ' + SDLab.Language._('ERROR_NOT_COMPLETED')).addClass('btn-warning');
        }
    })
}

function experimentAction(act, experiment_id){
    $('#experiment-action').attr('disabled', 'disabled').addClass('disabled').text(SDLab.Language._('RUNNING_'));
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
                alert($('#experiment-action').data('text-'+(act?'0':'1'))+': '+SDLab.Language._('ERROR_NOT_COMPLETED'));
            }
        } else if (typeof data.error !== 'undefined'){
            //error
            $('#experiment-action').attr('disabled', false).removeClass('disabled').text($('#experiment-action').data('text-'+(act?'0':'1'))).addClass('btn-warning').data('experiment-state',(act ? 0 : 1));
            alert($('#experiment-action').data('text-'+(act?'0':'1'))+': '+SDLab.Language._('ERROR_NOT_COMPLETED')+': '+data.error);
        }
    })
}

function updateExperimentStatus(exp_id, uuid, onalways){
    uuid = uuid || '';
    var rq = coreAPICall('Sensors.experimentStatus', {experiment: exp_id/*, uuid: uuid*/}, function(data, st, xhr){
        if(typeof data.result !== 'undefined'){
            var setup = data.result.setup,
                mon = data.result.monitor,
                stat = data.result.stat;
            if((setup == null)                                           // no Setup binded to Experiment?
                || (uuid.length>0 && monitor == null)                    // no monitoring active?
                || (uuid.length>0 && monitor.uuid !== xhr.monitor_uuid)  // another monitoring?
                || (stat == null)                                        // no stats?
            ){
                showExpStateUndefined();
                SDExperiment.stopTimer('MonId');  // Stop polling
                $('#setup_status_active i.glyphicon').hide().removeClass('blink');
                return;
            }
            else {
                //if(setup.active){ $('#setup_status_active').show(); }else{ $('#setup_status_active').hide(); }
                $('#setup_amount_cnt').text(stat.amount);
                $('#setup_done_cnt').text(stat.done_cnt);
                $('#setup_interval').text(setup.interval);
                $('#setup_remain_cnt').text(stat.remain_cnt);
                $('#setup_time_det').text(stat.time_det);
                $('#setup_stopat_parent').attr('title',$('#setup_stopat_parent').data('title-'+(setup.active ? '1' : '0')));
                $('#setup_stopat').text(stat.stopat).toggleClass('alert-success', ((stat.finished === false || stat.finished === true) ? stat.finished : false));

                if(!setup.active || (stat.finished !== false)){
                    SDExperiment.stopTimer('MonId');  // Stop polling
                    $('#setup_status_active i.glyphicon').hide().removeClass('blink');
                }
            }
        } else if (typeof data.error !== 'undefined'){
            //error
            showExpStateUndefined();
            SDExperiment.stopTimer('MonId');  // Stop polling
            $('#setup_status_active i.glyphicon').hide().removeClass('blink');
        }
    });
    rq.monitor_uuid = uuid;
    if(typeof onalways === "function"){
        rq.always(function(d,textStatus,err) {onalways();});
    }
}

function showExpStateUndefined() {
    // Reset to undefined state
    $('#setup_amount_cnt').text('?');
    $('#setup_done_cnt').text('?');
    $('#setup_interval').text('?');
    $('#setup_remain_cnt').text('?');
    $('#setup_time_det').text('?');
    $('#setup_stopat').text('?').removeClass('alert-success');

    // Show alert
    if($('table.exp-table tbody .exp-row-alert').length==0){
        $('table.exp-table tbody').append('\
            <tr class="exp-row-alert">\
                <td colspan="2">\
                    <div class="alert alert-warning alert-dismissible" role="alert">\
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>\
                        <div>'+SDLab.Language._('experiment.ERROR_CONFIGURATION_ORPHANED')+'<div>\
                    </div>\
                </td>\
            </tr>\
        ');
        $('.exp-row-alert .alert a').attr('href','javascript:void(0);').click(function(){window.location.reload();});
    }
}
