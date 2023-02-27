<?php

namespace YaleREDCap\SystemUserRights;

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    exit;
}

// $runOriginalScript = function () use ($module) {
//     require $module->getSafePath('UserRights/edit_user.php', APP_PATH_DOCROOT);
//     exit;
// };
// $runOriginalScript();

$data = filter_input_array(INPUT_POST, FILTER_SANITIZE_ENCODED);

$submit_action = $data["submit-action"]; // add_user, edit_user, delete_user, add_role, edit_role, delete_role, copy_role

$user = $data["user"];
$pid = $module->getProjectId();

if (in_array($submit_action, ["delete_user", "add_role", "delete_role", "copy_role"])) {
    echo json_encode([]);
    exit;
}

if (in_array($submit_action, ["add_user", "edit_user"])) {
    $acceptable_rights = $module->getAcceptableRights($username, $pid);
    if (($key = array_search("design", $acceptable_rights)) !== false) {
        unset($acceptable_rights[$key]);
    }
    $bad_rights = [];
    foreach ($data as $right => $value) {
        if (str_starts_with($right, "form-") or str_starts_with($right, "export-form-")) {
            continue;
        }
        if (in_array($right, ["user", "submit-action", "role_name", "redcap_csrf_token", "expiration", "group_role"])) {
            continue;
        }
        if (!in_array($right, $acceptable_rights)) {
            $bad_rights[] = $right;
        }
    }

    $errors = !empty($bad_rights);

    if ($errors === false) {
        require $module->getSafePath('UserRights/edit_user.php', APP_PATH_DOCROOT);
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
        $acceptable_rights = $module->getAcceptableRights($username, $pid);
        // CHECK PROPOSED ROLE RIGHTS AGAINST THESE
        // if (no good) {
        // $bad_rights[] = [
        //     "username" => $username,
        //     "rights" => array of bad rights
        // ];
        //}
    }
    if (empty($bad_rights)) {
        require $module->getSafePath('UserRights/edit_user.php', APP_PATH_DOCROOT);
    }
    exit; // PROBABLY WANT TO SEND BACK SOMETHING HERE
}
