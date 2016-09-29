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
        g = new TimeSeriesPlot('#graph-all', [], {
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
        $('#graph-refesh').click(function() {
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

        $("#graph-export").on("click", function(e) {
            e.preventDefault();
            exportPlot();
        });

        //$('input', choiceContainer).click(function() {
        //    $('#graph-refesh').trigger('click');
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
            //$('#graph-all').empty();
            setInterfaceError($('#graph-msgs'), 'API error: ' + data.error, 3000);
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
            //$('#graph-all').empty();
            setInterfaceError($('#graph-msgs'), 'API error: ' + data.error, 3000);

            // Plot data polling
            runPlotUpdate();  //xxx: start new update on error too?
        }
    }

    function exportPlot(){
        html2canvas(g.p.getPlaceholder().get(0), {
            onrendered: function(canvas) {
                document.body.appendChild(canvas);

                var imgData = canvas.toDataURL('image/png');
console.log('Report Image URL: '+imgData);
                var doc = new jsPDF('landscape');

                doc.addImage(imgData, 'PNG', 10, 10, 190, 95);
                doc.save('plot'+(new Date()).getTime()+'.pdf');
            }
        });
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

	<div class="col-md-9">
		<div id="graph-msgs">
		</div>
		<div id="control-zoom-x">
			<label>XRange</label>
			<div class="btn-group" role="group" aria-label="...">
				<button type="button" class="btn btn-default" onclick="g.setRange(null);g.refresh(true);return true;">All</button>
				<button type="button" class="btn btn-default" onclick="g.setRange(1);g.refresh(true);return true;">1s</button>
				<button type="button" class="btn btn-default" onclick="g.setRange(30);g.refresh(true);return true;">30s</button>
				<button type="button" class="btn btn-default" onclick="g.setRange(1*60);g.refresh(true);return true;">1m</button>
				<button type="button" class="btn btn-default" onclick="g.setRange(30*60);g.refresh(true);return true;">30m</button>
				<button type="button" class="btn btn-default" onclick="g.setRange(1*60*60);g.refresh(true);return true;">1h</button>
				<button type="button" class="btn btn-default" onclick="g.setRange(12*60*60);g.refresh(true);return true;">12h</button>
				<button type="button" class="btn btn-default" onclick="g.setRange(1*24*60*60);g.refresh(true);return true;">1d</button>
				<button type="button" class="btn btn-default" onclick="g.setRange(1*7*24*60*60);g.refresh(true);return true;">1w</button>
				<button type="button" class="btn btn-default" onclick="g.setRange(1*30*24*60*60);g.refresh(true);return true;">1M</button>
				<button type="button" class="btn btn-default" onclick="g.setRange(6*30*24*60*60);g.refresh(true);return true;">6M</button>
				<button type="button" class="btn btn-default" onclick="g.setRange(365*24*60*60);g.refresh(true);return true;">1Y</button>
			</div>
			<div class="btn-group" role="group" aria-label="...">
				<button type="button" class="btn btn-default" onclick="runPlotUpdate();">Update on</button>
				<button type="button" class="btn btn-default" onclick="stopPlotUpdate();">Update off</button>
			</div>
			<div class="btn-group" role="group" aria-label="...">
				<button type="button" id="graph-export" class="btn btn-info">Export</button>
			</div>
		</div>
		<div id="graph-all" style="height: 400px; padding-left: 15px;">
		</div>
	</div>
	<div class="col-md-3">
		<h4><?php echo L::SENSORS; ?></h4>

		<?php if (empty($this->view->content->available_sensors)) : ?>
		<div><?php echo L::graph_NO_SENSORS; ?></div>
		<?php endif; ?>
		<ul class="nav available-sensors">
			<?php foreach ($this->view->content->available_sensors as $sensor) :?>
				<li>
					<label class="chechbox"><input type="checkbox" <?php /* if (array_key_exists($sensor->sensor_id, $this->view->content->displayed_sensors)) echo 'checked';*/?> checked name="show-sensor[]" value="<?php 
						echo htmlspecialchars($sensor->sensor_id . '#' . (int)$sensor->sensor_val_id, ENT_QUOTES, 'UTF-8'); ?>"/>&nbsp;<?php 
						echo htmlspecialchars(constant('L::sensor_VALUE_NAME_' . strtoupper($sensor->value_name)), ENT_QUOTES, 'UTF-8') . ','
							. htmlspecialchars(constant('L::sensor_VALUE_SI_NOTATION_' . strtoupper($sensor->value_name) . '_' . strtoupper($sensor->si_notation)), ENT_QUOTES, 'UTF-8')
							. ' ('  . htmlspecialchars($sensor->sensor_id. '#' . (int)$sensor->sensor_val_id, ENT_QUOTES, 'UTF-8') . ')';
						?></label>
				</li>
			<?php endforeach; ?>
		</ul>
		<button type="button" id="graph-refesh" class="btn btn-primary"><?php echo L::REFRESH; ?></button>
	</div>
</div>
