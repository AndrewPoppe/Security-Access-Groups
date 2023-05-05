<?php
namespace YaleREDCap\SystemUserRights;

require_once 'TextReplacer.php';
use YaleREDCap\SystemUserRights\TextReplacer;

class Alert
{
    private $alertData;
    private $module;
    public function __construct(SystemUserRights $module, array $alertData)
    {
        $this->alertData = $alertData;
        $this->module    = $module;
    }

    public function sendAlerts()
    {
        $alertType = $this->getAlertType();

        $this->module->framework->log('Sending alerts', [
            "alertType"                                         => $alertType,
            "displayFromName"                                   => $this->getDisplayFromName(),
            "fromEmail"                                         => $this->getFromEmail(),
            "emailSubject"                                      => $this->getEmailSubject(),
            "emailSubject-userExpiration-UserRightsHolders"     => $this->getEmailSubjectUserExpirationUserRightsHolders(),
            "emailBody"                                         => $this->getEmailBody(),
            "usersEmailBody"                                    => $this->getUsersEmailBody(),
            "userRightsHoldersEmailBody"                        => $this->getUserRightsHoldersEmailBody(),
            "sendReminder"                                      => $this->getSendReminder(),
            "sendUserNotification"                              => $this->getSendUserNotification(),
            "sendNotification-userExpiration-UserRightsHolders" => $this->getSendNotificationUserExpirationUserRightsHolders(),
            "delayDays"                                         => $this->getDelayDays(),
            "delayDays-expiration"                              => $this->getDelayDaysExpiration(),
            "recipients"                                        => json_encode($this->getRecipients()),
            "reminderSubject"                                   => $this->getReminderSubject(),
            "reminderBody"                                      => $this->getReminderBody(),
            "users"                                             => json_encode($this->getUsers())
        ]);

        if ( $alertType === "users" ) {
            $this->sendUsersAlertsAndScheduleReminders();
        } else if ( $alertType === "userRightsHolders" ) {
            $this->sendUserRightsHoldersAlertsAndScheduleReminders();
        } else if ( $alertType === "expiration" ) {
            $this->sendUserExpirationAlertsAndScheduleReminders();
        }
    }

    private function sendUsersAlertsAndScheduleReminders()
    {
        try {
            $users = $this->getUsers();
            foreach ( $users as $user ) {
                $bodyReplacer    = new TextReplacer($this->module, $this->getEmailBody(), $user);
                $body            = $bodyReplacer->replaceText();
                $subjectReplacer = new TextReplacer($this->module, $this->getEmailSubject(), $user);
                $subject         = $subjectReplacer->replaceText();

                $email_success = \REDCap::email(
                    $user['sag_user_email'],
                    $this->getFromEmail(),
                    $subject,
                    $body,
                    null,
                    null,
                    $this->getDisplayFromName()
                );

                if ( !$email_success ) {
                    throw (new \Exception("Error sending email to " . $user['sag_user_email']));
                }

                $alert_log_id = $this->module->framework->log('ALERT', [
                    "user"            => json_encode($user),
                    "recipient"       => $user['sag_user'],
                    "recipientEmail"  => $user['sag_user_email'],
                    "alertType"       => $this->getAlertType(),
                    "displayFromName" => $this->getDisplayFromName(),
                    "fromEmail"       => $this->getFromEmail(),
                    "emailSubject"    => $subject,
                    "emailBody"       => $body,
                ]);

                if ( $this->getSendReminder() ) {
                    $reminderSubjectReplacer = new TextReplacer($this->module, $this->getReminderSubject(), $user);
                    $reminderSubject         = $reminderSubjectReplacer->replaceText();
                    $reminderBodyReplacer    = new TextReplacer($this->module, $this->getReminderBody(), $user);
                    $reminderBody            = $reminderBodyReplacer->replaceText();
                    $reminderDate            = date('Y-m-d', strtotime("+" . $this->getDelayDays() . " days"));

                    $this->module->framework->log('REMINDER', [
                        "user"            => json_encode($user),
                        "recipient"       => $user['sag_user'],
                        "recipientEmail"  => $user['sag_user_email'],
                        "alertType"       => $this->getAlertType(),
                        "displayFromName" => $this->getDisplayFromName(),
                        "fromEmail"       => $this->getFromEmail(),
                        "emailSubject"    => $reminderSubject,
                        "emailBody"       => $reminderBody,
                        "reminderDate"    => $reminderDate,
                        "alert_log_id"    => $alert_log_id
                    ]);
                }
            }
        } catch ( \Throwable $e ) {
            $this->module->framework->log("Error sending users alert", [ 'error' => $e->getMessage() ]);
        }
    }

