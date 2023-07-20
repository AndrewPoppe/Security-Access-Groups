const module = __MODULE__;

module.hover = function () {
    const thisNode = $(this);
    const rowIdx = thisNode.attr('data-dt-row');
    $("tr[data-dt-row='" + rowIdx + "'] td").addClass("highlight"); // shade only the hovered row
}

module.dehover = function () {
    const thisNode = $(this);
    const rowIdx = thisNode.attr('data-dt-row');
    $("tr[data-dt-row='" + rowIdx + "'] td").removeClass("highlight"); // shade only the hovered row
}

module.makeUserTable = function (usersString) {
    let tableString =
        '<table class="table table-sm table-bordered"><thead><tr><th>Username</th><th>Name</th><th>Email</th><th>Security Access Group</th></tr></thead><tbody>';
    JSON.parse(usersString).forEach(user => {
        tableString +=
            `<tr><td>${user.username}</td><td>${user.name}</td><td>${user.email}</td><td><strong>${user.sag}</strong><br><small>${user.sag_name}</small></td></tr>`;
    });
    tableString += '</tbody></table>';
    return Swal.fire({
        title: 'Users with Noncompliant Rights',
        html: tableString,
        width: '900px',
        showConfirmButton: false,
    });
}

module.clearTables = function () {
    $('.dataTables_filter').remove();
    $('.dataTables_length').remove();
    $('.dataTables_info').remove();
    $('.dataTables_paginate').remove();
    $('.dt-buttons').remove();
    $('.dataTable').DataTable().destroy();
    $('.tableSelect').empty();
}


// Projects Table
module.showProjectTable = function (includeExpired = false) {
    module.clearTables();
    $('.tableWrapper').hide();
    $('#projectTableTitle').text('Projects with Noncompliant Rights' + (includeExpired ?
        ' (including expired users)' :
        ' (excluding expired users)'));
    if ($('#projectTableWrapper').is(':hidden')) {
        Swal.fire({
            title: 'Loading...',
            didOpen: () => {
                Swal.showLoading()
            }
        });
    }
    $('#SUR-System-Table.projectTable').DataTable({
        ajax: function (data, callback, settings) {
            module.ajax('getProjectReport', { includeExpired: includeExpired })
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
                placeholder: "Filter users...",
                templateResult: function (user) {
                    return $(`<span>${user.text}</span>`);
                },
                templateSelection: function (user) {
                    return $(`<span>${user.id}</span>`);
                }
            });

            // SAGs filter
            const sagsSelect = $('#sagsSelectProject').select2({
                placeholder: "Filter SAGs...",
                templateResult: function (sag) {
                    return $(`<span>${sag.text}</span>`);
                },
                templateSelection: function (sag) {
                    return $(`<span>${sag.text}</span>`);
                }
            });

            // Projects filter
            const projectsSelect = $('#projectsSelectProject').select2({
                placeholder: "Filter projects...",
                templateResult: function (project) {
                    return $(`<span>${project.text}</span>`);
                },
                templateSelection: function (project) {
                    return $(`<span>PID: ${project.id}</span>`);
                }
            });

            // Rights filter
            const rightsSelect = $('#rightsSelectProject').select2({
                placeholder: "Filter rights...",
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
                    row.onmouseenter = module.hover;
                    row.onmouseleave = module.dehover;
                });
            });

            $('.dataTable tbody tr').each((i, row) => {
                row.onmouseenter = module.hover;
                row.onmouseleave = module.dehover;
            });
            $('div.dt-buttons button').removeClass('dt-button');
            table.columns.adjust().draw();
        },
        buttons: [{
            extend: 'excelHtml5',
            text: '<span style="font-size: .875rem;"><i class="fa-sharp fa-solid fa-file-excel fa-fw"></i>Export Excel</span>',
            exportOptions: {
                columns: [5, 6, 1, 7, 8, 9, 10]
            },
            className: 'btn btn-sm btn-success border mb-1',
            title: 'ProjectsWithNoncompliantRights' + (includeExpired ? '_all_' : '_nonexpired_') +
                moment().format('YYYY-MM-DD_HHmmss'),
        }],
        dom: 'lBftip',

        columns: [{
            title: "Project",
            data: function (row, type, set, meta) {
                const pid = row.project_id;
                const projectUrl =
                    `${app_path_webroot_full}redcap_v${redcap_version}/ExternalModules/?prefix={{MODULE_DIRECTORY_PREFIX}}&page=project-status&pid=${pid}`;
                const projectTitle = row.project_title.replaceAll('"', '');
                return `<strong><a target="_blank" rel="noreferrer noopener" href="${projectUrl}">PID: ${pid}</a></strong><br>${projectTitle}`;
            },
            width: '20%'
        },
        {
            title: "Count of Users",
            data: function (row, type, set, meta) {
                const users = row.users_with_bad_rights;
                const usersString = JSON.stringify(users);
                return '<a href="javascript:void(0)" onclick=\'module.makeUserTable(`' +
                    usersString + '`);\'>' + users.length + '</a>';
            },
            width: '5%'
        },
        {
            title: "Noncompliant Users",
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
        {
            title: "Security Access Groups",
            data: function (row, type, set, meta) {
                const sags = row.sags ?? [];
                return sags.map(sag => {
                    return `<strong>${sag.sag_name}</strong> <small>${sag.sag}</small>`;
                }).join('<br>');
            },
            width: '20%'
        },
        {
            title: "Noncompliant Rights",
            data: function (row, type, set, meta) {
                if (type === 'display') {
                    return row.bad_rights.join('<br>');
                }
                return row.bad_rights.join('&&&&&');
            },
            width: '35%'
        },
        {
            title: "Project ID",
            data: "project_id",
            visible: false
        },
        {
            title: "Project Title",
            data: "project_title",
            visible: false
        },
        {
            title: "Noncompliant Users",
            data: function (row, type, set, meta) {
                const users = row.users_with_bad_rights ?? [];
                return users.map(user => user.username).join(", ");
            },
            visible: false
        },
        {
            title: "SAG IDs",
            data: function (row, type, set, meta) {
                const sags = row.sags ?? [];
                return sags.map(sag => sag.sag).join(", ");
            },
            visible: false
        },
        {
            title: "SAG Names",
            data: function (row, type, set, meta) {
                const sags = row.sags ?? [];
                return sags.map(sag => sag.sag_name).join(", ");
            },
            visible: false
        },
        {
            title: "Noncompliant Rights",
            data: function (row, type, set, meta) {
                return row.bad_rights.join(', ');
            },
            visible: false
        }
        ],
        columnDefs: [{
            "className": "dt-center dt-head-center",
            "targets": "_all"
        }],
        language: {
            searchPlaceholder: "Search...",
            search: "",
        }
    });
}

