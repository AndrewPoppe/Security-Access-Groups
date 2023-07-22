<?php

namespace YaleREDCap\SecurityAccessGroups;

class AjaxHandler
{
    private SecurityAccessGroups $module;
    private array $params;
    private string $action;
    private static array $generalActions = [];
    private static array $adminActions = [
        'assignSag',
        'deleteAlert',
        'deleteSag',
        'editSag',
        'expireUsers',
        'getAlert',
        'getAlerts',
        'getProjectReport',
        'getProjectUsers',
        'getSags',
        'getUserReport',
        'getUserAndProjectReport',
        'getUsers',
        'importCsvSags',
        'importCsvUsers',
        'replacePlaceholders',
        'sendAlerts'
    ];

    public function __construct(SecurityAccessGroups $module, array $params)
    {
        $this->module = $module;
        $this->params = $this->module->framework->escape($params);
        $this->action = $this->module->framework->escape($this->params['action']);
    }

    // TODO: validate that the methods are being called from the correct page? Does it matter?
    public function handleAjax()
    {
        $action = $this->module->escape($this->action);
        if ( in_array($action, self::$generalActions, true) ) {
            return $this->$action();
        } elseif ( in_array($action, self::$adminActions, true) ) {
            if ( !$this->module->framework->isSuperUser() ) {
                throw new AjaxException("User is not a super user", 403);
            }
            return $this->$action();
        } else {
            throw new AjaxException("Invalid action: {$action}", 400);
        }

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

    // Reports

    private function getProjectReport()
    {
        $includeExpired = filter_var($this->params['payload']['includeExpired'], FILTER_VALIDATE_BOOL) ?? false;
        $results        = $this->module->getProjectsWithNoncompliantUsers($includeExpired);
        return json_encode([ 'data' => $results ]);
    }

    private function getUserReport()
    {
        $includeExpired = filter_var($this->params['payload']['includeExpired'], FILTER_VALIDATE_BOOL) ?? false;
        $users          = $this->module->getAllUsersWithNoncompliantRights($includeExpired);
        return json_encode([ 'data' => $users ]);
    }

    private function getUserAndProjectReport()
    {
        $includeExpired = filter_var($this->params['payload']['includeExpired'], FILTER_VALIDATE_BOOL) ?? false;
        $results        = $this->module->getAllUsersAndProjectsWithNoncompliantRights($includeExpired);
        return json_encode([ 'data' => $results ]);
    }

    // SAGs

    private function deleteSag()
    {
        $sagId = filter_var($this->params['payload']['sag_id'], FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? '';
        $sag = new SAG($this->module, $sagId);
        if ( empty($sagId) || !$sag->sagExists() ) {
            return json_encode([
                'status'  => 'error',
                'message' => 'The provided SAG ID was bad.'
            ]);
        }

        $sag->throttleDeleteSag();
        return json_encode([
            'status'  => 'ok',
            'message' => 'SAG deleted'
        ]);
    }

    private function editSag()
    {
        $subaction = $this->params['payload']['subaction'];

        // We're submitting the form to add/edit the SAG
        if ( $subaction == 'save' ) {
            $data    = filter_var_array($this->params['payload'], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
            $sagId   = $data['sag_id'] ?? $this->module->generateNewSagId();
            $sagName = $data['sag_name_edit'];
            $sag = new SAG($this->module, $sagId, $sagName);
            $newSag  = $data['newSag'];
            $this->module->framework->log('thing3', [
                'data' => json_encode($data),
                'sagId' => $sagId,
                'sagName' => $sagName,
                'newSag' => $newSag,
                'sag_exists' => $sag->sagExists() ? 'true' : 'false',
                'sag_sagId' => $sag->sagId,
                'sag_sagName' => $sag->sagName,
            ]);
            if ( $newSag == 1 ) {
                $sag->throttleSaveSag(json_encode($data));
            } else {
                $sag->throttleUpdateSag(json_encode($data));
            }
            return json_encode([ 'status' => 'ok', 'sagId' => $sagId ]);
        }

        // We're asking for the add/edit SAG form contents
        if ( $subaction == 'get' ) {
            $newSag  = filter_var($this->params['payload']['newSag'], FILTER_VALIDATE_BOOLEAN);
            $sagId   = filter_var($this->params['payload']['sag_id'], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
            $sagName = filter_var($this->params['payload']['sag_name'], FILTER_SANITIZE_FULL_SPECIAL_CHARS);

            if ( $newSag === true ) {
                $rights = $this->module->getDefaultRights();
                $newSag = true;
            } else {
                $sag = new SAG($this->module, $sagId);
                $rights   = $sag->getSagRights();
                $sagName  = $sag->sagName;
                $newSag   = false;
                $this->module->framework->log('thing', [
                    'sag_id'      => $sagId,
                    'sag_name'    => $sagName,
                    'permissions' => json_encode($rights),
                    'newSag' => $newSag
                ]);
            }
            $sagEditForm = new SagEditForm(
                $this->module,
                $rights,
                $newSag,
                $sagName,
                $sagId
            );
            return json_encode([ 'form' => $sagEditForm->getForm() ]);
        }
    }

    private function getSags()
    {
        $sags           = $this->module->getAllSags(false, true);
        $allPermissions = $this->module->getDisplayTextForRights(true);

        $sagsForTable = [];
        foreach ( $sags as $index => $sag ) {
            $thisSag['index'] = $index;
            $permissions        = $sag->permissions;
            $thisSag['permissions'] = [];
            foreach ( $allPermissions as $permission => $displayText ) {
                $thisSag['permissions'][$permission] = $permissions[$permission] ?? null;
            }
            $thisSag['sag_id']   = $sag->sagId;
            $thisSag['sag_name'] = $sag->sagName;
            $sagsForTable[] = $thisSag;
        }

        return json_encode([ 'data' => $sagsForTable ]);
    }

    private function importCsvSags()
    {
        $csvString = $this->params['payload']['data'];
        $sagImport = new CsvSAGImport($this->module, $csvString);
        $sagImport->parseCsvString();

        $contentsValid = $sagImport->contentsValid();
        if ( $contentsValid !== true ) {
            return json_encode([
                'status'  => 'error',
                'message' => $sagImport->errorMessages
            ]);
        }

        if ( filter_var($this->params['payload']['confirm'], FILTER_VALIDATE_BOOLEAN) ) {
            return json_encode([
                'status' => 'ok',
                'result' => $sagImport->import()
            ]);
        } else {
            return json_encode([
                'status' => 'ok',
                'table'  => $sagImport->getUpdateTable()
            ]);
        }
    }

    // Users

    private function assignSag()
    {
        $username = filter_var($this->params['payload']['username'], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $sagId      = filter_var($this->params['payload']['sag'], FILTER_SANITIZE_FULL_SPECIAL_CHARS);

        if ( empty($this->module->framework->getUser($username)->getEmail()) ) {
            return json_encode([
                'status'  => 'error',
                'message' => 'Username not found'
            ]);
        }

        $sag = new SAG($this->module, $sagId);
        if ( !$sag->sagExists() ) {
            return json_encode([
                'status'  => 'error',
                'message' => 'SAG not found'
            ]);
        }

        $setting = $username . "-sag";
        $this->module->framework->setSystemSetting($setting, $sagId);
        $this->module->framework->log('Assigned SAG', [ 'user' => $username, 'sag' => $sagId ]);
        return json_encode([
            'status'  => 'ok',
            'message' => 'SAG assigned'
        ]);
    }

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

    private function getUsers()
    {
        $users = $this->module->getAllUserInfo(true);
        return json_encode([ 'data' => $users ]);
    }

    private function importCsvUsers()
    {
        $userImport = new CsvUserImport($this->module, $this->params['payload']['data']);
        $userImport->parseCsvString();

        $contentsValid = $userImport->contentsValid();
        if ( $contentsValid !== true ) {
            return json_encode([
                'status' => 'error',
                'data'   => [
                    "error" => $userImport->errorMessages,
                    "sags"  => $userImport->badSags,
                    "users" => $userImport->badUsers
                ]
            ]);
        }

        if ( filter_var($this->params['payload']['confirm'], FILTER_VALIDATE_BOOLEAN) ) {
            return json_encode([
                'status' => 'ok',
                'data'   => $userImport->import()
            ]);
        } else {
            return json_encode([
                'status' => 'ok',
                'data'   => $userImport->getUpdateTable()
            ]);
        }
    }
}