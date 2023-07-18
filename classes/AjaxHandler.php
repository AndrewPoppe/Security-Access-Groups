<?php

namespace YaleREDCap\SecurityAccessGroups;

class AjaxHandler
{
    private SecurityAccessGroups $module;
    private array $params;
    private static array $generalActions = [];
    private static array $adminActions = [ 'getAlert', 'getAlerts' ];

    public function __construct(SecurityAccessGroups $module, array $params)
    {
        $this->module = $module;
        $this->params = $this->module->framework->escape($params);
    }

    public function handleAjax()
    {
        if ( in_array($this->params['action'], self::$generalActions) ) {
            return $this->handleGeneralAjax();
        } elseif ( in_array($this->params['action'], self::$adminActions) ) {
            return $this->handleAdminAjax();
        } else {
            return null;
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

        $action = $this->params['action'];

        if ( $action === 'getAlert' ) {
            $alerts = new Alerts($this->module);
            $alert  = $alerts->getAlertById($this->params['payload']['alert_id']);
            if ( !$alert ) {
                http_response_code(400);
                return 'Alert not found';
            }
            return json_encode($alert);
        } elseif ( $action === 'getAlerts' ) {
            $alerts      = new Alerts($this->module);
            $alertsArray = $alerts->getAlerts();
            return json_encode(
                array(
                    'data' => $alertsArray
                )
            );
        }

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
}