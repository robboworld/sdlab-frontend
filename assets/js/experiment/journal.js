$(document).ready(function(){
	$('#cleanDetections').click(function(){
		if(confirm(SDLab.Language._('journal_QUESTION_CLEAN_JOURNAL'))){
			var form = $('#journalForm').get(0);
			form.setAttribute('action', '?q=experiment/clean/'+form.exp_id.value);
			form.submit();
		}
	});
	$('#collapseSensors')
		.on('show.bs.collapse', function(){
			$('#collapseSensorsControl .glyphicon').removeClass('glyphicon-chevron-up glyphicon-chevron-down').addClass('glyphicon-chevron-down');
		})
		.on('hide.bs.collapse', function(){
			$('#collapseSensorsControl .glyphicon').removeClass('glyphicon-chevron-up glyphicon-chevron-down').addClass('glyphicon-chevron-up');
		});
		

	$('.btn-remove-detection').click(function(){
		var form = $('#journalForm').get(0);
		var row = $(this).parent().parent().parent();
		var rq = coreAPICall('Detections.deletebytime', {exp_id: form.exp_id.value, dt: [row.data('detection-time')]}, deleteDetection);
		rq.detections_row = row;
	});

	$('.detection-num').click(function(){
		$(this).parent().find('.btn-remove-detection').toggle();
	});
});

function deleteDetection(data, st, xhr) {
	if(typeof data.error !== 'undefined'){
		alert(SDLab.Language._('ERROR'));
	} else if ((typeof data.result !== 'undefined') && (data.result == true)) {
		 $(xhr.detections_row).remove();
	} else {
		alert(SDLab.Language._('ERROR'));
	}
}
