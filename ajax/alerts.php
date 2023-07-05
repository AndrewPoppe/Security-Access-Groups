<?php
namespace YaleREDCap\SecurityAccessGroups;

/** @var SecurityAccessGroups $module */

require_once $module->framework->getSafePath('classes/Alerts.php');

if ( $_SERVER['REQUEST_METHOD'] !== 'POST' ) {
    http_response_code(405);
    exit;
}

if ( !$module->framework->getUser()->isSuperUser() ) {
    http_response_code(401);
    exit;
}

$alerts      = new Alerts($module);
$alertsArray = $alerts->getAlerts();
echo json_encode(
    array(
        'data' => $alertsArray
    )
);