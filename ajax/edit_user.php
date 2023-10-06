<?php

namespace YaleREDCap\SecurityAccessGroups;

/** @var SecurityAccessGroups $module */

if ( $_SERVER['REQUEST_METHOD'] !== 'POST' ) {
    http_response_code(405);
    exit;
}

$data = filter_input_array(INPUT_POST, FILTER_SANITIZE_SPECIAL_CHARS);

$submitAction = $data['submit-action']; // add_user, edit_user, delete_user, add_role, edit_role, delete_role, copy_role

$user = $data['user'];
$pid  = $module->getProjectId();

$scriptPath = $module->getSafePath('UserRights/edit_user.php', APP_PATH_DOCROOT);

if ( in_array($submitAction, [ 'delete_user', 'delete_role' ], true) ) {
    require_once $scriptPath;
    exit;
}

$rightsUtilities = new RightsUtilities($module);

if ( in_array($submitAction, [ 'add_role', 'copy_role' ], true) ) {
    ob_start(function ($str) use ($submitAction, $module, $data) {
        try {
            $role           = $submitAction == 'add_role' ? new Role($module, $data['user'], null, $data['role_name']) : new Role($module, $data['user']);
            $action         = $submitAction == 'add_role' ? "Add role" : "Copy role";
            $rights         = json_encode($role->getRoleRightsRaw() ?? [], JSON_PRETTY_PRINT);
            $roleId         = $submitAction == 'add_role' ? '0' : $role->getRoleId();
            $roleName       = $role->getRoleName();
            $uniqueRoleName = $role->getUniqueRoleName();
            $dataValues     = "role = '" . $roleName . "'\nunique role name = '" . $uniqueRoleName . "'\nrights = " . $rights;

            $logTable   = $module->framework->getProject()->getLogTable();
            $projectId  = $module->framework->getProjectId();
            $sql        = "SELECT log_event_id FROM $logTable WHERE project_id = ? AND user = ? AND page = 'ExternalModules/index.php' AND object_type = 'redcap_user_rights' AND pk = ? AND event = 'INSERT' AND TIMESTAMPDIFF(SECOND,ts,NOW()) <= 10 ORDER BY ts DESC";
            $params     = [ $projectId, $module->getUser()->getUsername(), $roleId ];
            $result     = $module->query($sql, $params);
            $logEventId = intval($result->fetch_assoc()["log_event_id"]);
            if ( $logEventId != 0 ) {
                $module->query("UPDATE $logTable SET data_values = ? WHERE log_event_id = ?", [ $dataValues, $logEventId ]);
            } else {
                \Logging::logEvent(
                    '',
                    "redcap_user_rights",
                    "insert",
                    $roleId,
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
        } catch ( \Throwable $e ) {
            $module->log("Error logging role creation or copy", [ "error" => $e->getMessage() ]);
        }
        return $str;
    });
    require_once $scriptPath;
    ob_end_flush(); // End buffering and clean up
    exit;
}



if ( in_array($submitAction, [ 'add_user', 'edit_user' ]) ) {
    $sagUser          = new SAGUser($module, $user);
    $acceptableRights = $sagUser->getAcceptableRights();
    $rightsChecker    = new RightsChecker($module, $data, $acceptableRights, $module->framework->getProjectId());
    $badRights        = $rightsChecker->checkRights();
    $currentRights    = $sagUser->getCurrentRights($pid);
    $requestedRights  = $rightsUtilities->filterPermissions($data);
    $errors           = !empty($badRights);
    $sag              = $sagUser->getUserSag();

    // We ignore expired users, unless the request unexpires them
    $userExpired         = $sagUser->isUserExpired($module->getProjectId());
    $requestedExpiration = urldecode($data['expiration']);
    $requestedUnexpired  = empty($requestedExpiration) || (strtotime($requestedExpiration) >= strtotime('today'));
    if ( $userExpired && !$requestedUnexpired ) {
        $ignore = true;
    }

    if ( $errors === false || $ignore === true ) {
        $module->log('Adding/Editing User', [ 'user' => $user, 'requested_rights' => json_encode($requestedRights) ]);
        $actionInfo = [
            'action'        => $submitAction,
            'rights'        => $requestedRights,
            'currentRights' => $currentRights,
            'user'          => $user,
            'project_id'    => $pid,
            'sagUser'       => $sagUser
        ];

        ob_start(function ($str) use ($actionInfo, $module) {
            try {
                $succeeded = strpos($str, '<div class=\'userSaveMsg') !== false; // is there no better way?
                if ( $succeeded ) {
                    $action         = $actionInfo['submit_action'] === 'add_user' ? 'Add user' : 'Update user';
                    $updatedRights  = $actionInfo['sagUser']->getCurrentRights($actionInfo['project_id']) ?? [];
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
        http_response_code(403);
        echo json_encode([ 'error' => true, 'bad_rights' => [ "$user" => [ 'SAG' => $sag->sagName, 'rights' => $badRights ] ] ]);
    }
    exit;
}

if ( $submitAction === "edit_role" ) {
    if ( !isset($data["role_name"]) || $data["role_name"] == "" ) {
        exit;
    }
    $role        = new Role($module, $data["user"]);
    $usersInRole = $role->getUsersInRole($pid);
    $badRights   = [];
    foreach ( $usersInRole as $username ) {
        $sagUser          = new SAGUser($module, $username);
        $acceptableRights = $sagUser->getAcceptableRights();
        $rightsChecker    = new RightsChecker($module, $data, $acceptableRights, $module->framework->getProjectId());
        $theseBadRights   = $rightsChecker->checkRights();
        $sag              = $sagUser->getUserSag();

        // We ignore expired users
        $userExpired = $sagUser->isUserExpired($module->getProjectId());

        if ( !empty($theseBadRights) && !$userExpired ) {
            $badRights[$username] = [
                "role"   => $role->getRoleName(),
                "SAG"    => $sag->sagName,
                "rights" => $theseBadRights
            ];
        }
    }
    if ( empty($badRights) ) {
        $requestedRights = $rightsUtilities->filterPermissions($data);
        $module->log("Editing Role", [ "role" => $role->getRoleId(), "requested_rights" => json_encode($requestedRights) ]);
        $actionInfo = [
            "action"        => $submitAction,
            "rights"        => $requestedRights,
            "currentRights" => $role->getRoleRightsRaw(),
            "role"          => $role->getRoleId(),
            "project_id"    => $pid
        ];

        ob_start(function ($str) use ($actionInfo, $module, $role) {
            try {
                $succeeded = strpos($str, "<div class='userSaveMsg") !== false; // is there no better way?
                if ( $succeeded ) {
                    $action         = "Update role";
                    $updatedRights  = $role->getRoleRightsRaw() ?? [];
                    $roleName       = $updatedRights["role_name"];
                    $previousRights = $actionInfo["currentRights"] ?? [];
                    $changes        = json_encode(array_diff_assoc($updatedRights, $previousRights), JSON_PRETTY_PRINT);
                    $dataValues     = "role = '" . $roleName . "'\nunique role name = '" . $role->getUniqueRoleName() . "'\nchanges = " . $changes;

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
                            "update",
                            $actionInfo["role"],
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
        http_response_code(403);
        echo json_encode([ "error" => true, "bad_rights" => $badRights, "role" => $role->getRoleName() ]);
    }
    exit;
}