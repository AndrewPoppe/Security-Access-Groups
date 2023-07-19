<?php

namespace YaleREDCap\SecurityAccessGroups;

use AjaxException;

class AjaxHandler
{
    private SecurityAccessGroups $module;
    private array $params;
    private string $action;
    private static array $generalActions = [];
    private static array $adminActions = [
        'deleteAlert',
        'expireUsers',
        'getAlert',
        'getAlerts',
        'getProjectUsers',
        'replacePlaceholders',
        'sendAlerts'
    ];

    public function __construct(SecurityAccessGroups $module, array $params)
    {
        $this->module = $module;
        $this->params = $this->module->framework->escape($params);
        $this->action = $this->module->framework->escape($this->params['action']);
    }

    public function handleAjax()
    {
        if ( in_array($this->action, self::$generalActions, true) ) {
            return $this->handleGeneralAjax();
        } elseif ( in_array($this->action, self::$adminActions, true) ) {
            return $this->handleAdminAjax();
        } else {
            http_response_code(400);
            throw new AjaxException("Invalid action: {$this->action}");
        }

    }

    private function handleGeneralAjax()
    {
        return null;
    }

    private function handleAdminAjax()
    {
        if ( !$this->module->framework->getUser()->isSuperUser() ) {
            http_response_code(403);
            return [ 'data' => [] ];
        }

        $action = $this->action;
        $result = null;
        if ( $action === 'deleteAlert' ) {
            $result = $this->deleteAlert();
        } elseif ( $action === 'expireUsers' ) {
            $result = $this->expireUsers();
        } elseif ( $action === 'getAlert' ) {
            $result = $this->getAlert();
        } elseif ( $action === 'getAlerts' ) {
            $result = $this->getAlerts();
        } elseif ( $action === 'getProjectUsers' ) {
            $result = $this->getProjectUsers();
        } elseif ( $action === 'replacePlaceholders' ) {
            $result = $this->replacePlaceholders();
        } elseif ( $action === 'sendAlerts' ) {
            $result = $this->sendAlerts();
        }
        return $result;
    }

    private function logAjax()
    {
        return $this->module->framework->log("redcap_module_ajax", [
            'action'            => $this->action,
            'payload'           => json_encode($this->params['payload']),
            'project_id'        => $this->params['project_id'],
            'record'            => $this->params['record'],
            'instrument'        => $this->params['instrument'],
            'event_id'          => $this->params['event_id'],
            'repeat_instance'   => $this->params['repeat_instance'],
            'survey_hash'       => $this->params['survey_hash'],
            'response_id'       => $this->params['response_id'],
            'survey_queue_hash' => $this->params['survey_queue_hash'],
            'page'              => $this->params['page'],
            'page_full'         => $this->params['page_full'],
            'user_id'           => $this->params['user_id'],
            'group_id'          => $this->params['group_id']
        ]);
    }

    // Alerts
    private function deleteAlert()
    {
        $alerts = new Alerts($this->module);
        $alert  = $alerts->getAlertById($this->params['payload']['alert_id']);
        if ( !$alert ) {
            http_response_code(400);
            return 'Alert not found';
        }
        return json_encode($this->module->escape($alerts->deleteAlert($this->params['payload']['alert_id'])));
    }

    private function getAlert()
    {
        $alerts = new Alerts($this->module);
        $alert  = $alerts->getAlertById($this->params['payload']['alert_id']);
        if ( !$alert ) {
            http_response_code(400);
            return 'Alert not found';
        }
        return json_encode($alert);
    }

    private function getAlerts()
    {
        $alerts      = new Alerts($this->module);
        $alertsArray = $alerts->getAlerts();
        return json_encode(
            array(
                'data' => $alertsArray
            )
        );
    }

    private function replacePlaceholders()
    {
        $textReplacer = new TextReplacer(
            $this->module,
            $this->params['payload']['text'],
            $this->params['payload']['data'] ?? []
        );
        return $textReplacer->replaceText();
    }

