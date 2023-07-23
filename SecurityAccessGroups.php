<?php

namespace YaleREDCap\SecurityAccessGroups;

require_once 'classes/AjaxException.php';
require_once 'classes/AjaxHandler.php';
require_once 'classes/Alert.php';
require_once 'classes/Alerts.php';
require_once 'classes/APIHandler.php';
require_once 'classes/CsvSAGImport.php';
require_once 'classes/CsvUserImport.php';
require_once 'classes/RightsChecker.php';
require_once 'classes/Role.php';
require_once 'classes/SAG.php';
require_once 'classes/SAGProject.php';
require_once 'classes/SAGEditForm.php';
require_once 'classes/SAGException.php';
require_once 'classes/TextReplacer.php';
use ExternalModules\AbstractExternalModule;
use ExternalModules\Framework;

/**
 * @property Framework $framework
 * @see Framework
 */
class SecurityAccessGroups extends AbstractExternalModule
{

    public string $defaultSagId = 'sag_Default';
    public string $defaultSagName = 'Default SAG';

    public function __construct()
    {
        parent::__construct();
        $sag = new SAG($this, $this->defaultSagId, $this->defaultSagName);
        if ( !$sag->sagExists() ) {
            $this->setDefaultSag();
        }
    }

    // External Module Framework Hooks
    public function redcap_every_page_before_render()
    {
        // Only run on the pages we're interested in
        if (
            $_SERVER['REQUEST_METHOD'] !== 'POST' ||
            !in_array(PAGE, [
                'UserRights/edit_user.php',
                'UserRights/assign_user.php',
                'UserRights/import_export_users.php',
                'UserRights/import_export_roles.php',
                'api/index.php'
            ], true)
        ) {
            return;
        }

        // API
        if ( PAGE === 'api/index.php' ) {
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
            } else {
                [ $action, $project_id, $user, $original_rights ] = $api->getApiRequestInfo();
                $this->logApi($action, $project_id, $user, $original_rights);
            }
            return;
        }

        try {
            $username = $this->framework->getUser()->getUsername() ?? '';
        } catch ( \Throwable $e ) {
            $this->framework->log('Error', [ 'error' => $e->getMessage() ]);
        }

        // Edit User or Role
        if (
            PAGE === 'UserRights/edit_user.php' &&
            isset($_POST['submit-action']) &&
            in_array($_POST['submit-action'], [ 'edit_role', 'edit_user', 'add_user' ])
        ) {
            $this->framework->log('attempt to edit user or role directly', [ 'page' => PAGE, 'data' => json_encode($_POST), 'user' => $username ]);
            $this->framework->exitAfterHook();
        }

        // Assign User to Role
        elseif ( PAGE === 'UserRights/assign_user.php' ) {
            $this->log('attempt to assign user role directly', [ 'page' => PAGE, 'data' => json_encode($_POST), 'user' => $username ]);
            $this->exitAfterHook();
        }

        // Upload Users via CSV
        elseif ( PAGE === 'UserRights/import_export_users.php' ) {
            $this->log('attempt to upload users directly', [ 'page' => PAGE, 'data' => json_encode($_POST), 'user' => $username ]);
            $this->exitAfterHook();
        }

        // Upload Roles or Mappings via CSV
        elseif ( PAGE === 'UserRights/import_export_roles.php' ) {
            $this->log('attempt to upload roles or role mappings directly', [ 'page' => PAGE, 'data' => json_encode($_POST), 'user' => $username ]);
            $this->exitAfterHook();
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
        $this->framework->log('Module Enabled');
    }

    public function redcap_module_link_check_display($projectId, $link)
    {
        if ( empty($projectId) || $this->isSuperUser() ) {
            return $link;
        }

        return null;
    }

