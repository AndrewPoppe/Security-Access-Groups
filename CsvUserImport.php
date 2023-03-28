<?php

namespace YaleREDCap\SystemUserRights;

use YaleREDCap\SystemUserRights\SystemUserRights;

class CsvUserImport
{
    private $csvString;
    private $module;
    public $csvContents;
    public $cleanContents;
    public $bad_roles = [];
    public $bad_users = [];
    public $error_messages = [];
    public $assignments = [];
    function __construct(SystemUserRights $module, string $csvString)
    {
        $this->module = $module;
        $this->csvString = $csvString;
    }

    public function parseCsvString()
    {
        $lineEnding = str_contains($this->csvString, "\r\n") ? "\r\n" : "\n";
        $Data = str_getcsv($this->csvString, $lineEnding);
        foreach ($Data as &$Row) $Row = str_getcsv($Row, ",");
        $this->csvContents = $Data;
    }

    public function contentsValid()
    {
        $header = $this->csvContents[0];

        $usernameIndex = array_search("username", $header, true);
        $roleIdIndex = array_search("role_id", $header, true);
        if ($usernameIndex === false || $roleIdIndex === false) {
            $this->error_messages[] = "Input file did not contain 'username' and/or 'role_id' columns.";
            return false;
        }

        $valid = true;

        foreach ($this->csvContents as $key => $row) {
            $thisRowClean = true;
            if ($key === array_key_first($this->csvContents)) {
                continue;
            }
            $thisUsername = trim($row[$usernameIndex]);
            if (empty($thisUsername)) {
                $this->error_messages[] = "One or more username was invalid.";
                $thisRowClean = false;
            }
            if (is_null($this->module->getUserInfo($thisUsername))) {
                $this->bad_users[] = htmlspecialchars($thisUsername, ENT_QUOTES);
                $thisRowClean = false;
            }

            $thisRole = trim($row[$roleIdIndex]);
            if (empty($thisRole)) {
                $this->error_messages[] = "One or more role id was invalid.";
                $thisRowClean = false;
            }
            if ($this->module->systemRoleExists($thisRole) == false) {
                $this->bad_roles[] = htmlspecialchars($thisRole, ENT_QUOTES);
                $thisRowClean = false;
            }
            if (!$thisRowClean) {
                $valid = false;
            } else {
                $this->cleanContents[] = ["user" => $thisUsername, "role" => $thisRole];
            }
        }

        if (!empty($this->bad_users) || !empty($this->bad_roles)) {
            $this->error_messages[] = "The following users and/or roles do not exist.";
            $valid = false;
        }

        if (empty($this->cleanContents)) {
            $this->error_messages[] = "No valid user role assignments were present in the import file.";
            $valid = false;
        }

        $this->error_messages = array_values(array_unique($this->error_messages));
        return $valid;
    }

    private function getAssignments()
    {
        foreach ($this->cleanContents as $row) {
            $currentRole = $this->module->getUserSystemRole($row["user"]);
            $userInfo = $this->module->getUserInfo($row["user"]);
            $requestedRoleInfo = $this->module->getSystemRoleRightsById($row["role"]);
            $currentRoleInfo = $this->module->getSystemRoleRightsById($currentRole);

            $result = [
                "username" => $userInfo["username"],
                "name" => $userInfo["user_firstname"] . " " . $userInfo["user_lastname"],
                "currentRole" => "<strong>" . $currentRoleInfo["role_name"] . "</strong> (" . $currentRoleInfo["role_id"] . ")"
            ];

            if ($currentRole !== $row["role"]) {
                $result["newRoleId"] = $requestedRoleInfo["role_id"];
                $result["newRole"] = "<strong>" . $requestedRoleInfo["role_name"] . "</strong> (" . $requestedRoleInfo["role_id"] . ")";
            }

            $this->assignments[] = $result;
        }
    }

    public function getUpdateTable()
    {
        $this->getAssignments();
        $html = '<div class="modal fade">
            <div class="modal-lg modal-dialog modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Confirm role assignments</h5>
                        <button type="button" class="btn-close align-self-center" data-bs-dismiss="modal" data-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                    <div class="container mb-4 w-90" style="font-size:larger;">Examine the table of proposed changes below to verify it is correct. 
                    Only users in highlighted rows will be affected, and for those users the "Role" 
                    column will show both the <span class="text-primary font-weight-bold"">proposed role</span> as well as the <span class="text-danger font-weight-bold"">current role</span>.</div>
                    <table class="table table-bordered">
                        <thead class="thead-dark">
                            <tr>
                                <th>Username</th>
                                <th>Name</th>
                                <th>Role</th>
                            </tr>
                        </thead>
                        <tbody>';
        $nothingToDo = true;
        foreach ($this->assignments as $row) {
            $rowClass = "text-secondary";
            $cellClass = "";
            if (isset($row["newRole"])) {
                $nothingToDo = false;
                $rowClass = "table-warning";
                $cellClass = "font-weight-bold";
                $roleText = '<span>New: </span><span class="text-primary">' . $row["newRole"] . '</span><br><span>Current: </span><span class="text-danger">' . $row["currentRole"] . '</span>';
            } else {
                $roleText = '<span>' . $row["currentRole"] . '</span>';
            }
            $html .= '<tr class="' . $rowClass . '">
                <td class="' . $cellClass . ' align-middle">' . $row["username"] . '</td>
                <td class="' . $cellClass . ' align-middle">' . $row["name"] . '</td>
                <td class="align-middle">' . $roleText . '</td>        
            </tr>';
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
        $this->getAssignments();
        $success = false;
        try {
            foreach ($this->assignments as $row) {
                $username = $row["username"];
                $role = $row["newRoleId"];
                if (empty($role)) {
                    continue;
                }
                $setting = $username . "-role";
                $this->module->setSystemSetting($setting, $role);
            }
            $success = true;
        } catch (\Throwable $e) {
            $this->module->log('Error importing role assignments', ["error" => $e->getMessage()]);
        } finally {
            return $success;
        }
    }
}
