<?php

namespace YaleREDCap\SecurityAccessGroups;

/** @var SecurityAccessGroups $module */

$tab = filter_input(INPUT_GET, "tab", FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? "userlist";

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


<h4 style='color:#900; margin-top: 0 0 10px;'>
    <i class='fa-solid fa-users-between-lines'></i>&nbsp;<span>Security Access Groups</span>
</h4>
<div class="SUR_Container">
    <div id="sub-nav" class="d-none d-sm-block mr-4 mb-0 ml-0">
        <ul>
            <li class="<?= $tab === "userlist" ? "active" : "" ?>">
                <a href="<?= $module->framework->getUrl('system-settings.php?tab=userlist') ?>"
                    style="font-size:13px;color:#393733;padding:7px 9px;">
                    <i class="fa-solid fa-users"></i>
                    Users
                </a>
            </li>
            <li class="<?= $tab === "roles" ? "active" : "" ?>">
                <a href="<?= $module->framework->getUrl('system-settings.php?tab=roles') ?>"
                    style="font-size:13px;color:#393733;padding:7px 9px;">
                    <i class="fa-solid fa-user-tag"></i>
                    Roles
                </a>
            </li>
        </ul>
    </div>
    <div class="clear"></div>

    <?php if ( $tab == "userlist" ) {
        $roles    = $module->getAllSystemRoles();
        $users    = $module->getAllUserInfo(true);
        $userJson = json_encode($users);
        ?>

    <p style='margin:20px 0;max-width:1000px;'>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Nunc velit
        metus, venenatis in congue sed, ultrices sed nulla. Donec auctor bibendum mauris eget posuere. Ut rhoncus, nulla
        at auctor volutpat, urna odio ornare nulla, a ultrices neque massa sed est. Vestibulum dignissim feugiat turpis
        vel egestas. Integer eu purus vel dui egestas varius et ac erat. Donec blandit quam a enim faucibus ultrices.
        Aenean consectetur efficitur leo, et euismod arcu ultrices non. Ut et tincidunt tortor. Quisque eu interdum
        erat, vitae convallis ligula. Lorem ipsum dolor sit amet, consectetur adipiscing elit. Morbi interdum sapien nec
        quam blandit, vel faucibus turpis convallis. </p>


    <!-- Modals -->
    <div class="hidden">
        <div id="infoContainer" class="modal-body p-4 text-center" style="font-size:x-large;">
            Lorem ipsum dolor sit amet, consectetur adipiscing elit. Nunc velit metus, venenatis in congue sed, ultrices
            sed nulla. Donec auctor bibendum mauris eget posuere. Ut rhoncus, nulla at auctor volutpat, urna odio ornare
            nulla, a ultrices neque massa sed est. Vestibulum dignissim feugiat turpis vel egestas. Integer eu purus vel
            dui egestas varius et ac erat. Donec blandit quam a enim faucibus ultrices. Aenean consectetur efficitur
            leo, et euismod arcu ultrices non. Ut et tincidunt tortor. Quisque eu interdum erat, vitae convallis ligula.
            Lorem ipsum dolor sit amet, consectetur adipiscing elit. Morbi interdum sapien nec quam blandit, vel
            faucibus turpis convallis.
        </div>
    </div>
    <div id="loading" class="modal">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-body p-4 text-center">
                    <div class="spinner-border text-primary" role="status">
                        <span class="sr-only">Loading...</span>
                    </div>
                    <div class="mt-2">Loading...</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Table Controls -->
    <div class="hidden">
        <input type="file" accept="text/csv" class="form-control-file" id="importUsersFile">
        <table id="templateTable">
            <thead>
                <tr>
                    <th>username</th>
                    <th>role_id</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ( $roles as $index => $role ) {
                        echo "<tr><td>example_user_" . (intval($index) + 1) . "</td><td>" . \REDCap::escapeHtml($role["role_id"]) . "</td></tr>";
                    } ?>
            </tbody>
        </table>
    </div>
    <!-- Users Table -->
    <div class="card card-body bg-light">
        <div class="toolbar2 d-flex flex-row justify-content-between mb-2">
            <div class="d-flex">
                <button class="btn btn-danger btn-xs mr-1 editUsersButton" style="width: 8em;" data-editing="false"
                    onclick="toggleEditMode(event);">
                    <i class="fa-sharp fa-user-pen"></i>
                    <span>Edit Users</span>
                </button>
                <div class="d-flex dropdown">
                    <button type="button" class="btn btn-primary btn-xs dropdown-toggle mr-2" data-toggle="dropdown"
                        data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fa-sharp fa-file-excel mr-1"></i>
                        <span>Import or Export User Assignments</span>
                        <span class="sr-only">Toggle Dropdown</span>
                    </button>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" onclick="exportCsv();"><i
                                    class="fa-sharp fa-regular fa-file-arrow-down fa-fw mr-1 text-success"></i>Export
                                User Assignments</a></li>
                        <li><a class="dropdown-item" onclick="importCsv();"><i
                                    class="fa-sharp fa-solid fa-file-arrow-up fa-fw mr-1 text-danger"></i>Import User
                                Assignments</a></li>
                        <li><a class="dropdown-item" onclick="downloadTemplate();"><i
                                    class="fa-sharp fa-solid fa-download fa-fw mr-1 text-primary"></i>Download Import
                                Template</a></li>
                    </ul>
                    <i class="fa-solid fa-circle-info fa-lg align-self-center text-info" style="cursor:pointer;"
                        onclick="Swal.fire({html: $('#infoContainer').html(), icon: 'info', showConfirmButton: false});"></i>
                </div>

            </div>
        </div>
        <table id='SUR-System-Table' class="compact cell-border border">
            <thead>
                <tr>
                    <th data-id="username" class="py-3">Username</th>
                    <th data-id="name" class="py-3">Name</th>
                    <th data-id="email" class="py-3">Email</th>
                    <th data-id="role" class="py-3">Role</th>
                    <th data-id="role_id" class="py-3">Role Id</th>
                </tr>
            </thead>
            <tbody>
            </tbody>
        </table>
    </div>

    <script>
    window.system_roles = {
        <?php foreach ( $roles as $role ) {
                    echo "'" . $role["role_id"] . "': '" . $role["role_name"] . "',";
                } ?>
    };

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

    function formatNow() {
        const d = new Date();
        return d.getFullYear() + '-' + (d.getMonth() + 1).toString().padStart(2, 0) + '-' + (d.getDate()).toString()
            .padStart(2, 0)
    }

    function toggleEditMode(event) {
        const button = $('button.editUsersButton');
        const editing = !$(button).data('editing');
        $('.roleSelect').attr('disabled', !editing);
        $(button).data('editing', editing);
        let style = 'none';
        if (editing) {
            $(button).find('span').text('Stop Editing');
            $(button).addClass('btn-outline-danger');
            $(button).removeClass('btn-danger');
        } else {
            $(button).find('span').text('Edit Users');
            $(button).addClass('btn-danger');
            $(button).removeClass('btn-outline-danger');
            style = 'user-select:all; cursor: text; margin-left: 1px; margin-right: 1px;';
        }
        $('.roleSelect').select2({
            minimumResultsForSearch: 20,
            templateSelection: function(selection) {
                return $(
                    `<div class="d-flex justify-content-between"><strong>${selection.text}</strong>&nbsp;<span class="text-secondary" style="${style}">${selection.id}</span></div>`
                );
            },
            templateResult: function(option) {
                return $(
                    `<span><strong>${option.text}</strong><br><span class="text-secondary">${option.id}</span></span>`
                );
            }
        });
    }

    function exportCsv() {
        const newLine = navigator.userAgent.match(/Windows/) ? '\r\n' : '\n';
        const escapeChar = '"';
        const boundary = '"';
        const separator = ',';
        const extension = '.csv';
        const reBoundary = new RegExp(boundary, 'g');
        const filename = 'SystemRoles_Users_' + formatNow() + extension;
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
                    boundary + ('' + a[i]).replace(reBoundary, escapeChar + boundary) +
                    boundary :
                    a[i];
            }
            return s;
        };

        const dt = $('#SUR-System-Table').DataTable();
        const allData = dt.rows().data();
        const data = dt.buttons.exportData({
            format: {
                header: function(html, col, node) {
                    return $(node).data('id');
                },
                body: function(html, row, col, node) {
                    if (col === 0) {
                        return allData[row]["username"];
                    } else if (col === 1) {
                        return allData[row]["user_firstname"] + " " + allData[row][
                            "user_lastname"
                        ];
                    } else if (col === 2) {
                        return allData[row]["user_email"];
                    } else if (col === 3) {
                        return window.system_roles[allData[row]["system_role"]];
                    } else if (col === 4) {
                        return allData[row]["system_role"];
                    }
                }
            },
            exportOptions: {
                page: 'all',
                search: 'none'
            }
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
        $('#loading').modal('hide');
    }

    function importCsv() {
        $('#importUsersFile').click();
        console.log('import');
    }

    function handleFiles() {
        if (this.files.length !== 1) {
            return;
        }
        const file = this.files[0];

        if (!file.type === "text/csv") {
            return;
        }

        Swal.fire('Loading...');
        Swal.showLoading();

        const reader = new FileReader();
        reader.onload = (e) => {
            window.csv_file_contents = e.target.result;
            $.post("<?= $module->framework->getUrl("ajax/importCsvUsers.php") ?>", {
                    data: window.csv_file_contents
                })
                .done((response) => {
                    Swal.close();
                    $(response).modal('show');
                })
                .fail((error) => {
                    Swal.close();
                    try {
                        console.error(JSON.parse(error.responseText).error);
                        const response = JSON.parse(error.responseText);
                        let body = response.error.join('<br>') + "<div class='container'>";
                        if (response.users.length) {
                            body +=
                                "<div class='row justify-content-center m-2'><table><thead><tr><th>Username</th></tr></thead><tbody>";
                            response.users.forEach((user) => {
                                body += `<tr><td>${user}</td></tr>`;
                            });
                            body += "</tbody></table></div>";
                        }
                        if (response.roles.length) {
                            body +=
                                "<div class='row justify-content-center m-2'><table><thead><tr><th>Role ID</th></tr></thead><tbody>";
                            response.roles.forEach((role) => {
                                body += `<tr><td>${role}</td></tr>`;
                            });
                            body += "</tbody></table></div>";
                        }
                        body += "</div>";
                        Swal.fire({
                            title: 'Error',
                            html: body,
                            icon: 'error'
                        });
                    } catch (error) {
                        console.error(error);
                    }
                })
                .always(() => {
                    //Swal.fire("Sorry", "That feature is not yet implemented.");
                })
        };
        reader.readAsText(file);
    }

    function confirmImport() {
        $('.modal').modal('hide');
        if (!window.csv_file_contents || window.csv_file_contents === "") {
            return;
        }
        $.post("<?= $module->framework->getUrl("ajax/importCsvUsers.php") ?>", {
                data: window.csv_file_contents,
                confirm: true
            })
            .done((response) => {
                if (response == true) {
                    Swal.fire({
                            icon: 'success',
                            html: "Successfully imported assignments.",
                            customClass: {
                                confirmButton: 'btn btn-primary',
                            },
                            buttonsStyling: false
                        })
                        .then(() => {
                            window.location.reload();
                        });
                } else {
                    Toast.fire({
                        icon: 'error',
                        html: "Error importing CSV"
                    });
                }
            })
            .fail((error) => {
                Toast.fire({
                    icon: 'error',
                    html: "Error importing CSV"
                });
                console.error(error.responseText);
            })
    }

    function downloadTemplate() {
        const newLine = navigator.userAgent.match(/Windows/) ? '\r\n' : '\n';
        const escapeChar = '"';
        const boundary = '"';
        const separator = ',';
        const extension = '.csv';
        const reBoundary = new RegExp(boundary, 'g');
        const filename = 'SystemRoles_Users_ImportTemplate' + extension;
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
        const data = $('#templateTable').DataTable().buttons.exportData();
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

    function saveSystemRole(selectNode) {
        const select = $(selectNode);
        const tr = $(selectNode).closest('tr');
        const user = tr.data('user');
        const newRole = select.val();

        const url = '<?= $module->framework->getUrl("ajax/setUserRole.php") ?>';
        let color = "#66ff99";
        const dt = $('#SUR-System-Table').DataTable();
        $.post(url, {
                "username": user,
                "role": newRole
            })
            .done(function(response) {
                select.closest('td').data('role', newRole);
                select.closest('td').attr('data-role', newRole);
                const rowIndex = dt.row(select.closest('tr')).index();
                dt.cell(rowIndex, 4).data(newRole);
                //dt.rows().invalidate().draw();
            })
            .fail(function() {
                color = "#ff3300";
                select.val(select.closest('td').data('role')).select2();
            })
            .always(function() {
                $(tr).find('td').effect('highlight', {
                    color: color
                }, 2000);
            });
    }

    function handleSelects() {
        const button = $('button.editUsersButton');
        const editing = $(button).data('editing');
        console.log(editing);
        const style = editing ? 'user-select:all; cursor: text; margin-left: 1px; margin-right: 1px;' : 'none';

        $('.roleSelect').select2({
            minimumResultsForSearch: 20,
            templateSelection: function(selection) {
                return $(
                    `<div class="d-flex justify-content-between"><strong>${selection.text}</strong>&nbsp;<span class="text-secondary" style="${style}">${selection.id}</span></div>`
                );
            },
            templateResult: function(option) {
                return $(
                    `<span><strong>${option.text}</strong><br><span class="text-secondary">${option.id}</span></span>`
                );
            }
        });
        $('.roleSelect').attr('disabled', !editing);
    }

    $(document).ready(function() {
        const importFileElement = document.getElementById("importUsersFile");
        importFileElement.addEventListener("change", handleFiles, false);
        const dt = $('#SUR-System-Table').DataTable({
            // ajax: {
            //     url: '<?= $module->framework->getUrl("ajax/users.php") ?>',
            //     type: 'POST'
            // },
            data: JSON.parse('<?= $userJson ?>'),
            drawCallback: function(settings) {
                handleSelects();
            },
            deferRender: true,
            paging: true,
            pageLength: 10,
            info: true,
            columns: [{
                    title: 'Username',
                    data: function(row, type, set, meta) {
                        if (type === 'display') {
                            return `<a class="user-link" href="${app_path_webroot_full}${app_path_webroot}ControlCenter/view_users.php?username=${row.username}" target="_blank" rel="noopener noreferrer">${row.username}</a>`;
                        } else {
                            return row.username;
                        }
                    },
                }, {
                    title: 'Name',
                    data: function(row, type, set, meta) {
                        return row.user_firstname + ' ' + row.user_lastname;
                    }
                }, {
                    title: 'Email',
                    data: function(row, type, set, meta) {
                        if (type === 'display') {
                            return `<a href="mailto:${row.user_email}">${row.user_email}</a>`;
                        } else {
                            return row.user_email;
                        }
                    }
                }, {
                    title: 'Security Access Group',
                    data: function(row, type, set, meta) {
                        if (row.system_role === null) {
                            row.system_role = '<?= $module->defaultRoleId ?>';
                        }
                        if (type === 'filter') {
                            return row.system_role + ' ' + window.system_roles[row.system_role];
                        } else if (type === 'sort') {
                            return window.system_roles[row.system_role];
                        } else {
                            let result =
                                `<select class="roleSelect" disabled="true" onchange="saveSystemRole(this)">`;
                            for (let system_role_id in system_roles) {
                                const system_role_label = system_roles[system_role_id];
                                const selected = system_role_id == row.system_role ?
                                    "selected" : "";
                                result +=
                                    `<option value='${system_role_id}' ${selected}>${system_role_label}</option>`;
                            }
                            result += `</select>`;
                            return result;
                        }
                    }
                },
                {
                    title: 'Hidden Role',
                    data: 'system_role'
                }
            ],
            createdRow: function(row, data, dataIndex) {
                $(row).attr('data-user', data.username);
            },
            columnDefs: [{
                targets: [0, 1, 2],
                width: '25%'
            }, {
                targets: [3],
                createdCell: function(td, cellData, rowData, row, col) {
                    $(td).attr('data-role', rowData.system_role);
                }
            }, {
                targets: [4],
                visible: false,
                createdCell: function(td, cellData, rowData, row, col) {
                    $(td).addClass('hidden_role_id');
                }
            }],
            dom: 'lftip',
            initComplete: function() {
                $('div.dataTables_filter input').addClass('form-control');
                setTimeout(() => {
                    $(this).DataTable().columns.adjust().draw();
                }, 0);
                handleSelects();
                console.log(performance.now());
            },
            lengthMenu: [
                [10, 25, 50, 100, -1],
                [10, 25, 50, 100, "All"]
            ],
            language: {
                search: "_INPUT_",
                searchPlaceholder: "Search Users...",
                infoFiltered: " - filtered from _MAX_ total users",
                emptyTable: "No users found in this project",
                info: "Showing _START_ to _END_ of _TOTAL_ users",
                infoEmpty: "Showing 0 to 0 of 0 users",
                lengthMenu: "Show _MENU_ users",
                loadingRecords: "Loading...",
                zeroRecords: "No matching users found"
            }
        });

    });
    </script>
    <?php


    } else if ( $tab == "roles" ) {
        $displayTextForUserRights    = $module->getDisplayTextForRights();
        $allDisplayTextForUserRights = $module->getDisplayTextForRights(true);

        ?>

    <p style='margin:20px 0;max-width:1000px;'>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Nunc velit
        metus, venenatis in congue sed, ultrices sed nulla. Donec auctor bibendum mauris eget posuere. Ut rhoncus, nulla
        at auctor volutpat, urna odio ornare nulla, a ultrices neque massa sed est. Vestibulum dignissim feugiat turpis
        vel egestas. Integer eu purus vel dui egestas varius et ac erat. Donec blandit quam a enim faucibus ultrices.
        Aenean consectetur efficitur leo, et euismod arcu ultrices non. Ut et tincidunt tortor. Quisque eu interdum
        erat, vitae convallis ligula. Lorem ipsum dolor sit amet, consectetur adipiscing elit. Morbi interdum sapien nec
        quam blandit, vel faucibus turpis convallis. </p>

    <!-- Modal -->
    <div class="modal" id="edit_role_popup" data-backdrop="static" data-keyboard="false"
        aria-labelledby="staticBackdropLabel" aria-hidden="true"></div>


    <!-- Controls Container -->
    <div class="container ml-0 mt-2 mb-3 px-0"
        style="background-color: #eee; max-width: 550px; border: 1px solid #ccc;">
        <div class="d-flex flex-row justify-content-end my-1">
            <div class="dropdown">
                <button type="button" class="btn btn-primary btn-xs dropdown-toggle mr-2" data-toggle="dropdown"
                    data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="fa-sharp fa-file-excel"></i>
                    <span>Import or Export Roles</span>
                    <span class="sr-only">Toggle Dropdown</span>
                </button>
                <ul class="dropdown-menu">
                    <li><a class="dropdown-item" onclick="exportRawCsv();"><i
                                class="fa-sharp fa-regular fa-file-arrow-down fa-fw mr-1 text-info"></i>Export Roles
                            (raw)</a></li>
                    <li><a class="dropdown-item" onclick="exportCsv();"><i
                                class="fa-sharp fa-regular fa-file-arrow-down fa-fw mr-1 text-success"></i>Export Roles
                            (labels)</a></li>
                    <li><a class="dropdown-item" onclick="importCsv();"><i
                                class="fa-sharp fa-solid fa-file-arrow-up fa-fw mr-1 text-danger"></i>Import Roles</a>
                    </li>
                    <li><a class="dropdown-item" onclick="exportRawCsv(false);"><i
                                class="fa-sharp fa-solid fa-download fa-fw mr-1 text-primary"></i>Download Import
                            Template</a></li>
                </ul>
            </div>
            <div class="hidden">
                <input type="file" accept="text/csv" class="form-control-file" id="importRolesFile">
            </div>
        </div>
        <div class="row ml-2">
            <span><strong>Create new system user roles:</strong></span>
        </div>
        <div class="row ml-2 mb-2 mt-1 justify-content-start">
            <div class="col-6 px-0">
                <input id="newRoleName" class="form-control form-control-sm" type="text"
                    placeholder="Enter new system role name">
            </div>
            <div class="col ml-1 px-0 justify-content-start">
                <button class="btn btn-success btn-sm" id="addRoleButton" onclick="addNewRole();"
                    title="Add a New System User Role">
                    <i class="fa-kit fa-solid-tag-circle-plus fa-fw"></i>
                    <span>Create Role</span>
                </button>
            </div>
        </div>
    </div>


    <!-- Role Table -->
    <div class=" clear">
    </div>
    <div id="roleTableWrapper" style="display: none; width: 100%;">
        <table id="roleTable" class="roleTable cell-border" style="width: 100%">
            <!-- <table id="roleTable" class="table table-striped table-hover table-bordered table-responsive align-middle" style="width: 100%;"> -->
            <thead>
                <tr style="vertical-align: bottom; text-align: center;">
                    <th>Order</th>
                    <th data-key="role_name">Role</th>
                    <th data-key="role_id">Role ID</th>
                    <?php foreach ( $allDisplayTextForUserRights as $key => $text ) {
                            echo "<th data-key='" . \REDCap::escapeHtml($key) . "' class='dt-head-center'>" . \REDCap::escapeHtml($text) . "</th>";
                        } ?>
                </tr>
            </thead>
            <tbody>
            </tbody>
        </table>
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

    function openRoleEditor(url, role_id = "", role_name = "") {
        const deleteRoleButtonCallback = function() {
            Swal.fire({
                title: 'Are you sure you want to delete this role?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Delete Role',
                customClass: {
                    confirmButton: 'btn btn-danger m-1',
                    cancelButton: 'btn btn-secondary m-1'
                },
                buttonsStyling: false
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.showLoading();
                    $.post("<?= $module->framework->getUrl("ajax/deleteSystemRole.php") ?>", {
                            role_id: role_id
                        })
                        .done(function(response) {
                            Toast.fire({
                                    title: 'The role was deleted',
                                    icon: 'success'
                                })
                                .then(function() {
                                    window.location.reload();
                                });
                        })
                        .fail(function(error) {
                            console.error(error.responseText);
                            Swal.fire({
                                title: 'Error',
                                html: error.responseText,
                                icon: 'error',
                                customClass: {
                                    confirmButton: 'btn btn-primary',
                                },
                                buttonsStyling: false
                            });
                        })
                        .always(function() {});
                }
            });
        };
        const copyRoleButtonCallback = function() {
            const data = $("#SUR_Role_Setting").serializeObject();
            Swal.fire({
                    title: 'What would you like the new role to be called?',
                    input: 'text',
                    inputValue: `${data["role_name_edit"]} Copy`,
                    showCancelButton: true,
                    confirmButtonText: 'Copy Role',
                    customClass: {
                        confirmButton: 'btn btn-info m-1',
                        cancelButton: 'btn btn-secondary m-1'
                    },
                    buttonsStyling: false
                })
                .then(function(result) {
                    if (result.isConfirmed) {
                        const role_name = result.value;
                        data.role_name_edit = role_name;
                        data.newRole = 1;
                        $.post('<?= $module->framework->getUrl("ajax/editSystemRole.php") ?>', data)
                            .done(function(result) {
                                Toast.fire({
                                        icon: 'success',
                                        title: 'The role was copied'
                                    })
                                    .then(function() {
                                        window.location.reload();
                                    });
                            })
                            .fail(function(result) {
                                console.error(result.responseText);
                            })
                            .always(function() {});
                    }
                })
        };
        const saveRoleChangesButtonCallback = function() {
            $('input[name="role_name_edit"]').blur();
            const role_name_edit = $('input[name="role_name_edit"]').val();
            if (role_name_edit != '') {
                const data = $("#SUR_Role_Setting").serializeObject();
                data.role_id = role_id;
                $.post(url, data)
                    .done(function(response) {
                        Toast.fire({
                            icon: "success",
                            title: `Role "${role_name_edit}" Successfully Saved`
                        }).then(function() {
                            window.location.reload();
                        })
                    })
                    .fail(function(error) {
                        console.error(error.responseText);
                    });
            }
        };
        const saveNewRoleButtonCallback = function() {
            $('input[name="role_name_edit"]').blur();
            const role_name_edit = $('input[name="role_name_edit"]').val();
            if (role_name_edit != '') {
                const data = $("#SUR_Role_Setting").serializeObject();
                $.post(url, data)
                    .done(function(response) {
                        Toast.fire({
                            icon: "success",
                            title: `Role Successfully Created`
                        }).then(function() {
                            window.location.reload();
                        })
                    })
                    .fail(function(error) {
                        console.error(error.responseText);
                    });
            }
        };

        $.get(url, {
                role_id: role_id,
                role_name: role_name
            })
            .done(function(response) {
                $("#edit_role_popup").html(response);
                $("#edit_role_popup").on('shown.bs.modal', function(event) {
                    $('input[name="role_name_edit"]').blur(function() {
                        $(this).val($(this).val().trim());
                        if ($(this).val() == '') {
                            Swal.fire({
                                    title: '<?= $lang['rights_358'] ?>',
                                    icon: 'error',
                                    customClass: {
                                        confirmButton: 'btn btn-primary',
                                    },
                                    buttonsStyling: false
                                })
                                .then(() => {
                                    $('input[name=role_name_edit]').focus();
                                })
                        }
                    });
                    $('#SUR_Save').click(role_id == "" ? saveNewRoleButtonCallback :
                        saveRoleChangesButtonCallback);
                    if ($('#SUR_Copy')) $('#SUR_Copy').click(copyRoleButtonCallback);
                    if ($('#SUR_Delete')) $('#SUR_Delete').click(deleteRoleButtonCallback);
                })
                $("#edit_role_popup").modal('show');
            })
            .fail(function(error) {
                console.error(error.responseText)
            });
    }

    function editRole(role_id, role_name) {
        const url = "<?= $module->framework->getUrl("ajax/editSystemRole.php?newRole=false") ?>";
        console.log(url, role_id, role_name);
        openRoleEditor(url, role_id, role_name);
    }

    function addNewRole() {
        $('#addRoleButton').blur();
        const url = "<?= $module->framework->getUrl("ajax/editSystemRole.php?newRole=true") ?>";
        const newRoleName = $('#newRoleName').val().trim();
        if (newRoleName == "") {
            Toast.fire({
                title: "You must specify a role name",
                icon: "error",
                showConfirmButton: false,
                didClose: () => {
                    $("#newRoleName").focus()
                }
            });
        } else {
            openRoleEditor(url, "", newRoleName);
        }
    }

    function formatNow() {
        const d = new Date();
        return d.getFullYear() + '-' + (d.getMonth() + 1).toString().padStart(2, 0) + '-' + (d.getDate()).toString()
            .padStart(2, 0)
    }

    function exportRawCsv(includeData = true) {
        const newLine = navigator.userAgent.match(/Windows/) ? '\r\n' : '\n';
        const escapeChar = '"';
        const boundary = '"';
        const separator = ',';
        const extension = '.csv';
        const reBoundary = new RegExp(boundary, 'g');
        const filename = (includeData ? 'SystemRoles_Raw_' + formatNow() : 'SystemRoles_Roles_ImportTemplate') +
            extension;
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
        const data = $('#roleTable').DataTable().buttons.exportData({
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
        const filename = 'SystemRoles_Labels_' + formatNow() + extension;
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

        const data = $('#roleTable').DataTable().buttons.exportData({
            format: {
                body: function(html, row, col, node) {
                    console.log(html, row, col, node);
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

    function importCsv() {
        $('#importRolesFile').click();
        console.log('import');
    }

    function handleFiles() {
        if (this.files.length !== 1) {
            return;
        }
        const file = this.files[0];

        if (!file.type === "text/csv") {
            return;
        }

        const reader = new FileReader();
        reader.onload = (e) => {
            window.csv_file_contents = e.target.result;
            $.post("<?= $module->framework->getUrl("ajax/importCsvRoles.php") ?>", {
                    data: e.target.result
                })
                .done((response) => {
                    $(response).modal('show');
                })
                .fail((error) => {
                    const errorText = JSON.parse(error.responseText) ?? {};
                    const message = errorText.error;
                    Swal.fire({
                        icon: 'error',
                        title: "Error importing CSV",
                        html: message,
                        showConfirmButton: false
                    });
                });
        };
        reader.readAsText(file);
    }

    function confirmImport() {
        $('.modal').modal('hide');
        if (!window.csv_file_contents || window.csv_file_contents === "") {
            return;
        }
        $.post("<?= $module->framework->getUrl("ajax/importCsvRoles.php") ?>", {
                data: window.csv_file_contents,
                confirm: true
            })
            .done((response) => {
                if (response == true) {
                    Swal.fire({
                            icon: 'success',
                            html: "Successfully imported assignments.",
                            customClass: {
                                confirmButton: 'btn btn-primary',
                            },
                            buttonsStyling: false
                        })
                        .then(() => {
                            window.location.reload();
                        });
                } else {
                    Toast.fire({
                        icon: 'error',
                        html: "Error importing CSV"
                    });
                }
            })
            .fail((error) => {
                Toast.fire({
                    icon: 'error',
                    html: "Error importing CSV"
                });
                console.error(error.responseText);
            })
    }

    $(document).ready(function() {
        console.log('<?= $module->framework->getUrl("ajax/roles.php") ?>');
        const importFileElement = document.getElementById("importRolesFile");
        importFileElement.addEventListener("change", handleFiles, false);

        const shieldcheck = '<i class="fa-solid fa-shield-check fa-xl" style="color: green;"></i>';
        const check = '<i class="fa-solid fa-check fa-xl" style="color: green;"></i>';
        const x = '<i class="fa-regular fa-xmark" style="color: #D00000;"></i>';
        const table = $('#roleTable').DataTable({
            ajax: '<?= $module->framework->getUrl("ajax/roles.php") ?>',
            deferRender: true,
            searching: false,
            info: false,
            paging: false,
            rowReorder: true,
            ordering: true,
            fixedHeader: false,
            fixedColumns: true,
            scrollX: true,
            scrollY: '75vh',
            scrollCollapse: true,
            rowReorder: {
                dataSrc: 'index',
                snapX: 0,
                selector: '.dt-rowReorder-grab'
            },
            initComplete: function() {
                $('#roleTableWrapper').show();
                setTimeout(() => {
                    $(this).DataTable().stateRestore();
                    $(this).DataTable().columns.adjust().draw();
                }, 0);
                const theseSettingsString = localStorage.getItem('DataTables_roleOrder');
                if (theseSettingsString) {
                    const theseSettings = JSON.parse(theseSettingsString);
                    table.rows().every(function(rowIdx, tableLoop, rowLoop) {
                        const thisRoleId = table.cell(rowLoop, 2).data();
                        const desiredIndex = theseSettings.indexOf(thisRoleId);
                        table.cell(rowLoop, 0).data(desiredIndex);
                    });
                    table.order([0, 'asc']).draw();
                };
            },

            columns: [{
                    data: 'index',
                    orderable: true,
                    visible: false
                },
                {
                    className: '',
                    data: function(row, type, set, meta) {
                        if (type === 'display') {
                            return `<div style="display: flex; align-items: center; white-space: nowrap;"><i class="fa-solid  fa-grip-dots-vertical mr-2 dt-rowReorder-grab text-secondary"></i><a class="SUR_roleLink text-primary" onclick="editRole('${row.role_id}')">${row.role_name}</a></div>`;
                        } else {
                            return row.role_name;
                        }
                    }
                },
                {
                    className: 'role-id-column user-select-all',
                    data: 'role_id'
                },
                {
                    className: 'dt-center',
                    data: function(row, type, set, meta) {
                        if (type === 'display') {
                            return row.permissions.design ? check : x;
                        } else {
                            return row.permissions.design;
                        }
                    }
                },
                {
                    className: 'dt-center',
                    data: function(row, type, set, meta) {
                        if (type === 'display') {
                            return row.permissions.user_rights ? check : x;
                        } else {
                            return row.permissions.user_rights;
                        }
                    }
                },
                {
                    className: 'dt-center',
                    data: function(row, type, set, meta) {
                        if (type === 'display') {
                            return row.permissions.data_access_groups ? check : x;
                        } else {
                            return row.permissions.data_access_groups;
                        }
                    }
                },
                {
                    className: 'dt-center',
                    data: function(row, type, set, meta) {
                        if (type === 'display') {
                            switch (String(row.permissions.dataViewing)) {
                                case '3':
                                    return 'View & Edit Forms and Survey Responses';
                                case '2':
                                    return 'View & Edit Forms';
                                case '1':
                                    return 'Read Only';
                                default:
                                    return x;
                            }
                        } else {
                            return row.permissions.dataViewing;
                        }
                    }
                },
                {
                    className: 'dt-center',
                    data: function(row, type, set, meta) {
                        if (type === 'display') {
                            switch (String(row.permissions.dataExport)) {
                                case '3':
                                    return 'Full Data Set';
                                case '2':
                                    return 'Remove Identifiers';
                                case '1':
                                    return 'De-Identified';
                                default:
                                    return x;
                            }
                        } else {
                            return row.permissions.dataExport;
                        }
                    }
                },
                {
                    className: 'dt-center',
                    data: function(row, type, set, meta) {
                        if (type === 'display') {
                            return row.permissions.alerts ? check : x;
                        } else {
                            return row.permissions.alerts;
                        }
                    }
                },
                {
                    className: 'dt-center',
                    data: function(row, type, set, meta) {
                        if (type === 'display') {
                            return row.permissions.reports ? check : x;
                        } else {
                            return row.permissions.reports;
                        }
                    }
                },
                {
                    className: 'dt-center',
                    data: function(row, type, set, meta) {
                        if (type === 'display') {
                            return row.permissions.graphical ? check : x;
                        } else {
                            return row.permissions.graphical;
                        }
                    }
                },
                {
                    className: 'dt-center',
                    data: function(row, type, set, meta) {
                        if (type === 'display') {
                            return row.permissions.participants ? check : x;
                        } else {
                            return row.permissions.participants;
                        }
                    }
                },
                {
                    className: 'dt-center',
                    data: function(row, type, set, meta) {
                        if (type === 'display') {
                            return row.permissions.calendar ? check : x;
                        } else {
                            return row.permissions.calendar;
                        }
                    }
                },
                {
                    className: 'dt-center',
                    data: function(row, type, set, meta) {
                        if (type === 'display') {
                            return row.permissions.data_import_tool ? check : x;
                        } else {
                            return row.permissions.data_import_tool;
                        }
                    }
                },
                {
                    className: 'dt-center',
                    data: function(row, type, set, meta) {
                        if (type === 'display') {
                            return row.permissions.data_comparison_tool ? check : x;
                        } else {
                            return row.permissions.data_comparison_tool;
                        }
                    }
                },
                {
                    className: 'dt-center',
                    data: function(row, type, set, meta) {
                        if (type === 'display') {
                            return row.permissions.data_logging ? check : x;
                        } else {
                            return row.permissions.data_logging;
                        }
                    }
                },
                {
                    className: 'dt-center',
                    data: function(row, type, set, meta) {
                        if (type === 'display') {
                            return row.permissions.file_repository ? check : x;
                        } else {
                            return row.permissions.file_repository;
                        }
                    }
                },
                {
                    className: 'dt-center',
                    data: function(row, type, set, meta) {
                        if (type === 'display') {
                            switch (String(row.permissions.double_data)) {
                                case '1':
                                    return "Person #1";
                                case '2':
                                    return "Person #2";
                                default:
                                    return 'Reviewer';
                            }
                        } else {
                            return row.permissions.double_data;
                        }
                    }
                },
                {
                    className: 'dt-center',
                    data: function(row, type, set, meta) {
                        if (type === 'display') {
                            return row.permissions.lock_record_customize ? check : x;
                        } else {
                            return row.permissions.lock_record_customize;
                        }
                    }
                },
                {
                    className: 'dt-center',
                    data: function(row, type, set, meta) {
                        if (type === 'display') {
                            switch (String(row.permissions.lock_record)) {
                                case '2':
                                    return shieldcheck;
                                case '1':
                                    return check;
                                default:
                                    return x;
                            }
                        } else {
                            return row.permissions.lock_record;
                        }
                    }
                },
                {
                    className: 'dt-center',
                    data: function(row, type, set, meta) {
                        if (type === 'display') {
                            const random_setup = row.permissions.random_setup ? "Setup" : null;
                            const random_dashboard = row.permissions.random_dashboard ?
                                "Dashboard" : null;
                            const random_perform = row.permissions.random_perform ?
                                "Randomize" : null;
                            const cellValue = [random_setup, random_dashboard, random_perform]
                                .filter(el => el).join("<br/>");
                            return cellValue || x;
                        } else {
                            return row.permissions.randomization;
                        }
                    },
                    name: 'randomization'
                },
                {
                    className: 'dt-center',
                    data: function(row, type, set, meta) {
                        if (type === 'display') {
                            return row.permissions.data_quality_design ? check : x;
                        } else {
                            return row.permissions.data_quality_design;
                        }
                    }
                },
                {
                    className: 'dt-center',
                    data: function(row, type, set, meta) {
                        if (type === 'display') {
                            return row.permissions.data_quality_execute ? check : x;
                        } else {
                            return row.permissions.data_quality_execute;
                        }
                    }
                },
                {
                    className: 'dt-center',
                    data: function(row, type, set, meta) {
                        if (type === 'display') {
                            const view = row.permissions.data_quality_resolution_view ?
                                "View" : null;
                            const respond = row.permissions.data_quality_resolution_respond ?
                                "Respond" : null;
                            const open = row.permissions.data_quality_resolution_open ?
                                "Open" : null;
                            const close = row.permissions.data_quality_resolution_close ?
                                "Close" : null;
                            const cellValue = [view, respond, open, close].filter(el => el)
                                .join("<br/>");
                            return cellValue || x;
                        } else {
                            return row.permissions.data_quality_resolution;
                        }
                    },
                    name: 'data_quality_resolution'
                },
                {
                    className: 'dt-center',
                    data: function(row, type, set, meta) {
                        if (type === 'display') {
                            const api_export = row.permissions.api_export ? "Export" : null;
                            const api_import = row.permissions.api_import ? "Import" : null;
                            const cellValue = [api_export, api_import].filter(el => el).join(
                                "<br/>");
                            return cellValue || x;
                        } else {
                            return row.permissions.api;
                        }
                    },
                    name: 'api'
                },
                {
                    className: 'dt-center',
                    data: function(row, type, set, meta) {
                        if (type === 'display') {
                            return row.permissions.mobile_app ? check : x;
                        } else {
                            return row.permissions.mobile_app;
                        }
                    }
                },
                {
                    className: 'dt-center',
                    data: function(row, type, set, meta) {
                        if (type === 'display') {
                            return row.permissions.realtime_webservice_mapping ? check : x;
                        } else {
                            return row.permissions.realtime_webservice_mapping;
                        }
                    }
                },
                {
                    className: 'dt-center',
                    data: function(row, type, set, meta) {
                        if (type === 'display') {
                            return row.permissions.realtime_webservice_adjudicate ? check : x;
                        } else {
                            return row.permissions.realtime_webservice_adjudicate;
                        }
                    }
                },
                {
                    className: 'dt-center',
                    data: function(row, type, set, meta) {
                        if (type === 'display') {
                            return row.permissions.dts ? check : x;
                        } else {
                            return row.permissions.dts;
                        }
                    }
                },
                {
                    className: 'dt-center',
                    data: function(row, type, set, meta) {
                        if (type === 'display') {
                            return row.permissions.mycap_participants ? check : x;
                        } else {
                            return row.permissions.mycap_participants;
                        }
                    }
                },
                {
                    className: 'dt-center',
                    data: function(row, type, set, meta) {
                        if (type === 'display') {
                            return row.permissions.record_create ? check : x;
                        } else {
                            return row.permissions.record_create;
                        }
                    }
                },
                {
                    className: 'dt-center',
                    data: function(row, type, set, meta) {
                        if (type === 'display') {
                            return row.permissions.record_rename ? check : x;
                        } else {
                            return row.permissions.record_rename;
                        }
                    }
                },
                {
                    className: 'dt-center',
                    data: function(row, type, set, meta) {
                        if (type === 'display') {
                            return row.permissions.record_delete ? check : x;
                        } else {
                            return row.permissions.record_delete;
                        }
                    }
                },
                {
                    data: 'permissions.random_setup',
                    visible: false
                },
                {
                    data: 'permissions.random_dashboard',
                    visible: false
                },
                {
                    data: 'permissions.random_perform',
                    visible: false
                },
                {
                    data: 'permissions.data_quality_resolution_view',
                    visible: false
                },
                {
                    data: 'permissions.data_quality_resolution_open',
                    visible: false
                },
                {
                    data: 'permissions.data_quality_resolution_respond',
                    visible: false
                },
                {
                    data: 'permissions.data_quality_resolution_close',
                    visible: false
                },
                {
                    data: 'permissions.api_export',
                    visible: false
                },
                {
                    data: 'permissions.api_import',
                    visible: false
                },
                {
                    data: 'permissions.mobile_app_download_data',
                    visible: false
                },
                {
                    data: 'permissions.lock_record_multiform',
                    visible: false
                }
            ],
            columnDefs: [{
                targets: "_all",
                createdCell: function(td, cellData, rowData, row, col) {
                    $(td).data('value', cellData ?? 0);
                },
                name: 'export',
                orderable: false
            }]
        });

        table.on('draw', function() {
            $('.dataTable tbody tr').each((i, row) => {
                row.onmouseenter = hover;
                row.onmouseleave = dehover;
            });
        });

        table.on('row-reordered', function(e, diff, edit) {
            setTimeout(() => {
                const order = table.column(2).data().toArray();
                localStorage.setItem('DataTables_roleOrder', JSON.stringify(order));
            }, 0);
        });

        table.rows().every(function() {
            const rowNode = this.node();
            const rowIndex = this.index();
            $(rowNode).attr('data-dt-row', rowIndex);
        });

        const theseSettingsString = localStorage.getItem('DataTables_roleOrder');
        if (theseSettingsString) {
            const theseSettings = JSON.parse(theseSettingsString);
            table.rows().every(function(rowIdx, tableLoop, rowLoop) {
                const thisRoleId = table.cell(rowLoop, 2).data();
                const desiredIndex = theseSettings.indexOf(thisRoleId);
                table.cell(rowLoop, 0).data(desiredIndex);
            });
            table.order([0, 'asc']).draw();
        };

        $('.dataTable tbody tr').each((i, row) => {
            row.onmouseenter = hover;
            row.onmouseleave = dehover;
        });

        table.on('row-reorder', function(e, diff, edit) {
            const data = table.rows().data();
            const newOrder = [];
            for (let i = 0; i < data.length; i++) {
                newOrder.push(data[i][0]);
            }
        });

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

        $('#newRoleName').keyup(function(event) {
            if (event.which === 13) {
                $('#addRoleButton').click();
            }
        });
        window.scroll(0, 0);
    });
    </script>

    <?php
    }
    ?>
</div> <!-- End SUR_Container -->