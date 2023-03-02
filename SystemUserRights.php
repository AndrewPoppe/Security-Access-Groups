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
        return \UserRights::getApiUserPrivilegesAttr(false, $this->getProjectId());
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
}
