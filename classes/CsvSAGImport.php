<?php

namespace YaleREDCap\SecurityAccessGroups;

class CsvSAGImport
{
    private $csvString;
    private $module;
    private $permissions;
    public $csvContents;
    public $cleanContents;
    public $error_messages = [];
    public $proposed = [];
    private $header;
    public function __construct(SecurityAccessGroups $module, string $csvString)
    {
        $this->module    = $module;
        $this->csvString = $csvString;
        $permissions     = array_keys($module->getDisplayTextForRights(true));
        if ( ($key = array_search("randomization", $permissions)) !== false ) {
            unset($permissions[$key]);
        }
        if ( ($key = array_search("api", $permissions)) !== false ) {
            unset($permissions[$key]);
        }
        if ( ($key = array_search("data_quality_resolution", $permissions)) !== false ) {
            unset($permissions[$key]);
        }
        $this->permissions = $permissions;
    }

    public function parseCsvString()
    {
        $lineEnding = strpos($this->csvString, "\r\n") !== false ? "\r\n" : "\n";
        $Data       = str_getcsv($this->csvString, $lineEnding);
        foreach ( $Data as &$Row ) {
            $Row = str_getcsv($Row, ",");
        }
        $this->csvContents = $Data;
    }

    public function contentsValid()
    {
        $this->header = $this->csvContents[0];

        $roleNameIndex = array_search("role_name", $this->header, true);
        $roleIdIndex   = array_search("role_id", $this->header, true);
        if ( $roleNameIndex === false || $roleIdIndex === false ) {
            $this->error_messages[] = "Input file did not contain 'role_name' and/or 'role_id' columns.";
            return false;
        }

        $valid = true;

        foreach ( $this->csvContents as $key => $row ) {
            $thisRowClean = true;
            if ( $key === array_key_first($this->csvContents) ) {
                foreach ( $row as $permission => $value ) {
                    if ( $permission === 'role_name' || $permission === 'role_id' ) {
                        continue;
                    }
                    if ( !in_array($permission, $this->permissions) ) {
                        $this->error_messages[] = 'One or more permissions was invalid.';
                        $thisRowClean           = false;
                    } else {
                        continue;
                    }
                }
                continue;
            }

            $thisRoleName = htmlspecialchars(trim($row[$roleNameIndex]), ENT_QUOTES);
            if ( empty($thisRoleName) ) {
                $this->error_messages[] = "One or more role name was invalid.";
                $thisRowClean           = false;
            }

            $thisRole = trim($row[$roleIdIndex]);
            if ( empty($thisRole) || !$this->module->systemRoleExists($thisRole) ) {
                $thisRole = '[new]';
            }

            if ( !$thisRowClean ) {
                $valid = false;
            } else {
                $this->cleanContents[] = [ "role_name" => $thisRoleName, "role_id" => $thisRole, "permissions" => $this->parsePermissions($row) ];
            }
        }
        if ( empty($this->cleanContents) ) {
            $this->error_messages[] = "No valid roles were present in the import file.";
            $valid                  = false;
        }

        $this->error_messages = array_values(array_unique($this->error_messages));
        return $valid;
    }

