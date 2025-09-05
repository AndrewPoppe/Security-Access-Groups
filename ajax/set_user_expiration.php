<?php

namespace YaleREDCap\SecurityAccessGroups;

/** @var SecurityAccessGroups $module */

if ( $_SERVER["REQUEST_METHOD"] !== "POST" ) {
    http_response_code(405);
    exit;
}

$user       = $module->framework->getUser();
$userRights = $user->getRights();
if ( $module->framework->isSuperUser() === false && (int) $userRights['user_rights'] !== 1 ) {
    http_response_code(401);
    exit;
}

$scriptPath = $module->getSafePath('UserRights/set_user_expiration.php', APP_PATH_DOCROOT);

$username   = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
$expiration = filter_input(INPUT_POST, 'expiration', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
$expiration = \DateTimeRC::format_ts_to_ymd($expiration);

if ( !empty($expiration) && strtotime($expiration) < strtotime('today') ) {
    $errors = false;
} else {
    $sagUser          = new SAGUser($module, $username);
    $sag              = $sagUser->getUserSag();
    $acceptableRights = $sag->getSagRights();
    $projectId        = $module->framework->getProjectId();
    $currentRights    = $sagUser->getCurrentRightsFormatted($projectId);
    $rightsChecker    = new RightsChecker($module, $currentRights, $acceptableRights, $projectId);
    $badRights        = $rightsChecker->checkRights();
    $errors           = !empty($badRights);
}

if ( $errors === false ) {
    $info = [
        'project_id' => $project_id,
        'username'   => $username,
        'expiration' => $expiration
    ];
    ob_start(function ($str) use ($info, $module) {
        try {
            $succeeded = true; //strpos($str, 'userSaveMsg darkgreen') !== false; // is there no better way?
            if ( $succeeded ) {
                $dataValues = "user = '" . $info["username"] . "'\nexpiration date = '" . $info["expiration"] . "'";

                $logTable   = $module->framework->getProject($info['project_id'])->getLogTable();
                $sql        = "SELECT log_event_id FROM $logTable WHERE project_id = ? AND user = ? AND page in ('ExternalModules/index.php', 'external_modules/index.php') AND object_type = 'redcap_user_rights' AND pk = ? AND event IN ('INSERT','UPDATE') AND description = 'Edit user expiration' AND TIMESTAMPDIFF(SECOND,ts,NOW()) <= 10 ORDER BY ts DESC";
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
                        'Edit user expiration',
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
            $module->log('Error logging user expiration', [ 'error' => $e->getMessage() ]);
        }
        return $str;
    });
    require_once $scriptPath;
    ob_end_flush(); // End buffering and clean up
    require_once $scriptPath;
    exit;
} else {
    http_response_code(403);
    echo json_encode([ "error" => true, "bad_rights" => [ "$username" => [ "SAG" => $sag->sagName, "rights" => $badRights ] ] ]);
}