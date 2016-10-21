$(document).ready(function(){
    var delbtns=$('.experiment-delete-btn');
    if (delbtns.length) {
        delbtns.click(function(){
            var rq = coreAPICall('Experiment.isActive', {experiment: $(this).data('experiment')}, function(data, st, xhr){
                if (typeof data.error === 'undefined'){
                    if (data.result){
                        var msg = SDLab.Language._('journal_QUESTION_REMOVE_EXPERIMENT_WITH_1');
                        if (confirm(msg) && typeof xhr.activator !== 'undefined'){
                            experimentDelete($(xhr.activator).data('experiment'), 1);
                        }
                    } else {
                        experimentDelete($(xhr.activator).data('experiment'), 0);
                    }
                } else {
                    alert(SDLab.Language._('ERROR'));
                }
            });
            rq.activator = this;
        });
    }
});
function experimentDelete(experiment, force) {
    var form = $('#sdform');
    if(form.length == 0) return;

    form.attr("action","/?q=experiment/delete/" + experiment);
    form.get(0)['force'].value = force;
    form.submit();
}
