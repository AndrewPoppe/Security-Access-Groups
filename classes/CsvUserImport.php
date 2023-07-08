<?php

namespace YaleREDCap\SecurityAccessGroups;

class CsvUserImport
{
    private $csvString;
    private $module;
    public $csvContents;
    public $cleanContents;
    public $badSags = [];
    public $badUsers = [];
    public $errorMessages = [];
    public $assignments = [];
    private $valid = true;
    private $rowValid = true;
    public function __construct(SecurityAccessGroups $module, string $csvString)
    {
        $this->module    = $module;
        $this->csvString = $csvString;
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

    private function checkUsername($username)
    {
        $username = trim($username);
        if ( empty($username) ) {
            $this->errorMessages[] = 'One or more username was invalid.';
            $this->rowValid        = false;
        }
        return $username;
    }

    private function checkUser($username)
    {
        $userInfo = $this->module->getUserInfo($username);
        if ( is_null($userInfo) ) {
            $this->badUsers[] = htmlspecialchars($username, ENT_QUOTES);
            $this->rowValid   = false;
        }
        return $userInfo;
    }

    private function checkSag($sag)
    {
        $sag = trim($sag);
        if ( empty($sag) ) {
            $this->errorMessages[] = 'One or more SAG id was invalid.';
            $this->rowValid        = false;
        }
        if ( !$this->module->sagExists($sag) ) {
            $this->badSags[] = htmlspecialchars($sag, ENT_QUOTES);
            $this->rowValid  = false;
        }
        return $sag;
    }

    public function contentsValid()
    {
        $header = $this->csvContents[0];

        $usernameIndex = array_search('username', $header, true);
        $sagIdIndex    = array_search('sag_id', $header, true);
        if ( $usernameIndex === false || $sagIdIndex === false ) {
            $this->errorMessages[] = 'Input file did not contain \'username\' and/or \'sag_id\' columns.';
            return false;
        }


        foreach ( $this->csvContents as $key => $row ) {
            $this->rowValid = true;
            if ( $key === array_key_first($this->csvContents) ) {
                continue;
            }
            $thisUsername = $this->checkUsername($row[$usernameIndex]);
            $this->checkUser($thisUsername);
            $thisSag = $this->checkSag($row[$sagIdIndex]);

            if ( !$this->rowValid ) {
                $this->valid = false;
            } else {
                $this->cleanContents[] = [ 'user' => $thisUsername, 'sag' => $thisSag ];
            }
        }

        if ( !empty($this->badUsers) || !empty($this->badSags) ) {
            $this->errorMessages[] = 'The following users and/or SAGs do not exist.';
            $this->valid           = false;
        }

        if ( empty($this->cleanContents) ) {
            $this->errorMessages[] = 'No valid SAG assignments were present in the import file.';
            $this->valid           = false;
        }

        $this->errorMessages = array_values(array_unique($this->errorMessages));
        return $this->valid;
    }

    private function getAssignments()
    {
        foreach ( $this->cleanContents as $row ) {
            $currentSag       = $this->module->getUserSag($row['user']);
            $userInfo         = $this->module->getUserInfo($row['user']);
            $requestedSagInfo = $this->module->getSagRightsById($row['sag']);
            $currentSagInfo   = $this->module->getSagRightsById($currentSag);

            $result = [
                'username'   => $userInfo['username'],
                'name'       => $userInfo['user_firstname'] . ' ' . $userInfo['user_lastname'],
                'currentSag' => '<strong>' . $currentSagInfo['sag_name'] . '</strong> (' . $currentSagInfo['sag_id'] . ')'
            ];

            if ( $currentSag !== $row['sag'] ) {
                $result['newSagId'] = $requestedSagInfo['sag_id'];
                $result['newSag']   = '<strong>' . $requestedSagInfo['sag_name'] . '</strong> (' . $requestedSagInfo['sag_id'] . ')';
            }

            $this->assignments[] = $result;
        }
    }

    public function getUpdateTable()
    {
        $this->getAssignments();
        $html        = '<div class="modal fade">
            <div class="modal-lg modal-dialog modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Confirm SAG assignments</h5>
                        <button type="button" class="btn-close align-self-center" data-bs-dismiss="modal" data-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                    <div class="container mb-4 w-90" style="font-size:larger;">Examine the table of proposed changes below to verify it is correct.
                    Only users in highlighted rows will be affected, and for those users the "SAG"
                    column will show both the <span class="text-primary font-weight-bold"">proposed SAG</span> as well as the <span class="text-danger font-weight-bold"">current SAG</span>.</div>
                    <table class="table table-bordered">
                        <thead class="thead-dark">
                            <tr>
                                <th>Username</th>
                                <th>Name</th>
                                <th>SAG</th>
                            </tr>
                        </thead>
                        <tbody>';
        $nothingToDo = true;
        foreach ( $this->assignments as $row ) {
            $rowClass  = 'text-secondary';
            $cellClass = '';
            if ( isset($row['newSag']) ) {
                $nothingToDo = false;
                $rowClass    = 'table-warning';
                $cellClass   = 'font-weight-bold';
                $sagText     = '<span>New: </span><span class="text-primary">' . $row["newSag"] . '</span><br><span>Current: </span><span class="text-danger">' . $row["currentSag"] . '</span>';
            } else {
                $sagText = '<span>' . $row['currentSag'] . '</span>';
            }
            $html .= '<tr class="' . $rowClass . '">
                <td class="' . $cellClass . ' align-middle">' . $row["username"] . '</td>
                <td class="' . $cellClass . ' align-middle">' . $row["name"] . '</td>
                <td class="align-middle">' . $sagText . '</td>
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
            foreach ( $this->assignments as $row ) {
                $username = $row['username'];
                $sag      = $row['newSagId'];
                if ( empty($sag) ) {
                    continue;
                }
                $setting = $username . '-sag';
                $this->module->setSystemSetting($setting, $sag);
            }
            $this->module->log('Imported SAG assignments', [ 'assignments' => json_encode($this->assignments) ]);
            $success = true;
        } catch ( \Throwable $e ) {
            $this->module->log('Error importing SAG assignments', [ 'error' => $e->getMessage() ]);
        } finally {
            return $success;
        }
    }
}