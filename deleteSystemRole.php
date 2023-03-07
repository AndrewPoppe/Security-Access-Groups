<?php

namespace YaleREDCap\SystemUserRights;

if (!$module->getUser()->isSuperUser()) {
    http_response_code(401);
    exit;
}

// We're submitting the form to add/edit the role
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $role_id = filter_input(INPUT_POST, "role_id", FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    if (empty($role_id) || !$module->systemRoleExists($role_id)) {
        http_response_code(400);
        echo "The provided role ID was bad.";
        exit;
    }
    $module->log('deleting role', ['role_id' => $role_id]);
    echo $module->deleteSystemRole($role_id);
    exit;
}
