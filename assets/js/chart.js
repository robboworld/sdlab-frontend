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

    // global min-max
    this.xmin        = null;
    this.xmin_       = null;  //fulltime as is
    this.xmax        = null;
    this.xmax_       = null;  //fulltime as is
    this.ymin        = null;
    this.ymax        = null;

    // local min-max (on xrange with scroll on)
    this.rxmin       = null;
    this.rxmax       = null;
    this.rymin       = null;
    this.rymax       = null;

    // auto scroll on new data and time range
    this.scrollenabled = true;
    this.xrange      = 3600;   // Time window/range in seconds (default 1h)
    this.xrangeymode = 'auto'; // Y scale mode in xrange (auto - autoscale window, null - autoscale global, manual - no auto scale)

    var self = this;

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
            //hooks : {
            //    plotpan: [function(event, plot) {
            //        self.scrollenabled = false;  // disable auto scroll when navigate
            //    }],
            //    plotzoom: [function(event, plot) {
            //        self.scrollenabled = false;  // disable auto scroll when navigate
            //    }]
            //},

            // Custom settings
            plottooltip   : true,
            xrange        : self.xrange,
            xrangeymode   : self.xrangeymode,
            scrollenabled : self.scrollenabled,
    };

    // Merge settings
    var settings = {};  //global settings
    if (typeof options !== 'undefined') {
        $.extend(true, settings, this._defaults, options);
    } else {
        $.extend(settings, this._defaults);
    }

    // TODO: add calc getMinMaxPoints for init data and xrange

    // Init Plot
    //$(this.placeholder).empty();
    this.p = $.plot(this.placeholder, this.data, settings);

    // attach plugins hooks (no autoadd through options.hooks)
    // add unknown hooks from options
    if (typeof this.p.getOptions().hooks !== 'undefined' && !jQuery.isEmptyObject(this.p.getOptions().hooks)) {
        for (var n in this.p.getOptions().hooks) {
            if (!this.p.hooks[n] && this.p.getOptions().hooks[n].length>0) {
                for (var i = 0; i < this.p.getOptions().hooks[n].length; i++) {
                    this.p.getPlaceholder().bind(n, self.p.getOptions().hooks[n][i]);
                }
            }
        }
    }

    // Fill properties with settings
    // Time range: x axis
    this.xrange      = (settings.xrange === null ? null : (isNaN(settings.xrange) ? null : parseInt(settings.xrange)) );
    // Time range: y mode
    this.xrangeymode = (settings.xrangeymode === null || settings.xrangeymode === 'auto' || settings.xrangeymode === 'manual') ? settings.xrangeymode : null;
    // Auto scroll on new data
    this.scrollenabled = (settings.scrollenabled) ? true : false;

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
console.log('call TimeSeriesPlot.setData');
        if (typeof this.p === 'undefined') {
            return;
        }
        this.data = data;

        // calculate global and last x range min-max
        var po = this.getMinMaxPoints(this.data, this.xrange);

        // update global min-max
        this.ymin  = po.pymin !== null ? po.pymin[1] : null;
        this.ymax  = po.pymax !== null ? po.pymax[1] : null;
        this.xmin  = po.pxmin !== null ? po.pxmin[0] : null;
        this.xmin_ = po.pxmin !== null ? po.pxmin[3] : null;
        this.xmax  = po.pxmax !== null ? po.pxmax[0] : null;
        this.xmax_ = po.pxmax !== null ? po.pxmax[3] : null;

        // update last x range min-max
        this.rymin = po.rpymin !== null ? po.rpymin[1] : null;
        this.rymax = po.rpymax !== null ? po.rpymax[1] : null;
        this.rxmin = po.rxmin;
        this.rxmax = po.rxmax;
