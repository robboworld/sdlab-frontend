/* TODO: REMOVE! NOT USED SCRIPTS!*/

var sensorsList = new Object();



function destroySensorWidget(id){
    $('#'+id).remove();
}

function updateSensorsList(sensors){

    // TODO: need to compare with full list sensors
    if(typeof sensors.error === 'undefined'){

        /*
        sensors.forEach(function(entry){
            console.log(entry);
            var sensor = new Sensor(entry);
            if([sensor.id] in sensorsList){
                console.log('exists');
            }
            else{
                sensorsList[sensor.id] = sensor;
            }

        })
        */
        for (var id in sensors){
            console.log(sensors[id]);
        }
        updateSensorsListHTML();
    }
    else
    {
        setInterfaceError($('#available-sensors').before(), 'API error: ' + sensors.error, 3000);
        $('#available-sensors').append(list);
    }
}

function updateSensorsListHTML(){
    var sensorsListHolder = $('#available-sensors');
    var list = '';

    for (sensor in sensorsList){
        sensorsList[sensor].widgetActive == true ? active = ' active': active = '';
        list += '<a class="list-group-item '+ active +'" href="#" data-id="'+ sensorsList[sensor].id+'">'+ sensorsList[sensor].name+'<span class="badge">0</span></span></a>';
    }

    sensorsListHolder.html(list);
}

/*On-load section*/

$(document).ready(function(){

    /*
    // Leave page question
    $(document).on('click', 'a:not([href*="#"])', function(e){
        if(!confirm(SDLab.Language._('QUESTION_LEAVE_PAGE'))){
            e.preventDefault();
        }
    });
    */

    // Update sensors list event listener
    coreAPICall('Sensors.getSensors', null, updateSensorsList);
    $(document).on('click', '#sensors-list-update', function(){
        coreAPICall('Sensors.getSensors', null, updateSensorsList);
    });

    // Create sensor widget in workspace and destroy it
    $(document).on('click', '#available-sensors a', function(){
        if($(this).hasClass('active')){
            $(this).removeClass('active');
            sensorsList[$(this).attr('data-id')].destroyWidget();
        }
        else
        {
            $(this).addClass('active');
            /*
            createSensorWidget(
                $(this).attr('data-id'),
                'id: ' + $(this).attr('data-id'),
                0,
                $(this).attr('data-letter')
            );
            */
            sensorsList[$(this).attr('data-id')].createWidget($('#sensors-workspace'));
        }

    })

    $(document).on('click', '.show-graph:not(.active)', function(e){
        e.preventDefault();
        var widget = $(this).parent().parent().parent();

        $(widget).find('a.btn').removeClass('active');
        $(this).addClass('active');

        widget.graph = widget.find('.widget-graph');
        $(widget).find('.widget-pane').removeClass('active');
        widget.graph.addClass('active');
        testFlot(widget.graph);
    });

    $(document).on('click', '.show-info:not(.active)', function(e){
        e.preventDefault();
        var widget = $(this).parent().parent().parent();

        $(widget).find('a.btn').removeClass('active');
        $(this).addClass('active');

        $(widget).find('.widget-pane').removeClass('active');
        $(widget).find('.widget-pane.info').addClass('active');
    })

    $(document).on('click', '#menu-item-sensors-list', function(){
        coreAPICall('Sensors.getSensors', null, updateSensorsList);
        $('#sensors-list-bar').toggle();
        $('#workspace').toggleClass('col-lg-offset-3');
    });

    $(document).on('click', '#workspace', function(){
        $('#sensors-list-bar').hide();
        $('#workspace').removeClass('col-lg-offset-3');
    })
})