// Users Table
module.showUserTable = function (includeExpired = false) {
    module.clearTables();
    $('.tableWrapper').hide();
    $('#userTableTitle').text('Users with Noncompliant Rights' + (includeExpired ? ' (including expired users)' :
        ' (excluding expired users)'));
    if ($('#userTableWrapper').is(':hidden')) {
        Swal.fire({
            title: 'Loading...',
            didOpen: () => {
                Swal.showLoading()
            }
        });
    }
    $('#SUR-System-Table.userTable').DataTable({
        ajax: function (data, callback, settings) {
            module.ajax('getUserReport', { includeExpired: includeExpired })
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
                placeholder: "Filter users...",
                templateResult: function (user) {
                    return $(`<span>${user.text}</span>`);
                },
                templateSelection: function (user) {
                    return $(`<span>${user.id}</span>`);
                }
            });

            // SAG filter
            const sagsSelect = $('#sagsSelectUser').select2({
                placeholder: "Filter SAGs...",
                templateResult: function (sag) {
                    return $(`<span>${sag.text}</span>`);
                },
                templateSelection: function (sag) {
                    return $(`<span>${sag.text}</span>`);
                }
            });
            // Project filter
            const projectsSelect = $('#projectsSelectUser').select2({
                placeholder: "Filter projects...",
                templateResult: function (pid) {
                    return $(`<span>${pid.text}</span>`);
                },
                templateSelection: function (pid) {
                    return $(`<span>PID: ${pid.id}</span>`);
                }
            });
            // Rights filter
            const rightsSelect = $('#rightsSelectUser').select2({
                placeholder: "Filter rights...",
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
                    row.onmouseenter = module.hover;
                    row.onmouseleave = module.dehover;
                });
            });

            $('.dataTable tbody tr').each((i, row) => {
                row.onmouseenter = module.hover;
                row.onmouseleave = module.dehover;
            });
            table.columns.adjust().draw();
            $('div.dt-buttons button').removeClass(
                'dt-button');
        },
        buttons: [{
            extend: 'excelHtml5',
            text: '<i class="fa-sharp fa-solid fa-file-excel"></i> Export Excel',
            exportOptions: {
                columns: [7, 1, 2, 11, 12, 4, 8, 9, 10]
            },
            className: 'btn btn-sm btn-success border mb-1',
            title: 'UsersWithNoncompliantRights' + (includeExpired ? '_all_' :
                '_nonexpired_') +
                moment().format('YYYY-MM-DD_HHmmss'),
        }],
        dom: 'lBfrtip',
        columns: [{
            title: "User",
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
        {
            title: "Name",
            data: "name",
            visible: false
        },
        {
            title: "Email",
            data: function (row, type, set, meta) {
                if (type === 'display') {
                    return '<a href="mailto:' + row.email + '">' + row.email +
                        '</a>';
                }
                return row.email;
            },
            visible: false
        },
        {
            title: "Security Access Group",
            data: function (row, type, set, meta) {
                return `<strong>${row.sag_name}</strong><br><small>${row.sag}</small>`;
            },
            width: '20%'
        },
        {
            title: "Count of Projects",
            data: function (row, type, set, meta) {
                if (type === 'display') {
                    return row.projects.length;
                } else {
                    return row.projects;
                }
            },
            width: '5%'
        },
        {
            title: "Projects granting Noncompliant Rights to this User",
            data: function (row, type, set, meta) {
                const projects = row.projects ?? [];
                return projects.map(project => {
                    const pid = project.project_id;
                    const projectUrl =
                        `${app_path_webroot_full}redcap_v${redcap_version}/ExternalModules/?prefix={{MODULE_DIRECTORY_PREFIX}}&page=project-status&pid=${pid}`;
                    const projectTitle = project.project_title.replaceAll(
                        '"',
                        '');
                    return `<strong><a target="_blank" rel="noreferrer noopener" href="${projectUrl}">PID: ${pid}</a></strong> ${projectTitle}`;
                }).join("<br>");
            },
            width: '25%'
        },
        {
            title: 'Noncompliant Rights',
            data: function (row, type, set, meta) {
                if (type === "display") {
                    return row.bad_rights.join('<br>');
                }
                return row.bad_rights.join('&&&&&');
            },
            width: '35%'
        },
        {
            title: 'Username',
            data: 'username',
            visible: false
        },
        {
            title: "Projects granting Noncompliant Rights to this User",
            data: function (row, type, set, meta) {
                const projects = row.projects ?? [];
                return projects.map(project => project.project_id).join(", ");
            },
            visible: false
        },
        {
            title: "Project Titles",
            data: function (row, type, set, meta) {
                const projects = row.projects ?? [];
                return projects.map(project => project.project_title).join(", ");
            },
            visible: false
        },
        {
            title: 'Noncompliant Rights',
            data: function (row, type, set, meta) {
                return row.bad_rights.join(', ');
            },
            visible: false
        },
        {
            title: 'SAG',
            data: 'sag',
            visible: false
        },
        {
            title: 'SAG Name',
            data: 'sag_name',
            visible: false
        }

        ],
        columnDefs: [{
            "className": "dt-center dt-head-center",
            "targets": "_all"
        }],
        language: {
            searchPlaceholder: "Search...",
            search: "",
        }
    });
}

