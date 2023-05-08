<?php
namespace YaleREDCap\SystemUserRights;

/* @var SystemUserRights $module */

$module->framework->log('got request');
$alerts      = new Alerts($module);
$alertsArray = $alerts->getAlerts();
echo json_encode(
    array(
        "data" => $alertsArray
    )
);