    private function sendUserRightsHoldersAlertsAndScheduleReminders()
    {
        try {
            $recipients = $this->getRecipients();
            $userData   = $this->convertUsersToData();
            foreach ( $recipients as $recipient ) {
                $recipientEmail = $this->module->framework->getUser($recipient)->getEmail();

                $bodyReplacer    = new TextReplacer($this->module, $this->getEmailBody(), $userData);
                $body            = $bodyReplacer->replaceText();
                $subjectReplacer = new TextReplacer($this->module, $this->getEmailSubject(), $userData);
                $subject         = $subjectReplacer->replaceText();



                $email_success = \REDCap::email(
                    $recipientEmail,
                    $this->getFromEmail(),
                    $subject,
                    $body,
                    null,
                    null,
                    $this->getDisplayFromName()
                );

                if ( !$email_success ) {
                    throw (new \Exception("Error sending email to " . $recipientEmail));
                }

                $alert_log_id = $this->module->framework->log('ALERT', [
                    "recipient"        => $recipient,
                    "recipientAddress" => $recipientEmail,
                    "users"            => json_encode($this->getUsers()),
                    "alertType"        => $this->getAlertType(),
                    "displayFromName"  => $this->getDisplayFromName(),
                    "fromEmail"        => $this->getFromEmail(),
                    "emailSubject"     => $subject,
                    "emailBody"        => $body,
                ]);

                if ( $this->getSendReminder() ) {
                    $reminderSubjectReplacer = new TextReplacer($this->module, $this->getReminderSubject(), $userData);
                    $reminderSubject         = $reminderSubjectReplacer->replaceText();
                    $reminderBodyReplacer    = new TextReplacer($this->module, $this->getReminderBody(), $userData);
                    $reminderBody            = $reminderBodyReplacer->replaceText();
                    $reminderDate            = date('Y-m-d', strtotime("+" . $this->getDelayDays() . " days"));

                    $this->module->framework->log('REMINDER', [
                        "recipient"        => $recipient,
                        "recipientAddress" => $recipientEmail,
                        "users"            => json_encode($this->getUsers()),
                        "alertType"        => $this->getAlertType(),
                        "displayFromName"  => $this->getDisplayFromName(),
                        "fromEmail"        => $this->getFromEmail(),
                        "emailSubject"     => $reminderSubject,
                        "emailBody"        => $reminderBody,
                        "reminderDate"     => $reminderDate,
                        "alert_log_id"     => $alert_log_id
                    ]);
                }
            }
        } catch ( \Throwable $e ) {
            $this->module->framework->log("Error sending user rights holder alert", [ 'error' => $e->getMessage() ]);
        }
    }

    private function sendUserExpirationAlertsAndScheduleReminders()
    {
        if ( $this->getSendUserNotification() ) {
            $this->sendUserExpirationUserAlertsAndScheduleReminders();
        }

        if ( $this->getSendNotificationUserExpirationUserRightsHolders() ) {
            $this->sendUserExpirationUserRightsHoldersAlertsAndScheduleReminders();
        }
    }

    private function sendUserExpirationUserAlertsAndScheduleReminders()
    {
        try {
            $users = $this->getUsers();
            foreach ( $users as $user ) {
                $user['sag_expiration_date'] = $this->getSagExpirationDate();
                $bodyReplacer                = new TextReplacer($this->module, $this->getUsersEmailBody(), $user);
                $body                        = $bodyReplacer->replaceText();
                $this->module->log('body', [ 'body' => $body ]);
                $subjectReplacer = new TextReplacer($this->module, $this->getEmailSubject(), $user);
                $subject         = $subjectReplacer->replaceText();

                $email_success = \REDCap::email(
                    $user['sag_user_email'],
                    $this->getFromEmail(),
                    $subject,
                    $body,
                    null,
                    null,
                    $this->getDisplayFromName()
                );

                if ( !$email_success ) {
                    throw (new \Exception("Error sending expiration email to " . $user['sag_user_email']));
                }

                $this->module->framework->log('ALERT', [
                    "user"            => json_encode($user),
                    "recipient"       => $user['sag_user'],
                    "recipientEmail"  => $user['sag_user_email'],
                    "alertType"       => $this->getAlertType(),
                    "displayFromName" => $this->getDisplayFromName(),
                    "fromEmail"       => $this->getFromEmail(),
                    "emailSubject"    => $subject,
                    "emailBody"       => $body,
                    "expirationDate"  => $this->getSagExpirationDate()
                ]);

            }
        } catch ( \Throwable $e ) {
            $this->module->framework->log("Error sending expiration users alert", [ 'error' => $e->getMessage() ]);
        }

    }

