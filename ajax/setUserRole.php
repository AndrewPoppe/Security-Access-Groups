<?php

namespace YaleREDCap\SecurityAccessGroups;

/** @var SecurityAccessGroups $module */

if ( $_SERVER["REQUEST_METHOD"] !== "POST" ) {
    http_response_code(405);
    exit;
}

if ( !$module->getUser()->isSuperUser() ) {
    http_response_code(401);
    exit;
}

$username = filter_input(INPUT_POST, "username", FILTER_SANITIZE_FULL_SPECIAL_CHARS);
$role     = filter_input(INPUT_POST, "role", FILTER_SANITIZE_FULL_SPECIAL_CHARS);

if ( empty($module->framework->getUser($username)->getEmail()) ) {
    http_response_code(400);
    echo "Username not found";
    exit;
}

if ( !$module->systemRoleExists($role) ) {
    http_response_code(400);
    echo "Role not found";
    exit;
}

http_response_code(200);
$setting = $username . "-role";
$module->framework->setSystemSetting($setting, $role);
exit;