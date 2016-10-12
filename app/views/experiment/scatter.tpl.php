<?php
$lang_tag = '';
if (is_object($this->app->lang))
{
	$lang_tag = strtolower(substr($this->app->lang->getAppliedLang(),0,2));
}
if (empty($lang_tag))
{
	$lang_tag = 'en';
}
?>
<script type="text/javascript">
    var g,
        experiment=<?php echo (int)$this->view->content->experiment->id; ?>,
        errcnt=<?php echo (int)count($this->view->content->error); ?>,
        ufrom=<?php echo (($this->view->content->from !== null) ? $this->view->content->from->format('U') : 'null'); ?>,
        uto=<?php echo (($this->view->content->to !== null) ? $this->view->content->to->format('U') : 'null'); ?>;

    $(document).ready(function() {
        var now = new Date();
        g = new ScatterPlot('#graph_scatter', [], {
            xaxis: {
                min: null,
                max: null,
            },
            yaxis: {
                min: null,
                max: null
            },
            plottooltip: true
        });

        var dtFrom = $("#datetime_from"),
            dtTo = $("#datetime_to"),
            dt;

        if (ufrom !== null) {
            dt = new Date(ufrom*1000);
            //dtFrom.val(formatDate(dt, 'yyyy-MM-dd HH:mm:ss'));
            //dtFrom.val(dt.toISOString());
            dtFrom.val(formatDate(dt, 'yyyy-MM-dd HH:mm:ssK'));
        }
        if (uto !== null) {
            dt = new Date(uto*1000);
            //dtTo.val(formatDate(dt, 'yyyy-MM-dd HH:mm:ss'))
            //dtTo.val(dt.toISOString());
            dtTo.val(formatDate(dt, 'yyyy-MM-dd HH:mm:ssK'))
        }
/*
        dtFrom.datetimepicker({
            format:'Y-m-d H:i:s',
            onShow:function(ct,input){
                this.setOptions({
                    maxDate:dtTo.val() ? dtTo.val() : false
                })
            },
            mask:'9999-19-39 29:59:59',
            lang:'<?php echo $lang_tag; ?>'
        });
        dtTo.datetimepicker({
            format:'Y-m-d H:i:s',
            onShow:function(ct){
                this.setOptions({
                    minDate:dtFrom.val() ? dtFrom.val() : false
                })
            },
            mask:'9999-19-39 29:59:59',
            lang:'<?php echo $lang_tag; ?>'
        });
*/
        // Refresh data with sensor filter
        $('#graph_refesh').click(function() {
            emptyInterfaceError();
            return getData(0);
        });

        $(".btn-graph-export").on("click", function(e) {
            e.preventDefault();
            var ft = $(this).data('filetype') || "png";
            exportPlot(g.p, ft);
        });

        // Get data
        if (errcnt==0) {
            getData(1, false);
        }
    });

    function getData(errmode, push) {
        var listsx = $("select.available-sensors-x"),
            listsy = $("select.available-sensors-y"),
            dtFrom = $("#datetime_from"),
            dtTo = $("#datetime_to"),
            selsx = listsx.val(),
            selsy = listsy.val(),
            vfrom = dtFrom.val(),
            vto = dtTo.val(),
            params = {'experiment': experiment};
        if (listsx.length==0 || listsy.length==0 || dtFrom.length==0 || dtTo.length==0) return false;
        // Validate sensors
        if (selsx==="" || selsy==="") {
            // no sensor selected from list
            if (errmode === 0) {
                alert("<?php echo addslashes(L::graph_PLEASE_SELECT_SENSORS); ?>");
            } else if (errmode === 1) {
                setInterfaceError($('#graph_msgs'), "<?php echo addslashes(L::graph_PLEASE_SELECT_SENSORS); ?>", 3000);
            }
            return false;
        } else if (selsx===selsy) {
            // equal sensors selected
            if (errmode === 0) {
                alert("<?php echo addslashes(L::graph_PLEASE_SELECT_DIFFERENT_SENSORS); ?>");
            } else if (errmode === 1) {
                setInterfaceError($('#graph_msgs'), "<?php echo addslashes(L::graph_PLEASE_SELECT_DIFFERENT_SENSORS); ?>", 3000);
            }
            return false;
        }
        
        params['sx'] = selsx;
        params['sy'] = selsy;
        // Validate dates
        if (String(vfrom).length > 0) {
            params['from'] = vfrom;
        }
        if (String(vto).length > 0) {
            params['to'] = vto;
        }

        if ((typeof push === "undefined" || push) && history.pushState) {
            var urlp = $.extend(true, {}, params);
            delete urlp.experiment;
            var newurl = window.location.protocol + "//" + window.location.host + window.location.pathname + '?q=experiment/scatter/' + experiment + '&' + $.param(urlp);
            window.history.pushState({path:newurl},window.document.title,newurl);
            //window.history.replaceState(“object or string”, window.document.title, newurl);
            //window.history.replaceState({path:newurl}, window.document.title, newurl);
        }

        var rq = coreAPICall('Detections.getScatterData', params, dataReceivedAll);

        return true;
    }

    function setDefaultAxis(){
        $.each(g.p.getAxes(), function(_, axis) {
            var opts = axis.options;
            if (axis.direction === 'y') {
                opts.min = null;
                opts.max = null;
            }
            if (axis.direction === 'x') {
                opts.max = null;
                opts.min = null;
            }
        });
    }

    function dataReceivedAll(data, status, jqxhr){
console.log('call dataReceivedAll');
        if (typeof data.error === 'undefined') {
            resetPlot();
            g.setData(data.result);
            var pcnt = g.getTotalPointsCount(data.result);
console.log('new count: '+pcnt);
            if (!pcnt) {
                setDefaultAxis();
            }
            g.refresh();
        } else {
            //$('#graph_scatter').empty();
            resetPlot();
            setInterfaceError($('#graph_msgs'), 'API error: ' + data.error, 3000);
        }
    }

    function resetPlot() {
        g.setData([]);
        setDefaultAxis();
        g.refresh();
    }

    function resetZoom() {
        // todo: reset to init min-max range
        return true;
    }
    function zoomPlot(args, dir) {
        return ((typeof dir !== "undefined" && dir === "out") ? g.zoomOut(args) : g.zoom(args));
    }

    function graphFormSubmit(f) {
        var dtFrom = $("#datetime_from"), inpFrom = $('input[name="from"]'),
            dtTo = $("#datetime_to"), inpTo = $('input[name="to"]'),
            dt;

        if (dtFrom.val() !== "") {
            //inpFrom.val(Math.floor(dt.getTime()/1000));
            //inpFrom.val(Math.floor(dtFrom.datetimepicker('getValue').getTime()/1000));
            inpFrom.val(dtFrom.datetimepicker('getValue').toISOString());
        }

        if (dtTo.val() !== "") {
            //inpTo.val(Math.floor(dt.getTime()/1000));
            //inpTo.val(Math.floor(dtTo.datetimepicker('getValue').getTime()/1000));
            inpTo.val(dtTo.datetimepicker('getValue').toISOString());
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
</div>

<div class="row">
	<div class="col-md-12">
		<h3><?php echo L::graph_TITLE_SCATTER_FOR_2(htmlspecialchars($this->view->content->experiment->title, ENT_QUOTES, 'UTF-8')); ?></h3>
	</div>
	<form method="get" id="graphForm" class="form-horizontal" action="?q=experiment/scatter/<?php echo (int)$this->view->content->experiment->id; ?>" onsubmit="return graphFormSubmit(this);">
	<div class="col-md-12">
		<h4 style="display:none;"><?php echo L::SENSORS . ':'; ?></h4>
		<div class="form-group">
			<label for="sensor_x" class="col-md-1 control-label"><?php echo L::graph_TITLE_AXIS_X . ':'; ?></label>
			<div class="col-md-8 col-sm-12">
				<select id="sensor_x" class="form-control available-sensors-x" name="sx">
					<option value="" <?php if ($this->view->content->sensor_x === null) echo 'selected'; ?>><?php echo L::sensor_SELECT_OPTION; ?></option>
					<?php foreach ($this->view->content->available_sensors as $k => $sensor) :
						$kx = ($this->view->content->sensor_x !== null) ? ('' . $this->view->content->sensor_x->sensor_id . '#' . (int)$this->view->content->sensor_x->sensor_val_id) : null;
					?>
					<option value="<?php echo htmlspecialchars($sensor->sensor_id . '#' . (int)$sensor->sensor_val_id, ENT_QUOTES, 'UTF-8'); ?>" <?php if (($kx!== null) && ($kx == $k)) echo 'selected'; ?>><?php
						echo htmlspecialchars(constant('L::sensor_VALUE_NAME_' . strtoupper($sensor->value_name)), ENT_QUOTES, 'UTF-8') . ','
							. htmlspecialchars(constant('L::sensor_VALUE_SI_NOTATION_' . strtoupper($sensor->value_name) . '_' . strtoupper($sensor->si_notation)), ENT_QUOTES, 'UTF-8')
							. ' ('  . htmlspecialchars($sensor->sensor_id. '#' . (int)$sensor->sensor_val_id, ENT_QUOTES, 'UTF-8') . ')';
					?></option>
					<?php endforeach; ?>
				</select>
			</div>
		</div>
		<div class="form-group">
			<label for="sensor_y" class="col-md-1 control-label"><?php echo L::graph_TITLE_AXIS_Y . ':'; ?></label>
			<div class="col-md-8 col-sm-12">
				<select id="sensor_y" class="form-control available-sensors-y" name="sy">
					<option value="" <?php if ($this->view->content->sensor_y === null) echo 'selected'; ?>><?php echo L::sensor_SELECT_OPTION; ?></option>
					<?php foreach ($this->view->content->available_sensors as $k => $sensor) :
						$kx = ($this->view->content->sensor_y !== null) ? ('' . $this->view->content->sensor_y->sensor_id . '#' . (int)$this->view->content->sensor_y->sensor_val_id) : null;
					?>
					<option value="<?php echo htmlspecialchars($sensor->sensor_id . '#' . (int)$sensor->sensor_val_id, ENT_QUOTES, 'UTF-8'); ?>" <?php if (($kx!== null) && ($kx == $k)) echo 'selected'; ?>><?php
						echo htmlspecialchars(constant('L::sensor_VALUE_NAME_' . strtoupper($sensor->value_name)), ENT_QUOTES, 'UTF-8') . ','
							. htmlspecialchars(constant('L::sensor_VALUE_SI_NOTATION_' . strtoupper($sensor->value_name) . '_' . strtoupper($sensor->si_notation)), ENT_QUOTES, 'UTF-8')
							. ' ('  . htmlspecialchars($sensor->sensor_id. '#' . (int)$sensor->sensor_val_id, ENT_QUOTES, 'UTF-8') . ')';
					?></option>
					<?php endforeach; ?>
				</select>
			</div>
		</div>
		<div class="form-group">
			<div class="col-md-1 col-sm-12">
				<label><?php echo L::graph_PERIOD . ':'; ?></label>
			</div>
			<div class="col-md-4 col-xs-12" style="padding-bottom: 5px;">
				<div class="input-group">
					<span class="input-group-addon" id="datetime_from_addon"><?php echo L::FROM_; ?></span>
					<input type="text" class="form-control" id="datetime_from" name="from" aria-describedby="datetime_from_addon" value=""/>
				</div>
			</div>
			<div class="col-md-4 col-xs-12" style="padding-bottom: 5px;">
				<div class="input-group">
					<span class="input-group-addon" id="datetime_to_addon"><?php echo L::TO_; ?></span>
					<input type="text" class="form-control" id="datetime_to" name="to" aria-describedby="datetime_to_addon" value=""/>
				</div>
			</div>
		</div>
	</div>
	<div class="col-md-12">
		<div id="graph_msgs">
			<?php if (empty($this->view->content->available_sensors)) : ?>
			<div class="alert alert-info">
				<?php echo L::graph_NO_SENSORS; ?>
			</div>
			<?php endif; ?>
			<?php foreach ($this->view->content->error as $errmsg) : ?>
			<div class="alert alert-danger">
				<?php echo $errmsg; ?>
			</div>
			<?php endforeach; ?>
		</div>
		<div class="plot-control-panel-top">
			<div class="btn-toolbar" role="toolbar" aria-label="...">
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
					<button type="button" class="btn btn-sm btn-default btn-autozoom-y" onclick="return g.autozoomY();"><span class="glyphicon glyphicon-resize-vertical"></span></button>
				</div><br/><br/>
				<div class="btn-group-vertical btn-group-sm control-zoom-y" role="group" aria-label="...">
					<button type="button" class="btn btn-sm btn-default" onclick="return zoomPlot({axis:'y'},  'in');"><span class="fa fa-plus"></span></button>
					<button type="button" class="btn btn-sm btn-default" onclick="return zoomPlot({axis:'y'}, 'out');"><span class="fa fa-minus"></span></button>
				</div><br/><br/>
				<div class="btn-group-vertical btn-group-sm control-pan-y" role="group" aria-label="...">
					<button type="button" class="btn btn-sm btn-default" onclick="g.pan({top:'-/2'});return true;"><span class="fa fa-long-arrow-up"></span></button>
					<button type="button" class="btn btn-sm btn-default" onclick="g.pan({top:'+/2'});return true;"><span class="fa fa-long-arrow-down"></span></button>
				</div>
			</div>
			<div id="graph_scatter" style="width: 870px; height: 400px; margin-left: 40px;">
			</div>
		</div>
		<div class="plot-control-panel-bottom" style="padding-left:40px;">
			<div class="btn-toolbar" role="toolbar" aria-label="...">
				<div class="btn-group btn-group-sm control-pan-x" role="group" aria-label="...">
					<button type="button" class="btn btn-sm btn-default" onclick="g.pan({left:'-/2'});return true;"><span class="fa fa-long-arrow-left"></span></button>
					<button type="button" class="btn btn-sm btn-default" onclick="g.pan({left:'+/2'});return true;"><span class="fa fa-long-arrow-right"></span></button>
				</div>
				<div class="btn-group btn-group-sm control-zoom-x" role="group" aria-label="...">
					<button type="button" class="btn btn-sm btn-default" onclick="return zoomPlot({axis:'x'},  'in');"><span class="fa fa-plus"></span></button>
					<button type="button" class="btn btn-sm btn-default" onclick="return zoomPlot({axis:'x'}, 'out');"><span class="fa fa-minus"></span></button>
				</div>
			</div>
		</div>
	</div>
	<div class="col-md-12">
		<div class="text-center" style="margin-top:15px;">
			<!-- <button type="submit" id="graph_refesh" class="btn btn-primary"><?php echo L::REFRESH; ?></button> -->
			<button type="button" id="graph_refesh" class="btn btn-primary btn-lg"><?php echo L::REFRESH; ?></button>
		</div>
	</div>
	</form>
</div>
