<?php
namespace YaleREDCap\SecurityAccessGroups;

/** @var SecurityAccessGroups $module */

if ( !$module->framework->getUser()->isSuperUser() ) {
    http_response_code(401);
    exit;
}

$alerts      = new Alerts($module);
$alertsArray = $alerts->getAlerts();
echo json_encode(
    array(
        "data" => $alertsArray
    )
);