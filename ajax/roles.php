<?php
namespace YaleREDCap\SecurityAccessGroups;

/** @var SecurityAccessGroups $module */

if ( !$module->framework->getUser()->isSuperUser() ) {
    http_response_code(401);
    exit;
}

if ( $_SERVER["REQUEST_METHOD"] !== "POST" ) {
    http_response_code(405);
    exit;
}

$roles          = $module->getAllSystemRoles();
$allPermissions = $module->getDisplayTextForRights(true);

$rolesForTable = [];
foreach ( $roles as $index => $role ) {
    $role["index"]       = $index;
    $permissions         = json_decode($role["permissions"], true);
    $role["permissions"] = [];
    foreach ( $allPermissions as $permission => $displayText ) {
        $role["permissions"][$permission] = $permissions[$permission] ?? null;
    }
    $rolesForTable[] = $role;
}

echo json_encode([ "data" => $rolesForTable ]);