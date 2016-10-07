<?php
$timerange = 60;        // default time range in seconds, int or null|0 for all range
$scrollenabled = true;  // plot auto scroll on new data
$scrollenabled = true;  // plot auto scroll on new data
$xrangeymode = 'auto';
?>
<script type="text/javascript">
    var g,
        updaterPlot=null,
        updaterPlotTime=5,
        timerange=<?php echo (int)$timerange; ?>,
        scrollenabled=<?php echo $scrollenabled ? 'true' : 'false'; ?>,
        xrangeymode='<?php echo addcslashes($xrangeymode , "'"); ?>',
        experiment=<?php echo (int)$this->view->content->experiment->id; ?>,
        seqNum=0;

    $(document).ready(function() {
        var now = new Date();
        g = new TimeSeriesPlot('#graph_all', [], {
            xaxis: {
                min: ((timerange > 0) ? (Number(now.getTime()) - (timerange * 1000)) : null),
                max: Number(now.getTime())
            },
            yaxis: {
                min: null,
                max: null
            },
            hooks : {
                plotpan: [function(event, plot, args) {
                    var dx = ((!args) ? 0 : ((!args.left) ? 0 : args.left));
                    var dy = ((!args) ? 0 : ((!args.top)  ? 0 : args.top));
                    // disable auto scroll when pan x
                    if (dx) {
                        var oldstate = g.scrollenabled;
                        g.scrollenabled = false;
                        onPlotScrollChange(oldstate, g.scrollenabled);
                    }
                }],
                plotzoom: [function(event, plot, args) {
                    var axis = ((!args) ? null : ((!args.axis) ? null : args.axis));

                    // disable auto scroll when zoom x
                    if (axis === null || axis === "x") {
                        var oldstate = g.scrollenabled;
                        g.scrollenabled = false;
                        onPlotScrollChange(oldstate, g.scrollenabled);
                    }
                }]
            },
            xrange: timerange,
            "scrollenabled": scrollenabled,
            "xrangeymode": xrangeymode,
            plottooltip: true
        });

        var choiceContainer = $(".available-sensors");

        // Refresh data with sensor filter
        $('#graph_refesh').click(function() {
            stopPlotUpdate();
            var list = $('input', choiceContainer),
                clist = list.filter(':checked'),
                selall = false,
                params = {'experiment': experiment};
            if (list.length>0) {
                params['show-sensor'] = [];

                if (clist.length>0) {
                    $.each(clist, function() {
                        params['show-sensor'].push($(this).val());
                    });
                } else {
                    selall = true;
                    $.each(list, function() {
                        params['show-sensor'].push($(this).val());
                    });
                }
                var rq = coreAPICall('Detections.getGraphDataAll', params, dataReceivedAll);
                rq.seqnum = ++seqNum;
                rq.always(function(d,textStatus,err) {
                    if (typeof params['show-sensor'] === 'undefined' || params['show-sensor'].length == 0 || selall == true) {
                        $('input', choiceContainer).prop('checked', true);
                    }
                });
            } else {
                // no sensors in list - get all?
                //var rq = coreAPICall('Detections.getGraphDataAll', params, dataReceivedAll);
                //rq.seqnum = ++seqNum;

                // do nothing

                return false;
            }
            return true;
        });

        $(".btn-graph-export").on("click", function(e) {
            e.preventDefault();
            var ft = $(this).data('filetype') || "png";
            exportPlot(g.p, ft);
        });

        $("#chk_xrange_autozoom_y").on("change", function() {
            setRangeXAutozoomY($(this).is(":checked")?"auto":"manual");
        });

        //$('input', choiceContainer).click(function() {
        //    $('#graph_refesh').trigger('click');
        //});

        // Get data
        // Only for all available checked sensors
        var list = $('input', choiceContainer),
            params = {'experiment': experiment};
        if (list.length>0) {
            params['show-sensor'] = [];
            $.each(list, function() {
                params['show-sensor'].push($(this).val());
            });
            var rq = coreAPICall('Detections.getGraphDataAll', params, dataReceivedAll);
            rq.seqnum = ++seqNum;
            // Must be checked all sensors
            rq.always(function(d,textStatus,err) {
                if ($('input', choiceContainer).length>0) {
                    $('input', choiceContainer).prop('checked', true);
                }
            });
        }
    });

    function setDefaultAxis(){
        $.each(g.p.getAxes(), function(_, axis) {
            var opts = axis.options;
            if (axis.direction === 'y') {
                opts.min = null;
                opts.max = null;
            }
            if (axis.direction === 'x') {
                var r = g.getRange(),
                    now = new Date();
                opts.max = Number(now.getTime());
                opts.min = ((r !== null) ? (opts.max - (r * 1000)) : null);
            }
        });
    }

    function stopPlotUpdate(){
console.log('call stopPlotUpdate');
        if (updaterPlot !== null) {
            clearInterval(updaterPlot);
            updaterPlot = null;
        }
    }

    function runPlotUpdate(){
console.log('call runPlotUpdate');
        stopPlotUpdate();

        updaterPlot = setTimeout(function() {
            // Get data
            var params = {};
            params.experiment = experiment;
            if (g.xmax !== null) {
                params.from = g.xmax_;
                params.exclude = 1;  // not include the from-to points
            }
            var rq = coreAPICall('Detections.getGraphData', params, dataReceived);
            rq.seqnum = seqNum;
        }, updaterPlotTime*1000);
    }

    function dataReceivedAll(data, status, jqxhr){
console.log('call dataReceivedAll');
        if (jqxhr.seqnum != seqNum) {
            return;
        }

        if (typeof data.error === 'undefined') {
            g.setData(data.result);
            var pcnt = g.getTotalPointsCount(data.result);
console.log('new count: '+pcnt);
            if (!pcnt) {
                setDefaultAxis();
                // magic for next refresh without data
                g.rxmin = g.p.getAxes().xaxis.options.min;
                g.rxmax = g.p.getAxes().xaxis.options.max;
            }
            var oldstate = g.scrollenabled;
            g.scrollenabled = true; // TODO: setScroll(true/false) with change icon state
            g.refresh();
            onPlotScrollChange(oldstate, g.scrollenabled);

            // Plot data polling
            runPlotUpdate();
        } else {
            //$('#graph_all').empty();
            setInterfaceError($('#graph_msgs'), 'API error: ' + data.error, 3000);
        }
    }

    function dataReceived(data, status, jqxhr){
console.log('call dataReceived');
        if (jqxhr.seqnum != seqNum) {
            return;
        }

        if (typeof data.error === 'undefined') {
            var acnt = g.appendData(data.result);
console.log('added count: '+acnt);
            g.refresh();

            // Plot data polling
            runPlotUpdate();
        } else {
            //$('#graph_all').empty();
            setInterfaceError($('#graph_msgs'), 'API error: ' + data.error, 3000);

            // Plot data polling
            runPlotUpdate();  //xxx: start new update on error too?
        }
    }

    function changeScrollState(state) {
        var oldstate = g.scrollenabled;
        g.scrollenabled = (state ? true : false);
        g.refresh();
        onPlotScrollChange(oldstate, (state ? true : false));
    }

    function clickSetLastXRange(btn,range,scroll) {
        g.setRange(range);
        var oldstate = g.xrangeymode;
        //g.xrangeymode = 'auto';
        onPlotRangeXAutozoomYChange(oldstate, g.xrangeymode);
        g.refresh(true);

        // Fix range buttons state
        $(".control-zoom-range-x .btn-zoom-x").removeClass("active");
        $(".control-zoom-range-x .dropdown-toggle").removeClass("active");
        if (btn) {  // use btn
            $(btn).addClass("active");
            if ($(btn).parent(".dropdown-menu").length) {
                $(btn).parent(".dropdown-menu").siblings(".dropdown-toggle").addClass("active");
            }
        } else {  // found btn by range
            $(".control-zoom-range-x .btn-zoom-x").each(function(idx,elem) {
                if ($(elem).data("value") == range) {
                    $(elem).addClass("active");
                    if ($(elem).parent(".dropdown-menu").length) {
                        $(elem).parent(".dropdown-menu").siblings(".dropdown-toggle").addClass("active");
                    }
                    return false;
                }
            });
        }
        return true;
    }
    function resetZoom() {
        var active = $(".control-zoom-range-x .btn-zoom-x.active");
        if (active.length) {
            clickSetLastXRange(active.get(0),active.data("value"));
        } else {
            clickSetLastXRange(null,timerange);  // use default
        }
        return true;
    }
    function zoomPlot(args, dir) {
        return ((typeof dir !== "undefined" && dir === "out") ? g.zoomOut(args) : g.zoom(args));
    }

    function setRangeXAutozoomY(state) {
        var oldstate = g.xrangeymode;
        g.xrangeymode = state;
        onPlotRangeXAutozoomYChange(oldstate, g.xrangeymode);
        return true;
    }
    function autozoomY() {
        //Todo: add autozoomY
        return true;
    }

    function onPlotScrollChange(oldstate, newstate) {
        var el = $("#btn_scroll_x"), icon;
        if (el.length) {
            el.toggleClass('active', newstate ? true : false)
                .toggleClass('btn-info', newstate ? true : false)
                .data('state', newstate ? 1 : 0);
            icon = el.children('span')
            if (icon.length) {
                icon.removeClass(""+icon.data('icon-0')+" "+icon.data('icon-1')).addClass(newstate ? icon.data('icon-1') : icon.data('icon-0'));
            }
        }
    }
    function onPlotRangeXAutozoomYChange(oldstate, newstate) {
        var el = $("#chk_xrange_autozoom_y"),chk;
        if (el.length) {
            chk = el.is(":checked");
            // fix incorrect checkbox state
            if (chk) {
                if (newstate !== 'auto') {
                    el.prop('checked', false);
                }
            } else {
                if (newstate === 'auto') {
                    el.prop('checked', true);
                }
            }
        }
    }
