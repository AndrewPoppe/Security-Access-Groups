<?php
namespace YaleREDCap\SystemUserRights;

/* @var SystemUserRights $module */

if (!$module->framework->getUser()->isSuperUser()) {
    http_response_code(401);
    exit;
}

$module->framework->log('got request');
$alerts      = new Alerts($module);
$alertsArray = $alerts->getAlerts();
echo json_encode(
    array(
        "data" => $alertsArray
    )
);