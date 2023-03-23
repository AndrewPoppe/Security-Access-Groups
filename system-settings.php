<?php

namespace YaleREDCap\SystemUserRights;

require_once "SUR_User.php";

$tab = filter_input(INPUT_GET, "tab", FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? "userlist";

?>
<link href="https://cdn.datatables.net/v/dt/dt-1.13.3/b-2.3.5/b-html5-2.3.5/fc-4.2.1/datatables.min.css" rel="stylesheet" />
<script src="https://cdn.datatables.net/v/dt/dt-1.13.3/b-2.3.5/b-html5-2.3.5/fc-4.2.1/datatables.min.js"></script>
<script src="https://kit.fontawesome.com/015226af80.js" crossorigin="anonymous"></script>
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<!-- <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-rbsA2VBKQhggwzxH7pPCaAqO46MgnOM80zW1RWuH61DGLwZJEdK2Kadq2F9CUG65" crossorigin="anonymous">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-kenU1KFdBIe4zVF0s0G1M5b4hcpxyD9F7jL+jjXkk+Q2h455rYXK/7HAuoJl+0I4" crossorigin="anonymous"></script> -->

<link rel='stylesheet' type='text/css' href='<?= $module->getUrl('SystemUserRights.css') ?>' />
<h4 style='color:#900; margin-top: 0 0 10px;'>
    <i class='fa-solid fa-user-secret'></i>&nbsp;<span>System User Rights</span>
</h4>
<p style='margin:20px 0;max-width:1000px;'>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Nunc velit metus, venenatis in congue sed, ultrices sed nulla. Donec auctor bibendum mauris eget posuere. Ut rhoncus, nulla at auctor volutpat, urna odio ornare nulla, a ultrices neque massa sed est. Vestibulum dignissim feugiat turpis vel egestas. Integer eu purus vel dui egestas varius et ac erat. Donec blandit quam a enim faucibus ultrices. Aenean consectetur efficitur leo, et euismod arcu ultrices non. Ut et tincidunt tortor. Quisque eu interdum erat, vitae convallis ligula. Lorem ipsum dolor sit amet, consectetur adipiscing elit. Morbi interdum sapien nec quam blandit, vel faucibus turpis convallis. </p>

<div id="sub-nav" class="d-none d-sm-block" style="margin:5px 20px 15px 0px;">
    <ul>
        <li class="<?= $tab === "userlist" ? "active" : "" ?>">
            <a href="<?= $module->getUrl('system-settings.php?tab=userlist') ?>" style="font-size:13px;color:#393733;padding:7px 9px;">
                <i class="fa-solid fa-users"></i>
                Users
            </a>
        </li>
        <li class="<?= $tab === "roles" ? "active" : "" ?>">
            <a href="<?= $module->getUrl('system-settings.php?tab=roles') ?>" style="font-size:13px;color:#393733;padding:7px 9px;">
                <i class="fa-solid fa-user-tag"></i>
                Roles
            </a>
        </li>
    </ul>
</div>
<div class="clear"></div>

<?php if ($tab == "userlist") {

    $users = $module->getAllUserInfo();
    $roles = $module->getAllSystemRoles();

?>
    <table id='SUR-System-Table' class="compact hover">
        <thead>
            <tr>
                <th>Username</th>
                <th>Name</th>
                <th>Email</th>
                <!--<th>Suspended?</th>
                <th>Administrator?</th>
                <th>User's Sponsor</th>
                <th>Can create projects?</th>
                <th>Account Expiration</th>
                <th>Last Login</th> -->
                <th>Role</th>
                <th>Role Id</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($users as $user) {
                $thisUserRole = $module->getUserSystemRole($user["username"]); ?>
                <tr data-user="<?= $user["username"] ?>">
                    <td><?= $user["username"] ?></td>
                    <td><?= $user["user_firstname"] . " " . $user["user_lastname"] ?></td>
                    <td><a href="mailto:<?= $user["user_email"] ?>"><?= $user["user_email"] ?></a></td>
                    <!--<td><?= $user["user_suspended_time"] ?></td>
                    <td><?= $user["super_user"] ?></td>
                    <td><?= $user["user_sponsor"] ?></td>
                    <td><?= $user["allow_create_db"] ?></td>
                    <td><?= $user["user_expiration"] ?></td>
                    <td><?= $user["user_lastlogin"] ?></td>-->
                    <td data-role="<?= $thisUserRole ?>"><select class="roleSelect">
                            <?php
                            foreach ($roles as $role) {
                                echo "<option value='" . $role["role_id"] . "' " . ($role["role_id"] == $thisUserRole ? "selected" : "") . ">" . $role["role_name"] . "</option>";
                            }
                            ?>
                        </select>
                    </td>
                    <td class="hidden_role_id"><?= \REDCap::escapeHtml($thisUserRole) ?></td>
                </tr>
            <?php } ?>
        </tbody>
    </table>
    <script>
        const buttonCommon = {
            exportOptions: {
                format: {
                    body: function(html, row, col, node) {
                        if (col === 4) {
                            return $('#SUR-System-Table select').eq(row).val();
                        } else if (col === 3) {
                            return $('#SUR-System-Table select').eq(row).find('option:selected').text();
                        } else if (col === 2) {
                            // const value = $(node).data('value');
                            // return value == '' ? 0 : value;
                            return $(html).text();
                        } else {
                            return html;
                        }
                    }
                }
            }
        };
        let dt;
        dt = $('#SUR-System-Table').DataTable({
            paging: false,
            info: false,
            columnDefs: [{
                targets: [4],
                visible: false
            }, {
                targets: [3],
                data: function(row, type, val, meta) {
                    if (type === 'set') {
                        row.role = val;
                    } else if (type === 'filter') {
                        return $(`tr[data-user="${row[0]}"]`).find(':selected').text();
                    } else if (type === 'sort') {
                        return $(`tr[data-user="${row[0]}"]`).find(':selected').text();
                    }
                    return row.role;
                }
            }],
            buttons: [$.extend(true, {}, buttonCommon, {
                extend: 'csv',
                className: 'btn btn-sm btn-secondary',
                text: '<i class="fa-light fa-file-csv fa-xl fa-fw" style="line-height: 1;"></i>',
                attr: {
                    'data-toggle': "tooltip",
                    'data-placement': "bottom",
                    'title': "Export Table as CSV"
                }
            })],
            dom: 'ftB',
            initComplete: function() {
                $($(this).DataTable().buttons().nodes()[0]).removeClass('dt-button').attr('style', 'margin-top: 0.5rem;');
            }
        });
        $('.roleSelect').select2({
            minimumResultsForSearch: 20
        });
        $('.roleSelect').change(function() {
            const select = $(this);
            const tr = $(this).closest('tr');
            const user = tr.data('user');
            const newRole = select.val();

            const url = '<?= $module->getUrl("setUserRole.php") ?>';
            let color = "#66ff99";
            $.post(url, {
                    "username": user,
                    "role": newRole
                })
                .done(function(response) {
                    dt.rows().invalidate().draw();
                })
                .fail(function() {
                    color = "#ff3300";
                    select.val(select.closest('td').data('role')).select2();
                })
                .always(function() {
                    $(tr).find('td').effect('highlight', {
                        color: color
                    }, 3000);
                });

        })
    </script>
<?php


} else if ($tab == "roles") {
    // foreach ($module->getAllRights() as $key => $right) {
    //     echo "<p>" . $module->getDisplayTextForRight($right, $key) . "</p>";
    // }
    // $module->renderRoleEditTable([], false, "Test Role");
    $roles = $module->getAllSystemRoles();
    $displayTextForUserRights = $module->getDisplayTextForRights();

?>
    <!-- Modal -->
    <div class="modal" id="edit_role_popup" data-backdrop="static" data-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true"></div>


    <!-- Controls Container -->
    <div class="container ml-0 mt-2 mb-3 px-0" style="background-color: #eee; max-width: 550px; border: 1px solid #ccc;">
        <div class="d-flex flex-row justify-content-end my-1">
            <div class="dropdown">
                <button type="button" class="btn btn-primary btn-xs dropdown-toggle mr-2" data-toggle="dropdown" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="fa-solid fa-file-excel mr-1"></i>
                    <span>Import or Export Roles</span>
                    <span class="sr-only">Toggle Dropdown</span>
                </button>
                <ul class="dropdown-menu">
                    <li><a class="dropdown-item" onclick="exportCsv();">Export Roles as CSV</a></li>
                    <li><a class="dropdown-item" onclick="importCsv();">Import Roles</a></li>
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
                <input id="newRoleName" class="form-control form-control-sm" type="text" placeholder="Enter new system role name">
            </div>
            <div class="col ml-2 px-0 justify-content-start">
                <button class="btn btn-success btn-sm" id="addRoleButton" onclick="addNewRole();" title="Add a New System User Role">
                    <i class="fak fa-solid-tag-circle-plus fa-fw mr-1"></i>
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
                    <th data-key="role_name">Role</th>
                    <th data-key="role_id">Role ID</th>
                    <?php foreach ($displayTextForUserRights as  $key => $text) {
                        echo "<th data-key='" . \REDCap::escapeHtml($key) . "' class='dt-head-center'>" . \REDCap::escapeHtml($text) . "</th>";
                    } ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($roles as $role) {
                    $theseRights = json_decode($role["permissions"], true);
                ?>
                    <tr data-roleId="<?= \REDCap::escapeHtml($role["role_id"]) ?>">
                        <td><a class="SUR_roleLink text-primary" onclick="editRole($(this).closest('tr').data('roleid'));"><?= \REDCap::escapeHtml($role["role_name"]) ?></a></td>
                        <td><?= \REDCap::escapeHtml($role["role_id"]) ?></td>
                        <?php
                        $shieldcheck = '<i class="fa-solid fa-shield-check fa-xl" style="color: green;"></i>';
                        $check = '<i class="fa-solid fa-check fa-xl" style="color: green;"></i>';
                        $x = '<i class="fa-regular fa-xmark" style="color: #D00000;"></i>';
                        foreach ($displayTextForUserRights as $key => $text) {
                            if ($key === "randomization") {
                                $random_setup = $theseRights["random_setup"] ? "Setup" : "";
                                $random_dashboard = $theseRights["random_dashboard"] ? "Dashboard" : "";
                                $random_perform = $theseRights["random_perform"] ? "Randomize" : "";
                                $value = implode("<br>", array_filter([$random_setup, $random_dashboard, $random_perform]));
                            } else if ($key === "api") {
                                $api_export = $theseRights["api_export"] ? "Export" : "";
                                $api_import = $theseRights["api_import"] ? "Import" : "";
                                $value = implode("<br>", array_filter([$api_export, $api_import]));
                            } else if ($key === "double_data") {
                                switch ($theseRights[$key]) {
                                    case '1':
                                        $value = "Person #1";
                                        break;
                                    case '2':
                                        $value = "Person #2";
                                        break;
                                    default:
                                        $value = "Reviewer";
                                        break;
                                }
                            } else if ($key === "data_quality_resolution") {
                                $view = $theseRights["data_quality_resolution_view"] ? "View" : "";
                                $respond = $theseRights["data_quality_resolution_respond"] ? "Respond" : "";
                                $open = $theseRights["data_quality_resolution_open"] ? "Open" : "";
                                $close = $theseRights["data_quality_resolution_close"] ? "Close" : "";
                                $value = implode("<br>", array_filter([$view, $respond, $open, $close]));
                            } else if ($key === "lock_record") {
                                switch ($theseRights[$key]) {
                                    case '2':
                                        $value = $shieldcheck;
                                        break;
                                    case '1':
                                        $value = $check;
                                        break;
                                    default:
                                        $value = $x;
                                        break;
                                }
                            } else if ($key === 'data_entry') {
                                $theseRights[$key] = $theseRights['dataViewing'];
                                switch ($theseRights[$key]) {
                                    case '3':
                                        $value = "View & Edit Forms and Survey Responses";
                                        break;
                                    case '2':
                                        $value = "View & Edit Forms";
                                        break;
                                    case '1':
                                        $value = "Read Only";
                                        break;
                                    default:
                                        $value = $x;
                                        break;
                                }
                            } else if ($key === 'data_export_tool') {
                                $theseRights[$key] = $theseRights['dataExport'];
                                switch ($theseRights[$key]) {
                                    case '3':
                                        $value = "Full Data Set";
                                        break;
                                    case '2':
                                        $value = "Remove Identifiers";
                                        break;
                                    case '1':
                                        $value = "De-Identified";
                                        break;
                                    default:
                                        $value = $x;
                                        break;
                                }
                            } else {
                                $value = $theseRights[$key] == "1" ? $check : $x;
                            }
                            if (is_null($value)) {
                                $value = 'OK';
                            }
                            if ($value == "") $value = $x;
                            echo "<td data-value='" . \REDCap::escapeHtml($theseRights[$key]) . "' class='dt-body-center'>" . $value . "</td>";
                        } ?>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
    <script>
        const Toast = Swal.mixin({
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
                        $.post("<?= $module->getUrl("deleteSystemRole.php") ?>", {
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
                                Swal.fire('Error', error.responseText, 'error');
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
                            $.post('<?= $module->getUrl("editSystemRole.php") ?>', data)
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
                                        icon: 'error'
                                    })
                                    .then(() => {
                                        $('input[name=role_name_edit]').focus();
                                    })
                            }
                        });
                        $('#SUR_Save').click(role_id == "" ? saveNewRoleButtonCallback : saveRoleChangesButtonCallback);
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
            const url = "<?= $module->getUrl("editSystemRole.php?newRole=false") ?>";
            openRoleEditor(url, role_id, role_name);
        }

        function addNewRole() {
            $('#addRoleButton').blur();
            const url = "<?= $module->getUrl("editSystemRole.php?newRole=true") ?>";
            const newRoleName = $('#newRoleName').val();
            if (newRoleName == "") {
                Swal.fire({
                    title: "You must specify a role name",
                    icon: "error",
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
            return d.getFullYear() + '-' + (d.getMonth() + 1).toString().padStart(2, 0) + '-' + (d.getDate()).toString().padStart(2, 0)
        }

        function exportCsv() {
            const newLine = navigator.userAgent.match(/Windows/) ? '\r\n' : '\n';
            const escapeChar = '"';
            const boundary = '"';
            const separator = ',';
            const extension = '.csv';
            const reBoundary = new RegExp(boundary, 'g');
            const filename = 'SystemRoles_' + formatNow() + extension;
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
                    header: function(html, col, node) {
                        return ($(node).data('key'));
                    },
                    body: function(html, row, col, node) {
                        if (col === 0) {
                            return $(html).text();
                        } else if (col === 1) {
                            return html;
                        } else {
                            const value = $(node).data('value');
                            return value == '' ? 0 : value;
                        }
                    }
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
                $.post("<?= $module->getUrl("importCsvRoles.php") ?>", {
                        data: e.target.result
                    })
                    .done((response) => {
                        console.log(JSON.parse(response));
                    })
                    .fail((error) => {
                        console.error(error);
                    })
                    .always(() => {
                        Swal.fire("Sorry", "That feature is not yet implemented.");
                    })
            };
            reader.readAsText(file);
        }


        $(document).ready(function() {
            const importFileElement = document.getElementById("importRolesFile");
            importFileElement.addEventListener("change", handleFiles, false);

            const table = $('#roleTable').DataTable({
                searching: false,
                info: false,
                paging: false,
                rowReorder: true,
                ordering: false,
                fixedHeader: false,
                fixedColumns: true,
                scrollX: true,
                initComplete: function() {
                    $('#roleTableWrapper').show();
                    // THIS IS USING JQUERY UI'S TOOLTIPS BECAUSE GARBAGE
                    $('[data-toggle="tooltip"]').tooltip({
                        show: false,
                        hide: false,
                        tooltipClass: "bottom",
                        position: {
                            my: "center top",
                            at: "center bottom+10",
                            collision: 'none'
                        }
                    });
                },
                columnDefs: [{
                    targets: 1,
                    visible: false
                }, {
                    targets: 0,
                    data: function(row, type, val, meta) {
                        if (type === 'set') {
                            row.role_display = val;
                            row.role_name = $(val).text();
                        } else if (type === 'display') {
                            return row.role_display;
                        }
                        return row.role_name;
                    }
                }]
            });

            table.on('draw', function() {
                $('.dataTable tbody tr').each((i, row) => {
                    row.onmouseenter = hover;
                    row.onmouseleave = dehover;
                });
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

        });
        window.scroll(0, 0);
    </script>
<?php
}