</script>
<div class="row">
	<div class="col-md-12">
		<a href="/?q=experiment/view/<?php echo (int)$this->view->content->experiment->id; ?>" class="btn btn-sm btn-default">
			<span class="glyphicon glyphicon-chevron-left"></span> <?php echo htmlspecialchars($this->view->content->experiment->title, ENT_QUOTES, 'UTF-8'); ?>
		</a>
	</div>
	<div class="col-md-12">
		<h3><?php echo L::graph_TITLE_GRAPHS_FOR_2(htmlspecialchars($this->view->content->experiment->title, ENT_QUOTES, 'UTF-8')); ?></h3>
	</div>
</div>
<div class="row" style="display:none;">
	<div class="col-md-8">
		<table class="table table-bordered">
			<thead>
				<tr>
					<td><label>#</label></td>
					<td><label><?php echo L::graph_NAME; ?></label></td>
				</tr>
			</thead>
			<tbody>
				<?php foreach($this->view->content->list as $plot):?>
					<tr>
						<td>
							<?php echo ++$i; ?>
						</td>
						<td>
							<a href="?q=experiment/graph/<?php echo (int)$plot->exp_id;?>/<?php echo (int)$plot->id;?>"><?php echo L::GRAPH . ' #' . (int)$plot->id;?></a>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
			<?php if (empty($this->view->content->list)) : ?>
			<tfoot>
				<tr>
					<td colspan="2">
						<div class="alert alert-info">
						<?php echo L::graph_MSG_NO_SAVED_GRAPHS; ?>
						</div>
					</td>
				</tr>
			</tfoot>
			<?php endif; ?>
		</table>
	</div>
	<div class="col-md-4">
		<a href="?q=experiment/graph/<?php echo (int)$this->view->content->experiment->id;?>/add" class="btn btn-primary"><?php echo L::graph_ADD; ?></a>
	</div>
