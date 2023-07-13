<?php

namespace YaleREDCap\SecurityAccessGroups;

/** @var SecurityAccessGroups $module */

if ( !$module->framework->getUser()->isSuperUser() ) {
    exit();
}
require_once APP_PATH_DOCROOT . 'ControlCenter/header.php';

?>
<link href="https://cdn.datatables.net/v/dt/jszip-3.10.1/dt-1.13.5/b-2.4.1/b-html5-2.4.1/datatables.min.css"
    rel="stylesheet" />
<script src="https://cdn.datatables.net/v/dt/jszip-3.10.1/dt-1.13.5/b-2.4.1/b-html5-2.4.1/datatables.min.js"></script>

<script defer src="<?= $module->framework->getUrl('assets/fontawesome/js/regular.min.js') ?>"></script>
<script defer src="<?= $module->framework->getUrl('assets/fontawesome/js/sharp-regular.min.js') ?>"></script>
<script defer src="<?= $module->framework->getUrl('assets/fontawesome/js/light.min.js') ?>"></script>
<script defer src="<?= $module->framework->getUrl('assets/fontawesome/js/sharp-light.min.js') ?>"></script>
<script defer src="<?= $module->framework->getUrl('assets/fontawesome/js/solid.min.js') ?>"></script>
<script defer src="<?= $module->framework->getUrl('assets/fontawesome/js/sharp-solid.min.js') ?>"></script>
<script defer src="<?= $module->framework->getUrl('assets/fontawesome/js/custom-icons.min.js') ?>"></script>
<script defer src="<?= $module->framework->getUrl('assets/fontawesome/js/fontawesome.min.js') ?>"></script>

<link href="<?= $module->framework->getUrl('lib/Select2/select2.min.css') ?>" rel="stylesheet" />
<script src="<?= $module->framework->getUrl('lib/Select2/select2.min.js') ?>"></script>
<script src="<?= $module->framework->getUrl('lib/SweetAlert/sweetalert2.all.min.js') ?>"></script>

<link rel='stylesheet' type='text/css' href='<?= $module->framework->getUrl('SecurityAccessGroups.css') ?>' />


<h4 style='color:#900; margin: 0 0 10px;'>
    <i class='fa-solid fa-users-between-lines'></i>&nbsp;<span>Security Access Groups</span>
</h4>
<p style='max-width:1000px; margin-bottom:0;font-size:14px;'>Security Access Groups (SAGs) are used to restrict which
    user rights a REDCap user can be granted in a project. SAGs do not define the rights a user will have in a given
    project; rather, they define the set of allowable rights the user is able to be granted. If a user is assigned to a
    SAG that does not allow the Project Design right, then that user cannot have that user right granted in a project.
    The Security Access Groups module must be enabled in a project for the SAG to have an effect.</p>
