<?php

namespace YaleREDCap\SystemUserRights;

if (!$module->getUser()->isSuperUser()) {
    http_response_code(401);
    exit;
}

// We're submitting the form to add/edit the role
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $data = filter_input_array(INPUT_POST, FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $role_id = $data["role_id"] ?? $module->generateNewRoleId();
    $role_name = $data["role_name_edit"];
    echo $module->saveSystemRole($role_id, $role_name, json_encode($data));
    exit;
}

// We're asking for the add/edit role form contents
if ($_SERVER["REQUEST_METHOD"] === "GET") {
    $newRole = filter_input(INPUT_GET, "newRole", FILTER_VALIDATE_BOOLEAN);
    $role_id = filter_input(INPUT_GET, "role_id", FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $role_name = filter_input(INPUT_GET, "role_name", FILTER_SANITIZE_FULL_SPECIAL_CHARS);


    if ($newRole === true) {
        $defaultRights = $module->getDefaultRights();
        $form_contents = $module->getRoleEditForm($defaultRights, $newRole);
        echo $form_contents;
    } else {
        $this_role = $module->getSystemRoleRightsById($role_id);
        $rights = json_decode($this_role["permissions"], true);
        $form_contents = $module->getRoleEditForm($rights, false, $role_name, $role_id);
        echo $form_contents;
    }

    exit;
}