// Users and Projects Table
module.showUserAndProjectTable = function (includeExpired = false) {
    module.clearTables();
    $('.tableWrapper').hide();
    $('#allTableTitle').text('Users and Projects with Noncompliant Rights' + (includeExpired ?
        ' (including expired users)' :
        ' (excluding expired users)'));
    if ($('#allTableWrapper').is(':hidden')) {
        Swal.fire({
            title: 'Loading...',
            didOpen: () => {
                Swal.showLoading()
            }
        });
    }
    $('#SUR-System-Table.allTable').DataTable({
        ajax: function (data, callback, settings) {
            module.ajax('getUserAndProjectReport', { includeExpired: includeExpired })
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
                placeholder: "Filter users...",
                templateResult: function (user) {
                    return $(`<span>${user.text}</span>`);
                },
                templateSelection: function (user) {
                    return $(`<span>${user.id}</span>`);
                }
            });

            // SAG filter
            const sagsSelect = $('#sagsSelectAll').select2({
                placeholder: "Filter SAGs...",
                templateResult: function (sag) {
                    return $(`<span>${sag.text}</span>`);
                },
                templateSelection: function (sag) {
                    return $(`<span>${sag.text}</span>`);
                }
            });

            // Project filter
            const projectsSelect = $('#projectsSelectAll').select2({
                placeholder: "Filter projects...",
                templateResult: function (pid) {
                    return $(`<span>${pid.text}</span>`);
                },
                templateSelection: function (pid) {
                    return $(`<span>PID: ${pid.id}</span>`);
                }
            });

            // Rights filter
            const rightsSelect = $('#rightsSelectAll').select2({
                placeholder: "Filter rights...",
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
                    row.onmouseenter = module.hover;
                    row.onmouseleave = module.dehover;
                });
            });

            $('.dataTable tbody tr').each((i, row) => {
                row.onmouseenter = module.hover;
                row.onmouseleave = module.dehover;
            });
            table.columns.adjust().draw();
            $('div.dt-buttons button').removeClass('dt-button');
        },
        buttons: [{
            extend: 'excelHtml5',
            text: '<i class="fa-sharp fa-solid fa-file-excel"></i> Export Excel',
            exportOptions: {
                columns: [6, 1, 2, 10, 11, 7, 8, 9]
            },
            className: 'btn btn-sm btn-success border mb-1',
            title: 'UsersAndProjectsWithNoncompliantRights' + (includeExpired ?
                '_all_' :
                '_nonexpired_') +
                moment().format('YYYY-MM-DD_HHmmss'),
        }],
        dom: 'lBfrtip',
        columns: [{
            title: "User",
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
        {
            title: "Name",
            data: "name",
            visible: false
        },
        {
            title: "Email",
            data: function (row, type, set, meta) {
                if (type === 'display') {
                    return '<a href="mailto:' + row.email + '">' + row.email +
                        '</a>';
                }
                return row.email;
            },
            visible: false
        },
        {
            title: "Security Access Group",
            data: function (row, type, set, meta) {
                return `<strong>${row.sag_name}</strong><br><small>${row.sag}</small>`;
            },
            width: '20%'
        },
        {
            title: "Project granting Noncompliant Rights to this User",
            data: function (row, type, set, meta) {
                const pid = row.project_id;
                const projectUrl =
                    `${app_path_webroot_full}redcap_v${redcap_version}/ExternalModules/?prefix={{MODULE_DIRECTORY_PREFIX}}&page=project-status&pid=${pid}`;
                const projectTitle = row.project_title.replaceAll('"', '');
                return `<strong><a target="_blank" rel="noreferrer noopener" href="${projectUrl}">PID: ${pid}</a></strong> ${projectTitle}`;
            },
            width: '20%'
        },
        {
            title: 'Noncompliant Rights',
            data: function (row, type, set, meta) {
                if (type === "display") {
                    return row.bad_rights.join('<br>');
                }
                return row.bad_rights.join('&&&&&');
            },
            width: '40%'
        },
        {
            title: 'Username',
            data: 'username',
            visible: false
        },
        {
            title: "Project ID",
            data: 'project_id',
            visible: false
        },
        {
            title: "Project Title",
            data: 'project_title',
            visible: false
        },
        {
            title: 'Noncompliant Rights',
            data: function (row, type, set, meta) {
                return row.bad_rights.join(', ');
            },
            visible: false
        },
        {
            title: 'SAG',
            data: 'sag',
            visible: false
        },
        {
            title: 'SAG Name',
            data: 'sag_name',
            visible: false
        }
        ],
        columnDefs: [{
            "className": "dt-center dt-head-center",
            "targets": "_all"
        }],
        language: {
            searchPlaceholder: "Search...",
            search: "",
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
        const usersList = data[7].split(',').map(str => trim(str));
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
        const sagsList = data[8].split(',').map(str => trim(str));
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
        const sag = data[11];
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
        const sag = data[10];
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
});