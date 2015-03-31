function testFlot(placeholder)
{
    var data = [],
        totalPoints = 50;

    function getRandomData() {

        if (data.length > 0)
            data = data.slice(1);

        // Do a random walk

        while (data.length < totalPoints) {

            var prev = data.length > 0 ? data[data.length - 1] : 50,
                y = prev + Math.random() * 10 - 5;

            if (y < 0) {
                y = 0;
            } else if (y > 100) {
                y = 100;
            }

            data.push(y);
        }

        // Zip the generated y values with the x values

        var res = [];
        for (var i = 0; i < data.length; ++i) {
            res.push([i, data[i]])
        }

        return res;
    }

    var data = [
        {
            label: "Температура",
            color: 1,
            data: [
                [(new Date("2014-04-05 17:00").getTime()), 21.3],
                [(new Date("2014-04-06 09:05").getTime()), 19.7],
                [(new Date('2014-04-06 12:00:01.12').getTime()), 20.1],
                [(new Date('2014-04-06 17:00:00.53').getTime()), 20.7],
                [(new Date('2014-04-07 9:05:01.05').getTime()), 19.9],
                [(new Date('2014-04-07 12:00:00:31').getTime()), 20.3],
                [(new Date('2014-04-07 17:00:00.74').getTime()), 21.5]
            ]
        }];

    console.log((new Date("2014-04-06 09:05").getTime()/1000));
    // Set up the control widget

    var updateInterval = 1000;
    $("#updateInterval").val(updateInterval).change(function () {
        var v = $(this).val();
        if (v && !isNaN(+v)) {
            updateInterval = +v;
            if (updateInterval < 1) {
                updateInterval = 1;
            } else if (updateInterval > 2000) {
                updateInterval = 2000;
            }
            $(this).val("" + updateInterval);
        }
    });

    $(placeholder).empty();
    var plot = $.plot(placeholder, data, {
        series: {
            shadowSize: 0	// Drawing is faster without shadows
        },
        yaxis: {
            min: 15,
            max: 25,
            tickSize: 1
        },
        xaxis: {
            show: true,
            mode: 'time',
            timeformat: "%Y/%m/%d %H:%m",
            timezone: 'browser'
        },
        points: {
            show: true
        },
        lines: {
            show: true,
            fill: true
        }
    });

    function update() {

        plot.setData([getRandomData()]);

        // Since the axes don't change, we don't need to call plot.setupGrid()

        plot.draw();
        setTimeout(update, updateInterval);
    }

    //update();
}


function buildGraph(data, placeholder, options){
    $(placeholder).empty();

    var settings = {
        series: {
            shadowSize: 0	// Drawing is faster without shadows
        },
        yaxis: {
            min: 15,
            max: 25,
            tickSize: 1,
            //zoomRange: [1, 10],
            pan: [-10, 10]
        },
        /*
         xaxis: {
         show: true,
         mode: 'time',
         timeformat: "%Y/%m/%d %H:%m:%S",
         minTickSize: [1, 'second'],
         timezone: 'browser',
         zoomRange: [1, 10],
         pan: [-10, 10]
         },

         */
        xaxis: {
            //tickSize: 100,
            //zoomRange: [1, 10],
            //pan: [-10, 10]
        },
        points: {
            show: true
        },
        lines: {
            show: true,
            fill: true
        },
        zoom: {
            interactive: true
        },
        pan: {
            interactive: true
        }
    }

    if(typeof (options) != 'undefined'){
        $.extend(settings, options)
    }
    var plot = $.plot(placeholder, data, settings);
}