(() => {

    const deleteRecordsAlert = new bootstrap.Toast(document.getElementById('deleteRecordsAlert'));

    function onCheckDeleteRecords() {
        const viewAndEditInput = $('input#dataViewingViewAndEdit');
        const viewAndEditSurveysInput = $('input#dataViewingViewAndEditSurveys');
        const viewAndEditDeleteInput = $('input#dataViewingViewAndEditDelete');
        const viewAndEditSurveysDeleteInput = $('input#dataViewingViewAndEditSurveysDelete');

        if (viewAndEditInput.is(':checked')) {
            viewAndEditDeleteInput.prop('checked', true);
        } else if (viewAndEditSurveysInput.is(':checked')) {
            viewAndEditSurveysDeleteInput.prop('checked', true);
        }

        viewAndEditInput.prop('disabled', true);
        viewAndEditSurveysInput.prop('disabled', true);
        viewAndEditInput.parent().tooltip('enable');
        viewAndEditSurveysInput.parent().tooltip('enable');
        deleteRecordsAlert.hide();
    }

    function onUncheckDeleteRecords(showModal) {
        const viewAndEditInput = $('input#dataViewingViewAndEdit');
        const viewAndEditSurveysInput = $('input#dataViewingViewAndEditSurveys');
        viewAndEditInput.prop('disabled', false);
        viewAndEditSurveysInput.prop('disabled', false);
        viewAndEditInput.parent().tooltip('disable');
        viewAndEditSurveysInput.parent().tooltip('disable');
        if (showModal) {
            deleteRecordsAlert.show();
        }
    }

    const deleteRecordsInput = $('input[name="record_delete"]');

    deleteRecordsInput.change(function () {
        if ($(this).is(':checked')) {
            onCheckDeleteRecords();
        } else {
            onUncheckDeleteRecords(true);
        }
    });

    // Initialize the state on load
    if (deleteRecordsInput.is(':checked')) {
        onCheckDeleteRecords();
    } else {
        onUncheckDeleteRecords(false);
    }
})();
