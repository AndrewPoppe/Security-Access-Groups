<?php
namespace YaleREDCap\SecurityAccessGroups;

/** @var SecurityAccessGroups $module */

if ( !$module->framework->getUser()->isSuperUser() ) {
    http_response_code(401);
    exit;
}

?>
<link href="https://cdn.datatables.net/v/dt/dt-1.13.4/datatables.min.css" rel="stylesheet" />
<script src="https://cdn.datatables.net/v/dt/dt-1.13.4/datatables.min.js"></script>

<link rel="preload" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css" as="style"
    onload="this.onload=null;this.rel='stylesheet'">
<noscript>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
</noscript>
<script defer src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

<script defer src="<?= $module->framework->getUrl('assets/fontawesome/js/regular.min.js') ?>"></script>
<script defer src="<?= $module->framework->getUrl('assets/fontawesome/js/sharp-regular.min.js') ?>"></script>
<script defer src="<?= $module->framework->getUrl('assets/fontawesome/js/sharp-solid.min.js') ?>"></script>
<script defer src="<?= $module->framework->getUrl('assets/fontawesome/js/solid.min.js') ?>"></script>
<script defer src="<?= $module->framework->getUrl('assets/fontawesome/js/duotone.min.js') ?>"></script>
<script defer src="<?= $module->framework->getUrl('assets/fontawesome/js/custom-icons.min.js') ?>"></script>
<script defer src="<?= $module->framework->getUrl('assets/fontawesome/js/fontawesome.min.js') ?>"></script>

<script defer src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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
    <div class="alertLogWrapper my-4 mr-3 card card-body bg-light" style="width: 1100px; display: none;">
        <table aria-label="alert log table" id="alertLogTable" class="border" style="width:100%;">
            <thead>
                <tr style="background-color: #D7D7D7 !important;">
                    <th class="font-weight-normal" scope="col" colspan="8" style="border-bottom: none;">
                        <div class="container px-0">
                            <div class="row">
                                <div class="col-4">
                                    <div class="row px-3 pt-2">
                                        <div class="col pl-0 pr-1"><input id="mindatetime"
                                                class="timePicker form-control form-control-sm input" type="text"
                                                placeholder="Begin time">
                                        </div>
                                        <div class="col p-0">
                                            <input id="maxdatetime"
                                                class="timePicker form-control form-control-sm input" type="text"
                                                placeholder="End time">
                                        </div>
                                    </div>
                                    <div class="row pl-4 py-2">
                                        <div class="col">
                                            <button class="btn btn-xs btn-success" onclick="showPastAlerts()">View past
                                                alerts</button>
                                        </div>
                                        <div class="col">
                                            <button class="btn btn-xs btn-primaryrc" onclick="showFutureAlerts()">View
                                                future alerts</button>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-4 px-4" style="border-left: 1px solid #ccc">
                                    <div class="row pt-2 pb-1">
                                        <select class="" id="alertTypeSelect" multiple="multiple">
                                            <option value="users">User</option>
                                            <option value="userRightsHolders">User Rights Holder</option>
                                            <option value="expiration">Expiration</option>
                                        </select>
                                    </div>
                                    <div class="row ">
                                        <select class="form-control" id="notificationTypeSelect" multiple="multiple">
                                            <option value="false">Original alert</option>
                                            <option value="true">Reminder</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-4 px-4" style="border-left: 1px solid #ccc">
                                    <div class="row pt-2 pb-1">
                                        <select class="form-control" id="usersSelect" multiple="multiple">
                                        </select>
                                    </div>
                                    <div class="row">
                                        <select class="form-control" id="recipientSelect" multiple="multiple">
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </th>
                </tr>
                <tr>
                    <th scope="col">Alert ID</th>
                    <th scope="col">Send Time</th>
                    <th scope="col">Alert Type</th>
                    <th scope="col">Reminder</th>
                    <th scope="col">View Alert</th>
                    <th scope="col">User(s)</th>
                    <th scope="col">Recipient</th>
                    <th scope="col">Status</th>
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
console.log(performance.now());
console.time('dt');
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

function showPastAlerts() {
    document.querySelector('#mindatetime')._flatpickr.clear();
    document.querySelector('#maxdatetime')._flatpickr.setDate(new Date(), true);
}

function showFutureAlerts() {
    document.querySelector('#maxdatetime')._flatpickr.clear();
    document.querySelector('#mindatetime')._flatpickr.setDate(new Date(), true);
}

// Custom user function
function searchUsers() {
    const users = $('#usersSelect').val() || [];
    const dt = $('#alertLogTable').DataTable();
    dt.columns(5).search(users.join('|'), true).draw();
}

