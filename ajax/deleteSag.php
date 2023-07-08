<?php

namespace YaleREDCap\SecurityAccessGroups;

/** @var SecurityAccessGroups $module */

if ( !$module->getUser()->isSuperUser() ) {
    http_response_code(401);
    exit;
}

// We're submitting the form to add/edit the SAG
if ( $_SERVER["REQUEST_METHOD"] === "POST" ) {
    $sagId = filter_input(INPUT_POST, "sag_id", FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    if ( empty($sagId) || !$module->sagExists($sagId) ) {
        http_response_code(400);
        echo "The provided SAG ID was bad.";
        exit;
    }
    echo $module->throttleDeleteSag($sagId);
    exit;
}