    private function sendAlerts()
    {
        $data = filter_var_array($this->params['payload']['config'], [
            'alertType'                                         => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
            'displayFromName'                                   => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
            'displayFromName-userExpiration-UserRightsHolders'  => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
            'fromEmail'                                         => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
            'fromEmail-userExpiration-UserRightsHolders'        => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
            'emailSubject'                                      => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
            'emailSubject-userExpiration-UserRightsHolders'     => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
            'emailBody'                                         => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
            'usersEmailBody'                                    => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
            'userRightsHoldersEmailBody'                        => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
            'sendReminder'                                      => array(
                'filter' => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
                'flags'  => FILTER_VALIDATE_BOOL
            ),
            'sendUserNotification'                              => array(
                'filter' => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
                'flags'  => FILTER_VALIDATE_BOOL
            ),
            'sendNotification-userExpiration-UserRightsHolders' => array(
                'filter' => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
                'flags'  => FILTER_VALIDATE_BOOL
            ),
            'delayDays'                                         => array(
                'filter'  => FILTER_VALIDATE_INT,
                'options' => array( 'min_range' => 1 )
            ),
            'delayDays-UserRightsHolders'                       => array(
                'filter'  => FILTER_VALIDATE_INT,
                'options' => array( 'min_range' => 1 )
            ),
            'delayDays-expiration'                              => array(
                'filter'  => FILTER_VALIDATE_INT,
                'options' => array( 'min_range' => 0 )
            ),
            'recipients'                                        => array(
                'filter' => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
                'flags'  => FILTER_REQUIRE_ARRAY
            ),
            'reminderSubject'                                   => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
            'reminderBody'                                      => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
            'users'                                             => array(
                'filter' => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
                'flags'  => FILTER_REQUIRE_ARRAY
            )
        ]);

        if ( $data['alertType'] === 'expiration' ) {
            $data['sag_expiration_date'] = date('Y-m-d', strtotime("+" . $data['delayDays-expiration'] . " days"));
        }

        $this->module->framework->log('Sending alerts', [ 'data' => json_encode($data) ]);
        $alert = new Alert($this->module, $data);
        $error = $alert->sendAlerts();
        if ( !$error ) {
            $this->module->framework->log('Sent alerts', [ 'data' => json_encode($data) ]);
            http_response_code(200);
        } else {
            $this->module->framework->log('Error sending alerts: ', [ 'error' => $error ]);
            http_response_code(500);
            $data['error'] = $error;
        }
        return json_encode($data);
    }

    // Users

    private function expireUsers()
    {
        $users     = $this->module->framework->escape($this->params['payload']['users']);
        $delayDays = intval($this->params['payload']['delayDays']);

        $this->module->framework->log('Requested to expire users', [
            "users"     => json_encode($users),
            "delayDays" => $delayDays
        ]);

        $error            = false;
        $badUsers         = [];
        $project          = $this->module->framework->getProject();
        $projectUsers     = $project->getUsers();
        $projectUsernames = array_map(function ($user) {
            return $user->getUsername();
        }, $projectUsers);

        // Check users exist in the project
        foreach ( $users as $user ) {
            if ( !in_array($user, $projectUsernames, true) ) {
                $error      = true;
                $badUsers[] = $user;
            }
        }

        if ( $error ) {
            http_response_code(400);
            return json_encode($badUsers);
        }

        // Expire users
        try {
            $expiration = date('Y-m-d', strtotime(($delayDays - 1) . " days"));
            foreach ( $users as $user ) {
                $project->setRights($user, [ "expiration" => $expiration ]);
                $this->module->framework->log('Set Expiration Date', [ "user" => $user, "expiration" => $expiration ]);
                $dataValues = "user = $user\nexpiration date = $expiration";
                \Logging::logEvent(
                    '',
                    "redcap_user_rights",
                    "UPDATE",
                    $user,
                    $dataValues,
                    'Edit user expiration',
                    "",
                    "",
                    "",
                    true,
                    null,
                    null,
                    false
                );
            }
        } catch ( \Throwable $e ) {
            $this->module->framework->log('Error setting Expiration Date', [ "user" => $user, "expiration" => $expiration, "error" => $e->getMessage() ]);
            http_response_code(500);
            return "Error setting Expiration Date";
        }
    }

    private function getProjectUsers()
    {
        $projectId        = $this->params['project_id'];
        $discrepantRights = $this->module->getUsersWithBadRights2($projectId);
        return json_encode([ 'data' => $discrepantRights ]);
    }
}