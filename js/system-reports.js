const sag_module = __MODULE__;
console.log(performance.now());
console.time('dt');

sag_module.hover = function () {
    const thisNode = $(this);
    const rowIdx = thisNode.attr('data-dt-row');
    $("tr[data-dt-row='" + rowIdx + "'] td").addClass("highlight"); // shade only the hovered row
}

sag_module.dehover = function () {
    const thisNode = $(this);
    const rowIdx = thisNode.attr('data-dt-row');
    $("tr[data-dt-row='" + rowIdx + "'] td").removeClass("highlight"); // shade only the hovered row
}

sag_module.makeUserTable = function (usersString) {
    let tableString =
        `<table class="table table-sm table-bordered"><thead><tr><th>${sag_module.tt('status_ui_59')}</th><th>${sag_module.tt('status_ui_60')}</th><th>${sag_module.tt('status_ui_61')}</th><th>${sag_module.tt('status_ui_63')}</th></tr></thead><tbody>`;
    JSON.parse(usersString).forEach(user => {
        tableString +=
            `<tr><td>${user.username}</td><td>${user.name}</td><td>${user.email}</td><td><strong>${user.sag}</strong><br><small>${user.sag_name}</small></td></tr>`;
    });
    tableString += '</tbody></table>';
    return Swal.fire({
        title: sag_module.tt('status_ui_54'),
        html: tableString,
        width: '900px',
        showConfirmButton: false,
    });
}

sag_module.clearTables = function () {
    $('.dataTables_filter').remove();
    $('.dataTables_length').remove();
    $('.dataTables_info').remove();
    $('.dataTables_paginate').remove();
    $('.dt-buttons').remove();
    $('.dataTable').DataTable().destroy();
    $('.tableSelect').empty();
}

sag_module.getProjectStatusFormatted = function (projectStatus) {
    if (!projectStatus) return '';
    let result;
    if (projectStatus.status == 'DONE') {
        result = `<span class="font-weight-bold text-danger" style="font-size:14;"><i class="mr-1 fa-solid fa-archive" style="color: #C00000;"></i>${projectStatus.label}</span>`;
    } else if (projectStatus.status == 'AC') {
        result = `<span class="mt-1 font-weight-bold" style="font-size:14;color:#A00000;"><i class="mr-1 fa-solid fa-minus-circle" style="color: #A00000;"></i>${projectStatus.label}</span>`;
    } else if (projectStatus.status == 'PROD') {
        result = `<span class="mt-1 font-weight-bold" style="font-size:14;color:#00A000;""><i class="mr-1 fa-regular fa-check-square" style="color: #00A000;"></i>${projectStatus.label}</span>`;
    } else if (projectStatus.status == 'DEV') {
        result = `<span class="mt-1 font-weight-bold" style="font-size:14;color:#444"><i class="mr-1 fa-solid fa-wrench" style="color: #444;"></i>${projectStatus.label}</span>`;
    }
    return result;
}

sag_module.getProjectStatusIcon = function (projectStatus) {
    if (!projectStatus) return '';
    let result;
    if (projectStatus.status == 'DONE') {
        result = `<span title="${projectStatus.label}" class="text-danger" style="font-size:14;"><i class="fa-solid fa-archive" style="color: #C00000;"></i></span>`;
    } else if (projectStatus.status == 'AC') {
        result = `<span title="${projectStatus.label}" style="font-size:14;color:#A00000;"><i class="fa-solid fa-minus-circle" style="color: #A00000;"></i></span>`;
    } else if (projectStatus.status == 'PROD') {
        result = `<span title="${projectStatus.label}" style="font-size:14;color:#00A000;""><i class="fa-regular fa-check-square" style="color: #00A000;"></i></span>`;
    } else if (projectStatus.status == 'DEV') {
        result = `<span title="${projectStatus.label}" style="font-size:14;color:#444"><i class="fa-solid fa-wrench" style="color: #444;"></i></span>`;
    }
    return result;
}

// Set up "OR" search
sag_module.setUpOrSearch = function (table) {
    let searchTerm = '';
    $('.dataTables_filter input').off().on('input', function () {
        searchTerm = $(this).val().replaceAll(/[()]/g, '')
        if (searchTerm.includes('|')) {
            const newTerm = searchTerm.split('|').map(term => '(' + term.replaceAll('"', '').trim() + ')').filter(term => term && term != '()').join('|');
            table.search(newTerm, true, false, true).draw();
        } else {
            table.search(searchTerm, false, true, true).draw();
        }
        this.value = searchTerm;
    });
    table.on('search.dt', () => $('.dataTables_filter input').val(searchTerm));
}

$(document).on('preXhr.dt', function (e, settings, data) {
    console.time('report');
});

