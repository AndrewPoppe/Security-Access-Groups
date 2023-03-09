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

    function convertPermissions(string $permissions)
    {
        $rights = json_decode($permissions, true);
        foreach ($rights as $key => $value) {
            if ($value == "on") {
                $rights[$key] = 1;
            }
        }
        $this->log('converted rights', ['rights' => json_encode($rights)]);
        return json_encode($rights);
    }

    /**
     * @param string $role_id
     * @param string $role_name
     * @param string $permissions - json-encoded string of user rights
     * 
     * @return [type]
     */
    function saveSystemRole(string $role_id, string $role_name, string $permissions)
    {
        $permissions_converted = $this->convertPermissions($permissions);

        $this->log("role", [
            "role_id" => $role_id,
            "role_name" => $role_name,
            "permissions" => $permissions_converted
        ]);
    }

    function deleteSystemRole($role_id)
    {
        return $this->removeLogs("message = 'role' AND role_id = ? AND project_id is null", [$role_id]);
    }

    function getAllSystemRoles()
    {
        $sql = "SELECT MAX(log_id) AS 'log_id' WHERE message = 'role' GROUP BY role_id";
        $result = $this->queryLogs($sql, []);
        $roles = [];
        while ($row = $result->fetch_assoc()) {
            $logId = $row["log_id"];
            $sql2 = "SELECT role_id, role_name, permissions WHERE log_id = ?";
            $result2 = $this->queryLogs($sql2, [$logId]);
            $roles[] = $result2->fetch_assoc();
        }
        return $roles;
    }

    function getSystemRoleRightsById($role_id)
    {
        $sql = "SELECT role_id, role_name, permissions WHERE message = 'role' AND role_id = ? ORDER BY log_id DESC LIMIT 1";
        $result = $this->queryLogs($sql, [$role_id]);
        return $result->fetch_assoc();
    }

    function systemRoleExists($role_id)
    {
        foreach ($this->getAllSystemRoles() as $role) {
            if ($role_id == $role["role_id"]) {
                return true;
            }
        }
        return false;
    }

    function generateNewRoleId()
    {
        $new_role_id = uniqid("role_", true);

        if ($this->systemRoleExists($new_role_id)) {
            return $this->generateNewRoleId();
        } else {
            return $new_role_id;
        }
    }

    function getDisplayTextForRights()
    {
        global $lang;
        return [
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
            'realtime_webservice_mapping' =>  "CDP/DDP" . " " . $lang['ws_19'],
            'realtime_webservice_adjudicate' => "CDP/DDP" . " " . $lang['ws_20'],
            'dts' => $lang['rights_132'],
            'record_create' => $lang['rights_99'],
            'record_rename' => $lang['rights_100'],
            'record_delete' => $lang['rights_101']
        ];
    }

    function getDisplayTextForRight(string $right, string $key = "")
    {
        $rights = $this->getDisplayTextForRights();
        return $rights[$right] ?? $rights[$key] ?? "<<< " . $right . " >>>";
    }

    function getDefaultRights()
    {
        $allRights = $this->getAllRights();
        if (isset($allRights["data_export_tool"])) $allRights["data_export_tool"] = 2;
        if (isset($allRights["data_import_tool"])) $allRights["data_import_tool"] = 0;
        if (isset($allRights["data_comparison_tool"])) $allRights["data_comparison_tool"] = 0;
        if (isset($allRights["data_logging"])) $allRights["data_logging"] = 0;
        if (isset($allRights["file_repository"])) $allRights["file_repository"] = 1;
        if (isset($allRights["double_data"])) $allRights["double_data"] = 0;
        if (isset($allRights["user_rights"])) $allRights["user_rights"] = 0;
        if (isset($allRights["lock_record"])) $allRights["lock_record"] = 0;
        if (isset($allRights["lock_record_multiform"])) $allRights["lock_record_multiform"] = 0;
        if (isset($allRights["lock_record_customize"])) $allRights["lock_record_customize"] = 0;
        if (isset($allRights["data_access_groups"])) $allRights["data_access_groups"] = 0;
        if (isset($allRights["graphical"])) $allRights["graphical"] = 1;
        if (isset($allRights["reports"])) $allRights["reports"] = 1;
        if (isset($allRights["design"])) $allRights["design"] = 0;
        if (isset($allRights["alerts"])) $allRights["alerts"] = 0;
        if (isset($allRights["dts"])) $allRights["dts"] = 0;
        if (isset($allRights["calendar"])) $allRights["calendar"] = 1;
        if (isset($allRights["record_create"])) $allRights["record_create"] = 1;
        if (isset($allRights["record_rename"])) $allRights["record_rename"] = 0;
        if (isset($allRights["record_delete"])) $allRights["record_delete"] = 0;
        if (isset($allRights["participants"])) $allRights["participants"] = 1;
        if (isset($allRights["data_quality_design"])) $allRights["data_quality_design"] = 0;
        if (isset($allRights["data_quality_execute"])) $allRights["data_quality_execute"] = 0;
        if (isset($allRights["data_quality_resolution"])) $allRights["data_quality_resolution"] = 1;
        if (isset($allRights["api_export"])) $allRights["api_export"] = 0;
        if (isset($allRights["api_import"])) $allRights["api_import"] = 0;
        if (isset($allRights["mobile_app"])) $allRights["mobile_app"] = 0;
        if (isset($allRights["mobile_app_download_data"])) $allRights["mobile_app_download_data"] = 0;
        if (isset($allRights["random_setup"])) $allRights["random_setup"] = 0;
        if (isset($allRights["random_dashboard"])) $allRights["random_dashboard"] = 0;
        if (isset($allRights["random_perform"])) $allRights["random_perform"] =  1;
        if (isset($allRights["realtime_webservice_mapping"])) $allRights["realtime_webservice_mapping"] = 0;
        if (isset($allRights["realtime_webservice_adjudicate"])) $allRights["realtime_webservice_adjudicate"] = 0;
        if (isset($allRights["mycap_participants"])) $allRights["mycap_participants"] = 1;
        return $allRights;
    }

    function getRoleEditForm(array $rights, bool $newRole, $role_name = "", $role_id = "")
    {
        global $lang;
        $allRights = $this->getAllRights();
        //var_dump($allRights);
        $context_message = $newRole ? $lang["rights_159"] : $lang["rights_157"] . ' "<strong>' . \REDCap::escapeHtml($role_name) . '</strong>"';
    ?>
        <div class="modal-xl modal-dialog modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header" style="background-color: #e9e9e9;">
                    <span class="modal-title" id="staticBackdropLabel" style="font-size: 1rem;"><i class="fa-solid fa-fw fa-user-tag"></i> <?= $context_message ?></span>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">

                    <div style="text-align:center; margin: 15px 0;" class="fs14 alert <?= $newRole ? "alert-success" : "alert-primary" ?>">
                        <i class="fa-solid fa-fw fa-user-tag"></i> <?= $context_message ?>
                    </div>
                    <!-- <div class="hidden">
            <div id="dialog_title"><i class="fa-solid fa-fw fa-user-tag"></i> <?= $context_message ?> </div>
        </div> -->
                    <form id="SUR_Role_Setting">
                        <div class="form-row">
                            <div class="col-5" style='width:475px;'>
                                <div class='card' style='border-color:#00000060;'>
                                    <div class='card-header font-weight-bold fs14' style='background-color:#00000017;'>
                                        <?= $lang['rights_431'] ?>
                                    </div>
                                    <div class='card-body p-3' style='background-color:#00000007;'>

                                        <!-- EDIT ROLE NAME -->
                                        <div class="form-row">
                                            <div class="col" colspan='2'>
                                                <i class="fa-solid fa-fw fa-id-card"></i>&nbsp;&nbsp;<?= $lang['rights_199'] ?>
                                                <input type='text' value="<?= \REDCap::escapeHtml($role_name) ?>" class='x-form-text x-form-field' name='role_name_edit'>
                                            </div>
                                        </div>

                                        <!-- HIGHEST LEVEL PRIVILEGES -->
                                        <hr>
                                        <div class="form-row">
                                            <div class="col section-header" colspan='2'>
                                                <?= $lang['rights_299'] ?>
                                            </div>
                                        </div>

                                        <!-- Project Setup/Design -->
                                        <?php if (isset($allRights["design"])) { ?>
                                            <div class="form-row">
                                                <div class="col">
                                                    <i class="fa-solid fa-fw fa-tasks"></i>&nbsp;&nbsp;<?= $lang['rights_135'] ?>
                                                </div>
                                                <div class="col">
                                                    <input type='checkbox' name='design' <?= $rights["design"] == 1 ? "checked" : "" ?>>
                                                </div>
                                            </div>
                                        <?php } ?>

                                        <!-- User Rights -->
                                        <?php if (isset($allRights["user_rights"])) { ?>
                                            <div class="form-row">
                                                <div class="col">
                                                    <i class="fa-solid fa-fw fa-user"></i>&nbsp;&nbsp;<?= $lang['app_05'] ?>
                                                </div>
                                                <div class="col">
                                                    <input type='checkbox' name='user_rights' <?= $rights["user_rights"] == 1 ? "checked" : "" ?>>
                                                </div>
                                            </div>
                                        <?php } ?>

                                        <!--Data Access Groups -->
                                        <?php if (isset($allRights["data_access_groups"])) { ?>
                                            <div class="form-row">
                                                <div class="col">
                                                    <i class="fa-solid fa-fw fa-users"></i>&nbsp;&nbsp;<?= $lang['global_22'] ?>
                                                </div>
                                                <div class="col">
                                                    <input type='checkbox' name='data_access_groups' <?= $rights["data_access_groups"] == 1 ? "checked" : "" ?>>
                                                </div>
                                            </div>
                                        <?php } ?>

                                        <!-- OTHER PRIVILEGES -->
                                        <hr>
                                        <div class="form-row">
                                            <div class="col section-header" colspan='2'>
                                                <?= $lang['rights_300'] ?>
                                            </div>
                                        </div>

                                        <!-- MyCap Mobile App -->
                                        <?php if (isset($allRights["mycap_participants"])) { ?>

                                            <div class="form-row">
                                                <div class="col">
                                                    <img src='<?= APP_PATH_IMAGES . "mycap_logo_black.png" ?>'>&nbsp;<?= $lang['rights_437'] ?>
                                                </div>
                                                <div class="col">
                                                    <input type='checkbox' name='mycap_participants' <?= $rights["mycap_participants"] == 1 ? "checked" : "" ?>>
                                                </div>
                                            </div>
                                        <?php } ?>

                                        <!-- Survey Distribution Tool rights -->
                                        <?php if (isset($allRights["participants"])) { ?>
                                            <div class="form-row">
                                                <div class="col">
                                                    <div>
                                                        <i class="fa-solid fa-fw fa-chalkboard-teacher"></i>&nbsp;&nbsp;<?= $lang['app_24'] ?>
                                                    </div>
                                                </div>
                                                <div class="col">
                                                    <input type='checkbox' name='participants' <?= $rights["participants"] == 1 ? "checked" : "" ?>>
                                                </div>
                                            </div>
                                        <?php } ?>

                                        <!-- Alerts & Notifications -->
                                        <?php if (isset($allRights["alerts"])) { ?>
                                            <div class="form-row">
                                                <div class="col">
                                                    <i class="fa-solid fa-fw fa-bell"></i>&nbsp;&nbsp;<?= $lang['global_154'] ?>
                                                </div>
                                                <div class="col">
                                                    <input type='checkbox' name='alerts' <?= $rights["alerts"] == 1 ? "checked" : "" ?>>
                                                </div>
                                            </div>
                                        <?php } ?>

                                        <!--Calendar rights -->
                                        <?php if (isset($allRights["calendar"])) { ?>
                                            <div class="form-row">
                                                <div class="col">
                                                    <i class="far fa-calendar-alt"></i>&nbsp;&nbsp;
                                                    <?= $lang['app_08'] ?>
                                                    <?= $lang['rights_357'] ?>
                                                </div>
                                                <div class="col">
                                                    <input type='checkbox' name='calendar' <?= $rights["calendar"] == 1 ? "checked" : "" ?>>
                                                </div>
                                            </div>
                                        <?php } ?>

                                        <!-- Reports & Report Builder -->
                                        <?php if (isset($allRights["reports"])) { ?>
                                            <div class="form-row">
                                                <div class="col">
                                                    <i class="fa-solid fa-fw fa-search"></i>&nbsp;&nbsp;<?= $lang['rights_356'] ?>
                                                    <div class="extra-text">
                                                        <?= $lang['report_builder_130'] ?>
                                                    </div>
                                                </div>
                                                <div class="col">
                                                    <input type='checkbox' name='reports' <?= $rights["reports"] == 1 ? "checked" : "" ?>>
                                                </div>
                                            </div>
                                        <?php } ?>

                                        <!-- Graphical Data View & Stats -->
                                        <?php if (isset($allRights["graphical"])) { ?>
                                            <div class="form-row">
                                                <div class="col">
                                                    <i class="fa-solid fa-fw fa-chart-column"></i>&nbsp;&nbsp;<?= $lang['report_builder_78'] ?>
                                                </div>
                                                <div class="col">
                                                    <input type='checkbox' name='graphical' <?= $rights["graphical"] == 1 ? "checked" : "" ?>>
                                                </div>
                                            </div>
                                        <?php } ?>

                                        <!-- Double Data Entry -->
                                        <?php if (isset($allRights["double_data"])) { ?>
                                            <div class="form-row">
                                                <div class="col">
                                                    <i class="fa-solid fa-fw fa-users"></i>&nbsp;&nbsp;<?= $lang['rights_50'] ?>
                                                </div>
                                                <div class="col">
                                                    <input type='radio' name='double_data' value='0' <?= $rights["double_data"] == 0 ? "checked" : "" ?>> <?= $lang['rights_51'] ?><br>
                                                    <input type='radio' name='double_data' value='1' <?= $rights["double_data"] == 1 ? "checked" : "" ?>> <?= $lang['rights_52'] ?> #1<br>
                                                    <input type='radio' name='double_data' value='2' <?= $rights["double_data"] == 2 ? "checked" : "" ?>> <?= $lang['rights_52'] ?> #2
                                                </div>
                                            </div>
                                        <?php } ?>

                                        <!-- Data Import Tool -->
                                        <?php if (isset($allRights["data_import_tool"])) { ?>

                                            <div class="form-row">
                                                <div class="col">
                                                    <i class="fa-solid fa-fw fa-file-import"></i>&nbsp;&nbsp;<?= $lang['app_01'] ?>
                                                </div>
                                                <div class="col">
                                                    <input type='checkbox' name='data_import_tool' <?= $rights["data_import_tool"] == 1 ? "checked" : "" ?>>
                                                </div>
                                            </div>
                                        <?php } ?>

                                        <!-- Data Comparison Tool -->
                                        <?php if (isset($allRights["data_comparison_tool"])) { ?>
                                            <div class="form-row">
                                                <div class="col">
                                                    <i class="fa-solid fa-fw fa-not-equal"></i>&nbsp;&nbsp;<?= $lang['app_02'] ?>
                                                </div>
                                                <div class="col">
                                                    <input type='checkbox' name='data_comparison_tool' <?= $rights["data_comparison_tool"] == 1 ? "checked" : "" ?>>
                                                </div>
                                            </div>
                                        <?php } ?>

                                        <!-- Logging -->
                                        <?php if (isset($allRights["data_logging"])) { ?>
                                            <div class="form-row">
                                                <div class="col">
                                                    <i class="fa-solid fa-fw fa-receipt"></i>&nbsp;&nbsp;<?= $lang['app_07'] ?>
                                                </div>
                                                <div class="col"> <input type='checkbox' name='data_logging' <?= $rights["data_logging"] == 1 ? "checked" : "" ?>>
                                                </div>
                                            </div>
                                        <?php } ?>

                                        <!-- File Repository -->
                                        <?php if (isset($allRights["file_repository"])) { ?>
                                            <div class="form-row">
                                                <div class="col">
                                                    <i class="fa-solid fa-fw fa-folder-open"></i>&nbsp;&nbsp;<?= $lang['app_04'] ?>
                                                </div>
                                                <div class="col">
                                                    <input type='checkbox' name='file_repository' <?= $rights["file_repository"] == 1 ? "checked" : "" ?>>
                                                </div>
                                            </div>
                                        <?php } ?>

                                        <!-- Randomization -->
                                        <?php if (isset($allRights["random_setup"])) { ?>
                                            <div class="form-row">
                                                <div class="col">
                                                    <i class="fa-solid fa-fw fa-random"></i>&nbsp;&nbsp;<?= $lang['app_21'] ?>
                                                </div>
                                                <div class="col">
                                                    <input type='checkbox' name='random_setup' <?= $rights["random_setup"] == 1 ? "checked" : "" ?>> <?= $lang['rights_142'] ?><br />
                                                    <input type='checkbox' name='random_dashboard' <?= $rights["random_dashboard"] == 1 ? "checked" : "" ?>> <?= $lang['rights_143'] ?><br />
                                                    <input type='checkbox' name='random_perform' <?= $rights["random_perform"] == 1 ? "checked" : "" ?>> <?= $lang['rights_144'] ?>
                                                </div>
                                            </div>
                                        <?php } ?>

                                        <!-- Data Quality -->
                                        <?php if (isset($allRights["data_quality_design"])) { ?>
                                            <div class="form-row">
                                                <div class="col">
                                                    <i class="fa-solid fa-fw fa-clipboard-check"></i>&nbsp;&nbsp;<?= $lang['app_20'] ?>
                                                </div>
                                                <div class="col">
                                                    <input type='checkbox' name='data_quality_design' <?= $rights["data_quality_design"] == 1 ? "checked" : "" ?>>
                                                    <?= $lang['dataqueries_40'] ?><br>
                                                    <input type='checkbox' name='data_quality_execute' <?= $rights["data_quality_execute"] == 1 ? "checked" : "" ?>>
                                                    <?= $lang['dataqueries_41'] ?>
                                                </div>
                                            </div>
                                        <?php } ?>

                                        <!-- Data Quality resolution -->
                                        <?php if (isset($allRights["data_quality_resolution"])) { ?>
                                            <div class="form-row">
                                                <div class="col">
                                                    <i class='fa-solid fa-fw fa-comments'></i>&nbsp;&nbsp;<?= $lang['dataqueries_137'] ?>
                                                </div>
                                                <div class="col">
                                                    <div>
                                                        <input type='checkbox' class='data_quality_resolution data_quality_resolution_view' name='data_quality_resolution_view' <?= $rights["data_quality_resolution_view"] == '1' ? "checked" : "" ?> onchange="if(!this.checked) {$('.data_quality_resolution').prop('checked', false);}"> View Queries
                                                    </div>
                                                    <div>
                                                        <input type='checkbox' class='data_quality_resolution data_quality_resolution_open' name='data_quality_resolution_open' <?= $rights["data_quality_resolution_open"] == '1' ? "checked" : "" ?> onchange="if(!this.checked) {$('.data_quality_resolution_close').prop('checked', false);} else {$('.data_quality_resolution_view').prop('checked', true);}"> Open Queries
                                                    </div>
                                                    <div>
                                                        <input type='checkbox' class='data_quality_resolution data_quality_resolution_respond' name='data_quality_resolution_respond' <?= $rights["data_quality_resolution_respond"] == '1' ? "checked" : "" ?> onchange="if(!this.checked) {$('.data_quality_resolution_close').prop('checked', false);} else {$('.data_quality_resolution_view').prop('checked', true);}"> Respond to Queries
                                                    </div>
                                                    <div>
                                                        <input type='checkbox' class='data_quality_resolution data_quality_resolution_close' name='data_quality_resolution_close' <?= $rights["data_quality_resolution_close"] == '1' ? "checked" : "" ?> onchange="if(this.checked) {$('.data_quality_resolution').prop('checked', true);}"> Close Queries
                                                    </div>
                                                </div>
                                            </div>
                                        <?php } ?>

                                        <!-- API -->
                                        <?php if (isset($allRights["api_export"])) { ?>
                                            <div class="form-row">
                                                <div class="col">
                                                    <i class="fa-solid fa-fw fa-laptop-code"></i>&nbsp;&nbsp;<?= $lang['setup_77'] ?>
                                                </div>
                                                <div class="col">
                                                    <input type='checkbox' name='api_export' <?= $rights["api_export"] == 1 ? "checked" : "" ?>> <?= $lang['rights_139'] ?><br />
                                                    <input type='checkbox' name='api_import' <?= $rights["api_import"] == 1 ? "checked" : "" ?>> <?= $lang['rights_314'] ?>
                                                </div>
                                            </div>
                                        <?php } ?>

                                        <!-- Dynamic Data Pull OR CDIS-->
                                        <?php if (isset($allRights["realtime_webservice_mapping"])) { ?>
                                            <div class="form-row">
                                                <div class="col" valign="top">
                                                    <div>
                                                        <i class="fa-solid fa-fw fa-database"></i>&nbsp;&nbsp; Clinical Data Pull from EHR -or- Dynamic Data Pull from External Source System
                                                    </div>
                                                </div>
                                                <div class="col" valign="top">
                                                    <div>
                                                        <!-- Mapping rights -->
                                                        <input type="checkbox" name="realtime_webservice_mapping" <?= $rights["realtime_webservice_mapping"] == 1 ? "checked" : "" ?>>
                                                        <?php echo $lang['ws_19'] ?>
                                                    </div>
                                                    <div>
                                                        <!-- Adjudication rights -->
                                                        <input type="checkbox" name="realtime_webservice_adjudicate" <?= $rights["realtime_webservice_adjudicate"] == 1 ? "checked" : "" ?>>
                                                        <?php echo $lang['ws_20'] ?>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php } else { ?>
                                            <!-- Hide input fields to maintain values if setting is disabled at project level -->
                                            <input type="hidden" name="realtime_webservice_mapping" value="<?= $rights["realtime_webservice_mapping"] ?>">
                                            <input type="hidden" name="realtime_webservice_adjudicate" value="<?= $rights["realtime_webservice_adjudicate"] ?>">
                                        <?php } ?>

                                        <!-- Data Transfer Services -->
                                        <?php if (isset($allRights["dts"])) { ?>
                                            <div class="form-row">
                                                <div class="col" valign="top">
                                                    <div>
                                                        <i class="fa-solid fa-fw fa-database"></i>&nbsp;&nbsp;<?= $lang["rights_132"] ?>
                                                    </div>
                                                </div>
                                                <div class="col" valign="top">

                                                    <div>
                                                        <input type="checkbox" name="dts" <?= $rights["dts"] == 1 ? "checked" : "" ?>>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php } ?>

                                        <!-- Mobile App -->
                                        <?php if (isset($allRights["mobile_app"])) { ?>
                                            <hr>
                                            <div class="form-row">
                                                <div class="col section-header" colspan='2'>
                                                    <?= $lang['rights_309'] ?>
                                                </div>

                                            </div>
                                            <div class="form-row">
                                                <div class="col">
                                                    <i class="fa-solid fa-fw fa-tablet-alt"></i>&nbsp;&nbsp;<?= $lang['global_118'] ?>
                                                    <div class="extra-text">
                                                        <?= $lang['rights_307'] ?>
                                                    </div>
                                                </div>
                                                <div class="col">
                                                    <input type='checkbox' name='mobile_app' <?= $rights["mobile_app"] == '1' ? "checked" : "" ?>>

                                                </div>
                                            </div>
                                            <div class="form-row">
                                                <div class="col">
                                                    <?= $lang['rights_306'] ?>
                                                </div>
                                                <div class="col">
                                                    <div>
                                                        <input type='checkbox' name='mobile_app_download_data' <?= $rights["mobile_app_download_data"] == '1' ? "checked" : "" ?>>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php } ?>

                                        <!-- Create/Rename/Delete Records -->
                                        <hr>
                                        <div class="form-row">
                                            <div class="col section-header" colspan='2'>
                                                <?= $lang['rights_119'] ?>
                                            </div>
                                        </div>
                                        <?php if (isset($allRights["record_create"])) { ?>
                                            <div class="form-row">
                                                <div class="col">
                                                    <i class="fa-solid fa-fw fa-plus-square"></i>&nbsp;&nbsp;<?= $lang['rights_99'] ?>
                                                </div>
                                                <div class="col">
                                                    <input type='checkbox' name='record_create' <?= $rights["record_create"] == 1 ? "checked" : "" ?>>
                                                </div>
                                            </div>
                                        <?php } ?>
                                        <?php if (isset($allRights["record_rename"])) { ?>
                                            <div class="form-row">
                                                <div class="col">
                                                    <i class="fa-solid fa-fw fa-exchange-alt"></i>&nbsp;&nbsp;<?= $lang['rights_100'] ?>
                                                </div>
                                                <div class="col">
                                                    <input type='checkbox' name='record_rename' <?= $rights["record_rename"] == 1 ? "checked" : "" ?>>
                                                </div>
                                            </div>
                                        <?php } ?>
                                        <?php if (isset($allRights["record_delete"])) { ?>
                                            <div class="form-row">
                                                <div class="col">
                                                    <i class="fa-solid fa-fw fa-minus-square"></i>&nbsp;&nbsp;<?= $lang['rights_101'] ?>
                                                </div>
                                                <div class="col">
                                                    <input type='checkbox' name='record_delete' <?= $rights["record_delete"] == 1 ? "checked" : "" ?>>
                                                </div>
                                            </div>
                                        <?php } ?>

                                        <!-- Lock Record -->
                                        <hr>
                                        <div class="form-row">
                                            <div class="col section-header" colspan='2'>
                                                <?= $lang['rights_130'] ?>
                                            </div>
                                        </div>
                                        <?php if (isset($allRights["lock_record_customize"])) { ?>
                                            <div class="form-row">
                                                <div class="col">
                                                    <div>
                                                        <i class="fa-solid fa-fw fa-lock"></i>&nbsp;&nbsp;<?= $lang['app_11'] ?>
                                                    </div>
                                                </div>
                                                <div class="col">
                                                    <input type='checkbox' name='lock_record_customize' <?= $rights["lock_record_customize"] == 1 ? "checked" : "" ?>>
                                                </div>
                                            </div>
                                        <?php } ?>
                                        <?php if (isset($allRights["lock_record"])) { ?>
                                            <div class="form-row">
                                                <div class="col">
                                                    <div>
                                                        <i class="fa-solid fa-fw fa-unlock-alt"></i>&nbsp;&nbsp;<?= $lang['rights_97'] ?> <?= $lang['rights_371'] ?>
                                                    </div>
                                                    <div class="extra-text">
                                                        <?= $lang['rights_113'] ?>
                                                    </div>
                                                </div>
                                                <div class="col">
                                                    <div><input type='radio' name='lock_record' value='0' <?= $rights["lock_record"] == '0' ? "checked" : "" ?>> <?= $lang['global_23'] ?></div>
                                                    <div><input type='radio' name='lock_record' value='1' <?= $rights["lock_record"] == '1' ? "checked" : "" ?>> <?= $lang['rights_115'] ?></div>
                                                    <div>
                                                        <input type='radio' name='lock_record' value='2' <?= $rights["lock_record"] == '2' ? "checked" : "" ?>> <?= $lang['rights_116'] ?><br>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php } ?>
                                        <?php if (isset($allRights["lock_record_multiform"])) { ?>
                                            <div class="form-row">
                                                <div class="col">
                                                    <div><i class="fa-solid fa-fw fa-unlock-alt"></i>&nbsp;&nbsp;<?= $lang['rights_370'] ?></div>
                                                </div>
                                                <div class="col">
                                                    <div>
                                                        <input type='checkbox' name='lock_record_multiform' <?= $rights["lock_record_multiform"] == '1' ? "checked" : "" ?>>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php } ?>
                                        </table>
                                    </div>
                                </div>
                            </div>
                            <div class="col" style="padding-left:10px;">
                                <div class='card' style='border-color:#00000060;'>
                                    <div class='card-header font-weight-bold fs14' style='background-color:#00000017;'>
                                        <?= $lang['data_export_tool_291'] ?>
                                    </div>
                                    <div class='card-body p-0' style='background-color:#00000007;'>
                                        <div class="form-row">
                                            <div class="col extra-text" colspan='3'>
                                                <?= $lang['rights_429'] ?>
                                            </div>
                                        </div>
                                        <div class="form-row">
                                            <div class="col" class='fs13 pb-2 text-center font-weight-bold' style='border-right:1px solid #FFA3A3;color:#000;'><?= $lang['rights_373'] ?></div>
                                            <div class="col" class='fs13 pb-2 text-center font-weight-bold' style='color:#B00000;'><?= $lang['rights_428'] ?></div>
                                        </div>
                                        <div class="form-row">
                                            <div class="col">
                                                <div><?= $lang['rights_47'] ?><br><?= $lang['rights_395'] ?></div>
                                                <div><?= $lang['rights_61'] ?></div>
                                                <div><?= $lang['rights_138'] ?></div>
                                                <div><?= $lang['rights_137'] ?></div>
                                            </div>
                                            <div class="col" style='color:#B00000;'>
                                                <div><?= $lang['rights_47'] ?></div>
                                                <div><?= $lang['rights_48'] ?>*</div>
                                                <div><?= $lang['data_export_tool_290'] ?></div>
                                                <div><?= $lang['rights_49'] ?></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button id="SUR_Save" type="button" class="btn btn-<?= $newRole ? "success" : "primary" ?>"><?= $newRole ? "Save New Role" : "Save Changes" ?></button>
                    <button id="SUR_Cancel" type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <?php if (!$newRole) { ?>
                        <button id="SUR_Copy" type="button" class="btn btn-info btn-sm">Copy role</button>
                        <button id="SUR_Delete" type="button" class="btn btn-danger btn-sm">Delete role</button>
                    <?php } ?>
                </div>
            </div>
        </div>
<?php
    }
}
