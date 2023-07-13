<?php
namespace YaleREDCap\SecurityAccessGroups;

/** @var SecurityAccessGroups $module */

if ( !$module->framework->getUser()->isSuperUser() ) {
    http_response_code(401);
    exit;
}

if ( $_SERVER['REQUEST_METHOD'] !== 'POST' ) {
    http_response_code(405);
    exit;
}

$includeExpired = filter_input(INPUT_POST, 'includeExpired', FILTER_VALIDATE_BOOL) ?? false;
$projects       = $module->getProjectsWithNoncompliantUsers($includeExpired);

echo json_encode([ 'data' => $projects ]);