<?php

namespace YaleREDCap\SecurityAccessGroups;

/** @var SecurityAccessGroups $module */

if ( !$module->getUser()->isSuperUser() ) {
    http_response_code(401);
    exit;
}

// We're submitting the form to add/edit the role
if ( $_SERVER["REQUEST_METHOD"] === "POST" ) {
    $roleId = filter_input(INPUT_POST, "role_id", FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    if ( empty($roleId) || !$module->sagExists($roleId) ) {
        http_response_code(400);
        echo "The provided role ID was bad.";
        exit;
    }
    echo $module->throttleDeleteSag($roleId);
    exit;
}