console.log('updated minmax:');console.log(po.pymin,po.pymax,po.pxmin,po.pxmax,po.rxmin,po.rxmax,po.rpymin,po.rpymax);

        // TODO: filter out unknown series before set data?
        this.p.setData(this.data);
    };

    this.appendData = function(data){
console.log('call TimeSeriesPlot.appendData');
        if (typeof this.p === 'undefined') {
            return 0;
        }

        var newcnt = 0,     // added points count
            sindexes = [];  // passed series indexes list

        for (var i = 0; i < data.length; i++) {
            var idx = this._getSeriesIndexBySensor(data[i].sensor_id, data[i].sensor_val_id);
            if (idx >= 0) {
                // data series exists
                // check repeated series and skip
                if (sindexes.indexOf(idx) >= 0) {
                    continue;
                }
                sindexes.push(idx);

                if (data[i].data.length > 0) {
                    // nonempty data
                    // add only future values, skip past and incorrect

                    // get current series xmax point
                    // get not null x point
                    var plast = null,
                        j = this.data[idx].data.length-1;
                    while (j>=0) {
                        if (this.data[idx].data[j] !== null && this.data[idx].data[j][0] !== null) {
                            plast = this.data[idx].data[j];
                        }
                        j--;
                    }

                    // add new points
                    var pd, pass;
                    for (var j = 0; j < data[i].data.length; j++) {
                        pd = data[i].data[j];
                        pass = false;
                        if (pd === null) {  // check null point 
                            pass = true;
                        } else {
                            if (pd[0] === null) {  // check x null
                                pass = true;
                            } else {
                                if (plast === null || this.comparePointsX(pd,plast) > 0) {
                                    plast = pd;
                                    pass = true;
                                }
                            }
                        }
                        if (pass) {
                            this.data[idx].data.push($.extend(true, [], pd));
                            newcnt++;
                        }
                    }
                } else {
                    // no data, just info
                    // no action
                }
            } else {
                // TODO: add new data series?
                /*
                // add new data series points
                var pd = null;
                for (var j = 0; j < data[i].data.length; j++) {
                    newcnt++;
                }
                this.data.push($.extend(true, {}, data[i]));
                */
            }
        }

        if (newcnt > 0) {
            // update global min-max

            // TODO: use adaptive update minmax?
            // xxx: last xrange adaptive calc problem

            // use full update
            var po = this.getMinMaxPoints(this.data, this.xrange);
console.log('new minmax:');console.log(po.pxmin,po.pxmax,po.pymin,po.pymax,po.rxmin,po.rxmax,po.rpymin,po.rpymax);
            this.ymin  = po.pymin !== null ? po.pymin[1] : null;
            this.ymax  = po.pymax !== null ? po.pymax[1] : null;
            this.xmin  = po.pxmin !== null ? po.pxmin[0] : null;
            this.xmin_ = po.pxmin !== null ? po.pxmin[3] : null;
            this.xmax  = po.pxmax !== null ? po.pxmax[0] : null;
            this.xmax_ = po.pxmax !== null ? po.pxmax[3] : null;

            // update last x range min-max
            this.rymin = po.rpymin !== null ? po.rpymin[1] : null;
            this.rymax = po.rpymax !== null ? po.rpymax[1] : null;
            this.rxmin = po.rxmin;
            this.rxmax = po.rxmax;

            // TODO: filter out unknown series?
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

    this.setRange = function(value) {
        var old = this.xrange;
        this.xrange = ((value !== null && value > 0) ? value : null);

        var po = this.getMinMaxPoints(this.data, this.xrange);

        // update last x range min-max
        this.rymin = po.rpymin !== null ? po.rpymin[1] : null;
        this.rymax = po.rpymax !== null ? po.rpymax[1] : null;
        this.rxmin = po.rxmin;
        this.rxmax = po.rxmax;

        return old;
    }
    this.getRange = function(){
        return this.xrange;
    }

    this.refresh = function(userange){
console.log('call TimeSeriesPlot.refresh');
        if (typeof this.p === 'undefined') {
            return;
        }
        userange = ((typeof userange === 'undefined') ? false : (userange ? true : false));

        var self = this;
console.log('curxrange:');console.log(self.xrange);console.log(self.xrangeymode);console.log(self.scrollenabled);
        $.each(this.p.getAxes(), function(_, axis) {
            var opts = axis.options;
            if (axis.direction === 'y') {
console.log('yaxis:');console.log(self.ymin);console.log(self.ymax);console.log(self.rymin);console.log(self.rymax);
                if (userange || self.scrollenabled){
                    //if (self.xrange) {
                        if (self.xrangeymode === "auto") {
                            opts.min = self.rymin;  // may be null
                            opts.max = self.rymax;  // may be null
                        } else if (self.xrangeymode === "manual") {
                            // do nothing
                        } else {
                            // set to global
                            opts.min = self.ymin;  // may be null
                            opts.max = self.ymax;  // may be null
                        }
                    //}
                }
                //opts.zoomRange = [data[0].data[0][1], data[0].data[data.length-1][1]];
                //opts.panRange = [-10, 10];
            }
            if (axis.direction === 'x') {
console.log('xaxis:');console.log(self.xmin);console.log(self.xmax);console.log(self.rxmin);console.log(self.rxmax);
                if (userange || self.scrollenabled){
                    opts.min = self.rxmin;
                    opts.max = self.rxmax;
                }

                /*
                if (self.xrange !== null) {
                    if (self.xmax !== null) {
                        
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
                */
                //opts.zoomRange = [data[0].data[0][0], data[0].data[data.length-1][0]];
                //opts.panRange = [-10, 10];
            }
console.log('newopts:');console.log(opts);
        });

        this.p.setupGrid();
        this.p.draw();
    };

    this.zoom = function(args) {
        // By axis zoom, upgraded version of plot.zoom().
        // Added axis option (x or y).
        // @see jquery.flot.navigate.js plot.zoom()
        // args : {amount, center, preventEvent, axis}

        // TODO: need refactor, use disabling axis zoom (opts.zoomRange) and call parent plot.zoom()

console.log('call TimeSeriesPlot.zoom');
        if (typeof this.p === 'undefined') {
            return;
        }

        if (!args)
            args = {};

        var c = args.center,
            amount = args.amount || this.p.getOptions().zoom.amount,
            w = this.p.width(), h = this.p.height(),
            ax = args.axis;

        if (!c)
            c = { left: w / 2, top: h / 2 };

        if (!ax)
            ax = null;

        var xf = c.left / w,
            yf = c.top / h,
            minmax = {
                x: {
                    min: c.left - xf * w / amount,
                    max: c.left + (1 - xf) * w / amount
                },
                y: {
                    min: c.top - yf * h / amount,
                    max: c.top + (1 - yf) * h / amount
                }
            };

        $.each(this.p.getAxes(), function(_, axis) {
            if (ax === null || ax === axis.direction) {
                var opts = axis.options,
                min = minmax[axis.direction].min,
                max = minmax[axis.direction].max,
                zr = opts.zoomRange,
                pr = opts.panRange;

                if (zr === false) // no zooming on this axis
                    return false;

                min = axis.c2p(min);
                max = axis.c2p(max);
                if (min > max) {
                    // make sure min < max
                    var tmp = min;
                    min = max;
                    max = tmp;
                }

                //Check that we are in panRange
                if (pr) {
                    if (pr[0] != null && min < pr[0]) {
                        min = pr[0];
                    }
                    if (pr[1] != null && max > pr[1]) {
                        max = pr[1];
                    }
                }

                var range = max - min;
                if (zr &&
                    ((zr[0] != null && range < zr[0] && amount >1) ||
                     (zr[1] != null && range > zr[1] && amount <1)))
                    return;

                opts.min = min;
                opts.max = max;
            }
        });

        this.p.setupGrid();
        this.p.draw();

        if (!args.preventEvent)
            this.p.getPlaceholder().trigger("plotzoom", [ this.p, args ]);
    };
    this.zoomOut = function(args) {
        // By axis zoom out, upgraded version of plot.zoomOut().
        // Added axis option (x or y).
        // @see jquery.flot.navigate.js plot.zoomOut()
        // args : {amount, center, preventEvent, axis}

        if (typeof this.p === 'undefined') {
            return;
        }

        if (!args)
            args = {};

        if (!args.amount)
            args.amount = this.p.getOptions().zoom.amount;

        args.amount = 1 / args.amount;
        return this.zoom(args);
    }

    this.pan = function(args) {
        if (typeof this.p === 'undefined') {
            return;
        }

        if (!args)
            args = {};

        // Get plot width and height for default pan delta in pixels
        var w = this.p.width(), defw = 10, h = this.p.height(), defh = 10;
        if (w < defw)
            w = defw;
        if (h < defh)
            h = defh;

        switch (args.left) {
        case "+":
            dx = +w;
            break;
        case "-":
            dx = -w;
            break;
        case "+/2":
            dx = +w/2;
            break;
        case "-/2":
            dx = -w/2;
            break;
        default:
            dx = +args.left;
            break;
        }

        switch (args.top) {
        case "+":
            dy = +h;
            break;
        case "-":
            dy = -h;
            break;
        case "+/2":
            dy = +h/2;
            break;
        case "-/2":
            dy = -h/2;
            break;
        default:
            dy = +args.top;
            break;
        }

        var delta = {
            left: +dx,
            top:  +dy
        };

        if (isNaN(delta.left))
            delta.left = 0;
        if (isNaN(delta.top))
            delta.top = 0;

        var newargs = {};
        $.extend(true, newargs, args, delta);

        this.p.pan(newargs);
    }

    this.getYMinValue = function(data){
        var min = null,
            d = (typeof data === "undefined" || data === null) ? this.data : data;
        $.each(d, function(si, series) {
            $.each(series.data, function(pi, point) {
                if (point !== null) {
                    var v = parseFloat(point[1]);
                    if (!isNaN(v) && (min === null || v < min)) {
                        min = v;
                    }
                }
            });
        });
        return min;
    };
    this.getYMinPoint = function(data){
        var min = null, result = null,
            d = (typeof data === "undefined" || data === null) ? this.data : data;
        $.each(d, function(si, series) {
            $.each(series.data, function(pi, point) {
                if (point !== null) {
                    var v = parseFloat(point[1]);
                    if(!isNaN(v) && (min === null || v < min)) {
                        min = v;
                        result = point;
                    }
                }
            });
        });
        return result;
    };
    this.getYMaxValue = function(data){
        var max = null,
            d = (typeof data === "undefined" || data === null) ? this.data : data;
        $.each(d, function(si, series) {
            $.each(series.data, function(pi, point) {
                if (point !== null) {
                    var v = parseFloat(point[1]);
                    if (!isNaN(v) && (max === null || v > max)) {
                        max = v;
                    }
                }
            });
        });
        return max;
    };
    this.getYMaxPoint = function(data){
        var max = null, result = null,
            d = (typeof data === "undefined" || data === null) ? this.data : data;
        $.each(d, function(si, series) {
            $.each(series.data, function(pi, point) {
                if (point !== null) {
                    var v = parseFloat(point[1]);
                    if (!isNaN(v) && (max === null || v > max)) {
                        max = v;
                        result = point;
                    }
                }
            });
        });
        return result;
    };
    this.getXMinValue = function(data){
        var min = null,
            d = (typeof data === "undefined" || data === null) ? this.data : data;
        $.each(d, function(si, series) {
            // get not null x point
            var i = 0, v;
            while (i<series.data.length) {
                if (series.data[i] !== null) {
                    v = series.data[i][0];
                    if (v !== null) {
                        if (min === null || v < min) {
                            min = v;
                        }
                        break;
                    } 
                }
                i++;
            }
        });
        return min;
    };
    this.getXMinPoint = function(data){
        var min = null, result = null,
            d = (typeof data === "undefined" || data === null) ? this.data : data;
        $.each(d, function(si, series) {
            // get not null x point
            var i = 0, v;
            while (i<series.data.length) {
                if (series.data[i] !== null) {
                    v = series.data[i][0];
                    if (v !== null) {
                        if (min === null || v < min) {
                            min = v;
                            result = series.data[i];
                        }
                        break;
                    }
                }
                i++;
            }
        });
        return result;
    };
    this.getXMaxValue = function(data){
        var max = null,
            d = (typeof data === "undefined" || data === null) ? this.data : data;
        $.each(d, function(si, series) {
            // get not null x value
            var i = series.data.length-1, v;
            while (i>=0) {
                if (series.data[i] !== null) {
                    v = series.data[i][0];
                    if (v !== null) {
                        if (max === null || v > max) {
                            max = v;
                        }
                        break;
                    }
                }
                i--;
            }
        });
        return max;
    };
    this.getXMaxPoint = function(data){
        var max = null, result = null,
            d = (typeof data === "undefined" || data === null) ? this.data : data;
        $.each(d, function(si, series) {
            // get not null x point
            var i = series.data.length-1, v;
            while (i>=0) {
                if (series.data[i] !== null) {
                    v = series.data[i][0];
                    if (v !== null) {
                        if (max === null || v > max) {
                            max = v;
                            result = series.data[i];
                        }
                        break;
                    }
                }
                i--;
            }
        });
        return result;
    };
    this.getMinMaxPoints = function(data, xlastrange){
console.log('call TimeSeriesPlot.getMinMaxPoints');
        var d = (typeof data === "undefined" || data === null) ? this.data : data,
            result = {
                pxmin: null,
                pxmax: null,
                pymin: null,
                pymax: null,
                rxmin: null,
                rxmax: null,
                rpymin: null,
                rpymax: null,
            }; 

        xlastrange = (typeof xlastrange === "undefined") ? null : xlastrange;
        if (xlastrange !== null) {
            xlastrange = parseInt(xlastrange);
            if (isNaN(xlastrange)) {
                xlastrange = null;
            }
        }

        // get fast xmin-xmax
        $.each(d, function(si, series) {
            var v, i;
            // xmin
            // get not null x point
            i = 0;
            while (i<series.data.length) {
                if (series.data[i] !== null) {
                    v = series.data[i][0];
                    if (v !== null) {
                        if (result.pxmin === null || v < result.pxmin[0]) {
                            result.pxmin = series.data[i];
                        }
                        break;
                    }
                }
                i++;
            }
console.log('xmin: series:', si, ' pxmin: ', result.pxmin);
            // xmax
            // get not null x point
            i = series.data.length-1;
            while (i>=0) {
                if (series.data[i] !== null) {
                    v = series.data[i][0];
                    if (v !== null) {
                        if (result.pxmax === null || v > result.pxmax[0]) {
                            result.pxmax = series.data[i];
                        }
                        break;
                    }
                }
                i--;
            }
console.log('xmax: series:', si, ' pxmax: ', result.pxmax);
        });

        if (xlastrange && result.pxmax !== null) {
            result.rxmax = result.pxmax[0];
            result.rxmin = result.rxmax - xlastrange * 1000;
        }
console.log('rxmin: ', result.rxmin, ' rxmax: ', result.rxmax);
        // get other
        $.each(d, function(si, series) {
            // ymin-ymax, rpymin-rpymax
            var isrange = false;
            $.each(series.data, function(pi, point) {
                if (point !== null) {
                    var vx = point[0],
                        vy = parseFloat(point[1]);
                    if (!isNaN(vy)) {
                        // ymin
                        if (result.pymin === null || vy < result.pymin[1]) {
                            result.pymin = point;
                        }
                        // ymax
                        if (result.pymax === null || vy > result.pymax[1]) {
                            result.pymax = point;
                        }
                    }
                    // x last range
                    if (xlastrange && (result.pxmax !== null)) {
                        // check if inside range interval or not
                        if (vx !== null) {
                            if (isrange) {
                                if ((vx < result.rxmin) || (vx > result.rxmax)) {
                                    isrange = false;
                                }
                            } else {
                                if ((vx >= result.rxmin) && (vx <= result.rxmax)) {
                                    isrange = true;
                                }
                            }
                        }
                        // rpymin
                        if (!isNaN(vy) && isrange && (result.rpymin === null || vy <= result.rpymin[1])) {
                            result.rpymin = point;
                        }
                        // rpymax
                        if (!isNaN(vy) && isrange && (result.rpymax === null || vy >= result.rpymax[1])) {
                            result.rpymax = point;
                        }
                    }
                }
            });
        });
        // default rpymin-rpymax
        if (xlastrange === null) {
            result.rpymin = result.pymin;
        }
        if (xlastrange === null) {
            result.rpymax = result.pymax;
        }
console.log('result:',result);
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
        var np1 = parseFloat('0.' + (String(p1[3]).split(".")[1] || 0)),
            np2 = parseFloat('0.' + (String(p2[3]).split(".")[1] || 0));
        if (np1 > np2) return  1;
        if (np1 < np2) return -1;
        return 0;
    };
}

function exportPlot(plot,ftype) {
    if (ftype !== 'pdf' && ftype !== 'jpg' && ftype !== 'png') return false;

    var oldbg = plot.getPlaceholder().get(0).style.backgroundColor;  // save bg
    plot.getPlaceholder().get(0).style.backgroundColor = "white";  // change bg

    html2canvas(plot.getPlaceholder().get(0), {
        onrendered: function(canvas) {
            plot.getPlaceholder().get(0).style.backgroundColor = oldbg;  // restore bg

            var filename = 'plot'+(new Date()).getTime();
            switch (ftype) {
            case "pdf":
                var mimeType = "image/png",
                    imgData = canvas.toDataURL(mimeType),
                    width = canvas.width,
                    height = canvas.height,
                    k = height/width,
                    nw = 180, nh = nw*k;
                var doc = new jsPDF('landscape', 'mm', 'a4');
                doc.addImage(imgData, 'PNG', 10, 10, nw+10, nh+10);
                doc.save(filename+'.pdf');
                break;

            case "jpg":
                var mimeType = "image/jpeg",
                    // toDataURL defaults to png, so we need to request a jpeg, then convert for file download.
                    imgData = canvas.toDataURL(mimeType).replace(mimeType, "image/octet-stream");

                //window.open(imgData);
                return downloadData(imgData, filename + ".jpg", mimeType);
                break;

            case "png":
            default:
                var mimeType = "image/png",
                    imgData = canvas.toDataURL(mimeType);

                //window.open(imgData);
                return downloadData(imgData, filename + ".png", mimeType);
                break;
            }
        }
    });
}
