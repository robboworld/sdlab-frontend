function coreAPICall(method, params, callback){
    $.ajax({
        url: 'api.php',
        method: 'get',
        data: {
            method: method,
            params: params
        },
        success: function(result){
            callback(result);
        },
        error: function(){
            alert('Transport error');
        }
    })
}

function divideCallback(object){
    var data = JSON.parse(object);
    console.log(data);

    $('#divide-result').empty();
    if(data.result){
        var htmlResult = 'Ouo: '+data.result.Quo+', Rem: ' + data.result.Rem;
        $('#divide-result').append(htmlResult);
    }

    if(data.error != null){
        $('#divide-result').append('<div class="alert alert-danger">' +data.error+'</div>');
    }
}

function multiplyCallback(object){
    var data = JSON.parse(object);
    console.log(data);

    $('#multiply-result').empty();
    if(data.result){
        $('#multiply-result').append(data.result);
    }
    if(data.error != null){
        $('#multiply-result').append('<div class="alert alert-danger">' +data.error+'</div>');
    }
}

/* On-load section*/
$(document).ready(function(){
    $('#form-multiply').on('submit', function(e){
        e.preventDefault();
        coreAPICall('Arith.Multiply', {
            A: $(this).children('input[name="A"]').val(),
            B: $(this).children('input[name="B"]').val()
        }, multiplyCallback);
    })

    $('#form-divide').on('submit', function(e){
        e.preventDefault();
        coreAPICall('Arith.Divide', {
            A: $(this).children('input[name="A"]').val(),
            B: $(this).children('input[name="B"]').val()
        }, divideCallback);
    })
});