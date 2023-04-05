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

$users = $_POST["users"]; // WHY ISN'T FILTER INPUT WORKING HERE??

/** 1. loop through users 
 *   2. check the user exists
 *   3. check the user is in the project
 *   4. set the user's expiration date to yesterday's date
 *   5. log this 
 */
echo json_encode($users);
