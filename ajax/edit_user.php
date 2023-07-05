<?php

namespace YaleREDCap\SecurityAccessGroups;

/** @var SecurityAccessGroups $module */

if ( $_SERVER['REQUEST_METHOD'] !== 'POST' ) {
    http_response_code(405);
    exit;
}

$data = filter_input_array(INPUT_POST, FILTER_SANITIZE_ENCODED);

$submitAction = $data['submit-action']; // add_user, edit_user, delete_user, add_role, edit_role, delete_role, copy_role

$user = $data['user'];
$pid  = $module->getProjectId();

$scriptPath = $module->getSafePath('UserRights/edit_user.php', APP_PATH_DOCROOT);

if ( in_array($submitAction, [ 'delete_user', 'add_role', 'delete_role', 'copy_role' ]) ) {
    require_once $scriptPath;
    exit;
}

if ( in_array($submitAction, [ 'add_user', 'edit_user' ]) ) {
    $acceptable_rights = $module->getAcceptableRights($user);
    $sag_id            = $module->getUserSystemRole($user);
    $sag               = $module->getSystemRoleRightsById($sag_id);
    $badRights         = $module->checkProposedRights($acceptable_rights, $data);
    $current_rights    = $module->getCurrentRights($user, $pid);
    $requested_rights  = $module->filterPermissions($data);
    $errors            = !empty($badRights);

    // We ignore expired users, unless the request unexpires them
    $userExpired         = $module->isUserExpired($user, $module->getProjectId());
    $requestedExpiration = urldecode($data['expiration']);
    $requestedUnexpired  = empty($requestedExpiration) || (strtotime($requestedExpiration) >= strtotime('today'));
    if ( $userExpired && !$requestedUnexpired ) {
        $ignore = true;
    }

    if ( $errors === false || $ignore === true ) {
        $module->log('Adding/Editing User', [ 'user' => $user, 'requested_rights' => json_encode($requested_rights) ]);
        $actionInfo = [
            'action'        => $submitAction,
            'rights'        => $requested_rights,
            'currentRights' => $current_rights,
            'user'          => $user,
            'project_id'    => $pid
        ];

        ob_start(function ($str) use ($actionInfo, $module) {
            try {
                $succeeded = strpos($str, '<div class=\'userSaveMsg') !== false; // is there no better way?
                if ( $succeeded ) {
                    $action         = $actionInfo['submit_action'] === 'add_user' ? 'Add user' : 'Update user';
                    $updatedRights  = $module->getCurrentRights($actionInfo['user'], $actionInfo['project_id']) ?? [];
                    $previousRights = $actionInfo['currentRights'] ?? [];
                    $changes        = json_encode(array_diff_assoc($updatedRights, $previousRights), JSON_PRETTY_PRINT);
                    $dataValues     = "user = '" . $actionInfo['user'] . "'\nchanges = " . $changes;

                    $logTable   = $module->framework->getProject($actionInfo['project_id'])->getLogTable();
                    $sql        = "SELECT log_event_id FROM $logTable WHERE project_id = ? AND user = ? AND page = 'ExternalModules/index.php' AND object_type = 'redcap_user_rights' AND pk = ? AND event IN ('INSERT','UPDATE') AND TIMESTAMPDIFF(SECOND,ts,NOW()) <= 10 ORDER BY ts DESC";
                    $params     = [ $actionInfo['project_id'], $module->getUser()->getUsername(), $actionInfo['user'] ];
                    $result     = $module->query($sql, $params);
                    $logEventId = intval($result->fetch_assoc()['log_event_id']);
                    if ( $logEventId != 0 ) {
                        $module->query("UPDATE $logTable SET data_values = ? WHERE log_event_id = ?", [ $dataValues, $logEventId ]);
                    } else {
                        \Logging::logEvent(
                            '',
                            'redcap_user_rights',
                            'update',
                            $actionInfo['user'],
                            $dataValues,
                            $action,
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
                $module->log('Error logging user edit', [ 'error' => $e->getMessage() ]);
            }
            return $str;
        });
        require_once $scriptPath;
        ob_end_flush(); // End buffering and clean up
    } else {
        echo json_encode([ 'error' => true, 'bad_rights' => [ "$user" => [ 'SAG' => $sag['role_name'], 'rights' => $badRights ] ] ]);
    }
    exit;
}

if ( $submitAction === "edit_role" ) {
    if ( !isset($data["role_name"]) || $data["role_name"] == "" ) {
        exit;
    }
    $role        = $data["user"];
    $usersInRole = $module->getUsersInRole($pid, $role);
    $badRights   = [];
    foreach ( $usersInRole as $username ) {
        $acceptable_rights = $module->getAcceptableRights($username);
        $these_bad_rights  = $module->checkProposedRights($acceptable_rights, $data);
        $sag_id            = $module->getUserSystemRole($username);
        $sag               = $module->getSystemRoleRightsById($sag_id);

        // We ignore expired users
        $userExpired = $module->isUserExpired($username, $module->getProjectId());

        if ( !empty($these_bad_rights) && !$userExpired ) {
            $badRights[$username] = [
                "SAG"    => $sag["role_name"],
                "rights" => $these_bad_rights
            ];
        }
    }
    if ( empty($badRights) ) {
        $requested_rights = $module->filterPermissions($data);
        $module->log("Editing Role", [ "role" => $role, "requested_rights" => json_encode($requested_rights) ]);
        $actionInfo = [
            "action"        => $submitAction,
            "rights"        => $requested_rights,
            "currentRights" => $module->getRoleRightsRaw($role),
            "role"          => $role,
            "project_id"    => $pid
        ];

        ob_start(function ($str) use ($actionInfo, $module) {
            try {
                $succeeded = strpos($str, "<div class='userSaveMsg") !== false; // is there no better way?
                if ( $succeeded ) {
                    $action         = "Update role";
                    $updatedRights  = $module->getRoleRightsRaw($actionInfo["role"]) ?? [];
                    $roleName       = $updatedRights["role_name"];
                    $previousRights = $actionInfo["currentRights"] ?? [];
                    $changes        = json_encode(array_diff_assoc($updatedRights, $previousRights), JSON_PRETTY_PRINT);
                    $dataValues     = "role = '" . $roleName . "'\nchanges = " . $changes;

                    $logTable   = $module->framework->getProject($actionInfo["project_id"])->getLogTable();
                    $sql        = "SELECT log_event_id FROM $logTable WHERE project_id = ? AND user = ? AND page = 'ExternalModules/index.php' AND object_type = 'redcap_user_rights' AND pk = ? AND event IN ('INSERT','UPDATE') AND TIMESTAMPDIFF(SECOND,ts,NOW()) <= 10 ORDER BY ts DESC";
                    $params     = [ $actionInfo["project_id"], $module->getUser()->getUsername(), $actionInfo["role"] ];
                    $result     = $module->query($sql, $params);
                    $logEventId = intval($result->fetch_assoc()["log_event_id"]);
                    if ( $logEventId != 0 ) {
                        $module->query("UPDATE $logTable SET data_values = ? WHERE log_event_id = ?", [ $dataValues, $logEventId ]);
                    } else {
                        \Logging::logEvent(
                            '',
                            "redcap_user_roles",
                            "update", $actionInfo["role"],
                            $dataValues,
                            $action,
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
            } catch ( \Throwable $e ) {
                $module->log("Error logging role edit", [ "error" => $e->getMessage() ]);
            }
            return $str;
        });
        require_once $scriptPath;
        ob_end_flush(); // End buffering and clean up
    } else {
        echo json_encode([ "error" => true, "bad_rights" => $badRights, "role" => $data["role_name"] ]);
    }
    exit;
}