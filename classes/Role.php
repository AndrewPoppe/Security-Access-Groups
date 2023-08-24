<?php

namespace YaleREDCap\SecurityAccessGroups;

class Role
{
    private $roleId;
    private $roleName;
    private $uniqueRoleName;
    private SecurityAccessGroups $module;

    public function __construct(SecurityAccessGroups $module, $roleId = null, $uniqueRoleName = null, $roleName = null)
    {
        $this->module = $module;
        $uniqueRoleName = trim($uniqueRoleName ?? '');
        if ( $roleId === "0" && empty($roleName) ) {
            throw new SAGException('Must provide a role name if this is a newly created role');
        } elseif ( empty($roleId) && $roleId !== "0" && empty($uniqueRoleName) ) {
            throw new SAGException('Must provide either a role ID or a unique role name');
        }
        $roleId               = $roleId === "0" ? $this->getNewRoleIdFromRoleName($roleName) : $roleId;
        $this->roleId         = $roleId ?? $this->getRoleIdFromUniqueRoleName($uniqueRoleName);
        $this->uniqueRoleName = $uniqueRoleName ?? $this->getUniqueRoleNameFromRoleId($this->roleId);
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
        $projectId       = $pid ?? $this->module->framework->getProjectId();
        $roles           = \UserRights::getRoles($projectId);
        $rightsUtilities = new RightsUtilities($this->module);
        $allRights       = $rightsUtilities->getAllRights();
        $thisRole        = $roles[$this->roleId];
        return array_filter($thisRole, function ($value, $key) use ($allRights) {
            $off          = $value === '0';
            $null         = is_null($value);
            $unset        = isset($value) && is_null($value);
            $excluded     = in_array($key, [ 'role_name', 'unique_role_name', 'project_id', 'data_entry', 'data_export_instruments' ], true);
            $alsoExcluded = !in_array($key, $allRights, true);
            return !$off && !$unset && !$excluded && !$alsoExcluded && !$null;
        }, ARRAY_FILTER_USE_BOTH);
    }

    public function getRoleRightsRaw()
    {
        $sql                       = 'SELECT * FROM redcap_user_roles WHERE role_id = ?';
        $result                    = $this->module->framework->query($sql, [ $this->roleId ]);
        $row                       = $result->fetch_assoc();
        $roleName                  = \REDCap::filterHtml($row['role_name']);
        $safeRoleData              = $this->module->framework->escape($row);
        $safeRoleData['role_name'] = $roleName;
        return $safeRoleData;
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
        return \REDCap::filterHtml($row['role_name']);
    }

    private function getNewRoleIdFromRoleName(string $roleName)
    {
        $sql    = 'SELECT max(role_id) as role_id FROM redcap_user_roles WHERE project_id = ? AND role_name = ?';
        $params = [ $this->module->framework->getProjectId(), $roleName ];
        $result = $this->module->framework->query($sql, $params);
        return \REDCap::filterHtml($result->fetch_assoc()['role_id']);
    }
}