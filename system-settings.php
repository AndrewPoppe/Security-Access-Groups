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
    <table id='SUR-System-Table' class="hover compact">
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
                            foreach ($roles as $role_id => $role) {
                                echo "<option value='" . $role_id . "' " . ($role_id == $thisUserRole ? "selected" : "") . ">" . $role . "</option>";
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
            ]
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
    var_dump(\UserRights::getApiUserPrivilegesAttr(false, 21));
}