    public function redcap_module_ajax($action, $payload, $projectId, $record, $instrument, $eventId, $repeatInstance, $surveyHash, $responseId, $surveyQueueHash, $page, $pageFull, $userId, $groupId)
    {
        $ajaxHandler = new AjaxHandler($this, [
            'action'            => $action,
            'payload'           => $payload,
            'project_id'        => $projectId,
            'record'            => $record,
            'instrument'        => $instrument,
            'event_id'          => $eventId,
            'repeat_instance'   => $repeatInstance,
            'survey_hash'       => $surveyHash,
            'response_id'       => $responseId,
            'survey_queue_hash' => $surveyQueueHash,
            'page'              => $page,
            'page_full'         => $pageFull,
            'user_id'           => $userId,
            'group_id'          => $groupId
        ]);
        return $ajaxHandler->handleAjax();
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
                    return $this->framework->isModuleEnabled($prefix, $projectId);
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
            $query      = "SELECT project_id FROM redcap_projects
            WHERE created_by IS NOT NULL
            AND completed_time IS NULL
            AND date_deleted IS NULL";
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

    private function logApiUser($projectId, $user, array $originalRights)
    {
        foreach ( $originalRights as $theseOriginalRights ) {
            $username   = $theseOriginalRights['username'];
            $sagUser    = new SAGUser($this, $username);
            $oldRights  = $theseOriginalRights['rights'] ?? [];
            $newUser    = empty($oldRights);
            $newRights  = $sagUser->getCurrentRights($projectId);
            $changes    = json_encode(array_diff_assoc($newRights, $oldRights), JSON_PRETTY_PRINT);
            $changes    = $changes === '[]' ? 'None' : $changes;
            $dataValues = "user = '" . $username . "'\nchanges = " . $changes;
            if ( $newUser ) {
                $event       = 'INSERT';
                $description = 'Add user';
            } else {
                $event       = 'UPDATE';
                $description = 'Edit user';
            }
            $logTable   = $this->framework->getProject($projectId)->getLogTable();
            $sql        = "SELECT log_event_id FROM $logTable WHERE project_id = ? AND user = ? AND page = 'api/index.php' AND object_type = 'redcap_user_rights' AND pk = ? AND event = ? AND TIMESTAMPDIFF(SECOND,ts,NOW()) <= 10 ORDER BY ts DESC";
            $params     = [ $projectId, $user, $username, $event ];
            $result     = $this->framework->query($sql, $params);
            $logEventId = intval($result->fetch_assoc()['log_event_id']);
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
                    '',
                    '',
                    '',
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
            $roleLabel  = $role['role_name'];
            $changes    = json_encode(array_diff_assoc($role, $oldRights), JSON_PRETTY_PRINT);
            $changes    = $changes === '[]' ? 'None' : $changes;
            $dataValues = "role = '" . $roleLabel . "'\nchanges = " . $changes;
            $logTable   = $this->framework->getProject($projectId)->getLogTable();

            if ( $newRole ) {
                $description    = 'Add role';
                $event          = 'INSERT';
                $origDataValues = "role = '" . $roleLabel . "'";
                $objectType     = 'redcap_user_rights';
                $sql            = "SELECT log_event_id FROM $logTable WHERE project_id = ? AND user = ? AND page = 'api/index.php' AND object_type = 'redcap_user_rights' AND pk IS NULL AND event = 'INSERT' AND data_values = ? AND TIMESTAMPDIFF(SECOND,ts,NOW()) <= 10 ORDER BY ts DESC";
                $params         = [ $projectId, $user, $origDataValues ];
            } else {
                $description = 'Edit role';
                $event       = 'update';
                $objectType  = 'redcap_user_roles';
                $sql         = "SELECT log_event_id FROM $logTable WHERE project_id = ? AND user = ? AND page = 'api/index.php' AND object_type = 'redcap_user_roles' AND pk = ? AND event = 'UPDATE' AND TIMESTAMPDIFF(SECOND,ts,NOW()) <= 10 ORDER BY ts DESC";
                $params      = [ $projectId, $user, $role_id ];
            }

            $result     = $this->framework->query($sql, $params);
            $logEventId = intval($result->fetch_assoc()['log_event_id']);
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
                    '',
                    '',
                    '',
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
            $username       = $mapping['username'];
            $uniqueRoleName = $mapping['unique_role_name'];
            $role           = new Role($this, null, $uniqueRoleName);
            $roleLabel      = $role->getRoleName();

            $logTable   = $this->framework->getProject($projectId)->getLogTable();
            $sql        = "SELECT log_event_id FROM $logTable WHERE project_id = ? AND user = ? AND page = 'api/index.php' AND object_type = 'redcap_user_rights' AND pk = ? AND event = 'INSERT' AND TIMESTAMPDIFF(SECOND,ts,NOW()) <= 10 ORDER BY ts DESC";
            $params     = [ $projectId, $user, $username ];
            $result     = $this->framework->query($sql, $params);
            $logEventId = intval($result->fetch_assoc()['log_event_id']);

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
                    '',
                    '',
                    '',
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
            if ( $action === 'user' ) {
                $this->logApiUser($projectId, $user, $originalRights);
            } elseif ( $action === 'userRole' ) {
                $this->logApiUserRole($projectId, $user, $originalRights);
            } elseif ( $action === 'userRoleMapping' ) {
                $this->logApiUserRoleMapping($projectId, $user, $originalRights);
            }

            return $str;
        }, 0, PHP_OUTPUT_HANDLER_FLUSHABLE);
    }

