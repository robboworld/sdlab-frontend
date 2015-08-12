
/* Вызов к php-прослойке */
function coreAPICall(method, params, callback){
    return rq = $.ajax({
        url: '?q=api',
        method: 'get',
        data: {
            method: method,
            params: params
        },
        success: function(result){
            callback(result);
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

function  Graph (data) {
    this.data = data;
    this.getMinValue = function (){
        var min = null;
        this.data.forEach(function(sensor){
           sensor.data.forEach(function(point){
               if(point[1] < min || min == null) min = point[1];
           })
        });
        return min;
    }

    this.getMaxValue = function (){
        var max = null;
        this.data.forEach(function(sensor){
            sensor.data.forEach(function(point){
                if(point[1] > max || max == null) max = point[1];
            })
        });
        return max;
    }
}