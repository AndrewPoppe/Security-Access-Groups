<?php

namespace YaleREDCap\SystemUserRights;

use ExternalModules\AbstractExternalModule;

require_once "SUR_User.php";

class SystemUserRights extends AbstractExternalModule
{

    function redcap_every_page_before_render()
    {

        // Only run on the pages we're interested in
        if (
            $_SERVER["REQUEST_METHOD"] !== "POST" ||
            !in_array(PAGE, [
                "UserRights/edit_user.php",
                "UserRights/assign_user.php",
                "UserRights/import_export_users.php",
                "UserRights/import_export_roles.php",
                "api/index.php"
            ])
        ) {
            return;
        }

        // API Stuff
        if (PAGE === "api/index.php") {
            $rights = $this->getUserRightsFromToken($_POST["token"]);

            // Allow the API if the module is not enabled in the project.
            if (empty($rights) || !$this->isModuleEnabled($this->getModuleDirectoryPrefix(), $rights["project_id"])) {
                return;
            }

            // Take action if the user-related API methods are being called
            if (in_array($_POST["content"], ["user", "userRole", "userRoleMapping"]) && isset($_POST["data"])) {
                echo "This API Method is Not Allowed.";
                $this->exitAfterHook();
                return;
            }
        }


        // Edit User or Role
        if (
            PAGE === "UserRights/edit_user.php" &&
            isset($_POST['submit-action']) &&
            in_array($_POST['submit-action'], ["edit_role", "edit_user", "add_user"])
        ) {
            $this->log('attempt to edit user or role directly', ["page" => PAGE, "data" => json_encode($_POST)]);
            $this->exitAfterHook();
            return;
        }

        // Assign User to Role
        if (PAGE === "UserRights/assign_user.php") {
            $this->log('attempt to assign user role directly', ["page" => PAGE, "data" => json_encode($_POST)]);
            $this->exitAfterHook();
            return;
        }

        // Upload Users via CSV
        if (PAGE === "UserRights/import_export_users.php") {
            $this->log('attempt to upload users directly', ["page" => PAGE, "data" => json_encode($_POST)]);
            $this->exitAfterHook();
            return;
        }

        // Upload Roles or Mappings via CSV
        if (PAGE === "UserRights/import_export_roles.php") {
            $this->log('attempt to upload roles or role mappings directly', ["page" => PAGE, "data" => json_encode($_POST)]);
            $this->exitAfterHook();
            return;
        }
    }

