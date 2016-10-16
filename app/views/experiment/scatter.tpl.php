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
        uto=<?php echo (($this->view->content->to !== null) ? $this->view->content->to->format('U') : 'null'); ?>,
        hlFormatDate = "Y.m.d";

    $(document).ready(function() {
        var now = new Date();
        g = new ScatterPlot('#graph_scatter', [], {
            xaxis: {
                min: null,
                max: null,
            },
            yaxis: {
                min: null,
                max: null,
            },
            plottooltip: true,
        });

        var dtFrom = $("#datetime_from"),
            dtTo = $("#datetime_to"),
            dt;

        if (ufrom !== null) {
            dt = new Date(ufrom*1000);
            dtFrom.val(formatDate(dt, 'yyyy-MM-dd HH:mm:ss'));
        }
        if (uto !== null) {
            dt = new Date(uto*1000);
            dtTo.val(formatDate(dt, 'yyyy-MM-dd HH:mm:ss'))
        }

        dtFrom.datetimepicker({
            format:'<?php echo System::DATETIME_FORMAT1;?>',
            formatDate: hlFormatDate,
            onShow:function(ct,input){
                this.setOptions({
                    maxDate:dtTo.val() ? dtTo.val() : false
                })
            },
            lang:'<?php echo $lang_tag; ?>'  // obsolete
        });
        dtTo.datetimepicker({
            format:'<?php echo System::DATETIME_FORMAT1;?>',
            formatDate: hlFormatDate,
            onShow:function(ct){
                this.setOptions({
                    minDate:dtFrom.val() ? dtFrom.val() : false
                })
            },
            lang:'<?php echo $lang_tag; ?>'  // obsolete
        });
        $.datetimepicker.setLocale('<?php echo $lang_tag; ?>');  // because options 'lang' obsolete (not works)

        // Refresh data with sensor filter
        $('#graph_refesh').click(function() {
            emptyInterfaceError('#graph_msgs');
            return getData(0);
        });

        $('#btn_swap_xy').click(function() {
            var listsx = $("select.available-sensors-x"),
                listsy = $("select.available-sensors-y");
            if (listsx.length==0 || listsy.length==0) return false;
            // Swap sensors
            var tmp = listsx.val();
            listsx.val(listsy.val());
            listsy.val(tmp);
            $('#graph_refesh').trigger('click');
        });

        $('.btn-series-style input').change(function() {
            var styles = [false, false, false];
            $('.btn-series-style input').each(function(){
                var v = $(this).val();
                if (v == 0 || v == 1 || v == 2)
                    styles[v] = $(this).prop('checked');
            });
            setSeriesStyle(styles);
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
            dt, params = {'experiment': experiment};
        if (listsx.length==0 || listsy.length==0 || dtFrom.length==0 || dtTo.length==0) return false;
        // Validate sensors
        if (selsx==="" || selsy==="") {
            // no sensor selected from list
            if (errmode === 0) {
                alert(SDLab.Language._('graph_PLEASE_SELECT_SENSORS'));
            } else if (errmode === 1) {
                setInterfaceError($('#graph_msgs'), SDLab.Language._('graph_PLEASE_SELECT_SENSORS'), "warning", false, true, 3000);
            }
            return false;
        } else if (selsx===selsy) {
            // equal sensors selected
            if (errmode === 0) {
                alert(SDLab.Language._('graph_PLEASE_SELECT_DIFFERENT_SENSORS'));
            } else if (errmode === 1) {
                setInterfaceError($('#graph_msgs'), SDLab.Language._('graph_PLEASE_SELECT_DIFFERENT_SENSORS'), "warning", false, true, 3000);
            }
            return false;
        }

        params['sx'] = selsx;
        params['sy'] = selsy;
        // Validate dates
        if (String(vfrom).length > 0) {
            //params['from'] = vfrom;
            dt = dtFrom.datetimepicker("getValue");
            if (dt) {
                params['from'] = formatDate(dt, "yyyy-MM-dd\\THH:mm:ss\\Z", true);
            }
        }
        if (String(vto).length > 0) {
            //params['to'] = vto;
            dt = dtTo.datetimepicker("getValue");
            if (dt) {
                params['to'] = formatDate(dt, "yyyy-MM-dd\\THH:mm:ss\\Z", true);
            }
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
                opts.min = null;
                opts.max = null;
            }
        });
    }

    function setSeriesStyle(styles){
        // set data options
        $.each(g.p.getData(), function(_, d) {
            d.points.show  = (styles[0] === true) ? true : ((styles[0] === false) ? false : d.points.show);
            d.bubbles.show = (styles[1] === true) ? true : ((styles[1] === false) ? false : d.bubbles.show);
            d.heatmap.show = (styles[2] === true) ? true : ((styles[2] === false) ? false : d.heatmap.show);
        });
        // set default options
        g.p.getOptions().series.points.show  = (styles[0] === true) ? true : ((styles[0] === false) ? false : g.p.getOptions().series.points.show);
        g.p.getOptions().series.bubbles.show = (styles[1] === true) ? true : ((styles[1] === false) ? false : g.p.getOptions().series.bubbles.show);
        g.p.getOptions().series.heatmap.show = (styles[2] === true) ? true : ((styles[2] === false) ? false : g.p.getOptions().series.heatmap.show);
        g.refresh();
    }

    function dataReceivedAll(data, status, jqxhr){
        if (typeof data.error === 'undefined') {
            resetPlot();
            g.setData(data.result);
            if (!g.getTotalPointsCount(data.result)) {
                setDefaultAxis();
            }
            g.refresh();
            datetimepickerRefresh();
        } else {
            resetPlot();
            datetimepickerRefresh();
            setInterfaceError($('#graph_msgs'), /*'API error: ' +*/ data.error, "danger", false, true, 3000);
        }
    }

    function resetPlot() {
        g.setData([]);
        setDefaultAxis();
        g.refresh();
    }
    function resetZoom() {
        setDefaultAxis();
        g.refresh();
    }
    function zoomPlot(args, dir) {
        return ((typeof dir !== "undefined" && dir === "out") ? g.zoomOut(args) : g.zoom(args));
    }

    function datetimepickerRefresh() {
        var sformatDate = 'Y-m-d',  // part of server format date Y-m-dTH:i:s.uZ
            highlightedPeriods = [];
        $.each(g.p.getData(), function(_, d) {
            // Highlight full available time range
            if (typeof d.maxdatetime !== "undefined" && typeof d.mindatetime !== "undefined") {
                // must by set formatDate and equal
                var dfmt = new DateFormatter(),
                    dfrom = dfmt.parseDate(d.mindatetime,sformatDate),
                    dto = dfmt.parseDate(d.maxdatetime,sformatDate);
                if (dfrom !== false && dto !== false) {
                    var dfromout = dfmt.formatDate(dfrom, hlFormatDate),  // formatDate from datetimepicker options
                        dtoout = dfmt.formatDate(dto, hlFormatDate);
                    if (dfromout !== false && dtoout !== false) {
                        highlightedPeriods.push("" + dfromout + ","+ dtoout + "," + SDLab.Language._('graph_AVAILABLE_RANGE') + ",xdsoft_highlighted_mint");
                    }
                }
            }
        });
        //if (highlightedPeriods.length) {
            // reset highlight and set new
            $('#datetime_from').datetimepicker('data').options.highlightedDates = [];
            $('#datetime_from').datetimepicker('data').options.highlightedPeriods = [];
            $('#datetime_from').datetimepicker('setOptions',{"highlightedPeriods":highlightedPeriods});
            $('#datetime_to').datetimepicker('data').options.highlightedDates = [];
            $('#datetime_to').datetimepicker('data').options.highlightedPeriods = [];
            $("#datetime_to").datetimepicker('setOptions',{"highlightedPeriods":highlightedPeriods});
        //}
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
	<form method="get" id="graphForm" class="form-horizontal" action="?q=experiment/scatter/<?php echo (int)$this->view->content->experiment->id; ?>" onsubmit="return false;">
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
			<div class="col-md-1 col-sm-12 control-label">
				<label><?php echo L::graph_RANGE . ':'; ?></label>
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
			<div class="alert alert-info alert-dismissible" role="alert">
				<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
				<?php echo L::graph_NO_SENSORS; ?>
			</div>
			<?php endif; ?>
			<?php foreach ($this->view->content->error as $errmsg) : ?>
			<div class="alert alert-danger alert-dismissible" role="alert">
				<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
				<?php echo $errmsg; ?>
			</div>
			<?php endforeach; ?>
		</div>
		<div class="plot-control-panel-top">
			<div class="btn-toolbar" role="toolbar" aria-label="...">
				<div class="btn-group btn-group-sm" role="group" aria-label="...">
					<button type="button" id="btn_reset_zoom" class="btn btn-sm btn-default" onclick="resetZoom();return true;"><span class="fa fa-lg fa-home"></span></button>
					<button type="button" id="btn_swap_xy" class="btn btn-sm btn-default"><span class="fa fa-lg fa-exchange fa-rotate-90"></span></button>
				</div>
				<div class="btn-group btn-group-sm" data-toggle="buttons" role="group" aria-label="...">
					<label class="btn btn-default btn-series-style active">
						<input type="checkbox" autocomplete="off" id="series_style0" name="series_style_points" value="0" checked><span class="fa fa-plot-points-points"></span><span class="hidden-xs">&nbsp;<?php echo L::graph_SERIES_STYLE_POINTS; ?></span>
					</label>
				</div>
				<div class="btn-group btn-group-sm" data-toggle="buttons" role="group" aria-label="...">
					<label class="btn btn-default btn-series-style active">
						<input type="radio" autocomplete="off" id="series_style_none" name="series_style" value="-1" checked><span class="fa fa-plot-points-none"></span><span class="hidden-xs">&nbsp;<?php echo L::graph_SERIES_STYLE_NONE; ?></span>
					</label>
					<label class="btn btn-default btn-series-style">
						<input type="radio" autocomplete="off" id="series_style1" name="series_style" value="1"><span class="fa fa-plot-points-bubbles"></span><span class="hidden-xs">&nbsp;<?php echo L::graph_SERIES_STYLE_BUBBLES; ?></span>
					</label>
					<label class="btn btn-default btn-series-style">
						<input type="radio" autocomplete="off" id="series_style2" name="series_style" value="2"><span class="fa fa-plot-points-heatmap"></span><span class="hidden-xs">&nbsp;<?php echo L::graph_SERIES_STYLE_HEATMAP; ?></span>
					</label>
				</div>
				<div class="btn-group btn-group-sm" role="group" aria-label="...">
					<button type="button" id="graph_refesh" class="btn btn-primary"><span class="fa fa-refresh"></span><span class="">&nbsp;<?php echo L::REFRESH; ?></span></button>
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
	</form>
</div>
