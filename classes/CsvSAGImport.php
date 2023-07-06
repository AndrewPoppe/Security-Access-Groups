<?php

namespace YaleREDCap\SecurityAccessGroups;

class CsvSAGImport
{
    private string $csvString;
    private SecurityAccessGroups $module;
    private array $permissions;
    public $csvContents;
    public $cleanContents;
    public array $errorMessages = [];
    public array $proposed = [];
    private $header;
    private bool $valid = true;
    private bool $rowValid = true;
    public function __construct(SecurityAccessGroups $module, string $csvString)
    {
        $this->module    = $module;
        $this->csvString = $csvString;
        $permissions     = array_keys($module->getDisplayTextForRights(true));
        if ( ($key = array_search('randomization', $permissions)) !== false ) {
            unset($permissions[$key]);
        }
        if ( ($key = array_search('api', $permissions)) !== false ) {
            unset($permissions[$key]);
        }
        if ( ($key = array_search('data_quality_resolution', $permissions)) !== false ) {
            unset($permissions[$key]);
        }
        $this->permissions = $permissions;
    }

    public function parseCsvString()
    {
        $lineEnding = strpos($this->csvString, "\r\n") !== false ? "\r\n" : "\n";
        $data       = str_getcsv($this->csvString, $lineEnding);
        foreach ( $data as &$row ) {
            $row = str_getcsv($row, ',');
        }
        $this->csvContents = $data;
    }

    private function permissionsNamesAreClean($row)
    {
        foreach ( $row as $permission ) {
            if ( $permission === 'role_name' || $permission === 'role_id' ) {
                continue;
            }
            if ( !in_array($permission, $this->permissions, true) ) {
                $this->errorMessages[] = 'One or more permissions was invalid.' . $permission;
                $this->rowValid        = false;
            }
        }
    }

    private function checkRoleName($roleName)
    {
        $roleName = htmlspecialchars(trim($roleName), ENT_QUOTES);
        if ( empty($roleName) ) {
            $this->errorMessages[] = 'One or more role name was invalid.';
            $this->rowValid        = false;
        }
        return $roleName;
    }

    private function checkRoleId($roleId)
    {
        $roleId = trim($roleId);
        if ( empty($roleId) || !$this->module->systemRoleExists($roleId) ) {
            $roleId = '[new]';
        }
        return $roleId;
    }

    public function contentsValid()
    {
        $this->header = $this->csvContents[0];

        $roleNameIndex = array_search('role_name', $this->header, true);
        $roleIdIndex   = array_search('role_id', $this->header, true);
        if ( $roleNameIndex === false || $roleIdIndex === false ) {
            $this->errorMessages[] = 'Input file did not contain \'role_name\' and/or \'role_id\' columns.';
            return false;
        }

        foreach ( $this->csvContents as $key => $row ) {
            $this->rowValid = true;

            if ( $key === array_key_first($this->csvContents) ) {
                $this->permissionsNamesAreClean($row);
                continue;
            }

            $thisRoleName = $this->checkRoleName($row[$roleNameIndex]);
            $thisRole     = $this->checkRoleId($row[$roleIdIndex]);

            if ( !$this->rowValid ) {
                $this->valid = false;
            } else {
                $this->cleanContents[] = [
                    'role_name'   => $thisRoleName,
                    'role_id'     => $thisRole,
                    'permissions' => $this->parsePermissions($row)
                ];
            }
        }
        if ( empty($this->cleanContents) ) {
            $this->errorMessages[] = 'No valid roles were present in the import file.';
            $this->valid           = false;
        }

        $this->errorMessages = array_values(array_unique($this->errorMessages));
        return $this->valid;
    }

    private function parsePermissions($thesePermissions)
    {
        $result = [];
        foreach ( $thesePermissions as $index => $value ) {
            $permissionName = $this->header[$index];
            $isPermission   = in_array($permissionName, $this->permissions, true);
            if ( $isPermission ) {
                $result[$permissionName] = (int) $value;
            }
        }
        return $result;
    }

