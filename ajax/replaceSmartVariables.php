<?php

namespace YaleREDCap\SecurityAccessGroups;

/** @var SecurityAccessGroups $module */

require_once $module->framework->getSafePath("classes/TextReplacer.php");

use YaleREDCap\SecurityAccessGroups\TextReplacer;

if ( $_SERVER["REQUEST_METHOD"] !== "POST" ) {
    http_response_code(400);
    exit;
}

if ( !$module->getUser()->isSuperUser() ) {
    http_response_code(401);
    exit;
}

$text = filter_input(INPUT_POST, 'text');
$data = $_POST["data"] ?? [];

$textReplacer = new TextReplacer($module, $text, $data ?? []);

$replaced_text = $textReplacer->replaceText();

echo $replaced_text;