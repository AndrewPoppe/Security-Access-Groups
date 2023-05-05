<?php

namespace YaleREDCap\SystemUserRights;

use YaleREDCap\SystemUserRights;

/** @var SystemUserRights $module */

if (!$module->framework->getUser()->isSuperUser()) {
    http_response_code(401);
    exit;
}

// We're submitting the form to add/edit the role
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $data = filter_input_array(INPUT_POST, FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $role_id = $data["role_id"] ?? $module->generateNewRoleId();
    $role_name = $data["role_name_edit"];
    $newRole = $data["newRole"];
    if ($newRole == 1) {
        $module->throttleSaveSystemRole($role_id, $role_name, json_encode($data));
    } else {
        $module->throttleUpdateSystemRole($role_id, $role_name, json_encode($data));
    }
    echo $role_id;
    exit;
}

// We're asking for the add/edit role form contents
if ($_SERVER["REQUEST_METHOD"] === "GET") {
    $newRole = filter_input(INPUT_GET, "newRole", FILTER_VALIDATE_BOOLEAN);
    $role_id = filter_input(INPUT_GET, "role_id", FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $role_name = filter_input(INPUT_GET, "role_name", FILTER_SANITIZE_FULL_SPECIAL_CHARS);

    if ($newRole === true) {
        $defaultRights = $module->getDefaultRights();
        $module->getRoleEditForm($defaultRights, true, $role_name);
    } else {
        $this_role = $module->getSystemRoleRightsById($role_id);
        $rights = json_decode($this_role["permissions"], true);
        $module->getRoleEditForm($rights, false, $this_role["role_name"], $role_id);
    }
    exit;
}
