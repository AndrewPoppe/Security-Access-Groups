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

$scriptPath = $module->getSafePath('UserRights/set_user_expiration.php', APP_PATH_DOCROOT);

$data       = filter_input_array(INPUT_POST);
$username   = $data["username"];
$expiration = $data["expiration"];

if ( !empty($expiration) && strtotime($expiration) < strtotime('today') ) {
    require_once $scriptPath;
    exit;
}

$sagId            = $module->getUserSag($username);
$sag              = $module->getSagRightsById($sagId);
$acceptableRights = $module->getAcceptableRights($username);
$currentRights    = $module->getCurrentRights($username, $module->getProjectId());
$currentRights    = $module->getCurrentRightsFormatted($username, $module->getProjectId());
$badRights        = $module->checkProposedRights($acceptableRights, $currentRights);
$errors           = !empty($badRights);

if ( $errors === false ) {
    require_once $scriptPath;
    exit;
} else {
    http_response_code(403);
    echo json_encode([ "error" => true, "bad_rights" => [ "$username" => [ "SAG" => $sag["sag_name"], "rights" => $badRights ] ] ]);
}