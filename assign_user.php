<?php

namespace YaleREDCap\SystemUserRights;

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    exit;
}

$data = filter_input_array(INPUT_POST, FILTER_SANITIZE_ENCODED);
$username = $data["username"];
$role_id = $data["role_id"];
$role_name = \ExternalModules\ExternalModules::getRoleName($module->getProjectId(), $role_id);

$scriptPath = $module->getSafePath('UserRights/assign_user.php', APP_PATH_DOCROOT);

$role_rights = $module->getRoleRights($role_id);
$acceptable_rights = $module->getAcceptableRights($username);

$bad_rights = $module->checkProposedRights($acceptable_rights, $role_rights);

$errors = !empty($bad_rights);

if ($errors === false) {
    require $scriptPath;
} else {
    echo json_encode(["error" => true, "bad_rights" => ["$username" => $bad_rights], "role" => $role_name]);
}
exit;