    function redcap_user_rights($project_id)
    {

?>
        <script>
            $(function() {

                <?php if (isset($_SESSION['SUR_imported'])) { ?>
                    window.import_type = '<?= $_SESSION['SUR_imported'] ?>';
                    window.import_errors = JSON.parse('<?= $_SESSION['SUR_bad_rights'] ?>');
                <?php
                    unset($_SESSION['SUR_imported']);
                    unset($_SESSION['SUR_bad_rights']);
                } ?>

                function fixLinks() {
                    $('#importUserForm').attr('action', "<?= $this->getUrl("import_export_users.php") ?>");
                    $('#importUsersForm2').attr('action', "<?= $this->getUrl("import_export_users.php") ?>");
                    $('#importRoleForm').attr('action', "<?= $this->getUrl("import_export_roles.php") ?>");
                    $('#importRolesForm2').attr('action', "<?= $this->getUrl("import_export_roles.php") ?>");
                    $('#importUserRoleForm').attr('action', "<?= $this->getUrl("import_export_roles.php?action=uploadMapping") ?>");
                    $('#importUserRoleForm2').attr('action', "<?= $this->getUrl("import_export_roles.php?action=uploadMapping") ?>");
                }

                function checkImportErrors() {
                    if (window.import_type) {
                        let title = "You can't do that.";
                        let text = "";
                        if (window.import_type == "users") {
                            title = "You cannot import those users.";
                            text = `The following users included in the provided import file cannot have the following permissions granted to them:<br>
                                <table style="margin-top: 20px; width: 100%;"><thead style="border-bottom: 2px solid #666;"><tr><th>User</th><th>Permissions</th></tr></thead><tbody>`;
                            const users = Object.keys(window.import_errors);
                            users.forEach((user) => {
                                text += `<tr style="border-top: 1px solid #666;"><td>${user}</td><td>${window.import_errors[user].join('<br>')}</td></tr>`;
                            });
                            text += `</tbody></table>`;
                        } else if (window.import_type == "roles") {
                            title = "You cannot import those roles.";
                            text = `The following roles have users assigned to them, and the following permissions cannot be granted for those users:<br>
                            <table style="margin-top: 20px; width: 100%;"><thead style="border-bottom: 2px solid #666;"><tr><th>Role</th><th>User</th><th>Permissions</th></tr></thead><tbody>`;
                            const roles = Object.keys(window.import_errors);
                            roles.forEach((role) => {
                                const users = Object.keys(window.import_errors[role]);
                                text += `<tr style="border-top: 1px solid #666;"><td ROWSPAN="${users.length}">${role}</td>`;
                                users.forEach((user, index) => {
                                    const theseRights = window.import_errors[role][user];
                                    text += (index > 1) ? "<tr style='border-top: 1px solid red;'>" : "";
                                    text += `<td>${user}</td><td>${theseRights.join('<br>')}</td></tr>`;
                                });
                            })
                            text += `</tbody></table>`;
                        }
                        Swal.fire({
                            icon: 'error',
                            title: title,
                            html: text
                        });
                    }
                }
                window.saveUserFormAjax = function() {
                    showProgress(1);
                    const permissions = $('form#user_rights_form').serializeObject();
                    console.log(permissions);
                    $.post('<?= $this->getUrl("edit_user.php?pid=$project_id") ?>', permissions, function(data) {
                        showProgress(0, 0);
                        try {
                            const result = JSON.parse(data);
                            if (!result.error || !result.bad_rights) {
                                return;
                            }
                            let title = "You can't do that.";
                            let text = "";
                            let users = Object.keys(result.bad_rights);
                            if (!result.role) {
                                title = `You cannot grant those user rights to user "${users[0]}"`;
                                text = `The following permissions cannot be granted to that user: ${result.bad_rights[users[0]].join(', ')}`;
                            } else {
                                title = `You cannot grant those rights to the role<br>"${result.role}"`;
                                text = `The following users are assigned to that role, and the following permissions cannot be granted to them:<br>
                                <table style="margin-top: 20px; width: 100%;"><thead style="border-bottom: 2px solid #666;"><tr><th>User</th><th>Permissions</th></tr></thead><tbody>`;
                                users.forEach((user) => {
                                    text += `<tr style="border-top: 1px solid #666;"><td>${user}</td><td>${result.bad_rights[user].join('<br>')}</td></tr>`;
                                });
                                text += `</tbody></table>`;
                            }
                            Swal.fire({
                                icon: 'error',
                                title: title,
                                html: text
                            });
                            return;
                        } catch (error) {
                            if ($('#editUserPopup').hasClass('ui-dialog-content')) $('#editUserPopup').dialog('destroy');
                            $('#user_rights_roles_table_parent').html(data);
                            simpleDialogAlt($('#user_rights_roles_table_parent div.userSaveMsg'), 1.7);
                            enablePageJS();
                            if ($('#copy_role_success').length) {
                                setTimeout(function() {
                                    openAddUserPopup('', $('#copy_role_success').val());
                                }, 1500);
                            }
                        }
                        fixLinks();
                    });
                }

                window.assignUserRole = function(username, role_id) {
                    showProgress(1);
                    checkIfuserRights(username, role_id, function(data) {
                        if (data == 1) {
                            console.log(username, role_id);
                            $.post('<?= $this->getUrl("assign_user.php?pid=$project_id") ?>', {
                                username: username,
                                role_id: role_id,
                                notify_email_role: ($('#notify_email_role').prop('checked') ? 1 : 0),
                                group_id: $('#user_dag').val()
                            }, function(data) {
                                showProgress(0, 0);
                                if (data == '') {
                                    alert(woops);
                                    return;
                                }
                                try {
                                    const result = JSON.parse(data);
                                    if (!result.error || !result.bad_rights) {
                                        return;
                                    }
                                    let title = "You can't do that.";
                                    let text = "";
                                    let users = Object.keys(result.bad_rights);

                                    title = `You cannot assign user "${username}" to user role "${result.role}"`;
                                    text = `The following permissions allowed in user role "${result.role}" cannot be granted to that user: ${result.bad_rights[users[0]].join(', ')}`;

                                    Swal.fire({
                                        icon: 'error',
                                        title: title,
                                        html: text
                                    });
                                    return;
                                } catch (error) {
                                    $('#user_rights_roles_table_parent').html(data);
                                    showProgress(0, 0);
                                    simpleDialogAlt($('#user_rights_roles_table_parent div.userSaveMsg'), 1.7);
                                    enablePageJS();
                                    setTimeout(function() {
                                        if (role_id == '0') {
                                            simpleDialog(lang.rights_215, lang.global_03 + lang.colon + ' ' + lang.rights_214);
                                        }
                                    }, 3200);
                                }
                                fixLinks();
                            });
                        } else {
                            showProgress(0, 0);
                            setTimeout(function() {
                                simpleDialog(lang.rights_317, lang.global_03 + lang.colon + ' ' + lang.rights_316);
                            }, 500);
                        }
                        fixLinks();
                    });
                }
                fixLinks();
                checkImportErrors();
            });
        </script>
    <?php
        //var_dump(\UserRights::getRoles($this->getProjectId()));
    }

    function getUserInfo(string $username): ?array
    {
        $sql = "SELECT username
        , user_email
        , user_firstname
        , user_lastname
        , super_user
        , account_manager
        , access_system_config
        , access_system_upgrade
        , access_external_module_install
        , admin_rights
        , access_admin_dashboards
        , user_creation
        , user_lastlogin
        , user_suspended_time
        , user_expiration
        , user_sponsor
        , allow_create_db
        FROM redcap_user_information
        WHERE username = ?";
        try {
            $result = $this->query($sql, [$username]);
            return $result->fetch_assoc();
        } catch (\Throwable $e) {
            $this->log("Error getting user info", ["username" => $username, "error" => $e->getMessage()]);
        }
    }

    function getAllUserInfo(): ?array
    {
        $sql = "SELECT username
        , user_email
        , user_firstname
        , user_lastname
        , super_user
        , account_manager
        , access_system_config
        , access_system_upgrade
        , access_external_module_install
        , admin_rights
        , access_admin_dashboards
        , user_creation
        , user_lastlogin
        , user_suspended_time
        , user_expiration
        , user_sponsor
        , allow_create_db
        FROM redcap_user_information";
        try {
            $result = $this->query($sql, []);
            $userinfo = [];
            while ($row = $result->fetch_assoc()) {
                $userinfo[$row['username']] = $row;
            }
            return $userinfo;
        } catch (\Throwable $e) {
            $this->log("Error getting all user info", ["error" => $e->getMessage()]);
        }
    }

