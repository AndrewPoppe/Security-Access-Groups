<?php

namespace YaleREDCap\SecurityAccessGroups;

class Role
{
    private $roleId;
    private $roleName;
    private $uniqueRoleName;
    private SecurityAccessGroups $module;

    public function __construct(SecurityAccessGroups $module, $roleId = null, $uniqueRoleName = null)
    {
        if ( empty($roleId) && empty($uniqueRoleName) ) {
            throw new SAGException('Must provide either a role ID or a unique role name');
        }
        $this->module         = $module;
        $this->roleId         = $roleId ?? $this->getRoleIdFromUniqueRoleName($uniqueRoleName);
        $this->uniqueRoleName = $uniqueRoleName ?? $this->getUniqueRoleNameFromRoleId($roleId);
        $this->roleName       = $this->getRoleNameFromRoleId($this->roleId);
    }

    public function getRoleId() : string
    {
        return $this->roleId ?? '';
    }

    public function getRoleName() : string
    {
        return $this->roleName ?? '';
    }

    public function getUniqueRoleName() : string
    {
        return $this->uniqueRoleName ?? '';
    }

    public function getRoleRights($pid = null)
    {
        $projectId = $pid ?? $this->module->framework->getProjectId();
        $roles     = \UserRights::getRoles($projectId);
        $thisRole  = $roles[$this->roleId];
        return array_filter($thisRole, function ($value, $key) {
            $off          = $value === '0';
            $null         = is_null($value);
            $unset        = isset($value) && is_null($value);
            $excluded     = in_array($key, [ 'role_name', 'unique_role_name', 'project_id', 'data_entry', 'data_export_instruments' ], true);
            $alsoExcluded = !in_array($key, $this->module->getAllRights(), true);
            return !$off && !$unset && !$excluded && !$alsoExcluded && !$null;
        }, ARRAY_FILTER_USE_BOTH);
    }

    public function getRoleRightsRaw()
    {
        $sql    = 'SELECT * FROM redcap_user_roles WHERE role_id = ?';
        $result = $this->module->framework->query($sql, [ $this->roleId ]);
        return $this->module->framework->escape($result->fetch_assoc());
    }

    public function getUsersInRole($projectId)
    {
        $sql    = 'SELECT * FROM redcap_user_rights WHERE project_id = ? AND role_id = ?';
        $result = $this->module->framework->query($sql, [ $projectId, $this->roleId ]);
        $users  = [];
        while ( $row = $result->fetch_assoc() ) {
            $users[] = $row['username'];
        }
        return $this->module->framework->escape($users);
    }

    private function getRoleIdFromUniqueRoleName(string $uniqueRoleName)
    {
        $sql    = 'SELECT role_id FROM redcap_user_roles WHERE unique_role_name = ?';
        $result = $this->module->framework->query($sql, [ $uniqueRoleName ]);
        $row    = $result->fetch_assoc();
        return $this->module->framework->escape($row['role_id']);
    }

    private function getUniqueRoleNameFromRoleId(string $roleId)
    {
        $sql    = 'SELECT unique_role_name FROM redcap_user_roles WHERE role_id = ?';
        $result = $this->module->framework->query($sql, [ $roleId ]);
        $row    = $result->fetch_assoc();
        return $this->module->framework->escape($row['unique_role_name']);
    }

    private function getRoleNameFromRoleId(string $roleId)
    {
        $sql    = 'SELECT role_name FROM redcap_user_roles WHERE role_id = ?';
        $result = $this->module->framework->query($sql, [ $roleId ]);
        $row    = $result->fetch_assoc();
        return $this->module->framework->escape($row['role_name']);
    }

}