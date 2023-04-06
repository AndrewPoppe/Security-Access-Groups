<?php

namespace YaleREDCap\SystemUserRights;

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(400);
    exit;
}

if (!$module->getUser()->isSuperUser()) {
    http_response_code(401);
    exit;
}

$text = filter_input(INPUT_POST, 'text');

$replaced_text = \Piping::pipeSpecialTags($text, $module->getProjectId());

echo $replaced_text;
