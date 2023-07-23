<?php

namespace YaleREDCap\SecurityAccessGroups;


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
            $expiration            = $user['expiration'];
            $isExpired             = $expiration != '' && strtotime($expiration) < strtotime('today');
            $username              = $user['username'];
            $sag                   = $sags[$user['sag']] ?? $sags[$this->module->defaultSagId];
            $acceptableRights      = $sag->permissions;
            $currentRights         = $this->module->getCurrentRightsFormatted($username, $this->projectId);
            $bad                   = $this->module->checkProposedRights($acceptableRights, $currentRights);
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
                'bad'               => $bad
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
            $bad                   = $this->module->checkProposedRights2($acceptableRights, $currentRights);
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
                'bad'               => $bad
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

}