<?php

namespace YaleREDCap\SecurityAccessGroups;

/** @var SecurityAccessGroups $module */

if ( !$module->framework->getUser()->isSuperUser() ) {
    http_response_code(401);
    exit;
}

// We're submitting the form to add/edit the role
if ( $_SERVER['REQUEST_METHOD'] === 'POST' ) {
    $data     = filter_input_array(INPUT_POST, FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $roleId   = $data['role_id'] ?? $module->generateNewRoleId();
    $roleName = $data['role_name_edit'];
    $newRole  = $data['newRole'];
    if ( $newRole == 1 ) {
        $module->throttleSaveSag($roleId, $roleName, json_encode($data));
    } else {
        $module->throttleUpdateSag($roleId, $roleName, json_encode($data));
    }
    echo $roleId;
    exit;
}

// We're asking for the add/edit role form contents
if ( $_SERVER['REQUEST_METHOD'] === 'GET' ) {
    require_once $module->getSafePath('classes/RoleEditForm.php');
    $newRole  = filter_input(INPUT_GET, 'newRole', FILTER_VALIDATE_BOOLEAN);
    $roleId   = filter_input(INPUT_GET, 'role_id', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $roleName = filter_input(INPUT_GET, 'role_name', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

    if ( $newRole === true ) {
        $rights  = $module->getDefaultRights();
        $newRole = true;
    } else {
        $thisRole = $module->getSagRightsById($roleId);
        $rights   = json_decode($thisRole['permissions'], true);
        $roleName = $thisRole['role_name'];
        $newRole  = false;
    }
    $roleEditForm = new RoleEditForm(
        $module,
        $rights,
        $newRole,
        $roleName,
        $roleId
    );
    $roleEditForm->getForm();
    exit;
}