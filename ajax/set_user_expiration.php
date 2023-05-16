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

$scriptPath = $module->getSafePath('UserRights/set_user_expiration.php', APP_PATH_DOCROOT);

$data       = filter_input_array(INPUT_POST);
$username   = $data["username"];
$expiration = $data["expiration"];

$module->log('thing', [ "exp" => $expiration, "exps" => strtotime($expiration), "now" => strtotime('today') ]);

if ( !empty($expiration) && strtotime($expiration) < strtotime('today') ) {
    require_once $scriptPath;
    exit;
}

$acceptable_rights = $module->getAcceptableRights($username);
$current_rights    = $module->getCurrentRights($username, $module->getProjectId());
$current_rights    = $module->getCurrentRightsFormatted($username, $module->getProjectId());
$bad_rights        = $module->checkProposedRights($acceptable_rights, $current_rights);
$errors            = !empty($bad_rights);

if ( $errors === false ) {
    require_once $scriptPath;
    exit;
} else {
    echo json_encode([ "error" => true, "bad_rights" => [ "$username" => $bad_rights ] ]);
}