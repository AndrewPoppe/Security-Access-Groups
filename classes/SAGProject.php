<?php

namespace YaleREDCap\SecurityAccessGroups;

use ExternalModules\Framework;


/**
 * A class to represent a REDCap project that has the Security Access Groups module enabled
 */
class SAGProject
{
    /**
     * Instance of main EM class
     * @var SecurityAccessGroups
     */
    private SecurityAccessGroups $module;
    /**
     * REDCap project ID
     * @var string
     */
    public string $projectId;
    /**
     * REDCap title of project
     * @var string
     */
    public string $projectName;
    /**
     * Various project-level settings
     * @var array
     */
    public array $projectConfig;
    /**
     * Current system configuration
     * @var array
     */
    public array $systemConfig;

    private bool $getConfig;


    /**
     * Summary of __construct
     * @param \YaleREDCap\SecurityAccessGroups\SecurityAccessGroups $module
     * @param string $projectId
     * @param bool $getConfig
     */
    public function __construct(SecurityAccessGroups $module, string $projectId = '', bool $getConfig = false)
    {
        $this->module    = $module;
        $this->projectId = $projectId;
        $this->getConfig = $getConfig;

        if ( $getConfig ) {
            $this->projectConfig = $this->getProjectConfig();
            $this->systemConfig  = $this->getSystemConfig();
        }
    }

    /**
     * Return status of project
     *
     * Status can be:
     *     "DEV" (development mode)
     *     "PROD" (production mode)
     *     "AC" (analysis/cleanup mode)
     *     "DONE" (completed)
     *     In case the project does not exist, NULL is returned.
     *
     * @return array
     */
    public function getProjectStatus()
    {
        global $lang;
        $labels = [
            'DEV'  => $lang['global_29'],
            'PROD' => $lang['global_30'],
            'AC'   => $lang['global_159'],
            'DONE' => $lang['edit_project_207']
        ];
        $status = $this->module->framework->getProjectStatus($this->projectId);
        if ( $status === null ) {
            $result = [ 'status' => null, 'label' => '' ];
        } else {
            $result = [
                'status' => $status,
                'label'  => $labels[$status]
            ];
        }
        return $this->module->framework->escape($result);
    }

    /**
     * Get array of project configuration values from redcap_projects table
     * @return array
     */
    private function getProjectConfig() : array
    {
        $sql    = 'SELECT * FROM redcap_projects WHERE project_id = ?';
        $result = $this->module->framework->query($sql, [ $this->projectId ]);
        return $result->fetch_assoc();
    }

    /**
     * Get array of system configuration values from redcap_config table
     * @return array
     */
    private function getSystemConfig() : array
    {
        $sql    = 'SELECT * FROM redcap_config';
        $result = $this->module->framework->query($sql, []);
        $config = [];
        while ( $row = $result->fetch_assoc() ) {
            $config[$row['field_name']] = $row['value'];
        }
        return $config;
    }