</div>
<hr style="display:none;" />
<div class="row">
	<div class="col-md-12">
		<h3><?php echo L::graph_TITLE_ALL_DETECTIONS_BY_TIME; ?></h3>
	</div>

	<div class="col-lg-9 col-md-12">
		<div id="graph_msgs">
		</div>
		<div class="plot-control-panel-top">
			<div class="btn-toolbar" role="toolbar" aria-label="...">
				<div class="btn-group btn-group-sm" role="group" aria-label="...">
					<button type="button" id="btn_scroll_x" class="btn btn-sm <?php if ($scrollenabled) echo 'btn-info active'; ?>" onclick="return changeScrollState(!$(this).data('state'));" data-state="<?php $scrollenabled ? '1' : '0'; ?>"><span class="fa fa-lg <?php $scrollenabled ? 'fa-eye' : 'fa-eye-slash'; ?>" data-icon-0="fa-eye-slash" data-icon-1="fa-eye"></span></button>
				</div>
				<div class="btn-group btn-group-sm control-zoom-range-x" role="group" aria-label="...">
					<button type="button" class="btn btn-sm btn-zoom-x btn-default<?php $thisrange = 0;             echo $timerange == $thisrange ? ' active' : '' ; ?>" data-value="<?php echo $thisrange; ?>" onclick="return clickSetLastXRange(this,$(this).data('value'));" title="<?php echo L::graph_ZOOM_ALL; ?>"><?php echo L::graph_ZOOM_ALL; ?></button>
					<button type="button" class="btn btn-sm btn-zoom-x btn-default<?php $thisrange = 30;            echo $timerange == $thisrange ? ' active' : '' ; ?>" data-value="<?php echo $thisrange; ?>" onclick="return clickSetLastXRange(this,$(this).data('value'));" title="<?php echo L::graph_ZOOM_30S; ?>"><?php echo L::graph_ZOOM_30S_SHORT; ?></button>
					<button type="button" class="btn btn-sm btn-zoom-x btn-default<?php $thisrange = 1*60;          echo $timerange == $thisrange ? ' active' : '' ; ?>" data-value="<?php echo $thisrange; ?>" onclick="return clickSetLastXRange(this,$(this).data('value'));" title="<?php echo L::graph_ZOOM_1M; ?>"><?php echo L::graph_ZOOM_1M_SHORT; ?></button>
					<button type="button" class="btn btn-sm btn-zoom-x btn-default<?php $thisrange = 15*60;         echo $timerange == $thisrange ? ' active' : '' ; ?>" data-value="<?php echo $thisrange; ?>" onclick="return clickSetLastXRange(this,$(this).data('value'));" title="<?php echo L::graph_ZOOM_15M; ?>"><?php echo L::graph_ZOOM_15M_SHORT; ?></button>
					<button type="button" class="btn btn-sm btn-zoom-x btn-default<?php $thisrange = 30*60;         echo $timerange == $thisrange ? ' active' : '' ; ?>" data-value="<?php echo $thisrange; ?>" onclick="return clickSetLastXRange(this,$(this).data('value'));" title="<?php echo L::graph_ZOOM_30M; ?>"><?php echo L::graph_ZOOM_30M_SHORT; ?></button>
					<button type="button" class="btn btn-sm btn-zoom-x btn-default<?php $thisrange = 1*60*60;       echo $timerange == $thisrange ? ' active' : '' ; ?>" data-value="<?php echo $thisrange; ?>" onclick="return clickSetLastXRange(this,$(this).data('value'));" title="<?php echo L::graph_ZOOM_1H; ?>"><?php echo L::graph_ZOOM_1H_SHORT; ?></button>
					<div class="btn-group btn-group-sm" role="group">
						<button type="button" class="btn btn-sm btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
							<?php echo L::graph_ZOOM; ?>
							<span class="caret"></span>
						</button>
						<ul class="dropdown-menu">
							<li class="btn-zoom-x<?php $thisrange = 1;             echo $timerange == $thisrange ? ' active' : '' ; ?>" data-value="<?php echo $thisrange; ?>" onclick="return clickSetLastXRange(this,$(this).data('value'));"><a href="javascript:void(0);"><?php echo L::graph_ZOOM_1S; ?></a></li>
							<li class="btn-zoom-x<?php $thisrange = 5*60;          echo $timerange == $thisrange ? ' active' : '' ; ?>" data-value="<?php echo $thisrange; ?>" onclick="return clickSetLastXRange(this,$(this).data('value'));"><a href="javascript:void(0);"><?php echo L::graph_ZOOM_5M; ?></a></li>
							<li class="btn-zoom-x<?php $thisrange = 10*60;         echo $timerange == $thisrange ? ' active' : '' ; ?>" data-value="<?php echo $thisrange; ?>" onclick="return clickSetLastXRange(this,$(this).data('value'));"><a href="javascript:void(0);"><?php echo L::graph_ZOOM_10M; ?></a></li>
							<li class="btn-zoom-x<?php $thisrange = 12*60*60;      echo $timerange == $thisrange ? ' active' : '' ; ?>" data-value="<?php echo $thisrange; ?>" onclick="return clickSetLastXRange(this,$(this).data('value'));"><a href="javascript:void(0);"><?php echo L::graph_ZOOM_12H; ?></a></li>
							<li class="btn-zoom-x<?php $thisrange = 1*24*60*60;    echo $timerange == $thisrange ? ' active' : '' ; ?>" data-value="<?php echo $thisrange; ?>" onclick="return clickSetLastXRange(this,$(this).data('value'));"><a href="javascript:void(0);"><?php echo L::graph_ZOOM_1D; ?></a></li>
							<li class="btn-zoom-x<?php $thisrange = 1*7*24*60*60;  echo $timerange == $thisrange ? ' active' : '' ; ?>" data-value="<?php echo $thisrange; ?>" onclick="return clickSetLastXRange(this,$(this).data('value'));"><a href="javascript:void(0);"><?php echo L::graph_ZOOM_1W; ?></a></li>
							<li class="btn-zoom-x<?php $thisrange = 1*30*24*60*60; echo $timerange == $thisrange ? ' active' : '' ; ?>" data-value="<?php echo $thisrange; ?>" onclick="return clickSetLastXRange(this,$(this).data('value'));"><a href="javascript:void(0);"><?php echo L::graph_ZOOM_1MM; ?></a></li>
							<li class="btn-zoom-x<?php $thisrange = 6*30*24*60*60; echo $timerange == $thisrange ? ' active' : '' ; ?>" data-value="<?php echo $thisrange; ?>" onclick="return clickSetLastXRange(this,$(this).data('value'));"><a href="javascript:void(0);"><?php echo L::graph_ZOOM_6MM; ?></a></li>
							<li class="btn-zoom-x<?php $thisrange = 365*24*60*60;  echo $timerange == $thisrange ? ' active' : '' ; ?>" data-value="<?php echo $thisrange; ?>" onclick="return clickSetLastXRange(this,$(this).data('value'));"><a href="javascript:void(0);"><?php echo L::graph_ZOOM_1Y; ?></a></li>
							<li role="separator" class="divider"></li>
							<li><a href="javascript:void(0);" style="padding-left:4px;"><label class="checkbox"><input type="checkbox" id="chk_xrange_autozoom_y" value="1" <?php echo ($xrangeymode === 'auto' ? 'checked' : ''); ?>/>&nbsp;<?php echo L::graph_ZOOM_AUTO_Y; ?></label></a></li>
						</ul>
					</div>
				</div>
				<div class="btn-group btn-group-sm" role="group" aria-label="...">
					<button type="button" class="btn btn-sm btn-default" onclick="runPlotUpdate();"><span class="fa fa-play"></span></button>
					<button type="button" class="btn btn-sm btn-default" onclick="stopPlotUpdate();"><span class="fa fa-pause"></span></button>
				</div>
				<div class="btn-group btn-group-sm graph-export">
					<button type="button" class="btn btn-sm btn-info btn-graph-export" data-filetype="png"><span class="fa fa-download"></span><span class="hidden-xs">&nbsp;<?php echo L::graph_EXPORT; ?></span></button>
					<button type="button" class="btn btn-sm btn-info dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
						<span class="caret"></span>
						<span class="sr-only"><?php echo L::TOGGLE_DROPDOWN; ?></span>
					</button>
					<ul class="dropdown-menu">
						<li><a href="javascript:void(0);" class="btn-graph-export" role="button" data-filetype="png"><span class="fa fa-file-image-o"></span><span class="">&nbsp;png</span></a></li>
						<li><a href="javascript:void(0);" class="btn-graph-export" role="button" data-filetype="jpg"><span class="fa fa-file-image-o"></span><span class="">&nbsp;jpeg</span></a></li>
						<li><a href="javascript:void(0);" class="btn-graph-export" role="button" data-filetype="pdf"><span class="fa fa-file-pdf-o"></span><span class="">&nbsp;pdf</span></a></li>
					</ul>
				</div>
			</div>
		</div>
		<div style="position:relative;">
			<div class="plot-control-panel-left" style="position:absolute;bottom:20px;">
				<div class="btn-group-vertical btn-group-sm control-zoom-y" role="group" aria-label="...">
					<button type="button" class="btn btn-sm btn-default btn-autozoom-y" onclick="return autozoomY();"><span class="glyphicon glyphicon-resize-vertical"></span></button>
				</div><br/><br/>
				<div class="btn-group-vertical btn-group-sm control-zoom-y" role="group" aria-label="...">
					<button type="button" class="btn btn-sm btn-default" onclick="return zoomPlot({axis:'y'},  'in');"><span class="fa fa-expand"></span></button>
					<button type="button" class="btn btn-sm btn-default" onclick="return zoomPlot({axis:'y'}, 'out');"><span class="fa fa-compress"></span></button>
				</div><br/><br/>
				<div class="btn-group-vertical btn-group-sm control-pan-y" role="group" aria-label="...">
					<button type="button" class="btn btn-sm btn-default" onclick="g.pan({top:'-/2'});return true;"><span class="fa fa-long-arrow-up"></span></button>
					<button type="button" class="btn btn-sm btn-default" onclick="g.pan({top:'+/2'});return true;"><span class="fa fa-long-arrow-down"></span></button>
				</div>
			</div>
			<div id="graph_all" style="width: 870px; height: 400px; margin-left: 40px;">
			</div>
		</div>
		<div class="plot-control-panel-bottom" style="padding-left:40px;">
			<div class="btn-toolbar" role="toolbar" aria-label="...">
				<div class="btn-group btn-group-sm control-pan-x" role="group" aria-label="...">
					<button type="button" class="btn btn-sm btn-default" onclick="g.pan({left:'-/2'});return true;"><span class="fa fa-long-arrow-left"></span></button>
					<button type="button" class="btn btn-sm btn-default" onclick="g.pan({left:'+/2'});return true;"><span class="fa fa-long-arrow-right"></span></button>
				</div>
				<div class="btn-group btn-group-sm control-zoom-x" role="group" aria-label="...">
					<button type="button" class="btn btn-sm btn-default" onclick="return zoomPlot({axis:'x'},  'in');"><span class="fa fa-expand"></span></button>
					<button type="button" class="btn btn-sm btn-default" onclick="return zoomPlot({axis:'x'}, 'out');"><span class="fa fa-compress"></span></button>
				</div>
			</div>
		</div>
	</div>
	<div class="col-lg-3 col-md-12">
		<h4><?php echo L::SENSORS; ?></h4>

		<?php if (empty($this->view->content->available_sensors)) : ?>
		<div><?php echo L::graph_NO_SENSORS; ?></div>
		<?php endif; ?>
		<ul class="list-unstyled available-sensors small">
			<?php foreach ($this->view->content->available_sensors as $sensor) : ?>
				<li>
					<label class="checkbox"><input type="checkbox" <?php /* if (array_key_exists($sensor->sensor_id, $this->view->content->displayed_sensors)) echo 'checked';*/?> checked name="show-sensor[]" value="<?php 
						echo htmlspecialchars($sensor->sensor_id . '#' . (int)$sensor->sensor_val_id, ENT_QUOTES, 'UTF-8'); ?>"/>&nbsp;<?php 
						echo htmlspecialchars(constant('L::sensor_VALUE_NAME_' . strtoupper($sensor->value_name)), ENT_QUOTES, 'UTF-8') . ','
							. htmlspecialchars(constant('L::sensor_VALUE_SI_NOTATION_' . strtoupper($sensor->value_name) . '_' . strtoupper($sensor->si_notation)), ENT_QUOTES, 'UTF-8')
							. ' ('  . htmlspecialchars($sensor->sensor_id. '#' . (int)$sensor->sensor_val_id, ENT_QUOTES, 'UTF-8') . ')';
						?></label>
				</li>
			<?php endforeach; ?>
		</ul>
		<button type="button" id="graph_refesh" class="btn btn-primary"><?php echo L::REFRESH; ?></button>
	</div>
</div>
