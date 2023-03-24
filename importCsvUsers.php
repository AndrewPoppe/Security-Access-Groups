<?php

namespace YaleREDCap\SystemUserRights;

require_once "CsvUserImport.php";

use YaleREDCap\SystemUserRights\CsvUserImport;

if (!$module->getUser()->isSuperUser()) {
    http_response_code(401);
    exit;
}

if (!$_SERVER["REQUEST_METHOD"] === "POST") {
    exit;
}

$CsvString = filter_input(INPUT_POST, "data");

$userImport = new CsvUserImport($module, $CsvString);
$userImport->parseCsvString();

if (!$userImport->contentsValid()) {
    http_response_code(400);
    exit;
}

$assignments = $userImport->getAssignments();

echo json_encode($userImport->csvContents);