$(document).ready(function() {
    console.timeLog('dt', 'document ready');

    $('#sub-nav').removeClass('d-none');


    $(document).on('preXhr.dt', function(e, settings, json) {
        console.timeLog('dt', 'ajax start')
    });
    $(document).on('xhr.dt', function(e, settings, json) {
        console.timeLog('dt', 'ajax end')
    });

    // Custom range filtering function
    $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {

        // Dates
        const minDateTimeEl = document.querySelector('#mindatetime')._flatpickr;
        const maxDateTimeEl = document.querySelector('#maxdatetime')._flatpickr;

        if (typeof minDateTimeEl === 'undefined' || typeof maxDateTimeEl === 'undefined') {
            return true;
        }

        const minDateTime = minDateTimeEl.selectedDates[0]?.getTime() / 1000;
        const maxDateTime = maxDateTimeEl.selectedDates[0]?.getTime() / 1000;
        const sendDateTime = parseFloat(data[1]) || 0; // use data for the send time column

        if (
            (isNaN(minDateTime) && isNaN(maxDateTime)) ||
            (isNaN(minDateTime) && sendDateTime <= maxDateTime) ||
            (minDateTime <= sendDateTime && isNaN(maxDateTime)) ||
            (minDateTime <= sendDateTime && sendDateTime <= maxDateTime)
        ) {
            return true;
        }

        return false;
    });

    // Custom alert type function
    $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
        const alertTypes = $('#alertTypeSelect').val() || [];
        if (alertTypes.length === 0) {
            return true;
        }
        const thisAlertType = data[2];
        return alertTypes.includes(thisAlertType);
    });

    // Custom notification type function
    $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
        const notificationTypes = $('#notificationTypeSelect').val() || [];
        if (notificationTypes.length === 0) {
            return true;
        }
        const thisNotificationType = String(data[3] === "Reminder");
        return notificationTypes.includes(thisNotificationType);
    });

    // Custom recipient function
    $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
        const recipients = $('#recipientSelect').val() || [];
        if (recipients.length === 0) {
            return true;
        }
        const thisRecipient = String(data[6]);
        return recipients.map(recipient => $(`<span>${recipient}</span>`).text()).includes(
            thisRecipient);
    });

    $('#alertLogTable').DataTable({
        ajax: {
            url: "<?= $module->framework->getUrl('ajax/alerts.php') ?>",
            type: 'POST'
        },
        deferRender: true,
        columns: [{
                data: 'id',
                visible: true
            },
            {
                data: function(row, type, set, meta) {
                    if (type === 'display') {
                        let color = "";
                        let icon = "";
                        let sent = false;
                        let deleteButton = "";

                        if (row.reminder && moment.now() > (row.sendTime * 1000)) {
                            const status = row.status ?? "error";
                            color = status === "error" ? "text-danger" : "text-success";
                            icon = status === "error" ?
                                `<span class="fa-stack fa-sm default-cursor" ` +
                                `style="width: 1.15em; height: 1.15em; vertical-align: top;">` +
                                `<i class='fa-solid fa-circle-exclamation fa-stack-1x' ` +
                                `title="Error sending reminder"></i></span>` :
                                `<span class="fa-stack fa-sm default-cursor" ` +
                                `style="width: 1.15em; height: 1.15em; vertical-align: top;">` +
                                `<i class='fa-sharp fa-solid fa-check-circle fa-stack-1x' ` +
                                `title="Reminder sent"></i></span>`;
                        } else {
                            const sent = moment.now() > (row.sendTime * 1000);
                            color = sent ? "text-success" : "text-secondary";
                            const style = '--fa-primary-color: #000000;' +
                                '--fa-secondary-color: #000000;' +
                                '--fa-secondary-opacity: 0.1';
                            icon = sent ?
                                `<span class="fa-stack fa-sm default-cursor" ` +
                                `style="width: 1.15em; height: 1.15em; vertical-align: top;">` +
                                `<i class='fa-sharp fa-solid fa-check-circle fa-stack-1x' title="Alert sent"></i>` +
                                `</span>` :
                                `<span class="fa-stack fa-sm default-cursor" ` +
                                `style="width: 1.15em; height: 1.15em; vertical-align: top; opacity: 0.5;" ` +
                                `title="Reminder scheduled">` +
                                `<i class="fa-duotone fa-clock-three fa-stack-1x text-dark" ` +
                                `style="${style}"></i>` +
                                `<i class="fa-regular fa-circle fa-stack-1x text-dark"></i></span>`;
                            deleteButton = sent ? "" :
                                `<a class='deleteAlertButton' href='javascript:;' ` +
                                `onclick='deleteAlert(${row.id});'>` +
                                `<i class='fa-solid fa-xmark text-danger' title="Delete alert"></i></a>`;
                        }
                        const formattedDate = moment(row.sendTime * 1000).format(
                            'MM/DD/YYYY hh:mm A');
                        return `<span class="${color} default-cursor">${icon} ${formattedDate} ${deleteButton}</span>`;
                    } else {
                        return row.sendTime;
                    }
                },
            },
            {
                className: 'dt-center',
                data: function(row, type, set, meta) {
                    if (type === 'display') {
                        let result = '';
                        if (row.alertType === 'users') {
                            result =
                                `<span class="badge-primary text-light badge-pill py-1 px-2 default-cursor">` +
                                `User</span>`;
                        } else if (row.alertType === 'userRightsHolders') {
                            result =
                                `<span class="badge-warning text-body badge-pill py-1 px-2 default-cursor">` +
                                `User Rights Holder</span>`;
                        } else if (row.alertType === 'expiration') {
                            result =
                                `<span class="badge-danger text-light badge-pill py-1 px-2 default-cursor">` +
                                `Expiration</span>`;
                        }

                        return result;
                    } else {
                        return row.alertType;
                    }
                },
            },
            {
                className: 'dt-center',
                data: function(row, type, set, meta) {
                    if (type === 'display') {
                        return row.reminder ?
                            `<span class="badge badge-pill bg-reminder border font-weight-normal default-cursor">` +
                            `Reminder</span>` :
                            '';
                    } else if (type === 'filter') {
                        return row.reminder ? 'Reminder' : '';
                    }
                    return row.reminder;
                }
            },
            {
                className: 'dt-center',
                data: function(row, type, set, meta) {
                    if (type === 'filter') {
                        return row.subject + ' ' + row.body;
                    } else {
                        return `<button class='btn btn-xs btn-outline-info' ` +
                            `onclick='openAlertPreview("${row.id}");'>` +
                            `<i class="fa-solid fa-envelope"></i> View</button>`;
                    }
                }
            },
            {
                data: function(row, type, set, meta) {
                    const users = row['users'];
                    if (type === 'display') {
                        let result = [];
                        const root = app_path_webroot_full + app_path_webroot + 'ControlCenter';
                        for (let user of users) {
                            result.push(
                                `<strong><a rel="noreferrer noopener" target="_blank" ` +
                                `href="${root}/view_users.php?username=${user}">${user}</a></strong>`
                            );
                        }
                        return result.join('<br>');
                    } else if (type === 'filter') {
                        return users.join('&&&&&');
                    } else {
                        return users;
                    }
                },
            }, {
                data: 'recipients',
            }, {
                data: 'status',
                visible: false
            }
        ],
        responsive: false,
        order: [
            [1, 'desc']
        ],
        initComplete: function() {
            console.timeLog('dt', 'dt init complete');
            $('.timePicker').flatpickr({
                enableTime: true,
                dateFormat: "U",
                altInput: true,
                altFormat: "m/d/Y G:i K",
                onChange: function(selectedDates, dateStr, instance) {
                    const dt = $('#alertLogTable').DataTable();
                    dt.draw();
                },
            });


            const dt = this.api();
            const usersAll = dt.column(5).data().toArray();
            const users = Array.from(new Set(usersAll.flat()));
            const usersSelect = $('#usersSelect').select2({
                minimumResultsForSearch: 20,
                placeholder: "Filter users",
                allowClear: false
            });
            users.forEach(user => usersSelect.append(new Option(user, user, false, false)));
            usersSelect.trigger('change');
            usersSelect.on('change', searchUsers);

            const recipientsAll = dt.column(6).data().toArray();
            const recipients = Array.from(new Set(recipientsAll.flat()));
            const recipientSelect = $('#recipientSelect').select2({
                minimumResultsForSearch: 20,
                placeholder: "Filter recipients",
                allowClear: false,
                templateResult: function(recipient) {
                    return $(`<span>${recipient.text}</span>`);
                },
                templateSelection: function(recipient) {
                    return $(
                        `<span>${recipient.text.match('\<strong\>(.*)\<\/strong\>')[1]}</span>`
                    );
                }
            });
            recipients.forEach(recipient => recipientSelect.append(new Option(recipient, recipient,
                false, false)));
            recipientSelect.trigger('change');
            recipientSelect.on('change', function() {
                dt.draw();
            });

            const alertTypeSelect = $('#alertTypeSelect').select2({
                minimumResultsForSearch: 20,
                placeholder: "Filter alert types",
                allowClear: false
            });
            alertTypeSelect.on('change', function() {
                dt.draw();
            });

            const notificationTypeSelect = $('#notificationTypeSelect').select2({
                minimumResultsForSearch: 20,
                placeholder: "Filter notification types",
                allowClear: false
            });
            notificationTypeSelect.on('change', function() {
                dt.draw();
            });

            $('.alertLogWrapper').show();
            $('table#alertLogTable select').val(null).trigger('change');
            dt.columns.adjust();
            console.timeEnd('dt');
            console.log(performance.now());
        },
        lengthMenu: [
            [10, 25, 50, 100, -1],
            [10, 25, 50, 100, "All"]
        ],
        language: {
            search: "_INPUT_",
            searchPlaceholder: "Search Alerts...",
            search: "_INPUT_",
            infoFiltered: " - filtered from _MAX_ total alerts",
            emptyTable: "No alerts found in this project",
            info: "Showing _START_ to _END_ of _TOTAL_ alerts",
            infoEmpty: "Showing 0 to 0 of 0 alerts",
            lengthMenu: "Show _MENU_ alerts",
            loadingRecords: "Loading...",
            zeroRecords: "No matching alerts found"
        }
    });
});
</script>