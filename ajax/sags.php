<?php
namespace YaleREDCap\SecurityAccessGroups;

/** @var SecurityAccessGroups $module */

if ( !$module->framework->getUser()->isSuperUser() ) {
    http_response_code(401);
    exit;
}

if ( $_SERVER['REQUEST_METHOD'] !== 'POST' ) {
    http_response_code(405);
    exit;
}

$sags           = $module->getAllSags();
$allPermissions = $module->getDisplayTextForRights(true);

$sagsForTable = [];
foreach ( $sags as $index => $sag ) {
    $sag['index']       = $index;
    $permissions        = json_decode($sag['permissions'], true);
    $sag['permissions'] = [];
    foreach ( $allPermissions as $permission => $displayText ) {
        $sag['permissions'][$permission] = $permissions[$permission] ?? null;
    }
    $sagsForTable[] = $sag;
}

echo json_encode([ 'data' => $sagsForTable ]);