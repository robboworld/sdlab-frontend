function Sensor(options){
    this.name = options.Name;
    this.id = options.id;
    this.value = options.value;
    this.letter = options.letter;
    this.widgetActive = false;

    // Create sensor widget
    this.createWidget = function(holder){
        var output =
            '<div class="col-lg-4" id="'+ this.id +'">\
            <div class="panel panel-default">\
                <div class="panel-heading">\
                    <h3 class="panel-title"><span class="glyphicon glyphicon-check"></span> ' + this.name + '</h3>\
                </div>\
                <div class="panel-body">\
                    <div class="btn-group btn-group-justified">\
                        <a class="btn btn-sm btn-default show-graph" href="#"><span class="glyphicon glyphicon-stats"> '+ SDLab.Language._('GRAPH') +'</span></a>\
                        <a class="btn btn-sm btn-default show-info active" href="#">'+ SDLab.Language._('INFO') +'</a>\
                    </div>\
                    <div class="widget-pane info active ">\
                        <div class="label label-info">'+ this.value +' ' + this.letter + '</div>\
                    </div>\
                    <div class="widget-pane widget-graph">\
                    </div>\
                </div>\
            </div>\
        </div>';
        $(holder).append(output);
        this.widgetActive = true;
    }

    // Remove sensor widget
    this.destroyWidget = function(){
        $('#'+this.id).remove(); // TODO: create check for removing widget
        this.widgetActive = false;
    }
}