    public function getAllUserInfo($includeSag = false) : ?array
    {
        $sql = 'SELECT username
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
        , allow_create_db';
        if ( $includeSag ) {
            $sql .= ', em.value as sag';
        }
        $sql .= ' FROM redcap_user_information u';
        if ( $includeSag ) {
            $sql .= ' LEFT JOIN redcap_external_module_settings em ON em.key = concat(u.username,\'-sag\')';
        }
        try {
            $result   = $this->framework->query($sql, []);
            $userinfo = [];
            while ( $row = $result->fetch_assoc() ) {
                $userinfo[] = $row;
            }
            return $this->framework->escape($userinfo);
        } catch ( \Throwable $e ) {
            $this->log('Error getting all user info', [ 'error' => $e->getMessage(), 'user' => $this->getUser()->getUsername() ]);
        }
    }

    public function getAllRights()
    {
        $sql    = 'SHOW COLUMNS FROM redcap_user_rights';
        $result = $this->framework->query($sql, []);
        $rights = [];
        while ( $row = $result->fetch_assoc() ) {
            if ( !in_array($row['Field'], [ 'project_id', 'username', 'expiration', 'role_id', 'group_id', 'api_token', 'data_access_group' ], true) ) {
                $rights[$row['Field']] = $this->framework->escape($row['Field']);
            }
        }
        return $rights;
    }

    // E.g., from ["export-form-form1"=>"1", "export-form-form2"=>"1"] to "[form1,1][form2,1]"
    private function convertExportRightsArrayToString($fullRightsArray)
    {
        $result = "";
        foreach ( $fullRightsArray as $key => $value ) {
            if ( substr_compare($key, 'export-form-', 0, strlen('export-form-')) === 0 ) {
                $formName = str_replace('export-form-', '', $key);
                $result .= '[' . $formName . ',' . $value . ']';
            }
        }
        return $result;
    }

    // E.g., from ["form-form1"=>"1", "form-form2"=>"1"] to "[form1,1][form2,1]"
    private function convertDataEntryRightsArrayToString($fullRightsArray)
    {
        $result = "";
        foreach ( $fullRightsArray as $key => $value ) {
            if ( substr_compare($key, 'form-', 0, strlen('form-')) === 0 && substr_compare($key, 'form-editresp-', 0, strlen('form-editresp-')) !== 0 ) {
                $formName = str_replace('form-', '', $key);

                if ( $fullRightsArray['form-editresp-' . $formName] === 'on' ) {
                    $value = '3';
                }

                $result .= '[' . $formName . ',' . $value . ']';
            }
        }
        return $result;
    }

    // E.g., from "[form1,1][form2,1]" to ["export-form-form1"=>"1", "export-form-form2"=>"1"]
    public function convertExportRightsStringToArray($fullRightsString)
    {
        $raw    = \UserRights::convertFormRightsToArray($fullRightsString);
        $result = [];
        foreach ( $raw as $key => $value ) {
            $result['export-form-' . $key] = $value;
        }
        return $result;
    }

