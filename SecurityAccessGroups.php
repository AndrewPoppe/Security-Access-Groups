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
require_once 'classes/RightsUtilities.php';
require_once 'classes/Role.php';
require_once 'classes/SAG.php';
require_once 'classes/SAGProject.php';
require_once 'classes/SAGEditForm.php';
require_once 'classes/SAGException.php';
require_once 'classes/SAGUser.php';
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

    /**
     * REDCap Hook: Used here to prevent attempts to circumvent the module to add/edit user rights
     *
     * Be careful with this method, since it runs on every REDCap page.
     * @return void
     */
    public function redcap_every_page_before_render() : void
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
                $api->logApi();
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

    /**
     * REDCap Hook: Replace links in the User Rights page to point to this module instead
     * @param mixed $projectId The project ID
     * @return void
     */
    public function redcap_user_rights($projectId) : void
    {
        $this->framework->initializeJavascriptModuleObject();
        $this->framework->tt_transferToJavascriptModuleObject(); // If this slows things down, we can send just the keys we need
        if ( isset($_SESSION['SAG_imported']) ) {
            echo "<script>window.import_type = '" . $_SESSION['SAG_imported'] . "';" .
                "window.import_errors = JSON.parse('" . $_SESSION['SAG_bad_rights'] . "');</script>";
            unset($_SESSION['SAG_imported']);
            unset($_SESSION['SAG_bad_rights']);
        }
        $js = file_get_contents($this->framework->getSafePath('js/redcap_user_rights.js'));
        $js = str_replace('__MODULE__', $this->framework->getJavascriptModuleObjectName(), $js);
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
        $this->framework->log('Module Enabled', [
            'version' => $version,
            'user'    => $this->framework->getUser()->getUsername()
        ]);
    }

    public function redcap_module_project_disable($version, $projectId)
    {
        $this->framework->log('Module Disabled', [
            'version' => $version,
            'user'    => $this->framework->getUser()->getUsername()
        ]);
    }

    /**
     * REDCap Hook: Only show sidebar link if it's in the Control Center or if the user is a super user
     * @param mixed $projectId
     * @param mixed $link
     * @return mixed
     */
    public function redcap_module_link_check_display($projectId, $link) : ?array
    {
        if ( empty($projectId) || $this->isSuperUser() ) {
            return $link;
        }

        return null;
    }

    /**
     * REDCap Hook: Handle AJAX requests from the JS module object
     * @param mixed $action
     * @param mixed $payload
     * @param mixed $projectId
     * @param mixed $record
     * @param mixed $instrument
     * @param mixed $eventId
     * @param mixed $repeatInstance
     * @param mixed $surveyHash
     * @param mixed $responseId
     * @param mixed $surveyQueueHash
     * @param mixed $page
     * @param mixed $pageFull
     * @param mixed $userId
     * @param mixed $groupId
     * @return mixed
     */
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

    /**
     * Checks for alert reminders and sends them if necessary.
     * @param array $cronInfo
     * @return string
     */
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

    /**
     * Get the prefix for the module directory prefix (minus the v_version#)
     * @return string module prefix (minus the v_version#)
     */
    public function getModuleDirectoryPrefix() : string
    {
        return strrev(preg_replace('/^.*v_/', '', strrev($this->framework->getModuleDirectoryName()), 1));
    }


    //  PROJECT UTILITIES

    /**
     * Gets all project ids in the system, except deleted, completed, and system projects
     * @return int[] | null
     */
    public function getAllProjectIds() : ?array
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



    // USER UTILITIES

    /**
     * Get useful user information for all users
     * @param bool $includeSag
     * @return array[]|null
     */
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


    // SAG UTILITIES

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

    /**
     * Saves a default SAG to the database
     * @return array
     */
    public function setDefaultSag()
    {
        $rightsUtilities         = new RightsUtilities($this);
        $rights                  = $rightsUtilities->getDefaultRights();
        $rights['sag_id']        = $this->defaultSagId;
        $rights['sag_name_edit'] = $this->defaultSagName;
        $rights['dataViewing']   = '3';
        $rights['dataExport']    = '3';
        $sag                     = new SAG($this, $this->defaultSagId, $this->defaultSagName, json_encode($rights));
        $sag->saveSag();
        return $rights;
    }

    /**
     * Creates a new SAG id
     * @return string
     */
    public function generateNewSagId() : string
    {
        $newSagId = 'sag_' . substr(md5(uniqid()), 0, 13);
        $sag      = new SAG($this, $newSagId);

        if ( $sag->sagExists() ) {
            return $this->generateNewSagId();
        } else {
            return $newSagId;
        }
    }


    // REPORT UTILITIES

    /**
     * Get a report of all projects that have users with noncompliant rights
     *
     * @param bool $includeExpired if true, expired users will be included in the report
     * @return array<array>
     */
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

    /**
     * Get a report of all users with noncompliant rights
     * @param bool $includeExpired
     * @return array<array>
     */
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