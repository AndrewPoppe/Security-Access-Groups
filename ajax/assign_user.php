<?php

namespace YaleREDCap\SecurityAccessGroups;

/** @var SecurityAccessGroups $module */

$scriptPath = $module->getSafePath('UserRights/assign_user.php', APP_PATH_DOCROOT);

if ( $_SERVER["REQUEST_METHOD"] !== "POST" ) {
    require $scriptPath;
    exit;
}

$data     = filter_input_array(INPUT_POST, FILTER_SANITIZE_ENCODED);
$username = $data["username"];
$role_id  = $data["role_id"];

// We don't care if the user is being removed from a role.
if ( $role_id == 0 ) {
    require $scriptPath;
    exit;
}

$role_label       = $module->getRoleLabel($role_id);
$unique_role_name = $module->getUniqueRoleNameFromRoleId($role_id);
$project_id       = $module->framework->getProjectId();

$role_rights       = $module->getRoleRights($role_id);
$acceptable_rights = $module->getAcceptableRights($username);

$bad_rights = $module->checkProposedRights($acceptable_rights, $role_rights);
$errors     = !empty($bad_rights);

// We ignore expired users
$userExpired = $module->isUserExpired($username, $project_id);

if ( $errors === false || $userExpired ) {
    $info = [
        "project_id"       => $project_id,
        "username"         => $username,
        "role_id"          => $role_id,
        "role_label"       => $role_label,
        "unique_role_name" => $unique_role_name
    ];
    ob_start(function ($str) use ($info, $module) {
        try {
            $succeeded = strpos($str, "userSaveMsg darkgreen") !== false; // is there no better way?
            $module->log('ok', [ 'str' => $str, 'succeeded' => $succeeded ]);
            if ( $succeeded ) {
                $data_values = "user = '" . $info["username"] . "'\nrole = '" . $info["role_label"] . "'\nunique_role_name = '" . $info["unique_role_name"] . "'";

                $logTable     = $module->framework->getProject($info["project_id"])->getLogTable();
                $sql          = "SELECT log_event_id FROM $logTable WHERE project_id = ? AND user = ? AND page = 'ExternalModules/index.php' AND object_type = 'redcap_user_rights' AND pk = ? AND event IN ('INSERT','UPDATE') AND description = 'Assign user to role' AND TIMESTAMPDIFF(SECOND,ts,NOW()) <= 10 ORDER BY ts DESC";
                $params       = [ $info["project_id"], $module->framework->getUser()->getUsername(), $info["username"] ];
                $result       = $module->query($sql, $params);
                $log_event_id = $result->fetch_assoc()["log_event_id"];
                if ( !empty($log_event_id) ) {
                    $module->query("UPDATE $logTable SET data_values = ? WHERE log_event_id = ?", [ $data_values, $log_event_id ]);
                } else {
                    \Logging::logEvent(
                        '',
                        "redcap_user_rights",
                        "INSERT",
                        $info["username"],
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
        } catch ( \Throwable $e ) {
            $module->log("Error logging user role mapping", [ "error" => $e->getMessage() ]);
        }
        return $str;
    });
    require_once $scriptPath;
    ob_end_flush(); // End buffering and clean up
} else {
    echo json_encode([ "error" => true, "bad_rights" => [ "$username" => $bad_rights ], "role" => $role_name ]);
}
exit;