// Projects Table
sag_module.showProjectTable = function (includeExpired = false) {
    sag_module.clearTables();
    $('.tableWrapper').hide();
    $('#projectTableTitle').text(sag_module.tt('cc_reports_24') + ' ' + (includeExpired ?
        sag_module.tt('cc_reports_25') : sag_module.tt('cc_reports_26')));
    if ($('#projectTableWrapper').is(':hidden')) {
        Swal.fire({
            title: sag_module.tt('alerts_16'),
            didOpen: () => {
                Swal.showLoading()
            },
            confirmButtonText: sag_module.tt('ok'),
        });
    }
    $('#SAG-System-Table.projectTable').DataTable({
        ajax: function (data, callback, settings) {
            sag_module.ajax('getProjectReport', { includeExpired: includeExpired })
                .then(function (data) {
                    callback(JSON.parse(data));
                })
                .catch(function (error) {
                    console.error(error);
                    callback({ data: [] });
                });
        },
        deferRender: true,
        initComplete: function () {
            $('#projectTableWrapper').show();
            Swal.close();
            const table = this.api();
            const data = table.data().toArray();

            // Users filter
            const usersSelect = $('#usersSelectProject').select2({
                placeholder: sag_module.tt('cc_reports_27'),
                templateResult: function (user) {
                    return $(`<span>${user.text}</span>`);
                },
                templateSelection: function (user) {
                    return $(`<span>${user.id}</span>`);
                }
            });

            // SAGs filter
            const sagsSelect = $('#sagsSelectProject').select2({
                placeholder: sag_module.tt('cc_reports_28'),
                templateResult: function (sag) {
                    return $(`<span>${sag.text}</span>`);
                },
                templateSelection: function (sag) {
                    return $(`<span>${sag.text}</span>`);
                }
            });

            // Projects filter
            const projectsSelect = $('#projectsSelectProject').select2({
                placeholder: sag_module.tt('cc_reports_29'),
                templateResult: function (project) {
                    return $(`<span>${project.text}</span>`);
                },
                templateSelection: function (project) {
                    return $(`<span>PID: ${project.id}</span>`);
                }
            });

            // Rights filter
            const rightsSelect = $('#rightsSelectProject').select2({
                placeholder: sag_module.tt('cc_reports_30'),
                templateResult: function (right) {
                    return $(`<span>${right.text}</span>`);
                }
            });

            data.forEach(row => {
                // Users
                row.users_with_bad_rights.forEach(user => {
                    const userid = user.username;
                    if (!usersSelect.find(`option[value='${userid}']`).length) {
                        const text = `<strong>${userid}</strong> (${user.name})`;
                        usersSelect.append(new Option(text, userid, false, false));
                    }
                });

                // SAGs
                data.forEach(row => {
                    row.sags.forEach(sag => {
                        const sagId = sag.sag;
                        if (!sagsSelect.find(`option[value='${sagId}']`)
                            .length) {
                            const text =
                                `<strong>${sag.sag_name}</strong> <small>${sagId}</small>`;
                            sagsSelect.append(new Option(text, sagId, false,
                                false));
                        }
                    });
                });

                // Projects
                data.forEach(row => {
                    const pid = row.project_id;
                    if (!projectsSelect.find(`option[value='${pid}']`).length) {
                        const text =
                            `<strong>PID:${pid}</strong> <small>${row.project_title}</small>`;
                        projectsSelect.append(new Option(text, pid, false, false));
                    }
                });

                // Rights
                data.forEach(row => {
                    row.bad_rights.forEach(right => {
                        if (!rightsSelect.find(`option[value='${right}']`)
                            .length) {
                            rightsSelect.append(new Option(right, right, false,
                                false));
                        }
                    });
                });
            });

            $('.projectTableSelect').trigger('change');
            $('.projectTableSelect').on('change', () => table.draw());


            table.on('draw', function () {
                $('.dataTable tbody tr').each((i, row) => {
                    row.onmouseenter = sag_module.hover;
                    row.onmouseleave = sag_module.dehover;
                });
            });

            $('.dataTable tbody tr').each((i, row) => {
                row.onmouseenter = sag_module.hover;
                row.onmouseleave = sag_module.dehover;
            });
            $('div.dt-buttons button').removeClass('dt-button');
            table.columns.adjust().draw();
            sag_module.setUpOrSearch(table);
        },
        buttons: [{
            extend: 'excelHtml5',
            text: `<span style="font-size: .875rem;"><i class="fa-sharp fa-solid fa-file-excel fa-fw"></i>${sag_module.tt('cc_reports_31')}</span>`,
            exportOptions: {
                columns: [5, 6, 7, 1, 8, 9, 10, 11]
            },
            className: 'btn btn-sm btn-success border mb-1',
            title: 'ProjectsWithNoncompliantRights' + (includeExpired ? '_all_' : '_nonexpired_') +
                moment().format('YYYY-MM-DD_HHmmss'),
        }],
        dom: 'lBftip',

        columns: [
        // 0: Project (Display)    
        {
            title: sag_module.tt('cc_reports_17'),
            data: function (row, type, set, meta) {
                const pid = row.project_id;
                const projectUrl =
                    `${app_path_webroot_full}redcap_v${redcap_version}/ExternalModules/?prefix={{MODULE_DIRECTORY_PREFIX}}&page=project-status&pid=${pid}`;
                const projectTitle = row.project_title.replaceAll('"', '');
                return `<strong><a target="_blank" rel="noreferrer noopener" href="${projectUrl}">PID: ${pid}</a></strong><br>${projectTitle}<br>${sag_module.getProjectStatusFormatted(row.project_status)}`;
            },
            width: '20%'
        },
        // 1: Count of Users (Display)
        {
            title: sag_module.tt('cc_reports_32'),
            data: function (row, type, set, meta) {
                const users = row.users_with_bad_rights;
                const usersString = JSON.stringify(users);
                return '<a href="javascript:void(0)" onclick=\'sag_module.makeUserTable(`' +
                    usersString + '`);\'>' + users.length + '</a>';
            },
            width: '5%'
        },
        // 2: Users (Display)
        {
            title: sag_module.tt('cc_reports_19'),
            data: function (row, type, set, meta) {
                const users = row.users_with_bad_rights ?? [];
                return users.map(user => {
                    const url =
                        `${app_path_webroot_full}redcap_v${redcap_version}/ControlCenter/view_users.php?username=${user.username}`;
                    return `<strong><a target="_blank" rel="noreferrer noopener" href="${url}">${user.username}</a></strong>` +
                        ` (${user.name})`;
                }).join('<br>');
            },
            width: '20%'
        },
        // 3: SAG (Display)
        {
            title: sag_module.tt('cc_reports_20'),
            data: function (row, type, set, meta) {
                const sags = row.sags ?? [];
                return sags.map(sag => {
                    return `<strong>${sag.sag_name}</strong> <small>${sag.sag}</small>`;
                }).join('<br>');
            },
            width: '20%'
        },
        // 4: Bad Rights (Display)
        {
            title: sag_module.tt('cc_reports_21'),
            data: function (row, type, set, meta) {
                if (type === 'display') {
                    return row.bad_rights.join('<br>');
                }
                return row.bad_rights.join('&&&&&');
            },
            width: '35%'
        },
        // 5: project_id
        {
            title: sag_module.tt('cc_reports_33'),
            data: "project_id",
            visible: false
        },
        // 6: project title
        {
            title: sag_module.tt('cc_reports_34'),
            data: "project_title",
            visible: false
        },
        // 7: project status
        {
            title: sag_module.tt('status_ui_2'),
            data: function (row, type, set, meta) {
                if (type === 'filter') {
                    return 'project_status=' + row.project_status.label;
                }
                return row.project_status.label;
            },
            visible: false
        },
        // 8: users
        {
            title: sag_module.tt('cc_reports_19'),
            data: function (row, type, set, meta) {
                const users = row.users_with_bad_rights ?? [];
                return users.map(user => user.username).join(", ");
            },
            visible: false
        },
        // 9: sags
        {
            title: sag_module.tt('cc_reports_35'),
            data: function (row, type, set, meta) {
                const sags = row.sags ?? [];
                return sags.map(sag => sag.sag).join(", ");
            },
            visible: false
        },
        // 10: sag names
        {
            title: sag_module.tt('cc_reports_36'),
            data: function (row, type, set, meta) {
                const sags = row.sags ?? [];
                return sags.map(sag => sag.sag_name).join(", ");
            },
            visible: false
        },
        // 11: bad rights
        {
            title: sag_module.tt('cc_reports_21'),
            data: function (row, type, set, meta) {
                return row.bad_rights.join(', ');
            },
            visible: false
        }
        ],
        columnDefs: [{
            "className": "dt-center dt-head-center SAG",
            "targets": "_all"
        }],
        language: {
            search: "_INPUT_",
            searchPlaceholder: sag_module.tt('dt_cc_reports_search_placeholder'),
            infoFiltered: " - " + sag_module.tt('dt_cc_reports_info_filtered', '_MAX_'),
            emptyTable: sag_module.tt('dt_cc_reports_empty_table'),
            info: sag_module.tt('dt_cc_reports_info', { start: '_START_', end: '_END_', total: '_TOTAL_' }),
            infoEmpty: sag_module.tt('dt_cc_reports_info_empty'),
            lengthMenu: sag_module.tt('dt_cc_reports_length_menu', '_MENU_'),
            loadingRecords: sag_module.tt('dt_cc_reports_loading_records'),
            zeroRecords: sag_module.tt('dt_cc_reports_zero_records'),
            decimal: sag_module.tt('dt_cc_reports_decimal'),
            thousands: sag_module.tt('dt_cc_reports_thousands'),
            select: {
                rows: {
                    _: sag_module.tt('dt_cc_reports_select_rows_other'),
                    0: sag_module.tt('dt_cc_reports_select_rows_zero'),
                    1: sag_module.tt('dt_cc_reports_select_rows_one')
                }
            },
            paginate: {
                first: sag_module.tt('dt_cc_reports_paginate_first'),
                last: sag_module.tt('dt_cc_reports_paginate_last'),
                next: sag_module.tt('dt_cc_reports_paginate_next'),
                previous: sag_module.tt('dt_cc_reports_paginate_previous')
            },
            aria: {
                sortAscending: sag_module.tt('dt_cc_reports_aria_sort_ascending'),
                sortDescending: sag_module.tt('dt_cc_reports_aria_sort_descending')
            }
        }
    });
}