    // E.g., from "[form1,1][form2,1]" to ["form-form1"=>"1", "form-form2"=>"1"]
    public function convertDataEntryRightsStringToArray($fullRightsString)
    {
        $raw    = \UserRights::convertFormRightsToArray($fullRightsString);
        $result = [];
        foreach ( $raw as $key => $value ) {
            if ( $value == 3 ) {
                $result['form-' . $key]          = 2;
                $result['form-editresp-' . $key] = 'on';
            } else {
                $result['form-' . $key] = $value;
            }
        }
        return $result;
    }


    public function checkProposedRights(array $acceptableRights, array $requestedRights)
    {
        $rightsChecker = new RightsChecker($this, $requestedRights, $acceptableRights);
        return $rightsChecker->checkRights();
    }

    public function checkProposedRights2(array $acceptableRights, array $requestedRights)
    {
        $rightsChecker = new RightsChecker($this, $requestedRights, $acceptableRights);
        return $rightsChecker->checkRights2();
    }

    public function getModuleDirectoryPrefix()
    {
        return strrev(preg_replace('/^.*v_/', '', strrev($this->framework->getModuleDirectoryName()), 1));
    }

    private function convertDataQualityResolution($rights)
    {
        // 0: no access
        // 1: view only
        // 4: open queries only
        // 2: respond only to opened queries
        // 5: open and respond to queries
        // 3: open, close, and respond to queries
        $value = $rights['data_quality_resolution'];
        if ( $value ) {
            $rights['data_quality_resolution_view']    = intval($value) > 0 ? 1 : 0;
            $rights['data_quality_resolution_open']    = in_array(intval($value), [ 3, 4, 5 ], true) ? 1 : 0;
            $rights['data_quality_resolution_respond'] = in_array(intval($value), [ 2, 3, 5 ], true) ? 1 : 0;
            $rights['data_quality_resolution_close']   = intval($value) === 3 ? 1 : 0;
        }
        return $rights;
    }

    public function convertPermissions(string $permissions)
    {
        $rights = json_decode($permissions, true);
        $rights = $this->convertDataQualityResolution($rights);
        foreach ( $rights as $key => $value ) {
            if ( $value === 'on' ) {
                $rights[$key] = 1;
            }
        }

        return json_encode($rights);
    }

    /**
     * get an array of all existing SAGs as SAG objects
     * @param bool $idAsKey - if true, the array will be keyed by the SAG id
     * @param bool $parsePermissions - if true, the permissions will be parsed into an array
     * @return SAG[]
     */
    public function getAllSags($idAsKey = false, $parsePermissions = false)
    {
        $sql    = 'SELECT sag_id, sag_name, permissions WHERE message = \'sag\' AND (project_id IS NULL OR project_id IS NOT NULL)';
        $result = $this->framework->queryLogs($sql, []);
        $sags   = [];
        while ( $row = $result->fetch_assoc() ) {
            $sag = new SAG($this, $row['sag_id'], $row['sag_name'], $row['permissions']);
            if ( $parsePermissions ) {
                $sag->parsePermissions();
            }
            if ( $idAsKey ) {
                $sags[$row['sag_id']] = $sag;
            } else {
                $sags[] = $sag;
            }
        }
        return $sags;
    }

    public function setDefaultSag()
    {
        $rights                  = $this->getDefaultRights();
        $rights['sag_id']        = $this->defaultSagId;
        $rights['sag_name_edit'] = $this->defaultSagName;
        $rights['dataViewing']   = '3';
        $rights['dataExport']    = '3';
        $sag                     = new SAG($this, $this->defaultSagId, $this->defaultSagName, json_encode($rights));
        $sag->saveSag();
        return $rights;
    }

