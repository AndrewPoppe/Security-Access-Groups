<?php
namespace YaleREDCap\SecurityAccessGroups;

/** @var SecurityAccessGroups $module */

if ( !$module->framework->getUser()->isSuperUser() ) {
    http_response_code(401);
    exit;
}

$users = $module->getAllUserInfo(true);

$module->framework->log('ok', [ 'data' => json_encode($users) ]);

echo json_encode([ "data" => $users ]);