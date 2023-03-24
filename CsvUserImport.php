<?php

namespace YaleREDCap\SystemUserRights;

use YaleREDCap\SystemUserRights\SystemUserRights;

class CsvUserImport
{
    private $csvString;
    private $module;
    public $csvContents;
    function __construct(SystemUserRights $module, string $csvString)
    {
        $this->module = $module;
        $this->csvString = $csvString;
    }

    public function parseCsvString()
    {
        $lineEnding = str_contains($this->csvString, "\r\n") ? "\r\n" : "\n";
        $Data = str_getcsv($this->csvString, $lineEnding);
        foreach ($Data as &$Row) $Row = str_getcsv($Row, ",");
        $this->csvContents = $Data;
    }

    public function contentsValid()
    {
        $header = $this->csvContents[0];
        $valid = true;
        if (!in_array("username", $header, true) || !in_array("role_id", $header, true)) {
            $valid = false;
        }
        return $valid;
    }

    public function getAssignments()
    {
    }
}
