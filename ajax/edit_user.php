<?php

namespace YaleREDCap\SecurityAccessGroups;

/** @var SecurityAccessGroups $module */

if ( $_SERVER["REQUEST_METHOD"] !== "POST" ) {
    exit;
}

$data = filter_input_array(INPUT_POST, FILTER_SANITIZE_ENCODED);

$submit_action = $data["submit-action"]; // add_user, edit_user, delete_user, add_role, edit_role, delete_role, copy_role

$user = $data["user"];
$pid  = $module->getProjectId();

$scriptPath = $module->getSafePath('UserRights/edit_user.php', APP_PATH_DOCROOT);

if ( in_array($submit_action, [ "delete_user", "add_role", "delete_role", "copy_role" ]) ) {
    require $scriptPath;
    exit;
}

if ( in_array($submit_action, [ "add_user", "edit_user" ]) ) {
    $acceptable_rights = $module->getAcceptableRights($user);
    $sag_id            = $module->getUserSystemRole($user);
    $sag               = $module->getSystemRoleRightsById($sag_id);
    $bad_rights        = $module->checkProposedRights($acceptable_rights, $data);
    $current_rights    = $module->getCurrentRights($user, $pid);
    $requested_rights  = $module->filterPermissions($data);
    $errors            = !empty($bad_rights);

    // We ignore expired users, unless the request unexpires them
    $userExpired         = $module->isUserExpired($user, $module->getProjectId());
    $requestedExpiration = urldecode($data["expiration"]);
    $requestedUnexpired  = empty($requestedExpiration) || (strtotime($requestedExpiration) >= strtotime('today'));
    if ( $userExpired && !$requestedUnexpired ) {
        $ignore = true;
    }

    if ( $errors === false || $ignore === true ) {
        $module->log("Adding/Editing User", [ "user" => $user, "requested_rights" => json_encode($requested_rights) ]);
        $action_info = [
            "action"        => $submit_action,
            "rights"        => $requested_rights,
            "currentRights" => $current_rights,
            "user"          => $user,
            "project_id"    => $pid
        ];

        ob_start(function ($str) use ($action_info, $module) {
            try {
                $succeeded = strpos($str, "<div class='userSaveMsg") !== false; // is there no better way?
                if ( $succeeded ) {
                    $action          = $action_info["submit_action"] === "add_user" ? "Add user" : "Update user";
                    $updated_rights  = $module->getCurrentRights($action_info["user"], $action_info["project_id"]) ?? [];
                    $previous_rights = $action_info["currentRights"] ?? [];
                    $changes         = json_encode(array_diff_assoc($updated_rights, $previous_rights), JSON_PRETTY_PRINT);
                    $data_values     = "user = '" . $action_info["user"] . "'\nchanges = " . $changes;

                    $logTable     = $module->framework->getProject($action_info["project_id"])->getLogTable();
                    $sql          = "SELECT log_event_id FROM $logTable WHERE project_id = ? AND user = ? AND page = 'ExternalModules/index.php' AND object_type = 'redcap_user_rights' AND pk = ? AND event IN ('INSERT','UPDATE') AND TIMESTAMPDIFF(SECOND,ts,NOW()) <= 10 ORDER BY ts DESC";
                    $params       = [ $action_info["project_id"], $module->getUser()->getUsername(), $action_info["user"] ];
                    $result       = $module->query($sql, $params);
                    $log_event_id = intval($result->fetch_assoc()["log_event_id"]);
                    if ( $log_event_id != 0 ) {
                        $module->query("UPDATE $logTable SET data_values = ? WHERE log_event_id = ?", [ $data_values, $log_event_id ]);
                    } else {
                        \Logging::logEvent(
                            '',
                            "redcap_user_rights",
                            "update",
                            $action_info["user"],
                            $data_values,
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
                $module->log("Error logging user edit", [ "error" => $e->getMessage() ]);
            }
            return $str;
        });
        require_once $scriptPath;
        ob_end_flush(); // End buffering and clean up
    } else {
        echo json_encode([ "error" => true, "bad_rights" => [ "$user" => [ "SAG" => $sag["role_name"], "rights" => $bad_rights ] ] ]);
    }
    exit;
}

if ( $submit_action === "edit_role" ) {
    if ( !isset($data["role_name"]) || $data["role_name"] == "" ) {
        exit;
    }
    $role        = $data["user"];
    $usersInRole = $module->getUsersInRole($pid, $role);
    $bad_rights  = [];
    foreach ( $usersInRole as $username ) {
        $acceptable_rights = $module->getAcceptableRights($username);
        $these_bad_rights  = $module->checkProposedRights($acceptable_rights, $data);
        $sag_id            = $module->getUserSystemRole($username);
        $sag               = $module->getSystemRoleRightsById($sag_id);

        // We ignore expired users
        $userExpired = $module->isUserExpired($username, $module->getProjectId());

        if ( !empty($these_bad_rights) && !$userExpired ) {
            $bad_rights[$username] = [
                "SAG"    => $sag["role_name"],
                "rights" => $these_bad_rights
            ];
        }
    }
    if ( empty($bad_rights) ) {
        $requested_rights = $module->filterPermissions($data);
        $module->log("Editing Role", [ "role" => $role, "requested_rights" => json_encode($requested_rights) ]);
        $action_info = [
            "action"        => $submit_action,
            "rights"        => $requested_rights,
            "currentRights" => $module->getRoleRightsRaw($role),
            "role"          => $role,
            "project_id"    => $pid
        ];

        ob_start(function ($str) use ($action_info, $module) {
            try {
                $succeeded = strpos($str, "<div class='userSaveMsg") !== false; // is there no better way?
                if ( $succeeded ) {
                    $action          = "Update role";
                    $updated_rights  = $module->getRoleRightsRaw($action_info["role"]) ?? [];
                    $role_name       = $updated_rights["role_name"];
                    $previous_rights = $action_info["currentRights"] ?? [];
                    $changes         = json_encode(array_diff_assoc($updated_rights, $previous_rights), JSON_PRETTY_PRINT);
                    $data_values     = "role = '" . $role_name . "'\nchanges = " . $changes;

                    $logTable     = $module->framework->getProject($action_info["project_id"])->getLogTable();
                    $sql          = "SELECT log_event_id FROM $logTable WHERE project_id = ? AND user = ? AND page = 'ExternalModules/index.php' AND object_type = 'redcap_user_rights' AND pk = ? AND event IN ('INSERT','UPDATE') AND TIMESTAMPDIFF(SECOND,ts,NOW()) <= 10 ORDER BY ts DESC";
                    $params       = [ $action_info["project_id"], $module->getUser()->getUsername(), $action_info["role"] ];
                    $result       = $module->query($sql, $params);
                    $log_event_id = intval($result->fetch_assoc()["log_event_id"]);
                    if ( $log_event_id != 0 ) {
                        $module->query("UPDATE $logTable SET data_values = ? WHERE log_event_id = ?", [ $data_values, $log_event_id ]);
                    } else {
                        \Logging::logEvent(
                            '',
                            "redcap_user_roles",
                            "update", $action_info["role"],
                            $data_values,
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
        echo json_encode([ "error" => true, "bad_rights" => $bad_rights, "role" => $data["role_name"] ]);
    }
    exit;
}