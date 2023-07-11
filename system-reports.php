<?php

namespace YaleREDCap\SecurityAccessGroups;

/** @var SecurityAccessGroups $module */

if ( !$module->framework->getUser()->isSuperUser() ) {
    exit();
}
require_once APP_PATH_DOCROOT . 'ControlCenter/header.php';

?>
<link href="<?= $module->framework->getUrl('lib/DataTables/datatables.min.css') ?>" rel="stylesheet" />
<script src="<?= $module->framework->getUrl('lib/DataTables/datatables.min.js') ?>"></script>

<script defer src="<?= $module->framework->getUrl('assets/fontawesome/js/regular.min.js') ?>"></script>
<script defer src="<?= $module->framework->getUrl('assets/fontawesome/js/sharp-regular.min.js') ?>"></script>
<script defer src="<?= $module->framework->getUrl('assets/fontawesome/js/sharp-solid.min.js') ?>"></script>
<script defer src="<?= $module->framework->getUrl('assets/fontawesome/js/solid.min.js') ?>"></script>
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
                    <i class="fa-solid fa-user-tag"></i>
                    Reports
                </a>
            </li>
        </ul>
    </div>
    <div class="clear"></div>



    <p style='margin:20px 0;max-width:1000px;font-size:14px;'>This table shows all the SAGs that currently exist in the
        system. A SAG must be created here before it can be assigned to a user. The current list of SAGs can be exported
        as a CSV file, and a CSV file can be imported to update existing SAGs or to create new SAGs.
    </p>

    <!-- Modal -->
    <div class="modal" id="edit_sag_popup" data-backdrop="static" data-keyboard="false"
        aria-labelledby="staticBackdropLabel" aria-hidden="true"></div>


    <!-- Controls Container -->
    <div class="dropdown">
        <button type="button" class="btn btn-primary btn-xs dropdown-toggle mr-2" data-toggle="dropdown"
            data-bs-toggle="dropdown" aria-expanded="false">
            <i class="fa-sharp fa-file-excel"></i>
            <span>Select Report Type</span>
            <span class="sr-only">Toggle Dropdown</span>
        </button>
        <ul class="dropdown-menu">
            <li><a class="dropdown-item" onclick="showUserTable();"><i
                        class="fa-sharp fa-regular fa-file-arrow-down fa-fw mr-1 text-info"></i>Users with
                    Noncompliant Rights</a></li>
            <li><a class="dropdown-item" onclick="showProjectTable();"><i
                        class="fa-sharp fa-regular fa-file-arrow-down fa-fw mr-1 text-success"></i>Projects with
                    Noncompliant Users</a></li>
        </ul>
    </div>

    <!-- SAG Table -->
    <div class=" clear">
    </div>
    <div id="projectTableWrapper" style="display: none; width: 100%;">
        <table aria-label="Projects Table" id="SUR-System-Table" class="projectTable cell-border" style="width: 100%">
            <thead>
                <tr>
                    <th>Project ID</th>
                    <th>Project Name</th>
                    <th>Nonexpired Users with Noncompliant Rights</th>
                    <th>All Users with Noncompliant Rights</th>
                </tr>
            </thead>
            <tbody>
            </tbody>
        </table>
    </div>
    <div id="userTableWrapper" style="display: none; width: 100%;">
        <table aria-label="Users Table" id="SUR-System-Table" class="userTable cell-border" style="width: 100%">
            <thead>
                <tr>
                    <th>Username</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Projects with Noncompliant Rights (non-expired)</th>
                    <th>Projects with Noncompliant Rights (all)</th>
                </tr>
            </thead>
            <tbody>
            </tbody>
        </table>
    </div>
    <script>
    function formatNow() {
        const d = new Date();
        return d.getFullYear() + '-' + (d.getMonth() + 1).toString().padStart(2, 0) +
            '-' + (d.getDate()).toString().padStart(2, 0);
    }

    function exportRawCsv(includeData = true) {
        const newLine = navigator.userAgent.match(/Windows/) ? '\r\n' : '\n';
        const escapeChar = '"';
        const boundary = '"';
        const separator = ',';
        const extension = '.csv';
        const reBoundary = new RegExp(boundary, 'g');
        const filename = (includeData ?
            'SecurityAccessGroups_Raw_' + formatNow() :
            'SecurityAccessGroups_ImportTemplate') + extension;
        let charset = document.characterSet || document.charset;
        if (charset) {
            charset = ';charset=' + charset;
        }
        const join = function(a) {
            let s = '';
            for (let i = 0, ien = a.length; i < ien; i++) {
                if (i > 0) {
                    s += separator;
                }
                s += boundary ?
                    boundary + ('' + a[i]).replace(reBoundary, escapeChar + boundary) + boundary :
                    a[i];
            }
            return s;
        };

        const rowSelector = includeData ? undefined : -1;
        const data = $('#sagTable').DataTable().buttons.exportData({
            format: {
                header: function(html, col, node) {
                    const key = $(node).data('key');
                    return key;
                },
                body: function(html, row, col, node) {
                    if (col === 0) {
                        return;
                    } else if (col === 1) {
                        return $(html).text();
                    } else if (col === 2) {
                        return html;
                    } else {
                        const value = $(node).data('value');
                        return value == '' ? 0 : value;
                    }
                }
            },
            customizeData: function(data) {
                data.header.shift();
                data.body.forEach(row => row.shift());
                return data;
            },
            rows: rowSelector,
            columns: 'export:name'
        });

        const header = join(data.header) + newLine;
        const footer = data.footer ? newLine + join(data.footer) : '';
        const body = [];
        for (let i = 0, ien = data.body.length; i < ien; i++) {
            body.push(join(data.body[i]));
        }

        const result = {
            str: header + body.join(newLine) + footer,
            rows: body.length
        };

        $.fn.dataTable.fileSave(new Blob([result.str], {
                type: 'text/csv' + charset
            }),
            filename,
            true);
    }

    function exportCsv() {
        const newLine = navigator.userAgent.match(/Windows/) ? '\r\n' : '\n';
        const escapeChar = '"';
        const boundary = '"';
        const separator = ',';
        const extension = '.csv';
        const reBoundary = new RegExp(boundary, 'g');
        const filename = 'SecurityAccessGroups_Labels_' + formatNow() + extension;
        let charset = document.characterSet || document.charset;
        if (charset) {
            charset = ';charset=' + charset;
        }
        const join = function(a) {
            let s = '';
            for (let i = 0, ien = a.length; i < ien; i++) {
                if (i > 0) {
                    s += separator;
                }
                s += boundary ?
                    boundary + ('' + a[i]).replace(reBoundary, escapeChar + boundary) + boundary :
                    a[i];
            }
            return s;
        };

        const data = $('#sagTable').DataTable().buttons.exportData({
            format: {
                body: function(html, row, col, node) {
                    if (col === 0) {
                        return;
                    } else if (col === 1) {
                        return $(html).text();
                    } else if (col === 2) {
                        return html;
                    } else {
                        let result = $(node).text();
                        if (result == '') {
                            const value = $(node).data('value');
                            result = value == '' ? 0 : value;
                        } else {
                            result = String(html).replace(/<br>|<br\/>/g, '\n').replace(/&amp;/g, '&');
                        }
                        return result;
                    }
                }
            },
            customizeData: function(data) {
                data.header.shift();
                data.body.forEach(row => row.shift());
                return data;
            },
            columns: 'export:name'
        });

        const header = join(data.header) + newLine;
        const footer = data.footer ? newLine + join(data.footer) : '';
        const body = [];
        for (let i = 0, ien = data.body.length; i < ien; i++) {
            body.push(join(data.body[i]));
        }

        const result = {
            str: header + body.join(newLine) + footer,
            rows: body.length
        };

        $.fn.dataTable.fileSave(new Blob([result.str], {
                type: 'text/csv' + charset
            }),
            filename,
            true);
    }

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
        $('.dataTable').DataTable().destroy();
    }

    function showProjectTable() {
        clearTables();
        $('#userTableWrapper').hide();
        if ($('#projectTableWrapper').is(':hidden')) {
            Swal.fire({
                title: 'Loading...',
                didOpen: () => {
                    Swal.showLoading()
                }
            });
        }

        const table = $('#SUR-System-Table.projectTable').DataTable({
            ajax: {
                url: '<?= $module->framework->getUrl("ajax/projectsWithNoncompliantUsers.php") ?>',
                method: 'POST'
            },
            deferRender: true,
            "processing": true,
            initComplete: function() {
                $('#userTableWrapper').hide();
                $('#projectTableWrapper').show();
                Swal.close();
                const table = this.api();

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
            },

            columns: [{
                    title: "PID",
                    data: "project_id"
                },
                {
                    title: "Project Name",
                    data: "project_title"
                },
                {
                    title: "Noncompliant Users (non-expired)",
                    data: function(row, type, set, meta) {
                        if (type === 'display') {
                            return row.nonexpired_users_with_bad_rights.length;
                        } else {
                            return row.nonexpired_users_with_bad_rights;
                        }
                    }
                },
                {
                    title: "Noncompliant Users (all)",
                    data: function(row, type, set, meta) {
                        if (type === 'display') {
                            const users = row.users_with_bad_rights;
                            const usersString = JSON.stringify(users);
                            return '<a href="javascript:void(0)" onclick=\'makeUserTable(\`' +
                                usersString + '\`);\'>' + users.length + '</a>';
                        }
                        return row.users_with_bad_rights;

                    }
                }
            ],
            columnDefs: []
        });
    }

    function showUserTable() {
        clearTables();
        $('#projectTableWrapper').hide();
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
                method: 'POST'
            },
            deferRender: true,
            "processing": true,
            initComplete: function() {
                $('#projectTableWrapper').hide();
                $('#userTableWrapper').show();
                Swal.close();
                const table = this.api();

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
            },

            columns: [{
                    title: "Username",
                    data: "username"
                },
                {
                    title: "Name",
                    data: "name"
                },
                {
                    title: "Email",
                    data: "email"
                },
                {
                    title: "Projects with Noncompliant Rights (non-expired)",
                    data: function(row, type, set, meta) {
                        if (type === 'display') {
                            const projects = row.projects_unexpired ?? [];
                            return projects.length;
                        } else {
                            return row.projects_unexpired ?? [];
                        }
                    }
                },
                {
                    title: "Projects with Noncompliant Rights (all)",
                    data: function(row, type, set, meta) {
                        if (type === 'display') {
                            return row.projects.length;
                        } else {
                            return row.projects;
                        }

                    }
                }
            ],
            columnDefs: []
        });
    }

    $(document).ready(function() {


    });
    </script>

</div> <!-- End SAG_Container -->