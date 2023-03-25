<?php

namespace YaleREDCap\SystemUserRights;

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    exit;
}

$data = filter_input_array(INPUT_POST, FILTER_SANITIZE_ENCODED);

$submit_action = $data["submit-action"]; // add_user, edit_user, delete_user, add_role, edit_role, delete_role, copy_role

$user = $data["user"];
$pid = $module->getProjectId();

$scriptPath = $module->getSafePath('UserRights/edit_user.php', APP_PATH_DOCROOT);

if (in_array($submit_action, ["delete_user", "add_role", "delete_role", "copy_role"])) {
    require $scriptPath;
    exit;
}

if (in_array($submit_action, ["add_user", "edit_user"])) {
    $acceptable_rights = $module->getAcceptableRights($user);
    $bad_rights = $module->checkProposedRights($acceptable_rights, $data);
    $current_rights = $module->getCurrentRights($user, $pid);
    $requested_rights = $module->filterPermissions($data);
    $errors = !empty($bad_rights);

    if ($errors === false) {
        $module->log("User was edited", ["user" => $user]);
        $action_info = [
            "action" => $submit_action,
            "rights" => $requested_rights,
            "currentRights" => $current_rights,
            "user" => $user,
            "project_id" => $pid
        ];

        ob_start(function ($str) use ($action_info, $module) {
            $succeeded = strpos($str, "<div class='userSaveMsg") !== false;
            if ($succeeded) {
                $action = $action_info["submit_action"] === "add_user" ? "Add user" : "Update user";
                $module->log('ok', [
                    "thing" => json_encode(array_diff_assoc($module->getCurrentRights($action_info["user"], $action_info["project_id"]), $action_info["currentRights"]))
                ]);
                \Logging::logEvent(
                    '',                                                                 // SQL
                    "redcap_user_rights",                                               // table
                    "update",                                                           // event
                    $action_info["user"],                                               // record
                    "user = '" . $action_info["user"] . "'\nrights added = " . json_encode($action_info["rights"]),  // display
                    $action,                                                            // descrip
                    "",                                                                 // change_reason
                    "",                                                                 // userid_override
                    "",                                                                 // project_id_override
                    true,                                                               // useNOW
                    null,                                                               // event_id_override
                    null,                                                               // instance
                    false                                                               // bulkProcessing
                );
            }
            return $str;
        }); // Start output buffering
        require_once $scriptPath;
        ob_end_flush(); // End buffering and clean up
    } else {
        echo json_encode(["error" => true, "bad_rights" => ["$user" => $bad_rights]]);
    }
    exit;
}

if ($submit_action === "edit_role") {
    if (!isset($data["role_name"]) || $data["role_name"] == "") {
        exit;
    }

    $usersInRole = $module->getUsersInRole($pid, $data["user"]);
    $bad_rights = [];
    foreach ($usersInRole as $username) {
        $acceptable_rights = $module->getAcceptableRights($username);
        $these_bad_rights = $module->checkProposedRights($acceptable_rights, $data);
        if (!empty($these_bad_rights)) {
            $bad_rights[$username] = $these_bad_rights;
        }
    }
    if (empty($bad_rights)) {
        require $scriptPath;
    } else {
        echo json_encode(["error" => true, "bad_rights" => $bad_rights, "role" => $data["role_name"]]);
    }
    exit;
}


/**
 * "submit-action", "expiration", "group_role", "user", "redcap_csrf_token", "role_name_edit"
 * 
 * 
 */
