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
        dropdown below. The report will be generated and displayed in a table below. You can then export the report to
        Excel by clicking the <em>Export Excel</em> button.

    <div class="collapse collapse-group-1 show" id="helpLinkContainer">
        <button class="btn btn-link p-0" data-target=".collapse-group-1" aria-controls="helpLinkContainer help"
            data-toggle="collapse"><small id="helpLinkText">More...</small></button>
    </div>
    </p>
    <div id="help" class="collapse collapse-group-1 mb-2">
        <span></span>
        <strong><i class="fa-light fa-users fa-fw mr-1 text-danger"></i>Users with Noncompliant Rights
            (non-expired)</strong>
        <br>
        This report lists all users who are assigned to a SAG that does not allow the user to be granted all of the
        rights they currently have in a project. This report only includes users if they are not currently expired
        in the project(s).
        <br>
        <br>
        <strong><i class="fa-solid fa-users fa-fw mr-1 text-danger"></i>Users with Noncompliant Rights
            (all)</strong>
        <br>
        This report lists all users who are assigned to a SAG that does not allow the user to be granted all of the
        rights they currently have in a project. This report includes all users, regardless of whether they are
        currently expired in the project(s).
        <br>
        <br>
        <strong><i class="fa-sharp fa-light fa-rectangle-history-circle-user fa-fw mr-1 text-successrc"></i>Projects
            with Noncompliant Rights (non-expired)</strong>
        <br>
        This report lists all projects that have at least one user who is assigned to a SAG that does not allow the
        user to be granted all of the rights they currently have in the project. This report only includes users
        who have a non-expired user account.
        <br>
        <br>
        <strong><i class="fa-sharp fa-solid fa-rectangle-history-circle-user fa-fw mr-1 text-successrc"></i>Projects
            with Noncompliant Rights (all)</strong>
        <br>
        This report lists all projects that have at least one user who is assigned to a SAG that does not allow the
        user to be granted all of the rights they currently have in the project. This report includes all users,
        regardless of whether their user account is expired.
        <br>
        <br>
        <strong><i class="fa-sharp fa-light fa-rectangle-list fa-fw mr-1 text-info"></i>Users and Projects with
            Noncompliant Rights (non-expired)</strong>
        <br>
        This report lists every user and project combination in which the user is assigned to a SAG that does not
        allow the user to be granted all of the rights they currently have in the project. This report only
        includes users who are not currently expired in the project.
        <br>
        <br>
        <strong><i class="fa-sharp fa-solid fa-rectangle-list fa-fw mr-1 text-info"></i>Users and Projects with
            Noncompliant Rights (all)</strong>
        <br>
        This report lists every user and project combination in which the user is assigned to a SAG that does not
        allow the user to be granted all of the rights they currently have in the project. This report includes
        all users, regardless of whether they are currently expired in the project.
        <br>
        <br>
        <button class="btn btn-link p-0" data-target=".collapse-group-1" aria-controls="helpLinkContainer help"
            data-toggle="collapse"><small id="helpLinkText">Less...</small></button>
        </span>
    </div>

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
                    with Noncompliant Rights (non-expired)</a></li>
            <li><a class="dropdown-item" onclick="showProjectTable(true);"><i
                        class="fa-sharp fa-solid fa-rectangle-history-circle-user fa-fw mr-1 text-successrc"></i>Projects
                    with Noncompliant Rights (all)</a></li>
            <li><a class="dropdown-item" onclick="showUserAndProjectTable(false);"><i
                        class="fa-sharp fa-light fa-rectangle-list fa-fw mr-1 text-info"></i>Users
                    and Projects with Noncompliant Rights (non-expired)</a></li>
            <li><a class="dropdown-item" onclick="showUserAndProjectTable(true);"><i
                        class="fa-sharp fa-solid fa-rectangle-list fa-fw mr-1 text-info"></i>Users
                    and Projects with Noncompliant Rights (all)</a></li>
        </ul>
    </div>
    <!-- SAG Table -->
    <div class=" clear">
    </div>
    <div id="projectTableWrapper" class="tableWrapper mt-3 card p-3" style="display: none; width: 100%;">
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
                                    <div class="row pt-2 pb-1 pr-1">
                                        <select style="width:100%" class="form-control" id="sagsSelectProject"
                                            multiple="multiple">
                                            <option></option>
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
                    <!--0-->
                    <th scope="col">Project</th>
                    <!--1-->
                    <th scope="col">Count of Noncompliant Users</th>
                    <!--2-->
                    <th scope="col">Noncompliant Users</th>
                    <!--3-->
                    <th scope="col">Security Access Groups</th>
                    <!--4-->
                    <th scope="col">Noncompliant Rights</th>
                    <!--5-->
                    <th scope="col">PID for CSV</th>
                    <!--6-->
                    <th scope="col">Project Title for CSV</th>
                    <!--7-->
                    <th scope="col">Usernames for CSV</th>
                    <!--8-->
                    <th scope="col">SAG IDs for CSV</th>
                    <!--9-->
                    <th scope="col">SAG Names for CSV</th>
                    <!--10-->
                    <th scope="col">Rights for CSV</th>
                </tr>
            </thead>
            <tbody>
            </tbody>
        </table>
    </div>
    <div id="userTableWrapper" class="tableWrapper mt-3 card p-3" style="display: none; width: 100%;">
        <h5 id="userTableTitle"></h5>
        <table aria-label="Users Table" id="SUR-System-Table" class="userTable cell-border" style="width: 100%">
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
                                    <div class="row pt-2 pb-1 pr-1">
                                        <select style="width:100%" class="form-control" id="sagsSelectUser"
                                            multiple="multiple">
                                            <option></option>
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
                    <!--0-->
                    <th scope="col">User</th>
                    <!--1-->
                    <th scope="col">Name</th>
                    <!--2-->
                    <th scope="col">Email</th>
                    <!--3-->
                    <th scope="col">Security Access Group</th>
                    <!--4-->
                    <th scope="col">Count of Projects</th>
                    <!--5-->
                    <th scope="col">Projects</th>
                    <!--6-->
                    <th scope="col">Noncompliant Rights</th>
                    <!--7-->
                    <th scope="col">Username for CSV</th>
                    <!--8-->
                    <th scope="col">PIDs for CSV</th>
                    <!--9-->
                    <th scope="col">Project Titles for CSV</th>
                    <!--10-->
                    <th scope="col">Rights for CSV</th>
                    <!--11-->
                    <th scope="col">SAG ID for CSV</th>
                    <!--12-->
                    <th scope="col">SAG Name for CSV</th>
                </tr>
            </thead>
            <tbody>
            </tbody>
        </table>
    </div>
    <div id="allTableWrapper" class="tableWrapper mt-3 card p-3" style="display: none; width: 100%;">
        <h5 id="allTableTitle"></h5>
        <table aria-label="Users and Projects Table" id="SUR-System-Table" class="allTable cell-border"
            style="width: 100%">
            <thead>
                <tr style="background-color: #D7D7D7 !important;">
                    <th class="font-weight-normal" scope="col" colspan="10" style="border-bottom: none;">
                        <div class="container px-0">
                            <div class="row">
                                <div class="col px-4">
                                    <div class="row pt-2 pb-1 pl-1">
                                        <select style="width:100%" class="form-control" id="projectsSelectAll"
                                            multiple="multiple">
                                        </select>
                                    </div>
                                </div>
                                <div class="col px-4" style="border-left: 1px solid #ccc">
                                    <div class="row pt-2 pb-1 pr-1">
                                        <select style="width:100%" class="form-control" id="sagsSelectAll"
                                            multiple="multiple">
                                            <option></option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col px-4" style="border-left: 1px solid #ccc">
                                    <div class="row pt-2 pb-1">
                                        <select style="width:100%" class="form-control" id="usersSelectAll"
                                            multiple="multiple">
                                            <option></option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col px-4" style="border-left: 1px solid #ccc">
                                    <div class="row pt-2 pb-1 pr-1">
                                        <select style="width:100%" class="form-control" id="rightsSelectAll"
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
                    <!--0-->
                    <th scope="col">User</th>
                    <!--1-->
                    <th scope="col">Name</th>
                    <!--2-->
                    <th scope="col">Email</th>
                    <!--3-->
                    <th scope="col">Security Access Group</th>
                    <!--4-->
                    <th scope="col">Project</th>
                    <!--5-->
                    <th scope="col">Noncompliant Rights</th>
                    <!--6-->
                    <th scope="col">Username for CSV</th>
                    <!--7-->
                    <th scope="col">PID for CSV</th>
                    <!--8-->
                    <th scope="col">Project Title for CSV</th>
                    <!--9-->
                    <th scope="col">Rights for CSV</th>
                    <!--10-->
                    <th scope="col">SAG ID for CSV</th>
                    <!--11-->
                    <th scope="col">SAG Name for CSV</th>
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
            '<table class=\"table table-sm table-bordered\"><thead><tr><th>Username</th><th>Name</th><th>Email</th><th>Security Access Group</th></tr></thead><tbody>';
        JSON.parse(usersString).forEach(user => {
            tableString +=
                `<tr><td>${user.username}</td><td>${user.name}</td><td>${user.email}</td><td><strong>${user.sag}</strong><br><small>${user.sag_name}</small></td></tr>`;
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
        const usersList = data[7].split(',').map(str => trim(str));
        return users.some(user => usersList.includes(user));
    });

    // Projects - filter sag function
    $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
        if (!$('.projectTable').is(':visible')) {
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
    $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
        if (!$('.projectTable').is(':visible')) {
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
    $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
        if (!$('.projectTable').is(':visible')) {
            return true;
        }
        const rights = $('#rightsSelectProject').val() || [];
        if (rights.length === 0) {
            return true;
        }
        const rightsAll = data[4].split('&&&&&').map(str => trim(str));
        return rights.some(right => rightsAll.includes(right));
    });


    function showProjectTable(includeExpired) {
        clearTables();
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
        const table = $('#SUR-System-Table.projectTable')
            .DataTable({
                ajax: {
                    url: '<?= $module->framework->getUrl("ajax/projectsWithNoncompliantRights.php") ?>',
                    method: 'POST',
                    data: {
                        includeExpired: includeExpired
                    }
                },
                deferRender: true,
                initComplete: function() {
                    console.log('initComplete');
                    $('#projectTableWrapper').show();
                    Swal.close();
                    const table = this.api();
                    const data = table.data().toArray();

                    // Users filter
                    const usersSelect = $('#usersSelectProject').select2({
                        placeholder: "Filter users...",
                        templateResult: function(user) {
                            return $(`<span>${user.text}</span>`);
                        },
                        templateSelection: function(user) {
                            return $(`<span>${user.id}</span>`);
                        }
                    });
                    data.forEach(row => {
                        row.users_with_bad_rights.forEach(user => {
                            const id = user.username;
                            if (!usersSelect.find(`option[value='${id}']`).length) {
                                const text = `<strong>${user.username}</strong> (${user.name})`;
                                usersSelect.append(new Option(text, id, false, false));
                            }
                        });
                    });
                    usersSelect.trigger('change');
                    usersSelect.on('change', () => {
                        table.draw();
                    });

                    // SAGs filter
                    sagsSelect = $('#sagsSelectProject').select2({
                        placeholder: "Filter SAGs...",
                        templateResult: function(sag) {
                            return $(`<span>${sag.text}</span>`);
                        },
                        templateSelection: function(sag) {
                            return $(`<span>${sag.text}</span>`);
                        }
                    });
                    data.forEach(row => {
                        row.sags.forEach(sag => {
                            const id = sag.sag;
                            if (!sagsSelect.find(`option[value='${id}']`).length) {
                                const text =
                                    `<strong>${sag.sag_name}</strong> <small>${sag.sag}</small>`;
                                sagsSelect.append(new Option(text, id, false, false));
                            }
                        });
                    });
                    sagsSelect.trigger('change');
                    sagsSelect.on('change', () => {
                        table.draw();
                    });

                    // Projects filter
                    const projectsSelect = $('#projectsSelectProject').select2({
                        placeholder: "Filter projects...",
                        templateResult: function(project) {
                            return $(`<span>${project.text}</span>`);
                        },
                        templateSelection: function(project) {
                            return $(`<span>PID: ${project.id}</span>`);
                        }
                    });
                    data.forEach(row => {
                        const id = row.project_id;
                        if (!projectsSelect.find(`option[value='${id}']`).length) {
                            const text =
                                `<strong>PID:${row.project_id}</strong> <small>${row.project_title}</small>`;
                            projectsSelect.append(new Option(text, id, false, false));
                        }
                    })
                    projectsSelect.trigger('change');
                    projectsSelect.on('change', () => {
                        table.draw();
                    });

                    // Rights filter
                    const rightsSelect = $('#rightsSelectProject').select2({
                        placeholder: "Filter rights...",
                        templateResult: function(right) {
                            return $(`<span>${right.text}</span>`);
                        }
                    });
                    data.forEach(row => {
                        row.bad_rights.forEach(right => {
                            if (!rightsSelect.find(`option[value='${right}']`).length) {
                                rightsSelect.append(new Option(right, right, false, false));
                            }
                        });
                    });
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
                        columns: [5, 6, 1, 7, 8, 9, 10]
                    },
                    className: 'btn btn-sm btn-success border mb-1',
                    title: 'ProjectsWithNoncompliantRights' + (includeExpired ? '_all_' : '_nonexpired_') +
                        moment().format('YYYY-MM-DD_HHmmss'),
                }],
                dom: 'lBftip',

                columns: [{
                        title: "Project",
                        data: function(row, type, set, meta) {
                            const pid = row.project_id;
                            const projectUrl =
                                `${app_path_webroot_full}redcap_v${redcap_version}/ExternalModules/?prefix=<?= $module->getModuleDirectoryPrefix() ?>&page=project-status&pid=${pid}`;
                            const projectTitle = row.project_title.replaceAll('"', '');
                            return `<strong><a target="_blank" rel="noreferrer noopener" href="${projectUrl}">PID: ${pid}</a></strong><br>${projectTitle}`;
                        },
                        //width: '5%'
                    },
                    {
                        title: "Count of Noncompliant Users",
                        data: function(row, type, set, meta) {
                            const users = row.users_with_bad_rights;
                            const usersString = JSON.stringify(users);
                            return '<a href="javascript:void(0)" onclick=\'makeUserTable(\`' +
                                usersString + '\`);\'>' + users.length + '</a>';
                        },
                        //width: '10%'
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
                        //width: '15%'
                    },
                    {
                        title: "Security Access Groups",
                        data: function(row, type, set, meta) {
                            const sags = row.sags ?? [];
                            return sags.map(sag => {
                                return `<strong>${sag.sag_name}</strong> <small>${sag.sag}</small>`;
                            }).join('<br>');
                        },
                        //width: '10%'
                    },
                    {
                        title: "Noncompliant Rights",
                        data: function(row, type, set, meta) {
                            if (type === 'display') {
                                return row.bad_rights.join('<br>');
                            }
                            return row.bad_rights.join('&&&&&');
                        },
                        //width: '50%'
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
                        data: function(row, type, set, meta) {
                            const users = row.users_with_bad_rights ?? [];
                            return users.map(user => user.username).join(", ");
                        },
                        visible: false
                    },
                    {
                        title: "SAG IDs",
                        data: function(row, type, set, meta) {
                            const sags = row.sags ?? [];
                            return sags.map(sag => sag.sag).join(", ");
                        },
                        visible: false
                    },
                    {
                        title: "SAG Names",
                        data: function(row, type, set, meta) {
                            const sags = row.sags ?? [];
                            return sags.map(sag => sag.sag_name).join(", ");
                        },
                        visible: false
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
        const usersList = data[0].split(',').map(str => trim(str));
        return users.some(user => usersList.includes(user));
    });

    // Users - sag filter function
    $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
        if (!$('.userTable').is(':visible')) {
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
    $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
        if (!$('.userTable').is(':visible')) {
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
                $('#userTableWrapper').show();
                Swal.close();
                const table = this.api();
                const data = table.data().toArray();

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

                data.forEach(row => {
                    const id = row.username;
                    if (!usersSelect.find(`option[value='${id}']`).length) {
                        const text = `<strong>${row.username}</strong> (${row.name})`;
                        usersSelect.append(new Option(text, id, false, false));
                    }
                });
                usersSelect.trigger('change');
                usersSelect.on('change', () => {
                    table.draw();
                });

                // SAG filter
                const sagsSelect = $('#sagsSelectUser').select2({
                    placeholder: "Filter SAGs...",
                    templateResult: function(sag) {
                        return $(`<span>${sag.text}</span>`);
                    },
                    templateSelection: function(sag) {
                        return $(`<span>${sag.text}</span>`);
                    }
                });

                data.forEach(row => {
                    const id = row.sag;
                    if (!sagsSelect.find(`option[value='${id}']`).length) {
                        const text = `<strong>${row.sag}</strong> ${row.sag_name}`;
                        sagsSelect.append(new Option(text, id, false, false));
                    }
                });
                sagsSelect.trigger('change');
                sagsSelect.on('change', () => {
                    table.draw();
                });

                // Project filter
                const projectsSelect = $('#projectsSelectUser').select2({
                    placeholder: "Filter projects...",
                    templateResult: function(pid) {
                        return $(`<span>${pid.text}</span>`);
                    },
                    templateSelection: function(pid) {
                        return $(`<span>PID: ${pid.id}</span>`);
                    }
                });

                data.forEach(row => {
                    row.projects.forEach(project => {
                        const pid = project.project_id;
                        if (!projectsSelect.find(`option[value='${pid}']`).length) {
                            const text =
                                `<strong>PID:${pid}</strong> ${project.project_title}`;
                            projectsSelect.append(new Option(text, pid, false, false));
                        }
                    });
                });
                projectsSelect.trigger('change');
                projectsSelect.on('change', () => {
                    table.draw();
                });

                // Rights filter
                const rights = table.column(6).data().toArray().join('&&&&&').split('&&&&&')
                    .map(
                        str =>
                        trim(str));
                const rightsUnique = Array.from(new Set(rights));
                const rightsSelect = $('#rightsSelectUser').select2({
                    placeholder: "Filter rights...",
                    templateResult: function(right) {
                        return $(`<span>${right.text}</span>`);
                    }
                });
                rightsUnique.forEach(right => {
                    if (!rightsSelect.find(`option[value='${right}']`).length) {
                        rightsSelect.append(new Option(right, right, false, false));
                    }
                });
                rightsSelect.trigger('change');
                rightsSelect
                    .on(
                        'change', () => {
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
                    width: '15%'
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
                            return '<a href="mailto:' + row.email + '">' + row.email +
                                '</a>';
                        }
                        return row.email;
                    },
                    visible: false
                },
                {
                    title: "Security Access Group",
                    data: function(row, type, set, meta) {
                        return `<strong>${row.sag_name}</strong><br><small>${row.sag}</small>`;
                    },
                    width: '20%'
                },
                {
                    title: "Count of Projects",
                    data: function(row, type, set, meta) {
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
                    data: function(row, type, set, meta) {
                        const projects = row.projects ?? [];
                        return projects.map(project => {
                            const pid = project.project_id;
                            const projectUrl =
                                `${app_path_webroot_full}redcap_v${redcap_version}/ExternalModules/?prefix=<?= $module->getModuleDirectoryPrefix() ?>&page=project-status&pid=${pid}`;
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
                    data: function(row, type, set, meta) {
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
                    data: function(row, type, set, meta) {
                        const projects = row.projects ?? [];
                        return projects.map(project => project.project_id).join(", ");
                    },
                    visible: false
                },
                {
                    title: "Project Titles",
                    data: function(row, type, set, meta) {
                        const projects = row.projects ?? [];
                        return projects.map(project => project.project_title).join(", ");
                    },
                    visible: false
                },
                {
                    title: 'Noncompliant Rights',
                    data: function(row, type, set, meta) {
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
        });
    }





    // Users and Projects - user filter function
    $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
        if (!$('.allTable').is(':visible')) {
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
    $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
        if (!$('.allTable').is(':visible')) {
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
    $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
        if (!$('.allTable').is(':visible')) {
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
    $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
        if (!$('.allTable').is(':visible')) {
            return true;
        }
        const rights = $('#rightsSelectAll').val() || [];
        if (rights.length === 0) {
            return true;
        }
        const rightsAll = data[5].split('&&&&&').map(str => trim(str));
        return rights.some(right => rightsAll.includes(right));
    });

    // Users and Projects Table
    function showUserAndProjectTable(includeExpired) {
        clearTables();
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
        const table = $('#SUR-System-Table.allTable').DataTable({
            ajax: {
                url: '<?= $module->framework->getUrl("ajax/usersAndProjectsWithNoncompliantRights.php") ?>',
                method: 'POST',
                data: {
                    includeExpired: includeExpired
                }
            },
            deferRender: true,
            initComplete: function() {
                $('#allTableWrapper').show();
                Swal.close();
                const table = this.api();
                const data = table.data().toArray();

                // Users filter
                const usersSelect = $('#usersSelectAll').select2({
                    placeholder: "Filter users...",
                    templateResult: function(user) {
                        return $(`<span>${user.text}</span>`);
                    },
                    templateSelection: function(user) {
                        return $(`<span>${user.id}</span>`);
                    }
                });
                data.forEach(row => {
                    const id = row.username;
                    if (!usersSelect.find(`option[value='${id}']`).length) {
                        const text = `<strong>${row.username}</strong> (${row.name})`;
                        usersSelect.append(new Option(text, id, false,
                            false));
                    }
                });
                usersSelect.trigger('change');
                usersSelect.on('change', () => {
                    table.draw();
                });

                // SAG filter
                const sagsSelect = $('#sagsSelectAll').select2({
                    placeholder: "Filter SAGs...",
                    templateResult: function(sag) {
                        return $(`<span>${sag.text}</span>`);
                    },
                    templateSelection: function(sag) {
                        return $(`<span>${sag.text}</span>`);
                    }
                });
                data.forEach(row => {
                    const id = row.sag;
                    if (!sagsSelect.find(`option[value='${id}']`).length) {
                        const text = `<strong>${id}</strong> ${row.sag_name}`;
                        sagsSelect.append(new Option(text, id, false, false));
                    }
                });
                sagsSelect.trigger('change');
                sagsSelect.on('change', () => {
                    table.draw();
                });

                // Project filter
                const projectsSelect = $('#projectsSelectAll').select2({
                    placeholder: "Filter projects...",
                    templateResult: function(pid) {
                        return $(`<span>${pid.text}</span>`);
                    },
                    templateSelection: function(pid) {
                        return $(`<span>PID: ${pid.id}</span>`);
                    }
                });
                data.forEach(row => {
                    const id = row.project_id;
                    if (!projectsSelect.find(`option[value='${id}']`).length) {
                        const text = `<strong>PID:${id}</strong> ${row.project_title}`;
                        projectsSelect.append(
                            new Option(text, id, false, false)
                        );
                    }
                });
                projectsSelect.trigger('change');
                projectsSelect.on('change', () => {
                    table.draw();
                });

                // Rights filter
                const rightsSelect = $('#rightsSelectAll').select2({
                    placeholder: "Filter rights...",
                    templateResult: function(right) {
                        return $(`<span>${right.text}</span>`);
                    }
                });
                const allRights = data.map(row => row.bad_rights).flatten();
                const allRightsUnique = Array.from(new Set(allRights));
                allRightsUnique.forEach(right => {
                    const id = right;
                    if (!rightsSelect.find(`option[value='${id}']`).length) {
                        rightsSelect.append(new Option(right, right, false, false));
                    }
                });
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
                            return '<a href="mailto:' + row.email + '">' + row.email +
                                '</a>';
                        }
                        return row.email;
                    },
                    visible: false
                },
                {
                    title: "Security Access Group",
                    data: function(row, type, set, meta) {
                        return `<strong>${row.sag_name}</strong><br><small>${row.sag}</small>`;
                    },
                    width: '20%'
                },
                {
                    title: "Project granting Noncompliant Rights to this User",
                    data: function(row, type, set, meta) {
                        const pid = row.project_id;
                        const projectUrl =
                            `${app_path_webroot_full}redcap_v${redcap_version}/ExternalModules/?prefix=<?= $module->getModuleDirectoryPrefix() ?>&page=project-status&pid=${pid}`;
                        const projectTitle = row.project_title.replaceAll('"', '');
                        return `<strong><a target="_blank" rel="noreferrer noopener" href="${projectUrl}">PID: ${pid}</a></strong> ${projectTitle}`;
                    },
                    width: '20%'
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
                    data: function(row, type, set, meta) {
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
        });
    }
    if ('scrollRestoration' in history) {
        history.scrollRestoration = 'manual';
    }
    </script>

</div> <!-- End SAG_Container -->