    function getAllRights()
    {
        $sql = "SHOW COLUMNS FROM redcap_user_rights";
        $result = $this->query($sql, []);
        $rights = [];
        while ($row = $result->fetch_assoc()) {
            if (!in_array($row["Field"], ["project_id", "username", "expiration", "role_id", "group_id"], true)) {
                $rights[$row["Field"]] = $row["Field"];
            }
        }

        $modified = array_filter(\UserRights::getApiUserPrivilegesAttr(), function ($value) {
            return !in_array($value, ["username", "expiration", "group_id"], true);
        });

        return array_unique(array_merge($rights, $modified));
    }

    function getAcceptableRights(string $username)
    {
        $rights =  $this->getAllRights();
        if (($key = array_search("design", $rights)) !== false) {
            unset($rights[$key]);
        }
        if (($key = array_search("user_rights", $rights)) !== false) {
            unset($rights[$key]);
        }
        return $rights;
        //        return array_keys($rights);

    }

    function checkProposedRights(array $acceptable_rights, array $requested_rights)
    {
        //$this->log('checking rights', ["acceptable" => json_encode($acceptable_rights), "acceptable_keys" => json_encode(array_keys($acceptable_rights)), "requested" => json_encode($requested_rights)]);
        $bad_rights = [];
        foreach ($requested_rights as $right => $value) {
            if (str_starts_with($right, "form-") or str_starts_with($right, "export-form-")) {
                continue;
            }
            if (in_array($right, ["user", "submit-action", "role_name", "role_name_edit", "redcap_csrf_token", "expiration", "group_role", "data_access_group_id", "unique_role_name", "role_label"])) {
                continue;
            }
            if ($value === "0") {
                continue;
            }
            if (in_array($right, $acceptable_rights, true) || in_array($right, array_keys($acceptable_rights), true)) {
                continue;
            }
            $bad_rights[] = $right;
        }
        return $bad_rights;
    }

    function getRoleIdFromUniqueRoleName($uniqueRoleName)
    {
        $sql = "SELECT role_id FROM redcap_user_roles WHERE unique_role_name = ?";
        $result = $this->query($sql, [$uniqueRoleName]);
        $row = $result->fetch_assoc();
        return $row["role_id"];
    }

    function getUsersInRole($project_id, $role_id)
    {
        $sql = "select * from redcap_user_rights where project_id = ? and role_id = ?";
        $result = $this->query($sql, [$project_id, $role_id]);
        $users = [];
        while ($row = $result->fetch_assoc()) {
            $users[] = $row["username"];
        }
        return $users;
    }

    function getRoleRights($role_id)
    {
        $roles = \UserRights::getRoles($this->getProjectId());
        $this_role = $roles[$role_id];
        $role_rights = array_filter($this_role, function ($value, $key) {
            $off = $value === "0";
            $null = is_null($value);
            $unset = isset($value) && is_null($value);
            $excluded = in_array($key, ["role_name", "unique_role_name", "project_id", "data_entry", "data_export_instruments"], true);
            $also_excluded = !in_array($key, $this->getAllRights(), true);
            return !$off && !$unset && !$excluded && !$also_excluded && !$null;
        }, ARRAY_FILTER_USE_BOTH);
        return $role_rights;
    }

    function getModuleDirectoryPrefix()
    {
        return strrev(preg_replace("/^.*v_/", "", strrev($this->getModuleDirectoryName()), 1));
    }

    function getUserRightsFromToken($token)
    {
        $sql = "SELECT * FROM redcap_user_rights WHERE api_token = ?";
        $rights = [];
        try {
            $result = $this->query($sql, [$token]);
            $rights = $result->fetch_assoc();
        } catch (\Throwable $e) {
            $this->log('Error getting user rights from API token', ["error" => $e->getMessage()]);
        } finally {
            return $rights;
        }
    }

    function getUserSystemRole($username)
    {
        $setting = $username . "-role";
        //$this->removeSystemSetting($setting);
        $role = $this->getSystemSetting($setting);
        if (!isset($role)) {
            $role = "NA";
            $this->setSystemSetting($setting, $role);
        }
        return $role;
    }

    function getAllSystemRoles()
    {
        $roles = json_decode($this->getSystemSetting("roles"), true) ?? ["R1" => "Role 1", "R2" => "Role 2"];
        return array_merge(["NA" => "No Access"], $roles);
    }

    function getDisplayTextForRight(string $right, string $key = "")
    {
        global $lang;
        $rights = [
            'design' => $lang['rights_135'],
            'user_rights' => $lang['app_05'],
            'data_access_groups' => $lang['global_22'],
            'data_entry' => $lang['rights_373'],
            'data_export_tool' => $lang['rights_428'],
            'reports' => $lang['rights_96'],
            'graphical' => $lang['report_builder_78'],
            'participants' => $lang['app_24'],
            'calendar' => $lang['app_08'] . " " . $lang['rights_357'],
            'data_import_tool' => $lang['app_01'],
            'data_comparison_tool' => $lang['app_02'],
            'data_logging' => $lang['app_07'],
            'file_repository' => $lang['app_04'],
            'double_data' => $lang['rights_50'],
            'lock_record_customize' => $lang['app_11'],
            'lock_record' => $lang['rights_97'],
            'randomization' => $lang['app_21'],
            'data_quality_design' => $lang['dataqueries_38'],
            'data_quality_execute' => $lang['dataqueries_39'],
            'data_quality_resolution' => $lang['dataqueries_137'],
            'api' => $lang['setup_77'],
            'mobile_app' => $lang['global_118'],
            'realtime_webservice_mapping' =>  $lang['ws_210'] . " " . $lang['ws_51'] . " " . $lang['ws_19'],
            'realtime_webservice_adjudicate' => $lang['ws_210'] . " " . $lang['ws_51'] . " " . $lang['ws_20'],
            'dts' => $lang['rights_132'],
            'record_create' => $lang['rights_99'],
            'record_rename' => $lang['rights_100'],
            'record_delete' => $lang['rights_101']
        ];
        return $rights[$right] ?? $rights[$key] ?? "<<< " . $right . " >>>";
    }

