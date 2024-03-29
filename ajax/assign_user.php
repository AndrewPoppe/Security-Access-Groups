<?php

namespace YaleREDCap\SecurityAccessGroups;

/** @var SecurityAccessGroups $module */

$scriptPath = $module->getSafePath('UserRights/assign_user.php', APP_PATH_DOCROOT);

if ( $_SERVER['REQUEST_METHOD'] !== 'POST' ) {
    require_once $scriptPath;
    exit;
}

$data     = filter_input_array(INPUT_POST, FILTER_SANITIZE_ENCODED);
$username = $data['username'];
$sagUser  = new SAGUser($module, $username);
$roleId   = $data['role_id'];
$sag      = $sagUser->getUserSag();


// We don't care if the user is being removed from a role.
if ( $roleId == 0 ) {
    require_once $scriptPath;
    exit;
}

$role             = new Role($module, $roleId);
$roleLabel        = $role->getRoleName();
$unique_role_name = $role->getUniqueRoleName();
$project_id       = $module->framework->getProjectId();

$role_rights      = $role->getRoleRights($project_id);
$acceptableRights = $sagUser->getAcceptableRights();

$rightsChecker = new RightsChecker($module, $role_rights, $acceptableRights, $project_id);
$badRights     = $rightsChecker->checkRights();
$errors        = !empty($badRights);

// We ignore expired users
$userExpired = $sagUser->isUserExpired($project_id);

if ( $errors === false || $userExpired ) {
    $info = [
        'project_id'       => $project_id,
        'username'         => $username,
        'role_id'          => $roleId,
        'role_label'       => $roleLabel,
        'unique_role_name' => $unique_role_name
    ];
    ob_start(function ($str) use ($info, $module) {
        try {
            $succeeded = strpos($str, 'userSaveMsg darkgreen') !== false; // is there no better way?
            if ( $succeeded ) {
                $dataValues = "user = '" . $info["username"] . "'\nrole = '" . $info["role_label"] . "'\nunique_role_name = '" . $info["unique_role_name"] . "'";

                $logTable   = $module->framework->getProject($info['project_id'])->getLogTable();
                $sql        = "SELECT log_event_id FROM $logTable WHERE project_id = ? AND user = ? AND page in ('ExternalModules/index.php', 'external_modules/index.php') AND object_type = 'redcap_user_rights' AND pk = ? AND event IN ('INSERT','UPDATE') AND description = 'Assign user to role' AND TIMESTAMPDIFF(SECOND,ts,NOW()) <= 10 ORDER BY ts DESC";
                $params     = [ $info['project_id'], $module->framework->getUser()->getUsername(), $info['username'] ];
                $result     = $module->query($sql, $params);
                $logEventId = intval($result->fetch_assoc()['log_event_id']);
                if ( $logEventId != 0 ) {
                    $module->query("UPDATE $logTable SET data_values = ? WHERE log_event_id = ?", [ $module->framework->escape($dataValues), $logEventId ]);
                } else {
                    \Logging::logEvent(
                        '',
                        'redcap_user_rights',
                        'INSERT',
                        $info['username'],
                        $dataValues,
                        'Assign user to role',
                        '',
                        '',
                        '',
                        true,
                        null,
                        null,
                        false
                    );
                }
            }
        } catch ( \Throwable $e ) {
            $module->log('Error logging user role mapping', [ 'error' => $e->getMessage() ]);
        }
        return $str;
    });
    require_once $scriptPath;
    ob_end_flush(); // End buffering and clean up
} else {
    http_response_code(403);
    echo json_encode($module->framework->escape([ 'error' => true, 'bad_rights' => [ "$username" => [ 'SAG' => $sag->sagName, 'rights' => $badRights ] ], 'role' => $roleLabel ]));
}
exit;