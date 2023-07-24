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
        $permissions     = array_keys(RightsUtilities::getDisplayTextForRights(true));
        if ( ($key = array_search('randomization', $permissions)) !== false ) {
            unset($permissions[$key]);
        }
        if ( ($key = array_search('api', $permissions)) !== false ) {
            unset($permissions[$key]);
        }
        if ( ($key = array_search('data_quality_resolution', $permissions)) !== false ) {
            unset($permissions[$key]);
        }
        if ( ($key = array_search('double_data', $permissions)) !== false ) {
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
            if ( $permission === 'sag_name' || $permission === 'sag_id' ) {
                continue;
            }
            if ( !in_array($permission, $this->permissions, true) ) {
                $this->errorMessages[] = 'One or more permissions was invalid.' . $permission;
                $this->rowValid        = false;
            }
        }
    }

    private function checkSagName($sagName)
    {
        $sagName = htmlspecialchars(trim($sagName), ENT_QUOTES);
        if ( empty($sagName) ) {
            $this->errorMessages[] = 'One or more SAG name was invalid.';
            $this->rowValid        = false;
        }
        return $sagName;
    }

    private function checkSagId($sagId)
    {
        $sagId = trim($sagId);
        $sag   = new SAG($this->module, $sagId);
        if ( empty($sagId) || !$sag->sagExists() ) {
            $sagId = '[new]';
        }
        return $sagId;
    }

    public function contentsValid()
    {
        $this->header = $this->csvContents[0];

        $sagNameIndex = array_search('sag_name', $this->header, true);
        $sagIdIndex   = array_search('sag_id', $this->header, true);
        if ( $sagNameIndex === false || $sagIdIndex === false ) {
            $this->errorMessages[] = 'Input file did not contain \'sag_name\' and/or \'sag_id\' columns.';
            return false;
        }

        foreach ( $this->csvContents as $key => $row ) {
            $this->rowValid = true;

            if ( $key === array_key_first($this->csvContents) ) {
                $this->permissionsNamesAreClean($row);
                continue;
            }

            $thisSagName = $this->checkSagName($row[$sagNameIndex]);
            $thisSag     = $this->checkSagId($row[$sagIdIndex]);

            if ( !$this->rowValid ) {
                $this->valid = false;
            } else {
                $this->cleanContents[] = [
                    'sag_name'    => $thisSagName,
                    'sag_id'      => $thisSag,
                    'permissions' => $this->parsePermissions($row)
                ];
            }
        }
        if ( empty($this->cleanContents) ) {
            $this->errorMessages[] = 'No valid SAGs were present in the import file.';
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

    public function getSagDefinitions()
    {
        $result = [];
        foreach ( $this->cleanContents as $row ) {
            $thisResult             = [];
            $id                     = $row['sag_id'];
            $sag                    = new SAG($this->module, $id);
            $thisResult['existing'] = $sag->sagExists();
            if ( $thisResult['existing'] ) {
                $sag->getSagRights();
            } else {
                $sag->setSagRights($row['permissions']);
            }
            $thisResult['sag_id']  = $id;
            $thisResult['changes'] = false;
            if ( $row['sag_name'] == $sag->sagName ) {
                $thisResult['sag_name'] = $sag->sagName;
            } else {
                $thisResult['sag_name'] = [ 'current' => $sag->sagName, 'proposed' => $row['sag_name'] ];
                $thisResult['changes']  = true;
            }
            $thisResult['permissions'] = [];
            foreach ( $this->permissions as $permission ) {
                $current  = $sag->getSagRights()[$permission] ?? 0;
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
        $this->getSagDefinitions();
        $html = '<div class="modal fade">
            <div class="modal-lg modal-dialog modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Confirm SAG definitions</h5>
                        <button type="button" class="btn-close align-self-center" data-bs-dismiss="modal" data-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                    <div class="container mb-4 w-90" style="font-size:larger;">Examine the table of proposed changes below to verify it is correct.
                    Only SAGs in highlighted rows will be affected.</div>
                    <table class="table table-bordered">
                        <thead class="thead-dark">
                            <tr>
                                <th>SAG ID</th>
                                <th>SAG</th>';
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
                $this->formatCell($row['sag_id'], false) .
                $this->formatCell($row['sag_name'], false);
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
                        <button type="button" class="btn btn-primary" onclick="module.confirmImport()" ' .
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
                $sagName = $row['sag_name'];
                $sagId   = $row['sag_id'];
                if ( empty($sagName) ) {
                    continue;
                }
                $sag = new SAG($this->module, $sagId, $sagName);
                if ( $sag->sagExists() ) {
                    $sag->updateSag(json_encode($row['permissions']));
                } else {
                    $sagId = $this->module->generateNewSagId();
                    $sag->setSagId($sagId);
                    $sag->saveSag(json_encode($row['permissions']));
                }
            }
            $this->module->log('Imported SAGs from CSV');
            $success = true;
        } catch ( \Throwable $e ) {
            $this->module->log('Error importing SAGs', [ 'error' => $e->getMessage() ]);
        } finally {
            return $success;
        }
    }
}