<?php
namespace YaleREDCap\SecurityAccessGroups;

/** @var SecurityAccessGroups $module */
if ( !$module->framework->getUser()->isSuperUser() ) {
    http_response_code(401);
    exit;
}

$project_id       = $module->framework->getProjectId();
$discrepantRights = $module->getUsersWithBadRights($project_id);
$result           = json_encode([ "data" => $discrepantRights ]);
echo $result;