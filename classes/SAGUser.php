<?php

namespace YaleREDCap\SecurityAccessGroups;

class SAGUser
{
    private SecurityAccessGroups $module;
    public string $username;

    public function __construct(SecurityAccessGroups $module, string $username)
    {
        $this->module   = $module;
        $this->username = $username;
    }

    public function getCurrentRightsFormatted($projectId)
    {
        $currentRights     = $this->getCurrentRights($projectId);
        $currentDataExport = $this->module->convertExportRightsStringToArray($currentRights['data_export_instruments']);
        $currentDataEntry  = $this->module->convertDataEntryRightsStringToArray($currentRights['data_entry']);
        $currentRights     = array_merge($currentRights, $currentDataExport, $currentDataEntry);
        unset($currentRights['data_export_instruments']);
        unset($currentRights['data_entry']);
        unset($currentRights['data_export_tool']);
        unset($currentRights['external_module_config']);
        return $currentRights;
    }

    public function getCurrentRights($projectId)
    {
        $result = $this->module->framework->query('SELECT * FROM redcap_user_rights WHERE username = ? AND project_id = ?', [ $this->username, $projectId ]);
        $rights = $result->fetch_assoc();
        if ( !empty($rights['role_id']) ) {
            $result2 = $this->module->framework->query('SELECT * FROM redcap_user_roles WHERE role_id = ?', [ $rights['role_id'] ]);
            $rights  = $result2->fetch_assoc();
        }
        unset($rights['api_token'], $rights['expiration']);
        return $this->module->framework->escape($rights);
    }

    public function getUserInfo() : ?array
    {
        $sql = 'SELECT username
        , user_email
        , user_firstname
        , user_lastname
        , super_user
        , account_manager
        , access_system_config
        , access_system_upgrade
        , access_external_module_install
        , admin_rights
        , access_admin_dashboards
        , user_creation
        , user_lastlogin
        , user_suspended_time
        , user_expiration
        , user_sponsor
        , allow_create_db
        FROM redcap_user_information
        WHERE username = ?';
        try {
            $result = $this->module->framework->query($sql, [ $this->username ]);
            return $this->module->framework->escape($result->fetch_assoc());
        } catch ( \Throwable $e ) {
            $this->module->framework->log(
                'Error getting user info',
                [
                    'username' => $this->username,
                    'error'    => $e->getMessage(),
                    'user'     => $this->module->framework->getUser()->getUsername()
                ]
            );
        }
    }

    public function getAcceptableRights()
    {
        $sag = $this->getUserSag();
        return $sag->getSagRights();
    }

    public function isUserExpired($projectId)
    {
        $sql    = 'SELECT * FROM redcap_user_rights WHERE username = ? AND project_id = ?';
        $result = $this->module->framework->query($sql, [ $this->username, $projectId ]);
        $row    = $result->fetch_assoc();
        return !is_null($row['expiration']) && strtotime($row['expiration']) < strtotime('today');
    }

    public function setUserSag($sagId)
    {
        $setting = $this->username . '-sag';
        $this->module->framework->setSystemSetting($setting, $sagId);
    }

    /**
     * Gets user's SAG from system settings. If it doesn't exist, sets it to the default SAG ID.
     * @return SAG $sag
     */
    public function getUserSag() : SAG
    {
        $setting = $this->username . '-sag';
        $sagId   = $this->module->framework->getSystemSetting($setting) ?? '';
        $sag     = new SAG($this->module, $sagId);
        if ( empty($sagId) || !$sag->sagExists() ) {
            $sagId = $this->module->defaultSagId;
            $sag->setSagId($sagId);
            $this->setUserSag($sagId);
        }
        return $sag;
    }
}