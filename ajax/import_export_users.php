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
    $csvContent       = filter_input(INPUT_POST, 'csv_content');
    $data             = csvToArray(removeBOMfromUTF8($csvContent));
    $badRights        = [];
    $allCurrentRights = [];
    foreach ( $data as $key => $thisUser ) {
        $username = $thisUser['username'];
        $sagId    = $module->getUserSag($username);
        $sag      = new SAG($module, $sagId);

        if ( isset($thisUser['forms']) && $thisUser['forms'] != '' ) {
            foreach ( explode(',', $thisUser['forms']) as $thisPair ) {
                list( $thisForm, $thisRight )  = explode(':', $thisPair, 2);
                $thisUser['form-' . $thisForm] = $thisRight;
            }
            unset($thisUser['forms']);
        }
        if ( isset($thisUser['forms_export']) && $thisUser['forms_export'] != '' ) {
            foreach ( explode(',', $thisUser['forms_export']) as $thisPair ) {
                list( $thisForm, $thisRight )         = explode(':', $thisPair, 2);
                $thisUser['export-form-' . $thisForm] = $thisRight;
            }
            unset($thisUser['forms_export']);
        }
        $thisUser = array_filter($thisUser, function ($value) {
            return $value != 0;
        });

        $acceptableRights = $module->getAcceptableRights($username);
        $currentRights    = $module->getCurrentRights($username, $module->framework->getProjectId()) ?? [];
        $requestedRights  = $thisUser;
        $theseBadRights   = $module->checkProposedRights($acceptableRights, $requestedRights);

        // Store for later logging
        $allCurrentRights[$username] = $currentRights;

        // We ignore expired users, unless the request unexpires them
        $userExpired         = $module->isUserExpired($username, $module->framework->getProjectId());
        $requestedExpiration = $thisUser['expiration'];
        $requestedUnexpired  = empty($requestedExpiration) || (strtotime($requestedExpiration) >= strtotime('today'));
        $ignore              = $userExpired && !$requestedUnexpired;

        if ( !empty($theseBadRights) && !$ignore ) {
            $badRights[$username] = [
                'SAG'    => $sag->sagName,
                'rights' => $theseBadRights
            ];
        }
    }

    if ( empty($badRights) ) {
        ob_start(function () use ($allCurrentRights, $module) {
            try {
                $imported   = $_SESSION['imported'] === 'users';
                $errorCount = sizeof($_SESSION['errors']) ?? 0;
                $succeeded  = $imported && $errorCount === 0;
                if ( $succeeded ) {
                    $dataValues = '';
                    $pid        = $module->framework->getProjectId();
                    $logTable   = $module->framework->getProject($pid)->getLogTable();
                    $sql        = "SELECT log_event_id FROM $logTable WHERE project_id = ? AND user = ? AND page = 'ExternalModules/index.php' AND object_type = 'redcap_user_rights' AND pk = ? AND event IN ('INSERT','UPDATE') AND TIMESTAMPDIFF(SECOND,ts,NOW()) <= 10 ORDER BY ts DESC";
                    $redcapUser = $module->getUser()->getUsername();
                    foreach ( $allCurrentRights as $username => $currentRights ) {
                        $updatedRights = $module->getCurrentRights($username, $pid) ?? [];
                        $changes       = json_encode(array_diff_assoc($updatedRights, $currentRights), JSON_PRETTY_PRINT);
                        $changes       = $changes === '[]' ? 'None' : $changes;
                        $dataValues    = "user = '$username'\nchanges = $changes\n\n";

                        $params     = [ $pid, $redcapUser, $username ];
                        $result     = $module->framework->query($sql, $params);
                        $logEventId = intval($result->fetch_assoc()['log_event_id']);

                        if ( $logEventId != 0 ) {
                            $module->framework->query("UPDATE $logTable SET data_values = ? WHERE log_event_id = ?", [ $dataValues, $logEventId ]);
                        } else {
                            \Logging::logEvent(
                                '',
                                'redcap_user_rights',
                                'update',
                                $username,
                                $dataValues,
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
        $_SESSION['SAG_imported']   = 'users';
        $_SESSION['SAG_bad_rights'] = json_encode($badRights);
        redirect(APP_PATH_WEBROOT . 'UserRights/index.php?pid=' . PROJECT_ID);
    }
}