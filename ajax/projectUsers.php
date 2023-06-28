<?php
namespace YaleREDCap\SecurityAccessGroups;

/** @var SecurityAccessGroups $module */
$time_start = microtime(true);
if ( !$module->framework->getUser()->isSuperUser() ) {
    http_response_code(401);
    exit;
}

$project_id       = $module->framework->getProjectId();
$discrepantRights = $module->getUsersWithBadRights($project_id);

echo json_encode([ "data" => $discrepantRights ]);