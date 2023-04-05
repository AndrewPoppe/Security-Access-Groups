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

$users = $_POST["users"];
$users = array_map(function ($value) {
    return htmlspecialchars($value);
}, $users);

$module->log('Requested to expire users', ["users" => json_encode($users)]);

$error = false;
$bad_users = [];
$project = $module->getProject();
$projectUsers = $project->getUsers();
$projectUsernames = array_map(function ($user) {
    return $user->getUsername();
}, $projectUsers);
$project_id = $project->getProjectId();

// Check users exist in the project
foreach ($users as $user) {
    if (!in_array($user, $projectUsernames, true)) {
        $error = true;
        $bad_users[] = $user;
    }
}

if ($error) {
    http_response_code(400);
    echo json_encode($bad_users);
    exit;
}

// Expire users
$currentUser = $module->getUser()->getUsername();
try {
    $expiration = date('Y-m-d', strtotime("-1 days"));
    $logTable = $project->getLogTable();
    foreach ($users as $user) {
        $project->setRights($user, ["expiration" => $expiration]);
        $module->log('Set Expiration Date', ["user" => $user, "expiration" => $expiration]);
        $data_values = "user = $user\nexpiration date = $expiration";
        \Logging::logEvent(
            '',                                                                 // SQL
            "redcap_user_rights",                                               // table
            "UPDATE",                                                           // event
            $user,                                                              // record
            $data_values,                                                       // display
            'Edit user expiration',                                             // descrip
            "",                                                                 // change_reason
            "",                                                                 // userid_override
            "",                                                                 // project_id_override
            true,                                                               // useNOW
            null,                                                               // event_id_override
            null,                                                               // instance
            false                                                               // bulkProcessing
        );
    }
} catch (\Throwable $e) {
    $module->log('Error setting Expiration Date', ["user" => $user, "expiration" => $expiration, "error" => $e->getMessage()]);
    http_response_code(500);
    echo "Error setting Expiration Date";
    exit;
}
http_response_code(200);
exit;