<div class="SAG_Container" style="min-width: 900px;">
    <div id="sub-nav" class="mr-4 mb-0 ml-0" style="min-width: 900px;">
        <ul>
            <li>
                <a href="<?= $module->framework->getUrl('system-settings.php?tab=userlist') ?>"
                    style="font-size:13px;color:#393733;padding:7px 9px;">
                    <i class="fa-solid fa-users"></i>
                    Users
                </a>
            </li>
            <li>
                <a href="<?= $module->framework->getUrl('system-settings.php?tab=sags') ?>"
                    style="font-size:13px;color:#393733;padding:7px 9px;">
                    <i class="fa-solid fa-user-tag"></i>
                    Security Access Groups
                </a>
            </li>
            <li class="active">
                <a href="<?= $module->framework->getUrl('system-reports.php') ?>"
                    style="font-size:13px;color:#393733;padding:7px 9px;">
                    <i class="fa-solid fa-memo"></i>
                    Reports
                </a>
            </li>
        </ul>
    </div>
    <div class="clear"></div>



    <p style='margin-top: 20px; margin-bottom: 10px; max-width:1000px;font-size:14px;'>Select a report type from the
        dropdown below. The
        report
        will be generated and displayed in a table below. You can then export the report to Excel by clicking the
        Export button.
    </p>

    <!-- Controls Container -->
    <div class="dropdown">
        <button type="button" class="btn btn-primary btn-xs border dropdown-toggle mr-2" data-toggle="dropdown"
            data-bs-toggle="dropdown" aria-expanded="false">
            <i class="fa-sharp fa-file-excel"></i>
            <span>Select Report Type</span>
            <span class="sr-only">Toggle Dropdown</span>
        </button>
        <ul class="dropdown-menu">
            <li><a class="dropdown-item" onclick="showUserTable(false);"><i
                        class="fa-light fa-users fa-fw mr-1 text-danger"></i>Users with
                    Noncompliant Rights (non-expired)</a></li>
            <li><a class="dropdown-item" onclick="showUserTable(true);"><i
                        class="fa-solid fa-users fa-fw mr-1 text-danger"></i>Users with
                    Noncompliant Rights (all)</a></li>
            <li><a class="dropdown-item" onclick="showProjectTable(false);"><i
                        class="fa-sharp fa-light fa-rectangle-history-circle-user fa-fw mr-1 text-successrc"></i>Projects
                    with Noncompliant Users (non-expired)</a></li>
            <li><a class="dropdown-item" onclick="showProjectTable(true);"><i
                        class="fa-sharp fa-solid fa-rectangle-history-circle-user fa-fw mr-1 text-successrc"></i>Projects
                    with Noncompliant Users (all)</a></li>
        </ul>
    </div>
    <!-- SAG Table -->
    <div class=" clear">
    </div>
    <div id="projectTableWrapper" class="mt-3" style="display: none; width: 100%;">
        <h5 id="projectTableTitle"></h5>
        <table aria-label="Projects Table" id="SUR-System-Table" class="projectTable cell-border" style="width: 100%">
            <thead>
                <tr style="background-color: #D7D7D7 !important;">
                    <th class="font-weight-normal" scope="col" colspan="10" style="border-bottom: none;">
                        <div class="container px-0">
                            <div class="row">
                                <div class="col px-4">
                                    <div class="row pt-2 pb-1 pl-1">
                                        <select style="width:100%" class="form-control" id="usersSelectProject"
                                            multiple="multiple">
                                        </select>
                                    </div>
                                </div>
                                <div class="col px-4" style="border-left: 1px solid #ccc">
                                    <div class="row pt-2 pb-1">
                                        <select style="width:100%" class="form-control" id="projectsSelectProject"
                                            multiple="multiple">
                                            <option></option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col px-4" style="border-left: 1px solid #ccc">
                                    <div class="row pt-2 pb-1 pr-1">
                                        <select style="width:100%" class="form-control" id="rightsSelectProject"
                                            multiple="multiple">
                                            <option></option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </th>
                </tr>
                <tr>
                    <th>Project ID</th>
                    <th>Project Name</th>
                    <th></th>
                    <th></th>
                    <th></th>
                    <th></th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            </tbody>
        </table>
    </div>
    <div id="userTableWrapper" class="mt-3" style="display: none; width: 100%;">
        <h5 id="userTableTitle"></h5>
        <table aria-label="Users Table" id="SUR-System-Table" class="userTable cell-border" style="width: 100%">
            <caption>Users with Noncompliant Rights</caption>
            <thead>
                <tr style="background-color: #D7D7D7 !important;">
                    <th class="font-weight-normal" scope="col" colspan="10" style="border-bottom: none;">
                        <div class="container px-0">
                            <div class="row">
                                <div class="col px-4">
                                    <div class="row pt-2 pb-1 pl-1">
                                        <select style="width:100%" class="form-control" id="projectsSelectUser"
                                            multiple="multiple">
                                        </select>
                                    </div>
                                </div>
                                <div class="col px-4" style="border-left: 1px solid #ccc">
                                    <div class="row pt-2 pb-1">
                                        <select style="width:100%" class="form-control" id="usersSelectUser"
                                            multiple="multiple">
                                            <option></option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col px-4" style="border-left: 1px solid #ccc">
                                    <div class="row pt-2 pb-1 pr-1">
                                        <select style="width:100%" class="form-control" id="rightsSelectUser"
                                            multiple="multiple">
                                            <option></option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </th>
                </tr>
                <tr>
                    <th>Username</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th></th>
                    <th></th>
                    <th></th>
                    <th></th>
                    <th></th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            </tbody>
        </table>
    </div>
    <style>
    div.dt-buttons {
        float: right;
    }

    .select2-search__field {
        width: 100% !important;
    }

    div.dataTables_filter {
        margin-top: 4px;
        margin-right: 10px;
    }
    </style>
    <script>
    function hover() {
        const thisNode = $(this);
        const rowIdx = thisNode.attr('data-dt-row');
        $("tr[data-dt-row='" + rowIdx + "'] td").addClass("highlight"); // shade only the hovered row
    }

    function dehover() {
        const thisNode = $(this);
        const rowIdx = thisNode.attr('data-dt-row');
        $("tr[data-dt-row='" + rowIdx + "'] td").removeClass("highlight"); // shade only the hovered row
    }

    function makeUserTable(usersString) {
        let tableString =
            '<table class=\"table table-sm table-bordered\"><thead><tr><th>Username</th><th>Name</th><th>Email</th></tr></thead><tbody>';
        JSON.parse(usersString).forEach(user => {
            tableString += '<tr><td>' + user.username + '</td><td>' + user.name + '</td><td>' + user.email +
                '</td></tr>';
        });
        tableString += '</tbody></table>';
        return Swal.fire({
            title: 'Users with Noncompliant Rights',
            html: tableString,
            width: '700px',
            showConfirmButton: false,
        });
    }

    function clearTables() {
        $('.dataTables_filter').remove();
        $('.dataTables_length').remove();
        $('.dataTables_info').remove();
        $('.dataTables_paginate').remove();
        $('.dt-buttons').remove();
        $('.dataTable').DataTable().destroy();
    }

    // Projects - filter user function
    $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
        if (!$('.projectTable').is(':visible')) {
            return true;
        }
        const users = $('#usersSelectProject').val() || [];
        if (users.length === 0) {
            return true;
        }
        const usersList = data[4].split(',').map(str => trim(str));
        return users.some(user => usersList.includes(user));
    });

    // Projects - filter projects function
    $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
        if (!$('.projectTable').is(':visible')) {
            return true;
        }
        const projects = $('#projectsSelectProject').val() || [];
        if (projects.length === 0) {
            return true;
        }
        const projectsList = data[0].split(',').map(str => trim(str));
        return projects.some(project => projectsList.includes(project));
    });

    // Projects - filter rights function
    $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
        if (!$('.projectTable').is(':visible')) {
            return true;
        }
        const rights = $('#rightsSelectProject').val() || [];
        if (rights.length === 0) {
            return true;
        }
        const rightsAll = data[5].split('&&&&&').map(str => trim(str));
        return rights.some(right => rightsAll.includes(right));
    });


    function showProjectTable(includeExpired) {
        clearTables();
        $('#userTableWrapper').hide();
        $('#projectTableWrapper').hide();
        $('#projectTableTitle').text('Projects with Noncompliant Users' + (includeExpired ?
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
        const table = $('#SUR-System-Table.projectTable')
            .DataTable({
                ajax: {
                    url: '<?= $module->framework->getUrl("ajax/projectsWithNoncompliantUsers.php") ?>',
                    method: 'POST',
                    data: {
                        includeExpired: includeExpired
                    }
                },
                deferRender: true,
                initComplete: function() {
                    $('#projectTableWrapper').show();
                    Swal.close();
                    const table = this.api();

                    // Users filter
                    const usersAll = table.data()
                        .toArray()
                        .map(row => row.users_with_bad_rights)
                        .flatten();
                    const usersIdsUnique = [];
                    const usersAllUnique = usersAll.filter((v) => {
                        if (usersIdsUnique.includes(v.username)) {
                            return false;
                        }
                        usersIdsUnique.push(v.username);
                        return true;
                    });
                    const usersSelect = $('#usersSelectProject').select2({
                        placeholder: "Filter users...",
                        templateResult: function(user) {
                            return $(`<span>${user.text}</span>`);
                        },
                        templateSelection: function(user) {
                            return $(`<span>${user.id}</span>`);
                        }
                    });
                    usersAllUnique.forEach(user => {
                        const text = `<strong>${user.username}</strong> (${user.name})`;
                        usersSelect.append(new Option(text, user.username, false, false));
                    });
                    usersSelect.trigger('change');
                    usersSelect.on('change', () => {
                        table.draw();
                    });

                    // Projects filter
                    const projects = table.column(0).data().toArray();
                    const projectsSelect = $('#projectsSelectProject').select2({
                        placeholder: "Filter projects...",
                        templateResult: function(project) {
                            return $(`<span>PID: ${project.text}</span>`);
                        }
                    });
                    projects.forEach(pid => projectsSelect.append(new Option(
                        pid, pid, false, false)));
                    projectsSelect.trigger('change');
                    projectsSelect.on('change', () => {
                        table.draw();
                    });

                    // Rights filter
                    const rights = table.column(5).data().toArray().join('&&&&&').split('&&&&&').map(str =>
                        trim(str));
                    const rightsUnique = Array.from(new Set(rights));
                    const rightsSelect = $('#rightsSelectProject').select2({
                        placeholder: "Filter rights...",
                        templateResult: function(right) {
                            return $(`<span>${right.text}</span>`);
                        }
                    });
                    rightsUnique.forEach(right => rightsSelect.append(new Option(
                        right, right, false, false)));
                    rightsSelect.trigger('change');
                    rightsSelect.on('change', () => {
                        table.draw();
                    });

                    table.on('draw', function() {
                        $('.dataTable tbody tr').each((i, row) => {
                            row.onmouseenter = hover;
                            row.onmouseleave = dehover;
                        });
                    });

                    $('.dataTable tbody tr').each((i, row) => {
                        row.onmouseenter = hover;
                        row.onmouseleave = dehover;
                    });
                    $('div.dt-buttons button').removeClass('dt-button');
                    table.columns.adjust().draw();
                },
                buttons: [{
                    extend: 'excelHtml5',
                    text: '<span style="font-size: .875rem;"><i class="fa-sharp fa-solid fa-file-excel fa-fw"></i>Export Excel</span>',
                    exportOptions: {
                        columns: [0, 1, 2, 4, 6]
                    },
                    className: 'btn btn-sm btn-success border mb-1',
                    title: 'ProjectsWithNoncompliantUsers' + (includeExpired ? '_all_' : '_nonexpired_') +
                        moment().format('YYYY-MM-DD_HHmmss'),
                }],
                dom: 'lBftip',

                columns: [{
                        title: "PID",
                        data: function(row, type, set, meta) {
                            if (type === 'display') {
                                const pid = row.project_id;
                                const projectUrl =
                                    `${app_path_webroot_full}redcap_v${redcap_version}/ExternalModules/?prefix=<?= $module->getModuleDirectoryPrefix() ?>&page=project-status&pid=${pid}`;
                                return `<strong><a target="_blank" rel="noreferrer noopener" href="${projectUrl}">${pid}</a></strong>`;
                            } else {
                                return row.project_id;
                            }
                        },
                        width: '5%'
                    },
                    {
                        title: "Project Name",
                        data: "project_title",
                        width: '20%'
                    },
                    {
                        title: "Count of Noncompliant Users",
                        data: function(row, type, set, meta) {
                            if (type === 'display') {
                                const users = row.users_with_bad_rights;
                                const usersString = JSON.stringify(users);
                                return '<a href="javascript:void(0)" onclick=\'makeUserTable(\`' +
                                    usersString + '\`);\'>' + users.length + '</a>';
                            }
                            return row.users_with_bad_rights;

                        },
                        width: '10%'
                    },
                    {
                        title: "Noncompliant Users",
                        data: function(row, type, set, meta) {
                            const users = row.users_with_bad_rights ?? [];
                            return users.map(user => {
                                const url =
                                    `${app_path_webroot_full}redcap_v${redcap_version}/ControlCenter/view_users.php?username=${user.username}`;
                                return `<strong><a target="_blank" rel="noreferrer noopener" href="${url}">${user.username}</a></strong>` +
                                    ` (${user.name})`;
                            }).join('<br>');
                        },
                        width: '15%'
                    },
                    {
                        title: "Noncompliant Users",
                        data: function(row, type, set, meta) {
                            const users = row.users_with_bad_rights ?? [];
                            return users.map(user => user.username).join(", ");
                        },
                        visible: false
                    },
                    {
                        title: "Noncompliant Rights",
                        data: function(row, type, set, meta) {
                            if (type === 'display') {
                                return row.bad_rights.join('<br>');
                            }
                            return row.bad_rights.join('&&&&&');
                        },
                        width: '50%'
                    },
                    {
                        title: "Noncompliant Rights",
                        data: function(row, type, set, meta) {
                            return row.bad_rights.join(', ');
                        },
                        visible: false
                    }
                ],
                columnDefs: [{
                    "className": "dt-center dt-head-center",
                    "targets": "_all"
                }],
            });
    }

    // Users - user filter function
    $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
        if (!$('.userTable').is(':visible')) {
            return true;
        }
        const users = $('#usersSelectUser').val() || [];
        if (users.length === 0) {
            return true;
        }
        const usersList = data[8].split(',').map(str => trim(str));
        return users.some(user => usersList.includes(user));
    });

    // Users - project filter function
    $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
        if (!$('.userTable').is(':visible')) {
            return true;
        }
        const projects = $('#projectsSelectUser').val() || [];
        if (projects.length === 0) {
            return true;
        }
        const projectsList = data[5].split(',').map(str => trim(str));
        return projects.some(project => projectsList.includes(project));
    });

    // Users - rights filter function
    $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
        if (!$('.userTable').is(':visible')) {
            return true;
        }
        const rights = $('#rightsSelectUser').val() || [];
        if (rights.length === 0) {
            return true;
        }
        const rightsAll = data[6].split('&&&&&').map(str => trim(str));
        return rights.some(right => rightsAll.includes(right));
    });







    // Users - make user table
    function showUserTable(includeExpired) {
        clearTables();
        $('#projectTableWrapper').hide();
        $('#userTableWrapper').hide();
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
        const table = $('#SUR-System-Table.userTable').DataTable({
            ajax: {
                url: '<?= $module->framework->getUrl("ajax/usersWithNoncompliantRights.php") ?>',
                method: 'POST',
                data: {
                    includeExpired: includeExpired
                }
            },
            deferRender: true,
            initComplete: function() {
                $('#projectTableWrapper').hide();
                $('#userTableWrapper').show();
                Swal.close();
                const table = this.api();

                // Users filter
                const usersSelect = $('#usersSelectUser').select2({
                    placeholder: "Filter users...",
                    templateResult: function(user) {
                        return $(`<span>${user.text}</span>`);
                    },
                    templateSelection: function(user) {
                        return $(`<span>${user.id}</span>`);
                    }
                });
                table.data()
                    .toArray()
                    .forEach(row => {
                        const text = `<strong>${row.username}</strong> (${row.name})`;
                        usersSelect.append(new Option(text, row.username, false, false));
                    });
                usersSelect.trigger('change');
                usersSelect.on('change', () => {
                    table.draw();
                });

                // Project filter
                const projectsAll = table.column(5).data().toArray().join().split(',').map(str => Number(
                    str)).filter(pid => pid)
                const projectsAllUnique = Array.from(new Set(projectsAll));
                const projectsSelect = $('#projectsSelectUser').select2({
                    placeholder: "Filter projects...",
                    templateResult: function(pid) {
                        return $(`<span>PID: ${pid.text}</span>`);
                    }
                });
                projectsAllUnique.forEach(pid => projectsSelect.append(new Option(
                    pid, pid, false, false)));
                projectsSelect.trigger('change');
                projectsSelect.on('change', () => {
                    table.draw();
                });

                // Rights filter
                const rights = table.column(6).data().toArray().join('&&&&&').split('&&&&&').map(str =>
                    trim(str));
                const rightsUnique = Array.from(new Set(rights));
                const rightsSelect = $('#rightsSelectUser').select2({
                    placeholder: "Filter rights...",
                    templateResult: function(right) {
                        return $(`<span>${right.text}</span>`);
                    }
                });
                rightsUnique.forEach(right => rightsSelect.append(new Option(
                    right, right, false, false)));
                rightsSelect.trigger('change');
                rightsSelect.on('change', () => {
                    table.draw();
                });

                table.on('draw', function() {
                    $('.dataTable tbody tr').each((i, row) => {
                        row.onmouseenter = hover;
                        row.onmouseleave = dehover;
                    });
                });

                $('.dataTable tbody tr').each((i, row) => {
                    row.onmouseenter = hover;
                    row.onmouseleave = dehover;
                });
                table.columns.adjust().draw();
                $('div.dt-buttons button').removeClass('dt-button');
            },
            buttons: [{
                extend: 'excelHtml5',
                text: '<i class="fa-sharp fa-solid fa-file-excel"></i> Export Excel',
                exportOptions: {
                    columns: [8, 1, 2, 3, 5, 7]
                },
                className: 'btn btn-sm btn-success border mb-1',
                title: 'UsersWithNoncompliantRights' + (includeExpired ? '_all_' : '_nonexpired_') +
                    moment().format('YYYY-MM-DD_HHmmss'),
                messageTop: 'Users with noncompliant rights',
            }],
            dom: 'lBfrtip',
            columns: [{
                    title: "User",
                    data: function(row, type, set, meta) {
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
                    data: function(row, type, set, meta) {
                        if (type === 'display') {
                            return '<a href="mailto:' + row.email + '">' + row.email + '</a>';
                        }
                        return row.email;
                    },
                    visible: false
                },
                {
                    title: "Count of Projects granting Noncompliant Rights to this User",
                    data: function(row, type, set, meta) {
                        if (type === 'display') {
                            return row.projects.length;
                        } else {
                            return row.projects;
                        }
                    },
                    width: '10%',
                    visible: true
                },
                {
                    title: "Projects granting Noncompliant Rights to this User",
                    data: function(row, type, set, meta) {
                        const projects = row.projects ?? [];
                        return projects.map(project => {
                            const pid = project.project_id;
                            const projectUrl =
                                `${app_path_webroot_full}redcap_v${redcap_version}/ExternalModules/?prefix=<?= $module->getModuleDirectoryPrefix() ?>&page=project-status&pid=${pid}`;
                            const projectTitle = project.project_title.replaceAll('"', '');
                            return `<a target="_blank" rel="noreferrer noopener" href="${projectUrl}" title="${projectTitle}">PID: ${pid}</a>`;
                        }).join("<br>");
                    },
                    width: '10%'
                },
                {
                    title: "Projects granting Noncompliant Rights to this User",
                    data: function(row, type, set, meta) {
                        const projects = row.projects ?? [];
                        return projects.map(project => project.project_id).join(", ");
                    },
                    visible: false
                },
                {
                    title: 'Noncompliant Rights',
                    data: function(row, type, set, meta) {
                        if (type === "display") {
                            return row.bad_rights.join('<br>');
                        }
                        return row.bad_rights.join('&&&&&');
                    },
                    width: '40%'
                },
                {
                    title: 'Noncompliant Rights',
                    data: function(row, type, set, meta) {
                        return row.bad_rights.join(', ');
                    },
                    visible: false
                },
                {
                    title: 'Username',
                    data: 'username',
                    visible: false
                }
            ],
            columnDefs: [{
                "className": "dt-center dt-head-center",
                "targets": "_all"
            }],
        });
    }
    </script>

</div> <!-- End SAG_Container -->