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
            <li class="<?= $tab === "userlist" ? "active" : "" ?>">
                <a href="<?= $module->framework->getUrl('system-settings.php?tab=userlist') ?>"
                    style="font-size:13px;color:#393733;padding:7px 9px;">
                    <i class="fa-solid fa-users"></i>
                    Users
                </a>
            </li>
            <li class="<?= $tab === "sags" ? "active" : "" ?>">
                <a href="<?= $module->framework->getUrl('system-settings.php?tab=sags') ?>"
                    style="font-size:13px;color:#393733;padding:7px 9px;">
                    <i class="fa-solid fa-user-tag"></i>
                    Security Access Groups
                </a>
            </li>
            <li>
                <a href="<?= $module->framework->getUrl('system-reports.php') ?>"
                    style="font-size:13px;color:#393733;padding:7px 9px;">
                    <i class="fa-solid fa-memo"></i>
                    Reports
                </a>
            </li>
        </ul>
    </div>
    <div class="clear"></div>

    <?php if ( $tab == "userlist" ) {
        $sags = $module->getAllSags();
        ?>

    <p style='margin:20px 0;max-width:1000px;font-size:14px;'>This table shows all users in the REDCap system and their
        current SAG assignment. Use the <strong>Edit Users</strong> button to change a user's SAG assignment. You may
        export the current list of SAG assignments or import a CSV file of assignments using the buttons below.
    </p>

    <!-- Modals -->
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
        <table aria-label="template table" id="templateTable">
            <thead>
                <tr>
                    <th>username</th>
                    <th>sag_id</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ( $sags as $index => $sag ) {
                        echo "<tr><td>example_user_",
                            (intval($index) + 1),
                            "</td><td>",
                            \REDCap::escapeHtml($sag["sag_id"]),
                            "</td></tr>";
                    } ?>
            </tbody>
        </table>
    </div>
    <!-- Users Table -->
    <div class="card card-body bg-light" style="min-width:700px;">
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
                        <li><a class="dropdown-item" onclick="handleCsvExport();"><i
                                    class="fa-sharp fa-regular fa-file-arrow-down fa-fw mr-1 text-success">
                                </i>Export User Assignments</a></li>
                        <li><a class="dropdown-item" onclick="importCsv();"><i
                                    class="fa-sharp fa-solid fa-file-arrow-up fa-fw mr-1 text-danger"></i>Import User
                                Assignments</a></li>
                        <li><a class="dropdown-item" onclick="downloadTemplate();"><i
                                    class="fa-sharp fa-solid fa-download fa-fw mr-1 text-primary"></i>Download Import
                                Template</a></li>
                    </ul>
                </div>

            </div>
        </div>
        <table aria-label='Users Table' id='SUR-System-Table' class="compact cell-border border">
            <thead>
                <tr>
                    <th data-id="username" class="py-3">Username</th>
                    <th data-id="name" class="py-3">Name</th>
                    <th data-id="email" class="py-3">Email</th>
                    <th data-id="sag" class="py-3">SAG Name</th>
                    <th data-id="sag_id" class="py-3">SAG ID</th>
                </tr>
            </thead>
            <tbody>
            </tbody>
        </table>
    </div>

    <script>
    window.sags = {
        <?php foreach ( $sags as $sag ) {
                    echo "'" . $sag["sag_id"] . "': '" . $sag["sag_name"] . "',";
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
        $('.sagSelect').attr('disabled', !editing);
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
        $('.sagSelect').select2({
            minimumResultsForSearch: 20,
            templateSelection: function(selection) {
                return $(
                    `<div class="d-flex justify-content-between">` +
                    `<strong>${selection.text}</strong>&nbsp;` +
                    `<span class="text-secondary" style="${style}">${selection.id}</span>` +
                    `</div>`
                );
            },
            templateResult: function(option) {
                return $(
                    `<span><strong>${option.text}</strong><br><span class="text-secondary">${option.id}</span></span>`
                );
            }
        });
    }

    function handleCsvExport() {
        const dt = $('#SUR-System-Table').DataTable();
        if (dt.search() != '') {
            Swal.fire({
                title: 'Export Filtered Data?',
                text: 'You have a filter applied to the table. Do you want to export the filtered data or all data?',
                icon: 'question',
                showCancelButton: true,
                showDenyButton: true,
                confirmButtonText: 'Export Filtered Data',
                denyButtonText: 'Export All Data'
            }).then((result) => {
                if (result.isConfirmed) {
                    exportCsv(true);
                } else if (result.isDenied) {
                    exportCsv();
                }
            });
        } else {
            exportCsv();
        }
    }

    function exportCsv(useFilter = false) {
        const dt = $('#SUR-System-Table').DataTable();
        const newLine = navigator.userAgent.match(/Windows/) ? '\r\n' : '\n';
        const escapeChar = '"';
        const boundary = '"';
        const separator = ',';
        const extension = '.csv';
        const reBoundary = new RegExp(boundary, 'g');
        const filename = 'SecurityAccessGroups_Users_' + (useFilter ? 'FILTERED_' : '') + formatNow() + extension;
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


        const useSearch = useFilter ? 'applied' : 'none';
        const allData = dt.rows({
            search: useSearch,
            page: 'all'
        }).data();
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
                        return window.sags[allData[row]["sag"]];
                    } else if (col === 4) {
                        return allData[row]["sag"];
                    }
                }
            },
            modifier: {
                page: 'all',
                search: useSearch

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
                                "<div class='row justify-content-center m-2'>" +
                                "<table><thead><tr><th>Username</th></tr></thead><tbody>";
                            response.users.forEach((user) => {
                                body += `<tr><td>${user}</td></tr>`;
                            });
                            body += "</tbody></table></div>";
                        }
                        if (response.sags.length) {
                            body +=
                                "<div class='row justify-content-center m-2'>" +
                                "<table><thead><tr><th>SAG ID</th></tr></thead><tbody>";
                            response.sags.forEach((sag) => {
                                body += `<tr><td>${sag}</td></tr>`;
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
        const filename = 'SecurityAccessGroups_Users_ImportTemplate' + extension;
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

    function saveSag(selectNode) {
        const select = $(selectNode);
        const tr = $(selectNode).closest('tr');
        const user = tr.data('user');
        const newSag = select.val();

        const url = '<?= $module->framework->getUrl("ajax/assignSag.php") ?>';
        let color = "#66ff99";
        const dt = $('#SUR-System-Table').DataTable();
        $.post(url, {
                "username": user,
                "sag": newSag
            })
            .done(function(response) {
                select.closest('td').data('sag', newSag);
                select.closest('td').attr('data-sag', newSag);
                const rowIndex = dt.row(select.closest('tr')).index();
                dt.cell(rowIndex, 4).data(newSag);
            })
            .fail(function() {
                color = "#ff3300";
                select.val(select.closest('td').data('sag')).select2();
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
        const style = editing ? 'user-select:all; cursor: text; margin-left: 1px; margin-right: 1px;' : 'none';

        $('.sagSelect').select2({
            minimumResultsForSearch: 20,
            templateSelection: function(selection) {
                return $(
                    `<div class="d-flex justify-content-between">` +
                    `<strong>${selection.text}</strong>&nbsp;` +
                    `<span class="text-secondary" style="${style}">${selection.id}</span>` +
                    `</div>`
                );
            },
            templateResult: function(option) {
                return $(
                    `<span><strong>${option.text}</strong><br><span class="text-secondary">${option.id}</span></span>`
                );
            }
        });
        $('.sagSelect').attr('disabled', !editing);
    }

    $(document).ready(function() {
        const importFileElement = document.getElementById("importUsersFile");
        importFileElement.addEventListener("change", handleFiles, false);
        const dt = $('#SUR-System-Table').DataTable({
            ajax: {
                url: '<?= $module->framework->getUrl("ajax/users.php") ?>',
                type: 'POST'
            },
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
                            const root = `${app_path_webroot_full}redcap_v${redcap_version}`;
                            const href =
                                `${root}/ControlCenter/view_users.php?username=${row.username}`;
                            const attrs = `target="_blank" rel="noopener noreferrer"`;
                            return `<a class="user-link" href="${href}" ${attrs}>${row.username}</a>`;
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
                        if (row.sag === null) {
                            row.sag = '<?= $module->defaultSagId ?>';
                        }
                        if (type === 'filter') {
                            return row.sag + ' ' + window.sags[row.sag];
                        } else if (type === 'sort') {
                            return window.sags[row.sag];
                        } else {
                            let result =
                                `<select class="sagSelect" disabled="true" onchange="saveSag(this)">`;
                            for (let sag_id in sags) {
                                const sag_label = sags[sag_id];
                                const selected = sag_id == row.sag ?
                                    "selected" : "";
                                result +=
                                    `<option value='${sag_id}' ${selected}>${sag_label}</option>`;
                            }
                            result += `</select>`;
                            return result;
                        }
                    }
                },
                {
                    title: 'Hidden SAG',
                    data: 'sag'
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
                    $(td).attr('data-sag', rowData.sag);
                }
            }, {
                targets: [4],
                visible: false,
                createdCell: function(td, cellData, rowData, row, col) {
                    $(td).addClass('hidden_sag_id');
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


    } elseif ( $tab == "sags" ) {
        $displayTextForUserRights    = $module->getDisplayTextForRights();
        $allDisplayTextForUserRights = $module->getDisplayTextForRights(true);

        ?>

    <p style='margin:20px 0;max-width:1000px;font-size:14px;'>This table shows all the SAGs that currently exist in the
        system. A SAG must be created here before it can be assigned to a user. The current list of SAGs can be exported
        as a CSV file, and a CSV file can be imported to update existing SAGs or to create new SAGs.
    </p>

    <!-- Modal -->
    <div class="modal" id="edit_sag_popup" data-backdrop="static" data-keyboard="false"
        aria-labelledby="staticBackdropLabel" aria-hidden="true"></div>


    <!-- Controls Container -->
    <div class="container ml-0 mt-2 mb-3 px-0"
        style="background-color: #eee; max-width: 550px; border: 1px solid #ccc;">
        <div class="d-flex flex-row justify-content-end my-1">
            <div class="dropdown">
                <button type="button" class="btn btn-primary btn-xs dropdown-toggle mr-2" data-toggle="dropdown"
                    data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="fa-sharp fa-file-excel"></i>
                    <span>Import or Export SAGs</span>
                    <span class="sr-only">Toggle Dropdown</span>
                </button>
                <ul class="dropdown-menu">
                    <li><a class="dropdown-item" onclick="exportRawCsv();"><i
                                class="fa-sharp fa-regular fa-file-arrow-down fa-fw mr-1 text-info"></i>Export SAGs
                            (raw)</a></li>
                    <li><a class="dropdown-item" onclick="exportCsv();"><i
                                class="fa-sharp fa-regular fa-file-arrow-down fa-fw mr-1 text-success"></i>Export SAGs
                            (labels)</a></li>
                    <li><a class="dropdown-item" onclick="importCsv();"><i
                                class="fa-sharp fa-solid fa-file-arrow-up fa-fw mr-1 text-danger"></i>Import SAGs</a>
                    </li>
                    <li><a class="dropdown-item" onclick="exportRawCsv(false);"><i
                                class="fa-sharp fa-solid fa-download fa-fw mr-1 text-primary"></i>Download Import
                            Template</a></li>
                </ul>
            </div>
            <div class="hidden">
                <input type="file" accept="text/csv" class="form-control-file" id="importSagsFile">
            </div>
        </div>
        <div class="row ml-2">
            <span><strong>Create new Security Access Group:</strong></span>
        </div>
        <div class="row ml-2 mb-2 mt-1 justify-content-start">
            <div class="col-6 px-0">
                <input id="newSagName" class="form-control form-control-sm" type="text"
                    placeholder="Enter new SAG name">
            </div>
            <div class="col ml-1 px-0 justify-content-start">
                <button class="btn btn-success btn-sm" id="addSagButton" onclick="addNewSag();"
                    title="Add a New Security Access Group">
                    <i class="fa-kit fa-solid-tag-circle-plus fa-fw"></i>
                    <span>Create SAG</span>
                </button>
            </div>
        </div>
    </div>


    <!-- SAG Table -->
    <div class=" clear">
    </div>
    <div id="sagTableWrapper" style="display: none; width: 100%;">
        <table aria-label="SAGs Table" id="sagTable" class="sagTable cell-border" style="width: 100%">
            <thead>
                <tr style="vertical-align: bottom; text-align: center;">
                    <th>Order</th>
                    <th data-key="sag_name">SAG Name</th>
                    <th data-key="sag_id">SAG ID</th>
                    <?php foreach ( $allDisplayTextForUserRights as $key => $text ) {
                            echo "<th data-key='",
                                \REDCap::escapeHtml($key),
                                "' class='dt-head-center'>",
                                \REDCap::escapeHtml($text),
                                "</th>";
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

    function openSagEditor(url, sag_id = "", sag_name = "") {
        const deleteSagButtonCallback = function() {
            Swal.fire({
                title: 'Are you sure you want to delete this SAG?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Delete SAG',
                customClass: {
                    confirmButton: 'btn btn-danger m-1',
                    cancelButton: 'btn btn-secondary m-1'
                },
                buttonsStyling: false
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.showLoading();
                    $.post("<?= $module->framework->getUrl("ajax/deleteSag.php") ?>", {
                            sag_id: sag_id
                        })
                        .done(function(response) {
                            Toast.fire({
                                    title: 'The SAG was deleted',
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
        const copySagButtonCallback = function() {
            const data = $("#SAG_Setting").serializeObject();
            Swal.fire({
                    title: 'What would you like the new SAG to be called?',
                    input: 'text',
                    inputValue: `${data["sag_name_edit"]} Copy`,
                    showCancelButton: true,
                    confirmButtonText: 'Copy SAG',
                    customClass: {
                        confirmButton: 'btn btn-info m-1',
                        cancelButton: 'btn btn-secondary m-1'
                    },
                    buttonsStyling: false
                })
                .then(function(result) {
                    if (result.isConfirmed) {
                        const sag_name = result.value;
                        data.sag_name_edit = sag_name;
                        data.newSag = 1;
                        $.post('<?= $module->framework->getUrl("ajax/editSag.php") ?>', data)
                            .done(function(result) {
                                Toast.fire({
                                        icon: 'success',
                                        title: 'The SAG was copied'
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
        const saveSagChangesButtonCallback = function() {
            $('input[name="sag_name_edit"]').blur();
            const sag_name_edit = $('input[name="sag_name_edit"]').val();
            if (sag_name_edit != '') {
                const data = $("#SAG_Setting").serializeObject();
                data.sag_id = sag_id;
                $.post(url, data)
                    .done(function(response) {
                        Toast.fire({
                            icon: "success",
                            title: `SAG "${sag_name_edit}" Successfully Saved`
                        }).then(function() {
                            window.location.reload();
                        })
                    })
                    .fail(function(error) {
                        console.error(error.responseText);
                    });
            }
        };
        const saveNewSagButtonCallback = function() {
            $('input[name="sag_name_edit"]').blur();
            const sag_name_edit = $('input[name="sag_name_edit"]').val();
            if (sag_name_edit != '') {
                const data = $("#SAG_Setting").serializeObject();
                $.post(url, data)
                    .done(function(response) {
                        Toast.fire({
                            icon: "success",
                            title: `SAG Successfully Created`
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
                sag_id: sag_id,
                sag_name: sag_name
            })
            .done(function(response) {
                $("#edit_sag_popup").html(response);
                $("#edit_sag_popup").on('shown.bs.modal', function(event) {
                    $('input[name="sag_name_edit"]').blur(function() {
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
                                    $('input[name=sag_name_edit]').focus();
                                })
                        }
                    });
                    $('#SAG_Save').click(sag_id == "" ? saveNewSagButtonCallback :
                        saveSagChangesButtonCallback);
                    if ($('#SAG_Copy')) $('#SAG_Copy').click(copySagButtonCallback);
                    if ($('#SAG_Delete')) $('#SAG_Delete').click(deleteSagButtonCallback);
                })
                $("#edit_sag_popup").modal('show');
            })
            .fail(function(error) {
                console.error(error.responseText)
            });
    }

    function editSag(sag_id, sag_name) {
        const url = "<?= $module->framework->getUrl("ajax/editSag.php?newSag=false") ?>";
        openSagEditor(url, sag_id, sag_name);
    }

    function addNewSag() {
        $('#addSagButton').blur();
        const url = "<?= $module->framework->getUrl("ajax/editSag.php?newSag=true") ?>";
        const newSagName = $('#newSagName').val().trim();
        if (newSagName == "") {
            Toast.fire({
                title: "You must specify a SAG name",
                icon: "error",
                showConfirmButton: false,
                didClose: () => {
                    $("#newSagName").focus()
                }
            });
        } else {
            openSagEditor(url, "", newSagName);
        }
    }

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

    function importCsv() {
        $('#importSagsFile').click();
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
            $.post("<?= $module->framework->getUrl("ajax/importCsvSags.php") ?>", {
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
        $.post("<?= $module->framework->getUrl("ajax/importCsvSags.php") ?>", {
                data: window.csv_file_contents,
                confirm: true
            })
            .done((response) => {
                if (response == true) {
                    Swal.fire({
                            icon: 'success',
                            html: "Successfully imported Security Access Group definitions.",
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

    $(document).ready(function() {
        const importFileElement = document.getElementById("importSagsFile");
        importFileElement.addEventListener("change", handleFiles, false);

        const shieldcheck = '<i class="fa-solid fa-shield-check fa-xl" style="color: green;"></i>';
        const check = '<i class="fa-solid fa-check fa-xl" style="color: green;"></i>';
        const x = '<i class="fa-regular fa-xmark" style="color: #D00000;"></i>';
        const table = $('#sagTable').DataTable({
            ajax: {
                url: '<?= $module->framework->getUrl("ajax/sags.php") ?>',
                method: 'POST'
            },
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
                $('#sagTableWrapper').show();
                const table = this.api();


                const theseSettingsString = localStorage.getItem('DataTables_sagOrder');
                if (theseSettingsString) {
                    const theseSettings = JSON.parse(theseSettingsString);
                    table.rows().every(function(rowIdx, tableLoop, rowLoop) {
                        const thisSagId = table.cell(rowLoop, 2).data();
                        const desiredIndex = theseSettings.indexOf(thisSagId);
                        table.cell(rowLoop, 0).data(desiredIndex);
                    });
                    table.order([0, 'asc']).draw();
                } else {
                    const order = table.column(2).data().toArray();
                    localStorage.setItem('DataTables_sagOrder', JSON.stringify(order));
                }

                table.on('draw', function() {
                    $('.dataTable tbody tr').each((i, row) => {
                        row.onmouseenter = hover;
                        row.onmouseleave = dehover;
                    });
                });

                table.on('row-reordered', function(e, diff, edit) {
                    setTimeout(() => {
                        const order = table.column(2).data().toArray();
                        localStorage.setItem('DataTables_sagOrder', JSON.stringify(
                            order));
                    }, 0);
                });

                table.rows().every(function() {
                    const rowNode = this.node();
                    const rowIndex = this.index();
                    $(rowNode).attr('data-dt-row', rowIndex);
                });

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
                setTimeout(() => {
                    table.stateRestore();
                    table.columns.adjust().draw();
                }, 0);
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
                            const iclass =
                                "fa-solid  fa-grip-dots-vertical mr-2 dt-rowReorder-grab text-secondary";
                            const aclass = "SagLink text-primary";
                            return `<div style="display: flex; align-items: center; white-space: nowrap;">` +
                                `<i class="${iclass}"></i>` +
                                `<a class="${aclass}" onclick="editSag('${row.sag_id}')">${row.sag_name}</a>` +
                                `</div>`;
                        } else {
                            return row.sag_name;
                        }
                    }
                },
                {
                    className: 'sag-id-column user-select-all',
                    data: 'sag_id'
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

        $('#newSagName').keyup(function(event) {
            if (event.which === 13) {
                $('#addSagButton').click();
            }
        });
        window.scroll(0, 0);
    });
    </script>

    <?php
    }
    ?>
</div> <!-- End SAG_Container -->