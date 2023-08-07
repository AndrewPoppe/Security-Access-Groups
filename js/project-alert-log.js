
const sag_module = __MODULE__;

console.log(performance.now());

sag_module.uniqueArray = function (a) {
    return [...new Set(a.map(o => JSON.stringify(o)))].map(s => JSON.parse(s));
}

sag_module.openAlertPreview = function (alert_id) {
    Swal.fire({
        title: sag_module.tt('alerts_16'),
        confirmButtonText: sag_module.tt('ok'),
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
                title: sag_module.tt('alerts_17'),
                confirmButtonText: sag_module.tt('ok'),
                icon: 'error'
            });
        });
}

sag_module.createAlertPreviewModal = function (data) {
    $('#alertPreviewModal .modal-body').html(data.table);
    let title = sag_module.tt('alerts_15') + ' - ';
    if (data.alertType === "users") {
        $('#alertPreviewModal .modal-header')[0].classList = 'modal-header bg-primary text-light';
        title += sag_module.tt('alerts_18');
    } else if (data.alertType === "userRightsHolders") {
        $('#alertPreviewModal .modal-header')[0].classList = 'modal-header bg-warning text-body';
        title += sag_module.tt('alerts_19');
    } else if (data.alertType === "expiration") {
        $('#alertPreviewModal .modal-header')[0].classList = 'modal-header bg-danger text-light';
        title += sag_module.tt('alerts_20');
    }
    if (data.reminder) {
        title += sag_module.tt('alerts_21');
        $('#alertPreviewModal .modal-body')[0].classList = 'modal-body bg-reminder';
    } else {
        $('#alertPreviewModal .modal-body')[0].classList = 'modal-body';
    }
    $('#alertPreviewModalLabel').text(title);
    $('#alertPreviewModal').modal('show');
}

