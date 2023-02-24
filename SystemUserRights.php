<?php

namespace YaleREDCap\SystemUserRights;

use ExternalModules\AbstractExternalModule;
use Throwable;

require_once "SUR_User.php";

class SystemUserRights extends AbstractExternalModule
{
    function redcap_module_configuration_settings($project_id, $settings)
    {
        if (empty($project_id)) {
            return $settings;
        }
        try {
            // Get existing user access
            $all_users = $this->getProject($project_id)->getUsers();
            foreach ($all_users as $user) {
                $username = $user->getUsername();
                $name = $this->getName($username);
                $user_key = $username . "_access";
                $existing_access = $this->getProjectSetting($user_key, $project_id);
                $settings[] = [
                    "key" => $user_key,
                    "name" => "<strong>" . ucwords($name) . "</strong> (" . $username . ")",
                    "type" => "checkbox",
                    "branchingLogic" => [
                        "field" => "restrict-access",
                        "value" => "1"
                    ]
                ];
            }

            return $settings;
        } catch (\Exception $e) {
            $this->log("Error creating configuration", ["error" => $e->getMessage()]);
        }
    }

    function redcap_user_rights($project_id)
    {
        echo "<p>REDCAP USER RIGHTS</p>";
    }

    function redcap_every_page_top($project_id)
    {
        echo "<p>REDCAP EVERY PAGE TOP</p>";
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
}
