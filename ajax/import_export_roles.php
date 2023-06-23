<?php

namespace YaleREDCap\SecurityAccessGroups;

/** @var SecurityAccessGroups $module */

$scriptPath = $module->framework->getSafePath('UserRights/import_export_roles.php', APP_PATH_DOCROOT);

if ( $_SERVER["REQUEST_METHOD"] !== "POST" || !isset($_POST['csv_content']) ) {
    require $scriptPath;
    exit;
}
require_once $module->getSafePath('Config/init_functions.php', APP_PATH_DOCROOT);
if ( isset($_POST['csv_content']) && $_POST['csv_content'] != '' ) {

    $csv_content = filter_input(INPUT_POST, 'csv_content');
    $data        = csvToArray(removeBOMfromUTF8($csv_content));
    $pid         = $module->framework->getProjectId();

    if ( $_GET['action'] == 'uploadMapping' ) {
        $bad_rights = [];
        foreach ( $data as $key => $this_assignment ) {
            $username       = $this_assignment["username"];
            $sag_id         = $module->getUserSystemRole($username);
            $sag            = $module->getSystemRoleRightsById($sag_id);
            $uniqueRoleName = $this_assignment["unique_role_name"];
            if ( $uniqueRoleName == '' ) {
                continue;
            }
            $role_id           = $module->getRoleIdFromUniqueRoleName($uniqueRoleName);
            $role_name         = \ExternalModules\ExternalModules::getRoleName($pid, $role_id);
            $role_rights       = $module->getRoleRights($role_id);
            $acceptable_rights = $module->getAcceptableRights($username);
            $these_bad_rights  = $module->checkProposedRights($acceptable_rights, $role_rights);
            // We ignore expired users
            $userExpired = $module->isUserExpired($username, $pid);
            if ( !empty($these_bad_rights) && !$userExpired ) {
                $bad_rights[$role_name][$username] = [
                    "SAG"    => $sag["role_name"],
                    "rights" => $these_bad_rights
                ];
            }
        }
        if ( empty($bad_rights) ) {
            ob_start(function ($str) use ($module, $pid, $data) {
                try {
                    $imported    = $_SESSION["imported"] === "userroleMapping";
                    $error_count = sizeof($_SESSION["errors"]) ?? 0;
                    $succeeded   = $imported && $error_count === 0;
                    if ( $succeeded ) {
                        $data_values = "";
                        $logTable    = $module->framework->getProject($pid)->getLogTable();
                        $redcap_user = $module->getUser()->getUsername();
                        foreach ( $data as $this_assignment ) {
                            $username         = $this_assignment["username"];
                            $unique_role_name = $this_assignment["unique_role_name"];
                            $unique_role_name = $unique_role_name == '' ? 'None' : $unique_role_name;
                            $role_id          = $module->getRoleIdFromUniqueRoleName($unique_role_name);
                            $role_label       = $module->getRoleLabel($role_id) ?? 'None';
                            $data_values      = "user = '" . $username . "'\nrole = '" . $role_label . "'\nunique_role_name = '" . $unique_role_name . "'";

                            $sql    = "SELECT log_event_id FROM $logTable WHERE project_id = ? AND user = ? AND page = 'ExternalModules/index.php' AND object_type = 'redcap_user_rights' AND pk = ? AND event = 'INSERT' AND TIMESTAMPDIFF(SECOND,ts,NOW()) <= 10 ORDER BY ts DESC";
                            $params = [ $pid, $redcap_user, $username ];

                            $result       = $module->framework->query($sql, $params);
                            $log_event_id = $result->fetch_assoc()["log_event_id"];

                            if ( !empty($log_event_id) ) {
                                $module->framework->query("UPDATE $logTable SET data_values = ? WHERE log_event_id = ?", [ $data_values, $log_event_id ]);
                            } else {
                                \Logging::logEvent(
                                    '',
                                    "redcap_user_rights",
                                    "INSERT",
                                    $username,
                                    $data_values,
                                    "Assign user to role",
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
                    $module->log("Error logging user role assignment (csv import)", [ "error" => $e->getMessage() ]);
                }
            });
            $module->framework->log('User Rights Import: Importing role assignments', [ "roles" => json_encode($data) ]);
            require_once $scriptPath;
            ob_end_flush(); // End buffering and clean up
        } else {
            $_SESSION['SUR_imported']   = 'roleassignments';
            $_SESSION['SUR_bad_rights'] = json_encode($bad_rights);
            redirect(APP_PATH_WEBROOT . 'UserRights/index.php?pid=' . PROJECT_ID);
        }
    } else {

        $bad_rights         = [];
        $all_current_rights = [];
        $all_role_ids_orig  = array_keys(\UserRights::getRoles($pid));
        foreach ( $data as $key => $this_role ) {
            $role_label = $this_role["role_label"];
            $role_id    = $module->getRoleIdFromUniqueRoleName($this_role["unique_role_name"]);
            if ( isset($role_id) ) {
                $all_current_rights[$role_id] = $module->getRoleRightsRaw($role_id);
            }
            $usersInRole = $module->getUsersInRole($pid, $role_id);
            if ( isset($this_role['forms']) && $this_role['forms'] != '' ) {
                foreach ( explode(",", $this_role['forms']) as $this_pair ) {
                    list( $this_form, $this_right )  = explode(":", $this_pair, 2);
                    $this_role['form-' . $this_form] = $this_right;
                }
                unset($this_role['forms']);
            }
            if ( isset($this_role['forms_export']) && $this_role['forms_export'] != '' ) {
                foreach ( explode(",", $this_role['forms_export']) as $this_pair ) {
                    list( $this_form, $this_right )         = explode(":", $this_pair, 2);
                    $this_role['export-form-' . $this_form] = $this_right;
                }
                unset($this_role['forms_export']);
            }
            $this_role = array_filter($this_role, function ($value, $key) {
                return ($value != 0);
            }, ARRAY_FILTER_USE_BOTH);

            $these_bad_rights = [];
            foreach ( $usersInRole as $username ) {
                $sag_id            = $module->getUserSystemRole($username);
                $sag               = $module->getSystemRoleRightsById($sag_id);
                $acceptable_rights = $module->getAcceptableRights($username);
                $user_bad_rights   = $module->checkProposedRights($acceptable_rights, $this_role);
                // We ignore expired users
                $userExpired = $module->isUserExpired($username, $pid);
                if ( !empty($user_bad_rights) && !$userExpired ) {
                    $these_bad_rights[$username] = [
                        "SAG"    => $sag["role_name"],
                        "rights" => $user_bad_rights
                    ];
                }
            }
            if ( !empty($these_bad_rights) ) {
                $bad_rights[$role_label] = $these_bad_rights;
            }
        }

        if ( empty($bad_rights) ) {
            ob_start(function ($str) use ($all_current_rights, $module, $pid, $all_role_ids_orig) {
                try {
                    $imported    = $_SESSION["imported"] === "userroles";
                    $error_count = sizeof($_SESSION["errors"]) ?? 0;
                    $succeeded   = $imported && $error_count === 0;
                    if ( $succeeded ) {
                        $data_values      = "";
                        $all_role_ids_new = array_keys(\UserRights::getRoles($pid));
                        $logTable         = $module->framework->getProject($pid)->getLogTable();
                        $redcap_user      = $module->getUser()->getUsername();
                        foreach ( $all_role_ids_new as $role_id ) {
                            $newRole     = !in_array($role_id, $all_role_ids_orig, true);
                            $changedRole = in_array($role_id, array_keys($all_current_rights), true);
                            if ( !$newRole && !$changedRole ) {
                                continue;
                            }
                            $pk         = $role_id;
                            $role_label = $module->getRoleLabel($role_id);

                            if ( $newRole ) {
                                $description      = 'Add role';
                                $event            = 'INSERT';
                                $current_rights   = [];
                                $orig_data_values = "role = '" . $role_label . "'";
                                $sql              = "SELECT log_event_id FROM $logTable WHERE project_id = ? AND user = ? AND page = 'ExternalModules/index.php' AND object_type = 'redcap_user_rights' AND pk IS NULL AND event = 'INSERT' AND data_values = ? AND TIMESTAMPDIFF(SECOND,ts,NOW()) <= 10 ORDER BY ts DESC";
                                $params           = [ $pid, $redcap_user, $orig_data_values ];
                            } else {
                                $description    = "Edit role";
                                $event          = "update";
                                $current_rights = $all_current_rights[$role_id];
                                $sql            = "SELECT log_event_id FROM $logTable WHERE project_id = ? AND user = ? AND page = 'ExternalModules/index.php' AND object_type = 'redcap_user_roles' AND pk = ? AND event = 'UPDATE' AND TIMESTAMPDIFF(SECOND,ts,NOW()) <= 10 ORDER BY ts DESC";
                                $params         = [ $pid, $redcap_user, $pk ];
                            }

                            $updated_rights = $module->getRoleRightsRaw($role_id) ?? [];
                            $changes        = json_encode(array_diff_assoc($updated_rights, $current_rights), JSON_PRETTY_PRINT);
                            $changes        = $changes === "[]" ? "None" : $changes;
                            $data_values    = "role = '$role_label'\nchanges = $changes\n\n";

                            $result       = $module->framework->query($sql, $params);
                            $log_event_id = $result->fetch_assoc()["log_event_id"];

                            if ( !empty($log_event_id) ) {
                                $module->framework->query("UPDATE $logTable SET data_values = ?, pk = ? WHERE log_event_id = ?", [ $data_values, $pk, $log_event_id ]);
                            } else {
                                \Logging::logEvent(
                                    '',
                                    "redcap_user_roles",
                                    $event,
                                    $pk,
                                    $data_values,
                                    $description,
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
                    $module->log("Error logging user role edit (csv import)", [ "error" => $e->getMessage() ]);
                }
            });
            $module->framework->log('User Rights Import: Importing roles', [ "roles" => json_encode($data) ]);
            require_once $scriptPath;
            ob_end_flush(); // End buffering and clean up
        } else {
            $_SESSION['SUR_imported']   = 'roles';
            $_SESSION['SUR_bad_rights'] = json_encode($bad_rights);
            redirect(APP_PATH_WEBROOT . 'UserRights/index.php?pid=' . PROJECT_ID);
        }
    }
}