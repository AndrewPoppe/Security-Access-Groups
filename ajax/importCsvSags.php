<?php

namespace YaleREDCap\SecurityAccessGroups;

/** @var SecurityAccessGroups $module */

require_once $module->framework->getSafePath('classes/CsvSAGImport.php');

if ( !$module->getUser()->isSuperUser() ) {
    http_response_code(401);
    exit;
}

if ( !$_SERVER["REQUEST_METHOD"] === "POST" ) {
    http_response_code(405);
    exit;
}

$CsvString = filter_input(INPUT_POST, "data");

$sagImport = new CsvSAGImport($module, $CsvString);
$sagImport->parseCsvString();

$contentsValid = $sagImport->contentsValid();
if ( $contentsValid !== true ) {
    http_response_code(400);
    echo json_encode([
        "error" => $sagImport->errorMessages
    ]);
    exit;
}

if ( filter_input(INPUT_POST, "confirm", FILTER_VALIDATE_BOOLEAN) ) {
    echo $sagImport->import();
} else {
    echo $sagImport->getUpdateTable();
}