// Users Table
sag_module.showUserTable = function (includeExpired = false) {
    sag_module.clearTables();
    $('.tableWrapper').hide();
    $('#userTableTitle').text(sag_module.tt('cc_reports_38') + ' ' + (includeExpired ? sag_module.tt('cc_reports_25') : sag_module.tt('cc_reports_26')));
    if ($('#userTableWrapper').is(':hidden')) {
        Swal.fire({
            title: sag_module.tt('alerts_16'),
            didOpen: () => {
                Swal.showLoading()
            },
            confirmButtonText: sag_module.tt('ok'),
        });
    }
    $('#SAG-System-Table.userTable').DataTable({
        ajax: function (data, callback, settings) {
            sag_module.ajax('getUserReport', { includeExpired: includeExpired })
                .then(function (data) {
                    callback(JSON.parse(data));
                })
                .catch(function (error) {
                    console.error(error);
                    callback({ data: [] });
                });
        },
        deferRender: true,
        initComplete: function () {
            $('#userTableWrapper').show();
            Swal.close();
            const table = this.api();
            const data = table.data().toArray();

            // Users filter
            const usersSelect = $('#usersSelectUser').select2({
                placeholder: sag_module.tt('cc_reports_27'),
                templateResult: function (user) {
                    return $(`<span>${user.text}</span>`);
                },
                templateSelection: function (user) {
                    return $(`<span>${user.id}</span>`);
                }
            });

            // SAG filter
            const sagsSelect = $('#sagsSelectUser').select2({
                placeholder: sag_module.tt('cc_reports_28'),
                templateResult: function (sag) {
                    return $(`<span>${sag.text}</span>`);
                },
                templateSelection: function (sag) {
                    return $(`<span>${sag.text}</span>`);
                }
            });
            // Project filter
            const projectsSelect = $('#projectsSelectUser').select2({
                placeholder: sag_module.tt('cc_reports_29'),
                templateResult: function (pid) {
                    return $(`<span>${pid.text}</span>`);
                },
                templateSelection: function (pid) {
                    return $(`<span>PID: ${pid.id}</span>`);
                }
            });
            // Rights filter
            const rightsSelect = $('#rightsSelectUser').select2({
                placeholder: sag_module.tt('cc_reports_30'),
                templateResult: function (right) {
                    return $(`<span>${right.text}</span>`);
                }
            });

            data.forEach(row => {
                // Users
                const userid = row.username;
                if (!usersSelect.find(`option[value='${userid}']`).length) {
                    const text = `<strong>${userid}</strong> (${row.name})`;
                    usersSelect.append(new Option(text, userid, false, false));
                }

                // SAGs
                const sag = row.sag;
                if (!sagsSelect.find(`option[value='${sag}']`).length) {
                    const text = `<strong>${sag}</strong> ${row.sag_name}`;
                    sagsSelect.append(new Option(text, sag, false, false));
                }

                // Projects
                row.projects.forEach(project => {
                    const pid = project.project_id;
                    if (!projectsSelect.find(`option[value='${pid}']`).length) {
                        const text =
                            `<strong>PID:${pid}</strong> ${project.project_title}`;
                        projectsSelect.append(new Option(text, pid, false, false));
                    }
                });

                // Rights
                row.bad_rights.forEach(right => {
                    if (!rightsSelect.find(`option[value='${right}']`).length) {
                        rightsSelect.append(new Option(right, right, false, false));
                    }
                });
            });

            $('.userTableSelect').trigger('change');
            $('.userTableSelect').on('change', () => table.draw());


            table.on('draw', function () {
                $('.dataTable tbody tr').each((i, row) => {
                    row.onmouseenter = sag_module.hover;
                    row.onmouseleave = sag_module.dehover;
                });
            });

            $('.dataTable tbody tr').each((i, row) => {
                row.onmouseenter = sag_module.hover;
                row.onmouseleave = sag_module.dehover;
            });
            table.columns.adjust().draw();
            $('div.dt-buttons button').removeClass(
                'dt-button');
            sag_module.setUpOrSearch(table);
        },
        buttons: [{
            extend: 'excelHtml5',
            text: `<i class="fa-sharp fa-solid fa-file-excel"></i> ${sag_module.tt('cc_reports_31')}`,
            exportOptions: {
                columns: [7, 1, 2, 11, 12, 13, 4, 8, 9, 10]
            },
            className: 'btn btn-sm btn-success border mb-1',
            title: 'UsersWithNoncompliantRights' + (includeExpired ? '_all_' :
                '_nonexpired_') +
                moment().format('YYYY-MM-DD_HHmmss'),
        }],
        dom: 'lBfrtip',
        columns: [
        // 0: User (Display)    
        {
            title: sag_module.tt('user'),
            data: function (row, type, set, meta) {
                if (type === 'display') {
                    const url =
                        `${app_path_webroot_full}redcap_v${redcap_version}/ControlCenter/view_users.php?username=${row.username}`;
                    return `<strong><a target="_blank" rel="noreferrer noopener" href="${url}">${row.username}</a></strong>` +
                        `<br><small>${row.name}</small>` +
                        `<br><small><a href="mailto:${row.email}">${row.email}</a></small>`;
                }
                return row.username;
            },
            width: '15%'
        },
        // 1: name
        {
            title: sag_module.tt('status_ui_60'),
            data: "name",
            visible: false
        },
        // 2: email
        {
            title: sag_module.tt('status_ui_61'),
            data: function (row, type, set, meta) {
                if (type === 'display') {
                    return '<a href="mailto:' + row.email + '">' + row.email +
                        '</a>';
                }
                return row.email;
            },
            visible: false
        },
        // 3: SAG (Display)
        {
            title: sag_module.tt('status_ui_63'),
            data: function (row, type, set, meta) {
                return `<strong>${row.sag_name}</strong><br><small>${row.sag}</small>`;
            },
            width: '20%'
        },
        // 4: Project (Display)
        {
            title: sag_module.tt('cc_reports_22'),
            data: function (row, type, set, meta) {
                if (type === 'display') {
                    return row.projects.length;
                } else {
                    return row.projects;
                }
            },
            width: '5%'
        },
        // 5: Projects (Display)
        {
            title: sag_module.tt('cc_reports_39'),
            data: function (row, type, set, meta) {
                const projects = row.projects ?? [];
                return projects.map(project => {
                    const pid = project.project_id;
                    const projectUrl =
                        `${app_path_webroot_full}redcap_v${redcap_version}/ExternalModules/?prefix={{MODULE_DIRECTORY_PREFIX}}&page=project-status&pid=${pid}`;
                    const projectTitle = project.project_title.replaceAll(
                        '"',
                        '');
                    return `${sag_module.getProjectStatusIcon(project.project_status)} <strong><a target="_blank" rel="noreferrer noopener" href="${projectUrl}">PID: ${pid}</a></strong> ${projectTitle}`;
                }).join("<br>");
            },
            width: '25%'
        },
        // 6: bad rights (Display)
        {
            title: sag_module.tt('cc_reports_21'),
            data: function (row, type, set, meta) {
                if (type === "display") {
                    return row.bad_rights.join('<br>');
                }
                return row.bad_rights.join('&&&&&');
            },
            width: '35%'
        },
        // 7: username
        {
            title: sag_module.tt('status_ui_59'),
            data: 'username',
            visible: false
        },
        // 8: project ids
        {
            title: sag_module.tt('cc_reports_39'),
            data: function (row, type, set, meta) {
                const projects = row.projects ?? [];
                return projects.map(project => project.project_id).join(", ");
            },
            visible: false
        },
        // 9: project titles
        {
            title: sag_module.tt('cc_reports_40'),
            data: function (row, type, set, meta) {
                const projects = row.projects ?? [];
                return projects.map(project => project.project_title).join(", ");
            },
            visible: false
        },
        // 10: project statuses
        {
            title: sag_module.tt('status_ui_2'),
            data: function (row, type, set, meta) {
                const projects = row.projects ?? [];
                const prefix = type === 'filter' ? 'project_status=' : '';
                return [...new Set(projects.map(project => prefix + project.project_status.label))].join(", ");
            },
            visible: false
        },
        // 11: bad rights
        {
            title: sag_module.tt('cc_reports_21'),
            data: function (row, type, set, meta) {
                return row.bad_rights.join(', ');
            },
            visible: false
        },
        // 12: sag id
        {
            title: sag_module.tt('sag'),
            data: 'sag',
            visible: false
        },
        // 13: sag name
        {
            title: sag_module.tt('cc_user_11'),
            data: 'sag_name',
            visible: false
        }

        ],
        columnDefs: [{
            "className": "dt-center dt-head-center SAG",
            "targets": "_all"
        }],
        language: {
            search: "_INPUT_",
            searchPlaceholder: sag_module.tt('dt_cc_reports_search_placeholder'),
            infoFiltered: " - " + sag_module.tt('dt_cc_reports_info_filtered', '_MAX_'),
            emptyTable: sag_module.tt('dt_cc_reports_empty_table'),
            info: sag_module.tt('dt_cc_reports_info', { start: '_START_', end: '_END_', total: '_TOTAL_' }),
            infoEmpty: sag_module.tt('dt_cc_reports_info_empty'),
            lengthMenu: sag_module.tt('dt_cc_reports_length_menu', '_MENU_'),
            loadingRecords: sag_module.tt('dt_cc_reports_loading_records'),
            zeroRecords: sag_module.tt('dt_cc_reports_zero_records'),
            decimal: sag_module.tt('dt_cc_reports_decimal'),
            thousands: sag_module.tt('dt_cc_reports_thousands'),
            select: {
                rows: {
                    _: sag_module.tt('dt_cc_reports_select_rows_other'),
                    0: sag_module.tt('dt_cc_reports_select_rows_zero'),
                    1: sag_module.tt('dt_cc_reports_select_rows_one')
                }
            },
            paginate: {
                first: sag_module.tt('dt_cc_reports_paginate_first'),
                last: sag_module.tt('dt_cc_reports_paginate_last'),
                next: sag_module.tt('dt_cc_reports_paginate_next'),
                previous: sag_module.tt('dt_cc_reports_paginate_previous')
            },
            aria: {
                sortAscending: sag_module.tt('dt_cc_reports_aria_sort_ascending'),
                sortDescending: sag_module.tt('dt_cc_reports_aria_sort_descending')
            }
        }
    });
}

