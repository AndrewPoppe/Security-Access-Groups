<?php

namespace YaleREDCap\SecurityAccessGroups;

/** @var SecurityAccessGroups $module */

$scriptPath = $module->framework->getSafePath('UserRights/import_export_roles.php', APP_PATH_DOCROOT);

if ( $_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['csv_content']) ) {
    require_once $scriptPath;
    exit;
}
require_once $module->getSafePath('Config/init_functions.php', APP_PATH_DOCROOT);
if ( isset($_POST['csv_content']) && $_POST['csv_content'] != '' ) {

    $csvContent = filter_input(INPUT_POST, 'csv_content');
    $data       = csvToArray(removeBOMfromUTF8($csvContent));
    $pid        = $module->framework->getProjectId();

    if ( $_GET['action'] == 'uploadMapping' ) {
        $badRights = [];
        foreach ( $data as $key => $this_assignment ) {
            $username       = $this_assignment['username'];
            $sagId          = $module->getUserSag($username);
            $sag            = $module->getSagRightsById($sagId);
            $uniqueRoleName = $this_assignment['unique_role_name'];
            if ( $uniqueRoleName == '' ) {
                continue;
            }
            $role             = new Role($module, null, $uniqueRoleName);
            $roleName         = $role->getRoleName();
            $role_rights      = $role->getRoleRights();
            $acceptableRights = $module->getAcceptableRights($username);
            $theseBadRights   = $module->checkProposedRights($acceptableRights, $role_rights);
            // We ignore expired users
            $userExpired = $module->isUserExpired($username, $pid);
            if ( !empty($theseBadRights) && !$userExpired ) {
                $badRights[$roleName][$username] = [
                    'SAG'    => $sag['sag_name'],
                    'rights' => $theseBadRights
                ];
            }
        }
        if ( empty($badRights) ) {
            ob_start(function () use ($module, $pid, $data) {
                try {
                    $imported   = $_SESSION['imported'] === 'userroleMapping';
                    $errorCount = sizeof($_SESSION['errors']) ?? 0;
                    $succeeded  = $imported && $errorCount === 0;
                    if ( $succeeded ) {
                        $dataValues = '';
                        $logTable   = $module->framework->getProject($pid)->getLogTable();
                        $redcapUser = $module->getUser()->getUsername();
                        foreach ( $data as $this_assignment ) {
                            $username       = $this_assignment['username'];
                            $uniqueRoleName = $this_assignment['unique_role_name'];
                            $uniqueRoleName = $uniqueRoleName == '' ? 'None' : $uniqueRoleName;
                            $role           = new Role($module, null, $uniqueRoleName);
                            $roleLabel      = $role->getRoleName() ?? 'None';
                            $dataValues     = "user = '" . $username . "'\nrole = '" . $roleLabel . "'\nunique_role_name = '" . $uniqueRoleName . "'";

                            $sql    = "SELECT log_event_id FROM $logTable WHERE project_id = ? AND user = ? AND page = 'ExternalModules/index.php' AND object_type = 'redcap_user_rights' AND pk = ? AND event = 'INSERT' AND TIMESTAMPDIFF(SECOND,ts,NOW()) <= 10 ORDER BY ts DESC";
                            $params = [ $pid, $redcapUser, $username ];

                            $result     = $module->framework->query($sql, $params);
                            $logEventId = intval($result->fetch_assoc()['log_event_id']);

                            if ( $logEventId != 0 ) {
                                $module->framework->query("UPDATE $logTable SET data_values = ? WHERE log_event_id = ?", [ $dataValues, $logEventId ]);
                            } else {
                                \Logging::logEvent(
                                    '',
                                    'redcap_user_rights',
                                    'INSERT',
                                    $username,
                                    $dataValues,
                                    'Assign user to role',
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
                    $module->log('Error logging user role assignment (csv import)', [ 'error' => $e->getMessage() ]);
                }
            });
            $module->framework->log('User Rights Import: Importing role assignments', [ 'roles' => json_encode($data) ]);
            require_once $scriptPath;
            ob_end_flush(); // End buffering and clean up
        } else {
            $_SESSION['SAG_imported']   = 'roleassignments';
            $_SESSION['SAG_bad_rights'] = json_encode($badRights);
            redirect(APP_PATH_WEBROOT . 'UserRights/index.php?pid=' . PROJECT_ID);
        }
    } else {

        $badRights        = [];
        $allCurrentRights = [];
        $allRoleIdsOrig   = array_keys(\UserRights::getRoles($pid));
        foreach ( $data as $key => $thisRole ) {
            $roleLabel = $thisRole['role_label'];
            $role      = new Role($module, null, $thisRole['unique_role_name']);
            $roleId    = $role->getRoleId();
            if ( isset($roleId) ) {
                $allCurrentRights[$roleId] = $role->getRoleRightsRaw();
            }
            $usersInRole = $role->getUsersInRole($pid);
            if ( isset($thisRole['forms']) && $thisRole['forms'] != '' ) {
                foreach ( explode(',', $thisRole['forms']) as $thisPair ) {
                    list( $thisForm, $thisRight )  = explode(':', $thisPair, 2);
                    $thisRole['form-' . $thisForm] = $thisRight;
                }
                unset($thisRole['forms']);
            }
            if ( isset($thisRole['forms_export']) && $thisRole['forms_export'] != '' ) {
                foreach ( explode(',', $thisRole['forms_export']) as $thisPair ) {
                    list( $thisForm, $thisRight )         = explode(':', $thisPair, 2);
                    $thisRole['export-form-' . $thisForm] = $thisRight;
                }
                unset($thisRole['forms_export']);
            }
            $thisRole = array_filter($thisRole, function ($value) {
                return $value != 0;
            });

            $theseBadRights = [];
            foreach ( $usersInRole as $username ) {
                $sagId            = $module->getUserSag($username);
                $sag              = $module->getSagRightsById($sagId);
                $acceptableRights = $module->getAcceptableRights($username);
                $userBadRights    = $module->checkProposedRights($acceptableRights, $thisRole);
                // We ignore expired users
                $userExpired = $module->isUserExpired($username, $pid);
                if ( !empty($userBadRights) && !$userExpired ) {
                    $theseBadRights[$username] = [
                        'SAG'    => $sag['sag_name'],
                        'rights' => $userBadRights
                    ];
                }
            }
            if ( !empty($theseBadRights) ) {
                $badRights[$roleLabel] = $theseBadRights;
            }
        }

        if ( empty($badRights) ) {
            ob_start(function () use ($allCurrentRights, $module, $pid, $allRoleIdsOrig) {
                try {
                    $imported   = $_SESSION['imported'] === 'userroles';
                    $errorCount = sizeof($_SESSION['errors']) ?? 0;
                    $succeeded  = $imported && $errorCount === 0;
                    if ( !$succeeded ) {
                        return;
                    }
                    $dataValues    = '';
                    $allRoleIdsNew = array_keys(\UserRights::getRoles($pid));
                    $logTable      = $module->framework->getProject($pid)->getLogTable();
                    $redcapUser    = $module->getUser()->getUsername();
                    foreach ( $allRoleIdsNew as $roleId ) {
                        $newRole     = !in_array($roleId, $allRoleIdsOrig, true);
                        $changedRole = in_array($roleId, array_keys($allCurrentRights), true);
                        if ( !$newRole && !$changedRole ) {
                            continue;
                        }
                        $pk        = $roleId;
                        $role      = new Role($module, $roleId);
                        $roleLabel = $role->getRoleName();

                        if ( $newRole ) {
                            $description    = 'Add role';
                            $event          = 'INSERT';
                            $currentRights  = [];
                            $origDataValues = "role = '" . $roleLabel . "'";
                            $sql            = "SELECT log_event_id FROM $logTable WHERE project_id = ? AND user = ? AND page = 'ExternalModules/index.php' AND object_type = 'redcap_user_rights' AND pk IS NULL AND event = 'INSERT' AND data_values = ? AND TIMESTAMPDIFF(SECOND,ts,NOW()) <= 10 ORDER BY ts DESC";
                            $params         = [ $pid, $redcapUser, $origDataValues ];
                        } else {
                            $description   = 'Edit role';
                            $event         = 'UPDATE';
                            $currentRights = $allCurrentRights[$roleId];
                            $sql           = "SELECT log_event_id FROM $logTable WHERE project_id = ? AND user = ? AND page = 'ExternalModules/index.php' AND object_type = 'redcap_user_roles' AND pk = ? AND event = 'UPDATE' AND TIMESTAMPDIFF(SECOND,ts,NOW()) <= 10 ORDER BY ts DESC";
                            $params        = [ $pid, $redcapUser, $pk ];
                        }

                        $updatedRights = $role->getRoleRightsRaw() ?? [];
                        $changes       = json_encode(array_diff_assoc($updatedRights, $currentRights), JSON_PRETTY_PRINT);
                        $changes       = $changes === '[]' ? 'None' : $changes;
                        $dataValues    = "role = '$roleLabel'\nchanges = $changes\n\n";

                        $result     = $module->framework->query($sql, $params);
                        $logEventId = intval($result->fetch_assoc()["log_event_id"]);

                        if ( $logEventId != 0 ) {
                            $module->framework->query("UPDATE $logTable SET data_values = ?, pk = ? WHERE log_event_id = ?", [ $dataValues, $pk, $logEventId ]);
                        } else {
                            \Logging::logEvent(
                                '',
                                'redcap_user_roles',
                                $event,
                                $pk,
                                $dataValues,
                                $description,
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
                } catch ( \Throwable $e ) {
                    $module->log('Error logging user role edit (csv import)', [ 'error' => $e->getMessage() ]);
                }
            });
            $module->framework->log('User Rights Import: Importing roles', [ 'roles' => json_encode($data) ]);
            require_once $scriptPath;
            ob_end_flush(); // End buffering and clean up
        } else {
            $_SESSION['SAG_imported']   = 'roles';
            $_SESSION['SAG_bad_rights'] = json_encode($badRights);
            redirect(APP_PATH_WEBROOT . 'UserRights/index.php?pid=' . PROJECT_ID);
        }
    }
}