<?php

namespace YaleREDCap\SecurityAccessGroups;

require_once 'classes/APIHandler.php';
require_once 'classes/Alerts.php';
require_once 'classes/RightsChecker.php';
require_once 'classes/SagEditForm.php';
require_once 'classes/AjaxHandler.php';
use ExternalModules\AbstractExternalModule;
use ExternalModules\Framework;

/**
 * @property Framework $framework
 * @see Framework
 */
class SecurityAccessGroups extends AbstractExternalModule
{

    public string $defaultSagId = "sag_Default";
    public string $defaultSagName = "Default SAG";
    private array $defaultRights = [];


    public function __construct()
    {
        parent::__construct();
        $this->defaultRights = $this->getSagRightsById($this->defaultSagId);
    }

    public function redcap_every_page_before_render()
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
            ], true)
        ) {
            return;
        }

        // API
        if ( PAGE === "api/index.php" ) {
            $api = new APIHandler($this, $_POST);
            if ( !$api->shouldProcess() ) {
                return;
            }

            $api->handleRequest();
            if ( !$api->shouldAllowImport() ) {
                $badRights = $api->getBadRights();
                http_response_code(401);
                echo json_encode($badRights);
                $this->exitAfterHook();
                return;
            } else {
                [ $action, $project_id, $user, $original_rights ] = $api->getApiRequestInfo();
                $this->logApi($action, $project_id, $user, $original_rights);
            }
            return;
        }

        try {
            $username = $this->framework->getUser()->getUsername() ?? "";
        } catch ( \Throwable $e ) {
            $this->framework->log('Error', [ "error" => $e->getMessage() ]);
        }

        // Edit User or Role
        if (
            PAGE === "UserRights/edit_user.php" &&
            isset($_POST['submit-action']) &&
            in_array($_POST['submit-action'], [ "edit_role", "edit_user", "add_user" ])
        ) {
            $this->framework->log('attempt to edit user or role directly', [ "page" => PAGE, "data" => json_encode($_POST), "user" => $username ]);
            $this->framework->exitAfterHook();
            return;
        }

        // Assign User to Role
        if ( PAGE === "UserRights/assign_user.php" ) {
            $this->log('attempt to assign user role directly', [ "page" => PAGE, "data" => json_encode($_POST), "user" => $username ]);
            $this->exitAfterHook();
            return;
        }

        // Upload Users via CSV
        if ( PAGE === "UserRights/import_export_users.php" ) {
            $this->log('attempt to upload users directly', [ "page" => PAGE, "data" => json_encode($_POST), "user" => $username ]);
            $this->exitAfterHook();
            return;
        }

        // Upload Roles or Mappings via CSV
        if ( PAGE === "UserRights/import_export_roles.php" ) {
            $this->log('attempt to upload roles or role mappings directly', [ "page" => PAGE, "data" => json_encode($_POST), "user" => $username ]);
            $this->exitAfterHook();
            return;
        }
    }

    // CRON job
    public function sendReminders($cronInfo = array())
    {
        try {
            $alerts            = new Alerts($this);
            $enabledSystemwide = $this->framework->getSystemSetting('enabled');
            $prefix            = $this->getModuleDirectoryPrefix();

            if ( $enabledSystemwide ) {
                $allProjectIds = $this->getAllProjectIds();
                $projectIds    = array_filter($allProjectIds, function ($projectId) use ($prefix) {
                    return $this->isModuleEnabled($prefix, $projectId);
                });
            } else {
                $projectIds = $this->framework->getProjectsWithModuleEnabled();
            }

            foreach ( $projectIds as $localProjectId ) {
                // Specifying project id just to prevent reminders being sent
                // for projects that no longer have the module enabled.
                $alerts->sendUserReminders($localProjectId);
            }

            return "The \"{$cronInfo['cron_name']}\" cron job completed successfully.";
        } catch ( \Exception $e ) {
            $this->log("Error sending reminders", [ "error" => $e->getMessage() ]);
            return "The \"{$cronInfo['cron_name']}\" cron job failed: " . $e->getMessage();
        }
    }

    public function getAllProjectIds()
    {
        try {
            $query      = "select project_id from redcap_projects
            where created_by is not null
            and completed_time is null
            and date_deleted is null";
            $result     = $this->framework->query($query, []);
            $projectIds = [];
            while ( $row = $result->fetch_assoc() ) {
                $projectIds[] = intval($row["project_id"]);
            }
            return $projectIds;
        } catch ( \Exception $e ) {
            $this->log("Error fetching all projects", [ "error" => $e->getMessage() ]);
        }
    }

    public function redcap_user_rights($projectId)
    {
        if ( isset($_SESSION['SAG_imported']) ) {
            echo "<script>window.import_type = '" . $_SESSION['SAG_imported'] . "';" .
                "window.import_errors = JSON.parse('" . $_SESSION['SAG_bad_rights'] . "');</script>";
            unset($_SESSION['SAG_imported']);
            unset($_SESSION['SAG_bad_rights']);
        }
        $js = file_get_contents($this->framework->getSafePath('js/redcap_user_rights.js'));
        $js = str_replace('{{IMPORT_EXPORT_USERS_URL}}', $this->framework->getUrl('ajax/import_export_users.php'), $js);
        $js = str_replace('{{IMPORT_EXPORT_ROLES_URL}}', $this->framework->getUrl('ajax/import_export_roles.php'), $js);
        $js = str_replace('{{IMPORT_EXPORT_MAPPINGS_URL}}', $this->framework->getUrl('ajax/import_export_roles.php?action=uploadMapping'), $js);
        $js = str_replace('{{EDIT_USER_URL}}', $this->framework->getUrl('ajax/edit_user.php?pid=' . $projectId), $js);
        $js = str_replace('{{ASSIGN_USER_URL}}', $this->framework->getUrl('ajax/assign_user.php?pid=' . $projectId), $js);
        $js = str_replace('{{SET_USER_EXPIRATION_URL}}', $this->framework->getUrl('ajax/set_user_expiration.php?pid=' . $projectId), $js);
        echo '<script type="text/javascript">' . $js . '</script>';
    }

    public function redcap_module_project_enable($version, $projectId)
    {
        $this->log('Module Enabled');
    }

    public function redcap_module_link_check_display($projectId, $link)
    {
        if ( empty($projectId) || $this->getUser()->isSuperUser() ) {
            return $link;
        }

        return null;
    }

    public function getCurrentRightsFormatted(string $username, $projectId)
    {
        $currentRights     = $this->getCurrentRights($username, $projectId);
        $currentDataExport = $this->convertExportRightsStringToArray($currentRights["data_export_instruments"]);
        $currentDataEntry  = $this->convertDataEntryRightsStringToArray($currentRights["data_entry"]);
        $currentRights     = array_merge($currentRights, $currentDataExport, $currentDataEntry);
        unset($currentRights["data_export_instruments"]);
        unset($currentRights["data_entry"]);
        unset($currentRights["data_export_tool"]);
        unset($currentRights["external_module_config"]);
        return $currentRights;
    }


    private function getBasicProjectUsers($projectId)
    {
        $sql = 'select rights.username,
        info.user_firstname,
        info.user_lastname,
        user_email,
        expiration,
        rights.role_id,
        roles.unique_role_name,
        roles.role_name,
        em.value as sag
        from redcap_user_rights rights
        left join redcap_user_roles roles
        on rights.role_id = roles.role_id
        left join redcap_user_information info
        on rights.username = info.username
        LEFT JOIN redcap_external_module_settings em ON em.key = concat(rights.username,\'-sag\')
        where rights.project_id = ?';
        try {
            $result = $this->framework->query($sql, [ $projectId ]);
            $users  = [];
            while ( $row = $result->fetch_assoc() ) {
                $users[] = $this->framework->escape($row);
            }
            return $users;
        } catch ( \Throwable $e ) {
            $this->framework->log('Error getting project users', [ 'error' => $e->getMessage() ]);
            return [];
        }
    }
    public function getUsersWithBadRights($projectId)
    {
        $users     = $this->getBasicProjectUsers($projectId);
        $sags      = $this->getAllSags(true);
        $badRights = [];
        foreach ( $users as $user ) {
            $expiration            = $user["expiration"];
            $isExpired             = $expiration != "" && strtotime($expiration) < strtotime("today");
            $username              = $user["username"];
            $acceptableRights      = $sags[$user["sag"]]["permissions"];
            $currentRights         = $this->getCurrentRightsFormatted($username, $projectId);
            $bad                   = $this->checkProposedRights($acceptableRights, $currentRights);
            $sagName               = $sags[$user["sag"]]["sag_name"];
            $projectRoleUniqueName = $user["unique_role_name"];
            $projectRoleName       = $user["role_name"];
            $badRights[]           = [
                "username"          => $username,
                "name"              => $user["user_firstname"] . " " . $user["user_lastname"],
                "email"             => $user["user_email"],
                "expiration"        => $expiration == "" ? "never" : $expiration,
                "isExpired"         => $isExpired,
                "sag"               => $user["sag"],
                "sag_name"          => $sagName,
                "project_role"      => $projectRoleUniqueName,
                "project_role_name" => $projectRoleName,
                "acceptable"        => $acceptableRights,
                "current"           => $currentRights,
                "bad"               => $bad
            ];
        }
        return $badRights;
    }

    public function getUsersWithBadRights2($projectId)
    {
        $users            = $this->getBasicProjectUsers($projectId);
        $sags             = $this->getAllSags(true);
        $allCurrentRights = $this->getAllCurrentRights($projectId);
        $badRights        = [];
        foreach ( $users as $user ) {
            $expiration            = $user['expiration'];
            $isExpired             = $expiration != '' && strtotime($expiration) < strtotime('today');
            $username              = $user['username'];
            $sag                   = $user['sag'] ?? $this->defaultSagId;
            $sag                   = array_key_exists($sag, $sags) ? $sag : $this->defaultSagId;
            $acceptableRights      = $sags[$sag]['permissions'];
            $currentRights         = $allCurrentRights[$username];
            $bad                   = $this->checkProposedRights2($acceptableRights, $currentRights);
            $sagName               = $sags[$sag]['sag_name'];
            $projectRoleUniqueName = $user['unique_role_name'];
            $projectRoleName       = $user['role_name'];
            $badRights[]           = [
                'username'          => $username,
                'name'              => $user['user_firstname'] . ' ' . $user['user_lastname'],
                'email'             => $user['user_email'],
                'expiration'        => $expiration == '' ? 'never' : $expiration,
                'isExpired'         => $isExpired,
                'sag'               => $sag,
                'sag_name'          => $sagName,
                'project_role'      => $projectRoleUniqueName,
                'project_role_name' => $projectRoleName,
                'acceptable'        => $acceptableRights,
                'current'           => $currentRights,
                'bad'               => $bad
            ];
        }
        return $badRights;
    }

    private function logApiUser($projectId, $user, array $originalRights)
    {
        foreach ( $originalRights as $theseOriginalRights ) {
            $username   = $theseOriginalRights["username"];
            $oldRights  = $theseOriginalRights["rights"] ?? [];
            $newUser    = empty($oldRights);
            $newRights  = $this->getCurrentRights($username, $projectId);
            $changes    = json_encode(array_diff_assoc($newRights, $oldRights), JSON_PRETTY_PRINT);
            $changes    = $changes === "[]" ? "None" : $changes;
            $dataValues = "user = '" . $username . "'\nchanges = " . $changes;
            if ( $newUser ) {
                $event       = "INSERT";
                $description = "Add user";
            } else {
                $event       = "UPDATE";
                $description = "Edit user";
            }
            $logTable   = $this->framework->getProject($projectId)->getLogTable();
            $sql        = "SELECT log_event_id FROM $logTable WHERE project_id = ? AND user = ? AND page = 'api/index.php' AND object_type = 'redcap_user_rights' AND pk = ? AND event = ? AND TIMESTAMPDIFF(SECOND,ts,NOW()) <= 10 ORDER BY ts DESC";
            $params     = [ $projectId, $user, $username, $event ];
            $result     = $this->framework->query($sql, $params);
            $logEventId = intval($result->fetch_assoc()["log_event_id"]);
            if ( $logEventId != 0 ) {
                $this->framework->query("UPDATE $logTable SET data_values = ? WHERE log_event_id = ?", [ $dataValues, $logEventId ]);
            } else {
                \Logging::logEvent(
                    '',
                    'redcap_user_rights',
                    $event,
                    $username,
                    $dataValues,
                    $description,
                    "",
                    "",
                    "",
                    true,
                    null,
                    null,
                    false
                );
            }
        }
    }

    private function logApiUserRole($projectId, $user, array $originalRights)
    {
        $newRights = \UserRights::getRoles($projectId);
        foreach ( $newRights as $role_id => $role ) {
            $oldRights  = $originalRights[$role_id] ?? [];
            $newRole    = empty($oldRights);
            $roleLabel  = $role["role_name"];
            $changes    = json_encode(array_diff_assoc($role, $oldRights), JSON_PRETTY_PRINT);
            $changes    = $changes === "[]" ? "None" : $changes;
            $dataValues = "role = '" . $roleLabel . "'\nchanges = " . $changes;
            $logTable   = $this->framework->getProject($projectId)->getLogTable();

            if ( $newRole ) {
                $description    = 'Add role';
                $event          = 'INSERT';
                $origDataValues = "role = '" . $roleLabel . "'";
                $objectType     = "redcap_user_rights";
                $sql            = "SELECT log_event_id FROM $logTable WHERE project_id = ? AND user = ? AND page = 'api/index.php' AND object_type = 'redcap_user_rights' AND pk IS NULL AND event = 'INSERT' AND data_values = ? AND TIMESTAMPDIFF(SECOND,ts,NOW()) <= 10 ORDER BY ts DESC";
                $params         = [ $projectId, $user, $origDataValues ];
            } else {
                $description = "Edit role";
                $event       = "update";
                $objectType  = "redcap_user_roles";
                $sql         = "SELECT log_event_id FROM $logTable WHERE project_id = ? AND user = ? AND page = 'api/index.php' AND object_type = 'redcap_user_roles' AND pk = ? AND event = 'UPDATE' AND TIMESTAMPDIFF(SECOND,ts,NOW()) <= 10 ORDER BY ts DESC";
                $params      = [ $projectId, $user, $role_id ];
            }

            $result     = $this->framework->query($sql, $params);
            $logEventId = intval($result->fetch_assoc()["log_event_id"]);
            if ( $logEventId != 0 ) {
                $this->framework->query("UPDATE $logTable SET data_values = ? WHERE log_event_id = ?", [ $dataValues, $logEventId ]);
            } else {
                \Logging::logEvent(
                    '',
                    $objectType,
                    $event,
                    $role_id,
                    $dataValues,
                    $description,
                    "",
                    "",
                    "",
                    true,
                    null,
                    null,
                    false
                );
            }
        }
    }

    private function logApiUserRoleMapping($projectId, $user, array $originalRights)
    {
        foreach ( $originalRights as $mapping ) {
            $username       = $mapping["username"];
            $uniqueRoleName = $mapping["unique_role_name"];
            $roleId         = $this->getRoleIdFromUniqueRoleName($uniqueRoleName);
            $roleLabel      = $this->getRoleLabel($roleId);

            $logTable   = $this->framework->getProject($projectId)->getLogTable();
            $sql        = "SELECT log_event_id FROM $logTable WHERE project_id = ? AND user = ? AND page = 'api/index.php' AND object_type = 'redcap_user_rights' AND pk = ? AND event = 'INSERT' AND TIMESTAMPDIFF(SECOND,ts,NOW()) <= 10 ORDER BY ts DESC";
            $params     = [ $projectId, $user, $username ];
            $result     = $this->framework->query($sql, $params);
            $logEventId = intval($result->fetch_assoc()["log_event_id"]);

            $dataValues = "user = '" . $username . "'\nrole = '" . $roleLabel . "'\nunique_role_name = '" . $uniqueRoleName . "'";

            if ( $logEventId != 0 ) {
                $this->framework->query("UPDATE $logTable SET data_values = ? WHERE log_event_id = ?", [ $dataValues, $logEventId ]);
            } else {
                \Logging::logEvent(
                    '',
                    'redcap_user_rights',
                    'INSERT',
                    $username,
                    $dataValues,
                    'Assign user to role',
                    "",
                    "",
                    "",
                    true,
                    null,
                    null,
                    false
                );
            }
        }
    }

    private function logApi(string $action, $projectId, $user, array $originalRights)
    {
        ob_start(function ($str) use ($action, $projectId, $user, $originalRights) {

            if ( strpos($str, '{"error":') === 0 ) {
                $this->log('api_failed');
                return $str;
            }
            if ( $action === "user" ) {
                $this->logApiUser($projectId, $user, $originalRights);
            } elseif ( $action === "userRole" ) {
                $this->logApiUserRole($projectId, $user, $originalRights);
            } elseif ( $action === "userRoleMapping" ) {
                $this->logApiUserRoleMapping($projectId, $user, $originalRights);
            }

            return $str;
        }, 0, PHP_OUTPUT_HANDLER_FLUSHABLE);
    }

    public function getUserInfo(string $username) : ?array
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
            $result = $this->framework->query($sql, [ $username ]);
            return $this->framework->escape($result->fetch_assoc());
        } catch ( \Throwable $e ) {
            $this->log("Error getting user info", [ "username" => $username, "error" => $e->getMessage(), "user" => $this->getUser()->getUsername() ]);
        }
    }

    public function getAllUserInfo($includeSag = false) : ?array
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
        , allow_create_db";
        if ( $includeSag ) {
            $sql .= ", em.value as sag";
        }
        $sql .= " FROM redcap_user_information u";
        if ( $includeSag ) {
            $sql .= " LEFT JOIN redcap_external_module_settings em ON em.key = concat(u.username,'-sag')";
        }
        try {
            $result   = $this->framework->query($sql, []);
            $userinfo = [];
            while ( $row = $result->fetch_assoc() ) {
                $userinfo[] = $this->framework->escape($row);
            }
            return $userinfo;
        } catch ( \Throwable $e ) {
            $this->log("Error getting all user info", [ "error" => $e->getMessage(), "user" => $this->getUser()->getUsername() ]);
        }
    }

    public function getAllRights()
    {
        $sql    = "SHOW COLUMNS FROM redcap_user_rights";
        $result = $this->framework->query($sql, []);
        $rights = [];
        while ( $row = $result->fetch_assoc() ) {
            if ( !in_array($row["Field"], [ "project_id", "username", "expiration", "role_id", "group_id", "api_token", "data_access_group" ], true) ) {
                $rights[$row["Field"]] = $this->framework->escape($row["Field"]);
            }
        }
        return $rights;
    }

    public function getAcceptableRights(string $username)
    {
        $sagId = $this->getUserSag($username);
        $sag   = $this->getSagRightsById($sagId);
        return json_decode($sag["permissions"], true);
    }

    // E.g., from ["export-form-form1"=>"1", "export-form-form2"=>"1"] to "[form1,1][form2,1]"
    private function convertExportRightsArrayToString($fullRightsArray)
    {
        $result = "";
        foreach ( $fullRightsArray as $key => $value ) {
            if ( substr_compare($key, "export-form-", 0, strlen("export-form-")) === 0 ) {
                $formName = str_replace("export-form-", "", $key);
                $result .= "[" . $formName . "," . $value . "]";
            }
        }
        return $result;
    }

    // E.g., from ["form-form1"=>"1", "form-form2"=>"1"] to "[form1,1][form2,1]"
    private function convertDataEntryRightsArrayToString($fullRightsArray)
    {
        $result = "";
        foreach ( $fullRightsArray as $key => $value ) {
            if ( substr_compare($key, "form-", 0, strlen("form-")) === 0 && substr_compare($key, "form-editresp-", 0, strlen("form-editresp-")) !== 0 ) {
                $formName = str_replace("form-", "", $key);

                if ( $fullRightsArray["form-editresp-" . $formName] === "on" ) {
                    $value = "3";
                }

                $result .= "[" . $formName . "," . $value . "]";
            }
        }
        return $result;
    }

    // E.g., from "[form1,1][form2,1]" to ["export-form-form1"=>"1", "export-form-form2"=>"1"]
    private function convertExportRightsStringToArray($fullRightsString)
    {
        $raw    = \UserRights::convertFormRightsToArray($fullRightsString);
        $result = [];
        foreach ( $raw as $key => $value ) {
            $result["export-form-" . $key] = $value;
        }
        return $result;
    }

    // E.g., from "[form1,1][form2,1]" to ["form-form1"=>"1", "form-form2"=>"1"]
    private function convertDataEntryRightsStringToArray($fullRightsString)
    {
        $raw    = \UserRights::convertFormRightsToArray($fullRightsString);
        $result = [];
        foreach ( $raw as $key => $value ) {
            if ( $value == 3 ) {
                $result["form-" . $key]          = 2;
                $result["form-editresp-" . $key] = "on";
            } else {
                $result["form-" . $key] = $value;
            }
        }
        return $result;
    }


    public function checkProposedRights(array $acceptableRights, array $requestedRights)
    {
        $rightsChecker = new RightsChecker($this, $requestedRights, $acceptableRights);
        return $rightsChecker->checkRights();
    }

    private function checkProposedRights2(array $acceptableRights, array $requestedRights)
    {
        $rightsChecker = new RightsChecker($this, $requestedRights, $acceptableRights);
        return $rightsChecker->checkRights2();
    }

    public function isUserExpired($username, $projectId)
    {
        $sql    = "SELECT * FROM redcap_user_rights WHERE username = ? AND project_id = ?";
        $result = $this->framework->query($sql, [ $username, $projectId ]);
        $row    = $result->fetch_assoc();
        return !is_null($row["expiration"]) && strtotime($row["expiration"]) < strtotime("today");
    }

    /**
     * Gets project role's ID from its unique role name
     * @param string $uniqueRoleName
     * @return string project role's ID
     */
    public function getRoleIdFromUniqueRoleName(string $uniqueRoleName)
    {
        $sql    = "SELECT role_id FROM redcap_user_roles WHERE unique_role_name = ?";
        $result = $this->framework->query($sql, [ $uniqueRoleName ]);
        $row    = $result->fetch_assoc();
        return $this->framework->escape($row["role_id"]);
    }

    /**
     * Gets project role's unique role name from its ID
     * @param mixed $roleId
     * @return string project role's unique role name
     */
    public function getUniqueRoleNameFromRoleId($roleId)
    {
        $sql    = "SELECT unique_role_name FROM redcap_user_roles WHERE role_id = ?";
        $result = $this->framework->query($sql, [ $roleId ]);
        $row    = $result->fetch_assoc();
        return $this->framework->escape($row["unique_role_name"]);
    }

    public function getUsersInRole($projectId, $roleId)
    {
        if ( empty($roleId) ) {
            return [];
        }
        $sql    = "select * from redcap_user_rights where project_id = ? and role_id = ?";
        $result = $this->framework->query($sql, [ $projectId, $roleId ]);
        $users  = [];
        while ( $row = $result->fetch_assoc() ) {
            $users[] = $row["username"];
        }
        return $this->framework->escape($users);
    }

    public function getRoleLabel($roleId)
    {
        $sql    = "SELECT role_name FROM redcap_user_roles WHERE role_id = ?";
        $result = $this->framework->query($sql, [ $roleId ]);
        $row    = $result->fetch_assoc();
        return $this->framework->escape($row["role_name"]);
    }

    public function getRoleRightsRaw($roleId)
    {
        $sql    = "SELECT * FROM redcap_user_roles WHERE role_id = ?";
        $result = $this->framework->query($sql, [ $roleId ]);
        return $this->framework->escape($result->fetch_assoc());
    }

    public function getRoleRights($roleId, $pid = null)
    {
        $projectId = $pid ?? $this->getProjectId();
        $roles     = \UserRights::getRoles($projectId);
        $thisRole  = $roles[$roleId];
        return array_filter($thisRole, function ($value, $key) {
            $off          = $value === "0";
            $null         = is_null($value);
            $unset        = isset($value) && is_null($value);
            $excluded     = in_array($key, [ "role_name", "unique_role_name", "project_id", "data_entry", "data_export_instruments" ], true);
            $alsoExcluded = !in_array($key, $this->getAllRights(), true);
            return !$off && !$unset && !$excluded && !$alsoExcluded && !$null;
        }, ARRAY_FILTER_USE_BOTH);
    }

    public function getModuleDirectoryPrefix()
    {
        return strrev(preg_replace("/^.*v_/", "", strrev($this->framework->getModuleDirectoryName()), 1));
    }

    private function setUserSag($username, $sagId)
    {
        $setting = $username . "-sag";
        $this->setSystemSetting($setting, $sagId);
    }

    public function getUserSag($username)
    {
        $setting = $username . "-sag";
        $sag     = $this->getSystemSetting($setting);
        if ( empty($sag) || !$this->sagExists($sag) ) {
            $sag = $this->defaultSagId;
            $this->setUserSag($username, $sag);
        }
        return $sag;
    }

    private function convertDataQualityResolution($rights)
    {
        // 0: no access
        // 1: view only
        // 4: open queries only
        // 2: respond only to opened queries
        // 5: open and respond to queries
        // 3: open, close, and respond to queries
        $value = $rights["data_quality_resolution"];
        if ( $value ) {
            $rights["data_quality_resolution_view"]    = intval($value) > 0 ? 1 : 0;
            $rights["data_quality_resolution_open"]    = in_array(intval($value), [ 3, 4, 5 ], true) ? 1 : 0;
            $rights["data_quality_resolution_respond"] = in_array(intval($value), [ 2, 3, 5 ], true) ? 1 : 0;
            $rights["data_quality_resolution_close"]   = intval($value) === 3 ? 1 : 0;
        }
        return $rights;
    }

    private function convertPermissions(string $permissions)
    {
        $rights = json_decode($permissions, true);
        $rights = $this->convertDataQualityResolution($rights);
        foreach ( $rights as $key => $value ) {
            if ( $value === "on" ) {
                $rights[$key] = 1;
            }
        }

        return json_encode($rights);
    }

    public function throttleSaveSag(string $roleId, string $roleName, string $permissions)
    {
        if ( !$this->throttle("message = ?", 'role', 3, 1) ) {
            $this->saveSag($roleId, $roleName, $permissions);
        } else {
            $this->log('saveSag Throttled', [
                "role_id"   => $roleId,
                "role_name" => $roleName,
                "user"      => $this->getUser()->getUsername()
            ]);
        }
    }

    /**
     * @param string $sagId
     * @param string $sagName
     * @param string $permissions - json-encoded string of user rights
     *
     * @return [type]
     */
    public function saveSag(string $sagId, string $sagName, string $permissions)
    {
        try {
            $permissionsConverted = $this->convertPermissions($permissions);
            $this->log("sag", [
                "sag_id"      => $sagId,
                "sag_name"    => $sagName,
                "permissions" => $permissionsConverted,
                "user"        => $this->getUser()->getUsername()
            ]);
            $this->framework->log("Saved SAG", [
                "sag_id"      => $sagId,
                "sag_name"    => $sagName,
                "permissions" => $permissionsConverted
            ]);
        } catch ( \Throwable $e ) {
            $this->log('Error saving SAG', [
                "error"       => $e->getMessage(),
                "sag_id"      => $sagId,
                "sag_name"    => $sagName,
                "permissions" => $permissionsConverted,
                "user"        => $this->getUser()->getUsername()
            ]);
        }
    }

    public function throttleUpdateSag(string $sagId, string $sagName, string $permissions)
    {
        if ( !$this->throttle("message = 'Updated SAG'", [], 3, 1) ) {
            $this->updateSag($sagId, $sagName, $permissions);
        } else {
            $this->log('updateSag Throttled', [ "sag_id" => $sagId, "sag_name" => $sagName, "user" => $this->getUser()->getUsername() ]);
        }
    }

    public function updateSag(string $sagId, string $sagName, string $permissions)
    {
        try {
            $permissionsConverted = $this->convertPermissions($permissions);
            $sql1                 = "SELECT log_id WHERE message = 'sag' AND sag_id = ? AND project_id IS NULL";
            $result1              = $this->framework->queryLogs($sql1, [ $sagId ]);
            $logId                = intval($result1->fetch_assoc()["log_id"]);
            if ( $logId === 0 ) {
                throw new \Error('No SAG found with the specified id');
            }
            $params = [ "sag_name" => $sagName, "permissions" => $permissionsConverted ];
            foreach ( $params as $name => $value ) {
                $sql = "UPDATE redcap_external_modules_log_parameters SET value = ? WHERE log_id = ? AND name = ?";
                $this->framework->query($sql, [ $value, $logId, $name ]);
            }
            $this->log('Updated SAG', [
                'sag_id'      => $sagId,
                'sag_name'    => $sagName,
                'permissions' => $permissionsConverted,
                "user"        => $this->getUser()->getUsername()
            ]);
        } catch ( \Throwable $e ) {
            $this->log('Error updating SAG', [
                'error'                 => $e->getMessage(),
                'sag_id'                => $sagId,
                'sag_name'              => $sagName,
                'permissions_orig'      => $permissions,
                'permissions_converted' => $permissionsConverted,
                "user"                  => $this->getUser()->getUsername()
            ]);
        }
    }

    public function throttleDeleteSag($sagId)
    {
        if ( !$this->throttle("message = 'Deleted SAG'", [], 2, 1) ) {
            $this->deleteSag($sagId);
        } else {
            $this->log('deleteSag Throttled', [ "sag_id" => $sagId, "user" => $this->getUser()->getUsername() ]);
        }
    }

    private function deleteSag($sagId)
    {
        try {
            $result = $this->removeLogs("message = 'sag' AND sag_id = ? AND (project_id IS NULL OR project_id IS NOT NULL) ", [ $sagId ]);
            $this->log('Deleted SAG', [
                'user'   => $this->getUser()->getUsername(),
                'sag_id' => $sagId
            ]);
            return $result;
        } catch ( \Throwable $e ) {
            $this->log('Error deleting SAG', [ "error" => $e->getMessage(), "user" => $this->getUser()->getUsername(), "sag_id" => $sagId ]);
        }
    }

    public function getAllSags($parsePermissions = false)
    {
        $sql    = 'SELECT MAX(log_id) AS \'log_id\' WHERE message = \'sag\' AND (project_id IS NULL OR project_id IS NOT NULL) GROUP BY sag_id';
        $result = $this->framework->queryLogs($sql, []);
        $sags   = [];
        while ( $row = $result->fetch_assoc() ) {
            $logId            = $row['log_id'];
            $sql2             = 'SELECT sag_id, sag_name, permissions WHERE (project_id IS NULL OR project_id IS NOT NULL) AND log_id = ?';
            $result2          = $this->framework->queryLogs($sql2, [ $logId ]);
            $sag              = $result2->fetch_assoc();
            $sag['role_name'] = $this->framework->escape($sag['sag_name']);
            if ( $parsePermissions ) {
                $sag['permissions']   = json_decode($sag['permissions'], true);
                $sags[$sag['sag_id']] = $sag;
            } else {
                $sags[] = $sag;
            }
        }
        return $sags;
    }

    private function setDefaultSag()
    {
        $rights                  = $this->getDefaultRights();
        $rights['sag_id']        = $this->defaultSagId;
        $rights['sag_name_edit'] = $this->defaultSagName;
        $rights['dataViewing']   = '3';
        $rights['dataExport']    = '3';
        $this->saveSag($this->defaultSagId, $this->defaultSagName, json_encode($rights));
        return $rights;
    }

    public function getSagRightsById($sagId)
    {
        if ( empty($sagId) ) {
            $sagId = $this->defaultSagId;
        }
        $sql    = "SELECT sag_id, sag_name, permissions WHERE message = 'sag' AND sag_id = ? AND (project_id IS NULL OR project_id IS NOT NULL) ORDER BY log_id DESC LIMIT 1";
        $result = $this->framework->queryLogs($sql, [ $sagId ]);
        $rights = $result->fetch_assoc();
        if ( empty($rights) ) {
            $sagId2  = $this->defaultSagId;
            $result2 = $this->framework->queryLogs($sql, [ $sagId2 ]);
            $rights  = $result2->fetch_assoc();

            if ( empty($rights) ) {
                $rights = $this->setDefaultSag();
            }
        }
        return $rights;
    }

    public function sagExists($sagId)
    {
        if ( empty($sagId) ) {
            return false;
        }
        foreach ( $this->getAllSags() as $sag ) {
            if ( $sagId == $sag["sag_id"] ) {
                return true;
            }
        }
        return false;
    }

    public function generateNewSagId()
    {
        $newSagId = "sag_" . substr(md5(uniqid()), 0, 13);

        if ( $this->sagExists($newSagId) ) {
            return $this->generateNewSagId();
        } else {
            return $newSagId;
        }
    }

    public function getDisplayTextForRights(bool $allRights = false)
    {
        global $lang;
        $rights = [
            'design'                         => $lang['rights_135'],
            'user_rights'                    => $lang['app_05'],
            'data_access_groups'             => $lang['global_22'],
            'dataViewing'                    => $lang['rights_373'],
            'dataExport'                     => $lang['rights_428'],
            'alerts'                         => $lang['global_154'],
            'reports'                        => $lang['rights_96'],
            'graphical'                      => $lang['report_builder_78'],
            'participants'                   => $lang['app_24'],
            'calendar'                       => $lang['app_08'] . " " . $lang['rights_357'],
            'data_import_tool'               => $lang['app_01'],
            'data_comparison_tool'           => $lang['app_02'],
            'data_logging'                   => $lang['app_07'],
            'file_repository'                => $lang['app_04'],
            'double_data'                    => $lang['rights_50'],
            'lock_record_customize'          => $lang['app_11'],
            'lock_record'                    => $lang['rights_97'],
            'randomization'                  => $lang['app_21'],
            'data_quality_design'            => $lang['dataqueries_38'],
            'data_quality_execute'           => $lang['dataqueries_39'],
            'data_quality_resolution'        => $lang['dataqueries_137'],
            'api'                            => $lang['setup_77'],
            'mobile_app'                     => $lang['global_118'],
            'realtime_webservice_mapping'    => "CDP/DDP" . " " . $lang['ws_19'],
            'realtime_webservice_adjudicate' => "CDP/DDP" . " " . $lang['ws_20'],
            'dts'                            => $lang['rights_132'],
            'mycap_participants'             => $lang['rights_437'],
            'record_create'                  => $lang['rights_99'],
            'record_rename'                  => $lang['rights_100'],
            'record_delete'                  => $lang['rights_101']

        ];
        if ( $allRights === true ) {
            $rights['random_setup']                    = $lang['app_21'] . " - " . $lang['rights_142'];
            $rights['random_dashboard']                = $lang['app_21'] . " - " . $lang['rights_143'];
            $rights['random_perform']                  = $lang['app_21'] . " - " . $lang['rights_144'];
            $rights['data_quality_resolution_view']    = 'Data Quality Resolution - View Queries';
            $rights['data_quality_resolution_open']    = 'Data Quality Resolution - Open Queries';
            $rights['data_quality_resolution_respond'] = 'Data Quality Resolution - Respond to Queries';
            $rights['data_quality_resolution_close']   = 'Data Quality Resolution - Close Queries';
            $rights['api_export']                      = $lang['rights_139'];
            $rights['api_import']                      = $lang['rights_314'];
            $rights['mobile_app_download_data']        = $lang['rights_306'];
            $rights['lock_record_multiform']           = $lang['rights_370'];
        }
        return $rights;
    }

    public function getDisplayTextForRight(string $right, string $key = "")
    {
        $rights = $this->getDisplayTextForRights(true);
        return $rights[$right] ?? $rights[$key] ?? $right;
    }

    public function convertRightName($rightName)
    {

        $conversions = [
            "stats_and_charts"           => "graphical",
            "manage_survey_participants" => "participants",
            "logging"                    => "data_logging",
            "data_quality_create"        => "data_quality_design",
            "lock_records_all_forms"     => "lock_record_multiform",
            "lock_records"               => "lock_record",
            "lock_records_customization" => "lock_record_customize"
        ];

        return $conversions[$rightName] ?? $rightName;
    }

    public function filterPermissions($rawArray)
    {
        $allRights                         = $this->getAllRights();
        $dataEntryString                   = $this->convertDataEntryRightsArrayToString($rawArray);
        $dataExportString                  = $this->convertExportRightsArrayToString($rawArray);
        $result                            = array_intersect_key($rawArray, $allRights);
        $result["data_export_instruments"] = $dataExportString;
        $result["data_entry"]              = $dataEntryString;
        return $result;
    }

    public function getDefaultRights()
    {
        $allRights = $this->getAllRights();
        if ( isset($allRights["data_export_tool"]) ) {
            $allRights["data_export_tool"] = 2;
        }
        if ( isset($allRights["data_import_tool"]) ) {
            $allRights["data_import_tool"] = 0;
        }
        if ( isset($allRights["data_comparison_tool"]) ) {
            $allRights["data_comparison_tool"] = 0;
        }
        if ( isset($allRights["data_logging"]) ) {
            $allRights["data_logging"] = 0;
        }
        if ( isset($allRights["file_repository"]) ) {
            $allRights["file_repository"] = 1;
        }
        if ( isset($allRights["double_data"]) ) {
            $allRights["double_data"] = 0;
        }
        if ( isset($allRights["user_rights"]) ) {
            $allRights["user_rights"] = 0;
        }
        if ( isset($allRights["lock_record"]) ) {
            $allRights["lock_record"] = 0;
        }
        if ( isset($allRights["lock_record_multiform"]) ) {
            $allRights["lock_record_multiform"] = 0;
        }
        if ( isset($allRights["lock_record_customize"]) ) {
            $allRights["lock_record_customize"] = 0;
        }
        if ( isset($allRights["data_access_groups"]) ) {
            $allRights["data_access_groups"] = 0;
        }
        if ( isset($allRights["graphical"]) ) {
            $allRights["graphical"] = 1;
        }
        if ( isset($allRights["reports"]) ) {
            $allRights["reports"] = 1;
        }
        if ( isset($allRights["design"]) ) {
            $allRights["design"] = 0;
        }
        if ( isset($allRights["alerts"]) ) {
            $allRights["alerts"] = 0;
        }
        if ( isset($allRights["dts"]) ) {
            $allRights["dts"] = 0;
        }
        if ( isset($allRights["calendar"]) ) {
            $allRights["calendar"] = 1;
        }
        if ( isset($allRights["record_create"]) ) {
            $allRights["record_create"] = 1;
        }
        if ( isset($allRights["record_rename"]) ) {
            $allRights["record_rename"] = 0;
        }
        if ( isset($allRights["record_delete"]) ) {
            $allRights["record_delete"] = 0;
        }
        if ( isset($allRights["participants"]) ) {
            $allRights["participants"] = 1;
        }
        if ( isset($allRights["data_quality_design"]) ) {
            $allRights["data_quality_design"] = 0;
        }
        if ( isset($allRights["data_quality_execute"]) ) {
            $allRights["data_quality_execute"] = 0;
        }
        if ( isset($allRights["data_quality_resolution"]) ) {
            $allRights["data_quality_resolution"] = 1;
        }
        if ( isset($allRights["api_export"]) ) {
            $allRights["api_export"] = 0;
        }
        if ( isset($allRights["api_import"]) ) {
            $allRights["api_import"] = 0;
        }
        if ( isset($allRights["mobile_app"]) ) {
            $allRights["mobile_app"] = 0;
        }
        if ( isset($allRights["mobile_app_download_data"]) ) {
            $allRights["mobile_app_download_data"] = 0;
        }
        if ( isset($allRights["random_setup"]) ) {
            $allRights["random_setup"] = 0;
        }
        if ( isset($allRights["random_dashboard"]) ) {
            $allRights["random_dashboard"] = 0;
        }
        if ( isset($allRights["random_perform"]) ) {
            $allRights["random_perform"] = 1;
        }
        if ( isset($allRights["realtime_webservice_mapping"]) ) {
            $allRights["realtime_webservice_mapping"] = 0;
        }
        if ( isset($allRights["realtime_webservice_adjudicate"]) ) {
            $allRights["realtime_webservice_adjudicate"] = 0;
        }
        if ( isset($allRights["mycap_participants"]) ) {
            $allRights["mycap_participants"] = 1;
        }
        return $allRights;
    }

    public function getCurrentRights(string $username, $projectId)
    {
        $result = $this->framework->query("SELECT * FROM redcap_user_rights WHERE username = ? AND project_id = ?", [ $username, $projectId ]);
        $rights = $result->fetch_assoc();
        if ( !empty($rights["role_id"]) ) {
            $result2 = $this->framework->query("SELECT * FROM redcap_user_roles WHERE role_id = ?", [ $rights["role_id"] ]);
            $rights  = $result2->fetch_assoc();
        }
        unset($rights["api_token"], $rights["expiration"]);
        return $this->framework->escape($rights);
    }

    private function getAllCurrentRights($projectId)
    {
        $result = $this->framework->query('SELECT r.*,
        data_entry LIKE "%,3]%" data_entry3,
        data_entry LIKE "%,2]%" data_entry2,
        data_entry LIKE "%,1]%" data_entry1,
        data_export_instruments LIKE "%,3]%" data_export3,
        data_export_instruments LIKE "%,2]%" data_export2,
        data_export_instruments LIKE "%,1]%" data_export1
        FROM redcap_user_rights r WHERE project_id = ? AND role_id IS NULL', [ $projectId ]);
        $rights = [];
        while ( $row = $result->fetch_assoc() ) {
            unset($row['data_export_instruments']);
            unset($row['data_entry']);
            unset($row['data_export_tool']);
            unset($row['external_module_config']);
            $rights[$row['username']] = $row;
        }
        $result2 = $this->framework->query('SELECT user.username, role.*,
        role.data_entry LIKE "%,3]%" data_entry3,
        role.data_entry LIKE "%,2]%" data_entry2,
        role.data_entry LIKE "%,1]%" data_entry1,
        role.data_export_instruments LIKE "%,3]%" data_export3,
        role.data_export_instruments LIKE "%,2]%" data_export2,
        role.data_export_instruments LIKE "%,1]%" data_export1
        FROM redcap_user_rights user
        LEFT JOIN redcap_user_roles role
        ON user.role_id = role.role_id
        WHERE user.project_id = ? AND user.role_id IS NOT NULL', [ $projectId ]);
        while ( $row = $result2->fetch_assoc() ) {
            unset($row['data_export_instruments']);
            unset($row['data_entry']);
            unset($row['data_export_tool']);
            unset($row['external_module_config']);
            $rights[$row['username']] = $row;
        }
        return $this->framework->escape($rights);
    }

    public function getUserRightsHolders($projectId)
    {
        try {
            $sql    = 'SELECT rights.username username,
            CONCAT(info.user_firstname, " ", info.user_lastname) fullname,
            info.user_email email
            from redcap_user_rights rights
            left join redcap_user_information info
            on rights.username = info.username
            where project_id = ?
            and user_rights = 1';
            $result = $this->framework->query($sql, [ $projectId ]);
            $users  = [];
            while ( $row = $result->fetch_assoc() ) {
                $users[] = $row;
            }
            return $this->framework->escape($users);
        } catch ( \Throwable $e ) {
            $this->log('Error fetching user rights holders', [ "error" => $e->getMessage() ]);
        }
    }

    public function updateLog($logId, array $params)
    {
        $sql = "UPDATE redcap_external_modules_log_parameters SET value = ? WHERE log_id = ? AND name = ?";
        foreach ( $params as $name => $value ) {
            try {
                $this->framework->query($sql, [ $value, $logId, $name ]);
            } catch ( \Throwable $e ) {
                $this->framework->log("Error updating log parameter", [ "error" => $e->getMessage() ]);
                return false;
            }
        }
        return true;
    }

    public function getProjectsWithNoncompliantUsers(bool $includeExpired = false)
    {
        $enabledSystemwide = $this->framework->getSystemSetting('enabled');
        $prefix            = $this->getModuleDirectoryPrefix();

        if ( $enabledSystemwide ) {
            $allProjectIds = $this->getAllProjectIds();
            $projectIds    = array_filter($allProjectIds, function ($projectId) use ($prefix) {
                return $this->isModuleEnabled($prefix, $projectId);
            });
        } else {
            $projectIds = $this->framework->getProjectsWithModuleEnabled();
        }

        $projects = [];
        foreach ( $projectIds as $projectId ) {
            $discrepantRights = $this->getUsersWithBadRights2($projectId);
            $users            = array_filter($discrepantRights, function ($user) use ($includeExpired) {
                return !empty($user['bad'] && ($includeExpired || !$user['isExpired']));
            });
            if ( sizeof($users) > 0 ) {
                $thisProject = $this->framework->getProject($projectId);
                $projects[]  = [
                    'project_id'            => $projectId,
                    'project_title'         => $thisProject->getTitle(),
                    'users_with_bad_rights' => array_values(array_map(function ($thisUser) {
                        return [
                            'username' => $thisUser['username'],
                            'name'     => $thisUser['name'],
                            'email'    => $thisUser['email'],
                            'sag'      => $thisUser['sag'],
                            'sag_name' => $thisUser['sag_name']
                        ];
                    }, $users)),
                    'bad_rights'            =>
                    array_values(
                        array_unique(
                            array_merge(
                                ...array_map(function ($thisUser) {
                                    return $thisUser['bad'];
                                }, $users)
                            )
                        )
                    ),
                    'sags'                  =>
                    array_values(array_unique(array_map(function ($thisUser) {
                        return [
                            "sag"      => $thisUser['sag'],
                            "sag_name" => $thisUser['sag_name']
                        ];
                    }, $users), SORT_REGULAR))



                ];
            }
        }
        return $projects;
    }

    public function getAllUsersWithNoncompliantRights(bool $includeExpired = false)
    {
        $enabledSystemwide = $this->framework->getSystemSetting('enabled');
        $prefix            = $this->getModuleDirectoryPrefix();

        if ( $enabledSystemwide ) {
            $allProjectIds = $this->getAllProjectIds();
            $projectIds    = array_filter($allProjectIds, function ($projectId) use ($prefix) {
                return $this->isModuleEnabled($prefix, $projectId);
            });
        } else {
            $projectIds = $this->framework->getProjectsWithModuleEnabled();
        }

        $users = [];
        foreach ( $projectIds as $projectId ) {
            $discrepantRights = $this->getUsersWithBadRights2($projectId);
            foreach ( $discrepantRights as $user ) {
                if ( !empty($user['bad']) && ($includeExpired || !$user['isExpired']) ) {
                    $thisProject                            = $this->framework->getProject($projectId);
                    $users[$user['username']]['projects'][] = [
                        'project_id'    => $projectId,
                        'project_title' => $thisProject->getTitle(),
                        'bad_rights'    => $user['bad'],
                    ];
                    $users[$user['username']]['username']   = $user['username'];
                    $users[$user['username']]['name']       = $user['name'];
                    $users[$user['username']]['email']      = $user['email'];
                    $users[$user['username']]['sag']        = $user['sag'];
                    $users[$user['username']]['sag_name']   = $user['sag_name'];
                    $users[$user['username']]['bad_rights'] = array_values(
                        array_unique(
                            array_merge(
                                $users[$user['username']]['bad_rights'] ?? [],
                                $user['bad']
                            )
                            ,
                            SORT_REGULAR
                        )
                    );
                }
            }
        }
        return array_values($users);
    }

    public function getAllUsersAndProjectsWithNoncompliantRights(bool $includeExpired = false)
    {
        $enabledSystemwide = $this->framework->getSystemSetting('enabled');
        $prefix            = $this->getModuleDirectoryPrefix();

        if ( $enabledSystemwide ) {
            $allProjectIds = $this->getAllProjectIds();
            $projectIds    = array_filter($allProjectIds, function ($projectId) use ($prefix) {
                return $this->isModuleEnabled($prefix, $projectId);
            });
        } else {
            $projectIds = $this->framework->getProjectsWithModuleEnabled();
        }

        $allResults = [];
        foreach ( $projectIds as $projectId ) {
            $discrepantRights = $this->getUsersWithBadRights2($projectId);
            foreach ( $discrepantRights as $user ) {
                if ( !empty($user['bad']) && ($includeExpired || !$user['isExpired']) ) {
                    $thisProject  = $this->framework->getProject($projectId);
                    $allResults[] = [
                        "project_id"    => $projectId,
                        "project_title" => $thisProject->getTitle(),
                        "bad_rights"    => $user['bad'],
                        "username"      => $user['username'],
                        "name"          => $user['name'],
                        "email"         => $user['email'],
                        "sag"           => $user['sag'],
                        "sag_name"      => $user['sag_name']
                    ];
                }
            }
        }
        return $allResults;
    }

    public function redcap_module_ajax($action, $payload, $project_id, $record, $instrument, $event_id, $repeat_instance, $survey_hash, $response_id, $survey_queue_hash, $page, $page_full, $user_id, $group_id)
    {
        $ajaxHandler = new AjaxHandler($this, [
            'action'            => $action,
            'payload'           => $payload,
            'project_id'        => $project_id,
            'record'            => $record,
            'instrument'        => $instrument,
            'event_id'          => $event_id,
            'repeat_instance'   => $repeat_instance,
            'survey_hash'       => $survey_hash,
            'response_id'       => $response_id,
            'survey_queue_hash' => $survey_queue_hash,
            'page'              => $page,
            'page_full'         => $page_full,
            'user_id'           => $user_id,
            'group_id'          => $group_id
        ]);
        return $ajaxHandler->handleAjax();
    }
}