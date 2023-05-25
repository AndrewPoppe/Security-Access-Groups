<?php

namespace YaleREDCap\SecurityAccessGroups;

/** @var SecurityAccessGroups $module */

if ( !$module->framework->getUser()->isSuperUser() ) {
    http_response_code(401);
    exit;
}

$alert_id = filter_input(INPUT_POST, "alert_id", FILTER_VALIDATE_INT);
if ( !$alert_id ) {
    http_response_code(400);
    echo "Alert ID not formatted correctly";
    exit;
}

$Alerts = new Alerts($module);
$alert  = $Alerts->getAlertById($alert_id);
if ( !$alert ) {
    http_response_code(400);
    echo "Alert not found";
    exit;
}

echo json_encode($alert);
exit;