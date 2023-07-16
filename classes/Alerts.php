<?php

namespace YaleREDCap\SecurityAccessGroups;

class Alerts
{

    private SecurityAccessGroups $module;
    private string $adminUsername;

    public function __construct(SecurityAccessGroups $module)
    {
        $this->module = $module;
        $this->adminUsername = $this->module->framework->getUser()->getUsername();
    }

    private function getEmailOptions() : string
    {
        $emailAddresses = $this->getEmailAddresses();
        $emailOptions   = '';
        foreach ( $emailAddresses as $key => $emailAddress ) {
            $emailOptions .= '<option ' . ($key == 0 ? 'selected' : '') . '>' . $emailAddress . '</option>';
        }
        return $emailOptions;
    }

    private function getPlaceholdersText($type = 'user', $expiration = false) : string
    {
        if ( $type == 'user' ) {
            $placeholders = $this->getPlaceholdersUsers($expiration);
        } else {
            $placeholders = $this->getPlaceholdersUserRightsHolders($expiration);
        }
        $placeholdersText = '';
        foreach ( $placeholders as $placeholder => $description ) {
            $placeholdersText .= '<tr><td><code class="dataPlaceholder">[' . $placeholder . ']</code></td><td>' . $description . '</td></tr>';
        }
        return $placeholdersText;
    }

    private function getUserRightsHoldersText() : string
    {
        $userRightsHolders = $this->module->getUserRightsHolders($this->module->getProjectId());
        $result            = '';
        foreach ( $userRightsHolders as $userRightsHolder ) {
            $result .= '<tr data-user="' . $userRightsHolder["username"] . '">';
            $result .= '<td class="align-middle user-rights-holder-selector" style="vertical-align: middle !important;">';
            $result .= '<input style="display:block; margin: 0 auto;" type="checkbox">';
            $result .= '</td><td>' . $userRightsHolder["username"] . '</td>';
            $result .= '<td>' . $userRightsHolder["fullname"] . '</td>';
            $result .= '<td>' . $userRightsHolder["email"] . '</td></tr>';
        }
        return $result;
    }

    
    public function getUserEmailModal() : void
    {
        $html = file_get_contents($this->module->framework->getSafePath('html/userEmailModal.html'));
        $html = str_replace('{{EMAIL_OPTIONS}}', $this->getEmailOptions(), $html);
        $html = str_replace('{{PLACEHOLDERS}}', $this->getPlaceholdersText('user'), $html);
        echo $html;
    }

    
    public function getUserRightsHoldersEmailModal() : void
    {
        $html = file_get_contents($this->module->framework->getSafePath('html/userRightsHoldersEmailModal.html'));
        $html = str_replace('{{EMAIL_OPTIONS}}', $this->getEmailOptions(), $html);
        $html = str_replace('{{PLACEHOLDERS}}', $this->getPlaceholdersText('userRightsHolders'), $html);
        $html = str_replace('{{USER_RIGHTS_HOLDERS}}', $this->getUserRightsHoldersText(), $html);
        echo $html;
    }


    public function getUserExpirationModal()
    {
        $html = file_get_contents($this->module->framework->getSafePath('html/userExpirationModal.html'));
        $html = str_replace('{{EMAIL_OPTIONS}}', $this->getEmailOptions(), $html);
        $html = str_replace('{{PLACEHOLDERS_USERS}}', $this->getPlaceholdersText('user', true), $html);
        $html = str_replace('{{PLACEHOLDERS_USER_RIGHTS_HOLDERS}}', $this->getPlaceholdersText('userRightsHolders', true), $html);
        $html = str_replace('{{USER_RIGHTS_HOLDERS}}', $this->getUserRightsHoldersText(), $html);
        echo $html;
    }



    public function getEmailPreviewModal()
    {
        ?>
<div class="modal" id="emailPreview" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-body">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
<?php
    }

    public function getPlaceholdersUserRightsHolders($expiration = false) : array
    {
        $placeholders = [
            'sag-users'            => 'A formatted list of usernames',
            'sag-user-fullnames'   => 'A formatted list of users\' full names',
            'sag-user-emails'      => 'A formatted list of user emails',
            'sag-users-table'      => 'A formatted table of usernames, full names, and email addresses',
            'sag-users-table-full' =>
            'A formatted table of usernames, full names, email addresses, and non-compliant rights',
            'sag-project-title'    => 'The title of the project',
        ];

        if ( $expiration ) {
            $placeholders['sag-expiration-date'] = 'The date the users will be expired from the project';
        }

        return $placeholders;
    }

