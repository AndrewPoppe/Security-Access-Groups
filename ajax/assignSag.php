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
$sag      = filter_input(INPUT_POST, "sag", FILTER_SANITIZE_FULL_SPECIAL_CHARS);

if ( empty($module->framework->getUser($username)->getEmail()) ) {
    http_response_code(400);
    echo "Username not found";
    exit;
}

if ( !$module->sagExists($sag) ) {
    http_response_code(400);
    echo "SAG not found";
    exit;
}

http_response_code(200);
$setting = $username . "-sag";
$module->framework->setSystemSetting($setting, $sag);
$module->framework->log('Assigned SAG', [ 'user' => $username, 'sag' => $sag ]);
exit;