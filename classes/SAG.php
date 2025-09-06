<?php

namespace YaleREDCap\SecurityAccessGroups;


class SAG
{
    private SecurityAccessGroups $module;
    public string $sagId;
    public string $sagName;
    public string $permissionsJson;

    public ?array $permissions;

    public function __construct(SecurityAccessGroups $module, string $sagId = '', string $sagName = '', string $permissionsJson = '')
    {
        $this->module          = $module;
        $this->sagId           = $sagId;
        $this->permissionsJson = $permissionsJson;
        if ($sagName === '') {
            $sagName = $this->getSagNameFromSagId();
        }
        $this->sagName         = $this->module->framework->escape($sagName);
    }

    public function setSagId(string $sagId)
    {
        $this->sagId = $sagId;
    }

    public function throttleSaveSag(string $permissions, string $sagName = '')
    {
        if (
            !$this->module->framework->throttle(
                '(project_id IS NULL OR project_id IS NOT NULL) AND message = "Saved SAG"',
                [],
                2,
                1
            )
        ) {
            $this->saveSag($permissions, $sagName);
        } else {
            $this->module->framework->log('saveSag Throttled', [
                'sag_id'   => $this->sagId,
                'sag_name' => $sagName,
                'user'     => $this->module->framework->getUser()->getUsername()
            ]);
        }
    }


    public function saveSag(string $rightsJson = '', string $sagName = '')
    {
        $sagName    = empty($sagName) ? $this->sagName : $sagName;
        $rightsJson = empty($rightsJson) ? $this->permissionsJson : $rightsJson;
        $user       = $this->module->framework->getUser()->getUsername();
        try {
            $permissionsConverted = RightsUtilities::convertPermissions($rightsJson);
            $this->module->framework->log('sag', [
                'sag_id'      => $this->sagId,
                'sag_name'    => $sagName,
                'permissions' => $permissionsConverted,
                'user'        => $user
            ]);
            $this->module->framework->log('Saved SAG', [
                'sag_id'      => $this->sagId,
                'sag_name'    => $sagName,
                'permissions' => $permissionsConverted
            ]);
        } catch ( \Throwable $e ) {
            $this->module->framework->log('Error saving SAG', [
                'error'       => $e->getMessage(),
                'sag_id'      => $this->sagId,
                'sag_name'    => $sagName,
                'permissions' => $permissionsConverted,
                'user'        => $user
            ]);
        }
    }

    public function throttleUpdateSag(string $permissions, string $sagName = '')
    {
        if (
            !$this->module->framework->throttle(
                '(project_id IS NULL OR project_id IS NOT NULL) AND message = "Updated SAG"',
                [],
                2,
                1
            )
        ) {
            $this->updateSag($permissions, $sagName);
        } else {
            $this->module->framework->log('updateSag Throttled', [
                'sag_id'   => $this->sagId,
                'sag_name' => $sagName,
                'user'     => $this->module->framework->getUser()->getUsername()
            ]);
        }
    }

    public function updateSag(string $permissions, string $sagName = '')
    {
        $sagName = empty($sagName) ? $this->sagName : $sagName;
        $user    = $this->module->framework->getUser()->getUsername();
        try {
            $permissionsConverted = RightsUtilities::convertPermissions($permissions);
            $sql1                 = "SELECT log_id WHERE message = 'sag' AND sag_id = ? AND project_id IS NULL";
            $result1              = $this->module->framework->queryLogs($sql1, [ $this->sagId ]);
            $logId                = intval($result1->fetch_assoc()["log_id"]);
            if ( $logId === 0 ) {
                throw new \Error('No SAG found with the specified id');
            }
            $params = [ 'sag_name' => $sagName, 'permissions' => $permissionsConverted ];
            foreach ( $params as $name => $value ) {
                $sql = 'UPDATE redcap_external_modules_log_parameters SET value = ? WHERE log_id = ? AND name = ?';
                $this->module->framework->query($sql, [ $value, $logId, $name ]);
            }
            $this->module->framework->log('Updated SAG', [
                'sag_id'      => $this->sagId,
                'sag_name'    => $sagName,
                'permissions' => $permissionsConverted,
                'user'        => $user
            ]);
        } catch ( \Throwable $e ) {
            $this->module->framework->log('Error updating SAG', [
                'error'                 => $e->getMessage(),
                'sag_id'                => $this->sagId,
                'sag_name'              => $sagName,
                'permissions_orig'      => $permissions,
                'permissions_converted' => $permissionsConverted,
                'user'                  => $user
            ]);
        }
    }

