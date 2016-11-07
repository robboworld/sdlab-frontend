$(document).ready(function(){
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

    $(document).on('click', '#experiment_strob', function(){
        var id = $('.exp-table').data('experiment-id');
        getExperimentStrob(id);
    });
    $(document).on('click', '.monitor-strob', function(){
        var m = $(this).parents('.monitor-panel').first();
        getMonitorStrob(m.attr('id'), m.data('monitor-expid'), m.data('monitor-uuid'));
    });

    $(document).on('click', '#experiment_action', function(){
        var id = $('.exp-table').data('experiment-id'), el, state;

        el = $('#setup_status_active');
        state = ((el.length>0 && el.is(':visible'))?1:0);
        if($(this).prop('disabled') || $(this).hasClass('disabled')){
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
    });
    $(document).on('click', '.monitor-stop', function(){
        var m = $(this).parents('.monitor-panel').first(),
            id = m.data('monitor-expid'),
            uuid = m.data('monitor-uuid'),
            state = (m.hasClass('monitor-active') ? 1 : 0);
        if($(this).prop('disabled') || $(this).hasClass('disabled')){
            return false;
        }
        if (state == 1){
            monitorStop(m.attr('id'), id, uuid);
        }
        else{
            //error
        }
    });
    $(document).on('click', '.monitor-remove', function(){
        var m = $(this).parents('.monitor-panel').first(),
            id = m.data('monitor-expid'),
            uuid = m.data('monitor-uuid'),
            state = (m.hasClass('monitor-active') ? 1 : 0);
        if($(this).prop('disabled') || $(this).hasClass('disabled')){
            return false;
        }
        if (state == 0){
            monitorRemove(m.attr('id'), id, uuid);
        }else{
            //error
        }
    });

    $(document).on('change', '#experiment_sensors_refresh', function(){
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

    $('#collapseMonAll').click(function() {
        $('#accordion_monitors .panel-collapse.in').collapse('hide');
    });
    $('#expandMonAll').click(function() {
        $('#accordion_monitors .panel-collapse:not(.in)').collapse('show');
    });

    $(window).resize(function(){
        if($('#accordion_monitors').length > 0){
            if($(window).width() < 768){
                if ($('#accordion_monitors .monitor-control').hasClass('btn-group-vertical')){
                    $('#accordion_monitors .monitor-control').removeClass('btn-group-vertical').addClass('btn-group btn-group-justified')
                        .find('button').wrap('<div class="btn-group btn-group-wrap" role="group"></div>');
                }
            }else{
                if ($('#accordion_monitors .monitor-control').hasClass('btn-group')){
                    $('#accordion_monitors .monitor-control').removeClass('btn-group btn-group-justified').addClass('btn-group-vertical')
                        .find('button').unwrap('.btn-group-wrap');
                }
            }
        }
    });
    if($('#accordion_monitors').length > 0){
        $(window).trigger('resize');
        $('#accordion_monitors .panel-collapse')
            .on('show.bs.collapse', function(){
                $(this).siblings('.panel-heading').find('.panel-collapse-control a span').removeClass('glyphicon-chevron-up glyphicon-chevron-down').addClass('glyphicon-chevron-down');
            })
            .on('hide.bs.collapse', function(){
                $(this).siblings('.panel-heading').find('.panel-collapse-control a span').removeClass('glyphicon-chevron-up glyphicon-chevron-down').addClass('glyphicon-chevron-up');
            });
    }
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
        "Sensor": sid,
        "ValueIdx": idx
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
    $('#experiment_strob').prop('disabled', true).removeClass('btn-warning')
    $('#experiment_error_text').empty().hide();
    $('#experiment_control_waiting').show();
    coreAPICall('Sensors.experimentStrob', {
        "experiment": experiment_id
    }, function(data){
        //console.log('Sensors.experimentStrob'+experiment_id);console.log(data);
        $('#experiment_control_waiting').hide();
        if(typeof data.result !== 'undefined' && data.result == true){
            $('#experiment_strob').prop('disabled', false);
            //$('#experiment_error_text').empty().hide();
        }else{
            $('#experiment_strob').prop('disabled', false).addClass('btn-warning');
            $('#experiment_error_text').html(SDLab.Language._('STROBE') + ': ' + SDLab.Language._('ERROR_NOT_COMPLETED')).show();
        }
    })
}

function getMonitorStrob(sel, experiment_id, monitor_uuid){
    $('#'+sel+' .monitor-strob').prop('disabled', true).addClass('disabled').removeClass('btn-warning');
    $('#'+sel+' .monitor-error-text').empty().hide();
    $('#'+sel+' .monitor-control-waiting').show();
    coreAPICall('Sensors.experimentStrob', {
        "experiment": experiment_id,
        "uuid": monitor_uuid
    }, function(data){
        var m = $('#'+sel),
            btn = m.find('.monitor-strob');
        m.find('.monitor-control-waiting').hide();
        if(typeof data.result !== 'undefined' && data.result == true){
            btn.removeClass('disabled').prop('disabled', false);
            //m.find('.monitor-error-text').empty().hide();
        }else{
            btn.removeClass('disabled').prop('disabled', false).addClass('btn-warning');
            m.find('.monitor-error-text').html(SDLab.Language._('ERROR_NOT_COMPLETED')).show();
        }
    })
}

function experimentAction(act, experiment_id){
    $('#experiment_action').prop('disabled', true).addClass('disabled').removeClass('btn-warning');
    $('#experiment_error_text').empty().hide();
    $('#experiment_control_waiting').show();
    coreAPICall('Sensors.experiment'+(act ? 'Start' : 'Stop'), {
        "experiment": experiment_id
    }, function(data){
        var el,btn=$('#experiment_action');
        $('#experiment_control_waiting').hide();
        if(typeof data.result !== 'undefined'){
            var ok=false,uuid='';
            if(act){
                if((typeof data.result === 'string' || data.result instanceof String) && data.result.length>0){
                    uuid = data.result;// Start returns uuid of created monitor
                    ok = true;
                }
            }else{
                if(data.result == true){
                    ok = true;
                }
            }
            if(ok){
                // switch btn
                btn.prop('disabled', false).removeClass('disabled')
                    .find('.btn-text').text(btn.data('text-'+(act?'1':'0')));
                btn.find('span:first-child').removeClass(' '+btn.data('icon-0')+' '+btn.data('icon-1')).addClass(btn.data('icon-'+(act?'1':'0')));
                //$('#experiment_error_text').empty().hide();
                el = $('#setup_status_active');
                if(el.length>0){
                    el.toggle(act?true:false);
                }
                location.reload();
            }else{
                btn.prop('disabled', false).removeClass('disabled').addClass('btn-warning');
                $('#experiment_error_text').html(btn.data('text-'+(act?'0':'1'))+': '+SDLab.Language._('ERROR_NOT_COMPLETED')).show();
            }
        } else if (typeof data.error !== 'undefined'){
            //error
            btn.prop('disabled', false).removeClass('disabled').addClass('btn-warning');
            $('#experiment_error_text').html(btn.data('text-'+(act?'0':'1'))+': '+SDLab.Language._('ERROR_NOT_COMPLETED')+': '+data.error).show();
        } else {
            //error
            btn.prop('disabled', false).removeClass('disabled').addClass('btn-warning');
            $('#experiment_error_text').html(btn.data('text-'+(act?'0':'1'))+': '+SDLab.Language._('ERROR_NOT_COMPLETED')).show();
        }
    })
}
function monitorStop(sel, experiment_id, monitor_uuid){
    $('#'+sel+' .monitor-stop').prop('disabled', true).addClass('disabled').removeClass('btn-warning');
    $('#'+sel+' .monitor-error-text').empty().hide();
    $('#'+sel+' .monitor-control-waiting').show();
    coreAPICall('Sensors.monitorStop', {
        "experiment": experiment_id,
        "uuid": monitor_uuid
    }, function(data){
        var m = $('#'+sel),
            btn = m.find('.monitor-stop');
        m.find('.monitor-control-waiting').hide();
        if(typeof data.result !== 'undefined'){
            if(data.result == true){
                // switch btn
                btn.prop('disabled', false).removeClass('disabled');
                m.removeClass('monitor-active');
                //m.find('.monitor-error-text').empty().hide();
                location.reload();
            }else{
                btn.prop('disabled', false).removeClass('disabled').addClass('btn-warning');
                //m.removeClass('monitor-active');
                m.find('.monitor-error-text').html(SDLab.Language._('ERROR_NOT_COMPLETED')).show();
                alert(btn.data('text')+': '+SDLab.Language._('ERROR_NOT_COMPLETED'));
            }
        } else if (typeof data.error !== 'undefined'){
            //error
            btn.prop('disabled', false).removeClass('disabled').addClass('btn-warning');
            //m.removeClass('monitor-active');
            m.find('.monitor-error-text').html(SDLab.Language._('ERROR_NOT_COMPLETED')+': '+data.error).show();
            alert(btn.data('text')+': '+SDLab.Language._('ERROR_NOT_COMPLETED')+': '+data.error);
        } else {
            //error
            btn.prop('disabled', false).removeClass('disabled').addClass('btn-warning');
            //m.removeClass('monitor-active');
            m.find('.monitor-error-text').html(SDLab.Language._('ERROR')).show();
            alert(btn.data('text')+': '+SDLab.Language._('ERROR'));
        }
    })
}

function monitorRemove(sel, experiment_id, monitor_uuid){
    $('#'+sel+' .monitor-remove').prop('disabled', true).addClass('disabled').removeClass('btn-warning');
    $('#'+sel+' .monitor-error-text').empty().hide();
    $('#'+sel+' .monitor-control-waiting').show();
    coreAPICall('Sensors.monitorRemove', {
        "experiment": experiment_id,
        "uuid": monitor_uuid
    }, function(data){
        var m = $('#'+sel),
            btn = m.find('.monitor-remove');
        m.find('.monitor-control-waiting').hide();
        if(typeof data.result !== 'undefined'){
            if(data.result == true){
                // switch btn
                btn.prop('disabled', false).removeClass('disabled');
                m.remove();
                //m.find('.monitor-error-text').empty().hide();
                location.reload();
            }else{
                btn.prop('disabled', false).removeClass('disabled').addClass('btn-warning');
                //m.remove();
                m.find('.monitor-error-text').html(SDLab.Language._('ERROR_NOT_COMPLETED')).show();
                alert(btn.data('text')+': '+SDLab.Language._('ERROR_NOT_COMPLETED'));
            }
        } else if (typeof data.error !== 'undefined'){
            //error
            btn.prop('disabled', false).removeClass('disabled').addClass('btn-warning');
            //m.remove();
            m.find('.monitor-error-text').html(SDLab.Language._('ERROR_NOT_COMPLETED')+': '+data.error).show();
            alert(btn.data('text')+': '+SDLab.Language._('ERROR_NOT_COMPLETED')+': '+data.error);
        } else {
            //error
            btn.prop('disabled', false).removeClass('disabled').addClass('btn-warning');
            //m.remove();
            m.find('.monitor-error-text').html(SDLab.Language._('ERROR')).show();
            alert(btn.data('text')+': '+SDLab.Language._('ERROR'));
        }
    })
}

function updateExperimentStatus(exp_id, uuid, onalways){
    uuid = uuid || '';
    var rq = coreAPICall('Sensors.experimentStatus', {"experiment": exp_id, "uuid": uuid}, function(data, st, xhr){
        if(typeof data.result !== 'undefined'){
            var cursetup = $('.exp-table').data('setup-id'),
                vmons = {},//active monitors [uuid => monitor]
                uuids = [],//all uuids
                new_uuids = [],//received uuids
                setup = data.result.setup,
                mons = data.result.monitors,
                acnt = 0, el;

            $('.monitor-panel').each(function(index,value){
                uuids.push($(value).data('monitor-uuid'));
                if ($(value).hasClass('monitor-active')){
                    vmons[''+$(value).data('monitor-uuid')] = value;
                }
            });

            // Update monitors data
            if(mons.length > 0){
                for(var i=0;i<mons.length;i++){
                    var d = mons[i]['data'],
                        muuid = mons[i]['uuid'];

                    new_uuids.push(muuid);

                    // Update only active
                    if(muuid in vmons){
                        if(d != null){
                            showMonitorState($(vmons[muuid]),d);
                        }else{
                            showMonitorStateUndefined($(vmons[muuid]));
                        }
                    }

                    if(d != null && d.active){
                        acnt++;
                    }
                }
            }

            // Set current setup state
            if(setup!=null){
                if(setup.id == cursetup){
                    el = $('#setup_status_active');
                    if(el.length>0){
                        el.toggle((setup.active)?true:false);
                    }
                }
                // switch btn
                var btn = $('#experiment_action');
                btn.find('.btn-text').text(btn.data('text-'+(setup.active?'1':'0')));
                btn.find('span:first-child').removeClass(' '+btn.data('icon-0')+' '+btn.data('icon-1')).addClass(btn.data('icon-'+(setup.active?'1':'0')));
            }

            // Set global active
            if (uuid === ''){  // only in all status mode
                el = $('.exp-table .exp-title .experiment-icon-record');
                if(acnt>0){
                    el.addClass('blink text-danger2');
                }else{
                    SDExperiment.stopTimer('MonId');  // Stop polling
                    el.removeClass('blink text-danger2');
                }

                // Check errors
                if(    ((setup == null) && ($('.exp-table').data('setup-id')!=0))  // changed Setup binded to Experiment?
                    || ((setup != null) && ($('.exp-table').data('setup-id')!=setup.id))
                ){
                    //showSetupUndefined();
                    showExpAlert(SDLab.Language._('experiment.ERROR_CONFIGURATION_ORPHANED_REFRESH'));
                    SDExperiment.stopTimer('MonId');  // Stop polling
                    return;
                }
                else if (!arraysEqual(uuids.sort(),new_uuids.sort())){  // changed monitorings
                    // TODO: get diff for monitors received and viewed, red color for orphaned viewed, show refresh for new monitors
                    //showExpAlert(SDLab.Language._('experiment.ERROR_CONFIGURATION_ORPHANED_REFRESH'));
                    //SDExperiment.stopTimer('MonId');  // Stop polling
                    return;
                }
            }
        } else if (typeof data.error !== 'undefined'){
            //error
            //showExpStateUndefined();
            showExpAlert(SDLab.Language._('experiment.ERROR_STATUS_REFRESH'));
            SDExperiment.stopTimer('MonId');  // Stop polling
        }
    });
    rq.monitor_uuid = uuid;
    if(typeof onalways === "function"){
        rq.always(function(d,textStatus,err) {onalways();});
    }
}

function showMonitorState(jel,data){
    if((jel == null) || (jel.length == 0)) return;
    if(data == null) return;
    var el;

    jel.toggleClass('monitor-active', data.active);

    jel.find('.panel-title .monitor-icon-record')
        .toggleClass('blink text-danger2', data.active);

    jel.find('.panel-title .monitor-icon-errors')
        .toggle((data.err_cnt > 0)?true:false)
        .attr('title', SDLab.Language._('ERRORS')+': '+data.err_cnt);

    jel.find('.monitor-active-hidden').toggle((data.active)?false:true);

    el = jel.find('.monitor-amount-cnt');
    if(el.length>0){
        el.text(data.amount);
    }
    jel.find('.monitor-done-cnt').text(data.done_cnt);
    //jel.find('.monitor-interval').text(data.interval);
    el = jel.find('.monitor-remain-cnt');
    if(el.length>0){
        el.text(data.remain_cnt);
    }

    el = jel.find('.monitor-duration');
    if(el.length>0){
        el.text(data.duration);
    }

    jel.find('.monitor-stopat').text(data.stopat).toggleClass('alert-success', ((data.finished === false || data.finished === true) ? data.finished : false));

    if(!data.active){
        el = jel.find('.monitor-control .monitor-stop');
        if(!el.prop('disabled')){
            el.prop('disabled', true);
            el.addClass('disabled');
        }
        el = jel.find('.monitor-control .monitor-remove');
        if(el.prop('disabled')){
            el.prop('disabled', false);
            el.removeClass('disabled');
        }
    }
}

function showExpStateUndefined() {
    // TODO: Reset to undefined state
}

function showSetupUndefined() {
    // Reset to undefined state
    var el;
    el = $('#setup_amount_cnt');
    if (el.length>0) el.text('?');
    el = $('#setup_done_cnt');
    if (el.length>0) el.text('?');
    el = $('#setup_interval');
    if (el.length>0) el.text('?');
    el = $('#setup_remain_cnt');
    if (el.length>0) el.text('?');
    el = $('#setup_time_det');
    if (el.length>0) el.text('?');
    el = $('#setup_stopat');
    if (el.length>0) el.text('?');
}

// Reset monitor to undefined state
function showMonitorStateUndefined(jel) {
    if((jel == null) || (jel.length == 0)) return;
    var el;
    //jel.removeClass('monitor-active');
    //jel.find('.panel-title .monitor-icon-record')
    //    .removeClass('blink text-danger2');
    jel.find('.panel-title .monitor-icon-errors')
        .toggle(true)
        .attr('title', SDLab.Language._('ERRORS'));
    //jel.find('.monitor-active-hidden').toggle(false);
    el = jel.find('.monitor-amount-cnt');
    if(el.length>0){
        el.text('?');
    }
    jel.find('.monitor-done-cnt').text('?');
    //jel.find('.monitor-interval').text('?');
    el = jel.find('.monitor-remain-cnt');
    if(el.length>0){
        el.text('?');
    }
    el = jel.find('.monitor-duration');
    if(el.length>0){
        el.text('?');
    }
    jel.find('.monitor-stopat').text('?').removeClass('alert-success');
    jel.find('.monitor-control .monitor-stop').prop('disabled', true).addClass('disabled');
    //jel.find('.monitor-control .monitor-strobe').prop('disabled', true).addClass('disabled');
    jel.find('.monitor-control .monitor-remove').prop('disabled', true).addClass('disabled');
}

// Show alert
function showExpAlert(msg) {
    if($('table.exp-table tbody .exp-row-alert').length==0){
        $('table.exp-table tbody').append('\
            <tr class="exp-row-alert">\
                <td>\
                    <div class="alert alert-warning alert-dismissible" role="alert">\
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>\
                        <div>'+msg+'</div>\
                    </div>\
                </td>\
            </tr>\
        ');
        $('.exp-row-alert .alert a').attr('href','javascript:void(0);').click(function(){window.location.reload();});
    }
}
