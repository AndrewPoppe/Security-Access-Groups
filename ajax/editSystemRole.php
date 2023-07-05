<?php

namespace YaleREDCap\SecurityAccessGroups;

/** @var SecurityAccessGroups $module */

if ( !$module->framework->getUser()->isSuperUser() ) {
    http_response_code(401);
    exit;
}

// We're submitting the form to add/edit the role
if ( $_SERVER["REQUEST_METHOD"] === "POST" ) {
    $data      = filter_input_array(INPUT_POST, FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $roleId    = $data["role_id"] ?? $module->generateNewRoleId();
    $role_name = $data["role_name_edit"];
    $newRole   = $data["newRole"];
    if ( $newRole == 1 ) {
        $module->throttleSaveSystemRole($roleId, $role_name, json_encode($data));
    } else {
        $module->throttleUpdateSystemRole($roleId, $role_name, json_encode($data));
    }
    echo $roleId;
    exit;
}

// We're asking for the add/edit role form contents
if ( $_SERVER["REQUEST_METHOD"] === "GET" ) {
    $newRole   = filter_input(INPUT_GET, "newRole", FILTER_VALIDATE_BOOLEAN);
    $roleId    = filter_input(INPUT_GET, "role_id", FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $role_name = filter_input(INPUT_GET, "role_name", FILTER_SANITIZE_FULL_SPECIAL_CHARS);

    if ( $newRole === true ) {
        $defaultRights = $module->getDefaultRights();
        $module->getRoleEditForm($defaultRights, true, $role_name);
    } else {
        $thisRole = $module->getSystemRoleRightsById($roleId);
        $rights   = json_decode($thisRole["permissions"], true);
        $module->getRoleEditForm($rights, false, $thisRole["role_name"], $roleId);
    }
    exit;
}