$(document).ready(function(){
	$('#exportDetections').click(function(){
		var form = $('#journalForm').get(0);
		//form.setAttribute('action', '?q=experiment/clean/'+form.exp_id.value);
		//form.submit();
		//var rq = coreAPICall('Detections.export', {exp_id: form.exp_id.value/*,dtfrom: null,dtto: null*/}, exportDetection);

		var mode = 'post';    // ajax'ed POST or simple POST method (simple POST use jQuery.fileDownload plugin and injected <form> submit)
		if (isMobile.Android()){
			mode = 'get';
		}
		var doc_type = 'csv';

		// Get filtered sensors list
		var sens = [];
		$.each(form.elements.namedItem('show-sensor[]'), function(i, field){
			if (field.checked){
				sens.push(field.value);
			}
		});

		// Ajax post data
		var ajax_url_export = '?q=api';
		var ajax_rqdata = {
				"method": 'Detections.download',
				"params": {
					"exp_id":  form.exp_id.value,
					"form-id": form.elements.namedItem('form-id').value,
					"show-sensor": form.elements.namedItem('form-id').value,
					"type":    doc_type
					//,"dtfrom": null, "dtto": null,
				}
		};
		if (sens.length){
			ajax_rqdata.params["show-sensor"] = sens;
		}

		// Post/get data
		var url_export = '?q=experiment/download/'+form.exp_id.value;
		var rqdata = {
			"exp_id":  form.exp_id.value,
			"form-id": form.elements.namedItem('form-id').value,
			"type":    doc_type
			//,"dtfrom": null, "dtto": null
		};
		if (sens.length){
			rqdata["show-sensor"] = sens;
		}

		switch (mode) {
			case 'get':
			case 'post':
				// POST Request: with jquery.fileDownload.js (& jQuery UI Dialog)
				// uses data "options" argument to create a POST request from a form to initiate a file download
				// Cookies must be enabled
				if(!(("cookie" in document && (document.cookie.length > 0 || (document.cookie = "test_cookie").indexOf.call(document.cookie, "test_cookie") > -1)))) {
					alert(SDLab.Language._('ERROR'));
					return false;
				}
				document.body.style.cursor = 'wait';
				$.fileDownload(url_export, {
					//preparingMessageHtml: "We are preparing your report, please wait...",
					//failMessageHtml: "There was a problem generating your report, please try again.",
					httpMethod: mode.toUpperCase(),
					data: rqdata,
					successCallback: function (url) {
						document.body.style.cursor = 'default';
						//alert('You just got a file download dialog or ribbon for this URL :' + url);
					},
					failCallback: function (html, url) {
						document.body.style.cursor = 'default';
						//alert('Your file download just failed for this URL:' + url + '\r\n' + 'Here was the resulting error HTML: \r\n' + html);
						alert(SDLab.Language._('ERROR_DETECTIONS_EXPORT_DOWNLOAD'));
					}
				});
				break;
			case 'ajax':
				// Use jquery ajax POST request
				// XXX: Warning not correctly works on old browsers and android stock android browser
				ajax_rqdata.base64 = 1;  // XXX: temporary use encode document to base64 on server and decode on client (ONLY for mode=ajax)
				document.body.style.cursor = 'wait';

				var rq = $.ajax(
				{
					url: ajax_url_export,
					data: ajax_rqdata,
					type: 'POST',
					processData: true,
					cache: false,
					success: function(response, status, xhr)
					{
						var filename = "detections-exp"+form.exp_id.value+"-"+(new Date()).UTC()+'.'+doc_type;
						var ct = xhr.getResponseHeader("Content-Type") || "text/html";

						// Get url for manual download document
						if (ct.indexOf('text/html') > -1) {
							var a = jQuery("a#detections_export_link");
							if(a.length==0){
								a = jQuery("<a>").attr("id","detections_export_link");
								jQuery("body").append(a);
							}
							// safari doesn't support this yet
							if (typeof a[0].download === 'undefined') {
								window.location = response;
							} else {
								a.attr({
									href: response,
									download: filename
								});
								a[0].click();
							}
						}
						else if (ct.indexOf('application/octet-stream') > -1) {
						//Get binary data of document
							var blob, ab = null;
							if (ajax_rqdata.base64) {
								ab = base64DecToArr(response);
							}

							blob = new Blob([ajax_rqdata.base64 ? ab : response], { type: ct, endings: 'transparent'});

							if (typeof window.navigator.msSaveBlob !== 'undefined') {
								// IE workaround for "HTML7007: One or more blob URLs were revoked by closing the blob for which they were created. These URLs will no longer resolve as the data backing the URL has been freed."
								window.navigator.msSaveBlob(blob, filename);
							} else {
								var URL = window.URL || window.webkitURL;
								var downloadUrl = URL.createObjectURL(blob);

								// use HTML5 a[download] attribute to specify filename
								var a = jQuery("a#detections_export_link");
								if(a.length==0){
									a = jQuery("<a>").attr("id","detections_export_link");
									jQuery("body").append(a);
								}
								// safari doesn't support this yet
								if (typeof a[0].download === 'undefined') {
									window.location = downloadUrl;
								} else {
									a.attr({
										href: downloadUrl,
										download: "detections-exp"+form.exp_id.value+"-"+(new Date()).UTC()+'.'+doc_type //filename
									});
									a[0].click();
								}
								setTimeout(function () { URL.revokeObjectURL(downloadUrl); }, 100); // cleanup
							}
						}

						// TODO: use submit ajax created post form for download file for crossbrowser use...

						document.body.style.cursor = 'default';
					},
					error: function()
					{
						document.body.style.cursor = 'default';
					}
				});
				break;
			default:
				alert("Incorrect file download mode");
				return false;
				break;
		}
		return false;
	});

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
