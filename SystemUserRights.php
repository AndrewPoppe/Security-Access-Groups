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
                let origSaveUserFormAjax = saveUserFormAjax;
                let origAssignUserRole = assignUserRole;
                window.saveUserFormAjax = function() {
                    const permissions = $('form#user_rights_form').serializeObject();
                    console.log(permissions);
                    $.post('<?= $this->getUrl("edit_user.php?pid=$project_id") ?>', permissions, function(response) {
                        console.log(response);
                        const result = JSON.parse(response);
                        if (result["error"]) {
                            Swal.fire({
                                icon: 'error',
                                title: "You can't do that.",
                                text: "You don't have that power."
                            });
                            return;
                        }
                        origSaveUserFormAjax();
                    });
                }

                window.assignUserRole = function(username, role_id) {
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
}