    public function getRoleDefinitions()
    {
        $result = [];
        foreach ( $this->cleanContents as $row ) {
            $thisResult             = [];
            $id                     = $row['role_id'];
            $thisResult['existing'] = $this->module->systemRoleExists($id);
            if ( $thisResult['existing'] ) {
                $currentRole                = $this->module->getSystemRoleRightsById($id);
                $currentRole['permissions'] = json_decode($currentRole['permissions'], true);
            } else {
                $currentRole = $row;
            }
            $thisResult['role_id'] = $id;
            $thisResult['changes'] = false;
            if ( $row['role_name'] == $currentRole['role_name'] ) {
                $thisResult['role_name'] = $row['role_name'];
            } else {
                $thisResult['role_name'] = [ 'current' => $currentRole['role_name'], 'proposed' => $row['role_name'] ];
                $thisResult['changes']   = true;
            }
            $thisResult['permissions'] = [];
            foreach ( $this->permissions as $permission ) {
                $current  = $currentRole['permissions'][$permission] ?? 0;
                $proposed = $row['permissions'][$permission] ?? 0;

                if ( $current == $proposed ) {
                    $thisResult['permissions'][$permission] = $proposed;
                } else {
                    $thisResult['permissions'][$permission] = [ 'current' => $current, 'proposed' => $proposed ];
                    $thisResult['changes']                  = true;
                }
            }
            $result[] = $thisResult;
        }
        $this->proposed = $result;
    }

    private function formatCell($cellValue, $centerText = true)
    {
        if ( !is_array($cellValue) ) {
            return '<td class="' . ($centerText ? 'text-center' : '') . '">' . $cellValue . '</td>';
        }
        return '<td class="table-warning">New: <span class="text-primary font-weight-bold">' .
            $cellValue['proposed'] . '</span><br>Current: <span class="text-danger font-weight-bold">' .
            $cellValue['current'] . '</span></td>';
    }

    public function getUpdateTable()
    {
        $this->getRoleDefinitions();
        $html = '<div class="modal fade">
            <div class="modal-lg modal-dialog modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Confirm role definitions</h5>
                        <button type="button" class="btn-close align-self-center" data-bs-dismiss="modal" data-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                    <div class="container mb-4 w-90" style="font-size:larger;">Examine the table of proposed changes below to verify it is correct.
                    Only roles in highlighted rows will be affected.</div>
                    <table class="table table-bordered">
                        <thead class="thead-dark">
                            <tr>
                                <th>Role ID</th>
                                <th>Role</th>';
        foreach ( $this->permissions as $permission ) {
            $html .= '<th>' . $permission . '</th>';
        }
        $html .= '</tr>
                        </thead>
                        <tbody>';
        $nothingToDo = true;
        foreach ( $this->proposed as $row ) {
            if ( !$row['existing'] ) {
                $rowClass    = 'table-success';
                $nothingToDo = false;
            } else {
                $rowClass    = $row['changes'] ? 'bg-light' : 'text-secondary bg-light';
                $nothingToDo = $row['changes'] ? false : $nothingToDo;
            }
            $html .= '<tr class="' . $rowClass . '">' .
                $this->formatCell($row['role_id'], false) .
                $this->formatCell($row['role_name'], false);
            foreach ( $this->permissions as $permission ) {
                $value = $row['permissions'][$permission];
                $html .= $this->formatCell($value);
            }
            $html .= '</tr>';
        }

        $html .= '</tbody>
                    </table>
                </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-primary" onclick="confirmImport()" ' .
            ($nothingToDo ? 'title="There are no changes to make" disabled' : '') .
            '>Confirm</button>
                    </div>
                </div>
            </div>
        </div>';
        return $html;
    }

    public function import()
    {
        $success = false;
        try {
            foreach ( $this->cleanContents as $row ) {
                $roleName = $row['role_name'];
                $role     = $row['role_id'];
                if ( empty($roleName) ) {
                    continue;
                }
                if ( $this->module->systemRoleExists($role) ) {
                    $this->module->updateSystemRole($role, $roleName, json_encode($row['permissions']));
                } else {
                    $role = $this->module->generateNewRoleId();
                    $this->module->saveSystemRole($role, $roleName, json_encode($row['permissions']));
                }
            }
            $this->module->log('Imported SAGs from CSV');
            $success = true;
        } catch ( \Throwable $e ) {
            $this->module->log('Error importing roles', [ 'error' => $e->getMessage() ]);
        } finally {
            return $success;
        }
    }
}