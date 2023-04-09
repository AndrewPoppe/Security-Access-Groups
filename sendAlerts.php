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

$data = filter_input_array(INPUT_POST, [
    'alertType' => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
    'displayFromName' => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
    'fromEmail' => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
    'emailSubject' => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
    'emailBody' => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
    'delayDays' =>  array(
        'filter'    => FILTER_VALIDATE_INT,
        'options'   => array('min_range' => 1)
    ),
    'reminderSubject' => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
    'reminderBody' => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
    'users' => array(
        'filter' => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
        'flags' => FILTER_REQUIRE_ARRAY
    )
]);

echo json_encode($data);