    public function generateNewSagId()
    {
        $newSagId = 'sag_' . substr(md5(uniqid()), 0, 13);
        $sag      = new SAG($this, $newSagId);

        if ( $sag->sagExists() ) {
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
            'calendar'                       => $lang['app_08'] . ' ' . $lang['rights_357'],
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
            'realtime_webservice_mapping'    => 'CDP/DDP' . ' ' . $lang['ws_19'],
            'realtime_webservice_adjudicate' => 'CDP/DDP' . ' ' . $lang['ws_20'],
            'dts'                            => $lang['rights_132'],
            'mycap_participants'             => $lang['rights_437'],
            'record_create'                  => $lang['rights_99'],
            'record_rename'                  => $lang['rights_100'],
            'record_delete'                  => $lang['rights_101']

        ];
        if ( $allRights === true ) {
            $rights['random_setup']                    = $lang['app_21'] . ' - ' . $lang['rights_142'];
            $rights['random_dashboard']                = $lang['app_21'] . ' - ' . $lang['rights_143'];
            $rights['random_perform']                  = $lang['app_21'] . ' - ' . $lang['rights_144'];
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

    public function getDisplayTextForRight(string $right, string $key = '')
    {
        $rights = $this->getDisplayTextForRights(true);
        return $rights[$right] ?? $rights[$key] ?? $right;
    }

    public function convertRightName($rightName)
    {

        $conversions = [
            'stats_and_charts'           => 'graphical',
            'manage_survey_participants' => 'participants',
            'logging'                    => 'data_logging',
            'data_quality_create'        => 'data_quality_design',
            'lock_records_all_forms'     => 'lock_record_multiform',
            'lock_records'               => 'lock_record',
            'lock_records_customization' => 'lock_record_customize'
        ];

        return $conversions[$rightName] ?? $rightName;
    }

    public function filterPermissions($rawArray)
    {
        $allRights                         = $this->getAllRights();
        $dataEntryString                   = $this->convertDataEntryRightsArrayToString($rawArray);
        $dataExportString                  = $this->convertExportRightsArrayToString($rawArray);
        $result                            = array_intersect_key($rawArray, $allRights);
        $result['data_export_instruments'] = $dataExportString;
        $result['data_entry']              = $dataEntryString;
        return $result;
    }

    public function getDefaultRights()
    {
        $allRights = $this->getAllRights();
        if ( isset($allRights['data_export_tool']) ) {
            $allRights['data_export_tool'] = 2;
        }
        if ( isset($allRights['data_import_tool']) ) {
            $allRights['data_import_tool'] = 0;
        }
        if ( isset($allRights['data_comparison_tool']) ) {
            $allRights['data_comparison_tool'] = 0;
        }
        if ( isset($allRights['data_logging']) ) {
            $allRights['data_logging'] = 0;
        }
        if ( isset($allRights['file_repository']) ) {
            $allRights['file_repository'] = 1;
        }
        if ( isset($allRights['double_data']) ) {
            $allRights['double_data'] = 0;
        }
        if ( isset($allRights['user_rights']) ) {
            $allRights['user_rights'] = 0;
        }
        if ( isset($allRights['lock_record']) ) {
            $allRights['lock_record'] = 0;
        }
        if ( isset($allRights['lock_record_multiform']) ) {
            $allRights['lock_record_multiform'] = 0;
        }
        if ( isset($allRights['lock_record_customize']) ) {
            $allRights['lock_record_customize'] = 0;
        }
        if ( isset($allRights['data_access_groups']) ) {
            $allRights['data_access_groups'] = 0;
        }
        if ( isset($allRights['graphical']) ) {
            $allRights['graphical'] = 1;
        }
        if ( isset($allRights['reports']) ) {
            $allRights['reports'] = 1;
        }
        if ( isset($allRights['design']) ) {
            $allRights['design'] = 0;
        }
        if ( isset($allRights['alerts']) ) {
            $allRights['alerts'] = 0;
        }
        if ( isset($allRights['dts']) ) {
            $allRights['dts'] = 0;
        }
        if ( isset($allRights['calendar']) ) {
            $allRights['calendar'] = 1;
        }
        if ( isset($allRights['record_create']) ) {
            $allRights['record_create'] = 1;
        }
        if ( isset($allRights['record_rename']) ) {
            $allRights['record_rename'] = 0;
        }
        if ( isset($allRights['record_delete']) ) {
            $allRights['record_delete'] = 0;
        }
        if ( isset($allRights['participants']) ) {
            $allRights['participants'] = 1;
        }
        if ( isset($allRights['data_quality_design']) ) {
            $allRights['data_quality_design'] = 0;
        }
        if ( isset($allRights['data_quality_execute']) ) {
            $allRights['data_quality_execute'] = 0;
        }
        if ( isset($allRights['data_quality_resolution']) ) {
            $allRights['data_quality_resolution'] = 1;
        }
        if ( isset($allRights['api_export']) ) {
            $allRights['api_export'] = 0;
        }
        if ( isset($allRights['api_import']) ) {
            $allRights['api_import'] = 0;
        }
        if ( isset($allRights['mobile_app']) ) {
            $allRights['mobile_app'] = 0;
        }
        if ( isset($allRights['mobile_app_download_data']) ) {
            $allRights['mobile_app_download_data'] = 0;
        }
        if ( isset($allRights['random_setup']) ) {
            $allRights['random_setup'] = 0;
        }
        if ( isset($allRights['random_dashboard']) ) {
            $allRights['random_dashboard'] = 0;
        }
        if ( isset($allRights['random_perform']) ) {
            $allRights['random_perform'] = 1;
        }
        if ( isset($allRights['realtime_webservice_mapping']) ) {
            $allRights['realtime_webservice_mapping'] = 0;
        }
        if ( isset($allRights['realtime_webservice_adjudicate']) ) {
            $allRights['realtime_webservice_adjudicate'] = 0;
        }
        if ( isset($allRights['mycap_participants']) ) {
            $allRights['mycap_participants'] = 1;
        }
        return $allRights;
    }

    public function updateLog($logId, array $params)
    {
        $sql = 'UPDATE redcap_external_modules_log_parameters SET value = ? WHERE log_id = ? AND name = ?';
        foreach ( $params as $name => $value ) {
            try {
                $this->framework->query($sql, [ $value, $logId, $name ]);
            } catch ( \Throwable $e ) {
                $this->framework->log('Error updating log parameter', [ 'error' => $e->getMessage() ]);
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
            $sagProject       = new SAGProject($this, $projectId);
            $discrepantRights = $sagProject->getUsersWithBadRights();
            $users            = array_filter($discrepantRights, function ($user) use ($includeExpired) {
                return !empty($user['bad'] && ($includeExpired || !$user['isExpired']));
            });
            if ( sizeof($users) > 0 ) {
                $projects[] = [
                    'project_id'            => $projectId,
                    'project_title'         => $sagProject->getTitle(),
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
                            'sag'      => $thisUser['sag'],
                            'sag_name' => $thisUser['sag_name']
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
            $sagProject       = new SAGProject($this, $projectId);
            $discrepantRights = $sagProject->getUsersWithBadRights();
            foreach ( $discrepantRights as $user ) {
                if ( !empty($user['bad']) && ($includeExpired || !$user['isExpired']) ) {
                    $users[$user['username']]['projects'][] = [
                        'project_id'    => $projectId,
                        'project_title' => $sagProject->getTitle(),
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
            $sagProject       = new SAGProject($this, $projectId);
            $discrepantRights = $sagProject->getUsersWithBadRights();
            foreach ( $discrepantRights as $user ) {
                if ( !empty($user['bad']) && ($includeExpired || !$user['isExpired']) ) {
                    $allResults[] = [
                        'project_id'    => $projectId,
                        'project_title' => $sagProject->getTitle(),
                        'bad_rights'    => $user['bad'],
                        'username'      => $user['username'],
                        'name'          => $user['name'],
                        'email'         => $user['email'],
                        'sag'           => $user['sag'],
                        'sag_name'      => $user['sag_name']
                    ];
                }
            }
        }
        return $allResults;
    }
}