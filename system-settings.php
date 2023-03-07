<?php

namespace YaleREDCap\SystemUserRights;

require_once "SUR_User.php";

$tab = filter_input(INPUT_GET, "tab", FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? "userlist";

?>
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
        $('#SUR-System-Table').DataTable({
            lengthMenu: [
                [10, 25, 50, 100, -1],
                [10, 25, 50, 100, "All"]
            ],
            paging: false,
            info: false
        });
        $('.roleSelect').select2();
        $('.roleSelect').change(function() {
            const select = $(this);
            console.log(select);
            const tr = $(this).closest('tr');
            const user = tr.data('user');

            const url = '<?= $module->getUrl("setUserRole.php") ?>';
            console.log(user, select.val());
            let color = "#66ff99";
            $.post(url, {
                    "username": user,
                    "role": select.val()
                })
                .done(function(response) {
                    console.log(response);
                    console.log(JSON.parse(response));
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
    <table id="roleTable" class="hover cell-border" style="width: 100%">
        <thead>
            <tr style="vertical-align: bottom; text-align: center;">
                <th>Role</th>
                <?php foreach ($displayTextForUserRights as $text) {
                    echo "<th>" . \REDCap::escapeHtml($text) . "</th>";
                } ?>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($roles as $role) {
                $theseRights = json_decode($role["permissions"], true);
            ?>
                <tr data-roleId="<?= \REDCap::escapeHtml($role["role_id"]) ?>">
                    <td><a class="SUR_roleLink" onclick="editRole('<?= $role['role_id'] ?>', '<?= $role['role_name'] ?>');"><?= \REDCap::escapeHtml($role["role_name"]) ?></a></td>
                    <?php foreach ($displayTextForUserRights as $key => $text) {
                        echo "<td>" . $theseRights[$key] . "</td>";
                    } ?>
                </tr>
            <?php } ?>
        </tbody>
    </table>
    <button id="addRoleButton" onclick="addNewRole();">Add New Role</button>
    <div id="edit_role_popup"></div>

    <script>
        function openRoleEditor(url, buttons, role_id = "", role_name = "") {
            $.get(url, {
                    role_id: role_id,
                    role_name: role_name
                })
                .done(function(response) {
                    console.log(response);
                    $("#edit_role_popup").html(response);
                    const title = $('#dialog_title').html();
                    $('#edit_role_popup').dialog({
                        bgiframe: true,
                        modal: true,
                        width: 1250,
                        title: title,
                        open: function() {
                            $('.ui-dialog-buttonpane').find('button:last').css({
                                'font-weight': 'bold',
                                'color': '#222'
                            }).focus();
                            if ($('.ui-dialog-buttonpane button').length > 2) {
                                if ($('.ui-dialog-buttonpane button').length == 3) {
                                    // Stylize the delete button
                                    $('.ui-dialog-buttonpane').find('button:eq(0)').css({
                                        'color': '#C00000',
                                        'font-size': '11px',
                                        'margin': '9px 0 0 40px'
                                    });
                                } else {
                                    // Stylize the delete button AND copy button
                                    $('.ui-dialog-buttonpane').find('button:eq(0)').css({
                                        'color': '#C00000',
                                        'font-size': '11px',
                                        'margin': '9px 0 0 5px'
                                    });
                                    $('.ui-dialog-buttonpane').find('button:eq(1)').css({
                                        'color': '#000066',
                                        'font-size': '11px',
                                        'margin': '9px 0 0 40px'
                                    });
                                }
                            }
                            fitDialog(this);
                        },
                        buttons: buttons,
                        close: function() {
                            console.log('Close');
                        }
                    });
                    $('input[name="role_name_edit"]').blur(function() {
                        $(this).val($(this).val().trim());
                        if ($(this).val() == '') {
                            simpleDialog('<?= $lang['rights_358'] ?>', '<?= $lang['alerts_24'] ?>', null, null, function() {
                                $('input[name=role_name_edit]').focus();
                            }, 'Close');
                        }
                    });
                })
                .fail(function() {
                    color = "#ff3300";
                    console.log("ERROR")
                })
                .always(function() {
                    console.log("COMPLETE");
                });
        }

        function editRole(role_id, role_name) {
            const url = "<?= $module->getUrl("editSystemRole.php?newRole=false") ?>";
            const buttons = [{
                // Delete Role
                text: "<?= $lang["rights_190"] ?>",
                click: function() {
                    Swal.fire({
                        title: 'Are you sure you want to delete this role?',
                        text: "You won't be able to revert this!",
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#3085d6',
                        cancelButtonColor: '#d33',
                        confirmButtonText: 'Yes, delete it!'
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
                                            $('#edit_role_popup').html('');
                                            $('#edit_role_popup').dialog('destroy');
                                        });
                                })
                                .fail(function(error) {
                                    console.error(error.responseText);
                                    Swal.fire('Error', error.responseText, 'error');
                                })
                                .always(function() {});
                        }
                    });
                }
            }, {
                // Copy Role
                text: "<?= $lang["rights_211"] ?>",
                click: function() {
                    console.log("COPY ROLE");
                }
            }, {
                // Cancel
                text: "<?= $lang["global_53"] ?>",
                click: function() {
                    $('#edit_role_popup').html('');
                    $(this).dialog('destroy');
                }
            }, {
                // Save Changes
                text: "<?= $lang["report_builder_28"] ?>",
                click: function() {
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
                }
            }];
            openRoleEditor(url, buttons, role_id, role_name);
        }

        function addNewRole() {
            const url = "<?= $module->getUrl("editSystemRole.php?newRole=true") ?>";
            const buttons = [{
                // Cancel
                text: "<?= $lang["global_53"] ?>",
                click: function() {
                    $('#edit_role_popup').html('');
                    $(this).dialog('destroy');
                }
            }, {
                // Save Changes
                text: "<?= $lang["report_builder_28"] ?>",
                click: function() {
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
                            })
                            .always(function() {
                                console.log('always');
                            })
                    }
                }
            }];
            openRoleEditor(url, buttons);
        }

        $(document).ready(function() {
            $('#roleTable').DataTable({
                searching: false,
                info: false,
                paging: false,
                rowReorder: true,
                ordering: false,
                fixedHeader: true,
                fixedColumns: true
            });


        });
        window.scroll(0, 0);
    </script>
<?php
}