    public function getPlaceholdersUsers($expiration = false) : array
    {
        $placeholders = [
            'sag-user'          => 'The user\'s username',
            'sag-user-fullname' => 'The user\'s full name',
            'sag-user-email'    => 'The user\'s email address',
            'sag-rights'        => '<span>A formatted list of the rights that do not</span>' .
            '<br><span>conform with the user\'s security access group.</span>',
            'sag-project-title' => 'The title of the project',
        ];

        if ( $expiration ) {
            $placeholders['sag-expiration-date'] = 'The date the user will be expired from the project';
        }

        return $placeholders;
    }
    private function getEmailAddresses() : array
    {
        $emails   = [];
        $sql      = 'SELECT user_email, user_email2, user_email3 FROM redcap_user_information WHERE username = ?';
        $result   = $this->module->query($sql, [ $this->adminUsername ]);
        $emailRow = $result->fetch_assoc();
        foreach ( $emailRow as $email ) {
            if ( !empty($email) ) {
                $emails[] = $email;
            }
        }
        $universalEmail = $this->getUniversalEmailAddress();
        if ( !empty($universalEmail) ) {
            $emails[] = $universalEmail;
        }
        return $this->module->framework->escape($emails);
    }

    private function getUniversalEmailAddress()
    {
        $sql    = 'SELECT value FROM redcap_config WHERE field_name = \'from_email\'';
        $result = $this->module->query($sql, []);
        return $this->module->framework->escape($result->fetch_assoc()['value']);
    }

    public function sendUserReminders($projectId)
    {
        $reminders = $this->getUserRemindersToSend($projectId);
        foreach ( $reminders as $reminder ) {
            // Send Alert
            $emailSuccess = \REDCap::email(
                $reminder['to'],
                $reminder['from'],
                $reminder['subject'],
                $reminder['body'],
                null,
                null,
                $reminder['displayFromName']
            );

            if ( !$emailSuccess ) {
                $this->module->framework->log(
                    'Failure sending reminder',
                    [
                        'reminder_id' => $reminder['reminder_log_id'],
                        'to'          => $reminder['to'],
                        'from'        => $reminder['from'],
                        'project_id'  => $projectId
                    ]
                );
                $this->module->updateLog($reminder['reminder_log_id'], [ 'status' => 'error' ]);
            } else {
                $this->module->framework->log('Reminder sent', [
                    'reminder_id' => $reminder['reminder_log_id'],
                    'to'          => $reminder['to'],
                    'from'        => $reminder['from'],
                    'project_id'  => $projectId
                ]);
                $this->module->updateLog($reminder['reminder_log_id'], [
                    'sentTimestamp' => time(),
                    'status'        => 'sent'
                ]);
            }

        }
    }

    private function getUserRemindersToSend($projectId) : array
    {
        $sql             = 'SELECT log_id,
        user,
        users,
         alertType,
        reminderDate,
        fromEmail,
        displayFromName,
        emailBody,
        emailSubject,
        alert_log_id
        WHERE message = \'REMINDER\'
        AND sentTimestamp < 0
        AND status = \'scheduled\'
        AND reminderDate < ?
        AND project_id = ?';
        $params          = [ time(), $projectId ];
        $result          = $this->module->queryLogs($sql, $params);
        $remindersToSend = [];
        while ( $row = $result->fetch_assoc() ) {
            if ( empty($row['users']) ) {
                $users = [ json_decode($row['user'], true) ];
            } else {
                $users = json_decode($row['users'], true);
            }
            foreach ( $users as $user ) {
                $thisAlert                    = [];
                $thisAlert['to']              = \REDCap::escapeHtml($user['sag_user_email']);
                $thisAlert['from']            = \REDCap::escapeHtml($row['fromEmail']);
                $thisAlert['displayFromName'] = \REDCap::escapeHtml($row['displayFromName']);
                $thisAlert['subject']         = \REDCap::filterHtml($row['emailSubject']);
                $thisAlert['body']            = \REDCap::filterHtml($row['emailBody']);
                $thisAlert['alert_log_id']    = intval($row['alert_log_id']);
                $thisAlert['reminder_log_id'] = intval($row['log_id']);
                $remindersToSend[]            = $thisAlert;
            }
        }
        return $remindersToSend;
    }

