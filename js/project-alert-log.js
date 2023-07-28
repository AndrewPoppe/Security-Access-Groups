
const sag_module = __MODULE__;

console.log(performance.now());

sag_module.uniqueArray = function (a) {
    return [...new Set(a.map(o => JSON.stringify(o)))].map(s => JSON.parse(s));
}

sag_module.openAlertPreview = function (alert_id) {
    Swal.fire({
        title: 'Loading...',
        didOpen: () => {
            Swal.showLoading()
        }
    });
    sag_module.ajax('getAlert', { alert_id: alert_id })
        .then(function (json) {
            Swal.close();
            const data = JSON.parse(json);
            sag_module.createAlertPreviewModal(data);
        })
        .catch(function (data) {
            Swal.fire({
                title: 'There was an error loading the alert preview.',
                icon: 'error'
            });
        });
}

sag_module.createAlertPreviewModal = function (data) {
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

sag_module.deleteAlert = function (alert_id) {
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
                sag_module.ajax('deleteAlert', { alert_id: alert_id })
                    .then(function (json) {
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
                    .catch(function (data) {
                        Swal.fire({
                            title: 'There was an error deleting the alert.',
                            html: data.responseText,
                            icon: 'error'
                        })
                    });
            }
        });
}

sag_module.showPastAlerts = function () {
    document.querySelector('#mindatetime')._flatpickr.clear();
    document.querySelector('#maxdatetime')._flatpickr.setDate(new Date(), true);
}

sag_module.showFutureAlerts = function () {
    document.querySelector('#maxdatetime')._flatpickr.clear();
    document.querySelector('#mindatetime')._flatpickr.setDate(new Date(), true);
}

// Custom user function
sag_module.searchUsers = function () {
    const users = $('#usersSelect').val() || [];
    const dt = $('#alertLogTable').DataTable();
    dt.columns(5).search(users.join('|'), true).draw();
}

