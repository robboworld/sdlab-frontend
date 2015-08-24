
/* Вызов к php-прослойке */
function coreAPICall(method, params, callback){
    return rq = $.ajax({
        url: '?q=api',
        method: 'get',
        data: {
            method: method,
            params: params
        },
        success: function(result, status, jqxhr){
            callback(result, status, jqxhr);
        },
        error: function(){
            console.log('API Call error: Transport error');
        }
    })
}

/* Добавление блока с описание ошибки в произвольное место*/
function setInterfaceError(holder, message, autoclose){
    $(holder).html('<div class="alert alert-danger">' + message + '</div>');
    var error = $(holder).find('div.alert');
    //автоудаление сообщения об ошибке
    if(typeof autoclose == 'number'){
        setTimeout(function(){
            error.fadeOut(400, function(){
                error.remove();
            });
        }, autoclose);
    }
}


/**
 * API системы использует наносекунды в некоторых параметрах
 * @param value
 * @returns {number}
 */
function nano(value){
    return value * 1000000000;
}

function Graph(data) {
    this.data = data;
    this.getMinValue = function(){
        var min = null;
        $.each(this.data, function(si, sensor){
            $.each(sensor.data, function(pi, point){
                var p = parseFloat(point[1]);
                if(p < min || min == null) min = p;
            });
        });
        return min;
    };
    this.getMaxValue = function(){
        var max = null;
        $.each(this.data, function(si, sensor){
            $.each(sensor.data, function(pi, point){
                var p = parseFloat(point[1]);
                if(p > max || max == null) max = p;
            });
        });
        return max;
    };
}