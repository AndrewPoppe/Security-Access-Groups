<?php
namespace YaleREDCap\SecurityAccessGroups;

/** @var SecurityAccessGroups $module */

if ( !$module->framework->getUser()->isSuperUser() ) {
    http_response_code(401);
    exit;
}

// $Alerts = new Alerts($module);
// $alerts = $Alerts->getAlerts();
// foreach ( $alerts as $alert ) {
//     $Alerts->deleteAlert($alert['id']);
// }

?>
<link
    href="https://cdn.datatables.net/v/dt/dt-1.13.4/b-2.3.6/b-html5-2.3.6/fc-4.2.2/fh-3.3.2/r-2.4.1/rr-1.3.3/sr-1.2.2/datatables.min.css"
    rel="stylesheet" />
<script
    src="https://cdn.datatables.net/v/dt/dt-1.13.4/b-2.3.6/b-html5-2.3.6/fc-4.2.2/fh-3.3.2/r-2.4.1/rr-1.3.3/sr-1.2.2/datatables.min.js">
</script>

<script defer src="<?= $module->framework->getUrl('assets/fontawesome/js/regular.min.js') ?>"></script>
<script defer src="<?= $module->framework->getUrl('assets/fontawesome/js/sharp-regular.min.js') ?>"></script>
<script defer src="<?= $module->framework->getUrl('assets/fontawesome/js/sharp-solid.min.js') ?>"></script>
<script defer src="<?= $module->framework->getUrl('assets/fontawesome/js/solid.min.js') ?>"></script>
<script defer src="<?= $module->framework->getUrl('assets/fontawesome/js/duotone.min.js') ?>"></script>
<script defer src="<?= $module->framework->getUrl('assets/fontawesome/js/custom-icons.min.js') ?>"></script>
<script defer src="<?= $module->framework->getUrl('assets/fontawesome/js/fontawesome.min.js') ?>"></script>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/clipboard@2.0.10/dist/clipboard.min.js"></script>
<link rel='stylesheet' type='text/css' href='<?= $module->framework->getUrl('SecurityAccessGroups.css') ?>' />


<div class="SUR-Container">
    <div class="projhdr">
        <i class='fa-solid fa-users-between-lines'></i>&nbsp;<span>Security Access Groups</span>
    </div>
    <div class="clearfix">
        <div id="sub-nav" class="d-none d-sm-block mr-4 mb-0 ml-0">
            <ul>
                <li>
                    <a href="<?= $module->framework->getUrl('project-status.php') ?>"
                        style="font-size:13px;color:#393733;padding:7px 9px;">
                        <i class="fa-regular fa-clipboard-check"></i>
                        Project Status
                    </a>
                </li>
                <li class="active">
                    <a href="<?= $module->framework->getUrl('project-alert-log.php') ?>"
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
                <tr style="background-color: #D7D7D7 !important;">
                    <th colspan="8" style="border-bottom: none;">Test</th>
                </tr>
                <tr>
                    <th>Alert ID</th>
                    <th>Send Time</th>
                    <th>Alert Type</th>
                    <th>Reminder</th>
                    <th>View Alert</th>
                    <th>User(s)</th>
                    <th>Recipient</th>
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
var Toast = Swal.mixin({
    toast: true,
    position: 'middle',
    iconColor: 'white',
    customClass: {
        popup: 'colored-toast'
    },
    showConfirmButton: false,
    timer: 1500,
    timerProgressBar: true
});

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
    $('#alertPreviewModal .modal-body').html(data.table);
    let title = 'Alert Preview - ';
    if (data.alertType === "users") {
        $('#alertPreviewModal .modal-header')[0].classList = 'modal-header bg-primary text-light';
        title += 'User Alert';
    } else if (data.alertType === "userRightsHolders") {
        $('#alertPreviewModal .modal-header')[0].classList = 'modal-header bg-warning text-body';
        title += 'User Rights Holder Alert';
    } else if (data.alertType === "expiration") {
        $('#alertPreviewModal .modal-header')[0].classList = 'modal-header bg-danger text-light';
        title += 'User Expiration Alert';
    }
    if (data.reminder) {
        title += " (Reminder)";
        $('#alertPreviewModal .modal-body')[0].classList = 'modal-body bg-reminder';
    } else {
        $('#alertPreviewModal .modal-body')[0].classList = 'modal-body';
    }
    $('#alertPreviewModalLabel').text(title);
    $('#alertPreviewModal').modal('show');
}

