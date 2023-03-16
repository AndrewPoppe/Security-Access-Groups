<?php

namespace YaleREDCap\SystemUserRights;

if (!$module->getUser()->isSuperUser()) {
    http_response_code(401);
    exit;
}

if (!$_SERVER["REQUEST_METHOD"] === "POST") {
    exit;
}

$CsvString = filter_input(INPUT_POST, "data");

$lineEnding = str_contains($CsvString, "\r\n") ? "\r\n" : "\n";
$Data = str_getcsv($CsvString, $lineEnding);
foreach ($Data as &$Row) $Row = str_getcsv($Row, ",");

echo json_encode($Data);
