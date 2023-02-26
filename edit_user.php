<?php

namespace YaleREDCap\SystemUserRights;

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    exit;
}

$data = filter_input_array(INPUT_POST, FILTER_SANITIZE_ENCODED);

$submit_action = $data["submit-action"]; // add_user, edit_user, delete_user, add_role, edit_role, delete_role, copy_role

$user = $data["user"];
$pid = $module->getProjectId();

if (in_array($submit_action, ["delete_user", "delete_role", "copy_role"])) {
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
    echo json_encode(["error" => !empty($bad_rights), "bad_rights" => $bad_rights]);
    exit;
}



$error = false;
echo json_encode(["error" => $error]);
