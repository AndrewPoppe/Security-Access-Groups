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
    $acceptable_rights = $module->getAcceptableRights($username);
    $bad_rights = $module->checkProposedRights($acceptable_rights, $data);

    $errors = !empty($bad_rights);

    if ($errors === false) {
        require $scriptPath;
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
