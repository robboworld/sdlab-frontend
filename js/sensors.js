function createSensorWidget(id, title, value, c){
    var output =
    '<div class="col-lg-3" id="'+ id +'">\
        <div class="panel panel-default">\
            <div class="panel-heading">\
                <h3 class="panel-title">' + title + '</h3>\
            </div>\
            <div class="panel-body">\
                <h1>'+ value +' ' + c + '</h1>\
            </div>\
        </div>\
    </div>';
    $('#sensors-workspace').append(output);
}

function destroySensorWidget(id)
{
    $('#'+id).remove();
}
/*On-load section*/

$(document).ready(function(){
    $('#available-sensors a').on('click', function(){
        if($(this).hasClass('active')){
            $(this).removeClass('active');
            destroySensorWidget($(this).attr('data-id'));
        }
        else
        {
            $(this).addClass('active');
            createSensorWidget(
                $(this).attr('data-id'),
                $(this).text(),
                0,
                ''
            );
        }

    })
})
