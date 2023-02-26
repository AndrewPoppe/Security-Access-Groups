<?php

namespace YaleREDCap\SystemUserRights;

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    exit;
}

$data = filter_input_array(INPUT_POST, FILTER_SANITIZE_ENCODED);

$submit_action = $data["submit-action"]; // add_user, edit_user, delete_user, add_role, edit_role, delete_role, copy_role

if (in_array($submit_action, ["delete_user", "delete_role", "copy_role"])) {
    echo json_encode([]);
    exit;
}

if (in_array($submit_action, ["add_user", "edit_user"])) {
    //$acceptable
}

$username = $data["username"];

$error = false;
if ($data["design"]) {
    $error = true;
}

echo json_encode(["error" => $error]);
