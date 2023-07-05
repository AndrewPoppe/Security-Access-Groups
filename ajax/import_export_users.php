<?php

namespace YaleREDCap\SecurityAccessGroups;

/** @var SecurityAccessGroups $module */

$scriptPath = $module->getSafePath('UserRights/import_export_users.php', APP_PATH_DOCROOT);

if ( $_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['csv_content']) ) {
    require_once $scriptPath;
    exit;
}
require_once $module->getSafePath('Config/init_functions.php', APP_PATH_DOCROOT);
if ( isset($_POST['csv_content']) && $_POST['csv_content'] != '' ) {
    $csv_content      = filter_input(INPUT_POST, 'csv_content');
    $data             = csvToArray(removeBOMfromUTF8($csv_content));
    $badRights        = [];
    $allCurrentRights = [];
    foreach ( $data as $key => $this_user ) {
        $username = $this_user['username'];
        $sagId    = $module->getUserSystemRole($username);
        $sag      = $module->getSystemRoleRightsById($sagId);

        if ( isset($this_user['forms']) && $this_user['forms'] != '' ) {
            foreach ( explode(',', $this_user['forms']) as $thisPair ) {
                list( $thisForm, $thisRight )   = explode(':', $thisPair, 2);
                $this_user['form-' . $thisForm] = $thisRight;
            }
            unset($this_user['forms']);
        }
        if ( isset($this_user['forms_export']) && $this_user['forms_export'] != '' ) {
            foreach ( explode(',', $this_user['forms_export']) as $thisPair ) {
                list( $thisForm, $thisRight )          = explode(':', $thisPair, 2);
                $this_user['export-form-' . $thisForm] = $thisRight;
            }
            unset($this_user['forms_export']);
        }
        $this_user = array_filter($this_user, function ($value) {
            return $value != 0;
        });

        $acceptableRights = $module->getAcceptableRights($username);
        $current_rights   = $module->getCurrentRights($username, $module->framework->getProjectId()) ?? [];
        $requested_rights = $this_user;
        $theseBadRights   = $module->checkProposedRights($acceptableRights, $requested_rights);

        // Store for later logging
        $allCurrentRights[$username] = $current_rights;

        // We ignore expired users, unless the request unexpires them
        $userExpired         = $module->isUserExpired($username, $module->framework->getProjectId());
        $requestedExpiration = $this_user['expiration'];
        $requestedUnexpired  = empty($requestedExpiration) || (strtotime($requestedExpiration) >= strtotime('today'));
        $ignore              = $userExpired && !$requestedUnexpired;

        if ( !empty($theseBadRights) && !$ignore ) {
            $badRights[$username] = [
                'SAG'    => $sag['role_name'],
                'rights' => $theseBadRights
            ];
        }
    }

    if ( empty($badRights) ) {
        ob_start(function () use ($allCurrentRights, $module) {
            try {
                $imported    = $_SESSION['imported'] === 'users';
                $error_count = sizeof($_SESSION['errors']) ?? 0;
                $succeeded   = $imported && $error_count === 0;
                if ( $succeeded ) {
                    $data_values = '';
                    $pid         = $module->framework->getProjectId();
                    $logTable    = $module->framework->getProject($pid)->getLogTable();
                    $sql         = "SELECT log_event_id FROM $logTable WHERE project_id = ? AND user = ? AND page = 'ExternalModules/index.php' AND object_type = 'redcap_user_rights' AND pk = ? AND event IN ('INSERT','UPDATE') AND TIMESTAMPDIFF(SECOND,ts,NOW()) <= 10 ORDER BY ts DESC";
                    $redcap_user = $module->getUser()->getUsername();
                    foreach ( $allCurrentRights as $username => $current_rights ) {
                        $updated_rights = $module->getCurrentRights($username, $pid) ?? [];
                        $changes        = json_encode(array_diff_assoc($updated_rights, $current_rights), JSON_PRETTY_PRINT);
                        $changes        = $changes === '[]' ? 'None' : $changes;
                        $data_values    = "user = '$username'\nchanges = $changes\n\n";

                        $params       = [ $pid, $redcap_user, $username ];
                        $result       = $module->framework->query($sql, $params);
                        $log_event_id = intval($result->fetch_assoc()['log_event_id']);

                        if ( $log_event_id != 0 ) {
                            $module->framework->query("UPDATE $logTable SET data_values = ? WHERE log_event_id = ?", [ $data_values, $log_event_id ]);
                        } else {
                            \Logging::logEvent(
                                '',
                                'redcap_user_rights',
                                'update',
                                $username,
                                $data_values,
                                'Update user',
                                '',
                                '',
                                '',
                                true,
                                null,
                                null,
                                false
                            );
                        }
                    }
                }
            } catch ( \Throwable $e ) {
                $module->log('Error logging user edit (csv import)', [ 'error' => $e->getMessage() ]);
            }
        });
        $module->framework->log('User Rights Import: Importing users', [ 'users' => json_encode($data) ]);
        require_once $scriptPath;
        ob_end_flush(); // End buffering and clean up
    } else {
        $module->framework->log('User Rights Import: Bad rights found', [ 'bad rights' => json_encode($badRights) ]);
        $_SESSION['SUR_imported']   = 'users';
        $_SESSION['SUR_bad_rights'] = json_encode($badRights);
        redirect(APP_PATH_WEBROOT . 'UserRights/index.php?pid=' . PROJECT_ID);
    }
}