<?php

namespace YaleREDCap\SecurityAccessGroups;

use ExternalModules\Framework;


class SAGProject
{
    private SecurityAccessGroups $module;
    public string $projectId;
    public string $projectName;


    public function __construct(SecurityAccessGroups $module, string $projectId = '')
    {
        $this->module    = $module;
        $this->projectId = $projectId;
    }

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
                'expiration'        => $expiration == '' ? 'never' : $expiration,
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

    public function getUsersWithBadRights()
    {
        $users            = $this->getBasicProjectUsers();
        $sags             = $this->module->getAllSags(true, true);
        $allCurrentRights = $this->getAllCurrentRights();
        $badRights        = [];
        foreach ( $users as $user ) {
            $expiration            = $user['expiration'];
            $isExpired             = $expiration != '' && strtotime($expiration) < strtotime('today');
            $username              = $user['username'];
            $sag                   = $sags[$user['sag']] ?? $sags[$this->module->defaultSagId];
            $acceptableRights      = $sag->permissions;
            $currentRights         = $allCurrentRights[$username];
            $rightsChecker         = new RightsChecker($this->module, $currentRights, $acceptableRights, $this->projectId);
            $bad                   = $rightsChecker->checkRights2();
            $sagName               = $sag->sagName;
            $projectRoleUniqueName = $user['unique_role_name'];
            $projectRoleName       = $user['role_name'];
            $badRights[]           = [
                'username'          => $username,
                'name'              => $user['user_firstname'] . ' ' . $user['user_lastname'],
                'email'             => $user['user_email'],
                'expiration'        => $expiration == '' ? 'never' : $expiration,
                'isExpired'         => $isExpired,
                'sag'               => $sag->sagId,
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

    public function getTitle()
    {
        return $this->module->getProject($this->projectId)->getTitle();
    }

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

    public function areSurveysEnabled() : bool
    {
        $systemSql    = 'SELECT value FROM redcap_config WHERE field_name = "enable_projecttype_singlesurveyforms"';
        $systemResult = $this->module->framework->query($systemSql, []);
        // If surveys are disabled at the system level, it doesn't matter what the project setting is
        if ( $systemResult->fetch_assoc()['value'] == 0 ) {
            return false;
        }
        $projectSql    = 'SELECT surveys_enabled FROM redcap_projects WHERE project_id = ?';
        $projectResult = $this->module->framework->query($projectSql, [ $this->projectId ]);
        return $projectResult->fetch_assoc()['surveys_enabled'] == 1;
    }

    public function isCDPorDDPEnabled() : bool
    {
        $systemSql     = 'SELECT value FROM redcap_config WHERE field_name IN ("realtime_webservice_global_enabled", "fhir_ddp_enabled")';
        $systemResult  = $this->module->framework->query($systemSql, []);
        $enabledGlobal = false;
        while ( $row = $systemResult->fetch_assoc() ) {
            if ( $row['value'] == 1 ) {
                $enabledGlobal = true;
            }
        }
        // If surveys are disabled at the system level, it doesn't matter what the project setting is
        if ( !$enabledGlobal ) {
            return false;
        }
        $projectSql    = 'SELECT realtime_webservice_enabled FROM redcap_projects WHERE project_id = ?';
        $projectResult = $this->module->framework->query($projectSql, [ $this->projectId ]);
        return $projectResult->fetch_assoc()['realtime_webservice_enabled'] == 1;
    }

    public function isDataResolutionWorkflowEnabled() : bool
    {
        $sql    = 'SELECT data_resolution_enabled FROM redcap_projects WHERE project_id = ?';
        $result = $this->module->framework->query($sql, [ $this->projectId ]);
        return $result->fetch_assoc()['data_resolution_enabled'] == 2; // 2 = DRW, 1 = Field Comment Log
    }

    public function isDoubleDataEnabled() : bool
    {
        $sql    = 'SELECT double_data_entry FROM redcap_projects WHERE project_id = ?';
        $result = $this->module->framework->query($sql, [ $this->projectId ]);
        return $result->fetch_assoc()['double_data_entry'];
    }

    public function isMyCapEnabled() : bool
    {
        $systemSql    = 'SELECT value FROM redcap_config WHERE field_name = "mycap_enabled_global"';
        $systemResult = $this->module->framework->query($systemSql, []);
        // If surveys are disabled at the system level, it doesn't matter what the project setting is
        if ( $systemResult->fetch_assoc()['value'] == 0 ) {
            return false;
        }
        $projectSql    = 'SELECT mycap_enabled FROM redcap_projects WHERE project_id = ?';
        $projectResult = $this->module->framework->query($projectSql, [ $this->projectId ]);
        return $projectResult->fetch_assoc()['mycap_enabled'] == 1;
    }

    public function isRandomizationEnabled() : bool
    {
        $systemSql    = 'SELECT value FROM redcap_config WHERE field_name = "randomization_global"';
        $systemResult = $this->module->framework->query($systemSql, []);
        // If surveys are disabled at the system level, it doesn't matter what the project setting is
        if ( $systemResult->fetch_assoc()['value'] == 0 ) {
            return false;
        }
        $projectSql    = 'SELECT randomization FROM redcap_projects WHERE project_id = ?';
        $projectResult = $this->module->framework->query($projectSql, [ $this->projectId ]);
        return $projectResult->fetch_assoc()['randomization'] == 1;
    }

    public function isStatsAndChartsEnabled() : bool
    {
        $sql          = 'SELECT value FROM redcap_config WHERE field_name = "enable_plotting"';
        $systemResult = $this->module->framework->query($sql, []);
        return $systemResult->fetch_assoc()['value'] == 2; // 2 = enabled... yes, really
    }
}