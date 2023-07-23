<?php

namespace YaleREDCap\SecurityAccessGroups;

/** @var SecurityAccessGroups $module */

if ( $_SERVER["REQUEST_METHOD"] !== "POST" ) {
    http_response_code(405);
    exit;
}

if ( !$module->isSuperUser() ) {
    http_response_code(401);
    exit;
}

$scriptPath = $module->getSafePath('UserRights/set_user_expiration.php', APP_PATH_DOCROOT);

$username   = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
$expiration = filter_input(INPUT_POST, 'expiration', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

if ( !empty($expiration) && strtotime($expiration) < strtotime('today') ) {
    require_once $scriptPath;
    exit;
}

$sag              = $sagUser->getUserSag();
$acceptableRights = $sag->getSagRights();
$sagUser          = new SAGUser($module, $username);
$currentRights    = $sagUser->getCurrentRightsFormatted($module->getProjectId());
$badRights        = $module->checkProposedRights($acceptableRights, $currentRights);
$errors           = !empty($badRights);

if ( $errors === false ) {
    require_once $scriptPath;
    exit;
} else {
    http_response_code(403);
    echo json_encode([ "error" => true, "bad_rights" => [ "$username" => [ "SAG" => $sag->sagName, "rights" => $badRights ] ] ]);
}