$(document).ready(function () {
    window.Toast = Swal.mixin({
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


    $('#sub-nav').removeClass('d-none');

    // Custom range filtering function
    $.fn.dataTable.ext.search.push(function (settings, data, dataIndex) {

        // Dates
        const minDateTimeEl = document.querySelector('#mindatetime')._flatpickr;
        const maxDateTimeEl = document.querySelector('#maxdatetime')._flatpickr;

        if (typeof minDateTimeEl === 'undefined' || typeof maxDateTimeEl === 'undefined') {
            return true;
        }

        const minDateTime = minDateTimeEl.selectedDates[0]?.getTime() / 1000;
        const maxDateTime = maxDateTimeEl.selectedDates[0]?.getTime() / 1000;
        const sendDateTime = parseFloat(data[1]) || 0; // use data for the send time column

        const neitherDateSet = isNaN(minDateTime) && isNaN(maxDateTime);
        const lessThanMax = isNaN(minDateTime) && sendDateTime <= maxDateTime;
        const greaterThanMin = minDateTime <= sendDateTime && isNaN(maxDateTime);
        const betweenDates = minDateTime <= sendDateTime && sendDateTime <= maxDateTime;

        return neitherDateSet || lessThanMax || greaterThanMin || betweenDates;

    });

    // Custom alert type function
    $.fn.dataTable.ext.search.push(function (settings, data, dataIndex) {
        const alertTypes = $('#alertTypeSelect').val() || [];
        if (alertTypes.length === 0) {
            return true;
        }
        const thisAlertType = data[2];
        return alertTypes.includes(thisAlertType);
    });

    // Custom notification type function
    $.fn.dataTable.ext.search.push(function (settings, data, dataIndex) {
        const notificationTypes = $('#notificationTypeSelect').val() || [];
        if (notificationTypes.length === 0) {
            return true;
        }
        const thisNotificationType = String(data[3] === "Reminder");
        return notificationTypes.includes(thisNotificationType);
    });

    // Custom recipient function
    $.fn.dataTable.ext.search.push(function (settings, data, dataIndex) {
        const recipients = $('#recipientSelect').val() || [];
        if (recipients.length === 0) {
            return true;
        }
        const thisRecipient = String(data[6]).replace(/&&&&&.*/, '');
        return recipients.includes(thisRecipient);
    });

    $('#alertLogTable').DataTable({
        ajax: function (data, callback, settings) {
            sag_module.ajax('getAlerts')
                .then(response => {
                    callback(JSON.parse(response))
                })
                .catch(error => {
                    console.error(error);
                    callback({ data: [] });
                });
        },
        deferRender: true,
        columns: [{
            data: 'id',
            visible: true
        },
        {
            data: function (row, type, set, meta) {
                if (type === 'display') {
                    let color = "";
                    let icon = "";
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
                            `onclick='sag_module.deleteAlert(${row.id});'>` +
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
            data: function (row, type, set, meta) {
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
            data: function (row, type, set, meta) {
                if (type === 'display') {
                    return row.reminder ?
                        `<span class="badge badge-pill bg-reminder border font-weight-normal default-cursor text-dark">` +
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
            data: function (row, type, set, meta) {
                if (type === 'filter') {
                    return row.subject + ' ' + row.body;
                } else {
                    return `<button class='btn btn-xs btn-outline-info' ` +
                        `onclick='sag_module.openAlertPreview("${row.id}");'>` +
                        `<i class="fa-solid fa-envelope"></i> View</button>`;
                }
            }
        },
        {
            data: function (row, type, set, meta) {
                const users = row['users'];
                if (type === 'display') {
                    let result = [];
                    const root =
                        `${app_path_webroot_full}redcap_v${redcap_version}/ControlCenter`;
                    for (let user of users) {
                        result.push(
                            `<strong><a rel="noreferrer noopener" target="_blank" ` +
                            `href="${root}/view_users.php?username=${user.username}">${user.username}</a></strong> (${user.name})`
                        );
                    }
                    return result.join('<br>');
                } else if (type === 'filter') {
                    let result = '';
                    users.forEach(user => result +=
                        `${user.username}&&&&&${user.name}&&&&&${user.email}`);
                    return result;
                } else {
                    return users;
                }
            },
        }, {
            data: function (row, type, set, meta) {
                const recipient = row['recipient'];
                if (type === 'display') {
                    const root =
                        `${app_path_webroot_full}redcap_v${redcap_version}/ControlCenter`;
                    const url = `${root}/view_users.php?username=${recipient.username}`;
                    return `<strong><a rel="noreferrer noopener" target="_blank" ` +
                        `href="${url}">${recipient.username}</a></strong>` +
                        ` (${recipient.user_firstname} ${recipient.user_lastname})` +
                        `<br><a href="mailto:${recipient.user_email}">${recipient.user_email}</a>`;
                } else if (type === 'search' || type === 'filter') {
                    return `${recipient.username}&&&&&${recipient.user_firstname} ${recipient.user_lastname}&&&&&${recipient.user_email}`;
                }
                return recipient;
            },
        }, {
            data: 'status',
            visible: false
        }
        ],
        columnDefs: [{
            targets: '_all',
            className: 'SAG'
        }],
        responsive: false,
        order: [
            [1, 'desc']
        ],
        initComplete: function () {
            $('.timePicker').flatpickr({
                enableTime: true,
                dateFormat: "U",
                altInput: true,
                altFormat: "m/d/Y G:i K",
                allowClear: true,
                onChange: function (selectedDates, dateStr, instance) {
                    const dt = $('#alertLogTable').DataTable();
                    dt.draw();
                },
            });


            const dt = this.api();
            const usersAll = dt.column(5).data().toArray();
            const users = sag_module.uniqueArray(usersAll.flatten());
            const usersSelect = $('#usersSelect').select2({
                minimumResultsForSearch: 20,
                placeholder: "Filter users",
                allowClear: false,
                templateResult: function (user) {
                    return $(`<span>${user.text}</span>`);
                },
                templateSelection: function (user) {
                    return $(
                        `<span>${user.id}</span>`
                    );
                }
            });
            users.forEach(user => usersSelect.append(new Option(
                `<strong>${user.username}</strong> (${user.name})`,
                user.username, false, false)));
            usersSelect.trigger('change');
            usersSelect.on('change', sag_module.searchUsers);

            const recipientsAll = dt.column(6).data().toArray();
            const recipients = sag_module.uniqueArray(recipientsAll);
            const recipientSelect = $('#recipientSelect').select2({
                minimumResultsForSearch: 20,
                placeholder: "Filter recipients",
                allowClear: false,
                templateResult: function (recipient) {
                    return $(`<span>${recipient.text}</span>`);
                },
                templateSelection: function (recipient) {
                    return $(
                        `<span>${recipient.id}</span>`
                    );
                }
            });
            recipients.forEach(recipient => recipientSelect.append(new Option(
                `<strong>${recipient.username}</strong> (${recipient.user_firstname} ${recipient.user_lastname})`,
                recipient.username,
                false, false
            )));
            recipientSelect.trigger('change');
            recipientSelect.on('change', function () {
                dt.draw();
            });

            const alertTypeSelect = $('#alertTypeSelect').select2({
                minimumResultsForSearch: 20,
                placeholder: "Filter alert types",
                allowClear: false
            });
            alertTypeSelect.on('change', function () {
                dt.draw();
            });

            const notificationTypeSelect = $('#notificationTypeSelect').select2({
                minimumResultsForSearch: 20,
                placeholder: "Filter notification types",
                allowClear: false
            });
            notificationTypeSelect.on('change', function () {
                dt.draw();
            });

            $('.alertLogWrapper').show();
            $('table#alertLogTable select').val(null).trigger('change');
            dt.columns.adjust();
            console.log(performance.now());
        },
        lengthMenu: [
            [10, 25, 50, 100, -1],
            [10, 25, 50, 100, "All"]
        ],
        language: {
            search: "_INPUT_",
            searchPlaceholder: "Search Alerts...",
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