<?php

namespace YaleREDCap\SecurityAccessGroups;

/** @var SecurityAccessGroups $module */

if ( $_SERVER["REQUEST_METHOD"] !== "POST" ) {
    http_response_code(400);
    exit;
}

if ( !$module->getUser()->isSuperUser() ) {
    http_response_code(401);
    exit;
}

$username = filter_input(INPUT_POST, "username", FILTER_SANITIZE_FULL_SPECIAL_CHARS);
$role     = filter_input(INPUT_POST, "role", FILTER_SANITIZE_FULL_SPECIAL_CHARS);

// TODO: Need to confirm username corresponds with real user
// TODO: Need to confirm role is real role

http_response_code(200);
$setting = $username . "-role";
$module->framework->setSystemSetting($setting, $role);

echo json_encode([
    "all" => $module->getAllRights()
]);

exit;