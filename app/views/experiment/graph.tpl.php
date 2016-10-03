<?php
$timerange = 60;  // default time range
?>
<script type="text/javascript">
    var g,
        updaterPlot=null,
        updaterPlotTime=5,
        timerange=<?php echo (($timerange > 0) ? $timerange : null); ?>,
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
            xrange: timerange,
            plottooltip: true
        });
console.log('created TimeSeriesPlot:');console.log(g);

        var choiceContainer = $(".available-sensors");

        // Refresh data with sensor filter
        $('#graph_refesh').click(function() {
            stopPlotUpdate();
            var list = $('input', choiceContainer),
                clist = list.filter(':checked'), selall = false,
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
console.log('call dataReceivedAll');console.log(data);console.log('jqxhr.seqnum: '+jqxhr.seqnum);
        if (jqxhr.seqnum != seqNum) {
console.log('old seqnum,now: '+seqNum);
            return;
        }

        if (typeof data.error === 'undefined') {
            g.setData(data.result);
            var pcnt = g.getTotalPointsCount(data.result);
console.log('new count: '+pcnt);
            if (pcnt > 0) {
                g.refresh(true);
            } else {
                setDefaultAxis();
                g.refresh(false);
            }

            // Plot data polling
            runPlotUpdate();
        } else {
            //$('#graph_all').empty();
            setInterfaceError($('#graph_msgs'), 'API error: ' + data.error, 3000);
        }
    }

    function dataReceived(data, status, jqxhr){
console.log('call dataReceived');console.log(data);console.log('jqxhr.seqnum: '+jqxhr.seqnum);
        if (jqxhr.seqnum != seqNum) {
console.log('old seqnum,now: '+seqNum);
            return;
        }

        if(typeof data.error === 'undefined') {
            var acnt = g.addData(data.result);
console.log('added count: '+acnt);
            g.refresh((acnt > 0 && g.shiftenabled) ? true : false);

            // Plot data polling
            runPlotUpdate();
        } else {
            //$('#graph_all').empty();
            setInterfaceError($('#graph_msgs'), 'API error: ' + data.error, 3000);

            // Plot data polling
            runPlotUpdate();  //xxx: start new update on error too?
        }
    }

    function clickSetRange(el,range) {
        g.setRange(range);
        g.refresh(true)
        // Fix buttons state
        $(".control-zoom-range-x .btn-zoom-x").removeClass("active");
        $(".control-zoom-range-x .dropdown-toggle").removeClass("active");
        if (el) {  // use btn
            $(el).addClass("active");
            if ($(el).parent(".dropdown-menu").length) {
                $(el).parent(".dropdown-menu").siblings(".dropdown-toggle").addClass("active");
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
    function resetZoom(shifton) {
        var active = $(".control-zoom-range-x .btn-zoom-x.active");
        if (active.length) {
            clickSetRange(active.get(0),active.data("value"));
        } else {
            clickSetRange(null,timerange);  // use default
        }
        if (shifton === true) {
            g.shiftenabled = true;
        } else if (shifton === false) {
            g.shiftenabled = false;
        }
        return true;
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
					<button type="button" class="btn btn-sm btn-link" onclick="resetZoom(true);"><span class="glyphicon glyphicon-eye-open"></span></button>
				</div>
				<div class="btn-group btn-group-sm control-zoom-range-x" role="group" aria-label="...">
					<button type="button" class="btn btn-sm btn-zoom-x btn-default<?php $thisrange = 0;             echo $timerange == $thisrange ? ' active' : '' ; ?>" data-value="<?php echo $thisrange; ?>" onclick="return clickSetRange(this,$(this).data('value'));" title="<?php echo L::graph_ZOOM_ALL; ?>"><?php echo L::graph_ZOOM_ALL; ?></button>
					<button type="button" class="btn btn-sm btn-zoom-x btn-default<?php $thisrange = 30;            echo $timerange == $thisrange ? ' active' : '' ; ?>" data-value="<?php echo $thisrange; ?>" onclick="return clickSetRange(this,$(this).data('value'));" title="<?php echo L::graph_ZOOM_30S; ?>"><?php echo L::graph_ZOOM_30S_SHORT; ?></button>
					<button type="button" class="btn btn-sm btn-zoom-x btn-default<?php $thisrange = 1*60;          echo $timerange == $thisrange ? ' active' : '' ; ?>" data-value="<?php echo $thisrange; ?>" onclick="return clickSetRange(this,$(this).data('value'));" title="<?php echo L::graph_ZOOM_1M; ?>"><?php echo L::graph_ZOOM_1M_SHORT; ?></button>
					<button type="button" class="btn btn-sm btn-zoom-x btn-default<?php $thisrange = 15*60;         echo $timerange == $thisrange ? ' active' : '' ; ?>" data-value="<?php echo $thisrange; ?>" onclick="return clickSetRange(this,$(this).data('value'));" title="<?php echo L::graph_ZOOM_15M; ?>"><?php echo L::graph_ZOOM_15M_SHORT; ?></button>
					<button type="button" class="btn btn-sm btn-zoom-x btn-default<?php $thisrange = 30*60;         echo $timerange == $thisrange ? ' active' : '' ; ?>" data-value="<?php echo $thisrange; ?>" onclick="return clickSetRange(this,$(this).data('value'));" title="<?php echo L::graph_ZOOM_30M; ?>"><?php echo L::graph_ZOOM_30M_SHORT; ?></button>
					<button type="button" class="btn btn-sm btn-zoom-x btn-default<?php $thisrange = 1*60*60;       echo $timerange == $thisrange ? ' active' : '' ; ?>" data-value="<?php echo $thisrange; ?>" onclick="return clickSetRange(this,$(this).data('value'));" title="<?php echo L::graph_ZOOM_1H; ?>"><?php echo L::graph_ZOOM_1H_SHORT; ?></button>
					<div class="btn-group btn-group-sm" role="group">
						<button type="button" class="btn btn-sm btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
							<?php echo L::graph_ZOOM; ?>
							<span class="caret"></span>
						</button>
						<ul class="dropdown-menu">
							<li class="btn-zoom-x<?php $thisrange = 1;             echo $timerange == $thisrange ? ' active' : '' ; ?>" data-value="<?php echo $thisrange; ?>" onclick="return clickSetRange(this,$(this).data('value'));"><a href="javascript:void(0);"><?php echo L::graph_ZOOM_1S; ?></a></li>
							<li class="btn-zoom-x<?php $thisrange = 12*60*60;      echo $timerange == $thisrange ? ' active' : '' ; ?>" data-value="<?php echo $thisrange; ?>" onclick="return clickSetRange(this,$(this).data('value'));"><a href="javascript:void(0);"><?php echo L::graph_ZOOM_12H; ?></a></li>
							<li class="btn-zoom-x<?php $thisrange = 1*24*60*60;    echo $timerange == $thisrange ? ' active' : '' ; ?>" data-value="<?php echo $thisrange; ?>" onclick="return clickSetRange(this,$(this).data('value'));"><a href="javascript:void(0);"><?php echo L::graph_ZOOM_1D; ?></a></li>
							<li class="btn-zoom-x<?php $thisrange = 1*7*24*60*60;  echo $timerange == $thisrange ? ' active' : '' ; ?>" data-value="<?php echo $thisrange; ?>" onclick="return clickSetRange(this,$(this).data('value'));"><a href="javascript:void(0);"><?php echo L::graph_ZOOM_1W; ?></a></li>
							<li class="btn-zoom-x<?php $thisrange = 1*30*24*60*60; echo $timerange == $thisrange ? ' active' : '' ; ?>" data-value="<?php echo $thisrange; ?>" onclick="return clickSetRange(this,$(this).data('value'));"><a href="javascript:void(0);"><?php echo L::graph_ZOOM_1MM; ?></a></li>
							<li class="btn-zoom-x<?php $thisrange = 6*30*24*60*60; echo $timerange == $thisrange ? ' active' : '' ; ?>" data-value="<?php echo $thisrange; ?>" onclick="return clickSetRange(this,$(this).data('value'));"><a href="javascript:void(0);"><?php echo L::graph_ZOOM_6MM; ?></a></li>
							<li class="btn-zoom-x<?php $thisrange = 365*24*60*60;  echo $timerange == $thisrange ? ' active' : '' ; ?>" data-value="<?php echo $thisrange; ?>" onclick="return clickSetRange(this,$(this).data('value'));"><a href="javascript:void(0);"><?php echo L::graph_ZOOM_1Y; ?></a></li>
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
					<button type="button" class="btn btn-sm btn-default" onclick="zoomIn(alert('todo'),'y');"><span class="fa fa-expand"></span></button>
					<button type="button" class="btn btn-sm btn-default" onclick="zoomOut(alert('todo'),'y');"><span class="fa fa-compress"></span></button>
				</div><br/><br/>
				<div class="btn-group-vertical btn-group-sm control-pan-y" role="group" aria-label="...">
					<button type="button" class="btn btn-sm btn-default" onclick="panMinus(alert('todo'),'y');"><span class="fa fa-long-arrow-up"></span></button>
					<button type="button" class="btn btn-sm btn-default" onclick="panMinus(alert('todo'),'y');"><span class="fa fa-long-arrow-down"></span></button>
				</div>
			</div>
			<div id="graph_all" style="width: 870px; height: 400px; margin-left: 40px;">
			</div>
		</div>
		<div class="plot-control-panel-bottom" style="padding-left:40px;">
			<div class="btn-toolbar" role="toolbar" aria-label="...">
				<div class="btn-group btn-group-sm control-pan-x" role="group" aria-label="...">
					<button type="button" class="btn btn-sm btn-default" onclick="panMinus(alert('todo'),'x');"><span class="fa fa-long-arrow-left"></span></button>
					<button type="button" class="btn btn-sm btn-default" onclick="panPlus(alert('todo'),'x');"><span class="fa fa-long-arrow-right"></span></button>
				</div>
				<div class="btn-group btn-group-sm control-zoom-x" role="group" aria-label="...">
					<button type="button" class="btn btn-sm btn-default" onclick="zoomIn(alert('todo'),'x');"><span class="fa fa-expand"></span></button>
					<button type="button" class="btn btn-sm btn-default" onclick="zoomOut(alert('todo'),'x');"><span class="fa fa-compress"></span></button>
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