    private function sendUserExpirationUserRightsHoldersAlertsAndScheduleReminders()
    {
        try {
            $recipients                      = $this->getRecipients();
            $userData                        = $this->convertUsersToData();
            $userData["sag_expiration_date"] = $this->getSagExpirationDate();
            foreach ( $recipients as $recipient ) {
                $recipientEmail = $this->module->framework->getUser($recipient)->getEmail();

                $bodyReplacer    = new TextReplacer($this->module, $this->getUserRightsHoldersEmailBody(), $userData);
                $body            = $bodyReplacer->replaceText();
                $subjectReplacer = new TextReplacer($this->module, $this->getEmailSubjectUserExpirationUserRightsHolders(), $userData);
                $subject         = $subjectReplacer->replaceText();

                $email_success = \REDCap::email(
                    $recipientEmail,
                    $this->getFromEmail(),
                    $subject,
                    $body,
                    null,
                    null,
                    $this->getDisplayFromName()
                );

                if ( !$email_success ) {
                    throw (new \Exception("Error sending expiration email to " . $recipientEmail));
                }

                $this->module->framework->log('ALERT', [
                    "recipient"        => $recipient,
                    "recipientAddress" => $recipientEmail,
                    "users"            => json_encode($this->getUsers()),
                    "alertType"        => $this->getAlertType(),
                    "displayFromName"  => $this->getDisplayFromName(),
                    "fromEmail"        => $this->getFromEmail(),
                    "emailSubject"     => $subject,
                    "emailBody"        => $body,
                    "expirationDate"   => $this->getSagExpirationDate()
                ]);
            }
        } catch ( \Throwable $e ) {
            $this->module->framework->log("Error sending user expiration alert to user rights holder", [ 'error' => $e->getMessage() ]);
        }
    }

    private function convertUsersToData()
    {
        $users = $this->getUsers();
        $data  = [];
        foreach ( $users as $user ) {
            $data['sag_users'][]     = $user['sag_user'];
            $data['sag_fullnames'][] = $user['sag_user_fullname'];
            $data['sag_emails'][]    = $user['sag_user_email'];
            $data['sag_rights'][]    = $user['sag_user_rights'];
        }
        return $data;
    }

    // Getters
    public function getAlertType()
    {
        return $this->alertData['alertType'];
    }

    public function getDisplayFromName()
    {
        return $this->alertData['displayFromName'];
    }

    public function getFromEmail()
    {
        return $this->alertData['fromEmail'];
    }

    public function getEmailSubject()
    {
        return $this->alertData['emailSubject'];
    }

    public function getEmailSubjectUserExpirationUserRightsHolders()
    {
        return $this->alertData['emailSubject-userExpiration-UserRightsHolders'];
    }

    public function getEmailBody()
    {
        return $this->alertData['emailBody'];
    }

    public function getUsersEmailBody()
    {
        return $this->alertData['usersEmailBody'];
    }

    public function getUserRightsHoldersEmailBody()
    {
        return $this->alertData['userRightsHoldersEmailBody'];
    }

    public function getSendReminder()
    {
        return $this->alertData['sendReminder'];
    }

    public function getSendUserNotification()
    {
        return $this->alertData['sendUserNotification'];
    }

    public function getSendNotificationUserExpirationUserRightsHolders()
    {
        return $this->alertData['sendNotification-userExpiration-UserRightsHolders'];
    }

    public function getDelayDays()
    {
        return $this->alertData['delayDays'];
    }

    public function getDelayDaysExpiration()
    {
        return $this->alertData['delayDays-expiration'];
    }

    public function getRecipients()
    {
        return $this->alertData['recipients'];
    }

    public function getReminderSubject()
    {
        return $this->alertData['reminderSubject'];
    }

    public function getReminderBody()
    {
        return $this->alertData['reminderBody'];
    }

    public function getUsers()
    {
        return $this->alertData['users'];
    }

    public function getSagExpirationDate()
    {
        return $this->alertData['sag_expiration_date'];
    }

}