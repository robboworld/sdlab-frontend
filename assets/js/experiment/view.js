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

    $(document).on('click', '#experiment-strob', function(){
        var id = $(this).attr('experiment-id');
        getExperimentStrob(id);
    })
})


function updateSensorValue(id){
    coreAPICall('Sensors.GetData', {
        Sensor: id,
        ValueIdx: 0
    }, function(data){
        if(typeof data.Reading != 'undefined'){
            $('.sensor-widget[sensor-id="'+id+'"]').find('.sensor-value').html(data.Reading);
        }else{
            $('.sensor-widget[sensor-id="'+id+'"]').find('.sensor-value').html('--');
            $('.sensor-widget[sensor-id="'+id+'"]').find('.panel-body').addClass('bg-danger')
        }

    })
}

function getExperimentStrob(experiment_id){
    $('#experiment-strob').attr('disabled', true).text('Выполняется...');
    coreAPICall('Sensors.experimentStrob', {
        experiment: experiment_id
    }, function(data){
        //console.log(data);
        if(data.result == true){
            $('#experiment-strob').attr('disabled', false).text('Строб');
        } else
        {
            $('#experiment-strob').attr('disabled', false).text('Строб: Не выполнено').addClass('btn-warning');
        }

    })
}