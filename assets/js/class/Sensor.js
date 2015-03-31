function Sensor(options){
    this.name = options.Name;
    this.id = options.id;
    this.value = options.value;
    this.letter = options.letter;
    this.widgetActive = false;

    /* Создание виджета объекта */
    this.createWidget = function(holder){
        var output =
            '<div class="col-lg-4" id="'+ this.id +'">\
            <div class="panel panel-default">\
                <div class="panel-heading">\
                    <h3 class="panel-title"><span class="glyphicon glyphicon-check"></span> ' + this.name + '</h3>\
                </div>\
                <div class="panel-body">\
                    <div class="btn-group btn-group-justified">\
                        <a class="btn btn-sm btn-default show-graph" href="#"><span class="glyphicon glyphicon-stats"> График</span></a>\
                        <a class="btn btn-sm btn-default show-info active" href="#">Инфо</a>\
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

    /* Создание виджета объекта todo: delete */
    this.testCreateWidget = function(holder){
        var output =
            '<div class="col-md-3" id="'+ this.id +'">\
            <div class="panel panel-default">\
                <div class="panel-heading">\
                    <span class="panel-title"><span class="glyphicon glyphicon-eye-open"></span> ' + this.name + '</span>\
                </div>\
                <div class="panel-body">\
                    <div class="widget-pane info active ">\
                        <h3>230.001</h3>\
                    </div>\
                </div>\
            </div>\
        </div>';
        $(holder).append(output);
        this.widgetActive = true;
    }

    /* удаление виджета объекта */
    this.destroyWidget = function(){
        $('#'+this.id).remove(); //todo: более детальную проверку на удаление виджета
        this.widgetActive = false;
    }
}
