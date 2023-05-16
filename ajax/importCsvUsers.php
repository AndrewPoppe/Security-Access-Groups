<?php

namespace YaleREDCap\SecurityAccessGroups;

/** @var SecurityAccessGroups $module */

require_once $module->framework->getSafePath("classes/CsvUserImport.php");

use YaleREDCap\SecurityAccessGroups\CsvUserImport;

if ( !$module->getUser()->isSuperUser() ) {
    http_response_code(401);
    exit;
}

if ( !$_SERVER["REQUEST_METHOD"] === "POST" ) {
    http_response_code(405);
    exit;
}

// If we are confirming choices

$CsvString = filter_input(INPUT_POST, "data");

$userImport = new CsvUserImport($module, $CsvString);
$userImport->parseCsvString();

$contentsValid = $userImport->contentsValid();
if ( $contentsValid !== true ) {
    http_response_code(400);
    echo json_encode([
        "error" => $userImport->error_messages,
        "roles" => $userImport->bad_roles,
        "users" => $userImport->bad_users
    ]);
    exit;
}

if ( filter_input(INPUT_POST, "confirm", FILTER_VALIDATE_BOOLEAN) ) {
    echo $userImport->import();
} else {
    echo $userImport->getUpdateTable();
}