    function renderRoleEditTable(array $rights, bool $newRole, $role_name = "", $role_id = "")
    {
        global $lang;
        $allRights = $this->getAllRights();
        var_dump($allRights);
    ?>
        <form id="SUR_Role_Setting">
            <!-- Begin Table -->
            <table style='width:100%;'>
                <tr>
                    <td valign='top' style='width:475px;'>
                        <div class='card' style='border-color:#00000060;'>
                            <div class='card-header font-weight-bold fs14' style='background-color:#00000017;'>
                                <?= $lang['rights_431'] ?>
                            </div>
                            <div class='card-body p-3' style='background-color:#00000007;'>
                                <table id='user-rights-left-col'>

                                    <!-- HIGHEST LEVEL PRIVILEGES -->
                                    <tr>
                                        <td valign='top' colspan='2' style='border-top:1px solid #00000030;padding:6px 0 10px;color:#A00000;font-size:11px;'>
                                            <?= $lang['rights_299'] ?>
                                        </td>
                                    </tr>

                                    <!-- Project Setup/Design -->
                                    <?php if (isset($allRights["design"])) { ?>
                                        <tr>
                                            <td valign='top'>
                                                <i class="fas fa-tasks"></i>&nbsp;&nbsp;<?= $lang['rights_135'] ?>
                                            </td>
                                            <td valign='top' style='padding-top:2px;'>
                                                <input type='checkbox' name='design' <?= $rights["design"] == 1 ? "checked" : "" ?>>
                                            </td>
                                        </tr>
                                    <?php } ?>

                                    <!-- User Rights -->
                                    <?php if (isset($allRights["user_rights"])) { ?>
                                        <tr>
                                            <td valign='top'>
                                                <i class="fas fa-user"></i>&nbsp;&nbsp;<?= $lang['app_05'] ?>
                                            </td>
                                            <td valign='top' style='padding-top:2px;'>
                                                <input type='checkbox' name='user_rights' <?= $rights["user_rights"] == 1 ? "checked" : "" ?>>
                                            </td>
                                        </tr>
                                    <?php } ?>

                                    <!--Data Access Groups -->
                                    <?php if (isset($allRights["data_access_groups"])) { ?>
                                        <tr>
                                            <td valign='top' style='padding-bottom:10px;'>
                                                <i class="fas fa-users"></i>&nbsp;<?= $lang['global_22'] ?>
                                            </td>
                                            <td valign='top' style='padding-top:2px;'>
                                                <input type='checkbox' name='data_access_groups' <?= $rights["data_access_groups"] == 1 ? "checked" : "" ?>>
                                            </td>
                                        </tr>
                                    <?php } ?>

                                    <!-- OTHER PRIVILEGES -->

                                    <tr>
                                        <td valign='top' colspan='2' style='border-top:1px solid #00000030;padding:6px 0 10px;color:#A00000;font-size:11px;'>
                                            <?= $lang['rights_300'] ?>
                                        </td>
                                    </tr>

                                    <!-- MyCap Mobile App -->
                                    <?php if (isset($allRights["mycap_participants"])) { ?>

                                        <tr>
                                            <td valign='top'>
                                                <img src='<?= APP_PATH_IMAGES . "mycap_logo_black.png" ?>' style='width:24px;position:relative;top:-2px;margin-left:-9px;'>&nbsp;<?= $lang['rights_437'] ?>
                                            </td>
                                            <td valign='top' style='padding-top:2px;'>
                                                <input type='checkbox' name='mycap_participants' <?= $rights["mycap_participants"] == 1 ? "checked" : "" ?>>
                                            </td>
                                        </tr>
                                    <?php } ?>

                                    <!-- Survey Distribution Tool rights -->
                                    <?php if (isset($allRights["participants"])) { ?>
                                        <tr>
                                            <td valign='top'>
                                                <div style='text-indent: -32px;margin-left: 32px;'>
                                                    <i class="fas fa-chalkboard-teacher" style='margin-right:2px;text-indent: -3px;'></i>
                                                    <?= $lang['app_24'] ?>
                                                </div>
                                            </td>
                                            <td valign='top' style='padding-top:2px;'>
                                                <input type='checkbox' name='participants' <?= $rights["participants"] == 1 ? "checked" : "" ?>>
                                            </td>
                                        </tr>
                                    <?php } ?>

                                    <!-- Alerts & Notifications -->
                                    <?php if (isset($allRights["alerts"])) { ?>
                                        <tr>
                                            <td valign='top'>
                                                <i class="fas fa-bell"></i>&nbsp;&nbsp;<?= $lang['global_154'] ?>
                                            </td>
                                            <td valign='top' style='padding-top:2px;'>
                                                <input type='checkbox' name='alerts' <?= $rights["alerts"] == 1 ? "checked" : "" ?>>
                                            </td>
                                        </tr>
                                    <?php } ?>

                                    <!--Calendar rights -->
                                    <?php if (isset($allRights["calendar"])) { ?>
                                        <tr>
                                            <td valign='top'>
                                                <i class="far fa-calendar-alt"></i>&nbsp;&nbsp;
                                                <?= $lang['app_08'] ?>
                                                <?= $lang['rights_357'] ?>
                                            </td>
                                            <td valign='top' style='padding-top:2px;'>
                                                <input type='checkbox' name='calendar' <?= $rights["calendar"] == 1 ? "checked" : "" ?>>
                                            </td>
                                        </tr>
                                    <?php } ?>

                                    <!-- Reports & Report Builder -->
                                    <?php if (isset($allRights["reports"])) { ?>
                                        <tr>
                                            <td valign='top'>
                                                <i class="fas fa-search"></i>&nbsp;&nbsp;<?= $lang['rights_356'] ?>
                                                <div style='line-height:12px;padding:0px 0px 4px 22px;text-indent:-8px;font-size:11px;color:#999;'>
                                                    &nbsp; <?= $lang['report_builder_130'] ?>
                                                </div>
                                            </td>
                                            <td valign='top' style='padding-top:2px;'>
                                                <input type='checkbox' name='reports' <?= $rights["reports"] == 1 ? "checked" : "" ?>>
                                            </td>
                                        </tr>
                                    <?php } ?>

                                    <!-- Graphical Data View & Stats -->
                                    <?php if (isset($allRights["graphical"])) { ?>
                                        <tr>
                                            <td valign='top' style='padding-bottom:5px;'>
                                                <img src='<?= APP_PATH_IMAGES . "chart_bar.png" ?>'>&nbsp;&nbsp;<?= $lang['report_builder_78'] ?>
                                            </td>
                                            <td valign='top' style='padding-top:2px;padding-bottom:5px;'>
                                                <input type='checkbox' name='graphical' <?= $rights["graphical"] == 1 ? "checked" : "" ?>>
                                            </td>
                                        </tr>
                                    <?php } ?>

                                    <!-- Double Data Entry -->
                                    <?php if (isset($allRights["double_data"])) { ?>
                                        <tr>
                                            <td valign='top'>
                                                <i class="fas fa-users"></i>&nbsp;&nbsp;<?= $lang['rights_50'] ?>
                                            </td>
                                            <td valign='top' style='padding-top:2px;font-size:11px;color:#808080;'>
                                                <input type='radio' name='double_data' value='0' <?= $rights["double_data"] == 0 ? "checked" : "" ?>> <?= $lang['rights_51'] ?><br>
                                                <input type='radio' name='double_data' value='1' <?= $rights["double_data"] == 1 ? "checked" : "" ?>> <?= $lang['rights_52'] ?> #1<br>
                                                <input type='radio' name='double_data' value='2' <?= $rights["double_data"] == 2 ? "checked" : "" ?>> <?= $lang['rights_52'] ?> #2
                                            </td>
                                        </tr>
                                    <?php } ?>

                                    <!-- Data Import Tool -->
                                    <?php if (isset($allRights["data_import_tool"])) { ?>

                                        <tr>
                                            <td valign='top'>
                                                <i class="fas fa-file-import"></i>&nbsp;&nbsp;<?= $lang['app_01'] ?>
                                            </td>
                                            <td style='padding-top:2px;' valign='top'>
                                                <input type='checkbox' name='data_import_tool' <?= $rights["data_import_tool"] == 1 ? "checked" : "" ?>>
                                            </td>
                                        </tr>
                                    <?php } ?>

                                    <!-- Data Comparison Tool -->
                                    <?php if (isset($allRights["data_comparison_tool"])) { ?>
                                        <tr>
                                            <td valign='top'>
                                                <i class="fas fa-not-equal"></i>&nbsp;&nbsp;<?= $lang['app_02'] ?>
                                            </td>
                                            <td style='padding-top:2px;' valign='top'>
                                                <input type='checkbox' name='data_comparison_tool' <?= $rights["data_comparison_tool"] == 1 ? "checked" : "" ?>>
                                            </td>
                                        </tr>
                                    <?php } ?>

                                    <!-- Logging -->
                                    <?php if (isset($allRights["data_logging"])) { ?>
                                        <tr>
                                            <td valign='top'>
                                                <i class="fas fa-receipt" style='margin-left:2px;margin-right:2px;'></i>&nbsp;&nbsp;<?= $lang['app_07'] ?>
                                            </td>
                                            <td style='padding-top:2px;' valign='top'> <input type='checkbox' name='data_logging' <?= $rights["data_logging"] == 1 ? "checked" : "" ?>>
                                            </td>
                                        </tr>
                                    <?php } ?>

                                    <!-- File Repository -->
                                    <?php if (isset($allRights["file_repository"])) { ?>
                                        <tr>
                                            <td valign='top'>
                                                <i class="fas fa-folder-open"></i>&nbsp;&nbsp;<?= $lang['app_04'] ?>
                                            </td>
                                            <td style='padding-top:2px;' valign='top'>
                                                <input type='checkbox' name='file_repository' <?= $rights["file_repository"] == 1 ? "checked" : "" ?>>
                                            </td>
                                        </tr>
                                    <?php } ?>

                                    <!-- Randomization -->
                                    <?php if (isset($allRights["random_setup"])) { ?>
                                        <tr>
                                            <td valign='top'>
                                                <i class="fas fa-random"></i>&nbsp;&nbsp;<?= $lang['app_21'] ?>
                                            </td>
                                            <td valign='top' style='padding-top:2px;'>
                                                <input type='checkbox' name='random_setup' <?= $rights["random_setup"] == 1 ? "checked" : "" ?>> <?= $lang['rights_142'] ?><br />
                                                <input type='checkbox' name='random_dashboard' <?= $rights["random_dashboard"] == 1 ? "checked" : "" ?>> <?= $lang['rights_143'] ?><br />
                                                <input type='checkbox' name='random_perform' <?= $rights["random_perform" == 1] ? "checked" : "" ?>> <?= $lang['rights_144'] ?>
                                            </td>
                                        </tr>
                                    <?php } ?>

                                    <!-- Data Quality -->
                                    <?php if (isset($allRights["data_quality_design"])) { ?>
                                        <tr>
                                            <td valign='top'>
                                                <i class="fas fa-clipboard-check"></i>&nbsp;&nbsp;<?= $lang['app_20'] ?>
                                            </td>
                                            <td valign='top' style='padding-top:2px;'>
                                                <input type='checkbox' name='data_quality_design' <?= $rights["data_quality_design"] == 1 ? "checked" : "" ?>>
                                                <?= $lang['dataqueries_40'] ?><br>
                                                <input type='checkbox' name='data_quality_execute' <?= $rights["data_quality_execute"] == 1 ? "checked" : "" ?>>
                                                <?= $lang['dataqueries_41'] ?>
                                            </td>
                                        </tr>
                                    <?php } ?>

                                    <!-- Data Quality resolution -->
                                    <?php if (isset($allRights["data_quality_resolution"])) { ?>
                                        <tr>
                                            <td valign='top' style='width:180px;'>
                                                <i class='fas fa-comments'></i>&nbsp;&nbsp;<?= $lang['dataqueries_137'] ?>
                                            </td>
                                            <td style='padding-top:2px;' valign='top' style='font-size:11px;color:#808080;'>
                                                <div style='margin-left:1.4em;text-indent:-1.4em;'>
                                                    <input type='radio' name='data_quality_resolution' value='0' <?= $rights["data_quality_resolution"] == '0' ? "checked" : "" ?>> <?= $lang['rights_47'] ?>
                                                </div>
                                                <div style='margin-left:1.4em;text-indent:-1.4em;'>
                                                    <input type='radio' name='data_quality_resolution' value='1' <?= $rights["data_quality_resolution"] == '1' ? "checked" : "" ?>> <?= $lang['dataqueries_143'] ?>
                                                </div>
                                                <div style='margin-left:1.4em;text-indent:-1.4em;'>
                                                    <input type='radio' name='data_quality_resolution' value='4' <?= $rights["data_quality_resolution"] == '4' ? "checked" : "" ?>> <?= $lang['dataqueries_289'] ?>
                                                </div>
                                                <div style='margin-left:1.4em;text-indent:-1.4em;'>
                                                    <input type='radio' name='data_quality_resolution' value='2' <?= $rights["data_quality_resolution"] == '2' ? "checked" : "" ?>> <?= $lang['dataqueries_138'] ?>
                                                </div>
                                                <div style='margin-left:1.4em;text-indent:-1.4em;'>
                                                    <input type='radio' name='data_quality_resolution' value='5' <?= $rights["data_quality_resolution"] == '5' ? "checked" : "" ?>> <?= $lang['dataqueries_290'] ?>
                                                </div>
                                                <div style='margin-left:1.4em;text-indent:-1.4em;'>
                                                    <input type='radio' name='data_quality_resolution' value='3' <?= $rights["data_quality_resolution"] == '3' ? "checked" : "" ?>> <?= $lang['dataqueries_139'] ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php } ?>

                                    <!-- API -->
                                    <?php if (isset($allRights["api_export"])) { ?>
                                        <tr>
                                            <td valign='top'>
                                                <i class="fas fa-laptop-code"></i>&nbsp;&nbsp;<?= $lang['setup_77'] ?>
                                            </td>
                                            <td valign='top' style='padding-top:2px;'>
                                                <input type='checkbox' name='api_export' <?= $rights["api_export"] == 1 ? "checked" : "" ?>> <?= $lang['rights_139'] ?><br />
                                                <input type='checkbox' name='api_import' <?= $rights["api_import"] == 1 ? "checked" : "" ?>> <?= $lang['rights_314'] ?>
                                            </td>
                                        </tr>
                                    <?php } ?>

                                    <!-- Dynamic Data Pull OR CDIS-->
                                    <?php if (isset($allRights["realtime_webservice_mapping"])) { ?>
                                        <tr>
                                            <td valign="top" style="padding-top:8px;">
                                                <div style="margin-left:1.4em;text-indent:-1.4em;line-height: 13px;">
                                                    <i class="fas fa-database" style="text-indent: 0;"></i>&nbsp;&nbsp; Clinical Data Pull from EHR -or- Dynamic Data Pull from External Source System
                                                </div>
                                            </td>
                                            <td valign="top" style="padding-top:8px;">
                                                <div style="margin-left:1.4em;text-indent:-1.4em;">
                                                    <!-- Mapping rights -->
                                                    <input type="checkbox" name="realtime_webservice_mapping" <?= $rights["realtime_webservice_mapping"] == 1 ? "checked" : "" ?>>
                                                    <?php echo $lang['ws_19'] ?>
                                                </div>
                                                <div style="margin-left:1.4em;text-indent:-1.4em;">
                                                    <!-- Adjudication rights -->
                                                    <input type="checkbox" name="realtime_webservice_adjudicate" <?= $rights["realtime_webservice_adjudicate"] == 1 ? "checked" : "" ?>>
                                                    <?php echo $lang['ws_20'] ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php } else { ?>
                                        <!-- Hide input fields to maintain values if setting is disabled at project level -->
                                        <input type="hidden" name="realtime_webservice_mapping" value="<?= $rights["realtime_webservice_mapping"] ?>">
                                        <input type="hidden" name="realtime_webservice_adjudicate" value="<?= $rights["realtime_webservice_adjudicate"] ?>">
                                    <?php } ?>

                                    <!-- Data Transfer Services -->
                                    <?php if (isset($allRights["dts"])) { ?>
                                        <tr>
                                            <td valign="top">
                                                <div style="margin-left:1.4em;text-indent:-1.4em;">
                                                    <i class="fas fa-database" style="text-indent: 0;"></i>&nbsp;&nbsp;<?= $lang["rights_132"] ?>
                                                </div>
                                            </td>
                                            <td valign="top" style="padding-top:2px;">

                                                <div style="margin-left:1.4em;text-indent:-1.4em;">
                                                    <input type="checkbox" name="dts" <?= $rights["dts"] == 1 ? "checked" : "" ?>>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php } ?>

                                    <!-- Mobile App -->
                                    <?php if (isset($allRights["mobile_app"])) { ?>
                                        <tr>
                                            <td valign='top' colspan='2' style='border-top:1px solid #00000030;padding:6px 0 10px;color:#A00000;font-size:11px;'>
                                                <?= $lang['rights_309'] ?>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td valign='top'>
                                                <i class="fas fa-tablet-alt"></i>&nbsp;&nbsp;<?= $lang['global_118'] ?>
                                            </td>
                                            <td valign='top' style='padding-top:2px;'>
                                                <input type='checkbox' name='mobile_app' style='float:left;'>
                                                <div style='width: 100px;padding: 1px 0 0 8px;float:left;line-height:12px;font-size:11px;color:#999;'>
                                                    <?= $lang['rights_307'] ?>
                                                </div>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td valign='top' style='line-height: 11px;font-size:11px;padding:10px 3px 10px 22px;'>
                                                <?= $lang['rights_306'] ?>
                                            </td>
                                            <td valign='top' style='padding-top:12px;'>
                                                <div style='margin-left:1.4em;text-indent:-1.4em;'>
                                                    <input type='checkbox' name='mobile_app_download_data' <?= $rights["mobile_app_download_data"] == '1' ? "checked" : "" ?>>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php } ?>

                                    <!-- Create/Rename/Delete Records -->
                                    <tr>
                                        <td valign='top' colspan='2' style='border-top:1px solid #00000030;padding:6px 0 10px;color:#A00000;font-size:11px;'>
                                            <?= $lang['rights_119'] ?>
                                        </td>
                                    </tr>
                                    <?php if (isset($allRights["record_create"])) { ?>
                                        <tr>
                                            <td valign='top'>
                                                <i class="fas fa-plus-square"></i>&nbsp;&nbsp;<?= $lang['rights_99'] ?>
                                            </td>
                                            <td valign='top' style='padding-top:2px;'>
                                                <input type='checkbox' name='record_create' <?= $rights["record_create"] == 1 ? "checked" : "" ?>>
                                            </td>
                                        </tr>
                                    <?php } ?>
                                    <?php if (isset($allRights["record_rename"])) { ?>
                                        <tr>
                                            <td valign='top'>
                                                <i class="fas fa-exchange-alt"></i>&nbsp;<?= $lang['rights_100'] ?>
                                            </td>
                                            <td valign='top' style='padding-top:2px;'>
                                                <input type='checkbox' name='record_rename' <?= $rights["record_rename"] == 1 ? "checked" : "" ?>>
                                            </td>
                                        </tr>
                                    <?php } ?>
                                    <?php if (isset($allRights["record_delete"])) { ?>
                                        <tr>
                                            <td valign='top' style='padding:2px 0 4px;'>
                                                <i class="fas fa-minus-square"></i>&nbsp;&nbsp;<?= $lang['rights_101'] ?>
                                            </td>
                                            <td valign='top' style='padding:2px 0 4px;'>
                                                <input type='checkbox' name='record_delete' <?= $rights["record_delete"] == 1 ? "checked" : "" ?>>
                                            </td>
                                        </tr>
                                    <?php } ?>

                                    <!-- Lock Record -->
                                    <tr>
                                        <td valign='top' colspan='2' style='border-top:1px solid #00000030;padding:6px 0 10px;color:#A00000;font-size:11px;'>
                                            <?= $lang['rights_130'] ?>
                                        </td>
                                    </tr>
                                    <?php if (isset($allRights["lock_record_customize"])) { ?>
                                        <tr>
                                            <td valign='top'>
                                                <div style='margin-left:1.4em;text-indent:-1.4em;'>
                                                    <i class="fas fa-lock" style='text-indent:0;'></i>&nbsp;&nbsp;<?= $lang['app_11'] ?>
                                                </div>
                                            </td>
                                            <td valign='top' style='padding-top:6px;'>
                                                <input type='checkbox' name='lock_record_customize' <?= $rights["lock_record_customize"] == 1 ? "checked" : "" ?>>
                                            </td>
                                        </tr>
                                    <?php } ?>
                                    <?php if (isset($allRights["lock_record"])) { ?>
                                        <tr>
                                            <td valign='top'>
                                                <div style='margin-left:1.4em;text-indent:-1.4em;'>
                                                    <i class="fas fa-unlock-alt" style='text-indent:0;'></i>&nbsp;&nbsp;<?= $lang['rights_97'] ?> <?= $lang['rights_371'] ?>
                                                </div>
                                                <div style='line-height:12px;padding:4px 0 4px 22px;font-size:11px;color:#777;'>
                                                    <?= $lang['rights_113'] ?>
                                                </div>
                                            </td>
                                            <td valign='top' style='padding-top:2px;'>
                                                <div style='margin-left:1.4em;text-indent:-1.4em;'><input type='radio' name='lock_record' value='0' <?= $rights["lock_record"] == '0' ? "checked" : "" ?>> <?= $lang['global_23'] ?></div>
                                                <div style='margin-left:1.4em;text-indent:-1.4em;'><input type='radio' name='lock_record' value='1' <?= $rights["lock_record"] == '1' ? "checked" : "" ?>> <?= $lang['rights_115'] ?></div>
                                                <div style='line-height:13px;margin-left:1.4em;text-indent:-1.4em;'>
                                                    <input type='radio' name='lock_record' value='2' <?= $rights["lock_record"] == '2' ? "checked" : "" ?>> <?= $lang['rights_116'] ?><br>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php } ?>
                                    <?php if (isset($allRights["lock_record_multiform"])) { ?>
                                        <tr>
                                            <td valign='top'>
                                                <div style='margin-left:1.4em;text-indent:-1.4em;'><i class="fas fa-unlock-alt" style='text-indent:0;'></i>&nbsp;&nbsp;<?= $lang['rights_370'] ?></div>
                                            </td>
                                            <td valign='top' style='padding-top:2px;'>
                                                <div style='margin-left:1.4em;text-indent:-1.4em;margin-top:4px;'>
                                                    <input type='checkbox' name='lock_record_multiform' <?= $rights["lock_record_multiform"] == '1' ? "checked" : "" ?>>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php } ?>
                                </table>
                            </div>
                        </div>
                    </td>
                    <td valign='top' style='width:700px;padding-left:15px;'>
                        <div class='card' style='border-color:#00000060;'>
                            <div class='card-header font-weight-bold fs14' style='background-color:#00000017;'>
                                <?= $lang['data_export_tool_291'] ?>
                            </div>
                            <div class='card-body p-0' style='background-color:#00000007;'>
                                <table id='form_rights' style='width:100%;font-size:12px;color:#A00000;'>
                                    <tr>
                                        <td colspan='3' style='padding:12px 12px 15px;line-height:1.1;color:#777;font-size:11px;'>
                                            <?= $lang['rights_429'] ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td valign='top' style='border-right:1px solid #FFA3A3;'>&nbsp;</td>
                                        <td valign='top' class='fs13 pb-2 text-center font-weight-bold' style='border-right:1px solid #FFA3A3;color:#000;'><?= $lang['rights_373'] ?></td>
                                        <td valign='top' class='fs13 pb-2 text-center font-weight-bold' style='color:#B00000;'><?= $lang['rights_428'] ?></td>
                                    </tr>
                                    <tr>
                                        <td valign='top' style='border-right:1px solid #FFA3A3;'>&nbsp;</td>
                                        <td valign='top' style='border-right:1px solid #FFA3A3;text-align:left;width:235px;color:#000;font-size:11px;'>
                                            <div style='float:left;padding:2px 8px;white-space:normal;width:62px;line-height: 12px;cursor:pointer;cursor:hand;' data-toggle='popover' data-trigger='hover' data-placement='left' data-content="<?= $lang['rights_430'] ?>"><?= $lang['rights_47'] ?><br><?= $lang['rights_395'] ?></div>
                                            <div style='float:left;padding:2px 8px;white-space:normal;width:44px;line-height: 12px;cursor:pointer;cursor:hand;' data-toggle='popover' data-trigger='hover' data-placement='left' data-content="<?= $lang['rights_430'] ?>"><?= $lang['rights_61'] ?></div>
                                            <div style='float:left;padding:2px 8px;white-space:normal;width:50px;line-height: 12px;cursor:pointer;cursor:hand;' data-toggle='popover' data-trigger='hover' data-placement='left' data-content="<?= $lang['rights_430'] ?>"><?= $lang['rights_138'] ?></div>
                                            <div style='float:left;padding:2px 8px;white-space:normal;width:70px;line-height: 12px;cursor:pointer;cursor:hand;' data-toggle='popover' data-trigger='hover' data-placement='left' data-content="<?= $lang['rights_430'] ?>"><?= $lang['rights_137'] ?></div>
                                        </td>
                                        <td valign='top' style='text-align:left;width:250px;color:#B00000;font-size:11px;'>
                                            <div style='float:left;padding:2px 8px;white-space:normal;width:58px;line-height: 12px;cursor:pointer;cursor:hand;' data-toggle='popover' data-trigger='hover' data-placement='left' data-content="<?= $lang['rights_430'] ?>"><?= $lang['rights_47'] ?></div>
                                            <div style='float:left;padding:2px 8px;white-space:normal;width:72px;line-height: 12px;cursor:pointer;cursor:hand;' data-toggle='popover' data-trigger='hover' data-placement='left' data-content="<?= $lang['rights_430'] ?>"><?= $lang['rights_48'] ?>*</div>
                                            <div style='float:left;padding:2px 8px;white-space:normal;width:65px;line-height: 12px;cursor:pointer;cursor:hand;' data-toggle='popover' data-trigger='hover' data-placement='left' data-content="<?= $lang['rights_430'] ?>"><?= $lang['data_export_tool_290'] ?></div>
                                            <div style='float:left;padding:2px 8px;white-space:normal;width:45px;line-height: 12px;cursor:pointer;cursor:hand;' data-toggle='popover' data-trigger='hover' data-placement='left' data-content="<?= $lang['rights_430'] ?>"><?= $lang['rights_49'] ?></div>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </td>
                </tr>
            </table>
        </form>
        <script>
            let title = "TESTING";
            let newRole = <?= $newRole ? "true" : "false" ?>;
            let buttons = newRole ? [{
                text: "<?= $lang["global_53"] ?>",
                click: function() {
                    $(this).dialog('destroy');
                }
            }, {
                text: "<?= $lang["rights_158"] ?>",
                click: function() {
                    console.log('save it');
                }
            }] : [{
                text: "<?= $lang["global_53"] ?>",
                click: function() {
                    $(this).dialog('destroy');
                }
            }, {
                text: "<?= $lang["report_builder_28"] ?>",
                click: function() {
                    console.log('save it');
                }
            }];
            $('#SUR_Role_Setting').dialog({
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
                    console.log($(this).serializeObject());
                }
            });
        </script>
<?php


    }
}
