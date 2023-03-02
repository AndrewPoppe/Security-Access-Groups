<?php

namespace YaleREDCap\SystemUserRights;

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    exit;
}

if (!$module->getUser()->isSuperUser()) {
    echo "You must be an administrator to use this function.";
    exit;
}

$username = filter_input(INPUT_POST, "username", FILTER_SANITIZE_FULL_SPECIAL_CHARS);
$role = filter_input(INPUT_POST, "role", FILTER_SANITIZE_FULL_SPECIAL_CHARS);

// Need to confirm username corresponds with real user
// Need to confirm role is real role


$setting = $username . "-role";
echo $module->setSystemSetting($setting, $role);
exit;