// Users and Projects Table
sag_module.showUserAndProjectTable = function (includeExpired = false) {
    sag_module.clearTables();
    $('.tableWrapper').hide();
    $('#allTableTitle').text(sag_module.tt('cc_reports_42') + ' ' + (includeExpired ? sag_module.tt('cc_reports_25') : sag_module.tt('cc_reports_26')));
    if ($('#allTableWrapper').is(':hidden')) {
        Swal.fire({
            title: sag_module.tt('alerts_16'),
            didOpen: () => {
                Swal.showLoading()
            },
            confirmButtonText: sag_module.tt('ok'),
        });
    }
    $('#SAG-System-Table.allTable').DataTable({
        ajax: function (data, callback, settings) {
            sag_module.ajax('getUserAndProjectReport', { includeExpired: includeExpired })
                .then(function (data) {
                    callback(JSON.parse(data));
                })
                .catch(function (error) {
                    console.error(error);
                    callback({ data: [] });
                });
        },
        deferRender: true,
        initComplete: function () {
            $('#allTableWrapper').show();
            Swal.close();
            const table = this.api();
            const data = table.data().toArray();

            // Users filter
            const usersSelect = $('#usersSelectAll').select2({
                placeholder: sag_module.tt('cc_reports_27'),
                templateResult: function (user) {
                    return $(`<span>${user.text}</span>`);
                },
                templateSelection: function (user) {
                    return $(`<span>${user.id}</span>`);
                }
            });

            // SAG filter
            const sagsSelect = $('#sagsSelectAll').select2({
                placeholder: sag_module.tt('cc_reports_28'),
                templateResult: function (sag) {
                    return $(`<span>${sag.text}</span>`);
                },
                templateSelection: function (sag) {
                    return $(`<span>${sag.text}</span>`);
                }
            });

            // Project filter
            const projectsSelect = $('#projectsSelectAll').select2({
                placeholder: sag_module.tt('cc_reports_29'),
                templateResult: function (pid) {
                    return $(`<span>${pid.text}</span>`);
                },
                templateSelection: function (pid) {
                    return $(`<span>PID: ${pid.id}</span>`);
                }
            });

            // Rights filter
            const rightsSelect = $('#rightsSelectAll').select2({
                placeholder: sag_module.tt('cc_reports_30'),
                templateResult: function (right) {
                    return $(`<span>${right.text}</span>`);
                }
            });

            data.forEach(row => {
                // Users
                const userid = row.username;
                if (!usersSelect.find(`option[value='${userid}']`).length) {
                    const text = `<strong>${row.username}</strong> (${row.name})`;
                    usersSelect.append(new Option(text, userid, false,
                        false));
                }

                // SAGs
                const sag = row.sag;
                if (!sagsSelect.find(`option[value='${sag}']`).length) {
                    const text = `<strong>${sag}</strong> ${row.sag_name}`;
                    sagsSelect.append(new Option(text, sag, false, false));
                }

                // Projects
                const pid = row.project_id;
                if (!projectsSelect.find(`option[value='${pid}']`).length) {
                    const text = `<strong>PID:${pid}</strong> ${row.project_title}`;
                    projectsSelect.append(
                        new Option(text, pid, false, false)
                    );
                }
                // Rights
                row.bad_rights.forEach(right => {
                    if (!rightsSelect.find(`option[value='${right}']`).length) {
                        rightsSelect.append(new Option(right, right, false, false));
                    }
                });


            });

            $('.allTableSelect').trigger('change');
            $('.allTableSelect').on('change', () => table.draw());


            table.on('draw', function () {
                $('.dataTable tbody tr').each((i, row) => {
                    row.onmouseenter = sag_module.hover;
                    row.onmouseleave = sag_module.dehover;
                });
            });

            $('.dataTable tbody tr').each((i, row) => {
                row.onmouseenter = sag_module.hover;
                row.onmouseleave = sag_module.dehover;
            });
            table.columns.adjust().draw();
            $('div.dt-buttons button').removeClass('dt-button');

            sag_module.setUpOrSearch(table);
            console.timeEnd('report');
        },
        buttons: [{
            extend: 'excelHtml5',
            text: `<i class="fa-sharp fa-solid fa-file-excel"></i> ${sag_module.tt('cc_reports_31')}`,
            exportOptions: {
                columns: [6, 1, 2, 10, 11, 12, 7, 8, 9]
            },
            className: 'btn btn-sm btn-success border mb-1',
            title: 'UsersAndProjectsWithNoncompliantRights' + (includeExpired ?
                '_all_' :
                '_nonexpired_') +
                moment().format('YYYY-MM-DD_HHmmss'),
        }],
        dom: 'lBfrtip',
        columns: [
        // 0: User (Display)
        {
            title: sag_module.tt('user'),
            data: function (row, type, set, meta) {
                if (type === 'display') {
                    const url =
                        `${app_path_webroot_full}redcap_v${redcap_version}/ControlCenter/view_users.php?username=${row.username}`;
                    return `<strong><a target="_blank" rel="noreferrer noopener" href="${url}">${row.username}</a></strong>` +
                        `<br><small>${row.name}</small>` +
                        `<br><small><a href="mailto:${row.email}">${row.email}</a></small>`;
                }
                return row.username;
            },
            width: '20%'
        },
        // 1: name
        {
            title: sag_module.tt('status_ui_60'),
            data: "name",
            visible: false
        },
        // 2: email
        {
            title: sag_module.tt('status_ui_61'),
            data: function (row, type, set, meta) {
                if (type === 'display') {
                    return '<a href="mailto:' + row.email + '">' + row.email +
                        '</a>';
                }
                return row.email;
            },
            visible: false
        },
        // 3: SAG (Display)
        {
            title: sag_module.tt('status_ui_63'),
            data: function (row, type, set, meta) {
                return `<strong>${row.sag_name}</strong><br><small>${row.sag}</small>`;
            },
            width: '20%'
        },
        // 4: Project (Display)
        {
            title: sag_module.tt('cc_reports_41'),
            data: function (row, type, set, meta) {
                const pid = row.project_id;
                const projectUrl =
                    `${app_path_webroot_full}redcap_v${redcap_version}/ExternalModules/?prefix={{MODULE_DIRECTORY_PREFIX}}&page=project-status&pid=${pid}`;
                const projectTitle = row.project_title.replaceAll('"', '');
                return `<strong><a target="_blank" rel="noreferrer noopener" href="${projectUrl}">PID: ${pid}</a></strong> ${projectTitle}<br>${sag_module.getProjectStatusFormatted(row.project_status)}`;
            },
            width: '20%'
        },
        // 5: bad rights (Display)
        {
            title: sag_module.tt('cc_reports_21'),
            data: function (row, type, set, meta) {
                if (type === "display") {
                    return row.bad_rights.join('<br>');
                }
                return row.bad_rights.join('&&&&&');
            },
            width: '40%'
        },
        // 6: username
        {
            title: sag_module.tt('status_ui_59'),
            data: 'username',
            visible: false
        },
        // 7: project_id
        {
            title: sag_module.tt('cc_reports_33'),
            data: 'project_id',
            visible: false
        },
        // 8: project_title
        {
            title: sag_module.tt('cc_reports_34'),
            data: 'project_title',
            visible: false
        },
        // 9: project_status
        {
            title: sag_module.tt('status_ui_2'),
            data: function (row, type, set, meta) {
                if (type === 'filter') {
                    return 'project_status=' + row.project_status.label;
                }
                return row.project_status.label;
            },
            visible: false
        },
        // 10: bad_rights
        {
            title: sag_module.tt('cc_reports_21'),
            data: function (row, type, set, meta) {
                return row.bad_rights.join(', ');
            },
            visible: false
        },
        // 11: sag id
        {
            title: sag_module.tt('sag'),
            data: 'sag',
            visible: false
        },
        // 12: sag name
        {
            title: sag_module.tt('cc_user_11'),
            data: 'sag_name',
            visible: false
        }
        ],
        columnDefs: [{
            "className": "dt-center dt-head-center SAG",
            "targets": "_all"
        }],
        language: {
            search: "_INPUT_",
            searchPlaceholder: sag_module.tt('dt_cc_reports_search_placeholder'),
            infoFiltered: " - " + sag_module.tt('dt_cc_reports_info_filtered', '_MAX_'),
            emptyTable: sag_module.tt('dt_cc_reports_empty_table'),
            info: sag_module.tt('dt_cc_reports_info', { start: '_START_', end: '_END_', total: '_TOTAL_' }),
            infoEmpty: sag_module.tt('dt_cc_reports_info_empty'),
            lengthMenu: sag_module.tt('dt_cc_reports_length_menu', '_MENU_'),
            loadingRecords: sag_module.tt('dt_cc_reports_loading_records'),
            zeroRecords: sag_module.tt('dt_cc_reports_zero_records'),
            decimal: sag_module.tt('dt_cc_reports_decimal'),
            thousands: sag_module.tt('dt_cc_reports_thousands'),
            select: {
                rows: {
                    _: sag_module.tt('dt_cc_reports_select_rows_other'),
                    0: sag_module.tt('dt_cc_reports_select_rows_zero'),
                    1: sag_module.tt('dt_cc_reports_select_rows_one')
                }
            },
            paginate: {
                first: sag_module.tt('dt_cc_reports_paginate_first'),
                last: sag_module.tt('dt_cc_reports_paginate_last'),
                next: sag_module.tt('dt_cc_reports_paginate_next'),
                previous: sag_module.tt('dt_cc_reports_paginate_previous')
            },
            aria: {
                sortAscending: sag_module.tt('dt_cc_reports_aria_sort_ascending'),
                sortDescending: sag_module.tt('dt_cc_reports_aria_sort_descending')
            }
        }
    });
}