function deleteAlert(alert_id) {
    Swal.fire({
            title: 'Are you sure?',
            text: "You are about to delete this alert. This action cannot be undone.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Delete Alert',
            customClass: {
                confirmButton: 'btn btn-danger m-1',
                cancelButton: 'btn btn-secondary m-1'
            },
            buttonsStyling: false
        })
        .then((result) => {
            if (result.isConfirmed) {
                $.post('<?= $module->framework->getUrl('ajax/delete-alert.php') ?>', {
                        alert_id: alert_id
                    })
                    .done(function(json) {
                        const data = JSON.parse(json);
                        console.log(data);
                        if (data) {
                            Toast.fire({
                                icon: 'success',
                                title: 'Alert deleted successfully.'
                            });
                            $('#alertLogTable').DataTable().ajax.reload();
                        } else {
                            Swal.fire({
                                title: 'There was an error deleting the alert.',
                                html: data.message,
                                icon: 'error'
                            })
                        }
                    })
                    .fail(function(data) {
                        Swal.fire({
                            title: 'There was an error deleting the alert.',
                            html: data.responseText,
                            icon: 'error'
                        })
                    });
            }
        });
}

$('#alertLogTable').DataTable({
    ajax: "<?= $module->framework->getUrl('ajax/alerts.php') ?>",
    deferRender: true,
    columns: [{
            data: 'id',
            visible: true
        },
        {
            data: function(row, type, set, meta) {
                if (type === 'display') {

                    console.log(row);

                    let color = "";
                    let icon = "";
                    let sent = false;
                    let deleteButton = "";

                    if (row.reminder && moment.now() > (row.sendTime * 1000)) {
                        const status = row.status ?? "error";
                        color = status === "error" ? "text-danger" : "text-success";
                        icon = status === "error" ?
                            `<span class="fa-stack fa-sm" style="width: 1.15em; height: 1.15em; vertical-align: top;">
                            <i class='fa-solid fa-circle-exclamation fa-stack-1x' title="Error sending reminder"></i>
                        </span>` :
                            `<span class="fa-stack fa-sm" style="width: 1.15em; height: 1.15em; vertical-align: top;">
                            <i class='fa-sharp fa-solid fa-check-circle fa-stack-1x' title="Reminder sent"></i>
                        </span>`;
                    } else {
                        const sent = moment.now() > (row.sendTime * 1000);
                        color = sent ? "text-success" : "text-secondary";
                        icon = sent ?
                            `<span class="fa-stack fa-sm" style="width: 1.15em; height: 1.15em; vertical-align: top;">
                            <i class='fa-sharp fa-solid fa-check-circle fa-stack-1x' title="Alert sent"></i>
                        </span>` :
                            `<span class="fa-stack fa-sm" style="width: 1.15em; height: 1.15em; vertical-align: top; opacity: 0.5;" title="Reminder scheduled">
                            <i class="fa-duotone fa-clock-three fa-stack-1x text-dark" style="--fa-primary-color: #000000; --fa-secondary-color: #000000; --fa-secondary-opacity: 0.1"></i>
                            <i class="fa-regular fa-circle fa-stack-1x text-dark"></i>
                        </span>`;
                        deleteButton = sent ? "" :
                            `<a class='deleteAlertButton' href='javascript:;' onclick='deleteAlert(${row.id});'><i class='fa-solid fa-xmark text-danger' title="Delete alert"></i></a>`;
                    }
                    const formattedDate = moment(row.sendTime * 1000).format('MM/DD/YYYY hh:mm A');
                    return `<span class="${color}">${icon} ${formattedDate} ${deleteButton}</span>`;
                } else {
                    return row.sendTime;
                }
            },
        },
        {
            data: function(row, type, set, meta) {
                if (type === 'display') {
                    let result = '';
                    if (row.alertType === 'users') {
                        result =
                            '<span class="badge-primary text-light badge-pill py-1 px-2">User</span>';
                    } else if (row.alertType === 'userRightsHolders') {
                        result =
                            '<span class="badge-warning text-body badge-pill py-1 px-2">User Rights Holder</span>';
                    } else if (row.alertType === 'expiration') {
                        result =
                            '<span class="badge-danger text-light badge-pill py-1 px-2">Expiration</span>';
                    }

                    return result;
                } else {
                    return row.alertType;
                }
            },
        },
        {
            data: function(row, type, set, meta) {
                if (type === 'display') {
                    return row.reminder ?
                        '<span class="badge badge-pill bg-reminder border font-weight-normal">Reminder</span>' :
                        '';
                } else if (type === 'filter') {
                    return row.reminder ? 'Reminder' : '';
                }
                return row.reminder;
            }
        },
        {
            data: function(row, type, set, meta) {
                return `<button class='btn btn-xs btn-info' onclick='openAlertPreview("${row.id}");'><i class="fa-solid fa-envelope"></i> View</button>`;
            }
        },
        {
            data: function(row, type, set, meta) {
                const users = row['users'];
                if (type === 'display') {
                    let result = [];
                    for (let user of users.split('<br>')) {
                        result.push(
                            `<a rel="noreferrer noopener" target="_blank" href="${app_path_webroot_full + app_path_webroot + 'ControlCenter/view_users.php?username=' + user}">${user}</a>`
                        );
                    }
                    return result.join('<br>');
                } else {
                    return users;
                }
            },
        },
        {
            data: 'recipients',
        },
        {
            data: 'status',
            visible: false
        }
    ],
    responsive: true,
    order: [
        [1, 'desc']
    ],
});
</script>