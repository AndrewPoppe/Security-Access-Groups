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

$project_id       = $module->framework->getProjectId();
$discrepantRights = $module->getUsersWithBadRights2($project_id);
$result           = json_encode([ "data" => $discrepantRights ]);
echo $result;