    /**
     * Summary of getBasicProjectUsers
     * @return array
     */
    public function getBasicProjectUsers()
    {
        $sql = 'SELECT rights.username,
        info.user_firstname,
        info.user_lastname,
        user_email,
        expiration,
        rights.role_id,
        roles.unique_role_name,
        roles.role_name,
        em.value AS sag
        FROM redcap_user_rights rights
        LEFT JOIN redcap_user_roles roles
        ON rights.role_id = roles.role_id
        LEFT JOIN redcap_user_information info
        ON rights.username = info.username
        LEFT JOIN redcap_external_module_settings em ON em.key = CONCAT(rights.username,\'-sag\')
        WHERE rights.project_id = ?';
        try {
            $result = $this->module->framework->query($sql, [ $this->projectId ]);
            $users  = [];
            while ( $row = $result->fetch_assoc() ) {
                $users[] = $this->module->framework->escape($row);
            }
            return $users;
        } catch ( \Throwable $e ) {
            $this->module->framework->log('Error getting project users', [ 'error' => $e->getMessage() ]);
            return [];
        }
    }

    /**
     * Summary of getUsersWithBadRightsOld
     * @return array<array>
     */
    public function getUsersWithBadRightsOld()
    {
        $users     = $this->getBasicProjectUsers();
        $sags      = $this->module->getAllSags(true, true);
        $badRights = [];
        foreach ( $users as $user ) {
            $sagUser               = new SAGUser($this->module, $user['username']);
            $expiration            = $user['expiration'];
            $isExpired             = $expiration != '' && strtotime($expiration) < strtotime('today');
            $username              = $user['username'];
            $sag                   = $sags[$user['sag']] ?? $sags[$this->module->defaultSagId];
            $acceptableRights      = $sag->permissions;
            $currentRights         = $sagUser->getCurrentRightsFormatted($this->projectId);
            $rightsChecker         = new RightsChecker($this->module, $currentRights, $acceptableRights, $this->projectId);
            $bad                   = $rightsChecker->checkRights();
            $sagName               = $sag->sagName;
            $projectRoleUniqueName = $user['unique_role_name'];
            $projectRoleName       = $user['role_name'];
            $badRights[]           = [
                'username'          => $username,
                'name'              => $user['user_firstname'] . ' ' . $user['user_lastname'],
                'email'             => $user['user_email'],
                'expiration'        => $expiration == '' ? $this->module->framework->tt('status_ui_76') : $expiration,
                'isExpired'         => $isExpired,
                'sag'               => $user['sag'],
                'sag_name'          => $sagName,
                'project_role'      => $projectRoleUniqueName,
                'project_role_name' => $projectRoleName,
                'acceptable'        => $acceptableRights,
                'current'           => $currentRights,
                'bad'               => $this->module->framework->escape($bad)
            ];
        }
        return $badRights;
    }

    /**
     * Summary of getUsersWithBadRights
     * @return array<array>
     */
    public function getUsersWithBadRights()
    {
        $users            = $this->getBasicProjectUsers();
        $sags             = $this->module->getAllSags(true, true);
        $allCurrentRights = $this->getAllCurrentRights();
        $badRights        = [];
        if ( !$this->getConfig ) {
            $this->projectConfig = $this->getProjectConfig();
            $this->systemConfig  = $this->getSystemConfig();
        }
        foreach ( $users as $user ) {
            $expiration            = $user['expiration'];
            $isExpired             = $expiration != '' && strtotime($expiration) < strtotime('today');
            $username              = $user['username'];
            $sag                   = $sags[$user['sag']] ?? $sags[$this->module->defaultSagId];
            $acceptableRights      = $sag->permissions;
            $currentRights         = $allCurrentRights[$username];
            $rightsChecker         = new RightsChecker($this->module, $currentRights, $acceptableRights, null, false, $this);
            $bad                   = $rightsChecker->checkRights2();
            $sagName               = $sag->sagName;
            $projectRoleUniqueName = $user['unique_role_name'];
            $projectRoleName       = $user['role_name'];
            $badRights[]           = [
                'username'          => $username,
                'name'              => $user['user_firstname'] . ' ' . $user['user_lastname'],
                'email'             => $user['user_email'],
                'expiration'        => $expiration == '' ? $this->module->framework->tt('status_ui_76') : $expiration,
                'isExpired'         => $isExpired,
                'sag'               => $sag->sagId,
                'sag_name'          => $sagName,
                'project_role'      => $projectRoleUniqueName,
                'project_role_name' => $projectRoleName,
                'acceptable'        => $acceptableRights,
                'current'           => $currentRights,
                'bad'               => $bad //$this->module->framework->escape($bad)
            ];
        }
        return $badRights;
    }

    /**
     * Summary of getTitle
     * @return mixed
     */
    public function getTitle()
    {
        return $this->module->getProject($this->projectId)->getTitle();
    }

    /**
     * Summary of getUserRightsHolders
     * @return mixed
     */
    public function getUserRightsHolders()
    {
        try {
            $sql    = 'SELECT rights.username username,
            CONCAT(info.user_firstname, " ", info.user_lastname) fullname,
            info.user_email email
            FROM redcap_user_rights rights
            LEFT JOIN redcap_user_information info
            ON rights.username = info.username
            WHERE project_id = ?
            AND user_rights = 1';
            $result = $this->module->framework->query($sql, [ $this->projectId ]);
            $users  = [];
            while ( $row = $result->fetch_assoc() ) {
                $users[] = $row;
            }
            return $this->module->framework->escape($users);
        } catch ( \Throwable $e ) {
            $this->module->framework->log('Error fetching user rights holders', [ 'error' => $e->getMessage() ]);
        }
    }

    /**
     * Summary of getAllCurrentRights
     * @return mixed
     */
    private function getAllCurrentRights()
    {
        $result = $this->module->framework->query('SELECT r.*,
        data_entry LIKE "%,3]%" data_entry3,
        data_entry LIKE "%,2]%" data_entry2,
        data_entry LIKE "%,1]%" data_entry1,
        data_export_instruments LIKE "%,3]%" data_export3,
        data_export_instruments LIKE "%,2]%" data_export2,
        data_export_instruments LIKE "%,1]%" data_export1
        FROM redcap_user_rights r WHERE project_id = ? AND role_id IS NULL', [ $this->projectId ]);
        $rights = [];
        while ( $row = $result->fetch_assoc() ) {
            unset($row['data_export_instruments']);
            unset($row['data_entry']);
            unset($row['data_export_tool']);
            unset($row['external_module_config']);
            $rights[$row['username']] = $row;
        }
        $result2 = $this->module->framework->query('SELECT user.username, role.*,
        role.data_entry LIKE "%,3]%" data_entry3,
        role.data_entry LIKE "%,2]%" data_entry2,
        role.data_entry LIKE "%,1]%" data_entry1,
        role.data_export_instruments LIKE "%,3]%" data_export3,
        role.data_export_instruments LIKE "%,2]%" data_export2,
        role.data_export_instruments LIKE "%,1]%" data_export1
        FROM redcap_user_rights user
        LEFT JOIN redcap_user_roles role
        ON user.role_id = role.role_id
        WHERE user.project_id = ? AND user.role_id IS NOT NULL', [ $this->projectId ]);
        while ( $row = $result2->fetch_assoc() ) {
            unset($row['data_export_instruments']);
            unset($row['data_entry']);
            unset($row['data_export_tool']);
            unset($row['external_module_config']);
            $rights[$row['username']] = $row;
        }
        return $this->module->framework->escape($rights);
    }

    /**
     * Summary of areSurveysEnabled
     * @return bool
     */
    public function areSurveysEnabled() : bool
    {

        if ( $this->systemConfig['enable_projecttype_singlesurveyforms'] == 0 ) {
            return false;
        }
        return $this->projectConfig['surveys_enabled'] == 1;
    }

    /**
     * Summary of isCDPorDDPEnabled
     * @return bool
     */
    public function isCDPorDDPEnabled() : bool
    {
        if ( $this->systemConfig['realtime_webservice_global_enabled'] == 0 && $this->systemConfig['fhir_ddp_enabled'] == 0 ) {
            return false;
        }

        return $this->projectConfig['realtime_webservice_enabled'] == 1;
    }

    /**
     * Summary of isDataResolutionWorkflowEnabled
     * @return bool
     */
    public function isDataResolutionWorkflowEnabled() : bool
    {
        return $this->projectConfig['data_resolution_enabled'] == 2; // 2 = DRW, 1 = Field Comment Log
    }

    /**
     * Summary of isDoubleDataEnabled
     * @return bool
     */
    public function isDoubleDataEnabled() : bool
    {
        return $this->projectConfig['double_data_entry'] == 1;
    }

    /**
     * Summary of isMyCapEnabled
     * @return bool
     */
    public function isMyCapEnabled() : bool
    {
        if ( $this->systemConfig['mycap_enabled_global'] == 0 ) {
            return false;
        }
        return $this->projectConfig['mycap_enabled'] == 1;
    }

    /**
     * Summary of isRandomizationEnabled
     * @return bool
     */
    public function isRandomizationEnabled() : bool
    {
        if ( $this->systemConfig['randomization_global'] == 0 ) {
            return false;
        }
        return $this->projectConfig['randomization'] == 1;
    }

    /**
     * Summary of isStatsAndChartsEnabled
     * @return bool
     */
    public function isStatsAndChartsEnabled() : bool
    {
        return $this->systemConfig['enable plotting'] == 2; // 2 = enabled... yes, really
    }
}