$(document).ready(function () {
    // Projects - filter user function
    $.fn.dataTable.ext.search.push(function (settings, data, dataIndex) {
        if (!settings.nTable.getAttribute('class').includes('projectTable')) {
            return true;
        }
        const users = $('#usersSelectProject').val() || [];
        if (users.length === 0) {
            return true;
        }
        const usersList = data[8].split(',').map(str => trim(str));
        return users.some(user => usersList.includes(user));
    });

    // Projects - filter sag function
    $.fn.dataTable.ext.search.push(function (settings, data, dataIndex) {
        if (!settings.nTable.getAttribute('class').includes('projectTable')) {
            return true;
        }
        const sags = $('#sagsSelectProject').val() || [];
        if (sags.length === 0) {
            return true;
        }
        const sagsList = data[9].split(',').map(str => trim(str));
        return sags.some(sag => sagsList.includes(sag));
    });

    // Projects - filter projects function
    $.fn.dataTable.ext.search.push(function (settings, data, dataIndex) {
        if (!settings.nTable.getAttribute('class').includes('projectTable')) {
            return true;
        }
        const projects = $('#projectsSelectProject').val() || [];
        if (projects.length === 0) {
            return true;
        }
        const project = data[5];
        return projects.includes(project);
    });

    // Projects - filter rights function
    $.fn.dataTable.ext.search.push(function (settings, data, dataIndex) {
        if (!settings.nTable.getAttribute('class').includes('projectTable')) {
            return true;
        }
        const rights = $('#rightsSelectProject').val() || [];
        if (rights.length === 0) {
            return true;
        }
        const rightsAll = data[4].split('&&&&&').map(str => trim(str));
        return rights.some(right => rightsAll.includes(right));
    });

    // Users - user filter function
    $.fn.dataTable.ext.search.push(function (settings, data, dataIndex) {
        if (!settings.nTable.getAttribute('class').includes('userTable')) {
            return true;
        }
        const users = $('#usersSelectUser').val() || [];
        if (users.length === 0) {
            return true;
        }
        const usersList = data[0].split(',').map(str => trim(str));
        return users.some(user => usersList.includes(user));
    });

    // Users - sag filter function
    $.fn.dataTable.ext.search.push(function (settings, data, dataIndex) {
        if (!settings.nTable.getAttribute('class').includes('userTable')) {
            return true;
        }
        const sags = $('#sagsSelectUser').val() || [];
        if (sags.length === 0) {
            return true;
        }
        const sag = data[12];
        return sags.includes(sag);
    });

    // Users - project filter function
    $.fn.dataTable.ext.search.push(function (settings, data, dataIndex) {
        if (!settings.nTable.getAttribute('class').includes('userTable')) {
            return true;
        }
        const projects = $('#projectsSelectUser').val() || [];
        if (projects.length === 0) {
            return true;
        }
        const projectsList = data[8].split(',').map(str => trim(str));
        return projects.some(project => projectsList.includes(project));
    });

    // Users - rights filter function
    $.fn.dataTable.ext.search.push(function (settings, data, dataIndex) {
        if (!settings.nTable.getAttribute('class').includes('userTable')) {
            return true;
        }
        const rights = $('#rightsSelectUser').val() || [];
        if (rights.length === 0) {
            return true;
        }
        const rightsAll = data[6].split('&&&&&').map(str => trim(str));
        return rights.some(right => rightsAll.includes(right));
    });

    // Users and Projects - user filter function
    $.fn.dataTable.ext.search.push(function (settings, data, dataIndex) {
        if (!settings.nTable.getAttribute('class').includes('allTable')) {
            return true;
        }
        const users = $('#usersSelectAll').val() || [];
        if (users.length === 0) {
            return true;
        }
        const user = data[0];
        return users.includes(user);
    });

    // Users and Projects - sag filter function
    $.fn.dataTable.ext.search.push(function (settings, data, dataIndex) {
        if (!settings.nTable.getAttribute('class').includes('allTable')) {
            return true;
        }
        const sags = $('#sagsSelectAll').val() || [];
        if (sags.length === 0) {
            return true;
        }
        const sag = data[11];
        return sags.includes(sag);
    });

    // Users and Projects - project filter function
    $.fn.dataTable.ext.search.push(function (settings, data, dataIndex) {
        if (!settings.nTable.getAttribute('class').includes('allTable')) {
            return true;
        }
        const projects = $('#projectsSelectAll').val() || [];
        if (projects.length === 0) {
            return true;
        }
        const projectId = data[7];
        return projects.includes(projectId);
    });

    // Users and Projects - rights filter function
    $.fn.dataTable.ext.search.push(function (settings, data, dataIndex) {
        if (!settings.nTable.getAttribute('class').includes('allTable')) {
            return true;
        }
        const rights = $('#rightsSelectAll').val() || [];
        if (rights.length === 0) {
            return true;
        }
        const rightsAll = data[5].split('&&&&&').map(str => trim(str));
        return rights.some(right => rightsAll.includes(right));
    });

    if ('scrollRestoration' in history) {
        history.scrollRestoration = 'manual';
    }
    console.log(performance.now());
    console.timeEnd('dt');
});