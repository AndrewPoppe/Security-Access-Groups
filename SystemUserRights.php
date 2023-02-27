<?php

namespace YaleREDCap\SystemUserRights;

use ExternalModules\AbstractExternalModule;

require_once "SUR_User.php";

class SystemUserRights extends AbstractExternalModule
{

    function redcap_user_rights($project_id)
    {
?>
        <script>
            $(function() {
                window.saveUserFormAjax = function() {
                    showProgress(1);
                    const permissions = $('form#user_rights_form').serializeObject();
                    console.log(permissions);
                    $.post('<?= $this->getUrl("edit_user.php?pid=$project_id") ?>', permissions, function(data) {
                        showProgress(0, 0);
                        if (!data) {
                            Swal.fire({
                                icon: 'error',
                                title: "You can't do that.",
                                text: "You don't have that power."
                            });
                            return;
                        }
                        if ($('#editUserPopup').hasClass('ui-dialog-content')) $('#editUserPopup').dialog('destroy');
                        $('#user_rights_roles_table_parent').html(data);
                        simpleDialogAlt($('#user_rights_roles_table_parent div.userSaveMsg'), 1.7);
                        enablePageJS();
                        if ($('#copy_role_success').length) {
                            setTimeout(function() {
                                openAddUserPopup('', $('#copy_role_success').val());
                            }, 1500);
                        }
                    });
                }

                window.assignUserRole2 = function(username, role_id) {
                    $.post('<?= $this->getUrl("assign_user.php?pid=$project_id") ?>', {
                        "username": username,
                        "role_id": role_id
                    }, function(response) {
                        const result = JSON.parse(response);
                        if (result["error"]) {
                            Swal.fire({
                                icon: 'error',
                                title: "You can't do that.",
                                text: "You don't have that power."
                            });
                            return;
                        }
                        origAssignUserRole(username, role_id);
                    });
                }
            });
        </script>
<?php
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

    function getAcceptableRights(string $username, $project_id)
    {
        $project = $this->getProject($project_id);
        $rights =  $project->addMissingUserRightsKeys([]);
        return array_keys($rights);
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
}
