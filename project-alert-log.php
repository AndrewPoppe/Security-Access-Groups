<?php
namespace YaleREDCap\SystemUserRights;

/** @var SystemUserRights $module */

if ( !$module->framework->getUser()->isSuperUser() ) {
    http_response_code(401);
    exit;
}

?>
<link
    href="https://cdn.datatables.net/v/dt/dt-1.13.4/b-2.3.6/b-html5-2.3.6/fc-4.2.2/fh-3.3.2/r-2.4.1/rr-1.3.3/sr-1.2.2/datatables.min.css"
    rel="stylesheet" />
<script
    src="https://cdn.datatables.net/v/dt/dt-1.13.4/b-2.3.6/b-html5-2.3.6/fc-4.2.2/fh-3.3.2/r-2.4.1/rr-1.3.3/sr-1.2.2/datatables.min.js">
</script>

<script defer src="<?= $module->getUrl('assets/fontawesome/js/regular.min.js') ?>"></script>
<script defer src="<?= $module->getUrl('assets/fontawesome/js/sharp-regular.min.js') ?>"></script>
<script defer src="<?= $module->getUrl('assets/fontawesome/js/sharp-solid.min.js') ?>"></script>
<script defer src="<?= $module->getUrl('assets/fontawesome/js/solid.min.js') ?>"></script>
<script defer src="<?= $module->getUrl('assets/fontawesome/js/custom-icons.min.js') ?>"></script>
<script defer src="<?= $module->getUrl('assets/fontawesome/js/fontawesome.min.js') ?>"></script>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/clipboard@2.0.10/dist/clipboard.min.js"></script>
<link rel='stylesheet' type='text/css' href='<?= $module->getUrl('SystemUserRights.css') ?>' />


<div class="SUR-Container">
    <div class="projhdr">
        <i class='fa-solid fa-user-secret'></i>&nbsp;<span>Security Access Groups</span>
    </div>
    <div class="clearfix">
        <div id="sub-nav" class="d-none d-sm-block mr-4 mb-0 ml-0">
            <ul>
                <li>
                    <a href="<?= $module->getUrl('project-status.php') ?>"
                        style="font-size:13px;color:#393733;padding:7px 9px;">
                        <i class="fa-regular fa-clipboard-check"></i>
                        Project Status
                    </a>
                </li>
                <li class="active">
                    <a href="<?= $module->getUrl('project-alert-log.php') ?>"
                        style="font-size:13px;color:#393733;padding:7px 9px;">
                        <i class="fa-regular fa-envelopes-bulk"></i>
                        Alert Log
                    </a>
                </li>
            </ul>
        </div>
    </div>
    <div class="alertLogWrapper my-4 w-75">
        <table id="alertLogTable" class="border" style="">
            <thead>
                <tr>
                    <th>id</th>
                    <th>Send Time</th>
                    <th>Alert Type</th>
                    <th>View Alert</th>
                    <th>User(s)</th>
                    <th>Recipient(s)</th>
                    <th>Status</th>
                </tr>
            </thead>
        </table>
    </div>
</div>
<div class="modal fade" id="alertPreviewModal" tabindex="-1" aria-labelledby="alertPreviewModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="alertPreviewModalLabel">Alert Preview</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                ...
            </div>
        </div>
    </div>
</div>

<script>
function openAlertPreview(alert_id) {
    // console.log('openAlertPreview:', alert_id);
    // Swal.fire({
    //     title: 'Loading...',
    //     html: 'Please wait...',
    //     allowOutsideClick: false,
    //     didOpen: () => {
    //         Swal.showLoading();

    $.post('<?= $module->framework->getUrl('ajax/alert-preview.php') ?>', {
            alert_id: alert_id
        })
        .done(function(json) {
            const data = JSON.parse(json);
            console.log(data);
            createAlertPreviewModal(data);
            Swal.close();
        })
        .fail(function(data) {
            Swal.close();
            Swal.fire({
                title: 'There was an error loading the alert preview.',
                html: data.responseText,
                icon: 'error'
            })
        });
    //     }
    // });
}

function createAlertPreviewModal(data) {
    console.log(data);
    $('#alertPreviewModal .modal-body').html(data.table);
    $('#alertPreviewModal').modal('show');
}

$('#alertLogTable').DataTable({
    ajax: "<?= $module->getUrl('ajax/alerts.php') ?>",
    deferRender: true,
    columns: [{
            data: 'id',
        },
        {
            data: 'sendTime',
        },
        {
            data: 'alertType',
        },
        {
            data: function(row, type, set, meta) {
                return `<button class='btn btn-xs btn-info' onclick='openAlertPreview("${row.id}");'><i class="fa-solid fa-envelope"></i> View</button>`;
            }
        },
        {
            data: 'users',
        },
        {
            data: 'recipients',
        },
        {
            data: 'status',
            visible: false
        }
    ],
    responsive: true
});
</script>