    public function throttleDeleteSag()
    {
        if (
            !$this->module->framework->throttle(
                '(project_id IS NULL OR project_id IS NOT NULL) AND message = "Deleted SAG"',
                [],
                2,
                1
            )
        ) {
            $this->deleteSag();
        } else {
            $this->module->framework->log('deleteSag Throttled', [
                'sag_id' => $this->sagId,
                'user'   => $this->module->framework->getUser()->getUsername()
            ]);
        }
    }

    public function deleteSag()
    {
        $user = $this->module->framework->getUser()->getUsername();
        try {
            $result = $this->module->framework->removeLogs(
                "message = 'sag' AND sag_id = ? AND (project_id IS NULL OR project_id IS NOT NULL) ",
                [ $this->sagId ]
            );
            $this->module->framework->log('Deleted SAG', [
                'user'   => $user,
                'sag_id' => $this->sagId
            ]);
            return $result;
        } catch ( \Throwable $e ) {
            $this->module->framework->log('Error deleting SAG', [
                'error'  => $e->getMessage(),
                'user'   => $user,
                'sag_id' => $this->sagId
            ]);
        }
    }

    public function getSagRights()
    {
        if ( empty($this->permissions) ) {
            $sql    = "SELECT sag_id, sag_name, permissions WHERE message = 'sag' AND sag_id = ? AND (project_id IS NULL OR project_id IS NOT NULL) ORDER BY log_id DESC LIMIT 1";
            $result = $this->module->framework->queryLogs($sql, [ $this->sagId ]);
            $row    = $result->fetch_assoc();
            if ( empty($row) ) {
                $sagId2  = $this->module->defaultSagId;
                $result2 = $this->module->framework->queryLogs($sql, [ $sagId2 ]);
                $rights  = $result2->fetch_assoc();

                if ( empty($rights) ) {
                    $rights = $this->module->setDefaultSag();
                }
            } else {
                $this->permissionsJson = $row['permissions'];
                $rights                = json_decode($row['permissions'], true);
            }
            $this->permissions = $rights;
        } else {
            $rights = $this->permissions;
        }
        return $rights;
    }

    public function setSagRights(array $rights)
    {
        $this->permissions = $rights;
    }

    private function getSagNameFromSagId() : string
    {
        $sagName = '';
        if (empty($this->sagId)) {
            return $sagName;
        }
        $sql    = "SELECT sag_name WHERE message = 'sag' AND sag_id = ? AND (project_id IS NULL OR project_id IS NOT NULL) ORDER BY log_id DESC LIMIT 1";
        $result = $this->module->framework->queryLogs($sql, [ $this->sagId ]);
        if ( $row = $result->fetch_assoc() ) {
            $sagName = $row['sag_name'] ?? '';
        }
        return $sagName;
    }

    public function sagExists()
    {
        if ( empty($this->sagId) ) {
            return false;
        }
        $sql    = 'SELECT sag_id WHERE message = \'sag\' AND sag_id = ? AND (project_id IS NULL OR project_id IS NOT NULL)';
        $result = $this->module->framework->queryLogs($sql, [ $this->sagId ]);
        return $result->num_rows > 0;
    }

    public function parsePermissions()
    {
        $permissions       = json_decode($this->permissionsJson, true);
        $this->permissions = $permissions;
    }
}