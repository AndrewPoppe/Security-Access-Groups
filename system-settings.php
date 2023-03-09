<?php

namespace YaleREDCap\SystemUserRights;

require_once "SUR_User.php";

$tab = filter_input(INPUT_GET, "tab", FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? "userlist";

?>
<link href="https://cdn.datatables.net/v/dt/dt-1.13.3/b-2.3.5/b-html5-2.3.5/fc-4.2.1/datatables.min.css" rel="stylesheet" />
<script src="https://cdn.datatables.net/v/dt/dt-1.13.3/b-2.3.5/b-html5-2.3.5/fc-4.2.1/datatables.min.js"></script>
<script src="https://kit.fontawesome.com/8dcbb2bf31.js" crossorigin="anonymous"></script>
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<link rel='stylesheet' type='text/css' href='<?= $module->getUrl('SystemUserRights.css') ?>' />
<h4 style='color:#900; margin-top: 0 0 10px;'>
    <i class='fas fa-user-secret'></i>&nbsp;<span>System User Rights</span>
</h4>
<p style='margin:20px 0;max-width:1000px;'>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Nunc velit metus, venenatis in congue sed, ultrices sed nulla. Donec auctor bibendum mauris eget posuere. Ut rhoncus, nulla at auctor volutpat, urna odio ornare nulla, a ultrices neque massa sed est. Vestibulum dignissim feugiat turpis vel egestas. Integer eu purus vel dui egestas varius et ac erat. Donec blandit quam a enim faucibus ultrices. Aenean consectetur efficitur leo, et euismod arcu ultrices non. Ut et tincidunt tortor. Quisque eu interdum erat, vitae convallis ligula. Lorem ipsum dolor sit amet, consectetur adipiscing elit. Morbi interdum sapien nec quam blandit, vel faucibus turpis convallis. </p>

<div id="sub-nav" class="d-none d-sm-block" style="margin:5px 20px 15px 0px;">
    <ul>
        <li class="<?= $tab === "userlist" ? "active" : "" ?>">
            <a href="<?= $module->getUrl('system-settings.php?tab=userlist') ?>" style="font-size:13px;color:#393733;padding:7px 9px;">
                <i class="fas fa-users"></i>
                Users
            </a>
        </li>
        <li class="<?= $tab === "roles" ? "active" : "" ?>">
            <a href="<?= $module->getUrl('system-settings.php?tab=roles') ?>" style="font-size:13px;color:#393733;padding:7px 9px;">
                <i class="fas fa-user-tag"></i>
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
                </tr>
            <?php } ?>
        </tbody>
    </table>
    <script>
        let dt;
        dt = $('#SUR-System-Table').DataTable({
            paging: false,
            info: false,
            columnDefs: [{
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
            }]
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

    <!-- Role Table -->
    <!-- <div id="roleTableWrapper" style="display: none; width: calc(min(100vw,1600px) - 270px);"> -->
    <div id="roleTableWrapper" style="display: none; width: 100%;">
        <table id="roleTable" class="roleTable cell-border" style="width: 100%">
            <!-- <table id="roleTable" class="table table-striped table-hover table-bordered table-responsive align-middle" style="width: 100%;"> -->
            <thead>
                <tr style="vertical-align: bottom; text-align: center;">
                    <th>Role</th>
                    <th>Role ID</th>
                    <?php foreach ($displayTextForUserRights as  $text) {
                        echo "<th class='dt-head-center'>" . \REDCap::escapeHtml($text) . "</th>";
                    } ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($roles as $role) {
                    $theseRights = json_decode($role["permissions"], true);
                ?>
                    <tr data-roleId="<?= \REDCap::escapeHtml($role["role_id"]) ?>">
                        <td><a class="SUR_roleLink" onclick="editRole('<?= $role['role_id'] ?>', '<?= $role['role_name'] ?>');"><?= \REDCap::escapeHtml($role["role_name"]) ?></a></td>
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
                                switch ($theseRights['dataViewing']) {
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
                                switch ($theseRights['dataExport']) {
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
    <div style="margin-top:1rem;" id="buttonsContainer">
        <button class="btn btn-success btn-sm" id="addRoleButton" onclick="addNewRole();">Add New Role</button>
    </div>
    <script>
        function openRoleEditor(url, role_id = "", role_name = "") {
            const deleteRoleButtonCallback = function() {
                Swal.fire({
                    title: 'Are you sure you want to delete this role?',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#dc3545',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Delete Role'
                }).then((result) => {
                    if (result.isConfirmed) {
                        Swal.showLoading();
                        $.post("<?= $module->getUrl("deleteSystemRole.php") ?>", {
                                role_id: role_id
                            })
                            .done(function(response) {
                                Swal.fire(
                                        'The role was deleted',
                                        '',
                                        'success'
                                    )
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
                        confirmButtonColor: '#17a2b8',
                        cancelButtonColor: '#6c757d',
                        confirmButtonText: 'Copy Role'
                    })
                    .then(function(result) {
                        if (result.isConfirmed) {
                            const role_name = result.value;
                            data.role_name_edit = role_name;
                            $.post('<?= $module->getUrl("editSystemRole.php?newRole=true") ?>', data)
                                .done(function(result) {
                                    Swal.fire(
                                            'The role was copied',
                                            '',
                                            'success'
                                        )
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
                if ($('input[name="role_name_edit"]').val() != '') {
                    const data = $("#SUR_Role_Setting").serializeObject();
                    data.role_id = role_id;
                    $.post(url, data)
                        .done(function(response) {
                            Swal.fire({
                                icon: "success",
                                title: `Role "${role_name}" Successfully Saved`
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
                if ($('input[name="role_name_edit"]').val() != '') {
                    const data = $("#SUR_Role_Setting").serializeObject();
                    $.post(url, data)
                        .done(function(response) {
                            Swal.fire({
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
            const url = "<?= $module->getUrl("editSystemRole.php?newRole=true") ?>";
            openRoleEditor(url);
        }

        $(document).ready(function() {
            const buttonCommon = {
                exportOptions: {
                    format: {
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
                }
            };
            const table = $('#roleTable').DataTable({
                buttons: [
                    $.extend(true, {}, buttonCommon, {
                        extend: 'csvHtml5',
                        className: 'btn btn-sm btn-secondary',
                        text: 'Export Table'
                    })
                ],
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
                },
                dom: "tB",
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

            table
                .buttons()
                .container()
                .appendTo('#buttonsContainer');
            $(table.buttons().nodes()[0]).removeClass('dt-button').attr('style', 'margin-right: 5px;');

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
