/**
 * Graph data class
 * @param   data
 */
/*
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
*/

/**
 * Time series data plot class.
 * One plot for multiple series.
 * @param   placeholder
 * @param   data
 * @param   options
 */
function TimeSeriesPlot(placeholder, data, options) {

    this.p           = null;                     // Plot object
    this.placeholder = placeholder || '#graph';  // Selector
    this.data        = data || [];               // array of series

    this.shiftenabled = true;

    this.xmin        = null;
    this.xmin_       = null;//fulltime as is
    this.xmax        = null;
    this.xmax_       = null;//fulltime as is

    this.ymin        = null;
    this.ymax        = null;

    this.xrange      = 3600;  // Time window/range in seconds (default 1h)

    this._defaults = {
            // Plot and plugins settings
            series: {
                shadowSize: 0    // Drawing is faster without shadows
            },
            xaxis: {
                show: true,
                mode: 'time',
                //tickSize: 100,
                minTickSize: [1, 'second'],

                //min: 0,
                //max: 0,

                // Plugin: time
                //timezone: null,  // "browser" for local to the client or timezone for timezone-js
                timezone: 'browser',
                //timeformat: null,  // format string to use
                timeformat: "%Y/%m/%d %H:%M:%S"
                //twelveHourClock: false,  // 12 or 24 time in time mode
                //monthNames: null,  // list of names of months

                // Plugin: navigate
                //zoomRange: [1, 10],
                //zoomRange: null  // or [ number, number ] (min range, max range) or false
                //panRange: [-10, 10],
                //panRange: null   // or [ number, number ] (min, max) or false
            },
            yaxis: {
                show: true
                //min: 0,
                //min: g.getYMinValue()-1,
                //max: 100,
                //max: g.getYMaxValue()+3
                //tickSize: 1,

                // Plugin: navigate
                //zoomRange: [1, 10],
                //zoomRange: [data[0].data[0][0], data[0].data[data.length-1][0]],
                //zoomRange: null  // or [ number, number ] (min range, max range) or false
                //panRange: [-10, 10],
                //panRange: null   // or [ number, number ] (min, max) or false
            },
            points: {
                show: true
//                fill: true
            },
            lines: {
                show: true,
                fill: true
            },
//            bars: {
//                show: true,
//                barWidth: 1,
//                align: "left"
//            },
            grid: {
                hoverable: true,
                clickable: true
            },

            // Plugin: navigate
            zoom: {
                interactive: true
                //interactive: false,
                //trigger: "dblclick", // or "click" for single click
                //amount: 1.5,         // 2 = 200% (zoom in), 0.5 = 50% (zoom out)
            },
            pan: {
                interactive: true
                //interactive: false,
                //cursor: "move",      // CSS mouse cursor value used when dragging, e.g. "pointer"
                //frameRate: 20,
            },

            // Custom settings
            plottooltip: true,
            xrange     : this.xrange,
    };

    var self = this;

    // Plugin: navigate
    /*
    // TODO: fix init plugin hooks after options merge
    this._defaults.hooks = {
            plotpan: [function(event, plot) {
console.log('call plotpan:');console.log(arguments);
                self.shiftenabled = false;  // disable update-shift on navigation
            }],
            plotzoom: [function(event, plot) {
console.log('call plotzoom:');console.log(arguments);
                self.shiftenabled = false;  // disable update-shift on navigation
            }]
    };
    */

    var settings = {};//global settings
    if (typeof options !== 'undefined') {
        $.extend(true, settings, this._defaults, options);
    } else {
        $.extend(settings, this._defaults);
    }

    // Init Plot
    //$(this.placeholder).empty();
    this.p = $.plot(this.placeholder, this.data, settings);
console.log('init plot');console.log(this.data);console.log(settings);

    // attach plugins hooks (not works through options.hooks)
    // add unknown hooks from options
    /*
    if (typeof this.p.getOptions().hooks !== 'undefined' && this.p.getOptions().hooks.length>0) {
        for (var n in this.p.getOptions().hooks) {
            if (!this.p.hooks[n] && this.p.getOptions().hooks[n].length>0) {
                for (var i = 0; i < this.p.getOptions().hooks[n].length; i++) {
                    this.p.getPlaceholder().bind(n, self.p.getOptions().hooks[n][i]);
                    //this.p.getPlaceholder().bind(n, function(){
                    //    self.p.getOptions().hooks[n][i].apply(window, arguments);
                    //});
                }
            }
        }
    }
    */

    // XXX: temporary fix plugins hooks merge without init options
    (this.p.getPlaceholder()).bind('plotpan', function(event, plot) {
console.log('call plotpan:');console.log(arguments);
        self.shiftenabled = false;  // disable update-shift on navigation
    });
    (this.p.getPlaceholder()).bind('plotzoom', function(event, plot) {
console.log('call plotzoom:');console.log(arguments);
        self.shiftenabled = false;  // disable update-shift on navigation
    });

    // Custom settings
    this.xrange = (settings.xrange === null ? null : (isNaN(settings.xrange) ? null : parseInt(settings.xrange)) );

    // Tooltips init
    if (settings.plottooltip) {
        var tooltipid = 'tooltip';

        if ($("#"+tooltipid).length == 0) {
            $("<div id='"+tooltipid+"'></div>").css({
                position: "absolute",
                display: "none",
                border: "1px solid #fdd",
                padding: "2px",
                "background-color": "#fee",
                opacity: 0.80
            }).appendTo("body");
        }

        $(this.placeholder).bind("plothover", function (event, pos, item) {
            /*
            if ($("#enablePosition:checked").length > 0) {
                var str = "(" + pos.x.toFixed(2) + ", " + pos.y.toFixed(2) + ")";
                $("#hoverdata").text(str);
            }
            */
            //if ($("#enableTooltip:checked").length > 0)
            {
                if (item) {
                    var x = item.datapoint[0],
                        y = item.datapoint[1].toFixed(2);

                    var xdt = (new Date(x)).toISOString();

                    $("#"+tooltipid).html(item.series.label + " : " + xdt + " : " + y)
                        .css({top: item.pageY+5, left: item.pageX+5})
                        .fadeIn(200);
                } else {
                    $("#"+tooltipid).hide();
                }
            }
        });
    }

    this.setData = function(data){
console.log('call setData');
        if (typeof this.p === 'undefined') {
            return;
        }
        this.data = data;
        this.ymin = this.getYMinValue();
        this.ymax = this.getYMaxValue();
        var pxmin = this.getXMinPoint(),
            pxmax = this.getXMaxPoint();
        this.xmin  = ((pxmin !== null) ? pxmin[0] : null);
        this.xmin_ = ((pxmin !== null) ? pxmin[3] : null);
        this.xmax  = ((pxmax !== null) ? pxmax[0] : null);
        this.xmax_ = ((pxmax !== null) ? pxmax[3] : null);

console.log(this.ymin);console.log(this.ymax);console.log(pxmin);console.log(pxmax);

        // TODO: filter out unknown series before set data?

        this.p.setData(this.data);
    };

    this.addData = function(data){
console.log('call addData');
        if (typeof this.p === 'undefined') {
            return 0;
        }

        var scnt = data.length, // new series count
            newcnt = 0, // added count
            pxmin = null,pxmax = null,pymin = null,pymax = null;  // minmax points of new data
        if (scnt>0) {
            for (var i = 0; i < scnt; i++) {
                var idx = this._getSeriesIndexBySensor(data[i].sensor_id, data[i].sensor_val_id);
                if (idx >= 0) {
console.log('found series: '+idx);
                    // found data series
                    if (data[i].data.length > 0) {
                        // nonempty data
                        // add only future values, skip past and incorrect
                        var plast = (this.data[idx].data.length > 0 ? this.data[idx].data[this.data[idx].data.length-1] : null), pd;
                        for (var j = 0; j < data[i].data.length; j++) {
                            pd = data[i].data[j];
                            if (plast === null || this.comparePointsX(pd,plast) > 0) {
                                plast = pd;
                                // update local minmax
                                pxmax = ((pxmax === null || this.comparePointsX(pd,pxmax) > 0) ? pd : pxmax);
                                pxmin = ((pxmin === null || this.comparePointsX(pd,pxmin) < 0) ? pd : pxmin);
                                pymax = ((pymax === null || pd[1] > pymax[1]) ? pd : pymax);
                                pymin = ((pymin === null || pd[1] < pymin[1]) ? pd : pymin);
                                this.data[idx].data.push($.extend(true, [], pd));
                                newcnt++;
                            }
                        }
                    } else {
                        // no data, just info
                        // no action
                    }
                } else {
                    // new data series?
                    /*
                    // add all data 
                    var pd = null;
                    for (var j = 0; j < data[i].data.length; j++) {
                        pd = data[i].data[j];
                        // update local minmax
                        pxmax = ((pxmax === null || this.comparePointsX(pd,pxmax) > 0) ? pd : pxmax);
                        pxmin = ((pxmin === null || this.comparePointsX(pd,pxmin) < 0) ? pd : pxmin);
                        pymax = ((pymax === null || pd[1] > pymax[1]) ? pd : pymax);
                        pymin = ((pymin === null || pd[1] < pymin[1]) ? pd : pymin);
                        newcnt++;
                    }
                    this.data.push(data[i]);
                    */
                }
            }
console.log('newminmax:');console.log(pymin);console.log(pymax);console.log(pxmin);console.log(pxmax);
            // adaptive update minmax
            if (newcnt > 0) {
                //this.ymin = this.getYMinValue();
                if (pymin !== null) {
                    if (this.ymin !== null){
                        if (pymin[1] < this.ymin) {
                            this.ymin = pymin[1];
                        }
                    } else {
                        this.ymin = pymin[1];
                    }
                }
                //this.ymax = this.getYMaxValue();
                if (pymax !== null) {
                    if (this.ymax !== null) {
                        if (pymax[1] > this.ymax) {
                            this.ymax = pymax[1];
                        }
                    } else {
                        this.ymax = pymax[1];
                    }
                }
                //var pxmin = this.getXMinPoint();
                //this.xmin  = ((pxmin !== null) ? pxmin[0] : null);
                //this.xmin_ = ((pxmin !== null) ? pxmin[3] : null);
                if (pxmin !== null) {
                    if (this.xmin !== null) {
                        if (this.comparePointsX(pxmin,[this.xmin, null, null, this.xmin_]) < 0) {
                            this.xmin  = pxmin[0];
                            this.xmin_ = pxmin[3];
                        }
                    } else {
                        this.xmin  = pxmin[0];
                        this.xmin_ = pxmin[3];
                    }
                }
                //var pxmax = this.getXMaxPoint();
                //this.xmax  = ((pxmax !== null) ? pxmax[0] : null);
                //this.xmax_ = ((pxmax !== null) ? pxmax[3] : null);
                if (pxmax !== null) {
                    if (this.xmax !== null) {
                        if (this.comparePointsX(pxmax,[this.xmax, null, null, this.xmax_]) > 0) {
                            this.xmax  = pxmax[0];
                            this.xmax_ = pxmax[3];
                        }
                    } else {
                        this.xmax  = pxmax[0];
                        this.xmax_ = pxmax[3];
                    }
                }
            }
        }

        //this.p.shutdown();
        // TODO: filter out unknown series?
        if (newcnt>0) {
            this.p.setData(this.data);
        }
        return newcnt;
    };

    this._getSeriesIndexBySensor = function(sensor_id,sensor_val_id){
        if (this.data.length<=0) {
            return -1;
        }
        for (var i = 0; i < this.data.length; i++) {
            if (this.data[i].sensor_id == sensor_id && this.data[i].sensor_val_id == sensor_val_id) {
                return i;
            }
        }
        return -1;
    };

    this.setRange = function(value){
        var old = this.xrange;
        this.xrange = ((value !== null && value > 0) ? value : null);
        return old;
    }
    this.getRange = function(){
        return this.xrange;
    }

    this.refresh = function(reset){
console.log('call refresh');
        if (typeof this.p === 'undefined') {
            return;
        }
        reset = reset || false;

        //this.p.shutdown();

        if (reset) {
            var self = this;
            $.each(this.p.getAxes(), function(_, axis) {
                var opts = axis.options;
                if (axis.direction === 'y') {
                    opts.min = ((self.ymin !== null) ? self.ymin : null);
                    opts.max = ((self.ymax !== null) ? self.ymax : null);
                    //opts.zoomRange = [data[0].data[0][1], data[0].data[data.length-1][1]];
                    //opts.panRange = [-10, 10];
                }
                if (axis.direction === 'x') {
console.log('curminmax:');console.log(self.xmin);console.log(self.xmax);console.log(self.xrange);
                    if (self.xrange !== null) {
                        if (self.xmax !== null) {
                            opts.max = self.xmax;
                        } else {
                            opts.max = Number((new Date()).getTime());
                        }
                        opts.min = opts.max - (self.xrange * 1000);
                    } else {
                        if (self.xmax !== null) {
                            opts.max = null;
                            opts.min = null;
                        } else {
                            opts.max = Number((new Date()).getTime());
                            opts.min = null;
                        }
                    }
                    //opts.zoomRange = [data[0].data[0][0], data[0].data[data.length-1][0]];
                    //opts.panRange = [-10, 10];
                }
console.log('newopts:');console.log(opts);
            });
        }
        this.p.setupGrid();
        this.p.draw();
    };

    this.getYMinValue = function(){
        var min = null, v;
        $.each(this.data, function(si, sensor) {
            $.each(sensor.data, function(pi, point) {
                v = parseFloat(point[1]);
                if (min === null || v < min) {
                    min = v;
                }
            });
        });
        return min;
    };
    this.getYMinPoint = function(){
        var min = null, result = null, v;
        $.each(this.data, function(si, sensor) {
            $.each(sensor.data, function(pi, point) {
                v = parseFloat(point[1]);
                if(min === null || v < min) {
                    min = v;
                    result = point;
                }
            });
        });
        return result;
    };
    this.getYMaxValue = function(){
        var max = null, v;
        $.each(this.data, function(si, sensor) {
            $.each(sensor.data, function(pi, point) {
                v = parseFloat(point[1]);
                if(max === null || v > max) {
                    max = v;
                }
            });
        });
        return max;
    };
    this.getYMaxPoint = function(){
        var max = null, result = null, v;
        $.each(this.data, function(si, sensor) {
            $.each(sensor.data, function(pi, point) {
                v = parseFloat(point[1]);
                if(max === null || v > max) {
                    max = v;
                    result = point;
                }
            });
        });
        return result;
    };
    this.getXMinValue = function(){
        var min = null, v;
        $.each(this.data, function(si, sensor) {
            if (sensor.data.length>0) {
                v = sensor.data[0][0];
                if (min === null || v < min) {
                    min = v;
                }
            }
        });
        return min;
    };
    this.getXMinPoint = function(){
        var min = null, result = null, v;
        $.each(this.data, function(si, sensor) {
            if (sensor.data.length>0) {
                v = sensor.data[0][0];
                if (min === null || v < min) {
                    min = v;
                    result = sensor.data[0];
                }
            }
        });
        return result;
    };
    this.getXMaxValue = function(){
        var max = null, v;
        $.each(this.data, function(si, sensor) {
            if (sensor.data.length>0) {
                v = sensor.data[sensor.data.length-1][0];
                if (max === null || v > max) {
                    max = v;
                }
            }
        });
        return max;
    };
    this.getXMaxPoint = function(){
        var max = null, result = null, v;
        $.each(this.data, function(si, sensor) {
            if (sensor.data.length>0) {
                v = sensor.data[sensor.data.length-1][0];
                if (max === null || v > max) {
                    max = v;
                    result = sensor.data[sensor.data.length-1];
                }
            }
        });
        return result;
    };
    this.getTotalPointsCount = function(data){
        if (data.length <= 0) {
            return 0;
        }
        var c = 0;
        for (var i = 0; i < data.length; i++) {
            c = c + data[i].data.length;
        }
        return c;
    }
    this.comparePointsX = function(p1, p2){
        // Compare milliseconds
        if (p1[0] > p2[0]) return  1;
        if (p1[0] < p2[0]) return -1;
        // Compare nanoseconds parts
        var np1 = parseFloat('0.' + ((p1[3]).split(".")[1] || 0)),
            np2 = parseFloat('0.' + ((p2[3]).split(".")[1] || 0));
        if (np1 > np2) return  1;
        if (np1 < np2) return -1;
        return 0;
    };
}