    private function getRawAlerts($projectId)
    {
        $sql    = 'SELECT log_id,
        timestamp,
        message \'Type\',
        user,
        users,
        alertType \'Alert Type\',
        recipient,
        recipientAddress,
        reminderDate,
        fromEmail,
        emailBody,
        emailSubject,
        sentTimestamp,
        status  WHERE message IN (\'ALERT\', \'REMINDER\') AND project_id = ?';
        $params = [ $projectId ];
        $result = $this->module->framework->queryLogs($sql, $params);
        $alerts = [];
        while ( $row = $result->fetch_assoc() ) {
            $alerts[] = $row;
        }
        return $alerts;
    }

    private function getAlertUsers($alert)
    {
        $users      = [];
        $usersArray = isset($alert['users']) ?
            json_decode($alert['users'], true) :
            [ json_decode($alert['user'], true) ];
        foreach ( $usersArray as $user ) {
            $users[] = [
                'username' => $user['sag_user'],
                'name'     => $user['sag_user_fullname'],
                'email'    => $user['sag_user_email']
            ];
        }
        return $this->module->framework->escape($users);
    }

    private function getAlertRecipient($alert)
    {
        $thisRecipient = \REDCap::escapeHtml($alert['recipient']);
        return $this->module->getUserInfo($thisRecipient);
    }

    /**
     * Grab array of all alerts and reminders in the project, sent and scheduled
     *
     * @param mixed $projectId - if null, current project is used
     *
     */
    public function getAlerts($projectId = null)
    {
        if ( empty($projectId) ) {
            $projectId = $this->module->framework->getProjectId();
        }
        $rawAlerts = $this->getRawAlerts($projectId);
        $alerts    = [];
        foreach ( $rawAlerts as $row ) {
            $thisAlert       = [];
            $users           = $this->getAlertUsers($row);
            $recipient       = $this->getAlertRecipient($row);
            $thisAlert['id'] = \REDCap::escapeHtml($row['log_id']);
            if ( $row['Type'] === 'ALERT' ) {
                $thisAlert['sendTime'] = $row['sentTimestamp'];
                $thisAlert['reminder'] = false;
            } else {
                $thisAlert['sendTime'] = $row['sentTimestamp'] > 0 ? $row['sentTimestamp'] : $row['reminderDate'];
                $thisAlert['reminder'] = true;
            }
            $thisAlert['sendTime']  = \REDCap::escapeHtml($thisAlert['sendTime']);
            $thisAlert['alertType'] = \REDCap::escapeHtml($row['Alert Type']);
            $thisAlert['users']     = $users;
            $thisAlert['recipient'] = $recipient;
            $thisAlert['status']    = \REDCap::escapeHtml($row['status']) ?? '';
            $thisAlert['to']        = $row['recipientAddress'];
            $thisAlert['from']      = \REDCap::escapeHtml($row['fromEmail']);
            $thisAlert['subject']   = \REDCap::filterHtml($row['emailSubject']);
            $thisAlert['body']      = \REDCap::filterHtml($row['emailBody']);

            $alerts[] = $thisAlert;
        }
        return $alerts;
    }

    public function getAlertById($alertId)
    {
        $projectId = $this->module->framework->getProjectId();
        $alerts    = $this->getAlerts($projectId);
        $alert     = null;
        foreach ( $alerts as $thisAlert ) {
            if ( $thisAlert['id'] == $alertId ) {
                $alert = $thisAlert;
            }
        }
        if ( $alert ) {
            $alert['table'] = $this->getAlertTable($alert);
        }
        return $alert;
    }

    private function getAlertTable(array $alert)
    {
        $endRow = '</td></tr>';
        $table  = '<table aria-label=\'alerts\' class=\'table bg-white\' style=\'border:1px solid #dee2e6\'>';
        $table .= '<tr><th>From:</th><td>' . \REDCap::filterHtml($alert['from']) . $endRow;
        $table .= '<tr><th>To:</th><td>' . \REDCap::filterHtml($alert['to']) . $endRow;
        $table .= '<tr><th>Subject:</th><td>' . \REDCap::filterHtml($alert['subject']) . $endRow;
        $table .= '<tr><th>Message:</th><td>' . \REDCap::filterHtml($alert['body']) . $endRow;
        $table .= '</table>';
        return $table;
    }

    public function deleteAlert($alertId)
    {
        try {
            $sql    = 'log_id = ?';
            $result = $this->module->framework->removeLogs($sql, [ $alertId ]);
            if ( !$result ) {
                throw new \Error('Error deleting alert');
            }
            $this->module->framework->log('Alert deleted', [ 'alertId' => $alertId ]);
            return $result;
        } catch ( \Throwable $e ) {
            $this->module->framework->log('Error deleting alert', [
                'error'   => $e->getMessage(),
                'alertId' => $alertId
            ]);
            return false;
        }
    }
}