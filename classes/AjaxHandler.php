<?php

namespace YaleREDCap\SecurityAccessGroups;

class AjaxHandler
{
    private SecurityAccessGroups $module;
    private array $params;
    private static array $generalActions = [];
    private static array $adminActions = [ 'deleteAlert', 'getAlert', 'getAlerts', '' ];

    public function __construct(SecurityAccessGroups $module, array $params)
    {
        $this->module = $module;
        $this->params = $this->module->framework->escape($params);
    }

    public function handleAjax()
    {
        if ( in_array($this->params['action'], self::$generalActions, true) ) {
            return $this->handleGeneralAjax();
        } elseif ( in_array($this->params['action'], self::$adminActions, true) ) {
            $this->module->framework->log(1);
            return $this->handleAdminAjax();
        } else {
            http_response_code(400);
            throw new \Exception("Invalid action: {$this->params['action']}");
            return null;
        }

    }

    private function handleGeneralAjax()
    {
        return null;
    }

    private function handleAdminAjax()
    {
        $this->module->framework->log(2);
        if ( !$this->module->framework->getUser()->isSuperUser() ) {
            http_response_code(403);
            return [ 'data' => [] ];
        }

        $action = $this->params['action'];
        $result = null;
        if ( $action === 'getAlert' ) {
            $result = $this->getAlert();
        } elseif ( $action === 'getAlerts' ) {
            $result = $this->getAlerts();
        } elseif ( $action === 'deleteAlert' ) {
            $result = $this->deleteAlert();
        } elseif ( $action === 'sendAlerts' ) {
            $this->module->framework->log(3);
            $result = $this->sendAlerts();
        }
        return $result;
    }

    private function logAjax()
    {
        return $this->module->framework->log("redcap_module_ajax", [
            'action'            => $this->params['action'],
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

    private function sendAlerts()
    {
        $this->module->framework->log(4);

        $this->logAjax();
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
}