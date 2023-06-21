<?php

namespace YaleREDCap\SecurityAccessGroups;

/** @var SecurityAccessGroups $module */

$scriptPath = $module->getSafePath('UserRights/import_export_users.php', APP_PATH_DOCROOT);

if ( $_SERVER["REQUEST_METHOD"] !== "POST" || !isset($_POST['csv_content']) ) {
    require $scriptPath;
    exit;
}
require_once $module->getSafePath('Config/init_functions.php', APP_PATH_DOCROOT);
if ( isset($_POST['csv_content']) && $_POST['csv_content'] != '' ) {
    $csv_content        = filter_input(INPUT_POST, 'csv_content');
    $data               = csvToArray(removeBOMfromUTF8($csv_content));
    $bad_rights         = [];
    $all_current_rights = [];
    foreach ( $data as $key => $this_user ) {
        $username = $this_user['username'];
        if ( isset($this_user['forms']) && $this_user['forms'] != '' ) {
            foreach ( explode(",", $this_user['forms']) as $this_pair ) {
                list( $this_form, $this_right )  = explode(":", $this_pair, 2);
                $this_user['form-' . $this_form] = $this_right;
            }
            unset($this_user['forms']);
        }
        if ( isset($this_user['forms_export']) && $this_user['forms_export'] != '' ) {
            foreach ( explode(",", $this_user['forms_export']) as $this_pair ) {
                list( $this_form, $this_right )         = explode(":", $this_pair, 2);
                $this_user['export-form-' . $this_form] = $this_right;
            }
            unset($this_user['forms_export']);
        }
        $this_user = array_filter($this_user, function ($value, $key) {
            return ($value != 0);
        }, ARRAY_FILTER_USE_BOTH);

        $acceptable_rights = $module->getAcceptableRights($username);
        $current_rights    = $module->getCurrentRights($username, $module->framework->getProjectId());
        $requested_rights  = $this_user;
        $these_bad_rights  = $module->checkProposedRights($acceptable_rights, $requested_rights);

        // Store for later logging
        $all_current_rights[$username] = $current_rights;

        // We ignore expired users, unless the request unexpires them
        $userExpired         = $module->isUserExpired($username, $module->framework->getProjectId());
        $requestedExpiration = $this_user["expiration"];
        $requestedUnexpired  = empty($requestedExpiration) || (strtotime($requestedExpiration) >= strtotime('today'));
        $ignore              = $userExpired && !$requestedUnexpired;

        if ( !empty($these_bad_rights) && !$ignore ) {
            $bad_rights[$username] = $these_bad_rights;
        }
    }

    if ( empty($bad_rights) ) {
        ob_start(function ($str) use ($all_current_rights, $module) {
            try {
                $imported    = $_SESSION["imported"] === "users";
                $error_count = sizeof($_SESSION["errors"]) ?? 0;
                $succeeded   = $imported && $error_count === 0;
                if ( $succeeded ) {
                    $data_values = "";
                    $pid         = $module->framework->getProjectId();
                    $logTable    = $module->getLogTable($pid);
                    $sql         = "SELECT log_event_id FROM $logTable WHERE project_id = ? AND user = ? AND page = 'ExternalModules/index.php' AND object_type = 'redcap_user_rights' AND pk = ? AND event IN ('INSERT','UPDATE') AND TIMESTAMPDIFF(YEAR,ts,NOW()) <= 1 ORDER BY ts DESC";
                    $redcap_user = $module->getUser()->getUsername();
                    foreach ( $all_current_rights as $username => $current_rights ) {
                        $updated_rights = $module->getCurrentRights($username, $pid) ?? [];
                        $changes        = json_encode(array_diff_assoc($updated_rights, $current_rights), JSON_PRETTY_PRINT);
                        $changes        = $changes === "[]" ? "None" : $changes;
                        $data_values    = "user = '$username'\nchanges = $changes\n\n";

                        $params       = [ $pid, $redcap_user, $username ];
                        $result       = $module->framework->query($sql, $params);
                        $log_event_id = $result->fetch_assoc()["log_event_id"];

                        if ( !empty($log_event_id) ) {
                            $module->framework->query("UPDATE $logTable SET data_values = ? WHERE log_event_id = ?", [ $data_values, $log_event_id ]);
                        } else {
                            \Logging::logEvent(
                                '',
                                "redcap_user_rights",
                                "update",
                                $username,
                                $data_values,
                                "Update user",
                                "",
                                "",
                                "",
                                true,
                                null,
                                null,
                                false
                            );
                        }
                    }
                }
            } catch ( \Throwable $e ) {
                $module->log("Error logging user edit (csv import)", [ "error" => $e->getMessage() ]);
            }
        });
        require_once $scriptPath;
        ob_end_flush(); // End buffering and clean up
    } else {
        $_SESSION['SUR_imported']   = 'users';
        $_SESSION['SUR_bad_rights'] = json_encode($bad_rights);
        redirect(APP_PATH_WEBROOT . 'UserRights/index.php?pid=' . PROJECT_ID);
    }
}