sag_module.deleteAlert = function (alert_id) {
    Swal.fire({
        title: sag_module.tt('alerts_22'),
        text: sag_module.tt('alerts_23'),
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: sag_module.tt('alerts_24'),
        cancelButtonText: sag_module.tt('cancel'),
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
                                title: sag_module.tt('alerts_25')
                            });
                            $('#alertLogTable').DataTable().ajax.reload();
                        } else {
                            Swal.fire({
                                title: sag_module.tt('alerts_26'),
                                html: data.message,
                                icon: 'error',
                                confirmButtonText: sag_module.tt('ok')
                            })
                        }
                    })
                    .catch(function (data) {
                        Swal.fire({
                            title: sag_module.tt('alerts_26'),
                            html: data.responseText,
                            icon: 'error',
                            confirmButtonText: sag_module.tt('ok')
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

// Set up "OR" search
sag_module.setUpOrSearch = function (table) {
    $('.dataTables_filter input').off().on('input', function () {
        const searchTerm = $(this).val();
        if (searchTerm.includes('|')) {
            const newTerm = searchTerm.split('|').map(term => '(' + term.replaceAll('"', '').trim() + ')').filter(term => term && term != '()').join('|');
            table.search(newTerm, true, false, true).draw();
        } else {
            table.search(searchTerm, false, true, true).draw();
        }
        this.value = searchTerm;
    });
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
                            `title="${sag_module.tt('alerts_27')}"></i></span>` :
                            `<span class="fa-stack fa-sm default-cursor" ` +
                            `style="width: 1.15em; height: 1.15em; vertical-align: top;">` +
                            `<i class='fa-sharp fa-solid fa-check-circle fa-stack-1x' ` +
                            `title="${sag_module.tt('alerts_28')}"></i></span>`;
                    } else {
                        const sent = moment.now() > (row.sendTime * 1000);
                        color = sent ? "text-success" : "text-secondary";
                        const style = '--fa-primary-color: #000000;' +
                            '--fa-secondary-color: #000000;' +
                            '--fa-secondary-opacity: 0.1';
                        icon = sent ?
                            `<span class="fa-stack fa-sm default-cursor" ` +
                            `style="width: 1.15em; height: 1.15em; vertical-align: top;">` +
                            `<i class='fa-sharp fa-solid fa-check-circle fa-stack-1x' title="${sag_module.tt('alerts_29')}"></i>` +
                            `</span>` :
                            `<span class="fa-stack fa-sm default-cursor" ` +
                            `style="width: 1.15em; height: 1.15em; vertical-align: top; opacity: 0.5;" ` +
                            `title="${sag_module.tt('alerts_30')}">` +
                            `<i class="fa-duotone fa-clock-three fa-stack-1x text-dark" ` +
                            `style="${style}"></i>` +
                            `<i class="fa-regular fa-circle fa-stack-1x text-dark"></i></span>`;
                        deleteButton = sent ? "" :
                            `<a class='deleteAlertButton' href='javascript:;' ` +
                            `onclick='sag_module.deleteAlert(${row.id});'>` +
                            `<i class='fa-solid fa-xmark text-danger' title="${sag_module.tt('alerts_31')}"></i></a>`;
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
                            `${sag_module.tt('user')}</span>`;
                    } else if (row.alertType === 'userRightsHolders') {
                        result =
                            `<span class="badge-warning text-body badge-pill py-1 px-2 default-cursor">` +
                            `${sag_module.tt('alerts_6')}</span>`;
                    } else if (row.alertType === 'expiration') {
                        result =
                            `<span class="badge-danger text-light badge-pill py-1 px-2 default-cursor">` +
                            `${sag_module.tt('status_ui_62')}</span>`;
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
                        `${sag_module.tt('status_ui_24')}</span>` :
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
                        `<i class="fa-solid fa-envelope"></i> ${sag_module.tt('alerts_32')}</button>`;
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
                placeholder: sag_module.tt('alerts_33'),
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
                placeholder: sag_module.tt('alerts_34'),
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
                placeholder: sag_module.tt('alerts_35'),
                allowClear: false
            });
            alertTypeSelect.on('change', function () {
                dt.draw();
            });

            const notificationTypeSelect = $('#notificationTypeSelect').select2({
                minimumResultsForSearch: 20,
                placeholder: sag_module.tt('alerts_36'),
                allowClear: false
            });
            notificationTypeSelect.on('change', function () {
                dt.draw();
            });

            $('.alertLogWrapper').show();
            $('table#alertLogTable select').val(null).trigger('change');
            sag_module.setUpOrSearch(dt);
            dt.columns.adjust();
            console.log(performance.now());
        },
        lengthMenu: [
            [10, 25, 50, 100, -1],
            [10, 25, 50, 100, sag_module.tt('alerts_37')]
        ],
        language: {
            search: "_INPUT_",
            searchPlaceholder: sag_module.tt('dt_alerts_search_placeholder'),
            infoFiltered: " - " + sag_module.tt('dt_alerts_info_filtered', '_MAX_'),
            emptyTable: sag_module.tt('dt_alerts_empty_table'),
            info: sag_module.tt('dt_alerts_info', { start: '_START_', end: '_END_', total: '_TOTAL_' }),
            infoEmpty: sag_module.tt('dt_alerts_info_empty'),
            lengthMenu: sag_module.tt('dt_alerts_length_menu', '_MENU_'),
            loadingRecords: sag_module.tt('dt_alerts_loading_records'),
            zeroRecords: sag_module.tt('dt_alerts_zero_records'),
            select: {
                rows: {
                    _: sag_module.tt('dt_alerts_select_rows_other'),
                    0: sag_module.tt('dt_alerts_select_rows_zero'),
                    1: sag_module.tt('dt_alerts_select_rows_one')
                }
            },
            paginate: {
                first: sag_module.tt('dt_alerts_paginate_first'),
                last: sag_module.tt('dt_alerts_paginate_last'),
                next: sag_module.tt('dt_alerts_paginate_next'),
                previous: sag_module.tt('dt_alerts_paginate_previous')
            },
            aria: {
                sortAscending: sag_module.tt('dt_alerts_aria_sort_ascending'),
                sortDescending: sag_module.tt('dt_alerts_aria_sort_descending')
            }
        }
    });
});