    private function parsePermissions($thesePermissions)
    {
        $result = [];
        foreach ( $thesePermissions as $index => $value ) {
            $permissionName = $this->header[$index];

            // if ( $permissionName === "randomization" ) {
            //     $randomizationRights        = str_split(decbin($value));
            //     $result['random_perform']   = $randomizationRights[0];
            //     $result['random_dashboard'] = $randomizationRights[1];
            //     $result['random_setup']     = $randomizationRights[2];
            // }

            // if ( $permissionName === "api" ) {
            //     $apiRights            = str_split(decbin($value));
            //     $result['api_export'] = $apiRights[0];
            //     $result['api_import'] = $apiRights[1];
            // }

            // if ( $permissionName === "data_quality_resolution" ) {
            //     $dataQualityRights                         = str_split(decbin($value));
            //     $result['data_quality_resolution_view']    = $dataQualityRights[0];
            //     $result['data_quality_resolution_respond'] = $dataQualityRights[1];
            //     $result['data_quality_resolution_open']    = $dataQualityRights[2];
            //     $result['data_quality_resolution_close']   = $dataQualityRights[3];
            // }

            // $permissionName = $permissionName === "dataViewing" ? "data_entry" : $permissionName;
            // $permissionName = $permissionName === "dataExport" ? "data_export_tool" : $permissionName;

            $isPermission = in_array($permissionName, $this->permissions, true);
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
            $id                     = $row["role_id"];
            $thisResult["existing"] = $this->module->systemRoleExists($id);
            if ( $thisResult["existing"] ) {
                $currentRole                = $this->module->getSystemRoleRightsById($id);
                $currentRole["permissions"] = json_decode($currentRole["permissions"], true);
                // $currentRole["permissions"]["data_entry"]       = $currentRole["permissions"]["dataViewing"];
                // $currentRole["permissions"]["data_export_tool"] = $currentRole["permissions"]["dataExport"];
            } else {
                $currentRole = $row;
            }
            $thisResult["role_id"] = $id;
            $thisResult["changes"] = false;
            if ( $row["role_name"] == $currentRole["role_name"] ) {
                $thisResult["role_name"] = $row["role_name"];
            } else {
                $thisResult["role_name"] = [ 'current' => $currentRole["role_name"], 'proposed' => $row["role_name"] ];
                $thisResult["changes"]   = true;
            }
            $thisResult["permissions"] = [];
            foreach ( $this->permissions as $permission ) {
                $current  = $currentRole["permissions"][$permission] ?? 0;
                $proposed = $row["permissions"][$permission] ?? 0;

                if ( $current == $proposed ) {
                    $thisResult["permissions"][$permission] = $proposed;
                } else {
                    $thisResult["permissions"][$permission] = [ 'current' => $current, 'proposed' => $proposed ];
                    $thisResult["changes"]                  = true;
                }
            }
            $result[] = $thisResult;
            //$this->module->log("Role definition", [ "cc" => json_encode($this->cleanContents), "permissions" => json_encode($this->permissions), "stuff" => json_encode($thisResult), "current" => json_encode($currentRole), "proposed" => json_encode($row) ]);
        }
        $this->proposed = $result;
    }

    private function formatCell($cellValue, $centerText = true)
    {
        if ( !is_array($cellValue) ) {
            return '<td class="' . ($centerText ? "text-center" : "") . '">' . $cellValue . '</td>';
        }
        return '<td class="table-warning">New: <span class="text-primary font-weight-bold">' . $cellValue['proposed'] . '</span><br>Current: <span class="text-danger font-weight-bold">' . $cellValue['current'] . '</span></td>';
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
            if ( !$row["existing"] ) {
                $rowClass    = "table-success";
                $nothingToDo = false;
            } else {
                $rowClass    = $row["changes"] ? "bg-light" : "text-secondary bg-light";
                $nothingToDo = $row["changes"] ? false : $nothingToDo;
            }
            $html .= '<tr class="' . $rowClass . '">' . $this->formatCell($row["role_id"], false) . $this->formatCell($row["role_name"], false);
            foreach ( $this->permissions as $permission ) {
                $value = $row["permissions"][$permission];
                $html .= $this->formatCell($value);
            }
            $html .= '</tr>';
        }

        $html .= '</tbody>
                    </table>
                </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-primary" onclick="confirmImport()" ' . ($nothingToDo ? 'title="There are no changes to make" disabled' : '') . '>Confirm</button>            
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
                $role_name = $row["role_name"];
                $role      = $row["role_id"];
                if ( empty($role_name) ) {
                    continue;
                }
                if ( $this->module->systemRoleExists($role) ) {
                    $this->module->updateSystemRole($role, $role_name, json_encode($row["permissions"]));
                } else {
                    $role = $this->module->generateNewRoleId();
                    $this->module->saveSystemRole($role, $role_name, json_encode($row["permissions"]));
                }
            }
            $success = true;
        } catch ( \Throwable $e ) {
            $this->module->log('Error importing roles', [ "error" => $e->getMessage() ]);
        } finally {
            return $success;
        }
    }
}